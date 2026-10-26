<?php
declare(strict_types=1);

/**
 * Migration script to backfill notification_recipients for all existing system_notifications
 * according to the dynamic RBAC matrix (roles with permit 'R' in the respective module).
 */

define('API_AUTH_OPTIONAL', true);

require_once dirname(__DIR__) . '/api/db.php';
require_once dirname(__DIR__) . '/core/NotificationAudience.php';

$db = Database::getInstance();

echo "Running backfill of notification recipients based on RBAC matrix 'R' permissions...\n";
$inserted = NotificationAudience::backfillRecipients($db);
echo "Backfill complete. Inserted {$inserted} recipient linkages.\n";

// Show summary for key users
$checkStmt = $db->query("
    SELECT u.user_id, u.username, r.role_name, COUNT(nr.notification_id) as notif_count
    FROM users u
    INNER JOIN roles r ON r.role_id = u.role_id
    LEFT JOIN notification_recipients nr ON nr.user_id = u.user_id
    WHERE u.is_active = 1
    GROUP BY u.user_id, u.username, r.role_name
    ORDER BY u.user_id ASC
");
echo "\nActive Users Notification Count Summary:\n";
echo str_repeat('-', 65) . "\n";
printf("%-5s | %-18s | %-24s | %s\n", "ID", "Username", "Role", "Notifications");
echo str_repeat('-', 65) . "\n";
while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-5d | %-18s | %-24s | %d\n", $row['user_id'], $row['username'], $row['role_name'], $row['notif_count']);
}
echo str_repeat('-', 65) . "\n";
