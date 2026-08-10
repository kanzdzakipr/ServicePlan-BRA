<?php
declare(strict_types=1);

putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';

require_once __DIR__ . '/../api/security.php';
require_once __DIR__ . '/../archive/app/upload_security.php';

$passed = 0;
$failed = 0;

function securityAssert(bool $condition, string $message): void
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

function securityAssertThrows(callable $callback, string $message): void
{
    try {
        $callback();
        securityAssert(false, $message);
    } catch (Throwable $e) {
        securityAssert(true, $message);
    }
}

$_SESSION = ['auth_user' => [
    'id' => 5,
    'permissions' => ['assets.read', 'reports.read'],
    'assigned_location_id' => 4,
]];

securityAssert(api_can_access_location(4), 'limited user can access assigned location');
securityAssert(!api_can_access_location(3), 'limited user cannot access another location');
securityAssert(!api_can_access_location(null), 'limited user cannot access unassigned rows');
$locationScope = api_location_scope_clause('a', 'test_location_id');
securityAssert($locationScope['sql'] === 'a.current_location_id = :test_location_id', 'location scope emits a server-owned predicate');
securityAssert(($locationScope['params'][':test_location_id'] ?? null) === 4, 'location scope binds session location');

securityAssert(api_can_access_owner(5), 'report owner can access own report');
securityAssert(!api_can_access_owner(6), 'report owner cannot access another user report');
$ownerScope = api_report_owner_scope_clause('r', 'test_owner_id');
securityAssert($ownerScope['sql'] === 'r.created_by = :test_owner_id', 'report scope emits ownership predicate');
securityAssert(($ownerScope['params'][':test_owner_id'] ?? null) === 5, 'report scope binds authenticated user id');

$_SESSION['auth_user']['permissions'][] = 'scope.all_locations';
securityAssert(api_location_scope_clause()['sql'] === '', 'explicit all-location permission enables global location scope');
$_SESSION['auth_user']['permissions'][] = 'reports.read_all';
securityAssert(api_report_owner_scope_clause()['sql'] === '', 'explicit report read-all permission enables global report scope');

$_SESSION['auth_user'] = ['id' => 7, 'permissions' => ['assets.read'], 'assigned_location_id' => null];
securityAssert(api_location_scope_clause()['sql'] === '1 = 0', 'missing location assignment is deny-all');
securityAssertThrows(fn() => api_location_scope_clause('a;DROP_TABLE'), 'unsafe SQL alias is rejected');

$mimeMap = upload_mime_extension_map();
securityAssert(($mimeMap['image/jpeg'] ?? null) === 'jpg', 'JPEG extension is derived from MIME');
securityAssert(!in_array('php', $mimeMap, true), 'executable PHP extension is never generated');
securityAssert(upload_validate_folder('documents') === 'documents', 'known upload folder is accepted');
securityAssertThrows(fn() => upload_validate_folder('../public'), 'upload path traversal is rejected');
securityAssert(upload_sanitize_original_name("../evil\r\n.php") === 'evil.php', 'original filename is reduced to a safe basename');
securityAssert(upload_safe_download_name('evil.php', 'image/png') === 'evil.png', 'download filename uses verified MIME extension');
securityAssert(upload_resolve_storage_path('../../.env') === null, 'storage path traversal cannot be resolved');

$validPdf = tempnam(sys_get_temp_dir(), 'bra-pdf-');
$invalidPdf = tempnam(sys_get_temp_dir(), 'bra-bad-');
if ($validPdf !== false && $invalidPdf !== false) {
    file_put_contents($validPdf, "%PDF-1.7\n%%EOF");
    file_put_contents($invalidPdf, "not-a-pdf");
    try {
        upload_validate_content($validPdf, 'application/pdf');
        securityAssert(true, 'valid PDF signature is accepted');
    } catch (Throwable $e) {
        securityAssert(false, 'valid PDF signature is accepted');
    }
    securityAssertThrows(fn() => upload_validate_content($invalidPdf, 'application/pdf'), 'spoofed PDF content is rejected');
    @unlink($validPdf);
    @unlink($invalidPdf);
} else {
    securityAssert(false, 'temporary files available for upload validation tests');
}

echo "\nSecurity unit tests: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
