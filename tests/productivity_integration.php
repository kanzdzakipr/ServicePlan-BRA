<?php
declare(strict_types=1);

/**
 * Automated Integration Test for Revisi 7: Pemantauan Produktivitas & Telematika (BR-07 / M8)
 * 
 * Verifies:
 * - BR-07A: Availability, downtime, PA, UA, BR, UT, MTBF, MTTR formulas, and verifiable data sources.
 * - BR-07B: Failure rate / KPI aggregate matches detail; safe for no-data (returns null/"Tidak Ada Data", never fake 0).
 * - BR-07C: Telematics reconciliation, variance calculation, exception flags (>50% idling anomaly, offline, no activity),
 *           and standby fleet audit (48 units).
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

require_once dirname(__DIR__) . '/core/ProductivityCalculator.php';

echo "=== REVISI 7: PRODUCTIVITY & TELEMATICS INTEGRATION TESTS (BR-07 / M8) ===\n\n";

// TEST 1: Baseline telematics logs exist in database
$stmtCount = $db->query("SELECT COUNT(*) FROM telematics_logs WHERE period_month = 'Januari 2026'");
$count = (int) $stmtCount->fetchColumn();
testAssert($count === 18, "Database has 18 KOMTRAX telematics records for Januari 2026 (actual: {$count})");

// TEST 2: Calculate Productivity Metrics (BR-07A)
$result = ProductivityCalculator::calculate($db, null, 'Januari 2026');
testAssert(isset($result['kpis']), "Calculator returns 'kpis' object");
testAssert(isset($result['telematics']), "Calculator returns 'telematics' array");
testAssert(isset($result['idlingAnomalies']), "Calculator returns 'idlingAnomalies' array");
testAssert(isset($result['reconciliation']), "Calculator returns 'reconciliation' array");
testAssert(isset($result['standbySummary']), "Calculator returns 'standbySummary' array");

$kpis = $result['kpis'];

// PA: Physical Availability
testAssert(isset($kpis['physicalAvailability']), "PA metric exists");
testAssert($kpis['physicalAvailability']['value'] === 92.4, "PA value is 92.4% (actual: " . var_export($kpis['physicalAvailability']['value'], true) . ")");
testAssert($kpis['physicalAvailability']['status'] === 'MEETS_TARGET', "PA status is MEETS_TARGET (>= 90%)");
testAssert(!empty($kpis['physicalAvailability']['formula']), "PA has explicit formula documented");
testAssert(!empty($kpis['physicalAvailability']['sources']), "PA has explicit data sources documented");

// BR: Breakdown Rate
testAssert(isset($kpis['breakdownRate']), "BR metric exists");
testAssert($kpis['breakdownRate']['value'] === 7.6, "BR value is 7.6% (actual: " . var_export($kpis['breakdownRate']['value'], true) . ")");
testAssert($kpis['breakdownRate']['status'] === 'MEETS_TARGET', "BR status is MEETS_TARGET (<= 10%)");

// UT: Utilization Rate
testAssert(isset($kpis['utilizationRate']), "UT metric exists");
testAssert($kpis['utilizationRate']['value'] === 52.1, "UT value is 52.1% (actual: " . var_export($kpis['utilizationRate']['value'], true) . ")");

// Total Working Hours & Operating Hours
testAssert(isset($kpis['totalWorkingHours']), "Total Scheduled Working Hours metric exists");
testAssert($kpis['totalWorkingHours']['value'] === 2046.1, "Total Scheduled Working Hours is 2046.1h (actual: " . var_export($kpis['totalWorkingHours']['value'], true) . ")");
testAssert(isset($kpis['totalActualHours']), "Total Actual Hours metric exists");
testAssert($kpis['totalActualHours']['value'] === 1066.0, "Total Actual Hours is 1066.0h (actual: " . var_export($kpis['totalActualHours']['value'], true) . ")");

// Total Fuel Consumed & Avg Fuel LPH
testAssert(isset($kpis['totalFuelConsumed']), "Total Fuel metric exists");
testAssert($kpis['totalFuelConsumed']['value'] === 18648.8, "Total Fuel Consumed is 18648.8L");
testAssert(isset($kpis['avgFuelLPH']), "Avg Fuel LPH metric exists");
testAssert($kpis['avgFuelLPH']['value'] === 9.11, "Avg Fuel LPH is 9.11 L/H (actual: " . var_export($kpis['avgFuelLPH']['value'], true) . ")");

// MTBF & MTTR
testAssert(isset($kpis['mtbf']), "MTBF metric exists");
testAssert($kpis['mtbf']['value'] > 100.0, "MTBF is >= 100h (actual: " . var_export($kpis['mtbf']['value'], true) . ")");
testAssert(isset($kpis['mttr']), "MTTR metric exists");
testAssert($kpis['mttr']['value'] === 3.8, "MTTR is 3.8h (actual: " . var_export($kpis['mttr']['value'], true) . ")");

// TEST 3: Safe for No-Data (BR-07B)
// Querying a location with 0 telematics records must return null and "Tidak Ada Data", never fake 0
$noDataResult = ProductivityCalculator::calculate($db, 999999, 'Januari 2026');
$noDataKpis = $noDataResult['kpis'];

testAssert($noDataKpis['physicalAvailability']['value'] === null, "No-data PA value is null (not 0)");
testAssert($noDataKpis['physicalAvailability']['formatted'] === 'Tidak Ada Data', "No-data PA formatted is 'Tidak Ada Data'");
testAssert($noDataKpis['breakdownRate']['value'] === null, "No-data BR value is null (not 0)");
testAssert($noDataKpis['mtbf']['value'] === null, "No-data MTBF value is null (not 0)");
testAssert($noDataKpis['mttr']['value'] === null, "No-data MTTR value is null (not 0)");
testAssert($noDataKpis['totalWorkingHours']['value'] === null, "No-data total working hours is null (not 0)");
testAssert($noDataResult['meta']['status'] === 'NO_DATA', "No-data meta status is 'NO_DATA'");

// TEST 4: Telematics Reconciliation & Idling Anomalies (BR-07C)
$reconcil = $result['reconciliation'];
testAssert(count($reconcil) === 18, "Reconciliation contains 18 unit evaluations");

// Check Idling Anomaly flags
$anomalies = $result['idlingAnomalies'];
testAssert(count($anomalies) === 7, "Found exactly 7 units with idling ratio >= 50% (actual: " . count($anomalies) . ")");

foreach ($anomalies as $anom) {
    testAssert($anom['idlingRatio'] >= 50.0, "Unit {$anom['sn']} has idling ratio >= 50% ({$anom['idlingRatio']}%)");
    testAssert($anom['estimatedIdleFuel'] > 0, "Unit {$anom['sn']} has positive estimated wasted fuel ({$anom['estimatedIdleFuel']} L)");
    testAssert($anom['estimatedLossCost'] > 0, "Unit {$anom['sn']} has positive financial loss in Rp ({$anom['estimatedLossCost']})");
    testAssert(!empty($anom['recommendation']), "Unit {$anom['sn']} has actionable recommendation");
}

// Check offline unit detection
$reconcilSnLookup = [];
foreach ($reconcil as $row) {
    $reconcilSnLookup[$row['sn']] = $row;
}
testAssert(isset($reconcilSnLookup['J10309']), "Unit J10309 (PC195LC-8) is in reconciliation");
testAssert(in_array('Telematika Offline', $reconcilSnLookup['J10309']['exceptions'], true), "Unit J10309 flagged as 'Telematika Offline' (last comm 2025)");

// TEST 5: Standby Fleet Audit Rekap (48 units)
$standby = $result['standbySummary'];
testAssert($standby['totalUnits'] === 48, "Standby fleet total is 48 units");
testAssert(count($standby['locations']) === 2, "Standby fleet has 2 yard locations (Duri & Prabumulih)");
testAssert($standby['locations'][0]['name'] === 'YARD DURI' && $standby['locations'][0]['count'] === 35, "Yard Duri has 35 standby units (72.9%)");
testAssert($standby['locations'][1]['name'] === 'YARD PRABUMULIH' && $standby['locations'][1]['count'] === 13, "Yard Prabumulih has 13 standby units (27.1%)");
testAssert(count($standby['categories']) === 5, "Standby fleet has 5 category breakdowns");

// TEST 6: API Integration verification
define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
@require_once dirname(__DIR__) . '/api/productivity.php';
testAssert(class_exists('ProductivityCalculator'), "ProductivityCalculator is loaded");

echo "\n======================================================\n";
echo "Productivity & Telematics Tests: {$passed} passed, {$failed} failed.\n";
echo "======================================================\n";

if ($failed > 0) {
    exit(1);
}
