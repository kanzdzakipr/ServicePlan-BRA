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
                $stmt = $db->query("
                    SELECT pr.*, pri.id as item_id, pri.part_number, pri.description, pri.qty_requested, pri.status as item_status
                    FROM purchase_requests pr 
                    LEFT JOIN purchase_request_items pri ON pr.spb_id = pri.spb_id
                ");
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
                // Insert Header (Ignore duplicates)
                $stmtReq->execute([
                    ':spb' => $r['spbId'],
                    ':wo' => $r['woId'] ?? '',
                    ':asset' => $r['assetId'] ?? '',
                    ':user' => 1, // Default user
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
