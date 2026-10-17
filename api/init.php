<?php
require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';
$db = Database::getInstance();
ReportIntegration::ensureTables($db);

try {
    // Ambil data dasar dari data.json (sebagai fallback untuk struktur kompleks seperti costs dll)
    $jsonPath = __DIR__ . '/../data.json';
    $globalData = [];
    if (file_exists($jsonPath)) {
        $globalData = json_decode(file_get_contents($jsonPath), true);
    }

    // Override Assets dari Database
    $assetScope = api_location_scope_clause('a', 'init_asset_location_id');
    $assetSql = "SELECT a.asset_id as id, a.asset_code, a.serial_number, a.license_plate,
                        a.make_model, a.year_manufacture, a.type, a.category, a.status,
                        a.current_location_id,
                        COALESCE(l.location_name, a.raw_location_notes) as location,
                        l.location_name,
                        a.last_hm_km, a.last_latitude, a.last_longitude, a.gps_updated_at,
                        a.last_update_timestamp
                 FROM assets a
                 LEFT JOIN locations l ON l.location_id = a.current_location_id";
    if ($assetScope['sql'] !== '') $assetSql .= " WHERE " . $assetScope['sql'];
    $stmtAssets = $db->prepare($assetSql);
    $stmtAssets->execute($assetScope['params']);
    $dbAssets = $stmtAssets->fetchAll();
    // Never fall back to the unscoped JSON asset list when this user has no accessible rows.
    $globalData['assets'] = $dbAssets;

    // Riwayat perpindahan aktual untuk tab Lokasi & GPS pada detail unit.
    $movementSql = "SELECT am.movement_id AS movementId, am.asset_id AS assetId,
                           origin.location_name AS fromLocation,
                           destination.location_name AS toLocation,
                           am.bast_number AS bastNumber, am.movement_date AS movementDate,
                           requester.full_name AS requestedBy
                    FROM asset_movements am
                    INNER JOIN assets a ON a.asset_id = am.asset_id
                    LEFT JOIN locations origin ON origin.location_id = am.from_location_id
                    INNER JOIN locations destination ON destination.location_id = am.to_location_id
                    LEFT JOIN users requester ON requester.user_id = am.requested_by";
    if ($assetScope['sql'] !== '') $movementSql .= " WHERE " . $assetScope['sql'];
    $movementSql .= ' ORDER BY am.movement_date DESC, am.movement_id DESC';
    $stmtMovements = $db->prepare($movementSql);
    $stmtMovements->execute($assetScope['params']);
    $globalData['asset_movements'] = $stmtMovements->fetchAll();

    // Penyerahan unit kepada ekspedisi (BAPE) ditampilkan sebagai riwayat,
    // tetapi tidak mengubah lokasi aktif sebelum ada dokumen perpindahan BAST.
    $shipmentSql = "SELECT s.shipment_id AS shipmentId, s.report_id AS reportId,
                           s.asset_id AS assetId, origin.location_name AS originLocation,
                           s.bape_number AS bapeNumber, s.shipment_date AS shipmentDate,
                           sender.full_name AS senderName, s.carrier_name AS carrierName,
                           s.carrier_address AS carrierAddress, s.carrier_contact AS carrierContact,
                           s.transport_plate AS transportPlate, s.transport_contract AS transportContract,
                           s.unit_condition AS unitCondition, s.shipment_status AS shipmentStatus,
                           s.notes
                    FROM asset_shipments s
                    INNER JOIN report_records r ON r.report_id = s.report_id AND r.status = 'FINAL'
                    INNER JOIN assets a ON a.asset_id = s.asset_id
                    LEFT JOIN locations origin ON origin.location_id = s.origin_location_id
                    LEFT JOIN users sender ON sender.user_id = s.sender_user_id
                    WHERE s.reversed_at IS NULL";
    if ($assetScope['sql'] !== '') $shipmentSql .= ' AND ' . $assetScope['sql'];
    $shipmentSql .= ' ORDER BY s.shipment_date DESC, s.shipment_id DESC';
    $stmtShipments = $db->prepare($shipmentSql);
    $stmtShipments->execute($assetScope['params']);
    $globalData['asset_shipments'] = $stmtShipments->fetchAll();

    // SPL final untuk register review planner pada People & KPI.
    $splSql = "SELECT s.overtime_id AS overtimeId, s.report_id AS reportId,
                      s.report_item_position AS position, s.spl_number AS splNumber,
                      s.spl_date AS splDate, s.period_start AS periodStart,
                      s.period_end AS periodEnd, l.location_name AS location,
                      employee.full_name AS employeeName, s.role_snapshot AS employeeRole,
                      s.start_time AS startTime, s.end_time AS endTime,
                      s.duration_hours AS durationHours,
                      s.activity_description AS activityDescription,
                      s.target_description AS targetDescription,
                      s.general_instruction AS generalInstruction,
                      prepared.full_name AS preparedBy, instructed.full_name AS instructedBy,
                      s.start_verified AS startVerified, s.end_verified AS endVerified
               FROM spl_overtime_records s
               INNER JOIN report_records rr ON rr.report_id = s.report_id AND rr.status = 'FINAL'
               INNER JOIN locations l ON l.location_id = s.location_id
               INNER JOIN users employee ON employee.user_id = s.employee_user_id
               INNER JOIN users prepared ON prepared.user_id = s.prepared_by
               INNER JOIN users instructed ON instructed.user_id = s.instructed_by
               WHERE s.reversed_at IS NULL";
    $splParams = [];
    if (!api_has_global_location_scope()) {
        $scopeLocationId = api_current_location_id();
        if ($scopeLocationId === null) {
            $splSql .= ' AND 1 = 0';
        } else {
            $splSql .= ' AND s.location_id = :spl_location_id';
            $splParams[':spl_location_id'] = $scopeLocationId;
        }
    }
    $splSql .= ' ORDER BY s.spl_date DESC, s.spl_number, s.report_item_position';
    $stmtSpl = $db->prepare($splSql);
    $stmtSpl->execute($splParams);
    $globalData['spl_overtime_records'] = $stmtSpl->fetchAll();

    // Pemakaian Cutting Bit final untuk riwayat khusus recycler/milling pada Condition Monitoring.
    $cuttingSql = "SELECT c.bit_log_id AS bitLogId, c.report_id AS reportId,
                          c.report_item_position AS position, rr.report_number AS reportNumber,
                          c.asset_id AS assetId, c.activity_date AS activityDate,
                          l.location_name AS location, c.equipment_label AS equipmentLabel,
                          c.material, c.period_label AS periodLabel, c.report_year AS reportYear,
                          c.planning_qty AS planningQty, c.actual_qty AS actualQty,
                          c.variance_qty AS varianceQty,
                          c.realization_percent AS realizationPercent,
                          c.daily_requirement AS dailyRequirement,
                          c.daily_target_m AS dailyTargetM, c.working_days AS workingDays,
                          c.total_target_m AS totalTargetM,
                          c.safety_stock_minimum AS safetyStockMinimum,
                          c.total_requirement AS totalRequirement,
                          c.source_document AS sourceDocument, c.notes,
                          prepared.full_name AS preparedBy, checked.full_name AS checkedBy,
                          approved.full_name AS approvedBy, c.created_at AS createdAt
                   FROM cutting_bit_logs c
                   INNER JOIN report_records rr ON rr.report_id = c.report_id AND rr.status = 'FINAL'
                   INNER JOIN assets a ON a.asset_id = c.asset_id
                   LEFT JOIN locations l ON l.location_id = c.location_id
                   LEFT JOIN users prepared ON prepared.user_id = c.prepared_by
                   LEFT JOIN users checked ON checked.user_id = c.checked_by
                   LEFT JOIN users approved ON approved.user_id = c.approved_by
                   WHERE c.reversed_at IS NULL";
    $cuttingParams = [];
    if (!api_has_global_location_scope()) {
        $scopeLocationId = api_current_location_id();
        if ($scopeLocationId === null) {
            $cuttingSql .= ' AND 1 = 0';
        } else {
            $cuttingSql .= ' AND c.location_id = :cutting_location_id';
            $cuttingParams[':cutting_location_id'] = $scopeLocationId;
        }
    }
    $cuttingSql .= ' ORDER BY c.activity_date DESC, c.bit_log_id DESC';
    $stmtCutting = $db->prepare($cuttingSql);
    $stmtCutting->execute($cuttingParams);
    $globalData['cutting_bit_logs'] = $stmtCutting->fetchAll();

    // Override Work Orders dari Database
    $workOrderScope = api_location_scope_clause('a', 'init_wo_location_id');
    $workOrderSql = "SELECT w.wo_id as woId, w.asset_id as assetId, w.issue_description as issue,
                            w.downtime_formatted as downtime, w.downtime_minutes as downtimeMinutes,
                            w.status, w.priority, w.maintenance_type as maintenanceType,
                            w.assigned_mechanic as assignedTo, w.root_cause as rootCause,
                            w.repair_action as repairAction, w.reported_at as reportedAt,
                            w.repair_started_at as repairStartedAt, w.closed_at as closedAt,
                            TIMESTAMPDIFF(MINUTE, w.reported_at, COALESCE(w.closed_at, NOW())) as handlingDurationMinutes,
                            (SELECT rr.report_number
                             FROM report_work_order_integrations rwi
                             INNER JOIN report_records rr ON rr.report_id = rwi.report_id
                             WHERE rwi.work_order_id = w.wo_id AND rwi.reversed_at IS NULL
                             ORDER BY rwi.integration_id DESC LIMIT 1) AS sourceReportNumber
                     FROM work_orders w INNER JOIN assets a ON a.asset_id = w.asset_id";
    if ($workOrderScope['sql'] !== '') $workOrderSql .= " WHERE " . $workOrderScope['sql'];
    $stmtWO = $db->prepare($workOrderSql);
    $stmtWO->execute($workOrderScope['params']);
    $dbWO = $stmtWO->fetchAll();
    $globalData['work_orders'] = $dbWO;

    // Jika ingin override costs, dll bisa dilakukan di sini

    echo json_encode($globalData);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
