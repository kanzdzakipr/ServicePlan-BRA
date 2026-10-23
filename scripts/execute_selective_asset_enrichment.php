<?php
declare(strict_types=1);

/**
 * Strategy 1: Selective Asset Enrichment (Non-destructive)
 * Sources: material/ASSET/02_List_Data_Aset_BRA.md
 * Target Database: u646470441_ServicePlanBRA (assets table)
 */

$isCommit = in_array('--commit', $argv, true);
$mdPath = dirname(__DIR__) . '/material/ASSET/02_List_Data_Aset_BRA.md';

if (!file_exists($mdPath)) {
    die("Error: {$mdPath} not found.\n");
}

function clean_key(?string $s): string {
    if (!$s) return '';
    return strtoupper(preg_replace('/[\s\.\-\/\_]+/', '', $s));
}

// 1. Connect to Database
$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// 2. Fetch all current DB assets
$stmt = $pdo->query('
    SELECT asset_id, asset_code, serial_number, license_plate, previous_license_plate,
           chassis_number, make_model, category, warranty, dealer, current_operator_name,
           project_name, operational_position, capacity, invoice_location, stnk_location, bpkb_location,
           vehicle_type, hull_number, year_manufacture, status
    FROM assets
');
$dbAssets = $stmt->fetchAll();

$dbById = [];
$dbByChassis = [];
$dbBySn = [];
$dbByPlat = [];
$dbByPrevPlat = [];

$platCounts = [];
foreach ($dbAssets as $a) {
    $p = clean_key($a['license_plate']);
    if ($p !== '') $platCounts[$p] = ($platCounts[$p] ?? 0) + 1;
    $pp = clean_key($a['previous_license_plate']);
    if ($pp !== '') $platCounts[$pp] = ($platCounts[$pp] ?? 0) + 1;
}

foreach ($dbAssets as $a) {
    $aid = strtoupper(trim($a['asset_id']));
    $dbById[$aid] = $a;

    $ch = clean_key($a['chassis_number']);
    if (strlen($ch) >= 5) {
        $dbByChassis[$ch] = $a;
    }

    $sn = clean_key($a['serial_number']);
    if (strlen($sn) >= 4) {
        $dbBySn[$sn] = $a;
    }

    $p = clean_key($a['license_plate']);
    if ($p !== '' && ($platCounts[$p] ?? 0) === 1) {
        $dbByPlat[$p] = $a;
    }

    $pp = clean_key($a['previous_license_plate']);
    if ($pp !== '' && ($platCounts[$pp] ?? 0) === 1) {
        $dbByPrevPlat[$pp] = $a;
    }
}

// 3. Parse Markdown table
$lines = file($mdPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$inTable = false;
$currentSection = '';
$records = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (!str_starts_with($line, '|') || !str_ends_with($line, '|')) continue;

    $cells = array_map('trim', explode('|', substr($line, 1, -1)));
    if ($cells[0] === 'Baris') {
        $inTable = true;
        continue;
    }
    if ($inTable && str_starts_with($cells[0], ':---')) {
        continue;
    }

    if ($inTable) {
        $rowNumStr = $cells[0];
        if (!ctype_digit($rowNumStr)) continue;
        $rowNum = (int) $rowNumStr;

        $colA = $cells[1] ?? '';
        $colB = $cells[2] ?? '';
        $colC = $cells[3] ?? '';

        if ($colB === 'JENIS ALAT') {
            $currentSection = $colC;
            continue;
        } elseif (ctype_alpha($colA) && strlen($colA) === 1 && empty($cells[4] ?? '')) {
            $currentSection = $colC;
            continue;
        }

        if ($colA === 'No' || $colC === 'Nama Asset' || $colC === 'Asset') {
            continue;
        }

        $colL = $cells[12] ?? '';
        if (empty($colC) && empty($colL)) {
            continue;
        }

        $isVehicleSection = ($rowNum >= 97);

        $stnkLoc = '';
        $bpkbLoc = '';
        $invoiceLoc = '';
        $nomorRangka = '';
        $tipeUnit = '';

        if ($isVehicleSection) {
            $stnkLoc = $cells[17] ?? '';
            $bpkbLoc = $cells[18] ?? '';
            $nomorRangka = $cells[19] ?? '';
            $tipeUnit = $cells[20] ?? '';
        } else {
            $invoiceLoc = $cells[17] ?? '';
        }

        $records[] = [
            'baris' => $rowNum,
            'section' => $currentSection,
            'nama_asset' => $colC,
            'dealer' => $cells[4] ?? '',
            'tahun_pembuatan' => $cells[5] ?? '',
            'tahun_invoice' => $cells[6] ?? '',
            'sn_pelat' => $cells[7] ?? '',
            'warranty' => $cells[8] ?? '',
            'kapasitas' => $cells[9] ?? '',
            'kode_unit' => $cells[10] ?? '',
            'operator' => $cells[11] ?? '',
            'nomor_lambung' => $colL,
            'project' => $cells[13] ?? '',
            'lokasi' => $cells[14] ?? '',
            'status' => $cells[15] ?? '',
            'invoice_location' => $invoiceLoc,
            'stnk_location' => $stnkLoc,
            'bpkb_location' => $bpkbLoc,
            'nomor_rangka' => $nomorRangka,
            'tipe_unit' => $tipeUnit
        ];
    }
}

// 4. Build enrichment plan
$matchedIds = [];
$enrichmentPlan = [];
$fieldCounters = [
    'dealer' => 0,
    'capacity' => 0,
    'project_name' => 0,
    'operational_position' => 0,
    'invoice_location' => 0,
    'stnk_location' => 0,
    'bpkb_location' => 0,
    'hull_number' => 0,
    'chassis_number' => 0,
    'vehicle_type' => 0,
    'warranty' => 0,
    'current_operator_name' => 0,
    'year_manufacture' => 0
];

foreach ($records as $r) {
    $nlRaw = strtoupper(trim($r['nomor_lambung']));
    $nlClean = clean_key($nlRaw);
    $chassisClean = clean_key($r['nomor_rangka']);
    $snPelatRaw = strtoupper(trim($r['sn_pelat']));
    $snClean = clean_key($snPelatRaw);

    $subPlates = [];
    foreach (preg_split('/[\/\,]/', $snPelatRaw) as $part) {
        $c = clean_key($part);
        if (strlen($c) >= 4) $subPlates[] = $c;
    }

    $targetDb = null;
    $matchMethod = null;

    if (!empty($nlRaw) && $nlRaw !== '-' && isset($dbById[$nlRaw])) {
        $targetDb = $dbById[$nlRaw];
        $matchMethod = 'Exact asset_id';
    } elseif (!empty($chassisClean) && isset($dbByChassis[$chassisClean])) {
        $targetDb = $dbByChassis[$chassisClean];
        $matchMethod = 'Chassis Number';
    } elseif (!empty($snClean) && strlen($snClean) >= 5 && isset($dbBySn[$snClean])) {
        $targetDb = $dbBySn[$snClean];
        $matchMethod = 'Serial Number';
    } elseif (!empty($snClean) && isset($dbByPlat[$snClean])) {
        $targetDb = $dbByPlat[$snClean];
        $matchMethod = 'License Plate';
    } else {
        foreach ($subPlates as $p) {
            if (isset($dbByPlat[$p])) {
                $targetDb = $dbByPlat[$p];
                $matchMethod = "Sub License Plate ({$p})";
                break;
            } elseif (isset($dbByPrevPlat[$p])) {
                $targetDb = $dbByPrevPlat[$p];
                $matchMethod = "Previous License Plate ({$p})";
                break;
            }
        }
    }

    if (!$targetDb) continue;

    $aid = $targetDb['asset_id'];
    if (isset($matchedIds[$aid])) continue;
    $matchedIds[$aid] = true;

    $updates = [];

    // A. Dealer
    $dealerVal = trim($r['dealer']);
    if ($dealerVal !== '' && empty(trim((string)$targetDb['dealer']))) {
        $updates['dealer'] = $dealerVal;
        $fieldCounters['dealer']++;
    }

    // B. Capacity
    $capVal = trim($r['kapasitas']);
    if ($capVal !== '' && empty(trim((string)$targetDb['capacity']))) {
        $updates['capacity'] = $capVal;
        $fieldCounters['capacity']++;
    }

    // C. Project Name
    $projVal = trim($r['project']);
    if ($projVal !== '' && $projVal !== '-' && empty(trim((string)$targetDb['project_name']))) {
        $updates['project_name'] = $projVal;
        $fieldCounters['project_name']++;
    }

    // D. Operational Position
    $posVal = trim($r['lokasi']);
    if ($posVal !== '' && $posVal !== '-' && empty(trim((string)$targetDb['operational_position']))) {
        $updates['operational_position'] = $posVal;
        $fieldCounters['operational_position']++;
    }

    // E. Location fields (Invoice / STNK / BPKB)
    $invVal = trim($r['invoice_location']);
    if ($invVal !== '' && $invVal !== '-' && empty(trim((string)($targetDb['invoice_location'] ?? '')))) {
        $updates['invoice_location'] = $invVal;
        $fieldCounters['invoice_location']++;
    }

    $stnkVal = trim($r['stnk_location']);
    if ($stnkVal !== '' && $stnkVal !== '-' && empty(trim((string)$targetDb['stnk_location']))) {
        $updates['stnk_location'] = $stnkVal;
        $fieldCounters['stnk_location']++;
    }

    $bpkbVal = trim($r['bpkb_location']);
    if ($bpkbVal !== '' && $bpkbVal !== '-' && empty(trim((string)$targetDb['bpkb_location']))) {
        $updates['bpkb_location'] = $bpkbVal;
        $fieldCounters['bpkb_location']++;
    }

    // F. Hull Number
    $hullVal = trim($r['nomor_lambung']);
    if ($hullVal !== '' && $hullVal !== '-' && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $hullVal)) {
        if (empty(trim((string)$targetDb['hull_number']))) {
            $updates['hull_number'] = $hullVal;
            $fieldCounters['hull_number']++;
        }
    }

    // G. Chassis Number & Vehicle Type
    if (!empty($chassisClean) && strlen($chassisClean) >= 5 && empty(trim((string)$targetDb['chassis_number']))) {
        $updates['chassis_number'] = trim($r['nomor_rangka']);
        $fieldCounters['chassis_number']++;
    }

    $typeVal = trim($r['tipe_unit']);
    if ($typeVal !== '' && $typeVal !== '-' && empty(trim((string)($targetDb['vehicle_type'] ?? '')))) {
        $updates['vehicle_type'] = $typeVal;
        $fieldCounters['vehicle_type']++;
    }

    // H. Warranty (specific terms only)
    $warVal = trim($r['warranty']);
    if (preg_match('/(warran|voucher)/i', $warVal) && !preg_match('/(tidak|none)/i', $warVal)) {
        $currWar = trim((string)$targetDb['warranty']);
        if ($currWar === '' || $currWar === '0 HM' || $currWar === 'Warranty (Tidak Ada HM)' || stripos($currWar, 'tidak') !== false) {
            $updates['warranty'] = $warVal;
            $fieldCounters['warranty']++;
        }
    }

    // I. Operator Name: ONLY if DB operator is NULL/empty
    $opVal = trim($r['operator']);
    if ($opVal !== '' && $opVal !== '-' && empty(trim((string)$targetDb['current_operator_name']))) {
        $updates['current_operator_name'] = $opVal;
        $fieldCounters['current_operator_name']++;
    }

    // J. Year Manufacture (if DB is NULL or 0)
    $yrVal = trim($r['tahun_pembuatan']);
    if (ctype_digit($yrVal) && strlen($yrVal) === 4 && empty($targetDb['year_manufacture'])) {
        $updates['year_manufacture'] = (int) $yrVal;
        $fieldCounters['year_manufacture']++;
    }

    if (!empty($updates)) {
        $enrichmentPlan[] = [
            'asset_id' => $aid,
            'baris' => $r['baris'],
            'match_method' => $matchMethod,
            'updates' => $updates
        ];
    }
}

echo "================ STRATEGY 1: SELECTIVE ASSET ENRICHMENT ================\n";
echo "Execution Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written to database") . "\n";
echo "Matched High-Confidence Assets: " . count($matchedIds) . "\n";
echo "Assets to be enriched: " . count($enrichmentPlan) . "\n\n";

echo "Enrichment Breakdown:\n";
foreach ($fieldCounters as $f => $cnt) {
    echo sprintf("  - %-22s: %d assets\n", $f, $cnt);
}

if ($isCommit) {
    echo "\nApplying updates in database transaction...\n";
    $pdo->beginTransaction();
    try {
        $updateCount = 0;
        foreach ($enrichmentPlan as $item) {
            $aid = $item['asset_id'];
            $setParts = [];
            $params = [':aid' => $aid];

            foreach ($item['updates'] as $col => $val) {
                $setParts[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $val;
            }

            $sql = "UPDATE assets SET " . implode(', ', $setParts) . " WHERE asset_id = :aid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $updateCount++;
        }

        $pdo->commit();
        echo "SUCCESS: {$updateCount} assets successfully enriched in database!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaction rolled back. Reason: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nTo execute and commit to database, run with --commit flag.\n";
}
