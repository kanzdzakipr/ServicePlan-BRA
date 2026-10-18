<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AdministrationRecap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json_response(405, ['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
}
$db = Database::getInstance();
api_require_permission('logistics.read');

$period = trim((string) ($_GET['period'] ?? date('Y-m')));
if (!preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $period)) {
    api_json_response(422, ['status' => 'error', 'message' => 'Periode harus dalam format YYYY-MM.']);
}
try {
    api_json_response(200, AdministrationRecap::logistics($db, $period));
} catch (Throwable $error) {
    error_log('administration_recap: ' . $error->getMessage());
    api_json_response(500, ['status' => 'error', 'message' => 'Rekap administrasi belum dapat dimuat.']);
}
