<?php
declare(strict_types=1);

/**
 * Live HTTP finishing gate for F0, F4, and F5.
 *
 * This test creates isolated QA users/data, exercises the real Apache/PHP
 * endpoints with cookie sessions and CSRF tokens, then removes only records
 * carrying its unique marker. It never imports or resets the database.
 */

$baseUrl = rtrim((string) (getenv('FINISHING_BASE_URL') ?: 'http://serviceplan-bra.test'), '/');
$dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$dbUser = (string) (getenv('DB_USER') ?: 'root');
$dbPassword = (string) (getenv('DB_PASSWORD') ?: '');

if (!function_exists('curl_init')) {
    fwrite(STDERR, "FAIL: ekstensi PHP cURL wajib tersedia.\n");
    exit(1);
}

$pdo = new PDO($dsn, $dbUser, $dbPassword, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$marker = 'qa-finish-http-' . strtolower(bin2hex(random_bytes(4)));
$password = 'HttpQA!' . bin2hex(random_bytes(6));
$adminUsername = $marker . '-admin';
$assessorUsername = $marker . '-assessor';
$managedUsername = $marker . '-managed';
$assetId = strtoupper($marker . '-asset');
$locationId = 64;
$period = '2099-10';
$passed = 0;
$failed = 0;
$createdUserIds = [];
$notificationId = null;
$originalSetting = null;
$settingChanged = false;
$cookies = [];

function qaAssert(bool $condition, string $label, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS: {$label}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
        return;
    }
    $failed++;
    fwrite(STDERR, "FAIL: {$label}" . ($detail !== '' ? " ({$detail})" : '') . "\n");
}

function qaRequest(
    string $sessionName,
    string $path,
    string $method = 'GET',
    ?array $body = null,
    ?string $csrf = null
): array {
    global $baseUrl, $cookies;
    if (!isset($cookies[$sessionName])) {
        $cookie = tempnam(sys_get_temp_dir(), 'bra-finishing-http-');
        if ($cookie === false) {
            throw new RuntimeException('Gagal membuat cookie jar sementara.');
        }
        $cookies[$sessionName] = $cookie;
    }

    $headers = ['Accept: application/json'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($csrf !== null) {
        $headers[] = 'X-CSRF-Token: ' . $csrf;
    }

    $ch = curl_init($baseUrl . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookies[$sessionName],
        CURLOPT_COOKIEFILE => $cookies[$sessionName],
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
    $raw = curl_exec($ch);
    if ($raw === false) {
        $message = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('HTTP gagal: ' . $message);
    }
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $responseBody = substr($raw, $headerSize);
    $json = json_decode($responseBody, true);
    return [
        'status' => $status,
        'body' => $responseBody,
        'json' => is_array($json) ? $json : [],
    ];
}

function qaLogin(string $sessionName, string $username, string $password): array
{
    $bootstrap = qaRequest($sessionName, '/api/auth.php?action=session');
    $csrf = (string) ($bootstrap['json']['csrf_token'] ?? '');
    if ($bootstrap['status'] !== 200 || $csrf === '') {
        throw new RuntimeException("Bootstrap sesi {$sessionName} gagal.");
    }
    $login = qaRequest($sessionName, '/api/auth.php', 'POST', [
        'action' => 'login',
        'username' => $username,
        'password' => $password,
    ], $csrf);
    return [
        'response' => $login,
        'csrf' => (string) ($login['json']['csrf_token'] ?? ''),
        'user' => $login['json']['user'] ?? [],
    ];
}

function qaInsertUser(PDO $pdo, string $username, string $password, int $roleId, int $locationId): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, full_name, role_id, assigned_location_id, is_active, created_at)
         VALUES (:username, :hash, :full_name, :role_id, :location_id, 1, NOW())'
    );
    $stmt->execute([
        ':username' => $username,
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ':full_name' => 'QA Finishing HTTP ' . ucfirst(substr(strrchr($username, '-'), 1)),
        ':role_id' => $roleId,
        ':location_id' => $locationId,
    ]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare(
        'INSERT INTO user_roles (user_id, role_id, is_primary, assigned_at)
         VALUES (:user_id, :role_id, 1, NOW())'
    )->execute([':user_id' => $id, ':role_id' => $roleId]);
    return $id;
}

