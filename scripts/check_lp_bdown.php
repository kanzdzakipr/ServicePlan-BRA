<?php
define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
$pdo = Database::getInstance();

$path = __DIR__ . '/../material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI/01_des_25_peb_26.md';
$lines = file($path, FILE_IGNORE_NEW_LINES);
$cells = [];
foreach ($lines as $line) {
    $parts = array_map('trim', explode('|', $line));
    if (count($parts) >= 6 && $parts[1] !== '') {
        $cells[$parts[1]] = $parts[3];
    }
}

// Find rows where column F (ALAT BERAT) has unit names
$records = [];
for ($r = 8; $r <= 1200; $r++) {
    $unit = trim($cells['F' . $r] ?? '');
    if ($unit !== '' && $unit !== 'ALAT BERAT / TRUCK' && $unit !== 'TOTAL' && strlen($unit) >= 2) {
        $no = $cells['E' . $r] ?? '';
        $kondisi = $cells['I' . $r] ?? '';
        $trouble = $cells['S' . $r] ?? '';
        $waktuBd = $cells['J' . $r] ?? '';
        $waktuReady = $cells['L' . $r] ?? '';
        $lokasi = $cells['V' . $r] ?? '';
        $mekanik = $cells['T' . $r] ?? '';
        $records[] = [
            'row' => $r,
            'no' => $no,
            'unit' => $unit,
            'kondisi' => $kondisi,
            'trouble' => substr($trouble, 0, 40),
            'waktu_bd' => $waktuBd,
            'waktu_ready' => $waktuReady,
            'lokasi' => $lokasi,
            'mekanik' => $mekanik
        ];
    }
}

echo "Total breakdown/ready records in Sheet DES 25 - PEB 26: " . count($records) . "\n";
echo "First 5 records:\n";
print_r(array_slice($records, 0, 5));
echo "Last 5 records:\n";
print_r(array_slice($records, -5));

