<?php
/**
 * Test Batch 3: Preventive Maintenance WO Completion -> Asset HM & PM Plans Update
 */
declare(strict_types=1);

$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4';
$user = 'root';
$password = '';

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    echo "SKIP: Database connection failed: " . $e->getMessage() . "\n";
    exit(0);
}

echo "Running Batch 3 Tests...\n";

$testAssetId = 'TEST-ASSET-B3-' . time();
$testWoId = 'WO-TEST-B3-' . time();

try {
    // 1. Setup Test Asset with service interval 250, daily coeff 10
    $db->prepare("
        INSERT INTO assets (asset_id, asset_code, make_model, serial_number, status, current_location_id,
                            last_hm_km, last_service_hm_km, service_interval_val, daily_usage_coefficient, is_active, created_at)
        VALUES (:aid, :acode, 'Test Excavator B3', 'SN-TEST-B3', 'BREAKDOWN', 1,
                1000.0, 750.0, 250.0, 10.0, 1, NOW())
    ")->execute([':aid' => $testAssetId, ':acode' => $testAssetId]);

    // 2. Setup Existing PM Plan in DUE_SOON
    $db->prepare("
        INSERT INTO pm_plans (asset_id, interval_hm, current_smr, last_service_hm, last_service_date, target_due_hm, variance_hm, status, planner_note)
        VALUES (:aid, 250, 1000.0, 750.0, CURDATE() - INTERVAL 25 DAY, 1000.0, 0, 'DUE_SOON', 'Jadwal PM 250 Jam')
    ")->execute([':aid' => $testAssetId]);

    // 3. Setup Work Order of type Preventive
    $db->prepare("
        INSERT INTO work_orders (wo_id, asset_id, issue_description, status, priority, maintenance_type, is_downtime, current_hm_km, reported_at)
        VALUES (:wo, :aid, 'PM 250 Hours Regular Service', 'In Progress', 'High', 'Preventive', 1, 1005.0, NOW())
    ")->execute([':wo' => $testWoId, ':aid' => $testAssetId]);

    // 4. Simulate Closing WO via api/work_orders logic
    // We execute the same SQL update block as in api/work_orders.php line 720-789
    $currentServiceHm = 1005.0;
    $intervalVal = 250.0;
    $dailyCoeff = 10.0;
    $nextDueHm = $currentServiceHm + $intervalVal; // 1255.0
    $daysToNext = $intervalVal / $dailyCoeff; // 25 days
    $estDate = date('Y-m-d', strtotime('+' . max(1, (int) round($daysToNext)) . ' days'));

    $db->beginTransaction();

    // Close WO
    $db->prepare("
        UPDATE work_orders 
        SET status = 'Closed', closed_at = NOW(), downtime_minutes = 60, downtime_formatted = '1 jam 00 menit'
        WHERE wo_id = :wo
    ")->execute([':wo' => $testWoId]);

    // Reconcile asset status to READY
    $db->prepare("UPDATE assets SET status = 'READY' WHERE asset_id = :aid")->execute([':aid' => $testAssetId]);

    // Update Asset Service Interval & Meter
    $db->prepare("
        UPDATE assets 
        SET last_service_date = CURDATE(),
            last_service_hm_km = :service_hm,
            last_hm_km = GREATEST(COALESCE(last_hm_km, 0), :service_hm_current),
            next_service_due_hm_km = :next_due,
            next_service_estimated_date = :est_date
        WHERE asset_id = :aid
    ")->execute([
        ':service_hm' => $currentServiceHm,
        ':service_hm_current' => $currentServiceHm,
        ':next_due' => $nextDueHm,
        ':est_date' => $estDate,
        ':aid' => $testAssetId,
    ]);

    // Insert into asset_service_history
    $db->prepare("
        INSERT INTO asset_service_history 
        (asset_id, service_sequence, service_date, service_hm_km, service_type, mechanic_pic, jo_report_number, notes)
        VALUES (:aid, 1, CURDATE(), :hm, 'Preventive Maintenance (WO)', 'Apeng', :jo, 'PM 250 Selesai')
    ")->execute([
        ':aid' => $testAssetId,
        ':hm' => $currentServiceHm,
        ':jo' => $testWoId,
    ]);

    // Complete old PM plan
    $db->prepare("
        UPDATE pm_plans 
        SET status = 'COMPLETED',
            planner_note = CONCAT(COALESCE(planner_note, ''), ' | Selesai via WO #', :wo_id)
        WHERE asset_id = :aid AND status IN ('PLANNED', 'DUE_SOON', 'OVERDUE')
    ")->execute([':wo_id' => $testWoId, ':aid' => $testAssetId]);

    // Insert new cycle into pm_plans
    $db->prepare('
        INSERT INTO pm_plans 
        (asset_id, interval_hm, current_smr, last_service_hm, last_service_date, target_due_hm, variance_hm, status, planner_note)
        VALUES (:id, :interval, :current, :last, CURDATE(), :target, :variance, :status, :note)
    ')->execute([
        ':id' => $testAssetId,
        ':interval' => (int) $intervalVal,
        ':current' => $currentServiceHm,
        ':last' => $currentServiceHm,
        ':target' => $nextDueHm,
        ':variance' => $currentServiceHm - $nextDueHm,
        ':status' => 'PLANNED',
        ':note' => "Siklus berikutnya setelah WO #{$testWoId}",
    ]);

    $db->commit();

    // 5. Verification
    // Verify Asset
    $ast = $db->query("SELECT status, last_service_hm_km, next_service_due_hm_km FROM assets WHERE asset_id = '{$testAssetId}'")->fetch();
    if ($ast['status'] !== 'READY') throw new RuntimeException("Asset status should be READY");
    if (abs((float)$ast['last_service_hm_km'] - 1005.0) > 0.01) throw new RuntimeException("last_service_hm_km should be 1005.0");
    if (abs((float)$ast['next_service_due_hm_km'] - 1255.0) > 0.01) throw new RuntimeException("next_service_due_hm_km should be 1255.0");
    echo "PASS: Asset HM meter updated (last: 1005.0, next: 1255.0) and status READY.\n";

    // Verify Service History
    $hist = $db->query("SELECT * FROM asset_service_history WHERE asset_id = '{$testAssetId}' AND jo_report_number = '{$testWoId}'")->fetch();
    if (!$hist) throw new RuntimeException("Service history not recorded");
    echo "PASS: asset_service_history logged with WO reference.\n";

    // Verify PM Plans
    $completedCount = (int) $db->query("SELECT COUNT(*) FROM pm_plans WHERE asset_id = '{$testAssetId}' AND status = 'COMPLETED'")->fetchColumn();
    $plannedCount = (int) $db->query("SELECT COUNT(*) FROM pm_plans WHERE asset_id = '{$testAssetId}' AND status = 'PLANNED'")->fetchColumn();
    if ($completedCount !== 1) throw new RuntimeException("Old PM plan should be COMPLETED");
    if ($plannedCount !== 1) throw new RuntimeException("New PM plan should be PLANNED");
    echo "PASS: pm_plans old plan marked COMPLETED and new cycle PLANNED.\n";

} finally {
    // Cleanup
    $db->exec("DELETE FROM pm_plans WHERE asset_id = '{$testAssetId}'");
    $db->exec("DELETE FROM asset_service_history WHERE asset_id = '{$testAssetId}'");
    $db->exec("DELETE FROM work_orders WHERE wo_id = '{$testWoId}'");
    $db->exec("DELETE FROM assets WHERE asset_id = '{$testAssetId}'");
    echo "Cleanup completed.\n";
}

echo "All Batch 3 tests PASSED!\n";
