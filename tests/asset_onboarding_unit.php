<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/AssetOnboarding.php';

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$fields = AssetOnboarding::fields([
    'current_operator_name' => 'Operator QA',
    'last_service_date' => '2026-10-17T08:00',
    'last_service_hm_km' => '1000',
    'service_interval_val' => '250',
    'service_interval_unit' => 'HM',
    'daily_usage_coefficient' => '10',
    'legal_stnk_expiry' => '2027-10-17',
], 1100);
check($fields['next_service_due_hm_km'] === 1250.0, 'Target meter tidak sesuai.');
check($fields['next_service_estimated_date'] !== null, 'Estimasi tanggal kosong.');
check($fields['last_service_date'] === '2026-10-17 08:00:00', 'Tanggal servis tidak tersimpan sebagai DATETIME.');

foreach ([
    ['service_interval_val' => '-1'],
    ['daily_usage_coefficient' => '0'],
    ['legal_stnk_expiry' => '2027-02-31'],
] as $invalid) {
    try {
        AssetOnboarding::fields(array_merge([
            'last_service_date' => '2026-10-17T08:00',
            'last_service_hm_km' => '1000',
            'service_interval_val' => '250',
            'service_interval_unit' => 'HM',
            'daily_usage_coefficient' => '10',
        ], $invalid), 1100);
        throw new RuntimeException('Payload invalid diterima.');
    } catch (InvalidArgumentException $expected) {
    }
}

$empty = AssetOnboarding::fields([], 0);
check($empty['next_service_due_hm_km'] === null, 'Unit tanpa data PM tidak boleh diberi target rekaan.');
check($empty['service_interval_unit'] === null, 'Unit tanpa data PM tidak boleh diberi jenis meter rekaan.');
try {
    AssetOnboarding::fields([
        'last_service_date' => '2026-10-17',
        'last_service_hm_km' => '1200',
        'service_interval_val' => '250',
        'service_interval_unit' => 'HM',
        'daily_usage_coefficient' => '10',
    ], 1100);
    throw new RuntimeException('Meter servis yang melebihi meter unit diterima.');
} catch (InvalidArgumentException $expected) {
}
$history = AssetOnboarding::history([
    ['date' => '2026-04-01', 'meter' => '1000', 'type' => 'PS 500'],
    ['date' => '2026-01-01', 'meter' => '750', 'type' => 'PS 250'],
]);
check(count($history) === 2 && $history[0]['meter'] === 750.0, 'Urutan histori servis salah.');
echo "Asset onboarding unit tests passed.\n";
