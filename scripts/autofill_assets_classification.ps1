# ============================================================================
# ServicePlan-BRA: Autofill Asset Classifications in MySQL & SQL Dump
# ============================================================================

$sqlFile = "scripts\u646470441_ServicePlanBRA.sql"
$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$dbName = "u646470441_ServicePlanBRA"

Write-Host "Starting Asset Classification Autofill for ServicePlan-BRA..." -ForegroundColor Cyan

# 1. Fetch all assets from local MySQL database
$query = "SELECT asset_id, asset_code, type, category, make_model, sub_group_branch, year_manufacture FROM assets;"
$rawOutput = & $mysqlExe -u root -e "USE $dbName; $query"

$lines = $rawOutput -split "`r?`n"
if ($lines.Count -le 1) {
    Write-Host "[!] No assets found in database $dbName" -ForegroundColor Red
    exit
}

$header = $lines[0]
$dataRows = $lines[1..($lines.Count - 1)]

Write-Host "Auditing $($dataRows.Count) asset records in database..." -ForegroundColor Yellow

$updateStatements = @()
$updatedCount = 0

foreach ($row in $dataRows) {
    if ([string]::IsNullOrWhiteSpace($row)) { continue }
    $cols = $row -split "`t"
    if ($cols.Count -lt 7) { continue }

    $id        = $cols[0].Trim()
    $code      = $cols[1].Trim()
    $currType  = $cols[2].Trim()
    $currCat   = $cols[3].Trim()
    $currModel = $cols[4].Trim()
    $currSub   = $cols[5].Trim()
    $currYear  = $cols[6].Trim()

    $searchStr = "$id $code $currModel".ToUpper()

    # Determine correct category and type
    $targetCat = $currCat
    $targetType = if ([string]::IsNullOrWhiteSpace($currType) -or $currType -eq "NULL") { "Heavy Equipment" } else { $currType }
    $targetModel = if ($currModel -eq "NULL" -or [string]::IsNullOrWhiteSpace($currModel)) { "" } else { $currModel }
    $targetSub   = if ($currSub -eq "NULL" -or [string]::IsNullOrWhiteSpace($currSub)) { "PKB PEKANBARU Branch" } else { $currSub }
    $targetYear  = if ($currYear -eq "NULL" -or [string]::IsNullOrWhiteSpace($currYear)) { "2023" } else { $currYear }

    # Classification Rules based on Asset ID / Code
    if ($searchStr -match "\bDZ\b|\bBULLDOZER\b|\bSHANTUI\b") {
        $targetCat = "Bulldozer"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Bulldozer Caterpillar D6G / Shantui SD16" }
    }
    elseif ($searchStr -match "\bDT\b|\bDUMP\b|\bDUMPTRUCK\b|\bHINO RANGER\b|\bFUSO\b|\bQUESTER\b|\bISUZU NMR\b") {
        $targetCat = "Dump Truck"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Dump Truck Hino FM260JD / Mercedes Axor" }
    }
    elseif ($searchStr -match "\bMG\b|\bGRADER\b") {
        $targetCat = "Motor Grader"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Motor Grader XCMG GR135 MAX" }
    }
    elseif ($searchStr -match "\bPF\b|\bSD\b|\bPADFOOT\b|\bVIBRO\b|\bBOMAG\b|\bSAKAI\b") {
        $targetCat = "Vibro Compactor"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Vibro Compactor Bomag BW211D-40SL / Sakai" }
    }
    elseif ($searchStr -match "\bWT\b|\bWATER TRUCK\b") {
        $targetCat = "Water Truck"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Water Truck Hino Dutro 130 HD / Isuzu" }
    }
    elseif ($searchStr -match "\bEX\b|\bEXC\b|\bEXCAVATOR\b|\bPC\s*200\b|\bHE-6\b") {
        $targetCat = "Excavator"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Excavator Komatsu PC200-10M0 / CAT 320" }
    }
    elseif ($searchStr -match "\bPM\b|\bLOWBOY\b|\bLB\b|\bTRADO\b|\bSL-\b|\bFLAT\b|\bFBT\b") {
        $targetCat = "Trado"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Trado Heavy Hauler Mitsubishi FV 419 P / Hino" }
    }
    elseif ($searchStr -match "\bHILUX\b|\bLV\b") {
        $targetCat = "Light Vehicle"
        $targetType = "Light Vehicle"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Toyota Hilux Double Cabin 4x4" }
    }
    elseif ($searchStr -match "\bCS-01\b|\bRM-\b|\bCEMENT SPREADER\b") {
        $targetCat = "Reclaimer Spreader"
        $targetType = "Heavy Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Reclaimer / Cement Spreader XCMG XLZ2303" }
    }
    elseif ($searchStr -match "\bTMC\b|\bCRANE\b") {
        $targetCat = "Other"
        $targetType = "Support Equipment"
        if ([string]::IsNullOrWhiteSpace($targetModel)) { $targetModel = "Truck Mounted Crane XCMG B 9435 XFY" }
    }

    # If classification changed or model/branch/year autofilled
    if ($targetCat -ne $currCat -or $targetType -ne $currType -or $targetModel -ne $currModel -or $targetSub -ne $currSub -or $targetYear -ne $currYear) {
        $escapedId = $id.Replace("'", "''")
        $escapedModel = $targetModel.Replace("'", "''")
        $escapedSub = $targetSub.Replace("'", "''")
        
        $sql = "UPDATE assets SET category = '$targetCat', type = '$targetType', make_model = '$escapedModel', sub_group_branch = '$escapedSub', year_manufacture = $targetYear WHERE asset_id = '$escapedId';"
        $updateStatements += $sql
        $updatedCount++
        Write-Host " [Autofill] $id => Cat: '$targetCat', Type: '$targetType', Model: '$targetModel'" -ForegroundColor Green
    }
}

if ($updatedCount -gt 0) {
    Write-Host "Applying $updatedCount updates to MySQL database..." -ForegroundColor Cyan
    $batchSql = $updateStatements -join " "
    $tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
    [System.IO.File]::WriteAllText($tempFile, "USE $dbName; $batchSql", [System.Text.Encoding]::UTF8)
    
    Get-Content $tempFile | & $mysqlExe -u root $dbName
    Remove-Item $tempFile -Force

    Write-Host "Re-exporting clean SQL dump to $sqlFile..." -ForegroundColor Cyan
    $mysqldumpExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
    & $mysqldumpExe -u root --routines --triggers --events $dbName > $sqlFile

    Write-Host "Asset classification autofill completed! Updated $updatedCount asset records." -ForegroundColor Green
} else {
    Write-Host "All assets are already fully classified!" -ForegroundColor Green
}
