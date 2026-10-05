<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function bastAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

function bastUuid(): string
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
$locations = $db->query(
    'SELECT location_id, location_name FROM locations WHERE is_active = 1 ORDER BY location_id LIMIT 3'
)->fetchAll(PDO::FETCH_ASSOC);
$actorId = (int) $db->query('SELECT user_id FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 1')->fetchColumn();
if (count($locations) < 3 || $actorId < 1) {
    echo "SKIP: Batch 14 test requires three active locations and one active user.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$assetId = 'QA-BAST-' . $suffix;
$templateKey = 'qa-bast-' . strtolower($suffix);
$reportId = bastUuid();
$invalidOriginReportId = bastUuid();
$decreasingHmReportId = bastUuid();
$preservedReportId = bastUuid();

$db->beginTransaction();
try {
    $template = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'MDE-1', 'QA BAST MDE-1', 1, '{}', 1)"
    );
    $template->execute([':template_key' => $templateKey]);
    $templateId = (int) $db->lastInsertId();

    $insertReport = $db->prepare(
        "INSERT INTO report_records
         (report_id, template_id, client_key, report_number, status, source_method, field_data,
          created_by, finalized_at, final_number_key)
         VALUES (:report_id, :template_id, :client_key, :report_number, 'FINAL', 'test', '{}',
          :created_by, NOW(), :final_number_key)"
    );
    $createReport = static function (string $id, string $label) use (
        $insertReport,
        $templateId,
        $templateKey,
        $suffix,
        $actorId
    ): void {
        $number = "QA-BAST-{$label}-{$suffix}";
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-bast-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => $number,
            ':created_by' => $actorId,
            ':final_number_key' => $templateKey . '|' . strtolower($number),
        ]);
    };

    $db->prepare(
        "INSERT INTO assets
         (asset_id, asset_code, type, category, make_model, status, current_location_id, last_hm_km, is_active)
         VALUES (:asset_id, :asset_code, 'Heavy Equipment', 'Dump Truck', 'QA Model', 'READY',
          :location_id, 100, 1)"
    )->execute([
        ':asset_id' => $assetId,
        ':asset_code' => $assetId,
        ':location_id' => (int) $locations[0]['location_id'],
    ]);

    $baseFields = [
        'nomor_urut' => 'QA-BAST-' . $suffix,
        'tanggal' => '2026-10-05',
        'kode_alat' => $assetId,
        'project' => 'QA Project',
        'dari' => 'QA Sender',
        'kepada' => 'QA Recipient',
        'jenis_alat' => 'Dump Truck',
        'merek_model' => 'QA Model',
        'jenis_serah_terima' => 'Mobilisasi',
        'project_asal' => (string) $locations[0]['location_name'],
        'project_tujuan' => (string) $locations[1]['location_name'],
        'hm_om' => '110',
    ];
    $rows = [[
        'item' => 'Kunci dan toolkit unit',
        'jumlah' => '1',
        'kondisi' => 'Baik',
        'keterangan' => 'Lengkap',
    ]];

    $createReport($reportId, 'CREATE');
    $result = ReportIntegration::applyFinal($db, 'bast-mde1', $reportId, $baseFields, $rows, $actorId);
    bastAssert($result['applied'] === true && $result['itemCount'] === 1, 'final BAST creates one asset movement');

    $movement = $db->prepare(
        'SELECT * FROM asset_movements WHERE movement_id = :movement_id'
    );
    $movement->execute([':movement_id' => (int) $result['movementId']]);
    $movementRow = $movement->fetch(PDO::FETCH_ASSOC);
    bastAssert(
        $movementRow
        && (string) $movementRow['asset_id'] === $assetId
        && (int) $movementRow['from_location_id'] === (int) $locations[0]['location_id']
        && (int) $movementRow['to_location_id'] === (int) $locations[1]['location_id'],
        'movement stores the asset, origin, and destination'
    );

    $asset = $db->prepare('SELECT current_location_id, last_hm_km FROM assets WHERE asset_id = :asset_id');
    $asset->execute([':asset_id' => $assetId]);
    $assetState = $asset->fetch(PDO::FETCH_ASSOC);
    bastAssert(
        (int) $assetState['current_location_id'] === (int) $locations[1]['location_id']
        && abs((float) $assetState['last_hm_km'] - 110.0) < 0.005,
        'final BAST updates Master Asset location and HM'
    );

    $retry = ReportIntegration::applyFinal($db, 'bast-mde1', $reportId, $baseFields, $rows, $actorId);
    bastAssert(!empty($retry['alreadyApplied']), 'BAST retry is idempotent');
    $movementCount = $db->prepare('SELECT COUNT(*) FROM asset_movements WHERE bast_number = :number');
    $movementCount->execute([':number' => $baseFields['nomor_urut']]);
    bastAssert((int) $movementCount->fetchColumn() === 1, 'BAST retry does not duplicate asset movements');

    $reversal = ReportIntegration::reverseFinal($db, $reportId, $actorId);
    $asset->execute([':asset_id' => $assetId]);
    $restored = $asset->fetch(PDO::FETCH_ASSOC);
    bastAssert(
        $reversal['assetMovementDeletedCount'] === 1
        && (int) $restored['current_location_id'] === (int) $locations[0]['location_id']
        && abs((float) $restored['last_hm_km'] - 100.0) < 0.005,
        'void restores the previous location and HM when no later movement exists'
    );
    $secondReversal = ReportIntegration::reverseFinal($db, $reportId, $actorId);
    bastAssert($secondReversal['applied'] === false, 'repeated BAST void is idempotent');

    $createReport($invalidOriginReportId, 'ORIGIN');
    try {
        ReportIntegration::applyFinal(
            $db,
            'bast-mde1',
            $invalidOriginReportId,
            [...$baseFields, 'nomor_urut' => 'QA-BAST-ORIGIN-' . $suffix,
                'project_asal' => (string) $locations[1]['location_name'],
                'project_tujuan' => (string) $locations[2]['location_name']],
            $rows,
            $actorId
        );
        bastAssert(false, 'mismatched origin must be rejected');
    } catch (DomainException $error) {
        bastAssert(str_contains($error->getMessage(), 'lokasi unit saat ini'), 'mismatched origin is rejected');
    }

    $createReport($decreasingHmReportId, 'HM');
    try {
        ReportIntegration::applyFinal(
            $db,
            'bast-mde1',
            $decreasingHmReportId,
            [...$baseFields, 'nomor_urut' => 'QA-BAST-HM-' . $suffix, 'hm_om' => '99'],
            $rows,
            $actorId
        );
        bastAssert(false, 'decreasing BAST HM must be rejected');
    } catch (DomainException $error) {
        bastAssert(str_contains($error->getMessage(), 'tidak boleh lebih kecil'), 'decreasing BAST HM is rejected');
    }

    $createReport($preservedReportId, 'PRESERVE');
    $preservedFields = [...$baseFields, 'nomor_urut' => 'QA-BAST-PRESERVE-' . $suffix];
    $preservedResult = ReportIntegration::applyFinal(
        $db,
        'bast-mde1',
        $preservedReportId,
        $preservedFields,
        $rows,
        $actorId
    );
    $db->prepare(
        'INSERT INTO asset_movements
         (asset_id, from_location_id, to_location_id, bast_number, movement_date, notes, requested_by)
         VALUES (:asset_id, :from_location_id, :to_location_id, :bast_number, NOW(), :notes, :requested_by)'
    )->execute([
        ':asset_id' => $assetId,
        ':from_location_id' => (int) $locations[1]['location_id'],
        ':to_location_id' => (int) $locations[2]['location_id'],
        ':bast_number' => 'QA-DOWNSTREAM-' . $suffix,
        ':notes' => 'Downstream QA movement',
        ':requested_by' => $actorId,
    ]);
    $db->prepare(
        'UPDATE assets SET current_location_id = :location_id, last_hm_km = 120 WHERE asset_id = :asset_id'
    )->execute([':location_id' => (int) $locations[2]['location_id'], ':asset_id' => $assetId]);

    $preservedReversal = ReportIntegration::reverseFinal($db, $preservedReportId, $actorId);
    $asset->execute([':asset_id' => $assetId]);
    $preservedState = $asset->fetch(PDO::FETCH_ASSOC);
    $movement->execute([':movement_id' => (int) $preservedResult['movementId']]);
    bastAssert(
        $preservedReversal['assetMovementPreservedCount'] === 1
        && (int) $preservedState['current_location_id'] === (int) $locations[2]['location_id']
        && abs((float) $preservedState['last_hm_km'] - 120.0) < 0.005,
        'void preserves the current asset state after a later movement'
    );
    bastAssert((bool) $movement->fetch(PDO::FETCH_ASSOC), 'void preserves BAST movement history when downstream activity exists');

    echo "\nBatch 14 BAST MDE-1 integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
