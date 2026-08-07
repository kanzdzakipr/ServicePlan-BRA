# ============================================================================
# ServicePlan-BRA: Remove Extra Commas Near Middle Dot (·) and Degree (°)
# ============================================================================

$filePath = "dashboard.html"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$content = [System.IO.File]::ReadAllText($filePath, $utf8NoBom)

$dot = [char]0x00B7
$deg = [char]0x00B0

Write-Host "Checking $filePath for extra commas near '$dot' and '$deg'..." -ForegroundColor Cyan

# Define bad patterns containing extra commas
$badPatterns = @(
    ", $dot",
    ",$dot",
    "$dot,",
    "$dot ,",
    ", $deg",
    ",$deg",
    "$deg,",
    "$deg ,"
)

$goodReplacements = @{
    ", $dot" = " $dot"
    ",$dot"  = " $dot"
    "$dot,"  = "$dot"
    "$dot ," = "$dot"
    ", $deg" = " $deg"
    ",$deg"  = " $deg"
    "$deg,"  = "$deg"
    "$deg ," = "$deg"
}

$lines = $content -split "`r?`n"
for ($i = 0; $i -lt $lines.Count; $i++) {
    $line = $lines[$i]
    foreach ($p in $badPatterns) {
        if ($line.Contains($p)) {
            Write-Host "Found at Line $($i+1): $($line.Trim())" -ForegroundColor Yellow
        }
    }
}

$totalFixed = 0
foreach ($p in $badPatterns) {
    if ($content.Contains($p)) {
        $count = ([regex]::Matches($content, [regex]::Escape($p))).Count
        $totalFixed += $count
        $targetGood = $goodReplacements[$p]
        $content = $content.Replace($p, $targetGood)
    }
}

if ($totalFixed -gt 0) {
    [System.IO.File]::WriteAllText($filePath, $content, $utf8NoBom)
    Write-Host "Successfully fixed $totalFixed extra comma occurrences near '$dot' and '$deg'!" -ForegroundColor Green
} else {
    Write-Host "No extra commas found near '$dot' or '$deg'." -ForegroundColor Green
}
