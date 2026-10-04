<?php
require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$db = Database::getInstance();
ReportIntegration::ensureTables($db);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $scope = api_location_scope_clause('a', 'pm_location_id');
        $sql = "
            SELECT p.*, a.asset_code, a.category AS asset_category, a.make_model,
                   a.year_manufacture, l.location_name,
                   (SELECT rpi.report_id
                    FROM report_pm_integrations rpi
                    WHERE rpi.pm_plan_id = p.pm_plan_id AND rpi.reversed_at IS NULL
                    ORDER BY rpi.integration_id DESC LIMIT 1) AS source_report_id,
                   (SELECT rr.report_number
                    FROM report_pm_integrations rpi
                    INNER JOIN report_records rr ON rr.report_id = rpi.report_id
                    WHERE rpi.pm_plan_id = p.pm_plan_id AND rpi.reversed_at IS NULL
                    ORDER BY rpi.integration_id DESC LIMIT 1) AS source_report_number
            FROM pm_plans p 
            INNER JOIN assets a ON p.asset_id = a.asset_id
            LEFT JOIN locations l ON l.location_id = a.current_location_id
        ";
        if ($scope['sql'] !== '') $sql .= " WHERE " . $scope['sql'];
        $sql .= " ORDER BY p.target_due_hm ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($scope['params']);
        $result = $stmt->fetchAll();
        echo json_encode(["status" => "success", "data" => $result]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['asset_id']) || !isset($data['interval_hm'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }
        api_require_asset_access($db, (string) $data['asset_id']);

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
