<?php
declare(strict_types=1);

/**
 * Automated Integration Test for Revisi 9: Pencatatan Biaya & Transaksi (BR-09A / M10)
 * 
 * Verifies:
 * - Table creation and baseline seed.
 * - Server-side aggregation, currency formatting, category breakdown.
 * - Drill-down traceability by Unit and Work Order.
 * - Validation of input data (asset, WO, amount, category).
 * - Audit-compliant reversal/void without deleting history.
 * - Location scope filtering.
 * - Role permission gating (cost.read, cost.write).
 */

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');

$passed = 0;
$failed = 0;

function testAssert(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$message}\n";
    } else {
        $failed++;
        fwrite(STDERR, "[FAIL] {$message}\n");
    }
}

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

require_once dirname(__DIR__) . '/core/CostLedger.php';

echo "=== REVISI 9: COST LEDGER & TRANSACTION INTEGRATION TESTS (BR-09A / M10) ===\n\n";

CostLedger::ensureTables($db);

// Cleanup existing QA test records if any
$db->exec("DELETE FROM asset_cost_transactions WHERE cost_code LIKE 'QA-BRIAN-%'");

// TEST 1: Table Creation and Baseline Seeding
CostLedger::ensureTables($db);
$initCount = (int) $db->query("SELECT COUNT(*) FROM asset_cost_transactions")->fetchColumn();
testAssert($initCount >= 5, "Database has baseline cost transactions (actual count: {$initCount})");

// TEST 2: Multi-criteria List & Server-Side Aggregation
$res = CostLedger::getTransactions($db, ['status' => 'all']);
testAssert(isset($res['transactions']) && is_array($res['transactions']), "Returns transactions array");
testAssert(isset($res['summary']), "Returns summary object");

$summary = $res['summary'];
testAssert($summary['totalActiveAmount'] > 0, "Active amount is positive: {$summary['formattedTotalActive']}");
testAssert($summary['activeCount'] >= 5, "Active count is >= 5 ({$summary['activeCount']})");
testAssert(isset($summary['byCategory']['Sparepart']), "Category breakdown contains 'Sparepart'");
testAssert(isset($summary['byCategory']['Maintenance']), "Category breakdown contains 'Maintenance'");
testAssert(isset($summary['byCategory']['External']), "Category breakdown contains 'External'");

// TEST 3: Traceability by Work Order (WO-26-101)
$woCost = CostLedger::getSummaryByWo($db, 'WO-26-101');
testAssert($woCost['woId'] === 'WO-26-101', "WO ID is WO-26-101");
testAssert($woCost['itemCount'] === 2, "WO-26-101 has exactly 2 cost line items");
testAssert($woCost['totalCost'] === 18350000.0, "WO-26-101 total cost is 18,350,000 (actual: {$woCost['totalCost']})");

// TEST 4: Traceability by Asset
$assetSummary = CostLedger::getSummaryByAsset($db);
testAssert(count($assetSummary) > 0, "Returns asset cost summary rows");
$dtRow = null;
foreach ($assetSummary as $a) {
    if ($a['asset_id'] === 'DT-00049' || $a['asset_code'] === 'DT-00049') {
        $dtRow = $a;
        break;
    }
}
testAssert($dtRow !== null, "Asset DT-00049 is present in cost summary");
testAssert($dtRow['totalActiveCost'] >= 18350000.0, "DT-00049 has cumulative cost >= 18,350,000");

// TEST 5: Create New Transaction (WO Linked)
$testUser = ['id' => 1, 'username' => 'qa_user', 'full_name' => 'QA Tester'];
$createdWo = CostLedger::createTransaction($db, [
    'cost_code' => 'QA-BRIAN-CST-01',
    'asset_id' => 'DT-00049',
    'wo_id' => 'WO-26-101',
    'cost_category' => 'Sparepart',
    'description' => 'QA Test - Pembelian O-Ring & Packing Kopling',
    'amount' => 1500000.0,
    'vendor_name' => 'PT Test Vendor QA',
    'reference_no' => 'PO-QA-001',
    'transaction_date' => date('Y-m-d'),
], $testUser);

testAssert($createdWo['costCode'] === 'QA-BRIAN-CST-01', "Created transaction has cost_code QA-BRIAN-CST-01");
testAssert($createdWo['amount'] === 1500000.0, "Created transaction amount is 1,500,000");

// Verify WO cost updated
$woCostUpdated = CostLedger::getSummaryByWo($db, 'WO-26-101');
testAssert($woCostUpdated['totalCost'] === 19850000.0, "WO-26-101 updated total cost is 19,850,000 (18.35M + 1.5M)");

// TEST 6: Create New Transaction (Non-WO)
$createdNonWo = CostLedger::createTransaction($db, [
    'cost_code' => 'QA-BRIAN-CST-02',
    'asset_id' => 'EXC-00001',
    'wo_id' => null,
    'cost_category' => 'Maintenance',
    'description' => 'QA Test - Biaya Maintenance Rutin Tanpa WO',
    'amount' => 2000000.0,
    'transaction_date' => date('Y-m-d'),
], $testUser);

