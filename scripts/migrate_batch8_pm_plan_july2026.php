<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 8 - Preventive Maintenance Plan July 2026 & Telemetry Enrichment
 *
 * Ensures all 34 units from Plan_Service_Juli_2026_WUR_EW_Project.md (legacyPmPlans)
 * and all 13 units from Batch 3 (04_01_Tracking_HM_KM_dan_Rencana_Service.md)
 * are present in `assets`, `pm_plans`, and `asset_service_history` so the
 * Preventive Maintenance table displays all existing PM units completely.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 8 PM PLAN JULY 2026 & TELEMETRY ENRICHMENT\n";
echo "============================================================================\n\n";

$migrationKey = 'BATCH_8_PM_PLAN_JULY_2026';

$julyPlanUnits = [
    [
        'asset_id' => 'PF-00001',
        'code' => 'PF 05',
        'category' => 'Vibro Compactor',
        'make_model' => 'Vibro Bomag Pad Foot Compactor BW 211D-40SL',
        'year' => 2024,
        'warranty' => 'No Warranty',
        'current' => 1894.2,
        'tracking' => '2026-06-30',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 1509.0,
        'lastDate' => '2026-03-30',
        'target' => 2009.0,
        'completed' => true,
        'actual' => 2036.4,
        'actualDate' => '2026-07-22',
        'note' => null,
    ],
    [
        'asset_id' => 'PF-00006',
        'code' => 'SD 02',
        'category' => 'Vibro Compactor',
        'make_model' => 'Vibro Bomag Smooth Drum Compactor BW 211D-40SL',
        'year' => 2020,
        'warranty' => 'No Warranty',
        'current' => 6365.0,
        'tracking' => '2026-06-22',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 6045.0,
        'lastDate' => '2026-04-24',
        'target' => 6545.0,
        'completed' => true,
        'actual' => 6532.2,
        'actualDate' => '2026-07-20',
        'note' => null,
    ],
    [
        'asset_id' => 'MG-00004',
        'code' => 'GR 5',
        'category' => 'Motor Grader',
        'make_model' => 'Motor Grader XCMG GR135 MAX',
        'year' => 2025,
        'warranty' => 'No Warranty',
        'current' => 2446.1,
        'tracking' => '2026-07-02',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 2051.3,
        'lastDate' => '2026-04-27',
        'target' => 2551.3,
        'completed' => true,
        'actual' => null,
        'actualDate' => null,
        'note' => 'Ditandai realisasi pada sumber, tetapi HM/KM dan tanggal PM kosong.',
    ],
    [
        'asset_id' => 'DZ-00001',
        'code' => 'Dozer 08',
        'category' => 'Bulldozer',
        'make_model' => 'Bulldozer Komatsu D85ESS-2',
        'year' => 2024,
        'warranty' => 'No Warranty',
        'current' => 2465.9,
        'tracking' => '2026-07-01',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 2066.0,
        'lastDate' => '2026-03-05',
        'target' => 2566.0,
        'completed' => true,
        'actual' => 25781.0,
        'actualDate' => '2026-07-22',
        'note' => 'Realisasi 25.781 perlu verifikasi; tidak konsisten dengan tracking dan target sekitar 2.500.',
    ],
    [
        'asset_id' => 'DZ-00002',
        'code' => 'Dozer 03',
        'category' => 'Bulldozer',
        'make_model' => 'Bulldozer Caterpillar D6G 2XL',
        'year' => 2024,
        'warranty' => 'No Warranty',
        'current' => 2177.0,
        'tracking' => '2026-06-30',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 1709.0,
        'lastDate' => '2025-11-03',
        'target' => 2209.0,
        'completed' => true,
        'actual' => 2294.3,
        'actualDate' => '2026-07-21',
        'note' => null,
    ],
    [
        'asset_id' => 'EXC-00001',
        'code' => 'Exca 24',
        'category' => 'Excavator',
        'make_model' => 'Excavator Komatsu PC200-10M0 CE',
        'year' => 2024,
        'warranty' => 'Warranty - 4000',
        'current' => 2443.0,
        'tracking' => '2026-06-24',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 2001.0,
        'lastDate' => '2026-04-11',
        'target' => 2501.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'EXC-00008',
        'code' => 'Exca 01',
        'category' => 'Excavator',
        'make_model' => 'Excavator Komatsu PC210-10M0',
        'year' => 2025,
        'warranty' => 'Warranty - 10000',
        'current' => 10537.3,
        'tracking' => '2026-07-01',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 10154.0,
        'lastDate' => '2026-04-13',
        'target' => 10654.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'EXC-00009',
        'code' => 'EXCA-29',
        'category' => 'Excavator',
        'make_model' => 'Excavator Komatsu PC200-10M0 CE',
        'year' => 2025,
        'warranty' => 'Warranty - 1000',
        'current' => 1436.9,
        'tracking' => '2026-07-01',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 1117.3,
        'lastDate' => '2026-04-16',
        'target' => 1617.3,
        'completed' => true,
        'actual' => 1451.3,
        'actualDate' => '2026-07-03',
        'note' => 'Warranty UT.',
    ],
    [
        'asset_id' => 'EXC-00012',
        'code' => 'Exca BRA-23',
        'category' => 'Excavator',
        'make_model' => 'Excavator Komatsu PC200-10M0 CE',
        'year' => 2024,
        'warranty' => 'No Warranty',
        'current' => 5970.0,
        'tracking' => '2026-06-27',
        'interval' => 500,
        'unit' => 'HM',
        'last' => 5532.3,
        'lastDate' => '2025-04-07',
        'target' => 6032.3,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00001',
        'code' => 'DT 055',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9107 ZYT',
        'warranty' => 'No Warranty',
        'current' => 37720.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 29118.0,
        'lastDate' => '2026-03-04',
        'target' => 39118.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00004',
        'code' => 'DT 058',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9112 ZYT',
        'warranty' => 'No Warranty',
        'current' => 38146.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 30066.0,
        'lastDate' => '2026-02-12',
        'target' => 40066.0,
        'completed' => true,
        'actual' => 40031.0,
        'actualDate' => '2026-07-11',
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00007',
        'code' => 'DT 061',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9114 ZYT',
        'warranty' => 'No Warranty',
        'current' => 37555.0,
        'tracking' => '2026-06-26',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 28742.0,
        'lastDate' => '2026-03-06',
        'target' => 38742.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00010',
        'code' => 'DT 064',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9117 ZYT',
        'warranty' => 'No Warranty',
        'current' => 39146.0,
        'tracking' => '2026-06-27',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 19946.0,
        'lastDate' => '2025-11-29',
        'target' => 29946.0,
        'completed' => true,
        'actual' => 40346.0,
        'actualDate' => '2026-07-10',
        'note' => 'Catatan service di KM 30 ribu tidak tersedia.',
    ],
    [
        'asset_id' => 'DT-00013',
        'code' => 'DT 067',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9122 ZYT',
        'warranty' => 'No Warranty',
        'current' => 35397.0,
        'tracking' => '2026-05-18',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 19725.0,
        'lastDate' => '2025-11-04',
        'target' => 29725.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => 'Histori service KM 30 ribu dan informasi KM operator tidak tersedia.',
    ],
    [
        'asset_id' => 'DT-00017',
        'code' => 'DT 071',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9126 ZYT',
        'warranty' => 'No Warranty',
        'current' => 39890.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 29966.0,
        'lastDate' => '2026-02-25',
        'target' => 39966.0,
        'completed' => true,
        'actual' => 40906.0,
        'actualDate' => '2026-07-07',
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00018',
        'code' => 'DT 072',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9127 ZYT',
        'warranty' => 'No Warranty',
        'current' => 38522.0,
        'tracking' => '2026-06-27',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 29753.0,
        'lastDate' => '2026-03-04',
        'target' => 39753.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00021',
        'code' => 'DT 075',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9130 ZYT',
        'warranty' => 'No Warranty',
        'current' => 38348.0,
        'tracking' => '2026-07-07',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 19903.0,
        'lastDate' => '2025-11-18',
        'target' => 29903.0,
        'completed' => true,
        'actual' => 38348.0,
        'actualDate' => '2026-07-07',
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00025',
        'code' => 'DT 079',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9134 ZYT',
        'warranty' => 'No Warranty',
        'current' => 38756.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 18932.0,
        'lastDate' => '2025-11-03',
        'target' => 28932.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => 'Catatan service di KM 30 ribu tidak tersedia.',
    ],
    [
        'asset_id' => 'DT-00026',
        'code' => 'DT 080',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9135 ZYT',
        'warranty' => 'No Warranty',
        'current' => 35140.0,
        'tracking' => '2026-05-18',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 20128.0,
        'lastDate' => '2025-11-18',
        'target' => 30128.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => 'Catatan service di KM 30 ribu tidak tersedia.',
    ],
    [
        'asset_id' => 'DT-00027',
        'code' => 'DT 081',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2024,
        'plate' => 'B 9136 ZYT',
        'warranty' => 'No Warranty',
        'current' => 37984.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 20665.0,
        'lastDate' => '2025-11-27',
        'target' => 30665.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => 'Catatan service di KM 30 ribu tidak tersedia.',
    ],
    [
        'asset_id' => 'DT-00041',
        'code' => 'DT 038',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2023,
        'plate' => 'B 9082 ZYT',
        'warranty' => 'No Warranty',
        'current' => 77131.0,
        'tracking' => '2026-06-26',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 67729.0,
        'lastDate' => '2026-02-24',
        'target' => 77729.0,
        'completed' => true,
        'actual' => 77506.0,
        'actualDate' => '2026-07-08',
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00042',
        'code' => 'DT 039',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2023,
        'plate' => 'B 9089 ZYT',
        'warranty' => 'No Warranty',
        'current' => 70935.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 62804.0,
        'lastDate' => '2025-12-07',
        'target' => 72804.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00044',
        'code' => 'DT 041',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2023,
        'plate' => 'B 9092 ZYT',
        'warranty' => 'No Warranty',
        'current' => 77071.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 68506.0,
        'lastDate' => '2026-03-13',
        'target' => 78506.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00045',
        'code' => 'DT 042',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2023,
        'plate' => 'B 9093 ZYT',
        'warranty' => 'No Warranty',
        'current' => 74329.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 65199.0,
        'lastDate' => '2026-03-02',
        'target' => 75199.0,
        'completed' => true,
        'actual' => 75177.0,
        'actualDate' => '2026-07-08',
        'note' => null,
    ],
    [
        'asset_id' => 'DT-00062',
        'code' => 'DT 054',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 260 JD',
        'year' => 2019,
        'plate' => 'BG 8638 NI',
        'warranty' => 'No Warranty',
        'current' => 79494.0,
        'tracking' => '2026-06-27',
        'interval' => 7500,
        'unit' => 'KM',
        'last' => 74306.0,
        'lastDate' => '2026-04-30',
        'target' => 81806.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-04025',
        'code' => 'DT 104',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2022,
        'plate' => 'BM 9398 JO',
        'warranty' => 'No Warranty',
        'current' => 98230.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 90672.0,
        'lastDate' => '2026-02-23',
        'target' => 100672.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-04036',
        'code' => 'DT 101',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2022,
        'plate' => 'BM 9949 JO',
        'warranty' => 'No Warranty',
        'current' => 91068.0,
        'tracking' => '2026-07-23',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 80414.0,
        'lastDate' => '2026-02-08',
        'target' => 90414.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-04040',
        'code' => 'DT 093',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2022,
        'plate' => 'BM 9509 QO',
        'warranty' => 'No Warranty',
        'current' => 92939.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 83447.0,
        'lastDate' => '2026-02-05',
        'target' => 93447.0,
        'completed' => true,
        'actual' => 93965.0,
        'actualDate' => '2026-07-07',
        'note' => null,
    ],
    [
        'asset_id' => 'DT-04042',
        'code' => 'DT 100',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino Ranger FM 280 JD',
        'year' => 2022,
        'plate' => 'BM 9287 JO',
        'warranty' => 'No Warranty',
        'current' => 87515.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 79988.0,
        'lastDate' => '2026-02-27',
        'target' => 89988.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'DT-04053',
        'code' => 'DT 094',
        'category' => 'Dump Truck',
        'make_model' => 'Dump Truck Hino FM8JN2D-XGJ',
        'year' => 2022,
        'plate' => 'BM 9824 QO',
        'warranty' => 'No Warranty',
        'current' => 72830.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 63495.0,
        'lastDate' => '2026-01-19',
        'target' => 73495.0,
        'completed' => true,
        'actual' => 73763.0,
        'actualDate' => '2026-07-08',
        'note' => null,
    ],
    [
        'asset_id' => 'PM-00002',
        'code' => 'PM 02',
        'category' => 'Prime Mover',
        'make_model' => 'Prime Mover UD Truck GWE350 6x4',
        'year' => 2024,
        'plate' => 'B 9012 ZEH',
        'warranty' => 'No Warranty',
        'current' => 35552.0,
        'tracking' => '2026-06-30',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 26043.0,
        'lastDate' => '2026-02-07',
        'target' => 36043.0,
        'completed' => true,
        'actual' => 36624.0,
        'actualDate' => '2026-07-21',
        'note' => null,
    ],
    [
        'asset_id' => 'LV-00007',
        'code' => 'TR11',
        'category' => 'Light Vehicle',
        'make_model' => 'Double Cabin Mitsubishi Triton',
        'year' => 2024,
        'plate' => 'BM 8430 QK',
        'warranty' => 'No Warranty',
        'current' => 69580.0,
        'tracking' => '2026-07-01',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 59489.0,
        'lastDate' => '2026-03-12',
        'target' => 69489.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => null,
    ],
    [
        'asset_id' => 'LV-00022',
        'code' => 'LV-00022',
        'category' => 'Light Vehicle',
        'make_model' => 'Double Cabin Toyota Hilux',
        'year' => 2022,
        'plate' => 'B 9144 ZBA',
        'warranty' => 'No Warranty',
        'current' => 90898.0,
        'tracking' => '2026-07-01',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 81044.0,
        'lastDate' => '2026-03-03',
        'target' => 91044.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => 'Kode unit kosong pada sumber.',
    ],
    [
        'asset_id' => 'LV-00027',
        'code' => 'LV-00027',
        'category' => 'Light Vehicle',
        'make_model' => 'Light Vehicle Toyota Hilux',
        'year' => 2022,
        'plate' => 'B 9138 ZBA',
        'warranty' => 'No Warranty',
        'current' => 243559.0,
        'tracking' => '2026-07-01',
        'interval' => 10000,
        'unit' => 'KM',
        'last' => 233053.0,
        'lastDate' => '2026-02-06',
        'target' => 243053.0,
        'completed' => false,
        'actual' => null,
        'actualDate' => null,
        'note' => 'Kode unit kosong pada sumber.',
    ],
];