function qaCleanup(PDO $pdo): void
{
    global $marker, $assetId, $locationId, $period, $notificationId,
           $originalSetting, $settingChanged, $createdUserIds, $cookies;

    try {
        if ($settingChanged && $originalSetting !== null) {
            $pdo->prepare(
                "UPDATE operational_settings SET setting_value = :value WHERE setting_key = 'cfg_sla_wo'"
            )->execute([':value' => $originalSetting]);
        }

        $pdo->prepare('DELETE FROM head_kpi_audit_trail WHERE period_key = :period AND location_id = :location')
            ->execute([':period' => $period, ':location' => $locationId]);
        $pdo->prepare('DELETE FROM head_kpi_assessments WHERE period_key = :period AND location_id = :location')
            ->execute([':period' => $period, ':location' => $locationId]);

        if ($notificationId !== null) {
            $pdo->prepare('DELETE FROM notification_recipients WHERE notification_id = :id')
                ->execute([':id' => $notificationId]);
            $pdo->prepare('DELETE FROM system_notifications WHERE notification_id = :id')
                ->execute([':id' => $notificationId]);
        }

        $pdo->prepare('DELETE FROM archived_items WHERE item_type = \'asset\' AND item_id = :id')
            ->execute([':id' => $assetId]);
        $pdo->prepare('DELETE FROM archive_audit_trail WHERE item_type = \'asset\' AND item_id = :id')
            ->execute([':id' => $assetId]);
        $pdo->prepare('DELETE FROM assets WHERE asset_id = :id')->execute([':id' => $assetId]);

        $allIds = array_values(array_unique(array_filter(array_map('intval', $createdUserIds))));
        if ($allIds !== []) {
            $marks = implode(',', array_fill(0, count($allIds), '?'));
            $pdo->prepare("DELETE FROM user_roles WHERE user_id IN ({$marks})")->execute($allIds);
            $pdo->prepare("DELETE FROM users WHERE user_id IN ({$marks})")->execute($allIds);
        }
        $pdo->prepare('DELETE FROM user_roles WHERE user_id IN (SELECT user_id FROM users WHERE username LIKE :prefix)')
            ->execute([':prefix' => $marker . '%']);
        $pdo->prepare('DELETE FROM users WHERE username LIKE :prefix')
            ->execute([':prefix' => $marker . '%']);
    } catch (Throwable $cleanupError) {
        fwrite(STDERR, 'CLEANUP ERROR: ' . $cleanupError->getMessage() . "\n");
    }

    foreach ($cookies as $cookie) {
        if (is_string($cookie) && is_file($cookie)) {
            @unlink($cookie);
        }
    }
}

echo "=======================================================================\n";
echo " LIVE HTTP FINISHING GATE F0 / F4 / F5\n";
echo " Marker: {$marker}\n";
echo "=======================================================================\n\n";

