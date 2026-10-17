<?php
declare(strict_types=1);

/**
 * Automated Integration Test for Revisi 1: Master Aset (BR-01 / M3)
 * Tests completeness calculation, duplicate validations, CRUD persistence, and location linkage.
 */

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');

$passed = 0;
$failed = 0;

function testAssert(bool $condition, string $message): void
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

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "=== MEMULAI TEST INTEGRASI REVISI 1: MASTER ASET ===\n\n";

// 1. Verifikasi Kolom Database
echo "--- 1. Verifikasi Kolom Database Master Aset ---\n";
$stmt = $db->query("DESCRIBE assets");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$expectedColumns = [
    'asset_id', 'asset_code', 'serial_number', 'license_plate',
    'make_model', 'year_manufacture', 'category', 'status',
    'current_location_id', 'last_hm_km'
];
foreach ($expectedColumns as $col) {
    testAssert(in_array($col, $columns, true), "Kolom '{$col}' tersedia di tabel assets");
}

// 2. Verifikasi Fungsi Evaluasi Kelengkapan (asset_compute_completeness)
echo "\n--- 2. Verifikasi Evaluasi Kelengkapan Data ---\n";
require_once dirname(__DIR__) . '/core/AssetIdentity.php';

// Incomplete asset
$incompleteRow = [
    'serial_number' => null,
    'license_plate' => null,
    'make_model' => null,
    'year_manufacture' => null,
    'current_location_id' => null,
    'category' => 'Excavator'
];
$comp1 = asset_compute_completeness($incompleteRow);
testAssert(!$comp1['is_complete'], "Unit tanpa SN, Model, Tahun, Lokasi terdeteksi 'Belum Lengkap'");
testAssert(count($comp1['missing_fields']) >= 4, "Field yang kurang teridentifikasi dengan tepat");

// Complete asset
$completeRow = [
    'serial_number' => 'SN-QA-12345',
    'license_plate' => 'BM 1234 QA',
    'make_model' => 'Komatsu PC200-10M0',
    'year_manufacture' => 2023,
    'current_location_id' => 3,
    'category' => 'Excavator'
];
$comp2 = asset_compute_completeness($completeRow);
testAssert($comp2['is_complete'], "Unit dengan seluruh identitas terdeteksi 'Lengkap'");
testAssert(empty($comp2['missing_fields']), "Missing fields kosong untuk unit lengkap");

// Dump Truck tanpa plat
$truckNoPlate = [
    'serial_number' => 'SN-TRUCK-001',
    'license_plate' => null,
    'make_model' => 'Hino 500 Dump',
    'year_manufacture' => 2022,
    'current_location_id' => 1,
    'category' => 'Dump Truck'
];
$compTruck = asset_compute_completeness($truckNoPlate);
testAssert(!$compTruck['is_complete'], "Dump Truck tanpa nomor pelat terdeteksi 'Belum Lengkap'");
testAssert(in_array('Nomor Pelat', $compTruck['missing_fields'], true), "Missing field 'Nomor Pelat' tercantum");

// 3. Verifikasi Registrasi Unit Baru & Pencegahan Duplikasi
echo "\n--- 3. Verifikasi Insert Unit Baru & Validasi Duplikasi ---\n";
$testId = 'QA-BRIAN-ASSET-01';
$testSn = 'QA-SN-BRIAN-998811';
$testPlate = 'BM 9988 BRI';

// Pastikan data tes lama bersih
$db->prepare("DELETE FROM assets WHERE asset_id = :id OR serial_number = :sn OR license_plate = :plate")
   ->execute([':id' => $testId, ':sn' => $testSn, ':plate' => $testPlate]);

