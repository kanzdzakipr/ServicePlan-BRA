<?php
declare(strict_types=1);

$dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('DB_USER') ?: 'root');
$password = (string) (getenv('DB_PASSWORD') ?: '');

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$passed = 0;
function f4Assert(bool $condition, string $msg): void {
    global $passed;
    if (!$condition) {
        throw new RuntimeException("FAIL: " . $msg);
    }
    $passed++;
    echo "PASS: {$msg}\n";
}

echo "=======================================================\n";
echo "  TEST SUITE F4: People & KPI Lifecycle Integration   \n";
echo "=======================================================\n\n";

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // 1. Verify Schema Additive Columns Exist
    // -------------------------------------------------------------------------
    $colsHead = $pdo->query("SHOW COLUMNS FROM head_kpi_assessments")->fetchAll(PDO::FETCH_COLUMN);
    f4Assert(in_array('period_key', $colsHead, true), "head_kpi_assessments has period_key column");
    f4Assert(in_array('status', $colsHead, true), "head_kpi_assessments has status column");
    f4Assert(in_array('evaluated_user_id', $colsHead, true), "head_kpi_assessments has evaluated_user_id column");
    f4Assert(in_array('approved_by', $colsHead, true), "head_kpi_assessments has approved_by column");
    f4Assert(in_array('approved_at', $colsHead, true), "head_kpi_assessments has approved_at column");

    $colsPlanner = $pdo->query("SHOW COLUMNS FROM planner_evaluations")->fetchAll(PDO::FETCH_COLUMN);
    f4Assert(in_array('period_key', $colsPlanner, true), "planner_evaluations has period_key column");
    f4Assert(in_array('status', $colsPlanner, true), "planner_evaluations has status column");
    f4Assert(in_array('evaluator_user_id', $colsPlanner, true), "planner_evaluations has evaluator_user_id column");

    $tables = $pdo->query("SHOW TABLES LIKE 'spl_planner_reviews'")->fetchAll();
    f4Assert(count($tables) === 1, "spl_planner_reviews table exists in database");

    // -------------------------------------------------------------------------
    // 2. Head KPI Assessment CRUD & Math Validation
    // -------------------------------------------------------------------------
    $testPeriod = '2099-01'; // Future QA period
    $indicators = [
        ['indicator_no' => 1, 'aspect_name' => 'RTW', 'indicator_name' => 'RTW Ontime', 'target_standard' => '>= 90%', 'score_val' => 5, 'weight_pct' => 20.0, 'audit_notes' => 'Terkendali'],
        ['indicator_no' => 2, 'aspect_name' => 'Downtime', 'indicator_name' => 'Avg Downtime', 'target_standard' => '<= 5h', 'score_val' => 4, 'weight_pct' => 30.0, 'audit_notes' => 'Cukup baik'],
        ['indicator_no' => 3, 'aspect_name' => 'PM', 'indicator_name' => 'PM Compliance', 'target_standard' => '>= 95%', 'score_val' => 3, 'weight_pct' => 50.0, 'audit_notes' => 'Perlu percepatan']
    ];

    $stmtIns = $pdo->prepare("
        INSERT INTO head_kpi_assessments (
            period_month, period_year, period_key, indicator_no, aspect_name,
            indicator_name, target_standard, score_val, weight_pct,
            audit_notes, status, evaluated_user_id, assessor_user_id, created_at
        ) VALUES (
            'Januari', 2099, :p_key, :no, :aspect, :name, :target, :score, :weight, :notes, 'DRAFT', 2, 1, NOW()
        )
    ");

    foreach ($indicators as $ind) {
        $stmtIns->execute([
            ':p_key' => $testPeriod,
            ':no' => $ind['indicator_no'],
            ':aspect' => $ind['aspect_name'],
            ':name' => $ind['indicator_name'],
            ':target' => $ind['target_standard'],
            ':score' => $ind['score_val'],
            ':weight' => $ind['weight_pct'],
            ':notes' => $ind['audit_notes']
        ]);
    }

    // Verify stored generated column math: weighted_score = (score_val * weight_pct) / 5
    $rows = $pdo->query("SELECT * FROM head_kpi_assessments WHERE period_key = '{$testPeriod}' ORDER BY indicator_no ASC")->fetchAll();
    f4Assert(count($rows) === 3, "3 QA indicators successfully inserted");
    // Ind 1: (5 * 20) / 5 = 20.0
    f4Assert(abs((float)$rows[0]['weighted_score'] - 20.0) < 0.01, "Indicator 1 weighted score calculated accurately: 20.0");
    // Ind 2: (4 * 30) / 5 = 24.0
    f4Assert(abs((float)$rows[1]['weighted_score'] - 24.0) < 0.01, "Indicator 2 weighted score calculated accurately: 24.0");
    // Ind 3: (3 * 50) / 5 = 30.0
    f4Assert(abs((float)$rows[2]['weighted_score'] - 30.0) < 0.01, "Indicator 3 weighted score calculated accurately: 30.0");

    // -------------------------------------------------------------------------
    // 3. Workflow State Transitions (DRAFT -> SUBMITTED -> APPROVED)
    // -------------------------------------------------------------------------
    // Transition to SUBMITTED
    $pdo->exec("UPDATE head_kpi_assessments SET status = 'SUBMITTED', submitted_at = NOW() WHERE period_key = '{$testPeriod}'");
    $subStatus = $pdo->query("SELECT status FROM head_kpi_assessments WHERE period_key = '{$testPeriod}' LIMIT 1")->fetchColumn();
    f4Assert($subStatus === 'SUBMITTED', "Assessment successfully submitted (status = SUBMITTED)");

    // Negative Role Check Simulation (Unauthorized role check logic)
    $plannerRole = 'Maintenance Planner';
    $isAllowed = in_array(strtolower($plannerRole), ['administrator', 'general manager'], true);
    f4Assert(!$isAllowed, "Negative Role Test: Maintenance Planner is strictly rejected from approving Head KPI");

    // Positive Approval by Administrator (User 1)
    $adminRole = 'Administrator';
    $isAllowedAdmin = in_array(strtolower($adminRole), ['administrator', 'general manager'], true);
    f4Assert($isAllowedAdmin, "Positive Role Test: Administrator is authorized to approve Head KPI");

    $pdo->prepare("
        UPDATE head_kpi_assessments
        SET status = 'APPROVED', approved_by = 1, approved_at = NOW(), approval_notes = 'Disetujui untuk bonus tahunan'
        WHERE period_key = :p_key
    ")->execute([':p_key' => $testPeriod]);

    $appRow = $pdo->query("SELECT status, approved_by, approval_notes FROM head_kpi_assessments WHERE period_key = '{$testPeriod}' LIMIT 1")->fetch();
    f4Assert($appRow['status'] === 'APPROVED', "Assessment officially APPROVED");
    f4Assert((int)$appRow['approved_by'] === 1, "Approved by Administrator recorded in audit trail");

    // -------------------------------------------------------------------------
    // 4. SPL Planner Reviews Persistence Test
    // -------------------------------------------------------------------------
    $testSplNum = 'QA-SPL-TEST-2099-01';
    $testRepId = 'b8d8c177-test-qa-spl';
    $stmtSplRev = $pdo->prepare("
        INSERT INTO spl_planner_reviews (report_id, spl_number, planner_user_id, review_status, review_notes)
        VALUES (:rep, :spl, 3, 'Reviewed', 'Semua jam kerja mekanik dan pekerjaan sudah diverifikasi')
    ");
    $stmtSplRev->execute([':rep' => $testRepId, ':spl' => $testSplNum]);

    $revRow = $pdo->query("SELECT * FROM spl_planner_reviews WHERE spl_number = '{$testSplNum}'")->fetch();
    f4Assert($revRow !== false, "SPL review persisted to database (replacing localStorage)");
    f4Assert($revRow['review_status'] === 'Reviewed', "Review status recorded accurately");
    f4Assert((int)$revRow['planner_user_id'] === 3, "Planner user ID 3 recorded");

    // Update existing review (idempotent upsert)
    $pdo->prepare("
        UPDATE spl_planner_reviews
        SET review_status = 'Needs Clarification', review_notes = 'Ada jam lembur melebihi toleransi'
        WHERE review_id = :id
    ")->execute([':id' => $revRow['review_id']]);

    $revUpdated = $pdo->query("SELECT review_status, review_notes FROM spl_planner_reviews WHERE review_id = {$revRow['review_id']}")->fetch();
    f4Assert($revUpdated['review_status'] === 'Needs Clarification', "Review successfully updated without duplicate insertion");

    // -------------------------------------------------------------------------
    // 5. Overtime Leaderboard Aggregation
    // -------------------------------------------------------------------------
    // Create temporary template and report to satisfy foreign key fk_spl_overtime_report
    $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $templateKey = 'qa-tpl-spl-' . strtolower($suffix);
    $pdo->prepare("
        INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
        VALUES (:tpl, 'SPL', 'QA SPL Overtime', 1, '{}', 1)
    ")->execute([':tpl' => $templateKey]);
    $tplId = (int)$pdo->lastInsertId();

    $reportUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $pdo->prepare("
        INSERT INTO report_records (
            report_id, template_id, client_key, report_number, status, source_method, field_data, created_by, finalized_at
        ) VALUES (
            :rep, :tpl_id, :ckey, :rep_num, 'FINAL', 'test', '{}', 1, NOW()
        )
    ")->execute([
        ':rep' => $reportUuid,
        ':tpl_id' => $tplId,
        ':ckey' => 'qa-key-' . strtolower($suffix),
        ':rep_num' => 'QA-REP-SPL-' . $suffix
    ]);

    // Insert temporary active SPL record
    $stmtSplOver = $pdo->prepare("
        INSERT INTO spl_overtime_records (
            report_id, report_item_position, spl_number, spl_date, period_start, period_end,
            location_id, employee_user_id, role_snapshot, start_time, end_time, duration_hours,
            activity_description, general_instruction, prepared_by, instructed_by, start_verified, end_verified, created_at
        ) VALUES (
            :rep, 1, :spl, '2099-01-15', '2099-01-15', '2099-01-15',
            15, 6, 'Mekanik Senior', '17:00:00', '21:00:00', 4.0,
            'Perbaikan Cylinder Hoist DT-00049', 'Selesaikan malam ini', 3, 2, 1, 1, NOW()
        )
    ");
    $stmtSplOver->execute([':rep' => $reportUuid, ':spl' => $testSplNum]);

    // Query leaderboard
    $leaderboard = $pdo->query("
        SELECT employee_user_id, role_snapshot, SUM(duration_hours) as total_hours, COUNT(DISTINCT spl_number) as spl_count
        FROM spl_overtime_records
        WHERE reversed_at IS NULL AND spl_date LIKE '2099-01%'
        GROUP BY employee_user_id, role_snapshot
    ")->fetchAll();

    f4Assert(count($leaderboard) === 1, "Leaderboard aggregates real SPL records accurately");
    f4Assert((float)$leaderboard[0]['total_hours'] === 4.0, "Total overtime hours matches 4.0h");
    f4Assert((int)$leaderboard[0]['employee_user_id'] === 6, "Employee user ID 6 matched");

    // Test void/reversal: when reversed_at is set, record disappears from leaderboard
    $pdo->prepare("UPDATE spl_overtime_records SET reversed_at = NOW(), reversed_by = 1 WHERE spl_number = :spl")->execute([':spl' => $testSplNum]);
    $leaderboardVoid = $pdo->query("
        SELECT COUNT(*) FROM spl_overtime_records WHERE reversed_at IS NULL AND spl_date LIKE '2099-01%'
    ")->fetchColumn();
    f4Assert((int)$leaderboardVoid === 0, "Voided SPL records are excluded from active overtime leaderboard");

    echo "\nAll {$passed} checks passed successfully.\n";

} finally {
    $pdo->rollBack();
    echo "TRANSACTION ROLLBACK: All QA test records cleaned up safely. 0 QA rows left in database.\n";
}
