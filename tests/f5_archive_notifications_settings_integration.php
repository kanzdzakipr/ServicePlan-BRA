<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
define('API_SKIP_ROUTING', true);

$dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('DB_USER') ?: 'root');
$password = (string) (getenv('DB_PASSWORD') ?: '');

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$passed = 0;
function f5Assert(bool $condition, string $msg): void {
    global $passed;
    if (!$condition) {
        throw new RuntimeException("FAIL: " . $msg);
    }
    $passed++;
    echo "PASS: {$msg}\n";
}

echo "=======================================================================\n";
echo "  TEST SUITE F5: Archive Audit, Notifications & Settings Integration   \n";
echo "=======================================================================\n\n";

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // 1. Verify Schema Additive Columns & Tables
    // -------------------------------------------------------------------------
    $colsArchive = $pdo->query("SHOW COLUMNS FROM archived_items")->fetchAll(PDO::FETCH_COLUMN);
    f5Assert(in_array('archived_by_user_id', $colsArchive, true), "archived_items has archived_by_user_id column");
    f5Assert(in_array('archived_by_name', $colsArchive, true), "archived_items has archived_by_name column");
    f5Assert(in_array('reason', $colsArchive, true), "archived_items has reason column");

    $tablesAudit = $pdo->query("SHOW TABLES LIKE 'archive_audit_trail'")->fetchAll();
    f5Assert(count($tablesAudit) === 1, "archive_audit_trail table exists in database");

    $tablesSettings = $pdo->query("SHOW TABLES LIKE 'operational_settings'")->fetchAll();
    f5Assert(count($tablesSettings) === 1, "operational_settings table exists in database");

    // -------------------------------------------------------------------------
    // 2. Operational Settings Seed & Retrieval
    // -------------------------------------------------------------------------
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM operational_settings");
    $settingsMap = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
    
    f5Assert(isset($settingsMap['cfg_sla_wo']), "cfg_sla_wo setting exists in operational_settings");
    f5Assert(isset($settingsMap['cfg_tire_red']), "cfg_tire_red setting exists in operational_settings");
    f5Assert(isset($settingsMap['cfg_pm_warning']), "cfg_pm_warning setting exists in operational_settings");
    f5Assert(isset($settingsMap['cfg_fuel_anomali']), "cfg_fuel_anomali setting exists in operational_settings");
    f5Assert(isset($settingsMap['cfg_idle_timeout']), "cfg_idle_timeout setting exists in operational_settings");
    f5Assert(isset($settingsMap['cfg_wo_prefix']), "cfg_wo_prefix setting exists in operational_settings");

    // Test Updating Settings via Database Query
    $testSla = 45;
    $stmtUpdate = $pdo->prepare("
        UPDATE operational_settings 
        SET setting_value = :val, updated_by_user_id = 1, updated_by_name = 'Admin QA'
        WHERE setting_key = 'cfg_sla_wo'
    ");
    $stmtUpdate->execute([':val' => (string)$testSla]);
    
    $checkVal = $pdo->query("SELECT setting_value FROM operational_settings WHERE setting_key = 'cfg_sla_wo'")->fetchColumn();
    f5Assert((int)$checkVal === 45, "operational_settings successfully updated and persisted to DB");

    // -------------------------------------------------------------------------
    // 3. Archive & Restore Lifecycle with Audit Trail
    // -------------------------------------------------------------------------
    $testItemType = 'asset';
    $testItemId = 'QA-TEST-ASSET-01';
    $testActorId = 1;
    $testActorName = 'Administrator Utama';
    $archiveReason = 'Unit overhaul jangka panjang (QA Archive Test)';

    // Step A: Archive item
    $stmtArch = $pdo->prepare("
        INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason)
        VALUES (:type, :id, NOW(), :uid, :uname, :reason)
        ON DUPLICATE KEY UPDATE archived_at = NOW(), archived_by_user_id = :uid, archived_by_name = :uname, reason = :reason
    ");
    $stmtArch->execute([
        ':type' => $testItemType,
        ':id' => $testItemId,
        ':uid' => $testActorId,
        ':uname' => $testActorName,
        ':reason' => $archiveReason,
    ]);

    // Audit trail log for archive
    $stmtAuditArch = $pdo->prepare("
        INSERT INTO archive_audit_trail (item_type, item_id, action, actor_user_id, actor_name, reason, created_at)
        VALUES (:type, :id, 'ARCHIVE', :uid, :uname, :reason, NOW())
    ");
    $stmtAuditArch->execute([
        ':type' => $testItemType,
        ':id' => $testItemId,
        ':uid' => $testActorId,
        ':uname' => $testActorName,
        ':reason' => $archiveReason,
    ]);

    // Verify archived_items record
    $archRow = $pdo->query("SELECT * FROM archived_items WHERE item_type = '{$testItemType}' AND item_id = '{$testItemId}'")->fetch();
    f5Assert($archRow !== false, "QA item recorded in archived_items table");
    f5Assert((int)$archRow['archived_by_user_id'] === $testActorId, "archived_by_user_id matches actor ID 1");
    f5Assert($archRow['reason'] === $archiveReason, "Archive reason accurately stored in DB");

    // Verify archive_audit_trail record
    $auditArch = $pdo->query("SELECT * FROM archive_audit_trail WHERE item_type = '{$testItemType}' AND item_id = '{$testItemId}' AND action = 'ARCHIVE'")->fetch();
    f5Assert($auditArch !== false, "Action 'ARCHIVE' accurately recorded in archive_audit_trail");
    f5Assert($auditArch['actor_name'] === $testActorName, "Audit trail records correct actor name");

    // Step B: Restore item
    $restoreReason = 'Unit telah selesai servis dan siap operasi kembali (QA Restore Test)';
    $stmtDel = $pdo->prepare("DELETE FROM archived_items WHERE item_type = :type AND item_id = :id");
    $stmtDel->execute([':type' => $testItemType, ':id' => $testItemId]);

    // Audit trail log for restore
    $stmtAuditRest = $pdo->prepare("
        INSERT INTO archive_audit_trail (item_type, item_id, action, actor_user_id, actor_name, reason, created_at)
        VALUES (:type, :id, 'RESTORE', :uid, :uname, :reason, NOW())
    ");
    $stmtAuditRest->execute([
        ':type' => $testItemType,
        ':id' => $testItemId,
        ':uid' => $testActorId,
        ':uname' => $testActorName,
        ':reason' => $restoreReason,
    ]);

    // Verify removed from archived_items
    $archAfterRestore = $pdo->query("SELECT COUNT(*) FROM archived_items WHERE item_type = '{$testItemType}' AND item_id = '{$testItemId}'")->fetchColumn();
    f5Assert((int)$archAfterRestore === 0, "Restored item successfully removed from archived_items table");

    // Verify audit trail recorded 'RESTORE'
    $auditRest = $pdo->query("SELECT * FROM archive_audit_trail WHERE item_type = '{$testItemType}' AND item_id = '{$testItemId}' AND action = 'RESTORE'")->fetch();
    f5Assert($auditRest !== false, "Action 'RESTORE' accurately recorded in archive_audit_trail");
    f5Assert($auditRest['reason'] === $restoreReason, "Restore reason accurately recorded in audit trail");

    // -------------------------------------------------------------------------
    // 4. Role Authorization Negative Checks
    // -------------------------------------------------------------------------
    require_once dirname(__DIR__) . '/api/security.php';

    $mekanikRole = 'Mekanik Junior / Helper';
    $mekanikPermissions = api_builtin_permissions_for_roles([$mekanikRole]);
    f5Assert(!in_array('archive.write', $mekanikPermissions, true), "Negative Check: Mekanik Junior has NO archive.write permission");
    f5Assert(!in_array('*', $mekanikPermissions, true), "Negative Check: Mekanik Junior has NO wildcard admin permission");

    $adminRole = 'Administrator';
    $adminPermissions = api_builtin_permissions_for_roles([$adminRole]);
    f5Assert(in_array('*', $adminPermissions, true), "Positive Check: Administrator has full wildcard permissions");

    // -------------------------------------------------------------------------
    // 5. Notification Audience & Mark Read Lifecycle
    // -------------------------------------------------------------------------
    require_once dirname(__DIR__) . '/core/NotificationAudience.php';

    // Insert test notification
    $stmtNotif = $pdo->prepare("
        INSERT INTO system_notifications
        (menu_name, user_name, user_role, action_type, title, message, involved_parties_json, related_tables_json, is_read, created_at)
        VALUES ('System', 'Admin QA', 'Administrator', 'TEST', 'QA Notif F5', 'Pengujian notifikasi F5', '[]', '{}', 0, NOW())
    ");
    $stmtNotif->execute();
    $notifId = (int) $pdo->lastInsertId();
    f5Assert($notifId > 0, "Test notification successfully created with ID: {$notifId}");

    // Deliver to recipient user ID 1
    NotificationAudience::deliverSelf($pdo, $notifId, 1, 4);

    // Verify in recipient list
    $list = NotificationAudience::listForUser($pdo, 1, 4, true, 10);
    $found = array_filter($list, fn($n) => (int)$n['notification_id'] === $notifId);
    f5Assert(!empty($found), "Notification delivered and listed for user ID 1");
    $notifItem = reset($found);
    f5Assert($notifItem['read_at'] === null, "Notification initially unread (read_at is NULL)");

    // Mark as read
    $markResult = NotificationAudience::markRead($pdo, 1, 4, true, $notifId);
    f5Assert($markResult === true, "Notification markRead returned true");

    // Verify read status
    $listAfter = NotificationAudience::listForUser($pdo, 1, 4, true, 10);
    $foundAfter = array_filter($listAfter, fn($n) => (int)$n['notification_id'] === $notifId);
    $notifItemAfter = reset($foundAfter);
    f5Assert($notifItemAfter['read_at'] !== null, "Notification officially marked as read (read_at timestamp populated)");

    echo "\nAll {$passed} checks passed successfully.\n";
} finally {
    $pdo->rollBack();
    echo "TRANSACTION ROLLBACK: All test records cleaned up safely. 0 QA rows left in database.\n";
}
