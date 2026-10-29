<?php
/**
 * scripts/migrate_batch12_sold_assets_archive.php
 *
 * Batch 12 Migration:
 * - Reconciles sold assets from `material/ASSET/REKAP HEAVY EQUIPMENT BRA 2026  (8)/12_ASSET_DIJUAL.md`.
 * - Cross-references database fleet and archives all sold units matching Serial Number, License Plate, or Mutasi Plate:
 *     - DT-00071 (Hino Ranger FM260JD / DT 8126 JE) -> Sold to Kelmi per 25 Sept 2026 (Rp 450.000.000)
 *     - DT-00161 (Dump Truck BM 8737 DO - mutasi plate DT 01) -> Sold to Kelmi per 25 Sept 2026
 *     - DT-00073 (Hino Ranger FM260JD / DT 8973 IE) -> Sold to Kelmi per 25 Sept 2026 (Rp 450.000.000)
 *     - DT-00160 (Dump Truck BM 8736 DO - mutasi plate DT 02) -> Sold to Kelmi per 25 Sept 2026
 *     - DT-00061 (Hino Ranger FM260JD / BG 8367 IJ) -> Sold to Kelmi per 25 Sept 2026 (Rp 450.000.000)
 *     - DT-00023-D (Hino FM260JD / D 9692 XV / DD 8273 S) -> Sold to Kelmi per 29 Aug 2025 (Rp 250.000.000)
 *     - LV-00065 (Toyota Innova 2.4 G / B 1167 ZKS) -> Sold to Raymond per 21 Sept 2026
 * - Sets status = 'INACTIVE', is_active = 0 in `assets` table (preserving relational foreign keys for work_orders & inventory).
 * - Inserts audit record into `archived_items`.
 * - Records migration in `app_data_migrations` under `BATCH_12_SOLD_ASSETS_RECONCILIATION`.
 */

declare(strict_types=1);

define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Starting Batch 12 Migration: Sold Assets Archival & Reconciliation ===\n";

// 1. Check if already applied
$checkMig = $db->prepare("SELECT migration_key FROM app_data_migrations WHERE migration_key = ?");
$checkMig->execute(['BATCH_12_SOLD_ASSETS_RECONCILIATION']);
if ($checkMig->fetch()) {
    echo "[INFO] Migration BATCH_12_SOLD_ASSETS_RECONCILIATION has already been applied. Re-verifying archival state...\n";
}

// 2. Target matched assets list based on 12_ASSET_DIJUAL.md cross-reference
$soldAssets = [
    [
        'asset_id'    => 'DT-00071',
        'plate'       => 'DT 8126 JE',
        'buyer'       => 'Kelmi',
        'sale_date'   => '2026-09-25',
        'sale_price'  => 450000000,
        'unit_code'   => 'DT 01',
        'description' => 'Dump Truck HINO RANGER-FM 260 JD (DT 9884 JE / DT 8126 JE / BM 8737 DO)'
    ],
    [
        'asset_id'    => 'DT-00161',
        'plate'       => 'BM 8737 DO',
        'buyer'       => 'Kelmi',
        'sale_date'   => '2026-09-25',
        'sale_price'  => 450000000,
        'unit_code'   => 'DT 01',
        'description' => 'Dump Truck HINO RANGER-FM 260 JD (Mutasi Pelat BM 8737 DO ex DT 01)'
    ],
    [
        'asset_id'    => 'DT-00073',
        'plate'       => 'DT 8973 IE',
        'buyer'       => 'Kelmi',
        'sale_date'   => '2026-09-25',
        'sale_price'  => 450000000,
        'unit_code'   => 'DT 02',
        'description' => 'Dump Truck HINO RANGER-FM 260 JD (DT 9883 JE / DT 8973 IE / BM 8736 DO)'
    ],
    [
        'asset_id'    => 'DT-00160',
        'plate'       => 'BM 8736 DO',
        'buyer'       => 'Kelmi',
        'sale_date'   => '2026-09-25',
        'sale_price'  => 450000000,
        'unit_code'   => 'DT 02',
        'description' => 'Dump Truck HINO RANGER-FM 260 JD (Mutasi Pelat BM 8736 DO ex DT 02)'
    ],
    [
        'asset_id'    => 'DT-00061',
        'plate'       => 'BG 8367 IJ',
        'buyer'       => 'Kelmi',
        'sale_date'   => '2026-09-25',
        'sale_price'  => 450000000,
        'unit_code'   => 'DT 30',
        'description' => 'Dump Truck HINO RANGER-FM 260 JD (BG 8367 IJ)'
    ],
    [
        'asset_id'    => 'DT-00023-D',
        'plate'       => 'D 9692 XV / DD 8273 S',
        'buyer'       => 'Kelmi',
        'sale_date'   => '2025-08-29',
        'sale_price'  => 250000000,
        'unit_code'   => 'DT 24',
        'description' => 'Dump Truck HINO RANGER-FM 260 JD (DD 8273 SU / D 9692 XV)'
    ],
    [
        'asset_id'    => 'LV-00065',
        'plate'       => 'B 1167 ZKS',
        'buyer'       => 'RAYMOND',
        'sale_date'   => '2026-09-21',
        'sale_price'  => 0,
        'unit_code'   => 'INNOVA',
        'description' => 'Toyota Innova 2.4 G AT DSL (B 1167 ZKS)'
    ]
];

