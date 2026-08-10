<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $scope = api_location_scope_clause('a', 'wo_list_location_id');
            $sql = "SELECT w.*, a.asset_code, a.category AS asset_category
                    FROM work_orders w
                    INNER JOIN assets a ON w.asset_id = a.asset_id
                    WHERE w.wo_id = :id";
            if ($scope['sql'] !== '') $sql .= " AND " . $scope['sql'];
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge([':id' => (string) $_GET['id']], $scope['params']));
            $result = $stmt->fetch();
        } else {
            $scope = api_location_scope_clause('a', 'wo_list_location_id');
            $sql = "
                SELECT w.*, a.asset_code, a.category as asset_category
                FROM work_orders w
                INNER JOIN assets a ON w.asset_id = a.asset_id
            ";
            if ($scope['sql'] !== '') $sql .= " WHERE " . $scope['sql'];
            $sql .= " ORDER BY w.reported_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($scope['params']);
            $result = $stmt->fetchAll();
        }
        echo json_encode(["status" => "success", "data" => $result]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['wo_id']) || !isset($data['asset_id'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }
        api_require_asset_access($db, (string) $data['asset_id']);

        $stmt = $db->prepare("
            INSERT INTO work_orders (wo_id, asset_id, issue_description, status, priority, assigned_mechanic)
            VALUES (:wo_id, :asset_id, :issue, :status, :prio, :pic)
        ");
        
        try {
            $stmt->execute([
                ':wo_id' => $data['wo_id'],
                ':asset_id' => $data['asset_id'],
                ':issue' => $data['issue_description'] ?? '',
                ':status' => $data['status'] ?? 'Open',
                ':prio' => $data['priority'] ?? 'Normal',
                ':pic' => $data['assigned_mechanic'] ?? 'Belum ada PIC'
            ]);

            // Auto-create system notification
            try {
                $notifStmt = $db->prepare("INSERT INTO system_notifications 
                    (menu_name, user_name, user_role, action_type, title, message, involved_parties_json, related_tables_json, is_read, created_at)
                    VALUES ('Work Order Kanban', :user, 'Planner', 'WO_CREATE', :title, :msg, :parties, :tables, 0, NOW())");
                $notifStmt->execute([
                    ':user' => $data['created_by'] ?? 'Planner',
                    ':title' => "Penerbitan Work Order #{$data['wo_id']} ({$data['asset_id']})",
                    ':msg' => "Work Order #{$data['wo_id']} diterbitkan untuk unit {$data['asset_id']}. Prioritas: " . ($data['priority'] ?? 'Normal') . ". PIC: " . ($data['assigned_mechanic'] ?? 'Belum ada PIC'),
                    ':parties' => json_encode([$data['created_by'] ?? 'Planner', $data['assigned_mechanic'] ?? 'Tim Mekanik']),
                    ':tables' => json_encode(['tables' => ['work_orders', 'assets'], 'records' => [['table' => 'work_orders', 'id' => $data['wo_id']], ['table' => 'assets', 'id' => $data['asset_id']]]])
                ]);
            } catch (Exception $ne) {}

            echo json_encode(["status" => "success", "message" => "Work Order created"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || (!isset($data['wo_id']) && !isset($_GET['id']))) {
            echo json_encode(["status" => "error", "message" => "Invalid data or ID missing"]);
            exit;
        }

        $id = $_GET['id'] ?? $data['wo_id'];
        api_require_work_order_access($db, (string) $id);
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['status', 'priority', 'assigned_mechanic', 'issue_description', 'downtime_minutes'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            echo json_encode(["status" => "error", "message" => "No fields to update"]);
            exit;
        }

        $query = "UPDATE work_orders SET " . implode(", ", $fields) . " WHERE wo_id = :id";
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute($params);

            // Auto-create system notification for WO update
            try {
                $notifStmt = $db->prepare("INSERT INTO system_notifications 
                    (menu_name, user_name, user_role, action_type, title, message, involved_parties_json, related_tables_json, is_read, created_at)
                    VALUES ('Work Order Kanban', :user, 'Planner', 'WO_UPDATE', :title, :msg, :parties, :tables, 0, NOW())");
                $notifStmt->execute([
                    ':user' => $data['updated_by'] ?? 'Planner',
                    ':title' => "Pembaruan Status Work Order #{$id}",
                    ':msg' => "Work Order #{$id} diperbarui" . (isset($data['status']) ? " menjadi status {$data['status']}" : "") . (isset($data['assigned_mechanic']) ? " dengan PIC {$data['assigned_mechanic']}" : ""),
                    ':parties' => json_encode([$data['updated_by'] ?? 'Planner', $data['assigned_mechanic'] ?? 'Tim Mekanik']),
                    ':tables' => json_encode(['tables' => ['work_orders'], 'records' => [['table' => 'work_orders', 'id' => $id]]])
                ]);
            } catch (Exception $ne) {}

            echo json_encode(["status" => "success", "message" => "Work Order updated"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["status" => "error", "message" => "ID missing"]);
            exit;
        }
        api_require_work_order_access($db, (string) $_GET['id']);
        $stmt = $db->prepare("DELETE FROM work_orders WHERE wo_id = :id");
        try {
            $stmt->execute([':id' => $_GET['id']]);
            echo json_encode(["status" => "success", "message" => "Work Order deleted"]);
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
