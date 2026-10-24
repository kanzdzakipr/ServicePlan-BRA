<?php
declare(strict_types=1);

/**
 * Strategy: Step 5 (Batch 6: Operational Vehicles Ingestion - Light Vehicles)
 * Source:
 *   - material/ASSET/02_List_Data_Aset_BRA.md (Section T: KENDARAAN OPERASIONAL, Baris 259-319)
 *   - material/ASSET/09_Sheet27.md (Detail PIC, Jabatan, Project)
 * Target:
 *   - `assets` (Insert 40 operational vehicles as Category 'Light Vehicle')
 *   - `asset_operator_history` (Audit log of operator/PIC assignments)
 */

$isCommit = in_array('--commit', $argv, true);
$baseDir = dirname(__DIR__);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

function clean_plate(?string $s): string {
    if (!$s) return '';
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', $s));
}

// 1. Ambil data aset yang sudah ada di database untuk mencegah duplikasi
$dbAssets = $pdo->query("SELECT asset_id, asset_code, license_plate, previous_license_plate FROM assets")->fetchAll();
$dbPlates = [];
foreach ($dbAssets as $a) {
    if ($a['license_plate']) $dbPlates[clean_plate($a['license_plate'])] = $a['asset_id'];
    if ($a['previous_license_plate']) $dbPlates[clean_plate($a['previous_license_plate'])] = $a['asset_id'];
}

