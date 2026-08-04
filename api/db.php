<?php
/**
 * Database Connection Config for ServicePlan-BRA API
 * Update these credentials with your Hostinger database details later.
 */

// Allow CORS for development (if needed). In production on Hostinger, this might not be needed if frontend and backend are on the same domain.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            // TODO: Ganti dengan detail database Hostinger Anda nanti
            $host = '127.0.0.1';
            $port = '3306';
            $db   = 'u646470441_ServicePlanBRA';
            $user = 'u646470441_ptEClt5jaya'; // User database Hostinger Anda
            $pass = 'OpangGOD123';     // Password database Hostinger Anda
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
                // Untuk produksi, mungkin jangan echo error PDO langsung agar aman
                echo json_encode(["status" => "error", "message" => "Database Connection Error: " . $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}
?>
