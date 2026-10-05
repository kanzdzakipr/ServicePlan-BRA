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
    $assetSql = "SELECT a.asset_id as id, a.type, a.category, a.status,
                        COALESCE(l.location_name, a.raw_location_notes) as location,
                        a.last_hm_km
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

    // Override Work Orders dari Database
    $workOrderScope = api_location_scope_clause('a', 'init_wo_location_id');
    $workOrderSql = "SELECT w.wo_id as woId, w.asset_id as assetId, w.issue_description as issue,
                            w.downtime_formatted as downtime, w.status, w.priority, w.assigned_mechanic as assignedTo,
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
