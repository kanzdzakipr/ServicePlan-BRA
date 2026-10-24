<?php
declare(strict_types=1);

/**
 * Strategy: Step 1 (Batch 1 & Batch 2 Enrichment)
 * Sources:
 *   - material/ASSET/09_Sheet27.md (PIC & Jabatan Kendaraan Operasional / LV)
 *   - material/ASSET/15_Sheet5.md (Legal Expiry, Chassis & Hull HCT)
 *   - material/ASSET/16_Sheet6.md (Legal Expiry, Chassis & Hull TDO)
 * Target:
 *   - Database: u646470441_ServicePlanBRA (assets, asset_operator_history)
 */

$isCommit = in_array('--commit', $argv, true);
$baseDir = dirname(__DIR__);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

function clean_key(?string $s): string {
    if (!$s) return '';
    return strtoupper(preg_replace('/[\s\.\-\/\_]+/', '', $s));
}

function parse_md_table(string $filePath): array {
    if (!file_exists($filePath)) return [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $records = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (!str_starts_with($line, '|') || !str_ends_with($line, '|')) continue;
        $cells = array_map('trim', explode('|', substr($line, 1, -1)));
        if (!isset($cells[0]) || !ctype_digit($cells[0])) continue;
        $records[] = $cells;
    }
    return $records;
}

// 1. Fetch current DB assets
$dbAssets = $pdo->query('
    SELECT asset_id, asset_code, serial_number, license_plate, previous_license_plate,
           chassis_number, hull_number, category, make_model, current_operator_name,
           status, legal_stnk_expiry, legal_kir_expiry, operational_position, project_name
    FROM assets
')->fetchAll();

$dbById = [];
$dbBySn = [];
$dbByPlat = [];
$dbByChassis = [];

foreach ($dbAssets as $a) {
    $aid = strtoupper(trim($a['asset_id']));
    $dbById[$aid] = $a;

    $sn = clean_key($a['serial_number']);
    if (strlen($sn) >= 4) $dbBySn[$sn] = $a;

    $ch = clean_key($a['chassis_number']);
    if (strlen($ch) >= 5) $dbByChassis[$ch] = $a;

    $p = clean_key($a['license_plate']);
    if ($p !== '') $dbByPlat[$p] = $a;
    $pp = clean_key($a['previous_license_plate']);
    if ($pp !== '') $dbByPlat[$pp] = $a;
}

$enrichmentPlan = [];
$counters = [
    'current_operator_name' => 0,
    'legal_stnk_expiry' => 0,
    'legal_kir_expiry' => 0,
    'chassis_number' => 0,
    'hull_number' => 0,
    'project_name' => 0,
    'operational_position' => 0
];
$operatorHistoryInserts = [];

// ============================================================================
// PART 1: Process 09_Sheet27.md (PIC & Jabatan LV)
// ============================================================================
$rows09 = parse_md_table($baseDir . '/material/ASSET/09_Sheet27.md');
foreach ($rows09 as $c) {
    $platRaw = $c[4] ?? '';
    $pic = trim($c[5] ?? '');
    $jabatan = trim($c[6] ?? '');
    $proj = trim($c[7] ?? '');
    if (empty($platRaw) || empty($pic) || in_array(strtoupper($pic), ['-', 'STANDBY', 'HO', 'DIJUAL'])) continue;

    $target = null;
    foreach (preg_split('/[\/\,]/', $platRaw) as $part) {
        $cp = clean_key($part);
        if ($cp && isset($dbByPlat[$cp])) {
            $target = $dbByPlat[$cp];
            break;
        }
    }
    if (!$target) continue;

    $aid = $target['asset_id'];
    if (!isset($enrichmentPlan[$aid])) {
        $enrichmentPlan[$aid] = [
            'asset_id' => $aid,
            'source' => '09_Sheet27.md',
            'license_plate' => $target['license_plate'],
            'updates' => [],
            'operator_history' => null
        ];
    }

    // Only update if currently empty in DB
    if (empty(trim((string)$target['current_operator_name'])) && !isset($enrichmentPlan[$aid]['updates']['current_operator_name'])) {
        $enrichmentPlan[$aid]['updates']['current_operator_name'] = $pic;
        $counters['current_operator_name']++;

        $note = 'Kendaraan Operasional / LV';
        if (!empty($jabatan)) $note .= " (Jabatan: {$jabatan})";
        if (!empty($proj)) $note .= " - Proyek: {$proj}";
        
        $enrichmentPlan[$aid]['operator_history'] = [
            'asset_id' => $aid,
            'operator_name' => $pic,
            'start_date' => '2026-01-01',
            'shift_type' => 'Full Time',
            'handover_notes' => $note
        ];
    }

    if (!empty($proj) && $proj !== '-' && empty(trim((string)$target['project_name'])) && !isset($enrichmentPlan[$aid]['updates']['project_name'])) {
        $enrichmentPlan[$aid]['updates']['project_name'] = $proj;
        $counters['project_name']++;
    }
}

// ============================================================================
// PART 2: Process 15_Sheet5.md & 16_Sheet6.md (Legal Expiry, Chassis & Hull)
// ============================================================================
$rows15 = parse_md_table($baseDir . '/material/ASSET/15_Sheet5.md');
$rows16 = parse_md_table($baseDir . '/material/ASSET/16_Sheet6.md');

// Helper to resolve valid YYYY-MM-DD date
function clean_date(?string $d): ?string {
    if (!$d) return null;
    $d = trim($d);
    if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $d)) return $d;
    return null;
}

