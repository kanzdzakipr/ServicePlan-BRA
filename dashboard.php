<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/api/db.php';

if (!api_is_authenticated()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Location: index.html?reason=session', true, 302);
    exit;
}
$db = Database::getInstance();
$context = api_load_user_context($db, (int) api_current_user()['id']);
if ($context === null) {
    api_destroy_session();
    header('Location: index.html?reason=account', true, 302);
    exit;
}
$_SESSION['auth_user'] = $context;
if (!api_has_permission('dashboard.read')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Akses dashboard ditolak.');
}

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

readfile(__DIR__ . '/dashboard.html');
