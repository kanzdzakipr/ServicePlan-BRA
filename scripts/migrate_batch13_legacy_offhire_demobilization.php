<?php
/**
 * scripts/migrate_batch13_legacy_offhire_demobilization.php
 *
 * Batch 13 Migration:
 * - Demobilizes and archives 52 legacy assets from January 2026 contracts (Dump Trucks, Light Vehicles,
 *   and legacy heavy equipment) that are no longer active in the October 2026 fleet release.
 * - Enforces Zero Hard-Delete to preserve foreign keys for 14 historical Work Orders.
 * - Sets status = 'INACTIVE', is_active = 0, operational_position = 'DEMOBILIZED / OFF-HIRE'.
 * - Records audit entries in `archived_items`.
 * - Records migration in `app_data_migrations` under `BATCH_13_LEGACY_OFFHIRE_DEMOBILIZATION`.
 */

declare(strict_types=1);

define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Starting Batch 13 Migration: Legacy Fleet Demobilization & Archival ===\n";

// 1. Check if already applied
$checkMig = $db->prepare("SELECT migration_key FROM app_data_migrations WHERE migration_key = ?");
$checkMig->execute(['BATCH_13_LEGACY_OFFHIRE_DEMOBILIZATION']);
if ($checkMig->fetch()) {
    echo "[INFO] Migration BATCH_13_LEGACY_OFFHIRE_DEMOBILIZATION has already been applied. Re-verifying archival state...\n";
}

// 2. Define list of 52 legacy demobilized assets
$demobilizedAssets = [
    // Dump Trucks (36 units)
    'DT-00119' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00120' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00121' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00122' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00123' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00124' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00129' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00130' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00131' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00132' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00133' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00134' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00135' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00136' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00137' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00138' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00139' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00140' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00141' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00142' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00143' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00144' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00145' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00146' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00147' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00153' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00154' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00155' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00156' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00157' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00158' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00159' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-00186' => 'Dump Truck Hino Ranger FM280JD (BM 9903 JO) off-hire ex-Jan 2026',
    'DT-00192' => 'Placeholder Dump Truck ex-PHR Contract Jan 2026',
    'DT-04064' => 'Legacy Dump Truck placeholder (FM 517 HS) off-hire',
    'DT-04065' => 'Legacy Dump Truck placeholder off-hire',

    // Light Vehicles (3 units)
    'LV-00032' => 'Dummy Light Vehicle placeholder ex-Jan 2026',
    'LV-00045' => 'Dummy Light Vehicle placeholder ex-Jan 2026',
    'LV-00046' => 'Dummy Light Vehicle placeholder ex-Jan 2026',

    // Legacy Heavy Equipment & Support (13 units)
    'DZ-00007' => 'Bulldozer Caterpillar D6G / Shantui SD16 off-hire ex-Jan 2026',
    'DZ-00008' => 'Bulldozer Caterpillar D6G / Shantui SD16 off-hire ex-Jan 2026',
    'DZ-00009' => 'Bulldozer Caterpillar D6G / Shantui SD16 off-hire ex-Jan 2026',
    'EXC-00016' => 'Excavator Komatsu PC200-10M0 off-hire ex-Jan 2026',
    'EXC-00020' => 'Excavator Komatsu off-hire ex-Jan 2026',
    'EXC-06001' => 'Excavator Komatsu PC200-10M0 off-hire ex-Jan 2026',
    'EXC-31001' => 'Excavator off-hire ex-Jan 2026',
    'FBT-00005' => 'Trado Heavy Hauler Mitsubishi FV 419 P off-hire ex-Jan 2026',
    'FBT-00006' => 'Trado Heavy Hauler Mitsubishi FV 419 P off-hire ex-Jan 2026',
    'FBT-00007' => 'Trado Heavy Hauler Mitsubishi FV 419 P off-hire ex-Jan 2026',
    'SUP-00001' => 'Support Unit off-hire ex-Jan 2026',
    'SUP-31001' => 'Support Truck (B 9435 XFY) off-hire ex-Jan 2026',
    'TMC-00005' => 'Truck Mounted Crane off-hire ex-Jan 2026'
];

$stmtAssetUpdate = $db->prepare("
    UPDATE assets 
    SET status = 'INACTIVE', 
        is_active = 0,
        operational_position = 'DEMOBILIZED / OFF-HIRE',
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
        'MIGRATION_BATCH_13',
        :reason
    ) ON DUPLICATE KEY UPDATE
        archived_by_name = VALUES(archived_by_name),
        reason = VALUES(reason)
");

$db->beginTransaction();

try {
    $archivedCount = 0;

    foreach ($demobilizedAssets as $assetId => $desc) {
        $note = "DEMOBILISASI: {$desc} (Batch 13 Demobilisasi Kontrak Lampau)";
        $reason = "Demobilisasi / Selesai Kontrak PHR per rilis Oktober 2026: {$desc}";

        // 1. Update asset
        $stmtAssetUpdate->execute([
            ':asset_id'   => $assetId,
            ':note1'      => $note,
            ':note2'      => $note,
            ':check_note' => '%DEMOBILISASI%'
        ]);

        // 2. Insert into archived_items
        $stmtArchive->execute([
            ':asset_id' => $assetId,
            ':reason'   => $reason
        ]);

        $archivedCount++;
        echo "  [DEMOBILIZED] {$assetId} -> {$desc}\n";
    }

    // 3. Register in app_data_migrations
    $stmtReg = $db->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_13_LEGACY_OFFHIRE_DEMOBILIZATION', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtReg->execute();

    $db->commit();
    echo "[SUCCESS] Batch 13 completed: {$archivedCount} legacy units safely demobilized and archived.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "[ERROR] Failed executing Batch 13: " . $e->getMessage() . "\n";
    exit(1);
}

