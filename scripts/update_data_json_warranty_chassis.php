<?php
/**
 * Update warranty & chassis_number in data.json based on scripts/list_data_aset_bra.csv
 */

$csvPath = __DIR__ . '/list_data_aset_bra.csv';
$jsonPath = dirname(__DIR__) . '/data.json';

if (!file_exists($csvPath)) {
    die("CSV file not found: $csvPath\n");
}
if (!file_exists($jsonPath)) {
    die("JSON file not found: $jsonPath\n");
}

function parseWarranty($raw) {
    $raw = trim($raw ?? '');
    if ($raw === '') return '0 HM';
    $lower = strtolower($raw);

    // Rule 1: tidak waranty -> 0 HM
    if (strpos($lower, 'tidak') !== false) {
        return '0 HM';
    }

    // Rule 2 & 3: Warranty - (angka) / Warranty (angka) / Warranty -(angka)
    if (preg_match('/warran?ty\s*[-–—]?\s*(\d+)/i', $raw, $m)) {
        return $m[1] . ' HM';
    }

    // Rule 4: warranty (selain di atas)
    if (strpos($lower, 'warran') !== false) {
        return 'Warranty (Tidak Ada HM)';
    }

    // If pure number
    if (preg_match('/^(\d+)$/', $raw, $m)) {
        return $m[1] . ' HM';
    }

    return '0 HM';
}

function cleanStr($s) {
    if ($s === null) return '';
    $s = trim($s);
    return ($s === '-' || $s === 'N/A' || $s === 'null') ? '' : $s;
}

// 1. Parse CSV
$fp = fopen($csvPath, 'r');
$header = fgetcsv($fp); // skip header row

$sheetRecords = [];
$byHull = [];
$byKode = [];
$byPlate = [];
$bySerial = [];

while (($row = fgetcsv($fp)) !== false) {
    // Columns in list_data_aset_bra.csv:
    // 0: No, 1: Category, 2: Model, 3: Serial Number, 4: Year, 5: Status, 6: Status Warranty, 7: Lokasi,
    // 8: Kode Unit, 9: Nomor Lambung Aktual, 10: Operator, 11: HM, 12: KM, 13: Plat Nomor, 14: Nomor Mesin, 15: NOMOR RANGKA
    $rawWarranty = $row[6] ?? '';
    $kodeUnit = cleanStr($row[8] ?? '');
    $lambung = cleanStr($row[9] ?? '');
    $serial = cleanStr($row[3] ?? '');
    $plat = cleanStr($row[13] ?? '');
    $noRangka = cleanStr($row[15] ?? '');

    $warranty = parseWarranty($rawWarranty);

    $record = [
        'raw_warranty' => $rawWarranty,
        'warranty' => $warranty,
        'no_rangka' => $noRangka !== '' ? $noRangka : null,
        'kode' => $kodeUnit,
        'lambung' => $lambung,
        'serial' => $serial,
        'plat' => $plat,
    ];
    $sheetRecords[] = $record;

    if ($lambung !== '') {
        $key = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $lambung));
        $byHull[$key] = $record;
    }
    if ($kodeUnit !== '') {
        $key = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kodeUnit));
        $byKode[$key] = $record;
    }
    if ($plat !== '') {
        $key = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plat));
        $byPlate[$key] = $record;
    }
    if ($serial !== '') {
        $key = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $serial));
        $bySerial[$key] = $record;
    }
}
fclose($fp);

echo "Loaded " . count($sheetRecords) . " records from CSV.\n";

// 2. Load and Update data.json
$jsonContent = file_get_contents($jsonPath);
$data = json_decode($jsonContent, true);

if (!isset($data['assets']) || !is_array($data['assets'])) {
    die("data.json does not contain 'assets' array.\n");
}

$matchedCount = 0;
$chassisFilledCount = 0;

foreach ($data['assets'] as &$asset) {
    $id = $asset['id'] ?? '';
    $assetCode = $asset['asset_code'] ?? '';
    $hull = $asset['hull_number'] ?? '';
    $serial = $asset['serial_number'] ?? '';
    $plate = $asset['license_plate'] ?? '';

    // Extract code and plate/sn from compound id e.g. "DT-00071 - DT 8126 JE"
    $parts = explode('-', $id);
    $primaryCode = trim($parts[0] ?? '');
    if (isset($parts[1]) && count($parts) > 2) {
        $primaryCode .= '-' . trim($parts[1]);
    }
    
    // Find matching record
    $matched = null;

    // Try Lambung exact/norm
    foreach ([$hull, $primaryCode, $id, $assetCode] as $candidate) {
        if ($candidate) {
            $key = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $candidate));
            if (isset($byHull[$key])) {
                $matched = $byHull[$key];
                break;
            }
            if (isset($byKode[$key])) {
                $matched = $byKode[$key];
                break;
            }
        }
    }

    // Try Plate
    if (!$matched) {
        foreach ([$plate, $id] as $candidate) {
            if ($candidate) {
                $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $candidate));
                foreach ($byPlate as $pKey => $rec) {
                    if ($pKey !== '' && strpos($clean, $pKey) !== false) {
                        $matched = $rec;
                        break;
                    }
                }
                if ($matched) break;
            }
        }
    }

    // Try Serial
    if (!$matched && $serial) {
        $sKey = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $serial));
        if (isset($bySerial[$sKey])) {
            $matched = $bySerial[$sKey];
        }
    }

    if ($matched) {
        $matchedCount++;
        $asset['warranty'] = $matched['warranty'];
        $asset['chassis_number'] = $matched['no_rangka'];
        if ($matched['no_rangka']) {
            $chassisFilledCount++;
        }
    } else {
        if (!isset($asset['warranty']) || !$asset['warranty']) {
            $asset['warranty'] = '0 HM';
        }
        if (!isset($asset['chassis_number'])) {
            $asset['chassis_number'] = null;
        }
    }
}
unset($asset);

file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "Successfully updated data.json:\n";
echo "- Total Assets: " . count($data['assets']) . "\n";
echo "- Matched: $matchedCount\n";
echo "- Chassis Number filled: $chassisFilledCount\n";

// Verify DT-00071
foreach ($data['assets'] as $a) {
    if (strpos($a['id'], 'DT-00071') !== false) {
        echo "\nSample Verification DT-00071:\n";
        echo "ID: " . $a['id'] . "\n";
        echo "Warranty: " . $a['warranty'] . "\n";
        echo "Chassis: " . ($a['chassis_number'] ?? 'NULL') . "\n";
        break;
    }
}
