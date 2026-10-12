<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function cuttingAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

function cuttingUuid(): string
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
$asset = $db->query(
    "SELECT asset_id, category, make_model
     FROM assets
     WHERE asset_id REGEXP '^(RM|WR)-' OR category REGEXP 'Reclaimer|Milling'
     ORDER BY asset_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
$location = $db->query(
    'SELECT location_id, location_name FROM locations WHERE is_active = 1 ORDER BY location_id LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);
$people = $db->query(
    'SELECT user_id, full_name FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 3'
)->fetchAll(PDO::FETCH_ASSOC);
if (!$asset || !$location || count($people) < 3) {
    echo "SKIP: Batch 19 test requires a recycler/milling asset, one active location, and three active users.\n";
    exit(0);
}

$actorId = (int) $people[0]['user_id'];
$suffix = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$templateKey = 'qa-cutting-' . strtolower($suffix);

$db->beginTransaction();
try {
    $db->prepare(
        "INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
         VALUES (:template_key, 'CB-QA', 'QA Cutting Bit', 1, '{}', 1)"
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
        $id = cuttingUuid();
        $number = "QA-CB-{$label}-{$suffix}";
        $insertReport->execute([
            ':report_id' => $id,
            ':template_id' => $templateId,
            ':client_key' => 'qa-cb-' . strtolower($label) . '-' . strtolower($suffix),
            ':report_number' => $number,
            ':created_by' => $actorId,
            ':final_number_key' => $templateKey . '|' . strtolower($number),
        ]);
        return $id;
    };

    $fields = [
        'mesin_unit' => trim((string) $asset['category'] . ' ' . (string) $asset['make_model']),
        'kode_unit' => (string) $asset['asset_id'],
        'material' => 'Laterit/Tanah Padat',
        'lokasi' => (string) $location['location_name'],
        'periode_pencatatan' => 'Oktober 2026',
        'tahun' => '2026',
        'kebutuhan_harian' => '30',
        'target_harian_m' => '250',
        'hari_kerja' => '10',
        'target_total_m' => '2500',
        'kebutuhan_dasar' => '300',
        'safety_stock_persen' => '10',
        'safety_stock_minimum' => '30',
        'total_kebutuhan' => '330',
        'sumber_dokumen' => 'QA Cutting Bit Batch 19',
        'dibuat_oleh' => (string) $people[0]['full_name'],
        'diperiksa_oleh' => (string) $people[1]['full_name'],
        'disetujui_oleh' => (string) $people[2]['full_name'],
    ];
    $rows = [
        [
            'bulan' => 'Oktober', 'tanggal' => '4', 'planning' => '30', 'actual' => '32',
            'selisih_pcs' => '2', 'realisasi_persen' => '106.67', 'catatan' => 'Pemakaian QA',
        ],
        [
            'bulan' => 'Oktober', 'tanggal' => '5', 'planning' => '0', 'actual' => '5',
            'selisih_pcs' => '5', 'realisasi_persen' => '', 'catatan' => 'Actual tanpa planning',
        ],
    ];

    $validId = $createReport('VALID');
    $result = ReportIntegration::applyFinal($db, 'cutting-bit-usage', $validId, $fields, $rows, $actorId);
    cuttingAssert($result['applied'] === true && $result['itemCount'] === 2, 'final report creates every daily cutting-bit row');
    cuttingAssert($result['totalPlanning'] === 30 && $result['totalActual'] === 37, 'final result returns correct planning and actual totals');

    $records = $db->prepare('SELECT * FROM cutting_bit_logs WHERE report_id = :report_id ORDER BY report_item_position');
    $records->execute([':report_id' => $validId]);
    $stored = $records->fetchAll(PDO::FETCH_ASSOC);
    cuttingAssert(count($stored) === 2, 'cutting-bit register stores two report-linked records');
    cuttingAssert(
        $stored[0]['asset_id'] === $asset['asset_id']
        && (int) $stored[0]['location_id'] === (int) $location['location_id']
        && $stored[0]['activity_date'] === '2026-10-04',
        'asset, location, and Indonesian month/day map to database masters and ISO date'
    );
    cuttingAssert(
        (int) $stored[0]['planning_qty'] === 30
        && (int) $stored[0]['actual_qty'] === 32
        && (int) $stored[0]['variance_qty'] === 2
        && abs((float) $stored[0]['realization_percent'] - 106.67) < 0.005,
        'backend-calculated daily quantities are retained'
    );
    cuttingAssert(
        (int) $stored[0]['prepared_by'] === (int) $people[0]['user_id']
        && (int) $stored[0]['checked_by'] === (int) $people[1]['user_id']
        && (int) $stored[0]['approved_by'] === (int) $people[2]['user_id'],
        'approval names resolve to Master Personnel IDs'
    );
    cuttingAssert($stored[1]['realization_percent'] === null, 'zero planning keeps realization null without losing actual anomaly');

    $retry = ReportIntegration::applyFinal($db, 'cutting-bit-usage', $validId, $fields, $rows, $actorId);
    cuttingAssert(!empty($retry['alreadyApplied']), 'repeated finalization is idempotent');
    $records->execute([':report_id' => $validId]);
    cuttingAssert(count($records->fetchAll(PDO::FETCH_ASSOC)) === 2, 'idempotent retry does not duplicate daily rows');

    $reversal = ReportIntegration::reverseFinal($db, $validId, $actorId);
    cuttingAssert($reversal['cuttingBitItemCount'] === 2, 'void reverses all cutting-bit rows');
    $active = $db->prepare('SELECT COUNT(*) FROM cutting_bit_logs WHERE report_id = :report_id AND reversed_at IS NULL');
    $active->execute([':report_id' => $validId]);
    cuttingAssert((int) $active->fetchColumn() === 0, 'void hides report rows from active Condition Monitoring');
    cuttingAssert(ReportIntegration::reverseFinal($db, $validId, $actorId)['applied'] === false, 'repeated void is idempotent');

    $badCalculationId = $createReport('CALC');
    try {
        ReportIntegration::applyFinal($db, 'cutting-bit-usage', $badCalculationId, [...$fields, 'total_kebutuhan' => '999'], $rows, $actorId);
        cuttingAssert(false, 'tampered header total must be rejected');
    } catch (DomainException $error) {
        cuttingAssert(str_contains($error->getMessage(), 'perhitungan backend'), 'tampered header total is rejected');
    }

    $badDateId = $createReport('DATE');
    try {
        ReportIntegration::applyFinal($db, 'cutting-bit-usage', $badDateId, $fields, [[...$rows[0], 'tanggal' => '32']], $actorId);
        cuttingAssert(false, 'invalid calendar date must be rejected');
    } catch (DomainException $error) {
        cuttingAssert(str_contains($error->getMessage(), 'tidak valid'), 'invalid calendar date is rejected');
    }

    $badPersonId = $createReport('PERSON');
    try {
        ReportIntegration::applyFinal($db, 'cutting-bit-usage', $badPersonId, [...$fields, 'diperiksa_oleh' => 'QA-NOT-FOUND-' . $suffix], $rows, $actorId);
        cuttingAssert(false, 'unknown approver must be rejected');
    } catch (DomainException $error) {
        cuttingAssert(str_contains($error->getMessage(), 'Master Personel'), 'unknown approver is rejected');
    }

    echo "\nBatch 19 Cutting Bit integration tests: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
