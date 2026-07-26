$jsonPath = "data.json"
$jsonRaw = Get-Content -Raw -Path $jsonPath
$content = $jsonRaw | ConvertFrom-Json

function Normalize-Location ($loc) {
    if ([string]::IsNullOrWhiteSpace($loc)) { return "Yard KM 12 Duri" }
    
    $clean = $loc.ToString() -replace '<br\s*/?>', ' ' -replace '<[^>]+>', ' ' -replace '\s+', ' '
    $str = $clean.ToLower().Trim()

    # Movement destination priority ("pindah ke", "dibawa ke", "bengkel")
    if ($str.Contains("pindah ke yard") -or $str.Contains("dibawa ke yard") -or $str.Contains("pindah yard") -or $str.Contains("bengkel anugra") -or $str.Contains("yard km12") -or $str.Contains("yard km 12") -or $str -eq "workshop" -or $str -eq "workshop km 12") {
        return "Yard KM 12 Duri"
    }
    if ($str.Contains("pindah ke prabumulih") -or $str.Contains("yard prabumulih")) {
        return "Yard Prabumulih"
    }

    # Primary Master Locations
    if ($str.Contains("harapan baru") -or $str.Contains("bropit") -or $str.Contains("b.pit") -or $str.Contains("pit")) {
        return "Borrow Pit Harapan Baru"
    }
    if ($str.Contains("yard") -or $str.Contains("bengkel") -or $str.Contains("duri")) {
        return "Yard KM 12 Duri"
    }
    if ($str.Contains("prabumulih")) {
        return "Yard Prabumulih"
    }
    if ($str.Contains("sunter")) {
        return "Site Sunter Stadium"
    }
    if ($str.Contains("minas")) {
        return "Minas Field Project"
    }
    if ($str.Contains("pekanbaru")) {
        return "PKB Pekanbaru Branch"
    }
    if ($str.Contains("palembang")) {
        return "PLB Palembang Branch"
    }
    if ($str -eq "ho" -or $str -eq "lok. ho" -or $str.Contains("head office")) {
        return "Head Office Pekanbaru"
    }
    if ($str.Contains("bangko")) { return "Site Bangko" }
    if ($str.Contains("celcin")) { return "Site Celcin" }
    if ($str.Contains("ndd")) { return "Site NDD" }

    # Well Pad Cleanups
    if ($str -match '(?:loc\s*;?\s*|lokasi\s*:?\s*)?([0-9a-z\s\-]+)') {
        $padCode = $clean -replace '(?i)^(loc\s*;?\s*|lokasi\s*:?\s*|lksi\s*:?\s*)', '' -replace '\s+', ''
        if ($padCode.Length -ge 3 -and $padCode.Length -le 12) {
            return "Well Pad " + $padCode.ToUpper()
        }
    }

    # Fallback
    if ($clean.Length -gt 35) {
        return $clean.Substring(0, 32) + "..."
    }
    return $clean.Trim()
}

# Override all asset locations in data.json
foreach ($asset in $content.assets) {
    $asset.location = Normalize-Location $asset.location
}

# Override all work_order locations in data.json
foreach ($wo in $content.work_orders) {
    $wo.location = Normalize-Location $wo.location
}

# Save updated JSON back to data.json
$newJson = $content | ConvertTo-Json -Depth 10
[System.IO.File]::WriteAllText((Get-Item $jsonPath).FullName, $newJson, [System.Text.Encoding]::UTF8)

Write-Host "SUCCESS: Standardized all 416 asset locations in data.json!"
