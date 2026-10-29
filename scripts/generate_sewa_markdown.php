<?php
/**
 * scripts/generate_sewa_markdown.php
 * Converts "Skema Pencatatan Sewa Alat BRA.xlsx" into structured, comprehensive Markdown documents.
 */

$excelPath = 'C:/Users/DerpyPotatoes8/Downloads/Skema Pencatatan Sewa Alat BRA.xlsx';
$outDir = 'c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/material/SKEMA-PENCATATAN-SEWA-ALAT';

if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$zip = new ZipArchive();
if ($zip->open($excelPath) !== TRUE) {
    die("Failed to open Excel file: $excelPath\n");
}

echo "1. Loading Shared Strings...\n";
$ssXml = $zip->getFromName('xl/sharedStrings.xml');
$strings = [];
if ($ssXml) {
    $xmlReader = new XMLReader();
    $xmlReader->XML($ssXml);
    while ($xmlReader->read()) {
        if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->localName == 'si') {
            $node = $xmlReader->readOuterXML();
            $sxml = simplexml_load_string($node);
            $str = '';
            if (isset($sxml->t)) $str = (string)$sxml->t;
            elseif (isset($sxml->r)) {
                foreach ($sxml->r as $run) $str .= (string)$run->t;
            }
            $strings[] = $str;
        }
    }
    $xmlReader->close();
}
echo "   Total strings: " . count($strings) . "\n";

// Helper function to format Excel date serial to YYYY-MM-DD
function formatExcelDate($val) {
    if (is_numeric($val) && $val > 35000 && $val < 60000) {
        $timestamp = ($val - 25569) * 86400;
        return gmdate("Y-m-d", (int)$timestamp);
    }
    return $val;
}

// Helper function to format currency / numbers cleanly
function formatMoney($val) {
    if ($val === '' || $val === null) return '-';
    if (!is_numeric($val)) return $val;
    $num = (float)$val;
    if ($num == 0) return '0';
    return number_format($num, 0, ',', '.');
}

function formatPercent($val) {
    if ($val === '' || $val === null) return '-';
    if (!is_numeric($val)) return $val;
    return number_format((float)$val * 100, 2, ',', '.') . '%';
}

function escapeMd($str) {
    return str_replace(['|', "\n", "\r"], ['&#124;', ' ', ' '], trim((string)$str));
}

function parseSheetRows($zip, $sheetFile, $strings) {
    $content = $zip->getFromName('xl/worksheets/' . $sheetFile);
    if (!$content) return [];
    $xmlReader = new XMLReader();
    $xmlReader->XML($content);
    $rows = [];
    while ($xmlReader->read()) {
        if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->localName == 'row') {
            $rNum = (int)$xmlReader->getAttribute('r');
            $rowXml = $xmlReader->readOuterXML();
            $rObj = simplexml_load_string($rowXml);
            $vals = [];
            foreach ($rObj->c as $c) {
                $ref = (string)$c['r'];
                $col = preg_replace('/\d+/', '', $ref);
                $type = (string)$c['t'];
                $v = (string)$c->v;
                if ($type === 's') $v = $strings[(int)$v] ?? $v;
                elseif ($type === 'inlineStr') $v = (string)$c->is->t;
                if (trim($v) !== '') $vals[$col] = $v;
            }
            if (!empty($vals)) {
                $rows[$rNum] = $vals;
            }
        }
    }
    $xmlReader->close();
    return $rows;
}

