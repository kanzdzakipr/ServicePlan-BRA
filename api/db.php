<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

$authenticationOptional = defined('API_AUTH_OPTIONAL') && API_AUTH_OPTIONAL === true;
api_bootstrap_request($authenticationOptional);

final class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $environment = app_environment();
        $isLocal = in_array($environment, ['local', 'development', 'test'], true);

        $host = trim((string) (getenv('DB_HOST') ?: ($isLocal ? '127.0.0.1' : '')));
        $port = trim((string) (getenv('DB_PORT') ?: '3306'));
        $database = trim((string) (getenv('DB_NAME') ?: ($isLocal ? 'serviceplan_bra' : '')));
        $username = trim((string) (getenv('DB_USER') ?: ($isLocal ? 'root' : '')));
        $passwordValue = getenv('DB_PASSWORD');
        if ($passwordValue === false) {
            // Temporary environment-name compatibility; no credential fallback exists.
            $passwordValue = getenv('DB_PASS');
        }
        $password = $passwordValue !== false ? (string) $passwordValue : ($isLocal ? '' : '');

        if ($host === '' || $database === '' || $username === '' || (!$isLocal && $passwordValue === false)) {
            error_log('Database configuration is incomplete for APP_ENV=' . $environment);
            api_json_response(500, [
                'status' => 'error',
                'code' => 'DATABASE_NOT_CONFIGURED',
                'message' => 'Konfigurasi database server belum lengkap.',
            ]);
        }

        if (!preg_match('/^\d{1,5}$/', $port) || (int) $port < 1 || (int) $port > 65535) {
            api_json_response(500, [
                'status' => 'error',
                'code' => 'DATABASE_CONFIG_INVALID',
                'message' => 'Konfigurasi database server tidak valid.',
            ]);
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        try {
            self::$instance = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getCode());
            api_json_response(503, [
                'status' => 'error',
                'code' => 'DATABASE_UNAVAILABLE',
                'message' => 'Layanan data sementara tidak tersedia.',
            ]);
        }

        $authenticationOptional = defined('API_AUTH_OPTIONAL') && API_AUTH_OPTIONAL === true;
        if (!$authenticationOptional) {
            api_refresh_authenticated_user(self::$instance);
            api_authorize_current_route();
        }

        return self::$instance;
    }
}
