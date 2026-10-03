<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function lhoAssert(bool $condition, string $message): void
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
    "SELECT asset_id, last_hm_km
     FROM assets
     WHERE status NOT IN ('ACCIDENT_HOLD', 'INACTIVE')
     ORDER BY asset_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    echo "SKIP: no eligible asset is available.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
$templateKey = 'qa-lho-' . strtolower($suffix);
$reportId = '66666666-6666-4666-8666-' . $suffix;
$startingHm = (float) $asset['last_hm_km'];
$rowOneEnd = $startingHm + 5.5;
$rowTwoEnd = $startingHm + 9.0;
$dateOne = new DateTimeImmutable(date('Y-m-01'));
$dateTwo = $dateOne->modify('+1 day');

$db->beginTransaction();
try {
    $template = $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'LHO', 'QA LHO', 1, '{}', 1)"
    );
    $template->execute([':template_key' => $templateKey]);
    $templateId = (int) $db->lastInsertId();

    $report = $db->prepare(
        "INSERT INTO report_records
         (report_id, template_id, client_key, report_number, status, source_method, field_data,
          created_by, finalized_at, final_number_key)
         VALUES (:report_id, :template_id, :client_key, :report_number, 'FINAL', 'test', '{}',
          1, NOW(), :final_number_key)"
    );
    $report->execute([
        ':report_id' => $reportId,
        ':template_id' => $templateId,
        ':client_key' => 'qa-lho-client-' . strtolower($suffix),
        ':report_number' => 'QA-LHO-' . $suffix,
        ':final_number_key' => $templateKey . '|qa-lho-' . strtolower($suffix),
    ]);

    $fields = [
        'periode' => $dateOne->format('Y-m'),
        'jenis_alat' => 'Excavator',
        'tipe_merk' => 'QA Unit',
        'lokasi' => 'QA Laragon',
        'operator' => 'QA Operator',
        'id_alat' => $asset['asset_id'],
    ];
    $rows = [
        [
            'tanggal' => $dateOne->format('Y-m-d'),
            'jam_awal' => '08:00',
            'jam_akhir' => '16:00',
            'jam_kerja' => '8',
            'hm_awal' => (string) $startingHm,
            'hm_akhir' => (string) $rowOneEnd,
            'hm_operasi' => '5.5',
            'site' => 'QA Site A',
            'bbm' => '55',
            'cuaca' => 'Cerah',
            'keterangan' => 'QA first shift',
            'status' => 'Terverifikasi',
        ],
        [
            'tanggal' => $dateTwo->format('Y-m-d'),
            'jam_awal' => '08:00',
            'jam_akhir' => '12:00',
            'jam_kerja' => '4',
            'hm_awal' => (string) $rowOneEnd,
            'hm_akhir' => (string) $rowTwoEnd,
            'hm_operasi' => '3.5',
            'site' => 'QA Site A',
            'bbm' => '30',
            'cuaca' => 'Berawan',
            'keterangan' => 'QA second shift',
            'status' => 'Terverifikasi',
        ],
    ];

    $result = ReportIntegration::applyFinal($db, 'lho', $reportId, $fields, $rows, 1);
    lhoAssert($result['applied'] === true && $result['itemCount'] === 2, 'LHO integration applies two operation rows');
    lhoAssert(
        abs((float) $result['totalWorkHours'] - 12.0) < 0.005
        && abs((float) $result['totalHmOperation'] - 9.0) < 0.005
        && abs((float) $result['totalFuelLiters'] - 85.0) < 0.005,
        'LHO integration totals are correct'
    );

    $logs = $db->prepare(
        'SELECT COUNT(*) AS row_count, SUM(work_hours) AS work_hours, SUM(hm_operation) AS hm_operation
         FROM report_operation_logs WHERE report_id = :report_id AND reversed_at IS NULL'
    );
    $logs->execute([':report_id' => $reportId]);
    $logSummary = $logs->fetch(PDO::FETCH_ASSOC);
    lhoAssert((int) $logSummary['row_count'] === 2, 'operation ledger receives both rows');
    lhoAssert(
        abs((float) $logSummary['work_hours'] - 12.0) < 0.005
        && abs((float) $logSummary['hm_operation'] - 9.0) < 0.005,
        'operation ledger stores calculated hours'
    );

    $assetHm = $db->prepare('SELECT last_hm_km FROM assets WHERE asset_id = :asset_id');
    $assetHm->execute([':asset_id' => $asset['asset_id']]);
    lhoAssert(abs((float) $assetHm->fetchColumn() - $rowTwoEnd) < 0.005, 'LHO updates Master Asset HM to the final row');

    $duplicate = ReportIntegration::applyFinal($db, 'lho', $reportId, $fields, $rows, 1);
    lhoAssert(!empty($duplicate['alreadyApplied']), 'LHO retry is idempotent');

    try {
        ReportIntegration::applyFinal(
            $db,
            'lho',
            '77777777-7777-4777-8777-' . $suffix,
            $fields,
            [[...$rows[0], 'hm_awal' => (string) ($startingHm + 1), 'hm_akhir' => (string) ($startingHm + 6.5)]],
            1
        );
        lhoAssert(false, 'stale Master Asset HM must be rejected');
    } catch (DomainException $error) {
        lhoAssert(str_contains($error->getMessage(), 'HM Master Asset'), 'stale Master Asset HM is rejected');
    }

    try {
        ReportIntegration::applyFinal(
            $db,
            'lho',
            '88888888-8888-4888-8888-' . $suffix,
            $fields,
            [[...$rows[0], 'hm_awal' => (string) $rowTwoEnd, 'hm_akhir' => (string) ($rowTwoEnd + 5.5), 'jam_kerja' => '7']],
            1
        );
        lhoAssert(false, 'incorrect work-hour calculation must be rejected');
    } catch (DomainException $error) {
        lhoAssert(str_contains($error->getMessage(), 'selisih jam awal dan akhir'), 'incorrect work-hour calculation is rejected');
    }

    $reversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    $assetHm->execute([':asset_id' => $asset['asset_id']]);
    lhoAssert($reversal['operationItemCount'] === 2, 'void deactivates both LHO rows');
    lhoAssert(abs((float) $assetHm->fetchColumn() - $startingHm) < 0.005, 'void restores Master Asset HM');

    $logs->execute([':report_id' => $reportId]);
    lhoAssert((int) $logs->fetch(PDO::FETCH_ASSOC)['row_count'] === 0, 'void removes LHO rows from the active ledger');

    $secondReversal = ReportIntegration::reverseFinal($db, $reportId, 1);
    lhoAssert($secondReversal['applied'] === false, 'repeated LHO void is idempotent');

    echo "\nLHO report integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
