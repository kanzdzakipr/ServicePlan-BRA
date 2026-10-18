<?php
declare(strict_types=1);

// Batch 1: import only rows with a real part number. This is a local CLI tool,
// deliberately separate from the application's authenticated HTTP routes.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$apply = in_array('--apply', $argv, true);
$source = dirname(__DIR__) . '/material/STOCK_GUDANG.md';
$content = file_get_contents($source);
if ($content === false) {
    throw new RuntimeException('Dokumen stok tidak dapat dibaca.');
}
$sourceHash = hash('sha256', $content);
$groups = [];
$unnumbered = 0;
$invalid = [];
$lineNumber = 0;
foreach (preg_split('/\r\n|\n|\r/', $content) as $line) {
    ++$lineNumber;
    if (!str_starts_with($line, '|') || preg_match('/^\|\s*(Nama Parts|---)/u', $line)) {
        continue;
    }
    $cells = array_map(
        static fn (string $cell): string => str_replace('@@PIPE@@', '|', trim($cell)),
        explode('|', str_replace('\\|', '@@PIPE@@', trim($line, '|')))
    );
    if (count($cells) !== 18) {
        $invalid[] = "Baris {$lineNumber}: jumlah kolom bukan 18";
        continue;
    }
    [$name, $number, $unit] = $cells;
    if ($number === '') {
        ++$unnumbered;
        continue;
    }
    $balance = $cells[17];
    if ($name === '' || $unit === '' || mb_strlen($name) > 150 || mb_strlen($number) > 100
        || mb_strlen($unit) > 20 || !ctype_digit($balance) || !ctype_digit($cells[9])
        || !ctype_digit($cells[16]) || (int) $cells[9] - (int) $cells[16] !== (int) $balance) {
        $invalid[] = "Baris {$lineNumber}: identitas atau saldo tidak valid ({$number})";
        continue;
    }
    $key = mb_strtolower($number);
    if (isset($groups[$key])) {
        if (strcasecmp($groups[$key]['unit'], $unit) !== 0 || $groups[$key]['balance'] !== (int) $balance) {
            $invalid[] = "Baris {$lineNumber}: part {$number} punya satuan/saldo berbeda";
        }
        $groups[$key]['source_rows']++;
        continue;
    }
    $groups[$key] = [
        'number' => $number, 'name' => $name, 'unit' => $unit,
        'balance' => (int) $balance, 'line' => $lineNumber, 'source_rows' => 1,
    ];
}
if ($invalid !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_slice($invalid, 0, 20)) . PHP_EOL);
    exit(1);
}

$db = new PDO('mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4',
    'root', getenv('LOCAL_MYSQL_PASSWORD') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
$existing = [];
foreach ($db->query('SELECT part_id, part_number, part_name, unit_measure, stock_qty FROM parts') as $part) {
    $existing[mb_strtolower((string) $part['part_number'])] = $part;
}
$new = array_filter($groups, static fn (array $row, string $key): bool => !isset($existing[$key]), ARRAY_FILTER_USE_BOTH);
$conflicts = [];
foreach ($groups as $key => $row) {
    if (isset($existing[$key]) && (strcasecmp((string) $existing[$key]['part_name'], $row['name']) !== 0
        || strcasecmp((string) $existing[$key]['unit_measure'], $row['unit']) !== 0
        || (int) $existing[$key]['stock_qty'] !== $row['balance'])) {
        $conflicts[] = $row['number'];
    }
}
$summary = [
    'mode' => $apply ? 'apply' : 'dry-run', 'source_sha256' => $sourceHash,
    'period_end' => '2026-09-25', 'numbered_unique' => count($groups),
    'new' => count($new), 'existing_untouched' => count($groups) - count($new),
    'existing_different_untouched' => count($conflicts),
    'unnumbered_deferred' => $unnumbered,
    'duplicate_source_rows' => array_sum(array_column($groups, 'source_rows')) - count($groups),
];
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if ($conflicts !== []) {
    echo 'Nomor yang sudah ada dan berbeda (tidak diubah): ' . implode(', ', array_slice($conflicts, 0, 20)) . PHP_EOL;
}
if (!$apply) {
    exit(0);
}

$db->exec("CREATE TABLE IF NOT EXISTS stock_gudang_imports (
    import_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_sha256 CHAR(64) NOT NULL,
    source_period_end DATE NOT NULL,
    source_line INT NOT NULL,
    part_id INT NOT NULL,
    source_balance INT NOT NULL,
    imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stock_gudang_source_part (source_sha256, part_id),
    CONSTRAINT fk_stock_gudang_part FOREIGN KEY (part_id) REFERENCES parts(part_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$insertPart = $db->prepare("INSERT INTO parts
    (part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, unit_cost, location_warehouse)
    VALUES (:number, :name, 'Other', :unit, :balance, 2, 0, 'Gudang Yard KM 12')");
$insertAudit = $db->prepare('INSERT INTO stock_gudang_imports
    (source_sha256, source_period_end, source_line, part_id, source_balance)
    VALUES (:hash, :period, :line, :part_id, :balance)');
$db->beginTransaction();
try {
    foreach ($new as $row) {
        $insertPart->execute([
            ':number' => $row['number'], ':name' => $row['name'],
            ':unit' => $row['unit'], ':balance' => $row['balance'],
        ]);
        $insertAudit->execute([
            ':hash' => $sourceHash, ':period' => '2026-09-25',
            ':line' => $row['line'], ':part_id' => (int) $db->lastInsertId(),
            ':balance' => $row['balance'],
        ]);
    }
    $db->commit();
} catch (Throwable $error) {
    $db->rollBack();
    throw $error;
}
echo count($new) . " part baru ditambahkan; part lama tidak diubah.\n";
