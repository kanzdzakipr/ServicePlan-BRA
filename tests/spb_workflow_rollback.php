<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/SpbWorkflow.php';

$db = new PDO(
    (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4'),
    (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'),
    (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$source = $db->query("SELECT wo.wo_id, wo.asset_id FROM work_orders wo
    WHERE NOT EXISTS (SELECT 1 FROM inventory_transactions it
        WHERE it.wo_id = wo.wo_id AND it.movement_type = 'OUT' AND it.reversed_at IS NULL)
    ORDER BY wo.wo_id LIMIT 1")->fetch();
$actor = (int) $db->query('SELECT user_id FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 1')->fetchColumn();
if (!$source || $actor < 1) throw new RuntimeException('WO atau aktor uji tidak tersedia.');
$suffix = strtoupper(bin2hex(random_bytes(5)));
$ids = ['QA-SPB-A-' . $suffix, 'QA-SPB-R-' . $suffix, 'QA-SPB-V-' . $suffix];
SpbWorkflow::ensureVoidAudit($db);
$voidBefore = (int) $db->query('SELECT COUNT(*) FROM spb_void_audit')->fetchColumn();
$before = (int) $db->query('SELECT COUNT(*) FROM approvals')->fetchColumn();
$passed = 0;
if (!SpbWorkflow::canApproveRoles(['Logistic Head'])
    || SpbWorkflow::canApproveRoles(['HRD Manager'])
    || SpbWorkflow::canApproveRoles(['Maintenance Planner'])) {
    throw new RuntimeException('Matriks role approval SPB tidak sesuai.');
}
$passed++;

$db->beginTransaction();
try {
    foreach ($ids as $id) {
        $db->prepare("INSERT INTO purchase_requests
            (spb_id, wo_id, asset_id, requested_by, urgency, status)
            VALUES (:id, :wo, :asset, :actor, 'Normal', 'Submitted')")
            ->execute([':id' => $id, ':wo' => $source['wo_id'], ':asset' => $source['asset_id'], ':actor' => $actor]);
    }
    $authorized = static function (string $assetId) use ($source): void {
        if ($assetId !== $source['asset_id']) throw new RuntimeException('Aset tidak sesuai.');
    };
    $approved = SpbWorkflow::decide($db, $ids[0], $actor, 'approve', '', $authorized);
    if ($approved['status'] !== 'Approved') throw new RuntimeException('Persetujuan tidak tersimpan.');
    $passed++;
    try {
        SpbWorkflow::decide($db, $ids[0], $actor, 'approve', '', $authorized);
        throw new RuntimeException('Replay persetujuan diterima.');
    } catch (DomainException $expected) {
        $passed++;
    }
    $rejected = SpbWorkflow::decide($db, $ids[1], $actor, 'reject', 'Item tidak sesuai', $authorized);
    if ($rejected['status'] !== 'Draft') throw new RuntimeException('Penolakan tidak tersimpan.');
    $passed++;
    $audit = $db->prepare("SELECT status, rejection_reason FROM approvals WHERE document_type = 'SPB' AND document_id = :id");
    $audit->execute([':id' => $ids[1]]);
    $row = $audit->fetch();
    if ($row['status'] !== 'REJECTED' || $row['rejection_reason'] !== 'Item tidak sesuai') {
        throw new RuntimeException('Audit penolakan tidak benar.');
    }
    $passed++;
    $voided = SpbWorkflow::voidOperational($db, $ids[2], $actor, 'Uji rollback', $authorized);
    if ($voided['status'] !== 'VOID') throw new RuntimeException('Status void tidak benar.');
    $passed++;
    $voidCheck = $db->prepare('SELECT COUNT(*) FROM spb_void_audit WHERE spb_id = :id');
    $voidCheck->execute([':id' => $ids[2]]);
    if ((int) $voidCheck->fetchColumn() !== 1) throw new RuntimeException('Audit void tidak tersimpan dalam transaksi.');
    $passed++;
} finally {
    if ($db->inTransaction()) $db->rollBack();
}

$after = (int) $db->query('SELECT COUNT(*) FROM approvals')->fetchColumn();
$check = $db->prepare('SELECT COUNT(*) FROM purchase_requests WHERE spb_id IN (?, ?, ?)');
$check->execute($ids);
$voidAfter = (int) $db->query('SELECT COUNT(*) FROM spb_void_audit')->fetchColumn();
if ($after !== $before || $voidAfter !== $voidBefore || (int) $check->fetchColumn() !== 0) {
    throw new RuntimeException('Rollback uji SPB tidak bersih.');
}
echo "SPB workflow: {$passed}/7 passed; approvals {$before} -> {$after}; void audit {$voidBefore} -> {$voidAfter}; QA SPB tersisa 0.\n";
