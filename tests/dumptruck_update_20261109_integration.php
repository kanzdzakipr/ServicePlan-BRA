<?php
declare(strict_types=1);

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;
$failed = 0;

function checkThat(bool $condition, string $message): void
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

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: {$e->getMessage()}\n");
    exit(1);
}

echo "=== Verifikasi integrasi UPDATE DUMPTRUCK 09-11-2026 ===\n";

$root = dirname(__DIR__);
$rawFiles = glob($root . '/raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/*.pdf') ?: [];
$markdownFiles = glob($root . '/material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/UPDATE_DUMPTRUCK_*.md') ?: [];
checkThat(count($rawFiles) === 13, '13 PDF sumber tersedia di raw-material/ASSET');
checkThat(count($markdownFiles) === 13, '13 transkripsi Markdown tersedia per PDF');
checkThat(is_file($root . '/material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/README.md'), 'README pencocokan dan aturan integrasi tersedia');

$snapshotStats = $db->query(
    "SELECT COUNT(*) snapshots, COUNT(DISTINCT report_date) report_dates
     FROM asset_operational_snapshots
     WHERE report_date BETWEEN '2026-10-26' AND '2026-11-09'"
)->fetch();
checkThat((int) $snapshotStats['snapshots'] === 986, '986 baris snapshot tersimpan tanpa duplikasi');
checkThat((int) $snapshotStats['report_dates'] === 13, '13 tanggal laporan tersimpan');

$latestStats = $db->query(
    "SELECT COUNT(*) rows_total, COUNT(DISTINCT normalized_plate) unique_plates,
            SUM(resolved_asset_id IS NULL) unresolved,
            SUM(resolution_state = 'SOURCE_CONFLICT') source_conflicts
     FROM asset_operational_snapshots WHERE report_date = '2026-11-09'"
)->fetch();
checkThat((int) $latestStats['rows_total'] === 76, 'Snapshot terbaru memuat 76 baris sumber');
checkThat((int) $latestStats['unique_plates'] === 75, 'Snapshot terbaru memuat 75 aset fisik unik');
checkThat((int) $latestStats['unresolved'] === 0, 'Seluruh baris terbaru sudah terhubung ke Master Asset');
checkThat((int) $latestStats['source_conflicts'] === 2, 'Konflik DT 1429/DT 1440 dicatat, tidak digandakan');

$duplicateCount = (int) $db->query(
    "SELECT COUNT(*) FROM (
        SELECT matched.normalized_plate
        FROM (
            SELECT UPPER(REPLACE(a.license_plate, ' ', '')) normalized_plate
            FROM assets a
            JOIN (SELECT DISTINCT normalized_plate FROM asset_operational_snapshots WHERE report_date = '2026-11-09') s
              ON s.normalized_plate = UPPER(REPLACE(a.license_plate, ' ', ''))
            WHERE a.is_active = 1 AND a.status <> 'INACTIVE'
        ) matched
        GROUP BY matched.normalized_plate HAVING COUNT(*) > 1
     ) duplicate_report_assets"
)->fetchColumn();
checkThat($duplicateCount === 0, 'Tidak ada nomor polisi ganda aktif pada 75 aset hasil integrasi');

$resolvedAssets = (int) $db->query(
    "SELECT COUNT(DISTINCT resolved_asset_id)
     FROM asset_operational_snapshots WHERE report_date = '2026-11-09'"
)->fetchColumn();
checkThat($resolvedAssets === 75, 'Tepat 75 Master Asset disentuh oleh snapshot terbaru');

$createdIds = [
    'DT-00002', 'BRA-DT-1454', 'BRA-DT-1465', 'BRA-DT-1466', 'BRA-DT-1467',
    'BRA-DT-1468', 'BRA-DT-1469', 'BRA-DT-1472', 'BRA-DT-1473',
];
$placeholders = implode(',', array_fill(0, count($createdIds), '?'));
$stmt = $db->prepare("SELECT COUNT(*) FROM assets WHERE asset_id IN ({$placeholders}) AND is_active = 1");
$stmt->execute($createdIds);
checkThat((int) $stmt->fetchColumn() === 9, '9 aset yang sebelumnya kosong berhasil dilengkapi/dibuat');

$canonicalChecks = $db->query(
    "SELECT asset_id, hull_number, license_plate, status, operational_position
     FROM assets WHERE asset_id IN ('DT-00002','DT-00037','DT-00056','DT-00152','DT-04029','BRA-DT-1469')"
)->fetchAll(PDO::FETCH_UNIQUE);
checkThat(($canonicalChecks['DT-00037']['license_plate'] ?? '') === 'B 9078 ZYT', 'Pelat kacau DT-00037 dikoreksi ke B 9078 ZYT');
checkThat(($canonicalChecks['DT-00056']['hull_number'] ?? '') === 'DT 1156', 'Aset kanonis B 9892 PYW memakai kode terbaru DT 1156');
checkThat(($canonicalChecks['DT-00152']['status'] ?? '') === 'INACTIVE' && empty($canonicalChecks['DT-00152']['license_plate']), 'Record duplikat DT-00152 dinonaktifkan tanpa pelat aktif');
checkThat(($canonicalChecks['DT-04029']['hull_number'] ?? '') === 'DT 1429', 'Konflik BM 9819 QO diselesaikan ke kode kanonis DT 1429');
checkThat(($canonicalChecks['BRA-DT-1469']['status'] ?? '') === 'BREAKDOWN', 'Kondisi broken DT 1469 masuk sebagai BREAKDOWN');

$remainingDuplicateRefs = 0;
foreach (['asset_cost_transactions', 'contract_assignments', 'fuel_logs', 'tire_inspections'] as $table) {
    $remainingDuplicateRefs += (int) $db->query("SELECT COUNT(*) FROM {$table} WHERE asset_id = 'DT-00152'")->fetchColumn();
}
checkThat($remainingDuplicateRefs === 0, 'Riwayat record duplikat dipindahkan ke aset kanonis');

$latestViewCount = (int) $db->query('SELECT COUNT(*) FROM v_asset_latest_operational_snapshot')->fetchColumn();
checkThat($latestViewCount === 75, 'View provenance website menyediakan 75 aset terbaru');

echo "\nHasil: {$passed} PASS, {$failed} FAIL\n";
exit($failed === 0 ? 0 : 1);

