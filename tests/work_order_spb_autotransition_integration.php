<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $error) {
    echo "SKIPPED: MySQL not reachable: " . $error->getMessage() . "\n";
    exit(0);
}

ReportIntegration::ensureTables($db);

$testAssetId = 'TEST-UNIT-AUTO-01';
$testWoId = 'WO-TEST-AUTO-' . bin2hex(random_bytes(4));

// Cleanup previous runs if any
$db->prepare("DELETE FROM work_orders WHERE wo_id = :wo_id")->execute([':wo_id' => $testWoId]);
$db->prepare("DELETE FROM assets WHERE asset_id = :id")->execute([':id' => $testAssetId]);

// Setup asset and open work order
$db->prepare("INSERT INTO assets (asset_id, asset_code, category, status) VALUES (:id, :code, 'Excavator', 'BREAKDOWN')")
   ->execute([':id' => $testAssetId, ':code' => $testAssetId]);

$db->prepare("INSERT INTO work_orders (wo_id, asset_id, status, priority, maintenance_type, issue_description, reported_at) 
              VALUES (:wo_id, :asset_id, 'Open', 'High', 'Corrective', 'Bocoran hidrolik berat', NOW())")
   ->execute([':wo_id' => $testWoId, ':asset_id' => $testAssetId]);

// Test 1: markPartsRequired triggers transition from 'Open' to 'Waiting Part'
WorkOrderSupport::markPartsRequired($db, $testWoId, 'SPB-TEST-AUTO-01', 1, 'Planner Test');
$stmt = $db->prepare("SELECT status, parts_requirement FROM work_orders WHERE wo_id = :wo_id");
$stmt->execute([':wo_id' => $testWoId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row['status'] !== 'Waiting Part') throw new RuntimeException("Expected status 'Waiting Part', got '{$row['status']}'");
if ($row['parts_requirement'] !== 'REQUIRED') throw new RuntimeException("Expected parts_requirement 'REQUIRED'");
echo "PASS: markPartsRequired auto-transitions WO to Waiting Part\n";

// Test 2: Check timeline event recorded for Waiting Part
$stmt = $db->prepare("SELECT event_type, to_status FROM work_order_timeline WHERE wo_id = :wo_id AND to_status = 'Waiting Part'");
$stmt->execute([':wo_id' => $testWoId]);
$tl = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tl) throw new RuntimeException("Timeline entry for Waiting Part should exist");
echo "PASS: timeline event recorded for Waiting Part transition\n";

// Test 3: Insert SPB with status 'Submitted' -> checkAndResumeWorkOrder should NOT resume yet
$spbId = 'SPB-TEST-AUTO-01';
$db->prepare("DELETE FROM purchase_requests WHERE spb_id = :spb_id")->execute([':spb_id' => $spbId]);
$db->prepare("INSERT INTO purchase_requests (spb_id, wo_id, asset_id, requested_by, status, urgency, requested_at)
              VALUES (:spb_id, :wo_id, :asset_id, 1, 'Submitted', 'Emergency', NOW())")
   ->execute([':spb_id' => $spbId, ':wo_id' => $testWoId, ':asset_id' => $testAssetId]);

$resumed = WorkOrderSupport::checkAndResumeWorkOrder($db, $testWoId, 1, 'Warehouse Test');
if ($resumed !== false) throw new RuntimeException("Work Order should not resume when SPB is still Submitted");
echo "PASS: WO does not resume while SPB is Submitted\n";

// Test 4: Update SPB to 'Issued' (parts handed over) -> checkAndResumeWorkOrder should auto-resume to 'In Progress'
$db->prepare("UPDATE purchase_requests SET status = 'Issued' WHERE spb_id = :spb_id")->execute([':spb_id' => $spbId]);
$resumed = WorkOrderSupport::checkAndResumeWorkOrder($db, $testWoId, 1, 'Warehouse Test');
if ($resumed !== true) throw new RuntimeException("Work Order should resume to In Progress when SPB is Issued");

$stmt = $db->prepare("SELECT status FROM work_orders WHERE wo_id = :wo_id");
$stmt->execute([':wo_id' => $testWoId]);
$currentStatus = $stmt->fetchColumn();
if ($currentStatus !== 'In Progress') throw new RuntimeException("Expected status 'In Progress', got '{$currentStatus}'");
echo "PASS: checkAndResumeWorkOrder auto-resumes WO to In Progress upon SPB fulfillment\n";

// Cleanup
$db->prepare("DELETE FROM purchase_requests WHERE spb_id = :spb_id")->execute([':spb_id' => $spbId]);
$db->prepare("DELETE FROM work_order_timeline WHERE wo_id = :wo_id")->execute([':wo_id' => $testWoId]);
$db->prepare("DELETE FROM work_orders WHERE wo_id = :wo_id")->execute([':wo_id' => $testWoId]);
$db->prepare("DELETE FROM assets WHERE asset_id = :id")->execute([':id' => $testAssetId]);

echo "\nAll auto-transition tests passed successfully.\n";
