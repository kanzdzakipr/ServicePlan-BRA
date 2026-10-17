<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AssetOnboarding.php';
$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'POST') {
    $data = json_decode((string) file_get_contents('php://input'), true);
    $assetId = trim((string) ($data['asset_id'] ?? ''));
    api_require_asset_access($db, $assetId);
    try {
        $date = AssetOnboarding::date($data['actual_date'] ?? null, true);
        $meter = AssetOnboarding::nonNegative($data['actual_meter'] ?? null, 'Meter realisasi');
    } catch (InvalidArgumentException $e) {
        api_json_response(422, ['status' => 'error', 'message' => $e->getMessage()]);
    }
    if ($date === null || $meter === null) api_json_response(422, ['status' => 'error', 'message' => 'Tanggal dan meter realisasi wajib diisi.']);
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM assets WHERE asset_id = :id FOR UPDATE');
        $stmt->execute([':id' => $assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$asset || empty($asset['service_interval_val']) || empty($asset['daily_usage_coefficient'])) throw new InvalidArgumentException('Interval dan laju pemakaian harus diisi sebelum mencatat realisasi.');
        if ($meter < (float) $asset['last_service_hm_km']) throw new InvalidArgumentException('Meter realisasi tidak boleh lebih kecil dari servis terakhir.');
        $computed = AssetOnboarding::fields(array_merge($asset, ['last_service_date' => $date, 'last_service_hm_km' => $meter]), max((float) $asset['last_hm_km'], $meter));
        $update = $db->prepare('UPDATE assets SET last_service_date = :date, last_service_hm_km = :meter, last_hm_km = GREATEST(last_hm_km, :meter_current), next_service_due_hm_km = :target, next_service_estimated_date = :estimate WHERE asset_id = :id');
        $update->execute([':date' => $date, ':meter' => $meter, ':meter_current' => $meter, ':target' => $computed['next_service_due_hm_km'], ':estimate' => $computed['next_service_estimated_date'], ':id' => $assetId]);
        $seq = $db->prepare('SELECT COALESCE(MAX(service_sequence), 0) + 1 FROM asset_service_history WHERE asset_id = :id');
        $seq->execute([':id' => $assetId]);
        $insert = $db->prepare('INSERT INTO asset_service_history (asset_id, service_sequence, service_date, service_hm_km, service_type, mechanic_pic, jo_report_number, notes) VALUES (:id, :seq, :date, :meter, :type, :mechanic, :wo, :notes)');
        $insert->execute([':id' => $assetId, ':seq' => (int) $seq->fetchColumn(), ':date' => $date, ':meter' => $meter, ':type' => 'PM realisasi', ':mechanic' => trim((string) ($data['mechanic'] ?? '')) ?: null, ':wo' => trim((string) ($data['wo'] ?? '')) ?: null, ':notes' => trim((string) ($data['planner_note'] ?? '')) ?: null]);
        $nextPlan = $db->prepare('INSERT INTO pm_plans (asset_id, interval_hm, current_smr, last_service_hm, last_service_date, target_due_hm, variance_hm, status, planner_note) VALUES (:id, :interval, :current, :last, :date, :target, :variance, :status, :note)');
        $nextPlan->execute([
            ':id' => $assetId, ':interval' => (int) $asset['service_interval_val'], ':current' => max((float) $asset['last_hm_km'], $meter),
            ':last' => $meter, ':date' => substr($date, 0, 10), ':target' => $computed['next_service_due_hm_km'],
            ':variance' => max((float) $asset['last_hm_km'], $meter) - $computed['next_service_due_hm_km'],
            ':status' => 'PLANNED', ':note' => trim((string) ($data['planner_note'] ?? '')) ?: null,
        ]);
        $db->commit();
        api_json_response(201, ['status' => 'success', 'message' => 'Realisasi PM tersimpan.', 'data' => $computed]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        api_json_response($e instanceof InvalidArgumentException ? 422 : 500, ['status' => 'error', 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Gagal menyimpan realisasi PM.']);
    }
}

if ($method !== 'GET' || ($_GET['view'] ?? 'table_list') !== 'table_list') {
    api_json_response(405, ['status' => 'error', 'message' => 'Permintaan tidak didukung.']);
}

$scope = api_location_scope_clause('a', 'pm_asset_location');
$sql = 'SELECT a.asset_id, a.asset_code, a.hull_number, a.alias_name, a.serial_number,
               a.license_plate, a.year_manufacture, a.make_model, a.category, a.last_hm_km,
               a.current_location_id, l.location_name, a.current_operator_name,
               a.last_service_date, a.last_service_hm_km, a.service_interval_val,
               a.service_interval_unit, a.daily_usage_coefficient,
               a.next_service_due_hm_km, a.next_service_estimated_date,
               a.service_manual_pdf_url, p.pm_plan_id, p.status AS pm_status,
               p.warranty_status, p.planner_note
        FROM assets a
        LEFT JOIN locations l ON l.location_id = a.current_location_id
        LEFT JOIN pm_plans p ON p.pm_plan_id = (
            SELECT p2.pm_plan_id FROM pm_plans p2 WHERE p2.asset_id = a.asset_id ORDER BY p2.pm_plan_id DESC LIMIT 1
        )
        WHERE a.is_active = 1';
if ($scope['sql'] !== '') $sql .= ' AND ' . $scope['sql'];
$sql .= ' ORDER BY a.asset_code, a.asset_id';
$stmt = $db->prepare($sql);
$stmt->execute($scope['params']);
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
$byId = [];
foreach ($assets as &$asset) {
    $asset['service_history'] = [];
    $asset['gallery_photos'] = [];
    $byId[$asset['asset_id']] = &$asset;
}
unset($asset);

if ($assets) {
    $ids = array_keys($byId);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $historyStmt = $db->prepare("SELECT asset_id, service_date, service_hm_km, service_type FROM asset_service_history WHERE asset_id IN ($placeholders) ORDER BY service_date DESC, service_log_id DESC");
    $historyStmt->execute($ids);
    foreach ($historyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byId[$row['asset_id']]['service_history'][] = $row;
    }
    $photoStmt = $db->prepare("SELECT asset_id, photo_title, photo_url FROM asset_gallery_photos WHERE asset_id IN ($placeholders) ORDER BY photo_id DESC");
    $photoStmt->execute($ids);
    foreach ($photoStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byId[$row['asset_id']]['gallery_photos'][] = $row;
    }
}
api_json_response(200, ['status' => 'success', 'data' => $assets]);
