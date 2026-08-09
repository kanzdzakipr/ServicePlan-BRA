# ============================================================================
# ServicePlan-BRA: Deep Check & Removal for 'Ã' (U+00C3) and 'Â' (U+00C2)
# ============================================================================

$filePath = "dashboard.view.php"
$bytes = [System.IO.File]::ReadAllBytes($filePath)
$text = [System.Text.Encoding]::UTF8.GetString($bytes)

Write-Host "Deep scanning $filePath..." -ForegroundColor Cyan

$c382 = [System.Text.Encoding]::UTF8.GetString([byte[]](0xC3, 0x82))
$c2a0 = [System.Text.Encoding]::UTF8.GetString([byte[]](0xC2, 0xA0))

$text = $text.Replace($c382 + $c2a0, " ")
$text = $text.Replace($c382, "")
$text = $text.Replace($c2a0, " ")

# Direct Unicode char check
$c3 = [string][char]0x00C3
$c2 = [string][char]0x00C2

$countC3 = 0
$countC2 = 0

if ($text.Contains($c3)) {
    $countC3 = ([regex]::Matches($text, [regex]::Escape($c3))).Count
    $text = $text.Replace($c3, "")
}

if ($text.Contains($c2)) {
    $countC2 = ([regex]::Matches($text, [regex]::Escape($c2))).Count
    $text = $text.Replace($c2, "")
}

$total = $countC3 + $countC2

if ($total -gt 0) {
    $outBytes = [System.Text.Encoding]::UTF8.GetBytes($text)
    [System.IO.File]::WriteAllBytes($filePath, $outBytes)
    Write-Host "Stripped $total residual characters ($countC3 'Ã', $countC2 'Â')." -ForegroundColor Green
} else {
    Write-Host "100% CLEAN: Zero 'Ã' or 'Â' characters remaining in $filePath!" -ForegroundColor Green
}
