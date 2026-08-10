<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $scope = api_location_scope_clause('a', 'tire_location_id');
        $sql = "
            SELECT t.*, a.asset_code 
            FROM tire_inspections t 
            INNER JOIN assets a ON t.asset_id = a.asset_id
        ";
        if ($scope['sql'] !== '') $sql .= " WHERE " . $scope['sql'];
        $sql .= " ORDER BY t.inspected_at DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($scope['params']);
        $result = $stmt->fetchAll();
        echo json_encode(["status" => "success", "data" => $result]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['asset_id']) || !isset($data['tire_position'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }
        api_require_asset_access($db, (string) $data['asset_id']);

        $stmt = $db->prepare("
            INSERT INTO tire_inspections (
                asset_id, tire_serial_no, tire_position, 
                tread_depth_mm, air_pressure_psi, condition_color
            ) VALUES (
                :asset_id, :serial, :position, 
                :tread, :pressure, :color
            )
        ");
        
        try {
            $stmt->execute([
                ':asset_id' => $data['asset_id'],
                ':serial' => $data['tire_serial_no'] ?? null,
                ':position' => $data['tire_position'],
                ':tread' => $data['tread_depth_mm'] ?? 0,
                ':pressure' => $data['air_pressure_psi'] ?? 0,
                ':color' => $data['condition_color'] ?? 'GREEN'
            ]);
            echo json_encode(["status" => "success", "message" => "Tire inspection recorded"]);
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
