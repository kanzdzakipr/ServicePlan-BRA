# ============================================================================
# ServicePlan-BRA: Group-Based Asset ID Sanitization & Merge
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$mysqldumpExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$dbName = "u646470441_ServicePlanBRA"
$sqlFile = "scripts\u646470441_ServicePlanBRA.sql"

Write-Host "[1/4] Auditing assets table for messy asset_id and empty fields..." -ForegroundColor Cyan

# Fetch all assets
$rawOutput = & $mysqlExe -u root -e "USE $dbName; SELECT asset_id, asset_code, serial_number, license_plate, previous_license_plate, alias_name, type, category, make_model, sub_group_branch, year_manufacture FROM assets;"
$lines = $rawOutput -split "`r?`n"
if ($lines.Count -le 1) {
    Write-Host "[!] No assets found in database $dbName" -ForegroundColor Red
    exit
}

$dataRows = $lines[1..($lines.Count - 1)]

# Group records by target Clean ID
$groups = @{}

foreach ($row in $dataRows) {
    if ([string]::IsNullOrWhiteSpace($row)) { continue }
    $cols = $row -split "`t"
    if ($cols.Count -lt 11) { continue }

    $oldId     = $cols[0].Trim()
    $oldCode   = $cols[1].Trim()
    $oldSn     = if ($cols[2] -eq "NULL") { "" } else { $cols[2].Trim() }
    $oldPl     = if ($cols[3] -eq "NULL") { "" } else { $cols[3].Trim() }
    $oldPrevPl = if ($cols[4] -eq "NULL") { "" } else { $cols[4].Trim() }
    $oldAlias  = if ($cols[5] -eq "NULL") { "" } else { $cols[5].Trim() }
    $oldType   = if ($cols[6] -eq "NULL") { "Heavy Equipment" } else { $cols[6].Trim() }
    $oldCat    = if ($cols[7] -eq "NULL") { "Other" } else { $cols[7].Trim() }
    $oldModel  = if ($cols[8] -eq "NULL") { "" } else { $cols[8].Trim() }
    $oldSub    = if ($cols[9] -eq "NULL") { "PKB PEKANBARU Branch" } else { $cols[9].Trim() }
    $oldYear   = if ($cols[10] -eq "NULL") { "2023" } else { $cols[10].Trim() }

    # Extraction rules
    $cleanId = $oldId
    $newPl = $oldPl
    $newSn = $oldSn
    $newAlias = $oldAlias
    $newModel = $oldModel

    # Extract License Plate
    if ([string]::IsNullOrWhiteSpace($newPl)) {
        if ($oldId -match "([A-Z]{1,2}\s*\d{3,4}\s*[A-Z]{1,3})") {
            $rawPl = $matches[1]
            if ($rawPl -match "^([A-Z]{1,2})\s*(\d{3,4})\s*([A-Z]{1,3})$") {
                $newPl = "$($matches[1]) $($matches[2]) $($matches[3])"
            } else {
                $newPl = $rawPl
            }
        }
    }

    # Extract Serial Number
    if ([string]::IsNullOrWhiteSpace($newSn)) {
        if ($oldId -match "SN\s*:?\s*([A-Z0-9]+)") {
            $newSn = $matches[1]
        } elseif ($oldId -match "(DBCH\s*\d+|SYW\d+|C\d{5}|961\d{9})") {
            $newSn = $matches[1]
        }
    }

    # Extract Alias Code
    if ([string]::IsNullOrWhiteSpace($newAlias)) {
        if ($oldId -match "(DT-\d{5}|BRA-\d{2})") {
            $newAlias = $matches[1]
        }
    }

    # Extract Clean Primary Asset ID
    if ($oldId -match "^([A-Z]{2,3}-\d{5})") {
        $cleanId = $matches[1]
    } elseif ($oldId -match "^([A-Z]{2,3}-\d{2,4})") {
        $cleanId = $matches[1]
    } elseif ($oldId -match "^(HE-6\.\d{3})") {
        $cleanId = $matches[1]
    } elseif ($oldId -match "^(HILUX)") {
        $cleanId = "LV-HILUX-01"
    }

    $item = @{
        OldId        = $oldId
        CleanId      = $cleanId
        Code         = $cleanId
        LicensePlate = $newPl
        SerialNumber = $newSn
        AliasName    = $newAlias
        Type         = $oldType
        Category     = $oldCat
        MakeModel    = $newModel
        SubGroup     = $oldSub
        Year         = $oldYear
    }

    if (-not $groups.ContainsKey($cleanId)) {
        $groups[$cleanId] = @()
    }
    $groups[$cleanId] += $item
}

Write-Host "[2/4] Resolving $($groups.Count) asset groups and generating SQL statements..." -ForegroundColor Cyan

$sqlStatements = @()
$sqlStatements += "SET FOREIGN_KEY_CHECKS = 0;"

