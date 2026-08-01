param(
    [string]$BankPath = (Join-Path $PSScriptRoot '..\material\BANK_DATA_EQUIPMENT_1.md'),
    [string]$FilterPath = (Join-Path $PSScriptRoot '..\material\LOGISTIK\PEMAKAIAN_FILTER_DT_JANUARI_2026.md'),
    [string]$OutputPath = (Join-Path $PSScriptRoot 'logistics_data.js')
)

$ErrorActionPreference = 'Stop'

function Get-MarkdownCells {
    param([string]$Line)

    return @($Line.Trim().Trim('|').Split('|') | ForEach-Object { $_.Trim() })
}

function Get-PlainValue {
    param([string]$Value)

    if ($null -eq $Value) { return '' }
    return (($Value -replace '<br>.*$', '') -replace '&lt;', '<' -replace '&gt;', '>').Trim()
}

function Convert-ToIsoDate {
    param([string]$Value)

    $text = (Get-PlainValue $Value).Trim()
    if ($text -match '^(\d{4})-(\d{1,2})-(\d{1,2})$') {
        return '{0}-{1:D2}-{2:D2}' -f [int]$Matches[1], [int]$Matches[2], [int]$Matches[3]
    }
    if ($text -match '^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$') {
        return '{0}-{1:D2}-{2:D2}' -f [int]$Matches[3], [int]$Matches[2], [int]$Matches[1]
    }
    return $text
}

function Get-SectionLines {
    param(
        [string[]]$Lines,
        [string]$StartPattern,
        [string]$EndPattern
    )

    $start = -1
    $end = $Lines.Count
    for ($index = 0; $index -lt $Lines.Count; $index++) {
        if ($start -lt 0 -and $Lines[$index] -match $StartPattern) {
            $start = $index + 1
            continue
        }
        if ($start -ge 0 -and $Lines[$index] -match $EndPattern) {
            $end = $index
            break
        }
    }
    if ($start -lt 0) { throw "Bagian sumber tidak ditemukan: $StartPattern" }
    return @($Lines[$start..($end - 1)])
}

function Get-NumericCellValue {
    param([string]$Value)

    $plain = Get-PlainValue $Value
    if ($plain -match '-?\d+(?:[\.,]\d+)?') {
        return $Matches[0] -replace ',', '.'
    }
    return ''
}

function Get-NormalizedKeyPart {
    param([string]$Value)

    return ((Get-PlainValue $Value).ToUpperInvariant() -replace '[^A-Z0-9]', '')
}

function Get-UsageKey {
    param($Record)

    return @(
        (Convert-ToIsoDate $Record.tglSpb),
        (Get-NormalizedKeyPart $Record.spesifikasi),
        (Get-NormalizedKeyPart $Record.idUnit)
    ) -join '|'
}

$bankLines = @(Get-Content -LiteralPath $BankPath -Encoding UTF8)
$filterLines = @(Get-Content -LiteralPath $FilterPath -Encoding UTF8)

$partInLines = Get-SectionLines $bankLines '^# 2\. Sheet `PART MASUK`' '^# 3\. Sheet `PEMAKAIAN`'
$usageLines = Get-SectionLines $bankLines '^# 3\. Sheet `PEMAKAIAN`' '^# 4\. Sheet `STOCK`'
$stockLines = Get-SectionLines $bankLines '^# 4\. Sheet `STOCK`' '^# 5\. Sheet `stok`'

$partsIn = [System.Collections.Generic.List[object]]::new()
foreach ($line in $partInLines) {
    if ($line -notmatch '^\|\s*\d+\s*\|') { continue }
    $cells = Get-MarkdownCells $line
    if ($cells.Count -lt 13) { continue }
    $date = Convert-ToIsoDate $cells[1]
    $partName = Get-PlainValue $cells[5]
    if (-not $date -or -not $partName -or $date -notmatch '^\d{4}-\d{2}-\d{2}$') { continue }
    $partsIn.Add([ordered]@{
        tanggal = $date
        noBukti = (Get-PlainValue $(if ($cells[3]) { $cells[3] } else { $cells[2] }))
        terimaDari = (Get-PlainValue $cells[4])
        namaParts = $partName
        partNumber = (Get-PlainValue $cells[6])
        merk = (Get-PlainValue $cells[7])
        satuan = (Get-PlainValue $cells[8])
        jml = (Get-NumericCellValue $cells[9])
        unit = (Get-PlainValue $cells[10])
        noSpb = (Get-PlainValue $cells[12])
        source = 'BANK DATA EQUIPMENT / PART MASUK'
    })
}

$partsOut = [System.Collections.Generic.List[object]]::new()
foreach ($line in $usageLines) {
    if ($line -notmatch '^\|\s*\d+\s*\|') { continue }
    $cells = Get-MarkdownCells $line
    if ($cells.Count -lt 13) { continue }
    $date = Convert-ToIsoDate $cells[1]
    $partName = Get-PlainValue $cells[4]
    if (-not $date -or -not $partName -or $date -notmatch '^\d{4}-\d{2}-\d{2}$') { continue }
    $evidence = Get-PlainValue $(if ($cells[2]) { $cells[2] } else { $cells[3] })
    $partsOut.Add([ordered]@{
        no = [string]($partsOut.Count + 1)
        noSpb = $evidence
        noBukti = $evidence
        tglSpb = $date
        noJo = (Get-PlainValue $cells[9])
        idUnit = (Get-PlainValue $cells[10])
        namaSparepart = $partName
        spesifikasi = (Get-PlainValue $cells[5])
        merk = (Get-PlainValue $cells[6])
        qty = (Get-NumericCellValue $cells[8])
        satuan = (Get-PlainValue $cells[7])
        status = 'Dipakai / Dikeluarkan'
        kesimpulan = (Get-PlainValue $cells[12])
        source = 'BANK DATA EQUIPMENT / PEMAKAIAN'
    })
}

