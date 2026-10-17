<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';
require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';

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

function wo_notify(PDO $db, string $actionType, string $woId, string $message, string $mechanic, ?string $assetId = null): void
{
    try {
        $actor = wo_actor();
        $title = match ($actionType) {
            'WO_CREATE' => "Penerbitan Work Order #{$woId}",
            'WO_CLOSED' => "Work Order #{$woId} Ditutup",
            'WO_CANCELLED' => "Work Order #{$woId} Dibatalkan",
            default => "Pembaruan Work Order #{$woId}",
        };

        // Idempotency: Prevent duplicate within 60 seconds
        $dupStmt = $db->prepare(
            "SELECT notification_id FROM system_notifications
             WHERE action_type = :action_type AND title = :title AND created_at >= NOW() - INTERVAL 60 SECOND
             LIMIT 1"
        );
        $dupStmt->execute([':action_type' => $actionType, ':title' => $title]);
        if ($dupStmt->fetchColumn()) {
            return;
        }

        // Resolve asset and location
        $locationId = null;
        if ($assetId !== null && $assetId !== '') {
            $locStmt = $db->prepare('SELECT current_location_id FROM assets WHERE asset_id = :asset_id LIMIT 1');
            $locStmt->execute([':asset_id' => $assetId]);
            $locVal = $locStmt->fetchColumn();
            if ($locVal !== false && $locVal !== null) {
                $locationId = (int) $locVal;
            }
        } else {
            $woStmt = $db->prepare('SELECT w.asset_id, a.current_location_id FROM work_orders w LEFT JOIN assets a ON a.asset_id = w.asset_id WHERE w.wo_id = :wo_id LIMIT 1');
            $woStmt->execute([':wo_id' => $woId]);
            $woRow = $woStmt->fetch(PDO::FETCH_ASSOC);
            if ($woRow) {
                $assetId = $woRow['asset_id'];
                $locationId = $woRow['current_location_id'] ? (int) $woRow['current_location_id'] : null;
            }
        }

        $parties = array_values(array_unique(array_filter([
            'Tim Logistik & Suku Cadang',
            'Maintenance Planner',
            $actor['name'] ?: 'Planner',
            $mechanic ?: 'Mekanik Lead',
        ])));

        $tablesData = [
            'tables' => ['work_orders', 'assets'],
            'records' => [
                ['table' => 'work_orders', 'id' => $woId],
            ],
            'wo_id' => $woId,
            'asset_id' => $assetId,
            'location_id' => $locationId,
        ];
        if ($assetId) {
            $tablesData['records'][] = ['table' => 'assets', 'id' => $assetId];
        }

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
            ':title' => $title,
            ':message' => $message,
            ':parties' => json_encode($parties, JSON_UNESCAPED_UNICODE),
            ':tables' => json_encode($tablesData, JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $error) {
        error_log('Work Order notification failed: ' . $error->getMessage());
    }
}

function wo_upload_before_photo(PDO $db): never
{
    AuthMiddleware::checkAccess('wo', 'U');
    $woId = trim((string) ($_POST['wo_id'] ?? ''));
    if ($woId === '') {
        api_json_response(422, ['status' => 'error', 'message' => 'ID Work Order wajib diisi untuk upload foto.']);
    }
    api_require_work_order_access($db, $woId);

    $file = $_FILES['photo'] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        api_json_response(422, ['status' => 'error', 'message' => 'Pilih satu foto dokumentasi yang valid.']);
    }
    if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
        api_json_response(422, ['status' => 'error', 'message' => 'Ukuran foto wajib lebih dari 0 dan maksimal 5 MB.']);
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime]) || @getimagesize($tmpName) === false) {
        api_json_response(422, ['status' => 'error', 'message' => 'Foto harus berupa gambar JPEG, PNG, atau WebP yang valid.']);
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'work_orders';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
        api_json_response(500, ['status' => 'error', 'message' => 'Direktori dokumentasi Work Order tidak dapat disiapkan.']);
    }

    $safeWoId = preg_replace('/[^A-Za-z0-9_-]/', '-', $woId) ?: 'WO';
    $fileName = $safeWoId . '-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        api_json_response(500, ['status' => 'error', 'message' => 'Foto gagal disimpan pada server.']);
    }

    $relativePath = 'uploads/work_orders/' . $fileName;
    try {
        $db->beginTransaction();
        $stmt = $db->prepare('UPDATE work_orders SET before_photo_url = :photo WHERE wo_id = :wo_id');
        $stmt->execute([':photo' => $relativePath, ':wo_id' => $woId]);
        wo_insert_timeline($db, $woId, 'DETAIL_UPDATE', null, null, 'Dokumentasi foto ditambahkan', $relativePath);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        if (is_file($targetPath)) unlink($targetPath);
        error_log('WO photo upload failed: ' . $error->getMessage());
        api_json_response(500, ['status' => 'error', 'message' => 'Referensi foto gagal ditautkan ke Work Order.']);
    }

    api_json_response(200, [
        'status' => 'success',
        'message' => 'Foto dokumentasi berhasil disimpan.',
        'data' => ['woId' => $woId, 'beforePhotoUrl' => $relativePath],
    ]);
}

