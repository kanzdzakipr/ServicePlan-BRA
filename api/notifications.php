<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/NotificationAudience.php';

$db = Database::getInstance();
api_require_permission('dashboard.read');
$user = api_current_user();
$userId = (int) ($user['id'] ?? 0);
$locationId = api_current_location_id();
$globalScope = api_has_global_location_scope();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
    $rows = NotificationAudience::listForUser($db, $userId, $locationId, $globalScope, $limit);
    $results = array_map(static function (array $row): array {
        $related = json_decode((string) ($row['related_tables_json'] ?? '{}'), true) ?: [];
        $woId = $related['wo_id'] ?? null;
        $assetId = $related['asset_id'] ?? null;
        foreach (($related['records'] ?? []) as $record) {
            if (!$woId && ($record['table'] ?? '') === 'work_orders') $woId = (string) ($record['id'] ?? '');
            if (!$assetId && ($record['table'] ?? '') === 'assets') $assetId = (string) ($record['id'] ?? '');
        }
        return [
            'id' => (int) $row['notification_id'],
            'menu' => (string) $row['menu_name'],
            'badgeClass' => strtolower(explode(' ', trim((string) $row['menu_name']))[0] ?: 'wo'),
            'user' => (string) $row['user_name'],
            'role' => (string) $row['user_role'],
            'actionType' => (string) $row['action_type'],
            'title' => (string) $row['title'],
            'message' => (string) $row['message'],
            'involvedParties' => json_decode((string) ($row['involved_parties_json'] ?? '[]'), true) ?: [],
            'relatedTables' => $related,
            'woId' => $woId,
            'assetId' => $assetId,
            'locationId' => $related['location_id'] ?? null,
            'isRead' => $row['read_at'] !== null,
            'timestamp' => date('H:i \W\I\B (d M Y)', strtotime((string) $row['created_at'])),
        ];
    }, $rows);
    api_json_response(200, ['status' => 'success', 'data' => $results]);
}

if ($method !== 'POST') {
    api_json_response(405, ['status' => 'error', 'code' => 'METHOD_NOT_ALLOWED', 'message' => 'Metode tidak didukung.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    api_json_response(422, ['status' => 'error', 'code' => 'INVALID_PAYLOAD', 'message' => 'Payload tidak valid.']);
}
$action = strtoupper(trim((string) ($input['action'] ?? 'CREATE')));
if ($action === 'MARK_READ') {
    $id = (int) ($input['notification_id'] ?? $input['id'] ?? 0);
    if ($id <= 0) api_json_response(422, ['status' => 'error', 'message' => 'ID notifikasi tidak valid.']);
    if (!NotificationAudience::markRead($db, $userId, $locationId, $globalScope, $id)) {
        api_json_response(404, ['status' => 'error', 'code' => 'NOT_FOUND', 'message' => 'Notifikasi tidak ditemukan.']);
    }
    api_json_response(200, ['status' => 'success', 'message' => 'Notifikasi ditandai dibaca.']);
}
if ($action === 'MARK_ALL_READ') {
    $updated = NotificationAudience::markAllRead($db, $userId, $locationId, $globalScope);
    api_json_response(200, ['status' => 'success', 'updated' => $updated]);
}
if ($action !== 'CREATE') {
    api_json_response(422, ['status' => 'error', 'code' => 'INVALID_ACTION', 'message' => 'Aksi notifikasi tidak dikenal.']);
}

// Client-generated notifications are private to the authenticated actor.
// WO/part/service audience is generated only by trusted server-side producers.
$title = trim((string) ($input['title'] ?? 'Pembaruan Aktivitas Sistem'));
$message = trim((string) ($input['message'] ?? 'Pembaruan transaksi tercatat di sistem.'));
if ($title === '' || $message === '' || mb_strlen($title) > 255 || mb_strlen($message) > 10000) {
    api_json_response(422, ['status' => 'error', 'code' => 'VALIDATION_ERROR', 'message' => 'Judul atau isi notifikasi tidak valid.']);
}
$related = $input['related_tables_json'] ?? $input['relatedTables'] ?? [];
if (!is_array($related)) $related = [];
$related['location_id'] = $locationId;
$actorName = trim((string) ($user['full_name'] ?? $user['username'] ?? 'Pengguna'));
$actorRole = trim((string) ($user['primary_role'] ?? 'User'));
$stmt = $db->prepare(
    'INSERT INTO system_notifications
     (menu_name, user_name, user_role, action_type, title, message,
      involved_parties_json, related_tables_json, is_read, created_at)
     VALUES (:menu, :user_name, :user_role, :action_type, :title, :message,
             :parties, :related, 0, NOW())'
);
$db->beginTransaction();
try {
    $stmt->execute([
        ':menu' => mb_substr(trim((string) ($input['menu_name'] ?? $input['menu'] ?? 'System')), 0, 100),
        ':user_name' => $actorName,
        ':user_role' => $actorRole,
        ':action_type' => mb_substr(trim((string) ($input['action_type'] ?? 'UPDATE')), 0, 50),
        ':title' => $title,
        ':message' => $message,
        ':parties' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ':related' => json_encode($related, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
    $newId = (int) $db->lastInsertId();
    NotificationAudience::deliverSelf($db, $newId, $userId, $locationId);
    $db->commit();
} catch (Throwable $error) {
    $db->rollBack();
    error_log('Notification CREATE failed: ' . $error->getMessage());
    api_json_response(500, ['status' => 'error', 'code' => 'NOTIFICATION_FAILED', 'message' => 'Notifikasi gagal disimpan.']);
}
api_json_response(201, ['status' => 'success', 'notification_id' => $newId]);
