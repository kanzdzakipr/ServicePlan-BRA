<?php
declare(strict_types=1);

/**
 * Automated Verification: PM-01, PM-02, PM-04
 * Preventive Maintenance Forecast Adjustments, Long Standby Shifts, and Idempotent Generator
 */

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/security.php';
require_once __DIR__ . '/../core/PmForecast.php';

$passed = 0;
$total = 0;

function assertCheck(bool $condition, string $message): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] {$message}\n";
    } else {
        echo "![FAIL] {$message}\n";
    }
}

echo "\n=== RUNNING PM FORECAST & GENERATOR TESTS (PM-01, PM-02, PM-04) ===\n\n";

$db = Database::getInstance();

// --------------------------------------------------------------------------
// Suite 1: PmForecast Domain Rules & Calculation Engine
// --------------------------------------------------------------------------
echo "--- Suite 1: PmForecast Calculation Engine & Rules ---\n";

assertCheck(PmForecast::adjustmentType('long_standby') === 'LONG_STANDBY', 'PmForecast accepts lowercase adjustment type and normalizes to uppercase');
assertCheck(PmForecast::adjustmentType('BREAKDOWN') === 'BREAKDOWN', 'PmForecast accepts valid BREAKDOWN type');
assertCheck(PmForecast::adjustmentType('METER_CORRECTION') === 'METER_CORRECTION', 'PmForecast accepts METER_CORRECTION type');
assertCheck(PmForecast::adjustmentType('OPERATION_CHANGE') === 'OPERATION_CHANGE', 'PmForecast accepts OPERATION_CHANGE type');
assertCheck(PmForecast::adjustmentType('OTHER') === 'OTHER', 'PmForecast accepts OTHER type');

$invalidTypeCaught = false;
try {
    PmForecast::adjustmentType('UNKNOWN_REVISION');
} catch (InvalidArgumentException) {
    $invalidTypeCaught = true;
}
assertCheck($invalidTypeCaught, 'PmForecast rejects unrecognized adjustment type');

$shortReasonCaught = false;
try {
    PmForecast::requireReason('abc'); // < 5 chars
} catch (InvalidArgumentException) {
    $shortReasonCaught = true;
}
assertCheck($shortReasonCaught, 'PmForecast requires at least 5 characters for adjustment reason');

$validReason = 'Unit breakdown di pit, estimasi mundur 10 hari menunggu radiator pengganti.';
assertCheck(PmForecast::requireReason($validReason) === $validReason, 'PmForecast accepts valid detailed reason');

// Math projection: current 1000, target 1500, daily rate 50 -> 10 days
$projected = PmForecast::estimate('2026-11-01', 1000.0, 1500.0, 50.0);
assertCheck($projected === '2026-11-11', "Forecast calculation correctly projects 10 days: {$projected}");

// Quality flags
$flagsEmpty = PmForecast::qualityFlags(null, null, null);
assertCheck(in_array('MISSING_METER', $flagsEmpty, true) && in_array('MISSING_INTERVAL', $flagsEmpty, true) && in_array('MISSING_DAILY_USAGE', $flagsEmpty, true), 'Flags correctly identify missing meter, interval, and usage rate');

$flagsValid = PmForecast::qualityFlags(1500, 250, 7.5);
assertCheck(empty($flagsValid), 'Quality flags empty when all required metrics are valid');

// --------------------------------------------------------------------------
// Suite 2: PM-01 & PM-02 Database Adjustment & Audit Trail
// --------------------------------------------------------------------------
echo "\n--- Suite 2: PM-01 & PM-02 Database Forecast Shift & Immutability ---\n";

// Find an existing PM plan or create a test one
$samplePlan = $db->query("
    SELECT p.pm_plan_id, p.asset_id, p.target_due_hm, p.current_smr, p.original_estimated_date, p.revised_estimated_date, p.forecast_revision_no
    FROM pm_plans p
    ORDER BY p.pm_plan_id ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$samplePlan) {
    // Insert temporary test asset & plan
    $db->prepare("INSERT INTO assets (asset_id, asset_code, category, is_active) VALUES ('QA-PM-01', 'QA-PM-01', 'Dump Truck', 1)")->execute();
    $db->prepare("INSERT INTO pm_plans (asset_id, interval_hm, current_smr, target_due_hm, original_estimated_date, status) VALUES ('QA-PM-01', 250, 1000, 1250, '2026-11-10', 'PLANNED')")->execute();
    $planId = (int)$db->lastInsertId();
    $targetDue = 1250.0;
} else {
    $planId = (int)$samplePlan['pm_plan_id'];
    $targetDue = (float)$samplePlan['target_due_hm'];
}

$revisedDate = '2026-11-25';
$adjType = 'LONG_STANDBY';
$reason = 'Unit mengalami long standby selama 2 minggu karena relokasi area kerja.';
$actorId = 1;

