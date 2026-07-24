<?php
session_start();
$app = require __DIR__ . '/../config/app.php';
$db = require __DIR__ . '/../config/database.php';
date_default_timezone_set($app['timezone']);

try {
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa config/database.php dan pastikan MySQL Laragon aktif. Detail: ' . htmlspecialchars($e->getMessage()));
}

require_once __DIR__ . '/helpers.php';
