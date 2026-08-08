<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retrieve all archived items
        try {
            $stmt = $db->query("SELECT item_type, item_id FROM archived_items");
            $result = $stmt->fetchAll();
            echo json_encode(["status" => "success", "data" => $result]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'POST':
        // Archive or restore an item
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['type']) || !isset($input['id']) || !isset($input['action'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }

        $type = $input['type'];
        $id = $input['id'];
        $action = $input['action'];

        try {
            if ($action === 'archive') {
                $stmt = $db->prepare("INSERT IGNORE INTO archived_items (item_type, item_id) VALUES (:type, :id)");
                $stmt->execute([':type' => $type, ':id' => $id]);
                echo json_encode(["status" => "success", "message" => "Item archived successfully"]);
            } else if ($action === 'restore') {
                $stmt = $db->prepare("DELETE FROM archived_items WHERE item_type = :type AND item_id = :id");
                $stmt->execute([':type' => $type, ':id' => $id]);
                echo json_encode(["status" => "success", "message" => "Item restored successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid action"]);
            }
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
