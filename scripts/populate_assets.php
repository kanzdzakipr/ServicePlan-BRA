<?php
$sqlPath = __DIR__ . '/u646470441_ServicePlanBRA.sql';
$rawSql = file_get_contents($sqlPath);

$existingAssetIds = [];
$lines = explode("\n", $rawSql);
$insertLineIndex = -1;

foreach ($lines as $i => $line) {
    if (strpos($line, 'INSERT INTO `assets`') === 0) {
        $insertLineIndex = $i;
        preg_match_all("/\('([^']+)'/", $line, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $id) {
                $existingAssetIds[$id] = true;
            }
        }
    }
}

echo "Existing assets in SQL: " . count($existingAssetIds) . " units\n";

function getCategory($code, $nameStr) {
    $str = strtoupper($code . ' ' . $nameStr);
    if (strpos($str, 'DUMP TRUCK') !== false || strpos($code, 'DT-') === 0 || strpos($code, 'BRA-4') === 0 || strpos($code, 'BRA-5') === 0) return 'Dump Truck';
    if (strpos($str, 'EXCAVATOR') !== false || strpos($code, 'EXC-') === 0 || strpos($code, 'EX-') === 0 || strpos($code, 'EXCA-') === 0 || strpos($code, 'HE-6') === 0) return 'Excavator';
    if (strpos($str, 'BULLDOZER') !== false || strpos($code, 'DZ-') === 0 || strpos($code, 'BRA-01') === 0 || strpos($code, 'BRA-07') === 0) return 'Bulldozer';
    if (strpos($str, 'MOTOR GRADER') !== false || strpos($code, 'MG-') === 0) return 'Motor Grader';
    if (strpos($str, 'COMPACTOR') !== false || strpos($code, 'PF-') === 0 || strpos($code, 'SD-') === 0 || strpos($code, 'VIBRO-') === 0) return 'Vibro Compactor';
    if (strpos($str, 'WATER') !== false || strpos($code, 'WT-') === 0) return 'Water Truck';
    if (strpos($str, 'PRIME MOVER') !== false || strpos($code, 'PM-') === 0) return 'Prime Mover';
    if (strpos($str, 'DOUBLE CABIN') !== false || strpos($code, 'LV-') === 0 || strpos($code, 'B91') === 0 || strpos($code, 'B92') === 0 || strpos($code, 'B96') === 0 || strpos($code, 'B97') === 0) return 'Light Vehicle';
    if (strpos($str, 'TRADO') !== false || strpos($code, 'SL-') === 0 || strpos($code, 'FBT-') === 0 || strpos($code, 'LB-') === 0) return 'Trado';
    if (strpos($str, 'RECLAIMER') !== false || strpos($str, 'SPREADER') !== false || strpos($code, 'CS-') === 0 || strpos($code, 'RM-') === 0) return 'Reclaimer Spreader';
    return 'Other';
}

function getTypeFromCat($cat) {
    if ($cat === 'Light Vehicle') return 'Light Vehicle';
    if ($cat === 'Other') return 'Support Equipment';
    return 'Heavy Equipment';
}

$unitMap = [];

// 1. Read REKAP_DAFTAR_ASET_STANDBY_ALAT_BERAT.md
$standbyFile = __DIR__ . '/../material/REKAP_DAFTAR_ASET_STANDBY_ALAT_BERAT.md';
if (file_exists($standbyFile)) {
    $rows = explode("\n", file_get_contents($standbyFile));
    foreach ($rows as $r) {
        $parts = array_map('trim', explode('|', $r));
        if (count($parts) >= 11 && preg_match('/^\d+$/', $parts[1])) {
            $name = $parts[2];
            $dealer = $parts[3];
            $year = preg_match('/^\d{4}$/', $parts[4]) ? (int)$parts[4] : 2023;
            $serialOrNopol = $parts[5];
            $kodeUnit = $parts[7];
            $nopolAktual = $parts[8];
            $project = $parts[9];
            $lokasi = $parts[10];
            $status = !empty($parts[11]) ? $parts[11] : 'STANDBY';

            $unitId = ($nopolAktual && $nopolAktual !== '-') ? str_replace(' ', '', $nopolAktual) : str_replace(' ', '', $kodeUnit);
            $unitId = trim(str_replace('VIBROBW', '', $unitId));

            if (!empty($unitId) && $unitId !== '-') {
                $cat = getCategory($unitId, $name);
                $unitMap[$unitId] = [
                    'id' => $unitId,
                    'code' => $unitId,
                    'name' => $name,
                    'category' => $cat,
                    'makeModel' => "$name ($dealer)",
                    'year' => $year,
                    'serial' => preg_match('/^[A-Z0-9]{5,}$/', $serialOrNopol) ? $serialOrNopol : null,
                    'plate' => preg_match('/\b[A-Z]{1,2}\s*\d+\s*[A-Z]{1,3}\b/', $serialOrNopol) ? $serialOrNopol : ($nopolAktual !== '-' ? $nopolAktual : null),
                    'status' => $status,
                    'location' => $lokasi
                ];
            }
        }
    }
}

