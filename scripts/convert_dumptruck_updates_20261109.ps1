param(
    [string]$SourceDir = '',
    [string]$OutputDir = ''
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = (Get-Item $PSScriptRoot).Parent.FullName
if ([string]::IsNullOrWhiteSpace($SourceDir)) {
    $SourceDir = Join-Path $repositoryRoot 'raw-material\ASSET\UPDATE-DUMPTRUCK-OCT-NOV-2026'
}
if ([string]::IsNullOrWhiteSpace($OutputDir)) {
    $OutputDir = Join-Path $repositoryRoot 'material\ASSET\UPDATE-DUMPTRUCK-OCT-NOV-2026'
}

$pdfToTextCandidates = @(
    'C:\Program Files\Git\mingw64\bin\pdftotext.exe',
    'C:\laragon\bin\git\mingw64\bin\pdftotext.exe'
)
$pdfToText = $pdfToTextCandidates | Where-Object { Test-Path -LiteralPath $_ -PathType Leaf } | Select-Object -First 1
if (-not $pdfToText) {
    throw 'pdftotext.exe tidak ditemukan. Instal Xpdf/Poppler atau Git for Windows.'
}
if (-not (Test-Path -LiteralPath $SourceDir -PathType Container)) {
    throw "Folder sumber tidak ditemukan: $SourceDir"
}
New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
$dataDir = Join-Path $repositoryRoot 'scripts\data'
New-Item -ItemType Directory -Path $dataDir -Force | Out-Null

function Convert-ToSqlLiteral([AllowNull()][string]$Value) {
    if ($null -eq $Value) { return 'NULL' }
    return "'" + $Value.Replace("'", "''") + "'"
}

function Get-NormalizedStatus([string]$Condition) {
    $upper = $Condition.ToUpperInvariant()
    if ($upper -match 'BROKEN|BATERAI LOW|PENGELASAN') { return 'BREAKDOWN' }
    if ($upper -match 'CLOSE FINDING|JPK') { return 'INSPEKSI' }
    if ($upper -match 'KP DRIVER|KADALUWARSA') { return 'STANDBY' }
    if ($upper -match 'RTW,?\s*MUATAN') { return 'OPERATING' }
    if ($upper -match 'RTW') { return 'READY' }
    return 'INSPEKSI'
}

function Get-ReportDate([string]$Name) {
    if ($Name -notmatch '(\d{2})\.(\d{2})\.(\d{4})') {
        throw "Tanggal tidak ditemukan pada nama file: $Name"
    }
    return [datetime]::ParseExact("$($matches[1]).$($matches[2]).$($matches[3])", 'dd.MM.yyyy', [Globalization.CultureInfo]::InvariantCulture)
}

$allRows = [System.Collections.Generic.List[object]]::new()
$reports = [System.Collections.Generic.List[object]]::new()
$pdfFiles = Get-ChildItem -LiteralPath $SourceDir -Filter 'UPDATE DUMPTRUCK *.pdf' -File |
    Sort-Object @{ Expression = { Get-ReportDate $_.Name } }

foreach ($pdf in $pdfFiles) {
    $reportDate = Get-ReportDate $pdf.Name
    $rawLines = @(& $pdfToText -table -enc UTF-8 $pdf.FullName -)
    if ($LASTEXITCODE -ne 0) { throw "Ekstraksi PDF gagal: $($pdf.FullName)" }

    $section = ''
    $rows = [System.Collections.Generic.List[object]]::new()
    foreach ($line in $rawLines) {
        if ($line -match '^UNIT\s+DT DI (KSA|BAL)') {
            $section = $matches[1]
            continue
        }
        if ($line -notmatch '^\s*(\d+)\s+DT\s+(\d{4})\s+([A-Z]{1,2}\s+\d{4}\s+[A-Z]{1,3})\s+(.*)$') { continue }

        $rowNumber = [int]$matches[1]
        $unitCode = "DT $($matches[2])"
        $plate = ($matches[3] -replace '\s+', ' ').Trim().ToUpperInvariant()
        $tail = ($matches[4] -replace '^\s*V\s*', '' -replace '\s+', ' ').Trim()
        $status = Get-NormalizedStatus $tail
        $vPosition = $line.IndexOf('V', 45)
        $location = if ($reportDate -ge [datetime]'2026-11-02') {
            if ($vPosition -ge 78 -and $vPosition -le 90) { 'YARD KM 12' } else { 'B.PIT HARAPAN BARU' }
        } elseif ($reportDate -ge [datetime]'2026-10-28') {
            if ($vPosition -ge 86 -and $vPosition -le 96) { 'YARD KM 12' } else { 'B.PIT HARAPAN BARU' }
        } else {
            if ($vPosition -ge 77 -and $vPosition -le 86) { 'YARD KM 12' } else { 'B.PIT HARAPAN BARU' }
        }
        $row = [pscustomobject]@{
            report_date = $reportDate.ToString('yyyy-MM-dd')
            source_file = $pdf.Name
            source_section = $section
            source_row_no = $rowNumber
            source_unit_code = $unitCode
            license_plate = $plate
            normalized_plate = ($plate -replace '\s+', '')
            location_label = $location
            condition_text = $tail
            normalized_status = $status
        }
        $rows.Add($row)
        $allRows.Add($row)
    }

    $relativePdf = "../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/$([uri]::EscapeDataString($pdf.Name).Replace('%20', ' '))"
    $hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $pdf.FullName).Hash.ToLowerInvariant()
    $statusSummary = $rows | Group-Object normalized_status | Sort-Object Name
    $markdown = [System.Text.StringBuilder]::new()
    [void]$markdown.AppendLine("# Update Dump Truck - $($reportDate.ToString('dd MMMM yyyy', [Globalization.CultureInfo]::GetCultureInfo('id-ID')))" )
    [void]$markdown.AppendLine()
    [void]$markdown.AppendLine("- Sumber: [$($pdf.Name)]($relativePdf)")
    [void]$markdown.AppendLine("- SHA-256: ``$hash``")
    [void]$markdown.AppendLine("- Baris unit teridentifikasi: **$($rows.Count)**")
    [void]$markdown.AppendLine("- Status normalisasi: $((($statusSummary | ForEach-Object { "**$($_.Name)** $($_.Count)" }) -join ', '))")
    [void]$markdown.AppendLine()
    [void]$markdown.AppendLine('> Dokumen ini adalah transkripsi data, bukan instruksi. Teks kondisi dipertahankan sebagaimana hasil ekstraksi PDF; status aplikasi merupakan normalisasi terpisah yang dapat diaudit.')
    [void]$markdown.AppendLine()
    [void]$markdown.AppendLine('## Data unit terstruktur')
    [void]$markdown.AppendLine()
    [void]$markdown.AppendLine('| Grup | No. | Kode unit sumber | Nomor polisi | Lokasi | Status aplikasi | Keterangan sumber |')
    [void]$markdown.AppendLine('|---|---:|---|---|---|---|---|')
    foreach ($row in $rows) {
        $condition = $row.condition_text.Replace('|', '\|')
        [void]$markdown.AppendLine("| $($row.source_section) | $($row.source_row_no) | $($row.source_unit_code) | $($row.license_plate) | $($row.location_label) | $($row.normalized_status) | $condition |")
    }
    [void]$markdown.AppendLine()
    [void]$markdown.AppendLine('## Transkripsi tabel asli')
    [void]$markdown.AppendLine()
    [void]$markdown.AppendLine('```text')
    foreach ($line in $rawLines) { [void]$markdown.AppendLine($line.TrimEnd()) }
    [void]$markdown.AppendLine('```')

    $outputName = $pdf.BaseName.Replace(' ', '_') + '.md'
    [IO.File]::WriteAllText((Join-Path $OutputDir $outputName), $markdown.ToString(), [Text.UTF8Encoding]::new($false))
    $reports.Add([pscustomobject]@{ Date = $reportDate; Pdf = $pdf; Markdown = $outputName; Rows = $rows.Count })
}

