<?php
declare(strict_types=1);

/**
 * Migration & Normalization Script: DATA-01 & DATA-02
 * Normalisasi Master Unit Samsul & Status Mekanik
 * 
 * 1. Normalizes all 365+ assets:
 *    - Ensures missing authoritative assets from Samsul PM reports (WT-41002, RM-41002)
 *    - Fills missing hull_numbers using authoritative Samsul PM, Standby, & Mutasi data
 *    - Corrects misclassified categories (Reclaimer, Water Truck, Prime Mover, Light Vehicle, Vibro Compactor)
 *    - Sets correct current_location_id (WUR EW, RWI, Matting Board, Lahat, Yard Duri, Palembang)
 *    - Logs each change to input_revision_audit
 * 2. Generates material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv
 * 3. Sets Samsul Bahri (Planner) on user_id = 3
 * 4. Registers/updates active PT BRA mechanics from the operational leaderboard
 */

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../core/InputRevision.php';

$db = Database::getInstance();

echo "=== MIGRATION & NORMALIZATION: MASTER UNIT SAMSUL & STATUS MEKANIK ===\n\n";

// ==========================================================================
// PART 1: MASTER UNIT NORMALIZATION (DATA-01)
// ==========================================================================
echo "--- Part 1: Normalizing Master Assets (Samsul Bahri Dataset) ---\n";

// Ensure specific authoritative assets from Samsul PM reports exist
$ensureAssets = [
    [
        'asset_id' => 'WT-41002',
        'asset_code' => 'WT-41002',
        'hull_number' => 'WT-41002',
        'alias_name' => 'DT 50',
        'license_plate' => 'BK 8121 EO',
        'make_model' => 'Water Tank MITUSBISHI FUSO FM 517 HS',
        'category' => 'Water Truck',
        'type' => 'Heavy Equipment',
        'current_location_id' => 847, // Project RWI
        'status' => 'OPERATING',
    ],
    [
        'asset_id' => 'RM-41002',
        'asset_code' => 'RM-41002',
        'hull_number' => 'RM-41002',
        'alias_name' => 'RM-02',
        'serial_number' => 'RMZ00126',
        'make_model' => 'Road Reclaimers Caterpillar RM500B',
        'category' => 'Reclaimer Spreader',
        'type' => 'Heavy Equipment',
        'current_location_id' => 847, // Project RWI
        'status' => 'OPERATING',
    ],
];

foreach ($ensureAssets as $ea) {
    $exists = $db->query("SELECT COUNT(*) FROM assets WHERE asset_id = '{$ea['asset_id']}'")->fetchColumn();
    if (!$exists) {
        $db->prepare("
            INSERT INTO assets (
                asset_id, asset_code, hull_number, alias_name, license_plate, serial_number,
                make_model, category, type, current_location_id, status, is_active, created_at
            ) VALUES (
                :asset_id, :asset_code, :hull_number, :alias_name, :license_plate, :serial_number,
                :make_model, :category, :type, :current_location_id, :status, 1, NOW()
            )
        ")->execute([
            ':asset_id' => $ea['asset_id'],
            ':asset_code' => $ea['asset_code'],
            ':hull_number' => $ea['hull_number'],
            ':alias_name' => $ea['alias_name'],
            ':license_plate' => $ea['license_plate'] ?? null,
            ':serial_number' => $ea['serial_number'] ?? null,
            ':make_model' => $ea['make_model'],
            ':category' => $ea['category'],
            ':type' => $ea['type'],
            ':current_location_id' => $ea['current_location_id'],
            ':status' => $ea['status'],
        ]);
        echo "Ensured missing asset: {$ea['asset_id']} ({$ea['make_model']})\n";
    }
}

