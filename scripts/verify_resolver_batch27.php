<?php
define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/analyze_lp_deep.php';
require_once __DIR__ . '/lp_bdown_asset_resolver.php';

$pdo = Database::getInstance();
$resolver = new LpAssetResolver($pdo);

$baseDir = dirname(__DIR__) . '/material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI';
$c1 = parseCellMarkdown($baseDir . '/01_des_25_peb_26.md');

$totalValid = 0;
$resolvedCount = 0;
$unresolved = [];

foreach ($c1 as $r => $cols) {
    if (!isset($cols['F']) || !isset($cols['J'])) continue;
    $rawUnit = $cols['F']['val'];
    $dateVal = $cols['J']['val'];
    if (!is_numeric($dateVal) || $dateVal < 40000) continue;
    $totalValid++;
    
    $resolved = $resolver->resolve($rawUnit);
    if ($resolved !== null && $resolver->getAssetInfo($resolved) !== null) {
        $resolvedCount++;
    } else {
        $unresolved[$rawUnit] = ($unresolved[$rawUnit] ?? 0) + 1;
    }
}

echo "=== RESOLVER VERIFICATION REPORT ===\n";
echo "Total Valid Event Rows: $totalValid\n";
echo "Successfully Resolved: $resolvedCount (" . round($resolvedCount / $totalValid * 100, 2) . "%)\n";
echo "Unresolved: " . count($unresolved) . "\n";
if (count($unresolved) > 0) {
    print_r($unresolved);
} else {
    echo "🎯 SEMPURNA: 100.0% Unit Valid Terpetakan ke Canonical SSOT Assets!\n";
}

