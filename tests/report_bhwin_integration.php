<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function integrationAssert(bool $condition, string $message): void
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
$suffix = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$partNumber = 'QA-BHWIN-' . $suffix;
$templateKey = 'qa-bhwin-' . strtolower($suffix);
$reportId = sprintf('%s-%s-4%s-8%s-%s',
    substr($suffix . '00000000', 0, 8),
    substr($suffix . '0000', 0, 4),
    substr($suffix . '000', 0, 3),
    substr($suffix . '000', 0, 3),
    substr($suffix . '000000000000', 0, 12)
);
$outReportId = '11111111-1111-4111-8111-' . substr($suffix . '000000000000', 0, 12);

$db->beginTransaction();
try {
    $template = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'BHW-IN', 'QA BHW-IN', 1, '{}', 1)"
    );
    $template->execute([':template_key' => $templateKey]);
    $templateId = (int) $db->lastInsertId();

    $part = $db->prepare(
        "INSERT INTO parts
         (part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, unit_cost, location_warehouse)
         VALUES (:part_number, 'QA Integration Part', 'Other', 'Pcs', 10, 2, 0, 'Gudang QA')"
    );
    $part->execute([':part_number' => $partNumber]);

    $report = $db->prepare(
        "INSERT INTO report_records
         (report_id, template_id, client_key, report_number, status, source_method, field_data, created_by, finalized_at, final_number_key)
         VALUES (:report_id, :template_id, :client_key, :report_number, 'FINAL', 'test', '{}', 1, NOW(), :final_number_key)"
    );
    $report->execute([
        ':report_id' => $reportId,
        ':template_id' => $templateId,
        ':client_key' => 'qa-client-' . strtolower($suffix),
        ':report_number' => 'QA/' . $suffix,
        ':final_number_key' => $templateKey . '|qa/' . strtolower($suffix),
    ]);

    $fields = ['tanggal' => date('Y-m-d'), 'nomor_log' => 'QA/' . $suffix];
    $rows = [[
        'tanggal' => date('Y-m-d'),
        'bapb' => 'QA-BAPB-' . $suffix,
        'dari' => 'QA Supplier',
        'part_number' => $partNumber,
        'nama' => 'QA Integration Part',
        'satuan' => 'Pcs',
        'jumlah' => '3',
        'saldo_lalu' => '10',
        'saldo_sekarang' => '13',
        'keterangan' => 'Transactional integration test',
    ]];

    $result = ReportIntegration::applyFinal($db, 'bhw-in', $reportId, $fields, $rows, 1);
    integrationAssert($result['applied'] === true, 'BHW-IN integration is applied');
    integrationAssert($result['itemCount'] === 1 && $result['totalQuantity'] === 3, 'integration summary is correct');

    $stock = $db->prepare('SELECT stock_qty FROM parts WHERE part_number = :part_number');
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert((int) $stock->fetchColumn() === 13, 'stock increases exactly once');

    $transactions = $db->prepare('SELECT COUNT(*) FROM inventory_transactions WHERE report_id = :report_id');
    $transactions->execute([':report_id' => $reportId]);
    integrationAssert((int) $transactions->fetchColumn() === 1, 'inventory ledger receives one transaction');

    $duplicate = ReportIntegration::applyFinal($db, 'bhw-in', $reportId, $fields, $rows, 1);
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert(!empty($duplicate['alreadyApplied']) && (int) $stock->fetchColumn() === 13, 'retry is idempotent');

    $outTemplate = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'BHW-OUT', 'QA BHW-OUT', 1, '{}', 1)"
    );
    $outTemplate->execute([':template_key' => $templateKey . '-out']);
    $outTemplateId = (int) $db->lastInsertId();

    $report->execute([
        ':report_id' => $outReportId,
        ':template_id' => $outTemplateId,
        ':client_key' => 'qa-client-out-' . strtolower($suffix),
        ':report_number' => 'QA-OUT/' . $suffix,
        ':final_number_key' => $templateKey . '-out|qa-out/' . strtolower($suffix),
    ]);

    $outFields = ['tanggal' => date('Y-m-d'), 'nomor_log' => 'QA-OUT/' . $suffix];
    $outRows = [[
        'tanggal' => date('Y-m-d'),
        'nomor_bukti' => 'QA-BK-' . $suffix,
        'tujuan' => 'QA Workshop',
        'part_number' => $partNumber,
        'nama' => 'QA Integration Part',
        'satuan' => 'Pcs',
        'persediaan' => '13',
        'diberikan' => '2',
        'sisa' => '11',
        'keterangan' => 'Transactional outbound integration test',
    ]];

    $outResult = ReportIntegration::applyFinal($db, 'bhw-out', $outReportId, $outFields, $outRows, 1);
    integrationAssert($outResult['applied'] === true, 'BHW-OUT integration is applied');
    integrationAssert($outResult['itemCount'] === 1 && $outResult['totalQuantity'] === 2, 'outbound summary is correct');
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert((int) $stock->fetchColumn() === 11, 'BHW-OUT decreases stock exactly once');

    $outDuplicate = ReportIntegration::applyFinal($db, 'bhw-out', $outReportId, $outFields, $outRows, 1);
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert(!empty($outDuplicate['alreadyApplied']) && (int) $stock->fetchColumn() === 11, 'outbound retry is idempotent');

    try {
        ReportIntegration::applyFinal($db, 'bhw-out', $outReportId . 'x', $outFields, [[
            ...$outRows[0],
            'persediaan' => '12',
            'sisa' => '10',
        ]], 1);
        integrationAssert(false, 'stale outbound balance must be rejected');
    } catch (DomainException $error) {
        integrationAssert(str_contains($error->getMessage(), 'stok database'), 'stale outbound balance is rejected');
    }

    $outReversal = ReportIntegration::reverseFinal($db, $outReportId, 1);
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert($outReversal['applied'] === true && (int) $stock->fetchColumn() === 13, 'void restores outbound stock');

    $secondOutReversal = ReportIntegration::reverseFinal($db, $outReportId, 1);
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert($secondOutReversal['applied'] === false && (int) $stock->fetchColumn() === 13, 'repeated outbound void is idempotent');

    $reversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert($reversal['applied'] === true && (int) $stock->fetchColumn() === 10, 'void reverses stock');

    $secondReversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    $stock->execute([':part_number' => $partNumber]);
    integrationAssert($secondReversal['applied'] === false && (int) $stock->fetchColumn() === 10, 'repeated void is idempotent');

    try {
        ReportIntegration::applyFinal($db, 'bhw-in', $reportId . 'x', $fields, [[
            ...$rows[0],
            'saldo_lalu' => '9',
            'saldo_sekarang' => '12',
        ]], 1);
        integrationAssert(false, 'stale balance must be rejected');
    } catch (DomainException $error) {
        integrationAssert(str_contains($error->getMessage(), 'stok database'), 'stale balance is rejected');
    }

    echo "\nBHW-IN/BHW-OUT integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
