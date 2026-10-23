<?php
declare(strict_types=1);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// 1. Fetch DB records
$targetIds = [
    'EXC-00001', 'EXC-00012',
    'EXC-00017', 'EXC-00022',
    'EXC-00006', 'ML-00002',
    'FBT-00008', 'PM-00004',
    'LV-00030', 'LV-00051'
];
$inClause = "'" . implode("','", $targetIds) . "'";
$dbRows = $pdo->query("
    SELECT a.asset_id, a.asset_code, a.hull_number, a.category, a.make_model,
           a.serial_number, a.license_plate, a.previous_license_plate, a.chassis_number,
           a.year_manufacture, a.dealer, a.capacity, a.project_name, a.operational_position,
           l.location_name, a.status
    FROM assets a
    LEFT JOIN locations l ON l.location_id = a.current_location_id
    WHERE a.asset_id IN ($inClause)
")->fetchAll();

$dbById = [];
foreach ($dbRows as $r) {
    $dbById[$r['asset_id']] = $r;
}

// 2. Search REKAP lines for the matching keys
$rekapPath = __DIR__ . '/../REKAP HEAVY EQUIPMENT BRA 2026 - List Data Aset BRA.md';
$lines = file($rekapPath, FILE_IGNORE_NEW_LINES);

$keysToFind = ['C51502', 'DBCH1801', 'B 9096 ZYT', 'KT 9287 KU', 'B9123ZBA', 'B 9123 ZBA'];
$foundRekap = [];

foreach ($lines as $lineNum => $line) {
    foreach ($keysToFind as $k) {
        if (stripos($line, $k) !== false && str_starts_with(trim($line), '|')) {
            $cols = array_map('trim', explode('|', $line));
            if (isset($cols[0]) && $cols[0] === '') array_shift($cols);
            if (!empty($cols) && end($cols) === '') array_pop($cols);
            
            $foundRekap[$k][] = [
                'line_num' => $lineNum + 1,
                'no' => $cols[0] ?? '',
                'nama_asset' => $cols[2] ?? '',
                'dealer' => $cols[3] ?? '',
                'tahun' => $cols[4] ?? '',
                'sn_pelat' => $cols[5] ?? '',
                'kapasitas' => $cols[7] ?? '',
                'kode_unit' => $cols[8] ?? '',
                'lambung' => $cols[9] ?? '',
                'project' => $cols[10] ?? '',
                'lokasi' => $cols[11] ?? '',
                'status' => $cols[12] ?? '',
                'raw_line' => $line
            ];
        }
    }
}

$output = [
    'db_records' => $dbById,
    'rekap_records' => $foundRekap
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

