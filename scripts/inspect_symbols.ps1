# ============================================================================
# ServicePlan-BRA: Inspect All Lines Containing Middle Dot (·) or Degree (°)
# ============================================================================

$filePath = "dashboard.view.php"
$text = [System.IO.File]::ReadAllText($filePath, [System.Text.Encoding]::UTF8)

$dot = [char]0x00B7
$deg = [char]0x00B0

Write-Host "Inspecting all lines with middle dot or degree symbol in $filePath..." -ForegroundColor Cyan

$lines = $text -split "`r?`n"
for ($i = 0; $i -lt $lines.Count; $i++) {
    $line = $lines[$i]
    if ($line.Contains([string]$dot) -or $line.Contains([string]$deg)) {
        Write-Host "Line $($i+1): $($line.Trim())"
    }
}
