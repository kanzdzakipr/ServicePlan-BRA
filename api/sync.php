<?php
require_once 'db.php';
require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';
require_once dirname(__DIR__) . '/core/FleetSyncPolicy.php';
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        echo json_encode(["status" => "error", "message" => "No data provided"]);
        exit;
    }

    $policyError = is_array($data) ? FleetSyncPolicy::error($data) : 'Payload sinkronisasi tidak valid.';
    if ($policyError !== null) {
        api_json_response(422, ['status' => 'error', 'code' => 'TARGETED_SYNC_REQUIRED', 'message' => $policyError]);
    }

    try {
        $db->beginTransaction();

        // 1. Update Assets
        if (isset($data['assets']) && is_array($data['assets'])) {
            $stmtAsset = $db->prepare("
                UPDATE assets 
                SET status = :status,
                    raw_location_notes = COALESCE(:loc, raw_location_notes),
                    last_hm_km = GREATEST(COALESCE(last_hm_km, 0), :hm)
                WHERE asset_id = :id
            ");
            foreach ($data['assets'] as $a) {
                api_require_asset_access($db, (string) ($a['id'] ?? ''));
                $currentAsset = $db->prepare('SELECT status FROM assets WHERE asset_id = :id FOR UPDATE');
                $currentAsset->execute([':id' => (string) $a['id']]);
                $currentStatus = $currentAsset->fetchColumn();
                if ($currentStatus === false || (string) $currentStatus !== (string) $a['expected_status']) {
                    $db->rollBack();
                    api_json_response(409, [
                        'status' => 'error', 'code' => 'STALE_ASSET_STATUS',
                        'message' => 'Status unit telah berubah di server. Muat ulang sebelum menyimpan.',
                    ]);
                }
                $stmtAsset->execute([
                    ':status' => $a['status'] ?? 'READY',
                    ':loc' => isset($a['location']) && trim((string) $a['location']) !== '' ? trim((string) $a['location']) : null,
                    ':hm' => $a['last_hm_km'] ?? 0,
                    ':id' => $a['id']
                ]);
            }
        }

        // 2. Upsert Work Orders
        if (isset($data['work_orders']) && is_array($data['work_orders'])) {
            $stmtWO = $db->prepare("
                INSERT INTO work_orders (wo_id, asset_id, hull_number, equipment_type, current_hm_km,
                                         trouble_at, raw_location, status, priority, maintenance_type, issue_description,
                                         assigned_mechanic, assigned_mechanics_json, root_cause, repair_action,
                                         downtime_formatted, is_downtime, is_forced_operation, mitigation_action,
                                         repair_started_at, estimated_completion_at)
                VALUES (:wo, :ass, :hull_number, :equipment_type, :current_hm_km,
                        :trouble_at, :raw_location, :st, :prio, :maintenance_type, :iss,
                        :pic, :assigned_mechanics_json, :root_cause, :repair_action,
                        :df, :is_downtime, :is_forced_operation, :mitigation_action,
                        :repair_started_at, :estimated_completion_at)
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    priority = VALUES(priority), 
                    maintenance_type = VALUES(maintenance_type),
                    issue_description = VALUES(issue_description), 
                    assigned_mechanic = VALUES(assigned_mechanic), 
                    assigned_mechanics_json = COALESCE(VALUES(assigned_mechanics_json), assigned_mechanics_json),
                    hull_number = COALESCE(VALUES(hull_number), hull_number),
                    equipment_type = COALESCE(VALUES(equipment_type), equipment_type),
                    current_hm_km = COALESCE(VALUES(current_hm_km), current_hm_km),
                    trouble_at = COALESCE(VALUES(trouble_at), trouble_at),
                    raw_location = COALESCE(VALUES(raw_location), raw_location),
                    root_cause = COALESCE(NULLIF(VALUES(root_cause), ''), root_cause),
                    repair_action = COALESCE(NULLIF(VALUES(repair_action), ''), repair_action),
                    downtime_formatted = VALUES(downtime_formatted),
                    is_downtime = VALUES(is_downtime),
                    is_forced_operation = VALUES(is_forced_operation),
                    mitigation_action = COALESCE(NULLIF(VALUES(mitigation_action), ''), mitigation_action),
                    repair_started_at = COALESCE(VALUES(repair_started_at), repair_started_at),
                    estimated_completion_at = COALESCE(VALUES(estimated_completion_at), estimated_completion_at)
            ");
            foreach ($data['work_orders'] as $w) {
                if (!isset($w['woId']) || !isset($w['assetId'])) continue;
                api_require_asset_access($db, (string) $w['assetId']);
                $existingWorkOrder = $db->prepare('SELECT asset_id, status, issue_description FROM work_orders WHERE wo_id = :wo_id LIMIT 1 FOR UPDATE');
                $existingWorkOrder->execute([':wo_id' => (string) $w['woId']]);
                $existingRow = $existingWorkOrder->fetch(PDO::FETCH_ASSOC);
                if ($existingRow !== false) {
                    api_require_work_order_access($db, (string) $w['woId']);
                    if (!hash_equals((string) $existingRow['asset_id'], (string) $w['assetId'])) {
                        $db->rollBack();
                        api_json_response(409, [
                            'status' => 'error',
                            'code' => 'OBJECT_ID_CONFLICT',
                            'message' => 'ID work order sudah digunakan oleh aset lain.',
                        ]);
                    }
                    // This endpoint creates generated WOs only. Existing WOs are
                    // updated through work_orders.php, never by a stale UI snapshot.
                    if ((string) $existingRow['status'] === (string) $w['status']
                        && (string) $existingRow['issue_description'] === (string) ($w['issue'] ?? '')) {
                        continue; // Idempotent retry of the same generated WO.
                    }
                    $db->rollBack();
                    api_json_response(409, [
                        'status' => 'error', 'code' => 'WORK_ORDER_EXISTS',
                        'message' => 'Work Order sudah berubah. Muat ulang dan gunakan detail WO.',
                    ]);
                }
                $mechanicInput = $w['assignedMechanics'] ?? $w['assigned_mechanics'] ?? $w['assignedTo'] ?? '';
                $mechanics = WorkOrderSupport::normalizeMechanics($mechanicInput);
                $assignedTo = $mechanics ? implode(', ', $mechanics) : trim((string) ($w['assignedTo'] ?? 'Belum ada PIC'));
                $forcedOperation = in_array(strtolower((string) ($w['isForcedOperation'] ?? $w['is_forced_operation'] ?? '0')), ['1', 'true', 'yes', 'on'], true);
                $isDowntime = !$forcedOperation && (($w['isDowntime'] ?? $w['is_downtime'] ?? (($w['downtime'] ?? '-') !== '-')) ? true : false);
                $normalizeDateTime = static function (mixed $value): ?string {
                    $value = trim((string) ($value ?? ''));
                    return $value === '' ? null : str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
                };

                $stmtWO->execute([
                    ':wo' => $w['woId'],
                    ':ass' => $w['assetId'],
                    ':hull_number' => ($w['hullNumber'] ?? $w['hull_number'] ?? '') !== '' ? ($w['hullNumber'] ?? $w['hull_number']) : null,
                    ':equipment_type' => ($w['equipmentType'] ?? $w['equipment_type'] ?? '') !== '' ? ($w['equipmentType'] ?? $w['equipment_type']) : null,
                    ':current_hm_km' => is_numeric($w['currentHmKm'] ?? $w['current_hm_km'] ?? null) ? (float) ($w['currentHmKm'] ?? $w['current_hm_km']) : null,
                    ':trouble_at' => $normalizeDateTime($w['troubleAt'] ?? $w['trouble_at'] ?? null),
                    ':raw_location' => ($w['rawLocation'] ?? $w['raw_location'] ?? '') !== '' ? ($w['rawLocation'] ?? $w['raw_location']) : null,
                    ':st' => $w['status'] ?? 'Open',
                    ':prio' => $w['priority'] ?? 'Normal',
                    ':maintenance_type' => $w['maintenanceType'] ?? $w['maintenance_type'] ?? 'Corrective',
                    ':iss' => $w['issue'] ?? '',
                    ':pic' => $assignedTo,
                    ':assigned_mechanics_json' => $mechanics ? json_encode($mechanics, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null,
                    ':root_cause' => $w['rootCause'] ?? $w['root_cause'] ?? '',
                    ':repair_action' => $w['repairAction'] ?? $w['repair_action'] ?? '',
                    ':df' => $w['downtime'] ?? '',
                    ':is_downtime' => $isDowntime ? 1 : 0,
                    ':is_forced_operation' => $forcedOperation ? 1 : 0,
                    ':mitigation_action' => $w['mitigationAction'] ?? $w['mitigation_action'] ?? null,
                    ':repair_started_at' => $normalizeDateTime($w['repairStartedAt'] ?? $w['repair_started_at'] ?? null),
                    ':estimated_completion_at' => $normalizeDateTime($w['estimatedCompletionAt'] ?? $w['estimated_completion_at'] ?? null),
                ]);

                if (array_key_exists('currentHmKm', $w) || array_key_exists('current_hm_km', $w) || $forcedOperation) {
                    $assetStatus = WorkOrderSupport::assetStatusFor($forcedOperation, $isDowntime);
                    $meter = is_numeric($w['currentHmKm'] ?? $w['current_hm_km'] ?? null)
                        ? (float) ($w['currentHmKm'] ?? $w['current_hm_km'])
                        : 0.0;
                    $db->prepare(
                        "UPDATE assets SET status = CASE
                            WHEN status IN ('ACCIDENT_HOLD', 'INACTIVE') THEN status
                            ELSE :status
                         END,
                         last_hm_km = GREATEST(COALESCE(last_hm_km, 0), :meter)
                         WHERE asset_id = :asset_id"
                    )->execute([':status' => $assetStatus, ':meter' => $meter, ':asset_id' => (string) $w['assetId']]);
                }

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
