<?php
/**
 * Test Batch 4: SPB Part Issue -> Warehouse Stock Deduction -> Auto-Resume WO to In Progress
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/SpbWorkflow.php';
require_once __DIR__ . '/../core/WorkOrderSupport.php';

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

echo "Running Batch 4 Tests...\n";

$testAssetId = 'TEST-ASSET-B4-' . time();
$testWoId = 'WO-TEST-B4-' . time();
$testSpbId = 'SPB-TEST-B4-' . time();
$testPartNum = 'PART-TEST-B4-' . time();
$testPartId = null;

$actor = $db->query('SELECT user_id, full_name FROM users ORDER BY user_id LIMIT 1')->fetch();
$actorId = (int) ($actor['user_id'] ?? 1);
$actorName = (string) ($actor['full_name'] ?? 'Tester');

try {
    // 1. Setup Test Asset
    $db->prepare("
        INSERT INTO assets (asset_id, asset_code, make_model, serial_number, status, current_location_id, created_at)
        VALUES (:aid, :acode, 'Test Excavator B4', 'SN-TEST-B4', 'BREAKDOWN', 1, NOW())
    ")->execute([':aid' => $testAssetId, ':acode' => $testAssetId]);

    // 2. Setup Test Work Order in Waiting Part
    $db->prepare("
        INSERT INTO work_orders (wo_id, asset_id, issue_description, status, priority, maintenance_type, is_downtime, reported_at)
        VALUES (:wo, :aid, 'Test Issue Waiting Part', 'Waiting Part', 'High', 'Corrective', 1, NOW())
    ")->execute([':wo' => $testWoId, ':aid' => $testAssetId]);

    // 3. Setup Test Part with 10 stock
    $db->prepare("
        INSERT INTO parts (part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, unit_cost, location_warehouse)
        VALUES (:pnum, 'Test Hydraulic Seal B4', 'Hydraulic', 'Pcs', 10, 2, 150000.00, 'Gudang Yard KM 12')
    ")->execute([':pnum' => $testPartNum]);
    $testPartId = (int) $db->lastInsertId();

    // 4. Setup Test SPB and Item
    $db->prepare("
        INSERT INTO purchase_requests (spb_id, wo_id, asset_id, requested_by, urgency, status, notes, requested_at)
        VALUES (:spb, :wo, :aid, :req, 'Normal', 'Approved', 'Kebutuhan seal silinder hidrolik', NOW())
    ")->execute([
        ':spb' => $testSpbId,
        ':wo' => $testWoId,
        ':aid' => $testAssetId,
        ':req' => $actorId,
    ]);

    $itemId = 'ITEM-' . time();
    $db->prepare("
        INSERT INTO purchase_request_items (id, spb_id, part_number, description, qty_requested, status)
        VALUES (:id, :spb, :pnum, 'Hydraulic Seal 50mm', 3, 'Menunggu Pengeluaran')
    ")->execute([
        ':id' => $itemId,
        ':spb' => $testSpbId,
        ':pnum' => $testPartNum,
    ]);

    // 5. Test Stock Shortage Rejection
    try {
        $db->beginTransaction();
        SpbWorkflow::issueParts(
            $db, $testSpbId,
            [['id' => $itemId, 'part_number' => $testPartNum, 'qty_issued' => 999]], // More than 10
            $actorId, $actorName,
            static fn() => true
        );
        $db->commit();
        throw new RuntimeException("Should have thrown DomainException for insufficient stock!");
    } catch (DomainException $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo "PASS: Stock shortage correctly blocked: " . $e->getMessage() . "\n";
    }

    // 6. Test Valid Issue (3 items)
    $db->beginTransaction();
    $issueRes = SpbWorkflow::issueParts(
        $db, $testSpbId,
        [['id' => $itemId, 'part_number' => $testPartNum, 'qty_issued' => 3]],
        $actorId, $actorName,
        static fn() => true
    );
    $db->commit();

    // Verify part stock reduced to 7
    $updatedStock = (int) $db->query("SELECT stock_qty FROM parts WHERE part_id = {$testPartId}")->fetchColumn();
    if ($updatedStock !== 7) {
        throw new RuntimeException("Stock should be 7, got {$updatedStock}");
    }
    echo "PASS: Part stock accurately deducted from 10 to 7.\n";

    // Verify SPB item status
    $itemStatus = $db->query("SELECT status FROM purchase_request_items WHERE id = '{$itemId}'")->fetchColumn();
    if ($itemStatus !== 'Issued') {
        throw new RuntimeException("SPB item status should be Issued, got {$itemStatus}");
    }
    echo "PASS: SPB Item status updated to Issued.\n";

    // Verify SPB header status
    $spbStatus = $db->query("SELECT status FROM purchase_requests WHERE spb_id = '{$testSpbId}'")->fetchColumn();
    if ($spbStatus !== 'Issued') {
        throw new RuntimeException("SPB header status should be Issued, got {$spbStatus}");
    }
    echo "PASS: SPB Header status updated to Issued.\n";

    // Verify inventory transaction
    $invTx = $db->query("SELECT * FROM inventory_transactions WHERE reference_number = '{$testSpbId}'")->fetch();
    if (!$invTx) {
        throw new RuntimeException("Inventory transaction record not found");
    }
    if ($invTx['movement_type'] !== 'OUT' || (int)$invTx['quantity'] !== 3 || (int)$invTx['stock_after'] !== 7) {
        throw new RuntimeException("Inventory transaction record data mismatch");
    }
    echo "PASS: Inventory transaction logged with movement OUT, quantity 3, stock_after 7.\n";

    // Verify WO Auto-Resume: 'Waiting Part' -> 'In Progress'
    $woStatus = $db->query("SELECT status FROM work_orders WHERE wo_id = '{$testWoId}'")->fetchColumn();
    if ($woStatus !== 'In Progress') {
        throw new RuntimeException("Work Order should be auto-resumed to 'In Progress', got {$woStatus}");
    }
    echo "PASS: Work Order successfully auto-resumed to 'In Progress'.\n";

    // Verify WO timeline
    $tlCount = $db->query("SELECT COUNT(*) FROM work_order_timeline WHERE wo_id = '{$testWoId}' AND to_status = 'In Progress'")->fetchColumn();
    if ((int)$tlCount === 0) {
        throw new RuntimeException("Timeline event for auto-resume missing");
    }
    echo "PASS: Work Order timeline accurately recorded auto-resume event.\n";

} finally {
    // Cleanup
    $db->exec("DELETE FROM inventory_transactions WHERE reference_number = '{$testSpbId}'");
    $db->exec("DELETE FROM report_purchase_request_integrations WHERE spb_id = '{$testSpbId}'");
    $db->exec("DELETE FROM report_records WHERE report_number = '{$testSpbId}'");
    $db->exec("DELETE FROM purchase_request_items WHERE spb_id = '{$testSpbId}'");
    $db->exec("DELETE FROM purchase_requests WHERE spb_id = '{$testSpbId}'");
    $db->exec("DELETE FROM work_order_timeline WHERE wo_id = '{$testWoId}'");
    $db->exec("DELETE FROM work_orders WHERE wo_id = '{$testWoId}'");
    $db->exec("DELETE FROM assets WHERE asset_id = '{$testAssetId}'");
    if ($testPartId) {
        $db->exec("DELETE FROM parts WHERE part_id = {$testPartId}");
    }
    echo "Cleanup completed.\n";
}

echo "All Batch 4 tests PASSED!\n";
