<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
define('API_SKIP_ROUTING', true);

require_once dirname(__DIR__) . '/api/db.php';
require_once dirname(__DIR__) . '/core/NotificationAudience.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$db = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message): void
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

echo "=== MEMULAI TEST INTEGRASI AUDIENCE NOTIFIKASI BERDASARKAN PERMIT 'R' RBAC ===\n\n";

// 1. Module key resolution unit checks
assertTest(NotificationAudience::resolveModuleKey('Work Order Kanban', 'WO_CREATE') === 'wo', 'Work Order Kanban resolves to "wo"');
assertTest(NotificationAudience::resolveModuleKey('Work Order Kanban', 'PART_REQUEST') === 'logistics', 'PART_REQUEST resolves to "logistics"');
assertTest(NotificationAudience::resolveModuleKey('Inspections', 'P2H_SUBMIT') === 'inspection', 'Inspections resolves to "inspection"');
assertTest(NotificationAudience::resolveModuleKey('Archive', 'ARCHIVE_P2H') === 'inspection', 'ARCHIVE_P2H resolves to "inspection"');
assertTest(NotificationAudience::resolveModuleKey('Archive', 'ARCHIVE_ASSET') === 'asset', 'ARCHIVE_ASSET resolves to "asset"');
assertTest(NotificationAudience::resolveModuleKey('Archive', 'ARCHIVE_ACCIDENT') === 'hse', 'ARCHIVE_ACCIDENT resolves to "hse"');
assertTest(NotificationAudience::resolveModuleKey('People & KPI', 'KPI_UPDATE') === 'people', 'People & KPI resolves to "people"');

// 2. Fetch users and verify RBAC read permission filtering
$users = NotificationAudience::activeUsers($db);
$matrix = AuthMiddleware::getEffectiveMatrix();

$plannerId = null;
$logisticId = null;
$safetyId = null;
$hrdId = null;

foreach ($users as $u) {
    if (in_array('Maintenance Planner', $u['roles'], true)) $plannerId = (int)$u['user_id'];
    if (in_array('Logistic Head', $u['roles'], true)) $logisticId = (int)$u['user_id'];
    if (in_array('Inspector K3L / Safety', $u['roles'], true)) $safetyId = (int)$u['user_id'];
    if (in_array('HRD Manager', $u['roles'], true)) $hrdId = (int)$u['user_id'];
}

assertTest($plannerId !== null, 'Found active Maintenance Planner user');
assertTest($logisticId !== null, 'Found active Logistic Head user');
assertTest($safetyId !== null, 'Found active Inspector K3L / Safety user');
assertTest($hrdId !== null, 'Found active HRD Manager user');

