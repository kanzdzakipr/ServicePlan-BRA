<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function supportAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $error) {
    echo 'SKIP: database integration test unavailable: ' . $error->getMessage() . "\n";
    exit(0);
}

$asset = $db->query('SELECT asset_id FROM assets WHERE is_active = 1 ORDER BY asset_id LIMIT 1')->fetchColumn();
$actor = $db->query('SELECT user_id, full_name FROM users ORDER BY user_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$asset || !$actor) {
    echo "SKIP: active asset or user is unavailable.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
$woId = 'QA-SUP-' . $suffix;
$spbId = 'QA-SPB-' . $suffix;
$actorId = (int) $actor['user_id'];
$actorName = (string) ($actor['full_name'] ?: 'QA User');

$db->beginTransaction();
try {
    $insertWo = $db->prepare(
        "INSERT INTO work_orders
         (wo_id, asset_id, issue_description, status, priority)
         VALUES (:wo_id, :asset_id, 'QA Revisi 4', 'Open', 'Normal')"
    );
    $insertWo->execute([':wo_id' => $woId, ':asset_id' => $asset]);

    WorkOrderSupport::markNoParts($db, $woId, 'Pekerjaan inspeksi tanpa penggantian komponen.', $actorId, $actorName);
    $overview = WorkOrderSupport::overview($db, $woId);
    supportAssert($overview['workOrder']['partsRequirement'] === 'NOT_REQUIRED', 'WO can explicitly record that no part is required');
    supportAssert(str_contains($overview['workOrder']['partsRequirementNote'], 'inspeksi'), 'no-part decision retains its reason');

    $service = WorkOrderSupport::createServiceRequest($db, $woId, (string) $asset, [
        'serviceType' => 'EXTERNAL_WORKSHOP',
        'providerName' => 'QA Workshop',
        'description' => 'Pengujian dan perbaikan komponen di bengkel.',
        'priority' => 'Urgent',
        'assignedTo' => 'QA PIC',
    ], $actorId, $actorName);
    supportAssert($service['wo_id'] === $woId && $service['status'] === 'Assigned', 'service request is linked to its originating WO and assigned PIC');

    $service = WorkOrderSupport::updateServiceStatus(
        $db,
        (string) $service['service_request_id'],
        'In Progress',
        'QA PIC',
        'Unit diterima bengkel.',
        $actorId,
        $actorName
    );
    supportAssert($service['status'] === 'In Progress' && $service['started_at'] !== null, 'service follow-up records start time');

    $service = WorkOrderSupport::updateServiceStatus(
        $db,
        (string) $service['service_request_id'],
        'Completed',
        'QA PIC',
        'Perbaikan selesai.',
        $actorId,
        $actorName
    );
    supportAssert($service['status'] === 'Completed' && $service['completed_at'] !== null, 'service completion records completion time');

    $insertSpb = $db->prepare(
        "INSERT INTO purchase_requests
         (spb_id, wo_id, asset_id, requested_by, urgency, status, notes)
         VALUES (:spb_id, :wo_id, :asset_id, :user_id, 'Normal', 'Submitted', 'QA linked SPB')"
    );
    $insertSpb->execute([
        ':spb_id' => $spbId,
        ':wo_id' => $woId,
        ':asset_id' => $asset,
        ':user_id' => $actorId,
    ]);
    $db->prepare(
        "INSERT INTO purchase_request_items
         (id, spb_id, part_number, description, qty_requested, status)
         VALUES (:id, :spb_id, 'QA-PART', 'QA part', 2, 'Menunggu Approval')"
    )->execute([':id' => 'QA-ITEM-' . $suffix, ':spb_id' => $spbId]);

    WorkOrderSupport::markPartsRequired($db, $woId, 'QA linked SPB', $actorId, $actorName, $spbId);
    $overview = WorkOrderSupport::overview($db, $woId);
    supportAssert(
        $overview['workOrder']['partsRequirement'] === 'REQUIRED'
        && count($overview['purchaseRequests']) === 1
        && $overview['purchaseRequests'][0]['itemCount'] === 1,
        'linked SPB marks parts as required and is visible in WO overview'
    );
    supportAssert(count($overview['serviceRequests']) === 1, 'service request remains traceable in WO overview');

    try {
        WorkOrderSupport::markNoParts($db, $woId, 'Tidak jadi menggunakan part.', $actorId, $actorName);
        supportAssert(false, 'active SPB must block no-part decision');
    } catch (DomainException $error) {
        supportAssert(str_contains($error->getMessage(), 'SPB aktif'), 'active SPB blocks contradictory no-part decision');
    }

    $timelineCount = $db->prepare('SELECT COUNT(*) FROM work_order_timeline WHERE wo_id = :wo_id');
    $timelineCount->execute([':wo_id' => $woId]);
    supportAssert((int) $timelineCount->fetchColumn() >= 5, 'part and service actions are recorded in the WO timeline');

    echo "\nWork Order support integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}

