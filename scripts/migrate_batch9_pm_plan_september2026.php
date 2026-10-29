<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 9 - Preventive Maintenance Plan September 2026 Close
 *
 * Synchronizes and ingests all 37 units from material/PREVENTIVE-MAINTENANCE/Laporan_PM_Service_September_2026_Close.md:
 * 1. Ensures missing project locations exist (Project RWI, Project Matting Board, Lahat Project).
 * 2. Ensures missing assets exist in `assets` with canonical mappings.
 * 3. Updates asset live telemetries (last_hm_km, last_service_hm_km, last_service_date, next_service_due_hm_km, next_service_estimated_date, current_location_id).
 * 4. Ingests 32 realized service records into `asset_service_history` with jo_report_number and mechanic_pic.
 * 5. Generates/synchronizes Work Orders in `work_orders` (maintenance_type = 'Preventive', status = 'Closed' for realized, 'Open' for pending).
 * 6. Upserts all 37 unit states into `pm_plans`.
 * 7. Registers BATCH_9_PM_PLAN_SEPTEMBER_2026 into `app_data_migrations`.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 9 PM PLAN SEPTEMBER 2026 INGESTION\n";
echo "============================================================================\n\n";

$migrationKey = 'BATCH_9_PM_PLAN_SEPTEMBER_2026';

// Check if already applied
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
$alreadyApplied = (int) $stmtCheck->fetchColumn() > 0;

$force = in_array('--force', $argv, true);
if ($alreadyApplied && !$force) {
    echo "[!] Migration '$migrationKey' has already been applied. Re-running in idempotent refresh mode.\n\n";
}

