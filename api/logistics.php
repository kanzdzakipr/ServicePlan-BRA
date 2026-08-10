<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure purchase_request_items table exists for line items
$db->exec("CREATE TABLE IF NOT EXISTS `purchase_request_items` (
    `id` VARCHAR(100) PRIMARY KEY,
    `spb_id` VARCHAR(50) NOT NULL,
    `part_number` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `qty_requested` INT DEFAULT 1,
    `status` VARCHAR(50) DEFAULT 'Menunggu Approval'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

switch ($method) {
    case 'GET':
        if (isset($_GET['type'])) {
            if ($_GET['type'] == 'parts') {
                $stmt = $db->query("SELECT * FROM parts");
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            } elseif ($_GET['type'] == 'costs') {
                $stmt = $db->query("SELECT * FROM cost_financial_monthly");
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            } elseif ($_GET['type'] == 'spb') {
                $scope = api_location_scope_clause('a', 'spb_location_id');
                $sql = "
                    SELECT pr.*, pri.id as item_id, pri.part_number, pri.description, pri.qty_requested, pri.status as item_status
                    FROM purchase_requests pr 
                    LEFT JOIN purchase_request_items pri ON pr.spb_id = pri.spb_id
                    INNER JOIN assets a ON a.asset_id = pr.asset_id
                ";
                if ($scope['sql'] !== '') $sql .= " WHERE " . $scope['sql'];
                $stmt = $db->prepare($sql);
                $stmt->execute($scope['params']);
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            }
        }
        echo json_encode(["status" => "error", "message" => "Type not specified or supported"]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['records'])) {
            echo json_encode(["status" => "error", "message" => "Invalid payload"]);
            break;
        }

        try {
            $db->beginTransaction();
            
            $stmtReq = $db->prepare("INSERT INTO purchase_requests (spb_id, wo_id, asset_id, requested_by, status) 
                                     VALUES (:spb, :wo, :asset, :user, :status)
                                     ON DUPLICATE KEY UPDATE status=VALUES(status)");
                                     
            $stmtItem = $db->prepare("INSERT INTO purchase_request_items (id, spb_id, part_number, description, qty_requested, status)
                                      VALUES (:id, :spb, :part, :desc, :qty, :status)
                                      ON DUPLICATE KEY UPDATE status=VALUES(status), qty_requested=VALUES(qty_requested)");

            foreach ($input['records'] as $r) {
                $assetId = (string) ($r['assetId'] ?? '');
                api_require_asset_access($db, $assetId);

                $existingRequest = $db->prepare('SELECT asset_id FROM purchase_requests WHERE spb_id = :spb_id LIMIT 1');
                $existingRequest->execute([':spb_id' => (string) ($r['spbId'] ?? '')]);
                $existingAssetId = $existingRequest->fetchColumn();
                if ($existingAssetId !== false) {
                    api_require_asset_access($db, (string) $existingAssetId);
                    if (!hash_equals((string) $existingAssetId, $assetId)) {
                        api_json_response(409, [
                            'status' => 'error',
                            'code' => 'OBJECT_ID_CONFLICT',
                            'message' => 'Nomor SPB sudah digunakan oleh objek lain.',
                        ]);
                    }
                }

                $existingItem = $db->prepare('SELECT pr.asset_id, pri.spb_id
                    FROM purchase_request_items pri
                    INNER JOIN purchase_requests pr ON pr.spb_id = pri.spb_id
                    WHERE pri.id = :item_id LIMIT 1');
                $existingItem->execute([':item_id' => (string) ($r['id'] ?? '')]);
                $itemOwner = $existingItem->fetch(PDO::FETCH_ASSOC);
                if ($itemOwner) {
                    api_require_asset_access($db, (string) $itemOwner['asset_id']);
                    if (!hash_equals((string) $itemOwner['spb_id'], (string) ($r['spbId'] ?? ''))) {
                        api_json_response(409, [
                            'status' => 'error',
                            'code' => 'OBJECT_ID_CONFLICT',
                            'message' => 'ID item sudah digunakan oleh SPB lain.',
                        ]);
                    }
                }
                // Insert Header (Ignore duplicates)
                $stmtReq->execute([
                    ':spb' => $r['spbId'],
                    ':wo' => $r['woId'] ?? '',
                    ':asset' => $r['assetId'] ?? '',
                    ':user' => (int) api_current_user()['id'],
                    ':status' => 'Submitted'
                ]);
                
                // Insert Item
                $stmtItem->execute([
                    ':id' => $r['id'],
                    ':spb' => $r['spbId'],
                    ':part' => $r['partNumber'],
                    ':desc' => $r['description'],
                    ':qty' => $r['qtyRequested'],
                    ':status' => $r['status'] ?? 'Menunggu Approval'
                ]);
            }
            
            $db->commit();
            echo json_encode(["status" => "success", "message" => "SPB saved successfully"]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
?>