// Insert valid unit
$insertStmt = $db->prepare("
    INSERT INTO assets (
        asset_id, asset_code, serial_number, license_plate,
        make_model, year_manufacture, type, category, status,
        current_location_id, last_hm_km, is_active
    ) VALUES (
        :id, :code, :sn, :plate,
        :make_model, :year, 'Heavy Equipment', 'Excavator', 'READY',
        3, 150.50, 1
    )
");
$insertStmt->execute([
    ':id' => $testId,
    ':code' => $testId,
    ':sn' => $testSn,
    ':plate' => $testPlate,
    ':make_model' => 'Komatsu PC200-10M0 CE',
    ':year' => 2024
]);

$fetchStmt = $db->prepare("SELECT * FROM assets WHERE asset_id = :id");
$fetchStmt->execute([':id' => $testId]);
$inserted = $fetchStmt->fetch();
testAssert($inserted !== false, "Unit '{$testId}' berhasil disimpan di database");
testAssert($inserted['serial_number'] === $testSn, "Serial Number tersimpan sesuai input: {$testSn}");
testAssert($inserted['license_plate'] === $testPlate, "Nomor Pelat tersimpan sesuai input: {$testPlate}");
testAssert($inserted['make_model'] === 'Komatsu PC200-10M0 CE', "Merek/Model tersimpan sesuai input");
testAssert((int) $inserted['year_manufacture'] === 2024, "Tahun pembuatan tersimpan: 2024");
testAssert((int) $inserted['current_location_id'] === 3, "Lokasi operasional tersimpan: 3 (Borrow Pit)");

// Test Cek Duplikasi Asset ID
$dupIdCheck = $db->prepare("SELECT asset_id FROM assets WHERE asset_id = :id OR asset_code = :code LIMIT 1");
$dupIdCheck->execute([':id' => $testId, ':code' => $testId]);
testAssert($dupIdCheck->fetch() !== false, "Pemeriksaan duplikasi mendeteksi Unit ID yang sudah ada");

// Test Cek Duplikasi Serial Number
$dupSnCheck = $db->prepare("SELECT asset_id FROM assets WHERE serial_number = :sn LIMIT 1");
$dupSnCheck->execute([':sn' => $testSn]);
$dupSnRow = $dupSnCheck->fetch();
testAssert($dupSnRow !== false && $dupSnRow['asset_id'] === $testId, "Pemeriksaan duplikasi mendeteksi Serial Number yang sudah digunakan");

// Test Cek Duplikasi Nomor Pelat
$dupPlateCheck = $db->prepare("SELECT asset_id FROM assets WHERE license_plate = :plate LIMIT 1");
$dupPlateCheck->execute([':plate' => $testPlate]);
$dupPlateRow = $dupPlateCheck->fetch();
testAssert($dupPlateRow !== false && $dupPlateRow['asset_id'] === $testId, "Pemeriksaan duplikasi mendeteksi Nomor Pelat yang sudah digunakan");

// 4. Verifikasi Update / Edit Unit Aset (Lengkapi Data)
echo "\n--- 4. Verifikasi Update Identitas Unit (Edit / PUT) ---\n";
$updateStmt = $db->prepare("
    UPDATE assets 
    SET make_model = :model,
        year_manufacture = :year,
        status = :status,
        last_hm_km = :hm
    WHERE asset_id = :id
");
$updateStmt->execute([
    ':model' => 'Komatsu PC200-10M0 CE Mining Spec',
    ':year' => 2025,
    ':status' => 'OPERATING',
    ':hm' => 220.0,
    ':id' => $testId
]);

$fetchStmt->execute([':id' => $testId]);
$updated = $fetchStmt->fetch();
testAssert($updated['make_model'] === 'Komatsu PC200-10M0 CE Mining Spec', "Merek/Model berhasil diperbarui");
testAssert((int) $updated['year_manufacture'] === 2025, "Tahun pembuatan berhasil diperbarui ke 2025");
testAssert($updated['status'] === 'OPERATING', "Status berhasil diperbarui ke OPERATING");
testAssert((float) $updated['last_hm_km'] === 220.0, "Meter HM/KM berhasil diperbarui ke 220.0");

// 5. Verifikasi Query Init dengan Nama Lokasi Aktif
echo "\n--- 5. Verifikasi Linkage Query Master Aset & Lokasi ---\n";
$initStmt = $db->prepare("
    SELECT a.asset_id as id, a.asset_code, a.serial_number, a.license_plate,
           a.make_model, a.year_manufacture, a.type, a.category, a.status,
           a.current_location_id,
           COALESCE(l.location_name, a.raw_location_notes) as location,
           l.location_name
    FROM assets a
    LEFT JOIN locations l ON l.location_id = a.current_location_id
    WHERE a.asset_id = :id
");
$initStmt->execute([':id' => $testId]);
$linkedRow = $initStmt->fetch();
testAssert($linkedRow !== false, "Query linkage berhasil membaca unit terdaftar");
testAssert(!empty($linkedRow['location']), "Nama lokasi berhasil di-resolve dari tabel locations: " . ($linkedRow['location'] ?? ''));

// 6. Cleanup Data Pengujian
echo "\n--- 6. Cleanup Data Uji ---\n";
$delStmt = $db->prepare("DELETE FROM assets WHERE asset_id = :id");
$delStmt->execute([':id' => $testId]);
$fetchStmt->execute([':id' => $testId]);
testAssert($fetchStmt->fetch() === false, "Data uji '{$testId}' berhasil dibersihkan dari database");

echo "\n=======================================================\n";
echo "RINGKASAN HASIL TEST INTEGRASI: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
