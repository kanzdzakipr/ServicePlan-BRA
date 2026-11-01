<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$read = static function (string $path) use ($root): string {
    $content = file_get_contents($root . DIRECTORY_SEPARATOR . $path);
    if ($content === false) {
        throw new RuntimeException("Tidak dapat membaca {$path}");
    }
    return $content;
};

$activeFiles = [
    'api/people_kpi.php',
    'scripts/dashboard.js',
    'scripts/site-properties.js',
    'scripts/schema.sql',
    'scripts/u646470441_ServicePlanBRA.sql',
];
foreach ($activeFiles as $file) {
    $content = $read($file);
    foreach (['P. Martin', 'D3 Akuntansi'] as $forbidden) {
        if (str_contains($content, $forbidden)) {
            $failures[] = "{$file} masih memuat label personal: {$forbidden}";
        }
    }
}

$unitProperties = $read('api/unit_properties.php');
if (!str_contains($unitProperties, "in_array('Administrator'")) {
    $failures[] = 'api/unit_properties.php belum membatasi valuation ke Administrator.';
}
if (!str_contains($unitProperties, "if (\$canReadValuation)")) {
    $failures[] = 'api/unit_properties.php belum menghilangkan field valuation untuk non-admin.';
}

$costs = $read('api/costs.php');
foreach (['ASSET_VALUATION_FORBIDDEN', 'valuation_access_audit', "'access' => 'Administrator'"] as $expected) {
    if (!str_contains($costs, $expected)) {
        $failures[] = "api/costs.php kehilangan guard/audit valuasi: {$expected}";
    }
}

$view = $read('dashboard.view.php');
if (substr_count($view, 'if ($canViewAssetValuation)') < 3) {
    $failures[] = 'dashboard.view.php belum melindungi kartu, tab, dan payload JS valuasi.';
}

$css = $read('scripts/dashboard.css');
foreach (['scrollbar-gutter: stable', 'position: sticky', '.sl-master-part-select:focus-visible'] as $expected) {
    if (!str_contains($css, $expected)) {
        $failures[] = "Perbaikan UX grid Master Part belum lengkap: {$expected}";
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "OK: privacy valuasi, penghapusan label personal, dan UX Master Part terverifikasi.\n";
