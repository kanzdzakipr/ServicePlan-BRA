<?php
declare(strict_types=1);

/**
 * Strategy: Step 6 (Batch 7: Technical Identity, Serial Numbers, Chassis VIN, Capacity, Asset Age, and Dealer Enrichment)
 * Source:
 *   - material/ASSET/02_List_Data_Aset_BRA.md (Kapasitas, Dealer, Tahun, Usia, SN Alat Berat)
 *   - material/ASSET/05_List_rekap_dokumen.md (Factory Serial Numbers Alat Berat)
 *   - material/ASSET/14_Copy_of_List_Data_Aset_BRA.md (Chassis VIN Dump Truck & Kapasitas)
 * Target:
 *   - `assets` (`asset_age`, `chassis_number`, `serial_number`, `capacity`, `dealer`)
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

// 1. Ambil seluruh aset DB
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
    if ($a['previous_license_plate']) {
        $cp = clean_str($a['previous_license_plate']);
        if ($cp) $lookup[$cp] = $aid;
    }
}

// 2. Ingest Cross-Reference Mapping Files untuk alias
function ingest_map(string $file, int $cPlat, int $cKode, int $cHull, array &$lookup, array $assetById): void {
    if (!file_exists($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $l) {
        if (!str_starts_with(trim($l), '|')) continue;
        $c = array_map('trim', explode('|', substr(trim($l), 1, -1)));
        if (!isset($c[0]) || !ctype_digit($c[0])) continue;
        $plat = $c[$cPlat] ?? '';
        $kode = $c[$cKode] ?? '';
        $hull = $c[$cHull] ?? '';

        $target = null;
        if ($hull && isset($assetById[$hull])) $target = $hull;
        elseif ($hull && isset($lookup[clean_str($hull)])) $target = $lookup[clean_str($hull)];
        elseif ($kode && isset($lookup[clean_str($kode)])) $target = $lookup[clean_str($kode)];
        elseif ($plat) {
            foreach (preg_split('/[\/\,]/', $plat) as $p) {
                $cp = clean_str($p);
                if ($cp && isset($lookup[$cp])) { $target = $lookup[$cp]; break; }
            }
        }

        if ($target) {
            if ($hull) $lookup[clean_str($hull)] = $target;
            if ($kode) $lookup[clean_str($kode)] = $target;
            if ($plat) {
                foreach (preg_split('/[\/\,]/', $plat) as $p) {
                    $cp = clean_str($p);
                    if ($cp) $lookup[$cp] = $target;
                }
            }
        }
    }
}

ingest_map($baseDir . '/material/ASSET/06_PROJECT_WUR.md', 4, 6, 7, $lookup, $assetById);
ingest_map($baseDir . '/material/ASSET/14_Copy_of_List_Data_Aset_BRA.md', 4, 7, 8, $lookup, $assetById);
ingest_map($baseDir . '/material/ASSET/08_Copy_of_List_Data_Aset_BRA_3.md', 5, 8, 9, $lookup, $assetById);
ingest_map($baseDir . '/material/ASSET/02_List_Data_Aset_BRA.md', 7, 10, 12, $lookup, $assetById);

// Manual Aliases
$manualAliases = [
    'EXCA04' => 'EXC-00004', 'EXCA03' => 'EXC-00003', 'EXCA05' => 'EXC-00005',
    'EXCA07' => 'EXC-00014', 'EXCA08' => 'EXC-00008', 'EXCA10' => 'EXC-00010',
    'EXCA11' => 'EXC-00011', 'EXCA12' => 'EXC-00012', 'EXCA13' => 'EXC-00010',
    'EXCA14' => 'EXC-00014', 'EXCA15' => 'EXC-00015', 'EXCA16' => 'EXC-00016',
    'EXCA17' => 'EXC-00017', 'EXCA18' => 'EXC-06006', 'BRA04' => 'EXC-00004',
    'BRA01' => 'DZ-00010', 'BRA07' => 'DZ-00007', 'BRA10' => 'EXC-00010',
    'BRA11' => 'EXC-00011', 'BRA14' => 'EXC-00014', 'BRA15' => 'EXC-00015',
    'VIBROWBRA01' => 'SD-00001', 'VIBROWBRA02' => 'PF-00004',
    'VIBROWBRA03' => 'SD-00004', 'VIBROWBRA06' => 'SUP-00009',
    'VIBROWBRA07' => 'SD-00005', 'PF03' => 'PF-00003', 'SD04' => 'SD-00004',
    'SD06' => 'SUP-00009', 'SD08' => 'SD-00005'
];
foreach ($manualAliases as $k => $v) {
    if (isset($assetById[$v])) $lookup[$k] = $v;
}

// 3. Mapping baris 02_List_Data_Aset_BRA.md
$lines02 = file($baseDir . '/material/ASSET/02_List_Data_Aset_BRA.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$rowToAsset = [];

foreach ($lines02 as $line) {
    if (!str_starts_with(trim($line), '|')) continue;
    $c = array_map('trim', explode('|', substr(trim($line), 1, -1)));
    if (!isset($c[0]) || !ctype_digit($c[0])) continue;
    $row = (int)$c[0];
    if ($row < 6) continue;

    $nama = $c[3] ?? '';
    $dealer = $c[4] ?? '';
    $tahun = (int)($c[5] ?? 0);
    $snPlat = $c[7] ?? '';
    $kapasitas = $c[9] ?? '';
    $kode = $c[10] ?? '';
    $hull = $c[12] ?? '';
    $usia = (int)($c[23] ?? 0);

    $targetAid = null;
    if ($hull && isset($assetById[$hull])) $targetAid = $hull;
    elseif ($hull && isset($lookup[clean_str($hull)])) $targetAid = $lookup[clean_str($hull)];
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
        $rowToAsset[$row] = [
            'aid' => $targetAid,
            'nama' => $nama,
            'dealer' => $dealer,
            'tahun' => $tahun,
            'kapasitas' => $kapasitas,
            'snPlat' => $snPlat,
            'usia' => $usia
        ];
    }
}

// 4. Parse Chassis VIN dari 14_Copy_of_List_Data_Aset_BRA.md
$lines14 = file($baseDir . '/material/ASSET/14_Copy_of_List_Data_Aset_BRA.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$vinsFrom14 = [];

foreach ($lines14 as $l) {
    if (!str_starts_with(trim($l), '|')) continue;
    $c = array_map('trim', explode('|', substr(trim($l), 1, -1)));
    if (!isset($c[0]) || !ctype_digit($c[0])) continue;

    $snPlat = $c[4] ?? '';
    $kode = $c[7] ?? '';
    $hull = $c[8] ?? '';
    $vin = trim($c[13] ?? '');

    if (empty($vin) || strlen($vin) < 10 || !preg_match('/^[A-Z0-9]+$/', $vin)) continue;
    if (in_array(strtoupper($vin), ['NOVRIANSYAH', 'RIDHO', 'WAREEN', 'M.YUNUS', 'ALDIYOWANDA', 'AGUNG'])) continue;

    $target = null;
    if ($hull && isset($lookup[clean_str($hull)])) $target = $lookup[clean_str($hull)];
    elseif ($kode && isset($lookup[clean_str($kode)])) $target = $lookup[clean_str($kode)];
    elseif ($snPlat) {
        $cp = clean_str($snPlat);
        if ($cp && isset($lookup[$cp])) $target = $lookup[$cp];
    }

    if ($target) {
        $vinsFrom14[$target] = $vin;
    }
}

// 5. Parse Serial Numbers dari 05_List_rekap_dokumen.md untuk Heavy Equipment
$lines05 = file($baseDir . '/material/ASSET/05_List_rekap_dokumen.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$heavySnsFrom05 = [];

foreach ($lines05 as $l) {
    if (!str_starts_with(trim($l), '|')) continue;
    $c = array_map('trim', explode('|', substr(trim($l), 1, -1)));
    if (!isset($c[0]) || !ctype_digit($c[0])) continue;
    $row = (int)$c[0];
    $sn = trim($c[5] ?? '');

    if (empty($sn) || in_array(strtoupper($sn), ['FALSE', 'TRUE', '-', 'NO SERIAL', 'TIDAK ADA'])) continue;
    if (preg_match('/^[A-Z]{1,2}\s*\d{1,4}/i', $sn) || strpos($sn, '/') !== false) continue;

    if (isset($rowToAsset[$row])) {
        $aid = $rowToAsset[$row]['aid'];
        $cat = $assetById[$aid]['category'];
        if (!in_array($cat, ['Dump Truck', 'Light Vehicle'])) {
            $heavySnsFrom05[$aid] = $sn;
        }
    }
}

// 6. Rencana Pembaruan
$enrichmentPlan = [];

foreach ($assetById as $aid => $a) {
    $updates = [];

    // A. Usia Aset (asset_age)
    if (empty($a['asset_age']) || (int)$a['asset_age'] === 0) {
        $calcAge = null;
        if (!empty($a['year_manufacture']) && $a['year_manufacture'] > 1990 && $a['year_manufacture'] <= 2026) {
            $calcAge = 2026 - (int)$a['year_manufacture'];
        }
        if ($calcAge !== null) {
            $updates['asset_age'] = $calcAge;
        }
    }

    // B. Chassis Number (VIN) untuk Dump Truck / Vehicle
    if (empty($a['chassis_number']) && isset($vinsFrom14[$aid])) {
        $updates['chassis_number'] = $vinsFrom14[$aid];
    }

    // C. Serial Number (serial_number) untuk Heavy Equipment
    if (empty($a['serial_number'])) {
        if (isset($heavySnsFrom05[$aid])) {
            $updates['serial_number'] = $heavySnsFrom05[$aid];
        } else {
            // Cek di 02 jika ada SN murni dan bukan kendaraan jalan raya
            foreach ($rowToAsset as $r) {
                if ($r['aid'] === $aid && !empty($r['snPlat'])) {
                    $raw = trim($r['snPlat']);
                    if (!preg_match('/^[A-Z]{1,2}\s*\d{1,4}/i', $raw) && strlen($raw) >= 4 && !in_array($a['category'], ['Dump Truck', 'Light Vehicle'])) {
                        $updates['serial_number'] = $raw;
                        break;
                    }
                }
            }
        }
    }

    // D. Kapasitas (capacity)
    if (empty($a['capacity']) || preg_match('/^(Agung|Hafisudin|Ilham|Santo|Jumarno)/i', (string)$a['capacity'])) {
        foreach ($rowToAsset as $r) {
            if ($r['aid'] === $aid && !empty($r['kapasitas'])) {
                $rawCap = trim($r['kapasitas']);
                if (preg_match('/(HP|kW|TON|M3|Kg|METER)/i', $rawCap) || is_numeric($rawCap)) {
                    $updates['capacity'] = $rawCap;
                    break;
                }
            }
        }
    }

    // E. Dealer (dealer)
    if (empty($a['dealer'])) {
        foreach ($rowToAsset as $r) {
            if ($r['aid'] === $aid && !empty($r['dealer']) && !in_array($r['dealer'], ['-', '0'])) {
                $updates['dealer'] = trim($r['dealer']);
                break;
            }
        }
    }

    if (!empty($updates)) {
        $enrichmentPlan[$aid] = [
            'aid' => $aid,
            'make_model' => $a['make_model'],
            'category' => $a['category'],
            'updates' => $updates
        ];
    }
}

echo "================ STRATEGY: STEP 6 (BATCH 7 TECHNICAL IDENTITY ENRICHMENT) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Assets to Enrich with Technical Identity: " . count($enrichmentPlan) . " units\n\n";

$updateCounts = [
    'asset_age' => 0,
    'chassis_number' => 0,
    'serial_number' => 0,
    'capacity' => 0,
    'dealer' => 0
];

foreach ($enrichmentPlan as $p) {
    foreach ($p['updates'] as $k => $v) {
        $updateCounts[$k]++;
    }
}

echo "Rincian Atribut Teknis yang Akan Diperkaya:\n";
foreach ($updateCounts as $k => $c) {
    echo sprintf("  - %-22s: %d units\n", $k, $c);
}

echo "\nSample 15 Unit dengan Perubahan Lengkap:\n";
$cnt = 0;
foreach ($enrichmentPlan as $aid => $p) {
    if (++$cnt > 15) break;
    echo "  * {$aid} ({$p['make_model']}) [{$p['category']}]:\n";
    foreach ($p['updates'] as $f => $val) {
        echo "      + {$f} = '{$val}'\n";
    }
}

if ($isCommit) {
    echo "\nMengeksekusi pembaruan identitas teknis ke database dalam transaksi...\n";
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
        echo "SUCCESS: {$updateCount} unit aset berhasil diperkaya dengan identitas teknis otentik!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nUntuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
