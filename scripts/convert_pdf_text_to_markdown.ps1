param(
    [Parameter(Mandatory = $true)][string]$SourcePath,
    [Parameter(Mandatory = $true)][string]$OutputPath,
    [string]$PdfToTextPath = 'C:\Program Files\Git\mingw64\bin\pdftotext.exe'
)

$ErrorActionPreference = 'Stop'
$utf8 = [System.Text.UTF8Encoding]::new($false)

if (-not (Test-Path -LiteralPath $SourcePath -PathType Leaf)) { throw "PDF tidak ditemukan: $SourcePath" }
if (-not (Test-Path -LiteralPath $PdfToTextPath -PathType Leaf)) { throw "pdftotext tidak ditemukan: $PdfToTextPath" }

$outputDirectory = Split-Path -Parent $OutputPath
New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
$tempPath = Join-Path ([System.IO.Path]::GetTempPath()) ("serviceplan-pdf-{0}.txt" -f [guid]::NewGuid().ToString('N'))

try {
    & $PdfToTextPath -layout -enc UTF-8 -- $SourcePath $tempPath
    if ($LASTEXITCODE -ne 0) { throw "pdftotext gagal dengan exit code $LASTEXITCODE" }
    $text = [System.IO.File]::ReadAllText($tempPath, $utf8)
    $pages = [regex]::Split($text, "`f")
    if ($pages.Count -gt 0 -and [string]::IsNullOrWhiteSpace($pages[$pages.Count - 1])) {
        $pages = $pages[0..($pages.Count - 2)]
    }
    if ($pages.Count -eq 0) { throw 'PDF tidak menghasilkan halaman teks.' }

    $writer = [System.IO.StreamWriter]::new($OutputPath, $false, $utf8)
    try {
        $writer.WriteLine("# $([System.IO.Path]::GetFileNameWithoutExtension($SourcePath))")
        $writer.WriteLine()
        $writer.WriteLine("Sumber: ``$([System.IO.Path]::GetFileName($SourcePath))``")
        $writer.WriteLine()
        $writer.WriteLine("Metode: ekstraksi lapisan teks PDF dengan tata letak dipertahankan. Jumlah halaman sumber: $($pages.Count).")
        $writer.WriteLine()
        for ($index = 0; $index -lt $pages.Count; $index++) {
            $pageText = $pages[$index].Replace("`r`n", "`n").Replace("`r", "`n").Replace('````', '` ` ` `')
            $writer.WriteLine("## Halaman $($index + 1)")
            $writer.WriteLine()
            $writer.WriteLine('````text')
            $writer.Write($pageText)
            if (-not $pageText.EndsWith("`n")) { $writer.WriteLine() }
            $writer.WriteLine('````')
            $writer.WriteLine()
        }
    }
    finally {
        $writer.Dispose()
    }

    [pscustomobject]@{
        Source = [System.IO.Path]::GetFileName($SourcePath)
        Output = $OutputPath
        Pages = $pages.Count
        NonWhitespaceCharacters = ($text -replace '\s', '').Length
    }
}
finally {
    if (Test-Path -LiteralPath $tempPath) { Remove-Item -LiteralPath $tempPath -Force }
}