$stock = [System.Collections.Generic.List[object]]::new()
foreach ($line in $stockLines) {
    if ($line -notmatch '^\|\s*\d+\s*\|') { continue }
    $cells = Get-MarkdownCells $line
    if ($cells.Count -lt 26) { continue }
    $partName = Get-PlainValue $cells[2]
    if (-not $partName -or $partName -match '^(NAMA PARTS|LAPORAN STOK|YARD|Tanggal|Site)') { continue }
    $stock.Add([ordered]@{
        namaParts = $partName
        partNumber = (Get-PlainValue $cells[3])
        satuan = (Get-PlainValue $cells[4])
        penerimaanTotal = (Get-NumericCellValue $cells[11])
        pemakaianTotal = (Get-NumericCellValue $cells[24])
        saldo = (Get-NumericCellValue $cells[25])
        source = 'BANK DATA EQUIPMENT / STOCK 26 April-25 Mei 2026'
    })
}

$filterUsage = [System.Collections.Generic.List[object]]::new()
$insideFilterTable = $false
foreach ($line in $filterLines) {
    if ($line -match '^\| Tanggal \| No\. Bukti \| Nama Parts \|') {
        $insideFilterTable = $true
        continue
    }
    if (-not $insideFilterTable) { continue }
    if ($line -match '^\|\s*---') { continue }
    if ($line -notmatch '^\|') { break }
    $cells = Get-MarkdownCells $line
    if ($cells.Count -lt 10) { continue }
    $date = Convert-ToIsoDate $cells[0]
    if ($date -notmatch '^\d{4}-\d{2}-\d{2}$') { continue }
    $filterUsage.Add([ordered]@{
        tanggal = $date
        noBukti = (Get-PlainValue $cells[1])
        namaParts = (Get-PlainValue $cells[2])
        partNumber = (Get-PlainValue $cells[3])
        merk = (Get-PlainValue $cells[4])
        satuan = (Get-PlainValue $cells[5])
        qty = (Get-NumericCellValue $cells[6])
        permintaan = (Get-PlainValue $cells[7])
        idUnit = (Get-PlainValue $cells[8])
        statusBarangBekas = (Get-PlainValue $cells[9])
        source = 'PEMAKAIAN FILTER DT JANUARI 2026'
    })
}

$usageQueues = @{}
for ($index = 0; $index -lt $partsOut.Count; $index++) {
    $key = Get-UsageKey $partsOut[$index]
    if (-not $usageQueues.ContainsKey($key)) {
        $usageQueues[$key] = [System.Collections.Generic.Queue[int]]::new()
    }
    $usageQueues[$key].Enqueue($index)
}

$filterMatched = 0
foreach ($filter in $filterUsage) {
    $comparison = [pscustomobject]@{
        tglSpb = $filter.tanggal
        spesifikasi = $filter.partNumber
        idUnit = $filter.idUnit
    }
    $key = Get-UsageKey $comparison
    if ($usageQueues.ContainsKey($key) -and $usageQueues[$key].Count -gt 0) {
        $matchedIndex = $usageQueues[$key].Dequeue()
        $partsOut[$matchedIndex].source = 'BANK DATA EQUIPMENT / PEMAKAIAN + PEMAKAIAN FILTER DT JANUARI 2026'
        if ($filter.statusBarangBekas) { $partsOut[$matchedIndex].kesimpulan = $filter.statusBarangBekas }
        $filterMatched++
        continue
    }
    $partsOut.Add([ordered]@{
        no = [string]($partsOut.Count + 1)
        noSpb = $filter.noBukti
        noBukti = $filter.noBukti
        tglSpb = $filter.tanggal
        noJo = $filter.permintaan
        idUnit = $filter.idUnit
        namaSparepart = $filter.namaParts
        spesifikasi = $filter.partNumber
        merk = $filter.merk
        qty = $filter.qty
        satuan = $filter.satuan
        status = 'Dipakai / Dikeluarkan'
        kesimpulan = $filter.statusBarangBekas
        source = 'PEMAKAIAN FILTER DT JANUARI 2026'
    })
}

$payload = [ordered]@{
    metadata = [ordered]@{
        generatedAt = (Get-Date).ToString('yyyy-MM-ddTHH:mm:ssK')
        bankSource = 'material/BANK_DATA_EQUIPMENT_1.md'
        filterSource = 'material/LOGISTIK/PEMAKAIAN_FILTER_DT_JANUARI_2026.md'
        filterPeriod = '2025-12-26/2026-01-25'
        filterRows = $filterUsage.Count
        filterMatchedToBank = $filterMatched
    }
    masuk = $partsIn
    keluar = $partsOut
    stock = $stock
    filterUsage = $filterUsage
}

$json = $payload | ConvertTo-Json -Depth 8 -Compress
$javascript = "window.logisticsData = $json;"
[System.IO.File]::WriteAllText((Resolve-Path (Split-Path -Parent $OutputPath)).Path + '\' + (Split-Path -Leaf $OutputPath), $javascript, [System.Text.UTF8Encoding]::new($false))

Write-Output "Generated $OutputPath"
Write-Output "Parts masuk: $($partsIn.Count)"
Write-Output "Pemakaian/keluar: $($partsOut.Count)"
Write-Output "Stock: $($stock.Count)"
Write-Output "Filter Januari: $($filterUsage.Count) ($filterMatched matched to bank)"
