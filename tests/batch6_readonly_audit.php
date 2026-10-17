<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ProductivityCalculator.php';
require_once dirname(__DIR__) . '/core/CostLedger.php';
require_once dirname(__DIR__) . '/api/security.php';

$db = new PDO(
    (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4'),
    (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'),
    (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
    echo "PASS: {$message}\n";
};

$period = 'Januari 2026';
$asset = $db->query("SELECT asset_id FROM telematics_logs WHERE period_month = 'Januari 2026' AND asset_id IS NOT NULL LIMIT 1")->fetchColumn();
if ($asset === false) throw new RuntimeException('Tidak ada unit telematika untuk audit.');
$result = ProductivityCalculator::calculate($db, null, $period, (string) $asset);
$assert(count($result['telematics']) > 0, 'Filter unit dan periode telematika berisi data');
$assert(count(array_unique(array_column($result['telematics'], 'assetId'))) === 1, 'Tidak ada unit lain dalam hasil filter');
$lhoStmt = $db->prepare("SELECT COALESCE(SUM(work_hours),0) FROM report_operation_logs WHERE asset_id = :asset AND reversed_at IS NULL AND operation_date >= '2026-01-01' AND operation_date < '2026-02-01'");
$lhoStmt->execute([':asset' => $asset]);
$expectedLho = (float) $lhoStmt->fetchColumn();
$first = $result['reconciliation'][0];
$assert($expectedLho === 0.0 || abs((float) $first['manualLhoHours'] - $expectedLho) < 0.01, 'LHO pembanding sesuai periode yang sama');
$noData = ProductivityCalculator::calculate($db, null, 'Januari 2099', (string) $asset);
$assert($noData['kpis']['physicalAvailability']['value'] === null, 'Periode tanpa data tidak menjadi KPI nol palsu');

$costs = CostLedger::getTransactions($db, ['asset_id' => $asset, 'status' => 'all']);
$active = array_sum(array_map(static fn(array $row): float => $row['isReversed'] ? 0.0 : $row['amount'], $costs['transactions']));
$assert(abs($active - $costs['summary']['totalActiveAmount']) < 0.01, 'Total biaya kartu sama dengan rincian aktif');
$assert($costs['summary']['totalCount'] === count($costs['transactions']), 'Jumlah transaksi cocok dengan rincian');
$assert(!in_array('cost.read', api_builtin_permissions_for_roles(['Mekanik Senior']), true), 'Mekanik tanpa izin tidak memperoleh cost.read');
$assert(in_array('cost.read', api_builtin_permissions_for_roles(['Maintenance Planner']), true), 'Planner memperoleh cost.read');
$assert(!in_array('cost.write', api_builtin_permissions_for_roles(['Logistic Head']), true), 'Logistik baca-saja tidak memperoleh cost.write');
echo "Audit baca-saja: {$checks} pemeriksaan lulus.\n";
