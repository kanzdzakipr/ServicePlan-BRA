<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/KpiCalculator.php';

$passed = 0;
$failed = 0;

function kpiAssert(bool $condition, string $message): void
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

function kpiAssertNear(?float $actual, float $expected, string $message): void
{
    kpiAssert($actual !== null && abs($actual - $expected) < 0.001, $message);
}

echo "=== GLB-D: VERIFIKASI FORMULA KPI PRODUKTIVITAS ===\n";

$baseline = KpiCalculator::calculate([
    'operatingHours' => 60,
    'standbyHours' => 20,
    'repairHours' => 20,
    'scheduledHours' => 100,
    'failureCount' => 4,
    'repairCount' => 5,
]);

kpiAssertNear($baseline['kpis']['physicalAvailability']['value'], 80.0, 'PA = (W + S) / T');
kpiAssertNear($baseline['kpis']['mechanicalAvailability']['value'], 75.0, 'MA = W / (W + R)');
kpiAssertNear($baseline['kpis']['useOfAvailability']['value'], 75.0, 'UA = W / (W + S)');
kpiAssertNear($baseline['kpis']['effectiveUtilization']['value'], 60.0, 'EU = W / T');
kpiAssertNear($baseline['kpis']['mtbf']['value'], 15.0, 'MTBF = W / frekuensi kerusakan');
kpiAssertNear($baseline['kpis']['mttr']['value'], 4.0, 'MTTR = R / frekuensi perbaikan selesai');
kpiAssert($baseline['dataQuality']['isConsistent'] === true, 'W + S + R konsisten terhadap T');
kpiAssert($baseline['formulaVersion'] === 'GLB-D-2026-10-17', 'versi formula terkunci dan dapat diaudit');

$precision = KpiCalculator::calculate([
    'operatingHours' => 1,
    'standbyHours' => 1,
    'repairHours' => 1,
    'scheduledHours' => 3,
    'failureCount' => 1,
    'repairCount' => 1,
]);
kpiAssertNear($precision['kpis']['effectiveUtilization']['value'], 33.33, 'persentase dibulatkan setelah perhitungan rasio');

$zero = KpiCalculator::calculate([
    'operatingHours' => 0,
    'standbyHours' => 0,
    'repairHours' => 0,
    'scheduledHours' => 0,
    'failureCount' => 0,
    'repairCount' => 0,
]);
kpiAssert($zero['kpis']['physicalAvailability']['value'] === null, 'PA aman saat pembagi nol');
kpiAssert($zero['kpis']['mechanicalAvailability']['value'] === null, 'MA aman saat pembagi nol');
kpiAssert($zero['kpis']['useOfAvailability']['value'] === null, 'UA aman saat pembagi nol');
kpiAssert($zero['kpis']['effectiveUtilization']['value'] === null, 'EU aman saat pembagi nol');
kpiAssert($zero['kpis']['mtbf']['value'] === null, 'MTBF tidak dipalsukan menjadi nol');
kpiAssert($zero['kpis']['mttr']['value'] === null, 'MTTR tidak dipalsukan menjadi nol');

$inconsistent = KpiCalculator::calculate([
    'operatingHours' => 70,
    'standbyHours' => 20,
    'repairHours' => 20,
    'scheduledHours' => 100,
    'failureCount' => 2,
    'repairCount' => 2,
]);
kpiAssert($inconsistent['dataQuality']['isConsistent'] === false, 'selisih komponen jam ditandai sebagai masalah kualitas data');
kpiAssert(count($inconsistent['dataQuality']['warnings']) > 0, 'penjelasan inkonsistensi tersedia');

try {
    KpiCalculator::calculate([
        'operatingHours' => -1,
        'standbyHours' => 0,
        'repairHours' => 0,
        'scheduledHours' => 0,
        'failureCount' => 0,
        'repairCount' => 0,
    ]);
    kpiAssert(false, 'nilai negatif ditolak');
} catch (InvalidArgumentException $error) {
    kpiAssert(true, 'nilai negatif ditolak');
}

// Verifikasi tambahan terhadap database lokal. Ketika DB tidak aktif, unit test
// matematis di atas tetap dijalankan dan bagian integrasi dilaporkan sebagai SKIP.
$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $hours = $db->query("SELECT
            COALESCE(SUM(actual_working_hours), 0) AS operating_hours,
            COALESCE(SUM(working_hours), 0) AS scheduled_hours
        FROM telematics_logs
        WHERE period_month = 'Januari 2026'")->fetch();
    $repairs = $db->query("SELECT
            COALESCE(SUM(CASE WHEN is_downtime = 1 AND status <> 'Cancelled' THEN downtime_minutes ELSE 0 END), 0) / 60 AS repair_hours,
            COALESCE(SUM(CASE WHEN is_downtime = 1 AND status <> 'Cancelled' THEN 1 ELSE 0 END), 0) AS failure_count,
            COALESCE(SUM(CASE WHEN is_downtime = 1 AND status = 'Closed' THEN 1 ELSE 0 END), 0) AS repair_count
        FROM work_orders
        WHERE reported_at >= '2026-01-01 00:00:00'
          AND reported_at < '2026-02-01 00:00:00'")->fetch();

    $working = (float) $hours['operating_hours'];
    $scheduled = (float) $hours['scheduled_hours'];
    $repairHours = (float) $repairs['repair_hours'];
    $databaseResult = KpiCalculator::calculate([
        'operatingHours' => $working,
        'standbyHours' => max(0.0, $scheduled - $working - $repairHours),
        'repairHours' => $repairHours,
        'scheduledHours' => $scheduled,
        'failureCount' => (int) $repairs['failure_count'],
        'repairCount' => (int) $repairs['repair_count'],
    ]);

    kpiAssert($databaseResult['variables']['T'] === round($scheduled, 2), 'nilai T berasal dari telematics_logs lokal');
    kpiAssert($databaseResult['variables']['R'] === round($repairHours, 2), 'nilai R berasal dari work_orders lokal');
    kpiAssert(isset($databaseResult['kpis']['physicalAvailability']), 'data lokal dapat dihitung oleh engine KPI terkunci');
} catch (Throwable $error) {
    echo 'SKIP: integrasi database lokal tidak tersedia: ' . $error->getMessage() . "\n";
}

echo "\nKPI tests: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
