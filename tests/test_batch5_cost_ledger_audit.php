<?php
declare(strict_types=1);

/**
 * Automated Test Suite: Batch 5 Equipment Cost Ledger & Financial Monthly Audit
 *
 * Verifies:
 *  1. cost_financial_monthly has Januari 2026 executive total (actual_amount = 680276164.00)
 *  2. cost_financial_monthly has category breakdowns (Corrective, Preventive, SDM, Operasional)
 *  3. asset_cost_transactions has 60 authentic unit-level records for January 2026
 *  4. Foreign key integrity: 100% valid asset_id in assets table (zero orphan)
 *  5. Relational linking: work order links exist (wo_id is populated for active breakdown units)
 *  6. Financial integrity: zero negative or zero amounts (amount > 0)
 *  7. Total unit expenditure matches exact report sum (Rp 309.505.450,00)
 *  8. Benchmark machinery unit costs match exact source reports (PF-00005, DZ-00001, EXC-00001, LV-00018)
 *  9. API Logistics (api/logistics.php?type=costs) contract includes Januari 2026
 *  10. Core CostLedger service (CostLedger::getTransactions) returns active ledger records
 *  11. Migration key BATCH_5_EQUIPMENT_REPORT_202601 recorded in app_data_migrations
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';
require_once dirname(__DIR__) . '/core/CostLedger.php';

$pdo = Database::getInstance();

echo "====================================================================\n";
echo "TEST SUITE: BATCH 5 EQUIPMENT COST LEDGER & FINANCIAL MONTHLY\n";
echo "====================================================================\n\n";

$passCount = 0;
$totalTests = 11;

function assertTest(string $name, bool $condition, string $detail = ''): void {
    global $passCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] $name" . ($detail ? " ($detail)" : "") . "\n";
    } else {
        echo "  [FAIL] $name" . ($detail ? " - $detail" : "") . "\n";
    }
}

// -------------------------------------------------------------
// Test 1: cost_financial_monthly executive total
// -------------------------------------------------------------
$janTotal = (float)$pdo->query("
    SELECT actual_amount FROM cost_financial_monthly
    WHERE month_label = 'Januari' AND year_period = 2026
")->fetchColumn();
assertTest("1. Monthly Executive Actual Amount Equals Rp 680.276.164,00", abs($janTotal - 680276164.00) < 0.01, "Actual: Rp " . number_format($janTotal, 2, ',', '.'));

// -------------------------------------------------------------
// Test 2: cost_financial_monthly category breakdowns
// -------------------------------------------------------------
$breakdownRows = (int)$pdo->query("
    SELECT COUNT(*) FROM cost_financial_monthly
    WHERE year_period = 2026 AND month_label IN ('Jan-Corrective', 'Jan-Preventive', 'Jan-SDM', 'Jan-Operasional')
")->fetchColumn();
assertTest("2. Category Breakdowns in cost_financial_monthly", $breakdownRows === 4, "Breakdown Rows: $breakdownRows");

// -------------------------------------------------------------
// Test 3: Total Unit-Level Transactions in asset_cost_transactions
// -------------------------------------------------------------
$unitTxCount = (int)$pdo->query("
    SELECT COUNT(*) FROM asset_cost_transactions
    WHERE transaction_date = '2026-01-31' AND cost_code LIKE 'COST-202601-%'
")->fetchColumn();
assertTest("3. Unit-Level Transactions Ingested (>= 60 records)", $unitTxCount >= 60, "Ingested: $unitTxCount rows");

// -------------------------------------------------------------
// Test 4: Foreign Key Integrity to assets table (Zero Orphan)
// -------------------------------------------------------------
$orphanAssets = (int)$pdo->query("
    SELECT COUNT(*) FROM asset_cost_transactions act
    LEFT JOIN assets a ON a.asset_id = act.asset_id
    WHERE act.cost_code LIKE 'COST-202601-%' AND a.asset_id IS NULL
")->fetchColumn();
assertTest("4. Foreign Key Integrity to assets (Zero Orphan)", $orphanAssets === 0, "Orphans: $orphanAssets");

// -------------------------------------------------------------
// Test 5: Relational Linking to Work Orders
// -------------------------------------------------------------
$linkedWos = (int)$pdo->query("
    SELECT COUNT(*) FROM asset_cost_transactions
    WHERE cost_code LIKE 'COST-202601-%' AND wo_id IS NOT NULL
")->fetchColumn();
assertTest("5. Work Order Linking for Active Breakdown Units", $linkedWos >= 30, "Linked to WO: $linkedWos");

// -------------------------------------------------------------
// Test 6: Zero Negative or Zero Amounts
// -------------------------------------------------------------
$invalidAmounts = (int)$pdo->query("
    SELECT COUNT(*) FROM asset_cost_transactions
    WHERE cost_code LIKE 'COST-202601-%' AND amount <= 0
")->fetchColumn();
assertTest("6. Financial Integrity (amount > 0)", $invalidAmounts === 0, "Invalid: $invalidAmounts");

// -------------------------------------------------------------
// Test 7: Total Direct Equipment Expenditure
// -------------------------------------------------------------
$totalDirectCost = (float)$pdo->query("
    SELECT SUM(amount) FROM asset_cost_transactions
    WHERE cost_code LIKE 'COST-202601-%'
")->fetchColumn();
$expectedSum = 309505450.00;
assertTest("7. Total Direct Equipment Expenditure Sums Exactly to Rp 309.505.450,00", abs($totalDirectCost - $expectedSum) < 0.01, "Actual: Rp " . number_format($totalDirectCost, 2, ',', '.'));

// -------------------------------------------------------------
// Test 8: Benchmark Unit Costs Validation
// -------------------------------------------------------------
$benchmarks = [
    'PF-00005' => 265000.00,     // Vibro SD 01
    'DZ-00001' => 14500000.00,   // Dozer 01
    'EXC-00001' => 12775000.00,  // Exca 01
    'LV-00018' => 1917776.00,    // Triton TR 1
];
$benchmarksPass = true;
$benchDetails = [];
foreach ($benchmarks as $aid => $expectedAmount) {
    $actual = (float)$pdo->query("
        SELECT amount FROM asset_cost_transactions
        WHERE asset_id = '$aid' AND cost_code LIKE 'COST-202601-%' LIMIT 1
    ")->fetchColumn();
    if (abs($actual - $expectedAmount) > 0.01) {
        $benchmarksPass = false;
    }
    $benchDetails[] = "$aid: Rp " . number_format($actual, 0, ',', '.');
}
assertTest("8. Benchmark Unit Costs Reconciled", $benchmarksPass, implode(', ', $benchDetails));

// -------------------------------------------------------------
// Test 9: Logistics API Contract (api/logistics.php?type=costs)
// -------------------------------------------------------------
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['type'] = 'costs';
ob_start();
require dirname(__DIR__) . '/api/logistics.php';
$output = ob_get_clean();

$apiData = json_decode($output, true);
$hasJan = false;
if (is_array($apiData) && ($apiData['status'] ?? '') === 'success' && is_array($apiData['data'])) {
    foreach ($apiData['data'] as $row) {
        if (($row['month_label'] ?? '') === 'Januari' && (int)($row['year_period'] ?? 0) === 2026) {
            $hasJan = true;
            break;
        }
    }
}
assertTest("9. API Logistics (api/logistics.php?type=costs) Contract", $hasJan, "Januari 2026 Present: " . ($hasJan ? "YES" : "NO"));

// -------------------------------------------------------------
// Test 10: Core CostLedger Service Retrieval
// -------------------------------------------------------------
$ledgerRes = CostLedger::getTransactions($pdo, ['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
$ledgerActiveCount = $ledgerRes['summary']['activeCount'] ?? 0;
assertTest("10. Core CostLedger::getTransactions() Active Retrieval", $ledgerActiveCount >= 60, "Retrieved Active: $ledgerActiveCount");

// -------------------------------------------------------------
// Test 11: Migration Key Recorded in app_data_migrations
// -------------------------------------------------------------
$migRecorded = (int)$pdo->query("
    SELECT COUNT(*) FROM app_data_migrations
    WHERE migration_key = 'BATCH_5_EQUIPMENT_REPORT_202601'
")->fetchColumn();
assertTest("11. Migration Key BATCH_5_EQUIPMENT_REPORT_202601 Recorded", $migRecorded > 0, "Records: $migRecorded");

echo "\n--------------------------------------------------------------------\n";
echo "SUMMARY: $passCount / $totalTests TESTS PASSED\n";
echo "====================================================================\n";

if ($passCount === $totalTests) {
    echo "🎉 ALL BATCH 5 VERIFICATION TESTS PASSED PERFECTLY!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED. PLEASE REVIEW.\n";
    exit(1);
}
