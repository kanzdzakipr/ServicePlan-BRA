<?php
declare(strict_types=1);

/**
 * ServicePlan-BRA: Unit Assets Reorganization Script
 * Moves unit assets into structured directories: uploads/assets/units/{asset_id}/
 * and updates database URLs in assets and asset_gallery_photos.
 */

$rootDir = dirname(__DIR__);
$baseUploads = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assets';
$laragonUploads = 'C:\laragon\www\ServicePlan-BRA\uploads\assets';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$assetId = 'PF-00005';
$cleanId = preg_replace('/[^A-Za-z0-9_\-]/', '', $assetId);

$targetUnitDir = $baseUploads . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $cleanId;
if (!is_dir($targetUnitDir)) {
    mkdir($targetUnitDir, 0755, true);
}

$laragonUnitDir = $laragonUploads . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $cleanId;
if (is_dir('C:\laragon\www\ServicePlan-BRA') && !is_dir($laragonUnitDir)) {
    mkdir($laragonUnitDir, 0755, true);
}

echo "=== REORGANIZING ASSETS FOR UNIT: $assetId ===\n";
echo "Target Directory: $targetUnitDir\n\n";

// 1. Move 9 Photos
$photos = [
    'PF-00005-photo-front-01.jpg',
    'PF-00005-photo-front-02.jpg',
    'PF-00005-photo-right-01.jpg',
    'PF-00005-photo-right-02.jpg',
    'PF-00005-photo-left-01.jpg',
    'PF-00005-photo-left-02.jpg',
    'PF-00005-photo-engine-01.jpg',
    'PF-00005-photo-engine-02.jpg',
    'PF-00005-photo-serialplate.jpg',
];

foreach ($photos as $photo) {
    $src = $baseUploads . DIRECTORY_SEPARATOR . $photo;
    $dst = $targetUnitDir . DIRECTORY_SEPARATOR . $photo;

    if (file_exists($src)) {
        rename($src, $dst);
        echo "[MOVED] Photo: $photo -> units/$cleanId/$photo\n";
    } elseif (file_exists($dst)) {
        echo "[EXISTS] Photo already in target: $photo\n";
    }

    if (is_dir($laragonUnitDir)) {
        $laragonSrc = $laragonUploads . DIRECTORY_SEPARATOR . $photo;
        $laragonDst = $laragonUnitDir . DIRECTORY_SEPARATOR . $photo;
        if (file_exists($laragonSrc)) {
            rename($laragonSrc, $laragonDst);
        } elseif (file_exists($dst) && !file_exists($laragonDst)) {
            copy($dst, $laragonDst);
        }
    }
}

// 2. Move PDF Documents (SILO & Plat Identitas)
$pdfs = [
    'PF-00005-SILO-961582391008.pdf',
    'PF-00005-identitas-plat.pdf',
];

foreach ($pdfs as $pdf) {
    $src = $baseUploads . DIRECTORY_SEPARATOR . $pdf;
    $dst = $targetUnitDir . DIRECTORY_SEPARATOR . $pdf;

    if (file_exists($src)) {
        rename($src, $dst);
        echo "[MOVED] PDF: $pdf -> units/$cleanId/$pdf\n";
    } elseif (file_exists($dst)) {
        echo "[EXISTS] PDF already in target: $pdf\n";
    }

    if (is_dir($laragonUnitDir)) {
        $laragonSrc = $laragonUploads . DIRECTORY_SEPARATOR . $pdf;
        $laragonDst = $laragonUnitDir . DIRECTORY_SEPARATOR . $pdf;
        if (file_exists($laragonSrc)) {
            rename($laragonSrc, $laragonDst);
        } elseif (file_exists($dst) && !file_exists($laragonDst)) {
            copy($dst, $laragonDst);
        }
    }
}

// 3. Move Administrative Documents Folder (docs-PF-00005 -> units/PF-00005/docs)
$srcDocsDir = $baseUploads . DIRECTORY_SEPARATOR . 'docs-PF-00005';
$dstDocsDir = $targetUnitDir . DIRECTORY_SEPARATOR . 'docs';
if (!is_dir($dstDocsDir)) {
    mkdir($dstDocsDir, 0755, true);
}

if (is_dir($srcDocsDir)) {
    $files = scandir($srcDocsDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        rename($srcDocsDir . DIRECTORY_SEPARATOR . $f, $dstDocsDir . DIRECTORY_SEPARATOR . $f);
        echo "[MOVED] Admin Doc: $f -> units/$cleanId/docs/$f\n";
    }
    @rmdir($srcDocsDir);
}

if (is_dir($laragonUploads)) {
    $laragonSrcDocs = $laragonUploads . DIRECTORY_SEPARATOR . 'docs-PF-00005';
    $laragonDstDocs = $laragonUnitDir . DIRECTORY_SEPARATOR . 'docs';
    if (!is_dir($laragonDstDocs)) mkdir($laragonDstDocs, 0755, true);
    if (is_dir($laragonSrcDocs)) {
        $files = scandir($laragonSrcDocs);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            rename($laragonSrcDocs . DIRECTORY_SEPARATOR . $f, $laragonDstDocs . DIRECTORY_SEPARATOR . $f);
        }
        @rmdir($laragonSrcDocs);
    }
}

// 4. Update Database Records
echo "\n--- Updating Database Records ---\n";

// Update asset_gallery_photos
$galleryStmt = $pdo->prepare("
    UPDATE asset_gallery_photos
    SET photo_url = REPLACE(photo_url, 'uploads/assets/', 'uploads/assets/units/PF-00005/')
    WHERE asset_id = :aid AND photo_url NOT LIKE 'uploads/assets/units/%'
");
$galleryStmt->execute([':aid' => $assetId]);
echo "Updated gallery photo URLs: " . $galleryStmt->rowCount() . " row(s).\n";

// Update assets table
$newSiloUrl = "uploads/assets/units/$cleanId/PF-00005-SILO-961582391008.pdf";
$newPlatUrl = "uploads/assets/units/$cleanId/PF-00005-identitas-plat.pdf";

$assetStmt = $pdo->prepare("
    UPDATE assets
    SET silo_pdf_url = :silo_url,
        service_manual_pdf_url = :plat_url
    WHERE asset_id = :aid
");
$assetStmt->execute([
    ':silo_url' => $newSiloUrl,
    ':plat_url' => $newPlatUrl,
    ':aid'      => $assetId,
]);
echo "Updated assets document URLs for $assetId.\n";

// 5. Verification
echo "\n--- VERIFICATION ---\n";
$stmt = $pdo->prepare("SELECT silo_pdf_url, service_manual_pdf_url FROM assets WHERE asset_id = :aid");
$stmt->execute([':aid' => $assetId]);
$row = $stmt->fetch();
echo "silo_pdf_url: {$row['silo_pdf_url']}\n";
echo "service_manual_pdf_url: {$row['service_manual_pdf_url']}\n";

$pStmt = $pdo->prepare("SELECT photo_id, photo_title, photo_angle, photo_url FROM asset_gallery_photos WHERE asset_id = :aid");
$pStmt->execute([':aid' => $assetId]);
$photosInDb = $pStmt->fetchAll();
echo "Photos in Database: " . count($photosInDb) . "\n";
foreach ($photosInDb as $p) {
    echo "  [{$p['photo_angle']}] {$p['photo_title']} -> {$p['photo_url']}\n";
}
echo "REORGANIZATION COMPLETE!\n";
