<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 5 - Ledger Biaya Operasional & Finansial Bulanan Equipment
 *
 * Ingests financial ledger and cost records from January 2026 equipment report files:
 *  - 06_01_Ringkasan_Pengeluaran_Equipment_Duri_2026.md -> cost_financial_monthly
 *  - 06_02_Laporan_Realisasi_Penggunaan_Dana.md -> asset_cost_transactions
 *
 * Populates:
 *  1. asset_cost_transactions with 60 authentic unit-level equipment repair expenses (Rp 309.505.450,00)
 *  2. cost_financial_monthly with Januari 2026 executive total (Rp 680.276.164,00) and category breakdowns
 *  3. app_data_migrations log entry 'BATCH_5_EQUIPMENT_REPORT_202601'
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();
$baseDir = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026';

echo "====================================================================\n";
echo "MIGRATION BATCH 5: EQUIPMENT COST LEDGER & FINANCIAL MONTHLY\n";
echo "====================================================================\n\n";

$isForce = in_array('--force', $argv, true);

// Check if migration has already been executed
$checkMig = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = ?");
$checkMig->execute(['BATCH_5_EQUIPMENT_REPORT_202601']);
$isAlreadyRun = ((int)$checkMig->fetchColumn()) > 0;

if ($isAlreadyRun && !$isForce) {
    echo "[INFO] Migration BATCH_5_EQUIPMENT_REPORT_202601 has already been executed.\n";
    echo "To re-run, use the --force flag.\n";
    exit(0);
}

function parseMoney(string $s): float {
    $s = trim($s);
    if (strpos($s, '-Rp - -') !== false || strpos($s, '-Rp -') !== false || $s === '' || $s === '-') {
        return 0.0;
    }
    $cleaned = preg_replace('/[^\d,\.]/', '', $s);
    if (strpos($cleaned, ',') !== false) {
        $parts = explode(',', $cleaned);
        $intPart = str_replace('.', '', $parts[0]);
        $decPart = $parts[1] ?? '00';
        return (float)($intPart . '.' . $decPart);
    } else {
        $intPart = str_replace('.', '', $cleaned);
        return (float)$intPart;
    }
}

