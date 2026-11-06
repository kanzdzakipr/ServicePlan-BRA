<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/core/SpbWorkflow.php';
require_once dirname(__DIR__) . '/core/InputRevision.php';
require_once dirname(__DIR__) . '/core/PartUnitConversion.php';

$db = Database::getInstance();
$method = strtoupper((string) $_SERVER['REQUEST_METHOD']);

if ($method === 'GET') {
    $scope = api_location_scope_clause('a', 'spb_scope_location');
    $requestedSpbId = trim((string) ($_GET['spb_id'] ?? ''));
    if ($requestedSpbId !== '') {
        if (mb_strlen($requestedSpbId, 'UTF-8') > 50) {
            api_json_response(422, ['status' => 'error', 'message' => 'Nomor SPB tidak valid.']);
        }
        $detailSql = 'SELECT pr.spb_id, pr.parent_spb_id, pr.revision_no, pr.wo_id, pr.asset_id, pr.requested_by,
                             pr.requested_at, pr.status, pr.urgency, pr.spb_type, pr.notes,
                             u.full_name AS requester_name, l.location_name,
                             EXISTS(SELECT 1 FROM purchase_orders po WHERE po.spb_id = pr.spb_id) AS has_purchase_order,
                             EXISTS(SELECT 1 FROM report_purchase_request_integrations rpri WHERE rpri.spb_id = pr.spb_id AND rpri.reversed_at IS NULL) AS owned_by_report,
                             (SELECT ap.status FROM approvals ap WHERE ap.document_type = \'SPB\'
                              AND ap.document_id = pr.spb_id ORDER BY ap.approval_id DESC LIMIT 1) AS approval_status
                      FROM purchase_requests pr
                      LEFT JOIN assets a ON a.asset_id = pr.asset_id
                      LEFT JOIN users u ON u.user_id = pr.requested_by
                      LEFT JOIN locations l ON l.location_id = a.current_location_id
                      WHERE pr.spb_id = :spb_id';
        if ($scope['sql'] !== '') $detailSql .= ' AND (pr.asset_id IS NULL OR ' . $scope['sql'] . ')';
        $detail = $db->prepare($detailSql . ' LIMIT 1');
        $detail->execute(array_merge([':spb_id' => $requestedSpbId], $scope['params']));
        $document = $detail->fetch(PDO::FETCH_ASSOC);
        if (!$document) {
            api_json_response(404, ['status' => 'error', 'message' => 'SPB tidak ditemukan atau tidak dapat diakses.']);
        }
        $items = $db->prepare('SELECT pri.id, pri.part_id, pri.pm_plan_id, pri.part_number, pri.description, pri.qty_requested,
                                      pri.transaction_uom, pri.conversion_factor_snapshot, pri.base_quantity, pri.fulfilled_quantity,
                                      pri.status, COALESCE(p.base_uom,p.unit_measure, \'PCS\') AS unit_measure, p.technical_name, p.authenticity, p.part_group, p.conversion_factor
                                FROM purchase_request_items pri
                                LEFT JOIN parts p ON (p.part_number = pri.part_number OR p.part_id = pri.part_id)
                                WHERE pri.spb_id = :spb_id ORDER BY pri.id');
        $items->execute([':spb_id' => $requestedSpbId]);
        $document['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
        $document['revision_history'] = InputRevision::history($db, 'SPB', $requestedSpbId);
        $document['can_edit_own'] = ((int) $document['requested_by'] === InputRevision::actorId() || InputRevision::isPrivileged())
            && in_array((string) $document['status'], ['Draft', 'Submitted', 'Revision Required'], true)
            && strtoupper((string) ($document['approval_status'] ?? '')) !== 'APPROVED'
            && empty($document['has_purchase_order'])
            && empty($document['owned_by_report']);
        api_json_response(200, ['status' => 'success', 'data' => $document]);
    }
    $sql = "SELECT pr.spb_id, pr.wo_id, pr.asset_id, pr.requested_by, pr.requested_at,
                   pr.status, pr.urgency, pr.spb_type, pr.notes, u.full_name AS requester_name,
                   COUNT(pri.id) AS item_count, COALESCE(SUM(pri.qty_requested), 0) AS total_quantity,
                   (SELECT ap.status FROM approvals ap WHERE ap.document_type = 'SPB'
                    AND ap.document_id = pr.spb_id ORDER BY ap.approval_id DESC LIMIT 1) AS approval_status,
                   EXISTS(SELECT 1 FROM purchase_orders po WHERE po.spb_id = pr.spb_id) AS has_purchase_order,
                   (SELECT rpri.report_id FROM report_purchase_request_integrations rpri
                    WHERE rpri.spb_id = pr.spb_id AND rpri.reversed_at IS NULL
                    ORDER BY rpri.integration_id DESC LIMIT 1) AS source_report_id
            FROM purchase_requests pr
            LEFT JOIN assets a ON a.asset_id = pr.asset_id
            LEFT JOIN users u ON u.user_id = pr.requested_by
            LEFT JOIN purchase_request_items pri ON pri.spb_id = pr.spb_id";
    if ($scope['sql'] !== '') $sql .= ' WHERE (pr.asset_id IS NULL OR ' . $scope['sql'] . ')';
    $sql .= ' GROUP BY pr.spb_id, pr.wo_id, pr.asset_id, pr.requested_by, pr.requested_at,
              pr.status, pr.urgency, pr.spb_type, pr.notes, u.full_name
              ORDER BY pr.requested_at DESC, pr.spb_id DESC LIMIT 500';
    $stmt = $db->prepare($sql);
    $stmt->execute($scope['params']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['can_edit_own'] = ((int) $row['requested_by'] === InputRevision::actorId() || InputRevision::isPrivileged())
            && in_array((string) $row['status'], ['Draft', 'Submitted', 'Revision Required'], true)
            && strtoupper((string) ($row['approval_status'] ?? '')) !== 'APPROVED'
            && empty($row['has_purchase_order'])
            && empty($row['source_report_id']);
    }
    unset($row);
    api_json_response(200, [
        'status' => 'success',
        'data' => $rows,
        'canApprove' => api_has_permission('logistics.write')
            && SpbWorkflow::canApproveRoles((array) (api_current_user()['roles'] ?? [])),
        'canVoid' => api_has_permission('reports.approve'),
    ]);
}

if ($method !== 'POST') {
    api_json_response(405, ['status' => 'error', 'message' => 'Metode tidak didukung.']);
}
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) api_json_response(422, ['status' => 'error', 'message' => 'JSON tidak valid.']);
if (strtolower(trim((string) ($input['action'] ?? ''))) === 'void') {
    api_require_permission('reports.approve');
    $spbId = trim((string) ($input['spbId'] ?? ''));
    $reason = trim((string) ($input['reason'] ?? ''));
    try {
        SpbWorkflow::ensureVoidAudit($db);
        $db->beginTransaction();
        $result = SpbWorkflow::voidOperational($db, $spbId, (int) (api_current_user()['id'] ?? 0), $reason,
            static fn(string $assetId) => api_require_asset_access($db, $assetId));
        $db->commit();
        api_json_response(200, ['status' => 'success', 'data' => $result]);
    } catch (InvalidArgumentException | DomainException | OutOfBoundsException $error) {
        if ($db->inTransaction()) $db->rollBack();
        api_json_response($error instanceof InvalidArgumentException ? 422 : ($error instanceof OutOfBoundsException ? 404 : 409),
            ['status' => 'error', 'message' => $error->getMessage()]);
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('SPB void failed: ' . $error->getMessage());
        api_json_response(500, ['status' => 'error', 'message' => 'SPB gagal di-void.']);
    }
}

$action = strtolower(trim((string) ($input['action'] ?? '')));
if ($action === 'create_revision') {
    api_require_permission('logistics.write');
    $sourceId=trim((string)($input['spbId']??$input['spb_id']??''));
    $reason=InputRevision::reason($input); $idempotencyKey=InputRevision::idempotencyKey($input);
    if($sourceId==='')api_json_response(422,['status'=>'error','message'=>'Nomor SPB sumber wajib diisi.']);
    $db->beginTransaction();
    try{
        $result=SpbWorkflow::createRevision($db,$sourceId,InputRevision::actorId(),$reason,$idempotencyKey,static function(string $assetId)use($db):void{api_require_asset_access($db,$assetId);});
        $db->commit();
        if(!empty($result['idempotent']))api_json_response(200,['status'=>'success','message'=>'Revisi identik sudah diproses.','data'=>$result]);
        api_json_response(201,['status'=>'success','message'=>'Versi revisi dibuat sebagai Draft tanpa menimpa dokumen lama.','data'=>$result]);
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();api_json_response($e instanceof OutOfBoundsException?404:($e instanceof DomainException?409:422),['status'=>'error','message'=>$e->getMessage()]);}
}
if (in_array($action, ['update_own_pending', 'withdraw_own_pending'], true) || $action === 'cancel_own_pending') {
    api_require_permission('logistics.write');
    $spbId = trim((string) ($input['spbId'] ?? $input['spb_id'] ?? ''));
    if ($spbId === '') api_json_response(422, ['status' => 'error', 'message' => 'Nomor SPB wajib diisi.']);
    try {
        $reason = InputRevision::reason($input);
        $idempotencyKey = InputRevision::idempotencyKey($input);
        $db->beginTransaction();
        if ($idempotencyKey !== null && !InputRevision::reserveIdempotency($db, $idempotencyKey, 'SPB', $spbId, strtoupper($action))) {
            $db->rollBack();
            api_json_response(200, ['status'=>'success','message'=>'Permintaan identik sudah diproses.','data'=>['spbId'=>$spbId,'idempotent'=>true]]);
        }
        $lock = $db->prepare(
            "SELECT pr.*,
                    (SELECT ap.status FROM approvals ap WHERE ap.document_type = 'SPB' AND ap.document_id = pr.spb_id ORDER BY ap.approval_id DESC LIMIT 1) AS approval_status,
                    EXISTS(SELECT 1 FROM purchase_orders po WHERE po.spb_id = pr.spb_id) AS has_purchase_order,
                    EXISTS(SELECT 1 FROM report_purchase_request_integrations rpri WHERE rpri.spb_id = pr.spb_id AND rpri.reversed_at IS NULL) AS owned_by_report
             FROM purchase_requests pr WHERE pr.spb_id = :id FOR UPDATE"
        );
        $lock->execute([':id' => $spbId]);
        $current = $lock->fetch(PDO::FETCH_ASSOC);
        if (!$current) throw new OutOfBoundsException('SPB tidak ditemukan.');
        InputRevision::assertOwner($current['requested_by'] ?? null, 'Hanya pembuat SPB yang dapat merevisi input ini.');
        if (!in_array((string) $current['status'], ['Draft', 'Submitted', 'Revision Required'], true)) {
            throw new DomainException('SPB sudah diproses dan tidak dapat direvisi lagi.');
        }
        if (strtoupper((string) ($current['approval_status'] ?? '')) === 'APPROVED' || !empty($current['has_purchase_order'])) {
            throw new DomainException('SPB sudah disetujui atau memiliki PPB sehingga tidak aman untuk direvisi.');
        }
        if (!empty($current['owned_by_report'])) {
            throw new DomainException('SPB ini berasal dari Form. Revisi harus dilakukan pada Form sumbernya agar jejak tetap konsisten.');
        }
        if (!empty($current['asset_id'])) api_require_asset_access($db, (string) $current['asset_id']);

        $oldItemsStmt = $db->prepare('SELECT id, part_number, description, qty_requested, status FROM purchase_request_items WHERE spb_id = :id ORDER BY id');
        $oldItemsStmt->execute([':id' => $spbId]);
        $before = $current;
        $before['items'] = $oldItemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($action === 'cancel_own_pending') {
            if (!in_array((string) $current['status'], ['Draft', 'Submitted', 'Revision Required'], true)) throw new DomainException('Hanya SPB berstatus Draft, Submitted, atau Revision Required yang dapat dibatalkan.');
            $db->prepare("UPDATE purchase_requests SET status='Cancelled', input_revision_no=input_revision_no+1, input_last_edited_at=NOW(), input_last_edited_by=:actor WHERE spb_id=:id")
                ->execute([':actor'=>InputRevision::actorId(), ':id'=>$spbId]);
            $db->prepare("UPDATE purchase_request_items SET status='Cancelled' WHERE spb_id=:id")->execute([':id'=>$spbId]);
            $after = array_merge($before, ['status'=>'Cancelled','input_revision_no'=>(int)($current['input_revision_no'] ?? 0)+1]);
            InputRevision::record($db, 'SPB', $spbId, 'CANCEL', $reason, $before, $after, $idempotencyKey);
            $db->commit();
            api_json_response(200, ['status'=>'success','message'=>'SPB berhasil dibatalkan tanpa menghapus riwayat audit.','data'=>['spbId'=>$spbId,'status'=>'Cancelled']]);
        }

        if ($action === 'withdraw_own_pending') {
            $db->prepare("UPDATE purchase_requests SET status = 'Draft', input_revision_no = input_revision_no + 1, input_last_edited_at = NOW(), input_last_edited_by = :actor WHERE spb_id = :id")
                ->execute([':actor' => InputRevision::actorId(), ':id' => $spbId]);
            $db->prepare("UPDATE purchase_request_items SET status = 'Draft' WHERE spb_id = :id")
                ->execute([':id' => $spbId]);
            $after = array_merge($before, ['status' => 'Draft']);
            InputRevision::record($db, 'SPB', $spbId, 'WITHDRAW', $reason, $before, $after, $idempotencyKey);
            $db->commit();
            api_json_response(200, ['status' => 'success', 'message' => 'SPB ditarik ke Draft dan tetap dapat diedit.', 'data' => ['spbId' => $spbId, 'status' => 'Draft']]);
        }

        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        if (!$items) throw new InvalidArgumentException('Minimal satu item SPB wajib diisi.');
        $urgency = in_array((string) ($input['urgency'] ?? ''), ['Normal', 'Emergency'], true) ? (string) $input['urgency'] : (string) $current['urgency'];
        $notes = trim((string) ($input['notes'] ?? $current['notes'] ?? ''));
        $db->prepare("UPDATE purchase_requests SET urgency = :urgency, notes = :notes, status = 'Submitted', input_revision_no = input_revision_no + 1, input_last_edited_at = NOW(), input_last_edited_by = :actor WHERE spb_id = :id")
            ->execute([':urgency' => $urgency, ':notes' => $notes ?: null, ':actor' => InputRevision::actorId(), ':id' => $spbId]);
        $db->prepare('DELETE FROM purchase_request_items WHERE spb_id = :id')->execute([':id' => $spbId]);
        $insert = $db->prepare("INSERT INTO purchase_request_items (id,spb_id,part_number,part_id,pm_plan_id,description,qty_requested,transaction_uom,conversion_factor_snapshot,base_quantity,status) VALUES (:id,:spb_id,:part,:part_id,:pm_plan_id,:description,:qty,:uom,:factor,:base_qty,'Submitted')");
        $normalizedItems = [];
        foreach ($items as $idx => $item) {
            $part = trim((string) ($item['part_number'] ?? $item['partNumber'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $qty = (int) ($item['qty'] ?? $item['qty_requested'] ?? 0);
            if (($part === '' && $description === '') || $qty < 1) throw new InvalidArgumentException('Part, deskripsi, dan qty setiap baris wajib valid.');
            $part = $part ?: 'CUSTOM';
            $description = $description ?: $part;
            $partStmt=$db->prepare("SELECT part_id,COALESCE(base_uom,unit_measure,'Pcs') base_uom,COALESCE(transaction_uom,base_uom,unit_measure,'Pcs') transaction_uom,conversion_factor FROM parts WHERE part_number=:part LIMIT 1");
            $partStmt->execute([':part'=>$part]); $master=$partStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $uom=trim((string)($item['uom']??$item['transaction_uom']??$master['transaction_uom']??'Pcs'));
            $snapshot=PartUnitConversion::snapshot($qty,$uom,(float)($master['conversion_factor']??1),(string)($master['base_uom']??$uom));
            $insert->execute([':id' => sprintf('SPBI-%s-%02d', substr(md5($spbId . microtime(true)), 0, 8), $idx + 1), ':spb_id' => $spbId, ':part' => mb_substr($part, 0, 100), ':part_id'=>$master['part_id']??null, ':pm_plan_id'=>$item['pm_plan_id']??null, ':description' => mb_substr($description, 0, 255), ':qty' => $qty, ':uom'=>$snapshot['transaction_uom'], ':factor'=>$snapshot['conversion_factor'], ':base_qty'=>$snapshot['base_quantity']]);
            $normalizedItems[] = ['part_number' => $part, 'description' => $description, 'qty_requested' => $qty, 'transaction_uom'=>$snapshot['transaction_uom'], 'base_quantity'=>$snapshot['base_quantity'], 'status' => 'Submitted'];
        }
        $after = array_merge($current, ['urgency' => $urgency, 'notes' => $notes ?: null, 'status' => 'Submitted', 'items' => $normalizedItems]);
        InputRevision::record($db, 'SPB', $spbId, 'UPDATE', $reason, $before, $after, $idempotencyKey);
        $db->commit();
        api_json_response(200, ['status' => 'success', 'message' => 'Revisi SPB tersimpan dan kembali masuk antrean approval.', 'data' => ['spbId' => $spbId, 'status' => 'Submitted', 'itemCount' => count($normalizedItems)]]);
    } catch (InvalidArgumentException | DomainException | OutOfBoundsException $error) {
        if ($db->inTransaction()) $db->rollBack();
        api_json_response($error instanceof InvalidArgumentException ? 422 : ($error instanceof OutOfBoundsException ? 404 : 409), ['status' => 'error', 'message' => $error->getMessage()]);
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('SPB owner revision failed: ' . $error->getMessage());
        api_json_response(500, ['status' => 'error', 'message' => 'Revisi SPB gagal disimpan.']);
    }
}
if ($action === 'create') {
    api_require_permission('logistics.write');
    $spbId = trim((string) ($input['spbId'] ?? $input['spb_id'] ?? ''));
    if ($spbId === '') {
        $year = date('y');
        $month = date('m');
        $spbId = sprintf('SPB-%s%s-%04d', $year, $month, random_int(1000, 9999));
    }
    $woId = !empty($input['woId']) ? trim((string) $input['woId']) : (!empty($input['wo_id']) ? trim((string) $input['wo_id']) : null);
    $assetId = !empty($input['assetId']) ? trim((string) $input['assetId']) : (!empty($input['asset_id']) ? trim((string) $input['asset_id']) : null);
    if ($assetId !== null) {
        api_require_asset_access($db, $assetId);
    }
    $urgency = in_array((string) ($input['urgency'] ?? ''), ['Normal', 'Emergency'], true) ? (string) $input['urgency'] : 'Normal';
    $spbType = in_array((string) ($input['spbType'] ?? $input['spb_type'] ?? ''), ['NORMAL', 'EMERGENCY'], true)
        ? (string) ($input['spbType'] ?? $input['spb_type'])
        : ($woId ? 'EMERGENCY' : 'NORMAL');
    $notes = trim((string) ($input['notes'] ?? ''));
    $items = (array) ($input['items'] ?? []);

    if (empty($items)) {
        api_json_response(422, ['status' => 'error', 'message' => 'Daftar item suku cadang pada SPB wajib diisi.']);
    }

    try {
        $db->beginTransaction();
        $actorId = (int) (api_current_user()['id'] ?? 1);

        $db->prepare("
            INSERT INTO purchase_requests
            (spb_id, wo_id, asset_id, requested_by, urgency, spb_type, status, notes, requested_at)
            VALUES (:spb_id, :wo_id, :asset_id, :requested_by, :urgency, :spb_type, 'Submitted', :notes, NOW())
        ")->execute([
            ':spb_id' => $spbId,
            ':wo_id' => $woId,
            ':asset_id' => $assetId,
            ':requested_by' => $actorId,
            ':urgency' => $urgency,
            ':spb_type' => $spbType,
            ':notes' => $notes ?: null,
        ]);

        $itemStmt = $db->prepare("
            INSERT INTO purchase_request_items
            (id,spb_id,part_number,part_id,pm_plan_id,description,qty_requested,transaction_uom,conversion_factor_snapshot,base_quantity,status)
            VALUES (:id,:spb_id,:part_number,:part_id,:pm_plan_id,:description,:qty_requested,:transaction_uom,:factor,:base_quantity,'Submitted')
        ");

        foreach ($items as $idx => $p) {
            $itemId = sprintf('SPBI-%s-%02d', substr(md5($spbId), 0, 8), $idx + 1);
            $partNumber = trim((string) ($p['part_number'] ?? $p['partNumber'] ?? 'CUSTOM'));
            $desc = trim((string) ($p['description'] ?? $p['nama'] ?? $partNumber));
            $qty = max(1, (int) ($p['qty'] ?? $p['qty_requested'] ?? 1));
            $partStmt=$db->prepare("SELECT part_id,COALESCE(base_uom,unit_measure,'Pcs') base_uom,COALESCE(transaction_uom,base_uom,unit_measure,'Pcs') transaction_uom,conversion_factor FROM parts WHERE part_number=:part LIMIT 1");
            $partStmt->execute([':part'=>$partNumber]); $master=$partStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $uom=trim((string)($p['uom']??$p['transaction_uom']??$master['transaction_uom']??'Pcs'));
            $snapshot=PartUnitConversion::snapshot($qty,$uom,(float)($master['conversion_factor']??1),(string)($master['base_uom']??$uom));

            $itemStmt->execute([
                ':id' => $itemId,
                ':spb_id' => $spbId,
                ':part_number' => $partNumber ?: 'CUSTOM',
                ':part_id' => $master['part_id'] ?? null,
                ':pm_plan_id' => $p['pm_plan_id'] ?? null,
                ':description' => mb_substr($desc, 0, 255),
                ':qty_requested' => $qty,
                ':transaction_uom' => $snapshot['transaction_uom'],
                ':factor' => $snapshot['conversion_factor'],
                ':base_quantity' => $snapshot['base_quantity'],
            ]);
        }

        $db->commit();
        api_json_response(200, [
            'status' => 'success',
            'message' => "SPB #{$spbId} ({$spbType}) berhasil dibuat.",
            'data' => [
                'spbId' => $spbId,
                'spbType' => $spbType,
                'woId' => $woId,
                'assetId' => $assetId,
                'status' => 'Submitted',
                'itemCount' => count($items),
            ]
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('Create SPB failed: ' . $e->getMessage());
        api_json_response(500, ['status' => 'error', 'message' => 'Gagal membuat SPB: ' . $e->getMessage()]);
    }
}

if ($action === 'issue_parts' || $action === 'issue') {
    api_require_permission('logistics.write');
    $spbId = trim((string) ($input['spbId'] ?? $input['spb_id'] ?? ''));
    $items = (array) ($input['items'] ?? []);
    try {
        $db->beginTransaction();
        $currentUser = api_current_user();
        $actorId = (int) ($currentUser['id'] ?? 0);
        $actorName = trim((string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'User Logistik'));
        $result = SpbWorkflow::issueParts(
            $db, $spbId, $items, $actorId, $actorName,
            static fn(string $assetId) => api_require_asset_access($db, $assetId)
        );
        $db->commit();
        api_json_response(200, ['status' => 'success', 'data' => $result]);
    } catch (InvalidArgumentException | DomainException | OutOfBoundsException $error) {
        if ($db->inTransaction()) $db->rollBack();
        api_json_response(
            $error instanceof InvalidArgumentException ? 422 : ($error instanceof OutOfBoundsException ? 404 : 409),
            ['status' => 'error', 'message' => $error->getMessage()]
        );
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('SPB issue parts failed: ' . $error->getMessage());
        api_json_response(500, ['status' => 'error', 'message' => 'Pengeluaran barang SPB gagal: ' . $error->getMessage()]);
    }
}

AuthMiddleware::checkAccess('approval', 'A');
if (!SpbWorkflow::canApproveRoles((array) (api_current_user()['roles'] ?? []))) {
    api_json_response(403, ['status' => 'error', 'message' => 'Role ini tidak berwenang memutuskan SPB.']);
}
$spbId = trim((string) ($input['spbId'] ?? ''));
$decision = strtolower(trim((string) ($input['decision'] ?? '')));
$reason = trim((string) ($input['reason'] ?? ''));
if ($spbId === '' || !in_array($decision, ['approve', 'reject', 'request_revision'], true)) {
    api_json_response(422, ['status' => 'error', 'message' => 'SPB dan keputusan wajib diisi.']);
}
if (in_array($decision, ['reject', 'request_revision'], true) && ($reason === '' || mb_strlen($reason, 'UTF-8') > 500)) {
    api_json_response(422, ['status' => 'error', 'message' => 'Alasan penolakan wajib diisi (maksimal 500 karakter).']);
}

try {
    $db->beginTransaction();
    $actorId = (int) (api_current_user()['id'] ?? 0);
    $result = SpbWorkflow::decide(
        $db, $spbId, $actorId, $decision, $reason,
        static fn(string $assetId) => api_require_asset_access($db, $assetId)
    );
    $db->commit();
    api_json_response(200, ['status' => 'success', 'data' => $result]);
} catch (InvalidArgumentException | DomainException | OutOfBoundsException $error) {
    if ($db->inTransaction()) $db->rollBack();
    api_json_response($error instanceof InvalidArgumentException ? 422 : ($error instanceof OutOfBoundsException ? 404 : 409), [
        'status' => 'error', 'message' => $error->getMessage(),
    ]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('SPB approval failed: ' . $error->getMessage());
    api_json_response(500, ['status' => 'error', 'message' => 'Keputusan SPB gagal disimpan.']);
}