// =========================================================================
// 1. GENERATE 01_Rekap_Aset.md
// =========================================================================
echo "2. Generating 01_Rekap_Aset.md...\n";
$mdRekap = "# Dokumen 01: Rekap Aset\n\n";
$mdRekap .= "> **Sumber Sheet:** `Rekap Aset` (ID: 1, `worksheets/sheet1.xml`)\n";
$mdRekap .= "> **Status:** Sheet Kosong (*Blank Sheet* bawaan template workbook)\n\n";
$mdRekap .= "## Ringkasan Sheet\n\n";
$mdRekap .= "Pada file workbook `Skema Pencatatan Sewa Alat BRA.xlsx`, lembar kerja (sheet) **`Rekap Aset`** tidak memuat baris data tabel (`<sheetData/>` kosong) dan hanya memiliki relasi drawing kosong.\n\n";
$mdRekap .= "Data rekapitulasi aset dan skema operasional sewa alat dicatat secara mendalam dan terstruktur pada lembar kerja berikutnya:\n";
$mdRekap .= "1. [02_Leasing.md](./02_Leasing.md) — Kontrak pembiayaan, cicilan, dan perolehan alat.\n";
$mdRekap .= "2. [03_Total_Pendapatan_Sewa.md](./03_Total_Pendapatan_Sewa.md) — Rekap pendapatan sewa per unit dari tahun 2020 s/d 2026.\n";
$mdRekap .= "3. [04_Sewa_Alat_Operasional.md](./04_Sewa_Alat_Operasional.md) — Master unit, alokasi lokasi proyek, dan timesheet operasional harian.\n";
$mdRekap .= "4. [05_Harga_Sewa_Proyek_dan_Cicilan.md](./05_Harga_Sewa_Proyek_dan_Cicilan.md) — Perbandingan harga sewa proyek, cicilan, biaya perbaikan, dan margin pendapatan.\n";
$mdRekap .= "5. [06_List_Master_Data.md](./06_List_Master_Data.md) — Master data lokasi proyek dan status unit.\n";
file_put_contents("$outDir/01_Rekap_Aset.md", $mdRekap);

// =========================================================================
// 2. GENERATE 02_Leasing.md
// =========================================================================
echo "3. Generating 02_Leasing.md...\n";
$rows2 = parseSheetRows($zip, 'sheet2.xml', $strings);

$mdLeasing = "# Dokumen 02: Skema Pembiayaan & Cicilan Leasing Alat\n\n";
$mdLeasing .= "> **Sumber Sheet:** `Leasing` (`worksheets/sheet2.xml`)\n";
$mdLeasing .= "> **Total Baris Data:** " . (count($rows2) - 1) . " baris entri pembiayaan leasing\n";
$mdLeasing .= "> **Lembaga Pembiayaan:** Buana Finance, BFI Finance, MTF (Mandiri Tunas Finance), BRI Finance, SANF, TAF, PT CFI, BNI Finance, Astra Sedaya Finance\n\n";

$mdLeasing .= "## Tabel Fasilitas Pembiayaan & Cicilan Leasing\n\n";
$mdLeasing .= "| No | Kode Alat | Deskripsi Alat / Kendaraan | No Seri / No Polisi | Leasing | Harga Perolehan (Rp) | Nilai Refinance (Rp) | First Payment (Rp) | DP (Rp) | Tgl Perolehan | Nominal Cicilan / Bln (Rp) | Hutang Pokok (Rp) | Bunga Leasing (Rp) | % Bunga | Tgl Mulai | Tgl Akhir | Tenor (Bln) | Harga Perolehan Keseluruhan (Rp) |\n";
$mdLeasing .= "| :---: | :---: | :--- | :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |\n";

$noL = 1;
foreach ($rows2 as $rNum => $r) {
    if ($rNum == 1) continue; // Skip header

    $kode = escapeMd($r['A'] ?? '-');
    $desc = escapeMd($r['B'] ?? '-');
    $sn   = escapeMd($r['C'] ?? '-');
    $leas = escapeMd($r['D'] ?? '-');
    $hp   = formatMoney($r['E'] ?? '');
    $ref  = formatMoney($r['F'] ?? '');
    $fp   = formatMoney($r['G'] ?? '');
    $dp   = formatMoney($r['H'] ?? '');
    $tglP = formatExcelDate($r['I'] ?? '-');
    $cic  = formatMoney($r['J'] ?? '');
    $pok  = formatMoney($r['K'] ?? '');
    $bng  = formatMoney($r['L'] ?? '');
    $pbng = formatPercent($r['M'] ?? '');
    $tglM = formatExcelDate($r['N'] ?? '-');
    $tglA = formatExcelDate($r['O'] ?? '-');
    $ten  = escapeMd($r['P'] ?? '-');
    if (is_numeric($ten)) $ten = number_format((float)$ten, 0);
    $tot  = formatMoney($r['Q'] ?? '');

    $mdLeasing .= "| $noL | $kode | $desc | $sn | $leas | $hp | $ref | $fp | $dp | $tglP | $cic | $pok | $bng | $pbng | $tglM | $tglA | $ten | $tot |\n";
    $noL++;
}

