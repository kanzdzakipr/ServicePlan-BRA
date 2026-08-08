$sqlPath = "scripts/u646470441_ServicePlanBRA.sql"
$lines = Get-Content $sqlPath

$tables = @()
$assetLines = @()

foreach ($line in $lines) {
    if ($line -match "CREATE TABLE `([^`]+)`") {
        $tables += $Matches[1]
    }
    if ($line -match "INSERT INTO `([^`]+)`") {
        if ($Matches[1] -eq "assets" -or $Matches[1] -eq "master_assets" -or $Matches[1] -eq "units") {
            $assetLines += $line
        }
    }
}

Write-Host "Tables found in SQL:"
$tables | Out-String | Write-Host

Write-Host "Asset INSERT lines found: $($assetLines.Count)"
if ($assetLines.Count -gt 0) {
    $assetLines[0].Substring(0, [Math]::Min(150, $assetLines[0].Length)) | Write-Host
}
