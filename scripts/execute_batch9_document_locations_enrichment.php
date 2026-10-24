<?php
declare(strict_types=1);

/**
 * Strategy: Step 8 (Batch 9: Document Repository Tracking - Invoice, BPKB & STNK Physical Locations Enrichment)
 * Source:
 *   - material/ASSET/02_List_Data_Aset_BRA.md (Lokasi Invoice, Status Lunas/Leasing, Lokasi Fisik BPKB)
 *   - material/ASSET/05_List_rekap_dokumen.md (Status Arsip Dokumen Legalitas & Leasing)
 * Target:
 *   - `assets` (`invoice_location`, `bpkb_location`, `stnk_location`)
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

// 3. Mapping metadata sheet 02
$lines02 = file($baseDir . '/material/ASSET/02_List_Data_Aset_BRA.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$assetMeta02 = [];

foreach ($lines02 as $l) {
    if (!str_starts_with(trim($l), '|')) continue;
    $c = array_map('trim', explode('|', substr(trim($l), 1, -1)));
    if (!isset($c[0]) || !ctype_digit($c[0])) continue;
    $row = (int)$c[0];
    if ($row < 6) continue;

    $snPlat = $c[7] ?? '';
    $kode = $c[10] ?? '';
    $hull = $c[12] ?? '';
    $col17 = $c[17] ?? '';
    $col18 = $c[18] ?? '';
    $col19 = $c[19] ?? '';

    $target = null;
    if ($hull && isset($assetById[$hull])) $target = $hull;
    elseif ($hull && isset($lookup[clean_str($hull)])) $target = $lookup[clean_str($hull)];
    elseif ($kode && isset($lookup[clean_str($kode)])) $target = $lookup[clean_str($kode)];
    elseif ($snPlat) {
        foreach (preg_split('/[\/\,]/', $snPlat) as $p) {
            $cp = clean_str($p);
            if ($cp && isset($lookup[$cp])) { $target = $lookup[$cp]; break; }
        }
    }

    if ($target && !isset($assetMeta02[$target])) {
        $assetMeta02[$target] = [
            'col17' => $col17,
            'col18' => $col18,
            'col19' => $col19
        ];
    }
}

// 4. Bangun Rencana Pengayaan Dokumen
$docPlan = [];

foreach ($dbAssets as $a) {
    $aid = $a['asset_id'];
    $m = $assetMeta02[$aid] ?? null;
    $updates = [];

    // A. Invoice Location
    if (empty($a['invoice_location'])) {
        $inv = '';
        if ($m) {
            if (!empty($m['col17']) && in_array(strtoupper($m['col17']), ['PEKANBARU', 'JAKARTA', 'DURI', 'HO', 'PROSES PENGURUSAN'])) {
                $inv = strtoupper($m['col17']) === 'HO' ? 'Head Office Jakarta' : ucfirst(strtolower($m['col17']));
            } elseif (!empty($m['col18']) && in_array(strtoupper($m['col18']), ['PEKANBARU', 'JAKARTA', 'DURI', 'HO'])) {
                $inv = strtoupper($m['col18']) === 'HO' ? 'Head Office Jakarta' : ucfirst(strtolower($m['col18']));
            }
        }
        if (empty($inv)) {
            $pos = strtoupper((string)($a['operational_position'] . ' ' . $a['project_name']));
            if (strpos($pos, 'JAKARTA') !== false || strpos($pos, 'HO') !== false) $inv = 'Head Office Jakarta';
            elseif (strpos($pos, 'PEKANBARU') !== false) $inv = 'Head Office Pekanbaru';
            else $inv = 'Kantor Operasional Duri';
        }
        $updates['invoice_location'] = $inv;
    }

    // B. BPKB Location
    if (empty($a['bpkb_location'])) {
        $bpkb = '';
        $rawLeasing = ($m['col17'] ?? '') . ' ' . ($m['col18'] ?? '');
        if (stripos($rawLeasing, 'CSUL') !== false) $bpkb = 'CSUL Finance (Chandra Sakti Utama)';
        elseif (stripos($rawLeasing, 'MTF') !== false) $bpkb = 'Mandiri Tunas Finance (MTF)';
        elseif (stripos($rawLeasing, 'SANF') !== false) $bpkb = 'SANF (Surya Artha Nusantara Finance)';
        elseif (stripos($rawLeasing, 'BUANA') !== false) $bpkb = 'Buana Finance';
        elseif (stripos($rawLeasing, 'MITSUI') !== false) $bpkb = 'Mitsui Leasing Capital';
        elseif (stripos($rawLeasing, 'BFI') !== false) $bpkb = 'BFI Finance';
        elseif (stripos($rawLeasing, 'JATIM') !== false) $bpkb = 'Bank Jatim';
        elseif (stripos($rawLeasing, 'CFI') !== false) $bpkb = 'Century Tokyo Leasing (CFI)';
        elseif (stripos($rawLeasing, 'ACC') !== false) $bpkb = 'Astra Credit Companies (ACC)';
        elseif ($a['ownership'] === 'Leasing') $bpkb = 'Perusahaan Pembiayaan (Leasing)';
        elseif ($a['ownership'] === 'Sewa') $bpkb = 'Pihak Ketiga (Vendor Sewa)';
        else {
            $bpkb = 'Head Office Pekanbaru (Asset Treasury)';
        }
        $updates['bpkb_location'] = $bpkb;
    }

    // C. STNK Location
    if (empty($a['stnk_location'])) {
        $stnk = '';
        $pos = strtoupper((string)($a['operational_position'] . ' ' . $a['project_name'] . ' ' . $a['raw_location_notes']));
        if (strpos($pos, 'LAHAT') !== false) $stnk = 'Project Office Lahat';
        elseif (strpos($pos, 'ADERA') !== false) $stnk = 'Project Office Adera';
        elseif (strpos($pos, 'JAKARTA') !== false || strpos($pos, 'HO') !== false) $stnk = 'Head Office Jakarta';
        elseif (strpos($pos, 'PEKANBARU') !== false) $stnk = 'Head Office Pekanbaru';
        else $stnk = 'Kantor Operasional Duri (Unit Field)';

        $updates['stnk_location'] = $stnk;
    }

    if (!empty($updates)) {
        $docPlan[$aid] = [
            'aid' => $aid,
            'make_model' => $a['make_model'],
            'ownership' => $a['ownership'],
            'updates' => $updates
        ];
    }
}

echo "================ STRATEGY: STEP 8 (BATCH 9 DOCUMENT REPOSITORY TRACKING) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Assets to Enrich with Document Tracking: " . count($docPlan) . " units\n\n";

$counters = [
    'invoice_location' => 0,
    'bpkb_location' => 0,
    'stnk_location' => 0
];

foreach ($docPlan as $p) {
    foreach ($p['updates'] as $k => $v) {
        $counters[$k]++;
    }
}

echo "Rincian Bidang Arsip Dokumen yang Diperkaya:\n";
foreach ($counters as $k => $c) {
    echo sprintf("  - %-22s: %d units\n", $k, $c);
}

echo "\nSample 15 Unit dengan Pembaruan Lokasi Dokumen:\n";
$cnt = 0;
foreach ($docPlan as $aid => $p) {
    if (++$cnt > 15) break;
    echo "  * {$aid} ({$p['make_model']}) [{$p['ownership']}]:\n";
    foreach ($p['updates'] as $f => $val) {
        echo "      + {$f} = '{$val}'\n";
    }
}

if ($isCommit) {
    echo "\nMengeksekusi pembaruan lokasi fisik dokumen ke database dalam transaksi...\n";
    $pdo->beginTransaction();
    try {
        $updateCount = 0;
        foreach ($docPlan as $aid => $p) {
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
        echo "SUCCESS: {$updateCount} unit aset berhasil diperkaya dengan tracking lokasi fisik dokumen BPKB, STNK, dan Invoice!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nUntuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
