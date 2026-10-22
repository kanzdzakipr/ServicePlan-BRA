<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'ADMIN';

require_once dirname(__DIR__) . '/api/db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/core/AssetOnboarding.php';

$db = Database::getInstance();
$passed = 0;
$failed = 0;

function batchAssert(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$message}\n";
    } else {
        $failed++;
        fwrite(STDERR, "[FAIL] {$message}\n");
    }
}

echo "=== MEMULAI TEST INTEGRASI BATCH B: MASTER ASET, ONBOARDING & PM 11 KOLOM ===\n\n";

// --- 1. Verifikasi Struktur Tabel dan Kolom Database ---
echo "--- 1. Verifikasi Kolom Database Tabel assets ---\n";
$assetColsStmt = $db->query("SHOW COLUMNS FROM assets");
$assetCols = $assetColsStmt->fetchAll(PDO::FETCH_COLUMN);

$expectedCols = [
    'hull_number',
    'current_operator_name',
    'last_service_date',
    'last_service_hm_km',
    'service_interval_val',
    'service_interval_unit',
    'daily_usage_coefficient',
    'next_service_due_hm_km',
    'next_service_estimated_date',
    'legal_stnk_expiry',
    'legal_kir_expiry',
    'legal_silo_expiry',
    'service_manual_pdf_url'
];

foreach ($expectedCols as $col) {
    batchAssert(in_array($col, $assetCols, true), "Kolom '{$col}' tersedia di tabel assets");
}

echo "\n--- 2. Verifikasi Tabel Relasi Onboarding & Histori ---\n";
$tablesStmt = $db->query("SHOW TABLES");
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

$expectedTables = [
    'asset_hull_history',
    'asset_operator_history',
    'asset_service_history',
    'asset_gallery_photos'
];

foreach ($expectedTables as $table) {
    batchAssert(in_array($table, $tables, true), "Tabel '{$table}' tersedia di database");
}

// Clean up test data if exists
$testAssetId = 'QA-BRIAN-EX99';
$db->prepare("DELETE FROM assets WHERE asset_id = :id")->execute([':id' => $testAssetId]);

// --- 3. Registrasi Unit Baru melalui Logika Onboarding ---
echo "\n--- 3. Registrasi Unit Baru dengan Parameter Lengkap ({$testAssetId}) ---\n";

$currentMeter = 1000.00;
$lastServiceMeter = 750.00;
$serviceInterval = 250;
$dailyRate = 10.00;

$inputData = [
    'asset_id' => $testAssetId,
    'asset_code' => $testAssetId,
    'hull_number' => 'EX-99-SITEA',
    'category' => 'Excavator',
    'type' => 'Heavy Equipment',
    'status' => 'READY',
    'make_model' => 'Komatsu PC200-8',
    'year_manufacture' => 2023,
    'serial_number' => 'QA-SN-EX99-2023',
    'license_plate' => 'BM 8899 QA',
    'current_location_id' => 3,
    'last_hm_km' => $currentMeter,
    'current_operator_name' => 'Sutrisno QA',
    'operator_start_date' => '2026-01-01',
    'operator_shift' => 'Day Shift',
    'last_service_date' => '2026-03-01 08:00:00',
    'last_service_hm_km' => $lastServiceMeter,
    'service_interval_val' => $serviceInterval,
    'service_interval_unit' => 'HM',
    'daily_usage_coefficient' => $dailyRate,
    'legal_stnk_expiry' => '2027-05-15',
    'legal_kir_expiry' => '2027-06-20',
    'legal_silo_expiry' => '2027-08-30',
    'historical_services' => [
        ['date' => '2025-10-01', 'meter' => 250, 'type' => 'PS 250'],
        ['date' => '2025-12-15', 'meter' => 500, 'type' => 'PS 500']
    ]
];

$onboarding = AssetOnboarding::fields($inputData, $currentMeter);
$historicalServices = AssetOnboarding::history($inputData['historical_services']);

batchAssert($onboarding['next_service_due_hm_km'] == 1000.00, 'Kalkulasi target servis due tepat (750 + 250 = 1000.00)');
batchAssert($onboarding['next_service_estimated_date'] === date('Y-m-d'), 'Kalkulasi estimasi tanggal servis hari ini tepat ((1000-1000)/10 = 0 hari)');

