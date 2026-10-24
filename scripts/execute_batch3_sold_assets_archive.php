<?php
declare(strict_types=1);

/**
 * Strategy: Step 3 (Batch 3: Archive Sold Heavy Equipment Assets)
 * Source:
 *   - material/ASSET/12_ASSET_DIJUAL.md (Unit Terjual ke Kelmi pada 2026-09-25)
 * Targets:
 *   - `archived_items`
 *   - `archive_audit_trail`
 *   - `assets` (status = 'INACTIVE', is_active = 0)
 */

$isCommit = in_array('--commit', $argv, true);
$baseDir = dirname(__DIR__);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// The 3 verified heavy equipment units sold from 12_ASSET_DIJUAL.md
$soldUnits = [
    [
        'asset_id' => 'DT-00071',
        'code_alias' => 'DT 01',
        'license_plate' => 'DT 8126 JE',
        'prev_plate' => 'DT 9884 JE / BM 8737 DO',
        'make_model' => 'Dump Truck Hino FM260JD (2017)',
        'buyer' => 'Kelmi',
        'sold_date' => '2026-09-25',
        'price' => 450000000.00,
        'reason' => 'Unit Terjual: 2026-09-25 seharga Rp 450.000.000 ke Kelmi (Hino FM260JD DT 01 Plat DT 8126 JE)'
    ],
    [
        'asset_id' => 'DT-00073',
        'code_alias' => 'DT 02',
        'license_plate' => 'DT 8973 IE',
        'prev_plate' => 'DT 9883 JE / BM 8736 DO',
        'make_model' => 'Dump Truck Hino FM260JD (2017)',
        'buyer' => 'Kelmi',
        'sold_date' => '2026-09-25',
        'price' => 450000000.00,
        'reason' => 'Unit Terjual: 2026-09-25 seharga Rp 450.000.000 ke Kelmi (Hino FM260JD DT 02 Plat DT 8973 IE)'
    ],
    [
        'asset_id' => 'DT-00061',
        'code_alias' => 'DT 30',
        'license_plate' => 'BG 8367 IJ',
        'prev_plate' => '',
        'make_model' => 'Dump Truck Hino FM260JD (2018)',
        'buyer' => 'Kelmi',
        'sold_date' => '2026-09-25',
        'price' => 450000000.00,
        'reason' => 'Unit Terjual: 2026-09-25 seharga Rp 450.000.000 ke Kelmi (Hino FM260JD DT 30 Plat BG 8367 IJ)'
    ]
];

echo "================ STRATEGY: STEP 3 (ARCHIVE SOLD HEAVY EQUIPMENT) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Sold Units to Archive: " . count($soldUnits) . "\n\n";

foreach ($soldUnits as $u) {
    // Check current asset status in DB
    $stmt = $pdo->prepare("SELECT asset_id, make_model, license_plate, status, is_active FROM assets WHERE asset_id = :id");
    $stmt->execute([':id' => $u['asset_id']]);
    $curr = $stmt->fetch();

    $archStmt = $pdo->prepare("SELECT * FROM archived_items WHERE item_type = 'asset' AND item_id = :id");
    $archStmt->execute([':id' => $u['asset_id']]);
    $arch = $archStmt->fetch();

    echo "Asset: {$u['asset_id']} ({$u['make_model']} | Plat: {$u['license_plate']})\n";
    echo "  - DB Status Saat Ini: " . ($curr ? "{$curr['status']} (is_active={$curr['is_active']})" : "TIDAK DITEMUKAN") . "\n";
    echo "  - Status Arsip Saat Ini: " . ($arch ? "SUDAH DIARSIPKAN" : "BELUM DIARSIPKAN") . "\n";
    echo "  - Pembeli: {$u['buyer']} | Tanggal: {$u['sold_date']} | Nilai: Rp " . number_format($u['price'], 0, ',', '.') . "\n";
    echo "  - Rencana: Masukkan ke archived_items & archive_audit_trail, ubah status assets menjadi INACTIVE\n\n";
}

if ($isCommit) {
    echo "Mengeksekusi pengarsipan unit terjual ke database dalam transaksi...\n";
    $pdo->beginTransaction();
    try {
        $insertArch = $pdo->prepare("
            INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason)
            VALUES ('asset', :id, :sold_at, 1, 'Administrator Utama', :reason)
            ON DUPLICATE KEY UPDATE 
                archived_at = VALUES(archived_at),
                archived_by_user_id = VALUES(archived_by_user_id),
                archived_by_name = VALUES(archived_by_name),
                reason = VALUES(reason)
        ");

        $insertAudit = $pdo->prepare("
            INSERT INTO archive_audit_trail (item_type, item_id, action, actor_user_id, actor_name, reason, created_at)
            VALUES ('asset', :id, 'ARCHIVE', 1, 'Administrator Utama', :reason, NOW())
        ");

        $updateAsset = $pdo->prepare("
            UPDATE assets 
            SET status = 'INACTIVE',
                is_active = 0,
                raw_location_notes = CASE 
                    WHEN raw_location_notes LIKE '%[TERJUAL%' THEN raw_location_notes
                    ELSE CONCAT(COALESCE(raw_location_notes, ''), ' [TERJUAL: ', :buyer, ', ', :sold_date, ']')
                END,
                last_update_timestamp = NOW()
            WHERE asset_id = :id
        ");

        $count = 0;
        foreach ($soldUnits as $u) {
            $soldTimestamp = $u['sold_date'] . ' 00:00:00';
            $insertArch->execute([
                ':id' => $u['asset_id'],
                ':sold_at' => $soldTimestamp,
                ':reason' => $u['reason']
            ]);

            $insertAudit->execute([
                ':id' => $u['asset_id'],
                ':reason' => $u['reason']
            ]);

            $updateAsset->execute([
                ':id' => $u['asset_id'],
                ':buyer' => $u['buyer'],
                ':sold_date' => $u['sold_date']
            ]);

            $count++;
        }

        $pdo->commit();
        echo "SUCCESS: {$count} unit alat berat terjual berhasil diarsipkan ke archived_items dan status diubah ke INACTIVE!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "Untuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
