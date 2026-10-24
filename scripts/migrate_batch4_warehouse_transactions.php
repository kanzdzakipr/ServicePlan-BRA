<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 4 - Mutasi Pergudangan, Penerimaan & Pengeluaran Suku Cadang (BHW-IN / BHW-OUT)
 *
 * Ingests 637 warehouse ledger transactions from January 2026 equipment report files:
 *  - 05_01 (210 Parts In)
 *  - 05_02 (355 Parts Out)
 *  - 05_04 (2 Battery In)
 *  - 05_05 (6 Battery Out)
 *  - 05_07 (9 Tires In)
 *  - 05_08 (16 Tires Out)
 *  - 05_10 (6 Oils In)
 *  - 05_11 (33 Oils Out)
 *
 * Expands parts catalog with missing items, links master report records (BHW-IN, BHW-OUT),
 * applies mathematical inventory balance reconstruction (zero negative stocks),
 * and syncs ending stock balances with master stock files (05_03, 05_06, 05_09, 05_12).
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();
$baseDir = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026';

echo "====================================================================\n";
echo "MIGRATION BATCH 4: WAREHOUSE TRANSACTIONS & STOCK RECONCILIATION\n";
echo "====================================================================\n\n";

$isForce = in_array('--force', $argv, true);

// Check if migration has already been executed
$checkMig = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = ?");
$checkMig->execute(['BATCH_4_EQUIPMENT_REPORT_202601']);
$isAlreadyRun = ((int)$checkMig->fetchColumn()) > 0;

if ($isAlreadyRun && !$isForce) {
    echo "[INFO] Migration BATCH_4_EQUIPMENT_REPORT_202601 has already been executed.\n";
    echo "To re-run, use the --force flag.\n";
    exit(0);
}

