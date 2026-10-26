<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

// 1. Get initial session & CSRF
$ch = curl_init('http://localhost/ServicePlan-BRA/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$res = curl_exec($ch);
curl_close($ch);

$authData = json_decode($res, true);
$csrfToken = $authData['csrf_token'] ?? '';

// 2. Login as admin
$ch = curl_init('http://localhost/ServicePlan-BRA/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'action' => 'login',
    'username' => 'admin',
    'password' => 'admin123',
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-CSRF-Token: ' . $csrfToken,
]);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$loginRes = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Code: {$httpCode}\n";
echo "Login Response: {$loginRes}\n";

// 3. Request dashboard.php
$ch = curl_init('http://localhost/ServicePlan-BRA/dashboard.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
$dashCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Dashboard HTTP Code: {$dashCode}\n";
echo "Dashboard Length: " . strlen($html) . " bytes\n";

echo "Has nwo-parts-flow-alert: " . (str_contains($html, 'nwo-parts-flow-alert') ? 'YES' : 'NO') . "\n";
echo "Has modalWoOvertimeTabBadge: " . (str_contains($html, 'modalWoOvertimeTabBadge') ? 'YES' : 'NO') . "\n";
echo "Has modalWoDetail: " . (str_contains($html, 'modalWoDetail') ? 'YES' : 'NO') . "\n";
echo "Has Ajukan SPL Lembur: " . (str_contains($html, 'Ajukan SPL Lembur') ? 'YES' : 'NO') . "\n";
echo "Has check-parts-stock script reference: " . (str_contains($html, 'dashboard.js') ? 'YES' : 'NO') . "\n";

if (file_exists($cookieFile)) unlink($cookieFile);
