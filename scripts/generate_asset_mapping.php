<?php
$mysqli = new mysqli('localhost', 'root', '', 'u646470441_ServicePlanBRA');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

$res = $mysqli->query("SELECT asset_id, asset_code, license_plate, serial_number, category, make_model FROM assets ORDER BY category, asset_id");
$assets = [];
while ($r = $res->fetch_assoc()) {
    $assets[] = $r;
}

// Determine standard target prefix for each equipment category
function getTargetPrefix($category, $makeModel, $oldId) {
    if ($category === 'Dump Truck') return 'DT';
    if ($category === 'Excavator') return 'EXC';
    if ($category === 'Bulldozer') return 'DZ';
    if ($category === 'Motor Grader') return 'MG';
    if ($category === 'Vibro Compactor') return 'PF';
    if ($category === 'Water Truck') return 'WT';
    if ($category === 'Prime Mover') return 'PM';
    if ($category === 'Light Vehicle') return 'LV';
    if ($category === 'Trado') return 'FBT';
    if ($category === 'Reclaimer Spreader') return 'RM';
    
    $make = strtoupper($makeModel ?? '');
    if (strpos($make, 'CRANE') !== false) return 'TMC';
    if (strpos($make, 'FUEL') !== false) return 'FTK';
    if (strpos($make, 'LAB') !== false) return 'ML';
    if (strpos($make, 'BREAKER') !== false) return 'SB';
    return 'SUP';
}

$existingIds = [];
// Keep existing IDs that are ALREADY in the ideal prefix format: e.g. DT-00001, EXC-00001, DZ-00001, MG-00001, PF-00001, etc.
foreach ($assets as $a) {
    $id = $a['asset_id'];
    $cat = $a['category'];
    $prefix = getTargetPrefix($cat, $a['make_model'], $id);
    if (preg_match('/^' . $prefix . '-\d{5}$/', $id)) {
        $existingIds[$id] = true;
    }
}

$counters = [
    'DT' => 1,
    'EXC' => 1,
    'DZ' => 1,
    'MG' => 1,
    'PF' => 1,
    'WT' => 1,
    'PM' => 1,
    'LV' => 1,
    'FBT' => 1,
    'RM' => 1,
    'TMC' => 1,
    'FTK' => 1,
    'ML' => 1,
    'SB' => 1,
    'SUP' => 1
];

function getNextId($prefix, &$counters, &$existingIds) {
    while (true) {
        $num = $counters[$prefix]++;
        $candidate = sprintf("%s-%05d", $prefix, $num);
        if (!isset($existingIds[$candidate])) {
            $existingIds[$candidate] = true;
            return $candidate;
        }
    }
}

$mappings = [];

foreach ($assets as $a) {
    $oldId = $a['asset_id'];
    $cat = $a['category'];
    $targetPrefix = getTargetPrefix($cat, $a['make_model'], $oldId);
    
    // If asset_id is already in targetPrefix-00000 format, skip it
    if (preg_match('/^' . $targetPrefix . '-\d{5}$/', $oldId)) {
        continue;
    }

    $newId = null;

    // Try keeping number if available and candidate is free
    if (preg_match('/-(\d+)$/', $oldId, $m)) {
        $num = (int)$m[1];
        $candidate = sprintf("%s-%05d", $targetPrefix, $num);
        if (!isset($existingIds[$candidate])) {
            $newId = $candidate;
            $existingIds[$candidate] = true;
        }
    }

    if (!$newId) {
        $newId = getNextId($targetPrefix, $counters, $existingIds);
    }

    $mappings[$oldId] = [
        'old_id' => $oldId,
        'new_id' => $newId,
        'license_plate' => $a['license_plate'] ?: ($a['serial_number'] ?: '-'),
        'category' => $a['category'],
        'make_model' => $a['make_model']
    ];
}

echo "Total asset_id mappings generated: " . count($mappings) . "\n";
file_put_contents(__DIR__ . '/asset_id_normalization_map.json', json_encode($mappings, JSON_PRETTY_PRINT));
