<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/core/PurchaseOrderApproval.php';
$db = new PDO((string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4'),
    (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'), (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
PurchaseOrderApproval::ensureAuditTable($db);
$context = $db->query("SELECT w.wo_id,w.asset_id FROM work_orders w JOIN assets a ON a.asset_id=w.asset_id WHERE a.is_active=1 AND w.status NOT IN ('Closed','Cancelled') LIMIT 1")->fetch();
if (!$context) throw new RuntimeException('Tidak ada WO QA yang aman.');
$suffix = strtoupper(bin2hex(random_bytes(6)));
$spb = 'QA-F2-SPB-' . $suffix;
$po = 'QA-F2-PO-' . $suffix;
$db->beginTransaction();
try {
    $db->prepare("INSERT INTO purchase_requests(spb_id,wo_id,asset_id,requested_by,urgency,status,requested_at) VALUES(:spb,:wo,:asset,1,'Normal','Approved',NOW())")
        ->execute([':spb'=>$spb, ':wo'=>$context['wo_id'], ':asset'=>$context['asset_id']]);
    $db->prepare("INSERT INTO purchase_orders(ppb_id,spb_id,asset_id,wo_id,vendor,project,delivery_due,delivery_location,subtotal,tax_amount,total_amount,status,created_by) VALUES(:po,:spb,:asset,:wo,'QA Vendor','QA Project',CURRENT_DATE,'QA Yard',1000,110,1110,'Submitted',1)")
        ->execute([':po'=>$po, ':spb'=>$spb, ':asset'=>$context['asset_id'], ':wo'=>$context['wo_id']]);
    $result = PurchaseOrderApproval::decide($db, $po, 1, 'approve');
    if ($result['status'] !== 'Approved') throw new RuntimeException('PO tidak Approved.');
    $stmt = $db->prepare("SELECT amount,source_type,reversed_at FROM asset_cost_transactions WHERE source_type='PO_APPROVED' AND source_id=:po");
    $stmt->execute([':po'=>$po]);
    $rows = $stmt->fetchAll();
    if (count($rows) !== 1 || (float)$rows[0]['amount'] !== 1110.0 || $rows[0]['reversed_at'] !== null) throw new RuntimeException('Biaya PO tidak tepat satu.');
    try { PurchaseOrderApproval::decide($db, $po, 1, 'approve'); throw new RuntimeException('Replay tidak ditolak.'); }
    catch (DomainException $expected) { /* expected */ }
    echo "PASS: PO Approved, biaya satu kali Rp1.110, replay ditolak.\n";
    $cancel = PurchaseOrderApproval::cancel($db, $po, 1, 'Pembatalan QA F2');
    if ($cancel['status'] !== 'Cancelled') throw new RuntimeException('PO gagal dibatalkan.');
    $stmt->execute([':po'=>$po]);
    $rows = $stmt->fetchAll();
    if (count($rows) !== 1 || $rows[0]['reversed_at'] === null) throw new RuntimeException('Biaya PO tidak direversal.');
    try { PurchaseOrderApproval::cancel($db, $po, 1, 'Replay QA F2'); throw new RuntimeException('Reversal kedua tidak ditolak.'); }
    catch (DomainException $expected) { /* expected */ }
    echo "PASS: pembatalan PO membalik biaya sekali; reversal ulang ditolak.\n";
    $audit = $db->prepare('SELECT decision,reason FROM purchase_order_decision_audit WHERE ppb_id=:po ORDER BY audit_id');
    $audit->execute([':po'=>$po]);
    $events = $audit->fetchAll();
    if (count($events) !== 2 || $events[0]['decision'] !== 'APPROVED' || $events[1]['decision'] !== 'CANCELLED' || $events[1]['reason'] !== 'Pembatalan QA F2') {
        throw new RuntimeException('Audit keputusan PO tidak lengkap.');
    }
    echo "PASS: jejak audit approval dan pembatalan tercatat.\n";
    $rejectedPo = $po . '-R';
    $db->prepare("INSERT INTO purchase_orders(ppb_id,spb_id,asset_id,wo_id,vendor,project,delivery_due,delivery_location,subtotal,tax_amount,total_amount,status,created_by) VALUES(:po,:spb,:asset,:wo,'QA Vendor','QA Project',CURRENT_DATE,'QA Yard',1000,110,1110,'Submitted',1)")
        ->execute([':po'=>$rejectedPo, ':spb'=>$spb, ':asset'=>$context['asset_id'], ':wo'=>$context['wo_id']]);
    if (PurchaseOrderApproval::decide($db, $rejectedPo, 1, 'reject', 'Vendor tidak sesuai')['status'] !== 'Cancelled') throw new RuntimeException('Penolakan PO gagal.');
    $stmt->execute([':po'=>$rejectedPo]);
    if ($stmt->fetchAll() !== []) throw new RuntimeException('PO ditolak tidak boleh membuat biaya.');
    $audit->execute([':po'=>$rejectedPo]);
    $rejectEvents = $audit->fetchAll();
    if (count($rejectEvents) !== 1 || $rejectEvents[0]['decision'] !== 'REJECTED') throw new RuntimeException('Audit penolakan PO gagal.');
    echo "PASS: penolakan PO tanpa biaya, dengan alasan teraudit.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
