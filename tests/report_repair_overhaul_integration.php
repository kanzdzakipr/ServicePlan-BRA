<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function repairOverhaulAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
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
    "SELECT asset_id, category, make_model, serial_number, last_hm_km
     FROM assets WHERE is_active = 1 ORDER BY asset_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    echo "SKIP: no active asset is available.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$templateKey = 'qa-repair-overhaul-' . strtolower($suffix);
$reportId = 'cccccccc-cccc-4ccc-8ccc-' . $suffix;
$preservedReportId = 'dddddddd-dddd-4ddd-8ddd-' . $suffix;
$reportDate = (new DateTimeImmutable('today'))->format('Y-m-d');
$targetDate = (new DateTimeImmutable('today +7 days'))->format('Y-m-d');

$baseFields = [
    'nomor' => 'QA-RO-' . $suffix,
    'tanggal' => $reportDate,
    'asset' => trim($asset['category'] . ' ' . ($asset['make_model'] ?? '')),
    'serial_number' => (string) ($asset['serial_number'] ?? ''),
    'kode_unit' => $asset['asset_id'],
    'hm' => (string) $asset['last_hm_km'],
    'nama_parts' => 'QA Hydraulic Component',
    'part_number' => 'QA-PN-' . $suffix,
    'temuan' => 'QA ditemukan kebocoran dan penurunan tekanan pada komponen utama.',
    'riwayat' => 'QA pemeriksaan visual dan pengukuran tekanan telah dilakukan.',
    'urgensi' => 'Mendesak',
    'estimasi_min' => '1000000',
    'estimasi_max' => '2500000',
    'lampiran' => 'QA foto pemeriksaan',
];
$rows = [[
    'solusi' => 'Lakukan dismantle, inspeksi, penggantian seal, dan pengujian tekanan.',
    'pic' => 'Administrator Utama',
    'target' => $targetDate,
    'keterangan' => 'QA solution row',
]];

$db->beginTransaction();
try {
    $template = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'R&O', 'QA Repair Overhaul', 1, '{}', 1)"
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
            ':client_key' => 'qa-ro-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => 'QA-RO-' . $label . '-' . $suffix,
            ':final_number_key' => 'repair-overhaul|qa-ro-' . strtolower($label) . '-' . strtolower($suffix),
        ]);
    };

    $createReport($reportId, 'CREATE');
    $beforeCount = (int) $db->query('SELECT COUNT(*) FROM work_orders')->fetchColumn();
    $result = ReportIntegration::applyFinal($db, 'repair-overhaul', $reportId, $baseFields, $rows, 1);
    repairOverhaulAssert($result['applied'] === true && $result['createdItemCount'] === 1, 'final report creates one Work Order');
    repairOverhaulAssert($result['workOrderId'] === $baseFields['nomor'], 'report number becomes the Work Order ID');

    $workOrder = $db->prepare('SELECT * FROM work_orders WHERE wo_id = :wo_id');
    $workOrder->execute([':wo_id' => $baseFields['nomor']]);
    $createdWorkOrder = $workOrder->fetch(PDO::FETCH_ASSOC);
    repairOverhaulAssert(
        $createdWorkOrder
        && $createdWorkOrder['asset_id'] === $asset['asset_id']
        && $createdWorkOrder['priority'] === 'High'
        && $createdWorkOrder['assigned_mechanic'] === 'Administrator Utama',
        'asset, urgency, and PIC are mapped to the Work Order'
    );
    repairOverhaulAssert(
        str_contains((string) $createdWorkOrder['issue_description'], 'QA Hydraulic Component')
        && str_contains((string) $createdWorkOrder['issue_description'], 'Lakukan dismantle'),
        'part and solution details are retained in the Work Order description'
    );

    $ledger = $db->prepare(
        'SELECT work_order_id, owns_work_order FROM report_work_order_integrations
         WHERE report_id = :report_id AND reversed_at IS NULL'
    );
    $ledger->execute([':report_id' => $reportId]);
    $link = $ledger->fetch(PDO::FETCH_ASSOC);
    repairOverhaulAssert($link && (int) $link['owns_work_order'] === 1, 'report ledger owns the created Work Order');

    $retry = ReportIntegration::applyFinal($db, 'repair-overhaul', $reportId, $baseFields, $rows, 1);
    repairOverhaulAssert(!empty($retry['alreadyApplied']), 'retry is idempotent');
    repairOverhaulAssert(
        (int) $db->query('SELECT COUNT(*) FROM work_orders')->fetchColumn() === $beforeCount + 1,
        'retry does not duplicate the Work Order'
    );

    $reversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    $workOrder->execute([':wo_id' => $baseFields['nomor']]);
    repairOverhaulAssert(
        $reversal['workOrderDeletedCount'] === 1 && !$workOrder->fetch(),
        'void removes an unchanged report-owned Work Order'
    );
    $secondReversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    repairOverhaulAssert($secondReversal['applied'] === false, 'repeated void is idempotent');

    $createReport($preservedReportId, 'PRESERVE');
    $preservedFields = [...$baseFields, 'nomor' => 'QA-RO-P-' . $suffix, 'urgensi' => 'Emergency'];
    $preservedResult = ReportIntegration::applyFinal(
        $db,
        'repair-overhaul',
        $preservedReportId,
        $preservedFields,
        $rows,
        1
    );
    repairOverhaulAssert($preservedResult['createdItemCount'] === 1, 'second report creates a distinct Work Order');
    $db->prepare("UPDATE work_orders SET status = 'In Progress' WHERE wo_id = :wo_id")
        ->execute([':wo_id' => $preservedFields['nomor']]);
    $preservedReversal = ReportIntegration::reverseFinal($db, $preservedReportId, 1);
    $workOrder->execute([':wo_id' => $preservedFields['nomor']]);
    repairOverhaulAssert(
        $preservedReversal['workOrderPreservedCount'] === 1 && (bool) $workOrder->fetch(),
        'void preserves a Work Order already processed by the workshop'
    );

    try {
        ReportIntegration::applyFinal(
            $db,
            'repair-overhaul',
            'eeeeeeee-eeee-4eee-8eee-' . $suffix,
            [...$baseFields, 'nomor' => 'QA-RO-BAD-' . $suffix, 'estimasi_min' => '3000000', 'estimasi_max' => '1000000'],
            $rows,
            1
        );
        repairOverhaulAssert(false, 'invalid estimate range must be rejected');
    } catch (DomainException $error) {
        repairOverhaulAssert(str_contains($error->getMessage(), 'minimum'), 'invalid estimate range is rejected');
    }

    echo "\nRepair & Overhaul integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