$stmtAssetUpdate = $db->prepare("
    UPDATE assets 
    SET status = 'INACTIVE', 
        is_active = 0,
        raw_location_notes = CASE 
            WHEN raw_location_notes IS NULL OR raw_location_notes = '' THEN :note1
            WHEN raw_location_notes LIKE :check_note THEN raw_location_notes
            ELSE CONCAT(raw_location_notes, ' | ', :note2)
        END
    WHERE asset_id = :asset_id
");

$stmtArchive = $db->prepare("
    INSERT INTO archived_items (
        item_type,
        item_id,
        archived_at,
        archived_by_user_id,
        archived_by_name,
        reason
    ) VALUES (
        'asset',
        :asset_id,
        NOW(),
        NULL,
        'MIGRATION_BATCH_12',
        :reason
    ) ON DUPLICATE KEY UPDATE
        archived_by_name = VALUES(archived_by_name),
        reason = VALUES(reason)
");

$db->beginTransaction();

try {
    $archivedCount = 0;

    foreach ($soldAssets as $item) {
        $assetId = $item['asset_id'];
        $buyer = $item['buyer'];
        $saleDate = $item['sale_date'];
        $priceFmt = $item['sale_price'] > 0 ? " Rp " . number_format($item['sale_price'], 0, ',', '.') : "";
        $note = "TERJUAL: {$item['unit_code']} ke {$buyer} per {$saleDate}{$priceFmt} (Ref: 12_ASSET_DIJUAL.md)";
        $reason = "Terjual ke {$buyer} tgl {$saleDate} (Batch 12 Rekonsiliasi Aset Dijual)";

        // 1. Update asset
        $stmtAssetUpdate->execute([
            ':asset_id'   => $assetId,
            ':note1'      => $note,
            ':note2'      => $note,
            ':check_note' => '%TERJUAL%'
        ]);

        // 2. Insert / Update into archived_items
        $stmtArchive->execute([
            ':asset_id' => $assetId,
            ':reason'   => $reason
        ]);

        $archivedCount++;
        echo "  [ARCHIVED] {$assetId} ({$item['plate']}) -> Buyer: {$buyer}, Tgl: {$saleDate}, Price: {$priceFmt}\n";
    }

    // 3. Register in app_data_migrations
    $stmtReg = $db->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_12_SOLD_ASSETS_RECONCILIATION', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtReg->execute();

    $db->commit();
    echo "[SUCCESS] Batch 12 completed: {$archivedCount} sold units archived safely without breaking relational integrity.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "[ERROR] Failed executing Batch 12: " . $e->getMessage() . "\n";
    exit(1);
}
