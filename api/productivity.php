<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$db = Database::getInstance();
ReportIntegration::ensureTables($db);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json_response(405, [
        'status' => 'error',
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'Metode tidak didukung.',
    ]);
}

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
$sql .= ' ORDER BY o.operation_date DESC, o.operation_log_id DESC LIMIT 500';

$statement = $db->prepare($sql);
$statement->execute($scope['params']);
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
    'data' => [
        'operations' => $rows,
        'summary' => $summary,
    ],
]);
