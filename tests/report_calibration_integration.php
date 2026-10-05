<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function calibrationAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

function calibrationUuid(): string
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
$people = $db->query('SELECT user_id, full_name FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 2')
    ->fetchAll(PDO::FETCH_ASSOC);
$location = $db->query('SELECT location_id, location_name FROM locations WHERE is_active = 1 ORDER BY location_id LIMIT 1')
    ->fetch(PDO::FETCH_ASSOC);
if ($people === [] || !$location) {
    echo "SKIP: Batch 16 test requires active users and a location.\n";
    exit(0);
}

$actorId = (int) $people[0]['user_id'];
$preparedName = (string) $people[0]['full_name'];
$checkedName = (string) ($people[1]['full_name'] ?? $people[0]['full_name']);
$suffix = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$templateKey = 'qa-calibration-' . strtolower($suffix);
$validReportId = calibrationUuid();
$invalidLocationReportId = calibrationUuid();
$invalidPersonReportId = calibrationUuid();
$duplicateReportId = calibrationUuid();
$invalidResultReportId = calibrationUuid();

$db->beginTransaction();
try {
    $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'LPK', 'QA Calibration', 1, '{}', 1)"
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
        $number = "QA-LPK-{$label}-{$suffix}";
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-lpk-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => $number,
            ':created_by' => $actorId,
            ':final_number_key' => $templateKey . '|' . strtolower($number),
        ]);
    };

    $fields = [
        'nomor_laporan' => 'QA-LPK-' . $suffix,
        'periode' => '2026-10',
        'lokasi' => (string) $location['location_name'],
        'tanggal' => '2026-10-05',
        'dibuat_oleh' => $preparedName,
        'diperiksa_oleh' => $checkedName,
    ];
    $rows = [
        [
            'nama' => 'Torque Wrench QA',
            'identifikasi' => 'TW-' . $suffix,
            'merk' => 'QA Tools 200 Nm',
            'rencana' => '2026-10-01',
            'pelaksanaan' => '2026-10-02',
            'jenis' => 'Intern',
            'hasil' => 'Memenuhi',
            'tindak_lanjut' => 'Selesai',
            'keterangan' => 'Deviasi dalam toleransi',
        ],
        [
            'nama' => 'Pressure Gauge QA',
            'identifikasi' => 'PG-' . $suffix,
            'merk' => 'QA Gauge 300 PSI',
            'rencana' => '2026-10-03',
            'pelaksanaan' => '2026-10-04',
            'jenis' => 'Ekstern',
            'hasil' => 'Tidak memenuhi',
            'tindak_lanjut' => 'Kalibrasi ulang',
            'keterangan' => 'Deviasi melebihi toleransi',
        ],
    ];

    $createReport($validReportId, 'VALID');
    $result = ReportIntegration::applyFinal($db, 'kalibrasi', $validReportId, $fields, $rows, $actorId);
    calibrationAssert($result['applied'] === true && $result['itemCount'] === 2, 'final LPK creates two calibration records');

    $records = $db->prepare('SELECT * FROM calibration_records WHERE report_id = :report_id ORDER BY report_item_position');
    $records->execute([':report_id' => $validReportId]);
    $stored = $records->fetchAll(PDO::FETCH_ASSOC);
    calibrationAssert(count($stored) === 2, 'register stores every populated LPK row');
    calibrationAssert(
        (int) $stored[0]['location_id'] === (int) $location['location_id']
        && (int) $stored[0]['prepared_by'] === $actorId,
        'LPK maps Master Location and selected personnel'
    );
    calibrationAssert(
        $stored[0]['calibration_result'] === 'Memenuhi'
        && $stored[1]['follow_up_status'] === 'Kalibrasi ulang',
        'calibration result and follow-up are retained'
    );

    $retry = ReportIntegration::applyFinal($db, 'kalibrasi', $validReportId, $fields, $rows, $actorId);
    calibrationAssert(!empty($retry['alreadyApplied']), 'LPK retry is idempotent');
    $records->execute([':report_id' => $validReportId]);
    calibrationAssert(count($records->fetchAll(PDO::FETCH_ASSOC)) === 2, 'LPK retry does not duplicate the register');

    $reversal = ReportIntegration::reverseFinal($db, $validReportId, $actorId);
    calibrationAssert($reversal['calibrationItemCount'] === 2, 'void reverses every LPK register row');
    $active = $db->prepare('SELECT COUNT(*) FROM calibration_records WHERE report_id = :report_id AND reversed_at IS NULL');
    $active->execute([':report_id' => $validReportId]);
    calibrationAssert((int) $active->fetchColumn() === 0, 'void hides LPK rows from the active register');
    calibrationAssert(ReportIntegration::reverseFinal($db, $validReportId, $actorId)['applied'] === false, 'repeated LPK void is idempotent');

    $createReport($invalidLocationReportId, 'LOCATION');
    try {
        ReportIntegration::applyFinal($db, 'kalibrasi', $invalidLocationReportId, [...$fields, 'lokasi' => 'LOKASI-TIDAK-ADA-' . $suffix], $rows, $actorId);
        calibrationAssert(false, 'unknown calibration location must be rejected');
    } catch (DomainException $error) {
        calibrationAssert(str_contains($error->getMessage(), 'Master Lokasi'), 'unknown calibration location is rejected');
    }

    $createReport($invalidPersonReportId, 'PERSON');
    try {
        ReportIntegration::applyFinal($db, 'kalibrasi', $invalidPersonReportId, [...$fields, 'diperiksa_oleh' => 'PERSONEL-TIDAK-ADA-' . $suffix], $rows, $actorId);
        calibrationAssert(false, 'unknown calibration checker must be rejected');
    } catch (DomainException $error) {
        calibrationAssert(str_contains($error->getMessage(), 'Master Personel'), 'unknown calibration checker is rejected');
    }

    $createReport($duplicateReportId, 'DUPLICATE');
    $duplicateRows = [$rows[0], [...$rows[1], 'identifikasi' => $rows[0]['identifikasi']]];
    try {
        ReportIntegration::applyFinal($db, 'kalibrasi', $duplicateReportId, $fields, $duplicateRows, $actorId);
        calibrationAssert(false, 'duplicate instrument identification must be rejected');
    } catch (DomainException $error) {
        calibrationAssert(str_contains($error->getMessage(), 'lebih dari sekali'), 'duplicate instrument identification is rejected');
    }

    $createReport($invalidResultReportId, 'RESULT');
    try {
        ReportIntegration::applyFinal(
            $db,
            'kalibrasi',
            $invalidResultReportId,
            $fields,
            [[...$rows[0], 'hasil' => 'Belum diketahui']],
            $actorId
        );
        calibrationAssert(false, 'unknown calibration result must be rejected');
    } catch (DomainException $error) {
        calibrationAssert(str_contains($error->getMessage(), 'tidak dikenali'), 'unknown calibration result is rejected');
    }

    echo "\nBatch 16 calibration integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