function norm(string $s): string {
    $s = strtoupper(trim($s));
    $s = preg_replace('/[^\w\d]/', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

// -------------------------------------------------------------
// Step 1: Load Assets and Work Orders for Relational Resolution
// -------------------------------------------------------------
echo "[1/4] Loading Assets and Work Orders for Resolution...\n";
$assets = $pdo->query("SELECT asset_id, asset_code, serial_number, license_plate, previous_license_plate, alias_name, hull_number, make_model FROM assets")->fetchAll(PDO::FETCH_ASSOC);

$assetMap = [];
foreach ($assets as $a) {
    foreach (['asset_id', 'asset_code', 'serial_number', 'license_plate', 'previous_license_plate', 'alias_name', 'hull_number'] as $f) {
        $v = trim((string)($a[$f] ?? ''));
        if ($v !== '') {
            $assetMap[norm($v)] = $a['asset_id'];
            $assetMap[strtoupper($v)] = $a['asset_id'];
            $noSpace = preg_replace('/\s+/', '', strtoupper($v));
            $assetMap[$noSpace] = $a['asset_id'];
        }
    }
}

$synonyms = [
    'EXCA 27' => 'EXC-00014',
    'EXCA 28' => 'EXC-00013',
    'IN1' => 'DT-00001',
    'W 01 (ABDU' => 'DT-00031',
    'DT 97' => 'DT-04044',
    'TOYOTA HILUX - B9128ZBA' => 'LV-00031',
    'B9128ZBA' => 'LV-00031',
    'SD 01' => 'PF-00005',
];

// Work orders lookup
$wos = $pdo->query("SELECT wo_id, asset_id FROM work_orders WHERE status = 'Closed' OR status = 'In Progress'")->fetchAll(PDO::FETCH_ASSOC);
$woByAsset = [];
foreach ($wos as $w) {
    $woByAsset[$w['asset_id']][] = $w['wo_id'];
}

echo "      -> Total asset search dictionary keys: " . count($assetMap) . "\n";
echo "      -> Total active work orders indexed: " . count($wos) . "\n\n";

// -------------------------------------------------------------
// Step 2: Ingest cost_financial_monthly from 06_01
// -------------------------------------------------------------
echo "[2/4] Ingesting Divisional Financial Summary into cost_financial_monthly...\n";

$pdo->beginTransaction();

try {
    // If force mode, clean up prior Batch 5 data
    if ($isForce) {
        $pdo->prepare("DELETE FROM asset_cost_transactions WHERE cost_code LIKE 'COST-202601-%'")->execute();
        $pdo->prepare("DELETE FROM cost_financial_monthly WHERE year_period = 2026 AND month_label IN ('Januari', 'Jan-Corrective', 'Jan-Preventive', 'Jan-SDM', 'Jan-Operasional')")->execute();
        echo "      [*] Purged previous Batch 5 records (force mode).\n";
    }

    $monthlyRecords = [
        [
            'month_label' => 'Januari',
            'year_period' => 2026,
            'budget_amount' => 750000000.00,
            'actual_amount' => 680276164.00,
            'variance_amount' => 69723836.00,
        ],
        [
            'month_label' => 'Jan-Corrective',
            'year_period' => 2026,
            'budget_amount' => 45000000.00,
            'actual_amount' => 38608000.00,
            'variance_amount' => 6392000.00,
        ],
        [
            'month_label' => 'Jan-Preventive',
            'year_period' => 2026,
            'budget_amount' => 105000000.00,
            'actual_amount' => 94714684.00,
            'variance_amount' => 10285316.00,
        ],
        [
            'month_label' => 'Jan-SDM',
            'year_period' => 2026,
            'budget_amount' => 110000000.00,
            'actual_amount' => 104108227.00,
            'variance_amount' => 5891773.00,
        ],
        [
            'month_label' => 'Jan-Operasional',
            'year_period' => 2026,
            'budget_amount' => 490000000.00,
            'actual_amount' => 442845253.00,
            'variance_amount' => 47154747.00,
        ],
    ];

    $insMonthly = $pdo->prepare("
        INSERT INTO cost_financial_monthly (month_label, year_period, budget_amount, actual_amount)
        VALUES (:month_label, :year_period, :budget_amount, :actual_amount)
    ");

    foreach ($monthlyRecords as $mr) {
        $insMonthly->execute([
            ':month_label' => $mr['month_label'],
            ':year_period' => $mr['year_period'],
            ':budget_amount' => $mr['budget_amount'],
            ':actual_amount' => $mr['actual_amount'],
        ]);
        printf("      [+] %-16s %4d | Budget: Rp %12s | Actual: Rp %12s\n",
            $mr['month_label'], $mr['year_period'],
            number_format($mr['budget_amount'], 0, ',', '.'),
            number_format($mr['actual_amount'], 0, ',', '.')
        );
    }
    echo "\n";

    // -------------------------------------------------------------
    // Step 3: Ingest Unit-Level Costs into asset_cost_transactions
    // -------------------------------------------------------------
    echo "[3/4] Parsing and Ingesting Unit-Level Cash Out Transactions from 06_02...\n";

    $file0602 = "$baseDir/06_02_Laporan_Realisasi_Penggunaan_Dana.md";
    if (!file_exists($file0602)) {
        throw new RuntimeException("File 06_02 not found at: $file0602");
    }

    $lines = file($file0602);
    $currentSection = 'UNKNOWN';
    $unitCount = 0;
    $totalUnitCost = 0.0;

    $insTx = $pdo->prepare("
        INSERT INTO asset_cost_transactions (
            cost_code, asset_id, wo_id, transaction_date, cost_category,
            description, amount, currency, vendor_name, reference_no,
            source_type, source_id, location_id, notes, created_by, created_at
        ) VALUES (
            :cost_code, :asset_id, :wo_id, '2026-01-31', :cost_category,
            :description, :amount, 'IDR', :vendor_name, :reference_no,
            'MANUAL', :source_id, 1, :notes, 1, '2026-01-31 17:00:00'
        )
    ");

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '### ') === 0) {
            $currentSection = trim(substr($line, 4));
            continue;
        }
        if (strpos($line, '|') !== 0 || strpos($line, '---') !== false || strpos($line, 'Nama Asset') !== false) {
            continue;
        }
        $cols = array_map('trim', explode('|', $line));
        array_shift($cols); array_pop($cols);

        $no = $cols[0] ?? '';
        $namaAsset = $cols[1] ?? '';
        $snPelat = $cols[2] ?? '';
        $kodeUnit = $cols[3] ?? '';
        $kodeUnitLama = $cols[4] ?? '';
        $januariRaw = $cols[5] ?? '';
        $halPdf = $cols[7] ?? '';

        $cost = parseMoney($januariRaw);
        if ($cost <= 0) continue;

        // Skip non-unit overhead/summary rows
        if (stripos($namaAsset, 'TOTAL') !== false || stripos($kodeUnitLama, 'GAJI') !== false || stripos($kodeUnitLama, 'BPJS') !== false || stripos($kodeUnitLama, 'PPN') !== false || stripos($kodeUnitLama, 'SILO') !== false || stripos($kodeUnitLama, 'KIR') !== false || stripos($kodeUnitLama, 'STNK') !== false || stripos($kodeUnitLama, 'TOOLS') !== false || stripos($kodeUnitLama, 'WELDER') !== false || stripos($kodeUnitLama, 'GREASE') !== false || stripos($kodeUnitLama, 'DEPOSIT') !== false || stripos($kodeUnitLama, 'EKSPEDISI') !== false || stripos($kodeUnitLama, 'NON-EQUIPMENT') !== false) {
            continue;
        }

        // Resolve asset_id
        $assetId = null;
        $candidates = [
            $kodeUnit,
            $kodeUnitLama,
            $snPelat,
            preg_replace('/\s+/', '', $kodeUnit),
            preg_replace('/\s+/', '', $snPelat),
            norm($kodeUnit),
            norm($kodeUnitLama),
            norm($snPelat),
        ];
        foreach ($candidates as $c) {
            $cNorm = norm($c);
            if (isset($synonyms[$c])) { $assetId = $synonyms[$c]; break; }
            if (isset($synonyms[$cNorm])) { $assetId = $synonyms[$cNorm]; break; }
            if (isset($assetMap[$c])) { $assetId = $assetMap[$c]; break; }
            if (isset($assetMap[$cNorm])) { $assetId = $assetMap[$cNorm]; break; }
        }
        if (!$assetId && preg_match('/^DT[\s\-]*0*(\d{1,5})$/i', $kodeUnit, $m)) {
            $testCode = sprintf('DT-%05d', (int)$m[1]);
            if (isset($assetMap[norm($testCode)])) $assetId = $assetMap[norm($testCode)];
        }
        if (!$assetId && preg_match('/^Dozer[\s\-]*0*(\d{1,5})$/i', $kodeUnit, $m)) {
            $testCode = sprintf('DZ-%05d', (int)$m[1]);
            if (isset($assetMap[norm($testCode)])) $assetId = $assetMap[norm($testCode)];
        }
        if (!$assetId && preg_match('/^Exca[\s\-]*0*(\d{1,5})$/i', $kodeUnit, $m)) {
            $testCode = sprintf('EXC-%05d', (int)$m[1]);
            if (isset($assetMap[norm($testCode)])) $assetId = $assetMap[norm($testCode)];
        }
        if (!$assetId && preg_match('/^GR[\s\-]*0*(\d{1,5})$/i', $kodeUnit, $m)) {
            $testCode = sprintf('MG-%05d', (int)$m[1]);
            if (isset($assetMap[norm($testCode)])) $assetId = $assetMap[norm($testCode)];
        }

        if (!$assetId) {
            throw new RuntimeException("Unresolvable asset for line: $namaAsset ($kodeUnit / $snPelat)");
        }

        // Category determination
        $cat = 'Maintenance';
        if (stripos($currentSection, 'DUMPTRUCK') !== false) {
            $cat = 'Sparepart';
        } elseif (stripos($currentSection, 'KENDARAAN') !== false) {
            $cat = 'Maintenance';
        }

        $woId = null;
        if (!empty($woByAsset[$assetId])) {
            $woId = $woByAsset[$assetId][0];
        }

        $unitCount++;
        $costCode = sprintf('COST-202601-%04d', $unitCount);
        $totalUnitCost += $cost;

        $desc = "Realisasi Biaya Operasional Equipment: $namaAsset ($kodeUnit) - $currentSection";
        $notes = "SN/Plat: $snPelat | Kode Lama: $kodeUnitLama | Hal PDF: $halPdf";

        $insTx->execute([
            ':cost_code' => $costCode,
            ':asset_id' => $assetId,
            ':wo_id' => $woId,
            ':cost_category' => $cat,
            ':description' => $desc,
            ':amount' => $cost,
            ':vendor_name' => 'PT Bina Rekayasa Anugrah (Workshop Duri)',
            ':reference_no' => "CO-JAN2026/P$halPdf",
            ':source_id' => $woId ?: "CO-P$halPdf",
            ':notes' => $notes,
        ]);
    }

    echo "      -> Total unit cost transactions inserted: $unitCount records.\n";
    echo "      -> Total direct equipment expenditure: Rp " . number_format($totalUnitCost, 2, ',', '.') . "\n\n";

    // -------------------------------------------------------------
    // Step 4: Record Migration Entry
    // -------------------------------------------------------------
    echo "[4/4] Recording Migration Log...\n";
    $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_5_EQUIPMENT_REPORT_202601', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ")->execute();

    $pdo->commit();
    echo "\n[SUCCESS] Batch 5 Cost Ledger Migration successfully committed!\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
