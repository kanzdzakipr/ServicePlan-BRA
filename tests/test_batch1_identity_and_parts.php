<?php
declare(strict_types=1);

/**
 * Automated Test: Batch 1 Identity & Parts Verification
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();

echo "============================================================================\n";
echo " RUNNING TEST: BATCH 1 IDENTITY & PARTS VERIFICATION\n";
echo "============================================================================\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $message\n";
        $passCount++;
    } else {
        echo "  [FAIL] $message\n";
        $failCount++;
    }
}

// 1. Check uniqueness of part_number
$totalParts = (int)$db->query("SELECT COUNT(*) FROM parts")->fetchColumn();
$distinctPn = (int)$db->query("SELECT COUNT(DISTINCT part_number) FROM parts")->fetchColumn();
assertTest($totalParts === $distinctPn, "Zero Duplicate Part Numbers in `parts` ($totalParts total, $distinctPn distinct)");
assertTest($totalParts >= 1390, "Total Parts catalog expanded to >= 1390 items (Current: $totalParts)");

// 2. Check general hardware SKU presence
$genHwCount = (int)$db->query("SELECT COUNT(*) FROM parts WHERE part_number LIKE 'GEN-HW-%'")->fetchColumn();
assertTest($genHwCount >= 627, "Standardized General Hardware SKU (GEN-HW-xxxx) count is >= 627 (Current: $genHwCount)");

// 3. Check categories valid enum
$allowedCategories = ['Filter','Hose','Cutting Bit','Tire','Battery','Fast Moving','Engine Part','Hydraulic','Other'];
$invalidCats = $db->query("SELECT DISTINCT category FROM parts WHERE category NOT IN ('" . implode("','", $allowedCategories) . "')")->fetchAll(PDO::FETCH_COLUMN);
assertTest(empty($invalidCats), "All part categories conform to database ENUM specification");

// 4. Check asset alias updates
$checkVibro = $db->query("SELECT alias_name, hull_number, serial_number FROM assets WHERE asset_id = 'PF-00001'")->fetch(PDO::FETCH_ASSOC);
assertTest(($checkVibro['alias_name'] ?? '') === 'PF 01', "Vibro PF-00001 alias is 'PF 01'");
assertTest(($checkVibro['hull_number'] ?? '') === 'BOMAG FOOT (01)', "Vibro PF-00001 hull_number is 'BOMAG FOOT (01)'");

$checkExca = $db->query("SELECT alias_name, hull_number FROM assets WHERE asset_id = 'EXC-00001'")->fetch(PDO::FETCH_ASSOC);
assertTest(($checkExca['alias_name'] ?? '') === 'EXCA 01', "Excavator EXC-00001 alias is 'EXCA 01'");

$checkDozer = $db->query("SELECT alias_name, hull_number FROM assets WHERE asset_id = 'DZ-00001'")->fetch(PDO::FETCH_ASSOC);
assertTest(($checkDozer['alias_name'] ?? '') === 'DOZER 01', "Bulldozer DZ-00001 alias is 'DOZER 01'");

$checkSpreader = $db->query("SELECT alias_name, hull_number FROM assets WHERE asset_id = 'SUP-41001'")->fetch(PDO::FETCH_ASSOC);
assertTest(($checkSpreader['hull_number'] ?? '') === 'SPREADER (01)', "Spreader SUP-41001 hull_number is 'SPREADER (01)'");

// 5. Test search autocomplete query emulation
$stmtSearch = $db->prepare("SELECT asset_id, asset_code, alias_name, hull_number FROM assets WHERE alias_name LIKE :q1 OR hull_number LIKE :q2 LIMIT 5");
$stmtSearch->execute([':q1' => '%SD 01%', ':q2' => '%SD 01%']);
$searchResults = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
assertTest(!empty($searchResults), "Asset search autocomplete successfully finds unit by operational alias 'SD 01'");

echo "============================================================================\n";
echo " TEST SUMMARY: $passCount PASSED, $failCount FAILED\n";
echo "============================================================================\n";

if ($failCount > 0) {
    exit(1);
}
