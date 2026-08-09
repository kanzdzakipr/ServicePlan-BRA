<?php
$files = [
    __DIR__ . '/../dashboard.view.php',
    __DIR__ . '/../scripts/dashboard.js',
    __DIR__ . '/../scripts/dashboard.css'
];

$targets = ['Ã¢â‚¬â€œ', 'Ã¢â‚¬', 'Ã¢', 'â€“', 'â€”', 'â€', 'Ã', 'ï¿½', 'â€œ', 'â€'];

foreach ($files as $filePath) {
    if (!file_exists($filePath)) continue;
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);

    echo "Checking " . basename($filePath) . " (" . count($lines) . " lines)...\n";
    $count = 0;
    foreach ($lines as $i => $line) {
        foreach ($targets as $t) {
            if (strpos($line, $t) !== false) {
                echo "  Line " . ($i + 1) . ": Match [$t] -> " . trim($line) . "\n";
                $count++;
                break;
            }
        }
    }
    echo "Total matching lines in " . basename($filePath) . ": $count\n\n";
}
