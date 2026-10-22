<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';

$db = Database::getInstance();
AuthMiddleware::checkAccess('asset', 'R');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    api_json_response(405, [
        'status' => 'error',
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'Endpoint properties unit hanya menerima GET.',
    ]);
}

$assetId = trim((string) ($_GET['asset_id'] ?? ''));
if ($assetId === '' || mb_strlen($assetId, 'UTF-8') > 100) {
    api_json_response(422, [
        'status' => 'error',
        'code' => 'INVALID_ASSET_ID',
        'message' => 'ID aset wajib diisi.',
    ]);
}

$scope = api_location_scope_clause('a', 'unit_properties_location_id');
$assetSql = "SELECT a.*, l.location_name
             FROM assets a
             LEFT JOIN locations l ON l.location_id = a.current_location_id
             WHERE a.asset_id = :asset_id";
if ($scope['sql'] !== '') {
    $assetSql .= ' AND ' . $scope['sql'];
}
$assetStmt = $db->prepare($assetSql);
$assetStmt->execute(array_merge([':asset_id' => $assetId], $scope['params']));
$asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    api_json_response(404, [
        'status' => 'error',
        'code' => 'ASSET_NOT_FOUND',
        'message' => 'Aset tidak ditemukan atau berada di luar cakupan lokasi Anda.',
    ]);
}

$suppressedFields = [];
$generatedModels = [
    '22/01/2026',
    'Bulldozer Caterpillar D6G / Shantui SD16',
    'DOUBLE CABIN',
    'DUMP TRUCK',
    'Dump Truck Hino FM260JD / Mercedes Axor',
    'Excavator Komatsu PC200-10M0 / CAT 320',
    'FUEL TANK',
    'MOBILE LABORATORY',
    'Motor Grader XCMG GR135 MAX',
    'PRIME MOVER',
    'Reclaimer / Cement Spreader XCMG XLZ2303',
    'Service Truck Crane',
    'Toyota Hilux Double Cabin 4x4',
    'Trado Heavy Hauler Mitsubishi FV 419 P / Hino',
    'TRUCK MOUNTED CRANE',
    'Truck Mounted Crane XCMG B 9435 XFY',
    'Vibro Compactor Bomag BW211D-40SL / Sakai',
    'WATER TANK',
    'WATER TANK TRUCK',
    'Water Truck Hino Dutro 130 HD / Isuzu',
];
if (in_array(trim((string) ($asset['make_model'] ?? '')), $generatedModels, true)) {
    $asset['make_model'] = null;
    $suppressedFields[] = 'make_model';
}

$seededGpsTimestamp = '2026-08-05 06:40:37';
if (($asset['created_at'] ?? null) === $seededGpsTimestamp
    && ($asset['gps_updated_at'] ?? null) === $seededGpsTimestamp) {
    $asset['last_latitude'] = null;
    $asset['last_longitude'] = null;
    $asset['gps_updated_at'] = null;
    $suppressedFields[] = 'coordinates';
}

$fetchAll = static function (PDO $db, string $sql, array $params): array {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};

$params = [':asset_id' => $assetId];

$pmPlans = $fetchAll($db,
    "SELECT pm_plan_id, asset_id, interval_hm, current_smr, last_service_hm,
            last_service_date, target_due_hm, variance_hm, status,
            warranty_status, planner_note
     FROM pm_plans
     WHERE asset_id = :asset_id
     ORDER BY target_due_hm ASC, pm_plan_id ASC",
    $params
);

$workOrders = $fetchAll($db,
    "SELECT w.wo_id, w.asset_id, w.maintenance_type, w.issue_description,
            w.assigned_mechanic, w.downtime_formatted, w.downtime_minutes,
            w.status, w.priority, w.root_cause, w.repair_action,
            w.current_hm_km, w.trouble_at, w.reported_at,
            w.repair_started_at, w.closed_at, w.is_forced_operation,
            w.mitigation_action, w.before_photo_url, w.after_photo_url,
            (SELECT SUM(c.amount)
             FROM asset_cost_transactions c
             WHERE c.wo_id = w.wo_id AND c.reversed_at IS NULL) AS total_cost
     FROM work_orders w
     WHERE w.asset_id = :asset_id
     ORDER BY COALESCE(w.trouble_at, w.reported_at) DESC, w.wo_id DESC
     LIMIT 100",
    $params
);

$partUsage = $fetchAll($db,
    "SELECT it.transaction_id, it.transaction_date, it.reference_number,
            it.wo_id, it.quantity, it.unit_measure, it.notes,
            p.part_number, p.part_name, p.category
     FROM inventory_transactions it
     INNER JOIN parts p ON p.part_id = it.part_id
     WHERE it.asset_id = :asset_id
       AND it.movement_type = 'OUT'
       AND it.reversed_at IS NULL
     ORDER BY it.transaction_date DESC, it.transaction_id DESC
     LIMIT 100",
    $params
);

$allTires = $fetchAll($db,
    "SELECT tire_inspection_id, asset_id, tire_serial_no, tire_position,
            tread_depth_mm, air_pressure_psi, condition_color, inspected_at,
            physical_condition, wear_pattern, recommended_action,
            status_level, notes
     FROM tire_inspections
     WHERE asset_id = :asset_id
       AND NOT (
           COALESCE(tire_serial_no, '') REGEXP '^(BS|MI|GY|GT|MA)-1200R24-[0-9]+$'
           AND inspected_by IS NULL
           AND (notes IS NULL OR TRIM(notes) = '')
       )
     ORDER BY inspected_at DESC, tire_inspection_id DESC",
    $params
);
$latestTires = [];
foreach ($allTires as $tire) {
    $position = trim((string) ($tire['tire_position'] ?? ''));
    if ($position !== '' && !array_key_exists($position, $latestTires)) {
        $latestTires[$position] = $tire;
    }
}
$latestTires = array_values($latestTires);

