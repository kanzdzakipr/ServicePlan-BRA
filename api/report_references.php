<?php
declare(strict_types=1);

require_once 'db.php';

$db = Database::getInstance();

try {
    $assetScope = api_location_scope_clause('a', 'report_reference_asset_location_id');
    $assetSql = "SELECT a.asset_id, a.asset_code, a.category, a.make_model, a.serial_number,
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
        'licensePlate' => (string) ($row['license_plate'] ?? ''),
        'locationId' => $row['current_location_id'] !== null ? (int) $row['current_location_id'] : null,
        'location' => trim((string) ($row['location_name'] ?? '')) !== ''
            ? (string) $row['location_name']
            : trim(html_entity_decode(strip_tags((string) ($row['raw_location_notes'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        'lastHmKm' => (float) $row['last_hm_km'],
        'status' => (string) $row['status'],
    ], $assetStatement->fetchAll(PDO::FETCH_ASSOC));

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

    $peopleSql = 'SELECT user_id, full_name FROM users WHERE is_active = 1';
    $peopleParams = [];
    if (!api_has_global_location_scope()) {
        $locationId = api_current_location_id();
        if ($locationId === null) {
            $peopleSql .= ' AND 1 = 0';
        } else {
            $peopleSql .= ' AND assigned_location_id = :people_location_id';
            $peopleParams[':people_location_id'] = $locationId;
        }
    }
    $peopleSql .= ' ORDER BY full_name';
    $peopleStatement = $db->prepare($peopleSql);
    $peopleStatement->execute($peopleParams);
    $people = array_map(static fn(array $row): array => [
        'id' => (int) $row['user_id'],
        'name' => (string) $row['full_name'],
    ], $peopleStatement->fetchAll(PDO::FETCH_ASSOC));

    $categories = array_values(array_unique(array_filter(array_column($assets, 'category'))));
    natcasesort($categories);
    $categories = array_values($categories);
    $models = array_values(array_unique(array_filter(array_column($assets, 'makeModel'))));
    natcasesort($models);
    $models = array_values($models);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'assets' => $assets,
            'locations' => $locations,
            'projects' => $sites,
            'sites' => $sites,
            'parts' => $parts,
            'people' => $people,
            'categories' => $categories,
            'models' => $models,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Report references API error: ' . $error->getMessage());
    api_json_response(500, [
        'status' => 'error',
        'message' => 'Referensi pilihan laporan tidak dapat dimuat.',
    ]);
}
