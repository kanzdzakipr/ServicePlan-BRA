<?php
declare(strict_types=1);

require_once 'db.php';
require_once 'security.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

function archiveItemAccessible(PDO $db, string $type, string $id): bool
{
    if (!in_array($type, ['asset', 'p2h', 'accident'], true) || trim($id) === '') return false;
    $scope = api_location_scope_clause('a', 'archive_location_id');
    $params = [':id' => trim($id)];

    if ($type === 'asset') {
        $sql = 'SELECT 1 FROM assets a WHERE a.asset_id = :id';
    } elseif ($type === 'accident') {
        $sql = 'SELECT 1 FROM accidents ac INNER JOIN assets a ON a.asset_id = ac.asset_id WHERE ac.accident_id = :id';
    } else {
        $sql = "SELECT 1 FROM inspections i INNER JOIN assets a ON a.asset_id = i.asset_id
                WHERE (CAST(i.inspection_id AS CHAR) = :inspection_id
                       OR JSON_UNQUOTE(JSON_EXTRACT(i.payload_json, '$.id')) = :payload_id)";
        $params = [':inspection_id' => trim($id), ':payload_id' => trim($id)];
    }
    $params = array_merge($params, $scope['params']);
    if ($scope['sql'] !== '') $sql .= ' AND ' . $scope['sql'];
    $stmt = $db->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}

function requireArchiveItemAccess(PDO $db, string $type, string $id): void
{
    if (!archiveItemAccessible($db, $type, $id)) {
        api_json_response(404, [
            'status' => 'error',
            'code' => 'OBJECT_NOT_FOUND',
            'message' => 'Objek tidak ditemukan atau tidak dapat diakses.',
        ]);
    }
}

switch ($method) {
    case 'GET':
        api_require_permission('archive.read');
        try {
            $stmt = $db->query("SELECT item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason FROM archived_items ORDER BY archived_at DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = array_values(array_filter(
                $rows,
                static fn(array $row): bool => archiveItemAccessible($db, (string) $row['item_type'], (string) $row['item_id'])
            ));
            api_json_response(200, ["status" => "success", "data" => $result]);
        } catch (PDOException $e) {
            api_json_response(500, ["status" => "error", "message" => "Gagal memuat data arsip: " . $e->getMessage()]);
        }
        break;

    case 'POST':
        api_require_permission('archive.write');
        api_verify_csrf();

        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['type']) || empty($input['id']) || empty($input['action'])) {
            api_json_response(422, ["status" => "error", "message" => "Payload tidak lengkap (type, id, action wajib)."]);
        }

        $type = trim((string) $input['type']);
        $id = trim((string) $input['id']);
        $action = strtolower(trim((string) $input['action']));
        $reason = trim((string) ($input['reason'] ?? ''));

        if (!in_array($action, ['archive', 'restore'], true)) {
            api_json_response(422, ["status" => "error", "message" => "Aksi tidak valid (hanya 'archive' atau 'restore')."]);
        }

        requireArchiveItemAccess($db, $type, $id);

        $currentUser = api_current_user();
        $actorId = (int) ($currentUser['id'] ?? 0);
        $actorName = trim((string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'User'));

        if ($reason === '') {
            $reason = ($action === 'archive') ? 'Diarsipkan melalui sistem' : 'Dipulihkan ke operasional aktif';
        }

        $db->beginTransaction();
        try {
            if ($action === 'archive') {
                $stmt = $db->prepare("
                    INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason)
                    VALUES (:type, :id, NOW(), :uid, :uname, :reason)
                    ON DUPLICATE KEY UPDATE 
                        archived_at = NOW(),
                        archived_by_user_id = VALUES(archived_by_user_id),
                        archived_by_name = VALUES(archived_by_name),
                        reason = VALUES(reason)
                ");
                $stmt->execute([
                    ':type' => $type,
                    ':id' => $id,
                    ':uid' => $actorId > 0 ? $actorId : null,
                    ':uname' => $actorName,
                    ':reason' => $reason
                ]);

                $stmtAudit = $db->prepare("
                    INSERT INTO archive_audit_trail (item_type, item_id, action, actor_user_id, actor_name, reason, created_at)
                    VALUES (:type, :id, 'ARCHIVE', :uid, :uname, :reason, NOW())
                ");
                $stmtAudit->execute([
                    ':type' => $type,
                    ':id' => $id,
                    ':uid' => $actorId > 0 ? $actorId : null,
                    ':uname' => $actorName,
                    ':reason' => $reason
                ]);

                $db->commit();
                api_json_response(200, [
                    "status" => "success",
                    "message" => "Item {$type} #{$id} berhasil dipindahkan ke arsip.",
                    "data" => ["item_type" => $type, "item_id" => $id, "action" => "ARCHIVE"]
                ]);
            } else { // restore
                $stmt = $db->prepare("DELETE FROM archived_items WHERE item_type = :type AND item_id = :id");
                $stmt->execute([':type' => $type, ':id' => $id]);

                $stmtAudit = $db->prepare("
                    INSERT INTO archive_audit_trail (item_type, item_id, action, actor_user_id, actor_name, reason, created_at)
                    VALUES (:type, :id, 'RESTORE', :uid, :uname, :reason, NOW())
                ");
                $stmtAudit->execute([
                    ':type' => $type,
                    ':id' => $id,
                    ':uid' => $actorId > 0 ? $actorId : null,
                    ':uname' => $actorName,
                    ':reason' => $reason
                ]);

                $db->commit();
                api_json_response(200, [
                    "status" => "success",
                    "message" => "Item {$type} #{$id} berhasil dipulihkan dari arsip.",
                    "data" => ["item_type" => $type, "item_id" => $id, "action" => "RESTORE"]
                ]);
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response(500, ["status" => "error", "message" => "Gagal memproses arsip: " . $e->getMessage()]);
        }
        break;

    default:
        api_json_response(405, ["status" => "error", "code" => "METHOD_NOT_ALLOWED", "message" => "Metode HTTP tidak didukung."]);
        break;
}
