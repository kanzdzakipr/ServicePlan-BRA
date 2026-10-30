<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 29 - Injeksi Penuh Event Breakdown & Ready Februari 2026
 * 
 * Target:
 * 1. Tabel `work_orders`:
 *    - Injeksi 342 Work Orders baru untuk periode 1 s.d. 20 Februari 2026 (WO-26-843 s/d WO-26-1184).
 *    - Pemetaan field lengkap: asset_id, hull_number, equipment_type, current_hm_km, trouble_at,
 *      closed_at, status, priority, assigned_mechanic, assigned_mechanics_json, issue_description,
 *      repair_action, root_cause, downtime_minutes, downtime_formatted, parts_requirement, parts_requirement_note.
 * 2. Tabel `assets`:
 *    - Update status operasional armada terkini (status = 'READY' / 'BREAKDOWN' / 'INSPEKSI') berdasarkan log terakhir Februari 2026.
 * 3. Tabel `app_data_migrations`:
 *    - Catat migration key 'BATCH_29_INJECT_FEB_2026_WOS'.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';
require_once __DIR__ . '/analyze_lp_deep.php';
require_once __DIR__ . '/lp_bdown_asset_resolver.php';

$pdo = Database::getInstance();
$resolver = new LpAssetResolver($pdo);

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 29 FEBRUARY 2026 WORK ORDERS INJECTION\n";
echo " Source: material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI/ (01 & 04)\n";
echo "============================================================================\n\n";

$migrationKey = 'BATCH_29_INJECT_FEB_2026_WOS';

$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
if ((int)$stmtCheck->fetchColumn() > 0) {
    echo "⚠️ PERINGATAN: Migrasi $migrationKey sudah pernah dicatat di app_data_migrations.\n";
    echo "Melanjutkan pembaruan idempotent agar seluruh data dipastikan sinkron...\n\n";
}

$baseDir = dirname(__DIR__) . '/material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI';
$c1 = parseCellMarkdown($baseDir . '/01_des_25_peb_26.md');
$c4 = parseCellMarkdown($baseDir . '/04_avibility.md');

// 1. Index delay pengadaan dari Sheet 04 (AVIBILITY)
$delayMap = [];
foreach ($c4 as $r => $cols) {
    if ($r < 7) continue;
    $unitRaw = $cols['E']['val'] ?? '';
    $downRaw = $cols['I']['val'] ?? '';
    if (!is_numeric($downRaw) || $downRaw < 40000) continue;
    $downDate = date('Y-m-d', (int)(($downRaw - 25569) * 86400));
    $assetId = $resolver->resolve($unitRaw);
    if ($assetId) {
        $delayDays = is_numeric($cols['M']['val'] ?? null) ? (float)$cols['M']['val'] : 0.0;
        $delayHours = $delayDays * 24.0;
        $repairHours = is_numeric($cols['O']['val'] ?? null) ? (float)$cols['O']['val'] : 0.0;
        $delayMap[$assetId . '|' . $downDate] = [
            'delay_hours' => round($delayHours, 2),
            'repair_hours' => round($repairHours, 2),
            'delay_days' => $delayDays
        ];
    }
}
echo "Indexed delay & procurement metrics from Sheet 04: " . count($delayMap) . " records.\n";

// 2. Tentukan sequence WO awal
$stmtSeq = $pdo->query("SELECT wo_id FROM work_orders WHERE wo_id REGEXP '^WO-26-[0-9]{3,4}$' ORDER BY CAST(SUBSTRING(wo_id, 7) AS UNSIGNED) DESC LIMIT 1");
$lastWo = $stmtSeq->fetchColumn();
$currentWoSeq = 842;
if ($lastWo && preg_match('/^WO-26-(\d+)$/', $lastWo, $m)) {
    $currentWoSeq = (int)$m[1];
}
echo "Starting sequence: WO-26-" . ($currentWoSeq + 1) . "\n\n";

$pdo->beginTransaction();

