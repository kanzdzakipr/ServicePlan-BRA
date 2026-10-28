<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AssetOnboarding.php';
$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'POST') {
    $data = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($data)) {
        api_json_response(422, ['status' => 'error', 'message' => 'Payload JSON tidak valid.']);
    }
    $action = trim((string) ($data['action'] ?? ''));
    $assetId = trim((string) ($data['asset_id'] ?? ''));
    api_require_asset_access($db, $assetId);

    if ($action === 'enroll_unit') {
        try {
            $currentMeter = AssetOnboarding::nonNegative($data['current_meter'] ?? $data['last_hm_km'] ?? null, 'Meter unit saat ini');
            if ($currentMeter === null) {
                throw new InvalidArgumentException('Meter unit saat ini wajib diisi.');
            }
            $onboardingInput = [
                'current_operator_name' => $data['current_operator_name'] ?? $data['operator'] ?? null,
                'last_service_date' => $data['last_service_date'] ?? null,
                'last_service_hm_km' => $data['last_service_hm_km'] ?? null,
                'service_interval_val' => $data['service_interval_val'] ?? null,
                'service_interval_unit' => $data['service_interval_unit'] ?? null,
                'daily_usage_coefficient' => $data['daily_usage_coefficient'] ?? null,
            ];
            $computed = AssetOnboarding::fields($onboardingInput, $currentMeter);
            if ($computed['service_interval_val'] === null || $computed['next_service_due_hm_km'] === null) {
                throw new InvalidArgumentException('Tanggal dan meter servis terakhir, interval, jenis meter, serta pemakaian harian wajib diisi lengkap.');
            }
        } catch (InvalidArgumentException $e) {
            api_json_response(422, ['status' => 'error', 'message' => $e->getMessage()]);
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM assets WHERE asset_id = :id FOR UPDATE');
            $stmt->execute([':id' => $assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$asset) {
                throw new InvalidArgumentException('Data unit aset tidak ditemukan.');
            }

            $operatorName = $computed['current_operator_name'] ?? ($asset['current_operator_name'] ?? null);
            $plannerNote = trim((string) ($data['planner_note'] ?? '')) ?: null;

            $update = $db->prepare('
                UPDATE assets
                SET last_hm_km = :current_meter,
                    current_operator_name = :operator,
                    last_service_date = :service_date,
                    last_service_hm_km = :service_meter,
                    service_interval_val = :interval_val,
                    service_interval_unit = :interval_unit,
                    daily_usage_coefficient = :daily_rate,
                    next_service_due_hm_km = :next_due,
                    next_service_estimated_date = :next_estimate
                WHERE asset_id = :id
            ');
            $update->execute([
                ':current_meter' => $currentMeter,
                ':operator' => $operatorName,
                ':service_date' => $computed['last_service_date'],
                ':service_meter' => $computed['last_service_hm_km'],
                ':interval_val' => $computed['service_interval_val'],
                ':interval_unit' => $computed['service_interval_unit'],
                ':daily_rate' => $computed['daily_usage_coefficient'],
                ':next_due' => $computed['next_service_due_hm_km'],
                ':next_estimate' => $computed['next_service_estimated_date'],
                ':id' => $assetId,
            ]);

            $prevServiceDate = isset($asset['last_service_date']) ? substr((string) $asset['last_service_date'], 0, 10) : '';
            $newServiceDate = substr((string) $computed['last_service_date'], 0, 10);
            $prevServiceMeter = $asset['last_service_hm_km'] !== null ? (float) $asset['last_service_hm_km'] : null;
            if ($prevServiceDate !== $newServiceDate || $prevServiceMeter === null || abs($prevServiceMeter - (float) $computed['last_service_hm_km']) > 0.01) {
                $seq = $db->prepare('SELECT COALESCE(MAX(service_sequence), 0) + 1 FROM asset_service_history WHERE asset_id = :id');
                $seq->execute([':id' => $assetId]);
                $insertHist = $db->prepare('INSERT INTO asset_service_history (asset_id, service_sequence, service_date, service_hm_km, service_type, notes) VALUES (:id, :seq, :date, :meter, :type, :notes)');
                $insertHist->execute([
                    ':id' => $assetId,
                    ':seq' => (int) $seq->fetchColumn(),
                    ':date' => $computed['last_service_date'],
                    ':meter' => $computed['last_service_hm_km'],
                    ':type' => 'Pendaftaran Jadwal PM',
                    ':notes' => $plannerNote,
                ]);
            }

            $variance = $currentMeter - (float) $computed['next_service_due_hm_km'];
            $warningWindow = $computed['service_interval_unit'] === 'KM' ? 500.0 : 50.0;
            $pmStatus = 'PLANNED';
            if ($variance > 0) {
                $pmStatus = 'OVERDUE';
            } elseif (abs($variance) <= $warningWindow) {
                $pmStatus = 'DUE_SOON';
            }

            $warrantyStatus = trim((string) ($asset['warranty'] ?? '')) ?: 'No Warranty';
            $nextPlan = $db->prepare('
                INSERT INTO pm_plans (
                    asset_id, interval_hm, current_smr, last_service_hm,
                    last_service_date, target_due_hm, variance_hm, status,
                    warranty_status, planner_note
                ) VALUES (
                    :id, :interval, :current, :last,
                    :date, :target, :variance, :status,
                    :warranty, :note
                )
            ');
            $nextPlan->execute([
                ':id' => $assetId,
                ':interval' => (int) $computed['service_interval_val'],
                ':current' => $currentMeter,
                ':last' => $computed['last_service_hm_km'],
                ':date' => $newServiceDate,
                ':target' => $computed['next_service_due_hm_km'],
                ':variance' => $variance,
                ':status' => $pmStatus,
                ':warranty' => $warrantyStatus,
                ':note' => $plannerNote,
            ]);

            $db->commit();
            api_json_response(201, [
                'status' => 'success',
                'message' => "Unit {$assetId} berhasil dimasukkan ke tabel Preventive Maintenance.",
                'data' => array_merge($computed, [
                    'asset_id' => $assetId,
                    'last_hm_km' => $currentMeter,
                    'pm_status' => $pmStatus,
                    'planner_note' => $plannerNote,
                ]),
            ]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response($e instanceof InvalidArgumentException ? 422 : 500, [
                'status' => 'error',
                'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Gagal memasukkan unit ke tabel PM.',
            ]);
        }
    }

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

$view = (string) ($_GET['view'] ?? 'table_list');
if ($method !== 'GET' || !in_array($view, ['table_list', 'available_units'], true)) {
    api_json_response(405, ['status' => 'error', 'message' => 'Permintaan tidak didukung.']);
}

$scope = api_location_scope_clause('a', 'pm_asset_location');
if ($view === 'available_units') {
    $sql = 'SELECT a.asset_id, a.asset_code, a.hull_number, a.alias_name, a.serial_number,
                   a.license_plate, a.year_manufacture, a.make_model, a.category,
                   COALESCE(NULLIF(a.last_hm_km, 0), p.current_smr, a.last_hm_km) AS last_hm_km,
                   a.current_location_id, l.location_name, a.current_operator_name,
                   COALESCE(a.last_service_date, p.last_service_date) AS last_service_date,
                   COALESCE(
                       a.last_service_hm_km,
                       p.last_service_hm,
                       CASE
                           WHEN COALESCE(a.next_service_due_hm_km, p.target_due_hm) IS NOT NULL
                            AND COALESCE(a.service_interval_val, p.interval_hm) IS NOT NULL
                           THEN GREATEST(0, COALESCE(a.next_service_due_hm_km, p.target_due_hm) - COALESCE(a.service_interval_val, p.interval_hm))
                           ELSE NULL
                       END
                   ) AS last_service_hm_km,
                   COALESCE(a.service_interval_val, p.interval_hm) AS service_interval_val,
                   COALESCE(
                       NULLIF(a.service_interval_unit, \'\'),
                       CASE WHEN a.category IN (\'Dump Truck\', \'Prime Mover\', \'Light Vehicle\', \'Water Truck\', \'Trado\') THEN \'KM\' ELSE \'HM\' END
                   ) AS service_interval_unit,
                   COALESCE(
                       a.daily_usage_coefficient,
                       CASE WHEN COALESCE(NULLIF(a.service_interval_unit, \'\'), CASE WHEN a.category IN (\'Dump Truck\', \'Prime Mover\', \'Light Vehicle\', \'Water Truck\', \'Trado\') THEN \'KM\' ELSE \'HM\' END) = \'KM\' THEN 150.00 ELSE 7.00 END
                   ) AS daily_usage_coefficient,
                   COALESCE(a.next_service_due_hm_km, p.target_due_hm) AS next_service_due_hm_km,
                   a.next_service_estimated_date, p.pm_plan_id, p.status AS pm_status,
                   p.planner_note,
                   CASE WHEN (a.next_service_due_hm_km IS NOT NULL OR p.pm_plan_id IS NOT NULL) THEN 1 ELSE 0 END AS in_pm_table
            FROM assets a
            LEFT JOIN locations l ON l.location_id = a.current_location_id
            LEFT JOIN pm_plans p ON p.pm_plan_id = (
                SELECT p2.pm_plan_id FROM pm_plans p2 WHERE p2.asset_id = a.asset_id ORDER BY p2.pm_plan_id DESC LIMIT 1
            )
            WHERE a.is_active = 1
              AND NOT EXISTS (
                  SELECT 1 FROM archived_items arc
                  WHERE arc.item_type = \'asset\' AND arc.item_id = a.asset_id
              )';
    if ($scope['sql'] !== '') $sql .= ' AND ' . $scope['sql'];
    $sql .= ' ORDER BY a.asset_code, a.asset_id';
    $stmt = $db->prepare($sql);
    $stmt->execute($scope['params']);
    api_json_response(200, ['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

$sql = 'SELECT a.asset_id, a.asset_code, a.hull_number, a.alias_name, a.serial_number,
               a.license_plate, a.year_manufacture, a.make_model, a.category,
               COALESCE(NULLIF(a.last_hm_km, 0), p.current_smr, a.last_hm_km) AS last_hm_km,
               a.current_location_id, l.location_name, a.current_operator_name,
               COALESCE(a.last_service_date, p.last_service_date) AS last_service_date,
               COALESCE(
                   a.last_service_hm_km,
                   p.last_service_hm,
                   CASE
                       WHEN COALESCE(a.next_service_due_hm_km, p.target_due_hm) IS NOT NULL
                        AND COALESCE(a.service_interval_val, p.interval_hm) IS NOT NULL
                       THEN GREATEST(0, COALESCE(a.next_service_due_hm_km, p.target_due_hm) - COALESCE(a.service_interval_val, p.interval_hm))
                       ELSE NULL
                   END
               ) AS last_service_hm_km,
               COALESCE(a.service_interval_val, p.interval_hm) AS service_interval_val,
               COALESCE(
                   NULLIF(a.service_interval_unit, \'\'),
                   CASE WHEN a.category IN (\'Dump Truck\', \'Prime Mover\', \'Light Vehicle\', \'Water Truck\', \'Trado\') THEN \'KM\' ELSE \'HM\' END
               ) AS service_interval_unit,
               COALESCE(
                   a.daily_usage_coefficient,
                   CASE WHEN COALESCE(NULLIF(a.service_interval_unit, \'\'), CASE WHEN a.category IN (\'Dump Truck\', \'Prime Mover\', \'Light Vehicle\', \'Water Truck\', \'Trado\') THEN \'KM\' ELSE \'HM\' END) = \'KM\' THEN 150.00 ELSE 7.00 END
               ) AS daily_usage_coefficient,
               COALESCE(a.next_service_due_hm_km, p.target_due_hm) AS next_service_due_hm_km,
               a.next_service_estimated_date,
               a.service_manual_pdf_url, p.pm_plan_id, p.status AS pm_status,
               COALESCE(p.warranty_status, NULLIF(a.warranty, \'\'), \'No Warranty\') AS warranty_status,
               p.planner_note
        FROM assets a
        LEFT JOIN locations l ON l.location_id = a.current_location_id
        LEFT JOIN pm_plans p ON p.pm_plan_id = (
            SELECT p2.pm_plan_id FROM pm_plans p2 WHERE p2.asset_id = a.asset_id ORDER BY p2.pm_plan_id DESC LIMIT 1
        )
        WHERE a.is_active = 1
          AND (a.next_service_due_hm_km IS NOT NULL OR p.pm_plan_id IS NOT NULL)
          AND NOT EXISTS (
              SELECT 1 FROM archived_items arc
              WHERE arc.item_type = \'asset\' AND arc.item_id = a.asset_id
          )';
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
    $historyStmt = $db->prepare("SELECT asset_id, service_date, service_hm_km, service_type, mechanic_pic, jo_report_number, notes FROM asset_service_history WHERE asset_id IN ($placeholders) ORDER BY service_date DESC, service_log_id DESC");
    $historyStmt->execute($ids);
    foreach ($historyStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byId[$row['asset_id']]['service_history'][] = $row;
    }
    $photoStmt = $db->prepare("SELECT photo_id, asset_id, photo_title, photo_url FROM asset_gallery_photos WHERE asset_id IN ($placeholders) ORDER BY photo_id DESC");
    $photoStmt->execute($ids);
    foreach ($photoStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byId[$row['asset_id']]['gallery_photos'][] = $row;
    }
}
api_json_response(200, ['status' => 'success', 'data' => $assets]);