function norm(string $s): string {
    $s = strtoupper(trim($s));
    $s = preg_replace('/[^\w\d]/', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

function smartParseDate(string $raw, string $fileCode): string {
    $raw = trim($raw);
    if (!preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $raw, $m)) {
        return '2026-01-15';
    }
    $p1 = (int)$m[1];
    $p2 = (int)$m[2];
    $year = (int)$m[3];

    if ($p1 > 12) {
        $day = $p1;
        $month = $p2;
    } elseif ($p2 > 12) {
        $month = $p1;
        $day = $p2;
    } else {
        if (in_array($fileCode, ['05_01', '05_08'], true)) {
            $month = $p1;
            $day = $p2;
        } else {
            $day = $p1;
            $month = $p2;
        }
    }
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

// -------------------------------------------------------------
// Step 1: Read Stock Master Reports (05_03, 05_06, 05_09, 05_12)
// -------------------------------------------------------------
echo "[1/6] Loading Master Ending Stock from 05_03, 05_06, 05_09, 05_12...\n";
$stockMaster = [];

// 05_03: Spare Parts
$file0503 = "$baseDir/05_03_Laporan_Stok_Penerimaan_dan_Pemakaian_Spare_Part.md";
if (file_exists($file0503)) {
    foreach (file($file0503) as $l) {
        $l = trim($l);
        if (strpos($l, '|') !== 0 || strpos($l, '---') !== false || strpos($l, 'Nama Parts') !== false) continue;
        $c = array_map('trim', explode('|', $l));
        array_shift($c); array_pop($c);
        $name = $c[0] ?? '';
        $pn = $c[1] ?? '';
        $inLalu = (int)($c[3] ?? 0);
        $outLalu = (int)($c[10] ?? 0);
        $initial = max(0, $inLalu - $outLalu);
        $ending = max(0, (int)($c[17] ?? 0));
        if ($pn !== '') $stockMaster['PN:' . strtoupper(trim($pn))] = ['initial' => $initial, 'ending' => $ending];
        if ($name !== '') $stockMaster['NAME:' . norm($name)] = ['initial' => $initial, 'ending' => $ending];
    }
}

// 05_06: Baterai
$file0506 = "$baseDir/05_06_Laporan_Stok_Baterai_Accu.md";
if (file_exists($file0506)) {
    foreach (file($file0506) as $l) {
        $l = trim($l);
        if (strpos($l, '|') !== 0 || strpos($l, '---') !== false || strpos($l, 'Nama Parts') !== false) continue;
        $c = array_map('trim', explode('|', $l));
        array_shift($c); array_pop($c);
        $name = $c[0] ?? '';
        $pn = $c[1] ?? '';
        $inLalu = (int)($c[3] ?? 0);
        $outLalu = (int)($c[10] ?? 0);
        $initial = max(0, $inLalu - $outLalu);
        $ending = max(0, (int)($c[17] ?? 0));
        if ($pn !== '') $stockMaster['PN:' . strtoupper(trim($pn))] = ['initial' => $initial, 'ending' => $ending];
        if ($name !== '') $stockMaster['NAME:' . norm($name)] = ['initial' => $initial, 'ending' => $ending];
    }
}

// 05_09: Ban
$file0509 = "$baseDir/05_09_Laporan_Stok_Ban.md";
if (file_exists($file0509)) {
    foreach (file($file0509) as $l) {
        $l = trim($l);
        if (strpos($l, '|') !== 0 || strpos($l, '---') !== false || strpos($l, 'Nama Parts') !== false) continue;
        $c = array_map('trim', explode('|', $l));
        array_shift($c); array_pop($c);
        $name = $c[0] ?? '';
        $inLalu = (int)($c[2] ?? 0);
        $outLalu = (int)($c[9] ?? 0);
        $initial = max(0, $inLalu - $outLalu);
        $ending = max(0, (int)($c[16] ?? 0));
        if ($name !== '') $stockMaster['NAME:' . norm($name)] = ['initial' => $initial, 'ending' => $ending];
    }
}

// 05_12: Oli & Coolant
$file0512 = "$baseDir/05_12_Laporan_Stok_Oli_dan_Air_Coolant.md";
if (file_exists($file0512)) {
    foreach (file($file0512) as $l) {
        $l = trim($l);
        if (strpos($l, '|') !== 0 || strpos($l, '---') !== false || strpos($l, 'Nama Parts') !== false) continue;
        $c = array_map('trim', explode('|', $l));
        array_shift($c); array_pop($c);
        $name = $c[0] ?? '';
        $pn = $c[1] ?? '';
        $inLalu = (int)($c[3] ?? 0);
        $outLalu = (int)($c[10] ?? 0);
        $initial = max(0, $inLalu - $outLalu);
        $ending = max(0, (int)($c[17] ?? 0));
        if ($pn !== '') $stockMaster['PN:' . strtoupper(trim($pn))] = ['initial' => $initial, 'ending' => $ending];
        if ($name !== '') $stockMaster['NAME:' . norm($name)] = ['initial' => $initial, 'ending' => $ending];
    }
}

echo "      -> Total master stock dictionary entries: " . count($stockMaster) . "\n\n";

// -------------------------------------------------------------
// Step 2: Load Assets and Work Orders for Relational Linking
// -------------------------------------------------------------
echo "[2/6] Loading Assets and Work Orders for Relational Resolution...\n";
$assets = $pdo->query("SELECT asset_id, asset_code, license_plate, previous_license_plate, alias_name, hull_number FROM assets")->fetchAll(PDO::FETCH_ASSOC);
$assetMap = [];
foreach ($assets as $a) {
    foreach (['asset_id', 'asset_code', 'license_plate', 'previous_license_plate', 'alias_name', 'hull_number'] as $f) {
        $v = trim((string)$a[$f]);
        if ($v !== '') {
            $assetMap[norm($v)] = $a['asset_id'];
            $assetMap[strtoupper($v)] = $a['asset_id'];
        }
    }
}

$synonyms = [
    'EXCA 00001' => 'EXC-00001', 'EXCA 00003' => 'EXC-00003', 'EXCA 00004' => 'EXC-00004', 'EXCA 00005' => 'EXC-00005',
    'EXCA 00007' => 'EXC-00007', 'EXCA 00008' => 'EXC-00008', 'EXCA 00008 MINAS' => 'EXC-00008', 'EXCA 00004 MINAS' => 'EXC-00004',
    'EXCA 00012' => 'EXC-00012', 'EXCA 00013' => 'EXC-00013', 'EXCA 41001' => 'EXC-41001', 'EXCAMINI HE 6 006' => 'EXC-06006',
    'EXCA MINI EVAKUASI SUMBAR 2025' => 'EXC-06004', 'D85E SS' => 'DZ-00002', 'DOZER D85E SS' => 'DZ-00002',
    'SD 00001' => 'PF-00005', 'SD 00002' => 'PF-00006', 'SD 00004' => 'PF-00004', 'SD 00005 MINAS' => 'PF-00011',
    'SD 00005' => 'PF-00011', 'LB 00001' => 'FBT-00001', 'FBT 31103' => 'FBT-31003', 'TMC 31001' => 'TMC-00001',
    'LV B 9123 ZBA' => 'LV-00030', 'BM 8371 CU' => 'FBT-41001', 'BM 8443 NU' => 'FBT-31002',
    'XCMG CEMEN SPREADER' => 'SUP-41001', 'CC 00001' => 'SUP-41001',
];

function resolveAssetId(string $raw, array $assetMap, array $synonyms): ?string {
    $raw = trim($raw);
    if ($raw === '') return null;
    $n = norm($raw);
    if (isset($synonyms[$n])) return $synonyms[$n];
    if (isset($assetMap[$n])) return $assetMap[$n];
    if (isset($assetMap[strtoupper($raw)])) return $assetMap[strtoupper($raw)];
    if (preg_match('/^EXCA-(\d{5})$/i', $raw, $m)) {
        $c = 'EXC-' . $m[1];
        if (isset($assetMap[norm($c)])) return $assetMap[norm($c)];
    }
    if (preg_match('/^DT[\s\-]*0*(\d{1,5})$/i', $raw, $m)) {
        $c = sprintf('DT-%05d', (int)$m[1]);
        if (isset($assetMap[norm($c)])) return $assetMap[norm($c)];
    }
    return null;
}

// Load Work Orders for date-matching
$woRows = $pdo->query("SELECT wo_id, asset_id, DATE(trouble_at) as start_date, DATE(closed_at) as end_date FROM work_orders WHERE trouble_at IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
$woByAsset = [];
foreach ($woRows as $w) {
    $woByAsset[$w['asset_id']][] = $w;
}

function resolveWoId(?string $assetId, string $txDate, array $woByAsset): ?string {
    if (!$assetId || !isset($woByAsset[$assetId])) return null;
    $txTime = strtotime($txDate);
    foreach ($woByAsset[$assetId] as $w) {
        $sStr = !empty($w['start_date']) ? (string)$w['start_date'] : $txDate;
        $eStr = !empty($w['end_date']) ? (string)$w['end_date'] : $sStr;
        $sTime = strtotime($sStr) - 86400; // allow 1 day tolerance
        $eTime = strtotime($eStr) + 86400;
        if ($txTime >= $sTime && $txTime <= $eTime) {
            return (string)$w['wo_id'];
        }
    }
    return null;
}

echo "      -> Total assets indexed: " . count($assetMap) . "\n";
echo "      -> Total active WOs indexed: " . count($woRows) . "\n\n";

// -------------------------------------------------------------
// Step 3: Register Master Report Records (BHW-IN, BHW-OUT)
// -------------------------------------------------------------
echo "[3/6] Setting Up Master Report Records in report_records...\n";
$pdo->beginTransaction();

try {
    // Dynamically resolve template_ids for bhw-in and bhw-out
    $findTpl = $pdo->prepare("SELECT template_id FROM report_templates WHERE template_key = :k LIMIT 1");
    
    $findTpl->execute([':k' => 'bhw-in']);
    $bhwInTplId = (int)$findTpl->fetchColumn();
    if (!$bhwInTplId) {
        $insTpl = $pdo->prepare("INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active, created_at, updated_at) VALUES ('bhw-in', 'BHW-IN', 'Buku Harian Warehouse (Barang Masuk)', 1, '{}', 1, NOW(), NOW())");
        $insTpl->execute();
        $bhwInTplId = (int)$pdo->lastInsertId();
    }

    $findTpl->execute([':k' => 'bhw-out']);
    $bhwOutTplId = (int)$findTpl->fetchColumn();
    if (!$bhwOutTplId) {
        $insTpl = $pdo->prepare("INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active, created_at, updated_at) VALUES ('bhw-out', 'BHW-OUT', 'Buku Harian Warehouse (Barang Keluar)', 1, '{}', 1, NOW(), NOW())");
        $insTpl->execute();
        $bhwOutTplId = (int)$pdo->lastInsertId();
    }

    $masterReports = [
        'BHW-IN-PARTS-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000001',
            'template_id' => $bhwInTplId,
            'report_number' => '01/BAPB/BRA/PHR.DURI/I/2026',
            'final_number_key' => 'b4-bhw-in-parts-202601',
            'title' => 'Buku Harian Warehouse (Barang Masuk) Spare Part',
        ],
        'BHW-OUT-PARTS-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000002',
            'template_id' => $bhwOutTplId,
            'report_number' => 'BHW-OUT/I/2026',
            'final_number_key' => 'b4-bhw-out-parts-202601',
            'title' => 'Buku Harian Warehouse (Barang Keluar) Spare Part',
        ],
        'BHW-IN-BAT-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000003',
            'template_id' => $bhwInTplId,
            'report_number' => '01.01/BAPB/ACCU/2026',
            'final_number_key' => 'b4-bhw-in-bat-202601',
            'title' => 'Buku Harian Warehouse (Barang Masuk) Baterai Accu',
        ],
        'BHW-OUT-BAT-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000004',
            'template_id' => $bhwOutTplId,
            'report_number' => '01.01/BKTBI/ACCU/2026',
            'final_number_key' => 'b4-bhw-out-bat-202601',
            'title' => 'Buku Harian Warehouse (Barang Keluar) Baterai Accu',
        ],
        'BHW-IN-TIRES-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000005',
            'template_id' => $bhwInTplId,
            'report_number' => '01.01/BAPB/BAN/2026',
            'final_number_key' => 'b4-bhw-in-tires-202601',
            'title' => 'Buku Harian Warehouse (Barang Masuk) Ban',
        ],
        'BHW-OUT-TIRES-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000006',
            'template_id' => $bhwOutTplId,
            'report_number' => '01.01-BAN/BKTBI/2026',
            'final_number_key' => 'b4-bhw-out-tires-202601',
            'title' => 'Buku Harian Warehouse (Barang Keluar) Ban',
        ],
        'BHW-IN-OILS-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000007',
            'template_id' => $bhwInTplId,
            'report_number' => 'BHW-IN/OLI/I/2026',
            'final_number_key' => 'b4-bhw-in-oils-202601',
            'title' => 'Buku Harian Warehouse (Barang Masuk) Oli & Pelumas',
        ],
        'BHW-OUT-OILS-202601' => [
            'report_id' => 'b4000001-0000-0000-0000-000000000008',
            'template_id' => $bhwOutTplId,
            'report_number' => 'BHW-OUT/OLI/I/2026',
            'final_number_key' => 'b4-bhw-out-oils-202601',
            'title' => 'Buku Harian Warehouse (Barang Keluar) Oli & Pelumas',
        ],
    ];

    $insReport = $pdo->prepare("
        INSERT INTO report_records (
            report_id, template_id, client_key, report_number, status, source_method,
            field_data, final_number_key, created_at, updated_at, finalized_at
        ) VALUES (
            :report_id, :template_id, :client_key, :report_number, 'FINAL', 'migration',
            :field_data, :final_number_key, '2026-01-31 00:00:00', '2026-01-31 00:00:00', '2026-01-31 00:00:00'
        ) ON DUPLICATE KEY UPDATE
            template_id = VALUES(template_id),
            report_number = VALUES(report_number),
            status = 'FINAL',
            final_number_key = VALUES(final_number_key)
    ");

    foreach ($masterReports as $code => $rep) {
        $insReport->execute([
            ':report_id' => $rep['report_id'],
            ':template_id' => $rep['template_id'],
            ':client_key' => 'BRA-EQUIPMENT-JAN2026',
            ':report_number' => $rep['report_number'],
            ':field_data' => json_encode(['title' => $rep['title'], 'periode' => 'Januari 2026', 'site' => 'PHR DURI - Yard KM 12']),
            ':final_number_key' => $rep['final_number_key'],
        ]);
        echo "      [+] Master Report: {$rep['report_number']} ({$code})\n";
    }

    // If force mode, clean up prior batch 4 transactions
    if ($isForce) {
        $delTx = $pdo->prepare("DELETE FROM inventory_transactions WHERE report_id LIKE 'b4000001-%'");
        $delTx->execute();
        echo "      [*] Purged previous Batch 4 inventory transactions (force mode).\n";
    }

    echo "\n";

    // -------------------------------------------------------------
    // Step 4: Catalog Resolution & Missing Parts Registration
    // -------------------------------------------------------------
    echo "[4/6] Auditing and Expanding Parts Catalog...\n";
    $partsStmt = $pdo->query("SELECT part_id, part_number, part_name, category, unit_measure, stock_qty FROM parts");
    $existingParts = [];
    $partByNumber = [];
    $partByName = [];
    $maxHw = 627;

    while ($p = $partsStmt->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int)$p['part_id'];
        $existingParts[$pid] = $p;
        $pn = trim($p['part_number']);
        if ($pn !== '') $partByNumber[strtoupper($pn)] = $pid;
        $pnn = norm($p['part_name']);
        if ($pnn !== '') $partByName[$pnn] = $pid;
        if (preg_match('/^GEN\-HW\-0*(\d+)$/', $pn, $m)) {
            if ((int)$m[1] > $maxHw) $maxHw = (int)$m[1];
        }
    }

    $sources = [
        '05_01' => ['file' => '05_01_Buku_Harian_Warehouse_Parts_Masuk.md', 'type' => 'IN', 'cat' => 'PARTS', 'rep' => $masterReports['BHW-IN-PARTS-202601']['report_id']],
        '05_02' => ['file' => '05_02_Buku_Harian_Warehouse_Pemakaian_Spare_Part.md', 'type' => 'OUT', 'cat' => 'PARTS', 'rep' => $masterReports['BHW-OUT-PARTS-202601']['report_id']],
        '05_04' => ['file' => '05_04_Baterai_Accu_Parts_Masuk.md', 'type' => 'IN', 'cat' => 'BATTERY', 'rep' => $masterReports['BHW-IN-BAT-202601']['report_id']],
        '05_05' => ['file' => '05_05_Baterai_Accu_Pemakaian.md', 'type' => 'OUT', 'cat' => 'BATTERY', 'rep' => $masterReports['BHW-OUT-BAT-202601']['report_id']],
        '05_07' => ['file' => '05_07_Ban_Parts_Masuk.md', 'type' => 'IN', 'cat' => 'TIRES', 'rep' => $masterReports['BHW-IN-TIRES-202601']['report_id']],
        '05_08' => ['file' => '05_08_Ban_Pemakaian.md', 'type' => 'OUT', 'cat' => 'TIRES', 'rep' => $masterReports['BHW-OUT-TIRES-202601']['report_id']],
        '05_10' => ['file' => '05_10_Oli_Pelumas_Air_Coolant_Parts_Masuk.md', 'type' => 'IN', 'cat' => 'LUBRICANT', 'rep' => $masterReports['BHW-IN-OILS-202601']['report_id']],
        '05_11' => ['file' => '05_11_Oli_Pelumas_Air_Coolant_Pemakaian.md', 'type' => 'OUT', 'cat' => 'LUBRICANT', 'rep' => $masterReports['BHW-OUT-OILS-202601']['report_id']],
    ];

    $insPart = $pdo->prepare("
        INSERT INTO parts (part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, unit_cost, location_warehouse)
        VALUES (:part_number, :part_name, :category, :unit_measure, 0, 1, 0, 'Gudang Utama Duri KM 12')
    ");

    $newPartsAdded = 0;
    $rawTransactions = [];

    foreach ($sources as $code => $cfg) {
        $path = "$baseDir/{$cfg['file']}";
        $lines = file($path);
        $itemPos = 0;

        foreach ($lines as $line) {
            $l = trim($line);
            if ($l === '' || $l[0] !== '|') continue;
            $cols = array_map('trim', explode('|', $l));
            array_shift($cols); array_pop($cols);
            if (!isset($cols[0]) || strpos($cols[0], '/') === false) continue;
            $itemPos++;

            $txDate = smartParseDate($cols[0], $code);
            $refNo = '';
            $counterparty = null;
            $partName = '';
            $partNumber = '';
            $unitMeasure = 'Pcs';
            $quantity = 1;
            $target = '';
            $notes = '';

            if ($code === '05_01') {
                $refNo = $cols[1] ?: 'BHW-IN/I/2026';
                $counterparty = $cols[2] ?: 'Supplier';
                $partName = $cols[3];
                $partNumber = $cols[4] ?? '';
                $unitMeasure = $cols[6] ?: 'Pcs';
                $quantity = (int)($cols[7] ?? 1);
                $target = $cols[8] ?? '';
                $notes = trim($target . ' ' . ($cols[9] ?? ''));
            } elseif ($code === '05_02') {
                $refNo = $cols[1] ?: 'BHW-OUT/I/2026';
                $partName = $cols[2];
                $partNumber = $cols[3] ?? '';
                $unitMeasure = $cols[5] ?: 'Pcs';
                $quantity = (int)($cols[6] ?? 1);
                $counterparty = $cols[7] ?: null;
                $target = $cols[8] ?? '';
                $notes = trim($target . ' ' . ($cols[9] ?? ''));
            } elseif ($code === '05_04') {
                $refNo = $cols[1] ?: 'BHW-IN/BAT/2026';
                $counterparty = $cols[2] ?: 'Pekanbaru';
                $partName = $cols[3];
                $partNumber = $cols[4] ?? '';
                $unitMeasure = $cols[5] ?: 'Pcs';
                $quantity = (int)($cols[6] ?? 1);
                $notes = $cols[7] ?? '';
            } elseif ($code === '05_05') {
                $refNo = $cols[1] ?: 'BHW-OUT/BAT/2026';
                $partName = $cols[2];
                $partNumber = $cols[3] ?? '';
                $unitMeasure = $cols[4] ?: 'Pcs';
                $quantity = (int)($cols[5] ?? 1);
                $target = $cols[6] ?? '';
                $notes = $target;
            } elseif ($code === '05_07') {
                $refNo = $cols[1] ?: 'BHW-IN/BAN/2026';
                $counterparty = $cols[2] ?: 'PRB';
                $partName = $cols[3];
                $partNumber = $cols[4] ?? '';
                $unitMeasure = $cols[5] ?: 'Pcs';
                $quantity = (int)($cols[6] ?? 1);
                $notes = $cols[7] ?? '';
            } elseif ($code === '05_08') {
                $refNo = $cols[1] ?: 'BHW-OUT/BAN/2026';
                $partName = $cols[2];
                $partNumber = $cols[3] ?? '';
                $unitMeasure = $cols[4] ?: 'Pcs';
                $quantity = (int)($cols[5] ?? 1);
                $target = $cols[6] ?? '';
                $counterparty = $cols[7] ?: null;
                $notes = trim($target . ' ' . ($cols[8] ?? ''));
            } elseif ($code === '05_10') {
                $refNo = $cols[1] ?: 'BHW-IN/OLI/2026';
                $counterparty = $cols[2] ?: 'Pekanbaru';
                $partName = $cols[3];
                $partNumber = $cols[4] ?? '';
                $unitMeasure = $cols[5] ?: 'Drum';
                $quantity = (int)($cols[6] ?? 1);
                $notes = trim(($cols[7] ?? '') . ' ' . ($cols[8] ?? ''));
            } elseif ($code === '05_11') {
                $refNo = $cols[1] ?: 'BHW-OUT/OLI/2026';
                $partName = $cols[2];
                $partNumber = $cols[3] ?? '';
                $unitMeasure = $cols[4] ?: 'Liter';
                $quantity = (int)($cols[5] ?? 1);
                $target = $cols[6] ?? '';
                $notes = $target;
            }

            $pnUpper = strtoupper(trim($partNumber));
            $nameNorm = norm($partName);

            // Match or register
            $partId = null;
            if ($pnUpper !== '' && isset($partByNumber[$pnUpper])) {
                $partId = $partByNumber[$pnUpper];
            } elseif ($nameNorm !== '' && isset($partByName[$nameNorm])) {
                $partId = $partByName[$nameNorm];
            }

            if ($partId === null) {
                // Determine category
                $cat = 'Other';
                if ($cfg['cat'] === 'TIRES' || stripos($partName, 'Ban') !== false) $cat = 'Tire';
                elseif ($cfg['cat'] === 'BATTERY' || stripos($partName, 'Baterai') !== false || stripos($partName, 'Aki') !== false) $cat = 'Battery';
                elseif ($cfg['cat'] === 'LUBRICANT' || stripos($partName, 'Oli') !== false || stripos($partName, 'Oil') !== false || stripos($partName, 'Grease') !== false) $cat = 'Fast Moving';
                elseif (stripos($partName, 'Filter') !== false) $cat = 'Filter';
                elseif (stripos($partName, 'Hose') !== false) $cat = 'Hose';
                elseif (stripos($partName, 'Valve') !== false || stripos($partName, 'Bearing') !== false || stripos($partName, 'Gasket') !== false) $cat = 'Engine Part';

                $assignedPn = $partNumber;
                if (trim($assignedPn) === '') {
                    $maxHw++;
                    $assignedPn = sprintf('GEN-HW-%04d', $maxHw);
                }

                $insPart->execute([
                    ':part_number' => $assignedPn,
                    ':part_name' => $partName,
                    ':category' => $cat,
                    ':unit_measure' => $unitMeasure ?: 'Pcs',
                ]);
                $partId = (int)$pdo->lastInsertId();
                $newPartsAdded++;

                // Register into local lookups
                $existingParts[$partId] = [
                    'part_id' => $partId,
                    'part_number' => $assignedPn,
                    'part_name' => $partName,
                    'category' => $cat,
                    'unit_measure' => $unitMeasure ?: 'Pcs',
                    'stock_qty' => 0,
                ];
                if ($assignedPn !== '') $partByNumber[strtoupper($assignedPn)] = $partId;
                if ($nameNorm !== '') $partByName[$nameNorm] = $partId;
            }

            $assetId = ($cfg['type'] === 'OUT') ? resolveAssetId($target, $assetMap, $synonyms) : null;
            $woId = ($cfg['type'] === 'OUT' && $assetId) ? resolveWoId($assetId, $txDate, $woByAsset) : null;

            $rawTransactions[] = [
                'file' => $code,
                'pos' => $itemPos,
                'report_id' => $cfg['rep'],
                'movement_type' => $cfg['type'],
                'transaction_date' => $txDate,
                'reference_number' => $refNo,
                'counterparty' => $counterparty,
                'asset_id' => $assetId,
                'wo_id' => $woId,
                'part_id' => $partId,
                'quantity' => max(1, $quantity),
                'unit_measure' => $unitMeasure ?: 'Pcs',
                'notes' => $notes ?: null,
            ];
        }
    }

    echo "      -> New catalog parts registered into database: $newPartsAdded\n";
    echo "      -> Total database parts catalog size: " . count($existingParts) . "\n";
    echo "      -> Total transactions parsed across all files: " . count($rawTransactions) . "\n\n";

    // -------------------------------------------------------------
    // Step 5: Inventory Math Balance Reconstruction
    // -------------------------------------------------------------
    echo "[5/6] Executing Mathematical Inventory Reconstruction...\n";

    // Sort chronologically
    usort($rawTransactions, function ($a, $b) {
        if ($a['transaction_date'] !== $b['transaction_date']) {
            return strcmp($a['transaction_date'], $b['transaction_date']);
        }
        return $a['pos'] <=> $b['pos'];
    });

    // Group transactions by part_id
    $txByPart = [];
    foreach ($rawTransactions as $idx => $tx) {
        $txByPart[$tx['part_id']][] = $idx;
    }

    // Compute safe starting stock per part
    $startingStock = [];
    $targetEndingStock = [];

    foreach ($existingParts as $pid => $p) {
        $pn = strtoupper(trim($p['part_number'] ?? ''));
        $pnn = norm($p['part_name'] ?? '');
        $targetEnd = null;

        if ($pn !== '' && isset($stockMaster['PN:' . $pn])) {
            $targetEnd = $stockMaster['PN:' . $pn]['ending'];
        } elseif ($pnn !== '' && isset($stockMaster['NAME:' . $pnn])) {
            $targetEnd = $stockMaster['NAME:' . $pnn]['ending'];
        }

        $indexes = $txByPart[$pid] ?? [];
        if (empty($indexes)) {
            $startingStock[$pid] = $targetEnd ?? 0;
            $targetEndingStock[$pid] = $targetEnd ?? 0;
            continue;
        }

        $cum = 0;
        $minCum = 0;
        $totIn = 0;
        $totOut = 0;
        foreach ($indexes as $idx) {
            $t = $rawTransactions[$idx];
            if ($t['movement_type'] === 'IN') {
                $cum += $t['quantity'];
                $totIn += $t['quantity'];
            } else {
                $cum -= $t['quantity'];
                $totOut += $t['quantity'];
            }
            if ($cum < $minCum) {
                $minCum = $cum;
            }
        }

        $safeStart = max(0, -$minCum);
        if ($targetEnd !== null) {
            $neededForTarget = $targetEnd - ($totIn - $totOut);
            $start = max($safeStart, $neededForTarget);
        } else {
            $start = $safeStart;
        }

        $startingStock[$pid] = $start;
        $targetEndingStock[$pid] = ($targetEnd !== null) ? $targetEnd : max(0, $start + $totIn - $totOut);
    }

    // Replay transactions with safe balance
    $runningStock = $startingStock;
    $insTx = $pdo->prepare("
        INSERT INTO inventory_transactions (
            report_id, report_item_position, movement_type, transaction_date,
            reference_number, counterparty, asset_id, wo_id, part_id,
            quantity, unit_measure, stock_before, stock_after, notes, created_by, created_at
        ) VALUES (
            :report_id, :report_item_position, :movement_type, :transaction_date,
            :reference_number, :counterparty, :asset_id, :wo_id, :part_id,
            :quantity, :unit_measure, :stock_before, :stock_after, :notes, 1, '2026-01-31 12:00:00'
        )
    ");

    $negativeStockCount = 0;
    $txInserted = 0;

    foreach ($rawTransactions as $tx) {
        $pid = $tx['part_id'];
        $cur = $runningStock[$pid];
        $before = $cur;
        $after = ($tx['movement_type'] === 'IN') ? ($cur + $tx['quantity']) : ($cur - $tx['quantity']);

        if ($after < 0) {
            $negativeStockCount++;
            $after = 0;
        }
        $runningStock[$pid] = $after;

        $insTx->execute([
            ':report_id' => $tx['report_id'],
            ':report_item_position' => $tx['pos'],
            ':movement_type' => $tx['movement_type'],
            ':transaction_date' => $tx['transaction_date'],
            ':reference_number' => $tx['reference_number'],
            ':counterparty' => $tx['counterparty'],
            ':asset_id' => $tx['asset_id'],
            ':wo_id' => $tx['wo_id'],
            ':part_id' => $tx['part_id'],
            ':quantity' => $tx['quantity'],
            ':unit_measure' => $tx['unit_measure'],
            ':stock_before' => $before,
            ':stock_after' => $after,
            ':notes' => $tx['notes'],
        ]);
        $txInserted++;
    }

    echo "      -> Total transactions inserted into inventory_transactions: $txInserted\n";
    echo "      -> Negative stock occurrences: $negativeStockCount (Guarantee: 0)\n\n";

    // -------------------------------------------------------------
    // Step 6: Sync Ending Stock on parts Table
    // -------------------------------------------------------------
    echo "[6/6] Updating Ending Stock on parts Table...\n";
    $updPartStock = $pdo->prepare("UPDATE parts SET stock_qty = :stock_qty WHERE part_id = :part_id");
    $partsStockUpdated = 0;

    foreach ($existingParts as $pid => $p) {
        $finalStock = $targetEndingStock[$pid] ?? $runningStock[$pid] ?? 0;
        $updPartStock->execute([
            ':stock_qty' => max(0, (int)$finalStock),
            ':part_id' => $pid,
        ]);
        $partsStockUpdated++;
    }

    echo "      -> Parts ending stock updated: $partsStockUpdated records.\n";

    // Record migration in app_data_migrations
    $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_4_EQUIPMENT_REPORT_202601', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ")->execute();

    $pdo->commit();
    echo "\n[SUCCESS] Batch 4 Warehouse Migration successfully completed & committed!\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
