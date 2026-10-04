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
function internalDeliveryAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException("FAIL: {$message}");
    $passed++;
    echo "PASS: {$message}\n";
}

$part = $db->query(
    'SELECT part_id, part_number, part_name, unit_measure, stock_qty
     FROM parts ORDER BY part_id LIMIT 1'
)->fetch();
if (!$part) {
    echo "SKIP: Master Part is empty.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$incomingReportId = '83838383-8383-4838-8838-' . $suffix;
$outgoingReportId = '84848484-8484-4848-8848-' . $suffix;
$date = (new DateTimeImmutable('today'))->format('Y-m-d');
$stockBefore = (int) $part['stock_qty'];
$row = [[
    'nama' => $part['part_name'],
    'satuan' => $part['unit_measure'],
    'jumlah' => '3',
    'keterangan' => 'QA transfer intern',
]];

$db->beginTransaction();
try {
    $db->prepare(
        "INSERT INTO report_templates
         (template_key, code, title, version, schema_json, is_active)
         VALUES (:key, 'BK', 'QA Bukti Kirim', 1, '{}', 1)"
    )->execute([':key' => 'qa-bukti-kirim-' . strtolower($suffix)]);
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
            ':final_key' => 'bukti-kirim|' . strtolower($number),
        ]);
    };

    $incomingFields = [
        'transaksi' => 'Terima',
        'nomor' => 'QA-BT-' . $suffix,
        'dari' => 'Project QA',
        'ke' => 'Gudang QA',
        'tanggal' => $date,
    ];
    $makeReport($incomingReportId, $incomingFields['nomor']);
    $incoming = ReportIntegration::applyFinal(
        $db,
        'bukti-kirim',
        $incomingReportId,
        $incomingFields,
        $row,
        1
    );
    internalDeliveryAssert(
        $incoming['applied'] && $incoming['movementType'] === 'IN' && $incoming['totalQuantity'] === 3,
        'Terima creates an inbound internal transaction'
    );
    internalDeliveryAssert(
        (int) $db->query('SELECT stock_qty FROM parts WHERE part_id = ' . (int) $part['part_id'])->fetchColumn() === $stockBefore + 3,
        'Terima increases Master Part stock'
    );
    $incomingLedger = $db->query(
        'SELECT movement_type, counterparty, stock_before, stock_after
         FROM inventory_transactions WHERE report_id = ' . $db->quote($incomingReportId)
    )->fetch();
    internalDeliveryAssert(
        $incomingLedger['movement_type'] === 'IN'
        && $incomingLedger['counterparty'] === 'Project QA'
        && (int) $incomingLedger['stock_before'] === $stockBefore
        && (int) $incomingLedger['stock_after'] === $stockBefore + 3,
        'Terima ledger stores source party and stock balance'
    );
    internalDeliveryAssert(
        !empty(ReportIntegration::applyFinal($db, 'bukti-kirim', $incomingReportId, $incomingFields, $row, 1)['alreadyApplied']),
        'Terima retry is idempotent'
    );

    $outgoingFields = [
        'transaksi' => 'Kirim',
        'nomor' => 'QA-BK-' . $suffix,
        'dari' => 'Gudang QA',
        'ke' => 'Workshop QA',
        'tanggal' => $date,
    ];
    $outgoingRows = [[...$row[0], 'jumlah' => '2']];
    $makeReport($outgoingReportId, $outgoingFields['nomor']);
    $outgoing = ReportIntegration::applyFinal(
        $db,
        'bukti-kirim',
        $outgoingReportId,
        $outgoingFields,
        $outgoingRows,
        1
    );
    internalDeliveryAssert(
        $outgoing['applied'] && $outgoing['movementType'] === 'OUT' && $outgoing['totalQuantity'] === 2,
        'Kirim creates an outbound internal transaction'
    );
    $outgoingLedger = $db->query(
        'SELECT movement_type, counterparty, stock_before, stock_after
         FROM inventory_transactions WHERE report_id = ' . $db->quote($outgoingReportId)
    )->fetch();
    internalDeliveryAssert(
        $outgoingLedger['movement_type'] === 'OUT'
        && $outgoingLedger['counterparty'] === 'Workshop QA'
        && (int) $outgoingLedger['stock_after'] === $stockBefore + 1,
        'Kirim decreases stock and stores the destination'
    );
    internalDeliveryAssert(
        !empty(ReportIntegration::applyFinal($db, 'bukti-kirim', $outgoingReportId, $outgoingFields, $outgoingRows, 1)['alreadyApplied']),
        'Kirim retry is idempotent'
    );

    try {
        ReportIntegration::applyFinal(
            $db,
            'bukti-kirim',
            '85858585-8585-4858-8858-' . $suffix,
            [...$outgoingFields, 'nomor' => 'QA-BK-OVER-' . $suffix],
            [[...$row[0], 'jumlah' => (string) ($stockBefore + 1000)]],
            1
        );
        internalDeliveryAssert(false, 'stock overdraw must be rejected');
    } catch (DomainException $error) {
        internalDeliveryAssert(str_contains($error->getMessage(), 'tidak cukup'), 'Kirim rejects insufficient stock');
    }

    ReportIntegration::reverseFinal($db, $outgoingReportId, 1);
    internalDeliveryAssert(
        (int) $db->query('SELECT stock_qty FROM parts WHERE part_id = ' . (int) $part['part_id'])->fetchColumn() === $stockBefore + 3,
        'void Kirim restores outbound stock'
    );
    ReportIntegration::reverseFinal($db, $incomingReportId, 1);
    internalDeliveryAssert(
        (int) $db->query('SELECT stock_qty FROM parts WHERE part_id = ' . (int) $part['part_id'])->fetchColumn() === $stockBefore,
        'void Terima restores the original stock'
    );
    internalDeliveryAssert(
        ReportIntegration::reverseFinal($db, $incomingReportId, 1)['applied'] === false,
        'repeated internal delivery void is idempotent'
    );

    echo "\nBatch 12 internal delivery integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
