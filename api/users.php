<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    switch ($method) {
        case 'GET':
            $roleId = isset($_GET['role_id']) && $_GET['role_id'] !== '' ? (int) $_GET['role_id'] : null;
            $roleName = isset($_GET['role_name']) && trim((string)$_GET['role_name']) !== '' ? trim((string)$_GET['role_name']) : null;

            // Fetch list of all roles for reference
            $rolesStmt = $db->query('SELECT role_id, role_name, description FROM roles ORDER BY role_id ASC');
            $allRoles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Build user query
            $sql = '
                SELECT 
                    u.user_id,
                    u.username,
                    u.full_name,
                    u.role_id,
                    r.role_name,
                    u.is_active,
                    u.created_at
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.role_id
            ';
            $params = [];
            $where = [];

            if ($roleId !== null) {
                $where[] = 'u.role_id = :role_id';
                $params[':role_id'] = $roleId;
            } elseif ($roleName !== null) {
                $where[] = 'r.role_name = :role_name';
                $params[':role_name'] = $roleName;
            }

            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY u.full_name ASC';

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            api_json_response(200, [
                'status' => 'success',
                'data' => $users,
                'roles' => $allRoles,
                'total' => count($users),
                'filter' => [
                    'role_id' => $roleId,
                    'role_name' => $roleName
                ]
            ]);
            break;

        case 'POST':
            $rawInput = file_get_contents('php://input');
            $input = json_decode((string) $rawInput, true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $action = trim((string) ($input['action'] ?? 'create'));

            if ($action === 'create' || $action === 'add') {
                $username = strtolower(trim((string) ($input['username'] ?? '')));
                $fullName = trim((string) ($input['full_name'] ?? ''));
                $password = (string) ($input['password'] ?? '');
                $roleId = isset($input['role_id']) ? (int) $input['role_id'] : 0;

                // Validations
                if ($username === '' || !preg_match('/^[a-z0-9_.-]{3,50}$/', $username)) {
                    api_json_response(422, [
                        'status' => 'error',
                        'message' => 'Username wajib 3-50 karakter (hanya huruf kecil, angka, garis bawah, titik, atau strip).'
                    ]);
                }

                if ($fullName === '' || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
                    api_json_response(422, [
                        'status' => 'error',
                        'message' => 'Nama lengkap wajib diisi (2-100 karakter).'
                    ]);
                }

                if ($password === '' || strlen($password) < 6) {
                    api_json_response(422, [
                        'status' => 'error',
                        'message' => 'Password wajib minimal 6 karakter.'
                    ]);
                }

                if ($roleId <= 0) {
                    api_json_response(422, [
                        'status' => 'error',
                        'message' => 'Pilih role pengguna yang valid.'
                    ]);
                }

                // Verify role exists
                $roleCheck = $db->prepare('SELECT role_id, role_name FROM roles WHERE role_id = :role_id LIMIT 1');
                $roleCheck->execute([':role_id' => $roleId]);
                $roleData = $roleCheck->fetch(PDO::FETCH_ASSOC);
                if (!$roleData) {
                    api_json_response(422, [
                        'status' => 'error',
                        'message' => 'Role ID yang dipilih tidak terdaftar di sistem.'
                    ]);
                }

                // Verify username unique
                $userCheck = $db->prepare('SELECT user_id FROM users WHERE username = :username LIMIT 1');
                $userCheck->execute([':username' => $username]);
                if ($userCheck->fetchColumn()) {
                    api_json_response(422, [
                        'status' => 'error',
                        'message' => "Username '{$username}' sudah digunakan. Silakan gunakan username lain."
                    ]);
                }

                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $db->beginTransaction();
                $insertStmt = $db->prepare('
                    INSERT INTO users (username, password_hash, full_name, role_id, is_active, created_at)
                    VALUES (:username, :password_hash, :full_name, :role_id, 1, NOW())
                ');
                $insertStmt->execute([
                    ':username' => $username,
                    ':password_hash' => $passwordHash,
                    ':full_name' => $fullName,
                    ':role_id' => $roleId
                ]);
                $newUserId = (int) $db->lastInsertId();

                // If user_roles exists, also insert
                try {
                    $urStmt = $db->prepare('
                        INSERT INTO user_roles (user_id, role_id, is_primary, assigned_at)
                        VALUES (:user_id, :role_id, 1, NOW())
                    ');
                    $urStmt->execute([':user_id' => $newUserId, ':role_id' => $roleId]);
                } catch (Throwable $e) {
                    // Ignore if user_roles does not exist or duplicate
                }

                $db->commit();

                api_json_response(201, [
                    'status' => 'success',
                    'message' => "Pengguna '{$fullName}' ({$username}) berhasil ditambahkan ke role {$roleData['role_name']}.",
                    'data' => [
                        'user_id' => $newUserId,
                        'username' => $username,
                        'full_name' => $fullName,
                        'role_id' => $roleId,
                        'role_name' => $roleData['role_name'],
                        'is_active' => 1
                    ]
                ]);
            }

            if ($action === 'change_password') {
                $userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;
                $newPassword = (string) ($input['new_password'] ?? '');

                if ($userId <= 0) {
                    api_json_response(422, ['status' => 'error', 'message' => 'User ID tidak valid.']);
                }

                if ($newPassword === '' || strlen($newPassword) < 6) {
                    api_json_response(422, ['status' => 'error', 'message' => 'Password baru wajib minimal 6 karakter.']);
                }

                $userCheck = $db->prepare('SELECT user_id, username, full_name FROM users WHERE user_id = :id LIMIT 1');
                $userCheck->execute([':id' => $userId]);
                $user = $userCheck->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    api_json_response(404, ['status' => 'error', 'message' => 'Pengguna tidak ditemukan.']);
                }

                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateStmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
                $updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

                api_json_response(200, [
                    'status' => 'success',
                    'message' => "Password untuk pengguna '{$user['username']}' berhasil diubah."
                ]);
            }

            if ($action === 'delete') {
                $userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;

                if ($userId <= 0) {
                    api_json_response(422, ['status' => 'error', 'message' => 'User ID tidak valid.']);
                }

                $userCheck = $db->prepare('SELECT user_id, username, full_name FROM users WHERE user_id = :id LIMIT 1');
                $userCheck->execute([':id' => $userId]);
                $user = $userCheck->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    api_json_response(404, ['status' => 'error', 'message' => 'Pengguna tidak ditemukan.']);
                }

                // Prevent deleting primary admin account
                if ($userId === 1 || strtolower((string) $user['username']) === 'admin') {
                    api_json_response(403, [
                        'status' => 'error',
                        'message' => 'Akun Administrator Utama tidak boleh dihapus dari sistem.'
                    ]);
                }

                $currentUser = api_current_user();
                if ($currentUser && (int) ($currentUser['id'] ?? 0) === $userId) {
                    api_json_response(403, [
                        'status' => 'error',
                        'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.'
                    ]);
                }

                // Try hard delete first, fallback to soft delete if referenced in transactional tables
                try {
                    $db->beginTransaction();
                    $db->prepare('DELETE FROM user_roles WHERE user_id = :id')->execute([':id' => $userId]);
                    $db->prepare('DELETE FROM users WHERE user_id = :id')->execute([':id' => $userId]);
                    $db->commit();

                    api_json_response(200, [
                        'status' => 'success',
                        'message' => "Pengguna '{$user['username']}' berhasil dihapus dari sistem."
                    ]);
                } catch (PDOException $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    // MySQL 1451 is foreign key constraint violation
                    if (str_contains($e->getMessage(), '1451') || $e->getCode() === '23000') {
                        $deactStmt = $db->prepare('UPDATE users SET is_active = 0 WHERE user_id = :id');
                        $deactStmt->execute([':id' => $userId]);
                        api_json_response(200, [
                            'status' => 'success',
                            'message' => "Pengguna '{$user['username']}' memiliki rekam jejak operasional, sehingga akun telah dinonaktifkan (soft delete) untuk menjaga integritas data audit."
                        ]);
                    }
                    throw $e;
                }
            }

            if ($action === 'toggle_status') {
                $userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;
                if ($userId <= 0) {
                    api_json_response(422, ['status' => 'error', 'message' => 'User ID tidak valid.']);
                }

                $userCheck = $db->prepare('SELECT user_id, username, is_active FROM users WHERE user_id = :id LIMIT 1');
                $userCheck->execute([':id' => $userId]);
                $user = $userCheck->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    api_json_response(404, ['status' => 'error', 'message' => 'Pengguna tidak ditemukan.']);
                }

                if ($userId === 1) {
                    api_json_response(403, ['status' => 'error', 'message' => 'Akun Administrator Utama tidak boleh dinonaktifkan.']);
                }

                $newStatus = ((int) $user['is_active']) === 1 ? 0 : 1;
                $db->prepare('UPDATE users SET is_active = :status WHERE user_id = :id')->execute([
                    ':status' => $newStatus,
                    ':id' => $userId
                ]);

                $statusLabel = $newStatus === 1 ? 'diaktifkan' : 'dinonaktifkan';
                api_json_response(200, [
                    'status' => 'success',
                    'message' => "Status pengguna '{$user['username']}' berhasil {$statusLabel}.",
                    'is_active' => $newStatus
                ]);
            }

            api_json_response(400, [
                'status' => 'error',
                'message' => "Aksi '{$action}' tidak dikenal."
            ]);
            break;

        default:
            header('Allow: GET, POST');
            api_json_response(405, ['status' => 'error', 'message' => 'Metode HTTP tidak didukung.']);
    }
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 'SERVER_ERROR',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
