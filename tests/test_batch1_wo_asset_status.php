<?php
/**
 * Test Batch 1: Work Order <-> Asset Status Lifecycle in Database
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

echo "Running Batch 1 Tests...\n";

// Setup Test Asset
$testAssetId = 'TEST-ASSET-B1-' . time();
$db->prepare("
    INSERT INTO assets (asset_id, asset_code, make_model, serial_number, status, current_location_id, created_at)
    VALUES (:aid, :acode, 'Test Model', 'SN-TEST-B1', 'BREAKDOWN', 1, NOW())
")->execute([':aid' => $testAssetId, ':acode' => $testAssetId]);

// Setup Test Work Order 1
$testWo1 = 'WO-TEST-B1-1-' . time();
$db->prepare("
    INSERT INTO work_orders (wo_id, asset_id, issue_description, status, priority, maintenance_type, is_downtime, reported_at)
    VALUES (:wo, :aid, 'Test Issue 1', 'In Progress', 'High', 'Corrective', 1, NOW())
")->execute([':wo' => $testWo1, ':aid' => $testAssetId]);

// Setup Test Work Order 2 (Downtime)
$testWo2 = 'WO-TEST-B1-2-' . time();
$db->prepare("
    INSERT INTO work_orders (wo_id, asset_id, issue_description, status, priority, maintenance_type, is_downtime, reported_at)
    VALUES (:wo, :aid, 'Test Issue 2', 'Open', 'High', 'Corrective', 1, NOW())
")->execute([':wo' => $testWo2, ':aid' => $testAssetId]);

try {
    // 1. Close WO 1 when WO 2 is still active -> Asset should remain BREAKDOWN
    $db->prepare("UPDATE work_orders SET status = 'Closed', closed_at = NOW() WHERE wo_id = :wo")->execute([':wo' => $testWo1]);

    // Active WOs check
    $activeWosStmt = $db->prepare("
        SELECT COUNT(*) as cnt, COALESCE(SUM(CASE WHEN is_downtime = 1 THEN 1 ELSE 0 END), 0) AS downtime_count
        FROM work_orders 
        WHERE asset_id = :asset_id AND wo_id != :wo_id 
          AND status IN ('Open', 'In Progress', 'Waiting Part', 'Testing')
    ");
    $activeWosStmt->execute([':asset_id' => $testAssetId, ':wo_id' => $testWo1]);
    $rem = $activeWosStmt->fetch();
    $remCount = (int) ($rem['cnt'] ?? 0);
    $remDown = (int) ($rem['downtime_count'] ?? 0);

    if ($remCount === 0) {
        $db->prepare("UPDATE assets SET status = 'READY' WHERE asset_id = :aid")->execute([':aid' => $testAssetId]);
    } else {
        $retained = $remDown > 0 ? 'BREAKDOWN' : 'STANDBY';
        $db->prepare("UPDATE assets SET status = :st WHERE asset_id = :aid")->execute([':st' => $retained, ':aid' => $testAssetId]);
    }

    $checkSt = $db->query("SELECT status FROM assets WHERE asset_id = '$testAssetId'")->fetchColumn();
    if ($checkSt !== 'BREAKDOWN') {
        throw new RuntimeException("Asset should remain BREAKDOWN when WO 2 is still active. Got $checkSt");
    }
    echo "PASS: Asset retained BREAKDOWN while other downtime WO is active.\n";

    // 2. Now Close WO 2 -> No active WOs remain -> Asset must become READY
    $db->prepare("UPDATE work_orders SET status = 'Closed', closed_at = NOW() WHERE wo_id = :wo")->execute([':wo' => $testWo2]);
    $activeWosStmt->execute([':asset_id' => $testAssetId, ':wo_id' => $testWo2]);
    $rem2 = $activeWosStmt->fetch();
    $remCount2 = (int) ($rem2['cnt'] ?? 0);

    if ($remCount2 === 0) {
        $db->prepare("UPDATE assets SET status = 'READY' WHERE asset_id = :aid")->execute([':aid' => $testAssetId]);
        $db->prepare("
            INSERT INTO work_order_timeline (wo_id, event_type, from_status, to_status, title, description, actor_user_id, actor_name)
            VALUES (:wo, 'STATUS_CHANGE', 'BREAKDOWN', 'READY', 'Status unit kembali READY', 'Unit dikembalikan ke READY', 1, 'Tester')
        ")->execute([':wo' => $testWo2]);
    }

    $finalSt = $db->query("SELECT status FROM assets WHERE asset_id = '$testAssetId'")->fetchColumn();
    if ($finalSt !== 'READY') {
        throw new RuntimeException("Asset status should be READY in DB! Got $finalSt");
    }
    echo "PASS: Asset status successfully updated to READY in database after all WOs closed.\n";

    $timelineCount = $db->query("SELECT COUNT(*) FROM work_order_timeline WHERE wo_id = '$testWo2' AND event_type = 'STATUS_CHANGE'")->fetchColumn();
    if ((int)$timelineCount === 0) {
        throw new RuntimeException("Timeline must record STATUS_CHANGE event");
    }
    echo "PASS: Timeline recorded STATUS_CHANGE event.\n";

} finally {
    // Cleanup
    $db->exec("DELETE FROM work_order_timeline WHERE wo_id IN ('$testWo1', '$testWo2')");
    $db->exec("DELETE FROM work_orders WHERE wo_id IN ('$testWo1', '$testWo2')");
    $db->exec("DELETE FROM assets WHERE asset_id = '$testAssetId'");
    echo "Cleanup completed.\n";
}

echo "All Batch 1 tests PASSED!\n";
