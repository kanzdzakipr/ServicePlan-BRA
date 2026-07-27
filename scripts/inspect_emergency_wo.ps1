$json = Get-Content -Raw -Path 'data.json' | ConvertFrom-Json
Write-Host "High Priority Work Orders in data.json:"
$json.work_orders | Where-Object { $_.priority -eq 'High' } | Select-Object -First 10 | ForEach-Object {
    Write-Host "WO ID: $($_.woId)"
    Write-Host "Asset: $($_.assetId)"
    Write-Host "Issue: $($_.issue)"
    Write-Host "Downtime: $($_.downtime)"
    Write-Host "------------------------------------"
}
