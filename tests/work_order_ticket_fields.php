<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';

$passed = 0;
$failed = 0;

function ticketAssert(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS: {$message}\n";
        return;
    }
    $failed++;
    fwrite(STDERR, "FAIL: {$message}\n");
}

$asset = [
    'asset_code' => 'DT-00049',
    'alias_name' => 'DT-49-PROJECT',
    'category' => 'Dump Truck',
];

$forced = WorkOrderSupport::normalizeTicketInput([
    'current_hm_km' => '34550.75',
    'trouble_at' => '2026-10-17T09:30',
    'raw_location' => 'Hauling Road KM 14',
    'repair_started_at' => '2026-10-17T10:00',
    'estimated_completion_at' => '2026-10-17T14:00',
    'is_downtime' => true,
    'is_forced_operation' => true,
    'mitigation_action' => 'Batasi kecepatan dan inspeksi setiap pergantian shift.',
    'assigned_mechanics' => ['Apeng', 'apeng', 'Maman'],
], $asset);

ticketAssert($forced['is_forced_operation'] === 1, 'operasi paksa ditandai pada WO');
ticketAssert($forced['is_downtime'] === 0, 'RFU Bersyarat tidak dicatat sebagai berhenti total');
ticketAssert($forced['asset_status'] === 'OPERATING', 'status aset RFU Bersyarat tetap OPERATING');
ticketAssert($forced['assigned_mechanics'] === ['Apeng', 'Maman'], 'PIC dideduplikasi tanpa membedakan kapital');
ticketAssert($forced['assigned_mechanic'] === 'Apeng, Maman', 'label kompatibilitas PIC tersusun');
ticketAssert($forced['trouble_at'] === '2026-10-17 09:30:00', 'datetime-local dinormalisasi untuk MySQL');
ticketAssert($forced['hull_number'] === 'DT-49-PROJECT', 'nomor lambung mengambil snapshot master aset');

$breakdown = WorkOrderSupport::normalizeTicketInput([
    'currentHmKm' => 100,
    'troubleAt' => '2026-10-17 09:30:00',
    'rawLocation' => 'Workshop Induk',
    'isDowntime' => true,
    'assignedMechanics' => [],
], $asset);
ticketAssert($breakdown['asset_status'] === 'BREAKDOWN', 'downtime total mengubah status aset menjadi BREAKDOWN');

$safetyHold = WorkOrderSupport::normalizeTicketInput([
    'current_hm_km' => 100,
    'trouble_at' => '2026-10-17T09:30',
    'raw_location' => 'Workshop Induk',
    'is_forced_operation' => true,
    'mitigation_action' => 'Unit tidak boleh dilepas sebelum investigasi HSE selesai.',
], $asset + ['status' => 'ACCIDENT_HOLD']);
ticketAssert($safetyHold['asset_status'] === 'ACCIDENT_HOLD', 'RFU Bersyarat tidak menimpa hard safety hold');

try {
    WorkOrderSupport::normalizeTicketInput([
        'current_hm_km' => 100,
        'trouble_at' => '2026-10-17T09:30',
        'raw_location' => 'Pit Barat',
        'is_forced_operation' => true,
        'mitigation_action' => '',
    ], $asset);
    ticketAssert(false, 'RFU Bersyarat tanpa mitigasi ditolak');
} catch (InvalidArgumentException $error) {
    ticketAssert(true, 'RFU Bersyarat tanpa mitigasi ditolak');
}

try {
    WorkOrderSupport::normalizeTicketInput([
        'current_hm_km' => 100,
        'trouble_at' => '2026-10-17T09:30',
        'raw_location' => 'Pit Barat',
        'repair_started_at' => '2026-10-17T12:00',
        'estimated_completion_at' => '2026-10-17T11:00',
    ], $asset);
    ticketAssert(false, 'waktu selesai sebelum waktu mulai ditolak');
} catch (InvalidArgumentException $error) {
    ticketAssert(true, 'waktu selesai sebelum waktu mulai ditolak');
}

echo "\nWO ticket tests: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);

