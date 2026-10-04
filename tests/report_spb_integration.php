<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function spbAssert(bool $condition, string $message): void
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
$context = $db->query(
    "SELECT w.wo_id, w.asset_id, a.asset_code
     FROM work_orders w
     INNER JOIN assets a ON a.asset_id = w.asset_id
     WHERE a.is_active = 1 AND w.status NOT IN ('Closed', 'Cancelled')
     ORDER BY w.wo_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
$part = $db->query(
    'SELECT part_number, part_name, unit_measure FROM parts ORDER BY part_id LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);
if (!$context || !$part) {
    echo "SKIP: active Work Order or Master Part is unavailable.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$templateKey = 'qa-spb-' . strtolower($suffix);
$reportId = 'abababab-abab-4aba-8aba-' . $suffix;
$preservedReportId = 'cdcdcdcd-cdcd-4cdc-8cdc-' . $suffix;
$reportDate = (new DateTimeImmutable('today'))->format('Y-m-d');
$baseFields = [
    'nomor_spb' => 'QA-SPB-' . $suffix,
    'nomor_wo' => $context['wo_id'],
    'kode_unit' => $context['asset_id'],
    'tanggal' => $reportDate,
    'urgensi' => 'Emergency',
];
$rows = [[
    'nama' => $part['part_name'],
    'spesifikasi' => $part['part_number'],
    'satuan' => $part['unit_measure'],
    'jumlah' => '2',
    'keterangan' => 'QA kebutuhan untuk pekerjaan workshop',
    'status' => 'Diajukan',
]];

$db->beginTransaction();
try {
    $template = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'P-1', 'QA SPB', 1, '{}', 1)"
    );
    $template->execute([':template_key' => $templateKey]);
    $templateId = (int) $db->lastInsertId();

    $insertReport = $db->prepare(
        "INSERT INTO report_records
         (report_id, template_id, client_key, report_number, status, source_method, field_data,
          draft_data, standardized_payload, has_pending_attachments, created_by, finalized_at, final_number_key)
         VALUES (:report_id, :template_id, :client_key, :report_number, 'FINAL', 'form', '{}', '{}', '{}', 0, 1, NOW(), :final_number_key)"
    );
    $createReport = static function (string $id, string $label) use ($insertReport, $templateId, $templateKey): void {
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-client-' . strtolower($label),
            ':report_number' => 'QA-' . $label,
            ':final_number_key' => $templateKey . '|qa-' . strtolower($label),
        ]);
    };

    $beforeRequests = (int) $db->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn();
    $createReport($reportId, 'SPB-' . $suffix);
    $result = ReportIntegration::applyFinal($db, 'spb', $reportId, $baseFields, $rows, 1);

    spbAssert($result['applied'] === true && $result['itemCount'] === 1, 'final SPB creates one purchase request item');
    spbAssert($result['spbId'] === $baseFields['nomor_spb'], 'report number becomes the SPB ID');

    $requestStatement = $db->prepare('SELECT * FROM purchase_requests WHERE spb_id = :spb_id');
    $requestStatement->execute([':spb_id' => $baseFields['nomor_spb']]);
    $request = $requestStatement->fetch(PDO::FETCH_ASSOC);
    spbAssert(
        $request
        && $request['wo_id'] === $context['wo_id']
        && $request['asset_id'] === $context['asset_id']
        && $request['urgency'] === 'Emergency'
        && $request['status'] === 'Submitted',
        'Work Order, asset, urgency, and request status are mapped'
    );

    $itemStatement = $db->prepare('SELECT * FROM purchase_request_items WHERE spb_id = :spb_id');
    $itemStatement->execute([':spb_id' => $baseFields['nomor_spb']]);
    $item = $itemStatement->fetch(PDO::FETCH_ASSOC);
    spbAssert(
        $item
        && $item['part_number'] === $part['part_number']
        && (int) $item['qty_requested'] === 2
        && $item['status'] === 'Menunggu Approval',
        'part, quantity, and item status are mapped'
    );

    $ledger = $db->prepare(
        'SELECT spb_id, owns_purchase_request FROM report_purchase_request_integrations
         WHERE report_id = :report_id AND reversed_at IS NULL'
    );
    $ledger->execute([':report_id' => $reportId]);
    $link = $ledger->fetch(PDO::FETCH_ASSOC);
    spbAssert($link && (int) $link['owns_purchase_request'] === 1, 'integration ledger owns the created SPB');

    $retry = ReportIntegration::applyFinal($db, 'spb', $reportId, $baseFields, $rows, 1);
    spbAssert(!empty($retry['alreadyApplied']), 'retry is idempotent');
    spbAssert(
        (int) $db->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn() === $beforeRequests + 1,
        'retry does not duplicate the purchase request'
    );

    $reversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    $requestStatement->execute([':spb_id' => $baseFields['nomor_spb']]);
    spbAssert(
        $reversal['purchaseRequestDeletedCount'] === 1 && !$requestStatement->fetch(),
        'void removes an unchanged report-owned SPB'
    );
    $itemStatement->execute([':spb_id' => $baseFields['nomor_spb']]);
    spbAssert(!$itemStatement->fetch(), 'void removes the SPB line items');
    $secondReversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    spbAssert($secondReversal['applied'] === false, 'repeated void is idempotent');

    $createReport($preservedReportId, 'SPB-P-' . $suffix);
    $preservedFields = [...$baseFields, 'nomor_spb' => 'QA-SPB-P-' . $suffix];
    $preservedResult = ReportIntegration::applyFinal($db, 'spb', $preservedReportId, $preservedFields, $rows, 1);
    spbAssert($preservedResult['createdItemCount'] === 1, 'second report creates a distinct SPB');
    $db->prepare("UPDATE purchase_requests SET status = 'Approved' WHERE spb_id = :spb_id")
        ->execute([':spb_id' => $preservedFields['nomor_spb']]);
    $preservedReversal = ReportIntegration::reverseFinal($db, $preservedReportId, 1);
    $requestStatement->execute([':spb_id' => $preservedFields['nomor_spb']]);
    spbAssert(
        $preservedReversal['purchaseRequestPreservedCount'] === 1 && (bool) $requestStatement->fetch(),
        'void preserves an SPB already processed by logistics'
    );

    try {
        ReportIntegration::applyFinal(
            $db,
            'spb',
            'efefefef-efef-4efe-8efe-' . $suffix,
            [...$baseFields, 'nomor_spb' => 'QA-SPB-BAD-' . $suffix, 'kode_unit' => 'UNIT-TIDAK-SESUAI'],
            $rows,
            1
        );
        spbAssert(false, 'mismatched Work Order asset must be rejected');
    } catch (DomainException $error) {
        spbAssert(str_contains($error->getMessage(), 'tidak sesuai'), 'mismatched Work Order asset is rejected');
    }

    echo "\nSPB integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
