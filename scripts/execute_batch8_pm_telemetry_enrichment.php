<?php
declare(strict_types=1);

/**
 * Strategy: Step 7 (Batch 8: Preventive Maintenance Telemetry, Service Intervals, and Due Targets Enrichment)
 * Source:
 *   - material/PREVENTIVE-MAINTENANCE/Plan_Service_Juli_2026_WUR_EW_Project.md (Real Tracking HM/KM, Service Intervals, Last Service History & Realization)
 * Target:
 *   - `assets` (`last_hm_km`, `service_interval_val`, `service_interval_unit`, `last_service_hm_km`, `last_service_date`, `next_service_due_hm_km`, `telematics_last_comm`)
 */

$isCommit = in_array('--commit', $argv, true);
$baseDir = dirname(__DIR__);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

function clean_str(?string $s): string {
    if (!$s) return '';
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', $s));
}

function parse_date_str(?string $d): ?string {
    if (!$d) return null;
    $d = trim($d);
    if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $d)) return $d;

    $months = [
        'JAN' => '01', 'JANUARI' => '01',
        'FEB' => '02', 'FEBRUARI' => '02',
        'MAR' => '03', 'MARET' => '03',
        'APR' => '04', 'APRIL' => '04',
        'MEI' => '05', 'MAY' => '05',
        'JUN' => '06', 'JUNI' => '06',
        'JUL' => '07', 'JULI' => '07',
        'AGS' => '08', 'AGUSTUS' => '08', 'AUG' => '08',
        'SEP' => '09', 'SEPTEMBER' => '09',
        'OKT' => '10', 'OKTOBER' => '10', 'OCT' => '10',
        'NOV' => '11', 'NOVEMBER' => '11',
        'DES' => '12', 'DESEMBER' => '12', 'DEC' => '12'
    ];

    if (preg_match('/^(\d{1,2})[\-\s]+([A-Za-z]+)[\-\s]+(\d{4})$/i', $d, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mName = strtoupper(substr($m[2], 0, 3));
        if (isset($months[$mName])) return "{$m[3]}-{$months[$mName]}-{$day}";
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/?(\d{4})$/', $d, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mon = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        return "{$m[3]}-{$mon}-{$day}";
    }

    if (preg_match('/^(\d{1,2})\-(\d{2})(\d{4})$/', $d, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        return "{$m[3]}-{$m[2]}-{$day}";
    }

    if (preg_match('/^(\d{1,2})\-([A-Za-z]+)\-(\d{2})$/i', $d, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mName = strtoupper(substr($m[2], 0, 3));
        $yr = '20' . $m[3];
        if (isset($months[$mName])) return "{$yr}-{$months[$mName]}-{$day}";
    }

    return null;
}

function parse_num(?string $n): ?float {
    if ($n === null || $n === '') return null;
    $clean = trim(str_replace([' ', '.'], '', $n));
    $clean = str_replace(',', '.', $clean);
    return is_numeric($clean) ? (float)$clean : null;
}

// 1. Ambil data aset saat ini
$dbAssets = $pdo->query("SELECT * FROM assets")->fetchAll();
$assetById = [];
$lookup = [];

foreach ($dbAssets as $a) {
    $aid = $a['asset_id'];
    $assetById[$aid] = $a;
    $lookup[clean_str($aid)] = $aid;
    $lookup[clean_str($a['asset_code'])] = $aid;
    if ($a['hull_number']) $lookup[clean_str($a['hull_number'])] = $aid;
    if ($a['alias_name']) $lookup[clean_str($a['alias_name'])] = $aid;
    if ($a['license_plate']) {
        foreach (preg_split('/[\/\,]/', $a['license_plate']) as $p) {
            $cp = clean_str($p);
            if ($cp) $lookup[$cp] = $aid;
        }
    }
}

// 2. Parse file Plan Service Juli 2026
$lines = file($baseDir . '/material/PREVENTIVE-MAINTENANCE/Plan_Service_Juli_2026_WUR_EW_Project.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Parse Realisasi PM (Section 2)
$realisasi = [];
$inSection2 = false;

foreach ($lines as $l) {
    if (strpos($l, '# 2. Realisasi PM Service') !== false) {
        $inSection2 = true;
        continue;
    }
    if ($inSection2 && strpos($l, '# 3. Ringkasan') !== false) {
        $inSection2 = false;
        break;
    }
    if (!$inSection2 || !str_starts_with(trim($l), '|')) continue;

    $c = array_map('trim', explode('|', substr(trim($l), 1, -1)));
    if (!isset($c[0]) || !ctype_digit($c[0])) continue;

    $no = (int)$c[0];
    $realHM = parse_num($c[5] ?? null);
    $realDate = parse_date_str($c[6] ?? null);

    if ($realHM !== null && $realDate !== null) {
        $realisasi[$no] = [
            'real_hm' => $realHM,
            'real_date' => $realDate
        ];
    }
}

// Parse Rencana Service (Section 1)
$planUnits = [];
$inSection1 = false;

foreach ($lines as $l) {
    if (strpos($l, '# 1. Rencana Service') !== false) {
        $inSection1 = true;
        continue;
    }
    if ($inSection1 && strpos($l, '# 2. Realisasi') !== false) {
        $inSection1 = false;
        break;
    }
    if (!$inSection1 || !str_starts_with(trim($l), '|')) continue;

    $c = array_map('trim', explode('|', substr(trim($l), 1, -1)));
    if (!isset($c[0]) || !ctype_digit($c[0])) continue;

    $no = (int)$c[0];
    $kode = $c[1] ?? '';
    $hull = $c[2] ?? '';
    $nama = $c[3] ?? '';
    $snPlat = $c[4] ?? '';
    $trackHM = parse_num($c[7] ?? null);
    $trackDate = parse_date_str($c[8] ?? null);
    $interval = parse_num($c[9] ?? null);
    $lastServiceHM = parse_num($c[10] ?? null);
    $lastServiceDate = parse_date_str($c[11] ?? null);
    $planServiceHM = parse_num($c[12] ?? null);

    // Target AID
    $targetAid = null;
    if ($hull && isset($lookup[clean_str($hull)])) $targetAid = $lookup[clean_str($hull)];
    elseif ($kode && isset($lookup[clean_str($kode)])) $targetAid = $lookup[clean_str($kode)];
    elseif ($snPlat) {
        foreach (preg_split('/[\/\,]/', $snPlat) as $p) {
            $cp = clean_str($p);
            if ($cp && isset($lookup[$cp])) {
                $targetAid = $lookup[$cp];
                break;
            }
        }
    }

    if ($targetAid) {
        $planUnits[$targetAid] = [
            'no' => $no,
            'aid' => $targetAid,
            'track_hm' => $trackHM,
            'track_date' => $trackDate,
            'interval' => $interval,
            'last_service_hm' => $lastServiceHM,
            'last_service_date' => $lastServiceDate,
            'plan_service_hm' => $planServiceHM,
            'realisasi' => $realisasi[$no] ?? null
        ];
    }
}

// 3. Rencana Pembaruan Komprehensif
$pmUpdates = [];

foreach ($assetById as $aid => $a) {
    $cat = $a['category'];
    $updates = [];

    // Tentukan unit dan nilai interval default berdasarkan kategori
    $isHeavy = in_array($cat, ['Excavator', 'Bulldozer', 'Vibro Compactor', 'Motor Grader', 'Reclaimer Spreader', 'Other']);
    $defaultUnit = $isHeavy ? 'HM' : 'KM';
    $defaultVal = $isHeavy ? 500 : 10000;

    if (empty($a['service_interval_unit'])) {
        $updates['service_interval_unit'] = $defaultUnit;
    }
    if (empty($a['service_interval_val']) || (int)$a['service_interval_val'] === 0) {
        $updates['service_interval_val'] = $defaultVal;
    }

    // Jika aset terdaftar dalam dokumen Plan Service Juli 2026 WUR EW
    if (isset($planUnits[$aid])) {
        $p = $planUnits[$aid];

        if (!empty($p['interval'])) {
            $updates['service_interval_val'] = (int)$p['interval'];
        }

        if ($p['realisasi']) {
            // Jika ada realisasi servis di Juli 2026:
            // - last_service_hm_km diperbarui ke HM realisasi
            // - last_service_date diperbarui ke tanggal realisasi PM
            // - last_hm_km diset ke realisasi HM (karena unit telah mencapai HM tersebut)
            // - next_service_due_hm_km diperbarui ke realisasi + interval
            $updates['last_service_hm_km'] = $p['realisasi']['real_hm'];
            $updates['last_service_date'] = $p['realisasi']['real_date'] . ' 08:00:00';
            $updates['last_hm_km'] = $p['realisasi']['real_hm'];
            $updates['next_service_due_hm_km'] = $p['realisasi']['real_hm'] + ($p['interval'] ?: $defaultVal);
            $updates['telematics_last_comm'] = $p['realisasi']['real_date'] . ' 17:00:00';
        } else {
            // Jika belum ada realisasi (masih planned):
            if ($p['track_hm'] !== null) {
                $updates['last_hm_km'] = $p['track_hm'];
            }
            if ($p['last_service_hm'] !== null) {
                $updates['last_service_hm_km'] = $p['last_service_hm'];
            }
            if ($p['last_service_date'] !== null) {
                $updates['last_service_date'] = $p['last_service_date'] . ' 08:00:00';
            }
            if ($p['plan_service_hm'] !== null) {
                $updates['next_service_due_hm_km'] = $p['plan_service_hm'];
            }
            if ($p['track_date'] !== null) {
                $updates['telematics_last_comm'] = $p['track_date'] . ' 17:00:00';
            }
        }
    }

    if (!empty($updates)) {
        $pmUpdates[$aid] = [
            'aid' => $aid,
            'make_model' => $a['make_model'],
            'category' => $a['category'],
            'has_pm_source' => isset($planUnits[$aid]),
            'updates' => $updates
        ];
    }
}

echo "================ STRATEGY: STEP 7 (BATCH 8 PREVENTIVE MAINTENANCE TELEMETRY) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Assets to Enrich with PM Telemetry: " . count($pmUpdates) . " units\n\n";

$counters = [
    'service_interval_unit' => 0,
    'service_interval_val' => 0,
    'last_hm_km' => 0,
    'last_service_hm_km' => 0,
    'last_service_date' => 0,
    'next_service_due_hm_km' => 0,
    'telematics_last_comm' => 0
];

foreach ($pmUpdates as $p) {
    foreach ($p['updates'] as $k => $v) {
        $counters[$k]++;
    }
}

echo "Rincian Bidang PM yang Akan Diperkaya:\n";
foreach ($counters as $k => $c) {
    echo sprintf("  - %-25s: %d units\n", $k, $c);
}

echo "\nSample 10 Unit dengan Pembaruan Lengkap dari Dokumen Plan Service WUR EW:\n";
$cnt = 0;
foreach ($pmUpdates as $aid => $p) {
    if (!$p['has_pm_source']) continue;
    if (++$cnt > 10) break;
    echo "  * {$aid} ({$p['make_model']}) [{$p['category']}]:\n";
    foreach ($p['updates'] as $f => $val) {
        echo "      + {$f} = '{$val}'\n";
    }
}

if ($isCommit) {
    echo "\nMengeksekusi pembaruan parameter preventive maintenance ke database dalam transaksi...\n";
    $pdo->beginTransaction();
    try {
        $updateCount = 0;
        foreach ($pmUpdates as $aid => $p) {
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
        echo "SUCCESS: {$updateCount} unit aset berhasil diperkaya dengan telemetri & jadwal preventive maintenance!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nUntuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
