<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST') {
    AuthMiddleware::checkAccess('asset', 'U');
    $assetId = trim((string) ($_POST['asset_id'] ?? ''));
    api_require_asset_access($db, $assetId);
    $kind = (string) ($_POST['kind'] ?? '');
    if (!in_array($kind, ['photo', 'pdf'], true)) api_json_response(422, ['status' => 'error', 'message' => 'Jenis berkas tidak valid.']);
    $file = $_FILES['file'] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) api_json_response(422, ['status' => 'error', 'message' => 'Berkas tidak berhasil diunggah.']);
    $size = (int) ($file['size'] ?? 0);
    $limit = $kind === 'pdf' ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
    if ($size <= 0 || $size > $limit) api_json_response(422, ['status' => 'error', 'message' => 'Ukuran berkas tidak valid atau melebihi batas.']);
    $tmp = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) api_json_response(422, ['status' => 'error', 'message' => 'Sumber berkas tidak valid.']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowed = $kind === 'pdf' ? ['application/pdf' => 'pdf'] : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || ($kind === 'photo' && @getimagesize($tmp) === false)) api_json_response(422, ['status' => 'error', 'message' => 'Hanya JPEG, PNG, WebP, atau PDF sesuai jenis unggahan yang diterima.']);
    if ($kind === 'pdf' && file_get_contents($tmp, false, null, 0, 5) !== '%PDF-') api_json_response(422, ['status' => 'error', 'message' => 'Isi PDF tidak valid.']);
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assets';
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) api_json_response(500, ['status' => 'error', 'message' => 'Folder unggahan tidak tersedia.']);
    $deny = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!file_exists($deny)) file_put_contents($deny, 'Require all denied' . PHP_EOL);
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmp, $fullPath)) api_json_response(500, ['status' => 'error', 'message' => 'Berkas gagal disimpan.']);
    $relative = 'uploads/assets/' . $filename;
    try {
        if ($kind === 'photo') {
            $title = trim((string) ($_POST['title'] ?? pathinfo((string) ($file['name'] ?? ''), PATHINFO_FILENAME)));
            $angle = (string) ($_POST['angle'] ?? 'Other');
            if (!in_array($angle, ['Front', 'Right', 'Left', 'Cabin', 'Engine', 'SerialPlate', 'Other'], true)) $angle = 'Other';
            $stmt = $db->prepare('INSERT INTO asset_gallery_photos (asset_id, photo_title, photo_angle, photo_url) VALUES (:id, :title, :angle, :url)');
            $stmt->execute([':id' => $assetId, ':title' => mb_substr($title ?: 'Foto aset', 0, 150), ':angle' => $angle, ':url' => $relative]);
        } else {
            $stmt = $db->prepare('UPDATE assets SET service_manual_pdf_url = :url WHERE asset_id = :id');
            $stmt->execute([':url' => $relative, ':id' => $assetId]);
        }
    } catch (Throwable $e) {
        unlink($fullPath);
        error_log('Asset document DB update failed: ' . $e->getMessage());
        api_json_response(500, ['status' => 'error', 'message' => 'Metadata berkas gagal disimpan.']);
    }
    api_json_response(201, ['status' => 'success', 'photo_id' => $kind === 'photo' ? (int) $db->lastInsertId() : null]);
}

if ($method !== 'GET') api_json_response(405, ['status' => 'error', 'message' => 'Metode tidak didukung.']);
AuthMiddleware::checkAccess('asset', 'R');
$kind = (string) ($_GET['kind'] ?? '');
if ($kind === 'photo') {
    $stmt = $db->prepare('SELECT asset_id, photo_url AS path FROM asset_gallery_photos WHERE photo_id = :id');
    $stmt->execute([':id' => (int) ($_GET['photo_id'] ?? 0)]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($kind === 'pdf') {
    $stmt = $db->prepare('SELECT asset_id, service_manual_pdf_url AS path FROM assets WHERE asset_id = :id');
    $stmt->execute([':id' => (string) ($_GET['asset_id'] ?? '')]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    api_json_response(422, ['status' => 'error', 'message' => 'Jenis berkas tidak valid.']);
}
if (!$item || empty($item['path'])) api_json_response(404, ['status' => 'error', 'message' => 'Berkas tidak ditemukan.']);
api_require_asset_access($db, (string) $item['asset_id']);
$base = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assets');
$path = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $item['path']));
if (!$base || !$path || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) api_json_response(404, ['status' => 'error', 'message' => 'Berkas tidak ditemukan.']);
header('Content-Type: ' . ($kind === 'pdf' ? 'application/pdf' : ((new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream')));
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
