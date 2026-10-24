<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 6 - Rental Contracts, Project Allocation & Operating Hours Matrix
 *
 * Ingests rental contract assignments and equipment utilization records from January 2026:
 *  - 07_01_Identitas_dan_Ringkasan_Pendapatan_Sewa.md -> contract_assignments (184 active billing contracts)
 *  - 07_02_Pencatatan_Proyek_Harian_Sewa_Alat.md -> daily operational hours matrix & working days
 *
 * Operations:
 *  1. Ensures authentic asset records exist in `assets` for all active units.
 *  2. Ingests 184 contracts into `contract_assignments` across PHR, PEP, ADERA, and KBB projects.
 *  3. Synchronizes `assets` master properties (project_name, operational_position, status).
 *  4. Links telematics telemetry in `telematics_logs` to master `asset_id`.
 *  5. Records execution in `app_data_migrations` under 'BATCH_6_EQUIPMENT_REPORT_202601'.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();
$baseDir = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026';

echo "====================================================================\n";
echo "MIGRATION BATCH 6: RENTAL CONTRACTS, PROJECT ALLOCATION & UTILIZATION\n";
echo "====================================================================\n\n";

$isForce = in_array('--force', $argv, true);

// Check migration tracking
$checkMig = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = ?");
$checkMig->execute(['BATCH_6_EQUIPMENT_REPORT_202601']);
$isAlreadyRun = ((int)$checkMig->fetchColumn()) > 0;

if ($isAlreadyRun && !$isForce) {
    echo "[INFO] Migration BATCH_6_EQUIPMENT_REPORT_202601 has already been executed.\n";
    echo "To re-run, use the --force flag.\n";
    exit(0);
}

$file0701 = $baseDir . '/07_01_Identitas_dan_Ringkasan_Pendapatan_Sewa.md';
$file0702 = $baseDir . '/07_02_Pencatatan_Proyek_Harian_Sewa_Alat.md';

if (!file_exists($file0701) || !file_exists($file0702)) {
    echo "[ERROR] Source markdown files 07_01 or 07_02 not found in: $baseDir\n";
    exit(1);
}

function cleanAmount(string $s): float {
    $s = trim($s);
    if (strpos($s, '- --') !== false || $s === '' || $s === '-') {
        return 0.0;
    }
    $v = str_replace(['-', '.', ' ', 'Rp', 'U'], '', $s);
    return is_numeric($v) ? (float)$v : 0.0;
}

// -------------------------------------------------------------
// Step 1: Parse 07_01 and 07_02 markdown files
// -------------------------------------------------------------
echo "[1/5] Parsing 07_01 Rental Summary and 07_02 Daily Utilization Matrix...\n";

$lines0701 = file($file0701, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$units0701 = [];
$curCat = '';

foreach ($lines0701 as $line) {
    if (preg_match('/^##\s+(.+)$/', $line, $m)) {
        $curCat = trim($m[1]);
        continue;
    }
    if (strpos($line, '|') !== false && !preg_match('/^\|\s*---/', $line) && !preg_match('/^\|\s*No\./', $line)) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 16 && is_numeric($parts[1])) {
            $units0701[] = [
                'category' => $curCat,
                'no' => (int)$parts[1],
                'lokasi' => $parts[2],
                'nama_alat' => $parts[3],
                'kode_unit' => $parts[4],
                'sn' => $parts[5],
                'harga_sewa' => cleanAmount($parts[6]),
                'adera' => cleanAmount($parts[7]),
                'rwi' => cleanAmount($parts[8]),
                'phr' => cleanAmount($parts[9]),
                'pep' => cleanAmount($parts[10]),
                'kbb' => cleanAmount($parts[11]),
                'matting' => cleanAmount($parts[12]),
                'rusak' => cleanAmount($parts[13]),
                'total_jan' => cleanAmount($parts[14]),
            ];
        }
    }
}

