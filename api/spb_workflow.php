<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/core/SpbWorkflow.php';

$db = Database::getInstance();
$method = strtoupper((string) $_SERVER['REQUEST_METHOD']);

if ($method === 'GET') {
    $scope = api_location_scope_clause('a', 'spb_scope_location');
    $requestedSpbId = trim((string) ($_GET['spb_id'] ?? ''));
    if ($requestedSpbId !== '') {
        if (mb_strlen($requestedSpbId, 'UTF-8') > 50) {
            api_json_response(422, ['status' => 'error', 'message' => 'Nomor SPB tidak valid.']);
        }
        $detailSql = 'SELECT pr.spb_id, pr.wo_id, pr.asset_id, pr.requested_by,
                             pr.requested_at, pr.status, pr.urgency, pr.spb_type, pr.notes,
                             u.full_name AS requester_name, l.location_name,
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
        $items = $db->prepare('SELECT pri.id, pri.part_number, pri.description, pri.qty_requested,
                                      pri.status, COALESCE(p.unit_measure, \'PCS\') AS unit_measure
                                FROM purchase_request_items pri
                                LEFT JOIN parts p ON p.part_number = pri.part_number
                                WHERE pri.spb_id = :spb_id ORDER BY pri.id');
        $items->execute([':spb_id' => $requestedSpbId]);
        $document['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
        api_json_response(200, ['status' => 'success', 'data' => $document]);
    }
    $sql = "SELECT pr.spb_id, pr.wo_id, pr.asset_id, pr.requested_by, pr.requested_at,
                   pr.status, pr.urgency, pr.spb_type, pr.notes, u.full_name AS requester_name,
                   COUNT(pri.id) AS item_count, COALESCE(SUM(pri.qty_requested), 0) AS total_quantity,
                   (SELECT ap.status FROM approvals ap WHERE ap.document_type = 'SPB'
                    AND ap.document_id = pr.spb_id ORDER BY ap.approval_id DESC LIMIT 1) AS approval_status,
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
    api_json_response(200, [
        'status' => 'success',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
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
            (id, spb_id, part_number, description, qty_requested, status)
            VALUES (:id, :spb_id, :part_number, :description, :qty_requested, 'Submitted')
        ");

        foreach ($items as $idx => $p) {
            $itemId = sprintf('SPBI-%s-%02d', substr(md5($spbId), 0, 8), $idx + 1);
            $partNumber = trim((string) ($p['part_number'] ?? $p['partNumber'] ?? 'CUSTOM'));
            $desc = trim((string) ($p['description'] ?? $p['nama'] ?? $partNumber));
            $qty = max(1, (int) ($p['qty'] ?? $p['qty_requested'] ?? 1));

            $itemStmt->execute([
                ':id' => $itemId,
                ':spb_id' => $spbId,
                ':part_number' => $partNumber ?: 'CUSTOM',
                ':description' => mb_substr($desc, 0, 255),
                ':qty_requested' => $qty,
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
if ($spbId === '' || !in_array($decision, ['approve', 'reject'], true)) {
    api_json_response(422, ['status' => 'error', 'message' => 'SPB dan keputusan wajib diisi.']);
}
if ($decision === 'reject' && ($reason === '' || mb_strlen($reason, 'UTF-8') > 500)) {
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
