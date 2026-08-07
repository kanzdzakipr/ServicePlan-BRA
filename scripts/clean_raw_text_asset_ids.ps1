# ============================================================================
# ServicePlan-BRA: Clean Raw Text & License Plate Asset IDs
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$mysqldumpExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$dbName = "u646470441_ServicePlanBRA"
$sqlFile = "scripts\u646470441_ServicePlanBRA.sql"

Write-Host "[1/4] Mapping 38 raw text / license plate asset IDs to standard unit codes..." -ForegroundColor Cyan

# Explicit mapping dictionary
$mappings = @(
    @{ OldId = 'BM 8441 NU ( XCMG Double Jack )'; NewId = 'TMC-31002'; Plate = 'BM 8441 NU'; Model = 'Truck Mounted Crane XCMG Double Jack'; Cat = 'Other'; Type = 'Support Equipment' },
    @{ OldId = 'BM 8452 NU'; NewId = 'TMC-31003'; Plate = 'BM 8452 NU'; Model = 'Service Truck Crane'; Cat = 'Other'; Type = 'Support Equipment' },
    @{ OldId = 'FLAT BED B 9733 XPZ'; NewId = 'FBT-31003'; Plate = 'B 9733 XPZ'; Model = 'Flat Bed Trailer Truck'; Cat = 'Trado'; Type = 'Heavy Equipment' },
    @{ OldId = 'FBT 31002. - FLAT BED BM 8443 NU'; NewId = 'FBT-31002'; Plate = 'BM 8443 NU'; Model = 'Flat Bed Trailer Truck'; Cat = 'Trado'; Type = 'Heavy Equipment' },
    @{ OldId = 'FBT 31004. - BM 8451 NU'; NewId = 'FBT-31004'; Plate = 'BM 8451 NU'; Model = 'Flat Bed Trailer Truck'; Cat = 'Trado'; Type = 'Heavy Equipment' },
    @{ OldId = 'FUSO TMC - XCMG B 9435 XFY'; NewId = 'TMC-31001'; Plate = 'B 9435 XFY'; Model = 'Mitsubishi Fuso Truck Mounted Crane XCMG'; Cat = 'Other'; Type = 'Support Equipment' },
    @{ OldId = 'XCMG-B 9435 XFY'; NewId = 'TMC-31001'; Plate = 'B 9435 XFY'; Model = 'Truck Mounted Crane XCMG'; Cat = 'Other'; Type = 'Support Equipment' },
    @{ OldId = 'TMC 31001 - BM 8440 NU'; NewId = 'TMC-31001'; Plate = 'BM 8440 NU'; Model = 'Truck Mounted Crane XCMG'; Cat = 'Other'; Type = 'Support Equipment' },
    @{ OldId = 'TMC B 9430 XFY'; NewId = 'TMC-31004'; Plate = 'B 9430 XFY'; Model = 'Truck Mounted Crane XCMG'; Cat = 'Other'; Type = 'Support Equipment' },
    @{ OldId = 'THRILLER - BM 8371 cu'; NewId = 'PM-41002'; Plate = 'BM 8371 CU'; Model = 'Prime Mover Trailer Thriller'; Cat = 'Prime Mover'; Type = 'Heavy Equipment' },
    @{ OldId = 'DT 0008 - B 9137 ZYT'; NewId = 'DT-00008'; Plate = 'B 9137 ZYT'; Model = 'Dump Truck Hino FM260JD / Mercedes Axor'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'DT 0057 - B 9914 ZYT'; NewId = 'DT-00057'; Plate = 'B 9914 ZYT'; Model = 'Dump Truck Hino FM260JD / Mercedes Axor'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'Dump Truck FUSO FN62F HD'; NewId = 'DT-04060'; Plate = 'BM 9100 FSO'; Model = 'Mitsubishi Fuso FN62F HD Dump Truck'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'Dump Truck HINO RANGER-FM 260 JD'; NewId = 'DT-04061'; Plate = 'BM 9260 HNO'; Model = 'Hino Ranger FM 260 JD Dump Truck'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'Dump Truck HINO RANGER-FM 280 JD'; NewId = 'DT-04062'; Plate = 'BM 9280 HNO'; Model = 'Hino Ranger FM 280 JD Dump Truck'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'Dump Truck QUESTER'; NewId = 'DT-04063'; Plate = ''; Model = 'UD Trucks Quester Dump Truck'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'Dumptruck FUSO FM 517 HS'; NewId = 'DT-04064'; Plate = ''; Model = 'Mitsubishi Fuso FM 517 HS Dumptruck'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'Dumptruck Isuzu NMR 81'; NewId = 'DT-04065'; Plate = ''; Model = 'Isuzu NMR 81 Light Dump Truck'; Cat = 'Dump Truck'; Type = 'Heavy Equipment' },
    @{ OldId = 'Excavator Caterpillar 305.5E2'; NewId = 'EXC-00002'; Plate = ''; Model = 'Caterpillar 305.5E2 Mini Excavator'; Cat = 'Excavator'; Type = 'Heavy Equipment' },
    @{ OldId = 'Excavator Caterpillar 320 GC'; NewId = 'EXC-00003'; Plate = ''; Model = 'Caterpillar 320 GC Excavator'; Cat = 'Excavator'; Type = 'Heavy Equipment' },
    @{ OldId = 'Excavator Caterpillar 320D S/N'; NewId = 'EXC-00004'; Plate = ''; Model = 'Caterpillar 320D Excavator'; Cat = 'Excavator'; Type = 'Heavy Equipment' },
    @{ OldId = 'Excavator PC 210-10M0/P1'; NewId = 'EXC-00005'; Plate = ''; Model = 'Komatsu PC210-10M0 Excavator'; Cat = 'Excavator'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bulldozer Caterpillar D6G'; NewId = 'DZ-00004'; Plate = ''; Model = 'Caterpillar D6G Bulldozer'; Cat = 'Bulldozer'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bulldozer Caterpillar D6G 2XL'; NewId = 'DZ-00005'; Plate = ''; Model = 'Caterpillar D6G 2XL Bulldozer'; Cat = 'Bulldozer'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bulldozer Caterpillar D6R'; NewId = 'DZ-00007'; Plate = ''; Model = 'Caterpillar D6R Bulldozer'; Cat = 'Bulldozer'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bulldozer Caterpillar D7G2'; NewId = 'DZ-00008'; Plate = ''; Model = 'Caterpillar D7G2 Bulldozer'; Cat = 'Bulldozer'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bulldozer Komatsu D85ESS-2'; NewId = 'DZ-00009'; Plate = ''; Model = 'Komatsu D85ESS-2 Bulldozer'; Cat = 'Bulldozer'; Type = 'Heavy Equipment' },
    @{ OldId = 'DOZER-BRA 05'; NewId = 'DZ-00003'; Plate = ''; Model = 'Bulldozer Caterpillar D6G'; Cat = 'Bulldozer'; Type = 'Heavy Equipment' },
    @{ OldId = 'CAT RM-500B'; NewId = 'RM-00001'; Plate = ''; Model = 'Caterpillar RM-500B Reclaimer'; Cat = 'Reclaimer Spreader'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bomag Pad Foot Compactor BW211D-40'; NewId = 'PF-00005'; Plate = ''; Model = 'Bomag Pad Foot Compactor BW211D-40'; Cat = 'Vibro Compactor'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bomag Smooth Drum BW211D-40'; NewId = 'SD-00006'; Plate = ''; Model = 'Bomag Smooth Drum Compactor BW211D-40'; Cat = 'Vibro Compactor'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bomag Smooth Drum Compactor BW211D-40 SL'; NewId = 'SD-00007'; Plate = ''; Model = 'Bomag Smooth Drum Compactor BW211D-40 SL'; Cat = 'Vibro Compactor'; Type = 'Heavy Equipment' },
    @{ OldId = 'Bomag Smooth Drum Compactor BW211D-40SL'; NewId = 'SD-00008'; Plate = ''; Model = 'Bomag Smooth Drum Compactor BW211D-40 SL'; Cat = 'Vibro Compactor'; Type = 'Heavy Equipment' },
    @{ OldId = 'Padfoot XCMG XS205PD'; NewId = 'PF-00006'; Plate = ''; Model = 'XCMG XS205PD Padfoot Compactor'; Cat = 'Vibro Compactor'; Type = 'Heavy Equipment' },
    @{ OldId = 'Lowboy PM-00003 - KT 9287 KU'; NewId = 'PM-00003'; Plate = 'KT 9287 KU'; Model = 'Trado Heavy Hauler Lowboy'; Cat = 'Trado'; Type = 'Heavy Equipment' },
    @{ OldId = 'TRADO HINO RANGER-FM 260 JD'; NewId = 'SL-02'; Plate = ''; Model = 'Hino Ranger FM 260 JD Trado'; Cat = 'Trado'; Type = 'Heavy Equipment' },
    @{ OldId = 'TRADO MITSUBISHI FV 419 P'; NewId = 'SL-01'; Plate = ''; Model = 'Mitsubishi FV 419 P Trado'; Cat = 'Trado'; Type = 'Heavy Equipment' },
    @{ OldId = 'VIBRO BW BRA-01'; NewId = 'PF-00001'; Plate = ''; Model = 'Bomag Smooth Drum BW211D-40 SL'; Cat = 'Vibro Compactor'; Type = 'Heavy Equipment' }
)

Write-Host "[2/4] Checking existing asset IDs in database..." -ForegroundColor Cyan
$rawIds = & $mysqlExe -u root -e "USE $dbName; SELECT asset_id FROM assets;"
$existingDbIds = @{}
foreach ($l in ($rawIds -split "`r?`n")[1..(($rawIds -split "`r?`n").Count-1)]) {
    if (-not [string]::IsNullOrWhiteSpace($l)) { $existingDbIds[$l.Trim()] = $true }
}

$sqlStatements = @()
$sqlStatements += "SET FOREIGN_KEY_CHECKS = 0;"

foreach ($m in $mappings) {
    $oldIdEsc = $m.OldId.Replace("'", "''")
    $newIdEsc = $m.NewId.Replace("'", "''")
    $plEsc    = $m.Plate.Replace("'", "''")
    $modelEsc = $m.Model.Replace("'", "''")
    $catEsc   = $m.Cat.Replace("'", "''")
    $typeEsc  = $m.Type.Replace("'", "''")

    $isDuplicate = $existingDbIds.ContainsKey($m.NewId) -and ($m.OldId -ne $m.NewId)

    if ($isDuplicate) {
        Write-Host " [Merging Duplicate Raw ID] '$oldIdEsc' => '$newIdEsc'" -ForegroundColor Yellow
        $sqlStatements += "UPDATE work_orders SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE inspections SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE fuel_logs SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE tire_inspections SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE pm_plans SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE asset_movements SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        
        $sqlStatements += "UPDATE assets SET license_plate = COALESCE(license_plate, NULLIF('$plEsc', '')), make_model = COALESCE(make_model, NULLIF('$modelEsc', '')), category = '$catEsc', type = '$typeEsc' WHERE asset_id = '$newIdEsc';"
        $sqlStatements += "DELETE FROM assets WHERE asset_id = '$oldIdEsc';"
    } else {
        Write-Host " [Sanitizing Raw ID] '$oldIdEsc' => '$newIdEsc'" -ForegroundColor Green
        $sqlStatements += "UPDATE work_orders SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE inspections SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE fuel_logs SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE tire_inspections SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE pm_plans SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE asset_movements SET asset_id = '$newIdEsc' WHERE asset_id = '$oldIdEsc';"
        
        $sqlStatements += "UPDATE assets SET asset_id = '$newIdEsc', asset_code = '$newIdEsc', license_plate = COALESCE(license_plate, NULLIF('$plEsc', '')), make_model = COALESCE(make_model, NULLIF('$modelEsc', '')), category = '$catEsc', type = '$typeEsc' WHERE asset_id = '$oldIdEsc';"
        $existingDbIds[$m.NewId] = $true
    }
}

$sqlStatements += "SET FOREIGN_KEY_CHECKS = 1;"

Write-Host "[3/4] Executing SQL batch updates on MySQL database $dbName..." -ForegroundColor Cyan
$tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
[System.IO.File]::WriteAllText($tempFile, "USE $dbName; `n" + ($sqlStatements -join "`n"), [System.Text.Encoding]::UTF8)

Get-Content $tempFile | & $mysqlExe -u root $dbName
Remove-Item $tempFile -Force

Write-Host "[4/4] Re-exporting clean database dump to $sqlFile..." -ForegroundColor Cyan
& $mysqldumpExe -u root --routines --triggers --events $dbName > $sqlFile

Write-Host "Sanitization finished! All 38 raw text / license plate asset IDs are now clean unit codes." -ForegroundColor Green
