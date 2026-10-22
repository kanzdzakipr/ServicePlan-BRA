<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/FleetSyncPolicy.php';

$checks = 0;
$assert = static function (bool $ok, string $name) use (&$checks): void {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
    $checks++;
};

$asset = ['id' => 'QA-UNIT-1', 'status' => 'INSPEKSI', 'expected_status' => 'READY', 'last_hm_km' => 100];
$wo = ['woId' => 'QA-WO-1', 'assetId' => 'QA-UNIT-1', 'status' => 'Open'];
$valid = ['mode' => 'targeted', 'assets' => [$asset], 'work_orders' => [$wo]];
$assert(FleetSyncPolicy::error($valid) === null, 'one related asset and WO accepted');
$assert(FleetSyncPolicy::error(['mode' => 'targeted', 'assets' => [$asset]]) === null, 'one asset accepted');
$assert(FleetSyncPolicy::error(['mode' => 'targeted', 'work_orders' => [$wo]]) === null, 'one WO accepted');
$assert(FleetSyncPolicy::error(['assets' => [$asset]]) !== null, 'legacy mass mode rejected');
$assert(FleetSyncPolicy::error(['mode' => 'targeted']) !== null, 'empty write rejected');
$assert(FleetSyncPolicy::error(['mode' => 'targeted', 'assets' => [$asset, $asset]]) !== null, 'two assets rejected');
$assert(FleetSyncPolicy::error(['mode' => 'targeted', 'work_orders' => [$wo, $wo]]) !== null, 'two WOs rejected');
$bad = $valid;
$bad['work_orders'][0]['assetId'] = 'OTHER';
$assert(FleetSyncPolicy::error($bad) !== null, 'cross-asset WO rejected');
$bad = $valid;
$bad['assets'][0]['status'] = 'INVALID';
$assert(FleetSyncPolicy::error($bad) !== null, 'invalid asset status rejected');
$bad = $valid;
$bad['assets'][0]['last_hm_km'] = -1;
$assert(FleetSyncPolicy::error($bad) !== null, 'negative meter rejected');
$bad = $valid;
unset($bad['assets'][0]['expected_status']);
$assert(FleetSyncPolicy::error($bad) !== null, 'asset write requires expected server status');
$bad = $valid;
$bad['work_orders'][0]['status'] = 'Not a status';
$assert(FleetSyncPolicy::error($bad) !== null, 'invalid WO status rejected');

$view = file_get_contents(__DIR__ . '/../dashboard.view.php');
$api = file_get_contents(__DIR__ . '/../api/sync.php');
$script = file_get_contents(__DIR__ . '/../scripts/dashboard.js');
$assert(is_string($view) && !str_contains($view, 'assets: globalData.assets.map'), 'Dashboard no longer sends every asset');
$assert(is_string($view) && !str_contains($view, 'work_orders: globalData.work_orders || []'), 'Dashboard no longer sends every WO');
$assert(is_string($api) && str_contains($api, 'FleetSyncPolicy::error($data)'), 'API enforces bounded sync before transaction');
$assert(is_string($script) && str_contains($script, 'workOrderId: newAutoWoId'), 'P2H targets only generated WO');

echo "F1 targeted sync policy: $checks passed. No database writes.\n";
