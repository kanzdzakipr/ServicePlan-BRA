<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
define('API_SKIP_ROUTING', true);

require_once dirname(__DIR__) . '/core/NotificationAudience.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$db = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$users = NotificationAudience::activeUsers($db);
$planner = null;
$logistic = null;
$otherLocation = null;
foreach ($users as $candidate) {
    if (in_array('Maintenance Planner', $candidate['roles'], true)) $planner = $candidate;
    if (in_array('Logistic Head', $candidate['roles'], true)) $logistic = $candidate;
}
if (!$planner || !$logistic || $planner['assigned_location_id'] === null ||
    $planner['assigned_location_id'] !== $logistic['assigned_location_id']) {
    fwrite(STDERR, "SKIP: perlu planner dan logistik aktif di lokasi yang sama.\n");
    exit(0);
}
$locationId = $planner['assigned_location_id'];
foreach ($users as $candidate) {
    if ($candidate['assigned_location_id'] !== null && $candidate['assigned_location_id'] !== $locationId &&
        !array_intersect($candidate['roles'], NotificationAudience::GLOBAL_SCOPE_ROLES)) {
        $otherLocation = $candidate;
        break;
    }
}

$db->beginTransaction();
try {
    $stmt = $db->prepare(
        "INSERT INTO system_notifications
         (menu_name, user_name, user_role, action_type, title, message, related_tables_json, is_read, created_at)
         VALUES ('Work Order Kanban', 'QA', 'Planner', 'WO_CREATE', :title, 'QA transaction rollback', :related, 0, NOW())"
    );
    $stmt->execute([
        ':title' => 'QA-BRIAN-NOTIFICATION-AUDIENCE-' . getmypid(),
        ':related' => json_encode(['wo_id' => 'QA-BRIAN-WO', 'location_id' => $locationId]),
    ]);
    $id = (int) $db->lastInsertId();
    $count = NotificationAudience::deliverWo($db, $id, $locationId, (int) $planner['user_id']);
    if ($count < 2) throw new RuntimeException('Planner dan logistik tidak menerima notifikasi.');
    $plannerRows = NotificationAudience::listForUser($db, (int) $planner['user_id'], $locationId, false, 100);
    $logisticRows = NotificationAudience::listForUser($db, (int) $logistic['user_id'], $locationId, false, 100);
    if (!in_array($id, array_column($plannerRows, 'notification_id')) ||
        !in_array($id, array_column($logisticRows, 'notification_id'))) {
        throw new RuntimeException('Notifikasi tidak terlihat oleh dua penerima yang benar.');
    }
    if ($otherLocation && NotificationAudience::markRead($db, (int) $otherLocation['user_id'], $otherLocation['assigned_location_id'], false, $id)) {
        throw new RuntimeException('Pengguna lokasi lain dapat menandai notifikasi dibaca.');
    }
    if (!NotificationAudience::markRead($db, (int) $planner['user_id'], $locationId, false, $id)) {
        throw new RuntimeException('Planner gagal menandai baca.');
    }
    $read = $db->prepare('SELECT user_id, read_at FROM notification_recipients WHERE notification_id = :id');
    $read->execute([':id' => $id]);
    $states = array_column($read->fetchAll(), 'read_at', 'user_id');
    if ($states[$planner['user_id']] === null || $states[$logistic['user_id']] !== null) {
        throw new RuntimeException('Status baca antar akun tidak terisolasi.');
    }
    NotificationAudience::markAllRead($db, (int) $logistic['user_id'], $locationId, false);
    if (NotificationAudience::markRead($db, (int) $logistic['user_id'], $locationId, false, $id) !== true) {
        throw new RuntimeException('Mark all tidak memengaruhi akun penerima sendiri.');
    }
    echo "PASS: audience, lokasi, baca per user, dan mark-all terisolasi (transaksi rollback).\n";
} finally {
    $db->rollBack();
}
