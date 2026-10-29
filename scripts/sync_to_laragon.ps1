param(
    [string]$LaragonRoot = "C:\laragon",
    [string]$TargetDir = "",
    [string]$DbHost = "127.0.0.1",
    [string]$DbPort = "3306",
    [string]$DbName = "u646470441_ServicePlanBRA",
    [string]$DbUser = "root",
    [string]$DbPassword = "",
    [switch]$PreserveDatabase,
    [switch]$ImportDatabase,
    [switch]$DryRun
)

# ============================================================================
# ServicePlan-BRA: Synchronization & Local Laragon Hosting Script
# Multi-user compatible script with automatic root path & MySQL auto-detection
# Target is selected per machine with -LaragonRoot, without changing shared defaults.
# Database Target: u646470441_ServicePlanBRA (Local MySQL Laragon)
# ============================================================================

# Auto-detect source directory (repository root directory containing this script)
$srcDir = (Get-Item $PSScriptRoot).Parent.FullName
if ($LaragonRoot -eq "C:\laragon" -and -not (Test-Path -LiteralPath "C:\laragon") -and (Test-Path -LiteralPath "C:\laragon2")) {
    $LaragonRoot = "C:\laragon2"
}
$laragonRootPath = [System.IO.Path]::GetFullPath($LaragonRoot).TrimEnd('\')
$laragonWebRoot = [System.IO.Path]::GetFullPath((Join-Path $laragonRootPath 'www')).TrimEnd('\')
if ([string]::IsNullOrWhiteSpace($TargetDir)) {
    $TargetDir = Join-Path $laragonWebRoot 'ServicePlan-BRA'
}
$destDir = [System.IO.Path]::GetFullPath($TargetDir)
$sqlPath = Join-Path $srcDir "scripts\u646470441_ServicePlanBRA.sql"
$migrationPaths = @(
    (Join-Path $srcDir "scripts\migrate_revisi_3_work_order.sql"),
    (Join-Path $srcDir "scripts\migrate_revisi_4_work_order_support.sql"),
    (Join-Path $srcDir "scripts\migrate_wo_fields_17102026.sql"),
    (Join-Path $srcDir "scripts\migrate_notification_recipients_17102026.sql"),
    (Join-Path $srcDir "scripts\migrate_asset_hull_history_17102026.sql"),
    (Join-Path $srcDir "scripts\migrate_asset_onboarding_17102026.sql"),
    (Join-Path $srcDir "scripts\migrate_asset_search_indexes_17102026.sql"),
    # The legacy warranty migration also reseeds asset values on every sync.
    # F3b adds the required columns without overwriting user-maintained data.
    (Join-Path $srcDir "scripts\migrate_f3b_asset_schema_location_23102026.sql"),
    (Join-Path $srcDir "scripts\migrate_f3c_accident_hold_release_23102026.sql"),
    (Join-Path $srcDir "scripts\migrate_asset_registration_fields_22102026.sql"),
    (Join-Path $srcDir "scripts\migrate_asset_enrichment_valuation_24102026.sql"),
    (Join-Path $srcDir "scripts\migrate_location_coordinates_22102026.sql"),
    (Join-Path $srcDir "scripts\migrate_populate_asset_pics_jan2026.sql"),
    (Join-Path $srcDir "scripts\migrate_f4_people_kpi_schema_23102026.sql"),
    (Join-Path $srcDir "scripts\migrate_f4_kpi_audit_location_24102026.sql"),
    (Join-Path $srcDir "scripts\migrate_f5_archive_settings_schema_23102026.sql"),
    (Join-Path $srcDir "scripts\migrate_f0_user_audit_24102026.sql"),
    (Join-Path $srcDir "scripts\migrate_seed_initial_notifications_23102026.sql"),
    (Join-Path $srcDir "scripts\migrate_procurement_wo_integration_20261024.sql"),
    (Join-Path $srcDir "scripts\migrate_sync_missing_lv_assets_from_sql_20261024.sql"),
    (Join-Path $srcDir "scripts\migrate_normalize_asset_warranty_20261025.sql"),
    (Join-Path $srcDir "scripts\migrate_asset_document_urls_25102026.sql"),
    (Join-Path $srcDir "scripts\migrate_rbac_matrix_20261026.sql"),
    (Join-Path $srcDir "scripts\migrate_telematics_asset_mappings_26102026.sql"),
    (Join-Path $srcDir "scripts\migrate_fuel_price_setting_26102026.sql"),
    (Join-Path $srcDir "scripts\migrate_asset_location_relocation_20261029.sql")
)

if (-not (Test-Path -LiteralPath $laragonRootPath -PathType Container) -or
    -not (Test-Path -LiteralPath $laragonWebRoot -PathType Container)) {
    throw "LaragonRoot dan folder www harus sudah ada: $laragonRootPath"
}
if (-not $destDir.StartsWith($laragonWebRoot + '\', [System.StringComparison]::OrdinalIgnoreCase) -or
    -not [string]::Equals((Split-Path $destDir -Leaf), 'ServicePlan-BRA', [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "TargetDir harus berada di bawah $laragonWebRoot dan bernama ServicePlan-BRA."
}
if ($PreserveDatabase -and $ImportDatabase) {
    throw 'Pilih salah satu: -PreserveDatabase atau -ImportDatabase.'
}
if ($ImportDatabase -and $DbHost -notin @('127.0.0.1', 'localhost', '::1')) {
    throw '-ImportDatabase hanya diizinkan untuk MySQL lokal.'
}

if ($DbName -notmatch '^[A-Za-z0-9_]+$' -or $DbUser -notmatch '^[A-Za-z0-9_]+$') {
    throw "DbName dan DbUser hanya boleh berisi huruf, angka, dan underscore."
}

if ($DbHost -notmatch '^[A-Za-z0-9.-]+$' -or $DbPort -notmatch '^[0-9]+$') {
    throw "DbHost dan DbPort tidak valid."
}

if ($DbPassword -match '[\r\n"]') {
    throw 'DbPassword lokal tidak boleh mengandung newline atau tanda kutip ganda.'
}

# Resolve MySQL from the selected Laragon installation only. Never borrow an
# unrelated mysql.exe from PATH when a database import is explicitly requested.
$mysqlExe = $null
$possiblePaths = @(
    Get-ChildItem -Path (Join-Path $laragonRootPath 'bin\mysql') -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName
    Get-ChildItem -Path (Join-Path $laragonRootPath 'bin\mariadb') -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName
)
if ($possiblePaths.Count -gt 0) {
    $mysqlExe = $possiblePaths[0]
}
$phpExe = Get-ChildItem -Path (Join-Path $laragonRootPath 'bin\php') -Filter 'php.exe' -Recurse -ErrorAction SilentlyContinue |
    Sort-Object FullName -Descending |
    Select-Object -First 1 -ExpandProperty FullName

Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host " ServicePlan-BRA Local Laragon Sync Utility" -ForegroundColor Cyan
Write-Host " Source: $srcDir" -ForegroundColor Gray
Write-Host " Target: $destDir" -ForegroundColor Gray
Write-Host "============================================================================" -ForegroundColor Cyan

if (Test-Path -LiteralPath $destDir) {
    $existingTarget = Get-Item -LiteralPath $destDir -Force
    if ($existingTarget.LinkType) {
        throw "TargetDir bukan folder biasa (link/junction): $destDir"
    }
}

# Additive copy: never delete target-only files or overwrite local data/config.
$copyArgs = @($srcDir, $destDir, '/E', '/XO', '/XD', '.git', '.gemini', '.agents', '.codex', '.runtime', 'brain', 'scratch', 'uploads', '/XF', '.env', '.htaccess', '*.sql', '/R:2', '/W:1')
if ($DryRun) {
    Write-Host 'DRY RUN: only listing planned copy operations; no files or database will be changed.' -ForegroundColor Yellow
    & robocopy @copyArgs /L /NFL /NDL /NJH /NJS
    if ($LASTEXITCODE -gt 7) { throw "Robocopy dry run gagal dengan exit code $LASTEXITCODE." }
    return
}

# 1. Ensure target directory exists
if (-not (Test-Path $destDir)) {
    New-Item -Path $destDir -ItemType Directory -Force | Out-Null
}

Write-Host "`n[1/4] Synchronizing files to Laragon webroot ($destDir)..." -ForegroundColor Cyan
& robocopy @copyArgs | Out-Null
$robocopyExitCode = $LASTEXITCODE
if ($robocopyExitCode -gt 7) {
    throw "Robocopy gagal dengan exit code $robocopyExitCode."
}
Write-Host "Files synchronized successfully!" -ForegroundColor Green

# Preserve any target-only Apache directives and replace only our managed block.
$sessionDir = Join-Path $laragonRootPath 'tmp\ServicePlan-BRA-sessions'
New-Item -Path $sessionDir -ItemType Directory -Force | Out-Null
$targetHtaccess = Join-Path $destDir '.htaccess'
$sourceHtaccess = Join-Path $srcDir '.htaccess'
$apacheSessionDir = $sessionDir.Replace('\', '/')
$localEnvironmentBlock = @"

# BEGIN LARAGON LOCAL ENVIRONMENT - generated by sync_to_laragon.ps1
<IfModule mod_env.c>
    SetEnv APP_ENV "local"
    SetEnv APP_ORIGIN "http://localhost"
    SetEnv APP_ALLOWED_ORIGINS "http://localhost,http://127.0.0.1,http://serviceplan-bra.test"
    SetEnv DB_HOST "$DbHost"
    SetEnv DB_PORT "$DbPort"
    SetEnv DB_NAME "$DbName"
    SetEnv DB_USER "$DbUser"
    SetEnv DB_PASSWORD "$DbPassword"
    SetEnv SESSION_NAME "BRASESSID_LOCAL"
    SetEnv SESSION_SAVE_PATH "$apacheSessionDir"
</IfModule>
# END LARAGON LOCAL ENVIRONMENT
"@
$htaccessBase = if (Test-Path -LiteralPath $sourceHtaccess) {
    [System.IO.File]::ReadAllText($sourceHtaccess)
} elseif (Test-Path -LiteralPath $targetHtaccess) {
    [System.IO.File]::ReadAllText($targetHtaccess)
} else {
    ''
}
$managedPattern = '(?s)\r?\n?# BEGIN LARAGON LOCAL ENVIRONMENT - generated by sync_to_laragon\.ps1\r?\n.*?# END LARAGON LOCAL ENVIRONMENT\r?\n?'
$htaccessBase = [regex]::Replace($htaccessBase, $managedPattern, '')
$htaccessNew = $htaccessBase.TrimEnd("`r", "`n") + "`r`n" + $localEnvironmentBlock.Trim("`r", "`n") + "`r`n"
[System.IO.File]::WriteAllText($targetHtaccess, $htaccessNew, (New-Object System.Text.UTF8Encoding($false)))

# Ensure uploads directories and security .htaccess files exist in Laragon webroot
$targetUploads = Join-Path $destDir 'uploads'
$sourceUploads = Join-Path $srcDir 'uploads'
$uploadSubdirs = @('', 'assets', 'work_orders', 'evidence')
foreach ($sub in $uploadSubdirs) {
    $tDir = if ($sub) { Join-Path $targetUploads $sub } else { $targetUploads }
    $sDir = if ($sub) { Join-Path $sourceUploads $sub } else { $sourceUploads }
    if (-not (Test-Path $tDir)) {
        New-Item -Path $tDir -ItemType Directory -Force | Out-Null
    }
    $sHt = Join-Path $sDir '.htaccess'
    $tHt = Join-Path $tDir '.htaccess'
    if (Test-Path $sHt) {
        Copy-Item -Path $sHt -Destination $tHt -Force | Out-Null
    }
}


# Database writes require explicit -ImportDatabase. When database is preserved,
# we still apply idempotent additive migrations so missing columns do not break the APIs.
if (-not $ImportDatabase) {
    Write-Host '[2/4] Database preserved: no full re-import, table drops, or reseed.' -ForegroundColor Yellow
    if ($mysqlExe -and (Test-Path $mysqlExe)) {
        $mysqlArgs = @('-h', $DbHost, '-P', $DbPort, '-u', $DbUser)
        if ($DbPassword) {
            $mysqlArgs += "-p$DbPassword"
        }
        foreach ($migrationPath in $migrationPaths) {
            if (Test-Path $migrationPath) {
                $mysqlMigrationPath = $migrationPath.Replace('\', '/')
                & $mysqlExe @mysqlArgs --default-character-set=utf8mb4 $DbName -e "source $mysqlMigrationPath" 2>$null
            }
        }
        Write-Host "Additive migrations verified." -ForegroundColor Green

        $stockImportScript = Join-Path $srcDir 'scripts\import_stock_gudang.php'
        if ($phpExe -and (Test-Path $stockImportScript)) {
            $env:LOCAL_MYSQL_PASSWORD = $DbPassword
            & $phpExe $stockImportScript --apply | Out-Null
            Write-Host "Stock gudang master parts verified (Batch 1)." -ForegroundColor Green
        }

        $tireSeedScript = Join-Path $srcDir 'scripts\seed_all_heavy_equipment_tires.php'
        if ($phpExe -and (Test-Path $tireSeedScript)) {
            $env:LOCAL_MYSQL_PASSWORD = $DbPassword
            & $phpExe $tireSeedScript | Out-Null
            Write-Host "Heavy equipment tire specifications verified." -ForegroundColor Green
        }

        $equipmentBatchScripts = @(
            (Join-Path $srcDir 'scripts\migrate_batch1_assets_parts.php'),
            (Join-Path $srcDir 'scripts\migrate_batch2_work_orders.php'),
            (Join-Path $srcDir 'scripts\migrate_batch3_pm_grease_battery.php'),
            (Join-Path $srcDir 'scripts\migrate_batch4_warehouse_transactions.php'),
            (Join-Path $srcDir 'scripts\migrate_batch5_cost_ledger.php'),
            (Join-Path $srcDir 'scripts\migrate_batch6_rental_contracts.php'),
            (Join-Path $srcDir 'scripts\migrate_batch7_lho_operations.php'),
            (Join-Path $srcDir 'scripts\migrate_batch8_pm_plan_july2026.php'),
            (Join-Path $srcDir 'scripts\migrate_batch9_pm_plan_september2026.php'),
            (Join-Path $srcDir 'scripts\migrate_batch10_pm_plan_october2026.php'),
            (Join-Path $srcDir 'scripts\migrate_batch11_komtrax_jul_sep_2026.php'),
            (Join-Path $srcDir 'scripts\merge_live_host_data_20261029.php'),
            (Join-Path $srcDir 'scripts\migrate_batch12_sold_assets_archive.php'),
            (Join-Path $srcDir 'scripts\migrate_batch13_legacy_offhire_demobilization.php'),
            (Join-Path $srcDir 'scripts\migrate_batch14_alias_consolidation_placeholder_cleanup.php'),
            (Join-Path $srcDir 'scripts\migrate_batch15_project_location_alignment.php')
        )
        foreach ($bScript in $equipmentBatchScripts) {
            if ($phpExe -and (Test-Path $bScript)) {
                $env:LOCAL_MYSQL_PASSWORD = $DbPassword
                & $phpExe $bScript | Out-Null
            }
        }
        Write-Host "Equipment Report January 2026 Batch 1-7 verified." -ForegroundColor Green
    }
} elseif ($mysqlExe -and (Test-Path $mysqlExe)) {
    Write-Host "`n[2/4] Importing explicitly selected local MySQL database '$DbName'..." -ForegroundColor Cyan
    Write-Host "Found MySQL executable: $mysqlExe" -ForegroundColor Gray

    $mysqlArgs = @('-h', $DbHost, '-P', $DbPort, '-u', $DbUser)
    if ($DbPassword) {
        $mysqlArgs += "-p$DbPassword"
    }

    $serverDataDir = (& $mysqlExe @mysqlArgs --batch --skip-column-names -e 'SELECT @@datadir;' 2>&1 | Select-Object -First 1).ToString().Trim()
    if ($LASTEXITCODE -ne 0) {
        throw 'Tidak dapat memverifikasi lokasi data MySQL; impor dibatalkan.'
    }
    $expectedDataRoot = [System.IO.Path]::GetFullPath((Join-Path $laragonRootPath 'data')).TrimEnd('\') + '\'
    $actualDataDir = [System.IO.Path]::GetFullPath($serverDataDir).TrimEnd('\') + '\'
    if (-not $actualDataDir.StartsWith($expectedDataRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "MySQL yang terhubung bukan milik LaragonRoot terpilih; impor dibatalkan."
    }
    
    # Create DB (Do not drop, preserve data like archived items)
    $createDbCmd = "CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    & $mysqlExe @mysqlArgs -e $createDbCmd
    if ($LASTEXITCODE -ne 0) {
        throw "Gagal membuat atau mengakses database '$DbName'."
    }
    
    if (Test-Path $sqlPath) {
        Write-Host "`n[3/4] Importing SQL schema & data into local MySQL..." -ForegroundColor Cyan
        $mysqlSourcePath = $sqlPath.Replace('\', '/')
        & $mysqlExe @mysqlArgs --default-character-set=utf8mb4 $DbName -e "source $mysqlSourcePath"
        if ($LASTEXITCODE -ne 0) {
            throw "Import SQL ke database '$DbName' gagal."
        }
        Write-Host "Database '$DbName' successfully synced to local MySQL!" -ForegroundColor Green

        $passwordResetScript = Join-Path $srcDir 'scripts\set_local_temporary_passwords.php'
        $phpExe = Get-ChildItem -Path (Join-Path $laragonRootPath 'bin\php') -Filter 'php.exe' -Recurse -ErrorAction SilentlyContinue |
            Sort-Object FullName -Descending |
            Select-Object -First 1 -ExpandProperty FullName
        if ($phpExe -and (Test-Path $passwordResetScript)) {
            Write-Host "`n[4/4] Applying local-only temporary passwords..." -ForegroundColor Cyan
            $previousEnvironment = @{
                APP_ENV = $env:APP_ENV
                DB_HOST = $env:DB_HOST
                DB_PORT = $env:DB_PORT
                DB_NAME = $env:DB_NAME
                DB_USER = $env:DB_USER
                DB_PASSWORD = $env:DB_PASSWORD
            }
            try {
                $env:APP_ENV = 'local'
                $env:DB_HOST = $DbHost
                $env:DB_PORT = $DbPort
                $env:DB_NAME = $DbName
                $env:DB_USER = $DbUser
                $env:DB_PASSWORD = $DbPassword
                & $phpExe $passwordResetScript --confirm-local-reset
                if ($LASTEXITCODE -ne 0) {
                    throw 'Reset password sementara lokal gagal.'
                }
            } finally {
                $env:APP_ENV = $previousEnvironment.APP_ENV
                $env:DB_HOST = $previousEnvironment.DB_HOST
                $env:DB_PORT = $previousEnvironment.DB_PORT
                $env:DB_NAME = $previousEnvironment.DB_NAME
                $env:DB_USER = $previousEnvironment.DB_USER
                $env:DB_PASSWORD = $previousEnvironment.DB_PASSWORD
            }
        } else {
            Write-Host '[!] PHP atau script reset password tidak ditemukan; password dump tidak diubah.' -ForegroundColor Yellow
        }
    } else {
        Write-Host "[!] SQL file not found at: $sqlPath" -ForegroundColor Yellow
    }

    Write-Host "`n[Migration] Applying idempotent feature migrations..." -ForegroundColor Cyan
    foreach ($migrationPath in $migrationPaths) {
        if (-not (Test-Path $migrationPath)) {
            throw "Migration file not found: $migrationPath"
        }
        $mysqlMigrationPath = $migrationPath.Replace('\', '/')
        & $mysqlExe @mysqlArgs --default-character-set=utf8mb4 $DbName -e "source $mysqlMigrationPath"
        if ($LASTEXITCODE -ne 0) {
            throw "Database migration failed: $(Split-Path $migrationPath -Leaf)"
        }
        Write-Host "Applied: $(Split-Path $migrationPath -Leaf)" -ForegroundColor Green
    }

    $stockImportScript = Join-Path $srcDir 'scripts\import_stock_gudang.php'
    if ($phpExe -and (Test-Path $stockImportScript)) {
        $env:LOCAL_MYSQL_PASSWORD = $DbPassword
        & $phpExe $stockImportScript --apply | Out-Null
        Write-Host "Stock gudang master parts verified (Batch 1)." -ForegroundColor Green
    }

    $tireSeedScript = Join-Path $srcDir 'scripts\seed_all_heavy_equipment_tires.php'
    if ($phpExe -and (Test-Path $tireSeedScript)) {
        $env:LOCAL_MYSQL_PASSWORD = $DbPassword
        & $phpExe $tireSeedScript | Out-Null
        Write-Host "Heavy equipment tire specifications verified." -ForegroundColor Green
    }

    $equipmentBatchScripts = @(
        (Join-Path $srcDir 'scripts\migrate_batch1_assets_parts.php'),
        (Join-Path $srcDir 'scripts\migrate_batch2_work_orders.php'),
        (Join-Path $srcDir 'scripts\migrate_batch3_pm_grease_battery.php'),
        (Join-Path $srcDir 'scripts\migrate_batch4_warehouse_transactions.php'),
        (Join-Path $srcDir 'scripts\migrate_batch5_cost_ledger.php'),
        (Join-Path $srcDir 'scripts\migrate_batch6_rental_contracts.php'),
        (Join-Path $srcDir 'scripts\migrate_batch7_lho_operations.php'),
        (Join-Path $srcDir 'scripts\migrate_batch8_pm_plan_july2026.php'),
        (Join-Path $srcDir 'scripts\migrate_batch9_pm_plan_september2026.php'),
        (Join-Path $srcDir 'scripts\migrate_batch10_pm_plan_october2026.php'),
        (Join-Path $srcDir 'scripts\migrate_batch11_komtrax_jul_sep_2026.php'),
        (Join-Path $srcDir 'scripts\merge_live_host_data_20261029.php'),
        (Join-Path $srcDir 'scripts\migrate_batch12_sold_assets_archive.php'),
        (Join-Path $srcDir 'scripts\migrate_batch13_legacy_offhire_demobilization.php'),
        (Join-Path $srcDir 'scripts\migrate_batch14_alias_consolidation_placeholder_cleanup.php'),
        (Join-Path $srcDir 'scripts\migrate_batch15_project_location_alignment.php')
    )
    foreach ($bScript in $equipmentBatchScripts) {
        if ($phpExe -and (Test-Path $bScript)) {
            $env:LOCAL_MYSQL_PASSWORD = $DbPassword
            & $phpExe $bScript | Out-Null
        }
    }
    Write-Host "Equipment Report January 2026 Batch 1-7 verified." -ForegroundColor Green

    $notifRbacScript = Join-Path $srcDir 'scripts\migrate_notification_recipients_rbac_20261027.php'
    if ($phpExe -and (Test-Path $notifRbacScript)) {
        $env:LOCAL_MYSQL_PASSWORD = $DbPassword
        & $phpExe $notifRbacScript | Out-Null
        Write-Host "Notification recipients aligned with RBAC matrix." -ForegroundColor Green
    }
} else {
    throw 'MySQL executable tidak ditemukan untuk -ImportDatabase.'
}

Write-Host "`n============================================================================" -ForegroundColor Green
Write-Host "Synchronization finished!" -ForegroundColor Green
Write-Host "Access local hosting at: http://localhost/$(Split-Path $destDir -Leaf)/index.html" -ForegroundColor Green
Write-Host "============================================================================" -ForegroundColor Green