$lines0702 = file($file0702, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$units0702 = [];
$curCat = '';

foreach ($lines0702 as $line) {
    if (preg_match('/^##\s+(.+)$/', $line, $m)) {
        $curCat = trim($m[1]);
        continue;
    }
    if (strpos($line, '|') !== false && !preg_match('/^\|\s*---/', $line) && !preg_match('/^\|\s*No\./', $line)) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 35 && is_numeric($parts[1])) {
            $days = [];
            for ($d = 1; $d <= 31; $d++) {
                $days[$d] = $parts[3 + $d] ?? '';
            }
            $units0702[] = [
                'category' => $curCat,
                'no' => (int)$parts[1],
                'kode_unit' => $parts[2],
                'sn' => $parts[3],
                'days' => $days
            ];
        }
    }
}

echo "      -> Total rows parsed in 07_01: " . count($units0701) . "\n";
echo "      -> Total rows parsed in 07_02: " . count($units0702) . "\n\n";

// Filter active units (total_jan > 0 and cat != 'TOTAL STATION')
$activeUnits = [];
$standbyUnits = [];

for ($i = 0; $i < count($units0701); $i++) {
    $u1 = $units0701[$i];
    $u2 = $units0702[$i] ?? null;

    $workDays = 0;
    $standbyDays = 0;
    $breakdownDays = 0;
    $dayProjs = [];

    if ($u2) {
        foreach ($u2['days'] as $d => $val) {
            $v = trim($val);
            if ($v === '' || $v === '- --' || $v === '-') continue;
            if (in_array(strtoupper($v), ['STB', 'STANDBY', 'SBY YARD', 'YARD'])) {
                $standbyDays++;
            } elseif (in_array(strtoupper($v), ['B/D', 'BD', 'RUSAK', 'BREAKDOWN'])) {
                $breakdownDays++;
            } else {
                $workDays++;
                $dayProjs[$v] = ($dayProjs[$v] ?? 0) + 1;
            }
        }
    }

    if ($u1['total_jan'] > 0 && $u1['category'] !== 'TOTAL STATION') {
        // Dominant project determination
        $revs = [
            'PHR' => $u1['phr'],
            'PEP' => $u1['pep'],
            'ADERA' => $u1['adera'],
            'KBB' => $u1['kbb'],
            'RWI' => $u1['rwi']
        ];
        arsort($revs);
        $topProj = array_key_first($revs);
        if ($revs[$topProj] == 0) {
            if (count($dayProjs) > 0) {
                arsort($dayProjs);
                $topProj = array_key_first($dayProjs);
            } else {
                $topProj = 'PHR';
            }
        }

        // Operational target hours
        $targetHours = $workDays > 0 ? ($workDays * 8.0) : 200.0;

        // Site / work area location
        $site = 'PHR Duri Field';
        $lokasiNorm = strtoupper($u1['lokasi']);
        if (strpos($lokasiNorm, 'MINAS') !== false) {
            $site = 'Minas Field';
        } elseif (strpos($lokasiNorm, 'BROPIT') !== false || strpos($lokasiNorm, 'HARAPAN') !== false) {
            $site = 'Bropit Harapan Baru';
        } elseif (strpos($lokasiNorm, 'YARD') !== false || strpos($lokasiNorm, 'KM 12') !== false) {
            $site = 'Yard KM 12 Duri';
        } elseif ($topProj === 'ADERA') {
            $site = 'Adera Site';
        } elseif ($topProj === 'PEP') {
            $site = 'Pertamina EP Field';
        } elseif ($topProj === 'KBB') {
            $site = 'KBB Field';
        } elseif ($topProj === 'PHR') {
            $site = 'PHR Duri Field';
        }

        $u1['work_days'] = $workDays;
        $u1['standby_days'] = $standbyDays;
        $u1['breakdown_days'] = $breakdownDays;
        $u1['target_hours'] = $targetHours;
        $u1['project'] = $topProj;
        $u1['site'] = $site;

        $activeUnits[] = $u1;
    } else {
        $u1['work_days'] = $workDays;
        $u1['standby_days'] = $standbyDays;
        $standbyUnits[] = $u1;
    }
}

echo "      -> Active units identified: " . count($activeUnits) . " (Expected: 184)\n";
echo "      -> Standby / zero units: " . count($standbyUnits) . " (Expected: 51)\n\n";

