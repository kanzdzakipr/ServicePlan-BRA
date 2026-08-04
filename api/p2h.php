<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure table exists for P2H specific data
$db->exec("CREATE TABLE IF NOT EXISTS p2h_records (
    id VARCHAR(100) PRIMARY KEY,
    date DATETIME,
    unitId VARCHAR(100),
    category VARCHAR(100),
    operator VARCHAR(100),
    nrp VARCHAR(50),
    site VARCHAR(100),
    hmStart DECIMAL(10,2),
    hmEnd DECIMAL(10,2),
    status VARCHAR(50),
    criticalFails INT,
    warnings INT,
    notes TEXT,
    raw_data JSON
)");

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['id'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO p2h_records (id, date, unitId, category, operator, nrp, site, hmStart, hmEnd, status, criticalFails, warnings, notes, raw_data)
            VALUES (:id, :date, :unitId, :category, :operator, :nrp, :site, :hmStart, :hmEnd, :status, :criticalFails, :warnings, :notes, :raw_data)
        ");
        
        try {
            $stmt->execute([
                ':id' => $data['id'],
                ':date' => $data['date'] ?? date('Y-m-d H:i:s'),
                ':unitId' => $data['unitId'] ?? '',
                ':category' => $data['category'] ?? '',
                ':operator' => $data['operator'] ?? '',
                ':nrp' => $data['nrp'] ?? '',
                ':site' => $data['site'] ?? '',
                ':hmStart' => $data['hmStart'] ?? 0,
                ':hmEnd' => $data['hmEnd'] ?? 0,
                ':status' => $data['status'] ?? '',
                ':criticalFails' => $data['criticalFails'] ?? 0,
                ':warnings' => $data['warnings'] ?? 0,
                ':notes' => $data['notes'] ?? '',
                ':raw_data' => json_encode($data)
            ]);
            echo json_encode(["status" => "success", "message" => "P2H saved"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["status" => "error", "message" => "ID missing"]);
            exit;
        }
        $stmt = $db->prepare("DELETE FROM p2h_records WHERE id = :id");
        try {
            $stmt->execute([':id' => $_GET['id']]);
            echo json_encode(["status" => "success", "message" => "P2H deleted"]);
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
