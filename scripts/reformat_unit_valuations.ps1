# ============================================================================
# ServicePlan-BRA: Reformat & Sanitize unit_valuations Table
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$mysqldumpExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$dbName = "u646470441_ServicePlanBRA"
$sqlFile = "scripts\u646470441_ServicePlanBRA.sql"

Write-Host "[1/3] Reformatting unit_valuations table records..." -ForegroundColor Cyan

$cleanRows = @(
    @{ Id=1; AssetId='DZ-00002'; UnitName='Bulldozer Caterpillar D6G 2XL'; Alias='DZ-00002'; Repair=421415277.00; Purchase=1200000000.00; Book=650000000.00; Min=450000000.00; Max=520000000.00; Str='Rp 450.000.000 - Rp 520.000.000' },
    @{ Id=2; AssetId='DT-00049'; UnitName='Dump Truck Hino Ranger FM 280 JD'; Alias='DT-00049'; Repair=185000000.00; Purchase=850000000.00; Book=480000000.00; Min=400000000.00; Max=580000000.00; Str='Rp 400.000.000 - Rp 580.000.000' },
    @{ Id=3; AssetId='DZ-00006'; UnitName='Bulldozer Caterpillar D6R'; Alias='DZ-00006'; Repair=421415277.00; Purchase=600000000.00; Book=360000000.00; Min=450000000.00; Max=520000000.00; Str='Rp 450.000.000 - Rp 520.000.000' },
    @{ Id=4; AssetId='DZ-00004'; UnitName='Bulldozer Komatsu D85ESS-2'; Alias='DZ-00004'; Repair=403684851.00; Purchase=1220000000.00; Book=854000000.00; Min=750000000.00; Max=900000000.00; Str='Rp 750.000.000 - Rp 900.000.000' },
    @{ Id=5; AssetId='EXC-00016'; UnitName='Excavator Caterpillar 305.5E2'; Alias='EXC-00016'; Repair=35606864.00; Purchase=841380000.00; Book=588966000.00; Min=420000000.00; Max=480000000.00; Str='Rp 420.000.000 - Rp 480.000.000' },
    @{ Id=6; AssetId='EXC-00017'; UnitName='Excavator Caterpillar 305.5E2'; Alias='EXC-00017'; Repair=66808545.00; Purchase=841380000.00; Book=588966000.00; Min=420000000.00; Max=480000000.00; Str='Rp 420.000.000 - Rp 480.000.000' },
    @{ Id=7; AssetId='DZ-00007'; UnitName='Bulldozer Caterpillar D7G2'; Alias='DZ-00007'; Repair=286553522.00; Purchase=800000000.00; Book=480000000.00; Min=420000000.00; Max=500000000.00; Str='Rp 420.000.000 - Rp 500.000.000' },
    @{ Id=8; AssetId='DZ-00001'; UnitName='Bulldozer Caterpillar D6G'; Alias='DZ-00001'; Repair=579012317.00; Purchase=415000000.00; Book=290500000.00; Min=400000000.00; Max=550000000.00; Str='Rp 400.000.000 - Rp 550.000.000' },
    @{ Id=9; AssetId='EXC-00004'; UnitName='Excavator Caterpillar 320D'; Alias='EXC-00004'; Repair=456208914.00; Purchase=370000000.00; Book=185000000.00; Min=400000000.00; Max=580000000.00; Str='Rp 400.000.000 - Rp 580.000.000' }
)

$sqlStatements = @(
    "TRUNCATE TABLE unit_valuations;"
)

foreach ($r in $cleanRows) {
    $id = $r.Id
    $assetId = $r.AssetId.Replace("'", "''")
    $unitName = $r.UnitName.Replace("'", "''")
    $alias = $r.Alias.Replace("'", "''")
    $str = $r.Str.Replace("'", "''")

    $sqlStatements += "INSERT INTO unit_valuations (valuation_id, asset_id, unit_name, unit_code_alias, total_repair_cost, purchase_price, book_value, market_price_min, market_price_max, raw_market_price_str) VALUES ($id, '$assetId', '$unitName', '$alias', $($r.Repair), $($r.Purchase), $($r.Book), $($r.Min), $($r.Max), '$str');"
}

Write-Host "[2/3] Executing database update on MySQL $dbName..." -ForegroundColor Cyan
$tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($tempFile, "USE $dbName; `n" + ($sqlStatements -join "`n"), $utf8NoBom)

Get-Content $tempFile | & $mysqlExe -u root $dbName
Remove-Item $tempFile -Force

Write-Host "[3/3] Re-exporting clean database dump to $sqlFile..." -ForegroundColor Cyan
& $mysqldumpExe -u root --routines --triggers --events $dbName > $sqlFile

Write-Host "unit_valuations table reformatting completed!" -ForegroundColor Green
