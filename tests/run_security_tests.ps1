$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCommand) {
    $laragonPhp = Get-ChildItem -LiteralPath 'C:\laragon\bin\php' -Filter php.exe -Recurse -ErrorAction SilentlyContinue |
        Sort-Object FullName -Descending |
        Select-Object -First 1
    if (-not $laragonPhp) { throw 'PHP CLI tidak ditemukan.' }
    $php = $laragonPhp.FullName
} else {
    $php = $phpCommand.Source
}

$tests = @(
    'tests/security_unit.php',
    'tests/security_static.php',
    'tests/security_http_smoke.php'
)

foreach ($test in $tests) {
    & $php (Join-Path $projectRoot $test)
    if ($LASTEXITCODE -ne 0) { throw "Security test gagal: $test" }
}

Write-Host 'All configured security tests passed.'
