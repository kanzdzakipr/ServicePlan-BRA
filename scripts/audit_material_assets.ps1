# PowerShell script to audit material assets vs SQL database assets
$sqlPath = "scripts/u646470441_ServicePlanBRA.sql"
$materialDir = "material"

Write-Host "--- Reading Database Assets ---"
$sqlContent = Get-Content $sqlPath -Raw -Encoding UTF8

# Extract all asset_id values from INSERT INTO `assets`
$dbAssetIds = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)

# Match lines inserting into assets: INSERT INTO `assets` ... VALUES ('DZ-00001', ...) or ('DT-00001', ...)
$dbAssetMatches = [regex]::Matches($sqlContent, "INSERT INTO `assets` [^;]*;")
foreach ($match in $dbAssetMatches) {
    $valMatches = [regex]::Matches($match.Value, "\('([^']+)','([^']*)'")
    foreach ($vm in $valMatches) {
        $id = $vm.Groups[1].Value.Trim()
        [void]$dbAssetIds.Add($id)
    }
}

Write-Host "Database assets found in SQL: $($dbAssetIds.Count) units"

# Now scan all .md files in material/
Write-Host "--- Scanning Material Files ---"
$materialFiles = Get-ChildItem -Path $materialDir -Recurse -Include "*.md"

$materialUnits = [System.Collections.Generic.Dictionary[string, System.Object]]::new([System.StringComparer]::OrdinalIgnoreCase)

# Unit pattern regex matching standard asset codes: DT-00001, EXC-00001, DZ-00001, MG-00001, PF-00001, SD-00001, WT-00001, PM-00001, SL-00001, TMC-00001, FBT-00001, LV-00001, RM-00001, CS-00001, etc.
$unitPattern = [regex]"\b([A-Z]{2,5}-\d{2,5})\b"

foreach ($file in $materialFiles) {
    $text = Get-Content $file.FullName -Raw -Encoding UTF8
    $matches = $unitPattern.Matches($text)
    foreach ($m in $matches) {
        $unitCode = $m.Value.ToUpper()
        if (-not $materialUnits.ContainsKey($unitCode)) {
            $materialUnits[$unitCode] = @{
                Code = $unitCode
                File = $file.Name
            }
        }
    }
}

Write-Host "Total unique unit codes extracted from Material files: $($materialUnits.Count)"

$missingInDb = @()
foreach ($code in $materialUnits.Keys | Sort-Object) {
    if (-not $dbAssetIds.Contains($code)) {
        $missingInDb += $code
    }
}

Write-Host "Units in Material but MISSING in Database ($($missingInDb.Count)): "
$missingInDb -join ", " | Write-Host
