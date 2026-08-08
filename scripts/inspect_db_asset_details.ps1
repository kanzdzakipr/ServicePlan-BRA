# PowerShell script to detail db assets & check missing attributes
$sqlPath = 'scripts/u646470441_ServicePlanBRA.sql'
$rawText = [System.IO.File]::ReadAllText($sqlPath, [System.Text.Encoding]::UTF8)

# Find INSERT INTO assets ... VALUES ...
$assetLine = ($rawText -split "`n") | Where-Object { $_ -like "*INSERT INTO*`assets`*" -or $_ -like "*INSERT INTO assets*" }

$pattern = "'([^']*)'"

# Match tuples: ('asset_id', 'asset_code', 'asset_name', 'category', 'subcategory', 'make_model', 'serial_number', 'manufacture_year', 'status', 'location_id', ...)
$tuples = [regex]::Matches($assetLine, "\((.*?)\)")

Write-Host "Found $($tuples.Count) tuples in assets table insert statement."

$emptyStatusCount = 0
$emptyCatCount = 0
$emptyLocationCount = 0
$statusDistribution = @{}

foreach ($t in $tuples) {
    $str = $t.Groups[1].Value
    if ($str -like "*'DT-*'" -or $str -like "*'DZ-*'" -or $str -like "*'EXC-*'" -or $str -like "*'LV-*'" -or $str -like "*'BRA-*'" -or $str -like "*'CS-*'") {
        $parts = $str -split "," | ForEach-Object { $_.Trim().Trim("'") }
        if ($parts.Count -ge 9) {
            $id = $parts[0]
            $code = $parts[1]
            $name = $parts[2]
            $cat = $parts[3]
            $status = $parts[8]
            
            if ([string]::IsNullOrWhiteSpace($status) -or $status -eq 'NULL') {
                $emptyStatusCount++
            } else {
                if (-not $statusDistribution.ContainsKey($status)) { $statusDistribution[$status] = 0 }
                $statusDistribution[$status]++
            }
            if ([string]::IsNullOrWhiteSpace($cat) -or $cat -eq 'NULL') { $emptyCatCount++ }
        }
    }
}

Write-Host "Empty / NULL Status count: $emptyStatusCount"
Write-Host "Empty / NULL Category count: $emptyCatCount"
Write-Host "Status Distribution in DB:"
$statusDistribution.GetEnumerator() | Out-String | Write-Host
