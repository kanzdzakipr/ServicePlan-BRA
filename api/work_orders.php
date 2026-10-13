<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$db = Database::getInstance();
ReportIntegration::ensureTables($db);
$method = $_SERVER['REQUEST_METHOD'];

const WO_STATUSES = ['Open', 'In Progress', 'Waiting Part', 'Testing', 'Closed', 'Cancelled'];
const WO_PRIORITIES = ['Normal', 'High', 'Emergency'];
const WO_MAINTENANCE_TYPES = ['Corrective', 'Preventive', 'Inspection', 'Emergency'];

function wo_actor(): array
{
    $user = api_current_user();
    return [
        'id' => isset($user['id']) ? (int) $user['id'] : null,
        'name' => trim((string) ($user['full_name'] ?? $user['username'] ?? 'Pengguna')),
    ];
}

function wo_insert_timeline(PDO $db, string $woId, string $eventType, ?string $fromStatus, ?string $toStatus, string $title, ?string $description): void
{
    $actor = wo_actor();
    $stmt = $db->prepare(
        'INSERT INTO work_order_timeline
         (wo_id, event_type, from_status, to_status, title, description, actor_user_id, actor_name)
         VALUES (:wo_id, :event_type, :from_status, :to_status, :title, :description, :actor_user_id, :actor_name)'
    );
    $stmt->execute([
        ':wo_id' => $woId,
        ':event_type' => $eventType,
        ':from_status' => $fromStatus,
        ':to_status' => $toStatus,
        ':title' => $title,
        ':description' => ($description !== null && trim($description) !== '') ? trim($description) : null,
        ':actor_user_id' => $actor['id'],
        ':actor_name' => $actor['name'],
    ]);
}

function wo_validate_enum(string $field, string $value, array $allowed): string
{
    if (!in_array($value, $allowed, true)) {
        api_json_response(422, [
            'status' => 'error',
            'code' => 'VALIDATION_ERROR',
            'message' => "Nilai {$field} tidak valid.",
        ]);
    }
    return $value;
}

function wo_notify(PDO $db, string $actionType, string $woId, string $message, string $mechanic): void
{
    try {
        $actor = wo_actor();
        $stmt = $db->prepare(
            "INSERT INTO system_notifications
             (menu_name, user_name, user_role, action_type, title, message,
              involved_parties_json, related_tables_json, is_read, created_at)
             VALUES ('Work Order Kanban', :user_name, 'Planner', :action_type, :title, :message,
                     :parties, :tables, 0, NOW())"
        );
        $stmt->execute([
            ':user_name' => $actor['name'],
            ':action_type' => $actionType,
            ':title' => ($actionType === 'WO_CREATE' ? 'Penerbitan' : 'Pembaruan') . " Work Order #{$woId}",
            ':message' => $message,
            ':parties' => json_encode([$actor['name'], $mechanic], JSON_UNESCAPED_UNICODE),
            ':tables' => json_encode(['tables' => ['work_orders'], 'records' => [['table' => 'work_orders', 'id' => $woId]]]),
        ]);
    } catch (Throwable $error) {
        error_log('Work Order notification failed: ' . $error->getMessage());
    }
}

