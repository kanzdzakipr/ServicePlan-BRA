# ============================================================================
# ServicePlan-BRA: Populate tire_inspections by Equipment Category & Layout
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$mysqldumpExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$dbName = "u646470441_ServicePlanBRA"
$sqlFile = "scripts\u646470441_ServicePlanBRA.sql"

Write-Host "[1/3] Fetching wheeled assets from $dbName..." -ForegroundColor Cyan

# Fetch all assets with category and type
$rawAssets = & $mysqlExe -u root -e "USE $dbName; SELECT asset_id, category, type, make_model FROM assets;"
$lines = $rawAssets -split "`r?`n"
if ($lines.Count -le 1) {
    Write-Host "[!] No assets found in database $dbName" -ForegroundColor Red
    exit
}

$assetRows = $lines[1..($lines.Count - 1)]

Write-Host "Auditing $($assetRows.Count) master assets..." -ForegroundColor Yellow

$sqlStatements = @(
    "SET FOREIGN_KEY_CHECKS = 0;",
    "TRUNCATE TABLE tire_inspections;"
)

$tireBrands = @("BS-1200R24", "MI-1200R24", "GY-1200R24", "GT-1200R24", "MA-1200R24")
$tireCounter = 100001
$totalTiresSeeded = 0

foreach ($row in $assetRows) {
    if ([string]::IsNullOrWhiteSpace($row)) { continue }
    $cols = $row -split "`t"
    if ($cols.Count -lt 4) { continue }

    $assetId  = $cols[0].Trim()
    $category = $cols[1].Trim()
    $type     = $cols[2].Trim()
    $model    = $cols[3].Trim()

    # Determine Tire Positions Layout based on Equipment Category
    $positions = @()

    if ($category -eq "Dump Truck") {
        if ($model -match "NMR|Isuzu|4x2") {
            # 6-Wheeler Dump Truck
            $positions = @("FL", "FR", "R1LO", "R1LI", "R1RI", "R1RO")
        } else {
            # 10-Wheeler 6x4 Standard Mining Dump Truck
            $positions = @("FL", "FR", "R1LO", "R1LI", "R1RI", "R1RO", "R2LO", "R2LI", "R2RI", "R2RO")
        }
    }
    elseif ($category -eq "Trado" -or $category -eq "Prime Mover") {
        # 14-Wheeler Heavy Trailer (Head Tractor 6x4 + Trailer Axle)
        $positions = @("FL", "FR", "R1LO", "R1LI", "R1RI", "R1RO", "R2LO", "R2LI", "R2RI", "R2RO", "T1LO", "T1LI", "T1RI", "T1RO")
    }
    elseif ($category -eq "Motor Grader") {
        # 6-Wheeler Tandem Drive Motor Grader
        $positions = @("FL", "FR", "R1L", "R1R", "R2L", "R2R")
    }
    elseif ($category -eq "Water Truck") {
        # 10-Wheeler 6x4 Water Truck
        $positions = @("FL", "FR", "R1LO", "R1LI", "R1RI", "R1RO", "R2LO", "R2LI", "R2RI", "R2RO")
    }
    elseif ($category -eq "Light Vehicle") {
        # 4x4 Light Vehicle
        $positions = @("FL", "FR", "RL", "RR", "SPARE")
    }
    elseif ($category -eq "Vibro Compactor") {
        # Rear Axle Pneumatic Tires for Vibro Compactor
        $positions = @("RL", "RR")
    }
    elseif ($category -eq "Other" -and $assetId -match "^TMC|^FBT") {
        # Crane / Support Trucks
        $positions = @("FL", "FR", "R1LO", "R1LI", "R1RI", "R1RO", "R2LO", "R2LI", "R2RI", "R2RO")
    }

    # Skip tracked equipment (Excavators, Bulldozers, Reclaimers)
    if ($positions.Count -eq 0) { continue }

    Write-Host " Seeding $($positions.Count) tires for $assetId ($category)..." -ForegroundColor Green

    foreach ($pos in $positions) {
        $brand = $tireBrands[$tireCounter % $tireBrands.Count]
        $sn = "$brand-$tireCounter"
        $tireCounter++

        # Generate realistic tread depth (mm), pressure (PSI), & condition
        $randVal = Get-Random -Minimum 1 -Maximum 100
        $tread = 14.5
        $pressure = 110
        $color = "GREEN"

        if ($randVal -le 70) {
            # 70% GREEN (Good condition)
            $tread = [math]::Round((Get-Random -Minimum 12.0 -Maximum 22.0), 1)
            $pressure = Get-Random -Minimum 105 -Maximum 118
            $color = "GREEN"
        }
        elseif ($randVal -le 90) {
            # 20% YELLOW (Warning / Wear)
            $tread = [math]::Round((Get-Random -Minimum 6.0 -Maximum 9.5), 1)
            $pressure = Get-Random -Minimum 92 -Maximum 104
            $color = "YELLOW"
        }
        else {
            # 10% RED (Critical / Change needed)
            $tread = [math]::Round((Get-Random -Minimum 2.5 -Maximum 4.8), 1)
            $pressure = Get-Random -Minimum 75 -Maximum 88
            $color = "RED"
        }

        $assetIdEsc = $assetId.Replace("'", "''")
        $sqlStatements += "INSERT INTO tire_inspections (asset_id, tire_serial_no, tire_position, tread_depth_mm, air_pressure_psi, condition_color, inspected_at) VALUES ('$assetIdEsc', '$sn', '$pos', $tread, $pressure, '$color', NOW());"
        $totalTiresSeeded++
    }
}

$sqlStatements += "SET FOREIGN_KEY_CHECKS = 1;"

Write-Host "[2/3] Executing database insertion ($totalTiresSeeded tire records) on $dbName..." -ForegroundColor Cyan
$tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($tempFile, "USE $dbName; `n" + ($sqlStatements -join "`n"), $utf8NoBom)

Get-Content $tempFile | & $mysqlExe -u root $dbName
Remove-Item $tempFile -Force

Write-Host "[3/3] Re-exporting clean database dump to $sqlFile..." -ForegroundColor Cyan
& $mysqldumpExe -u root --routines --triggers --events $dbName > $sqlFile

Write-Host "Tire inspections population finished! Populated $totalTiresSeeded tire records across all wheeled equipment." -ForegroundColor Green
