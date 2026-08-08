<?php
$mapFile = __DIR__ . '/asset_id_normalization_map.json';
if (!file_exists($mapFile)) {
    die("Error: asset_id_normalization_map.json not found!\n");
}

$mappings = json_decode(file_get_contents($mapFile), true);
echo "Loaded " . count($mappings) . " asset_id normalization rules.\n";

$mysqli = new mysqli('localhost', 'root', '', 'u646470441_ServicePlanBRA');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

// Disable foreign key checks for schema updates
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

$tablesWithAssetId = [
    'accidents',
    'asset_movements',
    'battery_logs',
    'cutting_bit_logs',
    'fuel_logs',
    'inspections',
    'pm_plans',
    'purchase_requests',
    'telematics_gps_logs',
    'telematics_logs',
    'tire_inspections',
    'unit_valuations',
    'work_orders'
];

$updatedAssetsCount = 0;

foreach ($mappings as $oldId => $info) {
    $newId = $info['new_id'];
    if ($oldId === $newId) continue;

    $oldIdEsc = $mysqli->real_escape_string($oldId);
    $newIdEsc = $mysqli->real_escape_string($newId);

    // Update assets table asset_id AND asset_code
    $res = $mysqli->query("UPDATE assets SET asset_id = '$newIdEsc', asset_code = '$newIdEsc' WHERE asset_id = '$oldIdEsc'");
    if ($mysqli->affected_rows > 0) {
        $updatedAssetsCount++;
    }

    // Update all referencing tables
    foreach ($tablesWithAssetId as $table) {
        $mysqli->query("UPDATE `$table` SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc'");
    }
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "Successfully normalized $updatedAssetsCount asset_id records in local MySQL database!\n";

// Now update u646470441_ServicePlanBRA.sql dump file
$sqlPath = __DIR__ . '/u646470441_ServicePlanBRA.sql';
if (file_exists($sqlPath)) {
    echo "Updating SQL dump file $sqlPath...\n";
    $sqlContent = file_get_contents($sqlPath);

    foreach ($mappings as $oldId => $info) {
        $newId = $info['new_id'];
        if ($oldId === $newId) continue;

        // Replace asset_id string literals in SQL dump
        $oldPattern = "'$oldId'";
        $newPattern = "'$newId'";
        $sqlContent = str_replace($oldPattern, $newPattern, $sqlContent);
    }

    file_put_contents($sqlPath, $sqlContent);
    echo "SQL dump file successfully updated with normalized asset_ids!\n";
}
