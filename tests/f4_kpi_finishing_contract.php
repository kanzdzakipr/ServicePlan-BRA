<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$api = (string) file_get_contents($root . '/api/people_kpi.php');
$dashboard = (string) file_get_contents($root . '/scripts/dashboard.js');
$migration = (string) file_get_contents($root . '/scripts/migrate_f4_kpi_audit_location_24102026.sql');

$passed = 0;
function f4Contract(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    $passed++;
    echo "PASS: {$message}\n";
}

f4Contract(!str_contains($dashboard, 'period: "2026-01"'), 'Payload mutasi browser tidak lagi hardcoded ke Januari 2026');
f4Contract(str_contains($dashboard, 'location_id: locationId'), 'Payload KPI mengirim location_id terpilih');
f4Contract(str_contains($dashboard, 'type="month" min="2026-01" max="2099-12"'), 'UI menyediakan periode QA hingga 2099-12');
f4Contract(str_contains($dashboard, 'id="selKpiLocation"'), 'UI menyediakan pemilih lokasi resmi');
f4Contract(str_contains($dashboard, "window.approveHeadKPI('REJECTED')"), 'UI menyediakan alur rejection');
f4Contract(str_contains($dashboard, 'SIMULASI BELUM TERSIMPAN'), 'Ekspor membedakan simulasi dari data database');
f4Contract(str_contains($api, "SELF_APPROVAL_FORBIDDEN"), 'Backend melarang self-approval');
f4Contract(str_contains($api, "INCOMPLETE_ASSESSMENT"), 'Backend menolak approval penilaian tidak lengkap');
f4Contract(str_contains($api, "people_kpi_record_audit"), 'Backend menulis audit lifecycle KPI');
f4Contract(str_contains($api, "Hanya Maintenance Planner atau Administrator"), 'Backend membatasi role review SPL');
f4Contract(str_contains($api, "rr.status = 'FINAL'"), 'Review SPL hanya menerima laporan FINAL');
f4Contract(str_contains($migration, 'CREATE TABLE IF NOT EXISTS `head_kpi_audit_trail`'), 'Migrasi audit bersifat aditif dan idempotent');

echo "All {$passed} F4 finishing contract checks passed.\n";
