# ============================================================================
# ServicePlan-BRA: Complete Sanitization of All Database Tables
# ============================================================================

$mysqlExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$mysqldumpExe = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"
$dbName = "u646470441_ServicePlanBRA"
$sqlFile = "scripts\u646470441_ServicePlanBRA.sql"

function Clean-Text ($text) {
    if ([string]::IsNullOrWhiteSpace($text)) { return "" }
    $str = $text.ToString()
    $str = $str -replace '&amp;', '&' -replace '&lt;', '<' -replace '&gt;', '>' -replace '&quot;', '"' -replace '&#039;', "'"
    $str = $str -replace '(?i)<br\s*/?>', ' - ' -replace '<[^>]+>', ' '
    $str = $str -replace '(?i)\bACCU/BATTERAY\b', 'Accu / Baterai'
    $str = $str -replace '(?i)\bACCU/BATTERY\b', 'Accu / Baterai'
    $str = $str -replace '(?i)\bBATTERAY\b', 'Baterai'
    $str = $str -replace '(?i)\bBATTERY\b', 'Baterai'
    $str = $str -replace '(?i)\bSOAK\b', 'Soak'
    $str = $str -replace '\s+', ' '
    return $str.Trim()
}

Write-Host "[1/3] Sanitizing work_orders issue_description..." -ForegroundColor Cyan
$rawWo = & $mysqlExe -u root -e "USE $dbName; SELECT wo_id, issue_description FROM work_orders;"
$woLines = $rawWo -split "`r?`n"
$woUpdates = @()

foreach ($l in $woLines[1..($woLines.Count - 1)]) {
    if ([string]::IsNullOrWhiteSpace($l)) { continue }
    $cols = $l -split "`t"
    if ($cols.Count -lt 2) { continue }
    
    $woId = $cols[0].Trim()
    $desc = $cols[1].Trim()
    $cleanDesc = Clean-Text $desc

    if ($desc -ne $cleanDesc) {
        $woIdEsc = $woId.Replace("'", "''")
        $descEsc = $cleanDesc.Replace("'", "''")
        $woUpdates += "UPDATE work_orders SET issue_description = '$descEsc' WHERE wo_id = '$woIdEsc';"
    }
}
Write-Host "Prepared $($woUpdates.Count) work_orders text cleanup updates." -ForegroundColor Green

Write-Host "[2/3] Sanitizing inspections findings_summary..." -ForegroundColor Cyan
$rawInsp = & $mysqlExe -u root -e "USE $dbName; SELECT inspection_id, findings_summary FROM inspections;"
$inspLines = $rawInsp -split "`r?`n"
$inspUpdates = @()

foreach ($l in $inspLines[1..($inspLines.Count - 1)]) {
    if ([string]::IsNullOrWhiteSpace($l)) { continue }
    $cols = $l -split "`t"
    if ($cols.Count -lt 2) { continue }

    $inspId = $cols[0].Trim()
    $summary = $cols[1].Trim()
    $cleanSummary = Clean-Text $summary

    if ($summary -ne $cleanSummary) {
        $summaryEsc = $cleanSummary.Replace("'", "''")
        $inspUpdates += "UPDATE inspections SET findings_summary = '$summaryEsc' WHERE inspection_id = $inspId;"
    }
}
Write-Host "Prepared $($inspUpdates.Count) inspections text cleanup updates." -ForegroundColor Green

# Combine all SQL updates
$allSql = @("SET FOREIGN_KEY_CHECKS = 0;") + $woUpdates + $inspUpdates + @("SET FOREIGN_KEY_CHECKS = 1;")

if ($allSql.Count -gt 2) {
    Write-Host "Applying database updates..." -ForegroundColor Cyan
    $tempFile = [System.IO.Path]::GetTempFileName() + ".sql"
    [System.IO.File]::WriteAllText($tempFile, "USE $dbName; `n" + ($allSql -join "`n"), [System.Text.Encoding]::UTF8)
    
    Get-Content $tempFile | & $mysqlExe -u root $dbName
    Remove-Item $tempFile -Force
}

Write-Host "[3/3] Re-exporting clean SQL dump to $sqlFile..." -ForegroundColor Cyan
& $mysqldumpExe -u root --routines --triggers --events $dbName > $sqlFile

Write-Host "Complete database table sanitization finished!" -ForegroundColor Green
