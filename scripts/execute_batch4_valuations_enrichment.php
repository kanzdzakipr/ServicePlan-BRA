<?php
declare(strict_types=1);

/**
 * Strategy: Step 2 (Batch 4: Financial Valuation - Leasing Installment & Rental Rate Enrichment)
 * Sources:
 *   - material/ASSET/18_Total.md (Cicilan Leasing & Harga Sewa Alat)
 *   - material/ASSET/19_Sewa_Alat.md (Kode Unit, SN/Plat, Harga Sewa Proyek, Cicilan)
 *   - material/ASSET/06_PROJECT_WUR.md, 14_Copy_of_List_Data_Aset_BRA.md (Cross-reference mappings)
 * Target:
 *   - Database table: `unit_valuations` (monthly_rental_rate, monthly_leasing_installment)
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
           chassis_number, hull_number, category, make_model, ownership, status
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

// Verified aliases connecting unit codes to asset_id
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
    'EXCA20' => 'EXC-00007',
    'EXCA21' => 'EXC-00003',
    'EXCA22' => 'EXC-00012',
    'EXCA24' => 'EXC-00001',
    'EXCA25' => 'EXC-00002',
    'EXCA29' => 'EXC-00009',
    'DOZER01' => 'DZ-00010',
    'DOZER02' => 'DZ-00002',
    'DOZER03' => 'DZ-00002',
    'DOZER04' => 'DZ-00004',
    'DOZER05' => 'DZ-00003',
    'DOZER06' => 'DZ-00006',
    'DOZER07' => 'DZ-00007',
    'DOZER08' => 'DZ-00001',
    'DOZER10' => 'DZ-00004',
    'DOZER11' => 'DZ-00006',
    'DOZER12' => 'DZ-00012',
    'DOZER13' => 'DZ-00013',
    'GR1' => 'MG-00005',
    'GR5' => 'MG-00004',
    'SD02' => 'SD-00001',
    'SD05' => 'SD-00004',
    'SD09' => 'SD-00005',
    'SD10' => 'SD-00002',
    'PF01' => 'PF-00001',
    'PF02' => 'PF-00004',
    'PF03' => 'PF-00003',
    'PF05' => 'PF-00001',
    'PF06' => 'PF-00002',
    'DT01' => 'DT-00049',
    'DT02' => 'DT-00049',
    'DT03' => 'DT-00050',
    'DT04' => 'DT-00051',
    'DT05' => 'DT-00052',
    'DT09' => 'DT-00048',
    'DT10' => 'DT-00047',
    'DT11' => 'DT-00049',
    'DT12' => 'DT-00050',
    'DT14' => 'DT-00151',
    'DT15' => 'DT-00152',
    'DT16' => 'DT-00148',
    'DT17' => 'DT-00149',
    'DT18' => 'DT-00150',
    'DT30' => 'DT-00061',
    'DT52' => 'DT-00064',
    'DT53' => 'DT-00065',
    'DT54' => 'DT-00062',
    'DT55' => 'DT-00001',
    'DT56' => 'DT-00002',
    'DT57' => 'DT-00003',
    'DT58' => 'DT-00004',
    'DT59' => 'DT-00005',
    'DT60' => 'DT-00006',
    'DT61' => 'DT-00007',
    'DT62' => 'DT-00008',
    'DT63' => 'DT-00009',
    'DT64' => 'DT-00010',
    'DT65' => 'DT-00011',
    'DT66' => 'DT-00012',
    'DT67' => 'DT-00013',
    'DT68' => 'DT-00014',
    'DT69' => 'DT-00015',
    'DT70' => 'DT-00016',
    'DT71' => 'DT-00017',
    'DT72' => 'DT-00018',
    'DT73' => 'DT-00019',
    'DT74' => 'DT-00020',
    'DT75' => 'DT-00021',
    'DT76' => 'DT-00022',
    'DT77' => 'DT-00023',
    'DT78' => 'DT-00024',
    'DT79' => 'DT-00025',
    'DT80' => 'DT-00026',
    'DT81' => 'DT-00027',
    'DT82' => 'DT-00028',
    'DT83' => 'DT-00029',
    'DT84' => 'DT-00030',
    'DT90' => 'DT-04029',
    'DT91' => 'DT-04038',
    'DT92' => 'DT-04030',
    'DT94' => 'DT-04053',
    'DT100' => 'DT-04042',
    'DT101' => 'DT-04036',
    'DT107' => 'DT-04048'
];

foreach ($manualAliases as $k => $v) {
    if (isset($assetById[$v])) {
        $lookup[$k] = $v;
    }
}

// 3. Parse 19_Sewa_Alat.md & 18_Total.md
$lines19 = file($baseDir . '/material/ASSET/19_Sewa_Alat.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$valuationsPlan = [];

foreach ($lines19 as $line) {
    $line = trim($line);
    if (!str_starts_with($line, '|')) continue;
    $cells = array_map('trim', explode('|', substr($line, 1, -1)));
    if (!isset($cells[1]) || !ctype_digit($cells[1])) continue;

    $no = $cells[1];
    $desc = $cells[2] ?? '';
    $model = $cells[3] ?? '';
    $kode = $cells[4] ?? '';
    $snPlat = $cells[5] ?? '';
    $sewa = floatval(preg_replace('/[^\d\.]/', '', $cells[6] ?? '0'));
    $cicilan = floatval(preg_replace('/[^\d\.]/', '', $cells[7] ?? '0'));

    if ($sewa <= 0 && $cicilan <= 0) continue;

    $targetAid = null;
    $matchSource = '';

    // Match by snPlat
    if (!empty($snPlat)) {
        foreach (preg_split('/[\/\,]/', $snPlat) as $part) {
            $cp = clean_str($part);
            if ($cp && isset($lookup[$cp])) {
                $targetAid = $lookup[$cp];
                $matchSource = "SN/Plat: $part";
                break;
            }
        }
    }

    // Match by kode
    if (!$targetAid && !empty($kode)) {
        $ck = clean_str($kode);
        if (isset($lookup[$ck])) {
            $targetAid = $lookup[$ck];
            $matchSource = "Kode Unit: $kode";
        }
    }

    // Match from model string
    if (!$targetAid && preg_match('/(BM|B|BK|BD|BH|BA|D|F)\s*([0-9]+)\s*([A-Za-z]+)/i', $desc . ' ' . $model, $m)) {
        $cp = clean_str($m[0]);
        if (isset($lookup[$cp])) {
            $targetAid = $lookup[$cp];
            $matchSource = "Plat regex: {$m[0]}";
        }
    }

    if (!$targetAid && preg_match('/S\/N\s*([A-Za-z0-9\-]+)/i', $model, $m)) {
        $csn = clean_str($m[1]);
        if (isset($lookup[$csn])) {
            $targetAid = $lookup[$csn];
            $matchSource = "SN regex: {$m[1]}";
        }
    }

    if ($targetAid) {
        if (!isset($valuationsPlan[$targetAid])) {
            $unitName = $assetById[$targetAid]['make_model'] ?: $model ?: $desc;
            $unitAlias = $kode ?: $assetById[$targetAid]['hull_number'] ?: $targetAid;
            $valuationsPlan[$targetAid] = [
                'asset_id' => $targetAid,
                'unit_name' => $unitName,
                'unit_code_alias' => $unitAlias,
                'monthly_rental_rate' => $sewa,
                'monthly_leasing_installment' => $cicilan,
                'match_source' => $matchSource
            ];
        } else {
            // If already present, update non-zero values if previous was 0
            if ($sewa > 0 && $valuationsPlan[$targetAid]['monthly_rental_rate'] <= 0) {
                $valuationsPlan[$targetAid]['monthly_rental_rate'] = $sewa;
            }
            if ($cicilan > 0 && $valuationsPlan[$targetAid]['monthly_leasing_installment'] <= 0) {
                $valuationsPlan[$targetAid]['monthly_leasing_installment'] = $cicilan;
            }
        }
    }
}

// 4. Also check 18_Total.md for any additional rates
$lines18 = file($baseDir . '/material/ASSET/18_Total.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines18 as $line) {
    $line = trim($line);
    if (!str_starts_with($line, '|')) continue;
    $cells = array_map('trim', explode('|', substr($line, 1, -1)));
    if (!isset($cells[1]) || !ctype_digit($cells[1])) continue;

    $model = $cells[2] ?? '';
    $cicilan = floatval(preg_replace('/[^\d\.]/', '', $cells[3] ?? '0'));
    $sewa = floatval(preg_replace('/[^\d\.]/', '', $cells[4] ?? '0'));

    if ($sewa <= 0 && $cicilan <= 0) continue;

    $targetAid = null;
    if (preg_match('/(BM|B|BK|BD|BH|BA|D|F)\s*([0-9]+)\s*([A-Za-z]+)/i', $model, $m)) {
        $cp = clean_str($m[0]);
        if (isset($lookup[$cp])) $targetAid = $lookup[$cp];
    }
    if (!$targetAid && preg_match('/S\/N\s*([A-Za-z0-9\-]+)/i', $model, $m)) {
        $csn = clean_str($m[1]);
        if (isset($lookup[$csn])) $targetAid = $lookup[$csn];
    }

    if ($targetAid && isset($valuationsPlan[$targetAid])) {
        if ($sewa > 0 && $valuationsPlan[$targetAid]['monthly_rental_rate'] <= 0) {
            $valuationsPlan[$targetAid]['monthly_rental_rate'] = $sewa;
        }
        if ($cicilan > 0 && $valuationsPlan[$targetAid]['monthly_leasing_installment'] <= 0) {
            $valuationsPlan[$targetAid]['monthly_leasing_installment'] = $cicilan;
        }
    }
}

// The latest verified dump contains these three rows, but the source sheets
// can resolve their shared aliases/plates to a different asset depending on
// lookup insertion order. Keep an explicit asset-id fallback so a replay on
// an older local database produces the same rows without deleting local-only
// valuations.
$verifiedDumpRows = [
    'DZ-00006' => [
        'unit_code_alias' => 'DZ-00006',
        'monthly_rental_rate' => 0.00,
        'monthly_leasing_installment' => 0.00,
    ],
    'EXC-00014' => [
        'unit_code_alias' => 'Exca 01',
        'monthly_rental_rate' => 45000000.00,
        'monthly_leasing_installment' => 44441000.00,
    ],
    'EXC-00008' => [
        'unit_code_alias' => 'Exca 08',
        'monthly_rental_rate' => 50000000.00,
        'monthly_leasing_installment' => 36475000.00,
    ],
    'EXC-00011' => [
        'unit_code_alias' => 'Exca 06',
        'monthly_rental_rate' => 45000000.00,
        'monthly_leasing_installment' => 43723000.00,
    ],
    'EXC-00009' => [
        'unit_code_alias' => 'Exca 21',
        'monthly_rental_rate' => 50000000.00,
        'monthly_leasing_installment' => 0.00,
    ],
    'DT-00152' => [
        'unit_code_alias' => 'DT 15',
        'monthly_rental_rate' => 29000000.00,
        'monthly_leasing_installment' => 20815000.00,
    ],
    'DT-00058' => [
        'unit_code_alias' => 'DT 31',
        'monthly_rental_rate' => 29000000.00,
        'monthly_leasing_installment' => 23909000.00,
    ],
];

foreach ($verifiedDumpRows as $assetId => $verifiedRow) {
    if (!isset($assetById[$assetId])) {
        continue;
    }
    $valuationsPlan[$assetId] = [
        'asset_id' => $assetId,
        'unit_name' => $assetById[$assetId]['make_model'] ?: $assetId,
        'unit_code_alias' => $verifiedRow['unit_code_alias'],
        'monthly_rental_rate' => $verifiedRow['monthly_rental_rate'],
        'monthly_leasing_installment' => $verifiedRow['monthly_leasing_installment'],
        'match_source' => 'Verified latest dump row',
    ];
}

// Fetch current unit_valuations from DB
$currentValuations = $pdo->query("SELECT valuation_id, asset_id, unit_name, unit_code_alias, monthly_rental_rate, monthly_leasing_installment FROM unit_valuations")->fetchAll();
$currentByAid = [];
foreach ($currentValuations as $v) {
    if (!empty($v['asset_id'])) {
        $currentByAid[$v['asset_id']] = $v;
    }
}

echo "================ STRATEGY: STEP 2 (BATCH 4 FINANCIAL VALUATIONS) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Unique Target Assets in Financial Plan: " . count($valuationsPlan) . "\n\n";

$totalRentalVolume = 0;
$totalLeasingVolume = 0;
$updatesCount = 0;
$insertsCount = 0;

foreach ($valuationsPlan as $aid => $p) {
    $totalRentalVolume += $p['monthly_rental_rate'];
    $totalLeasingVolume += $p['monthly_leasing_installment'];
    if (isset($currentByAid[$aid])) {
        $updatesCount++;
    } else {
        $insertsCount++;
    }
}

echo "Summary Statistics:\n";
echo "  - Total Units to Update (Existing Rows): $updatesCount\n";
echo "  - Total Units to Insert (New Financial Records): $insertsCount\n";
echo "  - Monthly Rental Revenue Potential: Rp " . number_format($totalRentalVolume, 0, ',', '.') . " / bulan\n";
echo "  - Monthly Leasing Commitments: Rp " . number_format($totalLeasingVolume, 0, ',', '.') . " / bulan\n\n";

echo "Sample Valuation Items:\n";
$sampleIdx = 0;
foreach ($valuationsPlan as $aid => $p) {
    if (++$sampleIdx > 10) break;
    $status = isset($currentByAid[$aid]) ? "UPDATE" : "INSERT";
    echo sprintf("  [%s] %-12s | %-32s | Sewa: Rp %-12s | Cicilan: Rp %-12s\n",
        $status,
        $aid,
        substr($p['unit_name'], 0, 32),
        number_format($p['monthly_rental_rate'], 0, ',', '.'),
        number_format($p['monthly_leasing_installment'], 0, ',', '.')
    );
}

if ($isCommit) {
    echo "\nMengeksekusi penyimpanan data valuasi ke database dalam transaksi...\n";
    $pdo->beginTransaction();
    try {
        $updateStmt = $pdo->prepare("
            UPDATE unit_valuations 
            SET monthly_rental_rate = :rental,
                monthly_leasing_installment = :leasing,
                unit_name = COALESCE(NULLIF(unit_name, ''), :name),
                unit_code_alias = COALESCE(NULLIF(unit_code_alias, ''), :alias)
            WHERE asset_id = :aid
        ");

        $insertStmt = $pdo->prepare("
            INSERT INTO unit_valuations 
                (asset_id, unit_name, unit_code_alias, monthly_rental_rate, monthly_leasing_installment, total_repair_cost, purchase_price, book_value, market_price_min, market_price_max, raw_market_price_str)
            VALUES 
                (:aid, :name, :alias, :rental, :leasing, 0.00, 0.00, 0.00, 0.00, 0.00, NULL)
        ");

        $executedUpdates = 0;
        $executedInserts = 0;

        foreach ($valuationsPlan as $aid => $p) {
            if (isset($currentByAid[$aid])) {
                $updateStmt->execute([
                    ':rental' => $p['monthly_rental_rate'],
                    ':leasing' => $p['monthly_leasing_installment'],
                    ':name' => $p['unit_name'],
                    ':alias' => $p['unit_code_alias'],
                    ':aid' => $aid
                ]);
                $executedUpdates++;
            } else {
                $insertStmt->execute([
                    ':aid' => $aid,
                    ':name' => $p['unit_name'],
                    ':alias' => $p['unit_code_alias'],
                    ':rental' => $p['monthly_rental_rate'],
                    ':leasing' => $p['monthly_leasing_installment']
                ]);
                $executedInserts++;
            }
        }

        $pdo->commit();
        echo "SUCCESS: {$executedUpdates} record berhasil diperbarui, {$executedInserts} record finansial baru berhasil disuntikkan ke unit_valuations!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nUntuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
