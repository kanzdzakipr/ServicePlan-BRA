<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/AdministrationRecap.php';

$db = new PDO(
    (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4'),
    (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'),
    (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$months = ['2026-01', '2026-08', '2026-10'];
foreach ($months as $month) {
    $recap = AdministrationRecap::logistics($db, $month);
    $direct = $db->prepare("SELECT movement_type, COUNT(*) AS line_count, COALESCE(SUM(quantity),0) AS quantity
        FROM inventory_transactions WHERE reversed_at IS NULL
        AND transaction_date >= :start AND transaction_date < :end GROUP BY movement_type");
    $direct->execute([':start' => $recap['periodStart'], ':end' => $recap['periodEndExclusive']]);
    $counts = [];
    foreach ($direct->fetchAll() as $row) $counts[$row['movement_type']] = $row;
    foreach (['IN' => ['incomingLines', 'receivedQuantity', 'incoming'], 'OUT' => ['outgoingLines', 'issuedQuantity', 'outgoing']] as $type => $keys) {
        if ($recap['summary'][$keys[0]] !== (int) ($counts[$type]['line_count'] ?? 0)
            || $recap['summary'][$keys[1]] !== (int) ($counts[$type]['quantity'] ?? 0)
            || count($recap[$keys[2]]) !== $recap['summary'][$keys[0]]) {
            throw new RuntimeException("Rekap {$month} {$type} tidak sesuai transaksi aktif.");
        }
    }
    echo "PASS: {$month} cocok dengan transaksi aktif.\n";
}
try {
    AdministrationRecap::logistics($db, '2026-13');
    throw new RuntimeException('Periode tidak valid diterima.');
} catch (InvalidArgumentException) {
    echo "PASS: periode tidak valid ditolak.\n";
}