// 2. Parse 09_Sheet27.md untuk mengambil data PIC, Jabatan, dan Project
$lines27 = file($baseDir . '/material/ASSET/09_Sheet27.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$picData = [];
foreach ($lines27 as $line) {
    $line = trim($line);
    if (!str_starts_with($line, '|')) continue;
    $c = array_map('trim', explode('|', substr($line, 1, -1)));
    if (!isset($c[1]) || !ctype_digit($c[1])) continue;

    $model27 = $c[2] ?? '';
    $year27 = $c[3] ?? '';
    $plat27 = $c[4] ?? '';
    $pic27 = $c[5] ?? '';
    $jabatan27 = $c[6] ?? '';
    $proj27 = $c[7] ?? '';

    foreach (preg_split('/[\/\,]/', $plat27) as $p) {
        $cp = clean_plate($p);
        if ($cp) {
            $picData[$cp] = [
                'model' => $model27,
                'year' => $year27,
                'plat' => $plat27,
                'pic' => $pic27,
                'jabatan' => $jabatan27,
                'project' => $proj27
            ];
        }
    }
}

// 3. Parse 02_List_Data_Aset_BRA.md baris 260 s.d. 319 (Section T)
$lines02 = file($baseDir . '/material/ASSET/02_List_Data_Aset_BRA.md', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$candidates = [];

foreach ($lines02 as $line) {
    $line = trim($line);
    if (!str_starts_with($line, '|')) continue;
    $c = array_map('trim', explode('|', substr($line, 1, -1)));
    if (!isset($c[0]) || !ctype_digit($c[0])) continue;

    $rowNum = (int)$c[0];
    if ($rowNum < 260 || $rowNum > 319) continue;

    $noUrut = (int)$c[1];
    $jenisAlat = $c[2] ?: 'LIGHT VEHICLE';
    $namaAset = $c[3] ?? '';
    $dealer = $c[4] ?? '';
    $tahunBeli = $c[5] ?? '';
    $platRaw = $c[7] ?? '';
    $operator02 = $c[9] ?? ''; // Kolom 9 adalah operator
    $kodeUnit = $c[10] ?? '';   // Kolom 10 adalah kode unit
    $hull = $c[12] ?? '';
    $project02 = $c[13] ?? '';
    $lokasi02 = $c[14] ?? '';
    $statusRaw = $c[15] ?? 'AKTIF';
    $statusLunas = $c[21] ?? $c[18] ?? '';

    // Cek duplikasi di DB
    $isFound = false;
    foreach (preg_split('/[\/\,]/', $platRaw) as $p) {
        $cp = clean_plate($p);
        if ($cp && isset($dbPlates[$cp])) {
            $isFound = true;
            break;
        }
    }

    if ($isFound) continue;

    // Cross-reference dengan Sheet27
    $picInfo = null;
    foreach (preg_split('/[\/\,]/', $platRaw) as $p) {
        $cp = clean_plate($p);
        if ($cp && isset($picData[$cp])) {
            $picInfo = $picData[$cp];
            break;
        }
    }

    $finalPic = $picInfo['pic'] ?? $operator02;
    $finalProject = $picInfo['project'] ?? $project02;
    $finalJabatan = $picInfo['jabatan'] ?? '';

    // Pisahkan plat utama dan plat sebelumnya jika ada tanda '/'
    $platParts = array_map('trim', explode('/', $platRaw));
    $platUtama = $platParts[0] ?? '';
    $platLama = $platParts[1] ?? null;

    // Format plat utama rapi
    if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/i', preg_replace('/\s+/', '', $platUtama), $pm)) {
        $platUtamaFormatted = strtoupper("{$pm[1]} {$pm[2]} {$pm[3]}");
    } else {
        $platUtamaFormatted = strtoupper($platUtama);
    }

    if ($platLama && preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/i', preg_replace('/\s+/', '', $platLama), $pm)) {
        $platLamaFormatted = strtoupper("{$pm[1]} {$pm[2]} {$pm[3]}");
    } else {
        $platLamaFormatted = $platLama ? strtoupper($platLama) : null;
    }

    // Ownership mapping
    $ownership = 'Milik Sendiri';
    if (stripos($statusLunas, 'LEASING') !== false) {
        $ownership = 'Leasing';
    } elseif (stripos($statusLunas, 'SEWA') !== false || stripos($finalPic, 'SEWA') !== false) {
        $ownership = 'Sewa';
    }

    // Status mapping
    $status = 'READY';
    if (strtoupper($statusRaw) === 'AKTIF') {
        $status = (!empty($finalPic) && $finalPic !== '-') ? 'OPERATING' : 'READY';
    } elseif (strtoupper($statusRaw) === 'STANDBY') {
        $status = 'STANDBY';
    }

    // Location ID mapping
    $locId = null;
    $upperLok = strtoupper($lokasi02 . ' ' . $finalProject);
    if (strpos($upperLok, 'JAKARTA') !== false || strpos($upperLok, 'HO') !== false) {
        $locId = 5; // Site Sunter Area Stadium (HO Jakarta)
    } elseif (strpos($upperLok, 'PEKANBARU') !== false || strpos($upperLok, 'PKU') !== false) {
        $locId = 64; // Head Office Pekanbaru
    } elseif (strpos($upperLok, 'WUR') !== false) {
        $locId = 15; // WUR EW Project
    } elseif (strpos($upperLok, 'DURI') !== false || strpos($upperLok, 'EQUIPMENT') !== false) {
        $locId = 12; // Yard KM 12 Duri
    }

    // Clean Make/Model
    $cleanModel = trim($namaAset);
    if (empty($cleanModel) && !empty($dealer)) {
        $cleanModel = $dealer;
    }
    if ($dealer && stripos($cleanModel, $dealer) === false) {
        $cleanModel = $dealer . ' ' . $cleanModel;
    }

    $candidates[] = [
        'no_dokumen' => $noUrut,
        'kode_unit' => $kodeUnit,
        'nama_aset' => $cleanModel,
        'dealer' => $dealer ?: 'TOYOTA',
        'tahun' => (int)$tahunBeli ?: 2022,
        'plat_nomor' => $platUtamaFormatted,
        'plat_lama' => $platLamaFormatted,
        'operator' => (!empty($finalPic) && $finalPic !== '-') ? trim($finalPic) : null,
        'jabatan' => $finalJabatan,
        'project' => $finalProject,
        'lokasi' => $lokasi02,
        'ownership' => $ownership,
        'status' => $status,
        'location_id' => $locId
    ];
}

// 4. Alokasikan ID:
// - 27 unit pertama mengisi LV-00001 s.d. LV-00027
// - 13 unit sisa mengisi LV-00052 s.d. LV-00064
$allocatedPlan = [];
$idx27 = 1;
$idx52 = 52;

foreach ($candidates as $idx => $c) {
    if ($idx < 27) {
        $aid = sprintf('LV-%05d', $idx27++);
    } else {
        $aid = sprintf('LV-%05d', $idx52++);
    }

    $c['asset_id'] = $aid;
    $c['asset_code'] = $aid;
    $allocatedPlan[] = $c;
}

echo "================ STRATEGY: STEP 5 (BATCH 6 OPERATIONAL VEHICLES INGESTION) ================\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written") . "\n";
echo "Total Operational Vehicles to Insert: " . count($allocatedPlan) . " units\n\n";

