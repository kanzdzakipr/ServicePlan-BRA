$files = Get-ChildItem -Path "." -Recurse -Include "*.html","*.js","*.css","*.sql","*.php" | Where-Object { $_.FullName -notmatch '\\(\.git|vendor|node_modules)\\'}

$fixedCount = 0

foreach ($f in $files) {
    $raw = [System.IO.File]::ReadAllText($f.FullName, [System.Text.Encoding]::UTF8)
    $clean = $raw
    
    $clean = $clean.Replace("¢â€ â€™", " &rarr; ")
    $clean = $clean.Replace("¢â¬â€œ", " - ")
    $clean = $clean.Replace("¢â¬â€", " - ")
    $clean = $clean.Replace("ƒâ€š©", "&copy;")
    $clean = $clean.Replace("Ã‚", "")

    if ($clean -ne $raw) {
        [System.IO.File]::WriteAllText($f.FullName, $clean, (New-Object System.Text.UTF8Encoding $false))
        Write-Host "Fixed Mojibake in $($f.Name)"
        $fixedCount++
    }
}

Write-Host "Finished! Fixed $fixedCount files."
