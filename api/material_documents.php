<?php
declare(strict_types=1);

require_once 'db.php';

$db = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json_response(405, [
        'status' => 'error',
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'Endpoint arsip material hanya mendukung GET.',
    ]);
}

$path = trim((string) ($_GET['path'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
if ($path !== '') {
    $statement = $db->prepare(
        'SELECT relative_path, category, file_name, title, content_markdown, content_sha256,
                content_bytes, source_modified_at, mapping_target, synced_at
         FROM material_documents
         WHERE relative_path = :path AND source_exists = 1
         LIMIT 1'
    );
    $statement->execute([':path' => str_replace('\\', '/', $path)]);
    $document = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$document) {
        api_json_response(404, [
            'status' => 'error',
            'code' => 'DOCUMENT_NOT_FOUND',
            'message' => 'Dokumen material tidak ditemukan.',
        ]);
    }
    api_json_response(200, ['status' => 'success', 'data' => $document]);
}

$params = [];
$where = 'WHERE source_exists = 1';
if ($category !== '') {
    $where .= ' AND category = :category';
    $params[':category'] = $category;
}
$statement = $db->prepare(
    "SELECT relative_path, category, file_name, title, content_sha256, content_bytes,
            source_modified_at, mapping_target, synced_at
     FROM material_documents {$where}
     ORDER BY category, relative_path"
);
$statement->execute($params);
api_json_response(200, [
    'status' => 'success',
    'data' => $statement->fetchAll(PDO::FETCH_ASSOC),
]);
