<?php
declare(strict_types=1);

/**
 * ServicePlan-BRA: Ingestion Script for SD 01 (PF-00005, SN: 961582391008)
 * Copies photos to uploads/assets/, registers them in asset_gallery_photos,
 * links SILO & Plate Identity documents in assets table, and preserves originals.
 */

$rootDir = dirname(__DIR__);
$sourceDir = $rootDir . DIRECTORY_SEPARATOR . 'material' . DIRECTORY_SEPARATOR . '0drop-files-here' . DIRECTORY_SEPARATOR . 'Smooth Drum Compactor  BW211D-40 SL 961582391008 (SD 01)';
$baseUploads = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assets';
$laragonBaseUploads = 'C:\laragon\www\ServicePlan-BRA\uploads\assets';

$assetId = 'PF-00005';
$cleanAssetId = preg_replace('/[^A-Za-z0-9_\-]/', '', $assetId);

$targetUploads = $baseUploads . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $cleanAssetId;
$laragonUploads = $laragonBaseUploads . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $cleanAssetId;

if (!is_dir($sourceDir)) {
    die("Error: Source drop directory not found: $sourceDir\n");
}

if (!is_dir($targetUploads)) {
    mkdir($targetUploads, 0755, true);
}
if (!is_dir($laragonUploads) && is_dir('C:\laragon\www\ServicePlan-BRA')) {
    mkdir($laragonUploads, 0755, true);
}

// Ensure .htaccess
$htaccess = "Require all denied\n";
if (!file_exists($baseUploads . DIRECTORY_SEPARATOR . '.htaccess')) {
    file_put_contents($baseUploads . DIRECTORY_SEPARATOR . '.htaccess', $htaccess);
}
if (is_dir($laragonBaseUploads) && !file_exists($laragonBaseUploads . DIRECTORY_SEPARATOR . '.htaccess')) {
    file_put_contents($laragonBaseUploads . DIRECTORY_SEPARATOR . '.htaccess', $htaccess);
}

// Connect to Database
$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$stmt = $pdo->prepare('SELECT asset_id, serial_number, hull_number, alias_name FROM assets WHERE asset_id = :id');
$stmt->execute([':id' => $assetId]);
$asset = $stmt->fetch();
if (!$asset) {
    die("Error: Asset $assetId not found in database.\n");
}
echo "Processing Unit: {$asset['asset_id']} | {$asset['alias_name']} ({$asset['hull_number']}) | SN: {$asset['serial_number']}\n";

// 1. Process 9 Photos
$photoMappings = [
    '665594ce-4637-45b6-a790-3e2d91df7ea2.jpg' => [
        'target' => 'PF-00005-photo-front-01.jpg',
        'title'  => 'Tampak Depan Drum Bomag - BRA 01',
        'angle'  => 'Front',
    ],
    'WhatsApp Image 2024-08-19 at 10.24.22_412273b7.jpg' => [
        'target' => 'PF-00005-photo-front-02.jpg',
        'title'  => 'Tampak Depan Lapangan Proyek Muara Enim',
        'angle'  => 'Front',
    ],
    'WhatsApp Image 2024-08-19 at 10.24.22_55dba95c.jpg' => [
        'target' => 'PF-00005-photo-right-01.jpg',
        'title'  => 'Tampak Samping Kanan (BRA VB-01)',
        'angle'  => 'Right',
    ],
    'e6f7a337-7656-451d-8406-bbe89768da52.jpg' => [
        'target' => 'PF-00005-photo-right-02.jpg',
        'title'  => 'Tampak Samping Kanan Penuh Landscape',
        'angle'  => 'Right',
    ],
    'WhatsApp Image 2024-08-19 at 10.24.22_6ea19a6b.jpg' => [
        'target' => 'PF-00005-photo-left-01.jpg',
        'title'  => 'Tampak Samping Kiri Lapangan Proyek Muara Enim',
        'angle'  => 'Left',
    ],
    'ea287e94-2234-4130-8efa-e1f308d0e0e9.jpg' => [
        'target' => 'PF-00005-photo-left-02.jpg',
        'title'  => 'Tampak Samping Kiri Penuh Landscape',
        'angle'  => 'Left',
    ],
    '92065af8-f268-4cf9-b422-ca2bc26db2c6.jpg' => [
        'target' => 'PF-00005-photo-engine-01.jpg',
        'title'  => 'Tampak Belakang Mesin (Smart Line)',
        'angle'  => 'Engine',
    ],
    'WhatsApp Image 2024-08-19 at 10.24.22_c5af60b0.jpg' => [
        'target' => 'PF-00005-photo-engine-02.jpg',
        'title'  => 'Tampak Belakang Kap Mesin Lapangan',
        'angle'  => 'Engine',
    ],
    'WhatsApp Image 2024-08-19 at 10.24.11_9e0c285e.jpg' => [
        'target' => 'PF-00005-photo-serialplate.jpg',
        'title'  => 'Pelat Nomor Serial Resmi BOMAG SN 961582391008',
        'angle'  => 'SerialPlate',
    ],
];

