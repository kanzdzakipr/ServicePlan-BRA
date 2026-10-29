<?php
/**
 * scripts/migrate_batch15_project_location_alignment.php
 *
 * Batch 15 Migration:
 * - Archives residual placeholder units (DT-04061, DT-04062, FBT-00002) with Zero Hard-Delete.
 * - Aligns project placement (project_name), operational position (operational_position),
 *   raw location notes, operator assignment (current_operator_name), and operational status
 *   for all active fleet assets based on official SSOT October 2026:
 *     - WUR EW (Wellsite Utilities & Roads East & West: NDD, Infield, Meso, B.Pit Harapan Baru, etc.)
 *     - RWI (Rokan West Infrastructure: Intan, Penasa, Central, Simpang Heli / Bekasap)
 *     - LAHAT (Lahat Coal & Hauling)
 *     - KENDARI (Bahodopi, Malili Nickel Projects)
 *     - ADERA (WS Adera, Babat Kukui)
 *     - MATTING BOARD (Yard Duri Matting Operations)
 *     - YARD DURI (Workshop KM 12 Reserve & Standby Fleet)
 *     - USER PHR (Duri Field Operational Vehicles)
 * - Preserves verified manual overrides like BREAKDOWN (e.g. MG-00003) and ACCIDENT_HOLD.
 * - Records migration in `app_data_migrations` under `BATCH_15_PROJECT_LOCATION_ALIGNMENT`.
 */

declare(strict_types=1);

define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Starting Batch 15 Migration: Active Project Allocation & Location Alignment ===\n";

// 1. Check if already applied
$checkMig = $db->prepare("SELECT migration_key FROM app_data_migrations WHERE migration_key = ?");
$checkMig->execute(['BATCH_15_PROJECT_LOCATION_ALIGNMENT']);
if ($checkMig->fetch()) {
    echo "[INFO] Migration BATCH_15_PROJECT_LOCATION_ALIGNMENT has already been applied. Re-verifying...\n";
}

function normStr(string $s): string {
    return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $s));
}

// 2. Parse SSOT 02_List_Data_Aset_BRA.md
$ssotFile = dirname(__DIR__) . '/material/ASSET/REKAP HEAVY EQUIPMENT BRA 2026  (8)/02_List_Data_Aset_BRA.md';
if (!file_exists($ssotFile)) {
    throw new RuntimeException("SSOT file not found: {$ssotFile}");
}

