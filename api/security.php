<?php
declare(strict_types=1);

/**
 * Shared authentication, session, CSRF, CORS, and API authorization helpers.
 *
 * Every public API controller includes db.php, and db.php calls these helpers
 * before controller code is allowed to run. Authorization is deny-by-default.
 */

function api_load_env(): void
{
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $candidates = [
        dirname(__DIR__) . '/.env',
        __DIR__ . '/.env',
        dirname(__DIR__, 2) . '/.env'
    ];

    foreach ($candidates as $path) {
        if (file_exists($path) && is_readable($path)) {
            $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) continue;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    [$k, $v] = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v, " \t\n\r\0\x0B\"'");
                    $_ENV[$k] = $v;
                    $_SERVER[$k] = $v;
                    @putenv("{$k}={$v}");
                }
            }
            break;
        }
    }
}

function api_get_env(string $key, string $default = ''): string
{
    api_load_env();
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($val !== false && $val !== null && trim((string)$val) !== '') {
        return trim((string)$val);
    }
    return $default;
}

function app_environment(): string
{
    $env = api_get_env('APP_ENV', 'production');
    $environment = strtolower(trim($env));
    return $environment !== '' ? $environment : 'production';
}

function app_is_production(): bool
{
    return in_array(app_environment(), ['production', 'live'], true);
}

function api_json_response(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_request_origin(): string
{
    return rtrim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
}

function api_application_origin(): string
{
    $configured = rtrim((string) (getenv('APP_ORIGIN') ?: ''), '/');
    if ($configured !== '') {
        return $configured;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $scheme = $isHttps ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host;
}

function api_allowed_origins(): array
{
    $allowed = [api_application_origin()];
    $configured = (string) (getenv('APP_ALLOWED_ORIGINS') ?: '');
    foreach (explode(',', $configured) as $origin) {
        $origin = rtrim(trim($origin), '/');
        if ($origin !== '') {
            $allowed[] = $origin;
        }
    }
    return array_values(array_unique($allowed));
}

function api_apply_security_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Content-Type: application/json; charset=UTF-8');

    $origin = api_request_origin();
    if ($origin !== '') {
        if (!in_array($origin, api_allowed_origins(), true)) {
            api_json_response(403, [
                'status' => 'error',
                'code' => 'ORIGIN_DENIED',
                'message' => 'Origin tidak diizinkan.',
            ]);
        }
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
}

function api_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    if (app_is_production()) {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
    }

    $sessionSavePath = trim((string) (getenv('SESSION_SAVE_PATH') ?: ''));
    if ($sessionSavePath !== '' && is_dir($sessionSavePath) && is_writable($sessionSavePath)) {
        ini_set('session.save_path', $sessionSavePath);
    }

    $sessionName = trim((string) (getenv('SESSION_NAME') ?: 'BRASESSID'));
    session_name($sessionName !== '' ? $sessionName : 'BRASESSID');

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => app_is_production() || $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();

    $now = time();
    $idleTimeout = max(300, (int) (getenv('SESSION_IDLE_TIMEOUT') ?: 1800));
    $absoluteTimeout = max($idleTimeout, (int) (getenv('SESSION_ABSOLUTE_TIMEOUT') ?: 28800));

    if (!empty($_SESSION['auth_user'])) {
        $createdAt = (int) ($_SESSION['created_at'] ?? $now);
        $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);
        if (($now - $lastActivity) > $idleTimeout || ($now - $createdAt) > $absoluteTimeout) {
            api_destroy_session();
            session_start();
            $now = time();
        }
    }

    $_SESSION['last_activity'] = $now;
    $_SESSION['created_at'] = (int) ($_SESSION['created_at'] ?? $now);
}

