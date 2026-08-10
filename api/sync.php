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
                INSERT INTO work_orders (wo_id, asset_id, status, priority, issue_description, assigned_mechanic, downtime_formatted) 
                VALUES (:wo, :ass, :st, :prio, :iss, :pic, :df)
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    priority = VALUES(priority), 
                    issue_description = VALUES(issue_description), 
                    assigned_mechanic = VALUES(assigned_mechanic), 
                    downtime_formatted = VALUES(downtime_formatted)
            ");
            foreach ($data['work_orders'] as $w) {
                if (!isset($w['woId']) || !isset($w['assetId'])) continue;
                api_require_asset_access($db, (string) $w['assetId']);
                $existingWorkOrder = $db->prepare('SELECT asset_id FROM work_orders WHERE wo_id = :wo_id LIMIT 1');
                $existingWorkOrder->execute([':wo_id' => (string) $w['woId']]);
                $existingAssetId = $existingWorkOrder->fetchColumn();
                if ($existingAssetId !== false) {
                    api_require_work_order_access($db, (string) $w['woId']);
                    if (!hash_equals((string) $existingAssetId, (string) $w['assetId'])) {
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
                    ':iss' => $w['issue'] ?? '',
                    ':pic' => $w['assignedTo'] ?? '',
                    ':df' => $w['downtime'] ?? ''
                ]);
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
