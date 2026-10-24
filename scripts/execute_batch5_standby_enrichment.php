<?php
declare(strict_types=1);

/**
 * Strategy: Step 4 (Batch 5: Standby Action Plan & Target Ready Dates Enrichment)
 * Source:
 *   - material/ASSET/07_UNIT_STANDBY.md (Lokasi Standby, Next Action, Target Penyelesaian, Tanggal Mulai)
 * Target:
 *   - `assets` (standby_action_plan, standby_target_date, standby_start_date, raw_location_notes, operational_position)
 */

$isCommit = in_array('--commit', $argv, true);
$baseDir = dirname(__DIR__);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

function clean_str(?string $s): string {
    if (!$s) return '';
    return strtoupper(preg_replace('/[\s\.\-\/\_]+/', '', $s));
}

// 1. Load active assets
$dbAssets = $pdo->query("
    SELECT asset_id, asset_code, serial_number, license_plate, previous_license_plate,
           chassis_number, hull_number, category, make_model, ownership, status,
           standby_action_plan, standby_target_date, standby_start_date,
           raw_location_notes, operational_position
    FROM assets
")->fetchAll();

$assetById = [];
$lookup = [];

foreach ($dbAssets as $a) {
    $aid = $a['asset_id'];
    $assetById[$aid] = $a;
    $lookup[clean_str($aid)] = $aid;
    $lookup[clean_str($a['asset_code'])] = $aid;

    if ($a['serial_number'] && strlen(clean_str($a['serial_number'])) >= 4) {
        $lookup[clean_str($a['serial_number'])] = $aid;
    }
    if ($a['chassis_number'] && strlen(clean_str($a['chassis_number'])) >= 5) {
        $lookup[clean_str($a['chassis_number'])] = $aid;
    }
    if ($a['license_plate']) {
        $lookup[clean_str($a['license_plate'])] = $aid;
    }
    if ($a['previous_license_plate']) {
        $lookup[clean_str($a['previous_license_plate'])] = $aid;
    }
    if ($a['hull_number'] && strlen(clean_str($a['hull_number'])) >= 3) {
        $lookup[clean_str($a['hull_number'])] = $aid;
    }
}

// 2. Ingest Cross-Reference Mapping Files
function ingest_mapping(string $filePath, int $snCol, int $kodeCol, int $hullCol, array &$lookup, array $assetById): void {
    if (!file_exists($filePath)) return;
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $l) {
        if (!str_starts_with(trim($l), '|')) continue;
        $c = array_map('trim', explode('|', substr(trim($l), 1, -1)));
        if (!isset($c[1]) || !ctype_digit($c[1])) continue;

        $snPlat = $c[$snCol] ?? '';
        $kode = $c[$kodeCol] ?? '';
        $hull = $c[$hullCol] ?? '';

        $targetAid = null;
        if (!empty($hull) && isset($assetById[$hull])) {
            $targetAid = $hull;
        } elseif (!empty($hull) && isset($lookup[clean_str($hull)])) {
            $targetAid = $lookup[clean_str($hull)];
        }

        if ($targetAid) {
            if ($kode) $lookup[clean_str($kode)] = $targetAid;
            if ($snPlat) {
                foreach (preg_split('/[\/\,]/', $snPlat) as $p) {
                    $cp = clean_str($p);
                    if ($cp) $lookup[$cp] = $targetAid;
                }
            }
        }
    }
}

ingest_mapping($baseDir . '/material/ASSET/06_PROJECT_WUR.md', 4, 6, 7, $lookup, $assetById);
ingest_mapping($baseDir . '/material/ASSET/14_Copy_of_List_Data_Aset_BRA.md', 4, 8, 9, $lookup, $assetById);
ingest_mapping($baseDir . '/material/ASSET/08_Copy_of_List_Data_Aset_BRA_3.md', 5, 9, 10, $lookup, $assetById);
ingest_mapping($baseDir . '/material/ASSET/02_List_Data_Aset_BRA.md', 7, 10, 12, $lookup, $assetById);

