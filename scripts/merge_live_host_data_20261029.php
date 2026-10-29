<?php
/**
 * scripts/merge_live_host_data_20261029.php
 * Merges manual user inputs from live dump (live_compare_29102026) into local database (u646470441_serviceplanbra).
 */

$localDbName = 'u646470441_serviceplanbra';
$liveDbName  = 'live_compare_29102026';

$pdoLocal = new PDO("mysql:host=127.0.0.1;dbname=$localDbName;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$pdoLive = new PDO("mysql:host=127.0.0.1;dbname=$liveDbName;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$migrationKey = 'MERGE_LIVE_HOST_DATA_20261029';

// Check if already merged
$chk = $pdoLocal->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = ?");
$chk->execute([$migrationKey]);
if ($chk->fetchColumn() > 0) {
    echo "Migration $migrationKey has already been applied.\n";
    exit(0);
}

echo "Starting merge of Live Host data into Local database...\n";
$pdoLocal->beginTransaction();

try {
    // 1. Merge User ID 52 (dheny_meilisna)
    echo "1. Merging User 52 (dheny_meilisna)...\n";
    $user = $pdoLive->query("SELECT * FROM users WHERE user_id = 52")->fetch();
    if ($user) {
        $stmt = $pdoLocal->prepare("
            INSERT INTO users (user_id, username, password_hash, full_name, role_id, assigned_location_id, is_active, created_at)
            VALUES (:user_id, :username, :password_hash, :full_name, :role_id, :assigned_location_id, :is_active, :created_at)
            ON DUPLICATE KEY UPDATE 
                full_name = VALUES(full_name),
                role_id = VALUES(role_id),
                assigned_location_id = VALUES(assigned_location_id),
                is_active = VALUES(is_active)
        ");
        $stmt->execute($user);
        echo "   -> User inserted/updated.\n";
    }

    // 2. Merge User Role (ID 55)
    echo "2. Merging User Role ID 55...\n";
    $uRole = $pdoLive->query("SELECT * FROM user_roles WHERE user_role_id = 55")->fetch();
    if ($uRole) {
        $stmt = $pdoLocal->prepare("
            INSERT INTO user_roles (user_role_id, user_id, role_id, is_primary, assigned_at)
            VALUES (:user_role_id, :user_id, :role_id, :is_primary, :assigned_at)
            ON DUPLICATE KEY UPDATE 
                role_id = VALUES(role_id),
                is_primary = VALUES(is_primary)
        ");
        $stmt->execute($uRole);
        echo "   -> User role inserted/updated.\n";
    }

    // 3. Merge User Audit Trail (ID 46)
    echo "3. Merging User Audit Trail ID 46...\n";
    $uAudit = $pdoLive->query("SELECT * FROM user_audit_trail WHERE audit_id = 46")->fetch();
    if ($uAudit) {
        $stmt = $pdoLocal->prepare("
            INSERT INTO user_audit_trail (audit_id, action, actor_user_id, actor_username, target_user_id, target_username, details_json, created_at)
            VALUES (:audit_id, :action, :actor_user_id, :actor_username, :target_user_id, :target_username, :details_json, :created_at)
            ON DUPLICATE KEY UPDATE details_json = VALUES(details_json)
        ");
        $stmt->execute($uAudit);
        echo "   -> User audit trail inserted/updated.\n";
    }

    // 4. Merge Operational Settings (cfg_rbac_matrix & cfg_fuel_price_per_liter)
    echo "4. Merging Operational Settings...\n";
    $rbacSetting = $pdoLive->query("SELECT * FROM operational_settings WHERE setting_key = 'cfg_rbac_matrix'")->fetch();
    if ($rbacSetting) {
        $cols = array_keys($rbacSetting);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['setting_key'])));

        $stmt = $pdoLocal->prepare("INSERT INTO operational_settings ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($rbacSetting);
        echo "   -> cfg_rbac_matrix setting inserted/updated.\n";
    }

    $fuelSetting = $pdoLive->query("SELECT * FROM operational_settings WHERE setting_key = 'cfg_fuel_price_per_liter'")->fetch();
    if ($fuelSetting) {
        $stmt = $pdoLocal->prepare("
            UPDATE operational_settings 
            SET setting_value = :setting_value, updated_at = :updated_at 
            WHERE setting_key = 'cfg_fuel_price_per_liter'
        ");
        $stmt->execute([
            'setting_value' => $fuelSetting['setting_value'],
            'updated_at' => $fuelSetting['updated_at']
        ]);
        echo "   -> cfg_fuel_price_per_liter updated.\n";
    }

    // 5. Merge Asset PF-41002
    echo "5. Merging Asset PF-41002...\n";
    $asset = $pdoLive->query("SELECT * FROM assets WHERE asset_id = 'PF-41002'")->fetch();
    if ($asset) {
        $cols = array_keys($asset);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['asset_id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO assets ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($asset);
        echo "   -> Asset PF-41002 inserted/updated.\n";
    }

    // 6. Merge Asset Gallery Photos for PF-41002
    echo "6. Merging Asset Gallery Photos for PF-41002...\n";
    $photos = $pdoLive->query("SELECT * FROM asset_gallery_photos WHERE asset_id = 'PF-41002'")->fetchAll();
    foreach ($photos as $p) {
        $cols = array_keys($p);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['photo_id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO asset_gallery_photos ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($p);
    }
    echo "   -> " . count($photos) . " photos inserted/updated.\n";

    // 7. Merge Asset Service History for PF-41002
    echo "7. Merging Asset Service History for PF-41002...\n";
    $svc = $pdoLive->query("SELECT * FROM asset_service_history WHERE asset_id = 'PF-41002'")->fetchAll();
    foreach ($svc as $s) {
        $cols = array_keys($s);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['service_log_id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO asset_service_history ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($s);
    }
    echo "   -> " . count($svc) . " service logs inserted/updated.\n";

    // 8. Update Asset MG-00003 status to BREAKDOWN and update last_hm_km
    echo "8. Updating Asset MG-00003 status and HM...\n";
    $stmt = $pdoLocal->prepare("
        UPDATE assets 
        SET status = 'BREAKDOWN',
            last_hm_km = 970917.80,
            last_update_timestamp = '2026-10-28 07:14:37'
        WHERE asset_id = 'MG-00003'
    ");
    $stmt->execute();
    echo "   -> MG-00003 updated to BREAKDOWN (HM 970,917.80).\n";

    // 9. Merge Work Order WO-26-46472482
    echo "9. Merging Work Order WO-26-46472482...\n";
    $wo = $pdoLive->query("SELECT * FROM work_orders WHERE wo_id = 'WO-26-46472482'")->fetch();
    if ($wo) {
        $cols = array_keys($wo);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['wo_id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO work_orders ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($wo);
        echo "   -> WO-26-46472482 inserted/updated.\n";
    }

    // 10. Merge Work Order Timeline for WO-26-46472482
    echo "10. Merging Work Order Timeline...\n";
    $woTl = $pdoLive->query("SELECT * FROM work_order_timeline WHERE wo_id = 'WO-26-46472482'")->fetchAll();
    foreach ($woTl as $tl) {
        $cols = array_keys($tl);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['timeline_id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO work_order_timeline ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($tl);
    }
    echo "   -> " . count($woTl) . " timeline rows inserted/updated.\n";

    // 11. Merge Purchase Request SPB-2610-8824
    echo "11. Merging Purchase Request SPB-2610-8824...\n";
    $pr = $pdoLive->query("SELECT * FROM purchase_requests WHERE spb_id = 'SPB-2610-8824'")->fetch();
    if ($pr) {
        $cols = array_keys($pr);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['spb_id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO purchase_requests ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($pr);
        echo "   -> SPB-2610-8824 inserted/updated.\n";
    }

    // 12. Merge Purchase Request Item
    echo "12. Merging Purchase Request Items...\n";
    $prItems = $pdoLive->query("SELECT * FROM purchase_request_items WHERE spb_id = 'SPB-2610-8824'")->fetchAll();
    foreach ($prItems as $pri) {
        $cols = array_keys($pri);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO purchase_request_items ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($pri);
    }
    echo "   -> " . count($prItems) . " PR items inserted/updated.\n";

    // 13. Merge System Notification 148 & Recipients
    echo "13. Merging System Notification 148 & Recipients...\n";
    $notif = $pdoLive->query("SELECT * FROM system_notifications WHERE notification_id = 148")->fetch();
    if ($notif) {
        $cols = array_keys($notif);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $updates = implode(", ", array_map(fn($c) => "`$c` = VALUES(`$c`)", array_diff($cols, ['notification_id'])));

        $stmt = $pdoLocal->prepare("INSERT INTO system_notifications ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($notif);
        echo "   -> Notification 148 inserted/updated.\n";
    }

    $recipients = $pdoLive->query("SELECT * FROM notification_recipients WHERE notification_id = 148")->fetchAll();
    foreach ($recipients as $rec) {
        $cols = array_keys($rec);
        $fields = implode(", ", array_map(fn($c) => "`$c`", $cols));
        $placeholders = implode(", ", array_map(fn($c) => ":$c", $cols));
        $stmt = $pdoLocal->prepare("INSERT IGNORE INTO notification_recipients ($fields) VALUES ($placeholders)");
        $stmt->execute($rec);
    }
    echo "   -> " . count($recipients) . " recipients inserted/updated.\n";

    // 14. Record migration in app_data_migrations
    echo "14. Recording migration in app_data_migrations...\n";
    $stmt = $pdoLocal->prepare("INSERT INTO app_data_migrations (migration_key, applied_at) VALUES (?, NOW())");
    $stmt->execute([$migrationKey]);

    $pdoLocal->commit();
    echo "SUCCESS: All Live Host data successfully merged into Local database!\n";
} catch (Exception $e) {
    if ($pdoLocal->inTransaction()) {
        $pdoLocal->rollBack();
    }
    echo "ERROR during merge: " . $e->getMessage() . "\n";
    exit(1);
}