// In Sheet 5 (rows 5..63):
// Col B (idx 2): Plat, I (idx 9): No Lambung, L (idx 12): No Rangka,
// O (idx 15): STNK Status, P (idx 16): Tax Status, Q (idx 17): KIR Status
foreach ($rows15 as $c) {
    $platRaw = $c[2] ?? '';
    if (empty($platRaw) || $platRaw === 'Register Plat') continue;

    $target = null;
    $cp = clean_key($platRaw);
    if ($cp && isset($dbByPlat[$cp])) $target = $dbByPlat[$cp];
    if (!$target) continue;

    $aid = $target['asset_id'];
    if (!isset($enrichmentPlan[$aid])) {
        $enrichmentPlan[$aid] = [
            'asset_id' => $aid,
            'source' => '15_Sheet5.md',
            'license_plate' => $target['license_plate'],
            'updates' => [],
            'operator_history' => null
        ];
    }

    $noLambung = trim($c[9] ?? '');
    $noRangka = trim($c[12] ?? '');
    $stnkDate = clean_date($c[15] ?? '') ?: clean_date($c[16] ?? '');
    $kirDate = clean_date($c[17] ?? '');

    if (!empty($noLambung) && $noLambung !== '-' && empty(trim((string)$target['hull_number'])) && !isset($enrichmentPlan[$aid]['updates']['hull_number'])) {
        $enrichmentPlan[$aid]['updates']['hull_number'] = $noLambung;
        $counters['hull_number']++;
    }

    if (!empty($noRangka) && strlen($noRangka) >= 5 && empty(trim((string)$target['chassis_number'])) && !isset($enrichmentPlan[$aid]['updates']['chassis_number'])) {
        $enrichmentPlan[$aid]['updates']['chassis_number'] = $noRangka;
        $counters['chassis_number']++;
    }

    if ($stnkDate && empty($target['legal_stnk_expiry']) && !isset($enrichmentPlan[$aid]['updates']['legal_stnk_expiry'])) {
        $enrichmentPlan[$aid]['updates']['legal_stnk_expiry'] = $stnkDate;
        $counters['legal_stnk_expiry']++;
    }

    if ($kirDate && empty($target['legal_kir_expiry']) && !isset($enrichmentPlan[$aid]['updates']['legal_kir_expiry'])) {
        $enrichmentPlan[$aid]['updates']['legal_kir_expiry'] = $kirDate;
        $counters['legal_kir_expiry']++;
    }
}

// In Sheet 6 (rows 5..29):
// Col C (idx 2): Plat, J (idx 9): No Lambung, M (idx 12): No Rangka,
// P (idx 15): STNK Status, Q (idx 16): Tax Status, R (idx 17): KIR Status
foreach ($rows16 as $c) {
    $platRaw = $c[2] ?? '';
    if (empty($platRaw) || $platRaw === 'Register Plat') continue;

    $target = null;
    $cp = clean_key($platRaw);
    if ($cp && isset($dbByPlat[$cp])) $target = $dbByPlat[$cp];
    if (!$target) continue;

    $aid = $target['asset_id'];
    if (!isset($enrichmentPlan[$aid])) {
        $enrichmentPlan[$aid] = [
            'asset_id' => $aid,
            'source' => '16_Sheet6.md',
            'license_plate' => $target['license_plate'],
            'updates' => [],
            'operator_history' => null
        ];
    }

    $noLambung = trim($c[9] ?? '');
    $noRangka = trim($c[12] ?? '');
    $stnkDate = clean_date($c[15] ?? '') ?: clean_date($c[16] ?? '');
    $kirDate = clean_date($c[17] ?? '');

    if (!empty($noLambung) && $noLambung !== '-' && empty(trim((string)$target['hull_number'])) && !isset($enrichmentPlan[$aid]['updates']['hull_number'])) {
        $enrichmentPlan[$aid]['updates']['hull_number'] = $noLambung;
        $counters['hull_number']++;
    }

    if (!empty($noRangka) && strlen($noRangka) >= 5 && empty(trim((string)$target['chassis_number'])) && !isset($enrichmentPlan[$aid]['updates']['chassis_number'])) {
        $enrichmentPlan[$aid]['updates']['chassis_number'] = $noRangka;
        $counters['chassis_number']++;
    }

    if ($stnkDate && empty($target['legal_stnk_expiry']) && !isset($enrichmentPlan[$aid]['updates']['legal_stnk_expiry'])) {
        $enrichmentPlan[$aid]['updates']['legal_stnk_expiry'] = $stnkDate;
        $counters['legal_stnk_expiry']++;
    }

    if ($kirDate && empty($target['legal_kir_expiry']) && !isset($enrichmentPlan[$aid]['updates']['legal_kir_expiry'])) {
        $enrichmentPlan[$aid]['updates']['legal_kir_expiry'] = $kirDate;
        $counters['legal_kir_expiry']++;
    }
}

