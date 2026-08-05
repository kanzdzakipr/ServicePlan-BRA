<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $db->query("
            SELECT p.*, a.asset_code 
            FROM pm_plans p 
            LEFT JOIN assets a ON p.asset_id = a.asset_id 
            ORDER BY p.target_due_hm ASC 
        ");
        $result = $stmt->fetchAll();
        echo json_encode(["status" => "success", "data" => $result]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['asset_id']) || !isset($data['interval_hm'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO pm_plans (
                asset_id, interval_hm, current_smr, last_service_hm, 
                last_service_date, target_due_hm, variance_hm, status, 
                warranty_status, planner_note
            ) VALUES (
                :asset_id, :interval, :smr, :last_hm, 
                :last_date, :target, :variance, :status, 
                :warranty, :note
            )
        ");
        
        try {
            $stmt->execute([
                ':asset_id' => $data['asset_id'],
                ':interval' => $data['interval_hm'],
                ':smr' => $data['current_smr'] ?? 0,
                ':last_hm' => $data['last_service_hm'] ?? 0,
                ':last_date' => $data['last_service_date'] ?? date('Y-m-d'),
                ':target' => $data['target_due_hm'] ?? 0,
                ':variance' => $data['variance_hm'] ?? 0,
                ':status' => $data['status'] ?? 'PLANNED',
                ':warranty' => $data['warranty_status'] ?? 'No Warranty',
                ':note' => $data['planner_note'] ?? ''
            ]);
            echo json_encode(["status" => "success", "message" => "PM Plan created successfully"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
?>
