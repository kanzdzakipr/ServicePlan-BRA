<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 3 - Preventive Maintenance, Greasing Hauling & Battery Health Logs
 * 
 * Target:
 * 1. Tabel `grease_logs`:
 *    - Ingest 25 log pelumasan mingguan unit hauling (23 unit dump truck, Minggu 1 & 2) dari `03`.
 * 2. Tabel `battery_logs`:
 *    - Ingest 12 log riwayat pemakaian & kondisi baterai/accu (6 transaksi BKTBI dari `05_05` + 6 instalasi WO breakdown).
 * 3. Tabel `assets`:
 *    - Update parameter servis berkala (last_hm_km, last_service_hm_km, last_service_date,
 *      service_interval_val, service_interval_unit, next_service_due_hm_km, next_service_estimated_date)
 *      untuk 13 unit RSV1 dari `04_01`.
 * 4. Tabel `pm_plans`:
 *    - Sinkronisasi rencana pemeliharaan berkala (current_smr, last_service_hm, last_service_date,
 *      target_due_hm, variance_hm, status 'DUE_SOON'/'OVERDUE', warranty_status, planner_note).
 * 5. Tabel `app_data_migrations`:
 *    - Catat migration key 'BATCH_3_EQUIPMENT_REPORT_202601'.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 3 DATABASE ENRICHMENT MIGRATION\n";
echo " Source: Laporan Divisi Equipment Januari 2026 (Greasing, Battery & PM Service)\n";
echo "============================================================================\n\n";

$fileGrease = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/03_Laporan_Greasing_Januari_2026.md';
$fileBat    = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/05_05_Baterai_Accu_Pemakaian.md';
$filePM     = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/04_01_Tracking_HM_KM_dan_Rencana_Service.md';

if (!file_exists($fileGrease) || !file_exists($fileBat) || !file_exists($filePM)) {
    die("❌ FATAL ERROR: Berkas sumber markdown laporan Januari 2026 tidak lengkap!\n");
}

$migrationKey = 'BATCH_3_EQUIPMENT_REPORT_202601';

