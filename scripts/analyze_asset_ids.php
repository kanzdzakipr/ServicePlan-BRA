<?php
$mysqli = new mysqli('localhost', 'root', '', 'u646470441_ServicePlanBRA');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

// Fetch all assets
$res = $mysqli->query("SELECT * FROM assets ORDER BY category, asset_id");
$assets = [];
while ($r = $res->fetch_assoc()) {
    $assets[] = $r;
}

$standardPattern = '/^[A-Z]{2,5}-\d{5}$/';
$nonStandard = [];

foreach ($assets as $a) {
    if (!preg_match($standardPattern, $a['asset_id'])) {
        $nonStandard[] = $a;
    }
}

echo "Total Non-standard asset_id: " . count($nonStandard) . "\n";
echo "========================================================================================\n";
printf("%-26s | %-16s | %-20s | %-30s | %s\n", "Asset ID", "License Plate", "Category", "Make Model", "Serial");
echo "----------------------------------------------------------------------------------------\n";
foreach ($nonStandard as $a) {
    printf("%-26s | %-16s | %-20s | %-30s | %s\n", 
        $a['asset_id'], 
        $a['license_plate'] ?? '-', 
        $a['category'] ?? '-', 
        substr($a['make_model'] ?? '-', 0, 30),
        $a['serial_number'] ?? '-'
    );
}
