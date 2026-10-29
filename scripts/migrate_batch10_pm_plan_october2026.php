<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 10 - Preventive Maintenance Plan October 2026 Progress
 *
 * Synchronizes and ingests all 41 units from material/PREVENTIVE-MAINTENANCE/Laporan_Progress_PM_Service_per_29_Oktober_2026.md:
 * 1. Ensures 12 missing assets are registered in `assets` with valid categories.
 * 2. Remaps 14 alias assets to canonical DB IDs.
 * 3. Updates asset telemetries and marks DT 001 as sold/archived in `archived_items`.
 * 4. Ingests 31 completed service records into `asset_service_history` with jo_report_number and mechanic_pic.
 * 5. Generates/synchronizes 41 Work Orders in `work_orders` (WO-PM-01-102026 to WO-PM-41-102026).
 * 6. Upserts all 41 active PM monitoring states into `pm_plans`.
 * 7. Registers BATCH_10_PM_PLAN_OCTOBER_2026 into `app_data_migrations`.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 10 PM PLAN OCTOBER 2026 INGESTION\n";
echo "============================================================================\n\n";

$migrationKey = 'BATCH_10_PM_PLAN_OCTOBER_2026';

$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
$alreadyApplied = (int) $stmtCheck->fetchColumn() > 0;

$force = in_array('--force', $argv, true);
if ($alreadyApplied && !$force) {
    echo "[!] Migration '$migrationKey' has already been applied. Re-running in idempotent refresh mode.\n\n";
}

