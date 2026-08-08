# PowerShell script to audit units in database vs material files
$sqlPath = 'scripts/u646470441_ServicePlanBRA.sql'
$rawText = [System.IO.File]::ReadAllText($sqlPath, [System.Text.Encoding]::UTF8)

# Extract lines for assets table
$assetLines = $rawText.Split("`n") | Where-Object { $_.StartsWith("INSERT INTO `assets`") -or $_.StartsWith("INSERT INTO assets") }

Write-Host "INSERT INTO assets lines found: $($assetLines.Count)"

$assetIds = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)
$assetList = @()

$pattern = "\('([^']+)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)'"

foreach ($line in $assetLines) {
    $matches = [regex]::Matches($line, $pattern)
    foreach ($m in $matches) {
        $id = $m.Groups[1].Value
        $code = $m.Groups[2].Value
        $name = $m.Groups[3].Value
        $cat = $m.Groups[4].Value
        $status = $m.Groups[10].Value
        
        [void]$assetIds.Add($id)
        $assetList += @{
            Id = $id
            Code = $code
            Category = $cat
            Status = $status
        }
    }
}

Write-Host "Total Database Assets Parsed: $($assetIds.Count)"

# Check empty statuses
$emptyStatus = $assetList | Where-Object { [string]::IsNullOrWhiteSpace($_['Status']) -or $_['Status'] -eq 'NULL' }
Write-Host "Total assets with EMPTY/NULL status in DB: $($emptyStatus.Count)"

# Read material files
$materialDir = "material"
$mdFiles = Get-ChildItem -Path $materialDir -Recurse -Include "*.md"

$materialUnits = [System.Collections.Generic.Dictionary[string, System.Object]]::new([System.StringComparer]::OrdinalIgnoreCase)
$unitPattern = [regex]'\b([A-Z]{2,5}-\d{2,5})\b'

foreach ($file in $mdFiles) {
    $txt = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $m = $unitPattern.Matches($txt)
    foreach ($match in $m) {
        $code = $match.Value.ToUpper()
        if (-not $materialUnits.ContainsKey($code)) {
            $materialUnits[$code] = $file.Name
        }
    }
}

Write-Host "Total unique unit codes found across all Material files: $($materialUnits.Count)"

$missing = @()
foreach ($code in $materialUnits.Keys | Sort-Object) {
    if (-not $assetIds.Contains($code)) {
        $missing += $code
    }
}

Write-Host "Missing Units in DB ($($missing.Count)): "
$missing -join ", " | Write-Host