testAssert($createdNonWo['costCode'] === 'QA-BRIAN-CST-02', "Created non-WO transaction has cost_code QA-BRIAN-CST-02");
testAssert($createdNonWo['woId'] === null, "Created non-WO transaction has null woId");

// TEST 7: Input Validation Errors
$caughtInvalidAsset = false;
try {
    CostLedger::createTransaction($db, [
        'asset_id' => 'NON-EXISTENT-ASSET-999',
        'cost_category' => 'Maintenance',
        'amount' => 500000.0,
        'description' => 'Test invalid asset'
    ], $testUser);
} catch (InvalidArgumentException $e) {
    $caughtInvalidAsset = true;
}
testAssert($caughtInvalidAsset, "Rejects transaction with non-existent asset_id");

$caughtInvalidAmount = false;
try {
    CostLedger::createTransaction($db, [
        'asset_id' => 'DT-00049',
        'cost_category' => 'Maintenance',
        'amount' => -100.0,
        'description' => 'Test negative amount'
    ], $testUser);
} catch (InvalidArgumentException $e) {
    $caughtInvalidAmount = true;
}
testAssert($caughtInvalidAmount, "Rejects transaction with non-positive amount");

$caughtInvalidCategory = false;
try {
    CostLedger::createTransaction($db, [
        'asset_id' => 'DT-00049',
        'cost_category' => 'INVALID_CAT',
        'amount' => 500000.0,
        'description' => 'Test invalid category'
    ], $testUser);
} catch (InvalidArgumentException $e) {
    $caughtInvalidCategory = true;
}
testAssert($caughtInvalidCategory, "Rejects transaction with invalid cost category");

// TEST 8: Audit-Compliant Reversal / Void (No Delete)
$revResult = CostLedger::reverseTransaction($db, $createdWo['transactionId'], "Salah input nominal oleh QA", $testUser);
testAssert($revResult['status'] === 'REVERSED', "Transaction status is REVERSED");
testAssert($revResult['reversalReason'] === "Salah input nominal oleh QA", "Reversal reason recorded");

// Verify row still exists in DB (not deleted)
$verifyStmt = $db->prepare("SELECT * FROM asset_cost_transactions WHERE transaction_id = :id");
$verifyStmt->execute([':id' => $createdWo['transactionId']]);
$rawRow = $verifyStmt->fetch();
testAssert($rawRow !== false, "Transaction row still exists in database (audit trail intact)");
testAssert($rawRow['reversed_at'] !== null, "reversed_at timestamp is set");
testAssert($rawRow['reversal_reason'] === "Salah input nominal oleh QA", "reversal_reason is saved in database");

// Verify WO cost recalculated excluding reversed transaction
$woCostAfterRev = CostLedger::getSummaryByWo($db, 'WO-26-101');
testAssert($woCostAfterRev['totalCost'] === 18350000.0, "WO total cost reverts back to 18,350,000 (voided amount excluded)");

// Double reversal attempt should fail
$caughtDoubleRev = false;
try {
    CostLedger::reverseTransaction($db, $createdWo['transactionId'], "Coba batalkan lagi", $testUser);
} catch (InvalidArgumentException $e) {
    $caughtDoubleRev = true;
}
testAssert($caughtDoubleRev, "Prevents double-reversal of already reversed transaction");

// TEST 9: Location Scoping
$locationSummary = CostLedger::getTransactions($db, [], 1); // Location 1: Yard Duri
testAssert($locationSummary['summary']['totalActiveAmount'] > 0, "Location 1 returns active transactions");

$nonExistentLoc = CostLedger::getTransactions($db, [], 999999);
testAssert($nonExistentLoc['summary']['totalActiveAmount'] === 0.0, "Non-existent location returns 0 active amount");
testAssert(count($nonExistentLoc['transactions']) === 0, "Non-existent location returns empty transactions array");

// TEST 10: RBAC / Permissions
define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
@require_once dirname(__DIR__) . '/api/security.php';

$plannerPerms = api_builtin_permissions_for_roles(['Maintenance Planner']);
testAssert(in_array('cost.read', $plannerPerms, true), "Maintenance Planner has 'cost.read' permission");
testAssert(in_array('cost.write', $plannerPerms, true), "Maintenance Planner has 'cost.write' permission");

$logisticPerms = api_builtin_permissions_for_roles(['Logistic Head']);
testAssert(in_array('cost.read', $logisticPerms, true), "Logistic Head has 'cost.read' permission");
testAssert(!in_array('cost.write', $logisticPerms, true), "Logistic Head does NOT have 'cost.write' permission");

$mechanicPerms = api_builtin_permissions_for_roles(['Mekanik Senior']);
testAssert(!in_array('cost.read', $mechanicPerms, true), "Mekanik Senior does NOT have 'cost.read' permission (financial data restricted)");
testAssert(!in_array('cost.write', $mechanicPerms, true), "Mekanik Senior does NOT have 'cost.write' permission");

// CLEANUP test records
$db->exec("DELETE FROM asset_cost_transactions WHERE cost_code LIKE 'QA-BRIAN-%'");

echo "\n======================================================\n";
echo "Cost Ledger & Transaction Tests: {$passed} passed, {$failed} failed.\n";
echo "======================================================\n";

if ($failed > 0) {
    exit(1);
}
