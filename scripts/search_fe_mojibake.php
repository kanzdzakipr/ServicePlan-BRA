<?php
$dir = __DIR__ . '/..';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$targets = ['ƒÆ’', 'ƒ', 'Æ', 'Ãƒ', 'â€', 'Ã¢'];

foreach ($files as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '.git') !== false || strpos($path, 'node_modules') !== false || strpos($path, 'material') !== false || strpos($path, 'search_fe_mojibake.php') !== false) continue;
    
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['html', 'js', 'css', 'php', 'sql', 'json', 'md'])) continue;

    $content = file_get_contents($path);
    $lines = explode("\n", $content);

    $foundCount = 0;
    foreach ($lines as $i => $line) {
        foreach ($targets as $t) {
            if (strpos($line, $t) !== false) {
                echo basename($path) . " [Line " . ($i + 1) . "]: Found '$t' -> " . trim($line) . "\n";
                $foundCount++;
                break;
            }
        }
    }
    if ($foundCount > 0) {
        echo "Total found in " . basename($path) . ": $foundCount\n\n";
    }
}
