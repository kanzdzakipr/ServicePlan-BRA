<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/AssetSearch.php';

function assertSearch(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

assertSearch(AssetSearch::escapeLike('A_%!') === 'A!_!%!!', 'Wildcard LIKE tidak di-escape dengan benar.');
foreach (['', 'A', str_repeat('X', 101)] as $invalid) {
    try {
        AssetSearch::validateQuery($invalid);
        throw new RuntimeException('Kueri tidak valid diterima.');
    } catch (InvalidArgumentException $expected) {
    }
}

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
try {
    $db = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
} catch (Throwable $error) {
    echo 'SKIP: database tidak tersedia: ' . $error->getMessage() . PHP_EOL;
    exit(0);
}

$fixture = $db->query("SELECT asset_id, asset_code, COALESCE(NULLIF(hull_number, ''), NULLIF(alias_name, '')) AS hull, serial_number, license_plate, current_location_id FROM assets WHERE serial_number IS NOT NULL AND license_plate IS NOT NULL AND current_location_id IS NOT NULL LIMIT 1")->fetch();
if (!$fixture) {
    echo "SKIP: belum ada aset dengan empat identitas dan lokasi.\n";
    exit(0);
}
$global = ['sql' => '', 'params' => []];
foreach (['asset_code', 'hull', 'serial_number', 'license_plate'] as $field) {
    $matches = AssetSearch::find($db, (string) $fixture[$field], $global);
    assertSearch(count($matches) <= AssetSearch::MAX_RESULTS, 'Jumlah hasil melebihi batas.');
    assertSearch(in_array($fixture['asset_id'], array_column($matches, 'asset_id'), true), "$field tidak menemukan aset yang sama.");
}
$allowed = ['sql' => 'a.current_location_id = :test_location_id', 'params' => [':test_location_id' => (int) $fixture['current_location_id']]];
$denied = ['sql' => 'a.current_location_id = :test_location_id', 'params' => [':test_location_id' => -1]];
assertSearch(in_array($fixture['asset_id'], array_column(AssetSearch::find($db, (string) $fixture['asset_code'], $allowed), 'asset_id'), true), 'Aset di lokasi sendiri tidak muncul.');
assertSearch(!in_array($fixture['asset_id'], array_column(AssetSearch::find($db, (string) $fixture['asset_code'], $denied), 'asset_id'), true), 'Aset lokasi lain bocor.');
echo "Asset search integration tests passed (read-only).\n";
