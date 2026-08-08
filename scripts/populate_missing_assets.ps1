# PowerShell script to populate all missing material assets into u646470441_ServicePlanBRA.sql
$sqlPath = 'scripts/u646470441_ServicePlanBRA.sql'
$rawSql = [System.IO.File]::ReadAllText($sqlPath, [System.Text.Encoding]::UTF8)

# Read existing asset IDs
$existingAssetIds = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)

$rxQuote = [regex]"'([^']*)'"

$lines = $rawSql -split "`n"
$assetLineIndex = -1

for ($i = 0; $i -lt $lines.Count; $i++) {
    if ($lines[$i].StartsWith("INSERT INTO `assets`")) {
        $assetLineIndex = $i
        $matches = $rxQuote.Matches($lines[$i])
        for ($j = 0; $j -lt $matches.Count; $j++) {
            $val = $matches[$j].Groups[1].Value
            if ($val -match '^[A-Z0-9]{2,5}-\d{1,5}$' -or $val -match '^[A-Z]{2,4}\d{4,5}[A-Z]{2,3}$' -or $val -match '^HE-6\.') {
                [void]$existingAssetIds.Add($val)
            }
        }
    }
}

Write-Host "Existing assets in SQL: $($existingAssetIds.Count) units"

# Unit dictionary
$unitDict = [System.Collections.Generic.Dictionary[string, System.Object]]::new([System.StringComparer]::OrdinalIgnoreCase)

function Get-Category-From-Code([string]$code, [string]$jenisStr) {
    if ($jenisStr -like '*DUMP TRUCK*' -or $code -like 'DT-*' -or $code -like 'BRA-4*' -or $code -like 'BRA-5*' -or $code -like 'DT0*') { return 'Dump Truck' }
    if ($jenisStr -like '*EXCAVATOR*' -or $code -like 'EXC-*' -or $code -like 'EX-*' -or $code -like 'EXCA-*' -or $code -like 'HE-6*') { return 'Excavator' }
    if ($jenisStr -like '*BULLDOZER*' -or $code -like 'DZ-*' -or $code -like 'Dozer*' -or $code -like 'BRA-01' -or $code -like 'BRA-07') { return 'Bulldozer' }
    if ($jenisStr -like '*MOTOR GRADER*' -or $code -like 'MG-*') { return 'Motor Grader' }
    if ($jenisStr -like '*COMPACTOR*' -or $code -like 'PF-*' -or $code -like 'SD-*' -or $code -like 'VIBRO-*') { return 'Vibro Compactor' }
    if ($jenisStr -like '*WATER*' -or $code -like 'WT-*') { return 'Water Truck' }
    if ($jenisStr -like '*PRIME MOVER*' -or $code -like 'PM-*') { return 'Prime Mover' }
    if ($jenisStr -like '*DOUBLE CABIN*' -or $code -like 'LV-*' -or $code -like 'B 91*ZBA' -or $code -like 'B 92*ZBA' -or $code -like 'B 96*UBE' -or $code -like 'B 97*UBE') { return 'Light Vehicle' }
    if ($jenisStr -like '*TRADO*' -or $code -like 'SL-*' -or $code -like 'FBT-*' -or $code -like 'LB-*') { return 'Trado' }
    if ($jenisStr -like '*RECLAIMER*' -or $jenisStr -like '*SPREADER*' -or $code -like 'CS-*' -or $code -like 'RM-*') { return 'Reclaimer Spreader' }
    return 'Other'
}

function Get-Type-From-Category([string]$cat) {
    if ($cat -eq 'Light Vehicle') { return 'Light Vehicle' }
    if ($cat -eq 'Other') { return 'Support Equipment' }
    return 'Heavy Equipment'
}

# 1. Parse REKAP_DAFTAR_ASET_STANDBY_ALAT_BERAT.md
$standbyPath = 'material/REKAP_DAFTAR_ASET_STANDBY_ALAT_BERAT.md'
if (Test-Path $standbyPath) {
    $linesMb = [System.IO.File]::ReadAllLines($standbyPath, [System.Text.Encoding]::UTF8)
    foreach ($l in $linesMb) {
        $parts = $l -split '\|' | ForEach-Object { $_.Trim() }
        if ($parts.Count -ge 11 -and $parts[1] -match '^\d+$') {
            $name = $parts[2]
            $dealer = $parts[3]
            $year = if ($parts[4] -match '^\d{4}$') { [int]$parts[4] } else { 2023 }
            $serialOrNopol = $parts[5]
            $kodeUnit = $parts[7]
            $nopolAktual = $parts[8]
            $project = $parts[9]
            $lokasi = $parts[10]
            $status = $parts[11]
            
            $unitId = if ($nopolAktual -and $nopolAktual -ne '-') { $nopolAktual.Replace(' ', '') } else { $kodeUnit.Replace(' ', '') }
            $unitId = $unitId.Replace('VIBROBW', '').Trim()
            
            if ($unitId -and $unitId -ne '-') {
                $cat = Get-Category-From-Code $unitId $name
                $unitDict[$unitId] = @{
                    Id = $unitId
                    Code = $unitId
                    Name = $name
                    Category = $cat
                    MakeModel = "$name ($dealer)"
                    Year = $year
                    Serial = if ($serialOrNopol -match '^[A-Z0-9]{5,}$') { $serialOrNopol } else { $null }
                    Plate = if ($serialOrNopol -match '\b[A-Z]{1,2}\s*\d+\s*[A-Z]{1,3}\b') { $serialOrNopol } else { $null }
                    Status = if ($status) { $status } else { 'STANDBY' }
                    LocationNotes = $lokasi
                }
            }
        }
    }
}

