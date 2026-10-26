<?php
declare(strict_types=1);

require_once 'db.php';
require_once 'security.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

api_require_authenticated();
$currentUser = api_current_user();
$currentUserId = (int) ($currentUser['id'] ?? 0);
$currentUserName = trim((string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'User'));

if ($method === 'GET') {
    api_require_permission('dashboard.read');
    try {
        $stmt = $db->query("
            SELECT setting_key, setting_value, category, description, 
                   updated_by_user_id, updated_by_name, updated_at
            FROM operational_settings
            ORDER BY category ASC, setting_key ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settingsMap = [];
        foreach ($rows as $row) {
            $settingsMap[$row['setting_key']] = $row['setting_value'];
        }

        $customMatrix = null;
        if (!empty($settingsMap['cfg_rbac_matrix'])) {
            $decoded = json_decode($settingsMap['cfg_rbac_matrix'], true);
            if (is_array($decoded)) {
                $customMatrix = $decoded;
            }
        }

        api_json_response(200, [
            'status' => 'success',
            'settings' => $settingsMap,
            'rbac_matrix' => $customMatrix,
            'data' => $rows
        ]);
    } catch (PDOException $e) {
        api_json_response(500, ['status' => 'error', 'message' => 'Gagal memuat pengaturan: ' . $e->getMessage()]);
    }
}

if ($method === 'POST') {
    api_verify_csrf();

    // Restrict mutations to Administrator
    $isAdmin = api_can_manage_users($currentUser)
        || in_array('Administrator', (array) ($currentUser['roles'] ?? []), true)
        || strtolower((string) ($currentUser['username'] ?? '')) === 'admin';

    if (!$isAdmin) {
        api_json_response(403, [
            'status' => 'error',
            'code' => 'FORBIDDEN',
            'message' => 'Hanya Administrator yang berwenang mengubah konfigurasi operasional sistem.'
        ]);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = trim((string) ($input['action'] ?? ''));

    // Handle RBAC Matrix Save
    if ($action === 'save_rbac_matrix') {
        $matrix = $input['matrix'] ?? null;
        if (!is_array($matrix) || empty($matrix)) {
            api_json_response(422, ['status' => 'error', 'message' => 'Data matriks RBAC tidak valid.']);
        }

        foreach ($matrix as $r) {
            if (!isset($r['name']) || !isset($r['permissions']) || !is_array($r['permissions'])) {
                api_json_response(422, ['status' => 'error', 'message' => 'Format item matriks RBAC tidak valid.']);
            }
        }

        $jsonMatrix = json_encode($matrix, JSON_UNESCAPED_UNICODE);
        $chk = $db->prepare("SELECT COUNT(*) FROM operational_settings WHERE setting_key = 'cfg_rbac_matrix'");
        $chk->execute();
        if ((int)$chk->fetchColumn() > 0) {
            $upd = $db->prepare("
                UPDATE operational_settings 
                SET setting_value = :val, updated_by_user_id = :uid, updated_by_name = :uname, updated_at = NOW() 
                WHERE setting_key = 'cfg_rbac_matrix'
            ");
            $upd->execute([
                ':val' => $jsonMatrix,
                ':uid' => $currentUserId > 0 ? $currentUserId : null,
                ':uname' => $currentUserName
            ]);
        } else {
            $ins = $db->prepare("
                INSERT INTO operational_settings (setting_key, setting_value, category, description, updated_by_user_id, updated_by_name, updated_at) 
                VALUES ('cfg_rbac_matrix', :val, 'rbac', 'Custom RBAC Matrix Override', :uid, :uname, NOW())
            ");
            $ins->execute([
                ':val' => $jsonMatrix,
                ':uid' => $currentUserId > 0 ? $currentUserId : null,
                ':uname' => $currentUserName
            ]);
        }

        api_json_response(200, [
            'status' => 'success',
            'message' => 'Matriks Hak Akses (RCUAO) berhasil disimpan ke database.',
            'matrix' => $matrix
        ]);
    }

    // Handle RBAC Matrix Reset
    if ($action === 'reset_rbac_matrix') {
        $del = $db->prepare("DELETE FROM operational_settings WHERE setting_key = 'cfg_rbac_matrix'");
        $del->execute();

        api_json_response(200, [
            'status' => 'success',
            'message' => 'Matriks Hak Akses (RCUAO) berhasil di-reset ke standar default master.'
        ]);
    }

    $settingsToUpdate = $input['settings'] ?? $input;
    if (!is_array($settingsToUpdate) || empty($settingsToUpdate)) {
        api_json_response(422, ['status' => 'error', 'message' => 'Payload pengaturan kosong atau tidak valid.']);
    }

    // Validation definitions
    $allowedKeys = [
        'cfg_sla_wo' => ['type' => 'int', 'min' => 5, 'max' => 1440, 'desc' => 'SLA Tiket WO (Menit)'],
        'cfg_tire_red' => ['type' => 'float', 'min' => 0.5, 'max' => 30.0, 'desc' => 'Batas Merah Aus Ban (mm)'],
        'cfg_pm_warning' => ['type' => 'int', 'min' => 10, 'max' => 500, 'desc' => 'Batas HM Warning PM (HM)'],
        'cfg_fuel_anomali' => ['type' => 'float', 'min' => 1.0, 'max' => 100.0, 'desc' => 'Toleransi Anomali BBM (%)'],
        'cfg_fuel_price_per_liter' => ['type' => 'float', 'min' => 1000.0, 'max' => 100000.0, 'desc' => 'Harga Solar Industri per Liter (Rp)'],
        'cfg_idling_limit' => ['type' => 'float', 'min' => 5.0, 'max' => 100.0, 'desc' => 'Telematika Idling Alarm (%)'],
        'cfg_cuttingbit_safety' => ['type' => 'int', 'min' => 0, 'max' => 50000, 'desc' => 'Safety Stock Cutting Bit'],
        'cfg_idle_timeout' => ['type' => 'int', 'min' => 5, 'max' => 480, 'desc' => 'Session Timeout (Menit)'],
        'cfg_backup_frequency' => ['type' => 'string', 'max_len' => 100, 'desc' => 'Frekuensi Backup Database'],
        'cfg_wo_prefix' => ['type' => 'string', 'max_len' => 50, 'desc' => 'Prefix Penomoran WO'],
        'cfg_po_prefix' => ['type' => 'string', 'max_len' => 50, 'desc' => 'Prefix Penomoran PO'],
        'cfg_company_header' => ['type' => 'string', 'max_len' => 255, 'desc' => 'Header Kop Surat Perusahaan'],
    ];

    $validated = [];
    foreach ($settingsToUpdate as $rawKey => $rawValue) {
        // Map kebab-case or alternate names from UI (e.g. cfg-sla-wo -> cfg_sla_wo)
        $cleanKey = str_replace('-', '_', trim((string) $rawKey));
        if (!isset($allowedKeys[$cleanKey])) {
            continue; // Skip unrecognized keys safely
        }

        $def = $allowedKeys[$cleanKey];
        if ($def['type'] === 'int') {
            $intVal = filter_var($rawValue, FILTER_VALIDATE_INT);
            if ($intVal === false || $intVal < $def['min'] || $intVal > $def['max']) {
                api_json_response(422, [
                    'status' => 'error',
                    'message' => "Nilai untuk {$def['desc']} harus berupa angka bulat antara {$def['min']} dan {$def['max']}."
                ]);
            }
            $validated[$cleanKey] = (string) $intVal;
        } elseif ($def['type'] === 'float') {
            $floatVal = filter_var($rawValue, FILTER_VALIDATE_FLOAT);
            if ($floatVal === false || $floatVal < $def['min'] || $floatVal > $def['max']) {
                api_json_response(422, [
                    'status' => 'error',
                    'message' => "Nilai untuk {$def['desc']} harus berupa angka desimal antara {$def['min']} dan {$def['max']}."
                ]);
            }
            $validated[$cleanKey] = (string) round($floatVal, 2);
        } else { // string
            $strVal = trim((string) $rawValue);
            if ($strVal === '' || mb_strlen($strVal) > $def['max_len']) {
                api_json_response(422, [
                    'status' => 'error',
                    'message' => "Nilai untuk {$def['desc']} tidak boleh kosong dan maksimal {$def['max_len']} karakter."
                ]);
            }
            $validated[$cleanKey] = $strVal;
        }
    }

    if (empty($validated)) {
        api_json_response(422, ['status' => 'error', 'message' => 'Tidak ada parameter pengaturan valid yang dikirimkan.']);
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            UPDATE operational_settings
            SET setting_value = :val,
                updated_by_user_id = :uid,
                updated_by_name = :uname,
                updated_at = NOW()
            WHERE setting_key = :k
        ");

        $updatedCount = 0;
        foreach ($validated as $k => $v) {
            $stmt->execute([
                ':val' => $v,
                ':uid' => $currentUserId > 0 ? $currentUserId : null,
                ':uname' => $currentUserName,
                ':k' => $k
            ]);
            $updatedCount++;
        }

        $db->commit();
        api_json_response(200, [
            'status' => 'success',
            'message' => 'Konfigurasi operasional sistem berhasil disimpan ke database.',
            'updated_keys' => array_keys($validated)
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        api_json_response(500, ['status' => 'error', 'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()]);
    }
}

api_json_response(405, ['status' => 'error', 'code' => 'METHOD_NOT_ALLOWED', 'message' => 'Metode HTTP tidak didukung.']);

