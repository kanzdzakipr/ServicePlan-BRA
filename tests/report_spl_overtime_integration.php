<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function splAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

function splUuid(): string
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
    'SELECT u.user_id, u.full_name, r.role_name
     FROM users u INNER JOIN roles r ON r.role_id = u.role_id
     WHERE u.is_active = 1 ORDER BY u.user_id LIMIT 2'
)->fetchAll(PDO::FETCH_ASSOC);
$location = $db->query(
    'SELECT location_id, location_name FROM locations WHERE is_active = 1 ORDER BY location_id LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);
if (count($people) < 2 || !$location) {
    echo "SKIP: Batch 18 test requires two active users with roles and one location.\n";
    exit(0);
}

$actorId = (int) $people[0]['user_id'];
$suffix = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$templateKey = 'qa-spl-' . strtolower($suffix);

$db->beginTransaction();
try {
    $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'SPL', 'QA Overtime', 1, '{}', 1)"
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
        $id = splUuid();
        $number = "QA-SPL-{$label}-{$suffix}";
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-spl-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => $number,
            ':created_by' => $actorId,
            ':final_number_key' => $templateKey . '|' . strtolower($number),
        ]);
        return $id;
    };

    $fields = [
        'nomor_spl' => 'SPL-QA-' . $suffix,
        'tanggal_spl' => '2026-10-12',
        'mulai_periode' => '2026-10-12',
        'akhir_periode' => '2026-10-12',
        'lokasi' => (string) $location['location_name'],
        'target' => 'Pekerjaan selesai sebelum shift pagi',
        'uraian' => 'Percepatan penyelesaian pekerjaan workshop',
        'dibuat_oleh' => (string) $people[0]['full_name'],
        'instruksi_oleh' => (string) $people[1]['full_name'],
    ];
    $rows = [
        [
            'nama' => (string) $people[0]['full_name'],
            'jabatan' => (string) $people[0]['role_name'],
            'lokasi' => (string) $location['location_name'],
            'uraian' => 'Finishing pekerjaan unit QA pertama',
            'mulai' => '16:00', 'verifikasi_mulai' => 'Terverifikasi',
            'selesai' => '18:30', 'verifikasi_selesai' => 'Terverifikasi',
            'durasi' => '2.5',
        ],
        [
            'nama' => (string) $people[1]['full_name'],
            'jabatan' => (string) $people[1]['role_name'],
            'lokasi' => (string) $location['location_name'],
            'uraian' => 'Pemeriksaan akhir pekerjaan QA',
            'mulai' => '22:00', 'verifikasi_mulai' => 'Belum diverifikasi',
            'selesai' => '01:00', 'verifikasi_selesai' => 'Belum diverifikasi',
            'durasi' => '3',
        ],
    ];

    $validId = $createReport('VALID');
    $result = ReportIntegration::applyFinal($db, 'spl', $validId, $fields, $rows, $actorId);
    splAssert($result['applied'] === true && $result['itemCount'] === 2, 'final SPL creates two overtime records');
    splAssert(abs((float) $result['totalHours'] - 5.5) < 0.005, 'final SPL returns the correct person-hour total');

    $records = $db->prepare('SELECT * FROM spl_overtime_records WHERE report_id = :report_id ORDER BY report_item_position');
    $records->execute([':report_id' => $validId]);
    $stored = $records->fetchAll(PDO::FETCH_ASSOC);
    splAssert(count($stored) === 2, 'overtime register stores every selected person');
    splAssert(
        (int) $stored[0]['employee_user_id'] === (int) $people[0]['user_id']
        && (int) $stored[0]['location_id'] === (int) $location['location_id']
        && (int) $stored[0]['prepared_by'] === (int) $people[0]['user_id']
        && (int) $stored[0]['instructed_by'] === (int) $people[1]['user_id'],
        'SPL maps personnel, location, creator, and instructor masters'
    );
    splAssert(
        abs((float) $stored[0]['duration_hours'] - 2.5) < 0.005
        && abs((float) $stored[1]['duration_hours'] - 3.0) < 0.005,
        'backend calculates same-day and overnight duration correctly'
    );
    splAssert((int) $stored[0]['start_verified'] === 1 && (int) $stored[1]['start_verified'] === 0, 'time verification state is retained');

    $retry = ReportIntegration::applyFinal($db, 'spl', $validId, $fields, $rows, $actorId);
    splAssert(!empty($retry['alreadyApplied']), 'SPL retry is idempotent');
    $records->execute([':report_id' => $validId]);
    splAssert(count($records->fetchAll(PDO::FETCH_ASSOC)) === 2, 'SPL retry does not duplicate overtime rows');

    $reversal = ReportIntegration::reverseFinal($db, $validId, $actorId);
    splAssert($reversal['splOvertimeItemCount'] === 2, 'void reverses every SPL overtime row');
    $active = $db->prepare('SELECT COUNT(*) FROM spl_overtime_records WHERE report_id = :report_id AND reversed_at IS NULL');
    $active->execute([':report_id' => $validId]);
    splAssert((int) $active->fetchColumn() === 0, 'void hides SPL from active People & KPI register');
    splAssert(ReportIntegration::reverseFinal($db, $validId, $actorId)['applied'] === false, 'repeated SPL void is idempotent');

    $unknownPersonId = $createReport('PERSON');
    try {
        ReportIntegration::applyFinal($db, 'spl', $unknownPersonId, $fields, [[...$rows[0], 'nama' => 'PERSONEL-TIDAK-ADA-' . $suffix]], $actorId);
        splAssert(false, 'unknown personnel must be rejected');
    } catch (DomainException $error) {
        splAssert(str_contains($error->getMessage(), 'Master Personel'), 'unknown personnel is rejected');
    }

    $wrongRoleId = $createReport('ROLE');
    try {
        ReportIntegration::applyFinal($db, 'spl', $wrongRoleId, $fields, [[...$rows[0], 'jabatan' => 'Jabatan Salah']], $actorId);
        splAssert(false, 'mismatched role must be rejected');
    } catch (DomainException $error) {
        splAssert(str_contains($error->getMessage(), 'jabatan'), 'mismatched role is rejected');
    }

    $wrongDurationId = $createReport('DURATION');
    try {
        ReportIntegration::applyFinal($db, 'spl', $wrongDurationId, $fields, [[...$rows[0], 'durasi' => '7']], $actorId);
        splAssert(false, 'tampered duration must be rejected');
    } catch (DomainException $error) {
        splAssert(str_contains($error->getMessage(), 'durasi'), 'tampered duration is rejected');
    }

    $duplicateId = $createReport('DUPLICATE');
    try {
        ReportIntegration::applyFinal($db, 'spl', $duplicateId, $fields, [$rows[0], $rows[0]], $actorId);
        splAssert(false, 'duplicate personnel must be rejected');
    } catch (DomainException $error) {
        splAssert(str_contains($error->getMessage(), 'lebih dari sekali'), 'duplicate personnel is rejected');
    }

    $wrongPeriodId = $createReport('PERIOD');
    try {
        ReportIntegration::applyFinal($db, 'spl', $wrongPeriodId, [...$fields, 'akhir_periode' => '2026-10-11'], [$rows[0]], $actorId);
        splAssert(false, 'reversed overtime period must be rejected');
    } catch (DomainException $error) {
        splAssert(str_contains($error->getMessage(), 'sebelum tanggal mulai'), 'reversed overtime period is rejected');
    }

    echo "\nBatch 18 SPL overtime integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
