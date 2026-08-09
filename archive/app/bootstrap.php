<?php
declare(strict_types=1);

$environment = strtolower(trim((string) (getenv('APP_ENV') ?: 'production')));
$isProduction = in_array($environment, ['production', 'live'], true);
$forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$sessionSavePath = trim((string) (getenv('SESSION_SAVE_PATH') ?: ''));
if ($sessionSavePath !== '' && is_dir($sessionSavePath) && is_writable($sessionSavePath)) {
    ini_set('session.save_path', $sessionSavePath);
}
if ($isProduction) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
session_name((string) (getenv('ARCHIVE_SESSION_NAME') ?: 'BRAARCHIVESESSID'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/archive',
    'domain' => '',
    'secure' => $isProduction || $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$now = time();
$idleTimeout = max(300, (int) (getenv('SESSION_IDLE_TIMEOUT') ?: 1800));
$absoluteTimeout = max($idleTimeout, (int) (getenv('SESSION_ABSOLUTE_TIMEOUT') ?: 28800));
if (!empty($_SESSION['user'])) {
    $createdAt = (int) ($_SESSION['created_at'] ?? $now);
    $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);
    if (($now - $lastActivity) > $idleTimeout || ($now - $createdAt) > $absoluteTimeout) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
$_SESSION['created_at'] = (int) ($_SESSION['created_at'] ?? $now);
$_SESSION['last_activity'] = $now;

$app = require __DIR__ . '/../config/app.php';
date_default_timezone_set($app['timezone']);

try {
    $db = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    error_log('Archive database bootstrap failed: ' . $e->getCode());
    http_response_code(503);
    exit('Layanan data sementara tidak tersedia.');
}

require_once __DIR__ . '/helpers.php';
