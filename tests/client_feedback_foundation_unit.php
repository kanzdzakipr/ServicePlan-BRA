<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ExportService.php';
require_once dirname(__DIR__) . '/core/PartUnitConversion.php';
require_once dirname(__DIR__) . '/core/PmForecast.php';

$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("FAIL: {$message}");
};

$assert(ExportService::sanitizeCell('=SUM(A1:A2)') === "'=SUM(A1:A2)", 'formula injection disanitasi');
$assert(ExportService::sanitizeCell(' aman') === ' aman', 'teks biasa dipertahankan');
$snapshot = PartUnitConversion::snapshot(2, 'Drum', 200, 'Liter');
$assert($snapshot['base_quantity'] === 400.0, '2 drum menjadi 400 liter');
$assert($snapshot['transaction_uom'] === 'Drum' && $snapshot['conversion_factor'] === 200.0, 'satuan asal dan faktor tersimpan');
$assert(PmForecast::estimate('2026-11-01', 1400, 1500, 10) === '2026-11-11', 'forecast meter dihitung');
$assert(PmForecast::estimate('2026-11-01', 1400, 1500, 0) === null, 'rate kosong menghasilkan no data');
$assert(PmForecast::qualityFlags(null, 250, null) === ['MISSING_METER', 'MISSING_DAILY_USAGE'], 'flag kualitas data tepat');
$assert(str_contains(ExportService::csv([['name' => '@risk']]), "'@risk"), 'CSV aman dari formula');

echo "PASS: {$assertions} fondasi backlog internal terverifikasi.\n";
