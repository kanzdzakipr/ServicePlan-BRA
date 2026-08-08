<?php
/**
 * Database Connection Config for ServicePlan-BRA API
 * Hybrid Environment Support (Hostinger Production & Local Laragon/XAMPP)
 */

// Allow CORS for development & production
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            // 1. Deteksi Lingkungan (Hybrid Hosting vs Local Laragon)
            $appEnv = strtolower((string) (getenv('APP_ENV') ?: ''));
            $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
            $hostName = (string) (parse_url('http://' . $requestHost, PHP_URL_HOST) ?: $requestHost);
            
            $endsWith = static function ($value, $suffix) {
                return $suffix === '' || substr($value, -strlen($suffix)) === $suffix;
            };

            $localHosts = ['localhost', '127.0.0.1', '::1'];
            $isLocalHost = in_array($hostName, $localHosts, true)
                || $endsWith($hostName, '.test')
                || $endsWith($hostName, '.local');

            // Eksekusi via CLI atau domain lokal dianggap sebagai lingkungan lokal
            $isLocal = $appEnv === 'local'
                || $appEnv === 'development'
                || ($appEnv !== 'production' && $appEnv !== 'live' && ($isLocalHost || PHP_SAPI === 'cli'));

            // 2. Konfigurasi Lingkungan
            $localConfig = [
                'host' => '127.0.0.1',
                'port' => '3306',
                'name' => 'u646470441_ServicePlanBRA',
                'user' => 'root',
                'pass' => '',
            ];

            $hostingConfig = [
                'host' => '127.0.0.1',
                'port' => '3306',
                'name' => 'u646470441_ServicePlanBRA',
                'user' => 'u646470441_ptEClt5jaya',
                'pass' => 'OpangGOD123',
            ];

            $config = $isLocal ? $localConfig : $hostingConfig;

            // Environment variable mengutamakan konfigurasi server tanpa perlu mengubah kode
            $host = getenv('DB_HOST') ?: $config['host'];
            $port = getenv('DB_PORT') ?: $config['port'];
            $db   = getenv('DB_NAME') ?: $config['name'];
            $user = getenv('DB_USER') ?: $config['user'];
            $pass = getenv('DB_PASS');
            $pass = $pass !== false ? $pass : $config['pass'];
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Fallback otomatis jika di lokal Laragon memakai nama database serviceplan_bra
                if ($isLocal && $db === 'u646470441_ServicePlanBRA') {
                    try {
                        $fallbackDsn = "mysql:host={$host};port={$port};dbname=serviceplan_bra;charset={$charset}";
                        self::$instance = new PDO($fallbackDsn, $user, $pass, $options);
                        return self::$instance;
                    } catch (PDOException $ex) {
                        // Jika fallback juga gagal, lempar pesan error utama
                    }
                }

                echo json_encode([
                    "status" => "error",
                    "message" => "Database Connection Error (" . ($isLocal ? 'Lokal Laragon' : 'Hosting Hostinger') . "): " . $e->getMessage()
                ]);
                exit;
            }
        }
        return self::$instance;
    }
}
?>
