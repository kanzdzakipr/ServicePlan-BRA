<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

require_once dirname(__DIR__) . '/core/AssetIdentity.php';

switch ($method) {
    case 'GET':
        AuthMiddleware::checkAccess('asset', 'R');
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

        $assetId = trim((string) ($data['asset_id'] ?? ''));
        if ($assetId === '' || strlen($assetId) < 2) {
            api_json_response(422, ['status' => 'error', 'code' => 'VALIDATION_ERROR', 'message' => 'Unit ID / Kode Lambung wajib diisi minimal 2 karakter.']);
        }

        $assetCode = trim((string) ($data['asset_code'] ?? $assetId));
        if ($assetCode === '') $assetCode = $assetId;

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

        // 6. Validasi Kategori
        $category = trim((string) ($data['category'] ?? 'Other'));
        if (!in_array($category, ASSET_CATEGORIES, true)) {
            $category = 'Other';
        }

        // 7. Validasi Status
        $status = asset_normalize_status((string) ($data['status'] ?? 'READY'));
        if (!in_array($status, ASSET_STATUSES, true)) {
            $status = 'READY';
        }

        // 8. Validasi Lokasi & Scope
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
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO assets (
                    asset_id, asset_code, serial_number, license_plate,
                    make_model, year_manufacture, type, category, status,
                    current_location_id, last_hm_km, is_active
                ) VALUES (
                    :id, :code, :sn, :plate,
                    :make_model, :year, :type, :cat, :status,
                    :loc_id, :hm, 1
                )
            ");
            $stmt->execute([
                ':id' => $assetId,
                ':code' => $assetCode,
                ':sn' => $snValue,
                ':plate' => $plateValue,
                ':make_model' => $makeModelValue,
                ':year' => $yearValue,
                ':type' => $type,
                ':cat' => $category,
                ':status' => $status,
                ':loc_id' => $targetLocationId,
                ':hm' => $lastHmKm
            ]);
            $db->commit();

            api_json_response(201, [
                "status" => "success",
                "message" => "Unit aset baru '{$assetId}' berhasil didaftarkan.",
                "data" => [
                    "asset_id" => $assetId,
                    "asset_code" => $assetCode,
                    "serial_number" => $snValue,
                    "license_plate" => $plateValue,
                    "make_model" => $makeModelValue,
                    "year_manufacture" => $yearValue,
                    "category" => $category,
                    "status" => $status,
                    "current_location_id" => $targetLocationId,
                    "last_hm_km" => $lastHmKm
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

        if (isset($data['new_hull_number']) || (isset($data['action']) && $data['action'] === 'rename_hull')) {
            $data['alias_name'] = trim((string) ($data['new_hull_number'] ?? ''));
        } elseif (isset($data['hull_number']) && !isset($data['alias_name'])) {
            $data['alias_name'] = trim((string) $data['hull_number']);
        }

        $updatableFields = [
            'asset_code', 'serial_number', 'license_plate', 'make_model',
            'year_manufacture', 'type', 'category', 'status',
            'current_location_id', 'last_hm_km', 'alias_name'
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($updatableFields as $field) {
            if (array_key_exists($field, $data)) {
                $val = $data[$field];
                if (in_array($field, ['serial_number', 'license_plate', 'make_model'], true)) {
                    $val = (trim((string) $val) === '' || trim((string) $val) === '-') ? null : trim((string) $val);
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

        if (empty($fields)) {
            api_json_response(422, ["status" => "error", "message" => "Tidak ada field data yang diubah."]);
        }

        try {
            $db->beginTransaction();
            $query = "UPDATE assets SET " . implode(", ", $fields) . " WHERE asset_id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
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
