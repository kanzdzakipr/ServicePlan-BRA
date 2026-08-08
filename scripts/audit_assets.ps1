$sqlPath = 'scripts/u646470441_ServicePlanBRA.sql'
$rawText = [System.IO.File]::ReadAllText($sqlPath, [System.Text.Encoding]::UTF8)

$lines = $rawText -split "`n"
$assetLines = $lines | Where-Object { $_ -like "*INSERT INTO*assets*" }

Write-Host "INSERT INTO assets lines found: $($assetLines.Count)"

$assetIds = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)

foreach ($l in $assetLines) {
    $tokens = [regex]::Matches($l, "'([^']*)'")
    for ($i = 0; $i -lt $tokens.Count; $i++) {
        $val = $tokens[$i].Groups[1].Value
        if ($val -match '^[A-Z0-9]{2,5}-\d{1,5}$') {
            [void]$assetIds.Add($val)
        }
    }
}

Write-Host "Database Unique Unit Codes in SQL: $($assetIds.Count)"

$mdFiles = Get-ChildItem -Path "material" -Recurse -Filter "*.md"
$materialUnits = @{}

foreach ($f in $mdFiles) {
    $txt = [System.IO.File]::ReadAllText($f.FullName, [System.Text.Encoding]::UTF8)
    $m = [regex]::Matches($txt, '\b([A-Z]{2,5}-\d{2,5})\b')
    foreach ($match in $m) {
        $code = $match.Value.ToUpper()
        if (-not $materialUnits.ContainsKey($code)) {
            $materialUnits[$code] = $f.Name
        }
    }
}

Write-Host "Total unique unit codes in Material: $($materialUnits.Count)"

$missing = @()
foreach ($k in $materialUnits.Keys | Sort-Object) {
    if (-not $assetIds.Contains($k)) {
        $missing += $k
    }
}

Write-Host "Missing Units in Database ($($missing.Count)): "
$missing -join ", " | Write-Host
