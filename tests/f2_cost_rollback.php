<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/CostLedger.php';

$db = new PDO(
    (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4'),
    (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'),
    (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$context = $db->query('SELECT w.wo_id, w.asset_id FROM work_orders w JOIN assets a ON a.asset_id = w.asset_id ORDER BY w.wo_id LIMIT 1')->fetch();
$actor = (int) $db->query('SELECT user_id FROM users WHERE is_active = 1 ORDER BY user_id LIMIT 1')->fetchColumn();
if (!$context || $actor < 1) throw new RuntimeException('WO/aset/aktor untuk pengujian tidak tersedia.');
$code = 'QA-F2-COST-' . strtoupper(bin2hex(random_bytes(5)));
$user = ['id' => $actor];
$beforeCount = (int) $db->query('SELECT COUNT(*) FROM asset_cost_transactions')->fetchColumn();
$beforeSummary = CostLedger::getSummaryByWo($db, (string) $context['wo_id']);
$passed = 0;

$db->beginTransaction();
try {
    $created = CostLedger::createTransaction($db, [
        'cost_code' => $code,
        'asset_id' => $context['asset_id'],
        'wo_id' => $context['wo_id'],
        'transaction_date' => date('Y-m-d'),
        'cost_category' => 'Maintenance',
        'description' => 'Uji F2 biaya dalam transaksi rollback',
        'amount' => 125000,
        'source_type' => 'MANUAL',
    ], $user);
    if ($created['costCode'] !== $code) throw new RuntimeException('Kode biaya QA tidak sesuai.');
    $passed++;
    $during = CostLedger::getSummaryByWo($db, (string) $context['wo_id']);
    if (abs((float) $during['totalCost'] - ((float) $beforeSummary['totalCost'] + 125000)) > 0.01) {
        throw new RuntimeException('Total biaya WO tidak bertambah tepat 125000.');
    }
    $passed++;
    try {
        CostLedger::createTransaction($db, [
            'cost_code' => $code,
            'asset_id' => $context['asset_id'],
            'wo_id' => $context['wo_id'],
            'cost_category' => 'Maintenance',
            'description' => 'Duplikat QA',
            'amount' => 125000,
        ], $user);
        throw new RuntimeException('Kode biaya duplikat diterima.');
    } catch (InvalidArgumentException $expected) {
        $passed++;
    }
    $reversed = CostLedger::reverseTransaction($db, (int) $created['transactionId'], 'Uji reversal F2', $user);
    if ($reversed['status'] !== 'REVERSED') throw new RuntimeException('Status reversal biaya tidak benar.');
    $passed++;
    $restored = CostLedger::getSummaryByWo($db, (string) $context['wo_id']);
    if (abs((float) $restored['totalCost'] - (float) $beforeSummary['totalCost']) > 0.01) {
        throw new RuntimeException('Total biaya WO tidak pulih setelah reversal.');
    }
    $passed++;
    try {
        CostLedger::reverseTransaction($db, (int) $created['transactionId'], 'Reversal kedua', $user);
        throw new RuntimeException('Reversal biaya kedua diterima.');
    } catch (InvalidArgumentException $expected) {
        $passed++;
    }
} finally {
    if ($db->inTransaction()) $db->rollBack();
}

$afterCount = (int) $db->query('SELECT COUNT(*) FROM asset_cost_transactions')->fetchColumn();
$check = $db->prepare('SELECT COUNT(*) FROM asset_cost_transactions WHERE cost_code = :code');
$check->execute([':code' => $code]);
if ($afterCount !== $beforeCount || (int) $check->fetchColumn() !== 0) {
    throw new RuntimeException('Rollback biaya tidak bersih.');
}
echo "F2 cost rollback: {$passed}/6 passed; rows {$beforeCount} -> {$afterCount}; QA cost tersisa 0.\n";
