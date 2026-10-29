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
$assetSql = "SELECT a.*, l.location_name, l.location_type, l.region AS location_region,
                    l.latitude AS site_latitude, l.longitude AS site_longitude
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

$masterSiteCoords = [
    'Yard KM 12 Duri' => ['lat' => 1.2854600, 'lng' => 101.2185700, 'region' => 'Duri, Riau', 'type' => 'Yard'],
    'Yard Duri' => ['lat' => 1.2854300, 'lng' => 101.2185400, 'region' => 'Duri, Riau', 'type' => 'Yard'],
    'Workshop Bay KM 12' => ['lat' => 1.2789000, 'lng' => 101.2112000, 'region' => 'Duri, Riau', 'type' => 'Workshop'],
    'Borrow Pit Harapan Baru' => ['lat' => 1.3124500, 'lng' => 101.2451200, 'region' => 'Duri, Riau', 'type' => 'Borrow Pit'],
    'Site Alpha Duri' => ['lat' => 1.2991000, 'lng' => 101.2311000, 'region' => 'Duri, Riau', 'type' => 'Site Area'],
    'Minas Field Project' => ['lat' => 0.7321400, 'lng' => 101.4421100, 'region' => 'Minas, Riau', 'type' => 'Site Area'],
    'Site Celcin' => ['lat' => 1.3012000, 'lng' => 101.1984000, 'region' => 'Duri, Riau', 'type' => 'Borrow Pit'],
    'Site Bangko' => ['lat' => 1.7321000, 'lng' => 100.8214000, 'region' => 'Bangko, Riau', 'type' => 'Site Area'],
    'Site NDD' => ['lat' => 1.3054000, 'lng' => 101.2215000, 'region' => 'Duri, Riau', 'type' => 'Site Area'],
    'Yard Prabumulih' => ['lat' => -3.4354200, 'lng' => 104.2384500, 'region' => 'Prabumulih, Sumsel', 'type' => 'Yard'],
    'PKB PEKANBARU Branch' => ['lat' => 0.5070680, 'lng' => 101.4477790, 'region' => 'Pekanbaru, Riau', 'type' => 'Branch'],
    'Head Office Pekanbaru' => ['lat' => 0.5070680, 'lng' => 101.4477790, 'region' => 'Pekanbaru, Riau', 'type' => 'Branch'],
    'PLB PALEMBANG Branch' => ['lat' => -2.9760740, 'lng' => 104.7754310, 'region' => 'Palembang, Sumsel', 'type' => 'Branch'],
    'Site Sunter Area Stadium' => ['lat' => -6.1451200, 'lng' => 106.8741500, 'region' => 'Jakarta / Sunter', 'type' => 'Site Area'],
    'MDN MEDAN Branch' => ['lat' => 3.5951960, 'lng' => 98.6722230, 'region' => 'Medan, Sumut', 'type' => 'Branch'],
    'SMG SEMARANG Rep Office' => ['lat' => -6.9666670, 'lng' => 110.4166640, 'region' => 'Semarang, Jateng', 'type' => 'Branch'],
];

$locName = trim((string) ($asset['location_name'] ?? $asset['raw_location_notes'] ?? ''));
if ($locName === '') {
    $locName = 'Yard KM 12 Duri';
    $asset['location_name'] = $locName;
}