try {
    $insertedCount = 0;
    $unitLastStatus = [];

    $stmtInsert = $pdo->prepare("
        INSERT INTO work_orders (
            wo_id, asset_id, hull_number, equipment_type, current_hm_km, trouble_at,
            reported_at, raw_location, issue_description, downtime_formatted, downtime_minutes,
            is_downtime, is_forced_operation, status, priority, assigned_mechanic,
            assigned_mechanics_json, root_cause, repair_action, closed_at,
            maintenance_type, parts_requirement, parts_requirement_note
        ) VALUES (
            :wo_id, :asset_id, :hull, :type, :hm_km, :trouble_at,
            :reported_at, :raw_loc, :issue, :downtime_fmt, :downtime_min,
            1, 0, :status, :priority, :mechanic,
            :mech_json, :root_cause, :repair_action, :closed_at,
            :maint_type, :parts_req, :parts_note
        ) ON DUPLICATE KEY UPDATE
            repair_action = VALUES(repair_action),
            parts_requirement = VALUES(parts_requirement),
            parts_requirement_note = VALUES(parts_requirement_note)
    ");

    $stmtCheckExisting = $pdo->prepare("SELECT wo_id FROM work_orders WHERE asset_id = :aid AND DATE(trouble_at) = :d");

    foreach ($c1 as $r => $cols) {
        if (!isset($cols['F']) || !isset($cols['J'])) continue;
        $rawUnit = $cols['F']['val'];
        $dateVal = $cols['J']['val'];
        if (!is_numeric($dateVal) || $dateVal < 40000) continue;

        $downDate = date('Y-m-d', (int)(($dateVal - 25569) * 86400));
        
        // Khusus bulan Februari 2026
        if ($downDate < '2026-02-01') continue;

        $assetId = $resolver->resolve($rawUnit);
        if (!$assetId) continue;

        $assetInfo = $resolver->getAssetInfo($assetId);
        $hull = $assetInfo['hull_number'] ?? $assetId;
        $equipType = $assetInfo['category'] ?? ($assetInfo['type'] ?? 'Heavy Equipment');

        // Check if already injected (idempotent protection)
        $stmtCheckExisting->execute([':aid' => $assetId, ':d' => $downDate]);
        $existingWoId = $stmtCheckExisting->fetchColumn();
        if ($existingWoId) {
            continue; // Sudah terinjeksi, skip insert
        }

        // Parse trouble_at datetime
        $jamVal = $cols['K']['val'] ?? $dateVal;
        $troubleAt = $downDate . ' 08:00:00';
        if (is_numeric($jamVal)) {
            $sec = (int)(($jamVal - 25569) * 86400);
            $troubleAt = date('Y-m-d H:i:s', $sec);
        }

        // Parse ready_at datetime
        $readyAt = null;
        $readyDateVal = $cols['L']['val'] ?? null;
        $readyJamVal = $cols['M']['val'] ?? null;
        if (is_numeric($readyJamVal) && $readyJamVal > 40000) {
            $readySec = (int)(($readyJamVal - 25569) * 86400);
            $readyAt = date('Y-m-d H:i:s', $readySec);
        } elseif (is_numeric($readyDateVal) && $readyDateVal > 40000) {
            $readySec = (int)(($readyDateVal - 25569) * 86400) + (17 * 3600);
            $readyAt = date('Y-m-d H:i:s', $readySec);
        }

        // Parse downtime hours & formatted
        $downtimeHours = 0.0;
        if (isset($cols['O']['val']) && is_numeric($cols['O']['val'])) {
            $downtimeHours = (float)$cols['O']['val'] * 24.0;
        } elseif ($readyAt !== null) {
            $diffSec = strtotime($readyAt) - strtotime($troubleAt);
            if ($diffSec > 0) $downtimeHours = $diffSec / 3600.0;
        }
        $downtimeMin = (int)round($downtimeHours * 60);
        $hoursPart = floor($downtimeMin / 60);
        $minPart = $downtimeMin % 60;
        $downtimeFmt = "{$hoursPart} jam {$minPart} menit";

        $issue = trim($cols['S']['val'] ?? 'Pemeriksaan / Perbaikan Unit');
        $rawMech = trim($cols['T']['val'] ?? 'Tim Mekanik Workshop');
        $mechanic = mb_substr($rawMech, 0, 95);
        $rawLoc = trim($cols['V']['val'] ?? (trim($cols['H']['val'] ?? 'Yard PT BRA KM 12')));
        $location = mb_substr($rawLoc, 0, 250);
        $progress = trim($cols['X']['val'] ?? '');
        $condition = strtoupper(trim($cols['I']['val'] ?? 'READY'));

        // KM / HM down parsing (wajar 0 s.d. 999,999)
        $hmKmVal = null;
        if (isset($cols['U']['val'])) {
            $rawText = trim($cols['U']['val']);
            if (preg_match('/\b([0-9]{1,6}(?:\.[0-9]{1,2})?)\b/', $rawText, $hmM)) {
                $val = (float)$hmM[1];
                if ($val > 0 && $val < 1000000.0) {
                    $hmKmVal = $val;
                }
            }
        }

        // Delay pengadaan dari Sheet 04
        $delayKey = $assetId . '|' . $downDate;
        $delayHours = $delayMap[$delayKey]['delay_hours'] ?? 0.0;
        $partsReq = $delayHours > 0 ? 'REQUIRED' : 'NOT_REQUIRED';
        $partsNote = $delayHours > 0 ? "Delay pengadaan spare part: {$delayHours} jam." : null;

        // Status mapping
        if ($condition === 'READY' || $readyAt !== null) {
            $status = 'Closed';
            $unitLastStatus[$assetId] = 'READY';
        } elseif ($condition === 'BREAKDOWN') {
            $status = $delayHours > 0 ? 'Waiting Part' : 'In Progress';
            $unitLastStatus[$assetId] = 'BREAKDOWN';
        } elseif ($condition === 'INSPEKSI') {
            $status = 'Testing';
            $unitLastStatus[$assetId] = 'INSPEKSI';
        } else {
            $status = 'Closed';
            $unitLastStatus[$assetId] = 'READY';
        }

        // Priority mapping
        $priority = 'Normal';
        if ($downtimeHours > 72.0 || preg_match('/(stuck|mati total|patah|accident|kebakaran|bocor oli rem)/i', $issue)) {
            $priority = 'Emergency';
        } elseif ($downtimeHours > 24.0) {
            $priority = 'High';
        }

        // Assigned mechanics JSON
        $mechList = array_values(array_filter(array_map('trim', preg_split('/[,;\/]+/', $mechanic))));
        $mechJson = json_encode($mechList, JSON_UNESCAPED_UNICODE);

        // Root cause mapping
        $rootCause = "Keausan operasional komponen pada medan tambang / operational fatigue.";
        if (preg_match('/(baterai|accu|aki)/i', $issue)) {
            $rootCause = "Penurunan voltase baterai / degradasi sel accu akibat siklus starter berat.";
        } elseif (preg_match('/(radiator|overheat|panas)/i', $issue)) {
            $rootCause = "Kebocoran jalur pendingin atau sumbatan kisi radiator.";
        } elseif (preg_match('/(oli|kebocoran|seal|hose)/i', $issue)) {
            $rootCause = "Keretakan hose hidrolik / aus seal fluida di bawah tekanan operasional.";
        } elseif (preg_match('/(rem|brake)/i', $issue)) {
            $rootCause = "Penipisan kampas rem atau kebocoran angin brake booster.";
        } elseif (preg_match('/(lampu|wiring|kelistrikan)/i', $issue)) {
            $rootCause = "Vibrasi jalan tambang menyebabkan kelonggaran konektor harness / bohlam putus.";
        }

        $maintType = (strpos($condition, 'SERVICE') !== false || stripos($issue, 'service') !== false) ? 'Preventive' : 'Corrective';

        $currentWoSeq++;
        $newWoId = sprintf("WO-26-%03d", $currentWoSeq);

        $stmtInsert->execute([
            ':wo_id' => $newWoId,
            ':asset_id' => $assetId,
            ':hull' => $hull,
            ':type' => $equipType,
            ':hm_km' => $hmKmVal,
            ':trouble_at' => $troubleAt,
            ':reported_at' => $troubleAt,
            ':raw_loc' => $location,
            ':issue' => $issue,
            ':downtime_fmt' => $downtimeFmt,
            ':downtime_min' => $downtimeMin,
            ':status' => $status,
            ':priority' => $priority,
            ':mechanic' => $mechanic,
            ':mech_json' => $mechJson,
            ':root_cause' => $rootCause,
            ':repair_action' => $progress !== '' ? $progress : "Penanganan tim workshop sesuai SOP.",
            ':closed_at' => $readyAt ?? ($status === 'Closed' ? $troubleAt : null),
            ':maint_type' => $maintType,
            ':parts_req' => $partsReq,
            ':parts_note' => $partsNote
        ]);

        $insertedCount++;
    }

    echo "--- Injeksi Work Orders Februari 2026 Selesai ---\n";
    echo "✅ Work Orders baru Februari yang disuntikkan: $insertedCount records (Sequence s.d. WO-26-{$currentWoSeq})\n\n";

    // -------------------------------------------------------------------------
    // STEP 2: Update Status Armada Terkini di tabel assets
    // -------------------------------------------------------------------------
    echo "--- [2/3] Menyelaraskan Status Operasional Armada Terkini di tabel assets ---\n";
    $stmtUpdAssetStatus = $pdo->prepare("UPDATE assets SET status = :st, last_update_timestamp = NOW() WHERE asset_id = :aid");
    $statusUpdated = 0;
    foreach ($unitLastStatus as $aid => $st) {
        $stmtUpdAssetStatus->execute([':st' => $st, ':aid' => $aid]);
        $statusUpdated++;
    }
    echo "✅ Berhasil memperbarui status operasional $statusUpdated unit armada terkini.\n\n";

    // -------------------------------------------------------------------------
    // STEP 3: Catat Migrasi di app_data_migrations
    // -------------------------------------------------------------------------
    echo "--- [3/3] Mencatat Riwayat Migrasi di app_data_migrations ---\n";
    $stmtMig = $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES (:k, NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtMig->execute([':k' => $migrationKey]);
    echo "✅ Migrasi $migrationKey berhasil dicatat.\n\n";

    $pdo->commit();
    echo "============================================================================\n";
    echo " BATCH 29 SUKSES: Seluruh Work Orders Februari 2026 telah terinjeksi sempurna!\n";
    echo "============================================================================\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

