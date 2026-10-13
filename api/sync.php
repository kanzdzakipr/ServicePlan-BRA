<?php
require_once 'db.php';
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        echo json_encode(["status" => "error", "message" => "No data provided"]);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Update Assets
        if (isset($data['assets']) && is_array($data['assets'])) {
            $stmtAsset = $db->prepare("
                UPDATE assets 
                SET status = :status, raw_location_notes = :loc, last_hm_km = :hm 
                WHERE asset_id = :id
            ");
            foreach ($data['assets'] as $a) {
                api_require_asset_access($db, (string) ($a['id'] ?? ''));
                $stmtAsset->execute([
                    ':status' => $a['status'] ?? 'READY',
                    ':loc' => $a['location'] ?? '',
                    ':hm' => $a['last_hm_km'] ?? 0,
                    ':id' => $a['id']
                ]);
            }
        }

        // 2. Upsert Work Orders
        if (isset($data['work_orders']) && is_array($data['work_orders'])) {
            $stmtWO = $db->prepare("
                INSERT INTO work_orders (wo_id, asset_id, status, priority, maintenance_type, issue_description,
                                         assigned_mechanic, root_cause, repair_action, downtime_formatted, is_downtime)
                VALUES (:wo, :ass, :st, :prio, :maintenance_type, :iss, :pic, :root_cause, :repair_action, :df, :is_downtime)
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    priority = VALUES(priority), 
                    maintenance_type = VALUES(maintenance_type),
                    issue_description = VALUES(issue_description), 
                    assigned_mechanic = VALUES(assigned_mechanic), 
                    root_cause = COALESCE(NULLIF(VALUES(root_cause), ''), root_cause),
                    repair_action = COALESCE(NULLIF(VALUES(repair_action), ''), repair_action),
                    downtime_formatted = VALUES(downtime_formatted)
            ");
            foreach ($data['work_orders'] as $w) {
                if (!isset($w['woId']) || !isset($w['assetId'])) continue;
                api_require_asset_access($db, (string) $w['assetId']);
                $existingWorkOrder = $db->prepare('SELECT asset_id, status FROM work_orders WHERE wo_id = :wo_id LIMIT 1');
                $existingWorkOrder->execute([':wo_id' => (string) $w['woId']]);
                $existingRow = $existingWorkOrder->fetch(PDO::FETCH_ASSOC);
                if ($existingRow !== false) {
                    api_require_work_order_access($db, (string) $w['woId']);
                    if (!hash_equals((string) $existingRow['asset_id'], (string) $w['assetId'])) {
                        api_json_response(409, [
                            'status' => 'error',
                            'code' => 'OBJECT_ID_CONFLICT',
                            'message' => 'ID work order sudah digunakan oleh aset lain.',
                        ]);
                    }
                }
                $stmtWO->execute([
                    ':wo' => $w['woId'],
                    ':ass' => $w['assetId'],
                    ':st' => $w['status'] ?? 'Open',
                    ':prio' => $w['priority'] ?? 'Normal',
                    ':maintenance_type' => $w['maintenanceType'] ?? $w['maintenance_type'] ?? 'Corrective',
                    ':iss' => $w['issue'] ?? '',
                    ':pic' => $w['assignedTo'] ?? '',
                    ':root_cause' => $w['rootCause'] ?? $w['root_cause'] ?? '',
                    ':repair_action' => $w['repairAction'] ?? $w['repair_action'] ?? '',
                    ':df' => $w['downtime'] ?? '',
                    ':is_downtime' => (($w['downtime'] ?? '-') === '-') ? 0 : 1
                ]);

                $targetStatus = (string) ($w['status'] ?? 'Open');
                $actor = api_current_user();
                $actorId = isset($actor['id']) ? (int) $actor['id'] : null;
                $actorName = trim((string) ($actor['full_name'] ?? $actor['username'] ?? 'Pengguna'));
                if ($existingRow === false) {
                    $timeline = $db->prepare(
                        "INSERT INTO work_order_timeline
                         (wo_id, event_type, to_status, title, description, actor_user_id, actor_name)
                         VALUES (:wo_id, 'CREATED', :to_status, 'Work Order dibuat', :description, :actor_id, :actor_name)"
                    );
                    $timeline->execute([
                        ':wo_id' => (string) $w['woId'],
                        ':to_status' => $targetStatus,
                        ':description' => (string) ($w['issue'] ?? ''),
                        ':actor_id' => $actorId,
                        ':actor_name' => $actorName,
                    ]);
                } elseif ((string) $existingRow['status'] !== $targetStatus) {
                    if (in_array($targetStatus, ['In Progress', 'Waiting Part', 'Testing', 'Closed'], true)) {
                        $db->prepare('UPDATE work_orders SET repair_started_at = COALESCE(repair_started_at, NOW()) WHERE wo_id = :wo_id')
                            ->execute([':wo_id' => (string) $w['woId']]);
                    }
                    if ($targetStatus === 'Closed') {
                        $db->prepare('UPDATE work_orders SET closed_at = NOW() WHERE wo_id = :wo_id')
                            ->execute([':wo_id' => (string) $w['woId']]);
                    } elseif ((string) $existingRow['status'] === 'Closed') {
                        $db->prepare('UPDATE work_orders SET closed_at = NULL WHERE wo_id = :wo_id')
                            ->execute([':wo_id' => (string) $w['woId']]);
                    }
                    $timeline = $db->prepare(
                        "INSERT INTO work_order_timeline
                         (wo_id, event_type, from_status, to_status, title, description, actor_user_id, actor_name)
                         VALUES (:wo_id, 'STATUS_CHANGE', :from_status, :to_status, :title, :description, :actor_id, :actor_name)"
                    );
                    $timeline->execute([
                        ':wo_id' => (string) $w['woId'],
                        ':from_status' => (string) $existingRow['status'],
                        ':to_status' => $targetStatus,
                        ':title' => "Status berubah: {$existingRow['status']} menjadi {$targetStatus}",
                        ':description' => (string) ($w['repairAction'] ?? $w['repair_action'] ?? ''),
                        ':actor_id' => $actorId,
                        ':actor_name' => $actorName,
                    ]);
                }
            }
        }

        $db->commit();
        echo json_encode(["status" => "success", "message" => "State synced to database"]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Only POST is allowed"]);
}
?>
