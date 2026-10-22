<?php
declare(strict_types=1);

putenv('APP_ENV=local');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_NAME=u646470441_ServicePlanBRA');
putenv('DB_USER=root');
putenv('DB_PASSWORD=');

define('API_AUTH_OPTIONAL', true);
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['auth_user'] = [
    'primary_role' => 'Administrator',
    'permissions' => ['*'],
    'assigned_location_id' => null,
];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['asset_id'] = (string) ($argv[1] ?? 'DT-00049');

require dirname(__DIR__) . '/api/unit_properties.php';
