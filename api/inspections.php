<?php
require_once 'db.php';
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-add payload_json column if missing
try {
    $db->exec("ALTER TABLE inspections ADD COLUMN payload_json JSON NULL AFTER findings_summary");
} catch (PDOException $e) {
    // Ignore error if column already exists
}

switch ($method) {
    case 'GET':
        $stmt = $db->query("SELECT payload_json FROM inspections WHERE payload_json IS NOT NULL ORDER BY inspection_date DESC LIMIT 300");
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $parsed = json_decode($row['payload_json'], true);
            if ($parsed) $results[] = $parsed;
        }
        echo json_encode(["status" => "success", "data" => $results]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(["status" => "error", "message" => "No data provided"]);
            break;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO inspections (asset_id, inspector_id, inspection_date, current_hm_km, overall_result, findings_summary, payload_json) 
                                  VALUES (:asset, :inspector, :date, :hm, :result, :summary, :payload)");
            
            // Accept either single object or array
            $inspections = isset($input[0]) ? $input : [$input];
            
            foreach ($inspections as $ins) {
                // Determine result based on status string (PASS / WARNING / FAIL)
                $statusStr = strtoupper($ins['status'] ?? 'PASS');
                $result = 'PASS';
                if (strpos($statusStr, 'FAIL') !== false || strpos($statusStr, 'TIDAK LULUS') !== false) {
                    $result = 'FAIL';
                } elseif (strpos($statusStr, 'WARNING') !== false || strpos($statusStr, 'CATATAN') !== false) {
                    $result = 'WARNING';
                }

                // Default ID logic
                $assetId = $ins['unitId'] ?? $ins['assetId'] ?? '';
                $hm = $ins['hmEnd'] ?? $ins['hmStart'] ?? 0;
                $date = $ins['date'] ?? date('Y-m-d H:i:s');
                $summary = $ins['notes'] ?? '';

                $stmt->execute([
                    ':asset' => $assetId,
                    ':inspector' => 1, // Default user
                    ':date' => $date,
                    ':hm' => $hm,
                    ':result' => $result,
                    ':summary' => $summary,
                    ':payload' => json_encode($ins)
                ]);
            }

            $db->commit();
            echo json_encode(["status" => "success", "message" => "Inspection saved to database"]);
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
