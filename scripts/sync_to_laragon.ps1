param(
    [string]$TargetDir = "C:\laragon\www\ServicePlan-BRA",
    [string]$DbName = "u646470441_ServicePlanBRA",
    [string]$DbUser = "root",
    [string]$DbPassword = ""
)

# ============================================================================
# ServicePlan-BRA: Synchronization & Local Laragon Hosting Script
# Multi-user compatible script with automatic root path & MySQL auto-detection
# Target Local Path: C:\laragon\www\ServicePlan-BRA
# Database Target: u646470441_ServicePlanBRA (Local MySQL Laragon)
# ============================================================================

# Auto-detect source directory (repository root directory containing this script)
$srcDir = (Get-Item $PSScriptRoot).Parent.FullName
$destDir = $TargetDir
$sqlPath = Join-Path $srcDir "scripts\u646470441_ServicePlanBRA.sql"

# Auto-detect MySQL / MariaDB executable path
$mysqlExe = $null

$cmdMysql = Get-Command mysql -ErrorAction SilentlyContinue
if ($cmdMysql) {
    $mysqlExe = $cmdMysql.Source
} else {
    $possiblePaths = @(
        Get-ChildItem -Path "C:\laragon\bin\mysql" -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName
        Get-ChildItem -Path "C:\laragon\bin\mariadb" -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName
    )
    if ($possiblePaths.Count -gt 0) {
        $mysqlExe = $possiblePaths[0]
    }
}

Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host " ServicePlan-BRA Local Laragon Sync Utility" -ForegroundColor Cyan
Write-Host " Source: $srcDir" -ForegroundColor Gray
Write-Host " Target: $destDir" -ForegroundColor Gray
Write-Host "============================================================================" -ForegroundColor Cyan

# 1. Ensure target directory exists
if (-not (Test-Path $destDir)) {
    New-Item -Path $destDir -ItemType Directory -Force | Out-Null
}

Write-Host "`n[1/3] Synchronizing files to Laragon webroot ($destDir)..." -ForegroundColor Cyan
robocopy $srcDir $destDir /MIR /XD .git .gemini brain scratch /R:2 /W:1 | Out-Null
Write-Host "Files synchronized successfully!" -ForegroundColor Green

# 2. Prepare MySQL database
Write-Host "`n[2/3] Verifying & preparing MySQL database '$DbName'..." -ForegroundColor Cyan
if ($mysqlExe -and (Test-Path $mysqlExe)) {
    Write-Host "Found MySQL executable: $mysqlExe" -ForegroundColor Gray
    
    # Create DB (Do not drop, preserve data like archived items)
    $createDbCmd = "CREATE DATABASE IF NOT EXISTS $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    if ($DbPassword) {
        & $mysqlExe -u $DbUser "-p$DbPassword" -e $createDbCmd
    } else {
        & $mysqlExe -u $DbUser -e $createDbCmd
    }
    
    if (Test-Path $sqlPath) {
        Write-Host "`n[3/3] Importing SQL schema & data into local MySQL..." -ForegroundColor Cyan
        if ($DbPassword) {
            Get-Content $sqlPath -Raw | & $mysqlExe -u $DbUser "-p$DbPassword" $DbName
        } else {
            Get-Content $sqlPath -Raw | & $mysqlExe -u $DbUser $DbName
        }
        Write-Host "Database '$DbName' successfully synced to local MySQL!" -ForegroundColor Green
    } else {
        Write-Host "[!] SQL file not found at: $sqlPath" -ForegroundColor Yellow
    }
} else {
    Write-Host "[!] MySQL executable not found. Make sure Laragon MySQL service is installed/running." -ForegroundColor Yellow
    Write-Host "Skipping database import step. Code files were still copied to webroot." -ForegroundColor Yellow
}

Write-Host "`n============================================================================" -ForegroundColor Green
Write-Host "Synchronization finished!" -ForegroundColor Green
Write-Host "Access local hosting at: http://localhost/$(Split-Path $destDir -Leaf)/index.html" -ForegroundColor Green
Write-Host "============================================================================" -ForegroundColor Green

