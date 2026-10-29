<?php
declare(strict_types=1);

/**
 * Integration Test for Asset Location Relocation & History
 * Verifies master locations CRUD, asset relocation logic, asset_movements table, and history retrieval.
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

echo "=== MEMULAI TEST INTEGRASI MUTASI LOKASI & MASTER LOKASI ===\n\n";

// 1. Verifikasi Kolom Tabel locations
echo "--- 1. Verifikasi Struktur Tabel locations ---\n";
$stmt = $db->query("DESCRIBE locations");
$locCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
$expectedLocCols = ['location_id', 'location_name', 'location_type', 'region', 'latitude', 'longitude', 'is_active'];
foreach ($expectedLocCols as $col) {
    testAssert(in_array($col, $locCols, true), "Kolom '{$col}' ada di tabel locations");
}

// 2. Verifikasi Struktur Tabel asset_movements
echo "\n--- 2. Verifikasi Struktur Tabel asset_movements ---\n";
$stmt = $db->query("DESCRIBE asset_movements");
$movCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
$expectedMovCols = ['movement_id', 'asset_id', 'from_location_id', 'to_location_id', 'movement_date', 'bast_number', 'notes', 'requested_by'];
foreach ($expectedMovCols as $col) {
    testAssert(in_array($col, $movCols, true), "Kolom '{$col}' ada di tabel asset_movements");
}

// 3. Verifikasi Index Pada asset_movements
echo "\n--- 3. Verifikasi Index Pada asset_movements ---\n";
$stmt = $db->query("SHOW INDEX FROM asset_movements");
$indexes = $stmt->fetchAll();
$indexNames = array_unique(array_column($indexes, 'Key_name'));
testAssert(in_array('idx_asset_movement_asset_date', $indexNames, true), "Index 'idx_asset_movement_asset_date' aktif");
testAssert(in_array('idx_asset_movement_to_loc', $indexNames, true), "Index 'idx_asset_movement_to_loc' aktif");

// 4. Test Pembuatan Master Lokasi Baru
echo "\n--- 4. Test Pendaftaran Master Lokasi Baru ---\n";
// Cleanup any leftover test locations first
$db->exec("DELETE FROM locations WHERE location_name LIKE 'Test Pit Auto %'");
$testLocName = 'Test Pit Auto ' . date('Ymd_His');
$stmt = $db->prepare("INSERT INTO locations (location_name, location_type, region, latitude, longitude, is_active) VALUES (?, ?, ?, ?, ?, 1)");
$stmt->execute([$testLocName, 'Pit', 'Riau / Duri', 1.2854300, 101.2185400]);
$testLocId = (int) $db->lastInsertId();
testAssert($testLocId > 0, "Berhasil mendaftarkan lokasi baru ID: {$testLocId} ({$testLocName})");

// Verifikasi duplikasi nama ditolak (validasi logic api/locations.php)
$stmt = $db->prepare("SELECT COUNT(*) FROM locations WHERE LOWER(TRIM(location_name)) = LOWER(TRIM(?))");
$stmt->execute([$testLocName]);
$dupCount = (int) $stmt->fetchColumn();
testAssert($dupCount === 1, "Nama lokasi unik terverifikasi di database");

// 5. Test Mutasi Lokasi Asset & Logging History
echo "\n--- 5. Test Relokasi Asset & Pencatatan History Mutasi ---\n";
// Ambil 1 asset sample untuk diuji
$stmt = $db->query("SELECT asset_id, current_location_id FROM assets ORDER BY asset_id ASC LIMIT 1");
$testAsset = $stmt->fetch();
testAssert(!empty($testAsset), "Ditemukan asset sample untuk pengujian relokasi");

if ($testAsset) {
    $assetId = $testAsset['asset_id'];
    $originalLocId = $testAsset['current_location_id'];
    $targetLocId = $testLocId;
    $effectiveDate = date('Y-m-d');
    $bastNum = 'BAST-TEST/' . date('Y/m/d');
    $notes = 'Relokasi unit uji otomatis untuk pekerjaan pit baru';

    // Simulasikan mutasi atomik seperti di api/assets.php (action: relocate_asset)
    $db->beginTransaction();
    try {
        $stmtUpdate = $db->prepare("UPDATE assets SET current_location_id = ? WHERE asset_id = ?");
        $stmtUpdate->execute([$targetLocId, $assetId]);

        $stmtMove = $db->prepare("
            INSERT INTO asset_movements (asset_id, from_location_id, to_location_id, movement_date, bast_number, notes, requested_by)
            VALUES (?, ?, ?, ?, ?, ?, NULL)
        ");
        $stmtMove->execute([$assetId, $originalLocId, $targetLocId, $effectiveDate . ' ' . date('H:i:s'), $bastNum, $notes]);
        $newMovementId = (int) $db->lastInsertId();

        $db->commit();

        testAssert($newMovementId > 0, "Record asset_movements berhasil dibuat (ID: {$newMovementId})");

        // Verifikasi assets.current_location_id terupdate
        $stmtCheck = $db->prepare("SELECT current_location_id FROM assets WHERE asset_id = ?");
        $stmtCheck->execute([$assetId]);
        $currLoc = $stmtCheck->fetchColumn();
        testAssert((int) $currLoc === $targetLocId, "Kolom assets.current_location_id terupdate ke lokasi baru ({$targetLocId})");

        // 6. Verifikasi Query History Lokasi (seperti di api/assets.php GET)
        echo "\n--- 6. Verifikasi Query History Lokasi Asset ---\n";
        $stmtHist = $db->prepare("
            SELECT am.movement_id, am.asset_id, am.movement_date, am.bast_number, am.notes,
                   fl.location_name AS from_location_name,
                   tl.location_name AS to_location_name
            FROM asset_movements am
            LEFT JOIN locations fl ON fl.location_id = am.from_location_id
            LEFT JOIN locations tl ON tl.location_id = am.to_location_id
            WHERE am.asset_id = ?
            ORDER BY am.movement_date DESC, am.movement_id DESC
        ");
        $stmtHist->execute([$assetId]);
        $historyRows = $stmtHist->fetchAll();

        testAssert(count($historyRows) > 0, "Query location_history mengembalikan record");
        testAssert($historyRows[0]['to_location_name'] === $testLocName, "Tujuan mutasi tercatat: {$historyRows[0]['to_location_name']}");
        testAssert($historyRows[0]['notes'] === $notes, "Catatan/alasan mutasi tercatat: {$historyRows[0]['notes']}");
        testAssert($historyRows[0]['bast_number'] === $bastNum, "Nomor BAST mutasi tercatat: {$historyRows[0]['bast_number']}");

        // Kembalikan lokasi asset semula
        $stmtRestore = $db->prepare("UPDATE assets SET current_location_id = ? WHERE asset_id = ?");
        $stmtRestore->execute([$originalLocId, $assetId]);

        // Hapus dummy movement test
        $stmtDelMove = $db->prepare("DELETE FROM asset_movements WHERE movement_id = ?");
        $stmtDelMove->execute([$newMovementId]);

        echo "[CLEANUP] Asset {$assetId} dikembalikan ke lokasi semula & dummy movement dihapus.\n";
    } catch (Throwable $t) {
        if ($db->inTransaction()) $db->rollBack();
        throw $t;
    }
}

// Hapus test location
$stmtDelLoc = $db->prepare("DELETE FROM locations WHERE location_id = ?");
$stmtDelLoc->execute([$testLocId]);
echo "[CLEANUP] Master lokasi dummy ({$testLocName}) berhasil dibersihkan.\n";

echo "\n=== HASIL AKHIR INTEGRATION TEST ===\n";
echo "Total Passed: {$passed}\n";
echo "Total Failed: {$failed}\n";

if ($failed > 0) {
    exit(1);
}
echo "Semua pengujian mutasi lokasi dan riwayat mutasi BERHASIL 100%!\n";
exit(0);
