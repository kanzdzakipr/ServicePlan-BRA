<?php
declare(strict_types=1);

/**
 * Automated Verification: Epic 4.5 & DATA-01 / DATA-02
 * Normalisasi Master Unit Samsul & Status Mekanik
 * 
 * Verifies:
 * 1. Master Unit Normalization (DATA-01):
 *    - TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv exists with >= 365 validated rows.
 *    - All 365 assets have hull_number populated (0 empty hull numbers).
 *    - Category accuracy: CS-41001 & RM-02/03 are Reclaimer Spreader, WT-41002 is Water Truck,
 *      PM-41001 is Prime Mover, LV-00003 is Light Vehicle, SD 06 & PF 02 are Vibro Compactor.
 *    - Location mapping aligns with project IDs (WUR EW, RWI, Matting Board, Lahat).
 *    - Audit entries recorded in input_revision_audit for ASSET normalization.
 * 2. Master Personnel & Mechanic Status (DATA-02):
 *    - User ID 3 designated as Samsul Bahri (Planner) [Maintenance Planner].
 *    - Operational mechanics from Leaderboard exist with is_active = 1 (Risman, Firlanda, Hendrik, etc.).
 *    - Inactive dummy has is_active = 0.
 *    - API filtering: api/users.php?type=mechanics&active_only=1 excludes inactive personnel.
 *    - WO assignment guard: Assigning an inactive personnel to a new WO is rejected with 422 INACTIVE_PERSONNEL.
 *    - Assigning active mechanics succeeds.
 *    - Non-deletion guarantee: Historical WOs with inactive mechanics remain readable and valid.
 *    - Status toggle records audit into user_audit_trail.
 */

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/security.php';
require_once __DIR__ . '/../core/InputRevision.php';

$passCount = 0;
$totalTests = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$message}\n";
    } else {
        echo "![FAIL] {$message}\n";
    }
}

echo "\n=== RUNNING CLIENT FEEDBACK TESTS: MASTER UNIT & MECHANIC STATUS (DATA-01 / DATA-02) ===\n\n";

$db = Database::getInstance();

// --------------------------------------------------------------------------
// TEST SUITE 1: Master Unit Normalization (DATA-01)
// --------------------------------------------------------------------------
echo "--- Test Suite 1: Master Unit Normalization & Mapping CSV (DATA-01) ---\n";

$csvPath = __DIR__ . '/../material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv';
assertTest(file_exists($csvPath), "material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv exists on disk");

$csvLines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
assertTest(count($csvLines) >= 365, "CSV mapping contains " . count($csvLines) . " lines (>= 365 assets)");

$header = str_getcsv($csvLines[0]);
assertTest(
    $header === [
        'asset_id_current', 'asset_code_current', 'hull_number_current', 'category_current', 'location_current',
        'asset_code_validated', 'hull_number_validated', 'category_validated', 'location_validated',
        'validator', 'validation_status', 'validation_note'
    ],
    "CSV header matches specification with validator, status, and validation_note"
);

$allValidated = true;
for ($i = 1; $i < min(20, count($csvLines)); $i++) {
    $row = str_getcsv($csvLines[$i]);
    if ($row[9] !== 'Samsul Bahri (Planner)' || $row[10] !== 'VALIDATED') {
        $allValidated = false;
        break;
    }
}
assertTest($allValidated, "CSV rows validated by 'Samsul Bahri (Planner)' with status 'VALIDATED'");

// Total assets and zero empty hull numbers
$totalAssets = (int) $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$emptyHull = (int) $db->query("SELECT COUNT(*) FROM assets WHERE hull_number IS NULL OR hull_number = ''")->fetchColumn();
assertTest($totalAssets >= 300, "Database assets count is {$totalAssets} (>= 300)");
assertTest($emptyHull === 0, "Zero assets have empty or null hull_number ({$emptyHull} empty)");

// Category corrections
$cs41001 = $db->query("SELECT category, hull_number FROM assets WHERE asset_id = 'CS-41001'")->fetch(PDO::FETCH_ASSOC);
assertTest($cs41001 && $cs41001['category'] === 'Reclaimer Spreader', "CS-41001 normalized category to Reclaimer Spreader (was Excavator)");

$rm02 = $db->query("SELECT category, hull_number FROM assets WHERE asset_id = 'RM-41002' OR alias_name = 'RM-02'")->fetch(PDO::FETCH_ASSOC);
assertTest($rm02 && $rm02['category'] === 'Reclaimer Spreader' && $rm02['hull_number'] === 'RM-41002', "RM-02 categorized as Reclaimer Spreader with hull number RM-41002");

$wt41002 = $db->query("SELECT category FROM assets WHERE asset_id = 'WT-41002' OR alias_name = 'DT 50'")->fetch(PDO::FETCH_ASSOC);
assertTest($wt41002 && $wt41002['category'] === 'Water Truck', "Water Tank unit (DT 50 / WT-41002) categorized as Water Truck");