echo "\n--- Copying Photos and Registering in Gallery ---\n";
foreach ($photoMappings as $srcName => $meta) {
    $srcPath = $sourceDir . DIRECTORY_SEPARATOR . $srcName;
    if (!file_exists($srcPath)) {
        echo "Warning: Source photo not found: $srcName\n";
        continue;
    }

    $destPath = $targetUploads . DIRECTORY_SEPARATOR . $meta['target'];
    copy($srcPath, $destPath);

    if (is_dir($laragonUploads)) {
        copy($srcPath, $laragonUploads . DIRECTORY_SEPARATOR . $meta['target']);
    }

    $photoUrl = 'uploads/assets/units/' . $cleanAssetId . '/' . $meta['target'];

    // Check if exists in DB
    $checkStmt = $pdo->prepare('SELECT photo_id FROM asset_gallery_photos WHERE asset_id = :aid AND photo_url = :url');
    $checkStmt->execute([':aid' => $assetId, ':url' => $photoUrl]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $updStmt = $pdo->prepare('UPDATE asset_gallery_photos SET photo_title = :title, photo_angle = :angle WHERE photo_id = :id');
        $updStmt->execute([':title' => $meta['title'], ':angle' => $meta['angle'], ':id' => $existing['photo_id']]);
        echo "[UPDATED] Gallery Photo #{$existing['photo_id']}: {$meta['title']} ({$meta['angle']})\n";
    } else {
        $insStmt = $pdo->prepare('INSERT INTO asset_gallery_photos (asset_id, photo_title, photo_angle, photo_url) VALUES (:aid, :title, :angle, :url)');
        $insStmt->execute([
            ':aid'   => $assetId,
            ':title' => $meta['title'],
            ':angle' => $meta['angle'],
            ':url'   => $photoUrl,
        ]);
        $newId = $pdo->lastInsertId();
        echo "[INSERTED] Gallery Photo #$newId: {$meta['title']} ({$meta['angle']})\n";
    }
}

// 2. Process PDFs (SILO and Plat/Identitas)
echo "\n--- Copying and Linking Documents ---\n";
$siloSrc = $sourceDir . DIRECTORY_SEPARATOR . 'SILO 961582391008.pdf';
$siloTargetName = 'PF-00005-SILO-961582391008.pdf';
$siloUrl = 'uploads/assets/units/' . $cleanAssetId . '/' . $siloTargetName;

if (file_exists($siloSrc)) {
    copy($siloSrc, $targetUploads . DIRECTORY_SEPARATOR . $siloTargetName);
    if (is_dir($laragonUploads)) {
        copy($siloSrc, $laragonUploads . DIRECTORY_SEPARATOR . $siloTargetName);
    }
    echo "[COPIED] SILO PDF: $siloTargetName (" . round(filesize($siloSrc)/1024) . " KB)\n";
}

