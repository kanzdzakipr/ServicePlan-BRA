# ============================================================================
# ServicePlan-BRA: Audit All Tables for Orphaned & Messy References
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$dbName = "u646470441_ServicePlanBRA"

Write-Host "[1] Checking for orphaned asset_id references in dependent tables..." -ForegroundColor Cyan

$tables = @("work_orders", "inspections", "fuel_logs", "tire_inspections", "pm_plans", "asset_movements")

foreach ($t in $tables) {
    $sql = "USE $dbName; SELECT DISTINCT asset_id FROM $t WHERE asset_id NOT IN (SELECT asset_id FROM assets);"
    $raw = & $mysqlExe -u root -e $sql
    $lines = $raw -split "`r?`n"
    $orphans = $lines[1..($lines.Count - 1)] | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
    
    if ($orphans.Count -gt 0) {
        Write-Host " [!] Found $($orphans.Count) orphaned asset_id in table '$t':" -ForegroundColor Red
        foreach ($o in $orphans) {
            Write-Host "     -> '$o'" -ForegroundColor Yellow
        }
    } else {
        Write-Host " [x] Table '$t': All asset_id references match clean master assets!" -ForegroundColor Green
    }
}

Write-Host "`n[2] Checking work_orders table for messy HTML tags / typos in issue_description..." -ForegroundColor Cyan
$sqlWo = "USE $dbName; SELECT wo_id, asset_id, issue_description FROM work_orders WHERE issue_description LIKE '%<br%' OR issue_description LIKE '%&amp;%' OR issue_description LIKE '%&quot;%' OR issue_description LIKE '%SOAK%' OR issue_description LIKE '%BATTERAY%';"
$rawWo = & $mysqlExe -u root -e $sqlWo
$linesWo = $rawWo -split "`r?`n"
$woMessy = $linesWo[1..($linesWo.Count - 1)] | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }

Write-Host "Found $($woMessy.Count) work order descriptions needing text sanitization:" -ForegroundColor Yellow
foreach ($w in $woMessy) {
    Write-Host "  -> $w"
}