// Specific aliases
$manualAliases = [
    'EXCA04' => 'EXC-00004',
    'EXCA03' => 'EXC-00003',
    'EXCA05' => 'EXC-00005',
    'EXCA07' => 'EXC-00014',
    'EXCA08' => 'EXC-00008',
    'EXCA10' => 'EXC-00010',
    'EXCA11' => 'EXC-00011',
    'EXCA12' => 'EXC-00012',
    'EXCA13' => 'EXC-00010',
    'EXCA14' => 'EXC-00014',
    'EXCA15' => 'EXC-00015',
    'EXCA16' => 'EXC-00016',
    'EXCA17' => 'EXC-00017',
    'EXCA18' => 'EXC-06006',
    'WE217079' => 'EXC-00016',
    'WE217080' => 'EXC-00017',
    'BRA04' => 'EXC-00004',
    'BRA01' => 'DZ-00010',
    'BRA07' => 'DZ-00007',
    'BRA10' => 'EXC-00010',
    'BRA11' => 'EXC-00011',
    'BRA14' => 'EXC-00014',
    'BRA15' => 'EXC-00015',
    'BRA26' => 'EXC-00011',
    'BRA45' => 'DT-00064',
    'BRA46' => 'DT-00065',
    'BRA47' => 'DT-00062',
    'BRA48' => 'DT-00048',
    'BRA49' => 'DT-00047',
    'VIBROWBRA01' => 'SD-00001',
    'VIBROWBRA02' => 'PF-00004',
    'VIBROWBRA03' => 'SD-00004',
    'VIBROWBRA06' => 'SUP-00009',
    'VIBROWBRA07' => 'SD-00005',
    'PF03' => 'PF-00003',
    'SD04' => 'SD-00004',
    'SD06' => 'SUP-00009',
    'SD08' => 'SD-00005'
];

foreach ($manualAliases as $k => $v) {
    if (isset($assetById[$v])) {
        $lookup[$k] = $v;
    }
}

// 3. Helper to parse Indonesian date strings
function parse_target_date(?string $d): ?string {
    if (!$d) return null;
    $d = trim($d);
    if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $d)) return $d;

    $months = [
        'JANUARI' => '01', 'JAN' => '01',
        'FEBRUARI' => '02', 'FEB' => '02',
        'MARET' => '03', 'MAR' => '03',
        'APRIL' => '04', 'APR' => '04',
        'MEI' => '05', 'MAY' => '05',
        'JUNI' => '06', 'JUN' => '06',
        'JULI' => '07', 'JUL' => '07',
        'AGUSTUS' => '08', 'AGS' => '08', 'AUG' => '08',
        'SEPTEMBER' => '09', 'SEP' => '09',
        'OKTOBER' => '10', 'OKT' => '10', 'OCT' => '10',
        'NOVEMBER' => '11', 'NOV' => '11',
        'DESEMBER' => '12', 'DES' => '12', 'DEC' => '12'
    ];

    if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/i', $d, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mName = strtoupper($m[2]);
        if (isset($months[$mName])) {
            return "{$m[3]}-{$months[$mName]}-{$day}";
        }
    }

    if (preg_match('/\((\d{1,2})\s+([A-Za-z]+)\s+(\d{4})\)/i', $d, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mName = strtoupper($m[2]);
        if (isset($months[$mName])) {
            return "{$m[3]}-{$months[$mName]}-{$day}";
        }
    }

    return null;
}

