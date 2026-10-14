<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';

$db = Database::getInstance();
$method = strtoupper((string) $_SERVER['REQUEST_METHOD']);

function support_actor(): array
{
    $user = api_current_user();
    return [
        'id' => (int) ($user['id'] ?? 0),
        'name' => trim((string) ($user['full_name'] ?? $user['username'] ?? 'Pengguna')),
    ];
}

function support_error(Throwable $error): never
{
    $status = match (true) {
        $error instanceof InvalidArgumentException => 422,
        $error instanceof DomainException => 409,
        $error instanceof RuntimeException => 404,
        default => 500,
    };
    if ($status === 500) error_log('Work Order support failed: ' . $error->getMessage());
    api_json_response($status, [
        'status' => 'error',
        'message' => $status === 500 ? 'Permintaan kebutuhan Work Order gagal diproses.' : $error->getMessage(),
    ]);
}

try {
    if ($method === 'GET') {
        AuthMiddleware::checkAccess('wo', 'R');
        $woId = trim((string) ($_GET['wo_id'] ?? ''));
        if ($woId === '') throw new InvalidArgumentException('Parameter wo_id wajib diisi.');
        api_require_work_order_access($db, $woId);
        api_json_response(200, ['status' => 'success', 'data' => WorkOrderSupport::overview($db, $woId)]);
    }

    if ($method !== 'POST') {
        api_json_response(405, ['status' => 'error', 'message' => 'Method tidak didukung.']);
    }

    AuthMiddleware::checkAccess('wo', 'U');
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) throw new InvalidArgumentException('Payload JSON tidak valid.');
    $action = trim((string) ($input['action'] ?? ''));
    $actor = support_actor();
    if ($actor['id'] < 1) throw new RuntimeException('Pengguna aktif tidak ditemukan.');

    $db->beginTransaction();
    try {
        if ($action === 'mark_no_parts') {
            $woId = trim((string) ($input['woId'] ?? ''));
            api_require_work_order_access($db, $woId);
            WorkOrderSupport::markNoParts(
                $db,
                $woId,
                (string) ($input['reason'] ?? ''),
                $actor['id'],
                $actor['name']
            );
            $result = ['overview' => WorkOrderSupport::overview($db, $woId)];
        } elseif ($action === 'create_service') {
            $woId = trim((string) ($input['woId'] ?? ''));
            $workOrder = api_require_work_order_access($db, $woId);
            $request = WorkOrderSupport::createServiceRequest(
                $db,
                $woId,
                (string) $workOrder['asset_id'],
                $input,
                $actor['id'],
                $actor['name']
            );
            $result = [
                'serviceRequest' => $request,
                'overview' => WorkOrderSupport::overview($db, $woId),
            ];
        } elseif ($action === 'update_service_status') {
            $requestId = trim((string) ($input['serviceRequestId'] ?? ''));
            if ($requestId === '') throw new InvalidArgumentException('Nomor permintaan layanan wajib diisi.');
            $requestOwner = $db->prepare(
                'SELECT wo_id FROM work_order_service_requests WHERE service_request_id = :id LIMIT 1'
            );
            $requestOwner->execute([':id' => $requestId]);
            $woId = (string) ($requestOwner->fetchColumn() ?: '');
            if ($woId === '') throw new RuntimeException('Permintaan layanan tidak ditemukan.');
            api_require_work_order_access($db, $woId);
            $request = WorkOrderSupport::updateServiceStatus(
                $db,
                $requestId,
                (string) ($input['status'] ?? ''),
                isset($input['assignedTo']) ? (string) $input['assignedTo'] : null,
                isset($input['notes']) ? (string) $input['notes'] : null,
                $actor['id'],
                $actor['name']
            );
            $result = [
                'serviceRequest' => $request,
                'overview' => WorkOrderSupport::overview($db, $woId),
            ];
        } else {
            throw new InvalidArgumentException('Aksi kebutuhan Work Order tidak dikenal.');
        }

        $db->commit();
        api_json_response(200, ['status' => 'success', 'data' => $result]);
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
} catch (Throwable $error) {
    support_error($error);
}