// 2. Read ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md
$mutasiFile = __DIR__ . '/../material/ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md';
if (file_exists($mutasiFile)) {
    $rows = explode("\n", file_get_contents($mutasiFile));
    foreach ($rows as $r) {
        $parts = array_map('trim', explode('|', $r));
        if (count($parts) >= 8 && preg_match('/^\d+$/', $parts[1])) {
            $nopolLama = $parts[3];
            $jenis = $parts[4];
            $proyek = $parts[5];
            $nopolBaru = $parts[7];

            $unitId = !empty($nopolBaru) ? str_replace(' ', '', $nopolBaru) : str_replace(' ', '', $nopolLama);
            if (!empty($unitId) && $unitId !== 'Nopol' && !isset($unitMap[$unitId])) {
                $cat = getCategory($unitId, $jenis);
                $unitMap[$unitId] = [
                    'id' => $unitId,
                    'code' => $unitId,
                    'name' => "$jenis ($nopolLama)",
                    'category' => $cat,
                    'makeModel' => $jenis,
                    'year' => 2022,
                    'serial' => null,
                    'plate' => !empty($nopolBaru) ? $nopolBaru : $nopolLama,
                    'status' => 'OPERATING',
                    'location' => "$proyek Project"
                ];
            }
        }
    }
}

echo "Total unique unit records extracted from material docs: " . count($unitMap) . "\n";

$newTuples = [];

foreach ($unitMap as $id => $u) {
    if (!isset($existingAssetIds[$id])) {
        $idEsc = addslashes($id);
        $codeEsc = addslashes($u['code']);
        $nameEsc = addslashes($u['name']);
        $catEsc = addslashes($u['category']);
        $typeEsc = getTypeFromCat($u['category']);
        $modelEsc = addslashes($u['makeModel']);
        $statusEsc = !empty($u['status']) ? addslashes($u['status']) : 'READY';
        $plateEsc = !empty($u['plate']) ? "'" . addslashes($u['plate']) . "'" : 'NULL';
        $serialEsc = !empty($u['serial']) ? "'" . addslashes($u['serial']) . "'" : 'NULL';
        $locNotesEsc = !empty($u['location']) ? "'" . addslashes($u['location']) . "'" : "'Yard KM 12 Duri'";
        
        $locId = 12;
        if (strpos($u['location'], 'WUR') !== false) $locId = 15;
        else if (strpos($u['location'], 'Palembang') !== false) $locId = 2;
        else if (strpos($u['location'], 'Pekanbaru') !== false) $locId = 64;

        $tuple = "('$idEsc','$codeEsc',$serialEsc,$plateEsc,NULL,'$idEsc','$typeEsc','$catEsc','$modelEsc','PKB PEKANBARU Branch',{$u['year']},'Milik Sendiri','$statusEsc',$locId,$locNotesEsc,0.00,NULL,NULL,NULL,NULL,1,NOW(),NOW())";
        $newTuples[] = $tuple;
    }
}

echo "New missing assets to be added to SQL dump: " . count($newTuples) . "\n";

if (count($newTuples) > 0 && $insertLineIndex >= 0) {
    $line = rtrim($lines[$insertLineIndex], ";");
    $lines[$insertLineIndex] = $line . ",\n" . implode(",\n", $newTuples) . ";";
    file_put_contents($sqlPath, implode("\n", $lines));
    echo "Successfully appended " . count($newTuples) . " new missing assets to u646470441_ServicePlanBRA.sql!\n";
}