$octoberUnits = [
    [
        'no' => 1,
        'code' => 'PF 02',
        'asset_id' => 'PF-00004', // Canonical DB asset
        'source_aid' => 'PF-41002',
        'category' => 'Vibro Compactor',
        'make_model' => 'XCMG Padfoot XS205PD',
        'year' => 2022,
        'sn' => 'XUG02051KSJE02502',
        'plate' => null,
        'warranty' => 'Warranty xcmg',
        'unit' => 'HM',
        'tracking_val' => 652.0,
        'tracking_date' => '2026-10-12',
        'interval' => 250,
        'last_service_val' => 488.2,
        'last_service_date' => '2026-07-27',
        'target_due_val' => 738.2,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 652.0,
        'actual_date' => '2026-10-13',
        'jo_number' => 'Warranty xcmg',
        'mechanic' => 'Warranty xcmg',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 2,
        'code' => 'PF 010',
        'asset_id' => 'BRR-PF-4103', // New asset
        'source_aid' => 'BRR-PF-4103',
        'category' => 'Vibro Compactor',
        'make_model' => 'HAMM VBR Compactor HC 208 D',
        'year' => 2026,
        'sn' => 'WCHOH291 PHAA00344',
        'plate' => null,
        'warranty' => 'Warranty GMT',
        'unit' => 'HM',
        'tracking_val' => 107.5,
        'tracking_date' => '2026-10-01',
        'interval' => 50,
        'last_service_val' => 50.0,
        'last_service_date' => null,
        'target_due_val' => 50.0,
        'project' => 'Project RWI',
        'completed' => true,
        'actual_val' => 107.5,
        'actual_date' => '2026-10-01',
        'jo_number' => 'Warranty GMT',
        'mechanic' => 'Warranty GMT',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 3,
        'code' => 'GR 5',
        'asset_id' => 'MG-00004',
        'source_aid' => 'MG-00004',
        'category' => 'Motor Grader',
        'make_model' => 'MOTOR GRADER XMCG GR135 MAX',
        'year' => 2025,
        'sn' => 'PRDB00108',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 3039.0,
        'tracking_date' => '2026-10-09',
        'interval' => 500,
        'last_service_val' => 2547.6,
        'last_service_date' => '2026-07-22',
        'target_due_val' => 3047.6,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 3097.1,
        'actual_date' => '2026-10-19',
        'jo_number' => '01/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 4,
        'code' => 'GR 8',
        'asset_id' => 'MG-4106', // New asset
        'source_aid' => 'MG-4106',
        'category' => 'Motor Grader',
        'make_model' => 'MOTOR GRADER KOMATSU GD535-5',
        'year' => 2017,
        'sn' => '1017',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 8587.0,
        'tracking_date' => '2026-10-05',
        'interval' => 500,
        'last_service_val' => 8062.0,
        'last_service_date' => '2026-04-22',
        'target_due_val' => 8562.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 8587.3,
        'actual_date' => '2026-10-05',
        'jo_number' => '02/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 5,
        'code' => 'EXCA-29',
        'asset_id' => 'EXC-00009',
        'source_aid' => 'EXC-00009',
        'category' => 'Excavator',
        'make_model' => 'Excavator Komatsu PC200-10M0 CE',
        'year' => 2025,
        'sn' => 'DBCH2941',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 1859.0,
        'tracking_date' => '2026-10-27',
        'interval' => 500,
        'last_service_val' => 1451.3,
        'last_service_date' => '2026-07-03',
        'target_due_val' => 1951.3,
        'project' => 'WUR EW Project',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => '03/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'plan di minggu ke 4 awal (Ikuti Scehdule / Tercapai Lebih Dulu)'
    ],
    [
        'no' => 6,
        'code' => 'Exca 07',
        'asset_id' => 'EXC-00007', // Canonical DB asset
        'source_aid' => 'EXC-1114',
        'category' => 'Excavator',
        'make_model' => 'Excavator Komatsu PC 200-10 MOCE',
        'year' => 2022,
        'sn' => 'DBCHO302',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 9880.3,
        'tracking_date' => '2026-10-01',
        'interval' => 500,
        'last_service_val' => 9392.1,
        'last_service_date' => '2026-06-22',
        'target_due_val' => 9892.1,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 9880.3,
        'actual_date' => '2026-10-01',
        'jo_number' => '04/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 7,
        'code' => 'Exca BRA-23',
        'asset_id' => 'EXC-00012',
        'source_aid' => 'EXC-00012',
        'category' => 'Excavator',
        'make_model' => 'EXCA PC 200-10 MOCE',
        'year' => 2024,
        'sn' => 'DBCH1801',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 6596.0,
        'tracking_date' => '2026-10-13',
        'interval' => 500,
        'last_service_val' => 6154.7,
        'last_service_date' => '2026-07-31',
        'target_due_val' => 6654.7,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 6643.8,
        'actual_date' => '2026-10-21',
        'jo_number' => '05/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 8,
        'code' => 'CR02',
        'asset_id' => 'CC-00002', // New asset
        'source_aid' => 'CC-00002',
        'category' => 'Other',
        'make_model' => 'CRWLER CRANE XCMG-XGC35HD',
        'year' => 2025,
        'sn' => 'MHMFN527HAK003357',
        'plate' => null,
        'warranty' => 'No Warranty',
        'unit' => 'HM',
        'tracking_val' => 850.0,
        'tracking_date' => '2026-10-26',
        'interval' => 250,
        'last_service_val' => 651.8,
        'last_service_date' => '2026-06-22',
        'target_due_val' => 901.8,
        'project' => 'Project Matting Board',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => '29/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'part ada (plan di minggu ke 4)'
    ],
    [
        'no' => 9,
        'code' => 'CS-02',
        'asset_id' => 'CS-41002', // New asset
        'source_aid' => 'CS-41002',
        'category' => 'Reclaimer Spreader',
        'make_model' => 'Powder Binder Spreader XCMG XKC183',
        'year' => 2025,
        'sn' => 'CSD-02',
        'plate' => null,
        'warranty' => 'Warranty',
        'unit' => 'HM',
        'tracking_val' => 750.0,
        'tracking_date' => '2026-10-05',
        'interval' => 250,
        'last_service_val' => 536.9,
        'last_service_date' => '2026-08-09',
        'target_due_val' => 786.9,
        'project' => 'Project RWI',
        'completed' => true,
        'actual_val' => 754.0,
        'actual_date' => '2026-10-06',
        'jo_number' => 'Warranty',
        'mechanic' => 'Warranty',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 10,
        'code' => 'WL 02',
        'asset_id' => 'WL-31001', // New asset
        'source_aid' => 'WL-31001',
        'category' => 'Other',
        'make_model' => 'WHEEL LOADER SEM 655D',
        'year' => 2025,
        'sn' => 'AS5512759',
        'plate' => null,
        'warranty' => 'Warranty',
        'unit' => 'HM',
        'tracking_val' => 1061.50,
        'tracking_date' => '2026-10-13',
        'interval' => 500,
        'last_service_val' => 559.2,
        'last_service_date' => '2026-06-24',
        'target_due_val' => 1059.2,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 1067.0,
        'actual_date' => '2026-10-14',
        'jo_number' => 'Warranty',
        'mechanic' => 'Warranty',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 11,
        'code' => 'DT 064',
        'asset_id' => 'DT-00010',
        'source_aid' => 'DT-00010',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9117 ZYT',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 49674.0,
        'tracking_date' => '2026-10-09',
        'interval' => 10000,
        'last_service_val' => 40346.0,
        'last_service_date' => '2026-07-10',
        'target_due_val' => 50346.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 49728.0,
        'actual_date' => '2026-10-09',
        'jo_number' => '06/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 12,
        'code' => 'DT 074',
        'asset_id' => 'DT-00020', // New asset
        'source_aid' => 'DT-00020',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9129 ZYT',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 49432.0,
        'tracking_date' => '2026-10-01',
        'interval' => 10000,
        'last_service_val' => 38782.0,
        'last_service_date' => '2026-05-21',
        'target_due_val' => 48782.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 49432.0,
        'actual_date' => '2026-10-01',
        'jo_number' => '07/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 13,
        'code' => 'DT 082',
        'asset_id' => 'DT-00028',
        'source_aid' => 'DT-00028',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9137 ZYT',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 49939.0,
        'tracking_date' => '2026-10-06',
        'interval' => 10000,
        'last_service_val' => 40593.0,
        'last_service_date' => '2026-06-19',
        'target_due_val' => 50593.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 49939.0,
        'actual_date' => '2026-10-06',
        'jo_number' => '08/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 14,
        'code' => 'DT 084',
        'asset_id' => 'DT-00030',
        'source_aid' => 'DT-00030',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2024,
        'sn' => null,
        'plate' => 'B 9139 ZYT',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 49032.0,
        'tracking_date' => '2026-10-05',
        'interval' => 10000,
        'last_service_val' => 39163.0,
        'last_service_date' => '2026-06-18',
        'target_due_val' => 49163.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 49032.0,
        'actual_date' => '2026-10-05',
        'jo_number' => '09/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 15,
        'code' => 'DT 009',
        'asset_id' => 'DT-YX-00009', // Canonical DB asset
        'source_aid' => 'DT-1148',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 260 JD',
        'year' => 2018,
        'sn' => null,
        'plate' => 'B 9115 PYX',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 315740.0,
        'tracking_date' => '2026-10-09',
        'interval' => 7500,
        'last_service_val' => 309220.0,
        'last_service_date' => '2026-05-12',
        'target_due_val' => 316720.0,
        'project' => 'WUR EW Project',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => '010/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'Plan di minggu ke 4, unit long standby (Ikuti Scehdule / Tercapai Lebih Dulu)'
    ],
    [
        'no' => 16,
        'code' => 'DT 018',
        'asset_id' => 'DT-00054',
        'source_aid' => 'DT-00054',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 260 JD',
        'year' => 2018,
        'sn' => null,
        'plate' => 'B 9683 KYW',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 197888.0,
        'tracking_date' => '2026-10-20',
        'interval' => 7500,
        'last_service_val' => 189856.0,
        'last_service_date' => '2026-06-30',
        'target_due_val' => 197356.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 197888.0,
        'actual_date' => '2026-10-21',
        'jo_number' => '011/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 17,
        'code' => 'DT 031',
        'asset_id' => 'DT-00058',
        'source_aid' => 'DT-00058',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 260 JD',
        'year' => 2018,
        'sn' => null,
        'plate' => 'B 9973 BIS',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 142295.0,
        'tracking_date' => '2026-10-20',
        'interval' => 7500,
        'last_service_val' => 131770.0,
        'last_service_date' => '2026-05-28',
        'target_due_val' => 139270.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 142295.0,
        'actual_date' => '2026-10-26',
        'jo_number' => '012/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'overdue',
        'note' => 'Realisasi'
    ],
    [
        'no' => 18,
        'code' => 'DT 053',
        'asset_id' => 'DT-00065',
        'source_aid' => 'DT-00065',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 260 JD',
        'year' => 2019,
        'sn' => null,
        'plate' => 'BG 8641 NI',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 83666.0,
        'tracking_date' => '2026-10-26',
        'interval' => 7500,
        'last_service_val' => 76985.0,
        'last_service_date' => '2026-06-23',
        'target_due_val' => 84485.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 83705.0,
        'actual_date' => '2026-10-28',
        'jo_number' => '013/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 19,
        'code' => 'DT 098',
        'asset_id' => 'DT-04001', // Canonical DB asset
        'source_aid' => 'ABA-071',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9174 NU',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 104025.0,
        'tracking_date' => '2026-10-05',
        'interval' => 10000,
        'last_service_val' => 93917.0,
        'last_service_date' => '2026-07-23',
        'target_due_val' => 103917.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 104025.0,
        'actual_date' => '2026-10-05',
        'jo_number' => '014/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 20,
        'code' => 'DT 097',
        'asset_id' => 'DT-04045', // Canonical DB asset
        'source_aid' => 'ABA-072',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9682 JO',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 110029.0,
        'tracking_date' => '2026-10-03',
        'interval' => 10000,
        'last_service_val' => 99223.0,
        'last_service_date' => '2026-07-23',
        'target_due_val' => 109223.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 110029.0,
        'actual_date' => '2026-10-03',
        'jo_number' => '015/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 21,
        'code' => 'DT 091',
        'asset_id' => 'DT-04038', // Canonical DB asset
        'source_aid' => 'ABA-073',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9296 JO',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 109841.0,
        'tracking_date' => '2026-10-03',
        'interval' => 10000,
        'last_service_val' => 98414.0,
        'last_service_date' => '2026-06-23',
        'target_due_val' => 108414.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 109841.0,
        'actual_date' => '2026-10-03',
        'jo_number' => '016/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'overdue',
        'note' => 'Realisasi'
    ],
    [
        'no' => 22,
        'code' => 'DT 096',
        'asset_id' => 'DT-04044', // Canonical DB asset
        'source_aid' => 'ABA-076',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9244 NU',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 85994.0,
        'tracking_date' => '2026-07-10',
        'interval' => 10000,
        'last_service_val' => 84723.0,
        'last_service_date' => '2026-06-29',
        'target_due_val' => 94723.0,
        'project' => 'Lahat Project',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => '017/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'Terinfo Unit Ini LAKA di Lahat (Unit Alami LAKA DI lahat)'
    ],
    [
        'no' => 23,
        'code' => 'DT 109',
        'asset_id' => 'ABA-098', // New asset
        'source_aid' => 'ABA-098',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9489 QO',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 76597.0,
        'tracking_date' => '2026-10-05',
        'interval' => 10000,
        'last_service_val' => 65808.0,
        'last_service_date' => '2025-08-08',
        'target_due_val' => 75808.0,
        'project' => 'Lahat Project',
        'completed' => true,
        'actual_val' => 76597.0,
        'actual_date' => '2026-10-05',
        'jo_number' => '018/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'overdue',
        'note' => 'Realisasi'
    ],
    [
        'no' => 24,
        'code' => 'DT 112',
        'asset_id' => 'ABA-105', // New asset
        'source_aid' => 'ABA-105',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9840 QO',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 72970.0,
        'tracking_date' => '2026-10-03',
        'interval' => 10000,
        'last_service_val' => 62535.0,
        'last_service_date' => '2026-07-26',
        'target_due_val' => 72535.0,
        'project' => 'Lahat Project',
        'completed' => true,
        'actual_val' => 72970.0,
        'actual_date' => '2026-10-03',
        'jo_number' => '019/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 25,
        'code' => 'DT 118',
        'asset_id' => 'ABA-111', // New asset
        'source_aid' => 'ABA-111',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9842 QO',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 84658.0,
        'tracking_date' => '2026-10-05',
        'interval' => 10000,
        'last_service_val' => 75609.0,
        'last_service_date' => '2026-07-26',
        'target_due_val' => 85609.0,
        'project' => 'Lahat Project',
        'completed' => true,
        'actual_val' => 84658.0,
        'actual_date' => '2026-10-05',
        'jo_number' => '020/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi'
    ],
    [
        'no' => 26,
        'code' => 'DT 127',
        'asset_id' => 'DT-00187', // Canonical DB asset
        'source_aid' => 'DT-1457',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck HINO RANGER-FM 280 JD',
        'year' => 2022,
        'sn' => null,
        'plate' => 'BM 9680 JO',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 89266.0,
        'tracking_date' => '2026-10-22',
        'interval' => 10000,
        'last_service_val' => 80000.0,
        'last_service_date' => '2025-10-04',
        'target_due_val' => 90000.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 89425.0,
        'actual_date' => '2026-10-24',
        'jo_number' => '021/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 27,
        'code' => 'PM 03',
        'asset_id' => 'FBT-41001', // Canonical DB asset matching BM 8371 CU
        'source_aid' => 'PM-41001',
        'category' => 'Trado',
        'make_model' => 'Prime Mover FM2PK1B-MGJ HINO',
        'year' => 2019,
        'sn' => null,
        'plate' => 'BM 8371 CU',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 385010.0,
        'tracking_date' => '2026-09-25',
        'interval' => 7500,
        'last_service_val' => 376117.0,
        'last_service_date' => '2026-06-09',
        'target_due_val' => 383617.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 385010.0,
        'actual_date' => '2026-09-26',
        'jo_number' => '022/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'overdue',
        'note' => 'Realisasi'
    ],
    [
        'no' => 28,
        'code' => 'TMC-02',
        'asset_id' => 'TMC-31001', // New asset
        'source_aid' => 'TMC-31001',
        'category' => 'Other',
        'make_model' => 'TMC MITSUBISHI TRUCK CRANE FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8440 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 10347.0,
        'tracking_date' => '2026-10-25',
        'interval' => 5000,
        'last_service_val' => 4957.0,
        'last_service_date' => '2026-04-28',
        'target_due_val' => 9957.0,
        'project' => 'Project Matting Board',
        'completed' => true,
        'actual_val' => 10347.0,
        'actual_date' => '2026-10-25',
        'jo_number' => 'Waranty Fuso',
        'mechanic' => 'Waranty Fuso',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 29,
        'code' => 'FBT-01',
        'asset_id' => 'FBT-31001',
        'source_aid' => 'FBT-31001',
        'category' => 'Other',
        'make_model' => 'FLAT BED TRUCK MITSUBISHI FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8442 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 10122.0,
        'tracking_date' => '2026-10-26',
        'interval' => 5000,
        'last_service_val' => 4727.0,
        'last_service_date' => '2026-07-17',
        'target_due_val' => 9727.0,
        'project' => 'Project Matting Board',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => 'Waranty Fuso',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'plan minggu ke 4 , masih belum sempat full job (Ikuti Scehdule / Tercapai Lebih Dulu)'
    ],
    [
        'no' => 30,
        'code' => 'FBT-04',
        'asset_id' => 'FBT-31004',
        'source_aid' => 'FBT-31004',
        'category' => 'Other',
        'make_model' => 'FLAT BED TRUCK MITSUBISHI FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8451 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 11440.0,
        'tracking_date' => '2026-10-26',
        'interval' => 5000,
        'last_service_val' => 5215.0,
        'last_service_date' => '2026-06-26',
        'target_due_val' => 10215.0,
        'project' => 'Project Matting Board',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => 'Waranty Fuso',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'plan minggu ke 4 , masih belum sempat full job (Ikuti Scehdule / Tercapai Lebih Dulu)'
    ],
    [
        'no' => 31,
        'code' => 'FBT-05',
        'asset_id' => 'FBT-31005', // New asset
        'source_aid' => 'FBT-31005',
        'category' => 'Other',
        'make_model' => 'FLAT BED TRUCK MITSUBISHI FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8532 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 11027.0,
        'tracking_date' => '2026-10-22',
        'interval' => 5000,
        'last_service_val' => 5335.0,
        'last_service_date' => '2026-07-09',
        'target_due_val' => 10335.0,
        'project' => 'Project Matting Board',
        'completed' => true,
        'actual_val' => 11027.0,
        'actual_date' => '2026-10-22',
        'jo_number' => 'Waranty Fuso',
        'mechanic' => 'Waranty Fuso',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 32,
        'code' => 'FBT-06',
        'asset_id' => 'FBT-31006', // New asset
        'source_aid' => 'FBT-31006',
        'category' => 'Other',
        'make_model' => 'FLAT BED TRUCK MITSUBISHI FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8534 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 11772.0,
        'tracking_date' => '2026-10-22',
        'interval' => 5000,
        'last_service_val' => 5911.0,
        'last_service_date' => '2026-07-17',
        'target_due_val' => 10911.0,
        'project' => 'Project Matting Board',
        'completed' => true,
        'actual_val' => 11772.0,
        'actual_date' => '2026-10-22',
        'jo_number' => 'Waranty Fuso',
        'mechanic' => 'Waranty Fuso',
        'status_pm' => 'overdue',
        'note' => 'Realisasi'
    ],
    [
        'no' => 33,
        'code' => 'TR 4',
        'asset_id' => 'LV-00003',
        'source_aid' => 'LV-00003',
        'category' => 'Light Vehicle',
        'make_model' => 'DOUBLE CABIN MITSUBISHI TRITON',
        'year' => 2024,
        'sn' => null,
        'plate' => 'BM 8777 QJ',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 101848.0,
        'tracking_date' => '2026-09-25',
        'interval' => 10000,
        'last_service_val' => 90607.0,
        'last_service_date' => '2026-05-19',
        'target_due_val' => 100607.0,
        'project' => 'WUR EW Project',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => '023/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'overdue',
        'note' => 'part belum ada (Do Soon Action Service)'
    ],
    [
        'no' => 34,
        'code' => 'TR12',
        'asset_id' => 'LV-00006',
        'source_aid' => 'LV-00006',
        'category' => 'Light Vehicle',
        'make_model' => 'DOUBLE CABIN MITSUBISHI TRITON',
        'year' => 2024,
        'sn' => null,
        'plate' => 'BM 8429 QK',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 81970.0,
        'tracking_date' => '2026-10-01',
        'interval' => 10000,
        'last_service_val' => 70723.0,
        'last_service_date' => '2026-07-09',
        'target_due_val' => 80723.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 81970.0,
        'actual_date' => '2026-10-01',
        'jo_number' => '024/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'overdue',
        'note' => 'Realisasi'
    ],
    [
        'no' => 35,
        'code' => 'HI 23',
        'asset_id' => 'LV-00030', // Canonical DB asset matching B 9123 ZBA
        'source_aid' => 'LV-41002',
        'category' => 'Light Vehicle',
        'make_model' => 'Light Vehicle TOYOTA HILUX',
        'year' => 2022,
        'sn' => null,
        'plate' => 'B 9123 ZBA',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 129882.0,
        'tracking_date' => '2026-09-28',
        'interval' => 10000,
        'last_service_val' => 119298.0,
        'last_service_date' => '2026-05-28',
        'target_due_val' => 129298.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 129882.0,
        'actual_date' => '2026-09-28',
        'jo_number' => '025/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Tepat waktu',
        'note' => 'Realisasi'
    ],
    [
        'no' => 36,
        'code' => 'HI 31',
        'asset_id' => 'LV-00064', // Canonical DB asset matching B 9113 ZBA
        'source_aid' => 'LV-00064',
        'category' => 'Light Vehicle',
        'make_model' => 'DOUBLE CABIN TOYOTA HILUX',
        'year' => 2022,
        'sn' => null,
        'plate' => 'B 9113 ZBA',
        'warranty' => 'No Warranty',
        'unit' => 'KM',
        'tracking_val' => 132632.0,
        'tracking_date' => '2026-10-24',
        'interval' => 10000,
        'last_service_val' => 120000.0,
        'last_service_date' => '2026-06-10',
        'target_due_val' => 130000.0,
        'project' => 'WUR EW Project',
        'completed' => true,
        'actual_val' => 132808.0,
        'actual_date' => '2026-10-27',
        'jo_number' => '026/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'overdue',
        'note' => 'Realisasi'
    ],

    // CARRY-OVER FROM SEPTEMBER
    [
        'no' => 37,
        'code' => 'CS-01',
        'asset_id' => 'CS-41001',
        'source_aid' => 'CS-41001',
        'category' => 'Reclaimer Spreader',
        'make_model' => 'Powder Binder Spreader XCMG XKC185',
        'year' => 2025,
        'sn' => 'XUG0183KKSMQ0021',
        'plate' => null,
        'warranty' => 'Warranty',
        'unit' => 'HM',
        'tracking_val' => 679.5,
        'tracking_date' => '2026-10-13',
        'interval' => 250,
        'last_service_val' => 493.2,
        'last_service_date' => '2026-07-27',
        'target_due_val' => 743.2,
        'project' => 'Project RWI',
        'completed' => true,
        'actual_val' => 682.3,
        'actual_date' => '2026-10-15',
        'jo_number' => 'Warranty XCMG',
        'mechanic' => 'Warranty XCMG',
        'status_pm' => 'Due Soon',
        'note' => 'Realisasi Warranty (Carry-over September selesai)'
    ],
    [
        'no' => 38,
        'code' => 'DT 001',
        'asset_id' => 'DT-00071',
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
        'jo_number' => '027/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'Sudah terjual tgl 26 september 2026'
    ],
    [
        'no' => 39,
        'code' => 'DT 102',
        'asset_id' => 'DT-41002',
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
        'jo_number' => '028/JO-PM SERVICE/BRA/09/2026',
        'mechanic' => 'Tim Mekanik',
        'status_pm' => 'Overdue',
        'note' => 'sudah diservice'
    ],
    [
        'no' => 40,
        'code' => 'RM-02',
        'asset_id' => 'RM-41001',
        'source_aid' => 'RM-41002',
        'category' => 'Reclaimer Spreader',
        'make_model' => 'Road Reclaimers Caterpillar RM500B',
        'year' => 2025,
        'sn' => 'RMZ00126',
        'plate' => null,
        'warranty' => 'Warranty CAT',
        'unit' => 'HM',
        'tracking_val' => 476.0,
        'tracking_date' => '2026-10-26',
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
        'note' => 'Plan di minggu ke 4, filter di trakindo duri (Ikuti Scehdule / Tercapai Lebih Dulu)'
    ],
    [
        'no' => 41,
        'code' => 'TMC-06',
        'asset_id' => 'TMC-31005',
        'source_aid' => 'TMC-31005',
        'category' => 'Other',
        'make_model' => 'TMC MITSUBISHI TRUCK CRANE FN62FL',
        'year' => 2025,
        'sn' => null,
        'plate' => 'BM 8531 NU',
        'warranty' => 'Waranty Fuso',
        'unit' => 'KM',
        'tracking_val' => 8942.0,
        'tracking_date' => '2026-10-26',
        'interval' => 5000,
        'last_service_val' => 5000.0,
        'last_service_date' => null,
        'target_due_val' => 5000.0,
        'project' => 'Project Matting Board',
        'completed' => false,
        'actual_val' => null,
        'actual_date' => null,
        'jo_number' => 'Waranty Fuso',
        'mechanic' => null,
        'status_pm' => 'Due Soon',
        'note' => 'plan minggu ke 4 , masih belum sempat full job (Ikuti Scehdule / Tercapai Lebih Dulu)'
    ]
];

