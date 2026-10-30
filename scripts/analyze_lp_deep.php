<?php
define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
$pdo = Database::getInstance();

$baseDir = dirname(__DIR__) . '/material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI';

function parseCellMarkdown($filePath) {
    if (!file_exists($filePath)) return [];
    $lines = file($filePath);
    $cells = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '|') continue;
        $parts = explode('|', $line);
        if (count($parts) < 5) continue;
        $coord = trim($parts[1]);
        $type = trim($parts[2]);
        $val = trim($parts[3]);
        $formula = trim($parts[4] ?? '');
        if ($coord === 'Sel' || $coord === '---') continue;
        if (preg_match('/^([A-Z]+)(\d+)$/', $coord, $m)) {
            $col = $m[1];
            $row = (int)$m[2];
            $cells[$row][$col] = [
                'val' => $val,
                'type' => $type,
                'formula' => $formula
            ];
        }
    }
    return $cells;
}

echo "=== ANALYZING 01_des_25_peb_26.md ===\n";
$c1 = parseCellMarkdown($baseDir . '/01_des_25_peb_26.md');
echo "Rows with data in 01: " . count($c1) . "\n";
// Let's inspect rows and month distribution
$months1 = [];
$unitCount1 = [];
$woCount1 = 0;
foreach ($c1 as $r => $cols) {
    if (!isset($cols['F']) || !isset($cols['J'])) continue; // F is Unit, J is date
    if ($cols['F']['val'] === 'ALAT BERAT / TRUCK' || $cols['F']['val'] === 'Nama Alat') continue;
    $unit = $cols['F']['val'];
    $dateVal = $cols['J']['val'];
    if (is_numeric($dateVal) && $dateVal > 40000) {
        $dateStr = date('Y-m-d', (int)(($dateVal - 25569) * 86400));
        $ym = substr($dateStr, 0, 7);
        $months1[$ym] = ($months1[$ym] ?? 0) + 1;
        $unitCount1[$unit] = ($unitCount1[$unit] ?? 0) + 1;
        $woCount1++;
    }
}
echo "Valid event rows in 01: $woCount1\n";
echo "Month distribution in 01:\n";
print_r($months1);

echo "\n=== ANALYZING 02_jan_2026.md ===\n";
$c2 = parseCellMarkdown($baseDir . '/02_jan_2026.md');
echo "Rows with data in 02: " . count($c2) . "\n";
$sampleRow = null;
foreach ($c2 as $r => $cols) {
    if ($r <= 10) {
        echo "Row $r cols: " . implode(', ', array_keys($cols)) . "\n";
    }
}

echo "\n=== ANALYZING 03_peb_2026.md ===\n";
$c3 = parseCellMarkdown($baseDir . '/03_peb_2026.md');
echo "Rows with data in 03: " . count($c3) . "\n";
foreach ($c3 as $r => $cols) {
    if ($r <= 10) {
        echo "Row $r cols: " . implode(', ', array_keys($cols)) . "\n";
    }
}

echo "\n=== ANALYZING 04_avibility.md ===\n";
$c4 = parseCellMarkdown($baseDir . '/04_avibility.md');
echo "Rows with data in 04: " . count($c4) . "\n";
foreach ($c4 as $r => $cols) {
    if ($r <= 12) {
        echo "Row $r: ";
        foreach ($cols as $colName => $cell) {
            echo "$colName={$cell['val']} | ";
        }
        echo "\n";
    }
}

echo "\n=== ANALYZING 06_jmp_dec25.md ===\n";
$c6 = parseCellMarkdown($baseDir . '/06_jmp_dec25.md');
echo "Rows with data in 06: " . count($c6) . "\n";
foreach ($c6 as $r => $cols) {
    if ($r <= 10) {
        echo "Row $r: ";
        foreach ($cols as $colName => $cell) {
            echo "$colName={$cell['val']} | ";
        }
        echo "\n";
    }
}

