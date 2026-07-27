$json = Get-Content -Raw -Path 'data.json' | ConvertFrom-Json
Write-Host "Properties in data.json:"
$json.PSObject.Properties.Name | ForEach-Object { Write-Host " - $_" }

if ($json.work_orders) {
    Write-Host "`nTotal work_orders: $($json.work_orders.Count)"
    Write-Host "Sample work_orders (first 5):"
    $json.work_orders | Select-Object -First 5 | ForEach-Object {
        Write-Host "  WO ID: $($_.woId) | Asset: $($_.assetId) | Issue: $($_.issue) | Down: $($_.downtime) | Prio: $($_.priority)"
    }
}