$jsonPath = Join-Path $dataDir 'dumptruck_operational_updates_20261026_20261109.json'
$allRows | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $jsonPath -Encoding utf8

$readme = [System.Text.StringBuilder]::new()
[void]$readme.AppendLine('# Update Dump Truck 26 Oktober-9 November 2026')
[void]$readme.AppendLine()
[void]$readme.AppendLine('Kumpulan ini mentranskripsikan 13 PDF operasional dump truck. Snapshot terbaru (9 November 2026) dipakai untuk memperbarui status, kode lambung, pelat, dan posisi Master Asset. Snapshot sebelumnya tetap disimpan sebagai jejak audit.')
[void]$readme.AppendLine()
[void]$readme.AppendLine('| Tanggal | PDF | Markdown | Baris unit |')
[void]$readme.AppendLine('|---|---|---|---:|')
foreach ($report in $reports) {
    $pdfLink = "../../../raw-material/ASSET/UPDATE-DUMPTRUCK-OCT-NOV-2026/$($report.Pdf.Name)"
    [void]$readme.AppendLine("| $($report.Date.ToString('dd-MM-yyyy')) | [$($report.Pdf.Name)]($pdfLink) | [$($report.Markdown)]($($report.Markdown)) | $($report.Rows) |")
}
[void]$readme.AppendLine()
[void]$readme.AppendLine('## Aturan integrasi')
[void]$readme.AppendLine()
[void]$readme.AppendLine('- Nomor polisi dinormalisasi tanpa spasi hanya untuk pencocokan; tampilan tetap menggunakan format berjarak.')
[void]$readme.AppendLine('- `DT 1429` dan `DT 1440` memakai pelat `BM 9819 QO` pada semua snapshot. Master aset dan dokumen aset sebelumnya memastikan hanya satu aset fisik (`DT-04029`), sehingga konflik disimpan di snapshot dan tidak dibuat menjadi dua aset.')
[void]$readme.AppendLine('- `DT-00152` adalah duplikat lama `DT-00056` untuk pelat `B 9892 PYW`. Referensi historis dipindahkan ke aset kanonis, lalu record duplikat dinonaktifkan dan diarsipkan.')
[void]$readme.AppendLine('- Data aset di luar 75 nomor polisi unik pada snapshot terbaru tidak disentuh oleh migrasi.')
[IO.File]::WriteAllText((Join-Path $OutputDir 'README.md'), $readme.ToString(), [Text.UTF8Encoding]::new($false))

