<?php
declare(strict_types=1);

$view = file_get_contents(__DIR__ . '/../dashboard.view.php');
if ($view === false) {
    fwrite(STDERR, "FAIL: dashboard view unavailable\n");
    exit(1);
}

$checks = [
    'API init required before dashboard renders' => str_contains($view, "throw new Error('Data Master Asset tidak dapat dimuat dari API terproteksi')"),
    'legacy browser state is not restored over API' => !str_contains($view, 'restoreFleetState()'),
    'legacy integrated state is not saved to localStorage' => !str_contains($view, "localStorage.setItem(FLEET_STATE_KEY"),
    'total units uses filtered assets, including zero' => str_contains($view, 'const totalUnits = assets.length;'),
    'READY zero is preserved' => str_contains($view, "getElementById('kpiReady').textContent = readyOpCount;"),
    'STANDBY zero is preserved' => str_contains($view, "getElementById('kpiStandby').textContent = standbyCount;"),
    'BREAKDOWN zero is preserved' => str_contains($view, "getElementById('kpiBreakdown').textContent = breakdownCount;"),
    'INSPEKSI zero is preserved' => str_contains($view, "getElementById('kpiInspeksi').textContent = inspeksiCount;"),
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
echo 'F1 dashboard source static: ' . count($checks) . " passed. No database writes.\n";
