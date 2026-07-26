$ErrorActionPreference = 'Stop'

$workspaceRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$rawRoot = [IO.Path]::GetFullPath((Join-Path $workspaceRoot 'raw-material'))
$rawPrefix = $rawRoot.TrimEnd('\', '/') + [IO.Path]::DirectorySeparatorChar
$manifestPath = Join-Path $PSScriptRoot 'raw-material-manifest.json'
$expectedCorpusDigest = '870d1bde011ef3777d56723ec736f171ebddd5234e1bf7d985d932d6c35348cb'
$manifestDocument = Get-Content -LiteralPath $manifestPath -Raw -Encoding UTF8 |
    ConvertFrom-Json
$failures = @()

$normalizedEntries = [Collections.Generic.List[string]]::new()
foreach ($entry in $manifestDocument) {
    $normalizedEntries.Add(([string] $entry).Replace('\', '/'))
}

if ($normalizedEntries.Count -ne 69) {
    $failures += "manifest entry count is $($normalizedEntries.Count); expected 69"
}
$duplicates = $normalizedEntries |
    Group-Object { $_.ToLowerInvariant() } |
    Where-Object { $_.Count -gt 1 }
foreach ($duplicate in $duplicates) {
    $failures += "$($duplicate.Name): duplicate manifest entry"
}

$expectedSet = [Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
$resolvedFiles = @()
foreach ($entry in $normalizedEntries) {
    [void] $expectedSet.Add($entry)
    if (-not $entry.StartsWith('../raw-material/', [StringComparison]::Ordinal)) {
        $failures += "$entry`: entry must start with ../raw-material/"
        continue
    }
    $path = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot $entry))
    if (-not $path.StartsWith($rawPrefix, [StringComparison]::OrdinalIgnoreCase)) {
        $failures += "$entry`: path escapes raw-material directory"
        continue
    }
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
        $failures += "$entry`: file missing"
        continue
    }
    $resolvedFiles += [pscustomobject]@{
        ManifestPath = $entry
        FullPath = $path
    }
}

$actualSet = [Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
Get-ChildItem -LiteralPath $rawRoot -File -Recurse | ForEach-Object {
    $relative = $_.FullName.Substring($rawPrefix.Length).Replace('\', '/')
    [void] $actualSet.Add("../raw-material/$relative")
}

foreach ($expected in $expectedSet) {
    if (-not $actualSet.Contains($expected)) {
        $failures += "$expected`: listed file is not present"
    }
}
foreach ($actual in $actualSet) {
    if (-not $expectedSet.Contains($actual)) {
        $failures += "$actual`: physical file is absent from the manifest"
    }
}

if (-not $failures.Count) {
    $baselineLines = foreach ($file in $resolvedFiles) {
        $item = Get-Item -LiteralPath $file.FullPath
        $hash = (Get-FileHash -LiteralPath $file.FullPath -Algorithm SHA256).Hash.ToLowerInvariant()
        "$($file.ManifestPath)`t$($item.Length)`t$hash"
    }
    [string[]] $sortedBaselineLines = $baselineLines
    [Array]::Sort($sortedBaselineLines, [StringComparer]::OrdinalIgnoreCase)
    $baselinePayload = $sortedBaselineLines -join "`n"
    $sha256 = [Security.Cryptography.SHA256]::Create()
    try {
        $digestBytes = $sha256.ComputeHash([Text.Encoding]::UTF8.GetBytes($baselinePayload))
        $actualCorpusDigest = ([BitConverter]::ToString($digestBytes)).Replace('-', '').ToLowerInvariant()
    } finally {
        $sha256.Dispose()
    }
    if ($actualCorpusDigest -ne $expectedCorpusDigest) {
        $failures += "corpus content digest expected $expectedCorpusDigest, got $actualCorpusDigest"
    }
}

if ($failures.Count) {
    throw "Raw-material manifest verification failed:`n$($failures -join "`n")"
}

Write-Output "PASS raw-material exact set and content digest $($actualSet.Count)/$($normalizedEntries.Count)"
