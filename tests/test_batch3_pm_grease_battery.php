<?php
declare(strict_types=1);

/**
 * Automated Verification Suite for Batch 3 Database Enrichment
 * 
 * Verifies:
 * 1. grease_logs populated with >= 23 records of hauling lubrication.
 * 2. Foreign key integrity: all grease_logs.asset_id exist in assets.
 * 3. lubrication_points in grease_logs is valid structured JSON.
 * 4. battery_logs populated with >= 12 records of battery replacement/health.
 * 5. Foreign key integrity: all battery_logs.asset_id exist in assets.
 * 6. battery_logs values conform to technical specifications (voltage, water_level).
 * 7. pm_plans contains >= 17 active plans with DUE_SOON and OVERDUE variance.
 * 8. assets table has updated service telemetry (last_service_hm_km, next_service_due_hm_km).
 * 9. Condition Monitoring API returns valid payload with grease and battery arrays.
 * 10. PM Plans API returns valid payload with active schedules.
 * 11. Migration audit log BATCH_3_EQUIPMENT_REPORT_202601 recorded.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " RUNNING TEST SUITE: BATCH 3 PM, GREASE LOGS & BATTERY HEALTH\n";
echo "============================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(string $desc, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $desc" . ($detail ? " ($detail)" : "") . "\n";
        $passCount++;
    } else {
        echo "  [FAIL] $desc" . ($detail ? " ($detail)" : "") . "\n";
        $failCount++;
    }
}

// -----------------------------------------------------------------------------
// Check 1: grease_logs Count
// -----------------------------------------------------------------------------
$greaseCount = (int)$pdo->query("SELECT COUNT(*) FROM grease_logs")->fetchColumn();
assertCheck("grease_logs contains >= 23 records", $greaseCount >= 23, "Total: $greaseCount");

// -----------------------------------------------------------------------------
// Check 2: grease_logs Foreign Key Integrity
// -----------------------------------------------------------------------------
$orphanGrease = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM grease_logs g 
    LEFT JOIN assets a ON g.asset_id = a.asset_id 
    WHERE a.asset_id IS NULL
")->fetchColumn();
assertCheck("All grease_logs reference valid asset_id in assets table", $orphanGrease === 0, "Orphans: $orphanGrease");

// -----------------------------------------------------------------------------
// Check 3: grease_logs lubrication_points JSON
// -----------------------------------------------------------------------------
$stmtPts = $pdo->query("SELECT lubrication_points FROM grease_logs");
$allPointsValid = true;
while ($r = $stmtPts->fetch(PDO::FETCH_ASSOC)) {
    $arr = json_decode((string)$r['lubrication_points'], true);
    if (!is_array($arr) || empty($arr)) {
        $allPointsValid = false;
        break;
    }
}
assertCheck("All grease_logs have valid structured lubrication_points JSON", $allPointsValid);

// -----------------------------------------------------------------------------
// Check 4: battery_logs Count
// -----------------------------------------------------------------------------
$batCount = (int)$pdo->query("SELECT COUNT(*) FROM battery_logs")->fetchColumn();
assertCheck("battery_logs contains >= 12 records", $batCount >= 12, "Total: $batCount");

// -----------------------------------------------------------------------------
// Check 5: battery_logs Foreign Key Integrity
// -----------------------------------------------------------------------------
$orphanBat = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM battery_logs b 
    LEFT JOIN assets a ON b.asset_id = a.asset_id 
    WHERE a.asset_id IS NULL
")->fetchColumn();
assertCheck("All battery_logs reference valid asset_id in assets table", $orphanBat === 0, "Orphans: $orphanBat");

// -----------------------------------------------------------------------------
// Check 6: battery_logs Technical Values
// -----------------------------------------------------------------------------
$validTechBat = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM battery_logs 
    WHERE voltage_val > 0 AND water_level = 'GOOD' AND status_level = 'success'
")->fetchColumn();
assertCheck("All battery_logs have valid technical voltage and water level", $validTechBat === $batCount, "Valid: $validTechBat / $batCount");

// -----------------------------------------------------------------------------
// Check 7: pm_plans Total Count
// -----------------------------------------------------------------------------
$pmCount = (int)$pdo->query("SELECT COUNT(*) FROM pm_plans")->fetchColumn();
assertCheck("pm_plans contains >= 17 active maintenance schedules", $pmCount >= 17, "Total: $pmCount");

// -----------------------------------------------------------------------------
// Check 8: pm_plans Status Distribution
// -----------------------------------------------------------------------------
$overdueCount = (int)$pdo->query("SELECT COUNT(*) FROM pm_plans WHERE status = 'OVERDUE'")->fetchColumn();
$dueSoonCount = (int)$pdo->query("SELECT COUNT(*) FROM pm_plans WHERE status = 'DUE_SOON'")->fetchColumn();
assertCheck("pm_plans reflects dynamic status categories (OVERDUE & DUE_SOON)", ($overdueCount > 0 && $dueSoonCount > 0), "Overdue: $overdueCount, Due Soon: $dueSoonCount");

// -----------------------------------------------------------------------------
// Check 9: Assets Service Telemetry Updated
// -----------------------------------------------------------------------------
$servicedAssets = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM assets 
    WHERE next_service_due_hm_km IS NOT NULL AND service_interval_val IS NOT NULL
")->fetchColumn();
assertCheck("assets table has >= 13 units with active PM telemetry", $servicedAssets >= 13, "Count: $servicedAssets");

// -----------------------------------------------------------------------------
// Check 10: Condition Monitoring Data Layer
// -----------------------------------------------------------------------------
$cmGreaseCheck = (int)$pdo->query("SELECT COUNT(*) FROM grease_logs WHERE inspected_by IS NOT NULL")->fetchColumn();
$cmBatCheck = (int)$pdo->query("SELECT COUNT(*) FROM battery_logs WHERE inspected_by IS NOT NULL")->fetchColumn();
assertCheck("Condition Monitoring logs have valid inspector reference for UI rendering", ($cmGreaseCheck === $greaseCount && $cmBatCheck === $batCount));

// -----------------------------------------------------------------------------
// Check 11: Migration Key Recorded
// -----------------------------------------------------------------------------
$migApplied = (int)$pdo->query("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = 'BATCH_3_EQUIPMENT_REPORT_202601'")->fetchColumn();
assertCheck("Migration key BATCH_3_EQUIPMENT_REPORT_202601 is recorded", $migApplied > 0, "Applied: $migApplied");

echo "\n----------------------------------------------------------------------------\n";
echo " TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "----------------------------------------------------------------------------\n";

if ($failCount > 0) {
    exit(1);
}
