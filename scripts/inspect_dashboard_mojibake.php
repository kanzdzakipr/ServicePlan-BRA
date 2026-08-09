<?php
$filePath = __DIR__ . '/../dashboard.view.php';
$lines = explode("\n", file_get_contents($filePath));

$mojibakeMap = [
    'Meter Awal - "™ Akhir' => 'Meter Awal &rarr; Akhir',
    '15 Mei 2026 -œ 01 Juli' => '15 Mei 2026 &rarr; 01 Juli',
    '450.000.000 -œ 520.000.000' => '450.000.000 &ndash; 520.000.000',
    '750.000.000 -œ 900.000.000' => '750.000.000 &ndash; 900.000.000',
    '420.000.000 -œ 480.000.000' => '420.000.000 &ndash; 480.000.000',
    '420.000.000 -œ 500.000.000' => '420.000.000 &ndash; 500.000.000',
    '400.000.000 -œ 550.000.000' => '400.000.000 &ndash; 550.000.000',
    '400.000.00 -œ 580.000.000' => '400.000.000 &ndash; 580.000.000',
    '¢— ' => '&bull; ',
    '¢—' => '&bull;',
    '-  ' => '- ',
    '- "™ ' => '&rarr; ',
    '-œ' => '&ndash;',
];

foreach ($lines as $i => $line) {
    foreach ($mojibakeMap as $bad => $good) {
        if (strpos($line, $bad) !== false) {
            echo "Line " . ($i + 1) . ": MATCH: [$bad]\n  BEFORE: " . trim($line) . "\n";
        }
    }
}
