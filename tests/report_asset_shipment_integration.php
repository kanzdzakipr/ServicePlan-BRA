<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function shipmentAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

function shipmentUuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
        . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
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
$person = $db->query('SELECT user_id, full_name FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 1')
    ->fetch(PDO::FETCH_ASSOC);
$location = $db->query('SELECT location_id FROM locations WHERE is_active = 1 ORDER BY location_id LIMIT 1')
    ->fetchColumn();
if (!$person || $location === false) {
    echo "SKIP: Batch 17 test requires an active user and location.\n";
    exit(0);
}

$actorId = (int) $person['user_id'];
$senderName = (string) $person['full_name'];
$locationId = (int) $location;
$suffix = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$templateKey = 'qa-shipment-' . strtolower($suffix);
$assetOne = 'QA-BAPE-A-' . $suffix;
$assetTwo = 'QA-BAPE-B-' . $suffix;

$db->beginTransaction();
try {
    $insertAsset = $db->prepare(
        "INSERT INTO assets
         (asset_id, asset_code, type, category, status, current_location_id, last_hm_km, is_active)
         VALUES (:asset_id, :asset_code, 'Heavy Equipment', 'Other', :status, :location_id, :hm, 1)"
    );
    $insertAsset->execute([
        ':asset_id' => $assetOne, ':asset_code' => $assetOne,
        ':status' => 'READY', ':location_id' => $locationId, ':hm' => '123.45',
    ]);
    $insertAsset->execute([
        ':asset_id' => $assetTwo, ':asset_code' => $assetTwo,
        ':status' => 'BREAKDOWN', ':location_id' => $locationId, ':hm' => '678.90',
    ]);

    $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'BAPE', 'QA Asset Shipment', 1, '{}', 1)"
    )->execute([':template_key' => $templateKey]);
    $templateId = (int) $db->lastInsertId();
    $insertReport = $db->prepare(
        "INSERT INTO report_records
         (report_id, template_id, client_key, report_number, status, source_method, field_data,
          created_by, finalized_at, final_number_key)
         VALUES (:report_id, :template_id, :client_key, :report_number, 'FINAL', 'test', '{}',
          :created_by, NOW(), :final_number_key)"
    );
    $createReport = static function (string $label) use (
        $insertReport, $templateId, $templateKey, $suffix, $actorId
    ): string {
        $id = shipmentUuid();
        $number = "QA-BAPE-{$label}-{$suffix}";
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-bape-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => $number,
            ':created_by' => $actorId,
            ':final_number_key' => $templateKey . '|' . strtolower($number),
        ]);
        return $id;
    };

    $fields = [
        'nomor' => 'BAPE-QA-' . $suffix,
        'pengirim' => $senderName,
        'alamat' => 'Jl. Pengujian Integrasi No. 17',
        'nomor_polisi' => 'BM 1717 QA',
        'kontrak_angkutan' => 'KONTRAK-' . $suffix,
        'tanggal' => '2026-10-05',
        'penerima' => 'Ekspedisi QA ' . $suffix,
        'konfirmasi' => '0812-0000-0017',
    ];
    $rows = [
        ['nama' => $assetOne, 'satuan' => 'Unit', 'jumlah' => '1', 'baik' => '1', 'rusak' => '0', 'kurang' => '0', 'keterangan' => 'Segel lengkap'],
        ['nama' => $assetTwo, 'satuan' => 'Unit', 'jumlah' => '1', 'baik' => '0', 'rusak' => '1', 'kurang' => '0', 'keterangan' => 'Perlu pemeriksaan lanjutan'],
    ];

    $validReportId = $createReport('VALID');
    $result = ReportIntegration::applyFinal($db, 'penyerahan-ekspedisi', $validReportId, $fields, $rows, $actorId);
    shipmentAssert($result['applied'] === true && $result['itemCount'] === 2, 'final BAPE creates two shipment records');

    $records = $db->prepare('SELECT * FROM asset_shipments WHERE report_id = :report_id ORDER BY report_item_position');
    $records->execute([':report_id' => $validReportId]);
    $stored = $records->fetchAll(PDO::FETCH_ASSOC);
    shipmentAssert(count($stored) === 2, 'shipment register stores every selected asset');
    shipmentAssert(
        $stored[0]['asset_id'] === $assetOne
        && (int) $stored[0]['origin_location_id'] === $locationId
        && (int) $stored[0]['sender_user_id'] === $actorId
        && $stored[0]['carrier_name'] === $fields['penerima'],
        'BAPE maps Master Asset, origin, sender, and carrier'
    );
    shipmentAssert(
        $stored[0]['unit_condition'] === 'GOOD' && $stored[0]['shipment_status'] === 'IN_TRANSIT'
        && $stored[1]['unit_condition'] === 'DAMAGED' && $stored[1]['shipment_status'] === 'IN_TRANSIT',
        'BAPE retains good and damaged conditions in transit'
    );

    $assetState = $db->prepare('SELECT status, current_location_id, last_hm_km FROM assets WHERE asset_id = :asset_id');
    $assetState->execute([':asset_id' => $assetOne]);
    $unchanged = $assetState->fetch(PDO::FETCH_ASSOC);
    shipmentAssert(
        $unchanged['status'] === 'READY'
        && (int) $unchanged['current_location_id'] === $locationId
        && abs((float) $unchanged['last_hm_km'] - 123.45) < 0.005,
        'BAPE does not alter asset status, active location, or HM'
    );

    $retry = ReportIntegration::applyFinal($db, 'penyerahan-ekspedisi', $validReportId, $fields, $rows, $actorId);
    shipmentAssert(!empty($retry['alreadyApplied']), 'BAPE retry is idempotent');
    $records->execute([':report_id' => $validReportId]);
    shipmentAssert(count($records->fetchAll(PDO::FETCH_ASSOC)) === 2, 'BAPE retry does not duplicate shipment rows');

    $reversal = ReportIntegration::reverseFinal($db, $validReportId, $actorId);
    shipmentAssert($reversal['assetShipmentItemCount'] === 2, 'void reverses every BAPE shipment row');
    $active = $db->prepare('SELECT COUNT(*) FROM asset_shipments WHERE report_id = :report_id AND reversed_at IS NULL');
    $active->execute([':report_id' => $validReportId]);
    shipmentAssert((int) $active->fetchColumn() === 0, 'void hides BAPE rows from active shipment history');
    shipmentAssert(ReportIntegration::reverseFinal($db, $validReportId, $actorId)['applied'] === false, 'repeated BAPE void is idempotent');

    $unknownAssetId = $createReport('UNKNOWN-ASSET');
    try {
        ReportIntegration::applyFinal($db, 'penyerahan-ekspedisi', $unknownAssetId, $fields, [[...$rows[0], 'nama' => 'UNIT-TIDAK-ADA-' . $suffix]], $actorId);
        shipmentAssert(false, 'unknown asset must be rejected');
    } catch (DomainException $error) {
        shipmentAssert(str_contains($error->getMessage(), 'Master Asset'), 'unknown asset is rejected');
    }

    $unknownSenderId = $createReport('UNKNOWN-SENDER');
    try {
        ReportIntegration::applyFinal($db, 'penyerahan-ekspedisi', $unknownSenderId, [...$fields, 'pengirim' => 'PERSONEL-TIDAK-ADA-' . $suffix], [$rows[0]], $actorId);
        shipmentAssert(false, 'unknown sender must be rejected');
    } catch (DomainException $error) {
        shipmentAssert(str_contains($error->getMessage(), 'Master Personel'), 'unknown sender is rejected');
    }

    $duplicateId = $createReport('DUPLICATE');
    try {
        ReportIntegration::applyFinal($db, 'penyerahan-ekspedisi', $duplicateId, $fields, [$rows[0], $rows[0]], $actorId);
        shipmentAssert(false, 'duplicate asset must be rejected');
    } catch (DomainException $error) {
        shipmentAssert(str_contains($error->getMessage(), 'lebih dari sekali'), 'duplicate asset is rejected');
    }

    $invalidConditionId = $createReport('CONDITION');
    try {
        ReportIntegration::applyFinal($db, 'penyerahan-ekspedisi', $invalidConditionId, $fields, [[...$rows[0], 'baik' => '1', 'rusak' => '1']], $actorId);
        shipmentAssert(false, 'condition total mismatch must be rejected');
    } catch (DomainException $error) {
        shipmentAssert(str_contains($error->getMessage(), 'harus sama'), 'condition total mismatch is rejected');
    }

    $invalidQuantityId = $createReport('QUANTITY');
    try {
        ReportIntegration::applyFinal($db, 'penyerahan-ekspedisi', $invalidQuantityId, $fields, [[...$rows[0], 'jumlah' => '2', 'baik' => '2']], $actorId);
        shipmentAssert(false, 'quantity above one per asset must be rejected');
    } catch (DomainException $error) {
        shipmentAssert(str_contains($error->getMessage(), 'satu unit'), 'quantity above one per asset is rejected');
    }

    echo "\nBatch 17 asset shipment integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
