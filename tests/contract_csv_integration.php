<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ContractCsv.php';

$db = new PDO(
    (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4'),
    (string) (getenv('REPORT_TEST_DB_USER') ?: 'root'),
    (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
ContractCsv::ensureTable($db);
$asset = (string) $db->query('SELECT asset_id FROM assets ORDER BY asset_id LIMIT 1')->fetchColumn();
if ($asset === '') throw new RuntimeException('Master Asset belum tersedia untuk pengujian.');
$suffix = strtoupper(bin2hex(random_bytes(4)));
$contract = 'QA-CSV-' . $suffix;
$head = implode(',', ContractCsv::HEADERS);
$csv = "\xEF\xBB\xBF" . $head . "\r\n"
    . '"' . $contract . '","Proyek, Utama",Site A,"' . $asset . '",2035-01-01,2035-01-31,120.5,"Catatan, uji"' . "\r\n";
$parsed = ContractCsv::parse($csv, [$asset => true]);
if ($parsed['errors'] || count($parsed['rows']) !== 1 || $parsed['rows'][0]['project'] !== 'Proyek, Utama') {
    throw new RuntimeException('CSV valid dengan BOM dan tanda kutip gagal dibaca.');
}

$invalid = ContractCsv::parse($head . "\n"
    . "{$contract},=BAD,Site A,UNIT-TIDAK-ADA,2035-02-30,2035-01-01,-2,\n", [$asset => true]);
if (count($invalid['errors']) < 4) throw new RuntimeException('Validasi CSV tidak menangkap kesalahan penting.');

$duplicate = ContractCsv::parse($csv . substr($csv, strpos($csv, "\r\n") + 2), [$asset => true]);
if (!array_filter($duplicate['errors'], static fn(array $error): bool => str_contains($error['message'], 'Duplikat'))) {
    throw new RuntimeException('Duplikasi baris CSV tidak terdeteksi.');
}

$db->beginTransaction();
try {
    if (ContractCsv::import($db, $parsed['rows'], 1) !== 1) throw new RuntimeException('Impor kontrak gagal.');
    $rows = ContractCsv::records($db, 'a.asset_id = :asset_id', [':asset_id' => $asset]);
    $record = array_values(array_filter($rows, static fn(array $row): bool => $row['contract_number'] === $contract));
    if (count($record) !== 1 || $record[0]['target_hours'] !== 120.5 || $record[0]['actual_hours'] !== 0.0) {
        throw new RuntimeException('Kontrak atau dasar perhitungan produktivitas tidak cocok.');
    }
    $exported = ContractCsv::export($record);
    $roundTrip = ContractCsv::parse($exported, [$asset => true]);
    if ($roundTrip['errors'] || $roundTrip['rows'][0]['project'] !== 'Proyek, Utama') {
        throw new RuntimeException('CSV ekspor tidak dapat diimpor ulang.');
    }
    ContractCsv::import($db, $roundTrip['rows'], 1);
    $check = $db->prepare('SELECT COUNT(*) FROM contract_assignments WHERE contract_number = :number AND asset_id = :asset');
    $check->execute([':number' => $contract, ':asset' => $asset]);
    if ((int) $check->fetchColumn() !== 1) throw new RuntimeException('Impor ulang menduplikasi kontrak.');
    echo "PASS: validasi, penyimpanan, ekspor, impor ulang, dan idempotensi CSV kontrak.\n";
} finally {
    $db->rollBack();
}
