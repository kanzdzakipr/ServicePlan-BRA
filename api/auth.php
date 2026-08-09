<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/db.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    if (api_is_authenticated()) {
        api_refresh_authenticated_user($db);
    }
    api_json_response(200, [
        'status' => 'success',
        'authenticated' => api_is_authenticated(),
        'csrf_token' => api_csrf_token(),
        'user' => api_is_authenticated() ? api_current_user() : null,
    ]);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    api_json_response(405, [
        'status' => 'error',
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'Metode tidak didukung.',
    ]);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    api_json_response(400, [
        'status' => 'error',
        'code' => 'INVALID_JSON',
        'message' => 'Payload tidak valid.',
    ]);
}

$action = (string) ($input['action'] ?? 'login');
if ($action === 'logout') {
    api_destroy_session();
    api_json_response(200, ['status' => 'success', 'message' => 'Logout berhasil.']);
}

if ($action !== 'login') {
    api_json_response(400, [
        'status' => 'error',
        'code' => 'INVALID_ACTION',
        'message' => 'Aksi tidak valid.',
    ]);
}

$now = time();
$attemptWindow = 900;
$attempts = is_array($_SESSION['login_attempts'] ?? null) ? $_SESSION['login_attempts'] : [];
$attempts = array_values(array_filter($attempts, static fn($timestamp): bool => ($now - (int) $timestamp) < $attemptWindow));
if (count($attempts) >= 5) {
    api_json_response(429, [
        'status' => 'error',
        'code' => 'LOGIN_RATE_LIMITED',
        'message' => 'Terlalu banyak percobaan login. Coba kembali beberapa saat lagi.',
    ]);
}

$username = trim((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');
if ($username === '' || strlen($username) > 50 || $password === '' || strlen($password) > 4096) {
    api_json_response(422, [
        'status' => 'error',
        'code' => 'INVALID_CREDENTIAL_FORMAT',
        'message' => 'Username atau password tidak sesuai.',
    ]);
}

$stmt = $db->prepare(
    'SELECT user_id, password_hash
     FROM users
     WHERE username = :username AND is_active = 1
     LIMIT 1'
);
$stmt->execute([':username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$usesCompromisedSeedHash = $user
    && hash_equals(
        'eeff4d1bcd7b68413c85d06797b16fc8470aac23a6e828c4406f249400e78ac7',
        hash('sha256', (string) $user['password_hash'])
    );

if (!$user || (app_is_production() && $usesCompromisedSeedHash) || !password_verify($password, (string) $user['password_hash'])) {
    $attempts[] = $now;
    $_SESSION['login_attempts'] = $attempts;
    if ($usesCompromisedSeedHash) {
        error_log('Authentication blocked because the account still uses a compromised seed password hash.');
    }
    error_log('Authentication failed for a submitted username from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    usleep(random_int(150000, 300000));
    api_json_response(401, [
        'status' => 'error',
        'code' => 'INVALID_CREDENTIALS',
        'message' => 'Username atau password tidak sesuai.',
    ]);
}

$context = api_load_user_context($db, (int) $user['user_id']);
if ($context === null) {
    api_json_response(401, [
        'status' => 'error',
        'code' => 'ACCOUNT_DISABLED',
        'message' => 'Username atau password tidak sesuai.',
    ]);
}

if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
    $rehash = $db->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
    $rehash->execute([
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':user_id' => (int) $user['user_id'],
    ]);
}

session_regenerate_id(true);
$_SESSION['auth_user'] = $context;
$_SESSION['created_at'] = $now;
$_SESSION['last_activity'] = $now;
$_SESSION['login_attempts'] = [];
unset($_SESSION['_csrf']);

api_json_response(200, [
    'status' => 'success',
    'message' => 'Login berhasil.',
    'csrf_token' => api_csrf_token(),
    'user' => $context,
]);