if (!defined('API_SKIP_ROUTING') || API_SKIP_ROUTING !== true) {
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
        if ((string) ($_GET['action'] ?? '') === 'upload-photo') {
            wo_upload_before_photo($db);
        }
        AuthMiddleware::checkAccess('wo', 'C');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['wo_id']) || empty($data['asset_id'])) {
            api_json_response(422, ['status' => 'error', 'message' => 'ID Work Order dan aset wajib diisi.']);
        }
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,49}$/', (string) $data['wo_id'])) {
            api_json_response(422, ['status' => 'error', 'message' => 'Format ID Work Order tidak valid.']);
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
            $assetStmt = $db->prepare(
                'SELECT asset_id, asset_code, alias_name, category, type, status, last_hm_km
                 FROM assets WHERE asset_id = :asset_id FOR UPDATE'
            );
            $assetStmt->execute([':asset_id' => (string) $data['asset_id']]);
            $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
            if (!$asset) {
                throw new InvalidArgumentException('Aset Work Order tidak ditemukan.');
            }
            $ticket = WorkOrderSupport::normalizeTicketInput($data, $asset);
            $stmt = $db->prepare(
                'INSERT INTO work_orders
                 (wo_id, asset_id, hull_number, equipment_type, current_hm_km, trouble_at,
                  raw_location, issue_description, status, priority, maintenance_type,
                  assigned_mechanic, assigned_mechanics_json, root_cause, repair_action,
                  is_downtime, is_forced_operation, mitigation_action, downtime_formatted,
                  repair_started_at, estimated_completion_at)
                 VALUES (:wo_id, :asset_id, :hull_number, :equipment_type, :current_hm_km, :trouble_at,
                         :raw_location, :issue, :status, :priority, :maintenance_type,
                         :mechanic, :mechanics_json, :root_cause, :repair_action,
                         :is_downtime, :is_forced_operation, :mitigation_action, :downtime_formatted,
                         :repair_started_at, :estimated_completion_at)'
            );
            $stmt->execute([
                ':wo_id' => (string) $data['wo_id'],
                ':asset_id' => (string) $data['asset_id'],
                ':issue' => $issue,
                ':status' => $status,
                ':priority' => $priority,
                ':maintenance_type' => $maintenanceType,
                ':hull_number' => $ticket['hull_number'],
                ':equipment_type' => $ticket['equipment_type'],
                ':current_hm_km' => $ticket['current_hm_km'],
                ':trouble_at' => $ticket['trouble_at'],
                ':raw_location' => $ticket['raw_location'],
                ':mechanic' => $ticket['assigned_mechanic'],
                ':mechanics_json' => $ticket['assigned_mechanics_json'],
                ':root_cause' => trim((string) ($data['root_cause'] ?? '')) ?: null,
                ':repair_action' => trim((string) ($data['repair_action'] ?? '')) ?: null,
                ':is_downtime' => $ticket['is_downtime'],
                ':is_forced_operation' => $ticket['is_forced_operation'],
                ':mitigation_action' => $ticket['mitigation_action'],
                ':downtime_formatted' => $ticket['is_downtime'] ? '0 jam 00 menit' : '-',
                ':repair_started_at' => $ticket['repair_started_at'],
                ':estimated_completion_at' => $ticket['estimated_completion_at'],
            ]);
            $assetUpdate = $db->prepare(
                'UPDATE assets
                 SET status = :status,
                     last_hm_km = GREATEST(COALESCE(last_hm_km, 0), :current_hm_km)
                 WHERE asset_id = :asset_id'
            );
            $assetUpdate->execute([
                ':status' => $ticket['asset_status'],
                ':current_hm_km' => $ticket['current_hm_km'],
                ':asset_id' => (string) $data['asset_id'],
            ]);
            $timelineDescription = $issue;
            if ($ticket['is_forced_operation']) {
                $timelineDescription .= "\nRFU Bersyarat: " . $ticket['mitigation_action'];
            }
            wo_insert_timeline($db, (string) $data['wo_id'], 'CREATED', null, $status, 'Work Order dibuat', $timelineDescription);
            $db->commit();

            $mechanic = $ticket['assigned_mechanic'];
            wo_notify(
                $db,
                'WO_CREATE',
                (string) $data['wo_id'],
                "Work Order #{$data['wo_id']} diterbitkan untuk unit {$data['asset_id']}. Prioritas: {$priority}. PIC: {$mechanic}",
                $mechanic,
                (string) $data['asset_id']
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Work Order berhasil dibuat.',
                'data' => [
                    'woId' => (string) $data['wo_id'],
                    'assetStatus' => $ticket['asset_status'],
                    'isForcedOperation' => (bool) $ticket['is_forced_operation'],
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response(422, ['status' => 'error', 'message' => $e->getMessage()]);
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

            $assetStmt = $db->prepare(
                'SELECT asset_id, asset_code, alias_name, category, type, status, last_hm_km
                 FROM assets WHERE asset_id = :asset_id FOR UPDATE'
            );
            $assetStmt->execute([':asset_id' => (string) $current['asset_id']]);
            $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
            if (!$asset) {
                throw new RuntimeException('Aset Work Order tidak ditemukan.');
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

            $extendedKeys = [
                'hull_number' => 'hullNumber',
                'equipment_type' => 'equipmentType',
                'current_hm_km' => 'currentHmKm',
                'trouble_at' => 'troubleAt',
                'raw_location' => 'rawLocation',
                'repair_started_at' => 'repairStartedAt',
                'estimated_completion_at' => 'estimatedCompletionAt',
                'is_forced_operation' => 'isForcedOperation',
                'is_downtime' => 'isDowntime',
                'mitigation_action' => 'mitigationAction',
                'assigned_mechanics' => 'assignedMechanics',
            ];
            $mergedTicketInput = $current;
            $hasExtendedUpdate = false;
            foreach ($extendedKeys as $snakeKey => $camelKey) {
                if (array_key_exists($snakeKey, $data)) {
                    $mergedTicketInput[$snakeKey] = $data[$snakeKey];
                    $hasExtendedUpdate = true;
                } elseif (array_key_exists($camelKey, $data)) {
                    $mergedTicketInput[$snakeKey] = $data[$camelKey];
                    $hasExtendedUpdate = true;
                }
            }

            $ticket = null;
            if ($hasExtendedUpdate) {
                $ticket = WorkOrderSupport::normalizeTicketInput($mergedTicketInput, $asset);
                $simpleExtendedColumns = [
                    'hull_number', 'equipment_type', 'current_hm_km', 'trouble_at',
                    'raw_location', 'repair_started_at', 'estimated_completion_at',
                    'mitigation_action',
                ];
                foreach ($simpleExtendedColumns as $column) {
                    $camelKey = $extendedKeys[$column];
                    if (array_key_exists($column, $data) || array_key_exists($camelKey, $data)) {
                        $fields[] = "{$column} = :{$column}";
                        $params[":{$column}"] = $ticket[$column];
                    }
                }
                if (array_key_exists('assigned_mechanics', $data) || array_key_exists('assignedMechanics', $data)) {
                    $fields[] = 'assigned_mechanics_json = :assigned_mechanics_json';
                    $fields[] = 'assigned_mechanic = :assigned_mechanic_from_list';
                    $params[':assigned_mechanics_json'] = $ticket['assigned_mechanics_json'];
                    $params[':assigned_mechanic_from_list'] = $ticket['assigned_mechanic'];
                }
                if (
                    array_key_exists('is_forced_operation', $data) || array_key_exists('isForcedOperation', $data)
                    || array_key_exists('is_downtime', $data) || array_key_exists('isDowntime', $data)
                ) {
                    $fields[] = 'is_forced_operation = :is_forced_operation';
                    $fields[] = 'is_downtime = :is_downtime_extended';
                    $fields[] = 'downtime_formatted = :downtime_formatted_extended';
                    $params[':is_forced_operation'] = $ticket['is_forced_operation'];
                    $params[':is_downtime_extended'] = $ticket['is_downtime'];
                    $params[':downtime_formatted_extended'] = $ticket['is_downtime'] ? ($current['downtime_formatted'] ?: '0 jam 00 menit') : '-';
                }
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

            if ($ticket !== null && (
                array_key_exists('current_hm_km', $data) || array_key_exists('currentHmKm', $data)
                || array_key_exists('is_forced_operation', $data) || array_key_exists('isForcedOperation', $data)
                || array_key_exists('is_downtime', $data) || array_key_exists('isDowntime', $data)
            )) {
                $assetUpdate = $db->prepare(
                    'UPDATE assets SET status = :status,
                     last_hm_km = GREATEST(COALESCE(last_hm_km, 0), :current_hm_km)
                     WHERE asset_id = :asset_id'
                );
                $assetUpdate->execute([
                    ':status' => $ticket['asset_status'],
                    ':current_hm_km' => $ticket['current_hm_km'],
                    ':asset_id' => (string) $current['asset_id'],
                ]);
            }

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

            $woActionType = match ($newStatus) {
                'Closed' => 'WO_CLOSED',
                'Cancelled' => 'WO_CANCELLED',
                default => 'WO_UPDATE',
            };
            wo_notify(
                $db,
                $woActionType,
                $id,
                "Work Order #{$id} diperbarui" . ($statusChanged ? " menjadi status {$newStatus}" : '') . ". PIC: {$mergedMechanic}",
                $mergedMechanic,
                (string) $current['asset_id']
            );

            echo json_encode(['status' => 'success', 'message' => 'Work Order berhasil diperbarui.']);
        } catch (InvalidArgumentException $e) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response(422, ['status' => 'error', 'message' => $e->getMessage()]);
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
}
