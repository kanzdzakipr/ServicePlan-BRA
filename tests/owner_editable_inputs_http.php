<?php
declare(strict_types=1);

$base = getenv('APP_TEST_BASE_URL') ?: 'http://localhost/ServicePlan-BRA';
$cookie = tempnam(sys_get_temp_dir(), 'bra-owner-edit-');
if ($cookie === false) throw new RuntimeException('Cookie temp tidak dapat dibuat.');

$request = static function (string $url, string $method = 'GET', ?array $body = null, array $headers = []) use ($cookie): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_TIMEOUT => 20]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        $headers[] = 'Content-Type: application/json';
    }
    if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($raw, true), $raw];
};

try {
    [$authCode, $auth] = $request($base . '/api/auth.php');
    $csrf = (string) ($auth['csrf_token'] ?? '');
    [$loginCode, $login, $loginRaw] = $request($base . '/api/auth.php', 'POST', [
        'action' => 'login', 'username' => getenv('BROWSER_TEST_USER') ?: 'admin',
        'password' => getenv('BROWSER_TEST_PASSWORD') ?: 'admin123',
    ], ['X-CSRF-Token: ' . $csrf]);
    if ($authCode !== 200 || $loginCode !== 200 || ($login['status'] ?? '') !== 'success') {
        throw new RuntimeException('Login lokal gagal: ' . $loginRaw);
    }

    foreach (['assets.php', 'work_orders.php', 'spb_workflow.php'] as $endpoint) {
        [$code, $payload, $raw] = $request($base . '/api/' . $endpoint);
        if ($code !== 200 || ($payload['status'] ?? '') !== 'success' || !is_array($payload['data'] ?? null)) {
            throw new RuntimeException("{$endpoint} gagal ({$code}): {$raw}");
        }
        $rows = $payload['data'];
        if ($rows && !array_key_exists($endpoint === 'spb_workflow.php' ? 'can_edit_own' : 'can_owner_edit', $rows[0])) {
            throw new RuntimeException("{$endpoint} belum mengirim flag izin revisi pemilik.");
        }
    }
    fwrite(STDOUT, "PASS: endpoint live Asset, WO, dan SPB merespons setelah migrasi owner-editable inputs.\n");
} finally {
    @unlink($cookie);
}
