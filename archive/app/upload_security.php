<?php
declare(strict_types=1);

function upload_mime_extension_map(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
}

function upload_sanitize_original_name(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
    $name = preg_replace('/[^\pL\pN._ -]/u', '_', $name) ?? '';
    $name = trim($name, " .\t\n\r\0\x0B");
    if ($name === '') $name = 'document';
    return function_exists('mb_substr') ? mb_substr($name, 0, 180) : substr($name, 0, 180);
}

function upload_safe_download_name(string $originalName, string $mime): string
{
    $extension = upload_mime_extension_map()[$mime] ?? null;
    if ($extension === null) return 'document.bin';
    $sanitized = upload_sanitize_original_name($originalName);
    $stem = pathinfo($sanitized, PATHINFO_FILENAME);
    $stem = trim($stem, " .\t\n\r\0\x0B");
    if ($stem === '') $stem = 'document';
    return $stem . '.' . $extension;
}

function upload_storage_root(): string
{
    $configured = trim((string) (getenv('UPLOAD_STORAGE_PATH') ?: ''));
    $root = $configured !== ''
        ? $configured
        : dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'serviceplan-private-uploads';

    if (!is_dir($root) && !mkdir($root, 0750, true) && !is_dir($root)) {
        throw new RuntimeException('Storage upload tidak tersedia.');
    }
    $resolved = realpath($root);
    if ($resolved === false || !is_writable($resolved)) {
        throw new RuntimeException('Storage upload tidak dapat ditulis.');
    }

    $environment = strtolower(trim((string) (getenv('APP_ENV') ?: 'production')));
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if (in_array($environment, ['production', 'live'], true) && $documentRoot !== false) {
        $documentPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $storagePrefix = rtrim($resolved, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with(strtolower($storagePrefix), strtolower($documentPrefix))) {
            throw new RuntimeException('Storage upload production harus berada di luar webroot.');
        }
    }

    return $resolved;
}

function upload_validate_folder(string $folder): string
{
    $allowed = ['damage', 'quotations', 'purchase_orders', 'documents'];
    if (!in_array($folder, $allowed, true)) {
        throw new InvalidArgumentException('Folder upload tidak valid.');
    }
    return $folder;
}

function upload_validate_content(string $temporaryPath, string $mime): void
{
    if (str_starts_with($mime, 'image/')) {
        $image = @getimagesize($temporaryPath);
        if ($image === false || ($image['mime'] ?? '') !== $mime) {
            throw new RuntimeException('Konten gambar tidak valid.');
        }
        return;
    }

    if ($mime === 'application/pdf') {
        $handle = @fopen($temporaryPath, 'rb');
        $signature = $handle ? fread($handle, 5) : false;
        if (is_resource($handle)) fclose($handle);
        if ($signature !== '%PDF-') {
            throw new RuntimeException('Konten PDF tidak valid.');
        }
    }
}

function upload_file(array $file, string $folder, array $allowed = []): array
{
    $folder = upload_validate_folder($folder);
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File gagal diunggah.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 10 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file harus antara 1 byte dan 10 MB.');
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Sumber upload tidak valid.');
    }

    $mimeMap = upload_mime_extension_map();
    if ($allowed !== []) $mimeMap = array_intersect_key($mimeMap, array_flip($allowed));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    if (!is_string($mime) || !isset($mimeMap[$mime])) {
        throw new RuntimeException('Format file tidak diizinkan.');
    }
    upload_validate_content($temporaryPath, $mime);

    $storageRoot = upload_storage_root();
    $directory = $storageRoot . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan tidak tersedia.');
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $mimeMap[$mime];
    $destination = $directory . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('Tidak dapat menyimpan file.');
    }
    @chmod($destination, 0640);

    return [
        'original_name' => upload_sanitize_original_name((string) ($file['name'] ?? 'document')),
        'stored_name' => $storedName,
        'file_path' => $folder . '/' . $storedName,
        'mime_type' => $mime,
        'file_size' => $size,
    ];
}

function upload_resolve_storage_path(string $storageKey): ?string
{
    $storageKey = str_replace('\\', '/', trim($storageKey));
    if (str_starts_with($storageKey, 'uploads/')) {
        $storageKey = substr($storageKey, strlen('uploads/'));
    }
    if (!preg_match('#^(damage|quotations|purchase_orders|documents)/[a-f0-9]{32}\.(jpg|png|webp|pdf)$#', $storageKey)) {
        return null;
    }

    $relative = str_replace('/', DIRECTORY_SEPARATOR, $storageKey);
    $root = upload_storage_root();
    $candidate = realpath($root . DIRECTORY_SEPARATOR . $relative);

    // Read-only compatibility for existing uploads while they are migrated.
    if ($candidate === false) {
        $legacyRoot = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
        $candidate = $legacyRoot !== false ? realpath($legacyRoot . DIRECTORY_SEPARATOR . $relative) : false;
        $root = $legacyRoot !== false ? $legacyRoot : $root;
    }
    if ($candidate === false || !is_file($candidate) || is_link($candidate)) return null;

    $rootPrefix = rtrim((string) $root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with(strtolower($candidate), strtolower($rootPrefix))) return null;
    return $candidate;
}
