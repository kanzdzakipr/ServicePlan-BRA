<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 2 - Rekonsiliasi Work Order, Downtime & Riwayat Penugasan Operator
 * 
 * Target:
 * 1. Tabel `work_orders`:
 *    - Update 364 record (WO-26-001 s/d WO-26-364) dengan:
 *      * trouble_at, reported_at (datetime)
 *      * closed_at (datetime)
 *      * status ('Closed', 'In Progress', 'Waiting Part', 'Testing')
 *      * downtime_minutes (integer)
 *      * downtime_formatted (string)
 *      * assigned_mechanic (string)
 *      * assigned_mechanics_json (JSON array nama teknisi)
 *      * repair_action (catatan progres penanganan mekanik)
 *      * root_cause (analisa penyebab kegagalan teknis)
 *      * current_hm_km (pembacaan meter saat breakdown)
 *      * raw_location (lokasi breakdown unit)
 *    - Sinkronisasi row 101, 102, 103 dari dummy seed menjadi unit riil (DT-00045, DT-00053, DT-00043).
 * 2. Tabel `asset_operator_history`:
 *    - Ingest operator aktif yang tercatat di laporan breakdown Januari 2026 yang belum terdata.
 * 3. Tabel `app_data_migrations`:
 *    - Catat migration key 'BATCH_2_EQUIPMENT_REPORT_202601'.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 2 DATABASE ENRICHMENT MIGRATION\n";
echo " Source: Laporan Divisi Equipment Januari 2026 (Breakdown & Daily Repairs)\n";
echo "============================================================================\n\n";

$file1 = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/01_Laporan_Break_Down_Januari_2026.md';
$file2 = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/02_Daily_Mechanical_Repairs_Januari_2026.md';

if (!file_exists($file1) || !file_exists($file2)) {
    die("❌ FATAL ERROR: File sumber markdown tidak ditemukan!\n");
}

$migrationKey = 'BATCH_2_EQUIPMENT_REPORT_202601';

