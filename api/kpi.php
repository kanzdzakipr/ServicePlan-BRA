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
    $assetId = trim((string) ($_GET['asset_id'] ?? ''));
    if ($assetId !== '') {
        api_require_asset_access($db, $assetId);
    }

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
    if ($assetId !== '') $telematicsSql .= ' AND t.asset_id = :asset_id';
    $telematics = $db->prepare($telematicsSql);
    $telematics->execute(array_merge([':period' => $period], $locationScope['params'], $assetId !== '' ? [':asset_id' => $assetId] : []));
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
    if ($assetId !== '') $workOrderSql .= ' AND w.asset_id = :asset_id';
    $workOrders = $db->prepare($workOrderSql);
    $workOrders->execute(array_merge([
        ':period_start' => $periodStart,
        ':period_end' => $periodEnd,
    ], $locationScope['params'], $assetId !== '' ? [':asset_id' => $assetId] : []));
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

    // -------------------------------------------------------------------------
    // Unit-Level Breakdown & Reconciliation (Epic 4.1 Traceability)
    // -------------------------------------------------------------------------
    $unitSql = "
        SELECT 
            a.asset_id,
            COALESCE(a.model, a.equipment_type, 'Alat Berat') AS model,
            COALESCE(a.equipment_type, 'HE') AS equipment_type,
            COALESCE(l.location_name, 'Workshop Yard KM 12') AS location_name,
            COALESCE(t.operating_hours, 0.0) AS operating_hours,
            COALESCE(t.scheduled_hours, 0.0) AS scheduled_hours,
            COALESCE(w.repair_hours, 0.0) AS repair_hours,
            COALESCE(w.failure_count, 0) AS failure_count,
            COALESCE(w.repair_count, 0) AS repair_count,
            COALESCE(t.log_count, 0) AS telematics_rows,
            COALESCE(w.wo_count, 0) AS wo_rows
        FROM assets a
        LEFT JOIN locations l ON l.location_id = a.assigned_location_id
        LEFT JOIN (
            SELECT 
                asset_id,
                SUM(actual_working_hours) AS operating_hours,
                SUM(working_hours) AS scheduled_hours,
                COUNT(*) AS log_count
            FROM telematics_logs
            WHERE period_month = :period
            GROUP BY asset_id
        ) t ON t.asset_id = a.asset_id
        LEFT JOIN (
            SELECT 
                asset_id,
                SUM(CASE WHEN is_downtime = 1 AND status <> 'Cancelled' THEN downtime_minutes ELSE 0 END) / 60.0 AS repair_hours,
                SUM(CASE WHEN is_downtime = 1 AND status <> 'Cancelled' THEN 1 ELSE 0 END) AS failure_count,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS repair_count,
                COUNT(*) AS wo_count
            FROM work_orders
            WHERE COALESCE(trouble_at, reported_at) >= :p_start
              AND COALESCE(trouble_at, reported_at) < :p_end
            GROUP BY asset_id
        ) w ON w.asset_id = a.asset_id
        WHERE (t.scheduled_hours > 0 OR w.repair_hours > 0 OR a.is_active = 1)
    ";
    if ($locationScope['sql'] !== '') {
        $unitSql .= ' AND ' . $locationScope['sql'];
    }
    if ($assetId !== '') {
        $unitSql .= ' AND a.asset_id = :asset_id';
    }
    $unitSql .= ' ORDER BY a.asset_id ASC';

    $unitParams = array_merge(
        [':period' => $period, ':p_start' => $periodStart, ':p_end' => $periodEnd],
        $locationScope['params'],
        $assetId !== '' ? [':asset_id' => $assetId] : []
    );

    $unitStmt = $db->prepare($unitSql);
    $unitStmt->execute($unitParams);
    $rawUnits = $unitStmt->fetchAll(PDO::FETCH_ASSOC);

    $unitBreakdown = [];
    $sumUnitW = 0.0;
    $sumUnitS = 0.0;
    $sumUnitR = 0.0;
    $sumUnitT = 0.0;
    $sumUnitFailures = 0;
    $sumUnitRepairs = 0;

    foreach ($rawUnits as $u) {
        $uW = (float) $u['operating_hours'];
        $uT = (float) $u['scheduled_hours'];
        $uR = (float) $u['repair_hours'];
        $uS = max(0.0, $uT - $uW - $uR);
        $uFailures = (int) $u['failure_count'];
        $uRepairs = (int) $u['repair_count'];

        $sumUnitW += $uW;
        $sumUnitS += $uS;
        $sumUnitR += $uR;
        $sumUnitT += $uT;
        $sumUnitFailures += $uFailures;
        $sumUnitRepairs += $uRepairs;

        $uCalc = KpiCalculator::calculate([
            'operatingHours' => $uW,
            'standbyHours' => $uS,
            'repairHours' => $uR,
            'scheduledHours' => $uT,
            'failureCount' => $uFailures,
            'repairCount' => $uRepairs,
        ]);

        $unitBreakdown[] = [
            'assetId' => $u['asset_id'],
            'model' => $u['model'],
            'equipmentType' => $u['equipment_type'],
            'locationName' => $u['location_name'],
            'variables' => [
                'W' => round($uW, 2),
                'S' => round($uS, 2),
                'R' => round($uR, 2),
                'T' => round($uT, 2),
                'failures' => $uFailures,
                'repairs' => $uRepairs,
            ],
            'kpis' => $uCalc['kpis'],
            'source' => [
                'telematicsRows' => (int) $u['telematics_rows'],
                'workOrderRows' => (int) $u['wo_rows'],
                'reference' => 'LHO / Telematics + Work Order',
            ],
        ];
    }

    $reconciliation = [
        'isReconciled' => abs($sumUnitT - $scheduledHours) <= 0.01 && abs($sumUnitR - $repairHours) <= 0.01,
        'differenceT' => round($sumUnitT - $scheduledHours, 2),
        'differenceR' => round($sumUnitR - $repairHours, 2),
        'differenceW' => round($sumUnitW - $operatingHours, 2),
        'sumUnits' => [
            'W' => round($sumUnitW, 2),
            'S' => round($sumUnitS, 2),
            'R' => round($sumUnitR, 2),
            'T' => round($sumUnitT, 2),
            'failures' => $sumUnitFailures,
            'repairs' => $sumUnitRepairs,
        ],
        'aggregateCard' => [
            'W' => round($operatingHours, 2),
            'S' => round($standbyHours, 2),
            'R' => round($repairHours, 2),
            'T' => round($scheduledHours, 2),
            'failures' => (int) ($repairs['failure_count'] ?? 0),
            'repairs' => (int) ($repairs['repair_count'] ?? 0),
        ]
    ];

    api_json_response(200, [
        'status' => 'success',
        'data' => array_merge($result, [
            'unitBreakdown' => $unitBreakdown,
            'reconciliation' => $reconciliation,
            'period' => [
                'label' => $period,
                'startInclusive' => $periodStart,
                'endExclusive' => $periodEnd,
            ],
            'assetId' => $assetId !== '' ? $assetId : null,
            'formulaCatalog' => KpiCalculator::formulaCatalog(),
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
