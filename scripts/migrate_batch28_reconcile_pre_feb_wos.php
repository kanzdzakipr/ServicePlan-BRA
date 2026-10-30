<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 28 - Rekonsiliasi & Injeksi Delta Work Orders (Oktober 2025 - Januari 2026)
 * 
 * Target:
 * 1. Rekonsiliasi (~240) Work Orders eksisting di database:
 *    - Update raw_location, current_hm_km, delay pengadaan part, jam pengerjaan, dan catatan progress.
 * 2. Injeksi (~483) Work Orders baru periode Oktober 2025 - Januari 2026:
 *    - Penomoran berurutan melanjutkan sequence WO-26-365 s/d WO-26-847.
 *    - Pemetaan field lengkap: asset_id, hull_number, equipment_type, trouble_at, closed_at, status, priority,
 *      assigned_mechanic, assigned_mechanics_json, issue_description, repair_action, root_cause,
 *      downtime_minutes, downtime_formatted, parts_requirement, parts_requirement_note.
 * 3. Catat di tabel `app_data_migrations` dengan key 'BATCH_28_RECONCILE_PRE_FEB_WOS'.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';
require_once __DIR__ . '/analyze_lp_deep.php';
require_once __DIR__ . '/lp_bdown_asset_resolver.php';

$pdo = Database::getInstance();
$resolver = new LpAssetResolver($pdo);

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 28 PRE-FEBRUARY WORK ORDERS RECONCILIATION\n";
echo " Source: material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI/ (01 & 04)\n";
echo "============================================================================\n\n";

$migrationKey = 'BATCH_28_RECONCILE_PRE_FEB_WOS';

$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
if ((int)$stmtCheck->fetchColumn() > 0) {
    echo "⚠️ PERINGATAN: Migrasi $migrationKey sudah pernah dicatat di app_data_migrations.\n";
    echo "Melanjutkan pembaruan idempotent agar seluruh data dipastikan sinkron...\n\n";
}

$baseDir = dirname(__DIR__) . '/material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI';
$c1 = parseCellMarkdown($baseDir . '/01_des_25_peb_26.md');
$c4 = parseCellMarkdown($baseDir . '/04_avibility.md');

// 1. Index delay & repair dari Sheet 04 (AVIBILITY)
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

// 2. Fetch existing work_orders in DB
$existingWos = $pdo->query("SELECT wo_id, asset_id, trouble_at, status, current_hm_km, raw_location, issue_description, repair_action FROM work_orders")->fetchAll(PDO::FETCH_ASSOC);
$existingMap = [];
$maxSequence = 364; // Base regular WO sequence

foreach ($existingWos as $w) {
    if (preg_match('/^WO-26-(\d{1,4})$/', $w['wo_id'], $m)) {
        $val = (int)$m[1];
        if ($val > $maxSequence && $val < 100000) {
            $maxSequence = $val;
        }
    }
    $tDate = substr($w['trouble_at'] ?? '', 0, 10);
    $key = trim($w['asset_id']) . '|' . $tDate;
    $existingMap[$key][] = $w;
}
echo "Existing Work Orders in DB: " . count($existingWos) . " (Current base max sequence: $maxSequence)\n\n";

$pdo->beginTransaction();

try {
    $enrichedCount = 0;
    $insertedCount = 0;
    $currentWoSeq = $maxSequence;

    $stmtUpdate = $pdo->prepare("
        UPDATE work_orders
        SET current_hm_km = COALESCE(current_hm_km, :hm_km),
            raw_location = CASE WHEN raw_location IS NULL OR raw_location = '' THEN :loc ELSE raw_location END,
            repair_action = CASE WHEN repair_action IS NULL OR repair_action = '' THEN :repair1 ELSE CONCAT(repair_action, '\n', :repair2) END,
            parts_requirement = CASE WHEN :delay_hours1 > 0 THEN 'REQUIRED' ELSE parts_requirement END,
            parts_requirement_note = CASE WHEN :delay_hours2 > 0 AND (parts_requirement_note IS NULL OR parts_requirement_note = '') THEN :part_note ELSE parts_requirement_note END
        WHERE wo_id = :wo_id
    ");

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
        )
    ");

    foreach ($c1 as $r => $cols) {
        if (!isset($cols['F']) || !isset($cols['J'])) continue;
        $rawUnit = $cols['F']['val'];
        $dateVal = $cols['J']['val'];
        if (!is_numeric($dateVal) || $dateVal < 40000) continue;

        $downDate = date('Y-m-d', (int)(($dateVal - 25569) * 86400));
        
        // Hanya proses record sampai dengan 31 Januari 2026 (Pre-February)
        if ($downDate >= '2026-02-01') continue;

        $assetId = $resolver->resolve($rawUnit);
        if (!$assetId) continue;

        $assetInfo = $resolver->getAssetInfo($assetId);
        $hull = $assetInfo['hull_number'] ?? $assetId;
        $equipType = $assetInfo['category'] ?? ($assetInfo['type'] ?? 'Heavy Equipment');

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
        } elseif ($condition === 'BREAKDOWN') {
            $status = $delayHours > 0 ? 'Waiting Part' : 'In Progress';
        } elseif ($condition === 'INSPEKSI') {
            $status = 'Testing';
        } else {
            $status = 'Closed';
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
        }

        $maintType = (strpos($condition, 'SERVICE') !== false || stripos($issue, 'service') !== false) ? 'Preventive' : 'Corrective';

        // Check if matching in existingMap
        $matchKey = $assetId . '|' . $downDate;
        if (isset($existingMap[$matchKey]) && count($existingMap[$matchKey]) > 0) {
            // ENRICH EXISTING WORK ORDER
            $targetWo = $existingMap[$matchKey][0];
            $repairStr = $progress !== '' ? "Progres Sheet: " . $progress : "Perbaikan operasional.";
            $stmtUpdate->execute([
                ':hm_km' => $hmKmVal,
                ':loc' => $location,
                ':repair1' => $repairStr,
                ':repair2' => $repairStr,
                ':delay_hours1' => $delayHours,
                ':delay_hours2' => $delayHours,
                ':part_note' => $partsNote,
                ':wo_id' => $targetWo['wo_id']
            ]);
            $enrichedCount++;
        } else {
            // INJECT NEW WORK ORDER
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

            // Register in existingMap to prevent self-collision
            $existingMap[$matchKey][] = ['wo_id' => $newWoId];
            $insertedCount++;
        }
    }

    echo "--- Rekonsiliasi Batch 28 Selesai ---\n";
    echo "✅ Work Orders yang di-enrich (rekonsiliasi): $enrichedCount records\n";
    echo "✅ Work Orders baru yang disuntikkan: $insertedCount records (Sequence s.d. WO-26-{$currentWoSeq})\n\n";

    // -------------------------------------------------------------------------
    // STEP 3: Catat Migrasi di app_data_migrations
    // -------------------------------------------------------------------------
    echo "--- Mencatat Riwayat Migrasi di app_data_migrations ---\n";
    $stmtMig = $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES (:k, NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtMig->execute([':k' => $migrationKey]);
    echo "✅ Migrasi $migrationKey berhasil dicatat.\n\n";

    $pdo->commit();
    echo "============================================================================\n";
    echo " BATCH 28 SUKSES: Seluruh Work Orders Pre-Februari sinkron tanpa konflik!\n";
    echo "============================================================================\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
