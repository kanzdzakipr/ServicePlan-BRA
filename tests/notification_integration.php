<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
define('API_SKIP_ROUTING', true);
putenv('APP_ENV=local');
putenv('DB_NAME=u646470441_ServicePlanBRA');
putenv('DB_USER=root');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_PASSWORD=');

/**
 * Automated Integration Test for Revisi 6: Notifikasi WO (BR-06 / M7)
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

$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../api/security.php';
require_once __DIR__ . '/../core/WorkOrderSupport.php';
require_once __DIR__ . '/../api/work_orders.php';

echo "=== MEMULAI TEST INTEGRASI REVISI 6: NOTIFIKASI WO BARU & AUDIENCE ===\n\n";

$testWoId = 'QA-BRIAN-WO-NOTIF-01';
$testAssetId = 'DT-00049';

// 1. Cleanup awal
$db->exec("DELETE FROM system_notifications WHERE title LIKE '%{$testWoId}%'");
$db->exec("DELETE FROM work_orders WHERE wo_id = '{$testWoId}'");

// 2. Test pemanggilan wo_notify (Simulasi Create WO)
require_once __DIR__ . '/../api/work_orders.php';
wo_notify($db, 'WO_CREATE', $testWoId, "Work Order #{$testWoId} dibuat untuk pengetesan.", 'Rahmad K', $testAssetId);

$stmt = $db->prepare("SELECT * FROM system_notifications WHERE title LIKE :title ORDER BY notification_id DESC LIMIT 1");
$stmt->execute([':title' => "%{$testWoId}%"]);
$notif = $stmt->fetch();

testAssert(!empty($notif), "Notifikasi WO baru berhasil dibuat di system_notifications");
testAssert($notif['action_type'] === 'WO_CREATE', "Action type tercatat sebagai 'WO_CREATE'");
$parties = json_decode($notif['involved_parties_json'] ?? '[]', true);
testAssert(in_array('Tim Logistik & Suku Cadang', $parties, true), "Audience mencakup 'Tim Logistik & Suku Cadang'");
testAssert(in_array('Maintenance Planner', $parties, true), "Audience mencakup 'Maintenance Planner'");

$related = json_decode($notif['related_tables_json'] ?? '{}', true);
testAssert(($related['wo_id'] ?? '') === $testWoId, "Related JSON menyimpan relasi wo_id: {$testWoId}");
testAssert(($related['asset_id'] ?? '') === $testAssetId, "Related JSON menyimpan relasi asset_id: {$testAssetId}");

// 3. Test Idempotency (retry cepat tidak menduplikasi notifikasi)
$countBefore = (int) $db->query("SELECT COUNT(*) FROM system_notifications WHERE title LIKE '%{$testWoId}%'")->fetchColumn();
wo_notify($db, 'WO_CREATE', $testWoId, "Work Order #{$testWoId} dibuat untuk pengetesan (RETRY).", 'Rahmad K', $testAssetId);
$countAfter = (int) $db->query("SELECT COUNT(*) FROM system_notifications WHERE title LIKE '%{$testWoId}%'")->fetchColumn();
testAssert($countBefore === $countAfter, "Idempotency berfungsi: Notifikasi ganda ditolak pada retry");

// 4. Test Notifikasi Permintaan Suku Cadang (Part Request)
WorkOrderSupport::notify($db, 'PART_REQUEST', "Permintaan Suku Cadang WO #{$testWoId} (SPB-QA-99)", "Kebutuhan part filter oli", $testWoId, $testAssetId, 'SPB-QA-99');
$partNotif = $db->query("SELECT * FROM system_notifications WHERE action_type = 'PART_REQUEST' AND title LIKE '%{$testWoId}%'")->fetch();
testAssert(!empty($partNotif), "Notifikasi permintaan suku cadang (PART_REQUEST) berhasil tersimpan");
$partRelated = json_decode($partNotif['related_tables_json'] ?? '{}', true);
testAssert(($partRelated['spb_id'] ?? '') === 'SPB-QA-99', "Linkage ke SPB tersimpan di related_tables_json: SPB-QA-99");

// 5. Test Notifikasi Permintaan Layanan Bengkel (Service Request)
WorkOrderSupport::notify($db, 'SERVICE_REQUEST', "Permintaan Layanan WO #{$testWoId} (#SR-QA-88)", "Overhaul transmisi", $testWoId, $testAssetId, null, 'SR-QA-88');
$srvNotif = $db->query("SELECT * FROM system_notifications WHERE action_type = 'SERVICE_REQUEST' AND title LIKE '%{$testWoId}%'")->fetch();
testAssert(!empty($srvNotif), "Notifikasi permintaan layanan (SERVICE_REQUEST) berhasil tersimpan");

// 6. Test Mark Read
$testNotifId = (int) $notif['notification_id'];
$db->exec("UPDATE system_notifications SET is_read = 1 WHERE notification_id = {$testNotifId}");
$readStatus = (int) $db->query("SELECT is_read FROM system_notifications WHERE notification_id = {$testNotifId}")->fetchColumn();
testAssert($readStatus === 1, "Status is_read berhasil diperbarui menjadi 1 (dibaca)");

// 7. Cleanup
$db->exec("DELETE FROM system_notifications WHERE title LIKE '%{$testWoId}%'");

echo "\n=======================================================\n";
echo "RINGKASAN HASIL TEST INTEGRASI REVISI 6: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

if ($failed > 0) exit(1);