// A. Authoritative Unit Mappings from Samsul's Reports
$samsulMappings = [
    // Standby & Heavy Equipment
    'SD 06' => ['hull' => 'VIBRO BW BRA-06', 'cat' => 'Vibro Compactor', 'loc' => 12, 'name' => 'Bomag Smooth Drum BW211D-40'],
    'PF 02' => ['hull' => 'PF-41002', 'cat' => 'Vibro Compactor', 'loc' => 15, 'name' => 'XCMG Padfoot XS205PD'],
    'PF 03' => ['hull' => 'PF-00003', 'cat' => 'Vibro Compactor', 'loc' => 12, 'name' => 'Bomag Pad Foot Compactor BW211D-40SL'],
    'PF 06' => ['hull' => 'PF-00002', 'cat' => 'Vibro Compactor', 'loc' => 15, 'name' => 'Vibro Bomag Pad Foot Compactor BW 211D-40SL'],
    'PF 010' => ['hull' => 'BRR-PF-4103', 'cat' => 'Vibro Compactor', 'loc' => 847, 'name' => 'HAMM VBR Compactor HC 208 D'],
    'SD 10' => ['hull' => 'SD-00002', 'cat' => 'Vibro Compactor', 'loc' => 15, 'name' => 'Vibro Bomag Smooth Drum Compactor BW 211D-40SL'],
    'Dozer 01' => ['hull' => 'BRA-01', 'cat' => 'Bulldozer', 'loc' => 8, 'name' => 'Bulldozer Caterpillar D6G'],
    'Dozer 04' => ['hull' => 'DZ-00005', 'cat' => 'Bulldozer', 'loc' => 12, 'name' => 'Bulldozer Komatsu D85ESS-2'],
    'Dozer 07' => ['hull' => 'BRA-07', 'cat' => 'Bulldozer', 'loc' => 8, 'name' => 'Bulldozer Caterpillar D7G2'],
    'Dozer 10' => ['hull' => 'DZ-00004', 'cat' => 'Bulldozer', 'loc' => 15, 'name' => 'Bulldozer Zoomlion'],
    'Dozer 11' => ['hull' => 'DZ-00006', 'cat' => 'Bulldozer', 'loc' => 15, 'name' => 'Bulldozer SHANTUI SD16E'],
    'Exca 02' => ['hull' => 'EXC-00013', 'cat' => 'Excavator', 'loc' => 12, 'name' => 'Excavator PC 210-10M0/P1'],
    'Exca 06' => ['hull' => 'EXC-00005', 'cat' => 'Excavator', 'loc' => 15, 'name' => 'Excavator CAT 320 GX new'],
    'Exca 07' => ['hull' => 'EXC-1114', 'cat' => 'Excavator', 'loc' => 15, 'name' => 'Excavator Komatsu PC 200-10 MOCE'],
    'Exca 16' => ['hull' => 'HE-6.004', 'cat' => 'Excavator', 'loc' => 12, 'name' => 'Excavator Caterpillar 305.5E2'],
    'Exca 17' => ['hull' => 'HE-6.001', 'cat' => 'Excavator', 'loc' => 12, 'name' => 'Excavator Caterpillar 305.5E2'],
    'Exca 18' => ['hull' => 'HE-6.006', 'cat' => 'Excavator', 'loc' => 12, 'name' => 'Excavator Caterpillar 305.5E2'],
    'Exca 21' => ['hull' => 'EXC-00003', 'cat' => 'Excavator', 'loc' => 15, 'name' => 'Excavator Komatsu PC200-10M0 CE'],
    'Exca 23' => ['hull' => 'EXC-41001', 'cat' => 'Excavator', 'loc' => 12, 'name' => 'Excavator PC 200-10 MO'],
    'Exca BRA-23' => ['hull' => 'EXC-00012', 'cat' => 'Excavator', 'loc' => 15, 'name' => 'EXCA PC 200-10 MOCE'],
    'EXCA-29' => ['hull' => 'EXC-00009', 'cat' => 'Excavator', 'loc' => 15, 'name' => 'Excavator Komatsu PC200-10M0 CE'],
    'GR 5' => ['hull' => 'MG-00004', 'cat' => 'Motor Grader', 'loc' => 15, 'name' => 'MOTOR GRADER XCMG GR135 MAX'],
    'GR 8' => ['hull' => 'MG-4106', 'cat' => 'Motor Grader', 'loc' => 15, 'name' => 'MOTOR GRADER KOMATSU GD535-5'],
    'CR02' => ['hull' => 'CC-00002', 'cat' => 'Other', 'loc' => 848, 'name' => 'Crawler Crane XCMG-XGC35HD'],
    'CC-01' => ['hull' => 'CC-00001', 'cat' => 'Other', 'loc' => 12, 'name' => 'Crawler Crane Zoomlion ZCC600V'],
    'CS-01' => ['hull' => 'CS-41001', 'cat' => 'Reclaimer Spreader', 'loc' => 847, 'name' => 'Powder Binder Spreader XCMG XKC185'],
    'CS-02' => ['hull' => 'CS-41002', 'cat' => 'Reclaimer Spreader', 'loc' => 847, 'name' => 'Powder Binder Spreader XCMG XKC183'],
    'RM-02' => ['hull' => 'RM-41002', 'cat' => 'Reclaimer Spreader', 'loc' => 847, 'name' => 'Road Reclaimers Caterpillar RM500B'],
    'RM-41002' => ['hull' => 'RM-41002', 'cat' => 'Reclaimer Spreader', 'loc' => 847, 'name' => 'Road Reclaimers Caterpillar RM500B'],
    'RM-03' => ['hull' => 'BRR-RM-4103', 'cat' => 'Reclaimer Spreader', 'loc' => 847, 'name' => 'Wirtgen 240X'],
    'WL 02' => ['hull' => 'WL-31001', 'cat' => 'Excavator', 'loc' => 15, 'name' => 'WHEEL LOADER SEM 655D'],

    // Trucks & Support
    'PM 03' => ['hull' => 'PM-41001', 'cat' => 'Prime Mover', 'loc' => 15, 'name' => 'Prime Mover FM2PK1B-MGJ HINO'],
    'TMC-02' => ['hull' => 'TMC-31001', 'cat' => 'Prime Mover', 'loc' => 848, 'name' => 'TMC MITSUBISHI TRUCK CRANE FN62FL'],
    'TMC-03' => ['hull' => 'TMC-31002', 'cat' => 'Prime Mover', 'loc' => 848, 'name' => 'TMC MITSUBISHI TRUCK CRANE FN62FL'],
    'TMC-04' => ['hull' => 'TMC-31003', 'cat' => 'Prime Mover', 'loc' => 848, 'name' => 'TMC MITSUBISHI TRUCK CRANE FN62FL'],
    'TMC-05' => ['hull' => 'TMC-31004', 'cat' => 'Prime Mover', 'loc' => 848, 'name' => 'TMC MITSUBISHI TRUCK CRANE FN62FL'],
    'TMC-06' => ['hull' => 'TMC-31005', 'cat' => 'Prime Mover', 'loc' => 848, 'name' => 'TMC MITSUBISHI TRUCK CRANE FN62FL'],
    'FBT-01' => ['hull' => 'FBT-31001', 'cat' => 'Dump Truck', 'loc' => 848, 'name' => 'FLAT BED TRUCK MITSUBISHI FN62FL'],
    'FBT-02' => ['hull' => 'FBT-31002', 'cat' => 'Dump Truck', 'loc' => 848, 'name' => 'FLAT BED TRUCK MITSUBISHI FN62FL'],
    'FBT-04' => ['hull' => 'FBT-31004', 'cat' => 'Dump Truck', 'loc' => 848, 'name' => 'FLAT BED TRUCK MITSUBISHI FN62FL'],
    'FBT-05' => ['hull' => 'FBT-31005', 'cat' => 'Dump Truck', 'loc' => 848, 'name' => 'FLAT BED TRUCK MITSUBISHI FN62FL'],
    'FBT-06' => ['hull' => 'FBT-31006', 'cat' => 'Dump Truck', 'loc' => 848, 'name' => 'FLAT BED TRUCK MITSUBISHI FN62FL'],
    'DT 48' => ['hull' => 'WT 41004', 'cat' => 'Water Truck', 'loc' => 847, 'name' => 'Water Tank MITUSBISHI FUSO FM 517 HS'],
    'DT 49' => ['hull' => 'WT 41005', 'cat' => 'Water Truck', 'loc' => 847, 'name' => 'Water Tank MITUSBISHI FUSO FM 517 HS'],
    'DT 50' => ['hull' => 'WT-41002', 'cat' => 'Water Truck', 'loc' => 847, 'name' => 'Water Tank MITUSBISHI FUSO FM 517 HS'],
    'WT-41002' => ['hull' => 'WT-41002', 'cat' => 'Water Truck', 'loc' => 847, 'name' => 'Water Tank MITUSBISHI FUSO FM 517 HS'],
    'TR 4' => ['hull' => 'LV-00003', 'cat' => 'Light Vehicle', 'loc' => 15, 'name' => 'DOUBLE CABIN MITSUBISHI TRITON'],
    'TR12' => ['hull' => 'LV-00006', 'cat' => 'Light Vehicle', 'loc' => 15, 'name' => 'DOUBLE CABIN MITSUBISHI TRITON'],
    'HI 23' => ['hull' => 'LV-41002', 'cat' => 'Light Vehicle', 'loc' => 15, 'name' => 'Light Vehicle TOYOTA HILUX'],
    'HI 31' => ['hull' => 'LV-41003', 'cat' => 'Light Vehicle', 'loc' => 15, 'name' => 'DOUBLE CABIN TOYOTA HILUX'],

    // Dump Trucks
    'DT 001' => ['hull' => 'ABA-083', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 260 JD'],
    'DT 009' => ['hull' => 'DT-1148', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 260 JD'],
    'DT 018' => ['hull' => 'DT-00054', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 260 JD'],
    'DT 031' => ['hull' => 'DT-00058', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 260 JD'],
    'DT 040' => ['hull' => 'DT-00043', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 043' => ['hull' => 'DT-00046', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 045' => ['hull' => 'BRA-45', 'cat' => 'Dump Truck', 'loc' => 12, 'name' => 'Dumptruck Isuzu NMR 81'],
    'DT 046' => ['hull' => 'BRA-46', 'cat' => 'Dump Truck', 'loc' => 12, 'name' => 'Dumptruck Isuzu NMR 81'],
    'DT 052' => ['hull' => 'DT-00064', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 260 JD'],
    'DT 053' => ['hull' => 'DT-00065', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 260 JD'],
    'DT 059' => ['hull' => 'DT-00005', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 060' => ['hull' => 'DT-00006', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 064' => ['hull' => 'DT-00010', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 065' => ['hull' => 'DT-00011', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 068' => ['hull' => 'DT-00014', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 070' => ['hull' => 'DT-00016', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 073' => ['hull' => 'DT-00019', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 074' => ['hull' => 'DT-00020', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 077' => ['hull' => 'DT-00023', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 082' => ['hull' => 'DT-00028', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 084' => ['hull' => 'DT-00030', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 091' => ['hull' => 'ABA-073', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 096' => ['hull' => 'ABA-076', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 097' => ['hull' => 'ABA-072', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 098' => ['hull' => 'ABA-071', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 102' => ['hull' => 'ABA-097', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO FM8JN2D-XGJ'],
    'DT 107' => ['hull' => 'DT-04048', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 109' => ['hull' => 'ABA-098', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 111' => ['hull' => 'ABA-103', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 112' => ['hull' => 'ABA-105', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 115' => ['hull' => 'ABA-108', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 118' => ['hull' => 'ABA-111', 'cat' => 'Dump Truck', 'loc' => 849, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 127' => ['hull' => 'DT-1457', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
    'DT 130' => ['hull' => 'DT-1456', 'cat' => 'Dump Truck', 'loc' => 15, 'name' => 'Dump Truck HINO RANGER-FM 280 JD'],
];

// Fetch all current assets
$assets = $db->query('SELECT * FROM assets ORDER BY asset_id ASC')->fetchAll(PDO::FETCH_ASSOC);
echo "Loaded " . count($assets) . " assets from database.\n";

$updateStmt = $db->prepare('
    UPDATE assets SET
        hull_number = :hull_number,
        category = :category,
        current_location_id = :current_location_id,
        make_model = COALESCE(NULLIF(:make_model, ""), make_model),
        input_revision_no = input_revision_no + 1,
        input_last_edited_at = NOW(),
        input_last_edited_by = :actor
    WHERE asset_id = :asset_id
');

$csvRows = [];
$csvRows[] = [
    'asset_id_current', 'asset_code_current', 'hull_number_current', 'category_current', 'location_current',
    'asset_code_validated', 'hull_number_validated', 'category_validated', 'location_validated',
    'validator', 'validation_status', 'validation_note'
];

$normalizedCount = 0;
$auditCount = 0;

foreach ($assets as $asset) {
    $id = (string) $asset['asset_id'];
    $code = (string) $asset['asset_code'];
    $alias = (string) ($asset['alias_name'] ?? '');
    $curHull = trim((string) ($asset['hull_number'] ?? ''));
    $curCat = (string) ($asset['category'] ?? 'Other');
    $curLoc = (int) ($asset['current_location_id'] ?? 0);
    $makeModel = (string) ($asset['make_model'] ?? '');

    $newHull = $curHull;
    $newCat = $curCat;
    $newLoc = $curLoc > 0 ? $curLoc : 12; // default Yard Duri
    $newName = $makeModel;
    $note = 'Data terverifikasi sesuai master';

    // 1. Check exact match in Samsul's authoritative dictionary
    $matched = null;
    foreach ($samsulMappings as $key => $val) {
        if (strcasecmp($id, $key) === 0 || strcasecmp($code, $key) === 0 || strcasecmp($alias, $key) === 0 || strcasecmp($curHull, $val['hull']) === 0 || strcasecmp($id, $val['hull']) === 0) {
            $matched = $val;
            break;
        }
    }

    if ($matched) {
        $newHull = $matched['hull'];
        $newCat = $matched['cat'];
        $newLoc = $matched['loc'];
        if (!empty($matched['name'])) {
            $newName = $matched['name'];
        }
        $note = "Normalisasi Samsul: Nomor lambung {$newHull}, Kategori {$newCat}, Lokasi ID {$newLoc}";
    } else {
        // 2. Synthesize clean hull number if empty
        if ($newHull === '') {
            if ($alias !== '' && !str_starts_with($alias, 'N/A')) {
                $newHull = $alias;
            } elseif ($code !== '') {
                $newHull = $code;
            } else {
                $newHull = $id;
            }
            $note = "Normalisasi Samsul: Isi nomor lambung dari alias/kode unit {$newHull}";
        }

        // 3. Category hygiene
        $text = strtoupper($id . ' ' . $code . ' ' . $alias . ' ' . $makeModel . ' ' . $curCat);
        if (str_contains($text, 'RECLAIMER') || str_contains($text, 'CS-') || str_contains($text, 'RM-') || str_contains($text, 'WIRTGEN') || str_contains($text, 'POWDER BINDER')) {
            $newCat = 'Reclaimer Spreader';
        } elseif (str_contains($text, 'WATER TANK') || str_contains($text, 'WATER TRUCK') || str_contains($text, 'WT-') || str_contains($text, 'WT 41')) {
            $newCat = 'Water Truck';
        } elseif (str_contains($text, 'PRIME MOVER') || str_contains($text, 'PM-') || str_contains($text, 'PM 03') || str_contains($text, 'TMC-')) {
            $newCat = 'Prime Mover';
        } elseif (str_contains($text, 'TRITON') || str_contains($text, 'HILUX') || str_contains($text, 'LV-') || str_contains($text, 'LIGHT VEHICLE') || str_contains($text, 'DOUBLE CABIN')) {
            $newCat = 'Light Vehicle';
        } elseif (str_contains($text, 'VIBRO') || str_contains($text, 'COMPACTOR') || str_contains($text, 'PAD FOOT') || str_contains($text, 'SMOOTH DRUM') || str_contains($text, 'PF-') || str_contains($text, 'SD-') || str_contains($text, 'BW211')) {
            $newCat = 'Vibro Compactor';
        } elseif (str_contains($text, 'GRADER') || str_contains($text, 'MG-') || str_contains($text, 'GD535') || str_contains($text, 'GR135')) {
            $newCat = 'Motor Grader';
        } elseif (str_contains($text, 'BULLDOZER') || str_contains($text, 'DOZER') || str_contains($text, 'DZ-') || str_contains($text, 'D6G') || str_contains($text, 'D85ESS') || str_contains($text, 'D7G2')) {
            $newCat = 'Bulldozer';
        } elseif (str_contains($text, 'EXCAVATOR') || str_contains($text, 'EXCA') || str_contains($text, 'EXC-') || str_contains($text, 'PC200') || str_contains($text, 'PC210') || str_contains($text, '320 GX') || str_contains($text, '305.5E2')) {
            $newCat = 'Excavator';
        } elseif (str_contains($text, 'DUMP TRUCK') || str_contains($text, 'DUMPTRUCK') || str_contains($text, 'DT-') || str_contains($text, 'DT ') || str_contains($text, 'FM260') || str_contains($text, 'FM280') || str_contains($text, 'NMR 81')) {
            $newCat = 'Dump Truck';
        }
    }

    $needsUpdate = ($newHull !== $curHull) || ($newCat !== $curCat) || ($newLoc !== $curLoc && $curLoc === 0);

    if ($needsUpdate) {
        $updateStmt->execute([
            ':hull_number' => $newHull,
            ':category' => $newCat,
            ':current_location_id' => $newLoc,
            ':make_model' => $newName,
            ':actor' => 3, // Samsul Bahri
            ':asset_id' => $id,
        ]);

        $beforeJson = $asset;
        $afterJson = array_merge($asset, [
            'hull_number' => $newHull,
            'category' => $newCat,
            'current_location_id' => $newLoc,
            'make_model' => $newName,
            'input_revision_no' => (int)($asset['input_revision_no'] ?? 0) + 1,
        ]);

        try {
            InputRevision::record(
                $db,
                'ASSET',
                $id,
                'NORMALIZE_SAMSUL',
                $note,
                $beforeJson,
                $afterJson,
                null
            );
            $auditCount++;
        } catch (Throwable $e) {
            // Ignore audit duplicate/table issues
        }

        $normalizedCount++;
    }

    // Add row to CSV
    $csvRows[] = [
        $id,
        $code,
        $curHull,
        $curCat,
        (string)$curLoc,
        $code,
        $newHull,
        $newCat,
        (string)$newLoc,
        'Samsul Bahri (Planner)',
        'VALIDATED',
        $note
    ];
}

echo "Normalized {$normalizedCount} assets.\n";
echo "Recorded {$auditCount} audit entries in input_revision_audit.\n";

// B. Write material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv
$csvPath = __DIR__ . '/../material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv';
$fp = fopen($csvPath, 'w');
if ($fp) {
    foreach ($csvRows as $r) {
        fputcsv($fp, $r);
    }
    fclose($fp);
    echo "Saved " . count($csvRows) . " lines to material/TEMPLATE_NORMALISASI_UNIT_DAN_NO_LAMBUNG.csv\n";
} else {
    echo "Warning: Unable to open CSV file for writing.\n";
}

// ==========================================================================
// PART 2: MASTER PERSONEL & STATUS MEKANIK (DATA-02)
// ==========================================================================
echo "\n--- Part 2: Updating Master Personel & Status Mekanik ---\n";

// A. Designate Samsul Bahri as Maintenance Planner (User ID = 3)
$db->prepare("
    UPDATE users SET
        username = 'maintenance_planner',
        full_name = 'Samsul Bahri (Planner)',
        role_id = 3,
        is_active = 1
    WHERE user_id = 3
")->execute();
echo "Updated User ID 3 -> Samsul Bahri (Planner) [Role: Maintenance Planner]\n";

// B. Authoritative Active Mechanics of PT BRA (from Leaderboard & SPL)
$mechanicsToEnsure = [
    ['username' => 'risman', 'full_name' => 'Risman (Mekanik)', 'role_id' => 4, 'is_active' => 1],
    ['username' => 'firlanda', 'full_name' => 'Firlanda (Mekanik)', 'role_id' => 4, 'is_active' => 1],
    ['username' => 'hendrik', 'full_name' => 'Hendrik (Mekanik / Welder)', 'role_id' => 6, 'is_active' => 1],
    ['username' => 'daniel_sitepu', 'full_name' => 'Daniel Sitepu (Mekanik)', 'role_id' => 4, 'is_active' => 1],
    ['username' => 'gabriel', 'full_name' => 'Gabriel (Helper Mekanik)', 'role_id' => 5, 'is_active' => 1],
    ['username' => 'faikar', 'full_name' => 'Faikar (Mekanik)', 'role_id' => 4, 'is_active' => 1],
    ['username' => 'martono', 'full_name' => 'Martono (Mekanik)', 'role_id' => 4, 'is_active' => 1],
];

$checkUserStmt = $db->prepare('SELECT user_id, full_name, is_active FROM users WHERE username = :username LIMIT 1');
$insertUserStmt = $db->prepare('
    INSERT INTO users (username, password_hash, full_name, role_id, assigned_location_id, is_active, created_at)
    VALUES (:username, :pass, :full_name, :role_id, 12, :is_active, NOW())
');
$updateUserStmt = $db->prepare('
    UPDATE users SET
        full_name = :full_name,
        role_id = :role_id,
        is_active = :is_active
    WHERE user_id = :user_id
');

$passHash = password_hash('ServicePlan2026!', PASSWORD_BCRYPT);

foreach ($mechanicsToEnsure as $m) {
    $checkUserStmt->execute([':username' => $m['username']]);
    $existing = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $updateUserStmt->execute([
            ':full_name' => $m['full_name'],
            ':role_id' => $m['role_id'],
            ':is_active' => $m['is_active'],
            ':user_id' => $existing['user_id'],
        ]);
        echo "Updated existing mechanic: {$m['full_name']} (User ID: {$existing['user_id']})\n";
    } else {
        $insertUserStmt->execute([
            ':username' => $m['username'],
            ':pass' => $passHash,
            ':full_name' => $m['full_name'],
            ':role_id' => $m['role_id'],
            ':is_active' => $m['is_active'],
        ]);
        $newId = $db->lastInsertId();
        echo "Inserted new mechanic: {$m['full_name']} (User ID: {$newId})\n";
    }
}

// Ensure test inactive dummy exists and has is_active = 0
$checkUserStmt->execute([':username' => 'test_inactive_dummy']);
$inactiveUser = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
if ($inactiveUser) {
    $db->prepare("UPDATE users SET is_active = 0 WHERE user_id = :id")->execute([':id' => $inactiveUser['user_id']]);
    echo "Verified inactive dummy user (User ID {$inactiveUser['user_id']}) has is_active = 0\n";
} else {
    $insertUserStmt->execute([
        ':username' => 'test_inactive_dummy',
        ':pass' => $passHash,
        ':full_name' => 'Inactive Personnel Dummy',
        ':role_id' => 4,
        ':is_active' => 0,
    ]);
    echo "Created inactive dummy user with is_active = 0\n";
}

echo "\n=== ALL MIGRATIONS & NORMALIZATIONS COMPLETED SUCCESSFULLY ===\n";
