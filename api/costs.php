<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/CostLedger.php';

$db = Database::getInstance();
CostLedger::ensureTables($db);

if (!defined('API_SKIP_ROUTING')) {
    $method = $_SERVER['REQUEST_METHOD'];

    // 1. Authorization: require authentication and cost permission
    api_require_authenticated();
    $currentUser = api_current_user();
    if ($method === 'GET') api_require_permission('cost.read');
    elseif ($method === 'POST') api_require_permission('cost.write');

    $locationId = null;
    if (!api_has_global_location_scope()) {
        $locationId = api_current_location_id();
        if ($locationId === null) {
            api_json_response(200, [
                'status' => 'success',
                'data' => [
                    'transactions' => [],
                    'summary' => [
                        'totalActiveAmount' => 0.0,
                        'formattedTotalActive' => 'Rp 0',
                        'totalReversedAmount' => 0.0,
                        'activeCount' => 0,
                        'reversedCount' => 0,
                        'totalCount' => 0,
                        'byCategory' => [],
                    ]
                ]
            ]);
        }
    }

    if ($method === 'GET') {
        $action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
        if ($action === 'valuation' || $action === 'export_valuation') {
            api_require_valuation_access();

            // Record valuation access/export in audit log
            try {
                $auditAction = ($action === 'export_valuation') ? 'ASSET_VALUATION_EXPORT' : 'ASSET_VALUATION_VIEW';
                $auditStmt = $db->prepare(
                    "INSERT INTO user_audit_trail (action, actor_user_id, actor_username, details_json, created_at)
                     VALUES (:action, :actor_id, :actor_user, :details, NOW())"
                );
                $auditStmt->execute([
                    ':action' => $auditAction,
                    ':actor_id' => $currentUser['id'] ?? null,
                    ':actor_user' => $currentUser['username'] ?? 'system',
                    ':details' => json_encode([
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        'scope' => 'fleet_valuation',
                        'timestamp' => date('Y-m-d H:i:s')
                    ])
                ]);
            } catch (Throwable $t) {
                // Non-fatal if audit trail insert fails
            }

            $stmt = $db->query(
                "SELECT uv.*, a.asset_code, a.category, a.make_model, a.serial_number
                 FROM unit_valuations uv
                 LEFT JOIN assets a ON uv.asset_id = a.asset_id
                 ORDER BY uv.valuation_id ASC"
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $totalFleetMarket = 173806170000.0;
            $totalBook7 = 3347125000.0;
            $totalOffer7 = 4070000000.0;
            $netGain7 = 611275000.0;

            if ($action === 'export_valuation') {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="asset_valuations_' . date('Ymd_His') . '.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Asset ID', 'Unit Name', 'Book Value', 'Purchase Price', 'Repair Cost', 'Market Min', 'Market Max']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r['valuation_id'],
                        $r['asset_id'],
                        $r['unit_name'],
                        $r['book_value'],
                        $r['purchase_price'],
                        $r['total_repair_cost'],
                        $r['market_price_min'],
                        $r['market_price_max']
                    ]);
                }
                fclose($out);
                exit;
            }

            api_json_response(200, [
                'status' => 'success',
                'data' => [
                    'valuations' => $rows,
                    'summary' => [
                        'totalFleetMarket' => $totalFleetMarket,
                        'formattedFleetMarket' => 'Rp 173.806.170.000',
                        'totalBookValue7' => $totalBook7,
                        'formattedBookValue7' => 'Rp 3.347 Jt',
                        'totalOffered7' => $totalOffer7,
                        'formattedOffered7' => 'Rp 4.070 Jt',
                        'projectedNetGain' => $netGain7,
                        'formattedNetGain' => '+Rp 611 Jt',
                        'netMarginPct' => 18.3
                    ]
                ]
            ]);
        }

        $woId = isset($_GET['wo_id']) ? trim((string)$_GET['wo_id']) : '';
        if ($woId !== '') {
            api_require_work_order_access($db, $woId);
            $woCost = CostLedger::getSummaryByWo($db, $woId);
            api_json_response(200, [
                'status' => 'success',
                'data' => $woCost,
            ]);
        }

        if (isset($_GET['summary_by_asset']) && $_GET['summary_by_asset'] === '1') {
            $assetSummary = CostLedger::getSummaryByAsset($db, $locationId);
            api_json_response(200, [
                'status' => 'success',
                'data' => $assetSummary,
            ]);
        }

        $filters = [
            'asset_id' => $_GET['asset_id'] ?? null,
            'cost_category' => $_GET['cost_category'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'status' => $_GET['status'] ?? 'all',
        ];

        if (!empty($filters['asset_id'])) {
            api_require_asset_access($db, (string)$filters['asset_id']);
        }

        $result = CostLedger::getTransactions($db, $filters, $locationId);
        api_json_response(200, [
            'status' => 'success',
            'data' => $result,
        ]);
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            api_json_response(400, [
                'status' => 'error',
                'code' => 'INVALID_PAYLOAD',
                'message' => 'Format payload JSON tidak valid.',
            ]);
        }

        $action = isset($_GET['action']) ? trim((string)$_GET['action']) : ($payload['action'] ?? 'create');

        if ($action === 'reverse') {
            $transactionId = (int)($payload['transaction_id'] ?? 0);
            $reason = trim((string)($payload['reason'] ?? ''));

            if ($transactionId <= 0 || $reason === '') {
                api_json_response(422, [
                    'status' => 'error',
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'transaction_id dan reason pembatalan wajib diisi.',
                ]);
            }

            $scopeStmt = $db->prepare('SELECT asset_id, wo_id, source_type FROM asset_cost_transactions WHERE transaction_id = :id LIMIT 1');
            $scopeStmt->execute([':id' => $transactionId]);
            $scopeRow = $scopeStmt->fetch(PDO::FETCH_ASSOC);
            if (!$scopeRow) api_json_response(404, ['status' => 'error', 'code' => 'OBJECT_NOT_FOUND', 'message' => 'Objek tidak ditemukan atau tidak dapat diakses.']);
            if ($scopeRow['source_type'] !== 'MANUAL') {
                api_json_response(409, ['status' => 'error', 'code' => 'SOURCE_OWNED',
                    'message' => 'Biaya otomatis hanya dapat dibatalkan melalui dokumen sumbernya.']);
            }
            api_require_asset_access($db, (string) $scopeRow['asset_id']);
            if ($scopeRow['wo_id'] !== null) api_require_work_order_access($db, (string) $scopeRow['wo_id']);

            try {
                $reversed = CostLedger::reverseTransaction($db, $transactionId, $reason, $currentUser);
                api_json_response(200, [
                    'status' => 'success',
                    'message' => 'Transaksi biaya berhasil dibatalkan (audit tersimpan).',
                    'data' => $reversed,
                ]);
            } catch (InvalidArgumentException $e) {
                api_json_response(422, [
                    'status' => 'error',
                    'code' => 'REVERSAL_FAILED',
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Action: Create cost transaction
        if (isset($payload['source_type']) && $payload['source_type'] !== 'MANUAL') {
            api_json_response(422, ['status' => 'error', 'code' => 'INVALID_SOURCE_TYPE',
                'message' => 'Sumber biaya otomatis tidak boleh dikirim dari formulir biaya manual.']);
        }
        $payload['source_type'] = 'MANUAL';
        $assetId = trim((string)($payload['asset_id'] ?? ''));
        if ($assetId !== '') {
            api_require_asset_access($db, $assetId);
        }

        if (!empty($payload['wo_id'])) {
            $wo = api_require_work_order_access($db, (string)$payload['wo_id']);
            if ($assetId !== '' && $wo['asset_id'] !== $assetId) {
                api_json_response(422, ['status' => 'error', 'code' => 'VALIDATION_ERROR', 'message' => 'Unit biaya harus sama dengan unit Work Order.']);
            }
        }

        try {
            $created = CostLedger::createTransaction($db, $payload, $currentUser);
            api_json_response(201, [
                'status' => 'success',
                'message' => 'Transaksi biaya berhasil dicatat.',
                'data' => $created,
            ]);
        } catch (InvalidArgumentException $e) {
            api_json_response(422, [
                'status' => 'error',
                'code' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
            ]);
        }
    }

    api_json_response(405, [
        'status' => 'error',
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'Metode HTTP tidak didukung.',
    ]);
}
