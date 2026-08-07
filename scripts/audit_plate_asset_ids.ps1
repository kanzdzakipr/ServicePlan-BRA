# ============================================================================
# ServicePlan-BRA: Audit Asset IDs containing License Plate / Raw Text
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$dbName = "u646470441_ServicePlanBRA"

$rawOutput = & $mysqlExe -u root -e "USE $dbName; SELECT asset_id, asset_code, license_plate, category, make_model FROM assets;"
$lines = $rawOutput -split "`r?`n"

Write-Host "Auditing $(($lines.Count - 1)) asset records..." -ForegroundColor Cyan

$nonStandard = @()

foreach ($line in $lines[1..($lines.Count-1)]) {
    if ([string]::IsNullOrWhiteSpace($line)) { continue }
    $cols = $line -split "`t"
    if ($cols.Count -lt 5) { continue }

    $id   = $cols[0].Trim()
    $code = $cols[1].Trim()
    $pl   = $cols[2].Trim()
    $cat  = $cols[3].Trim()
    $mod  = $cols[4].Trim()

    # Standard pattern: DT-00001, EX-00001, DZ-00001, MG-00001, PF-00001, SD-00001, WT-00001, PM-00001, SL-01, LB-00001, LV-HILUX-01, HE-6.001
    if ($id -notmatch "^[A-Z]{2,3}-\d{2,5}$" -and $id -notmatch "^HE-6\.\d{3}$" -and $id -notmatch "^LV-") {
        $nonStandard += @{ Id = $id; Code = $code; LicensePlate = $pl; Category = $cat; Model = $mod }
    }
}

Write-Host "Found $($nonStandard.Count) non-standard asset IDs:" -ForegroundColor Yellow
foreach ($item in $nonStandard) {
    Write-Host "  ID: '$($item.Id)' | Code: '$($item.Code)' | Plate: '$($item.LicensePlate)' | Cat: '$($item.Category)'"
}
