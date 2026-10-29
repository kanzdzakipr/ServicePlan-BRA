<?php
/**
 * scripts/migrate_batch14_alias_consolidation_placeholder_cleanup.php
 *
 * Batch 14 Migration:
 * - Archives dummy placeholder units (RM-00001..3, DT-04060) with Zero Hard-Delete.
 * - Consolidates unit aliases, miscategorized assets, and plate mutasi:
 *     1. BRR-PF-4103 -> HAMM Vibro Compactor HC208D (PF10 / PF-41003, Loewinsyah, Simpang Heli)
 *     2. DT-00024-D -> Trado Hino Ranger FM 260 JD (SL 04 / SL-04, SN: DD8319SU, Pekanbaru Standby)
 *     3. DT-04063   -> Lowboy Diamond LB02 / LB-00001 (SN: AC74G18040636, Yard Duri Standby)
 *     4. DT-00179   -> Dump Truck Hino FM280JD (BM 9821 QO ex BM 9821 QD, DT 119 / BRA-DT-1460, Harapan Baru)
 *     5. FBT-31003  -> Flat Deck Fighter FN62FL (BM 8450 NU ex B 9733 XPZ, FD-01 / BRTW-FB-31003, Matting Board)
 *     6. TMC-31003  -> TMC Fighter FN62FL (BM 8449 NU, TMC02 / BRTW-TMC-31003, Matting Board)
 *     7. TMC-31004  -> TMC Fighter FN62FL (BM 8452 NU ex B 9430 XFY, TMC03 / BRTW-TMC-31004, Anada Riza)
 * - Records audit entries in `archived_items`.
 * - Records migration in `app_data_migrations` under `BATCH_14_ALIAS_CONSOLIDATION_PLACEHOLDER_CLEANUP`.
 */

declare(strict_types=1);

define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Starting Batch 14 Migration: Alias Consolidation & Placeholder Cleanup ===\n";

// 1. Check if already applied
$checkMig = $db->prepare("SELECT migration_key FROM app_data_migrations WHERE migration_key = ?");
$checkMig->execute(['BATCH_14_ALIAS_CONSOLIDATION_PLACEHOLDER_CLEANUP']);
if ($checkMig->fetch()) {
    echo "[INFO] Migration BATCH_14_ALIAS_CONSOLIDATION_PLACEHOLDER_CLEANUP has already been applied. Re-verifying...\n";
}

$db->beginTransaction();

