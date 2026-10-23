<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

require_once dirname(__DIR__) . '/core/AssetIdentity.php';
require_once dirname(__DIR__) . '/core/AssetOnboarding.php';
require_once dirname(__DIR__) . '/core/AssetSearch.php';

function generate_asset_identifier(PDO $db, string $category): string
{
    $prefixes = [
        'Excavator' => 'EXC',
        'Bulldozer' => 'DZ',
        'Dump Truck' => 'DT',
        'Motor Grader' => 'MG',
        'Vibro Compactor' => 'PF',
        'Water Truck' => 'WT',
        'Prime Mover' => 'PM',
        'Light Vehicle' => 'LV',
        'Reclaimer Spreader' => 'RM',
        'Trado' => 'TRD',
        'Other' => 'AST',
    ];
    $prefix = $prefixes[$category] ?? 'AST';
    $stmt = $db->prepare('SELECT asset_id FROM assets WHERE asset_id LIKE :prefix');
    $stmt->execute([':prefix' => $prefix . '-%']);
    $largest = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $existingId) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string) $existingId, $matches)) {
            $largest = max($largest, (int) $matches[1]);
        }
    }
    return sprintf('%s-%05d', $prefix, $largest + 1);
}

function nullable_asset_text(array $data, string $key, int $maxLength): ?string
{
    $value = trim((string) ($data[$key] ?? ''));
    if ($value === '' || $value === '-') return null;
    if (mb_strlen($value, 'UTF-8') > $maxLength) {
        throw new InvalidArgumentException("Nilai {$key} maksimal {$maxLength} karakter.");
    }
    return $value;
}

