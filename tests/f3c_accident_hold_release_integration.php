<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';
require_once dirname(__DIR__) . '/core/AccidentWorkflow.php';

$db = Database::getInstance();
$passed = 0;
function f3cAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

$userId = (int) $db->query('SELECT user_id FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 1')->fetchColumn();
$locationId = (int) $db->query('SELECT location_id FROM locations WHERE is_active = 1 ORDER BY location_id LIMIT 1')->fetchColumn();
if ($userId < 1 || $locationId < 1) {
    echo "SKIP: active user/location unavailable.\n";
    exit(0);
}

$suffix = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
$assetId = 'QA-F3C-' . $suffix;
$base = [
    'reportDate' => date('Y-m-d'),
    'incidentDate' => date('Y-m-d H:i:s'),
    'unitCode' => $assetId,
    'operatorName' => 'QA Safety',
    'chronology' => 'QA controlled HSE workflow test',
    'environment' => ['weather' => 'Cerah', 'roadCondition' => 'Kering', 'lighting' => 'Terang'],
    'impact' => ['physicalDamage' => 'QA only', 'estimatedRepairCost' => 0, 'estimatedDowntimeDays' => 0, 'totalFinancialImpact' => 0],
    'causeFactors' => ['QA'],
    'correctiveAction' => 'QA correction',
    'preventiveAction' => 'QA prevention',
];

$db->beginTransaction();
try {
    $db->prepare(
        "INSERT INTO assets (asset_id, asset_code, category, status, current_location_id, is_active)
         VALUES (:id, :code, 'Other', 'OPERATING', :location, 1)"
    )->execute([':id' => $assetId, ':code' => $assetId, ':location' => $locationId]);

    $minor = AccidentWorkflow::record($db, [...$base, 'docNo' => 'QA-MIN-' . $suffix, 'severity' => 'Minor'], $userId);
    $status = $db->query("SELECT status FROM assets WHERE asset_id = " . $db->quote($assetId))->fetchColumn();
    f3cAssert($minor['locked'] === false && $status === 'OPERATING', 'Minor incident does not hold the asset');

    $firstId = 'QA-MOD-' . $suffix;
    $first = AccidentWorkflow::record($db, [...$base, 'docNo' => $firstId, 'severity' => 'Moderate'], $userId);
    $row = $db->query("SELECT status FROM assets WHERE asset_id = " . $db->quote($assetId))->fetchColumn();
    f3cAssert($first['locked'] === true && $row === 'ACCIDENT_HOLD', 'Moderate incident persists ACCIDENT_HOLD');

    $secondId = 'QA-CRI-' . $suffix;
    AccidentWorkflow::record($db, [...$base, 'docNo' => $secondId, 'severity' => 'Critical'], $userId);
    $releaseFirst = AccidentWorkflow::release($db, $firstId, $userId, 'First CAPA completed');
    $row = $db->query("SELECT status FROM assets WHERE asset_id = " . $db->quote($assetId))->fetchColumn();
    f3cAssert($releaseFirst['remainingLocks'] === 1 && $row === 'ACCIDENT_HOLD', 'One release cannot bypass another active hold');

    $releaseSecond = AccidentWorkflow::release($db, $secondId, $userId, 'Final safety audit completed');
    $row = $db->query("SELECT status FROM assets WHERE asset_id = " . $db->quote($assetId))->fetchColumn();
    f3cAssert($releaseSecond['remainingLocks'] === 0 && $releaseSecond['restoredStatus'] === 'OPERATING' && $row === 'OPERATING', 'Final release restores pre-hold status');
    f3cAssert(AccidentWorkflow::release($db, $secondId, $userId, 'Retry release')['alreadyReleased'] === true, 'Repeated release is idempotent');

    $legacyId = 'QA-LEG-' . $suffix;
    AccidentWorkflow::record($db, [...$base, 'docNo' => $legacyId, 'severity' => 'Moderate'], $userId);
    $db->prepare('UPDATE accidents SET asset_status_before_hold = NULL WHERE accident_id = :id')
        ->execute([':id' => $legacyId]);
    $legacyRelease = AccidentWorkflow::release($db, $legacyId, $userId, 'Legacy hold completed');
    $row = $db->query("SELECT status FROM assets WHERE asset_id = " . $db->quote($assetId))->fetchColumn();
    f3cAssert($legacyRelease['restoredStatus'] === 'OPERATING' && $row === 'OPERATING', 'Legacy hold restores the last proven pre-hold status');

    $laterId = 'QA-LATER-' . $suffix;
    AccidentWorkflow::record($db, [...$base, 'docNo' => $laterId, 'severity' => 'Moderate'], $userId);
    $db->prepare("UPDATE assets SET status = 'BREAKDOWN' WHERE asset_id = :id")->execute([':id' => $assetId]);
    $laterRelease = AccidentWorkflow::release($db, $laterId, $userId, 'Released after later maintenance decision');
    $row = $db->query("SELECT status FROM assets WHERE asset_id = " . $db->quote($assetId))->fetchColumn();
    f3cAssert($laterRelease['restoredStatus'] === null && $row === 'BREAKDOWN', 'Release preserves a later asset status change');

    $audit = $db->prepare('SELECT released_by, release_reason, released_at, is_unit_locked, status FROM accidents WHERE accident_id = :id');
    $audit->execute([':id' => $laterId]);
    $auditRow = $audit->fetch(PDO::FETCH_ASSOC);
    f3cAssert((int) $auditRow['released_by'] === $userId && $auditRow['release_reason'] !== '' && $auditRow['released_at'] !== null && (int) $auditRow['is_unit_locked'] === 0 && $auditRow['status'] === 'Closed', 'Release actor, reason, time, and state are audited');

    echo "\nF3c HSE integration: {$passed} passed.\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
}

$residue = $db->prepare('SELECT COUNT(*) FROM assets WHERE asset_id = :id');
$residue->execute([':id' => $assetId]);
f3cAssert((int) $residue->fetchColumn() === 0, 'QA asset and incidents are rolled back');
