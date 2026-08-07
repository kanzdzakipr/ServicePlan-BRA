$sqlPath = "scripts/u646470441_ServicePlanBRA.sql"
$content = Get-Content -Path $sqlPath -Raw
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($sqlPath, $content, $utf8NoBom)
Write-Host "Successfully converted u646470441_ServicePlanBRA.sql to UTF-8 without BOM!"
