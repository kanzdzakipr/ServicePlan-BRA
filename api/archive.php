<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

function archiveItemAccessible(PDO $db, string $type, string $id): bool
{
    if (!in_array($type, ['asset', 'p2h', 'accident'], true) || trim($id) === '') return false;
    $scope = api_location_scope_clause('a', 'archive_location_id');
    $params = [':id' => trim($id)];

    if ($type === 'asset') {
        $sql = 'SELECT 1 FROM assets a WHERE a.asset_id = :id';
    } elseif ($type === 'accident') {
        $sql = 'SELECT 1 FROM accidents ac INNER JOIN assets a ON a.asset_id = ac.asset_id WHERE ac.accident_id = :id';
    } else {
        $sql = "SELECT 1 FROM inspections i INNER JOIN assets a ON a.asset_id = i.asset_id
                WHERE (CAST(i.inspection_id AS CHAR) = :inspection_id
                       OR JSON_UNQUOTE(JSON_EXTRACT(i.payload_json, '$.id')) = :payload_id)";
        $params = [':inspection_id' => trim($id), ':payload_id' => trim($id)];
    }
    $params = array_merge($params, $scope['params']);
    if ($scope['sql'] !== '') $sql .= ' AND ' . $scope['sql'];
    $stmt = $db->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}

function requireArchiveItemAccess(PDO $db, string $type, string $id): void
{
    if (!archiveItemAccessible($db, $type, $id)) {
        api_json_response(404, [
            'status' => 'error',
            'code' => 'OBJECT_NOT_FOUND',
            'message' => 'Objek tidak ditemukan atau tidak dapat diakses.',
        ]);
    }
}

// Auto-create table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS archived_items (
        item_type VARCHAR(50) NOT NULL,
        item_id VARCHAR(100) NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (item_type, item_id)
    )");
} catch (PDOException $e) {
    // Ignore error if we don't have permission, let the query fail later if so
}

switch ($method) {
    case 'GET':
        // Retrieve all archived items
        try {
            $stmt = $db->query("SELECT item_type, item_id FROM archived_items");
            $result = array_values(array_filter(
                $stmt->fetchAll(),
                static fn(array $row): bool => archiveItemAccessible($db, (string) $row['item_type'], (string) $row['item_id'])
            ));
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
        requireArchiveItemAccess($db, (string) $type, (string) $id);

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
