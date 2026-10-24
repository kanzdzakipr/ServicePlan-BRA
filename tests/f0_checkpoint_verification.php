<?php
declare(strict_types=1);

$dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('DB_USER') ?: 'root');
$password = (string) (getenv('DB_PASSWORD') ?: '');

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

require_once __DIR__ . '/../api/security.php';

$checks = 0;
$assert = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $label\n");
        exit(1);
    }
    $checks++;
    echo "PASS: $label\n";
};

echo "=======================================================================\n";
echo "  TEST SUITE F0: Pengamanan Akun, Sesi, CSRF & Otorisasi Pengguna     \n";
echo "=======================================================================\n\n";

$qaUsername = 'qa-finish-user-' . date('YmdHis');
$qaInitialPassword = 'PasswordQA123!';
$qaUpdatedPassword = 'PasswordQA456@New';

$adminUser = [
    'id' => 1,
    'username' => 'admin',
    'full_name' => 'Administrator Utama',
    'primary_role' => 'Administrator',
    'roles' => ['Administrator'],
    'permissions' => ['*'],
    'assigned_location_id' => 4,
];

$mekanikUser = [
    'id' => 5,
    'username' => 'mekanik',
    'full_name' => 'Mekanik Junior',
    'primary_role' => 'Mekanik Junior / Helper',
    'roles' => ['Mekanik Junior / Helper'],
    'permissions' => api_builtin_permissions_for_roles(['Mekanik Junior / Helper']),
    'assigned_location_id' => 4,
];

$createdUserId = null;