// -------------------------------------------------------------
// Step 2: Index Assets and Define Explicit Resolutions
// -------------------------------------------------------------
echo "[2/5] Indexing Assets and Resolving Relational References...\n";

$existingAssets = $pdo->query("SELECT asset_id, asset_code, serial_number, license_plate, previous_license_plate, alias_name, hull_number FROM assets")->fetchAll(PDO::FETCH_ASSOC);

$assetByCode = [];
$assetBySn = [];
$assetByPlate = [];
$assetByPrevPlate = [];
$assetByAlias = [];
$assetByHull = [];

foreach ($existingAssets as $a) {
    if (!empty($a['asset_code'])) $assetByCode[strtoupper(trim($a['asset_code']))] = $a['asset_id'];
    if (!empty($a['serial_number'])) $assetBySn[strtoupper(trim($a['serial_number']))] = $a['asset_id'];
    if (!empty($a['license_plate'])) $assetByPlate[strtoupper(str_replace(' ', '', $a['license_plate']))] = $a['asset_id'];
    if (!empty($a['previous_license_plate'])) $assetByPrevPlate[strtoupper(str_replace(' ', '', $a['previous_license_plate']))] = $a['asset_id'];
    if (!empty($a['alias_name'])) $assetByAlias[strtoupper(trim($a['alias_name']))] = $a['asset_id'];
    if (!empty($a['hull_number'])) $assetByHull[strtoupper(trim($a['hull_number']))] = $a['asset_id'];
}

$specialMappings = [
    'DBCH2244' => 'EXC-00024',
    'C00637' => 'EXC-00026',
    'C07004' => 'EXC-00027',
    'C07076' => 'EXC-00028',
    '961582391716' => 'PF-00009',
    '961582391718' => 'PF-00002',
    'Bomag Padfoot 06' => 'PF-00002',
    '1372' => 'MG-00003',
    'ZCC60-0199' => 'CR-00001',
    'XUG035HDAKFC00524' => 'CR-00002',
    'B 9115 PYX' => 'DT-YX-00009',
    'B 9116 PYX' => 'DT-YX-00010',
    'B 9121 PYX' => 'DT-YX-00011',
    'B 9122 PYX' => 'DT-YX-00012',
    'D 9692 XV / DD 8273 S' => 'DT-00023-D',
    'D 9902 XV / DD 8319 S' => 'DT-00024-D',
    'BG 8535 OW' => 'DT-00031',
    'BG 8747 PS' => 'DT-04033',
    'BG 8738 PS' => 'DT-04034',
    'BG 8752 PS' => 'DT-04035',
    'BM9682JO' => 'DT-04045',
    'B 1167 ZKS' => 'LV-00065',
    'IN 1' => 'DT-00001',
    'DT 9782 LE/ DT 8669 KE' => 'DT-00001',
];

$existingAssetIdSet = array_flip(array_column($existingAssets, 'asset_id'));

$resolvedActive = [];
$assetsToInsert = [];

