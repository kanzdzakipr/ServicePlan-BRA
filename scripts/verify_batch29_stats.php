<?php
define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
$pdo = Database::getInstance();

echo "=== WORK ORDERS SUMMARY POST-BATCH 29 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total_wo, MIN(trouble_at) as min_trouble, MAX(trouble_at) as max_trouble FROM work_orders");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- DISTRIBUTION BY MONTH ---\n";
$stmt = $pdo->query("SELECT DATE_FORMAT(trouble_at, '%Y-%m') as ym, COUNT(*) as cnt FROM work_orders GROUP BY ym ORDER BY ym");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Month: " . ($r['ym'] ?? 'NULL') . " -> " . $r['cnt'] . " WOs\n";
}

echo "\n--- ASSET STATUS DISTRIBUTION ---\n";
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM assets GROUP BY status");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Status: " . ($r['status'] ?? 'NULL') . " -> " . $r['cnt'] . " assets\n";
}

