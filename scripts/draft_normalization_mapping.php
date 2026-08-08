<?php
$mysqli = new mysqli('localhost', 'root', '', 'u646470441_ServicePlanBRA');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

$res = $mysqli->query("SELECT asset_id, asset_code, license_plate, serial_number, category, equipment_type, make_model FROM assets ORDER BY category, asset_id");
$assets = [];
while ($r = $res->fetch_assoc()) {
    $assets[] = $r;
}

$standardPattern = '/^[A-Z]{2,5}-\d{4,5}$/';

$existingIds = [];
foreach ($assets as $a) {
    $existingIds[$a['asset_id']] = true;
}

$prefixCounters = [
    'DT' => 100, // Dump Truck
    'EXC' => 10, // Excavator
    'DZ' => 10,  // Bulldozer
    'MG' => 10,  // Motor Grader
    'PF' => 10,  // Vibro Compactor
    'WT' => 10,  // Water Truck
    'PM' => 10,  // Prime Mover
    'LV' => 30,  // Light Vehicle
    'FBT' => 10, // Trado / Heavy Hauler
    'RM' => 10,  // Reclaimer / Spreader
    'TMC' => 10, // Truck Mounted Crane
    'FTK' => 1,  // Fuel Tanker / Fuel Truck
    'ML' => 10,  // Mobile Lab
    'SB' => 10,  // Stone Breaker
    'EQP' => 10  // General Support Equipment
];

echo "Total assets in database: " . count($assets) . "\n";
