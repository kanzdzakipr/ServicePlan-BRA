<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function maintenanceBoardAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    $passed++;
    echo "PASS: {$message}\n";
}

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $error) {
    echo 'SKIP: database integration test unavailable: ' . $error->getMessage() . "\n";
    exit(0);
}

ReportIntegration::ensureTables($db);
$asset = $db->query(
    "SELECT asset_id, asset_code, category, last_hm_km
     FROM assets WHERE is_active = 1 ORDER BY asset_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    echo "SKIP: no active asset is available.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$templateKey = 'qa-maintenance-board-' . strtolower($suffix);
$reportId = '99999999-9999-4999-8999-' . $suffix;
$linkedReportId = 'aaaaaaaa-aaaa-4aaa-8aaa-' . $suffix;
$preservedReportId = 'bbbbbbbb-bbbb-4bbb-8bbb-' . $suffix;
$reportDate = (new DateTimeImmutable('today'))->format('Y-m-d');
$linkedDate = (new DateTimeImmutable('today +1 day'))->format('Y-m-d');
$preservedDate = (new DateTimeImmutable('today +2 days'))->format('Y-m-d');
$hm = round((float) $asset['last_hm_km'], 2);

$fields = [
    'lokasi' => 'QA Laragon',
    'tanggal' => $reportDate,
    'dibuat_oleh' => 'QA Planner',
    'jabatan' => 'Maintenance Planner',
    'departemen' => 'Equipment',
];
$baseRow = [
    'kode' => $asset['asset_id'],
    'jenis' => $asset['category'],
    'hm_awal' => (string) $hm,
    'interval' => '500 HM',
    'tanggal_hm' => $reportDate,
    'realisasi' => '',
    'parts_pesan' => '',
    'parts_tiba' => '',
    'keterangan' => 'QA Maintenance Board integration',
];

$db->beginTransaction();
try {
    $template = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'MB-A2B', 'QA Maintenance Board', 1, '{}', 1)"
    );
    $template->execute([':template_key' => $templateKey]);
    $templateId = (int) $db->lastInsertId();

    $insertReport = $db->prepare(
        "INSERT INTO report_records
         (report_id, template_id, client_key, report_number, status, source_method, field_data,
          created_by, finalized_at, final_number_key)
         VALUES (:report_id, :template_id, :client_key, :report_number, 'FINAL', 'test', '{}',
          1, NOW(), :final_number_key)"
    );
    $createReport = static function (string $id, string $label) use ($insertReport, $templateId, $suffix): void {
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-mb-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => 'QA-MB-' . $label . '-' . $suffix,
            ':final_number_key' => 'maintenance-board|qa-mb-' . strtolower($label) . '-' . strtolower($suffix),
        ]);
    };

    $createReport($reportId, 'CREATE');
    $beforeCount = (int) $db->query('SELECT COUNT(*) FROM pm_plans')->fetchColumn();
    $result = ReportIntegration::applyFinal($db, 'maintenance-board', $reportId, $fields, [$baseRow], 1);
    maintenanceBoardAssert($result['applied'] === true && $result['createdItemCount'] === 1, 'final report creates one PM plan');

    $integration = $db->prepare(
        'SELECT pm_plan_id, owns_pm_plan FROM report_pm_integrations
         WHERE report_id = :report_id AND reversed_at IS NULL'
    );
    $integration->execute([':report_id' => $reportId]);
    $link = $integration->fetch(PDO::FETCH_ASSOC);
    maintenanceBoardAssert($link && (int) $link['owns_pm_plan'] === 1, 'report ledger owns the newly created plan');

    $plan = $db->prepare('SELECT * FROM pm_plans WHERE pm_plan_id = :pm_plan_id');
    $plan->execute([':pm_plan_id' => (int) $link['pm_plan_id']]);
    $createdPlan = $plan->fetch(PDO::FETCH_ASSOC);
    maintenanceBoardAssert(
        $createdPlan
        && (string) $createdPlan['asset_id'] === (string) $asset['asset_id']
        && (int) $createdPlan['interval_hm'] === 500
        && abs((float) $createdPlan['target_due_hm'] - ($hm + 500)) < 0.005,
        'asset, interval, and target HM are mapped correctly'
    );

    $retry = ReportIntegration::applyFinal($db, 'maintenance-board', $reportId, $fields, [$baseRow], 1);
    maintenanceBoardAssert(!empty($retry['alreadyApplied']), 'retry is idempotent');
    maintenanceBoardAssert(
        (int) $db->query('SELECT COUNT(*) FROM pm_plans')->fetchColumn() === $beforeCount + 1,
        'retry does not duplicate the PM plan'
    );

    $reversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    $plan->execute([':pm_plan_id' => (int) $link['pm_plan_id']]);
    maintenanceBoardAssert($reversal['pmDeletedCount'] === 1 && !$plan->fetch(), 'void removes an unchanged report-owned plan');
    $secondReversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    maintenanceBoardAssert($secondReversal['applied'] === false, 'repeated void is idempotent');

    $createReport($linkedReportId, 'LINK');
    $linkedFields = [...$fields, 'tanggal' => $linkedDate];
    $linkedRow = [...$baseRow, 'tanggal_hm' => $linkedDate, 'keterangan' => 'QA duplicate link'];
    $manualPlan = $db->prepare(
        "INSERT INTO pm_plans
         (asset_id, interval_hm, current_smr, last_service_hm, last_service_date,
          target_due_hm, variance_hm, status, warranty_status, planner_note)
         VALUES (:asset_id, 500, :current_smr, :last_hm, :last_date,
          :target_hm, :variance_hm, 'PLANNED', 'No Warranty', 'Manual QA plan')"
    );
    $manualPlan->execute([
        ':asset_id' => $asset['asset_id'],
        ':current_smr' => $hm,
        ':last_hm' => $hm,
        ':last_date' => $linkedDate,
        ':target_hm' => $hm + 500,
        ':variance_hm' => -500,
    ]);
    $manualPlanId = (int) $db->lastInsertId();
    $linkedResult = ReportIntegration::applyFinal($db, 'maintenance-board', $linkedReportId, $linkedFields, [$linkedRow], 1);
    maintenanceBoardAssert($linkedResult['createdItemCount'] === 0 && $linkedResult['linkedItemCount'] === 1, 'matching manual plan is linked without duplication');
    $linkedReversal = ReportIntegration::reverseFinal($db, $linkedReportId, 1);
    $plan->execute([':pm_plan_id' => $manualPlanId]);
    maintenanceBoardAssert($linkedReversal['pmDeletedCount'] === 0 && (bool) $plan->fetch(), 'void never deletes a linked manual plan');

    $createReport($preservedReportId, 'PRESERVE');
    $preservedFields = [...$fields, 'tanggal' => $preservedDate];
    $preservedRow = [...$baseRow, 'tanggal_hm' => $preservedDate, 'keterangan' => 'QA preserve change'];
    $preservedResult = ReportIntegration::applyFinal($db, 'maintenance-board', $preservedReportId, $preservedFields, [$preservedRow], 1);
    maintenanceBoardAssert($preservedResult['createdItemCount'] === 1, 'second distinct report creates its own plan');
    $integration->execute([':report_id' => $preservedReportId]);
    $preservedLink = $integration->fetch(PDO::FETCH_ASSOC);
    $db->prepare('UPDATE pm_plans SET planner_note = :note WHERE pm_plan_id = :pm_plan_id')->execute([
        ':note' => 'Planner changed this plan after finalization',
        ':pm_plan_id' => (int) $preservedLink['pm_plan_id'],
    ]);
    $preservedReversal = ReportIntegration::reverseFinal($db, $preservedReportId, 1);
    $plan->execute([':pm_plan_id' => (int) $preservedLink['pm_plan_id']]);
    maintenanceBoardAssert(
        $preservedReversal['pmPreservedCount'] === 1 && (bool) $plan->fetch(),
        'void preserves a report-owned plan that a planner changed later'
    );

    echo "\nMaintenance Board integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