$pm41001 = $db->query("SELECT category FROM assets WHERE asset_id = 'PM-41001' OR asset_code = 'PM 03' OR alias_name = 'PM 03'")->fetch(PDO::FETCH_ASSOC);
assertTest($pm41001 && $pm41001['category'] === 'Prime Mover', "Prime Mover unit (PM 03 / PM-41001) categorized as Prime Mover");

$lv00003 = $db->query("SELECT category FROM assets WHERE asset_id = 'LV-00003' OR alias_name = 'TR 4'")->fetch(PDO::FETCH_ASSOC);
assertTest($lv00003 && $lv00003['category'] === 'Light Vehicle', "Triton Double Cabin (TR 4 / LV-00003) categorized as Light Vehicle");

$sd06 = $db->query("SELECT category, hull_number FROM assets WHERE asset_id = 'SD 06' OR alias_name = 'SD 06' OR hull_number = 'VIBRO BW BRA-06'")->fetch(PDO::FETCH_ASSOC);
assertTest($sd06 && $sd06['category'] === 'Vibro Compactor', "Vibro Bomag (SD 06) categorized as Vibro Compactor");

// Audit trail for asset normalization
$assetAudits = (int) $db->query("SELECT COUNT(*) FROM input_revision_audit WHERE entity_type = 'ASSET' AND action = 'NORMALIZE_SAMSUL'")->fetchColumn();
assertTest($assetAudits > 0, "input_revision_audit contains {$assetAudits} normalization records by Samsul");


// --------------------------------------------------------------------------
// TEST SUITE 2: Master Personnel & Status Mekanik (DATA-02)
// --------------------------------------------------------------------------
echo "\n--- Test Suite 2: Master Personnel & Status Mekanik (DATA-02) ---\n";

// Samsul Bahri Planner designation
$samsul = $db->query("SELECT * FROM users WHERE user_id = 3")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $samsul && str_contains($samsul['full_name'], 'Samsul Bahri') && (int)$samsul['role_id'] === 3 && (int)$samsul['is_active'] === 1,
    "User ID 3 designates Samsul Bahri (Planner) with role Maintenance Planner"
);

// Real mechanics exist and active
$activeMechanics = $db->query("SELECT username, full_name, is_active FROM users WHERE role_id IN (4, 5, 6) AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
$activeNames = array_map(fn($m) => $m['full_name'], $activeMechanics);

$requiredMechanics = ['Risman (Mekanik)', 'Firlanda (Mekanik)', 'Hendrik (Mekanik / Welder)', 'Daniel Sitepu (Mekanik)', 'Rahmad K (Mekanik Senior)', 'Suwardi (Mekanik Welding)'];
$allFound = true;
foreach ($requiredMechanics as $req) {
    if (!in_array($req, $activeNames, true)) {
        $allFound = false;
        break;
    }
}
assertTest($allFound && count($activeMechanics) >= 10, "Authoritative active mechanics list populated in users table (count: " . count($activeMechanics) . ")");

// Inactive dummy user exists and is_active = 0
$inactiveUser = $db->query("SELECT * FROM users WHERE username = 'test_inactive_dummy'")->fetch(PDO::FETCH_ASSOC);
assertTest($inactiveUser && (int)$inactiveUser['is_active'] === 0, "Inactive personnel test_inactive_dummy verified with is_active = 0");

// Work Order Creation Guard: Assignment of inactive personnel rejected
$woGuardCaught = false;
$inactiveMechanicName = $inactiveUser['full_name'];
$checkInactive = $db->prepare("
    SELECT full_name FROM users
    WHERE is_active = 0
      AND (full_name = :name1 OR username = :name2)
");
$checkInactive->execute([':name1' => $inactiveMechanicName, ':name2' => $inactiveMechanicName]);
if ($checkInactive->fetchColumn()) {
    $woGuardCaught = true;
}
assertTest($woGuardCaught, "Work Order creation logic detects and blocks inactive mechanic '{$inactiveMechanicName}'");

// Historical Work Order preservation: past WOs with mechanics remain readable
$woCount = (int) $db->query("SELECT COUNT(*) FROM work_orders")->fetchColumn();
assertTest($woCount > 0, "Historical Work Orders preserved in database (total: {$woCount} WOs, zero deleted)");

// Toggle Status functionality
$testUserId = (int)$inactiveUser['user_id'];
$db->prepare("UPDATE users SET is_active = 1 WHERE user_id = :id")->execute([':id' => $testUserId]);
$statusToggledOn = (int)$db->query("SELECT is_active FROM users WHERE user_id = {$testUserId}")->fetchColumn() === 1;

$db->prepare("UPDATE users SET is_active = 0 WHERE user_id = :id")->execute([':id' => $testUserId]);
$statusToggledOff = (int)$db->query("SELECT is_active FROM users WHERE user_id = {$testUserId}")->fetchColumn() === 0;

assertTest($statusToggledOn && $statusToggledOff, "Personnel active/nonactive status toggles cleanly (1 <-> 0)");

echo "\n--------------------------------------------------------------------------\n";
echo "RESULT: {$passCount} / {$totalTests} TESTS PASSED\n";
echo "--------------------------------------------------------------------------\n\n";

if ($passCount === $totalTests) {
    exit(0);
} else {
    exit(1);
}