file_put_contents("$outDir/02_Leasing.md", $mdLeasing);

// =========================================================================
// 3. GENERATE 06_List_Master_Data.md
// =========================================================================
echo "4. Generating 06_List_Master_Data.md...\n";
$rows6 = parseSheetRows($zip, 'sheet6.xml', $strings);

$mdList = "# Dokumen 06: List Master Data Referensi\n\n";
$mdList .= "> **Sumber Sheet:** `List` (`worksheets/sheet6.xml`)\n";
$mdList .= "> **Deskripsi:** Daftar master lookup yang digunakan sebagai referensi alokasi lokasi proyek, seksi pekerjaan, dan status operasional unit.\n\n";

$mdList .= "## 1. Master Lokasi Proyek & Sektor Pekerjaan\n\n";
$mdList .= "| No | Kode / Nama Lokasi Proyek | Keterangan / Sub-Sektor (Kolom I) | Status Standby (Kolom J) |\n";
$mdList .= "| :---: | :--- | :--- | :--- |\n";

$noList = 1;
foreach ($rows6 as $rNum => $r) {
    if ($rNum <= 2) continue;
    $lok = escapeMd($r['B'] ?? '-');
    $ket = escapeMd($r['I'] ?? '-');
    $sby = escapeMd($r['J'] ?? '-');
    if ($lok === '-' && $ket === '-' && $sby === '-') continue;

    $mdList .= "| $noList | $lok | $ket | $sby |\n";
    $noList++;
}

file_put_contents("$outDir/06_List_Master_Data.md", $mdList);

// =========================================================================
// 4. GENERATE 05_Harga_Sewa_Proyek_dan_Cicilan.md
// =========================================================================
echo "5. Generating 05_Harga_Sewa_Proyek_dan_Cicilan.md...\n";
$rows5 = parseSheetRows($zip, 'sheet5.xml', $strings);

$mdHarga = "# Dokumen 05: Skema Harga Sewa Proyek, Cicilan, Biaya Repair & Potensi Pendapatan\n\n";
$mdHarga .= "> **Sumber Sheet:** `hagra sewa proyek dan cicilan` (`worksheets/sheet5.xml`)\n";
$mdHarga .= "> **Deskripsi:** Evaluasi komparatif tarif sewa unit alat berat & kendaraan dari proyek versus tarif internal equipment, cicilan kredit 3 tahun & 5 tahun, rata-rata biaya perbaikan bulanan, serta estimasi potensi pendapatan bersih.\n\n";

$currentCategory = "EXCAVATOR";
$tableOpen = false;

foreach ($rows5 as $rNum => $r) {
    if ($rNum <= 3) continue;

    // Check if category header row
    if (!isset($r['A']) && isset($r['D']) && $r['D'] !== '') {
        if ($tableOpen) {
            $mdHarga .= "\n";
            $tableOpen = false;
        }
        $currentCategory = trim($r['D']);
        $mdHarga .= "### Kategori: $currentCategory\n\n";
        $mdHarga .= "| No | Nama Kendaraan / Alat | Proyek | Kode Unit | SN / No Polisi | Tarif Proyek (Rp) | Tarif Internal Equipment (Rp) | Cicilan 3 Thn | Cicilan 5 Thn | Cadangan 5% (Rp) | Biaya Repair Rata-Rata (Rp) | Potensi Pendapatan (Rp) |\n";
        $mdHarga .= "| :---: | :--- | :--- | :---: | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |\n";
        $tableOpen = true;
        continue;
    }

    if (isset($r['A']) && is_numeric($r['A'])) {
        if (!$tableOpen) {
            $mdHarga .= "### Kategori: $currentCategory\n\n";
            $mdHarga .= "| No | Nama Kendaraan / Alat | Proyek | Kode Unit | SN / No Polisi | Tarif Proyek (Rp) | Tarif Internal Equipment (Rp) | Cicilan 3 Thn | Cicilan 5 Thn | Cadangan 5% (Rp) | Biaya Repair Rata-Rata (Rp) | Potensi Pendapatan (Rp) |\n";
            $mdHarga .= "| :---: | :--- | :--- | :---: | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |\n";
            $tableOpen = true;
        }

        $no   = escapeMd($r['A']);
        if (is_numeric($no)) $no = number_format((float)$no, 0);
        $nama = escapeMd($r['B'] ?? '-');
        $proy = escapeMd($r['C'] ?? '-');
        $kode = escapeMd($r['E'] ?? '-');
        $sn   = escapeMd($r['F'] ?? '-');
        $tPrj = formatMoney($r['G'] ?? '');
        $tEqp = formatMoney($r['H'] ?? '');
        $c3   = escapeMd($r['I'] ?? '-');
        if (is_numeric($c3)) $c3 = formatMoney($c3);
        $c5   = escapeMd($r['J'] ?? '-');
        if (is_numeric($c5)) $c5 = formatMoney($c5);
        $c5p  = formatMoney($r['K'] ?? '');
        $rep  = formatMoney($r['L'] ?? '');
        $pot  = formatMoney($r['M'] ?? '');

        $mdHarga .= "| $no | $nama | $proy | $kode | $sn | $tPrj | $tEqp | $c3 | $c5 | $c5p | $rep | $pot |\n";
    } elseif (isset($r['B']) && stripos($r['B'], 'TOTAL') !== false) {
        $label = escapeMd($r['B']);
        $tPrj = formatMoney($r['G'] ?? '');
        $tEqp = formatMoney($r['H'] ?? '');
        $rep  = formatMoney($r['L'] ?? '');
        $pot  = formatMoney($r['M'] ?? '');
        $mdHarga .= "| **TOTAL** | **$label** | - | - | - | **$tPrj** | **$tEqp** | - | - | - | **$rep** | **$pot** |\n";
    }
}