// PIC alat berat yang telah diverifikasi pada dump terbaru 24 Oktober 2026.
// Isi hanya kolom yang masih kosong agar perubahan pengguna lokal tetap aman.
$verifiedHeavyEquipmentOperators = [
    'DZ-00004' => 'SULARNO',
    'EXC-00010' => 'ADI SANTOSO',
    'EXC-00013' => 'GERY',
    'EXC-00015' => 'INDRA PRANATA',
    'EXC-41001' => 'BOIMAH',
    'FBT-31001' => 'ANTONI',
    'FBT-31002' => 'RENALDI SOARMIN',
    'FBT-31004' => 'BUSTAMI',
    'MG-00004' => 'JUNI HARDI',
    'PF-00003' => 'JORDAN',
    'PF-00011' => 'BAMBANG',
    'SUP-00009' => 'ROHIN',
    'TMC-31002' => 'ILHAM AKBAR',
    'TMC-31003' => 'ANADA RIZA',
];

foreach ($verifiedHeavyEquipmentOperators as $assetId => $operatorName) {
    $target = $dbById[$assetId] ?? null;
    if (!$target || trim((string) ($target['current_operator_name'] ?? '')) !== '') {
        continue;
    }
    if (!isset($enrichmentPlan[$assetId])) {
        $enrichmentPlan[$assetId] = [
            'asset_id' => $assetId,
            'source' => 'Verified dump 2026-10-24',
            'license_plate' => $target['license_plate'],
            'updates' => [],
            'operator_history' => null,
        ];
    }
    if (!isset($enrichmentPlan[$assetId]['updates']['current_operator_name'])) {
        $enrichmentPlan[$assetId]['updates']['current_operator_name'] = $operatorName;
        $counters['current_operator_name']++;
    }
}

// Filter only items with updates
$validPlan = array_filter($enrichmentPlan, fn($p) => !empty($p['updates']));

echo "================ STRATEGY: STEP 1 (BATCH 1 & 2 ENRICHMENT) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Assets to be Enriched: " . count($validPlan) . "\n\n";

echo "Breakdown Penambahan:\n";
foreach ($counters as $k => $cnt) {
    echo sprintf("  - %-25s: %d assets\n", $k, $cnt);
}

echo "\nSample Rencana Penambahan:\n";
$sampleIdx = 0;
foreach ($validPlan as $aid => $p) {
    if (++$sampleIdx > 8) break;
    echo "  * {$aid} ({$p['license_plate']}) [Sumber: {$p['source']}]:\n";
    foreach ($p['updates'] as $f => $val) {
        echo "      + {$f} = '{$val}'\n";
    }
}

if ($isCommit) {
    echo "\nMengeksekusi penambahan data ke database dalam transaksi...\n";
    $pdo->beginTransaction();
    try {
        $assetUpdateStmt = null;
        $updateCount = 0;
        $historyCount = 0;

        foreach ($validPlan as $aid => $p) {
            $setParts = [];
            $params = [':aid' => $aid];

            foreach ($p['updates'] as $col => $val) {
                $setParts[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $val;
            }

            $sql = "UPDATE assets SET " . implode(', ', $setParts) . " WHERE asset_id = :aid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $updateCount++;

            if (!empty($p['operator_history'])) {
                $h = $p['operator_history'];
                $histSql = "
                    INSERT INTO asset_operator_history (asset_id, operator_name, start_date, shift_type, handover_notes, created_at)
                    VALUES (:aid, :op, :sdate, :shift, :notes, NOW())
                ";
                $histStmt = $pdo->prepare($histSql);
                $histStmt->execute([
                    ':aid' => $h['asset_id'],
                    ':op' => $h['operator_name'],
                    ':sdate' => $h['start_date'],
                    ':shift' => $h['shift_type'],
                    ':notes' => $h['handover_notes']
                ]);
                $historyCount++;
            }
        }

        $pdo->commit();
        echo "SUCCESS: {$updateCount} unit aset berhasil diperkaya, {$historyCount} riwayat operator berhasil dicatat!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nUntuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