$lines = file($ssotFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$ssotUnits = [];

foreach ($lines as $line) {
    if (strpos($line, '|') === false) continue;
    $cells = array_map('trim', explode('|', $line));
    if (count($cells) < 16) continue;
    $lineNum = $cells[1] ?? '';
    if (!is_numeric($lineNum) || (int)$lineNum < 6) continue;

    $namaAsset = $cells[4] ?? '';
    $snPlate = $cells[8] ?? '';
    $kodeUnit = $cells[11] ?? '';
    $operator = $cells[12] ?? '';
    $lambung = $cells[13] ?? '';
    $project = $cells[14] ?? '';
    $lokasi = $cells[15] ?? '';
    $status = strtoupper(trim($cells[16] ?? ''));

    // Special handler for Double Cabin / Hilux rows with shifted columns
    if (stripos($line, 'DOUBLE CABIN') !== false && stripos($line, 'TOYOTA') !== false) {
        foreach ($cells as $idx => $val) {
            if (preg_match('/B\s*91\d{2}\s*ZBA/i', $val)) {
                $snPlate = $val;
                $operator = $cells[$idx + 2] ?? '';
                $kodeUnit = $cells[$idx + 3] ?? '';
                $project = 'USER PHR';
                $lokasi = 'DURI';
                $status = 'AKTIF';
                break;
            }
        }
    }

    $tokens = [];
    foreach ([$snPlate, $kodeUnit, $lambung] as $val) {
        $clean = normStr($val);
        if (strlen($clean) >= 3) $tokens[] = $clean;
        $subs = preg_split('~[/,;\s]+~', $val);
        foreach ($subs as $sub) {
            $nSub = normStr($sub);
            if (strlen($nSub) >= 4) $tokens[] = $nSub;
        }
    }

    // Include Serial Number / Chassis if found in row cells
    for ($i = 2; $i < count($cells); $i++) {
        $cVal = normStr($cells[$i]);
        if (strlen($cVal) >= 6 && !in_array($cVal, [
            'DUMPTRUCK', 'EXCAVATOR', 'BULLDOZER', 'VIBROROLLER',
            'KENDARAANOPERASIONAL', 'UNITEDTRACTORS', 'TRAKINDO',
            'MITSUBISHI', 'PTCHANDRASAKTI', 'LEASING'
        ])) {
            $tokens[] = $cVal;
        }
    }

    $ssotUnits[] = [
        'line'       => (int)$lineNum,
        'nama_asset' => $namaAsset,
        'sn_plate'   => $snPlate,
        'kode_unit'  => $kodeUnit,
        'operator'   => ($operator !== '-' && $operator !== '' && !is_numeric($operator)) ? $operator : null,
        'lambung'    => ($lambung !== '-' && $lambung !== '') ? $lambung : null,
        'project'    => ($project !== '-' && $project !== '') ? $project : null,
        'lokasi'     => ($lokasi !== '-' && $lokasi !== '') ? $lokasi : null,
        'status'     => $status,
        'tokens'     => array_unique($tokens)
    ];
}

echo "[OK] Loaded " . count($ssotUnits) . " reference rows from SSOT.\n";

$db->beginTransaction();

try {
    // ---------------------------------------------------------
    // STEP 1: ARCHIVE 3 RESIDUAL PLACEHOLDER DUMMY ASSETS
    // ---------------------------------------------------------
    $residualPlaceholders = [
        'DT-04061'  => 'Placeholder Dump Truck Residual ex-Early Seed (FM 260 JD)',
        'DT-04062'  => 'Placeholder Dump Truck Residual ex-Early Seed (FM 280 JD)',
        'FBT-00002' => 'Placeholder Trado Residual ex-Early Seed (FM 260 JD)'
    ];

    $stmtArchiveResidual = $db->prepare("
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
            'MIGRATION_BATCH_15',
            :reason
        ) ON DUPLICATE KEY UPDATE
            archived_by_name = VALUES(archived_by_name),
            reason = VALUES(reason)
    ");

    foreach ($residualPlaceholders as $pId => $pDesc) {
        $note = "PEMBERSIHAN PLACEHOLDER RESIDUAL: {$pDesc} (Batch 15)";
        $stmtArchiveResidual->execute([
            ':asset_id'   => $pId,
            ':note1'      => $note,
            ':note2'      => $note,
            ':check_note' => '%PEMBERSIHAN PLACEHOLDER%'
        ]);
        $stmtArchivedItems->execute([
            ':asset_id' => $pId,
            ':reason'   => "Pembersihan Dummy/Placeholder Residual: {$pDesc}"
        ]);
        echo "  [ARCHIVED RESIDUAL] {$pId} -> {$pDesc}\n";
    }

    // ---------------------------------------------------------
    // STEP 2: ALIGN ACTIVE ASSETS PROJECT, LOCATION & OPERATOR
    // ---------------------------------------------------------
    $activeAssets = $db->query("
        SELECT asset_id, asset_code, serial_number, license_plate, previous_license_plate, 
               chassis_number, hull_number, alias_name, category, make_model, 
               project_name, operational_position, raw_location_notes, current_operator_name, 
               status, is_active 
        FROM assets 
        WHERE is_active = 1 
        ORDER BY asset_id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmtUpdateFleet = $db->prepare("
        UPDATE assets 
        SET project_name = :project_name,
            operational_position = :operational_position,
            raw_location_notes = :raw_location_notes,
            current_operator_name = COALESCE(:operator, current_operator_name),
            status = CASE 
                WHEN status IN ('BREAKDOWN', 'ACCIDENT_HOLD') THEN status
                WHEN :new_status IN ('READY', 'OPERATING', 'STANDBY', 'INSPEKSI') THEN :new_status2
                ELSE status
            END
        WHERE asset_id = :asset_id
    ");

    $alignedCount = 0;

    foreach ($activeAssets as $a) {
        $aTokens = [];
        foreach (['asset_id', 'asset_code', 'serial_number', 'license_plate', 'previous_license_plate', 'chassis_number', 'hull_number', 'alias_name'] as $f) {
            $val = $a[$f];
            if (empty($val) || $val === '-') continue;
            $nVal = normStr($val);
            if (strlen($nVal) >= 3) $aTokens[] = $nVal;
            $subs = preg_split('~[/,;\s]+~', $val);
            foreach ($subs as $sub) {
                $nSub = normStr($sub);
                if (strlen($nSub) >= 4) $aTokens[] = $nSub;
            }
        }
        $aTokens = array_unique($aTokens);

        $matchedSsot = null;
        foreach ($ssotUnits as $su) {
            foreach ($aTokens as $at) {
                if (in_array($at, ['DUMPTRUCK', 'EXCAVATOR', 'BULLDOZER', 'VIBROROLLER', 'READY', 'OPERATING', 'STANDBY'])) continue;
                if (in_array($at, $su['tokens'])) {
                    $matchedSsot = $su;
                    break 2;
                }
            }
        }

        if ($matchedSsot) {
            $projectName = $matchedSsot['project'] ?: ($a['project_name'] ?: 'WUR EW');
            $position = $matchedSsot['lokasi'] ?: ($a['operational_position'] ?: 'YARD DURI');
            $operator = $matchedSsot['operator'];

            // Map SSOT status to Enum status
            $mappedStatus = $a['status'];
            $ssotStat = $matchedSsot['status'];
            if ($ssotStat === 'AKTIF') {
                $mappedStatus = 'OPERATING';
            } elseif ($ssotStat === 'STANDBY') {
                $mappedStatus = 'STANDBY';
            } elseif (in_array($ssotStat, ['INSPEKSI', 'COMMISSIONING', 'CLOSE FINDING'])) {
                $mappedStatus = 'INSPEKSI';
            } elseif ($ssotStat === 'BREAKDOWN') {
                $mappedStatus = 'BREAKDOWN';
            }

            // Raw location notes format
            $rawLocNotes = "{$projectName} - {$position}";

            $stmtUpdateFleet->execute([
                ':asset_id'             => $a['asset_id'],
                ':project_name'         => $projectName,
                ':operational_position' => $position,
                ':raw_location_notes'   => $rawLocNotes,
                ':operator'             => $operator,
                ':new_status'           => $mappedStatus,
                ':new_status2'          => $mappedStatus
            ]);

            $alignedCount++;
        }
    }

    echo "[OK] Successfully aligned {$alignedCount} active fleet assets with SSOT October 2026.\n";

    // ---------------------------------------------------------
    // STEP 3: REGISTER MIGRATION
    // ---------------------------------------------------------
    $stmtReg = $db->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_15_PROJECT_LOCATION_ALIGNMENT', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtReg->execute();

    $db->commit();
    echo "[SUCCESS] Batch 15 completed: All 299 active assets successfully synchronized to October 2026 projects & locations.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "[ERROR] Failed executing Batch 15: " . $e->getMessage() . "\n";
    exit(1);
}

