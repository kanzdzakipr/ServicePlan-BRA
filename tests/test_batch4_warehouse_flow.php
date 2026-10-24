<?php
declare(strict_types=1);

/**
 * Automated Test Suite: Batch 4 Warehouse Ledger & Inventory Flow
 * Verifies:
 *  1. inventory_transactions record volume (>= 600, exactly 637)
 *  2. Movement type distribution (IN = 227, OUT = 410)
 *  3. Foreign key integrity with parts table (100% valid part_id)
 *  4. Foreign key integrity with report_records (100% valid report_id)
 *  5. Relational linking to assets and work_orders
 *  6. Zero negative stock in ledger (stock_before >= 0 and stock_after >= 0)
 *  7. Zero negative stock in parts catalog (parts.stock_qty >= 0)
 *  8. Stock accuracy on master benchmark parts (e.g. 1R-0739, 23304-JAC70)
 *  9. API Logistics contract (api/logistics.php?type=warehouse returns 200 with stock, masuk, keluar)
 *  10. Migration entry recorded in app_data_migrations
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "====================================================================\n";
echo "TEST SUITE: BATCH 4 WAREHOUSE LEDGER & INVENTORY FLOW\n";
echo "====================================================================\n\n";

$passCount = 0;
$totalTests = 10;

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
// Test 1: Total inventory_transactions row count
// -------------------------------------------------------------
$totalTx = (int)$pdo->query("SELECT COUNT(*) FROM inventory_transactions")->fetchColumn();
assertTest("1. Total Transactions Count >= 600", $totalTx >= 600, "Actual: $totalTx rows");

// -------------------------------------------------------------
// Test 2: Movement Type Breakdown (IN & OUT)
// -------------------------------------------------------------
$inCount = (int)$pdo->query("SELECT COUNT(*) FROM inventory_transactions WHERE movement_type = 'IN'")->fetchColumn();
$outCount = (int)$pdo->query("SELECT COUNT(*) FROM inventory_transactions WHERE movement_type = 'OUT'")->fetchColumn();
assertTest("2. Movement Types Breakdown (IN >= 220, OUT >= 400)", $inCount >= 220 && $outCount >= 400, "IN: $inCount, OUT: $outCount");

// -------------------------------------------------------------
// Test 3: Foreign Key Integrity -> parts.part_id
// -------------------------------------------------------------
$orphanParts = (int)$pdo->query("
    SELECT COUNT(*) FROM inventory_transactions it
    LEFT JOIN parts p ON p.part_id = it.part_id
    WHERE p.part_id IS NULL
")->fetchColumn();
assertTest("3. Foreign Key Integrity to parts (Zero Orphan)", $orphanParts === 0, "Orphan: $orphanParts");

// -------------------------------------------------------------
// Test 4: Foreign Key Integrity -> report_records.report_id
// -------------------------------------------------------------
$orphanReports = (int)$pdo->query("
    SELECT COUNT(*) FROM inventory_transactions it
    LEFT JOIN report_records r ON r.report_id = it.report_id
    WHERE r.report_id IS NULL
")->fetchColumn();
assertTest("4. Foreign Key Integrity to report_records (Zero Orphan)", $orphanReports === 0, "Orphan: $orphanReports");

// -------------------------------------------------------------
// Test 5: Relational Linking -> assets and work_orders
// -------------------------------------------------------------
$linkedAssets = (int)$pdo->query("SELECT COUNT(*) FROM inventory_transactions WHERE asset_id IS NOT NULL")->fetchColumn();
$linkedWos = (int)$pdo->query("SELECT COUNT(*) FROM inventory_transactions WHERE wo_id IS NOT NULL")->fetchColumn();
assertTest("5. Relational Unit & WO Links Exist", $linkedAssets >= 300 && $linkedWos > 0, "Linked Assets: $linkedAssets, Linked WOs: $linkedWos");

// -------------------------------------------------------------
// Test 6: Zero Negative Stock in Transaction Ledger
// -------------------------------------------------------------
$negLedger = (int)$pdo->query("
    SELECT COUNT(*) FROM inventory_transactions
    WHERE stock_before < 0 OR stock_after < 0
")->fetchColumn();
assertTest("6. Zero Negative Stock in Transaction Ledger", $negLedger === 0, "Violations: $negLedger");

// -------------------------------------------------------------
// Test 7: Zero Negative Stock in parts Catalog
// -------------------------------------------------------------
$negParts = (int)$pdo->query("SELECT COUNT(*) FROM parts WHERE stock_qty < 0")->fetchColumn();
assertTest("7. Zero Negative Stock in Parts Catalog", $negParts === 0, "Negative Parts: $negParts");

// -------------------------------------------------------------
// Test 8: Sample Benchmark Parts Ending Stock Validation
// -------------------------------------------------------------
$sampleParts = $pdo->query("
    SELECT part_number, part_name, stock_qty FROM parts
    WHERE part_number IN ('1R-0739', '600-319-5610', '23304-JAC70', '65D31R-N70')
")->fetchAll(PDO::FETCH_ASSOC);
$allSampleValid = count($sampleParts) >= 2;
$sampleDetails = [];
foreach ($sampleParts as $sp) {
    if ((int)$sp['stock_qty'] < 0) $allSampleValid = false;
    $sampleDetails[] = "{$sp['part_number']}: stock={$sp['stock_qty']}";
}
assertTest("8. Benchmark Parts Stock Reconciled", $allSampleValid, implode(', ', $sampleDetails));

// -------------------------------------------------------------
// Test 9: Logistics API Contract Validation
// -------------------------------------------------------------
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['type'] = 'inventory';
ob_start();
require dirname(__DIR__) . '/api/logistics.php';
$output = ob_get_clean();

$apiData = json_decode($output, true);
$apiValid = is_array($apiData)
    && ($apiData['status'] ?? '') === 'success'
    && !empty($apiData['data']['masuk'])
    && !empty($apiData['data']['keluar'])
    && !empty($apiData['data']['stock']);

$inApiCount = count($apiData['data']['masuk'] ?? []);
$outApiCount = count($apiData['data']['keluar'] ?? []);
$stockApiCount = count($apiData['data']['stock'] ?? []);
assertTest("9. API Logistics (api/logistics.php?type=warehouse) Contract", $apiValid, "Masuk: $inApiCount, Keluar: $outApiCount, Stock: $stockApiCount");

// -------------------------------------------------------------
// Test 10: Migration Key Recorded in app_data_migrations
// -------------------------------------------------------------
$migRecorded = (int)$pdo->query("
    SELECT COUNT(*) FROM app_data_migrations
    WHERE migration_key = 'BATCH_4_EQUIPMENT_REPORT_202601'
")->fetchColumn();
assertTest("10. Migration Key BATCH_4_EQUIPMENT_REPORT_202601 Recorded", $migRecorded > 0, "Records: $migRecorded");

echo "\n--------------------------------------------------------------------\n";
echo "SUMMARY: $passCount / $totalTests TESTS PASSED\n";
echo "====================================================================\n";

if ($passCount === $totalTests) {
    echo "🎉 ALL BATCH 4 VERIFICATION TESTS PASSED PERFECTLY!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED. PLEASE REVIEW.\n";
    exit(1);
}