$pdo->beginTransaction();

try {
    // 1. Fix duplicate PF-00001 on pm_plan_id = 2 -> PF-00006 (SD 02)
    $pdo->exec("UPDATE pm_plans SET asset_id = 'PF-00006' WHERE pm_plan_id = 2 AND asset_id = 'PF-00001'");

    // 2. Ensure missing Dump Truck units DT-00007 and DT-00018 exist in assets
    $stmtCheckAsset = $pdo->prepare("SELECT asset_id, hull_number, alias_name, license_plate, daily_usage_coefficient FROM assets WHERE asset_id = :id");
    $stmtInsertMissingAsset = $pdo->prepare("
        INSERT INTO assets (
            asset_id, asset_code, hull_number, alias_name, license_plate,
            type, category, make_model, year_manufacture, status,
            current_location_id, last_hm_km, is_active
        ) VALUES (
            :id, :code, :hull, :alias, :plate,
            'Heavy Equipment', :category, :make_model, :year, 'OPERATING',
            3, :hm, 1
        )
    ");

    foreach ($julyPlanUnits as $item) {
        $aid = $item['asset_id'];
        $stmtCheckAsset->execute([':id' => $aid]);
        $existing = $stmtCheckAsset->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $stmtInsertMissingAsset->execute([
                ':id' => $aid,
                ':code' => $aid,
                ':hull' => $item['code'],
                ':alias' => $item['code'],
                ':plate' => $item['plate'] ?? null,
                ':category' => $item['category'],
                ':make_model' => $item['make_model'],
                ':year' => $item['year'],
                ':hm' => $item['current'],
            ]);
        }
    }

    // 3. Upsert July 2026 PM plan & enrich assets + asset_service_history
    $stmtUpdateAssetPm = $pdo->prepare("
        UPDATE assets SET
            hull_number = CASE WHEN (hull_number IS NULL OR hull_number = '') THEN :hull ELSE hull_number END,
            license_plate = CASE WHEN (license_plate IS NULL OR license_plate = '') AND :plate IS NOT NULL THEN :plate_val ELSE license_plate END,
            last_hm_km = :last_hm_km,
            last_service_hm_km = :last_service_hm_km,
            last_service_date = :last_service_date,
            service_interval_val = :service_interval_val,
            service_interval_unit = :service_interval_unit,
            daily_usage_coefficient = COALESCE(daily_usage_coefficient, :daily_rate),
            next_service_due_hm_km = :next_service_due_hm_km,
            next_service_estimated_date = :next_service_estimated_date
        WHERE asset_id = :asset_id
    ");

    $stmtFindPm = $pdo->prepare("SELECT pm_plan_id FROM pm_plans WHERE asset_id = :asset_id ORDER BY pm_plan_id DESC LIMIT 1");
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

    $stmtCheckHist = $pdo->prepare("SELECT COUNT(*) FROM asset_service_history WHERE asset_id = :id AND DATE(service_date) = :dt");
    $stmtNextSeq = $pdo->prepare("SELECT COALESCE(MAX(service_sequence), 0) + 1 FROM asset_service_history WHERE asset_id = :id");
    $stmtInsertHist = $pdo->prepare("
        INSERT INTO asset_service_history (asset_id, service_sequence, service_date, service_hm_km, service_type, notes)
        VALUES (:id, :seq, :dt, :hm, :type, :notes)
    ");

    $enrichedCount = 0;
    foreach ($julyPlanUnits as $item) {
        $aid = $item['asset_id'];
        $dailyRate = $item['unit'] === 'KM' ? 150.00 : 7.00;
        $variance = round($item['current'] - $item['target'], 2);
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

        $remaining = $item['target'] - $item['current'];
        $daysToNext = $remaining <= 0 ? 0 : (int) ceil($remaining / $dailyRate);
        $estDate = date('Y-m-d', strtotime('+' . $daysToNext . ' days'));

        $stmtUpdateAssetPm->execute([
            ':hull' => $item['code'],
            ':plate' => $item['plate'] ?? null,
            ':plate_val' => $item['plate'] ?? null,
            ':last_hm_km' => $item['current'],
            ':last_service_hm_km' => $item['last'],
            ':last_service_date' => $item['lastDate'] . ' 08:00:00',
            ':service_interval_val' => $item['interval'],
            ':service_interval_unit' => $item['unit'],
            ':daily_rate' => $dailyRate,
            ':next_service_due_hm_km' => $item['target'],
            ':next_service_estimated_date' => $estDate,
            ':asset_id' => $aid,
        ]);

        $stmtFindPm->execute([':asset_id' => $aid]);
        $pmPlanId = $stmtFindPm->fetchColumn();
        if ($pmPlanId) {
            $stmtUpdatePm->execute([
                ':interval_hm' => $item['interval'],
                ':current_smr' => $item['current'],
                ':last_service_hm' => $item['last'],
                ':last_service_date' => $item['lastDate'],
                ':target_due_hm' => $item['target'],
                ':variance_hm' => $variance,
                ':status' => $pmStatus,
                ':warranty_status' => $item['warranty'],
                ':planner_note' => $item['note'],
                ':pm_plan_id' => $pmPlanId,
            ]);
        } else {
            $stmtInsertPm->execute([
                ':asset_id' => $aid,
                ':interval_hm' => $item['interval'],
                ':current_smr' => $item['current'],
                ':last_service_hm' => $item['last'],
                ':last_service_date' => $item['lastDate'],
                ':target_due_hm' => $item['target'],
                ':variance_hm' => $variance,
                ':status' => $pmStatus,
                ':warranty_status' => $item['warranty'],
                ':planner_note' => $item['note'],
            ]);
        }

        // Seed previous service history if not present
        if (!empty($item['lastDate']) && $item['last'] !== null) {
            $stmtCheckHist->execute([':id' => $aid, ':dt' => $item['lastDate']]);
            if ((int) $stmtCheckHist->fetchColumn() === 0) {
                $stmtNextSeq->execute([':id' => $aid]);
                $seq = (int) $stmtNextSeq->fetchColumn();
                $stmtInsertHist->execute([
                    ':id' => $aid,
                    ':seq' => $seq,
                    ':dt' => $item['lastDate'] . ' 08:00:00',
                    ':hm' => $item['last'],
                    ':type' => 'PM berkala',
                    ':notes' => 'Histori servis berkala sebelum Juli 2026',
                ]);
            }
        }

        // Seed July 2026 realization history if present
        if ($item['completed'] && !empty($item['actualDate']) && $item['actual'] !== null) {
            $stmtCheckHist->execute([':id' => $aid, ':dt' => $item['actualDate']]);
            if ((int) $stmtCheckHist->fetchColumn() === 0) {
                $stmtNextSeq->execute([':id' => $aid]);
                $seq = (int) $stmtNextSeq->fetchColumn();
                $stmtInsertHist->execute([
                    ':id' => $aid,
                    ':seq' => $seq,
                    ':dt' => $item['actualDate'] . ' 08:00:00',
                    ':hm' => $item['actual'],
                    ':type' => 'PM realisasi',
                    ':notes' => $item['note'] ?: 'Realisasi PM Periode Juli 2026',
                ]);
            }
        }

        $enrichedCount++;
    }

    // 4. Ensure Batch 3 PM units also have hull_number & daily_usage_coefficient populated when null
    $batch3Hulls = [
        'DT-00031' => 'DT 088',
        'DT-00037' => 'DT 090',
        'DT-00047' => 'DT 010',
        'DT-00056' => 'DT 018',
        'DT-00071' => 'DT 028',
        'DT-41001' => 'DT 099',
        'DT-41002' => 'DT 102',
        'DT-41003' => 'DT 106',
        'DT-41004' => 'DT 095',
        'DT-41005' => 'DT 105',
    ];
    $stmtBatch3Hull = $pdo->prepare("
        UPDATE assets
        SET hull_number = CASE WHEN (hull_number IS NULL OR hull_number = '') THEN :hull ELSE hull_number END,
            daily_usage_coefficient = COALESCE(daily_usage_coefficient, 150.00)
        WHERE asset_id = :id
    ");
    foreach ($batch3Hulls as $bId => $bHull) {
        $stmtBatch3Hull->execute([':hull' => $bHull, ':id' => $bId]);
    }
    $pdo->exec("
        UPDATE assets
        SET daily_usage_coefficient = CASE
            WHEN service_interval_unit = 'KM' OR category IN ('Dump Truck', 'Prime Mover', 'Light Vehicle', 'Water Truck', 'Trado') THEN 150.00
            ELSE 7.00
        END
        WHERE next_service_due_hm_km IS NOT NULL AND daily_usage_coefficient IS NULL
    ");

    $stmtMig = $pdo->prepare("INSERT INTO app_data_migrations (migration_key, applied_at) VALUES (:k, NOW()) ON DUPLICATE KEY UPDATE applied_at = NOW()");
    $stmtMig->execute([':k' => $migrationKey]);

    $pdo->commit();
    $totalPm = (int) $pdo->query("SELECT COUNT(DISTINCT asset_id) FROM pm_plans")->fetchColumn();
    echo "  -> Berhasil menyinkronkan $enrichedCount unit Plan Service Juli 2026 (Total unit unik di pm_plans: $totalPm unit).\n";
    echo "============================================================================\n";
    echo " MIGRASI BATCH 8 BERHASIL DISELESAIKAN!\n";
    echo "============================================================================\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "FATAL ERROR: " . $e->getMessage() . "\n");
    exit(1);
}

