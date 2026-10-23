<?php
declare(strict_types=1);

$isCommit = in_array('--commit', $argv, true);

$jsonPath = __DIR__ . '/../reports/pic_migration_plan_jan2026.json';
if (!file_exists($jsonPath)) {
    die("Error: {$jsonPath} not found. Please run scripts/extract_equipment_report_pics.php first.\n");
}

$plan = json_decode(file_get_contents($jsonPath), true);
echo "Loaded " . count($plan) . " asset operator records to populate.\n";
echo "Mode: " . ($isCommit ? ">>> LIVE EXECUTION (TRANSACTION COMMIT) <<<" : "[DRY RUN] No changes written to database") . "\n\n";

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$pdo->beginTransaction();

try {
    $stmtUpdateAsset = $pdo->prepare("
        UPDATE assets
        SET current_operator_name = :op
        WHERE asset_id = :aid
    ");

    $stmtCloseOldHist = $pdo->prepare("
        UPDATE asset_operator_history
        SET end_date = :close_date
        WHERE asset_id = :aid AND end_date IS NULL
    ");

    $stmtInsertHist = $pdo->prepare("
        INSERT INTO asset_operator_history (
            asset_id, operator_name, start_date, end_date, shift_type, handover_notes, created_at
        ) VALUES (
            :aid, :op, :start_date, :end_date, :shift, :notes, NOW()
        )
    ");

    $updatedCount = 0;
    $historyCount = 0;

    foreach ($plan as $item) {
        $aid = $item['asset_id'];
        $primaryOp = $item['primary_operator'];
        $startDate = $item['evidence_date'] ?: '2026-01-01';
        $freq = $item['frequency'];

        // 1. Update Asset
        $stmtUpdateAsset->execute([
            ':op' => $primaryOp,
            ':aid' => $aid
        ]);
        $updatedCount++;

        // 2. Close any lingering active histories
        $stmtCloseOldHist->execute([
            ':close_date' => $startDate,
            ':aid' => $aid
        ]);

        // 3. Insert primary operator history
        $notes = "Sumber: Laporan Divisi Equipment Januari 2026 (tercatat {$freq}x penugasan/operasional)";
        $stmtInsertHist->execute([
            ':aid' => $aid,
            ':op' => $primaryOp,
            ':start_date' => $startDate,
            ':end_date' => null, // Active
            ':shift' => 'Shift 1 (Utama)',
            ':notes' => $notes
        ]);
        $historyCount++;

        // 4. If there were alternate / rotation drivers in Jan 2026, record them in history too
        if (!empty($item['all_operators'])) {
            foreach ($item['all_operators'] as $altOp) {
                if ($altOp === $primaryOp) continue;
                $stmtInsertHist->execute([
                    ':aid' => $aid,
                    ':op' => $altOp,
                    ':start_date' => '2026-01-01',
                    ':end_date' => $startDate,
                    ':shift' => 'Rotasi / Shift Cadangan',
                    ':notes' => 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026'
                ]);
                $historyCount++;
            }
        }
    }

    if ($isCommit) {
        $pdo->commit();
        echo "SUCCESS: Committed changes to database!\n";
    } else {
        $pdo->rollBack();
        echo "DRY RUN COMPLETE: Transaction safely rolled back.\n";
    }

    echo "Total Assets Updated: {$updatedCount}\n";
    echo "Total Operator History Entries Created: {$historyCount}\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

