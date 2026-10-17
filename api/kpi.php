<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/KpiCalculator.php';

$db = Database::getInstance();

if (!defined('API_SKIP_ROUTING')) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        api_json_response(405, [
            'status' => 'error',
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Endpoint KPI hanya menerima metode GET.',
        ]);
    }

    $period = trim((string) ($_GET['period'] ?? 'Januari 2026'));
    if ($period === '' || mb_strlen($period) > 40) {
        api_json_response(422, [
            'status' => 'error',
            'code' => 'INVALID_PERIOD',
            'message' => 'Periode KPI wajib diisi dan maksimal 40 karakter.',
        ]);
    }

    try {
        [$periodStart, $periodEnd] = KpiCalculator::periodBoundaries($period);
    } catch (InvalidArgumentException $error) {
        api_json_response(422, [
            'status' => 'error',
            'code' => 'INVALID_PERIOD',
            'message' => $error->getMessage(),
        ]);
    }

    $locationScope = api_location_scope_clause('a', 'kpi_location_id');

    $telematicsSql = "SELECT
            COUNT(*) AS source_rows,
            COALESCE(SUM(t.actual_working_hours), 0) AS operating_hours,
            COALESCE(SUM(t.working_hours), 0) AS scheduled_hours
        FROM telematics_logs t
        LEFT JOIN assets a ON a.asset_id = t.asset_id
        WHERE t.period_month = :period";
    if ($locationScope['sql'] !== '') {
        $telematicsSql .= ' AND ' . $locationScope['sql'];
    }
    $telematics = $db->prepare($telematicsSql);
    $telematics->execute(array_merge([':period' => $period], $locationScope['params']));
    $hours = $telematics->fetch(PDO::FETCH_ASSOC) ?: [];

    $workOrderSql = "SELECT
            COUNT(*) AS source_rows,
            COALESCE(SUM(CASE
                WHEN w.is_downtime = 1 AND w.status <> 'Cancelled' THEN w.downtime_minutes
                ELSE 0
            END), 0) / 60 AS repair_hours,
            COALESCE(SUM(CASE
                WHEN w.is_downtime = 1 AND w.status <> 'Cancelled' THEN 1
                ELSE 0
            END), 0) AS failure_count,
            COALESCE(SUM(CASE
                WHEN w.is_downtime = 1 AND w.status = 'Closed' THEN 1
                ELSE 0
            END), 0) AS repair_count
        FROM work_orders w
        INNER JOIN assets a ON a.asset_id = w.asset_id
        WHERE COALESCE(w.trouble_at, w.reported_at) >= :period_start
          AND COALESCE(w.trouble_at, w.reported_at) < :period_end";
    if ($locationScope['sql'] !== '') {
        $workOrderSql .= ' AND ' . $locationScope['sql'];
    }
    $workOrders = $db->prepare($workOrderSql);
    $workOrders->execute(array_merge([
        ':period_start' => $periodStart,
        ':period_end' => $periodEnd,
    ], $locationScope['params']));
    $repairs = $workOrders->fetch(PDO::FETCH_ASSOC) ?: [];

    $operatingHours = (float) ($hours['operating_hours'] ?? 0);
    $scheduledHours = (float) ($hours['scheduled_hours'] ?? 0);
    $repairHours = (float) ($repairs['repair_hours'] ?? 0);
    $standbyHours = max(0.0, $scheduledHours - $operatingHours - $repairHours);

    $result = KpiCalculator::calculate([
        'operatingHours' => $operatingHours,
        'standbyHours' => $standbyHours,
        'repairHours' => $repairHours,
        'scheduledHours' => $scheduledHours,
        'failureCount' => (int) ($repairs['failure_count'] ?? 0),
        'repairCount' => (int) ($repairs['repair_count'] ?? 0),
    ]);

    if ($repairHours > max(0.0, $scheduledHours - $operatingHours)) {
        $result['dataQuality']['isConsistent'] = false;
        $result['dataQuality']['warnings'][] =
            'Jam breakdown WO melampaui jam non-operasi telematika; periksa periode dan kelengkapan sumber data.';
    }

    api_json_response(200, [
        'status' => 'success',
        'data' => array_merge($result, [
            'period' => [
                'label' => $period,
                'startInclusive' => $periodStart,
                'endExclusive' => $periodEnd,
            ],
            'sources' => [
                'telematics' => [
                    'table' => 'telematics_logs',
                    'rowCount' => (int) ($hours['source_rows'] ?? 0),
                    'fields' => ['actual_working_hours', 'working_hours'],
                ],
                'workOrders' => [
                    'table' => 'work_orders',
                    'rowCount' => (int) ($repairs['source_rows'] ?? 0),
                    'fields' => ['downtime_minutes', 'is_downtime', 'status', 'trouble_at', 'reported_at'],
                ],
            ],
        ]),
    ]);
}

/** @return array{0:string,1:string} */
function kpi_period_boundaries(string $period): array
{
    return KpiCalculator::periodBoundaries($period);
}
