<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require_login();
require_permission('documents.view');

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$documentId) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$stmt = $pdo->prepare('SELECT original_name, file_path, mime_type, file_size FROM unit_documents WHERE id = ? LIMIT 1');
$stmt->execute([$documentId]);
$document = $stmt->fetch();
$path = $document ? upload_resolve_storage_path((string) $document['file_path']) : null;
if (!$document || $path === null) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$allowedMime = upload_mime_extension_map();
$mime = (string) ($document['mime_type'] ?? '');
if (!isset($allowedMime[$mime])) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$downloadName = upload_safe_download_name((string) $document['original_name'], $mime);
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="download.' . $allowedMime[$mime] . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($path);
exit;
