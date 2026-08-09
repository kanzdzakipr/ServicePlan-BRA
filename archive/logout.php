<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

verify_csrf();
audit($pdo, 'LOGOUT', 'user', (int) current_user()['id'], 'Logout berhasil');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?: '/archive',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool) ($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
session_destroy();
redirect('login.php');