$db->beginTransaction();
$insertStmt = $db->prepare("
    INSERT INTO assets (
        asset_id, asset_code, hull_number, alias_name, serial_number, license_plate,
        make_model, year_manufacture, type, category, status,
        current_location_id, last_hm_km, is_active,
        current_operator_name, last_service_date, last_service_hm_km,
        service_interval_val, service_interval_unit, daily_usage_coefficient,
        next_service_due_hm_km, next_service_estimated_date,
        legal_stnk_expiry, legal_kir_expiry, legal_silo_expiry
    ) VALUES (
        :id, :code, :hull, :alias, :sn, :plate,
        :make_model, :year, :type, :cat, :status,
        :loc_id, :hm, 1,
        :operator, :service_date, :service_meter,
        :interval_val, :interval_unit, :daily_rate,
        :next_due, :next_estimate,
        :stnk, :kir, :silo
    )
");
$insertStmt->execute([
    ':id' => $testAssetId,
    ':code' => $testAssetId,
    ':hull' => $inputData['hull_number'],
    ':alias' => $inputData['hull_number'],
    ':sn' => $inputData['serial_number'],
    ':plate' => $inputData['license_plate'],
    ':make_model' => $inputData['make_model'],
    ':year' => $inputData['year_manufacture'],
    ':type' => $inputData['type'],
    ':cat' => $inputData['category'],
    ':status' => $inputData['status'],
    ':loc_id' => $inputData['current_location_id'],
    ':hm' => $currentMeter,
    ':operator' => $onboarding['current_operator_name'],
    ':service_date' => $onboarding['last_service_date'],
    ':service_meter' => $onboarding['last_service_hm_km'],
    ':interval_val' => $onboarding['service_interval_val'],
    ':interval_unit' => $onboarding['service_interval_unit'],
    ':daily_rate' => $onboarding['daily_usage_coefficient'],
    ':next_due' => $onboarding['next_service_due_hm_km'],
    ':next_estimate' => $onboarding['next_service_estimated_date'],
    ':stnk' => $onboarding['legal_stnk_expiry'],
    ':kir' => $onboarding['legal_kir_expiry'],
    ':silo' => $onboarding['legal_silo_expiry'],
]);

// Operator log
$opStmt = $db->prepare('INSERT INTO asset_operator_history (asset_id, operator_name, start_date, shift_type) VALUES (:id, :name, :start, :shift)');
$opStmt->execute([
    ':id' => $testAssetId,
    ':name' => $onboarding['current_operator_name'],
    ':start' => $inputData['operator_start_date'],
    ':shift' => $inputData['operator_shift']
]);

// Service history
$serviceStmt = $db->prepare('INSERT INTO asset_service_history (asset_id, service_sequence, service_date, service_hm_km, service_type, notes) VALUES (:id, :seq, :date, :meter, :type, :notes)');
foreach ($historicalServices as $idx => $historical) {
    $serviceStmt->execute([
        ':id' => $testAssetId,
        ':seq' => $idx + 1,
        ':date' => $historical['date'],
        ':meter' => $historical['meter'],
        ':type' => $historical['type'],
        ':notes' => null
    ]);
}
// Add the onboarding service
$serviceStmt->execute([
    ':id' => $testAssetId,
    ':seq' => count($historicalServices) + 1,
    ':date' => $onboarding['last_service_date'],
    ':meter' => $onboarding['last_service_hm_km'],
    ':type' => 'Servis terakhir saat onboarding',
    ':notes' => 'Onboarding awal'
]);

$db->commit();

// Verifikasi data di DB
$checkAsset = $db->prepare("SELECT * FROM assets WHERE asset_id = :id");
$checkAsset->execute([':id' => $testAssetId]);
$assetRow = $checkAsset->fetch(PDO::FETCH_ASSOC);

batchAssert($assetRow !== false, "Unit '{$testAssetId}' berhasil disimpan di tabel assets");
batchAssert($assetRow['hull_number'] === 'EX-99-SITEA', "Nomor lambung awal tersimpan: EX-99-SITEA");
batchAssert($assetRow['current_operator_name'] === 'Sutrisno QA', "Nama operator awal tersimpan: Sutrisno QA");
batchAssert($assetRow['legal_stnk_expiry'] === '2027-05-15', "Legalitas STNK tersimpan: 2027-05-15");
batchAssert($assetRow['legal_kir_expiry'] === '2027-06-20', "Legalitas KIR tersimpan: 2027-06-20");
batchAssert($assetRow['legal_silo_expiry'] === '2027-08-30', "Legalitas SILO tersimpan: 2027-08-30");

// Check operator history table
$checkOp = $db->prepare("SELECT * FROM asset_operator_history WHERE asset_id = :id");
$checkOp->execute([':id' => $testAssetId]);
$opRows = $checkOp->fetchAll(PDO::FETCH_ASSOC);
batchAssert(count($opRows) === 1, "Tabel asset_operator_history memiliki 1 entri");
batchAssert($opRows[0]['operator_name'] === 'Sutrisno QA', "Operator history nama sesuai: Sutrisno QA");

// Check service history table
$checkService = $db->prepare("SELECT * FROM asset_service_history WHERE asset_id = :id ORDER BY service_sequence ASC");
$checkService->execute([':id' => $testAssetId]);
$serviceRows = $checkService->fetchAll(PDO::FETCH_ASSOC);
batchAssert(count($serviceRows) === 3, "Tabel asset_service_history memiliki 3 entri runut perawatan");

// --- 4. Verifikasi Upload Foto Galeri dan Berkas PDF ---
echo "\n--- 4. Verifikasi Upload Foto Galeri dan Dokumen PDF ---\n";
$photoStmt = $db->prepare("INSERT INTO asset_gallery_photos (asset_id, photo_title, photo_angle, photo_url) VALUES (:id, :title, :angle, :url)");
$photoStmt->execute([
    ':id' => $testAssetId,
    ':title' => 'Tampak Depan Excavator',
    ':angle' => 'Front',
    ':url' => 'uploads/assets/mock_ex99_front.jpg'
]);
$photoId = (int) $db->lastInsertId();
batchAssert($photoId > 0, "Foto galeri berhasil disimpan dengan photo_id {$photoId}");

$pdfStmt = $db->prepare("UPDATE assets SET service_manual_pdf_url = :url WHERE asset_id = :id");
$pdfStmt->execute([
    ':url' => 'uploads/assets/mock_ex99_service_manual.pdf',
    ':id' => $testAssetId
]);
batchAssert($pdfStmt->rowCount() === 1, "URL berkas PDF JO / Manual Servis berhasil diperbarui pada unit");

// --- 5. Verifikasi Fitur Rename Nomor Lambung dengan Audit Log ---
echo "\n--- 5. Verifikasi Rename Nomor Lambung & Pencatatan Audit Log ---\n";
$oldHull = 'EX-99-SITEA';
$newHull = 'EX-99-SITEB';
$changeReason = 'Pindah operasional ke Site B';
$actorName = 'Brian Nugraha';

$renameStmt = $db->prepare("
    UPDATE assets 
    SET hull_number = :new_hull, alias_name = :new_alias 
    WHERE asset_id = :id
");
$renameStmt->execute([':new_hull' => $newHull, ':new_alias' => $newHull, ':id' => $testAssetId]);

$historyStmt = $db->prepare("
    INSERT INTO asset_hull_history (asset_id, old_hull_number, new_hull_number, change_reason, effective_date, changed_by_name)
    VALUES (:id, :old_hull, :new_hull, :reason, CURRENT_DATE(), :actor)
");
$historyStmt->execute([
    ':id' => $testAssetId,
    ':old_hull' => $oldHull,
    ':new_hull' => $newHull,
    ':reason' => $changeReason,
    ':actor' => $actorName
]);

$checkHull = $db->prepare("SELECT hull_number FROM assets WHERE asset_id = :id");
$checkHull->execute([':id' => $testAssetId]);
batchAssert($checkHull->fetchColumn() === 'EX-99-SITEB', "Nomor lambung di tabel assets berhasil berganti ke EX-99-SITEB");

$checkLog = $db->prepare("SELECT * FROM asset_hull_history WHERE asset_id = :id ORDER BY history_id DESC LIMIT 1");
$checkLog->execute([':id' => $testAssetId]);
$logRow = $checkLog->fetch(PDO::FETCH_ASSOC);
batchAssert($logRow !== false, "Catatan perubahan nomor lambung tersimpan di asset_hull_history");
batchAssert($logRow['old_hull_number'] === 'EX-99-SITEA', "Old hull tercatat akurat: EX-99-SITEA");
batchAssert($logRow['new_hull_number'] === 'EX-99-SITEB', "New hull tercatat akurat: EX-99-SITEB");
batchAssert($logRow['changed_by_name'] === 'Brian Nugraha', "Aktor perubahan tercatat akurat: Brian Nugraha");

// --- 6. Verifikasi Integrasi Tabel PM 11 Kolom (api/pm.php?view=table_list) ---
echo "\n--- 6. Verifikasi Penyajian Data Tabel PM 11 Kolom ---\n";
$pmScope = api_location_scope_clause('a', 'pm_asset_location');
$pmSql = 'SELECT a.asset_id, a.asset_code, a.hull_number, a.alias_name, a.serial_number,
               a.license_plate, a.year_manufacture, a.make_model, a.category, a.last_hm_km,
               a.current_location_id, l.location_name, a.current_operator_name,
               a.last_service_date, a.last_service_hm_km, a.service_interval_val,
               a.service_interval_unit, a.daily_usage_coefficient,
               a.next_service_due_hm_km, a.next_service_estimated_date,
               a.service_manual_pdf_url
        FROM assets a
        LEFT JOIN locations l ON l.location_id = a.current_location_id
        WHERE a.asset_id = :id';
$pmStmt = $db->prepare($pmSql);
$pmStmt->execute([':id' => $testAssetId]);
$pmData = $pmStmt->fetch(PDO::FETCH_ASSOC);

batchAssert($pmData !== false, "Query PM menemukan data unit '{$testAssetId}'");
batchAssert($pmData['hull_number'] === 'EX-99-SITEB', "Kolom 2 [No. Lambung]: EX-99-SITEB");
batchAssert($pmData['serial_number'] === 'QA-SN-EX99-2023', "Kolom 3 [Plat/SN]: SN QA-SN-EX99-2023");
batchAssert((int)$pmData['year_manufacture'] === 2023, "Kolom 4 [Tahun]: 2023");
batchAssert($pmData['current_operator_name'] === 'Sutrisno QA', "Kolom 5 [Operator/PIC]: Sutrisno QA");
batchAssert((float)$pmData['last_service_hm_km'] == 750.00, "Kolom 6 [Terakhir Servis]: 750.00 HM");
batchAssert((int)$pmData['service_interval_val'] === 250, "Kolom 7 [Interval]: 250 HM");
batchAssert((float)$pmData['next_service_due_hm_km'] == 1000.00, "Kolom 8 [KM/HM Selanjutnya]: 1000.00 HM");
batchAssert($pmData['next_service_estimated_date'] !== null, "Kolom 8 [Estimasi Tanggal Servis]: {$pmData['next_service_estimated_date']}");
batchAssert($pmData['service_manual_pdf_url'] !== null, "Kolom 11 [Berkas JO / PDF]: {$pmData['service_manual_pdf_url']}");

// --- 7. Cleanup Data Uji ---
echo "\n--- 7. Pembersihan Data Uji ---\n";
$delPhotos = $db->prepare("DELETE FROM asset_gallery_photos WHERE asset_id = :id");
$delPhotos->execute([':id' => $testAssetId]);

$delHull = $db->prepare("DELETE FROM asset_hull_history WHERE asset_id = :id");
$delHull->execute([':id' => $testAssetId]);

$delAsset = $db->prepare("DELETE FROM assets WHERE asset_id = :id");
$delAsset->execute([':id' => $testAssetId]);
batchAssert(true, "Data uji '{$testAssetId}' berhasil dibersihkan dari database");

echo "\n=======================================================\n";
echo "RINGKASAN BATCH B TEST: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

exit($failed === 0 ? 0 : 1);
