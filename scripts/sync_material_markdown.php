<?php
declare(strict_types=1);

/**
 * Losslessly synchronize every material/*.md file into material_documents.
 *
 * Usage:
 *   php scripts/sync_material_markdown.php --apply
 *   php scripts/sync_material_markdown.php --verify
 */

$mode = in_array('--apply', $argv, true) ? 'apply' : (in_array('--verify', $argv, true) ? 'verify' : '');
if ($mode === '') {
    fwrite(STDERR, "Gunakan --apply atau --verify.\n");
    exit(2);
}

$host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
$port = (string) (getenv('DB_PORT') ?: '3306');
$dbName = (string) (getenv('DB_NAME') ?: 'u646470441_ServicePlanBRA');
$dbUser = (string) (getenv('DB_USER') ?: 'root');
$dbPassword = (string) (getenv('DB_PASSWORD') ?: (getenv('LOCAL_MYSQL_PASSWORD') ?: ''));

if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true) || $dbName !== 'u646470441_ServicePlanBRA') {
    throw new RuntimeException('Sinkronisasi Markdown hanya diizinkan untuk database Laragon lokal.');
}
if (!preg_match('/^\d{1,5}$/', $port)) {
    throw new RuntimeException('Port database tidak valid.');
}

$root = dirname(__DIR__);
$materialRoot = $root . DIRECTORY_SEPARATOR . 'material';
if (!is_dir($materialRoot)) {
    throw new RuntimeException('Folder material tidak ditemukan: ' . $materialRoot);
}

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]
);

$migrationPath = __DIR__ . DIRECTORY_SEPARATOR . 'migrate_material_documents.sql';
if (!is_file($migrationPath)) {
    throw new RuntimeException('Migrasi material_documents tidak ditemukan.');
}
$pdo->exec((string) file_get_contents($migrationPath));

/** @return array<int, array<string, mixed>> */
function collectMarkdownFiles(string $materialRoot): array
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($materialRoot, FilesystemIterator::SKIP_DOTS)
    );
    $rows = [];
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'md') {
            continue;
        }
        $absolutePath = $fileInfo->getPathname();
        $relativePath = str_replace('\\', '/', substr($absolutePath, strlen($materialRoot) + 1));
        $content = file_get_contents($absolutePath);
        if ($content === false) {
            throw new RuntimeException('Gagal membaca ' . $absolutePath);
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            throw new RuntimeException('Markdown bukan UTF-8: ' . $relativePath);
        }
        $segments = explode('/', $relativePath);
        $category = count($segments) > 1 ? $segments[0] : '_ROOT';
        $title = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
        if (preg_match('/^\s*#\s+(.+)$/m', $content, $match) === 1) {
            $title = trim($match[1]);
        }
        $mappingTargets = [
            'ASSET' => 'assets',
            'BREAKDOWN' => 'work_orders',
            'LP_UNIT_BDOWN_READY_SD_20_PEBRUARI' => 'work_orders',
            'Biaya' => 'procurement_costs',
            'LAPORAN-KEUANGAN' => 'costs',
            'KPI-TEAM' => 'people_kpi',
            'ABSENSI_DAN_LEMBUR_MEI_OKTOBER_2026' => 'attendance',
            'DATA-SPL' => 'spl_overtime_records',
            'KOMTRAX' => 'telematics_logs',
            'PREVENTIVE-MAINTENANCE' => 'pm_plans',
            'LOGISTIK' => 'logistics',
        ];
        $rows[] = [
            'relative_path' => $relativePath,
            'category' => $category,
            'file_name' => $fileInfo->getFilename(),
            'title' => mb_substr($title, 0, 500),
            'content' => $content,
            'sha256' => hash('sha256', $content),
            'bytes' => strlen($content),
            'modified_at' => date('Y-m-d H:i:s', $fileInfo->getMTime()),
            'mapping_target' => $mappingTargets[$category] ?? 'document_archive',
        ];
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($a['relative_path'], $b['relative_path']));
    return $rows;
}

$files = collectMarkdownFiles($materialRoot);
if ($mode === 'apply') {
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE material_documents SET source_exists = 0');
        $upsert = $pdo->prepare(<<<'SQL'
            INSERT INTO material_documents (
                relative_path, category, file_name, title, content_markdown,
                content_sha256, content_bytes, source_modified_at, mapping_target, source_exists
            ) VALUES (
                :relative_path, :category, :file_name, :title, :content,
                :sha256, :bytes, :modified_at, :mapping_target, 1
            )
            ON DUPLICATE KEY UPDATE
                category = VALUES(category),
                file_name = VALUES(file_name),
                title = VALUES(title),
                content_markdown = VALUES(content_markdown),
                content_sha256 = VALUES(content_sha256),
                content_bytes = VALUES(content_bytes),
                source_modified_at = VALUES(source_modified_at),
                mapping_target = VALUES(mapping_target),
                source_exists = 1
        SQL);
        foreach ($files as $row) {
            $upsert->execute([
                ':relative_path' => $row['relative_path'],
                ':category' => $row['category'],
                ':file_name' => $row['file_name'],
                ':title' => $row['title'],
                ':content' => $row['content'],
                ':sha256' => $row['sha256'],
                ':bytes' => $row['bytes'],
                ':modified_at' => $row['modified_at'],
                ':mapping_target' => $row['mapping_target'],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

$dbRows = $pdo->query(
    'SELECT relative_path, content_sha256, content_bytes FROM material_documents WHERE source_exists = 1 ORDER BY relative_path'
)->fetchAll();
$dbByPath = [];
foreach ($dbRows as $row) {
    $dbByPath[(string) $row['relative_path']] = $row;
}

$mismatches = [];
foreach ($files as $file) {
    $stored = $dbByPath[$file['relative_path']] ?? null;
    if ($stored === null
        || !hash_equals($file['sha256'], (string) $stored['content_sha256'])
        || (int) $stored['content_bytes'] !== $file['bytes']) {
        $mismatches[] = $file['relative_path'];
    }
}
$extraPaths = array_values(array_diff(array_keys($dbByPath), array_column($files, 'relative_path')));
$totalBytes = array_sum(array_column($files, 'bytes'));

echo "Material Markdown: " . count($files) . " file, {$totalBytes} byte.\n";
echo "Database aktif: " . count($dbRows) . " dokumen.\n";
echo "Ketidakcocokan: " . count($mismatches) . "; ekstra aktif: " . count($extraPaths) . ".\n";
if ($mismatches !== [] || $extraPaths !== []) {
    foreach (array_slice(array_merge($mismatches, $extraPaths), 0, 20) as $path) {
        echo " - {$path}\n";
    }
    exit(1);
}
echo "Verifikasi SHA-256 seluruh Markdown berhasil.\n";
