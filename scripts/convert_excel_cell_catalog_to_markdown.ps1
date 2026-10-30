param(
    [Parameter(Mandatory = $true)][string]$SourcePath,
    [Parameter(Mandatory = $true)][string]$OutputDirectory
)

$ErrorActionPreference = 'Stop'
$invariant = [System.Globalization.CultureInfo]::InvariantCulture
$utf8 = [System.Text.UTF8Encoding]::new($false)

function ConvertTo-MarkdownText {
    param([AllowNull()]$Value)
    if ($null -eq $Value) { return '' }
    return ([string]$Value).Replace('&', '&amp;').Replace('<', '&lt;').Replace('>', '&gt;').Replace('|', '&#124;').Replace("`r`n", '<br>').Replace("`n", '<br>').Replace("`r", '<br>')
}

function Get-ColumnName {
    param([int]$ColumnNumber)
    $name = ''
    while ($ColumnNumber -gt 0) {
        $ColumnNumber--
        $name = [char](65 + ($ColumnNumber % 26)) + $name
        $ColumnNumber = [math]::Floor($ColumnNumber / 26)
    }
    return $name
}

function Get-ExcelErrorText {
    param([int]$Code)
    $errors = @{
        -2146826281 = '#DIV/0!'
        -2146826246 = '#N/A'
        -2146826259 = '#NAME?'
        -2146826288 = '#NULL!'
        -2146826252 = '#NUM!'
        -2146826265 = '#REF!'
        -2146826273 = '#VALUE!'
        -2146826015 = '#GETTING_DATA'
    }
    if ($errors.ContainsKey($Code)) { return $errors[$Code] }
    return $null
}

function ConvertTo-RawValueText {
    param([AllowNull()]$Value)
    if ($null -eq $Value) { return '' }
    if ($Value -is [double] -or $Value -is [single]) { return $Value.ToString('R', $invariant) }
    if ($Value -is [decimal]) { return $Value.ToString($invariant) }
    if ($Value -is [int] -or $Value -is [long] -or $Value -is [int16]) {
        $errorText = Get-ExcelErrorText ([int]$Value)
        if ($null -ne $errorText) { return $errorText }
        return $Value.ToString($invariant)
    }
    if ($Value -is [bool]) { if ($Value) { return 'TRUE' } else { return 'FALSE' } }
    return [string]$Value
}

function Get-ValueType {
    param([AllowNull()]$Value, [string]$Formula)
    if ($Formula.StartsWith('=')) { return 'Formula' }
    if ($null -eq $Value) { return 'Kosong' }
    if (($Value -is [int] -or $Value -is [long] -or $Value -is [int16]) -and $null -ne (Get-ExcelErrorText ([int]$Value))) { return 'Error' }
    if ($Value -is [double] -or $Value -is [single] -or $Value -is [decimal] -or $Value -is [int] -or $Value -is [long] -or $Value -is [int16]) { return 'Angka' }
    if ($Value -is [bool]) { return 'Boolean' }
    return 'Teks'
}

function Get-SafeFileName {
    param([string]$Name)
    $safe = $Name.ToLowerInvariant() -replace '[^a-z0-9]+', '_'
    return $safe.Trim('_')
}

if (-not (Test-Path -LiteralPath $SourcePath -PathType Leaf)) {
    throw "Workbook tidak ditemukan: $SourcePath"
}

New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null

$excel = $null
$workbook = $null
$summary = [System.Collections.Generic.List[object]]::new()