try {
    $existingKpi = $pdo->prepare(
        'SELECT COUNT(*) FROM head_kpi_assessments WHERE period_key = :period AND location_id = :location'
    );
    $existingKpi->execute([':period' => $period, ':location' => $locationId]);
    if ((int) $existingKpi->fetchColumn() !== 0) {
        throw new RuntimeException("Slot QA KPI {$period}/lokasi {$locationId} sedang terpakai; tes dihentikan tanpa mutasi.");
    }

    $createdUserIds[] = $adminId = qaInsertUser($pdo, $adminUsername, $password, 1, $locationId);
    $createdUserIds[] = $assessorId = qaInsertUser($pdo, $assessorUsername, $password, 2, $locationId);

    $admin = qaLogin('admin', $adminUsername, $password);
    $assessor = qaLogin('assessor', $assessorUsername, $password);
    qaAssert($admin['response']['status'] === 200 && $admin['csrf'] !== '', 'F0 admin QA login melalui HTTP');
    qaAssert($assessor['response']['status'] === 200 && $assessor['csrf'] !== '', 'F0 penilai QA login melalui HTTP');

    $unauth = qaRequest('anonymous', '/api/users.php');
    qaAssert($unauth['status'] === 401, 'F0 request tanpa sesi ditolak 401');

    $listAdmin = qaRequest('admin', '/api/users.php');
    $listNonAdmin = qaRequest('assessor', '/api/users.php');
    qaAssert($listAdmin['status'] === 200, 'F0 admin dapat membaca daftar pengguna');
    qaAssert($listNonAdmin['status'] === 403, 'F0 non-admin ditolak membaca daftar pengguna');

    $missingCsrf = qaRequest('admin', '/api/users.php', 'POST', ['action' => 'create']);
    $fakeCsrf = qaRequest('admin', '/api/users.php', 'POST', ['action' => 'create'], 'qa-token-palsu');
    qaAssert($missingCsrf['status'] === 403 && ($missingCsrf['json']['code'] ?? '') === 'CSRF_INVALID', 'F0 POST tanpa CSRF ditolak');
    qaAssert($fakeCsrf['status'] === 403 && ($fakeCsrf['json']['code'] ?? '') === 'CSRF_INVALID', 'F0 POST dengan CSRF palsu ditolak');

    $create = qaRequest('admin', '/api/users.php', 'POST', [
        'action' => 'create',
        'username' => $managedUsername,
        'full_name' => 'QA Finishing Managed User',
        'password' => $password,
        'role_id' => 4,
        'assigned_location_id' => $locationId,
    ], $admin['csrf']);
    $managedId = (int) ($create['json']['data']['user_id'] ?? 0);
    if ($managedId > 0) {
        $createdUserIds[] = $managedId;
    }
    qaAssert($create['status'] === 201 && $managedId > 0, 'F0 admin membuat akun QA tepat satu kali');

    $toggleOff = qaRequest('admin', '/api/users.php', 'POST', [
        'action' => 'toggle_status', 'user_id' => $managedId,
    ], $admin['csrf']);
    qaAssert($toggleOff['status'] === 200 && (int) ($toggleOff['json']['is_active'] ?? -1) === 0, 'F0 akun QA dinonaktifkan');

    $disabledLogin = qaLogin('managed-disabled', $managedUsername, $password);
    qaAssert($disabledLogin['response']['status'] === 401, 'F0 akun nonaktif tidak dapat login');

    $toggleOn = qaRequest('admin', '/api/users.php', 'POST', [
        'action' => 'toggle_status', 'user_id' => $managedId,
    ], $admin['csrf']);
    qaAssert($toggleOn['status'] === 200 && (int) ($toggleOn['json']['is_active'] ?? -1) === 1, 'F0 akun QA diaktifkan kembali');

    $managed = qaLogin('managed', $managedUsername, $password);
    qaAssert($managed['response']['status'] === 200, 'F0 akun aktif dapat login kembali');
    $nonAdminCreate = qaRequest('managed', '/api/users.php', 'POST', [
        'action' => 'create', 'username' => $marker . '-forbidden',
    ], $managed['csrf']);
    qaAssert($nonAdminCreate['status'] === 403, 'F0 non-admin ditolak membuat akun');

    $newPassword = $password . '-changed';
    $changePassword = qaRequest('managed', '/api/users.php', 'POST', [
        'action' => 'change_password',
        'user_id' => $managedId,
        'current_password' => $password,
        'new_password' => $newPassword,
    ], $managed['csrf']);
    qaAssert($changePassword['status'] === 200, 'F0 pengguna mengganti password sendiri');
    $oldPasswordLogin = qaLogin('managed-old-password', $managedUsername, $password);
    $newPasswordLogin = qaLogin('managed-new-password', $managedUsername, $newPassword);
    qaAssert($oldPasswordLogin['response']['status'] === 401, 'F0 password lama ditolak');
    qaAssert($newPasswordLogin['response']['status'] === 200, 'F0 password baru diterima');

    $auditCountStmt = $pdo->prepare('SELECT COUNT(*) FROM user_audit_trail WHERE target_username = :username');
    $auditCountStmt->execute([':username' => $managedUsername]);
    qaAssert((int) $auditCountStmt->fetchColumn() >= 4, 'F0 audit create/toggle/password tersimpan di DB');

    // F4: real HTTP lifecycle DRAFT -> SUBMITTED -> APPROVED.
    $indicators = [];
    for ($number = 1; $number <= 10; $number++) {
        $indicators[] = [
            'indicator_no' => $number,
            'aspect_name' => 'QA Aspect ' . $number,
            'indicator_name' => 'QA Indicator ' . $number,
            'target_standard' => 'QA Target',
            'score_val' => ($number % 5) + 1,
            'weight_pct' => 10,
            'audit_notes' => $marker,
        ];
    }
    $kpiPayload = [
        'period' => $period,
        'location_id' => $locationId,
        'evaluated_user_id' => $managedId,
        'status' => 'DRAFT',
        'indicators' => $indicators,
    ];
    $draft = qaRequest('assessor', '/api/people_kpi.php?action=save_head_kpi', 'POST', $kpiPayload, $assessor['csrf']);
    qaAssert($draft['status'] === 200, 'F4 KPI disimpan sebagai DRAFT melalui HTTP', $draft['body']);

    $readDraft = qaRequest('assessor', '/api/people_kpi.php?action=head_kpi&period=' . $period . '&location_id=' . $locationId);
    qaAssert(
        $readDraft['status'] === 200
        && strtoupper((string) ($readDraft['json']['assessment']['status'] ?? '')) === 'DRAFT',
        'F4 DRAFT bertahan setelah request baca baru'
    );

    $kpiPayload['status'] = 'SUBMITTED';
    $submit = qaRequest('assessor', '/api/people_kpi.php?action=save_head_kpi', 'POST', $kpiPayload, $assessor['csrf']);
    qaAssert($submit['status'] === 200, 'F4 KPI diajukan menjadi SUBMITTED');
    $submitAgain = qaRequest('assessor', '/api/people_kpi.php?action=save_head_kpi', 'POST', $kpiPayload, $assessor['csrf']);
    qaAssert($submitAgain['status'] === 200 && !empty($submitAgain['json']['already_submitted']), 'F4 retry submit idempoten');

    $selfApproval = qaRequest('assessor', '/api/people_kpi.php?action=approve_head_kpi', 'POST', [
        'period' => $period, 'location_id' => $locationId,
        'decision' => 'APPROVED', 'approval_notes' => $marker,
    ], $assessor['csrf']);
    qaAssert($selfApproval['status'] === 403, 'F4 penilai/non-approver ditolak menyetujui');

    $approve = qaRequest('admin', '/api/people_kpi.php?action=approve_head_kpi', 'POST', [
        'period' => $period, 'location_id' => $locationId,
        'decision' => 'APPROVED', 'approval_notes' => $marker,
    ], $admin['csrf']);
    qaAssert($approve['status'] === 200 && ($approve['json']['decision'] ?? '') === 'APPROVED', 'F4 approver berbeda menyetujui KPI');

    $editApproved = qaRequest('assessor', '/api/people_kpi.php?action=save_head_kpi', 'POST', $kpiPayload, $assessor['csrf']);
    qaAssert($editApproved['status'] === 409, 'F4 KPI APPROVED terkunci dari edit biasa');

    $kpiCheck = $pdo->prepare(
        'SELECT status, assessor_user_id, approved_by, COUNT(*) AS row_count,
                SUM(weight_pct) AS total_weight
         FROM head_kpi_assessments WHERE period_key = :period AND location_id = :location
         GROUP BY status, assessor_user_id, approved_by'
    );
    $kpiCheck->execute([':period' => $period, ':location' => $locationId]);
    $kpiRow = $kpiCheck->fetch() ?: [];
    qaAssert(
        ($kpiRow['status'] ?? '') === 'APPROVED'
        && (int) ($kpiRow['assessor_user_id'] ?? 0) === $assessorId
        && (int) ($kpiRow['approved_by'] ?? 0) === $adminId
        && (int) ($kpiRow['row_count'] ?? 0) === 10
        && abs((float) ($kpiRow['total_weight'] ?? 0) - 100.0) < 0.01,
        'F4 DB cocok: 10 indikator, bobot 100, assessor dan approver berbeda'
    );
    $kpiAudit = $pdo->prepare('SELECT COUNT(*) FROM head_kpi_audit_trail WHERE period_key = :period AND location_id = :location');
    $kpiAudit->execute([':period' => $period, ':location' => $locationId]);
    qaAssert((int) $kpiAudit->fetchColumn() >= 3, 'F4 audit DRAFT/SUBMIT/APPROVE tersimpan');

    // F5: archive/register/restore over HTTP.
    $pdo->prepare(
        "INSERT INTO assets (asset_id, asset_code, category, make_model, current_location_id, status, last_hm_km, last_update_timestamp)
         VALUES (:id, :code, 'Other', 'QA HTTP Asset', :location, 'READY', 0, NOW())"
    )->execute([':id' => $assetId, ':code' => strtoupper(substr($marker, -12)), ':location' => $locationId]);

    $archive = qaRequest('admin', '/api/archive.php', 'POST', [
        'type' => 'asset', 'id' => $assetId, 'action' => 'archive', 'reason' => $marker . ' archive',
    ], $admin['csrf']);
    qaAssert($archive['status'] === 200, 'F5 aset QA diarsipkan melalui HTTP');
    $archiveList = qaRequest('admin', '/api/archive.php');
    $archivedIds = array_map(static fn(array $row): string => (string) ($row['item_id'] ?? ''), $archiveList['json']['data'] ?? []);
    qaAssert($archiveList['status'] === 200 && in_array($assetId, $archivedIds, true), 'F5 aset QA tampil pada register Archive');

    $unauthorizedArchive = qaRequest('managed-new-password', '/api/archive.php', 'POST', [
        'type' => 'asset', 'id' => $assetId, 'action' => 'restore', 'reason' => 'forbidden',
    ], $newPasswordLogin['csrf']);
    qaAssert($unauthorizedArchive['status'] === 403, 'F5 role tanpa archive.write ditolak');

    $restore = qaRequest('admin', '/api/archive.php', 'POST', [
        'type' => 'asset', 'id' => $assetId, 'action' => 'restore', 'reason' => $marker . ' restore',
    ], $admin['csrf']);
    qaAssert($restore['status'] === 200, 'F5 aset QA dipulihkan melalui HTTP');
    $archiveAudit = $pdo->prepare('SELECT GROUP_CONCAT(action ORDER BY audit_id) FROM archive_audit_trail WHERE item_type = \'asset\' AND item_id = :id');
    $archiveAudit->execute([':id' => $assetId]);
    qaAssert((string) $archiveAudit->fetchColumn() === 'ARCHIVE,RESTORE', 'F5 audit ARCHIVE dan RESTORE lengkap');

    // F5 notifications: create, inbox, mark read, and recipient isolation.
    $notification = qaRequest('admin', '/api/notifications.php', 'POST', [
        'action' => 'CREATE', 'menu_name' => 'QA', 'action_type' => 'TEST',
        'title' => $marker, 'message' => 'Finishing HTTP notification',
    ], $admin['csrf']);
    $notificationId = (int) ($notification['json']['notification_id'] ?? 0);
    qaAssert($notification['status'] === 201 && $notificationId > 0, 'F5 notifikasi dibuat melalui HTTP');
    $inbox = qaRequest('admin', '/api/notifications.php?limit=100');
    $inboxRows = $inbox['json']['data'] ?? [];
    $inboxMatch = array_values(array_filter($inboxRows, static fn(array $row): bool => (int) ($row['id'] ?? 0) === $notificationId));
    qaAssert(count($inboxMatch) === 1 && empty($inboxMatch[0]['isRead']), 'F5 notifikasi tampil unread untuk penerima');
    $markRead = qaRequest('admin', '/api/notifications.php', 'POST', [
        'action' => 'MARK_READ', 'notification_id' => $notificationId,
    ], $admin['csrf']);
    qaAssert($markRead['status'] === 200, 'F5 mark-read tersimpan melalui HTTP');
    $inboxAfter = qaRequest('admin', '/api/notifications.php?limit=100');
    $afterRows = array_values(array_filter($inboxAfter['json']['data'] ?? [], static fn(array $row): bool => (int) ($row['id'] ?? 0) === $notificationId));
    qaAssert(count($afterRows) === 1 && !empty($afterRows[0]['isRead']), 'F5 status read bertahan pada request baru');

    // F5 settings: valid admin mutation, invalid bound, non-admin denial, restore.
    $originalSetting = (string) $pdo->query("SELECT setting_value FROM operational_settings WHERE setting_key = 'cfg_sla_wo'")->fetchColumn();
    $testSetting = $originalSetting === '47' ? '48' : '47';
    $settingsUpdate = qaRequest('admin', '/api/settings.php', 'POST', [
        'settings' => ['cfg_sla_wo' => $testSetting],
    ], $admin['csrf']);
    $settingChanged = $settingsUpdate['status'] === 200;
    qaAssert($settingsUpdate['status'] === 200, 'F5 admin mengubah operational setting');
    $settingsGet = qaRequest('admin', '/api/settings.php');
    qaAssert(($settingsGet['json']['settings']['cfg_sla_wo'] ?? null) === $testSetting, 'F5 setting terbaca kembali dari server');
    $invalidSetting = qaRequest('admin', '/api/settings.php', 'POST', [
        'settings' => ['cfg_sla_wo' => 2],
    ], $admin['csrf']);
    qaAssert($invalidSetting['status'] === 422, 'F5 nilai setting di luar batas ditolak');
    $forbiddenSetting = qaRequest('managed-new-password', '/api/settings.php', 'POST', [
        'settings' => ['cfg_sla_wo' => 60],
    ], $newPasswordLogin['csrf']);
    qaAssert($forbiddenSetting['status'] === 403, 'F5 non-admin ditolak mengubah setting');
    $settingsRestore = qaRequest('admin', '/api/settings.php', 'POST', [
        'settings' => ['cfg_sla_wo' => $originalSetting],
    ], $admin['csrf']);
    if ($settingsRestore['status'] === 200) {
        $settingChanged = false;
    }
    qaAssert($settingsRestore['status'] === 200, 'F5 setting dipulihkan ke nilai awal');

    $deleteManaged = qaRequest('admin', '/api/users.php', 'POST', [
        'action' => 'delete', 'user_id' => $managedId,
    ], $admin['csrf']);
    qaAssert($deleteManaged['status'] === 200, 'F0 akun QA didelete/soft-delete melalui endpoint resmi');

} catch (Throwable $error) {
    $failed++;
    fwrite(STDERR, 'FAIL: exception: ' . $error->getMessage() . "\n");
} finally {
    qaCleanup($pdo);
}

$remainingStmt = $pdo->prepare(
    'SELECT
        (SELECT COUNT(*) FROM users WHERE username LIKE ?) AS users_remaining,
        (SELECT COUNT(*) FROM assets WHERE asset_id = ?) AS assets_remaining,
        (SELECT COUNT(*) FROM archived_items WHERE item_id = ?) AS archived_remaining,
        (SELECT COUNT(*) FROM head_kpi_assessments WHERE period_key = ? AND location_id = ?) AS kpi_remaining'
);
$remainingStmt->execute([$marker . '%', $assetId, $assetId, $period, $locationId]);
$remaining = $remainingStmt->fetch() ?: [];
$clean = array_sum(array_map('intval', $remaining)) === 0;
qaAssert($clean, 'Cleanup QA bersih', json_encode($remaining, JSON_UNESCAPED_UNICODE));

echo "\nHTTP finishing gate: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
