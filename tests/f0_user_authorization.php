<?php
declare(strict_types=1);

putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';
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

$admin = ['id' => 1, 'roles' => ['Administrator'], 'permissions' => ['*']];
$manager = ['id' => 2, 'roles' => ['Equipment Manager'], 'permissions' => api_builtin_permissions_for_roles(['Equipment Manager'])];
$assetManager = ['id' => 3, 'roles' => ['Asset Manager'], 'permissions' => api_builtin_permissions_for_roles(['Asset Manager'])];
$planner = ['id' => 4, 'roles' => ['Maintenance Planner'], 'permissions' => api_builtin_permissions_for_roles(['Maintenance Planner'])];

$assert(api_can_manage_users($admin), 'administrator may manage users');
foreach ([$manager, $assetManager, $planner] as $user) {
    $assert(!api_can_manage_users($user), 'non-admin cannot manage users');
    foreach (['create', 'add', 'delete', 'toggle_status', 'unknown'] as $action) {
        $assert(!api_can_perform_user_action($user, $action, 5), "non-admin denied $action");
    }
    $assert(!api_can_perform_user_action($user, 'change_password', 5), 'non-admin cannot reset another account');
    $assert(api_can_perform_user_action($user, 'change_password', $user['id']), 'self password change remains available');
}
$assert(api_can_perform_user_action($admin, 'create'), 'administrator may create');
$assert(api_can_perform_user_action($admin, 'change_password', 5), 'administrator may reset another account');
$assert(!api_can_manage_users(['id' => 9, 'roles' => ['Equipment Manager'], 'permissions' => ['*']]), 'wildcard without Administrator role is insufficient');
$assert(!in_array('*', $manager['permissions'], true), 'equipment manager fallback is not wildcard');
$assert(!in_array('*', $assetManager['permissions'], true), 'asset manager fallback is not wildcard');
$assert(!in_array('admin.seed', $manager['permissions'], true), 'equipment manager cannot seed');
$assert(!in_array('users.manage', $manager['permissions'], true), 'equipment manager cannot manage accounts');
$assert(in_array('scope.all_locations', $manager['permissions'], true), 'equipment manager operational location scope retained');
$assert(in_array('scope.all_locations', $assetManager['permissions'], true), 'asset manager operational location scope retained');
$assert(in_array('scope.all_locations', $planner['permissions'], true), 'maintenance planner operational location scope retained');

$inspector = ['id' => 5, 'roles' => ['Inspector K3L / Safety'], 'permissions' => api_builtin_permissions_for_roles(['Inspector K3L / Safety'])];
$assert(in_array('scope.all_locations', $inspector['permissions'], true), 'inspector k3l operational location scope retained');

$logistic = ['id' => 6, 'roles' => ['Logistic Head'], 'permissions' => api_builtin_permissions_for_roles(['Logistic Head'])];
$assert(in_array('scope.all_locations', $logistic['permissions'], true), 'logistic head operational location scope retained');

$hrd = ['id' => 7, 'roles' => ['HRD Manager'], 'permissions' => api_builtin_permissions_for_roles(['HRD Manager'])];
$assert(in_array('scope.all_locations', $hrd['permissions'], true), 'hrd manager operational location scope retained');

$mechanic = ['id' => 8, 'roles' => ['Mekanik Senior'], 'permissions' => api_builtin_permissions_for_roles(['Mekanik Senior'])];
$assert(!in_array('scope.all_locations', $mechanic['permissions'], true), 'field mechanic remains restricted to assigned location');

$_SERVER['SCRIPT_NAME'] = '/api/users.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$assert(api_route_permission() === 'users.manage', 'user list route requires users.manage');
$_SERVER['REQUEST_METHOD'] = 'POST';
$assert(api_route_permission() === 'dashboard.read', 'POST route permits self-service then action-level authorization');

echo "F0 user authorization: $checks passed. No database writes.\n";
