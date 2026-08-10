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
        $scope = api_location_scope_clause('a', 'inspection_location_id');
        $sql = "SELECT i.payload_json FROM inspections i
                INNER JOIN assets a ON a.asset_id = i.asset_id
                WHERE i.payload_json IS NOT NULL";
        if ($scope['sql'] !== '') $sql .= " AND " . $scope['sql'];
        $sql .= " ORDER BY i.inspection_date DESC LIMIT 300";
        $stmt = $db->prepare($sql);
        $stmt->execute($scope['params']);
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
                api_require_asset_access($db, (string) $assetId);
                $hm = $ins['hmEnd'] ?? $ins['hmStart'] ?? 0;
                $date = $ins['date'] ?? date('Y-m-d H:i:s');
                $summary = $ins['notes'] ?? '';

                $stmt->execute([
                    ':asset' => $assetId,
                    ':inspector' => (int) api_current_user()['id'],
                    ':date' => $date,
                    ':hm' => $hm,
                    ':result' => $result,
                    ':summary' => $summary,
                    ':payload' => json_encode($ins)
                ]);

                // Auto-create notification entry in system_notifications
                try {
                    $insId = $db->lastInsertId();
                    $notifStmt = $db->prepare("INSERT INTO system_notifications 
                        (menu_name, user_name, user_role, action_type, title, message, involved_parties_json, related_tables_json, is_read, created_at)
                        VALUES ('Inspections', :user, 'Inspector', :type, :title, :msg, :parties, :tables, 0, NOW())");
                    
                    $actionType = ($result === 'FAIL') ? 'P2H_REJECT' : 'P2H_SUBMIT';
                    $titleStr = ($result === 'FAIL') ? "Inspeksi P2H Ditolak - {$assetId} (BREAKDOWN)" : "Form P2H #{$insId} Disetujui ({$assetId})";
                    $msgStr = "Inspector memasukkan data inspeksi P2H untuk unit {$assetId}. Hasil: {$result}. " . ($summary ? "Catatan: {$summary}" : "");
                    
                    $notifStmt->execute([
                        ':user' => $ins['operatorName'] ?? 'Inspector',
                        ':type' => $actionType,
                        ':title' => $titleStr,
                        ':msg' => $msgStr,
                        ':parties' => json_encode([$ins['operatorName'] ?? 'Inspector', 'Supervisor Yard']),
                        ':tables' => json_encode(['tables' => ['inspections', 'assets'], 'records' => [['table' => 'inspections', 'id' => (string)$insId], ['table' => 'assets', 'id' => $assetId]]])
                    ]);
                } catch (Exception $ne) {
                    // Ignore non-critical notification error
                }
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
