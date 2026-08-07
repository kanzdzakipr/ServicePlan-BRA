# ============================================================================
# ServicePlan-BRA: Inspect unit_valuations Table
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$dbName = "u646470441_ServicePlanBRA"

Write-Host "[1] Showing columns of unit_valuations..." -ForegroundColor Cyan
& $mysqlExe -u root -e "USE $dbName; DESCRIBE unit_valuations;"

Write-Host "`n[2] Fetching data from unit_valuations..." -ForegroundColor Cyan
& $mysqlExe -u root -e "USE $dbName; SELECT * FROM unit_valuations;"