try {
    // ---------------------------------------------------------
    // PART 1: ARCHIVE DUMMY / OBSOLETE PLACEHOLDERS
    // ---------------------------------------------------------
    $placeholdersToArchive = [
        'RM-00001' => 'Placeholder Dummy Reclaimer Spreader (Tanpa SN / Off-Hire)',
        'RM-00002' => 'Placeholder Dummy Reclaimer Spreader (Tanpa SN / Off-Hire)',
        'RM-00003' => 'Placeholder Dummy Reclaimer Spreader (Tanpa SN / Off-Hire)',
        'DT-04060' => 'Placeholder Dump Truck Tidak Teridentifikasi di Rilis Okt 2026'
    ];

    $stmtAssetArchive = $db->prepare("
        UPDATE assets 
        SET status = 'INACTIVE', 
            is_active = 0,
            operational_position = 'ARCHIVED / PLACEHOLDER',
            raw_location_notes = CASE 
                WHEN raw_location_notes IS NULL OR raw_location_notes = '' THEN :note1
                WHEN raw_location_notes LIKE :check_note THEN raw_location_notes
                ELSE CONCAT(raw_location_notes, ' | ', :note2)
            END
        WHERE asset_id = :asset_id
    ");

    $stmtArchivedItems = $db->prepare("
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
            'MIGRATION_BATCH_14',
            :reason
        ) ON DUPLICATE KEY UPDATE
            archived_by_name = VALUES(archived_by_name),
            reason = VALUES(reason)
    ");

    foreach ($placeholdersToArchive as $assetId => $desc) {
        $note = "PEMBERSIHAN PLACEHOLDER: {$desc} (Batch 14)";
        $reason = "Pembersihan Dummy/Placeholder: {$desc}";

        $stmtAssetArchive->execute([
            ':asset_id'   => $assetId,
            ':note1'      => $note,
            ':note2'      => $note,
            ':check_note' => '%PEMBERSIHAN PLACEHOLDER%'
        ]);

        $stmtArchivedItems->execute([
            ':asset_id' => $assetId,
            ':reason'   => $reason
        ]);

        echo "  [ARCHIVED PLACEHOLDER] {$assetId} -> {$desc}\n";
    }

    // ---------------------------------------------------------
    // PART 2: CONSOLIDATE UNIT ALIASES & RECTIFY SPECIFICATIONS
    // ---------------------------------------------------------

    // 2.1 BRR-PF-4103 -> HAMM Vibro Compactor HC208D (PF10 / PF-41003)
    $db->prepare("
        UPDATE assets 
        SET hull_number = 'PF-41003',
            asset_code = 'PF-41003',
            alias_name = 'PF10 / BRR-PF-4103',
            category = 'Vibro Compactor',
            make_model = 'HAMM Vibro Pad Foot Compactor HC208D',
            capacity = '20 TON',
            current_operator_name = 'LOEWINSYAH',
            project_name = 'RWI',
            operational_position = 'SIMPANG HELI',
            status = 'OPERATING',
            is_active = 1,
            raw_location_notes = 'RWI - SIMPANG HELI (SSOT Line 35)'
        WHERE asset_id = 'BRR-PF-4103'
    ")->execute();
    echo "  [CONSOLIDATED] BRR-PF-4103 -> PF-41003 / PF10 (LOEWINSYAH, SIMPANG HELI)\n";

    // 2.2 DT-00024-D -> Trado Hino Ranger FM 260 JD (SL 04 / SL-04)
    $db->prepare("
        UPDATE assets 
        SET category = 'Trado',
            make_model = 'TRADO HINO RANGER-FM 260 JD',
            serial_number = 'DD8319SU',
            chassis_number = 'MJEFM8JNKCJM34628',
            hull_number = 'SL-04',
            alias_name = 'SL 04 / DT 24 D',
            capacity = '256 HP / 260 PS',
            project_name = 'PEKANBARU',
            operational_position = 'PEKANBARU',
            status = 'STANDBY',
            is_active = 1,
            raw_location_notes = 'PEKANBARU - STANDBY (SSOT Line 225)'
        WHERE asset_id = 'DT-00024-D'
    ")->execute();
    echo "  [CONSOLIDATED] DT-00024-D -> Trado SL-04 / SL 04 (DD8319SU, PEKANBARU STANDBY)\n";

    // 2.3 DT-04063 -> Lowboy Diamond LB02 / LB-00001
    $db->prepare("
        UPDATE assets 
        SET category = 'Trado',
            make_model = 'LOWBOY DIAMOND',
            serial_number = 'AC74G18040636',
            hull_number = 'LB-00001',
            alias_name = 'LB02 / DT-04063',
            capacity = '36 TON',
            project_name = 'WUR EW',
            operational_position = 'YARD DURI',
            status = 'STANDBY',
            is_active = 1,
            raw_location_notes = 'WUR EW - YARD DURI (SSOT Line 230)'
        WHERE asset_id = 'DT-04063'
    ")->execute();
    echo "  [CONSOLIDATED] DT-04063 -> Lowboy Diamond LB-00001 / LB02 (AC74G18040636, YARD DURI)\n";

    // 2.4 DT-00179 -> Correct Plate Mutasi BM 9821 QO ex BM 9821 QD (DT 119 / BRA-DT-1460)
    $db->prepare("
        UPDATE assets 
        SET license_plate = 'BM 9821 QO',
            previous_license_plate = 'BM 9821 QD',
            hull_number = 'BRA-DT-1460',
            alias_name = 'DT 119',
            project_name = 'WUR EW',
            operational_position = 'B.PIT HARAPAN BARU',
            status = 'OPERATING',
            is_active = 1,
            raw_location_notes = 'WUR EW - B.PIT HARAPAN BARU (SSOT Line 193)'
        WHERE asset_id = 'DT-00179'
    ")->execute();
    echo "  [CONSOLIDATED] DT-00179 -> BM 9821 QO (BRA-DT-1460 / DT 119, HARAPAN BARU)\n";

    // 2.5 FBT-31003 -> Correct Plate Mutasi BM 8450 NU ex temporary B 9733 XPZ (FD-01 / BRTW-FB-31003)
    $db->prepare("
        UPDATE assets 
        SET license_plate = 'BM 8450 NU',
            previous_license_plate = 'B 9733 XPZ',
            hull_number = 'BRTW-FB-31003',
            alias_name = 'FD-01 / FBT-31003',
            make_model = 'FLAT DECK FIGHTER FN62FL',
            category = 'Trado',
            capacity = '26.000 kg',
            project_name = 'MATTING BOARD',
            operational_position = 'YARD DURI',
            status = 'OPERATING',
            is_active = 1,
            raw_location_notes = 'MATTING BOARD - YARD DURI (SSOT Line 245)'
        WHERE asset_id = 'FBT-31003'
    ")->execute();
    echo "  [CONSOLIDATED] FBT-31003 -> BM 8450 NU ex B 9733 XPZ (BRTW-FB-31003 / FD-01, MATTING BOARD)\n";

    // 2.6 TMC-31003 -> TMC Fighter FN62FL (BM 8449 NU, TMC02 / BRTW-TMC-31003)
    $db->prepare("
        UPDATE assets 
        SET license_plate = 'BM 8449 NU',
            hull_number = 'BRTW-TMC-31003',
            alias_name = 'TMC02 / TMC-31003',
            make_model = 'TMC FIGHTER FN62FL / TADANO TM-ZT1005H',
            category = 'Other',
            capacity = '10 TON',
            project_name = 'MATTING BOARD',
            operational_position = 'YARD DURI',
            status = 'OPERATING',
            is_active = 1,
            raw_location_notes = 'MATTING BOARD - YARD DURI (SSOT Line 253)'
        WHERE asset_id = 'TMC-31003'
    ")->execute();
    echo "  [CONSOLIDATED] TMC-31003 -> BM 8449 NU (BRTW-TMC-31003 / TMC02, MATTING BOARD)\n";

    // 2.7 TMC-31004 -> TMC Fighter FN62FL (BM 8452 NU ex B 9430 XFY, TMC03 / BRTW-TMC-31004, Anada Riza)
    $db->prepare("
        UPDATE assets 
        SET license_plate = 'BM 8452 NU',
            previous_license_plate = 'B 9430 XFY',
            hull_number = 'BRTW-TMC-31004',
            alias_name = 'TMC03 / TMC-31004',
            make_model = 'TMC FIGHTER FN62FL / TADANO TM-ZT1005H',
            category = 'Other',
            capacity = '10 TON',
            current_operator_name = 'ANADA RIZA',
            project_name = 'MATTING BOARD',
            operational_position = 'YARD DURI',
            status = 'OPERATING',
            is_active = 1,
            raw_location_notes = 'MATTING BOARD - YARD DURI (SSOT Line 254)'
        WHERE asset_id = 'TMC-31004'
    ")->execute();
    echo "  [CONSOLIDATED] TMC-31004 -> BM 8452 NU ex B 9430 XFY (BRTW-TMC-31004 / TMC03, ANADA RIZA)\n";

    // ---------------------------------------------------------
    // PART 3: REGISTER MIGRATION
    // ---------------------------------------------------------
    $stmtReg = $db->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_14_ALIAS_CONSOLIDATION_PLACEHOLDER_CLEANUP', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtReg->execute();

    $db->commit();
    echo "[SUCCESS] Batch 14 completed: 4 placeholders archived and 7 asset aliases consolidated seamlessly.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "[ERROR] Failed executing Batch 14: " . $e->getMessage() . "\n";
    exit(1);
}

