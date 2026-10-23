<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';

$db = new PDO(
    (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4'),
    (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'),
    (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$assetId = $db->query('SELECT asset_id FROM assets WHERE is_active = 1 ORDER BY asset_id LIMIT 1')->fetchColumn();
$actorId = (int) $db->query('SELECT user_id FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 1')->fetchColumn();
if (!$assetId || $actorId < 1) throw new RuntimeException('Aset/aktor uji tidak tersedia.');
$suffix = strtoupper(bin2hex(random_bytes(5)));
$woId = 'QA-F2-WO-' . $suffix;
$spbId = 'QA-F2-SPB-' . $suffix;
$beforeWo = (int) $db->query('SELECT COUNT(*) FROM work_orders')->fetchColumn();
$beforeSpb = (int) $db->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn();
$passed = 0;

$db->beginTransaction();
try {
    $db->prepare("INSERT INTO work_orders (wo_id, asset_id, issue_description, status, priority)
                  VALUES (:wo, :asset, 'Uji transisi F2 rollback', 'Open', 'Normal')")
        ->execute([':wo' => $woId, ':asset' => $assetId]);
    WorkOrderSupport::markPartsRequired($db, $woId, 'Kebutuhan part QA', $actorId, 'QA F2', $spbId);
    $status = $db->prepare('SELECT status, parts_requirement FROM work_orders WHERE wo_id = :wo');
    $status->execute([':wo' => $woId]);
    $row = $status->fetch();
    if ($row['status'] !== 'Waiting Part' || $row['parts_requirement'] !== 'REQUIRED') {
        throw new RuntimeException('WO tidak masuk Waiting Part.');
    }
    $passed++;
    $db->prepare("INSERT INTO purchase_requests (spb_id, wo_id, asset_id, requested_by, status, urgency)
                  VALUES (:spb, :wo, :asset, :actor, 'Submitted', 'Normal')")
        ->execute([':spb' => $spbId, ':wo' => $woId, ':asset' => $assetId, ':actor' => $actorId]);
    if (WorkOrderSupport::checkAndResumeWorkOrder($db, $woId, $actorId, 'QA F2') !== false) {
        throw new RuntimeException('WO dilanjutkan saat SPB masih Submitted.');
    }
    $passed++;
    $db->prepare("UPDATE purchase_requests SET status = 'Issued' WHERE spb_id = :spb")
        ->execute([':spb' => $spbId]);
    if (WorkOrderSupport::checkAndResumeWorkOrder($db, $woId, $actorId, 'QA F2') !== true) {
        throw new RuntimeException('WO tidak lanjut setelah SPB Issued.');
    }
    $passed++;
    $status->execute([':wo' => $woId]);
    if ($status->fetch()['status'] !== 'In Progress') throw new RuntimeException('Status akhir WO tidak In Progress.');
    $passed++;
    if (WorkOrderSupport::checkAndResumeWorkOrder($db, $woId, $actorId, 'QA F2') !== false) {
        throw new RuntimeException('Pengulangan transisi mengubah WO lagi.');
    }
    $passed++;
} finally {
    if ($db->inTransaction()) $db->rollBack();
}

$check = $db->prepare('SELECT COUNT(*) FROM work_orders WHERE wo_id = :wo');
$check->execute([':wo' => $woId]);
$checkSpb = $db->prepare('SELECT COUNT(*) FROM purchase_requests WHERE spb_id = :spb');
$checkSpb->execute([':spb' => $spbId]);
if ((int) $check->fetchColumn() !== 0 || (int) $checkSpb->fetchColumn() !== 0
    || (int) $db->query('SELECT COUNT(*) FROM work_orders')->fetchColumn() !== $beforeWo
    || (int) $db->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn() !== $beforeSpb) {
    throw new RuntimeException('Rollback transisi WO/SPB tidak bersih.');
}
echo "F2 WO/SPB transition rollback: {$passed}/5 passed; WO {$beforeWo}, SPB {$beforeSpb}; QA tersisa 0.\n";