# Generate an idempotent SQL migration from the latest parsed snapshot.
$latestDate = ($reports | Sort-Object Date -Descending | Select-Object -First 1).Date.ToString('yyyy-MM-dd')
$latestRows = @($allRows | Where-Object report_date -eq $latestDate)
$canonicalConflictCode = @{ 'BM9819QO' = 'DT 1429' }
$latestPhysicalRows = @($latestRows |
    Group-Object normalized_plate |
    ForEach-Object {
        $preferredCode = $canonicalConflictCode[$_.Name]
        if ($preferredCode) { $_.Group | Where-Object source_unit_code -eq $preferredCode | Select-Object -First 1 }
        else { $_.Group | Select-Object -First 1 }
    } |
    Sort-Object source_unit_code)

$migration = [System.Text.StringBuilder]::new()
[void]$migration.AppendLine('-- Generated by scripts/convert_dumptruck_updates_20261109.ps1')
[void]$migration.AppendLine('-- Scoped reconciliation: UPDATE DUMPTRUCK 26-10-2026 through 09-11-2026.')
[void]$migration.AppendLine('SET NAMES utf8mb4;')
[void]$migration.AppendLine('START TRANSACTION;')
[void]$migration.AppendLine()
[void]$migration.AppendLine('CREATE TABLE IF NOT EXISTS asset_operational_snapshots (')
[void]$migration.AppendLine('  snapshot_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,')
[void]$migration.AppendLine('  report_date DATE NOT NULL,')
[void]$migration.AppendLine('  source_file VARCHAR(255) NOT NULL,')
[void]$migration.AppendLine('  source_section VARCHAR(20) NOT NULL,')
[void]$migration.AppendLine('  source_row_no INT NOT NULL,')
[void]$migration.AppendLine('  source_unit_code VARCHAR(50) NOT NULL,')
[void]$migration.AppendLine('  license_plate VARCHAR(50) NOT NULL,')
[void]$migration.AppendLine('  normalized_plate VARCHAR(50) NOT NULL,')
[void]$migration.AppendLine('  location_label VARCHAR(150) DEFAULT NULL,')
[void]$migration.AppendLine('  condition_text TEXT,')
[void]$migration.AppendLine("  normalized_status ENUM('READY','OPERATING','STANDBY','INSPEKSI','BREAKDOWN') NOT NULL,")
[void]$migration.AppendLine('  resolved_asset_id VARCHAR(100) DEFAULT NULL,')
[void]$migration.AppendLine("  resolution_state ENUM('MATCHED','CREATED','SOURCE_CONFLICT','UNRESOLVED') NOT NULL DEFAULT 'UNRESOLVED',")
[void]$migration.AppendLine('  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,')
[void]$migration.AppendLine('  PRIMARY KEY (snapshot_id),')
[void]$migration.AppendLine('  UNIQUE KEY uq_asset_operational_snapshot (report_date, source_section, source_row_no, source_unit_code),')
[void]$migration.AppendLine('  KEY idx_asset_operational_snapshot_asset_date (resolved_asset_id, report_date),')
[void]$migration.AppendLine('  KEY idx_asset_operational_snapshot_plate_date (normalized_plate, report_date)')
[void]$migration.AppendLine(') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;')
[void]$migration.AppendLine()