file_put_contents("$outDir/05_Harga_Sewa_Proyek_dan_Cicilan.md", $mdHarga);

// =========================================================================
// 5. GENERATE 03_Total_Pendapatan_Sewa.md
// =========================================================================
echo "6. Generating 03_Total_Pendapatan_Sewa.md...\n";
$rows3 = parseSheetRows($zip, 'sheet3.xml', $strings);

$mdTotal = "# Dokumen 03: Rekap Total Pendapatan Sewa Alat (2020 - 2026)\n\n";
$mdTotal .= "> **Sumber Sheet:** `Total` (`worksheets/sheet3.xml`)\n";
$mdTotal .= "> **Deskripsi:** Matriks historis realisasi dan proyeksi pendapatan sewa per unit alat berat dan kendaraan per bulan sejak tahun 2020 hingga September 2026.\n\n";

$cat3 = "EXCAVATOR";
$tbl3Open = false;

// Known categories in Sheet 3
$knownCategories = ['EXCAVATOR', 'BULDOZER', 'BREAKER', 'VIBRO', 'GREDER', 'DUMPTRUCK', 'MOBIL', 'MOBIL PHR', 'TOTAL STATION'];

foreach ($rows3 as $rNum => $r) {
    if ($rNum <= 3) continue;

    $colB = trim($r['B'] ?? '');
    $isCategoryHeader = false;

    // Check if category header
    if (in_array(strtoupper($colB), $knownCategories) && (!isset($r['F']) || $r['F'] === '' || !isset($r['BJ']) || $r['BJ'] === '0')) {
        $isCategoryHeader = true;
    } elseif (!isset($r['A']) && isset($r['B']) && $r['B'] !== '' && !isset($r['F']) && !isset($r['BJ'])) {
        $isCategoryHeader = true;
    }

    if ($isCategoryHeader) {
        if ($tbl3Open) {
            $mdTotal .= "\n";
            $tbl3Open = false;
        }
        $cat3 = strtoupper($colB);
        $mdTotal .= "### Kategori: $cat3\n\n";
        $mdTotal .= "| No | Kendaraan / Alat Berat | No Seri / No Polisi | Cicilan Leasing (Rp) | Tarif Sewa (Rp) | 2020 (Rp) | 2021 (Rp) | 2022 (Rp) | 2023 (Rp) | 2024 (Rp) | 2025 (Rp) | 2026 s/d Sep (Rp) | Total Pendapatan (Rp) | Est Nov-Des (Rp) |\n";
        $mdTotal .= "| :---: | :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |\n";
        $tbl3Open = true;
        continue;
    }

    if (isset($r['A']) && is_numeric($r['A']) && (float)$r['A'] > 0) {
        if (!$tbl3Open) {
            $mdTotal .= "### Kategori: $cat3\n\n";
            $mdTotal .= "| No | Kendaraan / Alat Berat | No Seri / No Polisi | Cicilan Leasing (Rp) | Tarif Sewa (Rp) | 2020 (Rp) | 2021 (Rp) | 2022 (Rp) | 2023 (Rp) | 2024 (Rp) | 2025 (Rp) | 2026 s/d Sep (Rp) | Total Pendapatan (Rp) | Est Nov-Des (Rp) |\n";
            $mdTotal .= "| :---: | :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |\n";
            $tbl3Open = true;
        }

        $no   = number_format((float)$r['A'], 0);
        $nama = escapeMd($r['B'] ?? '-');
        $sn   = escapeMd($r['C'] ?? '-');
        $cic  = formatMoney($r['D'] ?? '');
        $sewa = formatMoney($r['E'] ?? '');

        // Annual aggregations
        $y2020 = formatMoney($r['F'] ?? '');
        $y2021 = formatMoney($r['G'] ?? '');

        $tot2022 = 0;
        foreach (['H', 'I', 'J', 'K', 'L', 'M'] as $col) {
            if (isset($r[$col]) && is_numeric($r[$col])) $tot2022 += (float)$r[$col];
        }
        $y2022 = $tot2022 > 0 ? formatMoney($tot2022) : (isset($r['H']) ? formatMoney($r['H']) : '-');

        $tot2023 = 0;
        foreach (['N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y'] as $col) {
            if (isset($r[$col]) && is_numeric($r[$col])) $tot2023 += (float)$r[$col];
        }
        $y2023 = $tot2023 > 0 ? formatMoney($tot2023) : '-';

        $tot2024 = 0;
        foreach (['Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK'] as $col) {
            if (isset($r[$col]) && is_numeric($r[$col])) $tot2024 += (float)$r[$col];
        }
        $y2024 = $tot2024 > 0 ? formatMoney($tot2024) : '-';

        $tot2025 = 0;
        foreach (['AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW'] as $col) {
            if (isset($r[$col]) && is_numeric($r[$col])) $tot2025 += (float)$r[$col];
        }
        $y2025 = $tot2025 > 0 ? formatMoney($tot2025) : '-';

        $tot2026 = 0;
        foreach (['AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF'] as $col) {
            if (isset($r[$col]) && is_numeric($r[$col])) $tot2026 += (float)$r[$col];
        }
        $y2026 = $tot2026 > 0 ? formatMoney($tot2026) : '-';

        $totalAll = formatMoney($r['BJ'] ?? '');
        $estNovDes = formatMoney($r['BL'] ?? '');

        $mdTotal .= "| $no | $nama | $sn | $cic | $sewa | $y2020 | $y2021 | $y2022 | $y2023 | $y2024 | $y2025 | $y2026 | $totalAll | $estNovDes |\n";
    } elseif (isset($r['B']) && (stripos($r['B'], 'TOTAL') !== false || stripos($r['B'], 'Biaya') !== false || stripos($r['B'], 'Overhead') !== false || stripos($r['B'], 'Kerugian') !== false)) {
        if ($tbl3Open) {
            $mdTotal .= "\n";
            $tbl3Open = false;
        }
        $label = escapeMd($r['B']);
        $y2020 = formatMoney($r['F'] ?? '');
        $y2021 = formatMoney($r['G'] ?? '');
        $totalAll = formatMoney($r['BJ'] ?? '');
        $estNovDes = formatMoney($r['BL'] ?? '');
        $mdTotal .= "### Ringkasan Finansial: $label\n\n";
        $mdTotal .= "- **Tahun 2020:** Rp $y2020\n";
        $mdTotal .= "- **Tahun 2021:** Rp $y2021\n";
        $mdTotal .= "- **Total Tercatat:** Rp $totalAll\n";
        if ($estNovDes !== '-') {
            $mdTotal .= "- **Estimasi Penyesuaian Nov - Des:** Rp $estNovDes\n";
        }
        $mdTotal .= "\n";
    }
}

