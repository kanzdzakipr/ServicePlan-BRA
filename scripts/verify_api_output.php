<?php
define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
$db = Database::getInstance();

echo "=== VERIFIKASI DATA MASTER ASSET (DATABASE & PARSING) ===\n\n";

$testUnits = ['DT-00071', 'DT-00063', 'DT 02', 'SD-00001', 'EX-00001', 'DT-00073', 'DT-00062'];
$inClause = implode(',', array_fill(0, count($testUnits), '?'));

$stmt = $db->prepare("SELECT asset_id, hull_number, make_model, warranty, chassis_number, status, serial_number FROM assets WHERE asset_id IN ($inClause) OR hull_number IN ($inClause)");
$stmt->execute(array_merge($testUnits, $testUnits));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo sprintf(
        "ID: %-10s | Lambung: %-10s | Merek/Model: %-18s | GARANSI: %-15s | NO. RANGKA: %s\n",
        $r['asset_id'],
        $r['hull_number'] ?? '-',
        $r['make_model'] ?? '-',
        $r['warranty'] ?? '-',
        $r['chassis_number'] ?? '(kosong)'
    );
}

echo "\n--- STATISTIK KESELURUHAN ---\n";
$totalAssets = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$withWarranty = $db->query("SELECT COUNT(*) FROM assets WHERE warranty IS NOT NULL AND warranty != ''")->fetchColumn();
$withChassis = $db->query("SELECT COUNT(*) FROM assets WHERE chassis_number IS NOT NULL AND chassis_number != ''")->fetchColumn();
$warrantyBreakdown = $db->query("SELECT warranty, COUNT(*) as cnt FROM assets GROUP BY warranty ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

echo "Total Unit Aset: $totalAssets\n";
echo "Unit dengan Garansi terisi: $withWarranty\n";
echo "Unit dengan No. Rangka terisi: $withChassis\n";
echo "\nBreakdown Nilai Garansi:\n";
foreach ($warrantyBreakdown as $wb) {
    echo sprintf("  - %-25s: %d unit\n", $wb['warranty'] ?? '(NULL)', $wb['cnt']);
}