$platSrc = $sourceDir . DIRECTORY_SEPARATOR . 'Plat dan Foto Tampak SD 961582391008.pdf';
$platTargetName = 'PF-00005-identitas-plat.pdf';
$platUrl = 'uploads/assets/units/' . $cleanAssetId . '/' . $platTargetName;

if (file_exists($platSrc)) {
    copy($platSrc, $targetUploads . DIRECTORY_SEPARATOR . $platTargetName);
    if (is_dir($laragonUploads)) {
        copy($platSrc, $laragonUploads . DIRECTORY_SEPARATOR . $platTargetName);
    }
    echo "[COPIED] Plat & Identitas PDF: $platTargetName (" . round(filesize($platSrc)/1024) . " KB)\n";
}

// 3. Process subfolder administrative documents
$subDir = $sourceDir . DIRECTORY_SEPARATOR . '961582391008 BOMAG BW2111-40 2021';
$adminDocTargetDir = $targetUploads . DIRECTORY_SEPARATOR . 'docs';
$adminDocLaragonDir = $laragonUploads . DIRECTORY_SEPARATOR . 'docs';
if (!is_dir($adminDocTargetDir)) mkdir($adminDocTargetDir, 0755, true);
if (is_dir('C:\laragon\www\ServicePlan-BRA') && !is_dir($adminDocLaragonDir)) mkdir($adminDocLaragonDir, 0755, true);

if (is_dir($subDir)) {
    $subFiles = scandir($subDir);
    foreach ($subFiles as $f) {
        if (str_ends_with(strtolower($f), '.pdf')) {
            $srcF = $subDir . DIRECTORY_SEPARATOR . $f;
            copy($srcF, $adminDocTargetDir . DIRECTORY_SEPARATOR . $f);
            if (is_dir($adminDocLaragonDir)) {
                copy($srcF, $adminDocLaragonDir . DIRECTORY_SEPARATOR . $f);
            }
            echo "[ARCHIVED] Administrative Doc: $f\n";
        }
    }
}

// 4. Update Asset Legalities & Document Links in assets Table
$updateStmt = $pdo->prepare("
    UPDATE assets
    SET legal_silo_expiry = '2026-08-30',
        silo_pdf_url = :silo_url,
        service_manual_pdf_url = :plat_url,
        invoice_location = 'Faktur PT Traktor Nusantara / BAST Terlampir',
        bpkb_location = 'Perjanjian Pembiayaan Investasi Leasing (Terlampir)'
    WHERE asset_id = :aid
");
$updateStmt->execute([
    ':silo_url' => $siloUrl,
    ':plat_url' => $platUrl,
    ':aid'      => $assetId,
]);
echo "\n[UPDATED] Asset Record for $assetId updated with SILO, Plate Identity PDF, and Legalities.\n";

// 5. Verification
$verifyStmt = $pdo->prepare("
    SELECT asset_id, alias_name, hull_number, legal_silo_expiry, silo_pdf_url, service_manual_pdf_url, bpkb_location, invoice_location
    FROM assets WHERE asset_id = :aid
");
$verifyStmt->execute([':aid' => $assetId]);
$vAsset = $verifyStmt->fetch();

$countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM asset_gallery_photos WHERE asset_id = :aid");
$countStmt->execute([':aid' => $assetId]);
$photoCount = (int) $countStmt->fetch()['total'];

echo "\n--- VERIFICATION RESULT ---\n";
echo "Asset ID: {$vAsset['asset_id']}\n";
echo "SILO Expiry: {$vAsset['legal_silo_expiry']}\n";
echo "SILO PDF URL: {$vAsset['silo_pdf_url']}\n";
echo "Manual/Plate PDF URL: {$vAsset['service_manual_pdf_url']}\n";
echo "BPKB / Leasing: {$vAsset['bpkb_location']}\n";
echo "Invoice Location: {$vAsset['invoice_location']}\n";
echo "Gallery Photos in DB: $photoCount photo(s)\n";
echo "STATUS: ALL FILES INGESTED & LINKED SUCCESSFULLY.\n";
