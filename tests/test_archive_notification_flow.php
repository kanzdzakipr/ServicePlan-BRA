<?php
declare(strict_types=1);

$dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('DB_USER') ?: 'root');
$password = (string) (getenv('DB_PASSWORD') ?: '');

$db = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

require_once __DIR__ . '/../core/NotificationAudience.php';

$db->beginTransaction();

try {
    echo "=== TEST: Archive & Restore Notification Flow ===\n";

    // 1. Pick an active asset
    $stmt = $db->query("SELECT asset_id, asset_code, make_model, current_location_id FROM assets WHERE asset_id NOT IN (SELECT item_id FROM archived_items WHERE item_type = 'asset') LIMIT 1");
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$asset) {
        throw new RuntimeException("Tidak ada asset aktif untuk diuji.");
    }

    $assetId = (string) $asset['asset_id'];
    $locationId = isset($asset['current_location_id']) ? (int) $asset['current_location_id'] : null;
    $actorId = 2; // Dany Agung (Equipment Manager)
    $actorName = 'Dany Agung';
    $actorRole = 'Equipment Manager';
    $reason = 'Uji coba pengarsipan sistem';

    echo "[1] Testing Archive Notification Generation for Asset {$assetId}...\n";

    // Insert archive item
    $stmtArch = $db->prepare("INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason) VALUES ('asset', :id, NOW(), :uid, :uname, :reason)");
    $stmtArch->execute([':id' => $assetId, ':uid' => $actorId, ':uname' => $actorName, ':reason' => $reason]);

    // Insert notification
    $unitLabel = "Unit {$asset['asset_code']}";
    $notifTitle = "{$unitLabel} Dipindahkan ke Arsip";
    $notifMessage = "{$unitLabel} telah dipindahkan ke arsip oleh {$actorName}. Alasan: {$reason}";
    $relatedTables = [
        'records' => [
            ['table' => 'assets', 'id' => $assetId],
            ['table' => 'archived_items', 'id' => $assetId],
        ],
        'item_type' => 'asset',
        'item_id' => $assetId,
        'location_id' => $locationId,
        'asset_id' => $assetId,
    ];

    $notifStmt = $db->prepare("
        INSERT INTO system_notifications
            (menu_name, user_name, user_role, action_type, title, message,
             involved_parties_json, related_tables_json, is_read, created_at)
        VALUES ('Archive', :uname, :urole, 'ARCHIVE_ASSET', :title, :msg, :parties, :tables, 0, NOW())
    ");
    $notifStmt->execute([
        ':uname' => $actorName,
        ':urole' => $actorRole,
        ':title' => $notifTitle,
        ':msg' => $notifMessage,
        ':parties' => json_encode([$actorName], JSON_UNESCAPED_UNICODE),
        ':tables' => json_encode($relatedTables, JSON_UNESCAPED_UNICODE),
    ]);
    $archiveNotifId = (int) $db->lastInsertId();
    if ($archiveNotifId <= 0) {
        throw new RuntimeException("Gagal insert system_notifications untuk archive.");
    }

    $deliveredArchive = NotificationAudience::deliverWo($db, $archiveNotifId, $locationId, $actorId);
    echo "    Archive notif created (ID: {$archiveNotifId}), delivered to {$deliveredArchive} recipients.\n";

    // Verify Asset Manager (Widya, User ID 15) receives it
    $widyaNotifs = NotificationAudience::listForUser($db, 15, null, true, 10);
    $foundArchiveNotif = false;
    foreach ($widyaNotifs as $n) {
        if ((int) $n['notification_id'] === $archiveNotifId) {
            $foundArchiveNotif = true;
            break;
        }
    }
    if (!$foundArchiveNotif) {
        throw new RuntimeException("Asset Manager (User ID 15) tidak menerima notifikasi archive.");
    }
    echo "    PASS: Asset Manager (User ID 15) menerima notifikasi archive.\n";

    // 2. Testing Restore Notification
    echo "[2] Testing Restore Notification Generation for Asset {$assetId}...\n";
    $delArch = $db->prepare("DELETE FROM archived_items WHERE item_type = 'asset' AND item_id = :id");
    $delArch->execute([':id' => $assetId]);

    $restoreTitle = "{$unitLabel} Dipulihkan dari Arsip";
    $restoreMessage = "{$unitLabel} telah dipulihkan ke operasional aktif oleh Widya Apriani. Alasan: Pengembalian operasional";
    $notifRestoreStmt = $db->prepare("
        INSERT INTO system_notifications
            (menu_name, user_name, user_role, action_type, title, message,
             involved_parties_json, related_tables_json, is_read, created_at)
        VALUES ('Archive', 'Widya Apriani', 'Asset Manager', 'RESTORE_ASSET', :title, :msg, :parties, :tables, 0, NOW())
    ");
    $notifRestoreStmt->execute([
        ':title' => $restoreTitle,
        ':msg' => $restoreMessage,
        ':parties' => json_encode(['Widya Apriani'], JSON_UNESCAPED_UNICODE),
        ':tables' => json_encode($relatedTables, JSON_UNESCAPED_UNICODE),
    ]);
    $restoreNotifId = (int) $db->lastInsertId();
    if ($restoreNotifId <= 0) {
        throw new RuntimeException("Gagal insert system_notifications untuk restore.");
    }

    $deliveredRestore = NotificationAudience::deliverWo($db, $restoreNotifId, $locationId, 15);
    echo "    Restore notif created (ID: {$restoreNotifId}), delivered to {$deliveredRestore} recipients.\n";

    // Verify Equipment Manager (Dany Agung, User ID 2) receives it
    $danyNotifs = NotificationAudience::listForUser($db, 2, null, true, 10);
    $foundRestoreNotif = false;
    foreach ($danyNotifs as $n) {
        if ((int) $n['notification_id'] === $restoreNotifId) {
            $foundRestoreNotif = true;
            break;
        }
    }
    if (!$foundRestoreNotif) {
        throw new RuntimeException("Equipment Manager (User ID 2) tidak menerima notifikasi restore.");
    }
    echo "    PASS: Equipment Manager (User ID 2) menerima notifikasi restore.\n";

    echo "\n=== ALL NOTIFICATION FLOW CHECKS PASSED ===\n";
} finally {
    $db->rollBack();
    echo "Transaction safely rolled back. No test records left behind.\n";
}

