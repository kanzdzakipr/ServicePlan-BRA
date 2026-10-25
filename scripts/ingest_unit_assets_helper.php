<?php
declare(strict_types=1);

/**
 * ServicePlan-BRA: Reusable Unit Assets Ingestion Helper
 * Usage CLI:
 *   php scripts/ingest_unit_assets_helper.php --asset_id=PF-00005 --dir="path/to/folder"
 * Or via PHP include:
 *   ingest_unit_directory($pdo, $assetId, $sourceDir);
 */

function ingest_unit_directory(PDO $pdo, string $assetId, string $sourceDir): array
{
    $cleanId = preg_replace('/[^A-Za-z0-9_\-]/', '', $assetId);
    if ($cleanId === '') {
        throw new InvalidArgumentException("Asset ID is invalid: $assetId");
    }

    if (!is_dir($sourceDir)) {
        throw new RuntimeException("Source directory not found: $sourceDir");
    }

    $rootDir = dirname(__DIR__);
    $baseUploads = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assets';
    $unitDir = $baseUploads . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $cleanId;
    $laragonUnitDir = 'C:\laragon\www\ServicePlan-BRA\uploads\assets\units\\' . $cleanId;

    if (!is_dir($unitDir)) {
        mkdir($unitDir, 0755, true);
    }
    if (is_dir('C:\laragon\www\ServicePlan-BRA') && !is_dir($laragonUnitDir)) {
        mkdir($laragonUnitDir, 0755, true);
    }

    // Ensure .htaccess
    $htaccess = "Require all denied\n";
    if (!file_exists($baseUploads . DIRECTORY_SEPARATOR . '.htaccess')) {
        file_put_contents($baseUploads . DIRECTORY_SEPARATOR . '.htaccess', $htaccess);
    }

    $results = [
        'unit' => $cleanId,
        'photos' => [],
        'documents' => [],
    ];

    $items = scandir($sourceDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $sourceDir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath)) {
            // Subfolder -> docs
            $docsSubDir = $unitDir . DIRECTORY_SEPARATOR . 'docs';
            if (!is_dir($docsSubDir)) mkdir($docsSubDir, 0755, true);
            $subFiles = scandir($fullPath);
            foreach ($subFiles as $sf) {
                if ($sf === '.' || $sf === '..') continue;
                copy($fullPath . DIRECTORY_SEPARATOR . $sf, $docsSubDir . DIRECTORY_SEPARATOR . $sf);
                if (is_dir($laragonUnitDir)) {
                    $lDocs = $laragonUnitDir . DIRECTORY_SEPARATOR . 'docs';
                    if (!is_dir($lDocs)) mkdir($lDocs, 0755, true);
                    copy($fullPath . DIRECTORY_SEPARATOR . $sf, $lDocs . DIRECTORY_SEPARATOR . $sf);
                }
            }
            continue;
        }

        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            // Photo
            $targetName = $cleanId . '-' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $item);
            $targetFile = $unitDir . DIRECTORY_SEPARATOR . $targetName;
            copy($fullPath, $targetFile);
            if (is_dir($laragonUnitDir)) {
                copy($fullPath, $laragonUnitDir . DIRECTORY_SEPARATOR . $targetName);
            }

            $relUrl = "uploads/assets/units/$cleanId/$targetName";
            $title = pathinfo($item, PATHINFO_FILENAME);

            // Infer angle from filename
            $lower = strtolower($item);
            $angle = 'Other';
            if (str_contains($lower, 'front') || str_contains($lower, 'depan')) $angle = 'Front';
            elseif (str_contains($lower, 'right') || str_contains($lower, 'kanan')) $angle = 'Right';
            elseif (str_contains($lower, 'left') || str_contains($lower, 'kiri')) $angle = 'Left';
            elseif (str_contains($lower, 'plate') || str_contains($lower, 'pelat') || str_contains($lower, 'serial')) $angle = 'SerialPlate';
            elseif (str_contains($lower, 'cabin') || str_contains($lower, 'kabin')) $angle = 'Cabin';
            elseif (str_contains($lower, 'engine') || str_contains($lower, 'mesin') || str_contains($lower, 'belakang')) $angle = 'Engine';

            // Check if exists
            $chk = $pdo->prepare('SELECT photo_id FROM asset_gallery_photos WHERE asset_id = :aid AND photo_url = :url');
            $chk->execute([':aid' => $assetId, ':url' => $relUrl]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $upd = $pdo->prepare('UPDATE asset_gallery_photos SET photo_title = :t, photo_angle = :a WHERE photo_id = :pid');
                $upd->execute([':t' => $title, ':a' => $angle, ':pid' => $existing['photo_id']]);
            } else {
                $ins = $pdo->prepare('INSERT INTO asset_gallery_photos (asset_id, photo_title, photo_angle, photo_url) VALUES (:aid, :t, :a, :url)');
                $ins->execute([':aid' => $assetId, ':t' => $title, ':a' => $angle, ':url' => $relUrl]);
            }
            $results['photos'][] = $relUrl;
        } elseif ($ext === 'pdf') {
            // PDF document
            $targetName = $cleanId . '-' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $item);
            $targetFile = $unitDir . DIRECTORY_SEPARATOR . $targetName;
            copy($fullPath, $targetFile);
            if (is_dir($laragonUnitDir)) {
                copy($fullPath, $laragonUnitDir . DIRECTORY_SEPARATOR . $targetName);
            }

            $relUrl = "uploads/assets/units/$cleanId/$targetName";
            $lower = strtolower($item);

            if (str_contains($lower, 'silo')) {
                $upd = $pdo->prepare('UPDATE assets SET silo_pdf_url = :url WHERE asset_id = :aid');
                $upd->execute([':url' => $relUrl, ':aid' => $assetId]);
            } elseif (str_contains($lower, 'stnk')) {
                $upd = $pdo->prepare('UPDATE assets SET stnk_pdf_url = :url WHERE asset_id = :aid');
                $upd->execute([':url' => $relUrl, ':aid' => $assetId]);
            } elseif (str_contains($lower, 'kir')) {
                $upd = $pdo->prepare('UPDATE assets SET kir_pdf_url = :url WHERE asset_id = :aid');
                $upd->execute([':url' => $relUrl, ':aid' => $assetId]);
            } else {
                $upd = $pdo->prepare('UPDATE assets SET service_manual_pdf_url = :url WHERE asset_id = :aid');
                $upd->execute([':url' => $relUrl, ':aid' => $assetId]);
            }
            $results['documents'][] = $relUrl;
        }
    }

    return $results;
}

if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    $opts = getopt('', ['asset_id:', 'dir:']);
    $assetId = $opts['asset_id'] ?? null;
    $dir = $opts['dir'] ?? null;
    if (!$assetId || !$dir) {
        echo "Usage: php " . basename(__FILE__) . " --asset_id=<ID> --dir=<PATH>\n";
        exit(1);
    }
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $res = ingest_unit_directory($pdo, $assetId, $dir);
    echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
}
