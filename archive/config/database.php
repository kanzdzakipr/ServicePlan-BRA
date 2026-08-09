<?php
declare(strict_types=1);

$environment = strtolower(trim((string) (getenv('APP_ENV') ?: 'production')));
$isLocal = in_array($environment, ['local', 'development', 'test'], true);
$password = getenv('DB_PASSWORD');
if ($password === false) {
    $password = getenv('DB_PASS');
}

$config = [
    'host' => trim((string) (getenv('DB_HOST') ?: ($isLocal ? '127.0.0.1' : ''))),
    'port' => trim((string) (getenv('DB_PORT') ?: '3306')),
    'database' => trim((string) (getenv('ARCHIVE_DB_NAME') ?: (getenv('DB_NAME') ?: ($isLocal ? 'asset_manager' : '')))),
    'username' => trim((string) (getenv('DB_USER') ?: ($isLocal ? 'root' : ''))),
    'password' => $password !== false ? (string) $password : ($isLocal ? '' : null),
    'charset' => 'utf8mb4',
];

if ($config['host'] === '' || $config['database'] === '' || $config['username'] === '' || $config['password'] === null) {
    throw new RuntimeException('Database environment configuration is incomplete.');
}

return $config;