try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    $excel.AskToUpdateLinks = $false
    $workbook = $excel.Workbooks.Open($SourcePath, 0, $true)

    $sheetNumber = 0
    foreach ($worksheet in $workbook.Worksheets) {
        $sheetNumber++
        $fileName = ('{0:D2}_{1}.md' -f $sheetNumber, (Get-SafeFileName $worksheet.Name))
        $targetPath = Join-Path $OutputDirectory $fileName
        $writer = [System.IO.StreamWriter]::new($targetPath, $false, $utf8, 1048576)
        try {
            $writer.WriteLine("# Sheet: $($worksheet.Name)")
            $writer.WriteLine()
            $writer.WriteLine("Sumber: ``$([System.IO.Path]::GetFileName($SourcePath))``")
            $writer.WriteLine()

            $lastRowCell = $worksheet.Cells.Find('*', $worksheet.Cells.Item(1, 1), -4123, 2, 1, 2, $false)
            $lastColumnCell = $worksheet.Cells.Find('*', $worksheet.Cells.Item(1, 1), -4123, 2, 2, 2, $false)

            if ($null -eq $lastRowCell -or $null -eq $lastColumnCell) {
                $writer.WriteLine('Sheet ini tidak memiliki sel berisi nilai atau rumus.')
                $summary.Add([pscustomobject]@{ File = $fileName; Sheet = $worksheet.Name; Range = '-'; Cells = 0; Formulas = 0; Hyperlinks = 0; Shapes = $worksheet.Shapes.Count; Empty = $true })
                continue
            }

            $maxRow = $lastRowCell.Row
            $maxColumn = $lastColumnCell.Column
            $sourceRange = $worksheet.Range($worksheet.Cells.Item(1, 1), $worksheet.Cells.Item($maxRow, $maxColumn))
            $values = $sourceRange.Value2
            $formulas = $sourceRange.Formula

            $hyperlinks = @{}
            foreach ($hyperlink in $worksheet.Hyperlinks) {
                $address = ''
                try { $address = $hyperlink.Range.Address($false, $false) } catch { $address = '(objek)' }
                $target = [string]$hyperlink.Address
                $subAddress = [string]$hyperlink.SubAddress
                if ($subAddress -ne '') {
                    if ($target -ne '') { $target += '#' }
                    $target += $subAddress
                }
                $hyperlinks[$address] = $target
            }

            $writer.WriteLine("Rentang pemeriksaan: ``A1:$(Get-ColumnName $maxColumn)$maxRow``")
            $writer.WriteLine()
            $writer.WriteLine('| Sel | Tipe | Nilai mentah / hasil tersimpan | Rumus Excel | Hyperlink |')
            $writer.WriteLine('| --- | --- | --- | --- | --- |')

            $cellCount = 0
            $formulaCount = 0
            for ($row = 1; $row -le $maxRow; $row++) {
                for ($column = 1; $column -le $maxColumn; $column++) {
                    $value = $values[$row, $column]
                    $formulaValue = $formulas[$row, $column]
                    $formula = if ($null -eq $formulaValue) { '' } else { [string]$formulaValue }
                    if ($null -eq $value -and $formula -eq '') { continue }

                    $cellCount++
                    $isFormula = $formula.StartsWith('=')
                    if ($isFormula) { $formulaCount++ } else { $formula = '' }
                    $address = "$(Get-ColumnName $column)$row"
                    $type = Get-ValueType $value $formula
                    $rawValue = ConvertTo-MarkdownText (ConvertTo-RawValueText $value)
                    $formulaText = ConvertTo-MarkdownText $formula
                    $hyperlinkText = ''
                    if ($hyperlinks.ContainsKey($address)) { $hyperlinkText = ConvertTo-MarkdownText $hyperlinks[$address] }
                    $writer.WriteLine("| $address | $type | $rawValue | $formulaText | $hyperlinkText |")
                }
            }

            if ($worksheet.Shapes.Count -gt 0) {
                $writer.WriteLine()
                $writer.WriteLine('## Objek non-sel')
                $writer.WriteLine()
                foreach ($shape in $worksheet.Shapes) {
                    $shapeText = ''
                    try { $shapeText = [string]$shape.TextFrame2.TextRange.Text } catch { }
                    $shapeText = ConvertTo-MarkdownText $shapeText
                    $alternativeText = ConvertTo-MarkdownText ([string]$shape.AlternativeText)
                    $topLeft = $shape.TopLeftCell.Address($false, $false)
                    $writer.WriteLine("- ``$($shape.Name)``; tipe ``$($shape.Type)``; sel kiri atas ``$topLeft``; ukuran $([math]::Round($shape.Width)) x $([math]::Round($shape.Height)) pt; teks: $shapeText; alt: $alternativeText")
                }
            }

            $summary.Add([pscustomobject]@{
                File = $fileName
                Sheet = $worksheet.Name
                Range = "A1:$(Get-ColumnName $maxColumn)$maxRow"
                Cells = $cellCount
                Formulas = $formulaCount
                Hyperlinks = $hyperlinks.Count
                Shapes = $worksheet.Shapes.Count
                Empty = $false
            })
        }
        finally {
            $writer.Dispose()
        }
    }

    $namesFile = ('{0:D2}_named_ranges.md' -f ($sheetNumber + 1))
    $namesPath = Join-Path $OutputDirectory $namesFile
    $namesWriter = [System.IO.StreamWriter]::new($namesPath, $false, $utf8)
    try {
        $namesWriter.WriteLine('# Named Ranges Workbook')
        $namesWriter.WriteLine()
        $namesWriter.WriteLine('| Nama | Mengacu ke | Terlihat |')
        $namesWriter.WriteLine('| --- | --- | --- |')
        foreach ($name in $workbook.Names) {
            $nameText = ConvertTo-MarkdownText ([string]$name.Name)
            $refersTo = ConvertTo-MarkdownText ([string]$name.RefersTo)
            $visible = if ($name.Visible) { 'Ya' } else { 'Tidak' }
            $namesWriter.WriteLine("| $nameText | $refersTo | $visible |")
        }
    }
    finally {
        $namesWriter.Dispose()
    }

    $mediaFiles = [System.Collections.Generic.List[string]]::new()
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $archive = [System.IO.Compression.ZipFile]::OpenRead($SourcePath)
    try {
        $mediaDirectoryName = 'workbook_media'
        $mediaDirectory = Join-Path $OutputDirectory $mediaDirectoryName
        $mediaIndex = 0
        foreach ($entry in $archive.Entries) {
            if (-not $entry.FullName.StartsWith('xl/media/') -or $entry.Length -eq 0) { continue }
            $mediaIndex++
            New-Item -ItemType Directory -Path $mediaDirectory -Force | Out-Null
            $mediaName = ('{0:D2}_{1}' -f $mediaIndex, [System.IO.Path]::GetFileName($entry.FullName))
            $targetMediaPath = Join-Path $mediaDirectory $mediaName
            $inputStream = $entry.Open()
            $outputStream = [System.IO.File]::Create($targetMediaPath)
            try { $inputStream.CopyTo($outputStream) }
            finally { $outputStream.Dispose(); $inputStream.Dispose() }
            $mediaFiles.Add("$mediaDirectoryName/$mediaName")
        }
    }
    finally {
        $archive.Dispose()
    }

    $mediaFile = $null
    if ($mediaFiles.Count -gt 0) {
        $mediaFile = ('{0:D2}_workbook_media.md' -f ($sheetNumber + 2))
        $mediaWriter = [System.IO.StreamWriter]::new((Join-Path $OutputDirectory $mediaFile), $false, $utf8)
        try {
            $mediaWriter.WriteLine('# Media Workbook')
            $mediaWriter.WriteLine()
            $mediaWriter.WriteLine('Media berikut diekstrak langsung dari paket XLSX agar gambar atau logo tidak terlewat.')
            $mediaWriter.WriteLine()
            foreach ($mediaPath in $mediaFiles) {
                $mediaWriter.WriteLine("- [$mediaPath]($mediaPath)")
                if ($mediaPath -match '\.(png|jpe?g|gif|bmp)$') {
                    $mediaWriter.WriteLine()
                    $mediaWriter.WriteLine("  ![$mediaPath]($mediaPath)")
                }
            }
        }
        finally {
            $mediaWriter.Dispose()
        }
    }

    $readmePath = Join-Path $OutputDirectory '00_README.md'
    $readme = [System.IO.StreamWriter]::new($readmePath, $false, $utf8)
    try {
        $readme.WriteLine("# $([System.IO.Path]::GetFileNameWithoutExtension($SourcePath))")
        $readme.WriteLine()
        $readme.WriteLine('setiap tabel terpisah jadi satu markdown')
        $readme.WriteLine()
        $readme.WriteLine("Sumber: ``$([System.IO.Path]::GetFileName($SourcePath))``")
        $readme.WriteLine()
        $readme.WriteLine('Setiap sheet dikonversi menjadi satu tabel katalog sel. Koordinat sel, tipe data, nilai mentah atau hasil perhitungan yang tersimpan, rumus Excel, dan hyperlink dipertahankan. Sel kosong tidak ditulis karena koordinat sel terisi sudah mempertahankan posisi aslinya.')
        $readme.WriteLine()
        $readme.WriteLine('## Daftar berkas')
        $readme.WriteLine()
        foreach ($entry in $summary) {
            if ($entry.Empty) {
                $readme.WriteLine("- [$($entry.File)]($($entry.File)) - sheet ``$($entry.Sheet)`` kosong.")
            } else {
                $readme.WriteLine("- [$($entry.File)]($($entry.File)) - sheet ``$($entry.Sheet)``; rentang ``$($entry.Range)``; $($entry.Cells) sel terisi; $($entry.Formulas) rumus; $($entry.Hyperlinks) hyperlink; $($entry.Shapes) objek non-sel.")
            }
        }
        $readme.WriteLine("- [$namesFile]($namesFile) - seluruh $($workbook.Names.Count) named range workbook.")
        if ($null -ne $mediaFile) { $readme.WriteLine("- [$mediaFile]($mediaFile) - $($mediaFiles.Count) media asli dari paket XLSX.") }
        $readme.WriteLine()
        $totalCells = ($summary | Measure-Object -Property Cells -Sum).Sum
        $totalFormulas = ($summary | Measure-Object -Property Formulas -Sum).Sum
        $totalHyperlinks = ($summary | Measure-Object -Property Hyperlinks -Sum).Sum
        $totalShapes = ($summary | Measure-Object -Property Shapes -Sum).Sum
        $readme.WriteLine("Validasi: $($workbook.Worksheets.Count) sheet diperiksa; $totalCells sel berisi nilai atau rumus, $totalFormulas rumus, $totalHyperlinks hyperlink, $totalShapes objek non-sel, $($mediaFiles.Count) media, dan $($workbook.Names.Count) named range telah dicatat.")
    }
    finally {
        $readme.Dispose()
    }

    $summary | Format-Table -AutoSize
    "Named ranges: $($workbook.Names.Count)"
    "Output: $([System.IO.Path]::GetFullPath($OutputDirectory))"
}
finally {
    if ($null -ne $workbook) { $workbook.Close($false) }
    if ($null -ne $excel) {
        $excel.Quit()
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($excel) | Out-Null
    }
}