// 4. Parse 07_UNIT_STANDBY.md
$lines07 = file($baseDir . '/material/ASSET/07_UNIT_STANDBY.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$enrichmentPlan = [];

foreach ($lines07 as $line) {
    $line = trim($line);
    if (!str_starts_with($line, '|')) continue;
    $c = array_map('trim', explode('|', substr($line, 1, -1)));
    if (!isset($c[1]) || !ctype_digit($c[1])) continue;

    $nama = $c[2] ?? '';
    $lokasi = $c[5] ?? '';
    $statusRaw = $c[6] ?? '';
    $nextAction = $c[7] ?? '';
    $targetRaw = $c[9] ?? '';
    $snPlat = $c[10] ?? '';
    $hull = $c[11] ?? '';

    $targetAid = null;

    if (!empty($hull)) {
        $ch = clean_str($hull);
        if (isset($lookup[$ch])) $targetAid = $lookup[$ch];
    }
    if (!$targetAid && !empty($snPlat)) {
        foreach (preg_split('/[\/\,]/', $snPlat) as $part) {
            $cp = clean_str($part);
            if ($cp && isset($lookup[$cp])) {
                $targetAid = $lookup[$cp];
                break;
            }
        }
    }

    if (!$targetAid) continue;

    $targetDate = parse_target_date($targetRaw);

    $actionPlan = '';
    if (!empty($nextAction) && !in_array(strtoupper($nextAction), ['-', '#VALUE!'])) {
        $actionPlan = $nextAction;
    } elseif (!empty($statusRaw) && in_array(strtoupper($statusRaw), ['SEWA PAK KELMI', 'OTW DURI', 'STANDBY YARD (PERBAIKAN)'])) {
        $actionPlan = $statusRaw;
    }

    // Default standby start date from document header (Februari 2026)
    $startDate = '2026-02-15';

    if (!isset($enrichmentPlan[$targetAid])) {
        $target = $assetById[$targetAid];
        $updates = [];

        if (!empty($actionPlan)) {
            $updates['standby_action_plan'] = $actionPlan;
        }
        if (!empty($targetDate)) {
            $updates['standby_target_date'] = $targetDate;
        }
        if (!empty($startDate) && empty($target['standby_start_date'])) {
            $updates['standby_start_date'] = $startDate;
        }

        if (!empty($lokasi) && empty(trim((string)$target['raw_location_notes']))) {
            $updates['raw_location_notes'] = $lokasi;
        }
        if (!empty($lokasi) && empty(trim((string)$target['operational_position']))) {
            $updates['operational_position'] = $lokasi;
        }

        if (!empty($updates)) {
            $enrichmentPlan[$targetAid] = [
                'asset_id' => $targetAid,
                'make_model' => $target['make_model'] ?: $nama,
                'current_status' => $target['status'],
                'updates' => $updates
            ];
        }
    }
}

// Resolve the single ambiguous source alias to the value stored in the latest
// verified dump. Keep the remaining locally enriched fields untouched.
if (isset($assetById['DZ-00010'])) {
    if (!isset($enrichmentPlan['DZ-00010'])) {
        $enrichmentPlan['DZ-00010'] = [
            'asset_id' => 'DZ-00010',
            'make_model' => $assetById['DZ-00010']['make_model'],
            'current_status' => $assetById['DZ-00010']['status'],
            'updates' => [],
        ];
    }
    $enrichmentPlan['DZ-00010']['updates']['standby_action_plan'] = 'JUAL';
}

if (isset($assetById['EXC-00011'])) {
    if (!isset($enrichmentPlan['EXC-00011'])) {
        $enrichmentPlan['EXC-00011'] = [
            'asset_id' => 'EXC-00011',
            'make_model' => $assetById['EXC-00011']['make_model'],
            'current_status' => $assetById['EXC-00011']['status'],
            'updates' => [],
        ];
    }
    $enrichmentPlan['EXC-00011']['updates']['standby_action_plan'] = null;
    $enrichmentPlan['EXC-00011']['updates']['standby_target_date'] = null;
}

echo "================ STRATEGY: STEP 4 (BATCH 5 STANDBY ACTION PLAN) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Assets to Enrich with Standby Intelligence: " . count($enrichmentPlan) . " units\n\n";

$planCounters = [
    'standby_action_plan' => 0,
    'standby_target_date' => 0,
    'standby_start_date' => 0,
    'raw_location_notes' => 0,
    'operational_position' => 0
];

foreach ($enrichmentPlan as $p) {
    foreach ($p['updates'] as $k => $v) {
        $planCounters[$k]++;
    }
}

echo "Breakdown Penambahan Bidang Standby:\n";
foreach ($planCounters as $k => $cnt) {
    echo sprintf("  - %-25s: %d units\n", $k, $cnt);
}

echo "\nSample Rencana Penambahan:\n";
$sampleIdx = 0;
foreach ($enrichmentPlan as $aid => $p) {
    if (++$sampleIdx > 10) break;
    echo "  * {$aid} ({$p['make_model']}) [Status Saat Ini: {$p['current_status']}]:\n";
    foreach ($p['updates'] as $f => $val) {
        echo "      + {$f} = '{$val}'\n";
    }
}

if ($isCommit) {
    echo "\nMengeksekusi penambahan data standby ke database dalam transaksi...\n";
    $pdo->beginTransaction();
    try {
        $updateCount = 0;
        foreach ($enrichmentPlan as $aid => $p) {
            $setParts = [];
            $params = [':aid' => $aid];

            foreach ($p['updates'] as $col => $val) {
                $setParts[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $val;
            }

            $sql = "UPDATE assets SET " . implode(', ', $setParts) . ", last_update_timestamp = NOW() WHERE asset_id = :aid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $updateCount++;
        }

        $pdo->commit();
        echo "SUCCESS: {$updateCount} unit aset berhasil diperkaya dengan rencana tindak lanjut standby dan target tanggal penyelesaian!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nUntuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
