<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';
require_once dirname(__DIR__) . '/core/ProductivityCalculator.php';

$db = Database::getInstance();
ReportIntegration::ensureTables($db);

if (!defined('API_SKIP_ROUTING')) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        api_json_response(405, [
            'status' => 'error',
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Metode tidak didukung.',
        ]);
    }

    $period = isset($_GET['period']) && trim((string)$_GET['period']) !== ''
        ? trim((string)$_GET['period'])
        : 'Januari 2026';
    try {
        if ($period !== 'all') KpiCalculator::periodBoundaries($period);
    } catch (InvalidArgumentException $error) {
        api_json_response(422, ['status' => 'error', 'code' => 'INVALID_PERIOD', 'message' => $error->getMessage()]);
    }
    $assetId = trim((string) ($_GET['asset_id'] ?? ''));
    if ($assetId !== '') api_require_asset_access($db, $assetId);
    $category = trim((string) ($_GET['category'] ?? ''));
    if ($category !== '' && (mb_strlen($category) > 50 || !preg_match('/^[\p{L}\p{N} .\/-]+$/u', $category))) {
        api_json_response(422, ['status' => 'error', 'code' => 'INVALID_CATEGORY', 'message' => 'Kategori unit tidak valid.']);
    }

    $locationId = null;
    if (!api_has_global_location_scope()) {
        $locationId = api_current_location_id();
        if ($locationId === null) {
            // Deny-all: user has restricted scope but no location assigned
            $productivityData = ProductivityCalculator::calculate($db, 999999, $period, $assetId ?: null, $category ?: null);
            api_json_response(200, [
                'status' => 'success',
                'data' => array_merge($productivityData, [
                    'operations' => [],
                    'summary' => [
                        'recordCount' => 0,
                        'totalWorkHours' => 0.0,
                        'totalHmOperation' => 0.0,
                        'totalFuelLiters' => 0.0,
                        'averageFuelLph' => 0.0,
                    ],
                ]),
            ]);
        }
    }

    $productivityData = ProductivityCalculator::calculate($db, $locationId, $period, $assetId ?: null, $category ?: null);

    // Fetch manual LHO report operations
    $scope = api_location_scope_clause('a', 'productivity_location_id');
    $sql = "SELECT
                o.operation_log_id,
                o.operation_date,
                o.asset_id,
                a.asset_code,
                a.category,
                a.make_model,
                o.operator_name,
                o.site,
                TIME_FORMAT(o.start_time, '%H:%i') AS start_time,
                TIME_FORMAT(o.end_time, '%H:%i') AS end_time,
                o.work_hours,
                o.hm_start,
                o.hm_end,
                o.hm_operation,
                o.fuel_liters,
                o.weather,
                o.verification_status,
                o.notes,
                r.report_number
            FROM report_operation_logs o
            INNER JOIN assets a ON a.asset_id = o.asset_id
            INNER JOIN report_records r ON r.report_id = o.report_id
            WHERE o.reversed_at IS NULL";
    if ($scope['sql'] !== '') {
        $sql .= ' AND ' . $scope['sql'];
    }
    $operationParams = $scope['params'];
    if ($assetId !== '') { $sql .= ' AND o.asset_id = :operation_asset_id'; $operationParams[':operation_asset_id'] = $assetId; }
    if ($category !== '') { $sql .= ' AND a.category = :operation_category'; $operationParams[':operation_category'] = $category; }
    if ($period !== 'all') {
        [$periodStart, $periodEnd] = KpiCalculator::periodBoundaries($period);
        $sql .= ' AND o.operation_date >= :operation_start AND o.operation_date < :operation_end';
        $operationParams[':operation_start'] = substr($periodStart, 0, 10);
        $operationParams[':operation_end'] = substr($periodEnd, 0, 10);
    }
    // Summary must cover the same rows shown in detail; a silent LIMIT breaks reconciliation.
    $sql .= ' ORDER BY o.operation_date DESC, o.operation_log_id DESC';

    $statement = $db->prepare($sql);
    $statement->execute($operationParams);
    $rows = array_map(static function (array $row): array {
        return [
            'operationLogId' => (int) $row['operation_log_id'],
            'date' => (string) $row['operation_date'],
            'assetId' => (string) $row['asset_id'],
            'assetCode' => (string) $row['asset_code'],
            'category' => (string) $row['category'],
            'model' => (string) ($row['make_model'] ?? ''),
            'operator' => (string) $row['operator_name'],
            'site' => (string) $row['site'],
            'startTime' => (string) $row['start_time'],
            'endTime' => (string) $row['end_time'],
            'workHours' => (float) $row['work_hours'],
            'hmStart' => (float) $row['hm_start'],
            'hmEnd' => (float) $row['hm_end'],
            'hmOperation' => (float) $row['hm_operation'],
            'fuelLiters' => (float) $row['fuel_liters'],
            'fuelLph' => (float) $row['hm_operation'] > 0
                ? round((float) $row['fuel_liters'] / (float) $row['hm_operation'], 2)
                : 0.0,
            'weather' => (string) ($row['weather'] ?? ''),
            'status' => (string) $row['verification_status'],
            'notes' => (string) ($row['notes'] ?? ''),
            'reportNumber' => (string) ($row['report_number'] ?? ''),
            'source' => 'Laporan LHO',
        ];
    }, $statement->fetchAll(PDO::FETCH_ASSOC));

    $summary = [
        'recordCount' => count($rows),
        'totalWorkHours' => round(array_sum(array_column($rows, 'workHours')), 2),
        'totalHmOperation' => round(array_sum(array_column($rows, 'hmOperation')), 2),
        'totalFuelLiters' => round(array_sum(array_column($rows, 'fuelLiters')), 2),
    ];
    $summary['averageFuelLph'] = $summary['totalHmOperation'] > 0
        ? round($summary['totalFuelLiters'] / $summary['totalHmOperation'], 2)
        : 0.0;

    api_json_response(200, [
        'status' => 'success',
        'data' => array_merge($productivityData, [
            'operations' => $rows,
            'summary' => $summary,
        ]),
    ]);
}
