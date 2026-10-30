<?php
define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
$pdo = Database::getInstance();

echo "=== WORK ORDERS SUMMARY POST-BATCH 28 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total_wo, MIN(trouble_at) as min_trouble, MAX(trouble_at) as max_trouble, MIN(wo_id) as min_id, MAX(wo_id) as max_id FROM work_orders");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- DISTRIBUTION BY MONTH ---\n";
$stmt = $pdo->query("SELECT DATE_FORMAT(trouble_at, '%Y-%m') as ym, COUNT(*) as cnt FROM work_orders GROUP BY ym ORDER BY ym");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Month: " . ($r['ym'] ?? 'NULL') . " -> " . $r['cnt'] . " WOs\n";
}

echo "\n--- DISTRIBUTION BY STATUS ---\n";
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM work_orders GROUP BY status");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Status: " . ($r['status'] ?? 'NULL') . " -> " . $r['cnt'] . " WOs\n";
}

echo "\n--- PARTS REQUIREMENT STATS ---\n";
$stmt = $pdo->query("SELECT parts_requirement, COUNT(*) as cnt FROM work_orders GROUP BY parts_requirement");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Parts Requirement: " . ($r['parts_requirement'] ?? 'NULL') . " -> " . $r['cnt'] . " WOs\n";
}

