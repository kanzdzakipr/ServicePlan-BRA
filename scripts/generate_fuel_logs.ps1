$sqlPath = "C:\Users\KANZ\Documents\Kanz\Proyekan\ServicePlan-BRA\scripts\u646470441_ServicePlanBRA.sql"
$sqlContent = Get-Content -Raw -Path $sqlPath

# Extract ONLY from the assets insert block
$assetsBlockMatch = [regex]::Match($sqlContent, "(?s)INSERT INTO ``assets`` VALUES(.*?);")
if ($assetsBlockMatch.Success) {
    $assetsBlock = $assetsBlockMatch.Groups[1].Value
} else {
    Write-Host "Could not find assets insert block"
    exit
}

$assetPattern = "\('([^']+)',"
$matches = [regex]::Matches($assetsBlock, $assetPattern)
$assetIds = @()
foreach ($m in $matches) {
    $id = $m.Groups[1].Value
    if ($id -notmatch "^WO-" -and $id -notmatch "^PF-" -and $id -notmatch "^RM-") {
        if ($assetIds -notcontains $id) {
            $assetIds += $id
        }
    }
}

$drivers = @("Budi Santoso", "Rahmat Hidayat", "Supriadi", "Ahmad", "Joko", "Slamet", "Udin", "Asep")

$fuelLogsData = @()
$fuelLogId = 1

foreach ($asset in $assetIds) {
    # Generate realistic data
    $date = (Get-Date "2026-08-05 00:40:27").AddHours((Get-Random -Minimum 1 -Maximum 24)).ToString("yyyy-MM-dd HH:mm:ss")
    $flowStart = (Get-Random -Minimum 1000 -Maximum 50000)
    $flowEnd = $flowStart + (Get-Random -Minimum 50 -Maximum 400)
    $liters = $flowEnd - $flowStart
    $hm_km = $flowEnd
    
    $baseline = 15.0
    if ($asset -match "^DT-") { $baseline = 3.5 }
    elseif ($asset -match "^EXC-") { $baseline = 15.0 }
    elseif ($asset -match "^MG-") { $baseline = 13.0 }
    else { $baseline = 10.0 }
    
    $calc = $baseline + (($baseline * (Get-Random -Minimum -20 -Maximum 20)) / 100)
    $is_anomaly = if ($calc -gt ($baseline * 1.2)) { 1 } else { 0 }
    $driver = $drivers | Get-Random
    
    $calcStr = "{0:N2}" -f $calc
    $baselineStr = "{0:N2}" -f $baseline
    
    $fuelLogsData += "($fuelLogId,'$asset','$date',$flowStart,$flowEnd,$liters,$hm_km,$calcStr,$baselineStr,$is_anomaly,'$driver')"
    $fuelLogId++
}

$insertString = "INSERT INTO ``fuel_logs`` VALUES " + ($fuelLogsData -join ",") + ";"

$patternToReplace = "(?s)LOCK TABLES ``fuel_logs`` WRITE;\s*/\*\!40000 ALTER TABLE ``fuel_logs`` DISABLE KEYS \*/;\s*INSERT INTO ``fuel_logs`` VALUES .*?;\s*/\*\!40000 ALTER TABLE ``fuel_logs`` ENABLE KEYS \*/;"
$replacement = "LOCK TABLES ``fuel_logs`` WRITE;`n/*!40000 ALTER TABLE ``fuel_logs`` DISABLE KEYS */;`n" + $insertString + "`n/*!40000 ALTER TABLE ``fuel_logs`` ENABLE KEYS */;"

$newSqlContent = $sqlContent -replace $patternToReplace, $replacement
Set-Content -Path $sqlPath -Value $newSqlContent
Write-Host "Generated $fuelLogId fuel logs and updated SQL file!"
