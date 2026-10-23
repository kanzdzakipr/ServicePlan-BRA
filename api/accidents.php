<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AccidentWorkflow.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $scope = api_location_scope_clause('a', 'accident_location_id');
    $sql = "SELECT ac.*, l.location_name FROM accidents ac
            INNER JOIN assets a ON a.asset_id = ac.asset_id
            LEFT JOIN locations l ON l.location_id = ac.location_id
            WHERE NOT EXISTS (
                SELECT 1 FROM archived_items arc 
                WHERE arc.item_type = 'accident' AND arc.item_id = ac.accident_id
            )
            AND NOT EXISTS (
                SELECT 1 FROM archived_items arc 
                WHERE arc.item_type = 'asset' AND arc.item_id = a.asset_id
            )";
    if ($scope['sql'] !== '') $sql .= ' AND ' . $scope['sql'];
    $sql .= ' ORDER BY ac.incident_datetime DESC, ac.accident_id DESC LIMIT 300';
    $stmt = $db->prepare($sql);
    $stmt->execute($scope['params']);
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
        if (!is_array($payload)) $payload = [];
        $payload = array_replace($payload, [
            'docNo' => (string) $row['accident_id'],
            'reportDate' => (string) $row['report_date'],
            'incidentDate' => (string) $row['incident_datetime'],
            'unitCode' => (string) $row['asset_id'],
            'location' => (string) ($payload['location'] ?? $row['location_name'] ?? ''),
            'operatorName' => (string) ($row['operator_name'] ?? ''),
            'operatorTenure' => (string) ($row['operator_tenure'] ?? ''),
            'chronology' => (string) $row['chronology'],
            'severity' => (string) $row['severity'],
            'isUnitLocked' => (bool) $row['is_unit_locked'],
            'status' => (string) $row['status'],
            'releasedAt' => $row['released_at'],
            'releasedBy' => $row['released_by'],
            'releaseReason' => $row['release_reason'],
        ]);
        if (!isset($payload['environment']) || !is_array($payload['environment'])) {
            $payload['environment'] = [
                'weather' => (string) ($row['weather'] ?? ''),
                'roadCondition' => (string) ($row['road_condition'] ?? ''),
                'lighting' => (string) ($row['lighting_condition'] ?? ''),
            ];
        }
        if (!isset($payload['impact']) || !is_array($payload['impact'])) {
            $payload['impact'] = [
                'physicalDamage' => (string) ($row['physical_damage'] ?? ''),
                'estimatedRepairCost' => (float) $row['estimated_repair_cost'],
                'estimatedDowntimeDays' => (int) $row['estimated_downtime_days'],
                'totalFinancialImpact' => (float) $row['total_financial_loss'],
            ];
        }
        $results[] = $payload;
    }
    api_json_response(200, ['status' => 'success', 'data' => $results]);
}

if (!in_array($method, ['POST', 'PUT'], true)) {
    header('Allow: GET, POST, PUT');
    api_json_response(405, ['status' => 'error', 'code' => 'METHOD_NOT_ALLOWED', 'message' => 'Metode tidak didukung.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    api_json_response(422, ['status' => 'error', 'code' => 'INVALID_PAYLOAD', 'message' => 'Payload tidak valid.']);
}

try {
    $db->beginTransaction();
    if ($method === 'PUT') {
        if (($input['action'] ?? '') !== 'release') {
            throw new DomainException('Aksi HSE tidak valid.');
        }
        $accidentId = trim((string) ($input['accident_id'] ?? ''));
        $assetLookup = $db->prepare('SELECT asset_id FROM accidents WHERE accident_id = :id LIMIT 1');
        $assetLookup->execute([':id' => $accidentId]);
        $assetId = $assetLookup->fetchColumn();
        if ($assetId === false) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response(404, ['status' => 'error', 'code' => 'NOT_FOUND', 'message' => 'Laporan insiden tidak ditemukan.']);
        }
        api_require_asset_access($db, (string) $assetId);
        $result = AccidentWorkflow::release(
            $db,
            $accidentId,
            (int) api_current_user()['id'],
            (string) ($input['reason'] ?? '')
        );
        $db->commit();
        api_json_response(200, ['status' => 'success', 'message' => 'Hold unit berhasil dirilis.', 'data' => $result]);
    }

    $records = array_is_list($input) ? $input : [$input];
    if ($records === []) throw new DomainException('Tidak ada laporan insiden yang dikirim.');
    $saved = [];
    foreach ($records as $record) {
        if (!is_array($record)) throw new DomainException('Format laporan insiden tidak valid.');
        api_require_asset_access($db, (string) ($record['unitCode'] ?? ''));
        $saved[] = AccidentWorkflow::record($db, $record, (int) api_current_user()['id']);
    }
    $db->commit();
    api_json_response(201, ['status' => 'success', 'message' => 'Laporan insiden tersimpan.', 'data' => $saved]);
} catch (DomainException $error) {
    if ($db->inTransaction()) $db->rollBack();
    $code = str_contains($error->getMessage(), 'sudah digunakan') ? 409 : 422;
    api_json_response($code, ['status' => 'error', 'code' => 'HSE_VALIDATION_FAILED', 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Accident workflow failed: ' . $error->getMessage());
    api_json_response(500, ['status' => 'error', 'code' => 'HSE_SAVE_FAILED', 'message' => 'Proses HSE gagal disimpan.']);
}
