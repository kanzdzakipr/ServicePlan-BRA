<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/core/SpbWorkflow.php';
require_once dirname(__DIR__) . '/core/PurchaseOrderApproval.php';

$db = Database::getInstance();
api_require_authenticated();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $scope = api_location_scope_clause('a', 'po_scope_location');
    $sql = "SELECT po.ppb_id, po.spb_id, po.wo_id, po.asset_id, po.vendor,
                   po.total_amount, po.status, po.created_at
            FROM purchase_orders po JOIN assets a ON a.asset_id = po.asset_id";
    if ($scope['sql'] !== '') $sql .= ' WHERE ' . $scope['sql'];
    $sql .= ' ORDER BY po.created_at DESC, po.ppb_id DESC LIMIT 500';
    $stmt = $db->prepare($sql);
    $stmt->execute($scope['params']);
    api_json_response(200, ['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'canApprove' => api_has_permission('logistics.write')
            && SpbWorkflow::canApproveRoles((array) (api_current_user()['roles'] ?? []))]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_json_response(405, ['status' => 'error', 'message' => 'Metode tidak didukung.']);
AuthMiddleware::checkAccess('approval', 'A');
if (!SpbWorkflow::canApproveRoles((array) (api_current_user()['roles'] ?? []))) {
    api_json_response(403, ['status' => 'error', 'message' => 'Role tidak berwenang menyetujui PO.']);
}
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) api_json_response(422, ['status' => 'error', 'message' => 'JSON tidak valid.']);
$ppbId = trim((string) ($input['ppbId'] ?? ''));
$decision = strtolower(trim((string) ($input['decision'] ?? '')));
$reason = trim((string) ($input['reason'] ?? ''));
if ($ppbId === '' || mb_strlen($ppbId) > 50 || mb_strlen($reason) > 500) {
    api_json_response(422, ['status' => 'error', 'message' => 'Nomor PO atau alasan tidak valid.']);
}
try {
    PurchaseOrderApproval::ensureAuditTable($db);
    $db->beginTransaction();
    $scope = $db->prepare('SELECT asset_id FROM purchase_orders WHERE ppb_id = :id LIMIT 1');
    $scope->execute([':id' => $ppbId]);
    $assetId = $scope->fetchColumn();
    if ($assetId === false) throw new OutOfBoundsException('PO tidak ditemukan.');
    api_require_asset_access($db, (string) $assetId);
    $actorId = (int) (api_current_user()['id'] ?? 0);
    $result = $decision === 'cancel'
        ? PurchaseOrderApproval::cancel($db, $ppbId, $actorId, $reason)
        : PurchaseOrderApproval::decide($db, $ppbId, $actorId, $decision, $reason);
    $db->commit();
    api_json_response(200, ['status' => 'success', 'data' => $result]);
} catch (InvalidArgumentException | DomainException | OutOfBoundsException $error) {
    if ($db->inTransaction()) $db->rollBack();
    api_json_response($error instanceof InvalidArgumentException ? 422 : ($error instanceof OutOfBoundsException ? 404 : 409),
        ['status' => 'error', 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('PO approval failed: ' . $error->getMessage());
    api_json_response(500, ['status' => 'error', 'message' => 'Keputusan PO gagal disimpan.']);
}
