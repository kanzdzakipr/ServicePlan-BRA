# PowerShell script to clean up all remaining Mojibake text corruptions across codebase
$ErrorActionPreference = "Stop"

$filesToClean = Get-ChildItem -Path "." -Recurse -Include "*.html","*.js","*.css","*.sql","*.php" | 
    Where-Object { $_.FullName -notmatch '\\(\.git|vendor|node_modules)\\'}

$replacements = @{
    "¢â€ â€™"  = " &rarr; "
    "¢â¬â€œ"  = " - "
    "¢â¬â€"   = " - "
    "ƒâ€š©"   = "&copy;"
    "Ã‚"       = ""
    "Â"        = ""
    "¢â€"      = " - "
    "â€â€™"   = " -> "
    "â€œ"      = "-"
    "â€"       = "-"
}

Write-Host "Scanning $($filesToClean.Count) files for Mojibake corruptions..."

$totalReplaced = 0

foreach ($file in $filesToClean) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $modified = $false
    
    foreach ($key in $replacements.Keys) {
        if ($content.Contains($key)) {
            $val = $replacements[$key]
            $content = $content.Replace($key, $val)
            $modified = $true
            Write-Host "  [FIXED] Replaced '$key' with '$val' in $($file.Name)"
            $totalReplaced++
        }
    }
    
    if ($modified) {
        [System.IO.File]::WriteAllText($file.FullName, $content, (New-Object System.Text.UTF8Encoding $false))
    }
}

Write-Host "Mojibake cleaning complete! Total replacements: $totalReplaced"