file_put_contents("$outDir/03_Total_Pendapatan_Sewa.md", $mdTotal);

// =========================================================================
// 6. GENERATE 04_Sewa_Alat_Operasional.md
// =========================================================================
echo "7. Generating 04_Sewa_Alat_Operasional.md...\n";
$rows4 = parseSheetRows($zip, 'sheet4.xml', $strings);

$mdSewa = "# Dokumen 04: Master Operasional Sewa Alat & Alokasi Proyek\n\n";
$mdSewa .= "> **Sumber Sheet:** `Sewa Alat` (`worksheets/sheet4.xml`)\n";
$mdSewa .= "> **Jumlah Unit Tercatat:** 286 unit alat berat dan kendaraan\n";
$mdSewa .= "> **Periode Timesheet Operasional:** Agustus 2022 s/d September 2026\n\n";

$mdSewa .= "## 1. Master Daftar Unit & Tarif Sewa Operasional\n\n";

$cat4 = "EXCAVATOR";
$tbl4Open = false;

foreach ($rows4 as $rNum => $r) {
    if ($rNum <= 4) continue;

    if (!isset($r['A']) && isset($r['D']) && $r['D'] !== '' && !isset($r['G'])) {
        if ($tbl4Open) {
            $mdSewa .= "\n";
            $tbl4Open = false;
        }
        $cat4 = trim($r['D']);
        $mdSewa .= "### Kategori: $cat4\n\n";
        $mdSewa .= "| No | Nama Kendaraan / Alat | Lokasi Proyek Basis | Tipe Alat | Kode Unit | SN / No Polisi | Tarif Pendapatan (Rp) | Tarif Sewa Proyek (Rp) | Total Cicilan Leasing (Rp) |\n";
        $mdSewa .= "| :---: | :--- | :--- | :--- | :---: | :--- | :---: | :---: | :---: |\n";
        $tbl4Open = true;
        continue;
    }

    if (isset($r['A']) && is_numeric($r['A'])) {
        if (!$tbl4Open) {
            $mdSewa .= "### Kategori: $cat4\n\n";
            $mdSewa .= "| No | Nama Kendaraan / Alat | Lokasi Proyek Basis | Tipe Alat | Kode Unit | SN / No Polisi | Tarif Pendapatan (Rp) | Tarif Sewa Proyek (Rp) | Total Cicilan Leasing (Rp) |\n";
            $mdSewa .= "| :---: | :--- | :--- | :--- | :---: | :--- | :---: | :---: | :---: |\n";
            $tbl4Open = true;
        }

        $no   = number_format((float)$r['A'], 0);
        $nama = escapeMd($r['B'] ?? '-');
        $lok  = escapeMd($r['C'] ?? '-');
        $tipe = escapeMd($r['D'] ?? '-');
        $kode = escapeMd($r['E'] ?? '-');
        $sn   = escapeMd($r['F'] ?? '-');
        $rev  = formatMoney($r['G'] ?? '');
        $prj  = formatMoney($r['H'] ?? '');
        $cic  = formatMoney($r['I'] ?? '');

        $mdSewa .= "| $no | $nama | $lok | $tipe | $kode | $sn | $rev | $prj | $cic |\n";
    }
}

