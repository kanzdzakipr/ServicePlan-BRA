<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$db = Database::getInstance();
ReportIntegration::ensureTables($db);

try {
    $assetScope = api_location_scope_clause('a', 'report_reference_asset_location_id');
    $assetSql = "SELECT a.asset_id, a.asset_code, a.category, a.make_model, a.serial_number,
                        a.year_manufacture,
                        a.license_plate, a.last_hm_km, a.status, a.current_location_id,
                        a.raw_location_notes,
                        l.location_name
                 FROM assets a
                 LEFT JOIN locations l ON l.location_id = a.current_location_id
                 WHERE a.is_active = 1";
    if ($assetScope['sql'] !== '') {
        $assetSql .= ' AND ' . $assetScope['sql'];
    }
    $assetSql .= ' ORDER BY a.asset_code, a.asset_id';
    $assetStatement = $db->prepare($assetSql);
    $assetStatement->execute($assetScope['params']);
    $assets = array_map(static fn(array $row): array => [
        'id' => (string) $row['asset_id'],
        'code' => (string) $row['asset_code'],
        'category' => (string) $row['category'],
        'makeModel' => (string) ($row['make_model'] ?? ''),
        'serialNumber' => (string) ($row['serial_number'] ?? ''),
        'yearManufacture' => $row['year_manufacture'] !== null ? (int) $row['year_manufacture'] : '',
        'licensePlate' => (string) ($row['license_plate'] ?? ''),
        'locationId' => $row['current_location_id'] !== null ? (int) $row['current_location_id'] : null,
        'location' => trim((string) ($row['location_name'] ?? '')) !== ''
            ? (string) $row['location_name']
            : trim(html_entity_decode(strip_tags((string) ($row['raw_location_notes'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        'lastHmKm' => (float) $row['last_hm_km'],
        'status' => (string) $row['status'],
    ], $assetStatement->fetchAll(PDO::FETCH_ASSOC));

    $workOrderScope = api_location_scope_clause('a', 'report_reference_work_order_location_id');
    $workOrderSql = "SELECT w.wo_id, w.asset_id, w.status, w.priority, w.issue_description,
                            a.asset_code, a.category, a.make_model
                     FROM work_orders w
                     INNER JOIN assets a ON a.asset_id = w.asset_id
                     WHERE a.is_active = 1 AND w.status NOT IN ('Closed', 'Cancelled')";
    if ($workOrderScope['sql'] !== '') {
        $workOrderSql .= ' AND ' . $workOrderScope['sql'];
    }
    $workOrderSql .= ' ORDER BY w.reported_at DESC, w.wo_id';
    $workOrderStatement = $db->prepare($workOrderSql);
    $workOrderStatement->execute($workOrderScope['params']);
    $workOrders = array_map(static fn(array $row): array => [
        'id' => (string) $row['wo_id'],
        'assetId' => (string) $row['asset_id'],
        'assetCode' => (string) ($row['asset_code'] ?? ''),
        'asset' => trim((string) ($row['category'] ?? '') . ' ' . (string) ($row['make_model'] ?? '')),
        'status' => (string) $row['status'],
        'priority' => (string) $row['priority'],
        'issue' => (string) ($row['issue_description'] ?? ''),
    ], $workOrderStatement->fetchAll(PDO::FETCH_ASSOC));

    $purchaseRequestSql = "SELECT pr.spb_id, pr.wo_id, pr.asset_id, pr.urgency, pr.status,
                                  a.asset_code, l.location_name
                           FROM purchase_requests pr
                           INNER JOIN assets a ON a.asset_id = pr.asset_id
                           LEFT JOIN locations l ON l.location_id = a.current_location_id
                           WHERE pr.status <> 'Closed'";
    if ($workOrderScope['sql'] !== '') $purchaseRequestSql .= ' AND ' . $workOrderScope['sql'];
    $purchaseRequestSql .= ' ORDER BY pr.requested_at DESC, pr.spb_id';
    $purchaseRequestStatement = $db->prepare($purchaseRequestSql);
    $purchaseRequestStatement->execute($workOrderScope['params']);
    $purchaseRequests = array_map(static fn(array $row): array => [
        'id' => (string) $row['spb_id'],
        'workOrderId' => (string) $row['wo_id'],
        'assetId' => (string) $row['asset_id'],
        'assetCode' => (string) ($row['asset_code'] ?? ''),
        'location' => (string) ($row['location_name'] ?? ''),
        'urgency' => (string) $row['urgency'],
        'status' => (string) $row['status'],
    ], $purchaseRequestStatement->fetchAll(PDO::FETCH_ASSOC));

    $purchaseOrderSql = "SELECT po.ppb_id, po.spb_id, po.wo_id, po.asset_id, po.vendor,
                                po.project, po.quote_date, po.delivery_due, po.delivery_location,
                                po.status, poi.id AS item_id, poi.part_number, poi.description,
                                poi.unit_measure, poi.quantity
                         FROM purchase_orders po
                         INNER JOIN purchase_requests pr ON pr.spb_id = po.spb_id
                         INNER JOIN assets a ON a.asset_id = po.asset_id
                         INNER JOIN purchase_order_items poi ON poi.ppb_id = po.ppb_id
                         WHERE po.status NOT IN ('Received', 'Cancelled')";
    if ($workOrderScope['sql'] !== '') $purchaseOrderSql .= ' AND ' . $workOrderScope['sql'];
    $purchaseOrderSql .= ' ORDER BY po.created_at DESC, po.ppb_id, poi.id';
    $purchaseOrderStatement = $db->prepare($purchaseOrderSql);
    $purchaseOrderStatement->execute($workOrderScope['params']);
    $purchaseOrdersById = [];
    foreach ($purchaseOrderStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $id = (string) $row['ppb_id'];
        if (!isset($purchaseOrdersById[$id])) {
            $purchaseOrdersById[$id] = [
                'id' => $id,
                'spbId' => (string) $row['spb_id'],
                'workOrderId' => (string) $row['wo_id'],
                'assetId' => (string) $row['asset_id'],
                'vendor' => (string) $row['vendor'],
                'project' => (string) $row['project'],
                'quoteDate' => (string) ($row['quote_date'] ?? ''),
                'deliveryDue' => (string) $row['delivery_due'],
                'deliveryLocation' => (string) $row['delivery_location'],
                'status' => (string) $row['status'],
                'items' => [],
            ];
        }
        $purchaseOrdersById[$id]['items'][] = [
            'id' => (string) $row['item_id'],
            'partNumber' => (string) $row['part_number'],
            'name' => (string) $row['description'],
            'unit' => (string) $row['unit_measure'],
            'quantity' => (int) $row['quantity'],
        ];
    }
    $purchaseOrders = array_values($purchaseOrdersById);

    $locationSql = 'SELECT location_id, location_name, location_type, region FROM locations WHERE is_active = 1';
    $locationParams = [];
    if (!api_has_global_location_scope()) {
        $locationId = api_current_location_id();
        if ($locationId === null) {
            $locationSql .= ' AND 1 = 0';
        } else {
            $locationSql .= ' AND location_id = :location_id';
            $locationParams[':location_id'] = $locationId;
        }
    }
    $locationSql .= ' ORDER BY location_name';
    $locationStatement = $db->prepare($locationSql);
    $locationStatement->execute($locationParams);
    $locations = array_map(static fn(array $row): array => [
        'id' => (int) $row['location_id'],
        'name' => (string) $row['location_name'],
        'type' => (string) $row['location_type'],
        'region' => (string) ($row['region'] ?? ''),
    ], $locationStatement->fetchAll(PDO::FETCH_ASSOC));

    $siteNames = [];
    foreach ($locations as $location) {
        $siteNames[(string) $location['name']] = true;
    }
    try {
        $siteSql = "SELECT DISTINCT o.site
                    FROM report_operation_logs o
                    INNER JOIN assets a ON a.asset_id = o.asset_id
                    WHERE o.reversed_at IS NULL AND o.site <> ''";
        if ($assetScope['sql'] !== '') {
            $siteSql .= ' AND ' . $assetScope['sql'];
        }
        $siteSql .= ' ORDER BY o.site';
        $siteStatement = $db->prepare($siteSql);
        $siteStatement->execute($assetScope['params']);
        foreach ($siteStatement->fetchAll(PDO::FETCH_COLUMN) as $site) {
            $site = trim((string) $site);
            if ($site !== '') $siteNames[$site] = true;
        }
    } catch (PDOException $error) {
        // Instalasi lama tetap mendapat daftar lokasi meskipun ledger LHO belum dimigrasikan.
    }
    $sites = array_keys($siteNames);
    natcasesort($sites);
    $sites = array_values($sites);

    $parts = $db->query(
        'SELECT part_id, part_number, part_name, category, unit_measure, stock_qty,
                unit_cost, location_warehouse
         FROM parts
         ORDER BY part_name, part_number'
    )->fetchAll(PDO::FETCH_ASSOC);
    $parts = array_map(static fn(array $row): array => [
        'id' => (int) $row['part_id'],
        'number' => (string) $row['part_number'],
        'name' => (string) $row['part_name'],
        'category' => (string) $row['category'],
        'unit' => (string) $row['unit_measure'],
        'stock' => (int) $row['stock_qty'],
        'unitCost' => (float) $row['unit_cost'],
        'warehouse' => (string) $row['location_warehouse'],
    ], $parts);

    $peopleSql = 'SELECT u.user_id, u.full_name, r.role_name, l.location_name
                  FROM users u
                  LEFT JOIN roles r ON r.role_id = u.role_id
                  LEFT JOIN locations l ON l.location_id = u.assigned_location_id
                  WHERE u.is_active = 1';
    $peopleParams = [];
    if (!api_has_global_location_scope()) {
        $locationId = api_current_location_id();
        if ($locationId === null) {
            $peopleSql .= ' AND 1 = 0';
        } else {
            $peopleSql .= ' AND u.assigned_location_id = :people_location_id';
            $peopleParams[':people_location_id'] = $locationId;
        }
    }
    $peopleSql .= ' ORDER BY u.full_name';
    $peopleStatement = $db->prepare($peopleSql);
    $peopleStatement->execute($peopleParams);
    $people = array_map(static fn(array $row): array => [
        'id' => (int) $row['user_id'],
        'name' => (string) $row['full_name'],
        'role' => (string) ($row['role_name'] ?? ''),
        'location' => (string) ($row['location_name'] ?? ''),
    ], $peopleStatement->fetchAll(PDO::FETCH_ASSOC));

    $categories = array_values(array_unique(array_filter(array_column($assets, 'category'))));
    natcasesort($categories);
    $categories = array_values($categories);
    $models = array_values(array_unique(array_filter(array_column($assets, 'makeModel'))));
    natcasesort($models);
    $models = array_values($models);

    $calibrationSql = 'SELECT cr.instrument_name, cr.identification_no, cr.brand_type,
                              cr.planned_date, cr.performed_date, cr.calibration_result,
                              cr.follow_up_status, cr.location_id
                       FROM calibration_records cr
                       WHERE cr.reversed_at IS NULL';
    $calibrationParams = [];
    if (!api_has_global_location_scope()) {
        $locationId = api_current_location_id();
        if ($locationId === null) {
            $calibrationSql .= ' AND 1 = 0';
        } else {
            $calibrationSql .= ' AND cr.location_id = :calibration_location_id';
            $calibrationParams[':calibration_location_id'] = $locationId;
        }
    }
    $calibrationSql .= ' ORDER BY cr.performed_date DESC, cr.calibration_id DESC';
    $calibrationStatement = $db->prepare($calibrationSql);
    $calibrationStatement->execute($calibrationParams);
    $calibrationInstruments = [];
    $seenCalibrationInstruments = [];
    foreach ($calibrationStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = mb_strtolower(trim((string) $row['identification_no']));
        if ($key === '' || isset($seenCalibrationInstruments[$key])) continue;
        $seenCalibrationInstruments[$key] = true;
        $calibrationInstruments[] = [
            'name' => (string) $row['instrument_name'],
            'identification' => (string) $row['identification_no'],
            'brandType' => (string) $row['brand_type'],
            'plannedDate' => (string) $row['planned_date'],
            'performedDate' => (string) $row['performed_date'],
            'result' => (string) $row['calibration_result'],
            'followUp' => (string) $row['follow_up_status'],
        ];
    }

    $shippingSql = 'SELECT s.carrier_name, s.carrier_address, s.carrier_contact,
                           s.transport_plate, s.transport_contract
                    FROM asset_shipments s
                    LEFT JOIN assets a ON a.asset_id = s.asset_id
                    WHERE s.reversed_at IS NULL';
    if ($assetScope['sql'] !== '') {
        $shippingSql .= ' AND ' . $assetScope['sql'];
    }
    $shippingSql .= ' ORDER BY s.shipment_date DESC, s.shipment_id DESC';
    $shippingStatement = $db->prepare($shippingSql);
    $shippingStatement->execute($assetScope['params']);
    $shippingPartners = [];
    $seenShippingPartners = [];
    foreach ($shippingStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = trim((string) $row['carrier_name']);
        $key = mb_strtolower($name);
        if ($name === '' || isset($seenShippingPartners[$key])) continue;
        $seenShippingPartners[$key] = true;
        $shippingPartners[] = [
            'name' => $name,
            'address' => (string) ($row['carrier_address'] ?? ''),
            'contact' => (string) ($row['carrier_contact'] ?? ''),
            'lastPlate' => (string) ($row['transport_plate'] ?? ''),
            'lastContract' => (string) ($row['transport_contract'] ?? ''),
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'assets' => $assets,
            'locations' => $locations,
            'projects' => $sites,
            'sites' => $sites,
            'parts' => $parts,
            'people' => $people,
            'workOrders' => $workOrders,
            'purchaseRequests' => $purchaseRequests,
            'purchaseOrders' => $purchaseOrders,
            'categories' => $categories,
            'models' => $models,
            'calibrationInstruments' => $calibrationInstruments,
            'shippingPartners' => $shippingPartners,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Report references API error: ' . $error->getMessage());
    api_json_response(500, [
        'status' => 'error',
        'message' => 'Referensi pilihan laporan tidak dapat dimuat.',
    ]);
}