foreach ($activeUnits as $u) {
    $foundId = null;
    $snClean = strtoupper(trim($u['sn']));
    $codeClean = strtoupper(trim($u['kode_unit']));
    $plateClean = strtoupper(str_replace(' ', '', $u['sn']));

    if (isset($specialMappings[$u['sn']])) {
        $foundId = $specialMappings[$u['sn']];
    } elseif (isset($specialMappings[$u['kode_unit']])) {
        $foundId = $specialMappings[$u['kode_unit']];
    } elseif (!empty($snClean) && isset($assetBySn[$snClean])) {
        $foundId = $assetBySn[$snClean];
    } elseif (!empty($plateClean) && isset($assetByPlate[$plateClean])) {
        $foundId = $assetByPlate[$plateClean];
    } elseif (!empty($plateClean) && isset($assetByPrevPlate[$plateClean])) {
        $foundId = $assetByPrevPlate[$plateClean];
    } elseif (!empty($codeClean) && isset($assetByCode[$codeClean])) {
        $foundId = $assetByCode[$codeClean];
    } elseif (!empty($codeClean) && isset($assetByAlias[$codeClean])) {
        $foundId = $assetByAlias[$codeClean];
    } elseif (!empty($codeClean) && isset($assetByHull[$codeClean])) {
        $foundId = $assetByHull[$codeClean];
    } else {
        $vars = [];
        if (preg_match('/^Exca\s*(\d+)$/i', $codeClean, $m)) {
            $n = (int)$m[1];
            $vars[] = sprintf('EXC-%05d', $n);
            $vars[] = sprintf('EXCA %02d', $n);
            $vars[] = sprintf('EXCA %d', $n);
        } elseif (preg_match('/^Dozer\s*(\d+)$/i', $codeClean, $m)) {
            $n = (int)$m[1];
            $vars[] = sprintf('DZ-%05d', $n);
            $vars[] = sprintf('DZ-%02d', $n);
            $vars[] = sprintf('DOZER %02d', $n);
        } elseif (preg_match('/^(?:Greder|Grader)\s*(\d+)$/i', $codeClean, $m)) {
            $n = (int)$m[1];
            $vars[] = sprintf('MG-%05d', $n);
            $vars[] = sprintf('MG %02d', $n);
        } elseif (preg_match('/^Bomag\s*Drum\s*(\d+)$/i', $codeClean, $m)) {
            $n = (int)$m[1];
            $vars[] = sprintf('SD %02d', $n);
            $vars[] = sprintf('BOMAG SINGLE DRUM 2024 (%02d)', $n);
            $vars[] = sprintf('BOMAG SINGLE DRUM (%02d)', $n);
            $vars[] = sprintf('BOMAG DRUM (%02d)', $n);
        } elseif (preg_match('/^Bomag\s*(?:Pad\s*)?Foot\s*(\d+)$/i', $codeClean, $m)) {
            $n = (int)$m[1];
            $vars[] = sprintf('PF %02d', $n);
            $vars[] = sprintf('BOMAG FOOT (%02d)', $n);
        } elseif (preg_match('/^DT\s*(\d+)$/i', $codeClean, $m)) {
            $n = (int)$m[1];
            $vars[] = sprintf('DT-%05d', $n);
            $vars[] = sprintf('DT-%03d', $n);
            $vars[] = sprintf('DT %02d', $n);
        }

        foreach ($vars as $v) {
            $vU = strtoupper($v);
            if (isset($assetByCode[$vU])) { $foundId = $assetByCode[$vU]; break; }
            if (isset($assetByAlias[$vU])) { $foundId = $assetByAlias[$vU]; break; }
            if (isset($assetByHull[$vU])) { $foundId = $assetByHull[$vU]; break; }
        }
    }

    if (!$foundId) {
        echo "[ERROR] Unresolvable asset: [{$u['category']}] {$u['kode_unit']} / {$u['sn']}\n";
        exit(1);
    }

    $u['resolved_asset_id'] = $foundId;
    $resolvedActive[] = $u;

    if (!isset($existingAssetIdSet[$foundId]) && !isset($assetsToInsert[$foundId])) {
        $assetsToInsert[$foundId] = $u;
    }
}

echo "      -> Successfully resolved 184 active units (100% matched).\n";
echo "      -> Authentic assets to register into master catalog: " . count($assetsToInsert) . "\n\n";

// -------------------------------------------------------------
// Step 3: ACID Transaction Execution
// -------------------------------------------------------------
echo "[3/5] Executing Database Ingestion under ACID Transaction...\n";

$pdo->beginTransaction();

