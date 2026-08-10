<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Jika ada id, ambil 1 data, jika tidak, ambil semua
        if (isset($_GET['id'])) {
            $scope = api_location_scope_clause('a', 'asset_list_location_id');
            $sql = "SELECT a.* FROM assets a WHERE a.asset_id = :id";
            if ($scope['sql'] !== '') $sql .= " AND " . $scope['sql'];
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge([':id' => (string) $_GET['id']], $scope['params']));
            $result = $stmt->fetch();
        } else {
            $scope = api_location_scope_clause('a', 'asset_list_location_id');
            $sql = "
                SELECT a.*, l.location_name as location_name 
                FROM assets a 
                LEFT JOIN locations l ON a.current_location_id = l.location_id
            ";
            if ($scope['sql'] !== '') $sql .= " WHERE " . $scope['sql'];
            $stmt = $db->prepare($sql);
            $stmt->execute($scope['params']);
            $result = $stmt->fetchAll();
        }
        echo json_encode(["status" => "success", "data" => $result]);
        break;

    case 'POST':
        // Menambah aset baru
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['asset_id'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }

        $targetLocationId = isset($data['current_location_id']) ? (int) $data['current_location_id'] : api_current_location_id();
        if (!api_can_access_location($targetLocationId)) {
            api_json_response(403, [
                'status' => 'error',
                'code' => 'LOCATION_SCOPE_DENIED',
                'message' => 'Lokasi aset berada di luar cakupan akun Anda.',
            ]);
        }

        $stmt = $db->prepare("
            INSERT INTO assets (asset_id, asset_code, serial_number, license_plate, type, category, status, current_location_id)
            VALUES (:id, :code, :sn, :plate, :type, :cat, :status, :loc_id)
        ");
        
        try {
            $stmt->execute([
                ':id' => $data['asset_id'],
                ':code' => $data['asset_code'] ?? $data['asset_id'],
                ':sn' => $data['serial_number'] ?? null,
                ':plate' => $data['license_plate'] ?? null,
                ':type' => $data['type'] ?? 'Heavy Equipment',
                ':cat' => $data['category'] ?? 'Excavator',
                ':status' => $data['status'] ?? 'READY',
                ':loc_id' => $targetLocationId
            ]);
            echo json_encode(["status" => "success", "message" => "Asset created"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Mengubah data aset yang ada
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || (!isset($data['asset_id']) && !isset($_GET['id']))) {
            echo json_encode(["status" => "error", "message" => "Invalid data or ID missing"]);
            exit;
        }

        $id = $_GET['id'] ?? $data['asset_id'];
        api_require_asset_access($db, (string) $id);

        if (array_key_exists('current_location_id', $data)) {
            $targetLocationId = $data['current_location_id'] !== null ? (int) $data['current_location_id'] : null;
            if (!api_can_access_location($targetLocationId)) {
                api_json_response(403, [
                    'status' => 'error',
                    'code' => 'LOCATION_SCOPE_DENIED',
                    'message' => 'Lokasi tujuan berada di luar cakupan akun Anda.',
                ]);
            }
        }
        
        // Buat query update dinamis berdasarkan data yang dikirim
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['asset_code', 'serial_number', 'license_plate', 'type', 'category', 'status', 'current_location_id', 'last_hm_km'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            echo json_encode(["status" => "error", "message" => "No fields to update"]);
            exit;
        }

        $query = "UPDATE assets SET " . implode(", ", $fields) . " WHERE asset_id = :id";
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute($params);
            echo json_encode(["status" => "success", "message" => "Asset updated"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["status" => "error", "message" => "ID missing"]);
            exit;
        }
        api_require_asset_access($db, (string) $_GET['id']);
        $stmt = $db->prepare("DELETE FROM assets WHERE asset_id = :id");
        try {
            $stmt->execute([':id' => $_GET['id']]);
            echo json_encode(["status" => "success", "message" => "Asset deleted"]);
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