# Fix the two known pre-existing duplicate/corrupt identities before matching snapshots.
[void]$migration.AppendLine("UPDATE assets SET license_plate='B 9078 ZYT', year_manufacture=2023, category='Dump Truck', type='Dump Truck', make_model='Dump Truck HINO RANGER-FM 280 JD', capacity=COALESCE(NULLIF(capacity,''),'24 M3') WHERE asset_id='DT-00037';")
[void]$migration.AppendLine("UPDATE asset_cost_transactions SET asset_id='DT-00056' WHERE asset_id='DT-00152';")
[void]$migration.AppendLine("UPDATE contract_assignments SET asset_id='DT-00056' WHERE asset_id='DT-00152';")
[void]$migration.AppendLine("UPDATE fuel_logs SET asset_id='DT-00056' WHERE asset_id='DT-00152';")
[void]$migration.AppendLine("UPDATE tire_inspections SET asset_id='DT-00056' WHERE asset_id='DT-00152';")
[void]$migration.AppendLine("UPDATE assets SET previous_license_plate=COALESCE(previous_license_plate,license_plate), license_plate=NULL, serial_number=NULL, status='INACTIVE', is_active=0, raw_location_notes=CONCAT_WS(' | ',NULLIF(raw_location_notes,''),'DUPLIKAT DT-00056 direkonsiliasi dari UPDATE DUMPTRUCK 09-11-2026') WHERE asset_id='DT-00152';")
[void]$migration.AppendLine("INSERT INTO archived_items (item_type,item_id,archived_by_name,reason) SELECT 'asset','DT-00152','MIGRATION_DUMPTRUCK_20261109','Duplikat pelat/serial DT-00056; seluruh referensi historis dipindahkan ke aset kanonis' FROM DUAL WHERE EXISTS (SELECT 1 FROM assets WHERE asset_id='DT-00152') ON DUPLICATE KEY UPDATE archived_by_name=VALUES(archived_by_name), reason=VALUES(reason);")
[void]$migration.AppendLine()

