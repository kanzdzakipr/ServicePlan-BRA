<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();
$passed = 0;
$failed = 0;

function f3bAssert(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$message}\n";
        return;
    }

    $failed++;
    fwrite(STDERR, "[FAIL] {$message}\n");
}

echo "=== AUDIT READ-ONLY F3b: SKEMA MASTER ASSET & LOKASI ===\n";

$requiredColumns = [
    'warranty',
    'chassis_number',
    'dealer',
    'project_name',
    'operational_position',
    'invoice_location',
    'stnk_location',
    'bpkb_location',
    'vehicle_type',
    'asset_age',
    'capacity',
];

$columns = $db->query('SHOW COLUMNS FROM assets')->fetchAll(PDO::FETCH_COLUMN);
foreach ($requiredColumns as $column) {
    f3bAssert(in_array($column, $columns, true), "Kolom assets.{$column} tersedia");
}

$location = $db->query(
    "SELECT location_id, location_name, location_type, region, is_active
     FROM locations
     WHERE location_id = 15"
)->fetch(PDO::FETCH_ASSOC);
f3bAssert($location !== false, 'Lokasi ID 15 tersedia');
f3bAssert(($location['location_name'] ?? null) === 'WUR EW Project', 'Lokasi ID 15 adalah WUR EW Project');
f3bAssert(($location['is_active'] ?? null) == 1, 'Lokasi WUR EW aktif');

$orphanCount = (int) $db->query(
    "SELECT COUNT(*)
     FROM assets a
     LEFT JOIN locations l ON l.location_id = a.current_location_id
     WHERE a.current_location_id IS NOT NULL
       AND l.location_id IS NULL"
)->fetchColumn();
f3bAssert($orphanCount === 0, 'Tidak ada aset dengan referensi lokasi yatim');

$wurCount = (int) $db->query(
    "SELECT COUNT(*)
     FROM assets
     WHERE current_location_id = 15
       AND UPPER(COALESCE(raw_location_notes, '')) LIKE 'WUR EW%'"
)->fetchColumn();
f3bAssert($wurCount === 87, 'Seluruh 87 aset WUR EW tetap terhubung ke lokasi ID 15');

// Exercise the same registration columns used by api/assets.php. The row is
// deliberately wrapped in a transaction and rolled back.
$qaId = 'QA-BRIAN-F3B-ROLLBACK';
$db->beginTransaction();
try {
    $insert = $db->prepare(
        "INSERT INTO assets (
            asset_id, asset_code, type, category, status, current_location_id,
            warranty, chassis_number, dealer, project_name, operational_position,
            invoice_location, stnk_location, bpkb_location, vehicle_type,
            asset_age, capacity, is_active
        ) VALUES (
            :asset_id, :asset_code, 'Heavy Equipment', 'Other', 'READY', 15,
            'Aktif', 'QA-CHASSIS', 'QA Dealer', 'WUR EW Project', 'QA Position',
            'QA Invoice', 'QA STNK', 'QA BPKB', 'QA Type', 1, 'QA Capacity', 1
        )"
    );
    $insert->execute([':asset_id' => $qaId, ':asset_code' => $qaId]);
    $stored = $db->prepare(
        'SELECT project_name, operational_position, capacity FROM assets WHERE asset_id = :id'
    );
    $stored->execute([':id' => $qaId]);
    $qaRow = $stored->fetch(PDO::FETCH_ASSOC);
    f3bAssert(
        $qaRow !== false
        && $qaRow['project_name'] === 'WUR EW Project'
        && $qaRow['operational_position'] === 'QA Position'
        && $qaRow['capacity'] === 'QA Capacity',
        'Kolom registrasi Master Asset dapat ditulis dan dibaca'
    );
} finally {
    $db->rollBack();
}

$qaCheck = $db->prepare('SELECT COUNT(*) FROM assets WHERE asset_id = :id');
$qaCheck->execute([':id' => $qaId]);
f3bAssert((int) $qaCheck->fetchColumn() === 0, 'Data uji registrasi dibatalkan dengan rollback');

echo "\nHasil: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);