switch ($method) {
    case 'GET':
        AuthMiddleware::checkAccess('asset', 'R');
        if (($_GET['action'] ?? '') === 'search') {
            $scope = api_location_scope_clause('a', 'search_asset_location_id');
            try {
                $found = AssetSearch::find($db, (string) ($_GET['q'] ?? ''), $scope);
            } catch (InvalidArgumentException $e) {
                api_json_response(422, ['status' => 'error', 'code' => 'INVALID_SEARCH', 'message' => $e->getMessage()]);
            }
            api_json_response(200, ['status' => 'success', 'data' => $found, 'limit' => AssetSearch::MAX_RESULTS]);
        }
        if (($_GET['references'] ?? '') === 'locations') {
            $locationSql = 'SELECT location_id, location_name FROM locations WHERE is_active = 1';
            $locationParams = [];
            if (!api_has_global_location_scope()) {
                $locationSql .= ' AND location_id = :location_id';
                $locationParams[':location_id'] = api_current_location_id() ?? -1;
            }
            $locationSql .= ' ORDER BY location_name';
            $locationStmt = $db->prepare($locationSql);
            $locationStmt->execute($locationParams);
            api_json_response(200, ['status' => 'success', 'data' => $locationStmt->fetchAll()]);
        }
        $scope = api_location_scope_clause('a', 'asset_list_location_id');

        if (isset($_GET['id'])) {
            $sql = "SELECT a.*, l.location_name as location_name 
                    FROM assets a 
                    LEFT JOIN locations l ON a.current_location_id = l.location_id
                    WHERE a.asset_id = :id";
            if ($scope['sql'] !== '') $sql .= " AND " . $scope['sql'];
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge([':id' => (string) $_GET['id']], $scope['params']));
            $row = $stmt->fetch();
            if (!$row) {
                api_json_response(404, ['status' => 'error', 'code' => 'NOT_FOUND', 'message' => 'Aset tidak ditemukan atau di luar cakupan lokasi Anda.']);
            }
            $completeness = asset_compute_completeness($row);
            $result = array_merge($row, $completeness);
            $historyStmt = $db->prepare('SELECT history_id, old_hull_number, new_hull_number, change_reason, effective_date, changed_by_name, changed_at FROM asset_hull_history WHERE asset_id = :id ORDER BY changed_at DESC, history_id DESC');
            $historyStmt->execute([':id' => (string) $_GET['id']]);
            $result['hull_history'] = $historyStmt->fetchAll();
            foreach ([
                'operator_history' => 'SELECT history_id, operator_name, start_date, end_date, shift_type, handover_notes FROM asset_operator_history WHERE asset_id = :id ORDER BY start_date DESC, history_id DESC',
                'service_history' => 'SELECT service_log_id, service_sequence, service_date, service_hm_km, service_type, mechanic_pic, jo_report_number, notes FROM asset_service_history WHERE asset_id = :id ORDER BY service_date DESC, service_log_id DESC',
                'gallery_photos' => 'SELECT photo_id, photo_title, photo_angle, photo_url, uploaded_at FROM asset_gallery_photos WHERE asset_id = :id ORDER BY photo_id DESC',
            ] as $key => $relatedSql) {
                $relatedStmt = $db->prepare($relatedSql);
                $relatedStmt->execute([':id' => (string) $_GET['id']]);
                $result[$key] = $relatedStmt->fetchAll();
            }
        } else {
            $sql = "SELECT a.*, l.location_name as location_name 
                    FROM assets a 
                    LEFT JOIN locations l ON a.current_location_id = l.location_id";
            if ($scope['sql'] !== '') $sql .= " WHERE " . $scope['sql'];
            $sql .= " ORDER BY a.created_at DESC, a.asset_id ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($scope['params']);
            $rows = $stmt->fetchAll();
            $result = array_map(function (array $row) {
                return array_merge($row, asset_compute_completeness($row));
            }, $rows);
        }

        api_json_response(200, ["status" => "success", "data" => $result]);
        break;

    case 'POST':
        AuthMiddleware::checkAccess('asset', 'C');
        $data = json_decode((string) file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            api_json_response(422, ['status' => 'error', 'code' => 'INVALID_PAYLOAD', 'message' => 'Payload JSON tidak valid.']);
        }

        $category = trim((string) ($data['category'] ?? 'Other'));
        if (!in_array($category, ASSET_CATEGORIES, true)) $category = 'Other';

        $assetId = trim((string) ($data['asset_id'] ?? ''));
        if ($assetId === '') $assetId = generate_asset_identifier($db, $category);
        if (strlen($assetId) < 2) {
            api_json_response(422, ['status' => 'error', 'code' => 'VALIDATION_ERROR', 'message' => 'ID unit tidak valid.']);
        }

        $assetCode = trim((string) ($data['asset_code'] ?? $assetId));
        if ($assetCode === '') $assetCode = $assetId;
        $initialHull = trim((string) ($data['hull_number'] ?? $data['alias_name'] ?? ''));
        if (mb_strlen($initialHull, 'UTF-8') > 100) {
            api_json_response(422, ['status' => 'error', 'message' => 'Nomor lambung maksimal 100 karakter.']);
        }

        // 1. Cek duplikasi Asset ID / Asset Code
        $checkStmt = $db->prepare("SELECT asset_id FROM assets WHERE asset_id = :id OR asset_code = :code LIMIT 1");
        $checkStmt->execute([':id' => $assetId, ':code' => $assetCode]);
        $existing = $checkStmt->fetch();
        if ($existing) {
            api_json_response(409, [
                'status' => 'error',
                'code' => 'DUPLICATE_ASSET_ID',
                'message' => "Unit ID atau Kode Unit '{$assetId}' sudah terdaftar dalam sistem."
            ]);
        }

        // 2. Normalisasi & validasi Serial Number
        $serialNumber = trim((string) ($data['serial_number'] ?? ''));
        $snValue = ($serialNumber !== '' && $serialNumber !== '-') ? $serialNumber : null;
        if ($snValue !== null) {
            $checkSnStmt = $db->prepare("SELECT asset_id FROM assets WHERE serial_number = :sn LIMIT 1");
            $checkSnStmt->execute([':sn' => $snValue]);
            $dupSn = $checkSnStmt->fetch();
            if ($dupSn) {
                api_json_response(409, [
                    'status' => 'error',
                    'code' => 'DUPLICATE_SERIAL_NUMBER',
                    'message' => "Nomor Seri (SN) '{$snValue}' sudah terdaftar pada unit {$dupSn['asset_id']}."
                ]);
            }
        }

        // 3. Normalisasi & validasi Nomor Pelat
        $licensePlate = trim((string) ($data['license_plate'] ?? ''));
        $plateValue = ($licensePlate !== '' && $licensePlate !== '-') ? $licensePlate : null;
        if ($plateValue !== null) {
            $checkPlateStmt = $db->prepare("SELECT asset_id FROM assets WHERE license_plate = :plate LIMIT 1");
            $checkPlateStmt->execute([':plate' => $plateValue]);
            $dupPlate = $checkPlateStmt->fetch();
            if ($dupPlate) {
                api_json_response(409, [
                    'status' => 'error',
                    'code' => 'DUPLICATE_LICENSE_PLATE',
                    'message' => "Nomor Pelat '{$plateValue}' sudah terdaftar pada unit {$dupPlate['asset_id']}."
                ]);
            }
        }

        // 4. Validasi Tahun Pembuatan
        $yearInput = $data['year_manufacture'] ?? null;
        $yearValue = null;
        if ($yearInput !== null && trim((string) $yearInput) !== '') {
            $parsedYear = (int) $yearInput;
            if ($parsedYear < 1970 || $parsedYear > 2099) {
                api_json_response(422, [
                    'status' => 'error',
                    'code' => 'INVALID_YEAR',
                    'message' => 'Tahun pembuatan harus berupa angka tahun valid antara 1970 dan 2099.'
                ]);
            }
            $yearValue = $parsedYear;
        }

        // 5. Validasi Merek / Model
        $makeModel = trim((string) ($data['make_model'] ?? ''));
        $makeModelValue = ($makeModel !== '' && $makeModel !== '-') ? $makeModel : null;

        // 6. Validasi Status
        $status = asset_normalize_status((string) ($data['status'] ?? 'READY'));
        if (!in_array($status, ASSET_STATUSES, true)) {
            $status = 'READY';
        }

        // 7. Validasi Lokasi & Scope
        $targetLocationId = null;
        if (isset($data['current_location_id']) && $data['current_location_id'] !== '' && $data['current_location_id'] !== null) {
            $targetLocationId = (int) $data['current_location_id'];
            if (!api_can_access_location($targetLocationId)) {
                api_json_response(403, [
                    'status' => 'error',
                    'code' => 'LOCATION_SCOPE_DENIED',
                    'message' => 'Lokasi aset berada di luar cakupan izin akun Anda.',
                ]);
            }
        } else {
            $defaultLoc = api_current_location_id();
            if ($defaultLoc !== null && api_can_access_location($defaultLoc)) {
                $targetLocationId = $defaultLoc;
            }
        }

        $type = trim((string) ($data['type'] ?? 'Heavy Equipment'));
        $lastHmKm = isset($data['last_hm_km']) ? max(0.0, (float) $data['last_hm_km']) : 0.00;
        try {
            $registrationFields = [
                'dealer' => nullable_asset_text($data, 'dealer', 150),
                'project_name' => nullable_asset_text($data, 'project_name', 150),
                'operational_position' => nullable_asset_text($data, 'operational_position', 150),
                'invoice_location' => nullable_asset_text($data, 'invoice_location', 150),
                'stnk_location' => nullable_asset_text($data, 'stnk_location', 150),
                'bpkb_location' => nullable_asset_text($data, 'bpkb_location', 150),
                'vehicle_type' => nullable_asset_text($data, 'vehicle_type', 100),
            ];
            $ageInput = $data['asset_age'] ?? null;
            $assetAge = ($ageInput === null || $ageInput === '')
                ? ($yearValue !== null ? max(0, (int) date('Y') - $yearValue) : null)
                : filter_var($ageInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
            if ($assetAge === false) throw new InvalidArgumentException('Usia aset harus berupa angka antara 0 dan 100 tahun.');
            $onboarding = AssetOnboarding::fields($data, $lastHmKm);
            $historicalServices = AssetOnboarding::history($data['historical_services'] ?? []);
            foreach ($historicalServices as $historical) {
                if ($onboarding['last_service_date'] !== null && ($historical['date'] > $onboarding['last_service_date'] || $historical['meter'] > $onboarding['last_service_hm_km'])) {
                    throw new InvalidArgumentException('Riwayat perawatan lampau harus sebelum servis terakhir dan meternya tidak boleh lebih besar.');
                }
            }
        } catch (InvalidArgumentException $e) {
            api_json_response(422, ['status' => 'error', 'code' => 'INVALID_ONBOARDING', 'message' => $e->getMessage()]);
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO assets (
                    asset_id, asset_code, hull_number, alias_name, serial_number, license_plate,
                    make_model, year_manufacture, type, category, status,
                    warranty, chassis_number, dealer, project_name, operational_position,
                    invoice_location, stnk_location, bpkb_location, vehicle_type, asset_age,
                    current_location_id, last_hm_km, is_active,
                    current_operator_name, last_service_date, last_service_hm_km,
                    service_interval_val, service_interval_unit, daily_usage_coefficient,
                    next_service_due_hm_km, next_service_estimated_date,
                    legal_stnk_expiry, legal_kir_expiry, legal_silo_expiry
                ) VALUES (
                    :id, :code, :hull, :alias, :sn, :plate,
                    :make_model, :year, :type, :cat, :status,
                    :warranty, :chassis, :dealer, :project_name, :position,
                    :invoice_location, :stnk_location, :bpkb_location, :vehicle_type, :asset_age,
                    :loc_id, :hm, 1,
                    :operator, :service_date, :service_meter,
                    :interval_val, :interval_unit, :daily_rate,
                    :next_due, :next_estimate,
                    :stnk, :kir, :silo
                )
            ");
            $warrantyVal = trim((string) ($data['warranty'] ?? '0 HM')) ?: '0 HM';
            $chassisVal = trim((string) ($data['chassis_number'] ?? $data['chassis'] ?? '')) ?: null;
            $stmt->execute([
                ':id' => $assetId,
                ':code' => $assetCode,
                ':hull' => $initialHull !== '' ? $initialHull : null,
                ':alias' => $initialHull !== '' ? $initialHull : null,
                ':sn' => $snValue,
                ':plate' => $plateValue,
                ':make_model' => $makeModelValue,
                ':year' => $yearValue,
                ':type' => $type,
                ':cat' => $category,
                ':status' => $status,
                ':warranty' => $warrantyVal,
                ':chassis' => $chassisVal,
                ':dealer' => $registrationFields['dealer'],
                ':project_name' => $registrationFields['project_name'],
                ':position' => $registrationFields['operational_position'],
                ':invoice_location' => $registrationFields['invoice_location'],
                ':stnk_location' => $registrationFields['stnk_location'],
                ':bpkb_location' => $registrationFields['bpkb_location'],
                ':vehicle_type' => $registrationFields['vehicle_type'],
                ':asset_age' => $assetAge,
                ':loc_id' => $targetLocationId,
                ':hm' => $lastHmKm,
                ':operator' => $onboarding['current_operator_name'],
                ':service_date' => $onboarding['last_service_date'],
                ':service_meter' => $onboarding['last_service_hm_km'],
                ':interval_val' => $onboarding['service_interval_val'],
                ':interval_unit' => $onboarding['service_interval_unit'],
                ':daily_rate' => $onboarding['daily_usage_coefficient'],
                ':next_due' => $onboarding['next_service_due_hm_km'],
                ':next_estimate' => $onboarding['next_service_estimated_date'],
                ':stnk' => $onboarding['legal_stnk_expiry'],
                ':kir' => $onboarding['legal_kir_expiry'],
                ':silo' => $onboarding['legal_silo_expiry'],
            ]);
            if ($onboarding['current_operator_name'] !== null) {
                $opStmt = $db->prepare('INSERT INTO asset_operator_history (asset_id, operator_name, start_date, shift_type) VALUES (:id, :name, :start, :shift)');
                $opStmt->execute([':id' => $assetId, ':name' => $onboarding['current_operator_name'], ':start' => AssetOnboarding::date($data['operator_start_date'] ?? null) ?? date('Y-m-d'), ':shift' => trim((string) ($data['operator_shift'] ?? '')) ?: null]);
            }
            $serviceStmt = $db->prepare('INSERT INTO asset_service_history (asset_id, service_sequence, service_date, service_hm_km, service_type, notes) VALUES (:id, :seq, :date, :meter, :type, :notes)');
            foreach ($historicalServices as $index => $historical) {
                $serviceStmt->execute([':id' => $assetId, ':seq' => $index + 1, ':date' => $historical['date'], ':meter' => $historical['meter'], ':type' => $historical['type'], ':notes' => null]);
            }
            if ($onboarding['last_service_date'] !== null) {
                $serviceStmt->execute([':id' => $assetId, ':seq' => count($historicalServices) + 1, ':date' => $onboarding['last_service_date'], ':meter' => $onboarding['last_service_hm_km'], ':type' => 'Servis terakhir saat onboarding', ':notes' => trim((string) ($data['service_notes'] ?? '')) ?: null]);
            }
            $db->commit();

            api_json_response(201, [
                "status" => "success",
                "message" => "Unit aset baru '{$assetId}' berhasil didaftarkan.",
                "data" => [
                    "asset_id" => $assetId,
                    "asset_code" => $assetCode,
                    "hull_number" => $initialHull !== '' ? $initialHull : null,
                    "onboarding" => $onboarding,
                    "serial_number" => $snValue,
                    "license_plate" => $plateValue,
                    "make_model" => $makeModelValue,
                    "year_manufacture" => $yearValue,
                    "category" => $category,
                    "status" => $status,
                    "current_location_id" => $targetLocationId,
                    "last_hm_km" => $lastHmKm,
                    "dealer" => $registrationFields['dealer'],
                    "project_name" => $registrationFields['project_name'],
                    "operational_position" => $registrationFields['operational_position'],
                    "invoice_location" => $registrationFields['invoice_location'],
                    "stnk_location" => $registrationFields['stnk_location'],
                    "bpkb_location" => $registrationFields['bpkb_location'],
                    "vehicle_type" => $registrationFields['vehicle_type'],
                    "asset_age" => $assetAge
                ]
            ]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Asset create failed: ' . $e->getMessage());
            api_json_response(500, ["status" => "error", "message" => "Gagal menyimpan unit aset: " . $e->getMessage()]);
        }
        break;

    case 'PUT':
        AuthMiddleware::checkAccess('asset', 'U');
        $data = json_decode((string) file_get_contents("php://input"), true);
        if (!$data || !is_array($data) || (!isset($data['asset_id']) && !isset($_GET['id']))) {
            api_json_response(422, ["status" => "error", "message" => "Payload tidak valid atau ID aset tidak disertakan."]);
        }

        $id = trim((string) ($_GET['id'] ?? $data['asset_id']));
        api_require_asset_access($db, $id);

        if (array_key_exists('asset_code', $data)) {
            $codeStmt = $db->prepare('SELECT asset_code FROM assets WHERE asset_id = :id');
            $codeStmt->execute([':id' => $id]);
            if (trim((string) $data['asset_code']) !== (string) $codeStmt->fetchColumn()) {
                api_json_response(422, ['status' => 'error', 'code' => 'IMMUTABLE_ASSET_CODE', 'message' => 'Kode unit permanen tidak dapat diubah.']);
            }
        }

        $hasHullInput = array_key_exists('new_hull_number', $data) || array_key_exists('hull_number', $data) || array_key_exists('alias_name', $data);
        if (($data['action'] ?? '') === 'rename_hull' || $hasHullInput) {
            $roles = api_current_user()['roles'] ?? [];
            if (!array_intersect((array) $roles, ['Administrator', 'Asset Manager', 'Equipment Manager', 'Maintenance Planner'])) {
                api_json_response(403, ['status' => 'error', 'code' => 'HULL_RENAME_FORBIDDEN', 'message' => 'Peran Anda tidak dapat mengubah nomor lambung.']);
            }
            $newHull = trim((string) ($data['new_hull_number'] ?? $data['hull_number'] ?? $data['alias_name'] ?? ''));
            $reason = trim((string) ($data['change_reason'] ?? $data['notes'] ?? ''));
            $effectiveDate = trim((string) ($data['effective_date'] ?? ''));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveDate);
            if ($newHull === '' || mb_strlen($newHull, 'UTF-8') > 100 || $reason === '' || mb_strlen($reason, 'UTF-8') > 500 || !$date || $date->format('Y-m-d') !== $effectiveDate) {
                api_json_response(422, ['status' => 'error', 'code' => 'INVALID_HULL_RENAME', 'message' => 'Nomor lambung, alasan, dan tanggal efektif yang valid wajib diisi.']);
            }
            // Identity changes cannot be bundled with unrelated asset updates.
            $allowedKeys = ['asset_id', 'asset_code', 'action', 'new_hull_number', 'hull_number', 'alias_name', 'change_reason', 'notes', 'effective_date'];
            if (array_diff(array_keys($data), $allowedKeys)) {
                api_json_response(422, ['status' => 'error', 'message' => 'Perubahan lambung harus dikirim terpisah dari perubahan data aset lain.']);
            }
            try {
                $db->beginTransaction();
                $lockStmt = $db->prepare('SELECT hull_number, alias_name FROM assets WHERE asset_id = :id FOR UPDATE');
                $lockStmt->execute([':id' => $id]);
                $current = $lockStmt->fetch(PDO::FETCH_ASSOC);
                $oldHull = trim((string) ($current['hull_number'] ?: $current['alias_name'] ?: ''));
                if ($oldHull === $newHull) {
                    $db->rollBack();
                    api_json_response(409, ['status' => 'error', 'code' => 'HULL_UNCHANGED', 'message' => 'Nomor lambung baru sama dengan yang tercatat.']);
                }
                $updateStmt = $db->prepare('UPDATE assets SET hull_number = :hull, alias_name = :alias WHERE asset_id = :id');
                $updateStmt->execute([':hull' => $newHull, ':alias' => $newHull, ':id' => $id]);
                $actor = api_current_user();
                $auditStmt = $db->prepare('INSERT INTO asset_hull_history (asset_id, old_hull_number, new_hull_number, change_reason, effective_date, changed_by_user_id, changed_by_name) VALUES (:id, :old, :new, :reason, :effective, :actor_id, :actor_name)');
                $auditStmt->execute([':id' => $id, ':old' => $oldHull !== '' ? $oldHull : null, ':new' => $newHull, ':reason' => $reason, ':effective' => $effectiveDate, ':actor_id' => $actor['id'] ?? null, ':actor_name' => $actor['full_name'] ?? $actor['username'] ?? 'Unknown']);
                $db->commit();
                api_json_response(200, ['status' => 'success', 'message' => 'Nomor lambung dan riwayatnya berhasil disimpan.', 'data' => ['asset_id' => $id, 'hull_number' => $newHull]]);
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log('Hull rename failed: ' . $e->getMessage());
                api_json_response(500, ['status' => 'error', 'message' => 'Gagal menyimpan perubahan nomor lambung.']);
            }
        }

        $onboardingKeys = ['current_operator_name', 'operator_start_date', 'operator_shift', 'last_service_date', 'last_service_hm_km', 'service_interval_val', 'service_interval_unit', 'daily_usage_coefficient', 'legal_stnk_expiry', 'legal_kir_expiry', 'legal_silo_expiry'];
        $onboarding = null;
        $previous = null;
        if (array_intersect(array_keys($data), $onboardingKeys)) {
            $previousStmt = $db->prepare('SELECT * FROM assets WHERE asset_id = :id');
            $previousStmt->execute([':id' => $id]);
            $previous = $previousStmt->fetch(PDO::FETCH_ASSOC);
            $merged = array_merge($previous, $data);
            try {
                $onboarding = AssetOnboarding::fields($merged, isset($merged['last_hm_km']) ? (float) $merged['last_hm_km'] : 0.0);
                if (array_key_exists('operator_start_date', $data)) AssetOnboarding::date($data['operator_start_date']);
            } catch (InvalidArgumentException $e) {
                api_json_response(422, ['status' => 'error', 'code' => 'INVALID_ONBOARDING', 'message' => $e->getMessage()]);
            }
        }

        if (array_key_exists('current_location_id', $data)) {
            $targetLocationId = ($data['current_location_id'] !== null && $data['current_location_id'] !== '')
                ? (int) $data['current_location_id']
                : null;
            if ($targetLocationId !== null && !api_can_access_location($targetLocationId)) {
                api_json_response(403, [
                    'status' => 'error',
                    'code' => 'LOCATION_SCOPE_DENIED',
                    'message' => 'Lokasi tujuan berada di luar cakupan izin akun Anda.',
                ]);
            }
        }

        // Cek duplikasi SN jika diubah
        if (array_key_exists('serial_number', $data)) {
            $snVal = trim((string) $data['serial_number']);
            if ($snVal !== '' && $snVal !== '-') {
                $dupStmt = $db->prepare("SELECT asset_id FROM assets WHERE serial_number = :sn AND asset_id != :id LIMIT 1");
                $dupStmt->execute([':sn' => $snVal, ':id' => $id]);
                $dup = $dupStmt->fetch();
                if ($dup) {
                    api_json_response(409, [
                        'status' => 'error',
                        'code' => 'DUPLICATE_SERIAL_NUMBER',
                        'message' => "Nomor Seri (SN) '{$snVal}' sudah digunakan oleh unit {$dup['asset_id']}."
                    ]);
                }
            }
        }

        // Cek duplikasi Plat jika diubah
        if (array_key_exists('license_plate', $data)) {
            $plateVal = trim((string) $data['license_plate']);
            if ($plateVal !== '' && $plateVal !== '-') {
                $dupStmt = $db->prepare("SELECT asset_id FROM assets WHERE license_plate = :plate AND asset_id != :id LIMIT 1");
                $dupStmt->execute([':plate' => $plateVal, ':id' => $id]);
                $dup = $dupStmt->fetch();
                if ($dup) {
                    api_json_response(409, [
                        'status' => 'error',
                        'code' => 'DUPLICATE_LICENSE_PLATE',
                        'message' => "Nomor Pelat '{$plateVal}' sudah digunakan oleh unit {$dup['asset_id']}."
                    ]);
                }
            }
        }

        // Validasi Tahun Pembuatan jika diubah
        if (array_key_exists('year_manufacture', $data) && $data['year_manufacture'] !== null && trim((string) $data['year_manufacture']) !== '') {
            $yr = (int) $data['year_manufacture'];
            if ($yr < 1970 || $yr > 2099) {
                api_json_response(422, [
                    'status' => 'error',
                    'code' => 'INVALID_YEAR',
                    'message' => 'Tahun pembuatan harus berupa angka tahun valid antara 1970 dan 2099.'
                ]);
            }
        }

        $updatableFields = [
            'serial_number', 'license_plate', 'make_model',
            'year_manufacture', 'type', 'category', 'status',
            'warranty', 'chassis_number',
            'current_location_id', 'last_hm_km'
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($updatableFields as $field) {
            if (array_key_exists($field, $data)) {
                $val = $data[$field];
                if (in_array($field, ['serial_number', 'license_plate', 'make_model', 'chassis_number'], true)) {
                    $val = (trim((string) $val) === '' || trim((string) $val) === '-') ? null : trim((string) $val);
                } elseif ($field === 'warranty') {
                    $val = trim((string) $val) !== '' ? trim((string) $val) : '0 HM';
                } elseif ($field === 'year_manufacture') {
                    $val = ($val !== null && trim((string) $val) !== '') ? (int) $val : null;
                } elseif ($field === 'current_location_id') {
                    $val = ($val !== null && trim((string) $val) !== '') ? (int) $val : null;
                } elseif ($field === 'status') {
                    $val = asset_normalize_status((string) $val);
                } elseif ($field === 'last_hm_km') {
                    $val = max(0.0, (float) $val);
                }

                $fields[] = "`$field` = :$field";
                $params[":$field"] = $val;
            }
        }
        if ($onboarding !== null) {
            foreach ($onboarding as $field => $value) {
                $fields[] = "`$field` = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($fields)) {
            api_json_response(422, ["status" => "error", "message" => "Tidak ada field data yang diubah."]);
        }

        try {
            $db->beginTransaction();
            $query = "UPDATE assets SET " . implode(", ", $fields) . " WHERE asset_id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            if ($onboarding !== null && $onboarding['current_operator_name'] !== ($previous['current_operator_name'] ?? null)) {
                $closeOp = $db->prepare('UPDATE asset_operator_history SET end_date = CURDATE() WHERE asset_id = :id AND end_date IS NULL');
                $closeOp->execute([':id' => $id]);
                if ($onboarding['current_operator_name'] !== null) {
                    $newOp = $db->prepare('INSERT INTO asset_operator_history (asset_id, operator_name, start_date, shift_type) VALUES (:id, :name, :date, :shift)');
                    $newOp->execute([':id' => $id, ':name' => $onboarding['current_operator_name'], ':date' => AssetOnboarding::date($data['operator_start_date'] ?? null) ?? date('Y-m-d'), ':shift' => trim((string) ($data['operator_shift'] ?? '')) ?: null]);
                }
            }
            if ($onboarding !== null && $onboarding['last_service_date'] !== null && ($onboarding['last_service_date'] !== ($previous['last_service_date'] ?? null) || (float) $onboarding['last_service_hm_km'] !== (float) ($previous['last_service_hm_km'] ?? 0))) {
                $seqStmt = $db->prepare('SELECT COALESCE(MAX(service_sequence), 0) + 1 FROM asset_service_history WHERE asset_id = :id');
                $seqStmt->execute([':id' => $id]);
                $newService = $db->prepare('INSERT INTO asset_service_history (asset_id, service_sequence, service_date, service_hm_km, service_type) VALUES (:id, :seq, :date, :meter, :type)');
                $newService->execute([':id' => $id, ':seq' => (int) $seqStmt->fetchColumn(), ':date' => $onboarding['last_service_date'], ':meter' => $onboarding['last_service_hm_km'], ':type' => 'Servis tercatat']);
            }
            $db->commit();
            api_json_response(200, ["status" => "success", "message" => "Data unit aset '{$id}' berhasil diperbarui."]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Asset update failed: ' . $e->getMessage());
            api_json_response(500, ["status" => "error", "message" => "Gagal memperbarui aset: " . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        AuthMiddleware::checkAccess('asset', 'O');
        if (!isset($_GET['id']) || trim((string) $_GET['id']) === '') {
            api_json_response(422, ["status" => "error", "message" => "ID unit aset wajib disertakan."]);
        }
        $id = trim((string) $_GET['id']);
        api_require_asset_access($db, $id);

        // Periksa relasi penting sebelum delete (Work Orders, Transaksi)
        $chkWo = $db->prepare("SELECT COUNT(*) FROM work_orders WHERE asset_id = :id");
        $chkWo->execute([':id' => $id]);
        if ((int) $chkWo->fetchColumn() > 0) {
            api_json_response(409, [
                'status' => 'error',
                'code' => 'ASSET_HAS_DEPENDENCIES',
                'message' => 'Unit aset tidak dapat dihapus karena memiliki riwayat Work Order aktif.'
            ]);
        }

        try {
            $stmt = $db->prepare("DELETE FROM assets WHERE asset_id = :id");
            $stmt->execute([':id' => $id]);
            api_json_response(200, ["status" => "success", "message" => "Unit aset '{$id}' berhasil dihapus."]);
        } catch (Throwable $e) {
            api_json_response(500, ["status" => "error", "message" => "Gagal menghapus aset: " . $e->getMessage()]);
        }
        break;

    default:
        api_json_response(405, ["status" => "error", "message" => "Metode HTTP tidak didukung."]);
        break;
}
