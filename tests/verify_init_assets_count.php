<?php
declare(strict_types=1);

$cookieFile = __DIR__ . '/test_cookie_init.txt';
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

// 2. Login
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
curl_exec($ch);
curl_close($ch);

// 3. Request api/init.php
$ch = curl_init('http://localhost/ServicePlan-BRA/api/init.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$initJson = curl_exec($ch);
curl_close($ch);

$data = json_decode($initJson, true);
echo "API /api/init.php assets count: " . count($data['assets'] ?? []) . PHP_EOL;

// Breakdown by category
$categories = [];
foreach ($data['assets'] ?? [] as $a) {
    $cat = $a['category'] ?? 'Lainnya';
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}
echo "Assets by category: " . json_encode($categories, JSON_PRETTY_PRINT) . PHP_EOL;