switch ($method) {
    case 'GET':
        AuthMiddleware::checkAccess('wo', 'R');
        $scope = api_location_scope_clause('a', 'wo_list_location_id');
        $select = "SELECT w.*, a.asset_code, a.category AS asset_category,
                          TIMESTAMPDIFF(MINUTE, w.reported_at, COALESCE(w.closed_at, NOW())) AS handling_duration_minutes,
                          (SELECT rwi.report_id FROM report_work_order_integrations rwi
                           WHERE rwi.work_order_id = w.wo_id AND rwi.reversed_at IS NULL
                           ORDER BY rwi.integration_id DESC LIMIT 1) AS source_report_id,
                          (SELECT rr.report_number
                           FROM report_work_order_integrations rwi
                           INNER JOIN report_records rr ON rr.report_id = rwi.report_id
                           WHERE rwi.work_order_id = w.wo_id AND rwi.reversed_at IS NULL
                           ORDER BY rwi.integration_id DESC LIMIT 1) AS source_report_number
                   FROM work_orders w
                   INNER JOIN assets a ON w.asset_id = a.asset_id";

        if (isset($_GET['id'])) {
            $sql = $select . ' WHERE w.wo_id = :id';
            if ($scope['sql'] !== '') $sql .= ' AND ' . $scope['sql'];
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge([':id' => (string) $_GET['id']], $scope['params']));
            $result = $stmt->fetch();
            if (!$result) {
                api_json_response(404, ['status' => 'error', 'message' => 'Work Order tidak ditemukan.']);
            }

            $timelineStmt = $db->prepare(
                'SELECT timeline_id, event_type, from_status, to_status, title, description,
                        actor_user_id, actor_name, occurred_at
                 FROM work_order_timeline
                 WHERE wo_id = :wo_id
                 ORDER BY occurred_at DESC, timeline_id DESC'
            );
            $timelineStmt->execute([':wo_id' => (string) $_GET['id']]);
            $result['timeline'] = $timelineStmt->fetchAll();
        } else {
            $sql = $select;
            if ($scope['sql'] !== '') $sql .= ' WHERE ' . $scope['sql'];
            $sql .= ' ORDER BY w.reported_at DESC';
            $stmt = $db->prepare($sql);
            $stmt->execute($scope['params']);
            $result = $stmt->fetchAll();
        }
        echo json_encode(['status' => 'success', 'data' => $result]);
        break;

    case 'POST':
        AuthMiddleware::checkAccess('wo', 'C');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['wo_id']) || empty($data['asset_id'])) {
            api_json_response(422, ['status' => 'error', 'message' => 'ID Work Order dan aset wajib diisi.']);
        }
        api_require_asset_access($db, (string) $data['asset_id']);

        $status = wo_validate_enum('status', (string) ($data['status'] ?? 'Open'), WO_STATUSES);
        $priority = wo_validate_enum('prioritas', (string) ($data['priority'] ?? 'Normal'), WO_PRIORITIES);
        $maintenanceType = wo_validate_enum('jenis pemeliharaan', (string) ($data['maintenance_type'] ?? 'Corrective'), WO_MAINTENANCE_TYPES);
        $issue = trim((string) ($data['issue_description'] ?? ''));
        if ($issue === '') {
            api_json_response(422, ['status' => 'error', 'message' => 'Keluhan atau detail kerusakan wajib diisi.']);
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                'INSERT INTO work_orders
                 (wo_id, asset_id, issue_description, status, priority, maintenance_type,
                  assigned_mechanic, root_cause, repair_action, is_downtime, downtime_formatted)
                 VALUES (:wo_id, :asset_id, :issue, :status, :priority, :maintenance_type,
                         :mechanic, :root_cause, :repair_action, :is_downtime, :downtime_formatted)'
            );
            $stmt->execute([
                ':wo_id' => (string) $data['wo_id'],
                ':asset_id' => (string) $data['asset_id'],
                ':issue' => $issue,
                ':status' => $status,
                ':priority' => $priority,
                ':maintenance_type' => $maintenanceType,
                ':mechanic' => trim((string) ($data['assigned_mechanic'] ?? 'Belum ada PIC')),
                ':root_cause' => trim((string) ($data['root_cause'] ?? '')) ?: null,
                ':repair_action' => trim((string) ($data['repair_action'] ?? '')) ?: null,
                ':is_downtime' => !empty($data['is_downtime']) ? 1 : 0,
                ':downtime_formatted' => !empty($data['is_downtime']) ? '0 jam 00 menit' : '-',
            ]);
            wo_insert_timeline($db, (string) $data['wo_id'], 'CREATED', null, $status, 'Work Order dibuat', $issue);
            $db->commit();

            $mechanic = trim((string) ($data['assigned_mechanic'] ?? 'Belum ada PIC'));
            wo_notify(
                $db,
                'WO_CREATE',
                (string) $data['wo_id'],
                "Work Order #{$data['wo_id']} diterbitkan untuk unit {$data['asset_id']}. Prioritas: {$priority}. PIC: {$mechanic}",
                $mechanic
            );

            echo json_encode(['status' => 'success', 'message' => 'Work Order berhasil dibuat.']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || (!isset($data['wo_id']) && !isset($_GET['id']))) {
            api_json_response(422, ['status' => 'error', 'message' => 'ID Work Order wajib diisi.']);
        }

        $id = (string) ($_GET['id'] ?? $data['wo_id']);
        $isClosing = isset($data['status']) && $data['status'] === 'Closed';
        AuthMiddleware::checkAccess('wo', $isClosing ? 'A' : 'U');
        api_require_work_order_access($db, $id);

        try {
            $db->beginTransaction();
            $currentStmt = $db->prepare('SELECT * FROM work_orders WHERE wo_id = :id FOR UPDATE');
            $currentStmt->execute([':id' => $id]);
            $current = $currentStmt->fetch();
            if (!$current) {
                $db->rollBack();
                api_json_response(404, ['status' => 'error', 'message' => 'Work Order tidak ditemukan.']);
            }

            $fields = [];
            $params = [':id' => $id];
            foreach (['priority', 'assigned_mechanic', 'issue_description', 'downtime_minutes', 'root_cause', 'repair_action'] as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                }
            }
            if (isset($data['priority'])) {
                $params[':priority'] = wo_validate_enum('prioritas', (string) $data['priority'], WO_PRIORITIES);
            }
            if (isset($data['maintenance_type'])) {
                $fields[] = 'maintenance_type = :maintenance_type';
                $params[':maintenance_type'] = wo_validate_enum('jenis pemeliharaan', (string) $data['maintenance_type'], WO_MAINTENANCE_TYPES);
            }

            $newStatus = (string) $current['status'];
            if (isset($data['status'])) {
                $newStatus = wo_validate_enum('status', (string) $data['status'], WO_STATUSES);
                $fields[] = 'status = :status';
                $params[':status'] = $newStatus;
            }

            $mergedRootCause = trim((string) ($data['root_cause'] ?? $current['root_cause'] ?? ''));
            $mergedMechanic = trim((string) ($data['assigned_mechanic'] ?? $current['assigned_mechanic'] ?? ''));
            if ($newStatus === 'Closed') {
                if ($mergedRootCause === '') {
                    $db->rollBack();
                    api_json_response(422, ['status' => 'error', 'message' => 'Penyebab kerusakan wajib diisi sebelum WO ditutup.']);
                }
                if ($mergedMechanic === '' || in_array($mergedMechanic, ['Belum ada PIC', 'Pending'], true)) {
                    $db->rollBack();
                    api_json_response(422, ['status' => 'error', 'message' => 'Mekanik penanggung jawab wajib dipilih sebelum WO ditutup.']);
                }
            }

            $statusChanged = $newStatus !== (string) $current['status'];
            if ($statusChanged && in_array($newStatus, ['In Progress', 'Waiting Part', 'Testing', 'Closed'], true) && empty($current['repair_started_at'])) {
                $fields[] = 'repair_started_at = NOW()';
            }
            if ($statusChanged && $newStatus === 'Closed') {
                $fields[] = 'closed_at = NOW()';
                $fields[] = 'downtime_minutes = TIMESTAMPDIFF(MINUTE, reported_at, NOW())';
                $fields[] = "downtime_formatted = CONCAT(FLOOR(TIMESTAMPDIFF(MINUTE, reported_at, NOW()) / 60), ' jam ', LPAD(MOD(TIMESTAMPDIFF(MINUTE, reported_at, NOW()), 60), 2, '0'), ' menit')";
            } elseif ($statusChanged && $current['status'] === 'Closed') {
                $fields[] = 'closed_at = NULL';
            }

            if (!$fields) {
                $db->rollBack();
                api_json_response(422, ['status' => 'error', 'message' => 'Tidak ada data yang diperbarui.']);
            }

            $stmt = $db->prepare('UPDATE work_orders SET ' . implode(', ', array_unique($fields)) . ' WHERE wo_id = :id');
            $stmt->execute($params);

            $action = trim((string) ($data['repair_action'] ?? ''));
            if ($statusChanged) {
                wo_insert_timeline(
                    $db,
                    $id,
                    'STATUS_CHANGE',
                    (string) $current['status'],
                    $newStatus,
                    "Status berubah: {$current['status']} menjadi {$newStatus}",
                    $action !== '' ? $action : null
                );
            } else {
                wo_insert_timeline($db, $id, 'DETAIL_UPDATE', (string) $current['status'], $newStatus, 'Detail Work Order diperbarui', $action ?: null);
            }
            $db->commit();

            wo_notify(
                $db,
                'WO_UPDATE',
                $id,
                "Work Order #{$id} diperbarui" . ($statusChanged ? " menjadi status {$newStatus}" : '') . ". PIC: {$mergedMechanic}",
                $mergedMechanic
            );

            echo json_encode(['status' => 'success', 'message' => 'Work Order berhasil diperbarui.']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        AuthMiddleware::checkAccess('wo', 'O');
        if (!isset($_GET['id'])) {
            api_json_response(422, ['status' => 'error', 'message' => 'ID Work Order wajib diisi.']);
        }
        api_require_work_order_access($db, (string) $_GET['id']);
        $stmt = $db->prepare('DELETE FROM work_orders WHERE wo_id = :id');
        $stmt->execute([':id' => (string) $_GET['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Work Order dihapus.']);
        break;

    default:
        api_json_response(405, ['status' => 'error', 'message' => 'Method not allowed']);
}