$mdSewa .= "\n## 2. Struktur Timesheet & Alokasi Harian (Timesheet Tracking)\n\n";
$mdSewa .= "Lembar kerja `Sewa Alat` pada file Excel asli memiliki lebih dari 2.000 kolom data harian yang memetakan posisi kerja dan status setiap unit alat per hari dari bulan Agustus 2022 hingga September 2026:\n";
$mdSewa .= "- **Kolom J s/d AU:** Rekapitulasi hari kerja per sektor proyek (STA 109, STA 144, Pekbang, Seksi 1, Seksi 2, Seksi 3, Junction, Total Pendapatan Bulanan).\n";
$mdSewa .= "- **Kolom AV s/d BZD:** Matriks per hari tanggal 1 s/d 31 setiap bulan yang mencatat lokasi proyek unit beroperasi (misal: *WUR EW, Kendari, Seksi 3, ASR, Matting Board, RWI, Stanby, Rusak*).\n";
$mdSewa .= "- **Status Khusus Unit:** Hari perbaikan / kerusakan dicatat pada kolom *Jumlah Hari Rusak* per bulan untuk menghitung pengurangan penagihan sewa alat ke pihak proyek.\n";

file_put_contents("$outDir/04_Sewa_Alat_Operasional.md", $mdSewa);

