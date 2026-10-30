<?php
declare(strict_types=1);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_serviceplanbra;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$mapping = [
    18 => [
        'old_file' => '95d108a303ef1027cd12aad67d88e034.jpg',
        'new_file' => 'PF-41002-photo-cabin-01.jpg',
        'angle' => 'Cabin',
        'title' => 'Panel Indikator Hour Meter Kabin (00652.1 HM)'
    ],
    17 => [
        'old_file' => '36646bb0dc358075328fdbdf741d3deb.jpg',
        'new_file' => 'PF-41002-photo-engine-01.jpg',
        'angle' => 'Engine',
        'title' => 'Pelepasan Cover Samping Kompartemen Mesin'
    ],
    19 => [
        'old_file' => '42b641d75befa9ca95952e1a99860f96.jpg',
        'new_file' => 'PF-41002-photo-engine-02.jpg',
        'angle' => 'Engine',
        'title' => 'Pelepasan Grill Pelindung Kompartemen Mesin'
    ],
    24 => [
        'old_file' => '2eda3b0ff606812692464c366006f358.jpg',
        'new_file' => 'PF-41002-photo-engine-03.jpg',
        'angle' => 'Engine',
        'title' => 'Pemasangan Filter Mesin Weichai Baru'
    ],
    21 => [
        'old_file' => '59f312d906f6d672d1e6dcb8b39a57e0.jpg',
        'new_file' => 'PF-41002-photo-engine-04.jpg',
        'angle' => 'Engine',
        'title' => 'Pemasangan Filter Bahan Bakar Solar'
    ],
    26 => [
        'old_file' => '7cebaa63c522f81d1be718a66f3ee258.jpg',
        'new_file' => 'PF-41002-photo-engine-05.jpg',
        'angle' => 'Engine',
        'title' => 'Pelumasan & Pengecekan Filter Oli Mesin'
    ],
    23 => [
        'old_file' => '4ab92a10ae8d61371e345336d7396b48.jpg',
        'new_file' => 'PF-41002-photo-engine-06.jpg',
        'angle' => 'Engine',
        'title' => 'Pemasangan Filter Oli ke Dudukan Blok Mesin'
    ],
    25 => [
        'old_file' => 'fd05a3167e78e35f5ad1e8995dd5016b.jpg',
        'new_file' => 'PF-41002-photo-engine-07.jpg',
        'angle' => 'Engine',
        'title' => 'Pengencangan Filter Oli dengan Kunci Sabuk'
    ],
    22 => [
        'old_file' => '9d0cf791ebbf17d4035492ca43b2fae7.jpg',
        'new_file' => 'PF-41002-photo-engine-08.jpg',
        'angle' => 'Engine',
        'title' => 'Pembersihan Elemen Luar Filter Udara (Air Cleaner Outer)'
    ],
    20 => [
        'old_file' => 'ddffd33c43c7aa1b447f2f3e0eeb41ab.jpg',
        'new_file' => 'PF-41002-photo-engine-09.jpg',
        'angle' => 'Engine',
        'title' => 'Pembersihan Elemen Dalam Filter Udara (Air Cleaner Inner)'
    ],
];

$dir = dirname(__DIR__) . '/uploads/assets/units/PF-41002';
if (!is_dir($dir)) {
    die("Dir not found: $dir\n");
}

$updateStmt = $pdo->prepare("UPDATE asset_gallery_photos SET photo_title = :title, photo_angle = :angle, photo_url = :url WHERE photo_id = :id");

foreach ($mapping as $photoId => $info) {
    $oldPath = $dir . '/' . $info['old_file'];
    $newPath = $dir . '/' . $info['new_file'];
    $relPath = 'uploads/assets/units/PF-41002/' . $info['new_file'];

    if (file_exists($oldPath)) {
        if (!rename($oldPath, $newPath)) {
            echo "Failed to rename $oldPath to $newPath\n";
            continue;
        }
        echo "Renamed {$info['old_file']} -> {$info['new_file']}\n";
    } elseif (file_exists($newPath)) {
        echo "File already renamed: {$info['new_file']}\n";
    } else {
        echo "Source file missing: {$info['old_file']}\n";
    }

    $updateStmt->execute([
        ':title' => $info['title'],
        ':angle' => $info['angle'],
        ':url' => $relPath,
        ':id' => $photoId
    ]);
    echo "Updated DB photo_id $photoId -> $relPath ({$info['angle']})\n";
}

echo "Done processing PF-41002 gallery photos.\n";

