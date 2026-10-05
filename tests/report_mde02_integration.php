<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function mde02Assert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

function mde02Uuid(): string
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
$people = $db->query(
    'SELECT user_id, full_name FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 2'
)->fetchAll(PDO::FETCH_ASSOC);
$locationId = (int) $db->query(
    'SELECT location_id FROM locations WHERE is_active = 1 ORDER BY location_id LIMIT 1'
)->fetchColumn();
if ($people === [] || $locationId < 1) {
    echo "SKIP: Batch 15 test requires an active user and location.\n";
    exit(0);
}
$actorId = (int) $people[0]['user_id'];
$inspectorName = (string) $people[0]['full_name'];
$approverName = (string) ($people[1]['full_name'] ?? $people[0]['full_name']);

$suffix = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$assetId = 'QA-MDE02-' . $suffix;
$templateKey = 'qa-mde02-' . strtolower($suffix);
$passReportId = mde02Uuid();
$decreasingHmReportId = mde02Uuid();
$unknownInspectorReportId = mde02Uuid();
$failReportId = mde02Uuid();

$db->beginTransaction();
try {
    $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'MDE-02', 'QA MDE-02', 1, '{}', 1)"
    )->execute([':template_key' => $templateKey]);
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
        $number = "QA-MDE02-{$label}-{$suffix}";
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-mde02-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => $number,
            ':created_by' => $actorId,
            ':final_number_key' => $templateKey . '|' . strtolower($number),
        ]);
    };

    $db->prepare(
        "INSERT INTO assets
         (asset_id, asset_code, serial_number, type, category, make_model, year_manufacture,
          status, current_location_id, last_hm_km, is_active)
         VALUES (:asset_id, :asset_code, :serial_number, 'Heavy Equipment', 'Dump Truck',
          'QA MDE Model', 2024, 'READY', :location_id, 100, 1)"
    )->execute([
        ':asset_id' => $assetId,
        ':asset_code' => $assetId,
        ':serial_number' => 'SN-' . $suffix,
        ':location_id' => $locationId,
    ]);

    $baseFields = [
        'project' => 'QA Project',
        'tanggal' => '2026-10-05',
        'nomor_urut' => 'QA-MDE02-' . $suffix,
        'kode_alat' => $assetId,
        'merek_alat' => 'QA MDE Model',
        'jenis_alat' => 'Dump Truck',
        'tipe_alat' => 'QA MDE Model',
        'tahun' => '2024',
        'nomor_seri' => 'SN-' . $suffix,
        'hm_om' => '110',
        'diperiksa_oleh' => $inspectorName,
        'disetujui_oleh' => $approverName,
    ];
    $passRows = [[
        'kelompok' => 'Engine Group',
        'item' => 'Kondisi engine dan kebocoran',
        'kondisi' => 'Baik',
        'keterangan' => 'Tidak ada kebocoran',
    ]];

    $createReport($passReportId, 'PASS');
    $result = ReportIntegration::applyFinal($db, 'mde-02', $passReportId, $baseFields, $passRows, $actorId);
    mde02Assert($result['applied'] === true && $result['result'] === 'PASS', 'final MDE-02 creates a passing inspection');

    $inspection = $db->prepare('SELECT * FROM inspections WHERE inspection_id = :inspection_id');
    $inspection->execute([':inspection_id' => (int) $result['inspectionId']]);
    $inspectionRow = $inspection->fetch(PDO::FETCH_ASSOC);
    $payload = json_decode((string) $inspectionRow['payload_json'], true);
    mde02Assert(
        $inspectionRow
        && (string) $inspectionRow['asset_id'] === $assetId
        && (int) $inspectionRow['inspector_id'] === $actorId
        && ($payload['schemaId'] ?? '') === 'mde-02',
        'inspection stores the selected asset, inspector, and MDE-02 payload'
    );

    $asset = $db->prepare('SELECT status, last_hm_km FROM assets WHERE asset_id = :asset_id');
    $asset->execute([':asset_id' => $assetId]);
    $assetState = $asset->fetch(PDO::FETCH_ASSOC);
    mde02Assert(
        (string) $assetState['status'] === 'READY'
        && abs((float) $assetState['last_hm_km'] - 110.0) < 0.005,
        'passing MDE-02 updates HM without degrading asset status'
    );

    $retry = ReportIntegration::applyFinal($db, 'mde-02', $passReportId, $baseFields, $passRows, $actorId);
    mde02Assert(!empty($retry['alreadyApplied']), 'MDE-02 retry is idempotent');
    $inspectionCount = $db->prepare(
        'SELECT COUNT(*) FROM report_inspection_integrations WHERE report_id = :report_id'
    );
    $inspectionCount->execute([':report_id' => $passReportId]);
    mde02Assert((int) $inspectionCount->fetchColumn() === 1, 'MDE-02 retry does not duplicate inspections');

    $reversal = ReportIntegration::reverseFinal($db, $passReportId, $actorId);
    $asset->execute([':asset_id' => $assetId]);
    $restored = $asset->fetch(PDO::FETCH_ASSOC);
    mde02Assert(
        $reversal['inspectionItemCount'] === 1
        && (string) $restored['status'] === 'READY'
        && abs((float) $restored['last_hm_km'] - 100.0) < 0.005,
        'void restores status and HM when no later change exists'
    );
    mde02Assert(ReportIntegration::reverseFinal($db, $passReportId, $actorId)['applied'] === false, 'repeated MDE-02 void is idempotent');

    $createReport($decreasingHmReportId, 'HM');
    try {
        ReportIntegration::applyFinal(
            $db,
            'mde-02',
            $decreasingHmReportId,
            [...$baseFields, 'nomor_urut' => 'QA-MDE02-HM-' . $suffix, 'hm_om' => '99'],
            $passRows,
            $actorId
        );
        mde02Assert(false, 'decreasing MDE-02 HM must be rejected');
    } catch (DomainException $error) {
        mde02Assert(str_contains($error->getMessage(), 'tidak boleh lebih kecil'), 'decreasing MDE-02 HM is rejected');
    }

    $createReport($unknownInspectorReportId, 'INSPECTOR');
    try {
        ReportIntegration::applyFinal(
            $db,
            'mde-02',
            $unknownInspectorReportId,
            [...$baseFields, 'nomor_urut' => 'QA-MDE02-INSPECTOR-' . $suffix,
                'diperiksa_oleh' => 'PERSONEL-TIDAK-ADA-' . $suffix],
            $passRows,
            $actorId
        );
        mde02Assert(false, 'unknown inspector must be rejected');
    } catch (DomainException $error) {
        mde02Assert(str_contains($error->getMessage(), 'Master Personel'), 'unknown inspector is rejected');
    }

    $createReport($failReportId, 'FAIL');
    $failFields = [...$baseFields, 'nomor_urut' => 'QA-MDE02-FAIL-' . $suffix, 'hm_om' => '120'];
    $failRows = [[
        'kelompok' => 'Safety',
        'item' => 'Emergency stop dan alarm mundur',
        'kondisi' => 'Perlu perbaikan',
        'keterangan' => 'Alarm mundur tidak berfungsi',
    ]];
    $failResult = ReportIntegration::applyFinal($db, 'mde-02', $failReportId, $failFields, $failRows, $actorId);
    $asset->execute([':asset_id' => $assetId]);
    $failedState = $asset->fetch(PDO::FETCH_ASSOC);
    mde02Assert(
        $failResult['result'] === 'FAIL'
        && (string) $failedState['status'] === 'INSPEKSI'
        && abs((float) $failedState['last_hm_km'] - 120.0) < 0.005,
        'failed MDE-02 changes asset status to INSPEKSI and updates HM'
    );

    $db->prepare(
        "UPDATE assets SET status = 'OPERATING', last_hm_km = 130 WHERE asset_id = :asset_id"
    )->execute([':asset_id' => $assetId]);
    $preservedReversal = ReportIntegration::reverseFinal($db, $failReportId, $actorId);
    $asset->execute([':asset_id' => $assetId]);
    $preserved = $asset->fetch(PDO::FETCH_ASSOC);
    mde02Assert(
        $preservedReversal['inspectionItemCount'] === 1
        && (string) $preserved['status'] === 'OPERATING'
        && abs((float) $preserved['last_hm_km'] - 130.0) < 0.005,
        'void preserves later asset status and HM changes'
    );

    $activeLink = $db->prepare(
        'SELECT reversed_at FROM report_inspection_integrations WHERE report_id = :report_id'
    );
    $activeLink->execute([':report_id' => $failReportId]);
    mde02Assert($activeLink->fetchColumn() !== null, 'void marks the MDE-02 inspection link as reversed');

    echo "\nBatch 15 MDE-02 integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