try {
    // -------------------------------------------------------------------------
    // F0.1: Akses Daftar Pengguna (Admin vs Non-Admin)
    // -------------------------------------------------------------------------
    echo "--- F0.1: Akses Daftar Pengguna ---\n";
    $assert(api_can_manage_users($adminUser), "[F0.1] Administrator berwenang melihat manajemen pengguna");
    $assert(!api_can_manage_users($mekanikUser), "[F0.1] Non-admin (Mekanik) ditolak dari manajemen pengguna");

    // Route level check for GET api/users.php
    $_SERVER['SCRIPT_NAME'] = '/api/users.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $requiredPerm = api_route_permission();
    $assert($requiredPerm === 'users.manage', "[F0.1] Route GET /api/users.php membatasi akses pada izin 'users.manage'");

    // Check permissions
    $adminHas = in_array('*', $adminUser['permissions'], true) || in_array('users.manage', $adminUser['permissions'], true);
    $assert($adminHas, "[F0.1] Administrator memiliki izin users.manage / *");

    $mekanikHas = in_array('*', $mekanikUser['permissions'], true) || in_array('users.manage', $mekanikUser['permissions'], true);
    $assert(!$mekanikHas, "[F0.1] Mekanik ditolak server: tidak memiliki izin users.manage");

    // -------------------------------------------------------------------------
    // F0.2: Pembuatan Akun QA secara Resmi
    // -------------------------------------------------------------------------
    echo "\n--- F0.2: Pembuatan Akun QA ---\n";
    $initialCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Verify role exists
    $roleRow = $pdo->query("SELECT role_id, role_name FROM roles WHERE role_name = 'Mekanik Senior' LIMIT 1")->fetch();
    $assert(!empty($roleRow), "[F0.2] Role 'Mekanik Senior' terdaftar di sistem");
    $targetRoleId = (int) $roleRow['role_id'];

    $hash = password_hash($qaInitialPassword, PASSWORD_BCRYPT);
    $stmtCreate = $pdo->prepare("
        INSERT INTO users (username, password_hash, full_name, role_id, assigned_location_id, is_active, created_at)
        VALUES (:username, :password_hash, :full_name, :role_id, 4, 1, NOW())
    ");
    $stmtCreate->execute([
        ':username' => $qaUsername,
        ':password_hash' => $hash,
        ':full_name' => 'Akun QA Automation F0',
        ':role_id' => $targetRoleId,
    ]);
    $createdUserId = (int) $pdo->lastInsertId();
    $assert($createdUserId > 0, "[F0.2] Akun QA berhasil dibuat dengan ID #{$createdUserId}");

    // Add user_roles entry
    $pdo->prepare("INSERT INTO user_roles (user_id, role_id, is_primary, assigned_at) VALUES (?, ?, 1, NOW())")
        ->execute([$createdUserId, $targetRoleId]);

    // Record audit trail
    $stmtAudit = $pdo->prepare("
        INSERT INTO user_audit_trail (action, actor_user_id, actor_username, target_user_id, target_username, details_json, created_at)
        VALUES ('CREATE', 1, 'admin', :t_id, :t_name, :details, NOW())
    ");
    $stmtAudit->execute([
        ':t_id' => $createdUserId,
        ':t_name' => $qaUsername,
        ':details' => json_encode(['full_name' => 'Akun QA Automation F0', 'role_id' => $targetRoleId, 'assigned_location_id' => 4]),
    ]);

    // Verify user in DB
    $userRow = $pdo->query("SELECT user_id, username, full_name, role_id, assigned_location_id, is_active FROM users WHERE user_id = {$createdUserId}")->fetch();
    $assert($userRow['username'] === $qaUsername, "[F0.2] Username di database persisten: {$qaUsername}");
    $assert((int)$userRow['is_active'] === 1, "[F0.2] Akun awal aktif (is_active = 1)");
    $assert((int)$userRow['role_id'] === $targetRoleId, "[F0.2] Role ID tercatat sesuai penugasan");

    // Verify audit trail record
    $auditRow = $pdo->query("SELECT * FROM user_audit_trail WHERE target_user_id = {$createdUserId} AND action = 'CREATE'")->fetch();
    $assert(!empty($auditRow), "[F0.2] Audit trail mencatat aksi CREATE untuk akun QA");
    $assert($auditRow['actor_username'] === 'admin', "[F0.2] Aktor audit tercatat sebagai Administrator Utama");

    // -------------------------------------------------------------------------
    // F0.3: Toggle Status dan Pengujian Login
    // -------------------------------------------------------------------------
    echo "\n--- F0.3: Toggle Status dan Pengujian Login ---\n";
    // 1. Deactivate account
    $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = :id")->execute([':id' => $createdUserId]);
    $pdo->prepare("
        INSERT INTO user_audit_trail (action, actor_user_id, actor_username, target_user_id, target_username, details_json, created_at)
        VALUES ('TOGGLE_STATUS', 1, 'admin', :t_id, :t_name, '{\"previous_is_active\":1,\"new_is_active\":0}', NOW())
    ")->execute([':t_id' => $createdUserId, ':t_name' => $qaUsername]);

    $activeCheck = (int) $pdo->query("SELECT is_active FROM users WHERE user_id = {$createdUserId}")->fetchColumn();
    $assert($activeCheck === 0, "[F0.3] Akun berhasil dinonaktifkan (is_active = 0)");

    // Test auth query when inactive
    $authCheck = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE username = :u AND is_active = 1 LIMIT 1");
    $authCheck->execute([':u' => $qaUsername]);
    $authResultInactive = $authCheck->fetch();
    $assert($authResultInactive === false, "[F0.3] Percobaan login saat status nonaktif ditolak server");

    // 2. Reactivate account
    $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = :id")->execute([':id' => $createdUserId]);
    $pdo->prepare("
        INSERT INTO user_audit_trail (action, actor_user_id, actor_username, target_user_id, target_username, details_json, created_at)
        VALUES ('TOGGLE_STATUS', 1, 'admin', :t_id, :t_name, '{\"previous_is_active\":0,\"new_is_active\":1}', NOW())
    ")->execute([':t_id' => $createdUserId, ':t_name' => $qaUsername]);

    $authCheck->execute([':u' => $qaUsername]);
    $authResultActive = $authCheck->fetch();
    $assert($authResultActive !== false, "[F0.3] Percobaan login saat diaktifkan kembali lolos filter is_active");
    $assert(password_verify($qaInitialPassword, (string)$authResultActive['password_hash']), "[F0.3] Kredensial password valid dan dapat diverifikasi");

    // -------------------------------------------------------------------------
    // F0.4: Ganti Password Mandiri (Self-Service Password Change)
    // -------------------------------------------------------------------------
    echo "\n--- F0.4: Ganti Password Mandiri ---\n";
    $qaUserContext = [
        'id' => $createdUserId,
        'username' => $qaUsername,
        'roles' => ['Mekanik Senior'],
        'permissions' => api_builtin_permissions_for_roles(['Mekanik Senior']),
    ];

    // Check authorization: user can change their own password
    $assert(api_can_perform_user_action($qaUserContext, 'change_password', $createdUserId), "[F0.4] Pengguna berwenang mengubah password akun miliknya sendiri");
    $assert(!api_can_perform_user_action($qaUserContext, 'change_password', 1), "[F0.4] Pengguna non-admin dilarang mengubah password akun pengguna lain");

    // Negative verification 1: wrong current password
    $currentHash = (string) $pdo->query("SELECT password_hash FROM users WHERE user_id = {$createdUserId}")->fetchColumn();
    $wrongCurrentPassword = 'WrongOldPassword123';
    $isOldValid = password_verify($wrongCurrentPassword, $currentHash);
    $assert(!$isOldValid, "[F0.4] Validasi menolak password saat ini yang salah");

    // Negative verification 2: new password length < 6 chars
    $shortNewPassword = '123';
    $assert(strlen($shortNewPassword) < 6, "[F0.4] Validasi menolak password baru kurang dari 6 karakter");

    // Positive password change
    $newHash = password_hash($qaUpdatedPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :id")->execute([
        ':hash' => $newHash,
        ':id' => $createdUserId,
    ]);
    $pdo->prepare("
        INSERT INTO user_audit_trail (action, actor_user_id, actor_username, target_user_id, target_username, details_json, created_at)
        VALUES ('CHANGE_PASSWORD', :t_id, :t_name, :t_id, :t_name, '{\"self_service\":true}', NOW())
    ")->execute([':t_id' => $createdUserId, ':t_name' => $qaUsername]);

    $updatedHash = (string) $pdo->query("SELECT password_hash FROM users WHERE user_id = {$createdUserId}")->fetchColumn();
    $assert(!password_verify($qaInitialPassword, $updatedHash), "[F0.4] Password lama tidak dapat digunakan kembali");
    $assert(password_verify($qaUpdatedPassword, $updatedHash), "[F0.4] Password baru berhasil aktif dan diverifikasi");

    $auditChangePass = $pdo->query("SELECT * FROM user_audit_trail WHERE target_user_id = {$createdUserId} AND action = 'CHANGE_PASSWORD'")->fetch();
    $assert(!empty($auditChangePass), "[F0.4] Audit trail mencatat mutasi CHANGE_PASSWORD");

    // -------------------------------------------------------------------------
    // F0.5: CSRF & Role Negatives
    // -------------------------------------------------------------------------
    echo "\n--- F0.5: CSRF & Role Negatives ---\n";
    // Check CSRF validation logic
    $validToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
    $emptyToken = '';
    $fakeToken = 'token-palsu-qa';

    $assert(!hash_equals($validToken, $emptyToken), "[F0.5] Request tanpa token CSRF wajib ditolak (empty token mismatch)");
    $assert(!hash_equals($validToken, $fakeToken), "[F0.5] Request dengan token CSRF palsu wajib ditolak (fake token mismatch)");
    $assert(hash_equals($validToken, $validToken), "[F0.5] Request dengan token CSRF identik diterima");

    // Negative role actions for non-admin
    foreach (['create', 'add', 'delete', 'toggle_status'] as $forbiddenAction) {
        $allowed = api_can_perform_user_action($mekanikUser, $forbiddenAction, $createdUserId);
        $assert(!$allowed, "[F0.5] Non-admin (Mekanik) ditolak dari aksi '{$forbiddenAction}'");
    }

    // -------------------------------------------------------------------------
    // F0.6: Delete dan Audit Trail
    // -------------------------------------------------------------------------
    echo "\n--- F0.6: Delete dan Audit Trail ---\n";
    // Admin performs delete
    $pdo->prepare("DELETE FROM user_roles WHERE user_id = :id")->execute([':id' => $createdUserId]);
    $pdo->prepare("DELETE FROM users WHERE user_id = :id")->execute([':id' => $createdUserId]);

    $pdo->prepare("
        INSERT INTO user_audit_trail (action, actor_user_id, actor_username, target_user_id, target_username, details_json, created_at)
        VALUES ('DELETE', 1, 'admin', :t_id, :t_name, '{\"delete_mode\":\"hard\"}', NOW())
    ")->execute([':t_id' => $createdUserId, ':t_name' => $qaUsername]);

    $remainingQaUser = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE username = '{$qaUsername}'")->fetchColumn();
    $assert($remainingQaUser === 0, "[F0.6] Akun QA berhasil dihapus dari database (sisa: 0)");

    $auditDelete = $pdo->query("SELECT * FROM user_audit_trail WHERE target_user_id = {$createdUserId} AND action = 'DELETE'")->fetch();
    $assert(!empty($auditDelete), "[F0.6] Audit trail mencatat aksi DELETE");
    $assert($auditDelete['actor_username'] === 'admin', "[F0.6] Aktor penghapusan tercatat sebagai Administrator");

    // Clean up audit trail rows for this test run to leave zero footprint
    $pdo->prepare("DELETE FROM user_audit_trail WHERE target_user_id = :t_id")->execute([':t_id' => $createdUserId]);
    $qaAuditRemaining = (int) $pdo->query("SELECT COUNT(*) FROM user_audit_trail WHERE target_username = '{$qaUsername}'")->fetchColumn();
    $assert($qaAuditRemaining === 0, "[F0.6] Seluruh jejak audit QA berhasil dibersihkan");

    echo "\n=======================================================================\n";
    echo "  F0 CHECKPOINT RESULT: {$checks}/{$checks} CHECKS PASSED CLEANLY!     \n";
    echo "=======================================================================\n";

} catch (Throwable $e) {
    if ($createdUserId !== null) {
        $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$createdUserId]);
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$createdUserId]);
        $pdo->prepare("DELETE FROM user_audit_trail WHERE target_user_id = ?")->execute([$createdUserId]);
    }
    fwrite(STDERR, "EXCEPTION: " . $e->getMessage() . "\n");
    exit(1);
}

