<?php
/**
 * scripts/migrate_batch11_komtrax_jul_sep_2026.php
 *
 * Batch 11 Migration:
 * - Injects KOMTRAX Telematics Logs for:
 *   1. Periode Juli 2026 (18 units, W = 2,339.6 h, Actual = 1,099.2 h, Fuel = 17,771.5 L)
 *   2. Periode Agustus 2026 (18 units, W = 2,051.7 h, Actual = 943.6 h, Fuel = 15,276.1 L)
 *   3. Periode September 2026 (18 units, W = 2,087.3 h, Actual = 920.3 h, Fuel = 15,628.7 L)
 * - Ensures unique key (serial_number, period_month) on telematics_logs for idempotency.
 * - Synchronizes assets.last_hm_km with GREATEST(last_hm_km, smr_hours) from September 2026 KOMTRAX.
 */

declare(strict_types=1);

define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Starting Batch 11 Migration: KOMTRAX Juli, Agustus, September 2026 ===\n";

// 1. Ensure unique index on (serial_number, period_month)
$idxCheck = $db->query("SHOW INDEX FROM telematics_logs WHERE Key_name = 'uq_telematics_sn_period'")->fetchAll();
if (empty($idxCheck)) {
    // Remove any accidental duplicate (serial_number, period_month) keeping the highest log_id
    $db->exec("
        DELETE t1 FROM telematics_logs t1
        INNER JOIN telematics_logs t2
        ON t1.serial_number = t2.serial_number
        AND t1.period_month = t2.period_month
        AND t1.log_id < t2.log_id
    ");
    $db->exec("ALTER TABLE telematics_logs ADD UNIQUE KEY uq_telematics_sn_period (serial_number, period_month)");
    echo "[OK] Added UNIQUE KEY uq_telematics_sn_period (serial_number, period_month) on telematics_logs.\n";
} else {
    echo "[OK] UNIQUE KEY uq_telematics_sn_period already exists.\n";
}

// 2. Canonical mapping from Serial Number -> asset_id, model_type, sub_group
$unitMeta = [
    'J21020'   => ['asset_id' => 'DZ-00001',  'model_type' => 'D85ESS-2',    'sub_group' => 'PKB PEKANBARU Branch'],
    '2066'     => ['asset_id' => 'MG-00005',  'model_type' => 'GD535-5',     'sub_group' => 'PKB PEKANBARU Branch'],
    '2259'     => ['asset_id' => 'MG-00002',  'model_type' => 'GD535-5',     'sub_group' => 'PLB PALEMBANG Branch'],
    'J10309'   => ['asset_id' => 'EXC-00021', 'model_type' => 'PC195LC-8',   'sub_group' => 'MDN MEDAN Branch'],
    'C51502'   => ['asset_id' => 'EXC-00012', 'model_type' => 'PC200-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'C51503'   => ['asset_id' => 'EXC-00009', 'model_type' => 'PC200-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'DBCH0302' => ['asset_id' => 'EXC-00007', 'model_type' => 'PC200-10M0',  'sub_group' => 'PLB PALEMBANG Branch'],
    'DBCH0366' => ['asset_id' => 'EXC-00010', 'model_type' => 'PC200-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'DBCH0380' => ['asset_id' => 'EXC-00008', 'model_type' => 'PC200-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'DBCH1784' => ['asset_id' => 'EXC-41001', 'model_type' => 'PC200-10M0',  'sub_group' => 'PLB PALEMBANG Branch'],
    'DBCH1801' => ['asset_id' => 'EXC-00022', 'model_type' => 'PC200-10M0',  'sub_group' => 'PLB PALEMBANG Branch'],
    'DBCH2244' => ['asset_id' => 'EXC-00024', 'model_type' => 'PC200-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'DBCH2245' => ['asset_id' => 'EXC-00002', 'model_type' => 'PC200-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'DBCH2941' => ['asset_id' => 'EXC-00015', 'model_type' => 'PC200-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'C01530'   => ['asset_id' => 'EXC-00013', 'model_type' => 'PC210-10M0',  'sub_group' => 'PKB PEKANBARU Branch'],
    'C01531'   => ['asset_id' => 'EXC-00014', 'model_type' => 'PC210-10M0',  'sub_group' => 'SMG SEMARANG Rep Office'],
    'C07004'   => ['asset_id' => 'EXC-00027', 'model_type' => 'PC210-10M0',  'sub_group' => 'PLB PALEMBANG Branch'],
    'C07076'   => ['asset_id' => 'EXC-00028', 'model_type' => 'PC210-10M0',  'sub_group' => 'PLB PALEMBANG Branch'],
];

// 3. Verified KOMTRAX Datasets (Juli, Agustus, September 2026)
// Format: [sn, smr, last_comm_iso, days, wh, act_wh, act_ratio, e_mode, travel, digging, hoist, fuel_l, fuel_lph, idling]
$periodsData = [
    'Juli 2026' => [
        ['J21020',   390.3,  '2026-08-01 12:39:34',  0,   0.0,   0.0,  0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0,   0.0],
        ['2066',     4695.4, '2026-08-01 06:11:05', 29, 173.0,  99.2, 57.3, 45.5, 55.2,  0.0,  0.0,  704.6,  4.1,  42.7],
        ['2259',     2794.7, '2026-08-01 06:21:45', 31, 218.7, 116.4, 53.2, 51.9, 53.4,  0.0,  0.0,  772.5,  3.5,  46.8],
        ['J10309',   4135.7, '2026-07-31 13:09:41',  8,  15.6,  14.3, 91.6,  0.0, 23.5, 19.0, 26.3,  219.8, 14.0,   8.4],
        ['C51502',   7223.8, '2026-08-01 13:17:35', 26, 222.3,  69.4, 31.2, 18.1,  8.3,  6.4,  3.0, 1351.1,  6.1,  68.8],
        ['C51503',   6581.6, '2026-08-01 08:42:36', 25, 150.4,  58.0, 38.6, 31.8, 10.9,  6.7,  5.6, 1349.0,  9.0,  61.4],
        ['DBCH0302', 9603.1, '2026-08-01 06:31:14', 26, 163.3,  74.5, 45.6, 40.4,  8.2, 12.8,  7.4, 1436.4,  8.8,  54.4],
        ['DBCH0366', 7351.7, '2026-08-01 07:48:37', 27, 200.5,  71.3, 35.6, 30.9, 11.9,  4.4,  4.4, 1494.1,  7.5,  64.4],
        ['DBCH0380', 18481.9,'2026-08-01 07:55:38', 24, 182.6, 105.3, 57.7,  1.9, 12.8, 14.5,  9.0, 1973.8, 10.8,  42.3],
        ['DBCH1784', 5694.2, '2026-07-21 02:26:29',  5,   7.1,   1.0, 14.6, 12.9,  7.0,  0.2,  0.0,   21.3,  3.0,  85.4],
        ['DBCH1801', 6154.7, '2026-08-01 07:53:35', 23, 177.9,  70.6, 39.7, 32.6, 14.3,  6.6,  4.5, 1288.5,  7.2,  60.3],
        ['DBCH2244', 2475.4, '2026-08-01 06:43:11', 26, 118.8,  60.8, 51.2,  4.7, 14.7, 10.9,  7.9, 1271.7, 10.7,  48.8],
        ['DBCH2245', 2437.6, '2026-08-01 06:57:33', 27, 182.3,  99.7, 54.7, 11.4, 15.1, 12.7,  9.0, 1921.3, 10.5,  45.3],
        ['DBCH2941', 1570.9, '2026-07-31 08:00:36', 26, 135.2,  55.2, 40.9, 31.8, 11.3,  8.4,  5.6, 1112.2,  8.2,  59.1],
        ['C01530',   4931.3, '2026-07-25 11:10:32',  0,   6.5,   0.0,  0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0, 100.0],
        ['C01531',   8839.6, '2025-07-20 02:49:12',  0,   0.0,   0.0,  0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0,   0.0],
        ['C07004',   2641.2, '2026-08-01 07:30:38', 31, 191.8, 124.4, 64.9, 51.8, 16.3, 10.9,  9.6, 1460.2,  7.6,  35.1],
        ['C07076',   2603.7, '2026-08-01 09:42:33', 31, 193.6,  79.1, 40.8, 40.7,  9.3,  5.0,  5.0, 1395.0,  7.2,  59.2],
    ],
    'Agustus 2026' => [
        ['J21020',   390.3,  '2026-09-01 03:20:35',  0,   0.0,   0.0,   0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0,   0.0],
        ['2066',     4858.7, '2026-09-01 06:41:48', 28, 163.2,  90.7,  55.6, 55.4, 55.5,  0.0,  0.0,  603.4,  3.7,  44.4],
        ['2259',     2997.1, '2026-09-01 06:47:20', 31, 202.4,  94.7,  46.8, 45.9, 47.2,  0.0,  0.0,  815.0,  4.0,  53.2],
        ['J10309',   4135.8, '2026-08-05 04:47:35',  0,   0.0,   0.0, 100.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0,   0.0],
        ['C51502',   7349.6, '2026-08-21 01:34:03', 16, 125.7,  60.4,  48.1, 38.5,  9.5, 12.5,  6.2, 1089.6,  8.7,  51.9],
        ['C51503',   6711.2, '2026-08-31 08:30:31', 20, 129.5,  47.6,  36.8, 32.1,  9.8,  7.5,  5.7, 1064.6,  8.2,  63.2],
        ['DBCH0302', 9687.2, '2026-09-01 07:11:37', 24,  84.0,  42.5,  50.6, 49.4, 19.8,  9.1,  6.0,  792.5,  9.4,  49.4],
        ['DBCH0366', 7511.8, '2026-09-01 08:10:35', 22, 160.0,  55.1,  34.4, 25.5, 13.1,  4.2,  3.9, 1198.5,  7.5,  65.6],
        ['DBCH0380', 18668.2,'2026-09-01 07:47:37', 24, 186.2, 102.7,  55.2,  0.0,  9.2, 14.6,  9.8, 1953.8, 10.5,  44.8],
        ['DBCH1784', 5697.1, '2026-09-01 07:27:40',  4,   2.7,   1.5,  55.8, 50.6, 29.1,  2.8,  0.4,   17.3,  6.3,  44.2],
        ['DBCH1801', 6324.9, '2026-09-01 07:55:37', 23, 170.1,  70.9,  41.7, 33.4, 18.9,  6.6,  4.7, 1331.1,  7.8,  58.3],
        ['DBCH2244', 2625.9, '2026-09-01 06:38:19', 24, 150.5,  73.6,  48.9, 19.7, 12.8, 10.4,  7.3, 1415.8,  9.4,  51.1],
        ['DBCH2245', 2584.6, '2026-09-01 06:30:46', 24, 146.9,  93.3,  63.5, 21.2, 14.6, 16.9, 11.3, 1928.9, 13.1,  36.5],
        ['DBCH2941', 1695.1, '2026-08-31 09:06:31', 23, 120.7,  36.0,  29.9, 26.1,  7.8,  5.9,  4.5,  747.5,  6.2,  70.1],
        ['C01530',   4932.1, '2026-08-26 04:26:30',  0,   0.5,   0.0,   0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0, 100.0],
        ['C01531',   8839.6, '2025-07-20 02:49:12',  0,   0.0,   0.0,   0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0,   0.0],
        ['C07004',   2848.1, '2026-08-31 16:08:34', 30, 206.8, 114.9,  55.6, 55.1,  8.6, 11.1,  7.7, 1338.5,  6.5,  44.4],
        ['C07076',   2806.4, '2026-08-31 08:16:36', 30, 202.5,  59.7,  29.5, 29.5,  4.7,  4.5,  2.2,  979.6,  4.8,  70.5],
    ],
    'September 2026' => [
        ['J21020',   390.3,  '2026-10-01 02:20:22',  0,   0.0,   0.0,  0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0,   0.0],
        ['2066',     4919.3, '2026-09-18 12:06:49', 11,  60.6,  39.3, 64.9, 64.9, 62.9,  0.0,  0.0,  278.8,  4.6,  35.1],
        ['2259',     3195.3, '2026-10-01 06:29:29', 30, 198.1, 100.0, 50.5, 50.5, 50.5,  0.0,  0.0, 1005.8,  5.1,  49.5],
        ['J10309',   4136.3, '2026-09-28 13:22:00',  0,   0.2,   0.0, 22.5,  0.0,  3.5,  0.0,  0.5,    0.6,  3.3,  77.5],
        ['C51502',   7436.8, '2026-10-01 07:26:30', 18,  87.1,  41.7, 47.9, 42.5,  7.8, 13.6,  7.6,  743.8,  8.5,  52.1],
        ['C51503',   6829.9, '2026-09-30 09:01:18', 18, 106.5,  23.6, 22.2, 19.0,  7.8,  3.7,  2.7,  611.5,  5.7,  77.8],
        ['DBCH0302', 9879.3, '2026-10-01 06:43:38', 26, 192.0,  93.6, 48.8, 40.7,  7.5, 13.6,  8.1, 1776.1,  9.2,  51.2],
        ['DBCH0366', 7683.9, '2026-10-01 07:49:26', 25, 172.1,  50.5, 29.4, 24.5, 11.1,  3.6,  3.3, 1135.8,  6.6,  70.6],
        ['DBCH0380', 18840.4,'2026-09-30 07:53:20', 22, 172.1,  96.3, 56.0,  1.2,  7.7, 15.1, 10.9, 1798.5, 10.4,  44.0],
        ['DBCH1784', 5828.0, '2026-10-01 06:45:41', 21, 130.9,  63.9, 48.8, 39.1,  9.8, 12.9,  6.9, 1101.0,  8.4,  51.2],
        ['DBCH1801', 6522.9, '2026-10-01 07:53:23', 26, 197.9,  87.4, 44.2, 31.6, 12.9,  9.6,  7.3, 1688.6,  8.5,  55.8],
        ['DBCH2244', 2768.9, '2026-10-01 06:22:39', 24, 142.9,  57.0, 39.9, 12.8, 11.3,  8.4,  7.0, 1158.8,  8.1,  60.1],
        ['DBCH2245', 2707.6, '2026-10-01 06:22:06', 24, 122.9,  77.6, 63.1, 25.8, 20.0, 13.0,  9.4, 1550.5, 12.6,  36.9],
        ['DBCH2941', 1801.3, '2026-09-30 08:12:20', 19,  98.5,  30.3, 30.8, 22.4,  8.6,  5.7,  3.6,  609.0,  6.2,  69.2],
        ['C01530',   4980.6, '2026-10-01 01:53:26',  0,  48.5,   0.0,  0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0, 100.0],
        ['C01531',   8839.6, '2025-07-20 02:49:12',  0,   0.0,   0.0,  0.0,  0.0,  0.0,  0.0,  0.0,    0.0,  0.0,   0.0],
        ['C07004',   3032.6, '2026-09-30 13:05:22', 29, 179.4,  75.6, 42.1, 42.1, 11.3,  6.1,  4.7,  871.4,  4.9,  57.9],
        ['C07076',   2991.0, '2026-09-30 09:06:18', 27, 177.6,  83.5, 47.0, 47.0,  8.4,  6.5,  5.4, 1298.5,  7.3,  53.0],
    ],
];

$upsertSql = "
    INSERT INTO telematics_logs (
        asset_id, serial_number, model_type, sub_group,
        smr_hours, working_days, working_hours, actual_working_hours,
        actual_working_ratio, e_mode_ratio, travel_ratio, digging_ratio, hoist_ratio,
        fuel_consumed_liters, fuel_lph, idling_ratio, last_comm_date, period_month
    ) VALUES (
        :asset_id, :serial_number, :model_type, :sub_group,
        :smr_hours, :working_days, :working_hours, :actual_working_hours,
        :actual_working_ratio, :e_mode_ratio, :travel_ratio, :digging_ratio, :hoist_ratio,
        :fuel_consumed_liters, :fuel_lph, :idling_ratio, :last_comm_date, :period_month
    )
    ON DUPLICATE KEY UPDATE
        asset_id = VALUES(asset_id),
        model_type = VALUES(model_type),
        sub_group = VALUES(sub_group),
        smr_hours = VALUES(smr_hours),
        working_days = VALUES(working_days),
        working_hours = VALUES(working_hours),
        actual_working_hours = VALUES(actual_working_hours),
        actual_working_ratio = VALUES(actual_working_ratio),
        e_mode_ratio = VALUES(e_mode_ratio),
        travel_ratio = VALUES(travel_ratio),
        digging_ratio = VALUES(digging_ratio),
        hoist_ratio = VALUES(hoist_ratio),
        fuel_consumed_liters = VALUES(fuel_consumed_liters),
        fuel_lph = VALUES(fuel_lph),
        idling_ratio = VALUES(idling_ratio),
        last_comm_date = VALUES(last_comm_date)
";

$stmtUpsert = $db->prepare($upsertSql);
$stmtAssetHm = $db->prepare("
    UPDATE assets
    SET last_hm_km = GREATEST(COALESCE(last_hm_km, 0), :smr_hours)
    WHERE asset_id = :asset_id
");

$db->beginTransaction();
try {
    $totalInjected = 0;
    foreach ($periodsData as $periodMonth => $rows) {
        $periodWh = 0.0;
        $periodActWh = 0.0;
        $periodFuel = 0.0;
        foreach ($rows as $r) {
            [$sn, $smr, $lastComm, $days, $wh, $actWh, $actRatio, $eMode, $travel, $digging, $hoist, $fuelL, $fuelLph, $idling] = $r;
            $meta = $unitMeta[$sn];
            $stmtUpsert->execute([
                ':asset_id'             => $meta['asset_id'],
                ':serial_number'        => $sn,
                ':model_type'           => $meta['model_type'],
                ':sub_group'            => $meta['sub_group'],
                ':smr_hours'            => $smr,
                ':working_days'         => $days,
                ':working_hours'        => $wh,
                ':actual_working_hours' => $actWh,
                ':actual_working_ratio' => $actRatio,
                ':e_mode_ratio'         => $eMode,
                ':travel_ratio'         => $travel,
                ':digging_ratio'        => $digging,
                ':hoist_ratio'          => $hoist,
                ':fuel_consumed_liters' => $fuelL,
                ':fuel_lph'             => $fuelLph,
                ':idling_ratio'         => $idling,
                ':last_comm_date'       => $lastComm,
                ':period_month'         => $periodMonth,
            ]);
            $totalInjected++;
            $periodWh += $wh;
            $periodActWh += $actWh;
            $periodFuel += $fuelL;

            if ($periodMonth === 'September 2026' && $smr > 0) {
                $stmtAssetHm->execute([
                    ':smr_hours' => $smr,
                    ':asset_id'  => $meta['asset_id'],
                ]);
            }
        }
        printf(
            "[OK] Injected %s: %d units | Working Hours = %.1f h | Actual Working = %.1f h | Fuel = %.1f L\n",
            $periodMonth,
            count($rows),
            $periodWh,
            $periodActWh,
            $periodFuel
        );
    }

    // Record migration in app_data_migrations
    $stmtMig = $db->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_11_KOMTRAX_JUL_AUG_SEP_2026', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtMig->execute();

    $db->commit();
    echo "=== Batch 11 Migration Complete! Total rows upserted: {$totalInjected} ===\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "[ERROR] Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
