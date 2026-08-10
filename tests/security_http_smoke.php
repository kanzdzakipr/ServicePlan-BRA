<?php
declare(strict_types=1);

$baseUrl = rtrim((string) (getenv('SECURITY_TEST_BASE_URL') ?: ''), '/');
if ($baseUrl === '') {
    echo "SKIP: set SECURITY_TEST_BASE_URL to run read-only HTTP security tests.\n";
    exit(0);
}
if (!function_exists('curl_init')) {
    fwrite(STDERR, "FAIL: PHP cURL extension is required.\n");
    exit(1);
}

$passed = 0;
$failed = 0;
$cookieFile = tempnam(sys_get_temp_dir(), 'bra-security-cookie-');

function httpRequest(string $url, string $method = 'GET', array $headers = [], ?array $body = null): array
{
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $raw = curl_exec($ch);
    if ($raw === false) throw new RuntimeException(curl_error($ch));
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['status' => $status, 'body' => substr($raw, $headerSize), 'headers' => substr($raw, 0, $headerSize)];
}

function httpAssert(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS: {$message}\n";
    } else {
        $failed++;
        fwrite(STDERR, "FAIL: {$message}\n");
    }
}

try {
    $assets = httpRequest($baseUrl . '/api/assets.php');
    httpAssert($assets['status'] === 401, 'unauthenticated API request returns 401');

    $directView = httpRequest($baseUrl . '/dashboard.view.php');
    httpAssert(in_array($directView['status'], [403, 404], true), 'internal dashboard view is not directly accessible');

    $evilOrigin = httpRequest($baseUrl . '/api/auth.php?action=session', 'GET', ['Origin: https://evil.example']);
    httpAssert($evilOrigin['status'] === 403, 'untrusted Origin is denied');

    $session = httpRequest($baseUrl . '/api/auth.php?action=session');
    $sessionJson = json_decode($session['body'], true);
    httpAssert($session['status'] === 200 && is_array($sessionJson), 'session bootstrap returns JSON');
    httpAssert(!empty($sessionJson['csrf_token']), 'session bootstrap returns CSRF token');

    $username = (string) (getenv('SECURITY_TEST_LIMITED_USER') ?: '');
    $password = (string) (getenv('SECURITY_TEST_LIMITED_PASSWORD') ?: '');
    $crossScopeAsset = (string) (getenv('SECURITY_TEST_CROSS_SCOPE_ASSET_ID') ?: '');
    if ($username !== '' && $password !== '' && $crossScopeAsset !== '' && !empty($sessionJson['csrf_token'])) {
        $login = httpRequest($baseUrl . '/api/auth.php', 'POST', [
            'Content-Type: application/json',
            'X-CSRF-Token: ' . $sessionJson['csrf_token'],
        ], ['action' => 'login', 'username' => $username, 'password' => $password]);
        $loginJson = json_decode($login['body'], true);
        httpAssert($login['status'] === 200 && ($loginJson['status'] ?? '') === 'success', 'limited test user can authenticate');

        $user = $loginJson['user'] ?? [];
        $permissions = is_array($user['permissions'] ?? null) ? $user['permissions'] : [];
        $isLimited = !in_array('*', $permissions, true) && !in_array('scope.all_locations', $permissions, true);
        httpAssert($isLimited && isset($user['assigned_location_id']), 'IDOR test account is location-limited');

        $list = httpRequest($baseUrl . '/api/assets.php');
        $listJson = json_decode($list['body'], true);
        $rows = is_array($listJson['data'] ?? null) ? $listJson['data'] : [];
        $assigned = (int) ($user['assigned_location_id'] ?? 0);
        $allScoped = $assigned > 0;
        foreach ($rows as $row) {
            if ((int) ($row['current_location_id'] ?? 0) !== $assigned) $allScoped = false;
        }
        httpAssert($list['status'] === 200 && $allScoped, 'asset list contains only assigned-location rows');

        $cross = httpRequest($baseUrl . '/api/assets.php?id=' . rawurlencode($crossScopeAsset));
        $crossJson = json_decode($cross['body'], true);
        $crossReturned = is_array($crossJson['data'] ?? null) && ($crossJson['data']['asset_id'] ?? '') === $crossScopeAsset;
        httpAssert(in_array($cross['status'], [200, 404], true) && !$crossReturned, 'cross-location asset ID is not disclosed');
    } else {
        echo "SKIP: set limited-user credentials and a cross-scope asset ID for live IDOR verification.\n";
    }
} catch (Throwable $e) {
    $failed++;
    fwrite(STDERR, 'FAIL: HTTP test exception: ' . $e->getMessage() . "\n");
} finally {
    if (is_string($cookieFile) && is_file($cookieFile)) @unlink($cookieFile);
}

echo "\nSecurity HTTP tests: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