$inspections = $fetchAll($db,
    "SELECT inspection_id, asset_id, inspection_date, current_hm_km,
            overall_result, findings_summary, payload_json, created_wo_id
     FROM inspections
     WHERE asset_id = :asset_id
     ORDER BY inspection_date DESC, inspection_id DESC
     LIMIT 100",
    $params
);
foreach ($inspections as &$inspection) {
    $decoded = json_decode((string) ($inspection['payload_json'] ?? ''), true);
    $inspection['payload'] = is_array($decoded) ? $decoded : null;
    unset($inspection['payload_json']);
}
unset($inspection);

$accidents = $fetchAll($db,
    "SELECT ac.accident_id, ac.asset_id, ac.report_date, ac.incident_datetime,
            ac.operator_name, ac.chronology, ac.severity, ac.physical_damage,
            ac.estimated_repair_cost, ac.total_financial_loss,
            ac.corrective_action, ac.preventive_action, ac.status,
            l.location_name
     FROM accidents ac
     LEFT JOIN locations l ON l.location_id = ac.location_id
     WHERE ac.asset_id = :asset_id
     ORDER BY ac.incident_datetime DESC, ac.accident_id DESC
     LIMIT 100",
    $params
);

$gpsLogs = $fetchAll($db,
    "SELECT gps_log_id, asset_id, latitude, longitude, speed_kmh,
            heading_deg, ignition_status, recorded_at
     FROM telematics_gps_logs
     WHERE asset_id = :asset_id
     ORDER BY recorded_at DESC, gps_log_id DESC
     LIMIT 50",
    $params
);

$movements = $fetchAll($db,
    "SELECT am.movement_id, am.bast_number, am.movement_date,
            origin.location_name AS from_location,
            destination.location_name AS to_location
     FROM asset_movements am
     LEFT JOIN locations origin ON origin.location_id = am.from_location_id
     LEFT JOIN locations destination ON destination.location_id = am.to_location_id
     WHERE am.asset_id = :asset_id
     ORDER BY am.movement_date DESC, am.movement_id DESC
     LIMIT 50",
    $params
);

$telemetryRows = $fetchAll($db,
    "SELECT log_id, asset_id, smr_hours, working_days, working_hours,
            actual_working_hours, actual_working_ratio, e_mode_ratio,
            travel_ratio, digging_ratio, hoist_ratio, fuel_consumed_liters,
            fuel_lph, idling_ratio, last_comm_date, period_month
     FROM telematics_logs
     WHERE asset_id = :asset_id
     ORDER BY last_comm_date DESC, log_id DESC
     LIMIT 12",
    $params
);

$repairDurations = [];
$failureMeters = [];
foreach ($workOrders as $workOrder) {
    if (!empty($workOrder['repair_started_at']) && !empty($workOrder['closed_at'])) {
        $start = strtotime((string) $workOrder['repair_started_at']);
        $end = strtotime((string) $workOrder['closed_at']);
        if ($start !== false && $end !== false && $end >= $start) {
            $repairDurations[] = ($end - $start) / 3600;
        }
    }
    if (($workOrder['maintenance_type'] ?? '') === 'Corrective'
        && $workOrder['current_hm_km'] !== null
        && is_numeric($workOrder['current_hm_km'])) {
        $failureMeters[] = (float) $workOrder['current_hm_km'];
    }
}

$mttrHours = $repairDurations !== []
    ? array_sum($repairDurations) / count($repairDurations)
    : null;
$mtbfHours = null;
if (count($failureMeters) >= 2) {
    sort($failureMeters, SORT_NUMERIC);
    $intervals = [];
    for ($i = 1, $count = count($failureMeters); $i < $count; $i++) {
        $delta = $failureMeters[$i] - $failureMeters[$i - 1];
        if ($delta > 0) $intervals[] = $delta;
    }
    if ($intervals !== []) {
        $mtbfHours = array_sum($intervals) / count($intervals);
    }
}

$asset['gallery_photos'] = $fetchAll($db,
    "SELECT photo_id, photo_title, photo_angle, photo_url, uploaded_at
     FROM asset_gallery_photos
     WHERE asset_id = :asset_id
     ORDER BY photo_id DESC",
    $params
);

$asset['operator_history'] = $fetchAll($db,
    "SELECT history_id, operator_name, start_date, end_date, shift_type, handover_notes
     FROM asset_operator_history
     WHERE asset_id = :asset_id
     ORDER BY start_date DESC, history_id DESC",
    $params
);

$asset['service_history'] = $fetchAll($db,
    "SELECT service_log_id, service_sequence, service_date, service_hm_km, service_type, mechanic_pic, jo_report_number, notes
     FROM asset_service_history
     WHERE asset_id = :asset_id
     ORDER BY service_date DESC, service_log_id DESC",
    $params
);

api_json_response(200, [
    'status' => 'success',
    'data' => [
        'asset' => $asset,
        'pm_plans' => $pmPlans,
        'work_orders' => $workOrders,
        'part_usage' => $partUsage,
        'tire_inspections' => $latestTires,
        'inspections' => $inspections,
        'accidents' => $accidents,
        'gps_logs' => $gpsLogs,
        'movements' => $movements,
        'telemetry_periods' => $telemetryRows,
        'oil_analysis' => [],
        'canbus' => null,
        'calculated' => [
            'mttr_hours' => $mttrHours,
            'mtbf_hours' => $mtbfHours,
        ],
        'availability' => [
            'oil_analysis' => 'Data belum tersedia',
            'canbus' => 'Data belum tersedia',
        ],
        'data_quality' => [
            'suppressed_generated_fields' => $suppressedFields,
        ],
    ],
]);
