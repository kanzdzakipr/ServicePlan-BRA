<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../core/PartUnitConversion.php';

echo "=== MIGRATION & IMPORT MASTER PARTS DANY & FADLY ===\n";

$pdo = Database::getInstance();

// 1. Read and parse material/STOCK_GUDANG.md
$stockGudangPath = __DIR__ . '/../material/STOCK_GUDANG.md';
if (!file_exists($stockGudangPath)) {
    throw new RuntimeException("File material/STOCK_GUDANG.md tidak ditemukan!");
}

$content = file_get_contents($stockGudangPath);
$lines = explode("\n", $content);

$batchKey = 'BATCH-DANY-FADLY-' . date('Ymd-His');
echo "Batch Key: {$batchKey}\n";

$rows = [];
$rowNum = 0;
$inTable = false;

foreach ($lines as $lineIndex => $line) {
    $trimmed = trim($line);
    if (!str_starts_with($trimmed, '|')) {
        continue;
    }
    
    $cols = array_map('trim', explode('|', $trimmed));
    // Remove first and last empty elements from pipe split
    if (count($cols) >= 3 && $cols[0] === '') {
        array_shift($cols);
    }
    if (count($cols) >= 1 && $cols[count($cols) - 1] === '') {
        array_pop($cols);
    }
    
    if (count($cols) < 3) {
        continue;
    }
    
    // Check if header or separator
    if ($cols[0] === 'Nama Parts' || str_starts_with($cols[0], '---') || str_starts_with($cols[0], ':---')) {
        continue;
    }
    
    $partName = $cols[0];
    if ($partName === '' || $partName === 'Nama Parts') {
        continue;
    }
    
    $partNumber = $cols[1] ?? '';
    $satuan = $cols[2] ?? 'pcs';
    $saldoStok = 0.0;
    
    // The last column is Sisa/Saldo Stok
    $lastCol = end($cols);
    if (is_numeric($lastCol)) {
        $saldoStok = (float)$lastCol;
    } else {
        // Find numeric value in last columns
        for ($i = count($cols) - 1; $i >= 3; $i--) {
            if (is_numeric($cols[$i])) {
                $saldoStok = (float)$cols[$i];
                break;
            }
        }
    }
    
    $rowNum++;
    $rows[] = [
        'row_number' => $rowNum,
        'part_name' => $partName,
        'part_number' => $partNumber,
        'satuan' => $satuan,
        'saldo_stok' => $saldoStok,
    ];
}

echo "Parsed rows from STOCK_GUDANG.md: " . count($rows) . "\n";

