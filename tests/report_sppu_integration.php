<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
try {
    $db = new PDO($dsn, (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'), (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $error) {
    echo "SKIP: {$error->getMessage()}\n";
    exit(0);
}
ReportIntegration::ensureTables($db);
$passed = 0;
function sppuAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException("FAIL: {$message}");
    $passed++;
    echo "PASS: {$message}\n";
}

$context = $db->query("SELECT w.wo_id,w.asset_id,a.last_hm_km FROM work_orders w JOIN assets a ON a.asset_id=w.asset_id WHERE a.is_active=1 AND w.status NOT IN ('Closed','Cancelled') ORDER BY w.wo_id LIMIT 1")->fetch();
$part = $db->query('SELECT part_number,part_name,unit_measure FROM parts ORDER BY part_id LIMIT 1')->fetch();
if (!$context || !$part) {
    echo "SKIP: prerequisite Work Order or Master Part unavailable.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$yardReportId = '86868686-8686-4868-8868-' . $suffix;
$templateReportId = '87878787-8787-4878-8878-' . $suffix;
$date = (new DateTimeImmutable('today'))->format('Y-m-d');
$db->beginTransaction();
try {
    $db->prepare("INSERT INTO report_templates(template_key,code,title,version,schema_json,is_active) VALUES(:key,'SPPU','QA SPPU',1,'{}',1)")
        ->execute([':key' => 'qa-sppu-' . strtolower($suffix)]);
    $templateId = (int) $db->lastInsertId();
    $insertReport = $db->prepare("INSERT INTO report_records(report_id,template_id,client_key,report_number,status,source_method,field_data,draft_data,standardized_payload,has_pending_attachments,created_by,finalized_at,final_number_key) VALUES(:id,:template,:client,:number,'FINAL','form','{}','{}','{}',0,1,NOW(),:final_key)");
    $makeReport = static function (string $id, string $number) use ($insertReport, $templateId): void {
        $insertReport->execute([':id' => $id, ':template' => $templateId, ':client' => 'qa-' . strtolower($number), ':number' => $number, ':final_key' => 'sppu|' . strtolower($number)]);
    };

    $yardNumber = 'QA-SPPU-' . $suffix;
    $yardFields = [
        'nomor' => $yardNumber,
        'tanggal' => $date,
        'nomor_wo' => $context['wo_id'],
        'kode_unit' => $context['asset_id'],
        'lokasi' => 'QA Yard',
        'diajukan_oleh' => 'QA Mechanic',
    ];
    $yardRows = [[
        'nama' => $part['part_name'],
        'hm' => (string) $context['last_hm_km'],
        'operator' => 'QA Operator',
        'pn' => $part['part_number'],
        'jumlah' => '2',
        'satuan' => $part['unit_measure'],
        'analisa' => 'Kerusakan membutuhkan penggantian segera',
        'solusi' => 'Ganti part dan lakukan pengujian fungsi',
    ]];
    $makeReport($yardReportId, $yardNumber);
    $yard = ReportIntegration::applyFinal($db, 'sppu', $yardReportId, $yardFields, $yardRows, 1);
    sppuAssert($yard['applied'] && $yard['type'] === 'urgent-parts-request' && $yard['itemCount'] === 1, 'SPPU Yard creates one urgent purchase request item');

    $request = $db->prepare('SELECT * FROM purchase_requests WHERE spb_id=:id');
    $request->execute([':id' => $yardNumber]);
    $createdRequest = $request->fetch();
    sppuAssert($createdRequest && $createdRequest['wo_id'] === $context['wo_id'] && $createdRequest['asset_id'] === $context['asset_id'] && $createdRequest['urgency'] === 'Emergency', 'SPPU Yard links Work Order and unit as Emergency');

    $item = $db->prepare('SELECT * FROM purchase_request_items WHERE spb_id=:id');
    $item->execute([':id' => $yardNumber]);
    $createdItem = $item->fetch();
    sppuAssert($createdItem && $createdItem['part_number'] === $part['part_number'] && (int) $createdItem['qty_requested'] === 2 && str_contains((string) $createdItem['description'], 'Kerusakan'), 'SPPU Yard maps Master Part, quantity, and analysis');
    sppuAssert(!empty(ReportIntegration::applyFinal($db, 'sppu', $yardReportId, $yardFields, $yardRows, 1)['alreadyApplied']), 'SPPU Yard retry is idempotent');
    $yardVoid = ReportIntegration::reverseFinal($db, $yardReportId, 1);
    $request->execute([':id' => $yardNumber]);
    sppuAssert($yardVoid['purchaseRequestDeletedCount'] === 1 && !$request->fetch(), 'void removes unchanged SPPU request');

    $templateNumber = 'QA-SPPU6-' . $suffix;
    $templateFields = [
        'nomor' => $templateNumber,
        'tanggal' => $date,
        'nomor_wo' => $context['wo_id'],
        'kode_unit' => $context['asset_id'],
        'lokasi' => 'QA Yard',
        'prioritas' => 'Urgent dan diprioritaskan',
        'jenis_unit' => 'QA Unit',
        'serial_number' => 'QA Serial',
        'hour_meter' => (string) $context['last_hm_km'],
        'project' => 'QA Project',
        'analisa' => 'Analisa utama SPPU 006',
        'dampak' => 'Unit berisiko berhenti',
        'tindak_lanjut' => 'Pengadaan dan penggantian segera',
    ];
    $templateRows = [[
        'nama' => $part['part_name'], 'hm' => (string) $context['last_hm_km'],
        'operator' => 'QA Operator', 'pn' => $part['part_number'], 'jumlah' => '1',
        'satuan' => $part['unit_measure'], 'kelompok' => 'Critical part',
    ]];
    $makeReport($templateReportId, $templateNumber);
    $templateResult = ReportIntegration::applyFinal($db, 'sppu-006-pf04-cs10', $templateReportId, $templateFields, $templateRows, 1);
    sppuAssert($templateResult['applied'] && $templateResult['spbId'] === $templateNumber, 'SPPU 006 creates linked request');
    $item->execute([':id' => $templateNumber]);
    sppuAssert(str_contains((string) $item->fetch()['description'], 'Critical part'), 'SPPU 006 retains part group');

    $db->prepare("UPDATE purchase_requests SET status='Approved' WHERE spb_id=:id")->execute([':id' => $templateNumber]);
    $processedVoid = ReportIntegration::reverseFinal($db, $templateReportId, 1);
    $request->execute([':id' => $templateNumber]);
    sppuAssert($processedVoid['purchaseRequestPreservedCount'] === 1 && (bool) $request->fetch(), 'void preserves processed SPPU');

    try {
        ReportIntegration::applyFinal($db, 'sppu', '88888888-8888-4888-8888-' . $suffix, [...$yardFields, 'nomor' => 'QA-SPPU-BAD-' . $suffix, 'kode_unit' => 'MISMATCH-ASSET'], $yardRows, 1);
        sppuAssert(false, 'mismatched unit must be rejected');
    } catch (DomainException $error) {
        sppuAssert(str_contains($error->getMessage(), 'tidak sesuai'), 'SPPU rejects mismatched Work Order unit');
    }
    try {
        ReportIntegration::applyFinal($db, 'sppu', '89898989-8989-4898-8898-' . $suffix, [...$yardFields, 'nomor' => 'QA-SPPU-PART-' . $suffix], [[...$yardRows[0], 'nama' => 'QA Unknown Part', 'pn' => 'QA-UNKNOWN-' . $suffix]], 1);
        sppuAssert(false, 'unknown part must be rejected');
    } catch (DomainException $error) {
        sppuAssert(str_contains($error->getMessage(), 'Master Part'), 'SPPU rejects unknown Master Part');
    }

    echo "\nBatch 13 SPPU integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