echo "Alokasi Asset ID Range:\n";
echo "  - Slot 1 (LV-00001 s.d. LV-00027) : 27 units\n";
echo "  - Slot 2 (LV-00052 s.d. LV-00064) : 13 units\n\n";

echo "Sample 15 Unit Kendaraan Operasional yang Akan Disuntikkan:\n";
echo str_repeat('-', 110) . "\n";
echo sprintf("%-10s | %-6s | %-25s | %-13s | %-18s | %-14s | %s\n", "Asset ID", "Kode", "Model", "Plat Nomor", "PIC / Operator", "Project", "Ownership");
echo str_repeat('-', 110) . "\n";
foreach (array_slice($allocatedPlan, 0, 15) as $p) {
    echo sprintf("%-10s | %-6s | %-25s | %-13s | %-18s | %-14s | %s\n",
        $p['asset_id'],
        $p['kode_unit'] ?: '-',
        substr($p['nama_aset'], 0, 25),
        $p['plat_nomor'],
        substr($p['operator'] ?: '-', 0, 18),
        substr($p['project'] ?: '-', 0, 14),
        $p['ownership']
    );
}
echo str_repeat('-', 110) . "\n";

if ($isCommit) {
    echo "\nMengeksekusi penambahan 40 unit kendaraan operasional ke database dalam transaksi...\n";
    $pdo->beginTransaction();
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO assets (
                asset_id, asset_code, license_plate, previous_license_plate,
                alias_name, hull_number, type, category, make_model, dealer,
                year_manufacture, ownership, status, current_location_id,
                project_name, operational_position, raw_location_notes,
                current_operator_name, is_active, created_at, last_update_timestamp
            ) VALUES (
                :asset_id, :asset_code, :license_plate, :previous_license_plate,
                :alias_name, :hull_number, 'Light Vehicle', 'Light Vehicle', :make_model, :dealer,
                :year_manufacture, :ownership, :status, :current_location_id,
                :project_name, :operational_position, :raw_location_notes,
                :current_operator_name, 1, NOW(), NOW()
            )
        ");

        $historyStmt = $pdo->prepare("
            INSERT INTO asset_operator_history (
                asset_id, operator_name, start_date, shift_type, handover_notes, created_at
            ) VALUES (
                :asset_id, :operator_name, CURDATE(), 'General Shift', :notes, NOW()
            )
        ");

        $insertedCount = 0;
        $historyCount = 0;

        foreach ($allocatedPlan as $p) {
            $notes = "Project: {$p['project']}";
            if ($p['jabatan']) $notes .= " | Jabatan: {$p['jabatan']}";
            if ($p['lokasi']) $notes .= " | Lokasi: {$p['lokasi']}";

            $insertStmt->execute([
                ':asset_id' => $p['asset_id'],
                ':asset_code' => $p['asset_code'],
                ':license_plate' => $p['plat_nomor'],
                ':previous_license_plate' => $p['plat_lama'],
                ':alias_name' => $p['kode_unit'] ?: $p['asset_id'],
                ':hull_number' => $p['kode_unit'] ?: $p['asset_id'],
                ':make_model' => $p['nama_aset'],
                ':dealer' => $p['dealer'],
                ':year_manufacture' => $p['tahun'],
                ':ownership' => $p['ownership'],
                ':status' => $p['status'],
                ':current_location_id' => $p['location_id'],
                ':project_name' => $p['project'],
                ':operational_position' => $p['lokasi'],
                ':raw_location_notes' => $notes,
                ':current_operator_name' => $p['operator']
            ]);
            $insertedCount++;

            if (!empty($p['operator'])) {
                $historyStmt->execute([
                    ':asset_id' => $p['asset_id'],
                    ':operator_name' => $p['operator'],
                    ':notes' => "Inisialisasi armada kendaraan operasional. {$notes}"
                ]);
                $historyCount++;
            }
        }

        $pdo->commit();
        echo "SUCCESS: {$insertedCount} unit kendaraan operasional berhasil ditambahkan ke database `assets`!\n";
        echo "SUCCESS: {$historyCount} riwayat penugasan operator berhasil dicatat di `asset_operator_history`!\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: Transaksi dibatalkan. Alasan: " . $e->getMessage() . "\n");
    }
} else {
    echo "\nUntuk mengeksekusi langsung ke database, jalankan dengan flag --commit.\n";
}