function api_destroy_session(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE && ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function api_csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function api_verify_csrf(): void
{
    $provided = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = (string) ($_SESSION['_csrf'] ?? '');
    if ($provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
        api_json_response(419, [
            'status' => 'error',
            'code' => 'CSRF_INVALID',
            'message' => 'Token keamanan tidak valid atau telah kedaluwarsa.',
        ]);
    }
}

function api_is_authenticated(): bool
{
    return !empty($_SESSION['auth_user']['id']);
}

function api_current_user(): array
{
    return is_array($_SESSION['auth_user'] ?? null) ? $_SESSION['auth_user'] : [];
}

function api_require_authenticated(): void
{
    if (!api_is_authenticated()) {
        api_json_response(401, [
            'status' => 'error',
            'code' => 'AUTH_REQUIRED',
            'message' => 'Sesi tidak tersedia atau telah berakhir.',
        ]);
    }
}

function api_builtin_permissions_for_roles(array $roleNames): array
{
    $all = ['*'];
    $roleMap = [
        'Administrator' => $all,
        'Asset Manager' => $all,
        'Equipment Manager' => $all,
        'Maintenance Planner' => [
            'dashboard.read', 'assets.read', 'assets.write', 'work_orders.read', 'work_orders.write',
            'accidents.read', 'fuel.read', 'inspections.read', 'inspections.write', 'logistics.read',
            'logistics.write', 'pm.read', 'pm.write', 'tires.read', 'reports.read', 'reports.write',
            'archive.read', 'archive.write', 'sync.write',
        ],
        'Mekanik Senior' => [
            'dashboard.read', 'assets.read', 'work_orders.read', 'work_orders.write', 'inspections.read',
            'inspections.write', 'pm.read', 'tires.read', 'tires.write', 'reports.read', 'archive.read',
        ],
        'Mekanik Junior / Helper' => [
            'dashboard.read', 'assets.read', 'work_orders.read', 'work_orders.write', 'inspections.read',
            'inspections.write', 'pm.read', 'tires.read', 'reports.read',
        ],
        'Welder / Fabrikator' => [
            'dashboard.read', 'assets.read', 'work_orders.read', 'work_orders.write', 'inspections.read',
            'pm.read', 'reports.read',
        ],
        'Inspector K3L / Safety' => [
            'dashboard.read', 'assets.read', 'work_orders.read', 'accidents.read', 'accidents.write',
            'inspections.read', 'inspections.write', 'reports.read', 'reports.write', 'archive.read',
            'archive.write',
        ],
        'Logistic Head' => [
            'dashboard.read', 'assets.read', 'work_orders.read', 'logistics.read', 'logistics.write',
            'reports.read', 'reports.write', 'archive.read', 'archive.write',
        ],
        'HRD Manager' => [
            'dashboard.read', 'assets.read', 'reports.read', 'reports.write', 'archive.read',
        ],
    ];

    $permissions = [];
    foreach ($roleNames as $roleName) {
        $permissions = array_merge($permissions, $roleMap[$roleName] ?? []);
    }
    return array_values(array_unique($permissions));
}

function api_load_user_context(PDO $db, int $userId): ?array
{
    $stmt = $db->prepare(
        'SELECT u.user_id, u.username, u.full_name, u.role_id, u.assigned_location_id, u.is_active,
                r.role_name AS primary_role
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !(bool) $user['is_active']) {
        return null;
    }

    $roleIds = [(int) $user['role_id']];
    $roleNames = [(string) $user['primary_role']];

    try {
        $rolesStmt = $db->prepare(
            'SELECT r.role_id, r.role_name
             FROM user_roles ur
             INNER JOIN roles r ON r.role_id = ur.role_id
             WHERE ur.user_id = :user_id'
        );
        $rolesStmt->execute([':user_id' => $userId]);
        foreach ($rolesStmt->fetchAll(PDO::FETCH_ASSOC) as $role) {
            $roleIds[] = (int) $role['role_id'];
            $roleNames[] = (string) $role['role_name'];
        }
    } catch (PDOException $e) {
        // The primary role remains authoritative on installations without user_roles.
    }

    $roleIds = array_values(array_unique($roleIds));
    $roleNames = array_values(array_unique($roleNames));
    $permissions = [];

    if (in_array('Administrator', $roleNames, true)) {
        $permissions = ['*'];
    } else {
        try {
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $permissionStmt = $db->prepare(
                "SELECT DISTINCT p.permission_key
                 FROM role_permissions rp
                 INNER JOIN permissions p ON p.permission_id = rp.permission_id
                 WHERE rp.role_id IN ($placeholders)"
            );
            $permissionStmt->execute($roleIds);
            $permissions = array_values(array_filter(array_map('strval', $permissionStmt->fetchAll(PDO::FETCH_COLUMN))));
        } catch (PDOException $e) {
            $permissions = [];
        }

        // Existing production dumps contain the RBAC tables but no permission rows.
        // Use a conservative server-side role matrix until the RBAC migration is applied.
        if ($permissions === []) {
            $permissions = api_builtin_permissions_for_roles($roleNames);
        }
    }

    return [
        'id' => (int) $user['user_id'],
        'username' => (string) $user['username'],
        'full_name' => (string) $user['full_name'],
        'primary_role' => (string) $user['primary_role'],
        'roles' => $roleNames,
        'permissions' => $permissions,
        'assigned_location_id' => $user['assigned_location_id'] !== null ? (int) $user['assigned_location_id'] : null,
    ];
}

function api_refresh_authenticated_user(PDO $db): void
{
    if (!api_is_authenticated()) {
        return;
    }

    $context = api_load_user_context($db, (int) $_SESSION['auth_user']['id']);
    if ($context === null) {
        api_destroy_session();
        api_json_response(401, [
            'status' => 'error',
            'code' => 'ACCOUNT_DISABLED',
            'message' => 'Akun tidak aktif atau tidak ditemukan.',
        ]);
    }
    $_SESSION['auth_user'] = $context;
}

function api_has_permission(string $permission): bool
{
    $permissions = api_current_user()['permissions'] ?? [];
    return is_array($permissions)
        && (in_array('*', $permissions, true) || in_array($permission, $permissions, true));
}

function api_require_permission(string $permission): void
{
    if (!api_has_permission($permission)) {
        api_json_response(403, [
            'status' => 'error',
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki izin untuk tindakan ini.',
        ]);
    }
}

function api_route_permission(): ?string
{
    $route = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $readWrite = static fn(string $read, string $write): string => $method === 'GET' ? $read : $write;

    $routes = [
        'init.php' => 'dashboard.read',
        'assets.php' => $readWrite('assets.read', 'assets.write'),
        'work_orders.php' => $readWrite('work_orders.read', 'work_orders.write'),
        'accidents.php' => $readWrite('accidents.read', 'accidents.write'),
        'fuel_logs.php' => $readWrite('fuel.read', 'fuel.write'),
        'inspections.php' => $readWrite('inspections.read', 'inspections.write'),
        'logistics.php' => $readWrite('logistics.read', 'logistics.write'),
        'pm_plans.php' => $readWrite('pm.read', 'pm.write'),
        'tire_inspections.php' => $readWrite('tires.read', 'tires.write'),
        'reports.php' => $readWrite('reports.read', 'reports.write'),
        'archive.php' => $readWrite('archive.read', 'archive.write'),
        'sync.php' => 'sync.write',
        'seed_dummy.php' => 'admin.seed',
    ];

    return $routes[$route] ?? null;
}

function api_authorize_current_route(): void
{
    $permission = api_route_permission();
    if ($permission === null) {
        api_json_response(403, [
            'status' => 'error',
            'code' => 'ROUTE_NOT_AUTHORIZED',
            'message' => 'Route belum memiliki kebijakan akses.',
        ]);
    }
    api_require_permission($permission);
}

function api_bootstrap_request(bool $authenticationOptional = false): void
{
    api_apply_security_headers();
    api_start_session();

    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    api_csrf_token();
    if (!$authenticationOptional) {
        api_require_authenticated();
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        api_verify_csrf();
    }
}