# 2. Parse ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md
$mutasiPath = 'material/ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md'
if (Test-Path $mutasiPath) {
    $linesMb = [System.IO.File]::ReadAllLines($mutasiPath, [System.Text.Encoding]::UTF8)
    foreach ($l in $linesMb) {
        $parts = $l -split '\|' | ForEach-Object { $_.Trim() }
        if ($parts.Count -ge 8 -and $parts[1] -match '^\d+$') {
            $nopolLama = $parts[3]
            $jenis = $parts[4]
            $proyek = $parts[5]
            $nopolBaru = $parts[7]
            
            $unitId = if ($nopolBaru) { $nopolBaru.Replace(' ', '') } else { $nopolLama.Replace(' ', '') }
            if ($unitId -and $unitId -ne 'Nopol' -and -not $unitDict.ContainsKey($unitId)) {
                $cat = Get-Category-From-Code $unitId $jenis
                $unitDict[$unitId] = @{
                    Id = $unitId
                    Code = $unitId
                    Name = "$jenis ($nopolLama)"
                    Category = $cat
                    MakeModel = $jenis
                    Year = 2022
                    Serial = $null
                    Plate = if ($nopolBaru) { $nopolBaru } else { $nopolLama }
                    Status = 'OPERATING'
                    LocationNotes = "$proyek Project"
                }
            }
        }
    }
}

$newTuples = @()
$newCount = 0

foreach ($key in $unitDict.Keys | Sort-Object) {
    if (-not $existingAssetIds.Contains($key)) {
        $u = $unitDict[$key]
        $idEsc = $u.Id.Replace("'", "''")
        $codeEsc = $u.Code.Replace("'", "''")
        $nameEsc = $u.Name.Replace("'", "''")
        $catEsc = $u.Category.Replace("'", "''")
        $typeEsc = Get-Type-From-Category $u.Category
        $modelEsc = $u.MakeModel.Replace("'", "''")
        $yearVal = $u.Year
        $statusEsc = if ($u.Status) { $u.Status } else { 'READY' }
        $plateEsc = if ($u.Plate) { "'$($u.Plate.Replace("'", "''"))'" } else { 'NULL' }
        $serialEsc = if ($u.Serial) { "'$($u.Serial.Replace("'", "''"))'" } else { 'NULL' }
        $locNotesEsc = if ($u.LocationNotes) { "'$($u.LocationNotes.Replace("'", "''"))'" } else { "'Yard KM 12 Duri'" }
        
        $locId = 12
        if ($locNotesEsc -like '*WUR*') { $locId = 15 }
        elseif ($locNotesEsc -like '*Palembang*') { $locId = 2 }
        elseif ($locNotesEsc -like '*PEKANBARU*' -or $locNotesEsc -like '*Pekanbaru*') { $locId = 64 }
        
        $tuple = "('$idEsc','$codeEsc',$serialEsc,$plateEsc,NULL,'$idEsc','$typeEsc','$catEsc','$modelEsc','PKB PEKANBARU Branch',$yearVal,'Milik Sendiri','$statusEsc',$locId,$locNotesEsc,0.00,NULL,NULL,NULL,NULL,1,NOW(),NOW())"
        $newTuples += $tuple
        $newCount++
    }
}

Write-Host "Existing Assets in SQL: $($existingAssetIds.Count)"
Write-Host "New Missing Assets Identified from Material: $newCount"

if ($newCount -gt 0 -and $assetLineIndex -ge 0) {
    # Replace the trailing semicolon at the end of line 183 with , (newTuples);
    $origLine = $lines[$assetLineIndex]
    if ($origLine.EndsWith(";")) {
        $trimmedLine = $origLine.Substring(0, $origLine.Length - 1)
        $lines[$assetLineIndex] = $trimmedLine + "," + ($newTuples -join ",") + ";"
        [System.IO.File]::WriteAllLines($sqlPath, $lines, (New-Object System.Text.UTF8Encoding($false)))
        Write-Host "Successfully appended $newCount missing assets into u646470441_ServicePlanBRA.sql!"
    }
}
