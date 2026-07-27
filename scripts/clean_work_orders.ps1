$jsonPath = "data.json"
$jsonRaw = Get-Content -Raw -Path $jsonPath
$content = $jsonRaw | ConvertFrom-Json

function Clean-IssueText ($text) {
    if ([string]::IsNullOrWhiteSpace($text)) { return "Pemeriksaan dan Perbaikan Rutin" }

    # 1. Decode HTML entities
    $str = $text.ToString() -replace '&amp;', '&' -replace '&lt;', '<' -replace '&gt;', '>' -replace '&quot;', '"' -replace '&#039;', "'"
    
    # 2. Replace <br> tags with clean separator
    $str = $str -replace '(?i)<br\s*/?>', ' - ' -replace '<[^>]+>', ' '

    # 3. Clean up common typos and casing
    $str = $str -replace '(?i)\bACCU/BATTERAY\b', 'Accu / Baterai'
    $str = $str -replace '(?i)\bACCU/BATTERY\b', 'Accu / Baterai'
    $str = $str -replace '(?i)\bACCU\b', 'Accu'
    $str = $str -replace '(?i)\bBATTERAY\b', 'Baterai'
    $str = $str -replace '(?i)\bBATTERY\b', 'Baterai'
    $str = $str -replace '(?i)\bSOAK\b', 'Soak'
    $str = $str -replace '(?i)\bMINTA\b', 'Minta'
    $str = $str -replace '(?i)\bGANTI\b', 'Ganti'
    $str = $str -replace '(?i)\bPCS\b', 'Pcs'
    $str = $str -replace '(?i)\bPERBAIKAN\b', 'Perbaikan'
    $str = $str -replace '(?i)\bKUNCI\b', 'Kunci'
    $str = $str -replace '(?i)\bOMBENG\b', 'Om Beng'
    $str = $str -replace '(?i)\bombeng\b', 'Om Beng'
    $str = $str -replace '(?i)\bban serap\b', 'Ban Serep'
    $str = $str -replace '(?i)\bban serep\b', 'Ban Serep'
    $str = $str -replace '(?i)\bUNIT\b', 'Unit'
    $str = $str -replace '(?i)\bTIDAK\b', 'Tidak'
    $str = $str -replace '(?i)\bMAU\b', 'Mau'
    $str = $str -replace '(?i)\bSTARTER\b', 'Starter'
    $str = $str -replace '(?i)\bHAND PUMP\b', 'Hand Pump'
    $str = $str -replace '(?i)\bTROUBLE\b', 'Trouble'
    $str = $str -replace '(?i)\bOVER HOUL\b', 'Overhaul'
    $str = $str -replace '(?i)\bOVERHOUL\b', 'Overhaul'
    $str = $str -replace '(?i)\bRADIATOR LEAKING\b', 'Radiator Leaking'
    $str = $str -replace '(?i)\bHouse keping\b', 'Housekeeping'
    $str = $str -replace '(?i)\bHousekeping\b', 'Housekeeping'
    $str = $str -replace '(?i)\bSImpang\b', 'Simpang'
    $str = $str -replace '(?i)\bdilakukzn\b', 'dilakukan'
    $str = $str -replace '(?i)\baccses\b', 'akses'
    $str = $str -replace '(?i)\bacces\b', 'akses'
    $str = $str -replace '(?i)\bB\.Pit\b', 'Borrow Pit'
    $str = $str -replace '(?i)\bbropit\b', 'Borrow Pit'
    $str = $str -replace '~\s*', ''

    # Clean double symbols and extra spaces
    $str = $str -replace '\.\s*\.', '.' -replace '\-\s*\-', '-' -replace '\s+', ' '
    $str = $str.Trim(' ', '-', '.')

    # Title casing for words if text starts with lower or is ALL CAPS
    if ($str -cmatch '^[A-Z0-9\s\-\/\.,\(\)\&]+$' -and $str.Length -gt 4) {
        $words = $str.Split(' ')
        $cleanWords = @()
        foreach ($w in $words) {
            if ($w.Length -gt 1 -and $w -notmatch '^[0-9]+$' -and $w -notmatch '^(WO|DT|DZ|EXC|PF|SD|MG|BRA|KM|P2H|BBM|SMR|SLA|BAST|HSE|K3|PTO)$') {
                $cleanWords += ($w.Substring(0,1).ToUpper() + $w.Substring(1).ToLower())
            } else {
                $cleanWords += $w
            }
        }
        $str = $cleanWords -join ' '
    }

    # Capitalize first character
    if ($str.Length -gt 1) {
        $str = $str.Substring(0,1).ToUpper() + $str.Substring(1)
    }

    return $str
}

function Clean-DowntimeText ($dt) {
    if ([string]::IsNullOrWhiteSpace($dt) -or $dt.Contains("#")) { return "0 jam 00 menit" }
    
    $clean = $dt.ToString().Trim() -replace '\s+', ' '
    # Remove duplicate 'jam' at end if present
    $clean = $clean -replace '\s*jam\s*jam$', ' jam' -replace '\s*jam$', ''
    if (-not $clean.Contains("jam")) {
        $clean = "$clean jam"
    }
    return $clean
}

# Override all work orders issue and downtime fields
foreach ($wo in $content.work_orders) {
    $wo.issue = Clean-IssueText $wo.issue
    $wo.downtime = Clean-DowntimeText $wo.downtime
}

# Save clean JSON back to data.json
$newJson = $content | ConvertTo-Json -Depth 10
[System.IO.File]::WriteAllText((Get-Item $jsonPath).FullName, $newJson, [System.Text.Encoding]::UTF8)

Write-Host "SUCCESS: Cleaned up work order descriptions and downtime entries in data.json!"
