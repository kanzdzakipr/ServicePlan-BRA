<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/CostLedger.php';

$host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
$dbName = (string) (getenv('DB_NAME') ?: 'u646470441_ServicePlanBRA');
if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true) || $dbName !== 'u646470441_ServicePlanBRA') {
    throw new RuntimeException('Script ini hanya untuk database Laragon lokal yang ditentukan.');
}
$db = new PDO(
    "mysql:host={$host};port=3306;dbname={$dbName};charset=utf8mb4",
    (string) (getenv('DB_USER') ?: 'root'),
    (string) (getenv('DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
CostLedger::ensureTables($db);
echo 'Ledger tersedia; jumlah transaksi: ' . $db->query('SELECT COUNT(*) FROM asset_cost_transactions')->fetchColumn() . PHP_EOL;