$pdo->beginTransaction();

try {
    // ------------------------------------------------------------------------
    // Step 1: Ensure Locations exist
    // ------------------------------------------------------------------------
    echo "[1/6] Mapping project locations...\n";
    $locationMap = [];
    $stmtFindLoc = $pdo->prepare("SELECT location_id FROM locations WHERE location_name = :name");
    foreach (['WUR EW Project', 'Project RWI', 'Project Matting Board', 'Lahat Project'] as $locName) {
        $stmtFindLoc->execute([':name' => $locName]);
        $locId = $stmtFindLoc->fetchColumn();
        $locationMap[$locName] = $locId ? (int) $locId : 15;
    }

    // ------------------------------------------------------------------------
    // Step 2: Ensure 12 missing assets are registered in `assets`
    // ------------------------------------------------------------------------
    echo "\n[2/6] Ensuring 12 missing assets are registered in `assets`...\n";
    $validCategories = [
        'Excavator', 'Bulldozer', 'Dump Truck', 'Motor Grader',
        'Vibro Compactor', 'Water Truck', 'Prime Mover', 'Light Vehicle',
        'Reclaimer Spreader', 'Trado', 'Other'
    ];

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

    $insertedCount = 0;
    foreach ($octoberUnits as $item) {
        $aid = $item['asset_id'];
        $stmtCheckAsset->execute([':id' => $aid]);
        if (!$stmtCheckAsset->fetchColumn()) {
            $cat = in_array($item['category'], $validCategories, true) ? $item['category'] : 'Other';
            $assetType = in_array($cat, ['Dump Truck', 'Prime Mover', 'Water Truck', 'Trado', 'Light Vehicle'], true) ? $cat : 'Heavy Equipment';
            $locId = $locationMap[$item['project']] ?? 15;

            $stmtInsertAsset->execute([
                ':id' => $aid,
                ':code' => $aid,
                ':hull' => $item['code'],
                ':alias' => $item['code'],
                ':plate' => $item['plate'],
                ':sn' => $item['sn'],
                ':type' => $assetType,
                ':category' => $cat,
                ':make_model' => $item['make_model'],
                ':year' => $item['year'],
                ':loc_id' => $locId,
                ':hm' => $item['tracking_val']
            ]);
            $insertedCount++;
            echo "   + Registered new asset: {$item['code']} -> ID: $aid ({$item['make_model']})\n";
        }
    }
    echo "   Total new assets created: $insertedCount\n";

    // ------------------------------------------------------------------------
    // Step 3: Update live telemetries and special asset statuses in `assets`
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

    foreach ($octoberUnits as $item) {
        $aid = $item['asset_id'];
        $dailyRate = $item['unit'] === 'KM' ? 150.00 : 7.00;
        $remaining = $item['target_due_val'] - $item['tracking_val'];
        $daysToNext = $remaining <= 0 ? 0 : (int) ceil($remaining / $dailyRate);
        $estDate = date('Y-m-d', strtotime('+' . $daysToNext . ' days'));
        $locId = $locationMap[$item['project']] ?? 15;

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

    // Special status update for DT 001 (sold)
    $stmtArchive = $pdo->prepare("
        INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_name, reason)
        VALUES ('asset', 'DT-00071', NOW(), 'System Planner', 'Sudah terjual tgl 26 september 2026')
        ON DUPLICATE KEY UPDATE reason = VALUES(reason)
    ");
    $stmtArchive->execute();
    $pdo->exec("UPDATE assets SET status = 'INACTIVE', is_active = 0 WHERE asset_id = 'DT-00071'");
    echo "   Marked DT 001 (DT-00071) as SOLD / INACTIVE in archived_items.\n";

    // Special status update for DT 096 (LAKA incident)
    $pdo->exec("UPDATE assets SET status = 'ACCIDENT_HOLD' WHERE asset_id = 'DT-04044'");
    echo "   Marked DT 096 (DT-04044) status to ACCIDENT_HOLD due to LAKA incident.\n";

    // ------------------------------------------------------------------------
    // Step 4: Ingest 31 completed service records into `asset_service_history`
    // ------------------------------------------------------------------------
    echo "\n[4/6] Ingesting completed PM service records into `asset_service_history`...\n";
    $stmtCheckHist = $pdo->prepare("
        SELECT COUNT(*) FROM asset_service_history 
        WHERE asset_id = :id AND DATE(service_date) = :dt AND ABS(service_hm_km - :hm) < 0.1
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
    foreach ($octoberUnits as $item) {
        if (!$item['completed']) {
            continue; // Pending / sold units are not added to service history
        }

        $aid = $item['asset_id'];
        $sDate = $item['actual_date'] ?? '2026-10-28';
        $sHM = $item['actual_val'] ?? $item['tracking_val'];
        $joNum = $item['jo_number'];

        $stmtCheckHist->execute([
            ':id' => $aid,
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
    echo "   Ingested $ingestedHistCount new service history records for October.\n";

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
    foreach ($octoberUnits as $item) {
        $aid = $item['asset_id'];
        $locId = $locationMap[$item['project']] ?? 15;
        
        $woId = 'WO-PM-' . sprintf('%02d', $item['no']) . '-102026';
        $stmtCheckWo->execute([':wo_id' => $woId]);
        
        if (!$stmtCheckWo->fetchColumn()) {
            $isDone = $item['completed'];
            $woStatus = $isDone ? 'Closed' : 'Open';
            $tDate = ($item['actual_date'] ?? $item['tracking_date']) . ' 08:00:00';
            $cDate = $isDone ? (($item['actual_date'] ?? $item['tracking_date']) . ' 17:00:00') : null;

            $stmtInsertWo->execute([
                ':wo_id' => $woId,
                ':asset_id' => $aid,
                ':hull' => $item['code'],
                ':type' => $item['category'],
                ':hm' => $item['actual_val'] ?? $item['tracking_val'],
                ':trouble_at' => $tDate,
                ':loc_id' => $locId,
                ':raw_loc' => $item['project'],
                ':issue' => "PM Service {$item['interval']} {$item['unit']} - Ref JO: {$item['jo_number']}",
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
    echo "   Synchronized $ingestedWoCount October Work Orders.\n";

    // ------------------------------------------------------------------------
    // Step 6: Upsert active PM monitoring records in `pm_plans`
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
    foreach ($octoberUnits as $item) {
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
    echo "   Upserted $upsertedPmCount active PM Plan records for October.\n";

    // ------------------------------------------------------------------------
    // Step 7: Record Migration Key
    // ------------------------------------------------------------------------
    $stmtRegMigration = $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES (:k, NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtRegMigration->execute([':k' => $migrationKey]);

    $pdo->commit();
    echo "\n============================================================================\n";
    echo " SUCCESS: Batch 10 October 2026 PM Plan successfully injected!\n";
    echo "============================================================================\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
