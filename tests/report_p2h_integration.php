<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function p2hAssert(bool $condition, string $message): void
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
    "SELECT asset_id, status, last_hm_km
     FROM assets
     WHERE category = 'Excavator' AND status NOT IN ('ACCIDENT_HOLD', 'INACTIVE')
     ORDER BY asset_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    echo "SKIP: no eligible Excavator asset is available.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$templateKey = 'qa-p2h-' . strtolower($suffix);
$reportId = '22222222-2222-4222-8222-' . $suffix;
$failReportId = '33333333-3333-4333-8333-' . $suffix;
$startingHm = (float) $asset['last_hm_km'];
$hmStart = $startingHm + 1;
$hmEnd = $startingHm + 5;

$db->beginTransaction();
try {
    $template = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'P2H-HEX', 'QA P2H Excavator', 1, '{}', 1)"
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
    $insertReport->execute([
        ':report_id' => $reportId,
        ':template_id' => $templateId,
        ':client_key' => 'qa-p2h-client-' . strtolower($suffix),
        ':report_number' => 'QA-P2H-WARN-' . $suffix,
        ':final_number_key' => $templateKey . '|warn-' . strtolower($suffix),
    ]);

    $fields = [
        'bulan' => date('Y-m'),
        'model' => 'PC 200-8 MO',
        'operator' => 'QA Inspector',
        'nrp' => 'QA-001',
        'code_number' => $asset['asset_id'],
        'job_site' => 'QA Laragon',
        'tanggal_slot' => date('Y-m-d'),
        'hm_sebelum' => (string) $hmStart,
        'hm_selesai' => (string) $hmEnd,
    ];
    $warningRows = [
        [
            'kelompok' => 'Sebelum pemanasan',
            'item' => 'Level oli engine',
            'kondisi' => 'V — Normal',
            'tambahan' => '0',
            'tindakan' => '',
        ],
        [
            'kelompok' => 'Setelah pemanasan',
            'item' => 'Baut pelindung',
            'kondisi' => 'OK — Sudah diperbaiki',
            'tambahan' => '0',
            'tindakan' => 'Dikencangkan saat pemeriksaan',
        ],
    ];

    $result = ReportIntegration::applyFinal($db, 'p2h-excavator', $reportId, $fields, $warningRows, 1);
    p2hAssert($result['applied'] === true && $result['result'] === 'WARNING', 'P2H warning integration is applied');
    p2hAssert($result['itemCount'] === 1 && (int) $result['inspectionId'] > 0, 'inspection linkage is returned');

    $inspection = $db->prepare(
        'SELECT overall_result, payload_json FROM inspections WHERE inspection_id = :inspection_id'
    );
    $inspection->execute([':inspection_id' => $result['inspectionId']]);
    $inspectionRow = $inspection->fetch(PDO::FETCH_ASSOC);
    $payload = json_decode((string) $inspectionRow['payload_json'], true, 512, JSON_THROW_ON_ERROR);
    p2hAssert($inspectionRow['overall_result'] === 'WARNING', 'inspection row stores WARNING result');
    p2hAssert(
        $payload['id'] === 'QA-P2H-WARN-' . $suffix && $payload['source'] === 'Laporan & Form',
        'P2H history payload references the finalized report'
    );

    $assetState = $db->prepare('SELECT status, last_hm_km FROM assets WHERE asset_id = :asset_id');
    $assetState->execute([':asset_id' => $asset['asset_id']]);
    $updatedAsset = $assetState->fetch(PDO::FETCH_ASSOC);
    p2hAssert($updatedAsset['status'] === 'INSPEKSI', 'warning changes asset status to INSPEKSI');
    p2hAssert(abs((float) $updatedAsset['last_hm_km'] - $hmEnd) < 0.005, 'P2H updates the asset hour meter');

    $duplicate = ReportIntegration::applyFinal($db, 'p2h-excavator', $reportId, $fields, $warningRows, 1);
    p2hAssert(!empty($duplicate['alreadyApplied']), 'P2H retry is idempotent');

    $reversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    $assetState->execute([':asset_id' => $asset['asset_id']]);
    $restoredAsset = $assetState->fetch(PDO::FETCH_ASSOC);
    p2hAssert($reversal['inspectionItemCount'] === 1, 'void deactivates the integrated inspection');
    p2hAssert(
        $restoredAsset['status'] === $asset['status']
        && abs((float) $restoredAsset['last_hm_km'] - $startingHm) < 0.005,
        'void restores asset status and hour meter'
    );

    $secondReversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    p2hAssert($secondReversal['applied'] === false, 'repeated P2H void is idempotent');

    try {
        ReportIntegration::applyFinal(
            $db,
            'p2h-excavator',
            '44444444-4444-4444-8444-' . $suffix,
            [...$fields, 'code_number' => 'QA-ASSET-NOT-FOUND'],
            $warningRows,
            1
        );
        p2hAssert(false, 'unknown asset must be rejected');
    } catch (DomainException $error) {
        p2hAssert(str_contains($error->getMessage(), 'Master Asset'), 'unknown asset is rejected');
    }

    try {
        ReportIntegration::applyFinal(
            $db,
            'p2h-excavator',
            '55555555-5555-4555-8555-' . $suffix,
            [...$fields, 'hm_sebelum' => (string) ($hmEnd + 1), 'hm_selesai' => (string) $hmEnd],
            $warningRows,
            1
        );
        p2hAssert(false, 'decreasing hour meter must be rejected');
    } catch (DomainException $error) {
        p2hAssert(str_contains($error->getMessage(), 'tidak boleh lebih kecil'), 'decreasing hour meter is rejected');
    }

    $insertReport->execute([
        ':report_id' => $failReportId,
        ':template_id' => $templateId,
        ':client_key' => 'qa-p2h-fail-' . strtolower($suffix),
        ':report_number' => 'QA-P2H-FAIL-' . $suffix,
        ':final_number_key' => $templateKey . '|fail-' . strtolower($suffix),
    ]);
    $failRows = [[
        'kelompok' => 'Sebelum pemanasan',
        'item' => 'Kebocoran hydraulic',
        'kondisi' => 'X — Tidak normal',
        'tambahan' => '0',
        'tindakan' => 'Unit ditahan untuk pemeriksaan',
    ]];
    $failResult = ReportIntegration::applyFinal($db, 'p2h-excavator', $failReportId, $fields, $failRows, 1);
    $assetState->execute([':asset_id' => $asset['asset_id']]);
    p2hAssert($failResult['result'] === 'FAIL', 'abnormal P2H row produces FAIL result');
    p2hAssert($assetState->fetch(PDO::FETCH_ASSOC)['status'] === 'BREAKDOWN', 'failed P2H changes asset status to BREAKDOWN');

    ReportIntegration::reverseFinal($db, $failReportId, 1);
    echo "\nP2H report integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
