<?php
$mysqli = new mysqli('localhost', 'root', '', 'u646470441_ServicePlanBRA');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

$res = $mysqli->query("SELECT asset_id, asset_code, license_plate, category FROM assets ORDER BY category, asset_id");
$assets = [];
while ($r = $res->fetch_assoc()) {
    $assets[] = $r;
}

$nonStandard = [];
foreach ($assets as $a) {
    if (!preg_match('/^[A-Z]{2,5}-\d{4,5}$/', $a['asset_id'])) {
        $nonStandard[] = $a;
    }
}

echo "Total assets in DB: " . count($assets) . "\n";
echo "Total non-standard asset_id remaining: " . count($nonStandard) . "\n";

if (count($nonStandard) > 0) {
    echo "Remaining non-standard IDs:\n";
    foreach ($nonStandard as $ns) {
        echo " - " . $ns['asset_id'] . " (" . $ns['category'] . ")\n";
    }
} else {
    echo "SUCCESS: 100% of asset_id records match standard prefix rules (e.g. DT-00001, EXC-00001, LV-00028, etc.)!\n";
}

// Sample breakdown of asset prefixes
$resPrefix = $mysqli->query("SELECT SUBSTRING_INDEX(asset_id, '-', 1) AS prefix, COUNT(*) AS cnt FROM assets GROUP BY prefix ORDER BY cnt DESC");
echo "\nAsset Count Breakdown by Standard Prefix:\n";
while ($r = $resPrefix->fetch_assoc()) {
    printf(" - %-6s : %d units\n", $r['prefix'], $r['cnt']);
}
