<?php
declare(strict_types=1);

require_once 'db.php';
require_once 'security.php';
require_once dirname(__DIR__) . '/core/NotificationAudience.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

function ensureArchiveSchema(PDO $db): void
{
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `archived_items` (
                `item_type` VARCHAR(50) NOT NULL,
                `item_id` VARCHAR(100) NOT NULL,
                `archived_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `archived_by_user_id` INT NULL,
                `archived_by_name` VARCHAR(150) NULL,
                `reason` VARCHAR(255) NULL,
                PRIMARY KEY (`item_type`, `item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS `archive_audit_trail` (
                `audit_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `item_type` VARCHAR(50) NOT NULL,
                `item_id` VARCHAR(100) NOT NULL,
                `action` VARCHAR(20) NOT NULL,
                `actor_user_id` INT NULL,
                `actor_name` VARCHAR(150) NULL,
                `reason` TEXT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_aat_item` (`item_type`, `item_id`),
                INDEX `idx_aat_actor` (`actor_user_id`),
                INDEX `idx_aat_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $colsStmt = $db->query("SHOW COLUMNS FROM `archived_items`");
        $existingCols = [];
        if ($colsStmt) {
            foreach ($colsStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $existingCols[strtolower((string) ($c['Field'] ?? ''))] = true;
            }
        }
        if (!isset($existingCols['archived_by_user_id'])) {
            $db->exec("ALTER TABLE `archived_items` ADD COLUMN `archived_by_user_id` INT NULL AFTER `archived_at`");
        }
        if (!isset($existingCols['archived_by_name'])) {
            $db->exec("ALTER TABLE `archived_items` ADD COLUMN `archived_by_name` VARCHAR(150) NULL AFTER `archived_by_user_id`");
        }
        if (!isset($existingCols['reason'])) {
            $db->exec("ALTER TABLE `archived_items` ADD COLUMN `reason` VARCHAR(255) NULL AFTER `archived_by_name`");
        }
    } catch (Throwable $e) {
        // Abaikan jika user DB di hosting memiliki batas DDL, fallback ditangani pada saat SELECT
    }
}

ensureArchiveSchema($db);

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
    try {
        $stmt = $db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        if ($type === 'p2h') {
            try {
                $fallbackSql = 'SELECT 1 FROM inspections i INNER JOIN assets a ON a.asset_id = i.asset_id WHERE CAST(i.inspection_id AS CHAR) = :id';
                $fallbackParams = array_merge([':id' => trim($id)], $scope['params']);
                if ($scope['sql'] !== '') $fallbackSql .= ' AND ' . $scope['sql'];
                $stmt = $db->prepare($fallbackSql . ' LIMIT 1');
                $stmt->execute($fallbackParams);
                return (bool) $stmt->fetchColumn();
            } catch (Throwable $inner) {
                return true;
            }
        }
        return true;
    }
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

function archiveItemSnapshot(PDO $db, string $type, string $id): array
{
    try {
        if ($type === 'asset') {
            $stmt = $db->prepare("
                SELECT a.*, l.location_name AS joined_location_name
                FROM assets a
                LEFT JOIN locations l ON l.location_id = a.current_location_id
                WHERE a.asset_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['id' => $id];
            $loc = (string) ($row['joined_location_name'] ?? $row['raw_location_notes'] ?? '');
            return [
                'id' => (string) ($row['asset_id'] ?? $id),
                'assetCode' => (string) ($row['asset_code'] ?? $row['asset_id'] ?? $id),
                'hullNumber' => (string) ($row['hull_number'] ?? ''),
                'serialNumber' => (string) ($row['serial_number'] ?? ''),
                'licensePlate' => (string) ($row['license_plate'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
                'type' => (string) ($row['type'] ?? $row['make_model'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'location' => $loc,
                'lastUpdate' => $row['last_update_timestamp'] ?? null,
            ];
        }

        if ($type === 'p2h') {
            $stmt = $db->prepare("
                SELECT i.*
                FROM inspections i
                WHERE CAST(i.inspection_id AS CHAR) = :inspection_id
                   OR JSON_UNQUOTE(JSON_EXTRACT(i.payload_json, '$.id')) = :payload_id
                ORDER BY i.inspection_id DESC
                LIMIT 1
            ");
            $stmt->execute([':inspection_id' => $id, ':payload_id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['id' => $id];
            $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
            if (!is_array($payload)) $payload = [];
            return array_replace($payload, [
                'id' => (string) ($payload['id'] ?? $row['inspection_id'] ?? $id),
                'date' => (string) ($payload['date'] ?? $row['inspection_date'] ?? ''),
                'unitId' => (string) ($payload['unitId'] ?? $row['asset_id'] ?? ''),
                'hmStart' => $payload['hmStart'] ?? $row['current_hm_km'] ?? null,
                'status' => (string) ($payload['status'] ?? $row['overall_result'] ?? ''),
                'notes' => (string) ($payload['notes'] ?? $row['findings_summary'] ?? ''),
            ]);
        }

        if ($type === 'accident') {
            $stmt = $db->prepare("
                SELECT ac.*, l.location_name
                FROM accidents ac
                LEFT JOIN locations l ON l.location_id = ac.location_id
                WHERE ac.accident_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['docNo' => $id];
            $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
            if (!is_array($payload)) $payload = [];
            return array_replace($payload, [
                'docNo' => (string) ($row['accident_id'] ?? $id),
                'reportDate' => (string) ($row['report_date'] ?? ''),
                'incidentDate' => (string) ($row['incident_datetime'] ?? ''),
                'unitCode' => (string) ($row['asset_id'] ?? ''),
                'location' => (string) ($payload['location'] ?? $row['location_name'] ?? ''),
                'operatorName' => (string) ($row['operator_name'] ?? ''),
                'severity' => (string) ($row['severity'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
            ]);
        }
    } catch (Throwable $e) {
        return ['id' => $id];
    }

    return [];
}

switch ($method) {
    case 'GET':
        api_require_permission('archive.read');
        try {
            try {
                $stmt = $db->query("SELECT item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason FROM archived_items ORDER BY archived_at DESC");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $colErr) {
                $stmt = $db->query("SELECT item_type, item_id, archived_at, NULL AS archived_by_user_id, NULL AS archived_by_name, NULL AS reason FROM archived_items ORDER BY archived_at DESC");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $result = array_values(array_filter(
                $rows,
                static fn(array $row): bool => archiveItemAccessible($db, (string) $row['item_type'], (string) $row['item_id'])
            ));
            foreach ($result as &$row) {
                $row['details'] = archiveItemSnapshot($db, (string) $row['item_type'], (string) $row['item_id']);
            }
            unset($row);
            api_json_response(200, ["status" => "success", "data" => $result]);
        } catch (Throwable $e) {
            api_json_response(200, ["status" => "success", "data" => [], "warning" => "Tabel arsip belum tersedia atau sedang disinkronkan."]);
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
        $actorRole = trim((string) ($currentUser['primary_role'] ?? $currentUser['role_name'] ?? 'User'));

        if ($reason === '') {
            $reason = ($action === 'archive') ? 'Diarsipkan melalui sistem' : 'Dipulihkan ke operasional aktif';
        }

        // Ambil data unit & lokasi untuk pengiriman notifikasi terarah
        $locationId = null;
        $unitLabel = ucfirst($type) . " #{$id}";
        $makeModel = '';
        if ($type === 'asset') {
            $aStmt = $db->prepare("SELECT asset_code, make_model, current_location_id FROM assets WHERE asset_id = :id LIMIT 1");
            $aStmt->execute([':id' => $id]);
            $aInfo = $aStmt->fetch(PDO::FETCH_ASSOC);
            if ($aInfo) {
                $locationId = isset($aInfo['current_location_id']) ? (int) $aInfo['current_location_id'] : null;
                $assetCode = trim((string) $aInfo['asset_code']) ?: $id;
                $makeModel = trim((string) $aInfo['make_model']);
                $unitLabel = "Unit {$assetCode}";
            }
        } elseif ($type === 'accident') {
            $acStmt = $db->prepare("SELECT ac.asset_id, ac.location_id FROM accidents ac WHERE ac.accident_id = :id LIMIT 1");
            $acStmt->execute([':id' => $id]);
            $acInfo = $acStmt->fetch(PDO::FETCH_ASSOC);
            if ($acInfo) {
                $locationId = isset($acInfo['location_id']) ? (int) $acInfo['location_id'] : null;
                $unitLabel = "Laporan Insiden #{$id}";
            }
        } else {
            $iStmt = $db->prepare("SELECT i.asset_id, a.current_location_id FROM inspections i LEFT JOIN assets a ON a.asset_id = i.asset_id WHERE (CAST(i.inspection_id AS CHAR) = :id OR JSON_UNQUOTE(JSON_EXTRACT(i.payload_json, '$.id')) = :id) LIMIT 1");
            $iStmt->execute([':id' => $id]);
            $iInfo = $iStmt->fetch(PDO::FETCH_ASSOC);
            if ($iInfo) {
                $locationId = isset($iInfo['current_location_id']) ? (int) $iInfo['current_location_id'] : null;
                $unitLabel = "Inspeksi P2H #{$id}";
            }
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

                // Notifikasi sistem untuk pemindahan ke arsip
                $modelSuffix = $makeModel !== '' ? " ({$makeModel})" : "";
                $notifTitle = "{$unitLabel} Dipindahkan ke Arsip";
                $notifMessage = "{$unitLabel}{$modelSuffix} telah dipindahkan ke arsip oleh {$actorName}. Alasan: {$reason}";
                $notifAction = 'ARCHIVE_' . strtoupper($type);

                $relatedTables = [
                    'records' => [
                        ['table' => ($type === 'asset' ? 'assets' : ($type === 'p2h' ? 'inspections' : 'accidents')), 'id' => $id],
                        ['table' => 'archived_items', 'id' => $id],
                    ],
                    'item_type' => $type,
                    'item_id' => $id,
                    'location_id' => $locationId,
                ];
                if ($type === 'asset') {
                    $relatedTables['asset_id'] = $id;
                }

                $notifStmt = $db->prepare("
                    INSERT INTO system_notifications
                        (menu_name, user_name, user_role, action_type, title, message,
                         involved_parties_json, related_tables_json, is_read, created_at)
                    VALUES ('Archive', :uname, :urole, :act, :title, :msg, :parties, :tables, 0, NOW())
                ");
                $notifStmt->execute([
                    ':uname' => $actorName,
                    ':urole' => $actorRole,
                    ':act' => $notifAction,
                    ':title' => $notifTitle,
                    ':msg' => $notifMessage,
                    ':parties' => json_encode([$actorName], JSON_UNESCAPED_UNICODE),
                    ':tables' => json_encode($relatedTables, JSON_UNESCAPED_UNICODE),
                ]);
                $notifId = (int) $db->lastInsertId();
                if ($notifId > 0) {
                    NotificationAudience::deliverWo($db, $notifId, $locationId, $actorId > 0 ? $actorId : null);
                }

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

                // Notifikasi sistem untuk pemulihan dari arsip
                $modelSuffix = $makeModel !== '' ? " ({$makeModel})" : "";
                $notifTitle = "{$unitLabel} Dipulihkan dari Arsip";
                $notifMessage = "{$unitLabel}{$modelSuffix} telah dipulihkan ke operasional aktif oleh {$actorName}. Alasan: {$reason}";
                $notifAction = 'RESTORE_' . strtoupper($type);

                $relatedTables = [
                    'records' => [
                        ['table' => ($type === 'asset' ? 'assets' : ($type === 'p2h' ? 'inspections' : 'accidents')), 'id' => $id],
                    ],
                    'item_type' => $type,
                    'item_id' => $id,
                    'location_id' => $locationId,
                ];
                if ($type === 'asset') {
                    $relatedTables['asset_id'] = $id;
                }

                $notifStmt = $db->prepare("
                    INSERT INTO system_notifications
                        (menu_name, user_name, user_role, action_type, title, message,
                         involved_parties_json, related_tables_json, is_read, created_at)
                    VALUES ('Archive', :uname, :urole, :act, :title, :msg, :parties, :tables, 0, NOW())
                ");
                $notifStmt->execute([
                    ':uname' => $actorName,
                    ':urole' => $actorRole,
                    ':act' => $notifAction,
                    ':title' => $notifTitle,
                    ':msg' => $notifMessage,
                    ':parties' => json_encode([$actorName], JSON_UNESCAPED_UNICODE),
                    ':tables' => json_encode($relatedTables, JSON_UNESCAPED_UNICODE),
                ]);
                $notifId = (int) $db->lastInsertId();
                if ($notifId > 0) {
                    NotificationAudience::deliverWo($db, $notifId, $locationId, $actorId > 0 ? $actorId : null);
                }

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