foreach ($cleanId in $groups.Keys) {
    $groupItems = $groups[$cleanId]

    # Find if primary record (where OldId == CleanId) exists
    $primaryItem = $groupItems | Where-Object { $_.OldId -eq $cleanId } | Select-Object -First 1
    
    if (-not $primaryItem) {
        # Pick first item as primary and rename its ID to CleanId
        $primaryItem = $groupItems[0]
        $oldIdEsc = $primaryItem.OldId.Replace("'", "''")
        $cleanIdEsc = $cleanId.Replace("'", "''")
        $codeEsc = $cleanIdEsc
        $plEsc = $primaryItem.LicensePlate.Replace("'", "''")
        $snEsc = $primaryItem.SerialNumber.Replace("'", "''")
        $aliasEsc = $primaryItem.AliasName.Replace("'", "''")
        $modelEsc = $primaryItem.MakeModel.Replace("'", "''")
        $subEsc = $primaryItem.SubGroup.Replace("'", "''")

        # Update dependent tables
        $sqlStatements += "UPDATE work_orders SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE inspections SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE fuel_logs SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE tire_inspections SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE pm_plans SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
        $sqlStatements += "UPDATE asset_movements SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
        
        $sqlStatements += "UPDATE assets SET asset_id = '$cleanIdEsc', asset_code = '$codeEsc', license_plate = NULLIF('$plEsc', ''), serial_number = NULLIF('$snEsc', ''), alias_name = NULLIF('$aliasEsc', ''), make_model = NULLIF('$modelEsc', ''), sub_group_branch = '$subEsc', year_manufacture = $($primaryItem.Year) WHERE asset_id = '$oldIdEsc';"
    } else {
        # Update existing primary item with autofilled attributes
        $cleanIdEsc = $cleanId.Replace("'", "''")
        $plEsc = $primaryItem.LicensePlate.Replace("'", "''")
        $snEsc = $primaryItem.SerialNumber.Replace("'", "''")
        $aliasEsc = $primaryItem.AliasName.Replace("'", "''")
        $modelEsc = $primaryItem.MakeModel.Replace("'", "''")
        $subEsc = $primaryItem.SubGroup.Replace("'", "''")

        $sqlStatements += "UPDATE assets SET license_plate = COALESCE(license_plate, NULLIF('$plEsc', '')), serial_number = COALESCE(serial_number, NULLIF('$snEsc', '')), alias_name = COALESCE(alias_name, NULLIF('$aliasEsc', '')), make_model = COALESCE(make_model, NULLIF('$modelEsc', '')), sub_group_branch = '$subEsc', year_manufacture = $($primaryItem.Year) WHERE asset_id = '$cleanIdEsc';"
    }

    # Merge remaining duplicate items in the group
    foreach ($item in $groupItems) {
        if ($item.OldId -ne $primaryItem.OldId -and $item.OldId -ne $cleanId) {
            $oldIdEsc = $item.OldId.Replace("'", "''")
            $cleanIdEsc = $cleanId.Replace("'", "''")
            $plEsc = $item.LicensePlate.Replace("'", "''")
            $snEsc = $item.SerialNumber.Replace("'", "''")
            $aliasEsc = $item.AliasName.Replace("'", "''")
            $modelEsc = $item.MakeModel.Replace("'", "''")

            Write-Host " [Merging Duplicate] '$oldIdEsc' => '$cleanIdEsc'" -ForegroundColor Yellow

            $sqlStatements += "UPDATE work_orders SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
            $sqlStatements += "UPDATE inspections SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
            $sqlStatements += "UPDATE fuel_logs SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
            $sqlStatements += "UPDATE tire_inspections SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
            $sqlStatements += "UPDATE pm_plans SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
            $sqlStatements += "UPDATE asset_movements SET asset_id = '$cleanIdEsc' WHERE asset_id = '$oldIdEsc';"
            
            $sqlStatements += "UPDATE assets SET license_plate = COALESCE(license_plate, NULLIF('$plEsc', '')), serial_number = COALESCE(serial_number, NULLIF('$snEsc', '')), alias_name = COALESCE(alias_name, NULLIF('$aliasEsc', '')), make_model = COALESCE(make_model, NULLIF('$modelEsc', '')) WHERE asset_id = '$cleanIdEsc';"
            $sqlStatements += "DELETE FROM assets WHERE asset_id = '$oldIdEsc';"
        }
    }
}

$sqlStatements += "SET FOREIGN_KEY_CHECKS = 1;"

Write-Host "[3/4] Executing batch update & merge on MySQL database $dbName..." -ForegroundColor Cyan
$tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
[System.IO.File]::WriteAllText($tempFile, "USE $dbName; `n" + ($sqlStatements -join "`n"), [System.Text.Encoding]::UTF8)

Get-Content $tempFile | & $mysqlExe -u root $dbName
Remove-Item $tempFile -Force

Write-Host "[4/4] Re-exporting clean database dump to $sqlFile..." -ForegroundColor Cyan
& $mysqldumpExe -u root --routines --triggers --events $dbName > $sqlFile

Write-Host "Group-based sanitization complete! Cleaned $($groups.Count) asset groups." -ForegroundColor Green