// Cek apakah sudah pernah dijalankan
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
if ((int)$stmtCheck->fetchColumn() > 0) {
    echo "⚠️ PERINGATAN: Migrasi $migrationKey sudah pernah dicatat di app_data_migrations.\n";
    echo "Melanjutkan sinkronisasi idempotent agar seluruh data dipastikan sinkron...\n\n";
}

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // STEP 1: Ingest grease_logs dari 03_Laporan_Greasing_Januari_2026.md
    // -------------------------------------------------------------------------
    echo "--- [1/3] Ingest Riwayat Pelumasan ke Tabel `grease_logs` ---\n";
    $linesG = file($fileGrease);
    $greaseRows = [];

    foreach ($linesG as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '|') continue;
        $cols = array_map('trim', explode('|', $line));
        array_shift($cols);
        array_pop($cols);
        if (!isset($cols[1]) || !preg_match('/^DT-/', $cols[1])) continue;
        
        $assetId = $cols[1];
        
        // Minggu 1
        $km1 = (float)str_replace(',', '', $cols[4] ?? '0');
        $hm1 = (float)str_replace(',', '', $cols[5] ?? '0');
        $date1 = trim($cols[6] ?? '');
        if ($date1 !== '' && ($km1 > 0 || $hm1 > 0)) {
            $p = explode('/', $date1);
            $dt = sprintf('%04d-%02d-%02d 09:30:00', (int)$p[2], (int)$p[0], (int)$p[1]);
            $currentHm = $hm1 > 0 ? $hm1 : $km1;
            $greaseRows[] = [
                'asset_id' => $assetId,
                'current_hm' => $currentHm,
                'last_grease_hm' => max(0, $currentHm - 250),
                'interval_hm' => 250.00,
                'quantity_kg' => 2.50,
                'grease_type' => 'NLGI 2 / Cobra EP',
                'lubrication_points' => json_encode(["Chassis", "Propeller Shaft", "Trunnion", "Spring Hanger", "King Pin", "Tie Rod"]),
                'notes' => "Pelumasan mingguan hauling (Greasing chassis, trunnion, propeller shaft & pembersihan filter udara) - Minggu 1",
                'status_level' => 'success',
                'inspected_by' => 4, // Rahmad K (Mekanik Senior)
                'inspected_at' => $dt
            ];
        }
        
        // Minggu 2
        $km2 = (float)str_replace(',', '', $cols[7] ?? '0');
        $hm2 = (float)str_replace(',', '', $cols[8] ?? '0');
        $date2 = trim($cols[9] ?? '');
        if ($date2 !== '' && ($km2 > 0 || $hm2 > 0)) {
            $p = explode('/', $date2);
            $dt = sprintf('%04d-%02d-%02d 09:30:00', (int)$p[2], (int)$p[0], (int)$p[1]);
            $currentHm = $hm2 > 0 ? $hm2 : $km2;
            $greaseRows[] = [
                'asset_id' => $assetId,
                'current_hm' => $currentHm,
                'last_grease_hm' => max(0, $currentHm - 250),
                'interval_hm' => 250.00,
                'quantity_kg' => 2.50,
                'grease_type' => 'NLGI 2 / Cobra EP',
                'lubrication_points' => json_encode(["Chassis", "Propeller Shaft", "Trunnion", "Spring Hanger", "King Pin", "Tie Rod"]),
                'notes' => "Pelumasan mingguan hauling (Greasing chassis, trunnion, propeller shaft & pembersihan filter udara) - Minggu 2",
                'status_level' => 'success',
                'inspected_by' => 4,
                'inspected_at' => $dt
            ];
        }
    }

    $stmtInsertGrease = $pdo->prepare("
        INSERT INTO grease_logs (
            asset_id, current_hm, last_grease_hm, interval_hm, quantity_kg,
            grease_type, lubrication_points, notes, status_level, inspected_by, inspected_at
        ) VALUES (
            :asset_id, :current_hm, :last_grease_hm, :interval_hm, :quantity_kg,
            :grease_type, :lubrication_points, :notes, :status_level, :inspected_by, :inspected_at
        )
    ");

    $greaseInserted = 0;
    foreach ($greaseRows as $gr) {
        // Cek duplikasi identik
        $stmtDup = $pdo->prepare("SELECT COUNT(*) FROM grease_logs WHERE asset_id = :a AND inspected_at = :t");
        $stmtDup->execute([':a' => $gr['asset_id'], ':t' => $gr['inspected_at']]);
        if ((int)$stmtDup->fetchColumn() === 0) {
            $stmtInsertGrease->execute([
                ':asset_id' => $gr['asset_id'],
                ':current_hm' => $gr['current_hm'],
                ':last_grease_hm' => $gr['last_grease_hm'],
                ':interval_hm' => $gr['interval_hm'],
                ':quantity_kg' => $gr['quantity_kg'],
                ':grease_type' => $gr['grease_type'],
                ':lubrication_points' => $gr['lubrication_points'],
                ':notes' => $gr['notes'],
                ':status_level' => $gr['status_level'],
                ':inspected_by' => $gr['inspected_by'],
                ':inspected_at' => $gr['inspected_at']
            ]);
            $greaseInserted++;
        }
    }
    $totalGrease = $pdo->query("SELECT COUNT(*) FROM grease_logs")->fetchColumn();
    echo "  -> Berhasil menginput $greaseInserted entri baru (Total tabel grease_logs: $totalGrease baris).\n\n";

    // -------------------------------------------------------------------------
    // STEP 2: Ingest battery_logs dari 05_05 & Breakdown Log
    // -------------------------------------------------------------------------
    echo "--- [2/3] Ingest Riwayat Peremajaan & Kondisi Aki ke `battery_logs` ---\n";
    
    // Pastikan EXC-00019 memiliki alias MB-00006
    $pdo->query("UPDATE assets SET alias_name = 'MB-00006', hull_number = 'MB-06' WHERE asset_id = 'EXC-00019' AND (alias_name IS NULL OR alias_name = '')");

    $batteryList = [
        // 05_05 Outbound Warehouse Issues (Bukti BKTBI)
        [
            'asset_id' => 'DT-00065',
            'battery_brand' => 'GS Premium',
            'battery_type' => '65D31R-N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-26',
            'notes' => 'Penggantian 2 pcs aki GS Premium N70 baru sesuai BHW-OUT Bukti 01.01/BKTBI'
        ],
        [
            'asset_id' => 'DT-00031',
            'battery_brand' => 'GS Premium',
            'battery_type' => '65D31R-N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-27',
            'notes' => 'Penggantian 2 pcs aki GS Premium N70 baru sesuai BHW-OUT Bukti 02.01/BKTBI'
        ],
        [
            'asset_id' => 'FBT-41001',
            'battery_brand' => 'GS Premium',
            'battery_type' => '65D31R-N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-29',
            'notes' => 'Penggantian 1 pcs aki GS Premium N70 baru pada Trado BM 8371 CU sesuai Bukti 03.01/BKTBI'
        ],
        [
            'asset_id' => 'DZ-00002',
            'battery_brand' => 'GS Premium',
            'battery_type' => '65D31R-N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-29',
            'notes' => 'Penggantian 1 pcs aki GS Premium N70 baru pada Dozer DZ-00002 sesuai Bukti 03.01/BKTBI'
        ],
        [
            'asset_id' => 'EXC-00019',
            'battery_brand' => 'GS Premium',
            'battery_type' => '65D31R-N70 (70 Ah)',
            'voltage_val' => 12.00,
            'water_level' => 'GOOD',
            'installation_date' => '2026-01-23',
            'notes' => 'Penggantian 2 pcs aki GS Premium N70 baru pada Minibus MB-00006 BM 7680 TU sesuai Bukti 04.01/BKTBI'
        ],
        [
            'asset_id' => 'DT-04037',
            'battery_brand' => 'GS Premium',
            'battery_type' => '65D31R-N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2026-01-23',
            'notes' => 'Penggantian 2 pcs aki GS Premium N70 baru sesuai BHW-OUT Bukti 04.01/BKTBI'
        ],
        // WO Breakdown Event Replacements
        [
            'asset_id' => 'DT-00049',
            'battery_brand' => 'Incoe Heavy Duty',
            'battery_type' => 'N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-20',
            'notes' => 'Penggantian Accu 2 pcs dan perbaikan kepala baterai pada tiket WO-26-002'
        ],
        [
            'asset_id' => 'DT-00055',
            'battery_brand' => 'Incoe Heavy Duty',
            'battery_type' => 'N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-23',
            'notes' => 'Penggantian baterai baru & klem tutup aki pada tiket WO-26-004'
        ],
        [
            'asset_id' => 'DT-00059',
            'battery_brand' => 'Incoe Heavy Duty',
            'battery_type' => 'N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-23',
            'notes' => 'Pabrikasi bracket accu dan pemasangan aki baru pada tiket WO-26-005'
        ],
        [
            'asset_id' => 'DT-00060',
            'battery_brand' => 'Incoe Heavy Duty',
            'battery_type' => 'N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-26',
            'notes' => 'Penggantian aki baru 2 pcs dan bracket penahan accu pada tiket WO-26-008'
        ],
        [
            'asset_id' => 'DT-00064',
            'battery_brand' => 'Incoe Heavy Duty',
            'battery_type' => 'N70 (70 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-23',
            'notes' => 'Pemasangan bracket baterai dan aki baru pada tiket WO-26-009'
        ],
        [
            'asset_id' => 'EXC-00003',
            'battery_brand' => 'Komatsu Heavy Duty',
            'battery_type' => 'N120 (120 Ah)',
            'voltage_val' => 24.00,
            'water_level' => 'GOOD',
            'installation_date' => '2025-12-22',
            'notes' => 'Pemasangan aki 24V Komatsu pada Excavator PC200 pada tiket WO-26-030'
        ]
    ];

    $stmtInsertBat = $pdo->prepare("
        INSERT INTO battery_logs (
            asset_id, battery_brand, voltage_val, water_level, installation_date,
            battery_type, cca_percent, terminal_condition, electrolyte_level, case_condition,
            notes, status_level, inspected_by, inspected_at
        ) VALUES (
            :asset_id, :battery_brand, :voltage_val, :water_level, :installation_date,
            :battery_type, :cca_percent, :terminal_condition, :electrolyte_level, :case_condition,
            :notes, :status_level, :inspected_by, :inspected_at
        )
    ");

    $batInserted = 0;
    foreach ($batteryList as $b) {
        $stmtDupBat = $pdo->prepare("SELECT COUNT(*) FROM battery_logs WHERE asset_id = :a AND installation_date = :d");
        $stmtDupBat->execute([':a' => $b['asset_id'], ':d' => $b['installation_date']]);
        if ((int)$stmtDupBat->fetchColumn() === 0) {
            $stmtInsertBat->execute([
                ':asset_id' => $b['asset_id'],
                ':battery_brand' => $b['battery_brand'],
                ':voltage_val' => $b['voltage_val'],
                ':water_level' => $b['water_level'],
                ':installation_date' => $b['installation_date'],
                ':battery_type' => $b['battery_type'],
                ':cca_percent' => 98.50,
                ':terminal_condition' => 'Bersih & kencang (baru terpasang)',
                ':electrolyte_level' => 'Normal (Level Max)',
                ':case_condition' => 'Baik / Baru',
                ':notes' => $b['notes'],
                ':status_level' => 'success',
                ':inspected_by' => 4,
                ':inspected_at' => $b['installation_date'] . ' 14:00:00'
            ]);
            $batInserted++;
        }
    }
    $totalBat = $pdo->query("SELECT COUNT(*) FROM battery_logs")->fetchColumn();
    echo "  -> Berhasil menginput $batInserted entri peremajaan baterai baru (Total tabel battery_logs: $totalBat baris).\n\n";

    // -------------------------------------------------------------------------
    // STEP 3: Update Parameter Servis Berkala `assets` & `pm_plans`
    // -------------------------------------------------------------------------
    echo "--- [3/3] Update Parameter Servis Berkala pada `assets` & `pm_plans` ---\n";
    
    // Pastikan EXC-06004 memiliki hull number HE-6.004
    $pdo->query("UPDATE assets SET alias_name = 'EXCA 16', hull_number = 'HE-6.004', make_model = 'Excavator Caterpillar 305.5E2' WHERE asset_id = 'EXC-06004'");

    $pmList = [
        [
            'asset_id' => 'DZ-00004',
            'current_smr' => 977.00,
            'last_service_hm' => 550.30,
            'last_service_date' => '2025-09-22',
            'interval_hm' => 500,
            'interval_unit' => 'HM',
            'target_due_hm' => 1050.30,
            'target_due_date' => '2026-02-02',
            'variance_hm' => -73.30,
            'status' => 'DUE_SOON',
            'warranty_status' => 'Warranty',
            'planner_note' => 'RSV1 Tahap 1 - Bulldozer Zoomlion (Jatuh tempo service 500 HM)'
        ],
        [
            'asset_id' => 'EXC-00003',
            'current_smr' => 5488.60,
            'last_service_hm' => 4998.10,
            'last_service_date' => '2025-09-08',
            'interval_hm' => 500,
            'interval_unit' => 'HM',
            'target_due_hm' => 5498.10,
            'target_due_date' => '2026-01-13',
            'variance_hm' => -9.50,
            'status' => 'OVERDUE',
            'warranty_status' => 'Warranty - 6000 (Habis)',
            'planner_note' => 'RSV1 - Excavator Komatsu PC200-10M0 CE (Service Overdue per 13 Jan 2026)'
        ],
        [
            'asset_id' => 'EXC-06004',
            'current_smr' => 3631.00,
            'last_service_hm' => 3631.00,
            'last_service_date' => '2025-07-18',
            'interval_hm' => 500,
            'interval_unit' => 'HM',
            'target_due_hm' => 4131.00,
            'target_due_date' => '2026-01-28',
            'variance_hm' => -500.00,
            'status' => 'PLANNED',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Excavator Caterpillar 305.5E2 ID HE.6.004'
        ],
        [
            'asset_id' => 'DT-00031',
            'current_smr' => 74135.00,
            'last_service_hm' => 66550.00,
            'last_service_date' => '2025-07-21',
            'interval_hm' => 7500,
            'interval_unit' => 'KM',
            'target_due_hm' => 74050.00,
            'target_due_date' => '2026-01-17',
            'variance_hm' => 85.00,
            'status' => 'OVERDUE',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM 260 JD SW 02 (Service Overdue +85 KM)'
        ],
        [
            'asset_id' => 'DT-00037',
            'current_smr' => 55821.00,
            'last_service_hm' => 45807.00,
            'last_service_date' => '2025-06-02',
            'interval_hm' => 10000,
            'interval_unit' => 'KM',
            'target_due_hm' => 55807.00,
            'target_due_date' => '2026-01-18',
            'variance_hm' => 14.00,
            'status' => 'OVERDUE',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM 280 JD DT 034 (Service Overdue +14 KM)'
        ],
        [
            'asset_id' => 'DT-00047',
            'current_smr' => 111343.00,
            'last_service_hm' => 102966.00,
            'last_service_date' => '2025-11-10',
            'interval_hm' => 7500,
            'interval_unit' => 'KM',
            'target_due_hm' => 110466.00,
            'target_due_date' => '2026-01-07',
            'variance_hm' => 877.00,
            'status' => 'OVERDUE',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM 260 JD DT 010 (Service Overdue +877 KM)'
        ],
        [
            'asset_id' => 'DT-00056',
            'current_smr' => 238177.00,
            'last_service_hm' => 230933.00,
            'last_service_date' => '2025-05-15',
            'interval_hm' => 7500,
            'interval_unit' => 'KM',
            'target_due_hm' => 238433.00,
            'target_due_date' => '2026-01-15',
            'variance_hm' => -256.00,
            'status' => 'DUE_SOON',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM 260 JD DT 015 (Due Soon -256 KM)'
        ],
        [
            'asset_id' => 'DT-00071',
            'current_smr' => 112368.00,
            'last_service_hm' => 104926.00,
            'last_service_date' => '2025-05-22',
            'interval_hm' => 7500,
            'interval_unit' => 'KM',
            'target_due_hm' => 112426.00,
            'target_due_date' => '2026-01-19',
            'variance_hm' => -58.00,
            'status' => 'DUE_SOON',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM 260 JD DT 001 (Due Soon -58 KM)'
        ],
        [
            'asset_id' => 'DT-41001',
            'current_smr' => 93720.00,
            'last_service_hm' => null,
            'last_service_date' => null,
            'interval_hm' => 10000,
            'interval_unit' => 'KM',
            'target_due_hm' => 93720.00,
            'target_due_date' => '2026-01-28',
            'variance_hm' => 0.00,
            'status' => 'DUE_SOON',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM8JN2D-XGJ DT 099'
        ],
        [
            'asset_id' => 'DT-41002',
            'current_smr' => 43457.00,
            'last_service_hm' => null,
            'last_service_date' => null,
            'interval_hm' => 10000,
            'interval_unit' => 'KM',
            'target_due_hm' => 43457.00,
            'target_due_date' => '2026-01-28',
            'variance_hm' => 0.00,
            'status' => 'DUE_SOON',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM8JN2D-XGJ DT 102 (STB Yard)'
        ],
        [
            'asset_id' => 'DT-41003',
            'current_smr' => 80382.00,
            'last_service_hm' => null,
            'last_service_date' => null,
            'interval_hm' => 10000,
            'interval_unit' => 'KM',
            'target_due_hm' => 80382.00,
            'target_due_date' => '2026-01-28',
            'variance_hm' => 0.00,
            'status' => 'DUE_SOON',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM8JN2D-XGJ DT 106'
        ],
        [
            'asset_id' => 'DT-41004',
            'current_smr' => 77729.00,
            'last_service_hm' => null,
            'last_service_date' => null,
            'interval_hm' => 10000,
            'interval_unit' => 'KM',
            'target_due_hm' => 77729.00,
            'target_due_date' => '2026-01-28',
            'variance_hm' => 0.00,
            'status' => 'DUE_SOON',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM8JN2D-XGJ DT 095'
        ],
        [
            'asset_id' => 'DT-41005',
            'current_smr' => 89250.00,
            'last_service_hm' => null,
            'last_service_date' => null,
            'interval_hm' => 10000,
            'interval_unit' => 'KM',
            'target_due_hm' => 89250.00,
            'target_due_date' => '2026-01-28',
            'variance_hm' => 0.00,
            'status' => 'DUE_SOON',
            'warranty_status' => 'No Warranty',
            'planner_note' => 'RSV1 - Dump Truck HINO FM8JN2D-XGJ DT 105'
        ]
    ];

    $stmtUpdateAsset = $pdo->prepare("
        UPDATE assets SET
            last_hm_km = :last_hm_km,
            last_service_hm_km = :last_service_hm_km,
            last_service_date = :last_service_date,
            service_interval_val = :service_interval_val,
            service_interval_unit = :service_interval_unit,
            next_service_due_hm_km = :next_service_due_hm_km,
            next_service_estimated_date = :next_service_estimated_date
        WHERE asset_id = :asset_id
    ");

    $stmtInsertPM = $pdo->prepare("
        INSERT INTO pm_plans (
            asset_id, interval_hm, current_smr, last_service_hm, last_service_date,
            target_due_hm, variance_hm, status, warranty_status, planner_note
        ) VALUES (
            :asset_id, :interval_hm, :current_smr, :last_service_hm, :last_service_date,
            :target_due_hm, :variance_hm, :status, :warranty_status, :planner_note
        )
    ");

    $stmtUpdatePM = $pdo->prepare("
        UPDATE pm_plans SET
            interval_hm = :interval_hm,
            current_smr = :current_smr,
            last_service_hm = :last_service_hm,
            last_service_date = :last_service_date,
            target_due_hm = :target_due_hm,
            variance_hm = :variance_hm,
            status = :status,
            warranty_status = :warranty_status,
            planner_note = :planner_note
        WHERE asset_id = :asset_id
    ");

    $pmUpdated = 0;
    foreach ($pmList as $pm) {
        $stmtUpdateAsset->execute([
            ':last_hm_km' => $pm['current_smr'],
            ':last_service_hm_km' => $pm['last_service_hm'],
            ':last_service_date' => $pm['last_service_date'] ? ($pm['last_service_date'] . ' 08:00:00') : null,
            ':service_interval_val' => $pm['interval_hm'],
            ':service_interval_unit' => $pm['interval_unit'],
            ':next_service_due_hm_km' => $pm['target_due_hm'],
            ':next_service_estimated_date' => $pm['target_due_date'],
            ':asset_id' => $pm['asset_id']
        ]);

        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM pm_plans WHERE asset_id = :a");
        $stmtC->execute([':a' => $pm['asset_id']]);
        if ((int)$stmtC->fetchColumn() > 0) {
            $stmtUpdatePM->execute([
                ':interval_hm' => $pm['interval_hm'],
                ':current_smr' => $pm['current_smr'],
                ':last_service_hm' => $pm['last_service_hm'],
                ':last_service_date' => $pm['last_service_date'],
                ':target_due_hm' => $pm['target_due_hm'],
                ':variance_hm' => $pm['variance_hm'],
                ':status' => $pm['status'],
                ':warranty_status' => $pm['warranty_status'],
                ':planner_note' => $pm['planner_note'],
                ':asset_id' => $pm['asset_id']
            ]);
        } else {
            $stmtInsertPM->execute([
                ':asset_id' => $pm['asset_id'],
                ':interval_hm' => $pm['interval_hm'],
                ':current_smr' => $pm['current_smr'],
                ':last_service_hm' => $pm['last_service_hm'],
                ':last_service_date' => $pm['last_service_date'],
                ':target_due_hm' => $pm['target_due_hm'],
                ':variance_hm' => $pm['variance_hm'],
                ':status' => $pm['status'],
                ':warranty_status' => $pm['warranty_status'],
                ':planner_note' => $pm['planner_note']
            ]);
        }
        $pmUpdated++;
    }
    $totalPM = $pdo->query("SELECT COUNT(*) FROM pm_plans")->fetchColumn();
    echo "  -> Berhasil memperbarui $pmUpdated unit jadwal servis PM (Total tabel pm_plans: $totalPM baris).\n\n";

    // -------------------------------------------------------------------------
    // Record Migration Log
    // -------------------------------------------------------------------------
    $stmtMig = $pdo->prepare("INSERT INTO app_data_migrations (migration_key, applied_at) VALUES (:k, NOW()) ON DUPLICATE KEY UPDATE applied_at = NOW()");
    $stmtMig->execute([':k' => $migrationKey]);

    $pdo->commit();
    echo "============================================================================\n";
    echo " ✅ MIGRASI BATCH 3 BERHASIL DISELESAIKAN & DI-COMMIT SECARA ACID!\n";
    echo "============================================================================\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ FATAL EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