$newAssets = @(
    @{ Id='DT-00001'; Code='DT 1101'; Plate='B 9107 ZYT'; Model='Dump Truck Hino FM260JD / Mercedes Axor'; Year=2023; Capacity='24 M3'; Hull='DT 1101'; Status='BREAKDOWN'; Warranty='Warranty'; Chassis='MJEFM8JN2RJX25335' },
    @{ Id='DT-00007'; Code='DT 1107'; Plate='B 9114 ZYT'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2024; Capacity='24 M3'; Hull='DT 1107'; Status='OPERATING' },
    @{ Id='DT-00018'; Code='DT 1118'; Plate='B 9127 ZYT'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2024; Capacity='24 M3'; Hull='DT 1118'; Status='OPERATING' },
    @{ Id='DT-00020'; Code='DT 1120'; Plate='B 9129 ZYT'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2024; Capacity='24 M3'; Hull='DT 1120'; Status='OPERATING' },
    @{ Id='DT-YX-00010'; Code='DT 1147'; Plate='B 9116 PYX'; Model='Dump Truck HINO LOHAN 500 FM 260 JD'; Year=2017; Capacity='24 M3'; Hull='DT 1147'; Status='INSPEKSI' },
    @{ Id='DT-00179'; Code='DT 1460'; Plate='BM 9821 QO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='24 M3'; Hull='DT 1460'; Status='OPERATING' },
    @{ Id='DT-YX-00009'; Code='DT 1148'; Plate='B 9115 PYX'; Model='Dump Truck HINO LOHAN 500 FM 260 JD'; Year=2017; Capacity='24 M3'; Hull='DT 1148'; Status='OPERATING' },
    @{ Id='DT-YX-00011'; Code='DT 1149'; Plate='B 9121 PYX'; Model='Dump Truck HINO LOHAN 500 FM 260 JD'; Year=2017; Capacity='24 M3'; Hull='DT 1149'; Status='OPERATING' },
    @{ Id='DT-YX-00012'; Code='DT 1150'; Plate='B 9122 PYX'; Model='Dump Truck HINO LOHAN 500 FM 260 JD'; Year=2017; Capacity='24 M3'; Hull='DT 1150'; Status='OPERATING' },
    @{ Id='DT-00002'; Code='DT 1102'; Plate='B 9110 ZYT'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2024; Capacity='24 M3'; Hull='DT 1102'; Status='OPERATING'; Warranty='Warranty'; Chassis='MJEFM8JN2RJX25619' },
    @{ Id='BRA-DT-1454'; Code='DT 1454'; Plate='BM 9403 JO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='24 M3'; Hull='DT 124'; Status='OPERATING' },
    @{ Id='BRA-DT-1465'; Code='DT 1465'; Plate='BM 9293 JO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='28 M3'; Hull='REKON 05'; Status='OPERATING' },
    @{ Id='BRA-DT-1466'; Code='DT 1466'; Plate='BM 9295 JO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='32 M3'; Hull='REKON 09'; Status='OPERATING' },
    @{ Id='BRA-DT-1467'; Code='DT 1467'; Plate='BM 9401 JO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='27 M3'; Hull='REKON 04'; Status='OPERATING' },
    @{ Id='BRA-DT-1468'; Code='DT 1468'; Plate='BM 9471 QO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='31 M3'; Hull='REKON 08'; Status='OPERATING' },
    @{ Id='BRA-DT-1469'; Code='DT 1469'; Plate='BM 9505 QO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='26 M3'; Hull='REKON 03'; Status='BREAKDOWN' },
    @{ Id='BRA-DT-1472'; Code='DT 1472'; Plate='BM 9681 JO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='29 M3'; Hull='REKON 06'; Status='OPERATING' },
    @{ Id='BRA-DT-1473'; Code='DT 1473'; Plate='BM 9683 JO'; Model='Dump Truck HINO RANGER-FM 280 JD'; Year=2022; Capacity='24 M3'; Hull='REKON 01'; Status='OPERATING' }
)
foreach ($asset in $newAssets) {
    $warranty = if ($asset.Warranty) { Convert-ToSqlLiteral $asset.Warranty } else { 'NULL' }
    $chassis = if ($asset.Chassis) { Convert-ToSqlLiteral $asset.Chassis } else { 'NULL' }
    $values = @(
        (Convert-ToSqlLiteral $asset.Id), (Convert-ToSqlLiteral $asset.Id), (Convert-ToSqlLiteral $asset.Plate),
        (Convert-ToSqlLiteral $asset.Code), (Convert-ToSqlLiteral $asset.Code), "'Dump Truck'", "'Dump Truck'",
        (Convert-ToSqlLiteral $asset.Model), $asset.Year, (Convert-ToSqlLiteral $asset.Capacity), $warranty, $chassis,
        "'WUR EW'", "'B.PIT HARAPAN BARU'", (Convert-ToSqlLiteral $asset.Status), '3', "'WUR EW - B.PIT HARAPAN BARU'", '1'
    ) -join ','
    [void]$migration.AppendLine("INSERT INTO assets (asset_id,asset_code,license_plate,hull_number,alias_name,type,category,make_model,year_manufacture,capacity,warranty,chassis_number,project_name,operational_position,status,current_location_id,raw_location_notes,is_active) VALUES ($values) ON DUPLICATE KEY UPDATE license_plate=VALUES(license_plate), category=VALUES(category), type=VALUES(type), make_model=COALESCE(NULLIF(assets.make_model,''),VALUES(make_model)), year_manufacture=COALESCE(assets.year_manufacture,VALUES(year_manufacture)), capacity=COALESCE(NULLIF(assets.capacity,''),VALUES(capacity)), warranty=COALESCE(NULLIF(assets.warranty,''),VALUES(warranty)), chassis_number=COALESCE(NULLIF(assets.chassis_number,''),VALUES(chassis_number)), is_active=1;")
}
[void]$migration.AppendLine()

[void]$migration.AppendLine('DROP TEMPORARY TABLE IF EXISTS tmp_dumptruck_latest;')
[void]$migration.AppendLine("CREATE TEMPORARY TABLE tmp_dumptruck_latest (source_unit_code VARCHAR(50) NOT NULL, normalized_plate VARCHAR(50) NOT NULL PRIMARY KEY, license_plate VARCHAR(50) NOT NULL, location_label VARCHAR(150) NOT NULL, normalized_status VARCHAR(20) NOT NULL, condition_text TEXT) ENGINE=InnoDB;")
[void]$migration.AppendLine('INSERT INTO tmp_dumptruck_latest (source_unit_code,normalized_plate,license_plate,location_label,normalized_status,condition_text) VALUES')
$latestSqlRows = foreach ($row in $latestPhysicalRows) {
    '(' + (@(
        (Convert-ToSqlLiteral $row.source_unit_code), (Convert-ToSqlLiteral $row.normalized_plate),
        (Convert-ToSqlLiteral $row.license_plate), (Convert-ToSqlLiteral $row.location_label),
        (Convert-ToSqlLiteral $row.normalized_status), (Convert-ToSqlLiteral $row.condition_text)
    ) -join ',') + ')'
}
[void]$migration.AppendLine(($latestSqlRows -join ",`n") + ';')
[void]$migration.AppendLine()
[void]$migration.AppendLine("INSERT INTO asset_hull_history (asset_id,old_hull_number,new_hull_number,change_reason,effective_date,changed_by_name) SELECT a.asset_id,a.hull_number,t.source_unit_code,'Sinkronisasi kode unit dari UPDATE DUMPTRUCK 09.11.2026','2026-11-09','MIGRATION_DUMPTRUCK_20261109' FROM assets a JOIN tmp_dumptruck_latest t ON UPPER(REPLACE(a.license_plate,' ',''))=t.normalized_plate LEFT JOIN asset_hull_history h ON h.asset_id=a.asset_id AND h.new_hull_number=t.source_unit_code AND h.effective_date='2026-11-09' WHERE a.is_active=1 AND COALESCE(a.hull_number,'')<>t.source_unit_code AND h.history_id IS NULL;")
[void]$migration.AppendLine("UPDATE assets a JOIN tmp_dumptruck_latest t ON UPPER(REPLACE(a.license_plate,' ',''))=t.normalized_plate SET a.hull_number=t.source_unit_code,a.alias_name=t.source_unit_code,a.type='Dump Truck',a.category='Dump Truck',a.project_name='WUR EW',a.operational_position=t.location_label,a.current_location_id=CASE WHEN t.location_label='YARD KM 12' THEN 12 ELSE 3 END,a.status=t.normalized_status,a.is_active=1 WHERE a.asset_id<>'DT-00152';")
[void]$migration.AppendLine("DELETE arc FROM archived_items arc JOIN assets a ON a.asset_id=arc.item_id AND arc.item_type='asset' JOIN tmp_dumptruck_latest t ON UPPER(REPLACE(a.license_plate,' ',''))=t.normalized_plate WHERE a.asset_id<>'DT-00152';")
[void]$migration.AppendLine()

[void]$migration.AppendLine('INSERT INTO asset_operational_snapshots (report_date,source_file,source_section,source_row_no,source_unit_code,license_plate,normalized_plate,location_label,condition_text,normalized_status) VALUES')
$snapshotSqlRows = foreach ($row in $allRows) {
    '(' + (@(
        (Convert-ToSqlLiteral $row.report_date), (Convert-ToSqlLiteral $row.source_file),
        (Convert-ToSqlLiteral $row.source_section), $row.source_row_no, (Convert-ToSqlLiteral $row.source_unit_code),
        (Convert-ToSqlLiteral $row.license_plate), (Convert-ToSqlLiteral $row.normalized_plate),
        (Convert-ToSqlLiteral $row.location_label), (Convert-ToSqlLiteral $row.condition_text),
        (Convert-ToSqlLiteral $row.normalized_status)
    ) -join ',') + ')'
}
[void]$migration.AppendLine(($snapshotSqlRows -join ",`n") + ' ON DUPLICATE KEY UPDATE source_file=VALUES(source_file),license_plate=VALUES(license_plate),normalized_plate=VALUES(normalized_plate),location_label=VALUES(location_label),condition_text=VALUES(condition_text),normalized_status=VALUES(normalized_status);')
[void]$migration.AppendLine('DROP TEMPORARY TABLE IF EXISTS tmp_dumptruck_snapshot_conflicts;')
[void]$migration.AppendLine("CREATE TEMPORARY TABLE tmp_dumptruck_snapshot_conflicts AS SELECT report_date,normalized_plate,COUNT(*) row_count FROM asset_operational_snapshots WHERE report_date BETWEEN '2026-10-26' AND '2026-11-09' GROUP BY report_date,normalized_plate;")
[void]$migration.AppendLine("UPDATE asset_operational_snapshots s LEFT JOIN assets a ON UPPER(REPLACE(a.license_plate,' ',''))=s.normalized_plate AND a.is_active=1 AND a.status<>'INACTIVE' LEFT JOIN tmp_dumptruck_snapshot_conflicts c ON c.report_date=s.report_date AND c.normalized_plate=s.normalized_plate SET s.resolved_asset_id=a.asset_id,s.resolution_state=CASE WHEN c.row_count>1 THEN 'SOURCE_CONFLICT' WHEN a.asset_id IS NULL THEN 'UNRESOLVED' WHEN a.created_at>='2026-11-09' THEN 'CREATED' ELSE 'MATCHED' END WHERE s.report_date BETWEEN '2026-10-26' AND '2026-11-09';")
[void]$migration.AppendLine('DROP TEMPORARY TABLE IF EXISTS tmp_dumptruck_snapshot_conflicts;')
[void]$migration.AppendLine()
[void]$migration.AppendLine('CREATE OR REPLACE VIEW v_asset_latest_operational_snapshot AS SELECT s.resolved_asset_id AS asset_id,s.report_date AS operational_report_date,GROUP_CONCAT(DISTINCT s.source_unit_code ORDER BY s.source_unit_code SEPARATOR ", ") AS operational_source_unit_codes,MAX(s.source_file) AS operational_source_file,MAX(s.location_label) AS operational_source_location,MAX(s.condition_text) AS operational_source_condition,MAX(s.resolution_state) AS operational_resolution_state FROM asset_operational_snapshots s JOIN (SELECT resolved_asset_id,MAX(report_date) report_date FROM asset_operational_snapshots WHERE resolved_asset_id IS NOT NULL GROUP BY resolved_asset_id) latest ON latest.resolved_asset_id=s.resolved_asset_id AND latest.report_date=s.report_date GROUP BY s.resolved_asset_id,s.report_date;')
[void]$migration.AppendLine('DROP TEMPORARY TABLE IF EXISTS tmp_dumptruck_latest;')
[void]$migration.AppendLine('COMMIT;')

$migrationPath = Join-Path $repositoryRoot 'scripts\migrate_dumptruck_update_20261109.sql'
[IO.File]::WriteAllText($migrationPath, $migration.ToString(), [Text.UTF8Encoding]::new($false))

Write-Host "Converted $($reports.Count) PDFs into Markdown and $($allRows.Count) snapshot rows." -ForegroundColor Green
Write-Host "Markdown: $OutputDir" -ForegroundColor Gray
Write-Host "Dataset: $jsonPath" -ForegroundColor Gray
Write-Host "Migration: $migrationPath" -ForegroundColor Gray
