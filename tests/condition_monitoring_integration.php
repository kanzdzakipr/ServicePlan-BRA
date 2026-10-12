<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ConditionMonitoring.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function conditionAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

function conditionRejects(callable $callback, string $message): void
{
    try {
        $callback();
        conditionAssert(false, $message);
    } catch (DomainException) {
        conditionAssert(true, $message);
    }
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

ConditionMonitoring::ensureTables($db);
$assetId = (string) $db->query('SELECT asset_id FROM assets ORDER BY asset_id LIMIT 1')->fetchColumn();
$actorId = (int) $db->query('SELECT user_id FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 1')->fetchColumn();
if ($assetId === '' || $actorId <= 0) {
    echo "SKIP: Batch 20 test requires one asset and one active user.\n";
    exit(0);
}

$before = [
    'tire' => (int) $db->query('SELECT COUNT(*) FROM tire_inspections')->fetchColumn(),
    'grease' => (int) $db->query('SELECT COUNT(*) FROM grease_logs')->fetchColumn(),
    'battery' => (int) $db->query('SELECT COUNT(*) FROM battery_logs')->fetchColumn(),
];

$db->beginTransaction();
try {
    $tire = ConditionMonitoring::save($db, 'tire', [
        'asset_id' => $assetId,
        'position' => 'QA20',
        'serial_no' => 'QA-BATCH20',
        'tread_depth_mm' => 7.5,
        'air_pressure_psi' => 110,
        'physical_condition' => 'Baik',
        'wear_pattern' => 'Merata',
        'recommended_action' => 'Monitor',
        'notes' => 'Transactional Batch 20 test',
    ], $actorId);
    conditionAssert($tire['status'] === 'warning' && str_starts_with($tire['id'], 'CM-TIRE-'), 'tread 7.5 mm is saved as tire warning');

    $tireRow = $db->prepare('SELECT * FROM tire_inspections WHERE tire_inspection_id = :id');
    $tireRow->execute([':id' => $tire['recordId']]);
    $storedTire = $tireRow->fetch(PDO::FETCH_ASSOC);
    conditionAssert(
        $storedTire && $storedTire['asset_id'] === $assetId && $storedTire['recommended_action'] === 'Monitor',
        'tire record keeps asset and recommendation fields'
    );
    conditionAssert((int) $storedTire['inspected_by'] === $actorId, 'tire record keeps authenticated inspector ID');

    $grease = ConditionMonitoring::save($db, 'grease', [
        'asset_id' => $assetId,
        'current_hm' => 1095,
        'last_grease_hm' => 1000,
        'interval_hm' => 100,
        'quantity_kg' => 4.5,
        'grease_type' => 'Lithium EP-2',
        'points' => ['Pin & bushing' => true, 'Steering linkage' => false],
        'notes' => 'QA grease',
    ], $actorId);
    conditionAssert($grease['status'] === 'warning' && str_starts_with($grease['id'], 'CM-GREASE-'), '95 percent grease interval is saved as warning');

    $greaseRow = $db->prepare('SELECT * FROM grease_logs WHERE grease_log_id = :id');
    $greaseRow->execute([':id' => $grease['recordId']]);
    $storedGrease = $greaseRow->fetch(PDO::FETCH_ASSOC);
    $points = json_decode((string) $storedGrease['lubrication_points'], true);
    conditionAssert($points['Pin & bushing'] === true && $points['Steering linkage'] === false, 'grease lubrication checklist is stored as JSON');

    $battery = ConditionMonitoring::save($db, 'battery', [
        'asset_id' => $assetId,
        'voltage' => 11.8,
        'cca_percent' => 55,
        'brand' => 'GS Astra',
        'battery_type' => 'N100 12V',
        'installation_date' => '2026-01-10',
        'terminal_condition' => 'Korosi ringan',
        'electrolyte_level' => 'Rendah',
        'case_condition' => 'Baik',
        'notes' => 'QA battery',
    ], $actorId);
    conditionAssert($battery['status'] === 'danger' && str_starts_with($battery['id'], 'CM-BATTERY-'), 'low voltage and CCA are saved as battery danger');

    $batteryRow = $db->prepare('SELECT * FROM battery_logs WHERE battery_log_id = :id');
    $batteryRow->execute([':id' => $battery['recordId']]);
    $storedBattery = $batteryRow->fetch(PDO::FETCH_ASSOC);
    conditionAssert($storedBattery['water_level'] === 'LOW' && $storedBattery['battery_type'] === 'N100 12V', 'battery record maps electrolyte and type fields');

    conditionRejects(fn() => ConditionMonitoring::save($db, 'tire', [
        'asset_id' => $assetId,
        'position' => 'QA21',
        'air_pressure_psi' => 100,
        'physical_condition' => 'Baik',
        'wear_pattern' => 'Merata',
        'recommended_action' => 'Monitor',
    ], $actorId), 'healthy tire without tread depth is rejected');

    conditionRejects(fn() => ConditionMonitoring::save($db, 'grease', [
        'asset_id' => $assetId,
        'current_hm' => 900,
        'last_grease_hm' => 1000,
        'interval_hm' => 100,
        'quantity_kg' => 1,
        'grease_type' => 'Lithium EP-2',
        'points' => ['Pin' => true],
    ], $actorId), 'grease current HM below previous HM is rejected');

    conditionRejects(fn() => ConditionMonitoring::save($db, 'battery', [
        'asset_id' => $assetId,
        'voltage' => 12.7,
        'cca_percent' => 101,
        'brand' => 'GS Astra',
        'battery_type' => 'N100 12V',
        'installation_date' => '2026-01-10',
        'terminal_condition' => 'Bersih & kencang',
        'electrolyte_level' => 'Normal',
        'case_condition' => 'Baik',
    ], $actorId), 'battery CCA above 100 percent is rejected');

    conditionRejects(fn() => ConditionMonitoring::save($db, 'battery', [
        'asset_id' => $assetId,
        'voltage' => 12.7,
        'cca_percent' => 90,
        'brand' => 'GS Astra',
        'battery_type' => 'N100 12V',
        'installation_date' => date('Y-m-d', strtotime('+1 day')),
        'terminal_condition' => 'Bersih & kencang',
        'electrolyte_level' => 'Normal',
        'case_condition' => 'Baik',
    ], $actorId), 'future battery installation date is rejected');

    conditionRejects(fn() => ConditionMonitoring::save($db, 'unknown', ['asset_id' => $assetId], $actorId), 'unknown monitoring domain is rejected');
    conditionRejects(fn() => ConditionMonitoring::save($db, 'tire', ['asset_id' => 'QA-ASSET-NOT-FOUND'], $actorId), 'unknown Master Asset is rejected');

    conditionAssert((int) $db->query('SELECT COUNT(*) FROM tire_inspections')->fetchColumn() === $before['tire'] + 1, 'transaction contains exactly one new tire row');
    conditionAssert((int) $db->query('SELECT COUNT(*) FROM grease_logs')->fetchColumn() === $before['grease'] + 1, 'transaction contains exactly one new grease row');
    conditionAssert((int) $db->query('SELECT COUNT(*) FROM battery_logs')->fetchColumn() === $before['battery'] + 1, 'transaction contains exactly one new battery row');

} finally {
    if ($db->inTransaction()) $db->rollBack();
}

conditionAssert((int) $db->query('SELECT COUNT(*) FROM tire_inspections')->fetchColumn() === $before['tire'], 'test rollback preserves existing tire data');
conditionAssert((int) $db->query('SELECT COUNT(*) FROM grease_logs')->fetchColumn() === $before['grease'], 'test rollback preserves existing grease data');
conditionAssert((int) $db->query('SELECT COUNT(*) FROM battery_logs')->fetchColumn() === $before['battery'], 'test rollback preserves existing battery data');
echo "\nBatch 20 Condition Monitoring integration tests: {$passed} passed.\n";
