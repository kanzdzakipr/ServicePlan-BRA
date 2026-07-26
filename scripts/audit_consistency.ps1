$json = Get-Content -Raw -Path 'data.json' | ConvertFrom-Json
$assetLocs = $json.assets | Select-Object -ExpandProperty location -Unique
$woLocs = $json.work_orders | Select-Object -ExpandProperty location -Unique

Write-Host "=== AUDIT KONSISTENSI LOKASI DATA.JSON ==="
Write-Host "Unique Asset Locations ($($assetLocs.Count)):"
$assetLocs | ForEach-Object { Write-Host "  - $_" }

Write-Host "`nUnique Work Order Locations ($($woLocs.Count)):"
$woLocs | ForEach-Object { Write-Host "  - $_" }