$septemberUnits = [
    // 1. WUR EW PROJECT
    [
        'no' => 1,
        'code' => 'SD 08',
        'asset_id' => 'PF-00012', // Canonical in DB matching SN 961582391661
        'source_aid' => 'SD-00005',
        'category' => 'Vibro Compactor',
        'make_model' => 'Vibro Bomag Smooth Drum Compactor BW 211D-40SL',
        'year' => 2024,
        'sn' => '961582391661',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 3925.6,
        'tracking_date' => '2026-09-05',
        'interval' => 500,
        'last_service_val' => 3482.6,
        'last_service_date' => '2026-03-02',
        'target_due_val' => 3982.6,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 3925.6,
        'actual_date' => '2026-09-05',
        'jo_number' => '01/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 2,
        'code' => 'PF-02',
        'asset_id' => 'PF-00004',
        'source_aid' => 'PF-00004',
        'category' => 'Vibro Compactor',
        'make_model' => 'Bomag Pad Foot Compactor BW211D-40',
        'year' => 2022,
        'sn' => '961884301614',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 5392.0,
        'tracking_date' => '2026-09-10',
        'interval' => 500,
        'last_service_val' => 4921.0,
        'last_service_date' => '2026-06-04',
        'target_due_val' => 5421.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 5392.0,
        'actual_date' => '2026-09-10',
        'jo_number' => '02/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 3,
        'code' => 'GR 1',
        'asset_id' => 'MG-00005',
        'source_aid' => 'MG-00005',
        'category' => 'Motor Grader',
        'make_model' => 'MOTOR GRADER CATERPILLAR 120 K',
        'year' => 2025,
        'sn' => 'SZN01229',
        'plate' => null,
        'warranty' => 'No Warranty/HM ERROR',
        'unit' => 'HM',
        'tracking_val' => 471.0,
        'tracking_date' => '2026-07-03',
        'interval' => 500,
        'last_service_val' => 219.7,
        'last_service_date' => '2026-05-19',
        'target_due_val' => 719.7,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 471.0,
        'actual_date' => '2026-09-15',
        'jo_number' => '03/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Catatan: HM Error pada saat PM'
    ],
    [
        'no' => 4,
        'code' => 'Dozer 10',
        'asset_id' => 'DZ-00004',
        'source_aid' => 'DZ-00004',
        'category' => 'Bulldozer',
        'make_model' => 'Bulldozer Zoomlion',
        'year' => 2025,
        'sn' => 'ZLHZD1116HR1B05388',
        'plate' => null,
        'warranty' => 'No warranty',
        'unit' => 'HM',
        'tracking_val' => 1955.4,
        'tracking_date' => '2026-09-26',
        'interval' => 500,
        'last_service_val' => 1539.9,
        'last_service_date' => '2026-06-30',
        'target_due_val' => 2039.9,
        'project' => 'WUR EW Project',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => '04/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'unit free service dari zoomlion , akan di FU kembali (Plan 02-10-2026)'
    ],
    [
        'no' => 5,
        'code' => 'Dozer 11',
        'asset_id' => 'DZ-00006',
        'source_aid' => 'DZ-00006',
        'category' => 'Bulldozer',
        'make_model' => 'Bulldozer SHANTUI SD16E',
        'year' => 2025,
        'sn' => 'CHSD16AEKR1054524',
        'plate' => null,
        'warranty' => 'Warrnaty 2000 HM',
        'unit' => 'HM',
        'tracking_val' => 1519.0,
        'tracking_date' => '2026-09-25',
        'interval' => 500,
        'last_service_val' => 1102.0,
        'last_service_date' => '2026-06-19',
        'target_due_val' => 1602.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 1537.3,
        'actual_date' => '2026-09-30',
        'jo_number' => '05/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 6,
        'code' => 'Exca 21',
        'asset_id' => 'EXC-00003',
        'source_aid' => 'EXC-00003',
        'category' => 'Excavator',
        'make_model' => 'Excavator Komatsu PC200-10M0 CE',
        'year' => 2022,
        'sn' => 'C51503',
        'plate' => null,
        'warranty' => 'No warranty',
        'unit' => 'HM',
        'tracking_val' => 6766.2,
        'tracking_date' => '2026-09-09',
        'interval' => 500,
        'last_service_val' => 6218.6,
        'last_service_date' => '2026-05-18',
        'target_due_val' => 6718.6,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 6766.2,
        'actual_date' => '2026-09-09',
        'jo_number' => '06/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 7,
        'code' => 'Exca 06',
        'asset_id' => 'EXC-00005',
        'source_aid' => 'EXC-00005',
        'category' => 'Excavator',
        'make_model' => 'Excavator Caterpillar 320 GX',
        'year' => 2021,
        'sn' => 'SYW01364',
        'plate' => null,
        'warranty' => 'No warranty',
        'unit' => 'HM',
        'tracking_val' => 7650.0,
        'tracking_date' => '2026-09-24',
        'interval' => 500,
        'last_service_val' => 7241.0,
        'last_service_date' => '2026-01-22',
        'target_due_val' => 7741.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 7652.6,
        'actual_date' => '2026-09-26',
        'jo_number' => '07/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 10,
        'code' => 'DT 059',
        'asset_id' => 'DT-00005',
        'source_aid' => 'DT-00005',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9113 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 49626.0,
        'tracking_date' => '2026-09-03',
        'interval' => 10000,
        'last_service_val' => 39204.0,
        'last_service_date' => '2026-06-10',
        'target_due_val' => 49204.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 49626.0,
        'actual_date' => '2026-09-03',
        'jo_number' => '08/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 11,
        'code' => 'DT 060',
        'asset_id' => 'DT-00006',
        'source_aid' => 'DT-00006',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9118 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 48068.0,
        'tracking_date' => '2026-09-25',
        'interval' => 10000,
        'last_service_val' => 39555.0,
        'last_service_date' => '2026-06-10',
        'target_due_val' => 49555.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 48346.0,
        'actual_date' => '2026-09-29',
        'jo_number' => '09/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 12,
        'code' => 'DT 065',
        'asset_id' => 'DT-00011',
        'source_aid' => 'DT-00011',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9120 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 48502.0,
        'tracking_date' => '2026-09-25',
        'interval' => 10000,
        'last_service_val' => 39838.0,
        'last_service_date' => '2026-06-18',
        'target_due_val' => 49838.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 48656.0,
        'actual_date' => '2026-09-29',
        'jo_number' => '10/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 13,
        'code' => 'DT 068',
        'asset_id' => 'DT-00014',
        'source_aid' => 'DT-00014',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9123 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 49201.0,
        'tracking_date' => '2026-09-03',
        'interval' => 10000,
        'last_service_val' => 39183.0,
        'last_service_date' => '2026-06-06',
        'target_due_val' => 49183.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 49402.0,
        'actual_date' => '2026-09-05',
        'jo_number' => '11/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 14,
        'code' => 'DT 070',
        'asset_id' => 'DT-00016',
        'source_aid' => 'DT-00016',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9125 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 48207.0,
        'tracking_date' => '2026-09-12',
        'interval' => 10000,
        'last_service_val' => 38729.0,
        'last_service_date' => '2026-05-26',
        'target_due_val' => 48729.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 48285.0,
        'actual_date' => '2026-09-15',
        'jo_number' => '12/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 15,
        'code' => 'DT 073',
        'asset_id' => 'DT-00019',
        'source_aid' => 'DT-00019',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9128 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 48924.0,
        'tracking_date' => '2026-09-11',
        'interval' => 10000,
        'last_service_val' => 39571.0,
        'last_service_date' => '2026-06-17',
        'target_due_val' => 49571.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 49090.0,
        'actual_date' => '2026-09-15',
        'jo_number' => '13/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 16,
        'code' => 'DT 077',
        'asset_id' => 'DT-00023',
        'source_aid' => 'DT-00023',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9132 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 48508.0,
        'tracking_date' => '2026-09-24',
        'interval' => 10000,
        'last_service_val' => 38686.0,
        'last_service_date' => '2026-06-17',
        'target_due_val' => 48686.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 48635.0,
        'actual_date' => '2026-09-28',
        'jo_number' => '14/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 17,
        'code' => 'DT 040',
        'asset_id' => 'DT-00043',
        'source_aid' => 'DT-00043',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2023,
        'sn' => null,
        'plate' => 'B 9091 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 78871.0,
        'tracking_date' => '2026-09-12',
        'interval' => 10000,
        'last_service_val' => 68300.0,
        'last_service_date' => '2026-05-12',
        'target_due_val' => 78300.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 78871.0,
        'actual_date' => '2026-09-12',
        'jo_number' => '15/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 18,
        'code' => 'DT 043',
        'asset_id' => 'DT-00046',
        'source_aid' => 'DT-00046',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2023,
        'sn' => null,
        'plate' => 'B 9094 ZYT',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 79641.0,
        'tracking_date' => '2026-09-19',
        'interval' => 10000,
        'last_service_val' => 70844.0,
        'last_service_date' => '2026-05-12',
        'target_due_val' => 80844.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 79864.0,
        'actual_date' => '2026-09-28',
        'jo_number' => '16/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 20,
        'code' => 'DT 052',
        'asset_id' => 'DT-00064',
        'source_aid' => 'DT-00064',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 260 JD',
        'year' => 2019,
        'sn' => null,
        'plate' => 'BG 8640 NI',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 81965.0,
        'tracking_date' => '2026-09-14',
        'interval' => 7500,
        'last_service_val' => 74482.0,
        'last_service_date' => '2026-06-20',
        'target_due_val' => 81982.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 82103.0,
        'actual_date' => '2026-09-16',
        'jo_number' => '18/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 21,
        'code' => 'DT 107',
        'asset_id' => 'DT-04048',
        'source_aid' => 'DT-04048',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9951 JO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 91438.0,
        'tracking_date' => '2026-09-02',
        'interval' => 10000,
        'last_service_val' => 81630.0,
        'last_service_date' => '2026-05-18',
        'target_due_val' => 91630.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 91438.0,
        'actual_date' => '2026-09-03',
        'jo_number' => '19/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 25,
        'code' => 'DT 130',
        'asset_id' => 'DT-00190', // Canonical matching BM 9506 QO
        'source_aid' => 'DT-1456',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9506 QO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 65054.0,
        'tracking_date' => '2026-09-21',
        'interval' => 10000,
        'last_service_val' => 56101.0,
        'last_service_date' => '2025-01-05',
        'target_due_val' => 66101.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 65565.0,
        'actual_date' => '2026-09-29',
        'jo_number' => '23/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 26,
        'code' => 'PM 03',
        'asset_id' => 'PM-00003', // New asset to insert
        'source_aid' => 'PM-00003',
        'category' => 'Prime Mover',
        'make_model' => 'PRIME MOVER Mercedes Benz 4928T',
        'year' => 2019,
        'sn' => null,
        'plate' => 'KT 8465 LU',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 52515.0,
        'tracking_date' => '2026-09-17',
        'interval' => 7500,
        'last_service_val' => 45905.0,
        'last_service_date' => '2026-04-17',
        'target_due_val' => 53405.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 52516.0,
        'actual_date' => '2026-09-26',
        'jo_number' => '24/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 31,
        'code' => 'PF 06',
        'asset_id' => 'PF-00002',
        'source_aid' => 'PF-00002',
        'category' => 'Vibro Compactor',
        'make_model' => 'Vibro Bomag Pad Foot Compactor BW 211D-40SL',
        'year' => 2024,
        'sn' => '961582391718',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 1823.1,
        'tracking_date' => '2026-09-03',
        'interval' => 500,
        'last_service_val' => 1373.5,
        'last_service_date' => '2026-03-27',
        'target_due_val' => 1873.5,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 1877.5,
        'actual_date' => '2026-09-16',
        'jo_number' => '01/JO-PM SERVICE/BRA/08/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 32,
        'code' => 'SD 10',
        'asset_id' => 'PF-00009', // Canonical in DB matching SN 961582391716
        'source_aid' => 'SD-00002',
        'category' => 'Vibro Compactor',
        'make_model' => 'Vibro Bomag Smooth Drum Compactor BW 211D-40SL',
        'year' => 2024,
        'sn' => '961582391716',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 2123.0,
        'tracking_date' => '2026-09-03',
        'interval' => 500,
        'last_service_val' => 1665.8,
        'last_service_date' => '2026-05-13',
        'target_due_val' => 2165.8,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 2165.7,
        'actual_date' => '2026-09-16',
        'jo_number' => '03/JO-PM SERVICE/BRA/08/2026',
        'mechanic' => 'Tepu, all',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],

    // 2. PROJECT RWI
    [
        'no' => 8,
        'code' => 'RM-03',
        'asset_id' => 'BRR-RM-4103', // New asset to insert
        'source_aid' => 'BRR-RM-4103',
        'category' => 'Soil Stabilizer',
        'make_model' => 'Wirtgen 240X',
        'year' => 2026,
        'sn' => 'WGW014WRVWIA00061',
        'plate' => null,
        'warranty' => 'Warranty',
        'unit' => 'HM',
        'tracking_val' => 38.0,
        'tracking_date' => '2026-09-28',
        'interval' => 50,
        'last_service_val' => null,
        'last_service_date' => null,
        'target_due_val' => 50.0,
        'project' => 'Project RWI',
        'completed' => true,
        'actual_val' => 38.0,
        'actual_date' => '2026-09-28',
        'jo_number' => 'Warranty GMT',
        'mechanic' => 'Mekanik GMT',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi Warranty GMT'
    ],
    [
        'no' => 9,
        'code' => 'CS-01',
        'asset_id' => 'CS-41001',
        'source_aid' => 'CS-41001',
        'category' => 'Cement Spreader',
        'make_model' => 'Powder Binder Spreader XCMG XKC185',
        'year' => 2025,
        'sn' => 'XUG0183KKSMQ0021',
        'plate' => null,
        'warranty' => 'Warranty',
        'unit' => 'HM',
        'tracking_val' => 631.6,
        'tracking_date' => '2026-09-25',
        'interval' => 250,
        'last_service_val' => 493.2,
        'last_service_date' => '2026-07-27',
        'target_due_val' => 743.2,
        'project' => 'Project RWI',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => 'Warranty XCMG',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'Unit belum tercapai HM banyak standby (mutasi plat WT)'
    ],
    [
        'no' => 27,
        'code' => 'DT 50',
        'asset_id' => 'WT-00003', // Canonical matching BK 8121 EO
        'source_aid' => 'WT-41002',
        'category' => 'Water Truck',
        'make_model' => 'Water Tank MITUSBISHI FUSO FM 517 HS',
        'year' => 2019,
        'sn' => null,
        'plate' => 'BK 8121 EO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 45207.0,
        'tracking_date' => '2026-09-15',
        'interval' => 7500,
        'last_service_val' => 3900.0,
        'last_service_date' => '2025-10-25',
        'target_due_val' => 11400.0,
        'project' => 'Project RWI',
        'completed' => true,
        'actual_val' => 4536.0,
        'actual_date' => '2026-09-25',
        'jo_number' => '25/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Darmawan, landa',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi (Monitor service next dgn Hour Meter)'
    ],
    [
        'no' => 28,
        'code' => 'DT 49',
        'asset_id' => 'WT-41005', // New asset to insert
        'source_aid' => 'WT-41005',
        'category' => 'Water Truck',
        'make_model' => 'Water Tank MITUSBISHI FUSO FM 517 HS',
        'year' => 2019,
        'sn' => null,
        'plate' => 'BK 8117 EO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 35826.0,
        'tracking_date' => '2026-09-15',
        'interval' => 7500,
        'last_service_val' => 35907.0,
        'last_service_date' => '2026-09-25',
        'target_due_val' => 43407.0,
        'project' => 'Project RWI',
        'completed' => true,
        'actual_val' => 35907.0,
        'actual_date' => '2026-09-25',
        'jo_number' => '26/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Darmawan, landa',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi (Monitor service next dgn Hour Meter)'
    ],
    [
        'no' => 29,
        'code' => 'DT 48',
        'asset_id' => 'WT-41004', // New asset to insert
        'source_aid' => 'WT-41004',
        'category' => 'Water Truck',
        'make_model' => 'Water Tank MITUSBISHI FUSO FM 517 HS',
        'year' => 2019,
        'sn' => null,
        'plate' => 'BK 8119 EO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 53957.0,
        'tracking_date' => '2026-09-15',
        'interval' => 7500,
        'last_service_val' => 5405.0,
        'last_service_date' => '2026-09-25',
        'target_due_val' => 7500.0,
        'project' => 'Project RWI',
        'completed' => true,
        'actual_val' => 5405.0,
        'actual_date' => '2026-09-25',
        'jo_number' => '27/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Darmawan, landa',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi (Monitor service next dgn Hour Meter)'
    ],
    [
        'no' => 33,
        'code' => 'RM-02',
        'asset_id' => 'RM-41001', // Canonical in DB matching SN RMZ00126
        'source_aid' => 'RM-41002',
        'category' => 'Soil Stabilizer',
        'make_model' => 'Road Reclaimers Caterpillar RM500B',
        'year' => 2025,
        'sn' => 'RMZ00126',
        'plate' => null,
        'warranty' => 'Warranty CAT',
        'unit' => 'HM',
        'tracking_val' => 413.4,
        'tracking_date' => '2026-09-25',
        'interval' => 250,
        'last_service_val' => 251.1,
        'last_service_date' => '2025-06-24',
        'target_due_val' => 501.1,
        'project' => 'Project RWI',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => 'Warranty CAT',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'Belum tercapai HM (plan minggu pertama oktober)'
    ],

    // 3. PROJECT MATTING BOARD
    [
        'no' => 30,
        'code' => 'TMC-03',
        'asset_id' => 'TMC-31002',
        'source_aid' => 'TMC-31002',
        'category' => 'Truck Crane',
        'make_model' => 'TMC MITSUBISHI TRUCK CRANE FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8441 NU',
        'warranty' => 'Warranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 5536.0,
        'tracking_date' => '2026-09-05',
        'interval' => 5000,
        'last_service_val' => 0.0,
        'last_service_date' => null,
        'target_due_val' => 5000.0,
        'project' => 'Project Matting Board',
        'completed' => true,
        'actual_val' => 5536.0,
        'actual_date' => '2026-09-05',
        'jo_number' => 'Warranty Fuso',
        'mechanic' => 'Warranty Fuso',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi Warranty Fuso'
    ],
    [
        'no' => 34,
        'code' => 'TMC-04',
        'asset_id' => 'TMC-31003',
        'source_aid' => 'TMC-31003',
        'category' => 'Truck Crane',
        'make_model' => 'TMC MITSUBISHI TRUCK CRANE FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8449 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 10910.0,
        'tracking_date' => '2026-09-03',
        'interval' => 5000,
        'last_service_val' => 4868.0,
        'last_service_date' => '2026-05-16',
        'target_due_val' => 9868.0,
        'project' => 'Project Matting Board',
        'completed' => true,
        'actual_val' => 10910.0,
        'actual_date' => '2026-09-03',
        'jo_number' => 'Warranty Fuso',
        'mechanic' => 'Warranty Fuso',
        'status_pm' => 'Overdue',
        'note' => 'Realisasi Warranty Fuso'
    ],
    [
        'no' => 35,
        'code' => 'TMC-05',
        'asset_id' => 'TMC-31004',
        'source_aid' => 'TMC-31004',
        'category' => 'Truck Crane',
        'make_model' => 'TMC MITSUBISHI TRUCK CRANE FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8452 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 11213.0,
        'tracking_date' => '2026-09-03',
        'interval' => 5000,
        'last_service_val' => 4447.0,
        'last_service_date' => '2026-04-30',
        'target_due_val' => 9447.0,
        'project' => 'Project Matting Board',
        'completed' => true,
        'actual_val' => 11213.0,
        'actual_date' => '2026-09-03',
        'jo_number' => 'Warranty Fuso',
        'mechanic' => 'Warranty Fuso',
        'status_pm' => 'Overdue',
        'note' => 'Realisasi Warranty Fuso'
    ],
    [
        'no' => 36,
        'code' => 'TMC-06',
        'asset_id' => 'TMC-31005', // New asset to insert
        'source_aid' => 'TMC-31005',
        'category' => 'Truck Crane',
        'make_model' => 'TMC MITSUBISHI TRUCK CRANE FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8531 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 7072.0,
        'tracking_date' => '2026-09-25',
        'interval' => 5000,
        'last_service_val' => 0.0,
        'last_service_date' => null,
        'target_due_val' => 5000.0,
        'project' => 'Project Matting Board',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => 'Warranty Fuso',
        'mechanic' => null,
        'status_pm' => 'Overdue',
        'note' => 'Sudah di FU belum realisasi, Full job (plan minggu pertama oktober)'
    ],
    [
        'no' => 37,
        'code' => 'FBT-02',
        'asset_id' => 'FBT-31002',
        'source_aid' => 'FBT-31002',
        'category' => 'Flat Bed Truck',
        'make_model' => 'FLAT BED TRUCK MITSUBISHI FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8443 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 11958.0,
        'tracking_date' => '2026-09-27',
        'interval' => 5000,
        'last_service_val' => 4883.0,
        'last_service_date' => '2026-04-28',
        'target_due_val' => 9883.0,
        'project' => 'Project Matting Board',
        'completed' => true,
        'actual_val' => 11958.0,
        'actual_date' => '2026-09-27',
        'jo_number' => 'Warranty Fuso',
        'mechanic' => 'Warranty Fuso',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi Warranty Fuso'
    ],

    // 4. LAHAT PROJECT
    [
        'no' => 19,
        'code' => 'DT 001',
        'asset_id' => 'DT-00071', // Canonical in DB matching DT 8126 JE
        'source_aid' => 'ABA-083',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 260 JD',
        'year' => 2017,
        'sn' => null,
        'plate' => 'DT 8126 JE',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 125679.0,
        'tracking_date' => '2026-08-30',
        'interval' => 7500,
        'last_service_val' => 120509.0,
        'last_service_date' => '2026-07-09',
        'target_due_val' => 128009.0,
        'project' => 'Lahat Project',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => '17/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'belum ada info pic lahat , sudah di daily FU (Unit terjual tgl 26 Sept 2026)'
    ],
    [
        'no' => 22,
        'code' => 'DT 102',
        'asset_id' => 'DT-41002', // Canonical in DB matching BM 9503 QO
        'source_aid' => 'ABA-097',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO FM8JN2D-XGJ',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9503 QO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 58600.0,
        'tracking_date' => '2026-08-28',
        'interval' => 10000,
        'last_service_val' => 43458.0,
        'last_service_date' => '2026-02-07',
        'target_due_val' => 53458.0,
        'project' => 'Lahat Project',
        'completed' => true,
        'actual_val' => 58600.0,
        'actual_date' => '2026-08-28',
        'jo_number' => '20/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Afriansyah',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 23,
        'code' => 'DT 111',
        'asset_id' => 'ABA-103', // New asset to insert
        'source_aid' => 'ABA-103',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9832 QO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 80987.0,
        'tracking_date' => '2026-09-15',
        'interval' => 10000,
        'last_service_val' => 63454.0,
        'last_service_date' => '2026-02-11',
        'target_due_val' => 73454.0,
        'project' => 'Lahat Project',
        'completed' => true,
        'actual_val' => 80110.0,
        'actual_date' => '2026-09-14',
        'jo_number' => '21/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Afriansyah',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 24,
        'code' => 'DT 115',
        'asset_id' => 'ABA-108', // New asset to insert
        'source_aid' => 'ABA-108',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9843 QO',
        'warranty' => 'No warranty',
        'unit' => 'KM',
        'tracking_val' => 88664.0,
        'tracking_date' => '2026-08-26',
        'interval' => 10000,
        'last_service_val' => 78021.0,
        'last_service_date' => '2026-05-18',
        'target_due_val' => 88021.0,
        'project' => 'Lahat Project',
        'completed' => true,
        'actual_val' => 89023.0,
        'actual_date' => '2026-09-14',
        'jo_number' => '22/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Afriansyah',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ]
];

