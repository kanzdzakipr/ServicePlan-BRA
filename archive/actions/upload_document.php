<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();
require_permission('documents.upload');
verify_csrf();

try {
    $folders = [
        'Foto Kerusakan' => 'damage',
        'Quotation' => 'quotations',
        'Purchase Order' => 'purchase_orders',
        'Invoice' => 'documents',
        'BAST' => 'documents',
        'Lainnya' => 'documents',
    ];
    $type = (string) ($_POST['document_type'] ?? '');
    if (!isset($folders[$type]) || !isset($_FILES['document'])) {
        throw new InvalidArgumentException('Data upload tidak valid.');
    }

    $file = upload_file($_FILES['document'], $folders[$type]);
    $unitId = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $notes = function_exists('mb_substr') ? mb_substr($notes, 0, 255) : substr($notes, 0, 255);

    $stmt = $pdo->prepare('INSERT INTO unit_documents
        (unit_id, document_type, original_name, stored_name, file_path, mime_type, file_size, notes, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $unitId ?: null, $type, $file['original_name'], $file['stored_name'], $file['file_path'],
        $file['mime_type'], $file['file_size'], $notes, current_user()['id'],
    ]);
    audit($pdo, 'UPLOAD', 'document', (int) $pdo->lastInsertId(), 'Upload ' . $type);
    flash('success', 'Dokumen berhasil diunggah.');
} catch (Throwable $e) {
    error_log('Document upload rejected: ' . $e->getMessage());
    flash('error', 'Dokumen tidak dapat diunggah. Periksa tipe dan ukuran file.');
}
redirect('../' . page_url('documents'));