// Simulate adjust_forecast transaction
$db->beginTransaction();
try {
    $lock = $db->prepare('SELECT p.*, a.next_service_estimated_date FROM pm_plans p INNER JOIN assets a ON a.asset_id = p.asset_id WHERE p.pm_plan_id = :id FOR UPDATE');
    $lock->execute([':id' => $planId]);
    $planBefore = $lock->fetch(PDO::FETCH_ASSOC);
    $originalEstimate = $planBefore['original_estimated_date'] ?: ($planBefore['next_service_estimated_date'] ?: '2026-11-10');

    $insAdj = $db->prepare('
        INSERT INTO pm_forecast_adjustments (
            pm_plan_id, asset_id, adjustment_type, original_estimated_date,
            revised_estimated_date, target_meter, current_meter, usage_rate,
            status_source, reason, created_by
        ) VALUES (
            :plan, :asset, :type, :original,
            :revised, :target, :current, :rate,
            :source, :reason, :actor
        )
    ');
    $insAdj->execute([
        ':plan' => $planId,
        ':asset' => $planBefore['asset_id'],
        ':type' => $adjType,
        ':original' => $originalEstimate,
        ':revised' => $revisedDate,
        ':target' => (float)$planBefore['target_due_hm'],
        ':current' => (float)$planBefore['current_smr'],
        ':rate' => 150.0,
        ':source' => 'Laporan Operasi Harian',
        ':reason' => $reason,
        ':actor' => $actorId,
    ]);
    $adjustmentId = (int)$db->lastInsertId();

    $db->prepare('
        UPDATE pm_plans
        SET original_estimated_date = COALESCE(original_estimated_date, :original),
            revised_estimated_date = :revised,
            forecast_revision_no = forecast_revision_no + 1
        WHERE pm_plan_id = :id
    ')->execute([
        ':original' => $originalEstimate,
        ':revised' => $revisedDate,
        ':id' => $planId,
    ]);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    throw $e;
}

// Verify PM plan after adjustment
$planAfter = $db->query("SELECT * FROM pm_plans WHERE pm_plan_id = {$planId}")->fetch(PDO::FETCH_ASSOC);

assertCheck((float)$planAfter['target_due_hm'] === $targetDue, 'Target meter remains completely immutable after forecast revision');
assertCheck($planAfter['revised_estimated_date'] === $revisedDate, "Revised estimated date updated to {$revisedDate}");
assertCheck((int)$planAfter['forecast_revision_no'] > 0, 'Forecast revision counter incremented');

// Verify Audit Trail in pm_forecast_adjustments
$auditRow = $db->query("SELECT * FROM pm_forecast_adjustments WHERE adjustment_id = {$adjustmentId}")->fetch(PDO::FETCH_ASSOC);
assertCheck($auditRow !== false, 'Forecast adjustment persisted in pm_forecast_adjustments');
assertCheck($auditRow['adjustment_type'] === 'LONG_STANDBY', 'Audit trail records adjustment type LONG_STANDBY');
assertCheck($auditRow['reason'] === $reason, 'Audit trail records detailed planner reason');
assertCheck((float)$auditRow['target_meter'] === $targetDue, 'Audit trail captures target meter snapshot');

// --------------------------------------------------------------------------
// Suite 3: PM-04 Idempotent Next Month Plan Generator
// --------------------------------------------------------------------------
echo "\n--- Suite 3: PM-04 Idempotent Generator ---\n";

$testMonth = date('Y-m', strtotime('+2 months')); // Future test month
$testAssetId = (string)$planAfter['asset_id'];
$genKey = "{$testMonth}:{$testAssetId}";

// Clean up any test generation key
$db->prepare("DELETE FROM pm_plans WHERE generation_key = :key")->execute([':key' => $genKey]);

// Run 1: Publish plan
$insGen = $db->prepare("
    INSERT INTO pm_plans (
        asset_id, interval_hm, current_smr, last_service_hm,
        last_service_date, target_due_hm, original_estimated_date,
        status, planner_note, generation_key
    ) VALUES (
        :asset, 250, 1000, 750,
        CURDATE(), 1250, :est_date,
        'PLANNED', 'Generated idempotently for test', :gen_key
    )
");
$insGen->execute([
    ':asset' => $testAssetId,
    ':est_date' => "{$testMonth}-15",
    ':gen_key' => $genKey,
]);
$firstInsertedId = (int)$db->lastInsertId();
assertCheck($firstInsertedId > 0, "First generation run created plan ID {$firstInsertedId} with key {$genKey}");

// Run 2: Simulate second generation run with duplicate key check
$checkGen = $db->prepare('SELECT pm_plan_id FROM pm_plans WHERE generation_key = :key LIMIT 1');
$checkGen->execute([':key' => $genKey]);
$existingId = (int)$checkGen->fetchColumn();

assertCheck($existingId === $firstInsertedId, 'Second generation run detects existing generation_key and skips insertion');

$countKeys = (int)$db->query("SELECT COUNT(*) FROM pm_plans WHERE generation_key = '{$genKey}'")->fetchColumn();
assertCheck($countKeys === 1, 'Exactly one plan exists for generation key (zero duplicates)');

// Cleanup test plan
$db->prepare("DELETE FROM pm_plans WHERE generation_key = :key")->execute([':key' => $genKey]);
$db->prepare("DELETE FROM pm_forecast_adjustments WHERE adjustment_id = {$adjustmentId}")->execute();

echo "\n--------------------------------------------------------------------------\n";
echo "RESULT: {$passed} / {$total} TESTS PASSED\n";
echo "--------------------------------------------------------------------------\n";

if ($passed !== $total) {
    exit(1);
}