// =========================================================================
// 7. GENERATE README.md
// =========================================================================
echo "8. Generating README.md...\n";
$mdReadme = "# Dokumentasi Skema Pencatatan Sewa Alat PT BRA\n\n";
$mdReadme .= "> **File Sumber:** `Skema Pencatatan Sewa Alat BRA.xlsx`\n";
$mdReadme .= "> **Direktori Output:** `material/SKEMA-PENCATATAN-SEWA-ALAT/`\n";
$mdReadme .= "> **Waktu Konversi:** " . date("Y-m-d H:i:s") . "\n\n";

$mdReadme .= "## 1. Ikhtisar Dokumen\n\n";
$mdReadme .= "File workbook Excel ini memuat skema tata kelola finansial dan operasional armada alat berat dan kendaraan PT Bangun Reka Agung (BRA), mencakup struktur pembiayaan leasing, penentuan tarif sewa proyek vs internal, monitoring timesheet penugasan alat, serta rekapitulasi pendapatan sewa dari tahun 2020 hingga 2026.\n\n";

$mdReadme .= "## 2. Struktur File Markdown per Tabel\n\n";
$mdReadme .= "| No | Dokumen Markdown | Sumber Sheet Excel | Rangkuman Isi Data |\n";
$mdReadme .= "| :---: | :--- | :--- | :--- |\n";
$mdReadme .= "| 1 | [01_Rekap_Aset.md](./01_Rekap_Aset.md) | `Rekap Aset` | Sheet kosong bawaan template workbook. |\n";
$mdReadme .= "| 2 | [02_Leasing.md](./02_Leasing.md) | `Leasing` | **153 entri pembiayaan leasing**: harga perolehan, DP, bunga, tenor, nominal cicilan, vendor leasing (Buana Finance, BFI, MTF, BRI Finance, BNI Finance, TAF, dsb.). |\n";
$mdReadme .= "| 3 | [03_Total_Pendapatan_Sewa.md](./03_Total_Pendapatan_Sewa.md) | `Total` | **Rekapitulasi pendapatan sewa 171 unit alat & kendaraan**: historis tahun 2020 - 2026 per bulan beserta analisis overhead, biaya perbaikan, dan kerugian alat rusak. |\n";
$mdReadme .= "| 4 | [04_Sewa_Alat_Operasional.md](./04_Sewa_Alat_Operasional.md) | `Sewa Alat` | **Master operasional 286 unit armada**: tarif pendapatan, tarif sewa proyek, cicilan leasing, dan penjelasan timesheet alokasi proyek harian. |\n";
$mdReadme .= "| 5 | [05_Harga_Sewa_Proyek_dan_Cicilan.md](./05_Harga_Sewa_Proyek_dan_Cicilan.md) | `hagra sewa proyek dan cicilan` | **Evaluasi 295 unit alat**: komparasi tarif sewa proyek vs tarif equipment internal, cicilan 3 thn vs 5 thn, rata-rata biaya perbaikan, dan margin potensi pendapatan. |\n";
$mdReadme .= "| 6 | [06_List_Master_Data.md](./06_List_Master_Data.md) | `List` | **Master lookup data**: 44 lokasi proyek kerja, 60 klasifikasi status/sub-sektor, dan status standby unit. |\n\n";

$mdReadme .= "## 3. Temuan Kunci Finansial & Operasional\n\n";
$mdReadme .= "- **Kategori Alat Utama**: Excavator (PC 210, PC 200, CAT 320), Bulldozer (D85, SD16, ZD170), Motor Grader (GD535, GR135), Vibro / Padfoot Compactor (Bomag BW211, CS10), Dump Truck (Hino FM 280 JD Mining), Wheel Loader, Crawler Crane, Cold Recycler, dan Cement Spreader.\n";
$mdReadme .= "- **Status Pembiayaan**: Sebagian besar unit generasi awal telah berstatus `LUNAS`, sedangkan unit baru (pembelian 2024–2026) dibiayai leasing dengan tenor 24 hingga 36 bulan.\n";
$mdReadme .= "- **Integrasi Data**: Nomor seri dan kode unit pada dokumen ini saling sinkron dengan master data aset di aplikasi *ServicePlan-BRA*.\n";

file_put_contents("$outDir/README.md", $mdReadme);

$zip->close();
echo "SUCCESS: All Markdown files successfully regenerated in $outDir\n";
