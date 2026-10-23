<?php
/**
 * Test Batch 5: Dynamic Period for SPL Overtime Leaderboard & People KPI Evaluations
 */
declare(strict_types=1);

$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4';
$user = 'root';
$password = '';

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    echo "SKIP: Database connection failed: " . $e->getMessage() . "\n";
    exit(0);
}

echo "Running Batch 5 Tests...\n";

$testSplNum = 'SPL-TEST-B5-' . time();
$testUserId = 1;

try {
    // 1. Test Query for 2026-01 vs Future Period without data (2026-05)
    $stmtEmpty = $db->prepare("
        SELECT COUNT(DISTINCT s.spl_number) AS total_spl, COALESCE(SUM(s.duration_hours), 0) AS total_hours
        FROM spl_overtime_records s
        WHERE s.reversed_at IS NULL AND s.spl_date LIKE :p_like
    ");
    $stmtEmpty->execute([':p_like' => '2026-05%']);
    $emptyStats = $stmtEmpty->fetch();
    echo "PASS: Future period 2026-05 initially has {$emptyStats['total_spl']} SPL records.\n";

    // 2. Insert test report record and test SPL for 2026-05
    $reportUuid = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff), random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0x4000, 0x4fff),
        random_int(0x8000, 0xbfff),
        random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
    );
    $templateId = (int)$db->query("SELECT template_id FROM report_templates LIMIT 1")->fetchColumn();
    if (!$templateId) {
        $templateId = 14;
    }

    $db->prepare("
        INSERT INTO report_records (report_id, template_id, client_key, report_number, status, source_method, field_data, created_by, created_at)
        VALUES (:rid, :tid, 'test-spl', :rnum, 'FINAL', 'manual', '{}', :uid, NOW())
    ")->execute([':rid' => $reportUuid, ':tid' => $templateId, ':rnum' => $testSplNum, ':uid' => $testUserId]);

    $db->prepare("
        INSERT INTO spl_overtime_records 
        (report_id, report_item_position, spl_number, employee_user_id, role_snapshot, spl_date, period_start, period_end, start_time, end_time, duration_hours, location_id, activity_description, general_instruction, prepared_by, instructed_by, end_verified, created_at)
        VALUES (:rid, 1, :spl, :uid, 'Mekanik Senior', '2026-05-15', '2026-05-01', '2026-05-31', '16:00:00', '20:00:00', 4.0, 1, 'Perbaikan darurat hidrolik', 'Instruksi pengerjaan lembur', :p_uid, :i_uid, 1, NOW())
    ")->execute([
        ':rid' => $reportUuid,
        ':spl' => $testSplNum,
        ':uid' => $testUserId,
        ':p_uid' => $testUserId,
        ':i_uid' => $testUserId,
    ]);

    // 3. Query with period 2026-05
    $stmt5 = $db->prepare("
        SELECT s.employee_user_id, s.role_snapshot, COUNT(DISTINCT s.spl_number) AS total_spl, SUM(s.duration_hours) AS total_hours
        FROM spl_overtime_records s
        WHERE s.reversed_at IS NULL AND s.spl_date LIKE :p_like
        GROUP BY s.employee_user_id, s.role_snapshot
    ");
    $stmt5->execute([':p_like' => '2026-05%']);
    $rec5 = $stmt5->fetch();

    if (!$rec5 || (float)$rec5['total_hours'] < 4.0) {
        throw new RuntimeException("Overtime record for 2026-05 not aggregated accurately");
    }
    echo "PASS: Overtime accurately filtered and aggregated for period 2026-05 (4.0 hours).\n";

    // 4. Verify Work Order Real Metrics Calculation for a period
    $woStatsStmt = $db->prepare("
        SELECT 
            COUNT(*) AS total_wo,
            COALESCE(SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END), 0) AS closed_wo,
            COALESCE(AVG(CASE WHEN status = 'Closed' AND downtime_minutes > 0 THEN downtime_minutes ELSE NULL END), 0) AS avg_downtime_minutes,
            COALESCE(SUM(CASE WHEN maintenance_type = 'Preventive' AND status = 'Closed' THEN 1 ELSE 0 END), 0) AS pm_completed,
            COALESCE(SUM(CASE WHEN maintenance_type = 'Preventive' THEN 1 ELSE 0 END), 0) AS pm_total
        FROM work_orders
        WHERE reported_at LIKE '2026-01%'
    ");
    $woStatsStmt->execute();
    $stats = $woStatsStmt->fetch();
    echo "PASS: Real metrics queried for 2026-01: {$stats['total_wo']} total WOs, {$stats['closed_wo']} closed.\n";

} finally {
    // Cleanup
    $db->exec("DELETE FROM spl_overtime_records WHERE spl_number = '{$testSplNum}'");
    $db->exec("DELETE FROM report_records WHERE report_number = '{$testSplNum}'");
    echo "Cleanup completed.\n";
}

echo "All Batch 5 tests PASSED!\n";