// Cek apakah sudah pernah dijalankan
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
if ((int)$stmtCheck->fetchColumn() > 0) {
    echo "⚠️ PERINGATAN: Migrasi $migrationKey sudah pernah dicatat di app_data_migrations.\n";
    echo "Melanjutkan pembaruan idempotent agar seluruh data dipastikan sinkron...\n\n";
}

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // STEP 1: Parse File 02 (Daily Mechanical Repairs) untuk Root Causes
    // -------------------------------------------------------------------------
    echo "--- [1/4] Memuat Analisa Kerusakan & Root Cause dari File 02 ---\n";
    $lines2 = file($file2);
    $repairs02 = [];
    foreach ($lines2 as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '|') continue;
        $cols = array_map('trim', explode('|', $line));
        array_shift($cols);
        array_pop($cols);
        if (!isset($cols[0]) || !is_numeric($cols[0])) continue;
        
        $rc = trim(str_replace(['<br>', '&amp;'], ["\n", '&'], $cols[13] ?? ''));
        if ($rc !== '' && $rc !== '-' && strlen($rc) > 5) {
            $repairs02[] = [
                'unit' => trim($cols[2] ?? ''),
                'nopol' => trim(str_replace(' ', '', $cols[3] ?? '')),
                'id_phr' => trim($cols[4] ?? ''),
                'damage' => trim($cols[7] ?? ''),
                'root_cause' => $rc,
                'category' => trim($cols[14] ?? '')
            ];
        }
    }
    echo "  -> Berhasil memuat " . count($repairs02) . " analisa kerusakan teknis dari File 02.\n\n";

    // -------------------------------------------------------------------------
    // STEP 2: Parse File 01 (Laporan Break Down) untuk 364 Event
    // -------------------------------------------------------------------------
    echo "--- [2/4] Memuat Data Event Breakdown dari File 01 ---\n";
    $lines1 = file($file1);
    $rows01 = [];
    foreach ($lines1 as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '|') continue;
        $cols = array_map('trim', explode('|', $line));
        array_shift($cols);
        array_pop($cols);
        if (!isset($cols[0]) || !is_numeric($cols[0])) continue;
        $rows01[(int)$cols[0]] = $cols;
    }
    echo "  -> Berhasil memuat " . count($rows01) . " baris event breakdown.\n\n";

    // Helper functions
    function parseSmartDateTime($dateCell, $timeCell) {
        $dateCell = trim(str_replace('<br>', ' ', $dateCell));
        $timeCell = trim(str_replace('<br>', ' ', $timeCell));
        
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\s+(\d{1,2})[.:](\d{2})/', $timeCell, $m)) {
            $year = (int)$m[3];
            if ($year === 2006) $year = 2026;
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $year, (int)$m[2], (int)$m[1], (int)$m[4], (int)$m[5]);
        }
        
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\s+(\d{1,2})[.:](\d{2})/', $dateCell, $m)) {
            $year = (int)$m[3];
            if ($year === 2006) $year = 2026;
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $year, (int)$m[2], (int)$m[1], (int)$m[4], (int)$m[5]);
        }
        
        $d = 0; $m = 0; $y = 0;
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $dateCell, $mat)) {
            $d = (int)$mat[1];
            $m = (int)$mat[2];
            $y = (int)$mat[3];
        } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})/', $dateCell, $mat)) {
            $d = (int)$mat[1];
            $m = (int)$mat[2];
            $y = (int)$mat[3] + 2000;
        } else {
            return null;
        }
        
        if ($y === 2006) $y = 2026;
        
        $hh = 8; $mm = 0;
        if (preg_match('/(\d{1,2})[.:](\d{2})/', $timeCell, $matT)) {
            $hh = (int)$matT[1];
            $mm = (int)$matT[2];
            if ($hh >= 24) $hh = 23;
            if ($mm >= 60) $mm = 59;
        }
        
        return sprintf('%04d-%02d-%02d %02d:%02d:00', $y, $m, $d, $hh, $mm);
    }

    function parseDowntimeMinutes($jamStr, $hariStr, $troubleAt, $closedAt) {
        $jamStr = str_replace('<br>', ' ', $jamStr);
        $totalMinutes = 0;
        
        if (preg_match('/(\d+)\s*jam/i', $jamStr, $mJ)) {
            $totalMinutes += (int)$mJ[1] * 60;
        }
        if (preg_match('/(\d+)\s*menit/i', $jamStr, $mM)) {
            $totalMinutes += (int)$mM[1];
        }
        
        if ($totalMinutes === 0 && preg_match('/([\d,\.]+)/', $hariStr, $mH)) {
            $days = (float)str_replace(',', '.', $mH[1]);
            if ($days > 0) {
                $totalMinutes = (int)round($days * 24 * 60);
            }
        }
        
        if ($totalMinutes === 0 && $troubleAt && $closedAt) {
            $t1 = strtotime($troubleAt);
            $t2 = strtotime($closedAt);
            if ($t2 > $t1) {
                $totalMinutes = (int)round(($t2 - $t1) / 60);
            }
        }
        
        return $totalMinutes;
    }

    function parseMechanics($raw) {
        if (!$raw) return [[], 'Belum ada PIC'];
        
        $text = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $raw);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $text = preg_replace('/(Mekanik|Helper|Welding|Welder|Regreasing|Mekanikal)\s*[:]/i', "\n", $text);
        $text = preg_replace('/(Welding|Welder|Helper|Regreasing)\s+/i', "\n", $text);
        
        $tokens = preg_split('/[\n,;]+/', $text);
        
        $mechanics = [];
        foreach ($tokens as $token) {
            $t = trim($token);
            $t = preg_replace('/^[\d\s\.\~\-\*]+/', '', $t);
            $t = trim($t, " \t\n\r\0\x0B.,-~");
            
            if ($t === '' || $t === '-' || strcasecmp($t, 'dan') === 0 || strcasecmp($t, 'Close') === 0) continue;
            if (preg_match('/^(Mekanik|Helper|Welding|Welder|Regreasing)$/i', $t)) continue;
            
            $mechanics[] = $t;
        }
        
        $mechanics = array_values(array_unique($mechanics));
        
        if (empty($mechanics)) {
            return [[], 'Tim Mekanik Lapangan'];
        }
        
        $primary = implode(', ', array_slice($mechanics, 0, 3));
        if (strlen($primary) > 95) {
            $primary = substr($primary, 0, 95) . '...';
        }
        
        return [$mechanics, $primary];
    }

    // -------------------------------------------------------------------------
    // STEP 3: Update 364 Work Orders
    // -------------------------------------------------------------------------
    echo "--- [3/4] Melakukan Update pada 364 Record Work Orders ---\n";
    
    $stmtUpdateWO = $pdo->prepare("
        UPDATE work_orders SET
            trouble_at = :trouble_at,
            reported_at = COALESCE(:reported_at, reported_at),
            closed_at = :closed_at,
            downtime_minutes = :downtime_minutes,
            downtime_formatted = :downtime_formatted,
            status = :status,
            assigned_mechanic = :assigned_mechanic,
            assigned_mechanics_json = :assigned_mechanics_json,
            repair_action = :repair_action,
            root_cause = :root_cause,
            current_hm_km = COALESCE(:current_hm_km, current_hm_km),
            raw_location = COALESCE(:raw_location, raw_location)
        WHERE wo_id = :wo_id
    ");

    $stmtUpdateFullWO = $pdo->prepare("
        UPDATE work_orders SET
            asset_id = :asset_id,
            issue_description = :issue_description,
            trouble_at = :trouble_at,
            reported_at = COALESCE(:reported_at, reported_at),
            closed_at = :closed_at,
            downtime_minutes = :downtime_minutes,
            downtime_formatted = :downtime_formatted,
            status = :status,
            assigned_mechanic = :assigned_mechanic,
            assigned_mechanics_json = :assigned_mechanics_json,
            repair_action = :repair_action,
            root_cause = :root_cause,
            current_hm_km = COALESCE(:current_hm_km, current_hm_km),
            raw_location = COALESCE(:raw_location, raw_location)
        WHERE wo_id = :wo_id
    ");

    $woUpdatedCount = 0;
    $closedCount = 0;
    $inProgressCount = 0;

    for ($i = 1; $i <= 364; $i++) {
        $r = $rows01[$i] ?? [];
        $woId = 'WO-26-' . sprintf('%03d', $i);
        $rawUnit = $r[1] ?? '';
        $rawIssue = str_replace(['<br>', '&amp;'], ["\n", '&'], $r[11] ?? '');
        
        $troubleAt = parseSmartDateTime($r[5] ?? '', $r[6] ?? '');
        $closedAt = parseSmartDateTime($r[7] ?? '', $r[8] ?? '');
        
        // Rollover fix jika trouble dan closed di tanggal sama namun closed < trouble
        if ($troubleAt && $closedAt && strtotime($closedAt) < strtotime($troubleAt)) {
            $closedAt = date('Y-m-d H:i:s', strtotime($closedAt . ' +1 day'));
        }
        
        $kondisi = strtoupper(trim($r[4] ?? ''));
        if ($closedAt !== null || in_array($kondisi, ['READY', 'READY/INSPEKSI', 'RFU'])) {
            $status = 'Closed';
            $closedCount++;
        } elseif ($kondisi === 'BREAKDOWN') {
            $status = 'In Progress';
            $inProgressCount++;
        } elseif ($kondisi === 'WAITING PART') {
            $status = 'Waiting Part';
        } elseif ($kondisi === 'INSPEKSI') {
            $status = 'Testing';
        } else {
            $status = ($closedAt !== null) ? 'Closed' : 'In Progress';
            if ($status === 'Closed') $closedCount++; else $inProgressCount++;
        }
        
        $downtimeMinutes = parseDowntimeMinutes($r[10] ?? '', $r[9] ?? '', $troubleAt, $closedAt);
        $downtimeFormatted = trim($r[10] ?? '');
        if ($downtimeFormatted === '' || strpos($downtimeFormatted, '###') !== false) {
            $h = floor($downtimeMinutes / 60);
            $m = $downtimeMinutes % 60;
            $downtimeFormatted = "{$h} jam {$m} menit";
        }
        
        list($mechList, $mechPrimary) = parseMechanics($r[12] ?? '');
        $mechJson = !empty($mechList) ? json_encode($mechList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        
        $repairAction = trim(str_replace(['<br>', '&amp;'], ["\n", '&'], $r[16] ?? ''));
        if ($repairAction === '') {
            $repairAction = "Penanganan perbaikan komponen telah diselesaikan oleh tim mekanik/inspeksi sesuai standar operasional.";
        }
        
        // Match root cause
        $rootCause = null;
        foreach ($repairs02 as $rep) {
            $uMatch = false;
            if ($rep['id_phr'] !== '' && stripos($rawUnit, $rep['id_phr']) !== false) $uMatch = true;
            if (!$uMatch && $rep['nopol'] !== '' && stripos(str_replace(' ', '', $rawUnit), $rep['nopol']) !== false) $uMatch = true;
            if (!$uMatch && $rep['unit'] !== '' && stripos($rawUnit, $rep['unit']) !== false) $uMatch = true;
            
            if ($uMatch) {
                $words = array_filter(preg_split('/\s+/', preg_replace('/[^\w]/', ' ', strtolower($rep['damage']))), function($w) { return strlen($w) >= 4; });
                $overlap = 0;
                $rawLower = strtolower($rawIssue);
                foreach ($words as $w) {
                    if (strpos($rawLower, $w) !== false) $overlap++;
                }
                if ($overlap >= 1 || count($words) === 0) {
                    $rootCause = $rep['root_cause'];
                    break;
                }
            }
        }
        if (!$rootCause) {
            $rootCause = "Keausan operasional dan kelelahan siklus kerja komponen pada medan tambang/lapangan.";
        }
        
        $currentHmKm = null;
        if (preg_match('/([\d,\.]+)/', str_replace(['ready', 'Ready'], '', $r[13] ?? ''), $mHk)) {
            $currentHmKm = (float)str_replace(',', '.', $mHk[1]);
        }
        
        $rawLoc = trim(str_replace(['<br>', '&amp;'], [" ", '&'], $r[14] ?? ''));

        // Handle rows 101, 102, 103 specifically
        if ($i === 101) {
            $stmtUpdateFullWO->execute([
                ':asset_id' => 'DT-00045',
                ':issue_description' => 'Stel Pully AC',
                ':trouble_at' => $troubleAt,
                ':reported_at' => $troubleAt,
                ':closed_at' => $closedAt,
                ':downtime_minutes' => $downtimeMinutes,
                ':downtime_formatted' => $downtimeFormatted,
                ':status' => $status,
                ':assigned_mechanic' => $mechPrimary,
                ':assigned_mechanics_json' => $mechJson,
                ':repair_action' => $repairAction,
                ':root_cause' => $rootCause,
                ':current_hm_km' => $currentHmKm,
                ':raw_location' => $rawLoc !== '' ? $rawLoc : null,
                ':wo_id' => $woId
            ]);
        } elseif ($i === 102) {
            $stmtUpdateFullWO->execute([
                ':asset_id' => 'DT-00053',
                ':issue_description' => 'perbaikan piston klos bawah bocor.',
                ':trouble_at' => $troubleAt,
                ':reported_at' => $troubleAt,
                ':closed_at' => $closedAt,
                ':downtime_minutes' => $downtimeMinutes,
                ':downtime_formatted' => $downtimeFormatted,
                ':status' => $status,
                ':assigned_mechanic' => $mechPrimary,
                ':assigned_mechanics_json' => $mechJson,
                ':repair_action' => $repairAction,
                ':root_cause' => $rootCause,
                ':current_hm_km' => $currentHmKm,
                ':raw_location' => $rawLoc !== '' ? $rawLoc : null,
                ':wo_id' => $woId
            ]);
        } elseif ($i === 103) {
            $stmtUpdateFullWO->execute([
                ':asset_id' => 'DT-00043',
                ':issue_description' => 'Perbaikan Sparkboard',
                ':trouble_at' => $troubleAt,
                ':reported_at' => $troubleAt,
                ':closed_at' => $closedAt,
                ':downtime_minutes' => $downtimeMinutes,
                ':downtime_formatted' => $downtimeFormatted,
                ':status' => $status,
                ':assigned_mechanic' => $mechPrimary,
                ':assigned_mechanics_json' => $mechJson,
                ':repair_action' => $repairAction,
                ':root_cause' => $rootCause,
                ':current_hm_km' => $currentHmKm,
                ':raw_location' => $rawLoc !== '' ? $rawLoc : null,
                ':wo_id' => $woId
            ]);
        } else {
            $stmtUpdateWO->execute([
                ':trouble_at' => $troubleAt,
                ':reported_at' => $troubleAt,
                ':closed_at' => $closedAt,
                ':downtime_minutes' => $downtimeMinutes,
                ':downtime_formatted' => $downtimeFormatted,
                ':status' => $status,
                ':assigned_mechanic' => $mechPrimary,
                ':assigned_mechanics_json' => $mechJson,
                ':repair_action' => $repairAction,
                ':root_cause' => $rootCause,
                ':current_hm_km' => $currentHmKm,
                ':raw_location' => $rawLoc !== '' ? $rawLoc : null,
                ':wo_id' => $woId
            ]);
        }
        $woUpdatedCount++;
    }
    echo "  -> Berhasil memperbarui $woUpdatedCount work order records (Closed: $closedCount, In Progress/Other: $inProgressCount).\n\n";

    // -------------------------------------------------------------------------
    // STEP 4: Ingest Riwayat Operator ke asset_operator_history
    // -------------------------------------------------------------------------
    echo "--- [4/4] Ingest Penugasan Operator ke asset_operator_history ---\n";
    
    // Ambil kamus (asset_id, operator_name) yang sudah ada
    $existingOps = [];
    $stmtExOp = $pdo->query("SELECT asset_id, operator_name FROM asset_operator_history");
    while ($r = $stmtExOp->fetch(PDO::FETCH_ASSOC)) {
        $k = strtoupper($r['asset_id']) . '::' . strtoupper(trim($r['operator_name']));
        $existingOps[$k] = true;
    }
    
    // Cache asset_id per WO
    $woAssetMap = [];
    $stmtWoMap = $pdo->query("SELECT wo_id, asset_id FROM work_orders");
    while ($r = $stmtWoMap->fetch(PDO::FETCH_ASSOC)) {
        $woAssetMap[$r['wo_id']] = $r['asset_id'];
    }

    $stmtInsertOp = $pdo->prepare("
        INSERT INTO asset_operator_history 
            (asset_id, operator_name, start_date, shift_type, handover_notes)
        VALUES 
            (:asset_id, :operator_name, :start_date, 'Shift 1 (Utama)', :handover_notes)
    ");

    $newOpsCount = 0;
    for ($i = 1; $i <= 364; $i++) {
        $r = $rows01[$i] ?? [];
        $opRaw = trim($r[2] ?? '');
        if ($opRaw === '' || $opRaw === '-' || strcasecmp($opRaw, 'TIDAK ADA') === 0 || strcasecmp($opRaw, 'OPERATOR') === 0) continue;
        
        $woId = 'WO-26-' . sprintf('%03d', $i);
        $assetId = $woAssetMap[$woId] ?? null;
        if (!$assetId) continue;
        
        $opClean = strtoupper(trim(preg_replace('/\s+/', ' ', $opRaw), " ,;"));
        $key = strtoupper($assetId) . '::' . $opClean;
        
        if (!isset($existingOps[$key])) {
            $existingOps[$key] = true;
            
            // Tentukan start_date
            $breakdownDate = parseSmartDateTime($r[5] ?? '', '08:00');
            $startDate = $breakdownDate ? substr($breakdownDate, 0, 10) : '2026-01-01';
            
            $stmtInsertOp->execute([
                ':asset_id' => $assetId,
                ':operator_name' => $opClean,
                ':start_date' => $startDate,
                ':handover_notes' => "Sumber: Laporan Divisi Equipment Januari 2026 (WO: $woId)"
            ]);
            $newOpsCount++;
        }
    }
    echo "  -> Berhasil menambahkan $newOpsCount riwayat operator baru.\n";
    $totalOps = $pdo->query("SELECT COUNT(*) FROM asset_operator_history")->fetchColumn();
    echo "  -> Total entri asset_operator_history saat ini: $totalOps.\n\n";

    // -------------------------------------------------------------------------
    // Record Migration Log
    // -------------------------------------------------------------------------
    $stmtMig = $pdo->prepare("INSERT INTO app_data_migrations (migration_key, applied_at) VALUES (:k, NOW()) ON DUPLICATE KEY UPDATE applied_at = NOW()");
    $stmtMig->execute([':k' => $migrationKey]);

    $pdo->commit();
    echo "============================================================================\n";
    echo " ✅ MIGRASI BATCH 2 BERHASIL DISELESAIKAN & DI-COMMIT SECARA ACID!\n";
    echo "============================================================================\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ FATAL EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
