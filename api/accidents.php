<?php
require_once 'db.php';
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Auto-add payload_json column if missing
try {
    $db->exec("ALTER TABLE accidents ADD COLUMN payload_json JSON NULL AFTER is_unit_locked");
} catch (PDOException $e) {
    // Ignore error if column already exists
}

switch ($method) {
    case 'GET':
        $stmt = $db->query("SELECT payload_json FROM accidents WHERE payload_json IS NOT NULL ORDER BY incident_datetime DESC LIMIT 100");
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
            // Using REPLACE so we can safely overwrite when updating existing incidents
            $stmt = $db->prepare("REPLACE INTO accidents (accident_id, asset_id, report_date, incident_datetime, operator_name, chronology, severity, payload_json) 
                                  VALUES (:id, :asset, :rdate, :idate, :operator, :chronology, :severity, :payload)");
            
            // Accept either single object or array
            $accidents = isset($input[0]) ? $input : [$input];
            
            foreach ($accidents as $acc) {
                // Map the frontend structure
                $id = $acc['docNo'] ?? ('ACC-' . time());
                
                // asset_id MUST exist in `assets` table because of FOREIGN KEY
                // The frontend passes `unitCode` as "CS-41001", but the asset_id might be "CS-41001"
                $asset = $acc['unitCode'] ?? '';
                
                $rDate = $acc['reportDate'] ?? date('Y-m-d');
                $iDate = $acc['incidentDate'] ?? date('Y-m-d H:i:s');
                $operator = $acc['operatorName'] ?? '';
                $chronology = $acc['chronology'] ?? '';
                $severity = $acc['severity'] ?? 'Minor';
                if (!in_array($severity, ['Minor', 'Moderate', 'Critical'])) $severity = 'Minor';

                // Check if asset exists before inserting to prevent foreign key constraint failures
                $checkStmt = $db->prepare("SELECT 1 FROM assets WHERE asset_id = :asset");
                $checkStmt->execute([':asset' => $asset]);
                if (!$checkStmt->fetch()) {
                    // if asset doesn't exist, we must create a placeholder or skip
                    // For safety, we will just use a generic 'UNKNOWN' asset or throw error
                    // Let's create it on the fly if missing (this is dangerous but prevents total crash)
                    $insertAsset = $db->prepare("INSERT IGNORE INTO assets (asset_id, asset_code) VALUES (:asset1, :asset2)");
                    $insertAsset->execute([':asset1' => $asset, ':asset2' => $asset]);
                }

                $stmt->execute([
                    ':id' => $id,
                    ':asset' => $asset,
                    ':rdate' => $rDate,
                    ':idate' => $iDate,
                    ':operator' => $operator,
                    ':chronology' => $chronology,
                    ':severity' => $severity,
                    ':payload' => json_encode($acc)
                ]);
            }

            $db->commit();
            echo json_encode(["status" => "success", "message" => "Accident records saved to database"]);
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
