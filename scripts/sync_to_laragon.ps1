# ============================================================================
# ServicePlan-BRA: Synchronization & Local Laragon Hosting Script
# Target Local Path: C:\laragon\www\ServicePlan-BRA1
# Database Target: u646470441_ServicePlanBRA (Local MySQL Laragon)
# ============================================================================

$srcDir = "c:\Users\DerpyPotatoes8\Downloads\vscode\widya\ServicePlan-BRA"
$destDir = "C:\laragon\www\ServicePlan-BRA1"
$sqlPath = Join-Path $srcDir "scripts\u646470441_ServicePlanBRA.sql"
$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

Write-Host "[1/3] Synchronizing files to Laragon webroot ($destDir)..." -ForegroundColor Cyan
robocopy $srcDir $destDir /MIR /XD .git .gemini brain scratch /R:2 /W:1 | Out-Null

Write-Host "[2/3] Verifying & preparing MySQL database 'u646470441_ServicePlanBRA'..." -ForegroundColor Cyan
if (Test-Path $mysqlExe) {
    & $mysqlExe -u root -e "DROP DATABASE IF EXISTS u646470441_ServicePlanBRA; CREATE DATABASE u646470441_ServicePlanBRA;"
    if (Test-Path $sqlPath) {
        Write-Host "[3/3] Importing SQL schema & data into local MySQL..." -ForegroundColor Green
        Get-Content $sqlPath | & $mysqlExe -u root u646470441_ServicePlanBRA
        Write-Host "Database successfully synced to local MySQL!" -ForegroundColor Green
    }
} else {
    Write-Host "[!] MySQL executable not found at $mysqlExe" -ForegroundColor Yellow
}

Write-Host "Synchronization finished! Access local hosting at: http://localhost/ServicePlan-BRA1/dashboard.html" -ForegroundColor Green
