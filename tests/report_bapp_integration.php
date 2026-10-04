<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
try {
    $db = new PDO(
        $dsn,
        (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'),
        (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $error) {
    echo "SKIP: {$error->getMessage()}\n";
    exit(0);
}

ReportIntegration::ensureTables($db);
$passed = 0;
function bappAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException("FAIL: {$message}");
    $passed++;
    echo "PASS: {$message}\n";
}

$context = $db->query(
    "SELECT w.wo_id, w.asset_id
     FROM work_orders w INNER JOIN assets a ON a.asset_id = w.asset_id
     WHERE a.is_active = 1 AND w.status NOT IN ('Closed', 'Cancelled')
     ORDER BY w.wo_id LIMIT 1"
)->fetch();
$part = $db->query(
    'SELECT part_id, part_number, part_name, unit_measure, stock_qty
     FROM parts ORDER BY part_id LIMIT 1'
)->fetch();
if (!$context || !$part) {
    echo "SKIP: prerequisite data unavailable.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$spbId = 'QA-BAPP-SPB-' . $suffix;
$ppbId = 'QA-BAPP-PPB-' . $suffix;
$requestItemId = 'QA-BAPP-PRI-' . $suffix;
$orderItemId = 'QA-BAPP-POI-' . $suffix;
$partialReportId = '81818181-8181-4818-8818-' . $suffix;
$finalReportId = '82828282-8282-4828-8828-' . $suffix;
$date = (new DateTimeImmutable('today'))->format('Y-m-d');
$stockBefore = (int) $part['stock_qty'];

$db->beginTransaction();
try {
    $db->prepare(
        "INSERT INTO purchase_requests
         (spb_id, wo_id, asset_id, requested_by, urgency, status, requested_at)
         VALUES (:spb, :wo, :asset, 1, 'Normal', 'Submitted', NOW())"
    )->execute([':spb' => $spbId, ':wo' => $context['wo_id'], ':asset' => $context['asset_id']]);
    $db->prepare(
        "INSERT INTO purchase_request_items
         (id, spb_id, part_number, description, qty_requested, status)
         VALUES (:id, :spb, :part, :description, 5, 'Menunggu Approval')"
    )->execute([
        ':id' => $requestItemId,
        ':spb' => $spbId,
        ':part' => $part['part_number'],
        ':description' => $part['part_name'],
    ]);
    $db->prepare(
        "INSERT INTO purchase_orders
         (ppb_id, spb_id, asset_id, wo_id, vendor, project, delivery_due,
          delivery_location, subtotal, tax_amount, total_amount, status, created_by)
         VALUES
         (:ppb, :spb, :asset, :wo, 'QA Vendor', 'QA Project', :due,
          'QA Yard', 5000, 550, 5550, 'Submitted', 1)"
    )->execute([
        ':ppb' => $ppbId,
        ':spb' => $spbId,
        ':asset' => $context['asset_id'],
        ':wo' => $context['wo_id'],
        ':due' => $date,
    ]);
    $db->prepare(
        'INSERT INTO purchase_order_items
         (id, ppb_id, part_number, description, unit_measure, quantity, unit_price, total_price)
         VALUES (:id, :ppb, :part, :description, :unit, 5, 1000, 5000)'
    )->execute([
        ':id' => $orderItemId,
        ':ppb' => $ppbId,
        ':part' => $part['part_number'],
        ':description' => $part['part_name'],
        ':unit' => $part['unit_measure'],
    ]);

    $db->prepare(
        "INSERT INTO report_templates
         (template_key, code, title, version, schema_json, is_active)
         VALUES (:key, 'BAPP', 'QA BAPP', 1, '{}', 1)"
    )->execute([':key' => 'qa-bapp-' . strtolower($suffix)]);
    $templateId = (int) $db->lastInsertId();
    $insertReport = $db->prepare(
        "INSERT INTO report_records
         (report_id, template_id, client_key, report_number, status, source_method,
          field_data, draft_data, standardized_payload, has_pending_attachments,
          created_by, finalized_at, final_number_key)
         VALUES
         (:id, :template, :client, :number, 'FINAL', 'form', '{}', '{}', '{}',
          0, 1, NOW(), :final_key)"
    );
    $makeReport = static function (string $id, string $number) use ($insertReport, $templateId): void {
        $insertReport->execute([
            ':id' => $id,
            ':template' => $templateId,
            ':client' => 'qa-' . strtolower($number),
            ':number' => $number,
            ':final_key' => 'bapp|' . strtolower($number),
        ]);
    };

    $fields = [
        'nomor' => 'QA-BAPP-PARTIAL-' . $suffix,
        'tanggal' => $date,
        'pengirim' => 'QA Vendor',
        'nomor_po' => $ppbId,
    ];
    $partialRows = [[
        'nama' => $part['part_name'],
        'satuan' => $part['unit_measure'],
        'jumlah' => '5',
        'baik' => '4',
        'rusak' => '0',
        'kurang' => '1',
        'keterangan' => 'QA partial receipt',
    ]];
    $makeReport($partialReportId, $fields['nomor']);
    $partial = ReportIntegration::applyFinal($db, 'bapp', $partialReportId, $fields, $partialRows, 1);
    bappAssert($partial['applied'] && $partial['itemCount'] === 1, 'partial BAPP creates one receipt ledger row');
    bappAssert($partial['totalQuantity'] === 4, 'only good quantity is accepted into inventory');
    bappAssert(
        (int) $db->query('SELECT stock_qty FROM parts WHERE part_id = ' . (int) $part['part_id'])->fetchColumn() === $stockBefore + 4,
        'partial BAPP increases stock by good quantity only'
    );
    $statuses = $db->query(
        'SELECT po.status AS order_status, pr.status AS request_status, pri.status AS item_status
         FROM purchase_orders po
         INNER JOIN purchase_requests pr ON pr.spb_id = po.spb_id
         INNER JOIN purchase_request_items pri ON pri.spb_id = pr.spb_id
         WHERE po.ppb_id = ' . $db->quote($ppbId)
    )->fetch();
    bappAssert(
        $statuses['order_status'] === 'Ordered'
        && $statuses['request_status'] === 'Ordered'
        && $statuses['item_status'] === 'Parsial',
        'partial BAPP keeps procurement open and marks the SPB item partial'
    );
    bappAssert(
        !empty(ReportIntegration::applyFinal($db, 'bapp', $partialReportId, $fields, $partialRows, 1)['alreadyApplied']),
        'BAPP retry is idempotent'
    );

    $finalFields = [...$fields, 'nomor' => 'QA-BAPP-FINAL-' . $suffix];
    $finalRows = [[
        'nama' => $part['part_name'],
        'satuan' => $part['unit_measure'],
        'jumlah' => '1',
        'baik' => '1',
        'rusak' => '0',
        'kurang' => '0',
        'keterangan' => 'QA final receipt',
    ]];
    $makeReport($finalReportId, $finalFields['nomor']);
    ReportIntegration::applyFinal($db, 'bapp', $finalReportId, $finalFields, $finalRows, 1);
    $statuses = $db->query(
        'SELECT po.status AS order_status, pr.status AS request_status, pri.status AS item_status
         FROM purchase_orders po
         INNER JOIN purchase_requests pr ON pr.spb_id = po.spb_id
         INNER JOIN purchase_request_items pri ON pri.spb_id = pr.spb_id
         WHERE po.ppb_id = ' . $db->quote($ppbId)
    )->fetch();
    bappAssert(
        $statuses['order_status'] === 'Received'
        && $statuses['request_status'] === 'Issued'
        && $statuses['item_status'] === 'Tiba',
        'complete BAPP closes the PPB and marks the SPB item arrived'
    );
    bappAssert(
        (int) $db->query('SELECT stock_qty FROM parts WHERE part_id = ' . (int) $part['part_id'])->fetchColumn() === $stockBefore + 5,
        'complete receipt reaches the ordered stock quantity'
    );

    $finalVoid = ReportIntegration::reverseFinal($db, $finalReportId, 1);
    bappAssert(
        $finalVoid['goodsReceiptItemCount'] === 1
        && (int) $db->query('SELECT stock_qty FROM parts WHERE part_id = ' . (int) $part['part_id'])->fetchColumn() === $stockBefore + 4,
        'void reverses the final receipt stock movement'
    );
    $statuses = $db->query(
        'SELECT po.status AS order_status, pr.status AS request_status, pri.status AS item_status
         FROM purchase_orders po
         INNER JOIN purchase_requests pr ON pr.spb_id = po.spb_id
         INNER JOIN purchase_request_items pri ON pri.spb_id = pr.spb_id
         WHERE po.ppb_id = ' . $db->quote($ppbId)
    )->fetch();
    bappAssert(
        $statuses['order_status'] === 'Ordered'
        && $statuses['request_status'] === 'Ordered'
        && $statuses['item_status'] === 'Parsial',
        'void restores the previous partial procurement statuses'
    );

    ReportIntegration::reverseFinal($db, $partialReportId, 1);
    $statuses = $db->query(
        'SELECT po.status AS order_status, pr.status AS request_status, pri.status AS item_status
         FROM purchase_orders po
         INNER JOIN purchase_requests pr ON pr.spb_id = po.spb_id
         INNER JOIN purchase_request_items pri ON pri.spb_id = pr.spb_id
         WHERE po.ppb_id = ' . $db->quote($ppbId)
    )->fetch();
    bappAssert(
        (int) $db->query('SELECT stock_qty FROM parts WHERE part_id = ' . (int) $part['part_id'])->fetchColumn() === $stockBefore
        && $statuses['order_status'] === 'Submitted'
        && $statuses['request_status'] === 'Submitted'
        && $statuses['item_status'] === 'Menunggu Approval',
        'voiding all BAPP reports restores stock and original statuses'
    );
    bappAssert(
        ReportIntegration::reverseFinal($db, $partialReportId, 1)['applied'] === false,
        'repeated BAPP void is idempotent'
    );

    echo "\nBatch 11 BAPP integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
