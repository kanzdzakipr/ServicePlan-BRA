<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retrieve fuel logs
        $stmt = $db->query("
            SELECT f.*, a.category AS asset_category 
            FROM fuel_logs f 
            LEFT JOIN assets a ON f.asset_id = a.asset_id 
            ORDER BY f.refuel_date DESC 
            LIMIT 100
        ");
        $result = $stmt->fetchAll();
        echo json_encode(["status" => "success", "data" => $result]);
        break;

    case 'POST':
        // Insert new fuel log
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['asset_id'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO fuel_logs (
                asset_id, refuel_date, flowmeter_start, flowmeter_end, 
                liters_issued, current_hm_km, calculated_lph, baseline_lph, 
                is_anomaly, driver_name
            ) VALUES (
                :asset_id, :refuel_date, :f_start, :f_end, 
                :liters, :hm_km, :lph, :baseline, 
                :anomaly, :driver
            )
        ");
        
        try {
            $stmt->execute([
                ':asset_id' => $data['asset_id'],
                ':refuel_date' => date('Y-m-d H:i:s'), // or from frontend
                ':f_start' => $data['flowmeter_start'] ?? 0,
                ':f_end' => $data['flowmeter_end'] ?? 0,
                ':liters' => $data['liters_issued'] ?? 0,
                ':hm_km' => $data['current_hm_km'] ?? 0,
                ':lph' => $data['calculated_lph'] ?? 0,
                ':baseline' => $data['baseline_lph'] ?? 0,
                ':anomaly' => (isset($data['is_anomaly']) && $data['is_anomaly']) ? 1 : 0,
                ':driver' => $data['driver_name'] ?? ''
            ]);
            
            // Optionally, update asset's last HM/KM
            $updateAsset = $db->prepare("UPDATE assets SET last_hm_km = :hm_km WHERE asset_id = :asset_id AND last_hm_km < :hm_km");
            $updateAsset->execute([
                ':hm_km' => $data['current_hm_km'] ?? 0,
                ':asset_id' => $data['asset_id']
            ]);

            echo json_encode(["status" => "success", "message" => "Fuel log recorded successfully"]);
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
