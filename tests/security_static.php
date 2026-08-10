<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$passed = 0;
$failed = 0;

function staticAssert(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS: {$message}\n";
        return;
    }
    $failed++;
    fwrite(STDERR, "FAIL: {$message}\n");
}

function source(string $relative): string
{
    global $root;
    $content = file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    if ($content === false) throw new RuntimeException("Cannot read {$relative}");
    return $content;
}

$scopeCoverage = [
    'api/assets.php' => ['api_location_scope_clause', 'api_require_asset_access'],
    'api/work_orders.php' => ['api_location_scope_clause', 'api_require_work_order_access'],
    'api/fuel_logs.php' => ['api_location_scope_clause', 'api_require_asset_access'],
    'api/pm_plans.php' => ['api_location_scope_clause', 'api_require_asset_access'],
    'api/tire_inspections.php' => ['api_location_scope_clause', 'api_require_asset_access'],
    'api/inspections.php' => ['api_location_scope_clause', 'api_require_asset_access'],
    'api/accidents.php' => ['api_location_scope_clause', 'api_require_asset_access', 'OBJECT_ID_CONFLICT'],
    'api/logistics.php' => ['api_location_scope_clause', 'api_require_asset_access', 'OBJECT_ID_CONFLICT'],
    'api/init.php' => ['api_location_scope_clause'],
    'api/sync.php' => ['api_require_asset_access', 'api_require_work_order_access', 'OBJECT_ID_CONFLICT'],
    'api/archive.php' => ['api_location_scope_clause', 'requireArchiveItemAccess'],
    'api/reports.php' => ['api_report_owner_scope_clause'],
];

foreach ($scopeCoverage as $file => $needles) {
    $content = source($file);
    foreach ($needles as $needle) {
        staticAssert(str_contains($content, $needle), "{$file} contains {$needle}");
    }
}

$security = source('api/security.php');
staticAssert(str_contains($security, "'sql' => '1 = 0'"), 'missing location assignment remains deny-all');
staticAssert(str_contains($security, "'scope.all_locations'"), 'global location access requires explicit permission');
staticAssert(str_contains($security, "'reports.read_all'"), 'global report access requires explicit permission');

$upload = source('archive/app/upload_security.php');
staticAssert(!str_contains($upload, "pathinfo(\$file['name']"), 'stored extension never comes from user filename');
staticAssert(str_contains($upload, 'is_uploaded_file'), 'upload source must be an HTTP upload');
staticAssert(str_contains($upload, 'upload_validate_content'), 'upload content signature is validated');
staticAssert(str_contains($upload, 'serviceplan-private-uploads'), 'default upload storage is outside project webroot');

$htaccess = source('.htaccess');
staticAssert(str_contains($htaccess, 'archive|arsip|storage|tests'), 'legacy app, private storage, and tests are denied from web');
staticAssert(str_contains($htaccess, 'scripts/.*'), 'maintenance script extensions are denied from web');

$migration = source('scripts/security_rbac_migration.sql');
staticAssert(str_contains($migration, "'scope.all_locations'"), 'RBAC migration includes global location scope');
staticAssert(str_contains($migration, "'reports.read_all'"), 'RBAC migration includes report read-all scope');
staticAssert(!str_contains($migration, "'READ_ALL'"), 'RBAC migration uses only schema-supported action types');

echo "\nSecurity static tests: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
