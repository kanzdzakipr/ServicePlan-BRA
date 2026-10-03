<?php
require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
ReportIntegration::ensureTables($db);

// Ensure purchase_request_items table exists for line items
$db->exec("CREATE TABLE IF NOT EXISTS `purchase_request_items` (
    `id` VARCHAR(100) PRIMARY KEY,
    `spb_id` VARCHAR(50) NOT NULL,
    `part_number` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `qty_requested` INT DEFAULT 1,
    `status` VARCHAR(50) DEFAULT 'Menunggu Approval'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

switch ($method) {
    case 'GET':
        if (isset($_GET['type'])) {
            if ($_GET['type'] == 'inventory') {
                $stockStatement = $db->query(
                    "SELECT p.part_id, p.part_number, p.part_name, p.unit_measure, p.stock_qty,
                            p.min_stock_qty, p.location_warehouse,
                            COALESCE(SUM(CASE
                                WHEN it.movement_type = 'IN' AND it.reversed_at IS NULL THEN it.quantity
                                ELSE 0
                            END), 0) AS received_total
                     FROM parts p
                     LEFT JOIN inventory_transactions it ON it.part_id = p.part_id
                     GROUP BY p.part_id, p.part_number, p.part_name, p.unit_measure, p.stock_qty,
                              p.min_stock_qty, p.location_warehouse
                     ORDER BY p.part_name, p.part_number"
                );
                $stock = array_map(static function (array $row): array {
                    return [
                        'partId' => (int) $row['part_id'],
                        'partNumber' => (string) $row['part_number'],
                        'namaParts' => (string) $row['part_name'],
                        'satuan' => (string) $row['unit_measure'],
                        'penerimaanTotal' => (int) $row['received_total'],
                        'pemakaianTotal' => 0,
                        'saldo' => (int) $row['stock_qty'],
                        'minimumStock' => (int) $row['min_stock_qty'],
                        'gudang' => (string) $row['location_warehouse'],
                        'source' => 'Database parts / integrasi laporan',
                    ];
                }, $stockStatement->fetchAll(PDO::FETCH_ASSOC));

                $incomingStatement = $db->query(
                    "SELECT it.transaction_id, it.transaction_date, it.reference_number, it.counterparty,
                            it.quantity, it.unit_measure, it.stock_before, it.stock_after, it.notes,
                            p.part_number, p.part_name, r.report_number
                     FROM inventory_transactions it
                     INNER JOIN parts p ON p.part_id = it.part_id
                     INNER JOIN report_records r ON r.report_id = it.report_id
                     WHERE it.movement_type = 'IN' AND it.reversed_at IS NULL
                     ORDER BY it.transaction_date DESC, it.transaction_id DESC
                     LIMIT 500"
                );
                $incoming = array_map(static function (array $row): array {
                    return [
                        'transactionId' => (int) $row['transaction_id'],
                        'tanggal' => (string) $row['transaction_date'],
                        'noBukti' => (string) $row['reference_number'],
                        'terimaDari' => (string) ($row['counterparty'] ?? ''),
                        'namaParts' => (string) $row['part_name'],
                        'partNumber' => (string) $row['part_number'],
                        'merk' => '',
                        'satuan' => (string) $row['unit_measure'],
                        'jml' => (int) $row['quantity'],
                        'unit' => '',
                        'noSpb' => (string) ($row['report_number'] ?? ''),
                        'saldoLalu' => (int) $row['stock_before'],
                        'saldoSekarang' => (int) $row['stock_after'],
                        'keterangan' => (string) ($row['notes'] ?? ''),
                        'source' => 'Laporan BHW-IN',
                    ];
                }, $incomingStatement->fetchAll(PDO::FETCH_ASSOC));

                echo json_encode([
                    'status' => 'success',
                    'data' => ['stock' => $stock, 'masuk' => $incoming],
                ]);
                break;
            } elseif ($_GET['type'] == 'parts') {
                $stmt = $db->query("SELECT * FROM parts");
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            } elseif ($_GET['type'] == 'costs') {
                $stmt = $db->query("SELECT * FROM cost_financial_monthly");
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            } elseif ($_GET['type'] == 'spb') {
                $scope = api_location_scope_clause('a', 'spb_location_id');
                $sql = "
                    SELECT pr.*, pri.id as item_id, pri.part_number, pri.description, pri.qty_requested, pri.status as item_status
                    FROM purchase_requests pr 
                    LEFT JOIN purchase_request_items pri ON pr.spb_id = pri.spb_id
                    INNER JOIN assets a ON a.asset_id = pr.asset_id
                ";
                if ($scope['sql'] !== '') $sql .= " WHERE " . $scope['sql'];
                $stmt = $db->prepare($sql);
                $stmt->execute($scope['params']);
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            }
        }
        echo json_encode(["status" => "error", "message" => "Type not specified or supported"]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['records'])) {
            echo json_encode(["status" => "error", "message" => "Invalid payload"]);
            break;
        }

        try {
            $db->beginTransaction();
            
            $stmtReq = $db->prepare("INSERT INTO purchase_requests (spb_id, wo_id, asset_id, requested_by, status) 
                                     VALUES (:spb, :wo, :asset, :user, :status)
                                     ON DUPLICATE KEY UPDATE status=VALUES(status)");
                                     
            $stmtItem = $db->prepare("INSERT INTO purchase_request_items (id, spb_id, part_number, description, qty_requested, status)
                                      VALUES (:id, :spb, :part, :desc, :qty, :status)
                                      ON DUPLICATE KEY UPDATE status=VALUES(status), qty_requested=VALUES(qty_requested)");

            foreach ($input['records'] as $r) {
                $assetId = (string) ($r['assetId'] ?? '');
                api_require_asset_access($db, $assetId);

                $existingRequest = $db->prepare('SELECT asset_id FROM purchase_requests WHERE spb_id = :spb_id LIMIT 1');
                $existingRequest->execute([':spb_id' => (string) ($r['spbId'] ?? '')]);
                $existingAssetId = $existingRequest->fetchColumn();
                if ($existingAssetId !== false) {
                    api_require_asset_access($db, (string) $existingAssetId);
                    if (!hash_equals((string) $existingAssetId, $assetId)) {
                        api_json_response(409, [
                            'status' => 'error',
                            'code' => 'OBJECT_ID_CONFLICT',
                            'message' => 'Nomor SPB sudah digunakan oleh objek lain.',
                        ]);
                    }
                }

                $existingItem = $db->prepare('SELECT pr.asset_id, pri.spb_id
                    FROM purchase_request_items pri
                    INNER JOIN purchase_requests pr ON pr.spb_id = pri.spb_id
                    WHERE pri.id = :item_id LIMIT 1');
                $existingItem->execute([':item_id' => (string) ($r['id'] ?? '')]);
                $itemOwner = $existingItem->fetch(PDO::FETCH_ASSOC);
                if ($itemOwner) {
                    api_require_asset_access($db, (string) $itemOwner['asset_id']);
                    if (!hash_equals((string) $itemOwner['spb_id'], (string) ($r['spbId'] ?? ''))) {
                        api_json_response(409, [
                            'status' => 'error',
                            'code' => 'OBJECT_ID_CONFLICT',
                            'message' => 'ID item sudah digunakan oleh SPB lain.',
                        ]);
                    }
                }
                // Insert Header (Ignore duplicates)
                $stmtReq->execute([
                    ':spb' => $r['spbId'],
                    ':wo' => $r['woId'] ?? '',
                    ':asset' => $r['assetId'] ?? '',
                    ':user' => (int) api_current_user()['id'],
                    ':status' => 'Submitted'
                ]);
                
                // Insert Item
                $stmtItem->execute([
                    ':id' => $r['id'],
                    ':spb' => $r['spbId'],
                    ':part' => $r['partNumber'],
                    ':desc' => $r['description'],
                    ':qty' => $r['qtyRequested'],
                    ':status' => $r['status'] ?? 'Menunggu Approval'
                ]);
            }
            
            $db->commit();
            echo json_encode(["status" => "success", "message" => "SPB saved successfully"]);
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
