$ErrorActionPreference = 'Stop'

$workspaceRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$vendorRoot = [IO.Path]::GetFullPath((Join-Path $workspaceRoot 'scripts\vendor'))
$vendorPrefix = $vendorRoot.TrimEnd('\', '/') + [IO.Path]::DirectorySeparatorChar
$readmePath = Join-Path $vendorRoot 'README.md'
$entries = @()

foreach ($line in Get-Content -LiteralPath $readmePath -Encoding UTF8) {
    if ($line -match '^(\S+)\s+([0-9a-fA-F]{64})$') {
        $entries += [pscustomobject]@{
            FileName = $Matches[1]
            Expected = $Matches[2].ToLowerInvariant()
        }
    }
}

if (-not $entries.Count) {
    throw 'No checksum entries were found in scripts/vendor/README.md.'
}

$failures = @()
$duplicateEntries = $entries |
    Group-Object { $_.FileName.Replace('\', '/').ToLowerInvariant() } |
    Where-Object { $_.Count -gt 1 }
foreach ($duplicate in $duplicateEntries) {
    $failures += "$($duplicate.Name): duplicate checksum entry"
}

$expectedFiles = [Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
foreach ($entry in $entries) {
    $normalizedName = $entry.FileName.Replace('\', '/')
    [void] $expectedFiles.Add($normalizedName)
    $path = [IO.Path]::GetFullPath((Join-Path $vendorRoot $entry.FileName))
    if (-not $path.StartsWith($vendorPrefix, [StringComparison]::OrdinalIgnoreCase)) {
        $failures += "$($entry.FileName): path escapes vendor directory"
        continue
    }
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
        $failures += "$($entry.FileName): file missing"
        continue
    }
    $actual = (Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($actual -ne $entry.Expected) {
        $failures += "$($entry.FileName): expected $($entry.Expected), got $actual"
    }
}

$actualFiles = [Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
Get-ChildItem -LiteralPath $vendorRoot -File -Recurse |
    Where-Object { $_.FullName -ne $readmePath } |
    ForEach-Object {
        $relativeName = $_.FullName.Substring($vendorPrefix.Length).Replace('\', '/')
        [void] $actualFiles.Add($relativeName)
    }

foreach ($expectedFile in $expectedFiles) {
    if (-not $actualFiles.Contains($expectedFile)) {
        $failures += "$expectedFile`: listed checksum asset is missing"
    }
}
foreach ($actualFile in $actualFiles) {
    if (-not $expectedFiles.Contains($actualFile)) {
        $failures += "$actualFile`: vendor asset has no checksum entry"
    }
}

if ($failures.Count) {
    throw "Vendor checksum verification failed:`n$($failures -join "`n")"
}

Write-Output "PASS vendor checksums exact set $($actualFiles.Count)/$($entries.Count)"