$pdo->beginTransaction();

try {
    // ------------------------------------------------------------------------
    // Step 1: Ensure project locations exist in `locations` table
    // ------------------------------------------------------------------------
    echo "[1/6] Ensuring project locations exist...\n";
    $locationMap = [];
    $projectLocations = [
        'WUR EW Project' => ['type' => 'Site Area', 'region' => 'Duri, Riau'],
        'Project RWI' => ['type' => 'Site Area', 'region' => 'Duri, Riau'],
        'Project Matting Board' => ['type' => 'Site Area', 'region' => 'Duri, Riau'],
        'Lahat Project' => ['type' => 'Site Area', 'region' => 'Lahat, Sumsel']
    ];

    $stmtFindLoc = $pdo->prepare("SELECT location_id FROM locations WHERE location_name = :name");
    $stmtInsertLoc = $pdo->prepare("
        INSERT INTO locations (location_name, location_type, region, is_active)
        VALUES (:name, :type, :region, 1)
    ");

    foreach ($projectLocations as $name => $meta) {
        $stmtFindLoc->execute([':name' => $name]);
        $locId = $stmtFindLoc->fetchColumn();
        if (!$locId) {
            $stmtInsertLoc->execute([
                ':name' => $name,
                ':type' => $meta['type'],
                ':region' => $meta['region']
            ]);
            $locId = (int) $pdo->lastInsertId();
            echo "   + Created location: $name (ID: $locId)\n";
        }
        $locationMap[$name] = (int) $locId;
    }

    // ------------------------------------------------------------------------
    // Step 2: Ensure 7 missing assets are registered in `assets`
    // ------------------------------------------------------------------------
    echo "\n[2/6] Ensuring 7 missing assets are registered in `assets`...\n";
    $stmtCheckAsset = $pdo->prepare("SELECT asset_id FROM assets WHERE asset_id = :id");
    $stmtInsertAsset = $pdo->prepare("
        INSERT INTO assets (
            asset_id, asset_code, hull_number, alias_name, license_plate, serial_number,
            type, category, make_model, year_manufacture, status,
            current_location_id, last_hm_km, is_active
        ) VALUES (
            :id, :code, :hull, :alias, :plate, :sn,
            :type, :category, :make_model, :year, 'OPERATING',
            :loc_id, :hm, 1
        )
    ");

    $validCategories = [
        'Excavator', 'Bulldozer', 'Dump Truck', 'Motor Grader',
        'Vibro Compactor', 'Water Truck', 'Prime Mover', 'Light Vehicle',
        'Reclaimer Spreader', 'Trado', 'Other'
    ];
    $categoryMap = [
        'Soil Stabilizer' => 'Reclaimer Spreader',
        'Cement Spreader' => 'Reclaimer Spreader',
        'Truck Crane' => 'Other',
        'Flat Bed Truck' => 'Other'
    ];

    $insertedAssetsCount = 0;
    foreach ($septemberUnits as $item) {
        $aid = $item['asset_id'];
        $stmtCheckAsset->execute([':id' => $aid]);
        if (!$stmtCheckAsset->fetchColumn()) {
            $locId = $locationMap[$item['project']] ?? 15;
            $rawCat = $item['category'];
            $cleanCat = $categoryMap[$rawCat] ?? (in_array($rawCat, $validCategories, true) ? $rawCat : 'Other');
            $assetType = in_array($cleanCat, ['Dump Truck', 'Prime Mover', 'Water Truck', 'Trado'], true) ? $cleanCat : 'Heavy Equipment';
            $stmtInsertAsset->execute([
                ':id' => $aid,
                ':code' => $aid,
                ':hull' => $item['code'],
                ':alias' => $item['code'],
                ':plate' => $item['plate'],
                ':sn' => $item['sn'],
                ':type' => $assetType,
                ':category' => $cleanCat,
                ':make_model' => $item['make_model'],
                ':year' => $item['year'],
                ':loc_id' => $locId,
                ':hm' => $item['tracking_val']
            ]);
            $insertedAssetsCount++;
            echo "   + Registered new asset: {$item['code']} -> ID: $aid ({$item['make_model']})\n";
        }
    }
    echo "   Total new assets created: $insertedAssetsCount\n";

    // ------------------------------------------------------------------------
    // Step 3: Update live telemetries in `assets`
    // ------------------------------------------------------------------------
    echo "\n[3/6] Updating live telemetries and scheduling in `assets`...\n";
    $stmtUpdateAsset = $pdo->prepare("
        UPDATE assets SET
            hull_number = COALESCE(NULLIF(hull_number, ''), :hull),
            alias_name = COALESCE(NULLIF(alias_name, ''), :alias),
            license_plate = COALESCE(NULLIF(license_plate, ''), :plate),
            serial_number = COALESCE(NULLIF(serial_number, ''), :sn),
            last_hm_km = :last_hm_km,
            last_service_hm_km = :last_service_hm_km,
            last_service_date = :last_service_date,
            service_interval_val = :service_interval_val,
            service_interval_unit = :service_interval_unit,
            daily_usage_coefficient = COALESCE(daily_usage_coefficient, :daily_rate),
            next_service_due_hm_km = :next_service_due_hm_km,
            next_service_estimated_date = :next_service_estimated_date,
            current_location_id = :loc_id
        WHERE asset_id = :asset_id
    ");

    foreach ($septemberUnits as $item) {
        $aid = $item['asset_id'];
        $dailyRate = $item['unit'] === 'KM' ? 150.00 : 7.00;
        $remaining = $item['target_due_val'] - $item['tracking_val'];
        $daysToNext = $remaining <= 0 ? 0 : (int) ceil($remaining / $dailyRate);
        $estDate = date('Y-m-d', strtotime('+' . $daysToNext . ' days'));
        $locId = $locationMap[$item['project']] ?? 15;

        // If completed in Sept, the last service date/val becomes the actual September values
        $effLastServiceVal = $item['completed'] && $item['actual_val'] !== null ? $item['actual_val'] : $item['last_service_val'];
        $effLastServiceDate = $item['completed'] && $item['actual_date'] !== null ? $item['actual_date'] : $item['last_service_date'];

        $stmtUpdateAsset->execute([
            ':hull' => $item['code'],
            ':alias' => $item['code'],
            ':plate' => $item['plate'],
            ':sn' => $item['sn'],
            ':last_hm_km' => $item['actual_val'] ?? $item['tracking_val'],
            ':last_service_hm_km' => $effLastServiceVal,
            ':last_service_date' => $effLastServiceDate ? $effLastServiceDate . ' 08:00:00' : null,
            ':service_interval_val' => $item['interval'],
            ':service_interval_unit' => $item['unit'],
            ':daily_rate' => $dailyRate,
            ':next_service_due_hm_km' => $item['completed'] ? ($item['actual_val'] ?? $item['tracking_val']) + $item['interval'] : $item['target_due_val'],
            ':next_service_estimated_date' => $estDate,
            ':loc_id' => $locId,
            ':asset_id' => $aid
        ]);
    }
    echo "   Updated telemetry for all 37 assets.\n";

    // ------------------------------------------------------------------------
    // Step 4: Ingest 32 realized records into `asset_service_history`
    // ------------------------------------------------------------------------
    echo "\n[4/6] Ingesting completed PM service records into `asset_service_history`...\n";
    $stmtCheckHist = $pdo->prepare("
        SELECT COUNT(*) FROM asset_service_history 
        WHERE asset_id = :id AND (jo_report_number = :jo OR (DATE(service_date) = :dt AND ABS(service_hm_km - :hm) < 0.1))
    ");
    $stmtNextSeq = $pdo->prepare("SELECT COALESCE(MAX(service_sequence), 0) + 1 FROM asset_service_history WHERE asset_id = :id");
    $stmtInsertHist = $pdo->prepare("
        INSERT INTO asset_service_history (
            asset_id, service_sequence, service_date, service_hm_km,
            service_type, mechanic_pic, jo_report_number, notes
        ) VALUES (
            :id, :seq, :dt, :hm, :type, :pic, :jo, :notes
        )
    ");

    $ingestedHistCount = 0;
    foreach ($septemberUnits as $item) {
        if (!$item['completed']) {
            continue; // 5 pending units are not logged as service history
        }

        $aid = $item['asset_id'];
        $sDate = $item['actual_date'] ?? '2026-09-30';
        $sHM = $item['actual_val'] ?? $item['tracking_val'];
        $joNum = $item['jo_number'];

        $stmtCheckHist->execute([
            ':id' => $aid,
            ':jo' => $joNum,
            ':dt' => $sDate,
            ':hm' => $sHM
        ]);

        if ((int) $stmtCheckHist->fetchColumn() === 0) {
            $stmtNextSeq->execute([':id' => $aid]);
            $nextSeq = (int) $stmtNextSeq->fetchColumn();

            $serviceType = 'Periodic Maintenance ' . $item['interval'] . ' ' . $item['unit'];
            $stmtInsertHist->execute([
                ':id' => $aid,
                ':seq' => $nextSeq,
                ':dt' => $sDate . ' 08:00:00',
                ':hm' => $sHM,
                ':type' => $serviceType,
                ':pic' => $item['mechanic'] ?? 'Tim Mekanik',
                ':jo' => $joNum,
                ':notes' => $item['note'] . " (" . $item['status_pm'] . ")"
            ]);
            $ingestedHistCount++;
        }
    }
    echo "   Ingested $ingestedHistCount new service history records.\n";

    // ------------------------------------------------------------------------
    // Step 5: Ingest / synchronize Work Orders in `work_orders`
    // ------------------------------------------------------------------------
    echo "\n[5/6] Synchronizing PM Work Orders in `work_orders`...\n";
    $stmtCheckWo = $pdo->prepare("SELECT wo_id FROM work_orders WHERE wo_id = :wo_id");
    $stmtInsertWo = $pdo->prepare("
        INSERT INTO work_orders (
            wo_id, asset_id, hull_number, equipment_type, current_hm_km,
            trouble_at, location_id, raw_location, issue_description,
            is_downtime, status, priority, assigned_mechanic,
            maintenance_type, reported_at, repair_started_at, closed_at,
            repair_action
        ) VALUES (
            :wo_id, :asset_id, :hull, :type, :hm,
            :trouble_at, :loc_id, :raw_loc, :issue,
            1, :status, 'Normal', :mechanic,
            'Preventive', :reported_at, :started_at, :closed_at,
            :repair_action
        )
    ");

    $ingestedWoCount = 0;
    foreach ($septemberUnits as $item) {
        $aid = $item['asset_id'];
        $locId = $locationMap[$item['project']] ?? 15;
        
        // Generate uniform unique WO ID from JO
        $woId = 'WO-PM-' . sprintf('%02d', $item['no']) . '-092026';
        $stmtCheckWo->execute([':wo_id' => $woId]);
        
        if (!$stmtCheckWo->fetchColumn()) {
            $isDone = $item['completed'];
            $woStatus = $isDone ? 'Closed' : 'Open';
            $tDate = ($item['actual_date'] ?? $item['tracking_date']) . ' 08:00:00';
            $cDate = $isDone ? ($item['actual_date'] . ' 17:00:00') : null;

            $stmtInsertWo->execute([
                ':wo_id' => $woId,
                ':asset_id' => $aid,
                ':hull' => $item['code'],
                ':type' => $item['category'],
                ':hm' => $item['actual_val'] ?? $item['tracking_val'],
                ':trouble_at' => $tDate,
                ':loc_id' => $locId,
                ':raw_loc' => $item['project'],
                ':issue' => "PM Service {$item['interval']} {$item['unit']} - JO: {$item['jo_number']}",
                ':status' => $woStatus,
                ':mechanic' => $item['mechanic'] ?? 'Belum ada PIC',
                ':reported_at' => $tDate,
                ':started_at' => $tDate,
                ':closed_at' => $cDate,
                ':repair_action' => $item['note'] . " (" . $item['status_pm'] . ")"
            ]);
            $ingestedWoCount++;
        }
    }
    echo "   Synchronized $ingestedWoCount Work Orders.\n";

    // ------------------------------------------------------------------------
    // Step 6: Upsert PM monitoring records in `pm_plans`
    // ------------------------------------------------------------------------
    echo "\n[6/6] Upserting active PM plans in `pm_plans`...\n";
    $stmtFindPm = $pdo->prepare("SELECT pm_plan_id FROM pm_plans WHERE asset_id = :id ORDER BY pm_plan_id DESC LIMIT 1");
    $stmtUpdatePm = $pdo->prepare("
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
        WHERE pm_plan_id = :pm_plan_id
    ");
    $stmtInsertPm = $pdo->prepare("
        INSERT INTO pm_plans (
            asset_id, interval_hm, current_smr, last_service_hm, last_service_date,
            target_due_hm, variance_hm, status, warranty_status, planner_note
        ) VALUES (
            :asset_id, :interval_hm, :current_smr, :last_service_hm, :last_service_date,
            :target_due_hm, :variance_hm, :status, :warranty_status, :planner_note
        )
    ");

    $upsertedPmCount = 0;
    foreach ($septemberUnits as $item) {
        $aid = $item['asset_id'];
        $variance = round($item['tracking_val'] - $item['target_due_val'], 2);
        $warningWindow = $item['unit'] === 'KM' ? 500.0 : 50.0;

        if ($item['completed']) {
            $pmStatus = 'COMPLETED';
        } elseif ($variance > 0) {
            $pmStatus = 'OVERDUE';
        } elseif (abs($variance) <= $warningWindow) {
            $pmStatus = 'DUE_SOON';
        } else {
            $pmStatus = 'PLANNED';
        }

        $effLastServiceVal = $item['completed'] && $item['actual_val'] !== null ? $item['actual_val'] : $item['last_service_val'];
        $effLastServiceDate = $item['completed'] && $item['actual_date'] !== null ? $item['actual_date'] : $item['last_service_date'];

        $stmtFindPm->execute([':id' => $aid]);
        $pmPlanId = $stmtFindPm->fetchColumn();

        if ($pmPlanId) {
            $stmtUpdatePm->execute([
                ':interval_hm' => $item['interval'],
                ':current_smr' => $item['actual_val'] ?? $item['tracking_val'],
                ':last_service_hm' => $effLastServiceVal,
                ':last_service_date' => $effLastServiceDate,
                ':target_due_hm' => $item['target_due_val'],
                ':variance_hm' => $variance,
                ':status' => $pmStatus,
                ':warranty_status' => $item['warranty'],
                ':planner_note' => $item['note'],
                ':pm_plan_id' => $pmPlanId
            ]);
        } else {
            $stmtInsertPm->execute([
                ':asset_id' => $aid,
                ':interval_hm' => $item['interval'],
                ':current_smr' => $item['actual_val'] ?? $item['tracking_val'],
                ':last_service_hm' => $effLastServiceVal,
                ':last_service_date' => $effLastServiceDate,
                ':target_due_hm' => $item['target_due_val'],
                ':variance_hm' => $variance,
                ':status' => $pmStatus,
                ':warranty_status' => $item['warranty'],
                ':planner_note' => $item['note']
            ]);
        }
        $upsertedPmCount++;
    }
    echo "   Upserted $upsertedPmCount PM Plan monitoring records.\n";

    // ------------------------------------------------------------------------
    // Step 7: Record Migration in app_data_migrations
    // ------------------------------------------------------------------------
    $stmtRegMigration = $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES (:k, NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtRegMigration->execute([':k' => $migrationKey]);

    $pdo->commit();
    echo "\n============================================================================\n";
    echo " SUCCESS: Batch 9 September 2026 PM Plan successfully injected!\n";
    echo "============================================================================\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
