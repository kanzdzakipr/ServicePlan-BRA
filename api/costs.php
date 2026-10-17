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

            $scopeStmt = $db->prepare('SELECT asset_id, wo_id FROM asset_cost_transactions WHERE transaction_id = :id LIMIT 1');
            $scopeStmt->execute([':id' => $transactionId]);
            $scopeRow = $scopeStmt->fetch(PDO::FETCH_ASSOC);
            if (!$scopeRow) api_json_response(404, ['status' => 'error', 'code' => 'OBJECT_NOT_FOUND', 'message' => 'Objek tidak ditemukan atau tidak dapat diakses.']);
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