if (($asset['site_latitude'] === null || $asset['site_longitude'] === null) && $locName !== '') {
    if (isset($masterSiteCoords[$locName])) {
        $asset['site_latitude'] = $masterSiteCoords[$locName]['lat'];
        $asset['site_longitude'] = $masterSiteCoords[$locName]['lng'];
        $asset['location_type'] = $asset['location_type'] ?? $masterSiteCoords[$locName]['type'];
        $asset['location_region'] = $asset['location_region'] ?? $masterSiteCoords[$locName]['region'];
    } else {
        $lower = strtolower($locName);
        if (str_contains($lower, 'km 12') || str_contains($lower, 'km12') || str_contains($lower, 'workshop')) {
            $asset['site_latitude'] = $masterSiteCoords['Workshop Bay KM 12']['lat'];
            $asset['site_longitude'] = $masterSiteCoords['Workshop Bay KM 12']['lng'];
        } elseif (str_contains($lower, 'prabumulih')) {
            $asset['site_latitude'] = $masterSiteCoords['Yard Prabumulih']['lat'];
            $asset['site_longitude'] = $masterSiteCoords['Yard Prabumulih']['lng'];
        } elseif (str_contains($lower, 'minas')) {
            $asset['site_latitude'] = $masterSiteCoords['Minas Field Project']['lat'];
            $asset['site_longitude'] = $masterSiteCoords['Minas Field Project']['lng'];
        } elseif (str_contains($lower, 'pekanbaru')) {
            $asset['site_latitude'] = $masterSiteCoords['PKB PEKANBARU Branch']['lat'];
            $asset['site_longitude'] = $masterSiteCoords['PKB PEKANBARU Branch']['lng'];
        } elseif (str_contains($lower, 'bangko')) {
            $asset['site_latitude'] = $masterSiteCoords['Site Bangko']['lat'];
            $asset['site_longitude'] = $masterSiteCoords['Site Bangko']['lng'];
        } elseif (str_contains($lower, 'ndd')) {
            $asset['site_latitude'] = $masterSiteCoords['Site NDD']['lat'];
            $asset['site_longitude'] = $masterSiteCoords['Site NDD']['lng'];
        } elseif (str_contains($lower, 'celcin')) {
            $asset['site_latitude'] = $masterSiteCoords['Site Celcin']['lat'];
            $asset['site_longitude'] = $masterSiteCoords['Site Celcin']['lng'];
        } else {
            $hash = crc32($locName . '_' . ($asset['asset_id'] ?? ''));
            $angle = ($hash % 360) * M_PI / 180.0;
            $radius = 0.002 + (($hash >> 8) % 25) / 10000.0;
            $asset['site_latitude'] = round(1.2854600 + sin($angle) * $radius, 7);
            $asset['site_longitude'] = round(101.2185700 + cos($angle) * $radius / 0.9997, 7);
        }
    }
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
$asset['display_model'] = $asset['make_model']
    ?? ($asset['category'] ? ($asset['category'] . ' (' . ($asset['type'] ?? 'Heavy Equipment') . ')') : ($asset['type'] ?? 'Heavy Equipment'));


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
    "SELECT am.movement_id, am.bast_number, am.movement_date, am.notes,
            origin.location_name AS from_location,
            destination.location_name AS to_location,
            requester.full_name AS requested_by
     FROM asset_movements am
     LEFT JOIN locations origin ON origin.location_id = am.from_location_id
     LEFT JOIN locations destination ON destination.location_id = am.to_location_id
     LEFT JOIN users requester ON requester.user_id = am.requested_by
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

// Contracts & Rental Assignments
$contracts = $fetchAll($db,
    "SELECT assignment_id, contract_number, project, site, start_date, end_date, target_hours, notes
     FROM contract_assignments
     WHERE asset_id = :asset_id
     ORDER BY end_date DESC, assignment_id DESC",
    $params
);
$asset['contracts'] = $contracts;
$asset['active_contract'] = $contracts[0] ?? null;

// Unit Valuations
$valuations = $fetchAll($db,
    "SELECT valuation_id, unit_name, unit_code_alias, total_repair_cost, purchase_price, book_value, 
            monthly_rental_rate, monthly_leasing_installment, market_price_min, market_price_max, raw_market_price_str
     FROM unit_valuations
     WHERE asset_id = :asset_id
     ORDER BY valuation_id DESC",
    $params
);
$asset['valuation'] = $valuations[0] ?? null;

// Direct Operational Cost Transactions
$costTransactions = $fetchAll($db,
    "SELECT transaction_id, cost_code, transaction_date, cost_category, description, amount, currency, vendor_name, reference_no
     FROM asset_cost_transactions
     WHERE asset_id = :asset_id AND reversed_at IS NULL
     ORDER BY transaction_date DESC, transaction_id DESC",
    $params
);
$asset['cost_transactions'] = $costTransactions;
$asset['total_direct_cost'] = array_sum(array_column($costTransactions, 'amount'));

// Operator Shift & Telemetry Usage Rate
if (!empty($asset['operator_history']) && empty($asset['operator_shift'])) {
    $asset['operator_shift'] = $asset['operator_history'][0]['shift_type'] ?? 'Shift 1 (Utama)';
}

if ($asset['daily_usage_coefficient'] === null) {
    if (!empty($asset['active_contract']['target_hours']) && (float) $asset['active_contract']['target_hours'] > 0) {
        $asset['daily_usage_coefficient'] = round((float) $asset['active_contract']['target_hours'] / 31.0, 1);
    } elseif (!empty($telemetryRows[0]['working_hours']) && (float) $telemetryRows[0]['working_hours'] > 0) {
        $wDays = max(1, (int) ($telemetryRows[0]['working_days'] ?? 25));
        $asset['daily_usage_coefficient'] = round((float) $telemetryRows[0]['working_hours'] / $wDays, 1);
    }
}

// Dynamic Health Score Calculation
$statusUpper = strtoupper((string) ($asset['status'] ?? 'READY'));
$baseHealth = 98;
if ($statusUpper === 'BREAKDOWN') {
    $baseHealth -= 35;
} elseif (str_contains($statusUpper, 'ACCIDENT')) {
    $baseHealth -= 40;
} elseif ($statusUpper === 'STANDBY') {
    $baseHealth -= 10;
}

$openWOCount = 0;
foreach ($workOrders as $wo) {
    $wSt = strtoupper((string) ($wo['status'] ?? ''));
    if (!in_array($wSt, ['CLOSED', 'COMPLETED', 'CANCELLED'], true)) {
        $openWOCount++;
    }
}
$baseHealth -= min(25, $openWOCount * 8);

$redTireCount = 0;
foreach ($latestTires as $tire) {
    if (strtoupper((string) ($tire['condition_color'] ?? '')) === 'RED') {
        $redTireCount++;
    }
}
$baseHealth -= min(15, $redTireCount * 5);

$asset['health_score'] = max(35, min(100, $baseHealth));

// Dynamic Next Service Date Calculation
if (empty($asset['next_service_estimated_date']) && !empty($asset['next_service_due_hm_km']) && !empty($asset['last_hm_km'])) {
    $remainingHm = (float) $asset['next_service_due_hm_km'] - (float) $asset['last_hm_km'];
    $dailyRate = (float) ($asset['daily_usage_coefficient'] ?? 8.0);
    if ($dailyRate <= 0) {
        $dailyRate = 8.0;
    }

    if ($remainingHm <= 0) {
        $asset['next_service_estimated_date'] = date('Y-m-d');
    } else {
        $daysNeeded = max(1, (int) round($remainingHm / $dailyRate));
        $asset['next_service_estimated_date'] = date('Y-m-d', strtotime("+$daysNeeded days"));
    }
}

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
