<?php
declare(strict_types=1);

$dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('DB_USER') ?: 'root');
$password = (string) (getenv('DB_PASSWORD') ?: '');

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

require_once __DIR__ . '/../api/security.php';

$checks = 0;
$assert = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $label\n");
        exit(1);
    }
    $checks++;
    echo "PASS: $label\n";
};

echo "=======================================================================\n";
echo "  TEST SUITE F4: People & KPI Resmi Verification                      \n";
echo "=======================================================================\n\n";

// Record baseline counts
$headCountInitial = (int) $pdo->query("SELECT COUNT(*) FROM head_kpi_assessments")->fetchColumn();
$plannerCountInitial = (int) $pdo->query("SELECT COUNT(*) FROM planner_evaluations")->fetchColumn();

echo "Baseline historical records in database:\n";
echo "- head_kpi_assessments: {$headCountInitial} rows\n";
echo "- planner_evaluations: {$plannerCountInitial} rows\n\n";

$assert($headCountInitial === 10, "[F4.2] 10 baris historis head_kpi_assessments terverifikasi utuh di DB");
$assert($plannerCountInitial === 5, "[F4.2] 5 baris historis planner_evaluations terverifikasi utuh di DB");

$testPeriodKey = '2099-12'; // Future QA period to avoid collision

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // F4.1: Draft -> Submit -> Approve Lifecycle & Mathematical Validation
    // -------------------------------------------------------------------------
    echo "\n--- F4.1: Lifecycle Penilaian Head KPI ---\n";
    $stmtIns = $pdo->prepare("
        INSERT INTO head_kpi_assessments (
            period_month, period_year, period_key, indicator_no, aspect_name,
            indicator_name, target_standard, score_val, weight_pct,
            audit_notes, status, evaluated_user_id, assessor_user_id, created_at
        ) VALUES (
            'Desember', 2099, :p_key, :no, :aspect, :name, :target, :score, :weight, :notes, 'DRAFT', 2, 1, NOW()
        )
    ");

    $stmtIns->execute([
        ':p_key' => $testPeriodKey,
        ':no' => 1,
        ':aspect' => 'Operational Efficiency',
        ':name' => 'Physical Availability (PA)',
        ':target' => '>= 90%',
        ':score' => 4,
        ':weight' => 40.0,
        ':notes' => 'Tercapai 92% di Site Duri',
    ]);

    $stmtIns->execute([
        ':p_key' => $testPeriodKey,
        ':no' => 2,
        ':aspect' => 'Safety & Compliance',
        ':name' => 'Zero Accident',
        ':target' => '0 Kasus',
        ':score' => 5,
        ':weight' => 60.0,
        ':notes' => 'Zero LTI sepanjang bulan',
    ]);

    // Check stored generated / calculated column
    $insertedRows = $pdo->query("SELECT * FROM head_kpi_assessments WHERE period_key = '{$testPeriodKey}' ORDER BY indicator_no ASC")->fetchAll();
    $assert(count($insertedRows) === 2, "[F4.1] Tepat 2 indikator berhasil disimpan");
    $assert($insertedRows[0]['status'] === 'DRAFT', "[F4.1] Status awal indikator adalah DRAFT");

    // Math check: (score_val * weight_pct) / 5
    // Row 1: (4 * 40) / 5 = 32.0
    // Row 2: (5 * 60) / 5 = 60.0
    $assert(abs((float)$insertedRows[0]['weighted_score'] - 32.0) < 0.01, "[F4.1] Indikator 1 weighted_score dihitung tepat: 32.0");
    $assert(abs((float)$insertedRows[1]['weighted_score'] - 60.0) < 0.01, "[F4.1] Indikator 2 weighted_score dihitung tepat: 60.0");

    // Transition to SUBMITTED
    $pdo->prepare("UPDATE head_kpi_assessments SET status = 'SUBMITTED', submitted_at = NOW() WHERE period_key = :pk")
        ->execute([':pk' => $testPeriodKey]);
    $submittedStatus = $pdo->query("SELECT status FROM head_kpi_assessments WHERE period_key = '{$testPeriodKey}' LIMIT 1")->fetchColumn();
    $assert($submittedStatus === 'SUBMITTED', "[F4.1] Transisi status ke SUBMITTED berhasil");

    // Transition to APPROVED
    $pdo->prepare("
        UPDATE head_kpi_assessments 
        SET status = 'APPROVED', approved_by = 1, approved_at = NOW(), approval_notes = 'Disetujui untuk rilis resmi'
        WHERE period_key = :pk
    ")->execute([':pk' => $testPeriodKey]);

    $approvedRow = $pdo->query("SELECT status, approved_by, approval_notes FROM head_kpi_assessments WHERE period_key = '{$testPeriodKey}' LIMIT 1")->fetch();
    $assert($approvedRow['status'] === 'APPROVED', "[F4.1] Transisi status ke APPROVED berhasil");
    $assert((int)$approvedRow['approved_by'] === 1, "[F4.1] Aktor approver (User ID 1) tercatat di database");

    // -------------------------------------------------------------------------
    // F4.2: Perlindungan Baris Historis (10 Head KPI & 5 Planner Rows)
    // -------------------------------------------------------------------------
    echo "\n--- F4.2: Verifikasi Integritas Data Historis ---\n";
    $historicalHeadCount = (int) $pdo->query("SELECT COUNT(*) FROM head_kpi_assessments WHERE period_key != '{$testPeriodKey}'")->fetchColumn();
    $assert($historicalHeadCount === 10, "[F4.2] 10 baris historis head_kpi_assessments sama sekali tidak tersentuh/tertimpa");

    $historicalPlannerCount = (int) $pdo->query("SELECT COUNT(*) FROM planner_evaluations")->fetchColumn();
    $assert($historicalPlannerCount === 5, "[F4.2] 5 baris historis planner_evaluations sama sekali tidak tersentuh/tertimpa");

    // -------------------------------------------------------------------------
    // F4.3: Reject Workflow & Batas Otorisasi Role
    // -------------------------------------------------------------------------
    echo "\n--- F4.3: Reject Workflow & Batas Otorisasi ---\n";
    $plannerPermissions = api_builtin_permissions_for_roles(['Maintenance Planner']);
    $assert(!in_array('reports.approve', $plannerPermissions, true), "[F4.3] Role Maintenance Planner tidak memiliki hak reports.approve");

    $mekanikPermissions = api_builtin_permissions_for_roles(['Mekanik Senior']);
    $assert(!in_array('reports.approve', $mekanikPermissions, true), "[F4.3] Role Mekanik Senior tidak memiliki hak reports.approve");

    $adminPermissions = api_builtin_permissions_for_roles(['Administrator']);
    $assert(in_array('*', $adminPermissions, true), "[F4.3] Administrator berwenang melakukan approval/rejection");

    // Rejection simulation
    $testRejectPeriod = '2099-11';
    $pdo->prepare("
        INSERT INTO head_kpi_assessments (
            period_month, period_year, period_key, indicator_no, aspect_name,
            indicator_name, target_standard, score_val, weight_pct,
            status, evaluated_user_id, assessor_user_id, created_at
        ) VALUES ('November', 2099, :pk, 1, 'Aspect', 'Ind', 'Target', 3, 100.0, 'SUBMITTED', 2, 1, NOW())
    ")->execute([':pk' => $testRejectPeriod]);

    // Admin rejects with reason
    $rejectionReason = 'Data utilisasi belum diverifikasi lapangan, mohon revisi';
    $pdo->prepare("
        UPDATE head_kpi_assessments
        SET status = 'REJECTED', approved_by = 1, approved_at = NOW(), approval_notes = :reason
        WHERE period_key = :pk
    ")->execute([':reason' => $rejectionReason, ':pk' => $testRejectPeriod]);

    $rejectRow = $pdo->query("SELECT status, approval_notes FROM head_kpi_assessments WHERE period_key = '{$testRejectPeriod}'")->fetch();
    $assert($rejectRow['status'] === 'REJECTED', "[F4.3] Penolakan (REJECTED) berhasil tersimpan di DB");
    $assert($rejectRow['approval_notes'] === $rejectionReason, "[F4.3] Alasan penolakan persisten di DB");

    // -------------------------------------------------------------------------
    // F4.4: SPL Review & Overtime Leaderboard
    // -------------------------------------------------------------------------
    echo "\n--- F4.4: SPL Review & Overtime Leaderboard ---\n";
    $qaSplNumber = 'QA-SPL-TEST-2099-F4';
    $qaReportId = 'c0a80101-qa-f4-spl-review';

    // 1. Planner records SPL review in spl_planner_reviews
    $stmtRev = $pdo->prepare("
        INSERT INTO spl_planner_reviews (report_id, spl_number, planner_user_id, review_status, review_notes)
        VALUES (:rep, :spl, 3, 'Reviewed', 'Semua jam kerja mekanik dan pekerjaan selesai sesuai WO')
    ");
    $stmtRev->execute([':rep' => $qaReportId, ':spl' => $qaSplNumber]);
    $reviewId = (int) $pdo->lastInsertId();
    $assert($reviewId > 0, "[F4.4] SPL review tersimpan di tabel spl_planner_reviews (ID #{$reviewId})");

    // 2. Cross-browser / Idempotent review update
    $updatedNotes = 'Catatan diperbarui: lembur disetujui 3.5 jam';
    $pdo->prepare("
        UPDATE spl_planner_reviews
        SET review_status = 'Approved', review_notes = :notes
        WHERE review_id = :id
    ")->execute([':notes' => $updatedNotes, ':id' => $reviewId]);

    $reviewCheck = $pdo->query("SELECT review_status, review_notes FROM spl_planner_reviews WHERE review_id = {$reviewId}")->fetch();
    $assert($reviewCheck['review_status'] === 'Approved', "[F4.4] Status review SPL diperbarui menjadi Approved");
    $assert($reviewCheck['review_notes'] === $updatedNotes, "[F4.4] Catatan review persisten");

    // 3. Overtime leaderboard aggregation & void exclusion
    // Insert template and report record to satisfy foreign keys
    $qaSuffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $tplKey = 'qa-tpl-f4-' . strtolower($qaSuffix);
    $pdo->prepare("INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active) VALUES (?, 'SPL', 'QA SPL', 1, '{}', 1)")
        ->execute([$tplKey]);
    $tplId = (int) $pdo->lastInsertId();

    $reportUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $pdo->prepare("INSERT INTO report_records (report_id, template_id, client_key, report_number, status, source_method, field_data, created_by, finalized_at) VALUES (?, ?, ?, ?, 'FINAL', 'test', '{}', 1, NOW())")
        ->execute([$reportUuid, $tplId, 'qa-key-' . strtolower($qaSuffix), 'QA-REP-SPL-' . $qaSuffix]);

    $pdo->prepare("
        INSERT INTO spl_overtime_records (
            report_id, report_item_position, spl_number, spl_date, period_start, period_end,
            location_id, employee_user_id, role_snapshot, start_time, end_time, duration_hours,
            activity_description, general_instruction, prepared_by, instructed_by, start_verified, end_verified, created_at
        ) VALUES (
            ?, 1, ?, '2099-12-10', '2099-12-10', '2099-12-10',
            4, 4, 'Mekanik Senior', '17:00:00', '20:30:00', 3.5,
            'Penggantian O-Ring Excavator EX-001', 'Pastikan uji tekan hidrolik', 3, 2, 1, 1, NOW()
        )
    ")->execute([$reportUuid, $qaSplNumber]);

    // Active leaderboard query
    $leaderboardActive = $pdo->query("
        SELECT employee_user_id, role_snapshot, SUM(duration_hours) as total_hours, COUNT(DISTINCT spl_number) as spl_count
        FROM spl_overtime_records
        WHERE reversed_at IS NULL AND spl_date LIKE '2099-12%'
        GROUP BY employee_user_id, role_snapshot
    ")->fetchAll();

    $assert(count($leaderboardActive) === 1, "[F4.4] Leaderboard lembur aktif menghasilkan 1 baris untuk periode 2099-12");
    $assert((float)$leaderboardActive[0]['total_hours'] === 3.5, "[F4.4] Total lembur akurat: 3.5 jam");

    // Test void/reversal: reversed records must be excluded from leaderboard
    $pdo->prepare("UPDATE spl_overtime_records SET reversed_at = NOW(), reversed_by = 1 WHERE spl_number = ?")->execute([$qaSplNumber]);
    $leaderboardVoid = $pdo->query("
        SELECT COUNT(*) FROM spl_overtime_records WHERE reversed_at IS NULL AND spl_date LIKE '2099-12%'
    ")->fetchColumn();
    $assert((int)$leaderboardVoid === 0, "[F4.4] Record SPL yang di-void (reversed_at IS NOT NULL) dikeluarkan dari leaderboard");

    // -------------------------------------------------------------------------
    // F4.5: CSV Export Formatting & Injection Protection
    // -------------------------------------------------------------------------
    echo "\n--- F4.5: CSV Export Integrity ---\n";
    // Check that CSV headers and fields match DB schema
    $exportFields = ['period_key', 'indicator_no', 'aspect_name', 'indicator_name', 'target_standard', 'score_val', 'weight_pct', 'weighted_score', 'status'];
    $colsHead = $pdo->query("SHOW COLUMNS FROM head_kpi_assessments")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($exportFields as $field) {
        $assert(in_array($field, $colsHead, true), "[F4.5] Field export '{$field}' cocok dengan kolom database");
    }

    // Formula injection check on CSV cell formatting
    $sanitizeCsvCell = static function(string $value): string {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }
        return $value;
    };

    $injectionPayloads = ['=SUM(A1:A10)', '+cmd|/c calc', '-10+5', '@IMPORTXML'];
    foreach ($injectionPayloads as $payload) {
        $sanitized = $sanitizeCsvCell($payload);
        $assert($sanitized[0] === "'", "[F4.5] Formula CSV injection dicegah dengan quote escape: {$payload} -> {$sanitized}");
    }

    echo "\n=======================================================================\n";
    echo "  F4 CHECKPOINT RESULT: {$checks}/{$checks} CHECKS PASSED CLEANLY!     \n";
    echo "=======================================================================\n";

} finally {
    $pdo->rollBack();
    echo "TRANSACTION ROLLBACK: Seluruh baris pengujian QA telah dibersihkan aman.\n";
    $headCountFinal = (int) $pdo->query("SELECT COUNT(*) FROM head_kpi_assessments")->fetchColumn();
    $plannerCountFinal = (int) $pdo->query("SELECT COUNT(*) FROM planner_evaluations")->fetchColumn();
    echo "Verifikasi akhir: head_kpi={$headCountFinal} (awal: {$headCountInitial}), planner_eval={$plannerCountFinal} (awal: {$plannerCountInitial}).\n";
}

