<?php
$dir = __DIR__ . '/..';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$mojibakePatterns = [
    '-"™', '-œ', '¢—', 'Â', 'Ã', 'â€', 'â€”', 'â€¢', 'ï¿½', ''
];

foreach ($files as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '.git') !== false || strpos($path, 'node_modules') !== false || strpos($path, 'material') !== false) continue;
    
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['html', 'js', 'css', 'php'])) continue;

    $content = file_get_contents($path);
    $found = [];
    foreach ($mojibakePatterns as $pat) {
        if (strpos($content, $pat) !== false) {
            $found[] = $pat;
        }
    }

    if (count($found) > 0) {
        echo "Mojibake in " . basename($path) . ": " . implode(", ", $found) . "\n";
    }
}