// 3. Test WO notification delivery:
// In RBAC: Maintenance Planner has 'RCUA' (has 'R'), Logistic Head has 'R' (has 'R'), HRD Manager has '-' (NO 'R')
$db->beginTransaction();
try {
    $notifStmt = $db->prepare("
        INSERT INTO system_notifications
        (menu_name, user_name, user_role, action_type, title, message, related_tables_json, is_read, created_at)
        VALUES ('Work Order Kanban', 'QA Tester', 'Tester', 'WO_CREATE', 'QA WO RBAC Test', 'Test message', '{}', 0, NOW())
    ");
    $notifStmt->execute();
    $woNotifId = (int)$db->lastInsertId();

    NotificationAudience::deliverNotification($db, $woNotifId, null, null, 'Work Order Kanban', 'WO_CREATE');

    $recipientStmt = $db->prepare("SELECT user_id FROM notification_recipients WHERE notification_id = :id");
    $recipientStmt->execute([':id' => $woNotifId]);
    $recipients = $recipientStmt->fetchAll(PDO::FETCH_COLUMN);

    assertTest(in_array($plannerId, $recipients, false), 'Maintenance Planner receives WO notification (has R permit)');
    assertTest(in_array($logisticId, $recipients, false), 'Logistic Head receives WO notification (has R permit)');
    assertTest(!in_array($hrdId, $recipients, false), 'HRD Manager does NOT receive WO notification (has - permit)');

    // 4. Test Inspection notification delivery:
    // In RBAC: Inspector K3L has 'RCUA' (has 'R'), Maintenance Planner has 'RU' (has 'R'), Logistic Head has '-' (NO 'R')
    $notifStmt = $db->prepare("
        INSERT INTO system_notifications
        (menu_name, user_name, user_role, action_type, title, message, related_tables_json, is_read, created_at)
        VALUES ('Inspections', 'QA Inspector', 'Inspector', 'P2H_SUBMIT', 'QA P2H RBAC Test', 'Test message', '{}', 0, NOW())
    ");
    $notifStmt->execute();
    $p2hNotifId = (int)$db->lastInsertId();

    NotificationAudience::deliverNotification($db, $p2hNotifId, null, null, 'Inspections', 'P2H_SUBMIT');

    $recipientStmt->execute([':id' => $p2hNotifId]);
    $p2hRecipients = $recipientStmt->fetchAll(PDO::FETCH_COLUMN);

    assertTest(in_array($safetyId, $p2hRecipients, false), 'Inspector K3L receives Inspection notification (has R permit)');
    assertTest(in_array($plannerId, $p2hRecipients, false), 'Maintenance Planner receives Inspection notification (has R permit)');
    assertTest(!in_array($logisticId, $p2hRecipients, false), 'Logistic Head does NOT receive Inspection notification (has - permit)');

    // 5. Test People & HRD notification delivery:
    // In RBAC: HRD Manager has 'RCUA' (has 'R'), Maintenance Planner has 'RU' (has 'R'), Inspector K3L has '-' (NO 'R')
    $notifStmt = $db->prepare("
        INSERT INTO system_notifications
        (menu_name, user_name, user_role, action_type, title, message, related_tables_json, is_read, created_at)
        VALUES ('People & KPI', 'QA HRD', 'HRD', 'KPI_RECORD', 'QA HRD RBAC Test', 'Test message', '{}', 0, NOW())
    ");
    $notifStmt->execute();
    $hrdNotifId = (int)$db->lastInsertId();

    NotificationAudience::deliverNotification($db, $hrdNotifId, null, null, 'People & KPI', 'KPI_RECORD');

    $recipientStmt->execute([':id' => $hrdNotifId]);
    $hrdRecipients = $recipientStmt->fetchAll(PDO::FETCH_COLUMN);

    assertTest(in_array($hrdId, $hrdRecipients, false), 'HRD Manager receives People & KPI notification (has R permit)');
    assertTest(in_array($plannerId, $hrdRecipients, false), 'Maintenance Planner receives People & KPI notification (has R permit)');
    assertTest(!in_array($safetyId, $hrdRecipients, false), 'Inspector K3L does NOT receive People & KPI notification (has - permit)');

    // 6. Test on-the-fly syncMissingRecipientsForUser via listForUser
    // Create an unlinked WO notification without recipients
    $notifStmt = $db->prepare("
        INSERT INTO system_notifications
        (menu_name, user_name, user_role, action_type, title, message, related_tables_json, is_read, created_at)
        VALUES ('Work Order Kanban', 'QA Unlinked', 'Planner', 'WO_UPDATE', 'QA Unlinked Notif', 'Test unlinked', '{}', 0, NOW())
    ");
    $notifStmt->execute();
    $unlinkedId = (int)$db->lastInsertId();

    // Verify initially no recipients
    $recipientStmt->execute([':id' => $unlinkedId]);
    assertTest(count($recipientStmt->fetchAll()) === 0, 'Unlinked notification initially has 0 recipients');

    // Call listForUser as Maintenance Planner
    $plannerList = NotificationAudience::listForUser($db, $plannerId, null, true, 50);
    $foundInPlannerList = false;
    foreach ($plannerList as $row) {
        if ((int)$row['notification_id'] === $unlinkedId) {
            $foundInPlannerList = true;
            break;
        }
    }
    assertTest($foundInPlannerList, 'listForUser automatically backfills missing notification for Maintenance Planner');

} finally {
    $db->rollBack();
}

echo "\n=======================================================\n";
echo "RINGKASAN HASIL TEST RBAC NOTIFIKASI: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

if ($failed > 0) exit(1);