// 2. Prepare staging insertion
$stmtStage = $pdo->prepare("
    INSERT INTO part_import_staging (
        batch_key, `row_number`, sku, technical_name, authenticity, part_group,
        base_uom, transaction_uom, conversion_factor, warehouse_location,
        opening_balance, validation_status, validation_errors, created_at
    ) VALUES (
        :batch_key, :row_number, :sku, :technical_name, :authenticity, :part_group,
        :base_uom, :transaction_uom, :conversion_factor, :warehouse_location,
        :opening_balance, :validation_status, :validation_errors, NOW()
    )
");

$seenSkus = [];
$stagedCount = 0;
$validCount = 0;
$duplicateCount = 0;
$invalidCount = 0;

$stagedItems = [];

foreach ($rows as $item) {
    $name = $item['part_name'];
    $pn = trim($item['part_number']);
    $rawUom = $item['satuan'];
    $balance = $item['saldo_stok'];
    
    // Determine SKU
    if ($pn !== '') {
        $sku = strtoupper($pn);
    } else {
        // Generate pseudo SKU from sanitized name
        $cleanName = preg_replace('/[^A-Za-z0-9]+/', '-', strtoupper(trim($name)));
        $cleanName = trim($cleanName, '-');
        $sku = 'STK-' . substr(md5($name), 0, 8) . '-' . substr($cleanName, 0, 15);
    }
    
    // Technical Name
    $technicalName = trim($name);
    if ($pn !== '' && !str_contains(strtoupper($technicalName), strtoupper($pn))) {
        $technicalName .= ' (' . $pn . ')';
    }
    
    // Authenticity
    $authenticity = PartUnitConversion::inferAuthenticity($name, $pn);
    
    // Part Group
    $partGroup = PartUnitConversion::inferPartGroup($name);
    
    // UOM & Conversion
    $uomData = PartUnitConversion::inferUom($name, $rawUom);
    $baseUom = $uomData['base_uom'];
    $transactionUom = $uomData['transaction_uom'];
    $conversionFactor = $uomData['conversion_factor'];
    
    // Validation
    $status = 'VALID';
    $errors = [];
    
    if (trim($name) === '') {
        $status = 'INVALID';
        $errors[] = 'Nama part kosong';
    }
    
    if (isset($seenSkus[$sku])) {
        $status = 'DUPLICATE';
        $errors[] = "SKU duplikat dengan baris {$seenSkus[$sku]}";
        $duplicateCount++;
    } else {
        $seenSkus[$sku] = $item['row_number'];
        if ($status === 'VALID') {
            $validCount++;
        } else {
            $invalidCount++;
        }
    }
    
    $errorString = !empty($errors) ? implode('; ', $errors) : null;
    
    $stmtStage->execute([
        'batch_key' => $batchKey,
        'row_number' => $item['row_number'],
        'sku' => $sku,
        'technical_name' => $technicalName,
        'authenticity' => $authenticity,
        'part_group' => $partGroup,
        'base_uom' => $baseUom,
        'transaction_uom' => $transactionUom,
        'conversion_factor' => $conversionFactor,
        'warehouse_location' => 'Gudang Yard KM 12',
        'opening_balance' => $balance,
        'validation_status' => $status,
        'validation_errors' => $errorString,
    ]);
    
    $stagedCount++;
    
    if ($status === 'VALID') {
        $stagedItems[] = [
            'sku' => $sku,
            'part_number' => $pn !== '' ? $pn : $sku,
            'part_name' => $name,
            'technical_name' => $technicalName,
            'authenticity' => $authenticity,
            'part_group' => $partGroup,
            'base_uom' => $baseUom,
            'transaction_uom' => $transactionUom,
            'conversion_factor' => $conversionFactor,
            'stock_qty' => (int)round($balance),
        ];
    }
}

echo "Staging Summary:\n";
echo "- Total Staged: {$stagedCount}\n";
echo "- Valid: {$validCount}\n";
echo "- Duplicate: {$duplicateCount}\n";
echo "- Invalid: {$invalidCount}\n";

// 3. Add explicit Vendor Oil / Drum items from material/LAPORAN-KEUANGAN/05_Buku_Besar_Cashout_dan_Perbandingan_Harga_Vendor.md
$vendorOils = [
    [
        'part_number' => 'OIL-15W40-DRUM200',
        'part_name' => 'Oli Mesin SAE 15W40 Drum 200L (PT King Indo / Giar)',
        'technical_name' => 'Engine Oil Heavy Duty SAE 15W-40 Drum 200 Liter',
        'authenticity' => 'GENUINE',
        'part_group' => 'Pelumas',
        'category' => 'Fast Moving',
        'unit_measure' => 'Drum',
        'base_uom' => 'Liter',
        'transaction_uom' => 'Drum',
        'conversion_factor' => 200.0,
        'stock_qty' => 10,
        'unit_cost' => 8400000.00,
    ],
    [
        'part_number' => 'OIL-15W40-DRUM209',
        'part_name' => 'Oli Mesin SAE 15W40 Drum 209L (PT Surya Mandiri Makmur)',
        'technical_name' => 'Engine Oil Heavy Duty SAE 15W-40 Drum 209 Liter',
        'authenticity' => 'GENUINE',
        'part_group' => 'Pelumas',
        'category' => 'Fast Moving',
        'unit_measure' => 'Drum',
        'base_uom' => 'Liter',
        'transaction_uom' => 'Drum',
        'conversion_factor' => 209.0,
        'stock_qty' => 8,
        'unit_cost' => 8569000.00,
    ],
    [
        'part_number' => 'OIL-SAE30-DRUM200',
        'part_name' => 'Oli Transmisi SAE 30 Drum 200L (PT King Indo)',
        'technical_name' => 'Transmission & Drive Train Oil SAE 30 Drum 200 Liter',
        'authenticity' => 'GENUINE',
        'part_group' => 'Pelumas',
        'category' => 'Fast Moving',
        'unit_measure' => 'Drum',
        'base_uom' => 'Liter',
        'transaction_uom' => 'Drum',
        'conversion_factor' => 200.0,
        'stock_qty' => 5,
        'unit_cost' => 7387500.00,
    ],
    [
        'part_number' => 'OIL-HYD68-DRUM200',
        'part_name' => 'Hydraulic Oil ISO VG 68 Drum 200L (PT Riodi Jaya)',
        'technical_name' => 'Hydraulic Oil ISO VG 68 Drum 200 Liter',
        'authenticity' => 'GENUINE',
        'part_group' => 'Pelumas',
        'category' => 'Hydraulic',
        'unit_measure' => 'Drum',
        'base_uom' => 'Liter',
        'transaction_uom' => 'Drum',
        'conversion_factor' => 200.0,
        'stock_qty' => 6,
        'unit_cost' => 6216000.00,
    ],
    [
        'part_number' => 'OIL-TDTO50-PAIL20',
        'part_name' => 'Oli Final Drive TDTO SAE 50 Pail 20L',
        'technical_name' => 'Transmission and Drive Train Oil TDTO SAE 50 Pail 20 Liter',
        'authenticity' => 'GENUINE',
        'part_group' => 'Pelumas',
        'category' => 'Fast Moving',
        'unit_measure' => 'Pail',
        'base_uom' => 'Liter',
        'transaction_uom' => 'Pail',
        'conversion_factor' => 20.0,
        'stock_qty' => 15,
        'unit_cost' => 1250000.00,
    ],
    [
        'part_number' => 'GREASE-EP2-DRUM180',
        'part_name' => 'Grease EP2 Heavy Duty Drum 180Kg',
        'technical_name' => 'Lithium Complex Extreme Pressure Grease NLGI 2 Drum 180 Kg',
        'authenticity' => 'GENUINE',
        'part_group' => 'Pelumas',
        'category' => 'Fast Moving',
        'unit_measure' => 'Drum',
        'base_uom' => 'Kg',
        'transaction_uom' => 'Drum',
        'conversion_factor' => 180.0,
        'stock_qty' => 4,
        'unit_cost' => 9500000.00,
    ],
    [
        'part_number' => 'GREASE-EP2-PAIL15',
        'part_name' => 'Grease EP2 Heavy Duty Pail 15Kg',
        'technical_name' => 'Lithium Complex Extreme Pressure Grease NLGI 2 Pail 15 Kg',
        'authenticity' => 'GENUINE',
        'part_group' => 'Pelumas',
        'category' => 'Fast Moving',
        'unit_measure' => 'Pail',
        'base_uom' => 'Kg',
        'transaction_uom' => 'Pail',
        'conversion_factor' => 15.0,
        'stock_qty' => 12,
        'unit_cost' => 850000.00,
    ],
];

// 4. Enrich existing `parts` table and upsert staged items
echo "\n--- Synchronizing to parts table ---\n";

// A. Map category helper
function mapToLegacyCategory(string $partGroup): string {
    return match ($partGroup) {
        'Filter Udara', 'Filter Oli', 'Filter Solar', 'Filter Hidrolik' => 'Filter',
        'Selang & Fitting' => 'Hose',
        'Cutting Bit & Blade' => 'Cutting Bit',
        'Ban & Velg' => 'Tire',
        'Aki / Baterai' => 'Battery',
        'Engine & Drivetrain' => 'Engine Part',
        'Pelumas' => 'Fast Moving',
        'Brake & Chassis', 'Fast Moving & Hardware' => 'Fast Moving',
        default => 'Other',
    };
}

// B. Load all existing parts by part_number and part_name
$existingPartsStmt = $pdo->query("SELECT part_id, part_number, part_name, category, unit_measure FROM parts");
$existingByPn = [];
$existingByName = [];
while ($p = $existingPartsStmt->fetch(PDO::FETCH_ASSOC)) {
    $existingByPn[strtoupper(trim($p['part_number']))] = $p;
    $existingByName[strtoupper(trim($p['part_name']))] = $p;
}

$stmtUpdatePart = $pdo->prepare("
    UPDATE parts SET
        technical_name = :technical_name,
        authenticity = :authenticity,
        part_group = :part_group,
        base_uom = :base_uom,
        transaction_uom = :transaction_uom,
        conversion_factor = :conversion_factor,
        stock_qty = GREATEST(stock_qty, :stock_qty),
        location_warehouse = 'Gudang Yard KM 12'
    WHERE part_id = :part_id
");

$stmtInsertPart = $pdo->prepare("
    INSERT INTO parts (
        part_number, part_name, technical_name, authenticity, part_group,
        category, unit_measure, base_uom, transaction_uom, conversion_factor,
        stock_qty, min_stock_qty, unit_cost, location_warehouse
    ) VALUES (
        :part_number, :part_name, :technical_name, :authenticity, :part_group,
        :category, :unit_measure, :base_uom, :transaction_uom, :conversion_factor,
        :stock_qty, :min_stock_qty, :unit_cost, 'Gudang Yard KM 12'
    )
");

$updatedCount = 0;
$insertedCount = 0;

// Upsert staged items
foreach ($stagedItems as $item) {
    $pnUpper = strtoupper(trim($item['part_number']));
    $nameUpper = strtoupper(trim($item['part_name']));
    
    $existing = $existingByPn[$pnUpper] ?? ($existingByName[$nameUpper] ?? null);
    
    if ($existing !== null) {
        $stmtUpdatePart->execute([
            'technical_name' => $item['technical_name'],
            'authenticity' => $item['authenticity'],
            'part_group' => $item['part_group'],
            'base_uom' => $item['base_uom'],
            'transaction_uom' => $item['transaction_uom'],
            'conversion_factor' => $item['conversion_factor'],
            'stock_qty' => $item['stock_qty'],
            'part_id' => $existing['part_id'],
        ]);
        $updatedCount++;
    } else {
        $category = mapToLegacyCategory($item['part_group']);
        $stmtInsertPart->execute([
            'part_number' => $item['part_number'],
            'part_name' => $item['part_name'],
            'technical_name' => $item['technical_name'],
            'authenticity' => $item['authenticity'],
            'part_group' => $item['part_group'],
            'category' => $category,
            'unit_measure' => $item['transaction_uom'],
            'base_uom' => $item['base_uom'],
            'transaction_uom' => $item['transaction_uom'],
            'conversion_factor' => $item['conversion_factor'],
            'stock_qty' => $item['stock_qty'],
            'min_stock_qty' => 2,
            'unit_cost' => 0.00,
        ]);
        $insertedCount++;
        // Add to cache to avoid duplicate inserts
        $existingByPn[$pnUpper] = ['part_id' => (int)$pdo->lastInsertId()];
    }
}

// Upsert vendor oil items
foreach ($vendorOils as $oil) {
    $pnUpper = strtoupper(trim($oil['part_number']));
    $existing = $existingByPn[$pnUpper] ?? null;
    
    if ($existing !== null) {
        $stmtUpdatePart->execute([
            'technical_name' => $oil['technical_name'],
            'authenticity' => $oil['authenticity'],
            'part_group' => $oil['part_group'],
            'base_uom' => $oil['base_uom'],
            'transaction_uom' => $oil['transaction_uom'],
            'conversion_factor' => $oil['conversion_factor'],
            'stock_qty' => $oil['stock_qty'],
            'part_id' => $existing['part_id'],
        ]);
        $updatedCount++;
    } else {
        $stmtInsertPart->execute([
            'part_number' => $oil['part_number'],
            'part_name' => $oil['part_name'],
            'technical_name' => $oil['technical_name'],
            'authenticity' => $oil['authenticity'],
            'part_group' => $oil['part_group'],
            'category' => $oil['category'],
            'unit_measure' => $oil['unit_measure'],
            'base_uom' => $oil['base_uom'],
            'transaction_uom' => $oil['transaction_uom'],
            'conversion_factor' => $oil['conversion_factor'],
            'stock_qty' => $oil['stock_qty'],
            'min_stock_qty' => 2,
            'unit_cost' => $oil['unit_cost'],
        ]);
        $insertedCount++;
        $existingByPn[$pnUpper] = ['part_id' => (int)$pdo->lastInsertId()];
    }
}

// C. Enrich remaining existing parts that still have NULL technical_name or NULL base_uom
$stmtRemaining = $pdo->query("SELECT part_id, part_number, part_name, category, unit_measure FROM parts WHERE technical_name IS NULL OR base_uom IS NULL OR part_group IS NULL");
$remainingRows = $stmtRemaining->fetchAll(PDO::FETCH_ASSOC);
$enrichedRemaining = 0;

$stmtEnrich = $pdo->prepare("
    UPDATE parts SET
        technical_name = :technical_name,
        authenticity = :authenticity,
        part_group = :part_group,
        base_uom = :base_uom,
        transaction_uom = :transaction_uom,
        conversion_factor = :conversion_factor
    WHERE part_id = :part_id
");

foreach ($remainingRows as $rem) {
    $name = $rem['part_name'];
    $pn = $rem['part_number'];
    $cat = $rem['category'] ?? '';
    $uom = $rem['unit_measure'] ?? 'Pcs';
    
    $tech = trim($name);
    if (!empty($pn) && !str_contains(strtoupper($tech), strtoupper($pn))) {
        $tech .= ' (' . $pn . ')';
    }
    $auth = PartUnitConversion::inferAuthenticity($name, $pn);
    $grp = PartUnitConversion::inferPartGroup($name, $cat);
    $uomInf = PartUnitConversion::inferUom($name, $uom);
    
    $stmtEnrich->execute([
        'technical_name' => $tech,
        'authenticity' => $auth,
        'part_group' => $grp,
        'base_uom' => $uomInf['base_uom'],
        'transaction_uom' => $uomInf['transaction_uom'],
        'conversion_factor' => $uomInf['conversion_factor'],
        'part_id' => $rem['part_id'],
    ]);
    $enrichedRemaining++;
}

echo "Parts Table Sync:\n";
echo "- Updated from staged: {$updatedCount}\n";
echo "- Inserted new parts: {$insertedCount}\n";
echo "- Enriched existing pre-migration parts: {$enrichedRemaining}\n";

// Verify drum conversion parts in DB
$drumParts = $pdo->query("SELECT part_number, part_name, base_uom, transaction_uom, conversion_factor FROM parts WHERE transaction_uom IN ('Drum', 'Pail') ORDER BY conversion_factor DESC")->fetchAll(PDO::FETCH_ASSOC);
echo "\n--- Sample Drum & Pail Parts in Catalog (" . count($drumParts) . " total) ---\n";
foreach (array_slice($drumParts, 0, 10) as $dp) {
    echo "  [{$dp['part_number']}] {$dp['part_name']}: 1 {$dp['transaction_uom']} = {$dp['conversion_factor']} {$dp['base_uom']}\n";
}

echo "\n=== Migration Completed Successfully ===\n";