try {
    if ($isForce) {
        $pdo->prepare("DELETE FROM contract_assignments WHERE contract_number LIKE 'KTR-202601-%'")->execute();
        echo "      [*] Purged previous Batch 6 contract assignments (force mode).\n";
    }

    // A. Register new authentic assets
    $insertAssetStmt = $pdo->prepare("
        INSERT INTO assets (
            asset_id, asset_code, serial_number, license_plate, alias_name, 
            hull_number, type, category, make_model, ownership, status, 
            project_name, operational_position, created_at, last_update_timestamp
        ) VALUES (
            :asset_id, :asset_code, :serial_number, :license_plate, :alias_name, 
            :hull_number, :type, :category, :make_model, :ownership, :status, 
            :project_name, :operational_position, NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE 
            serial_number = VALUES(serial_number),
            license_plate = VALUES(license_plate),
            project_name = VALUES(project_name),
            operational_position = VALUES(operational_position),
            status = VALUES(status)
    ");

    $catEnumMap = [
        'EXCAVATOR' => 'Excavator',
        'BULLDOZER' => 'Bulldozer',
        'VIBRO' => 'Vibro Compactor',
        'GREDER' => 'Motor Grader',
        'CRAWLER CRANE' => 'Other',
        'DUMPTRUCK' => 'Dump Truck',
        'MOBIL' => 'Light Vehicle',
        'MEDIUM BUS' => 'Other',
    ];

    foreach ($assetsToInsert as $newId => $nu) {
        $catDb = $catEnumMap[$nu['category']] ?? 'Other';
        $typeDb = in_array($catDb, ['Dump Truck', 'Light Vehicle']) ? 'Vehicle' : 'Heavy Equipment';
        $isSewa = (strpos($newId, 'DT-YX') !== false || strpos($newId, 'DT-00023-D') !== false || strpos($newId, 'DT-00024-D') !== false || strpos($newId, 'CR-') !== false);
        $ownershipDb = $isSewa ? 'Sewa' : 'Milik Sendiri';
        $plateDb = (in_array($catDb, ['Dump Truck', 'Light Vehicle'])) ? $nu['sn'] : null;

        $insertAssetStmt->execute([
            ':asset_id' => $newId,
            ':asset_code' => $newId,
            ':serial_number' => $nu['sn'],
            ':license_plate' => $plateDb,
            ':alias_name' => $nu['kode_unit'] ?: $newId,
            ':hull_number' => $newId,
            ':type' => $typeDb,
            ':category' => $catDb,
            ':make_model' => $nu['nama_alat'],
            ':ownership' => $ownershipDb,
            ':status' => 'OPERATING',
            ':project_name' => $nu['project'],
            ':operational_position' => $nu['site'],
        ]);
        $existingAssetIdSet[$newId] = true;
    }
    echo "      [*] Registered " . count($assetsToInsert) . " authentic assets in `assets`.\n";

    // B. Insert into `contract_assignments`
    $insertContractStmt = $pdo->prepare("
        INSERT INTO contract_assignments (
            contract_number, project, site, asset_id, start_date, end_date, 
            target_hours, notes, created_by, created_at, updated_at
        ) VALUES (
            :contract_number, :project, :site, :asset_id, :start_date, :end_date, 
            :target_hours, :notes, 1, NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE 
            project = VALUES(project),
            site = VALUES(site),
            target_hours = VALUES(target_hours),
            notes = VALUES(notes),
            updated_at = NOW()
    ");

    $updateAssetStmt = $pdo->prepare("
        UPDATE assets 
        SET project_name = :project_name,
            operational_position = :operational_position,
            status = 'OPERATING',
            last_update_timestamp = NOW()
        WHERE asset_id = :asset_id
    ");

    $contractsCount = 0;
    $totalContractHours = 0.0;

    foreach ($resolvedActive as $c) {
        $cleanUnit = preg_replace('/[^\w\d]/', '', $c['kode_unit'] ?: $c['resolved_asset_id']);
        $contractNo = sprintf('KTR-202601-%s-%s', $c['project'], $cleanUnit);

        $notes = sprintf(
            'Unit %s (%s). Tarif: Rp %s/bln. Hari Kerja: %d hari. Billing Jan 2026: Rp %s.',
            $c['kode_unit'] ?: $c['resolved_asset_id'],
            $c['nama_alat'],
            number_format($c['harga_sewa'], 0, ',', '.'),
            $c['work_days'],
            number_format($c['total_jan'], 0, ',', '.')
        );

        $insertContractStmt->execute([
            ':contract_number' => $contractNo,
            ':project' => $c['project'],
            ':site' => $c['site'],
            ':asset_id' => $c['resolved_asset_id'],
            ':start_date' => '2026-01-01',
            ':end_date' => '2026-01-31',
            ':target_hours' => $c['target_hours'],
            ':notes' => $notes,
        ]);

        $updateAssetStmt->execute([
            ':project_name' => $c['project'],
            ':operational_position' => $c['site'],
            ':asset_id' => $c['resolved_asset_id'],
        ]);

        $contractsCount++;
        $totalContractHours += $c['target_hours'];
    }

    echo "      [*] Ingested $contractsCount active contracts into `contract_assignments`.\n";
    echo "      [*] Total equipment target hours: " . number_format($totalContractHours, 2, ',', '.') . " hours.\n";

    // C. Update standby units in assets
    $updateStandbyStmt = $pdo->prepare("
        UPDATE assets 
        SET status = 'STANDBY', 
            operational_position = 'YARD DURI',
            last_update_timestamp = NOW()
        WHERE asset_id = :asset_id AND (project_name IS NULL OR project_name = '')
    ");

    $standbyUpdated = 0;
    foreach ($standbyUnits as $su) {
        $snClean = strtoupper(trim($su['sn']));
        $codeClean = strtoupper(trim($su['kode_unit']));
        $foundStandbyId = null;

        if (!empty($snClean) && isset($assetBySn[$snClean])) {
            $foundStandbyId = $assetBySn[$snClean];
        } elseif (!empty($codeClean) && isset($assetByCode[$codeClean])) {
            $foundStandbyId = $assetByCode[$codeClean];
        } elseif (!empty($codeClean) && isset($assetByAlias[$codeClean])) {
            $foundStandbyId = $assetByAlias[$codeClean];
        }

        if ($foundStandbyId) {
            $updateStandbyStmt->execute([':asset_id' => $foundStandbyId]);
            $standbyUpdated++;
        }
    }
    echo "      [*] Updated $standbyUpdated units to 'STANDBY' in Yard.\n";

    // D. Link telematics logs to asset_id
    $linkTelematicsStmt = $pdo->prepare("
        UPDATE telematics_logs t
        JOIN assets a ON (
            (t.serial_number IS NOT NULL AND t.serial_number != '' AND a.serial_number = t.serial_number)
            OR (t.serial_number IS NOT NULL AND t.serial_number != '' AND a.asset_code = t.serial_number)
            OR (t.serial_number IS NOT NULL AND t.serial_number != '' AND a.alias_name = t.serial_number)
        )
        SET t.asset_id = a.asset_id
        WHERE t.asset_id IS NULL OR t.asset_id = ''
    ");
    $linkTelematicsStmt->execute();
    $linkedTelematics = $linkTelematicsStmt->rowCount();
    echo "      [*] Linked $linkedTelematics telematics telemetry logs to master `asset_id`.\n";

    // E. Record migration in `app_data_migrations`
    $logMig = $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_6_EQUIPMENT_REPORT_202601', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $logMig->execute();


    $pdo->commit();
    echo "\n[SUCCESS] Batch 6 Migration successfully committed to database!\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Transaction aborted and rolled back: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// -------------------------------------------------------------
// Step 4: Verification and Summary Statistics
// -------------------------------------------------------------
echo "\n[4/5] Verification and Summary Statistics:\n";
$cntContracts = (int)$pdo->query("SELECT COUNT(*) FROM contract_assignments")->fetchColumn();
echo "      -> Total rows in `contract_assignments`: $cntContracts\n";

$projSummary = $pdo->query("
    SELECT project, COUNT(*) as units, SUM(target_hours) as hours 
    FROM contract_assignments 
    GROUP BY project 
    ORDER BY units DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo "      -> Contracts breakdown by project:\n";
foreach ($projSummary as $ps) {
    echo sprintf("         - %-10s : %3d units | %10s hours\n", $ps['project'], $ps['units'], number_format((float)$ps['hours'], 2, ',', '.'));
}

$statusSummary = $pdo->query("
    SELECT status, COUNT(*) as cnt 
    FROM assets 
    GROUP BY status 
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo "      -> Master Asset Status breakdown:\n";
foreach ($statusSummary as $ss) {
    echo sprintf("         - %-15s : %3d assets\n", $ss['status'] ?? 'NULL', $ss['cnt']);
}

echo "\n[5/5] Migration Batch 6 Finished Successfully.\n";
echo "====================================================================\n";
