<?php
declare(strict_types=1);

$cookieFile = __DIR__ . "/test_cookie.txt";
$ch = curl_init("http://localhost/ServicePlan-BRA/api/auth.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$res = curl_exec($ch);
curl_close($ch);
$csrf = json_decode($res, true)["csrf_token"] ?? "";

$ch = curl_init("http://localhost/ServicePlan-BRA/api/auth.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["action" => "login", "username" => "admin", "password" => "admin123"]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "X-CSRF-Token: " . $csrf]);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_exec($ch);
curl_close($ch);

$ch = curl_init("http://localhost/ServicePlan-BRA/dashboard.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
curl_close($ch);

file_put_contents("C:/laragon/www/ServicePlan-BRA/test_rendered_dashboard.html", $html);
echo "Saved test_rendered_dashboard.html (" . strlen($html) . " bytes)\n";
