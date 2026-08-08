$sqlPath = 'scripts/u646470441_ServicePlanBRA.sql'
$lines = [System.IO.File]::ReadAllLines($sqlPath, [System.Text.Encoding]::UTF8)

$assetCount = 0
$nullStatusCount = 0

foreach ($line in $lines) {
    if ($line.Contains('INSERT INTO `assets`')) {
        $tuples = $line.Split(@("),("), [System.StringSplitOptions]::None)
        $assetCount = $tuples.Count
        Write-Host "Total tuples in assets INSERT statement: $assetCount"
        
        foreach ($t in $tuples) {
            $parts = $t -split "," | ForEach-Object { $_.Trim().Trim("'") }
            if ($parts.Count -ge 13) {
                $status = $parts[12]
                if ([string]::IsNullOrWhiteSpace($status) -or $status -eq 'NULL') {
                    $nullStatusCount++
                }
            }
        }
    }
}

Write-Host "Total NULL or empty status assets in assets table: $nullStatusCount"
