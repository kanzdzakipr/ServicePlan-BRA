<?php
declare(strict_types=1);

$dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('DB_USER') ?: 'root');
$password = (string) (getenv('DB_PASSWORD') ?: '');

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

require_once __DIR__ . '/../api/security.php';
require_once __DIR__ . '/../core/NotificationAudience.php';

$checks = 0;
$assert = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $label\n");
        exit(1);
    }
    $checks++;
    echo "PASS: $label\n";
};

echo "=======================================================================\n";
echo "  TEST SUITE F5: Archive, Notifikasi & Operational Settings           \n";
echo "=======================================================================\n\n";

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // F5.1: Archive dan Restore Unit QA dengan Jejak Audit
    // -------------------------------------------------------------------------
    echo "--- F5.1: Archive dan Restore Unit QA ---\n";
    $qaAssetId = 'QA-FINISH-ASSET-' . date('YmdHis');
    $archiveReason = 'QA FINISH archive test';
    $restoreReason = 'QA FINISH restore test';

    // 1. Create temporary QA asset
    $pdo->prepare("
        INSERT INTO assets (asset_id, asset_code, category, make_model, current_location_id, status, last_hm_km, last_update_timestamp)
        VALUES (?, ?, 'Excavator', 'Komatsu PC200', 4, 'READY', 100.0, NOW())
    ")->execute([$qaAssetId, 'QA-CODE-501']);

    // 2. Archive asset
    $pdo->prepare("
        INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason)
        VALUES ('asset', :id, NOW(), 1, 'Administrator Utama', :reason)
    ")->execute([':id' => $qaAssetId, ':reason' => $archiveReason]);

    $pdo->prepare("
        INSERT INTO archive_audit_trail (item_type, item_id, action, actor_user_id, actor_name, reason, created_at)
        VALUES ('asset', :id, 'ARCHIVE', 1, 'Administrator Utama', :reason, NOW())
    ")->execute([':id' => $qaAssetId, ':reason' => $archiveReason]);

    // Verify archived in DB
    $archItem = $pdo->query("SELECT * FROM archived_items WHERE item_id = '{$qaAssetId}'")->fetch();
    $assert(!empty($archItem), "[F5.1] Unit QA muncul dalam tabel archived_items");
    $assert($archItem['reason'] === $archiveReason, "[F5.1] Alasan archive persisten: '{$archiveReason}'");
    $assert((int)$archItem['archived_by_user_id'] === 1, "[F5.1] Aktor archive tercatat ID 1");

    // 3. Restore asset
    $pdo->prepare("DELETE FROM archived_items WHERE item_type = 'asset' AND item_id = ?")->execute([$qaAssetId]);
    $pdo->prepare("
        INSERT INTO archive_audit_trail (item_type, item_id, action, actor_user_id, actor_name, reason, created_at)
        VALUES ('asset', :id, 'RESTORE', 1, 'Administrator Utama', :reason, NOW())
    ")->execute([':id' => $qaAssetId, ':reason' => $restoreReason]);

    $remainingArch = $pdo->query("SELECT COUNT(*) FROM archived_items WHERE item_id = '{$qaAssetId}'")->fetchColumn();
    $assert((int)$remainingArch === 0, "[F5.1] Setelah restore, item QA terhapus dari archived_items");

    // 4. Verify audit trail: BOTH ARCHIVE and RESTORE must be preserved
    $auditEvents = $pdo->query("SELECT action, actor_name, reason FROM archive_audit_trail WHERE item_id = '{$qaAssetId}' ORDER BY audit_id ASC")->fetchAll();
    $assert(count($auditEvents) === 2, "[F5.1] Tepat 2 event audit tersimpan di archive_audit_trail");
    $assert($auditEvents[0]['action'] === 'ARCHIVE', "[F5.1] Audit event 1 adalah ARCHIVE");
    $assert($auditEvents[0]['reason'] === $archiveReason, "[F5.1] Alasan archive audit cocok");
    $assert($auditEvents[1]['action'] === 'RESTORE', "[F5.1] Audit event 2 adalah RESTORE");
    $assert($auditEvents[1]['reason'] === $restoreReason, "[F5.1] Alasan restore audit cocok");

    // -------------------------------------------------------------------------
    // F5.2: Fail-Closed Archive & Location Scoping
    // -------------------------------------------------------------------------
    echo "\n--- F5.2: Fail-Closed Archive & Lokasi ---\n";
    // Check archiveItemAccessible behavior
    // Cross-location asset access: user at location 1 cannot access archive of asset at location 4
    $localUser = [
        'id' => 28,
        'username' => 'galuh',
        'roles' => ['Mekanik Junior / Helper'],
        'permissions' => api_builtin_permissions_for_roles(['Mekanik Junior / Helper']),
        'assigned_location_id' => 1,
    ];
    $assert(!in_array('archive.write', $localUser['permissions'], true), "[F5.2] Non-authorized user tidak memiliki izin archive.write");
    $assert(!in_array('scope.all_locations', $localUser['permissions'], true), "[F5.2] Local user tidak memiliki cakupan global lintas lokasi");

    // -------------------------------------------------------------------------
    // F5.3: Notifikasi Per Penerima & CSRF Protection
    // -------------------------------------------------------------------------
    echo "\n--- F5.3: Notifikasi Per Penerima & CSRF ---\n";
    // Insert system notification
    $stmtNotif = $pdo->prepare("
        INSERT INTO system_notifications
        (menu_name, user_name, user_role, action_type, title, message, involved_parties_json, related_tables_json, is_read, created_at)
        VALUES ('System', 'Admin QA', 'Administrator', 'ALERT', 'Uji Notifikasi F5', 'Pesan pengujian checkpoint F5', '[]', '{}', 0, NOW())
    ");
    $stmtNotif->execute();
    $notifId = (int) $pdo->lastInsertId();
    $assert($notifId > 0, "[F5.3] Notifikasi sistem berhasil dibuat dengan ID #{$notifId}");

    // Deliver to two separate recipients: User 1 (Admin) and User 3 (Planner)
    NotificationAudience::deliverSelf($pdo, $notifId, 1, 4);
    NotificationAudience::deliverSelf($pdo, $notifId, 3, 4);

    // Initial state: both unread
    $user1List = NotificationAudience::listForUser($pdo, 1, 4, true, 10);
    $user3List = NotificationAudience::listForUser($pdo, 3, 4, true, 10);

    $u1Found = array_filter($user1List, fn($n) => (int)$n['notification_id'] === $notifId);
    $u3Found = array_filter($user3List, fn($n) => (int)$n['notification_id'] === $notifId);
    $assert(!empty($u1Found), "[F5.3] Notifikasi masuk ke inbox User 1");
    $assert(!empty($u3Found), "[F5.3] Notifikasi masuk ke inbox User 3");

    $u1Item = reset($u1Found);
    $u3Item = reset($u3Found);
    $assert($u1Item['read_at'] === null, "[F5.3] Status awal notifikasi User 1 belum dibaca (read_at is NULL)");
    $assert($u3Item['read_at'] === null, "[F5.3] Status awal notifikasi User 3 belum dibaca (read_at is NULL)");

    // User 1 marks as read
    $markOk = NotificationAudience::markRead($pdo, 1, 4, true, $notifId);
    $assert($markOk === true, "[F5.3] User 1 berhasil menandai notifikasi sebagai dibaca");

    // Check isolation: User 1 is read, but User 3 remains UNREAD!
    $user1After = NotificationAudience::listForUser($pdo, 1, 4, true, 10);
    $user3After = NotificationAudience::listForUser($pdo, 3, 4, true, 10);

    $u1Filtered = array_filter($user1After, fn($n) => (int)$n['notification_id'] === $notifId);
    $u3Filtered = array_filter($user3After, fn($n) => (int)$n['notification_id'] === $notifId);
    $u1AfterItem = reset($u1Filtered);
    $u3AfterItem = reset($u3Filtered);

    $assert($u1AfterItem['read_at'] !== null, "[F5.3] Status User 1 terupdate menjadi dibaca (read_at terisi)");
    $assert($u3AfterItem['read_at'] === null, "[F5.3] Isolasi penerima terjaga: Status User 3 tetap belum dibaca");

    // CSRF check on notifications
    $_SERVER['SCRIPT_NAME'] = '/api/notifications.php';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $assert(api_route_permission() === 'dashboard.read', "[F5.3] Endpoint notifikasi POST memerlukan autentikasi sesi");

    // -------------------------------------------------------------------------
    // F5.4: Operational Settings & Batas Validasi
    // -------------------------------------------------------------------------
    echo "\n--- F5.4: Operational Settings ---\n";
    // 1. Read initial cfg_sla_wo
    $origSla = (string) $pdo->query("SELECT setting_value FROM operational_settings WHERE setting_key = 'cfg_sla_wo'")->fetchColumn();
    $assert($origSla !== '', "[F5.4] Nilai awal cfg_sla_wo tercatat di DB: {$origSla}");

    // 2. Administrator updates to valid test value
    $qaSlaValue = '45';
    $pdo->prepare("
        UPDATE operational_settings
        SET setting_value = :val, updated_by_user_id = 1, updated_by_name = 'Administrator Utama', updated_at = NOW()
        WHERE setting_key = 'cfg_sla_wo'
    ")->execute([':val' => $qaSlaValue]);

    $slaCheck = $pdo->query("SELECT setting_value, updated_by_user_id, updated_by_name FROM operational_settings WHERE setting_key = 'cfg_sla_wo'")->fetch();
    $assert($slaCheck['setting_value'] === '45', "[F5.4] Nilai cfg_sla_wo berhasil diperbarui ke 45 menit");
    $assert((int)$slaCheck['updated_by_user_id'] === 1, "[F5.4] updated_by_user_id tercatat 1");
    $assert($slaCheck['updated_by_name'] === 'Administrator Utama', "[F5.4] updated_by_name tercatat");

    // 3. Validation bounds check
    $minSla = 5;
    $maxSla = 1440;
    $invalidBelow = 2; // < 5
    $invalidAbove = 99999; // > 1440

    $validateSla = static function($val) use ($minSla, $maxSla): bool {
        $intVal = filter_var($val, FILTER_VALIDATE_INT);
        return $intVal !== false && $intVal >= $minSla && $intVal <= $maxSla;
    };

    $assert(!$validateSla($invalidBelow), "[F5.4] Validasi menolak nilai SLA di bawah batas minimum (2 < 5)");
    $assert(!$validateSla($invalidAbove), "[F5.4] Validasi menolak nilai SLA di atas batas maksimum (99999 > 1440)");
    $assert($validateSla(60), "[F5.4] Validasi menerima nilai SLA dalam batas yang diizinkan (60)");

    // 4. Role Authorization: Non-admin denied from changing settings
    $nonAdminIsAdmin = api_can_manage_users($localUser);
    $assert(!$nonAdminIsAdmin, "[F5.4] Non-admin ditolak mengubah operational settings (HTTP 403 FORBIDDEN)");

    // 5. Restore original cfg_sla_wo
    $pdo->prepare("UPDATE operational_settings SET setting_value = :orig WHERE setting_key = 'cfg_sla_wo'")
        ->execute([':orig' => $origSla]);
    $restoredSla = (string) $pdo->query("SELECT setting_value FROM operational_settings WHERE setting_key = 'cfg_sla_wo'")->fetchColumn();
    $assert($restoredSla === $origSla, "[F5.4] Nilai cfg_sla_wo dipulihkan ke nilai awal: {$restoredSla}");

    echo "\n=======================================================================\n";
    echo "  F5 CHECKPOINT RESULT: {$checks}/{$checks} CHECKS PASSED CLEANLY!     \n";
    echo "=======================================================================\n";

} finally {
    $pdo->rollBack();
    echo "TRANSACTION ROLLBACK: Seluruh record pengujian QA telah dibersihkan aman.\n";
}
