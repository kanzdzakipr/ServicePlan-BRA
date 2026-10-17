<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/ContractCsv.php';

try {
    $db = Database::getInstance();
    ContractCsv::ensureTable($db);
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $scope = api_location_scope_clause('a', 'contract_location_id');

    if ($method === 'GET') {
        $format = (string) ($_GET['format'] ?? 'json');
        if ($format === 'template' || $format === 'csv') {
            $rows = $format === 'template' ? [] : ContractCsv::records($db, $scope['sql'], $scope['params']);
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . ($format === 'template' ? 'template_kontrak_unit.csv' : 'data_kontrak_unit.csv') . '"');
            echo ContractCsv::export($rows);
            exit;
        }
        api_json_response(200, [
            'status' => 'success',
            'data' => ContractCsv::records($db, $scope['sql'], $scope['params']),
        ]);
    }

    if ($method !== 'POST') api_json_response(405, ['status' => 'error', 'message' => 'Metode tidak didukung.']);
    $mode = (string) ($_POST['mode'] ?? 'preview');
    if (!in_array($mode, ['preview', 'import'], true)) {
        api_json_response(422, ['status' => 'error', 'message' => 'Mode impor tidak dikenal.']);
    }
    $file = $_FILES['file'] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        api_json_response(422, ['status' => 'error', 'message' => 'Pilih file CSV yang valid.']);
    }
    if ((int) $file['size'] > 1024 * 1024 || strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        api_json_response(422, ['status' => 'error', 'message' => 'File harus .csv berukuran maksimal 1 MB.']);
    }
    $csv = file_get_contents((string) $file['tmp_name']);
    if ($csv === false) throw new RuntimeException('File CSV gagal dibaca.');

    $assetsSql = 'SELECT a.asset_id FROM assets a';
    if ($scope['sql'] !== '') $assetsSql .= ' WHERE ' . $scope['sql'];
    $assets = $db->prepare($assetsSql);
    $assets->execute($scope['params']);
    $parsed = ContractCsv::parse($csv, array_fill_keys($assets->fetchAll(PDO::FETCH_COLUMN), true));
    if ($mode === 'preview' || $parsed['errors']) {
        api_json_response(200, [
            'status' => $parsed['errors'] ? 'invalid' : 'success',
            'data' => [
                'rowCount' => count($parsed['rows']),
                'errors' => $parsed['errors'],
                'preview' => array_slice($parsed['rows'], 0, 20),
            ],
            'message' => $parsed['errors'] ? 'Perbaiki seluruh kesalahan sebelum impor.' : 'CSV siap diimpor.',
        ]);
    }

    $db->beginTransaction();
    try {
        $count = ContractCsv::import($db, $parsed['rows'], (int) (api_current_user()['id'] ?? 0));
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
    api_json_response(200, ['status' => 'success', 'data' => ['rowCount' => $count], 'message' => "{$count} baris kontrak tersimpan."]);
} catch (Throwable $error) {
    error_log('Contract CSV failed: ' . $error->getMessage());
    api_json_response(500, ['status' => 'error', 'message' => 'Data kontrak gagal diproses.']);
}
