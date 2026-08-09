# ============================================================================
# ServicePlan-BRA: Remove Extra '‚' (U+201A Single Low-9 Quotation Mark)
# ============================================================================

$filePath = "dashboard.view.php"
$bytes = [System.IO.File]::ReadAllBytes($filePath)
$text = [System.Text.Encoding]::UTF8.GetString($bytes)

Write-Host "Scanning $filePath for extra '‚' (U+201A)..." -ForegroundColor Cyan

$low9Quote = [string][char]0x201A

if ($text.Contains($low9Quote)) {
    $count = ([regex]::Matches($text, [regex]::Escape($low9Quote))).Count
    Write-Host "Found $count occurrences of extra character '‚' (U+201A), stripping..." -ForegroundColor Yellow
    $text = $text.Replace($low9Quote, "")
    
    $outBytes = [System.Text.Encoding]::UTF8.GetBytes($text)
    [System.IO.File]::WriteAllBytes($filePath, $outBytes)
    Write-Host "Successfully stripped $count extra '‚' characters!" -ForegroundColor Green
} else {
    Write-Host "No '‚' (U+201A) characters found." -ForegroundColor Green
}
