<?php
declare(strict_types=1);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// 1. Fetch DB Assets
$dbAssets = $pdo->query("
    SELECT asset_id, asset_code, hull_number, category, make_model, serial_number, license_plate, current_operator_name
    FROM assets
    WHERE is_active = 1
")->fetchAll();

$assetLookup = [];
$assetsByPlate = [];
$assetsByHull = [];
$assetsBySerial = [];

foreach ($dbAssets as $a) {
    $aid = strtoupper(trim($a['asset_id']));
    $code = strtoupper(trim($a['asset_code'] ?? ''));
    $hull = strtoupper(trim($a['hull_number'] ?? ''));
    $plate = strtoupper(str_replace(' ', '', trim($a['license_plate'] ?? '')));
    $serial = strtoupper(trim($a['serial_number'] ?? ''));

    if ($aid !== '') $assetLookup[$aid] = $a;
    if ($code !== '') $assetLookup[$code] = $a;
    if ($hull !== '') $assetsByHull[$hull] = $a;
    if ($plate !== '') $assetsByPlate[$plate] = $a;
    if ($serial !== '' && strlen($serial) >= 5) $assetsBySerial[$serial] = $a;
}

// 2. Read Report Markdown
$path = __DIR__ . '/../material/ADMINISTRASI/LAPORAN_DIVISI_EQUIPMENT_JANUARI_2026.md';
$lines = file($path, FILE_IGNORE_NEW_LINES);

$inSection1 = false;
$parsedRows = [];

$ignoreWords = [
    'WELLPAD', 'WUR', 'EW', 'BROPIT', 'HARBAR', 'SELSIN', 'PIT', 'YARD', 'MINAS', 'DURI', 'KM 12', 'KM12',
    'WORKSHOP', 'READY', 'DOWN', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'BREAK DOWN', 'RFU', 'BD',
    'TIDAK ADA', 'TIDAK DIKETAHUI', 'BELUM ADA', 'UNIT BARU', 'PROSES', 'MATING BOARD', 'CEMENT SPREADER'
];

function isLikelyPersonName(string $name, array $ignoreWords): bool {
    $clean = trim($name);
    if (strlen($clean) < 3 || strlen($clean) > 40) return false;
    foreach ($ignoreWords as $ig) {
        if (strcasecmp($clean, $ig) === 0) return false;
    }
    if (!preg_match('/[a-zA-Z]/', $clean)) return false;
    if (preg_match('/^\d+[\/\-\.]\d+/', $clean)) return false;
    if (preg_match('/^[A-Z]{2,4}-\d+$/', $clean)) return false;
    if (preg_match('/^[A-Z]{1,2}\s*\d{1,4}\s*[A-Z]{1,3}$/i', $clean)) return false;
    return true;
}

function cleanOperatorName(string $name): string {
    $name = trim($name);
    $name = preg_replace('/^(BAPAK|BPK|SDR|DRIVER|OPERATOR)\s+/i', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

foreach ($lines as $idx => $line) {
    if (strpos($line, '## Laporan Break Down Januari 2026') !== false) {
        $inSection1 = true;
        continue;
    }
    if ($inSection1 && strpos($line, '### Halaman PDF 14') !== false) {
        $inSection1 = false;
        break;
    }
    if (!$inSection1) continue;

    if (preg_match('/^\s*(\d{1,3})\s+([A-Z]{2,4}-[\w\d\-\s\.\/]+?)(?:\s{2,}|\t)/', $line, $m)) {
        $rowNo = (int)$m[1];
        $fullMatch = $m[0];
        $unitStr = trim($m[2]);
        $rem = substr($line, strlen($fullMatch));

        $operator = '';
        $kondisi = '';
        $tanggalBreakdown = '';
        $mechanic = '';

        if (preg_match('/\b(READY|BREAK\s*DOWN|DOWN|STANDBY|INSPEKSI)\b/i', $rem, $km, PREG_OFFSET_CAPTURE)) {
            $kondisi = strtoupper($km[1][0]);
            $kondisiOffset = $km[0][1];
            $beforeKondisi = trim(substr($rem, 0, $kondisiOffset));

            $cleaned = preg_replace('/\b(WUR\s*-?\s*EW|WUR|EW|HARBAR|BROPIT|PIT|WELLPAD|MINAS|DURI)\b/i', '', $beforeKondisi);
            $cleaned = trim($cleaned, " \t\n\r\0\x0B-");

            if (isLikelyPersonName($cleaned, $ignoreWords)) {
                $operator = cleanOperatorName($cleaned);
            }

            $afterKondisi = substr($rem, $kondisiOffset + strlen($km[0][0]));
            if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $afterKondisi, $dm)) {
                $tanggalBreakdown = $dm[1];
            }
        }

        if (preg_match('/(Darmawan|Rejeki Siregar|Joni Septian|Uskha|Daniel Sitepu|Suwardi|Hendrik|Soleh|Wagiman|Afriyandi|Firlanda|Alex|Charles Sibarani|Syawal|Boy Gabriel|Maman|Jhonny|Jhoni)/i', $line, $mm)) {
            $mechanic = trim($mm[1]);
        }

        $baseCode = '';
        $plate = '';
        $hull = '';
        $serial = '';

        if (preg_match('/([A-Z]{2,4}-[\w\d]+)/i', $unitStr, $bm)) {
            $baseCode = strtoupper($bm[1]);
        }
        if (preg_match('/([A-Z]{1,2}\s*\d{1,4}\s*[A-Z]{1,3})/i', $unitStr, $pm)) {
            $plate = strtoupper(str_replace(' ', '', $pm[1]));
        }
        if (preg_match('/(BRA-\d{1,4})/i', $unitStr, $hm)) {
            $hull = strtoupper($hm[1]);
        }
        if (preg_match('/(SN\s+[\w\d]+|96158\d+|DBCH\d+)/i', $unitStr, $sm)) {
            $serial = strtoupper(trim(preg_replace('/^SN\s+/i', '', $sm[1])));
        }

        $parsedRows[] = [
            'row_no' => $rowNo,
            'line_num' => $idx + 1,
            'raw_unit' => $unitStr,
            'base_code' => $baseCode,
            'plate' => $plate,
            'hull' => $hull,
            'serial' => $serial,
            'operator' => $operator,
            'kondisi' => $kondisi,
            'breakdown_date' => $tanggalBreakdown,
            'mechanic' => $mechanic,
            'line' => $line
        ];
    }
}

// 3. Match against DB Assets
$matchedAssets = [];
$unmatchedUnitCodes = [];

foreach ($parsedRows as $row) {
    if ($row['operator'] === '') continue;

    $matched = null;

    // Check direct code/id
    if ($row['base_code'] !== '' && isset($assetLookup[$row['base_code']])) {
        $matched = $assetLookup[$row['base_code']];
    } elseif ($row['base_code'] !== '') {
        if (str_starts_with($row['base_code'], 'EX-')) {
            $exc = 'EXC-' . substr($row['base_code'], 3);
            if (isset($assetLookup[$exc])) $matched = $assetLookup[$exc];
        } elseif (preg_match('/^([A-Z]{2,3})-(\d+)$/', $row['base_code'], $vm)) {
            $padded = $vm[1] . '-' . str_pad($vm[2], 5, '0', STR_PAD_LEFT);
            if (isset($assetLookup[$padded])) $matched = $assetLookup[$padded];
        }
    }

    // Check by serial
    if (!$matched && $row['serial'] !== '' && isset($assetsBySerial[$row['serial']])) {
        $matched = $assetsBySerial[$row['serial']];
    }

    // Check by plate
    if (!$matched && $row['plate'] !== '' && isset($assetsByPlate[$row['plate']])) {
        $matched = $assetsByPlate[$row['plate']];
    }

    // Check by hull
    if (!$matched && $row['hull'] !== '' && isset($assetsByHull[$row['hull']])) {
        $matched = $assetsByHull[$row['hull']];
    }

    if ($matched) {
        $aid = $matched['asset_id'];
        if (!isset($matchedAssets[$aid])) {
            $matchedAssets[$aid] = [
                'asset' => $matched,
                'operators' => [],
                'latest_date' => '',
                'evidence' => []
            ];
        }
        $op = $row['operator'];
        $matchedAssets[$aid]['operators'][$op] = ($matchedAssets[$aid]['operators'][$op] ?? 0) + 1;
        $matchedAssets[$aid]['evidence'][] = $row;
        if ($row['breakdown_date'] > $matchedAssets[$aid]['latest_date']) {
            $matchedAssets[$aid]['latest_date'] = $row['breakdown_date'];
        }
    } else {
        $unmatchedUnitCodes[$row['base_code']][] = $row;
    }
}

ksort($matchedAssets);

// Generate Migration Plan
$migrationPlan = [];
foreach ($matchedAssets as $aid => $data) {
    arsort($data['operators']);
    $primaryOperator = array_key_first($data['operators']);
    $allOps = array_keys($data['operators']);

    // Standardize date YYYY-MM-DD
    $evidenceDate = '2026-01-01';
    if ($data['latest_date'] && preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $data['latest_date'], $dm)) {
        $evidenceDate = "{$dm[3]}-{$dm[2]}-{$dm[1]}";
    }
    
    $migrationPlan[] = [
        'asset_id' => $aid,
        'hull_number' => $data['asset']['hull_number'],
        'category' => $data['asset']['category'],
        'make_model' => $data['asset']['make_model'],
        'license_plate' => $data['asset']['license_plate'],
        'serial_number' => $data['asset']['serial_number'],
        'primary_operator' => $primaryOperator,
        'frequency' => $data['operators'][$primaryOperator],
        'all_operators' => $allOps,
        'evidence_date' => $evidenceDate,
        'evidence_count' => count($data['evidence']),
        'is_multi_driver' => count($allOps) > 1
    ];
}

echo "Matched assets: " . count($matchedAssets) . " units\n";
file_put_contents(__DIR__ . '/../reports/pic_migration_plan_jan2026.json', json_encode($migrationPlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
