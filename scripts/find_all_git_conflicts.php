<?php
$dir = __DIR__ . '/..';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$foundConflicts = [];

foreach ($files as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strpos($path, '.git') !== false || strpos($path, 'node_modules') !== false) continue;

    $content = file_get_contents($path);
    $lines = explode("\n", $content);

    foreach ($lines as $i => $line) {
        if (strpos($line, '<<<<<<<') === 0 || strpos($line, '=======') === 0 || strpos($line, '>>>>>>>') === 0) {
            $foundConflicts[] = [
                'file' => basename($path),
                'rel_path' => str_replace(realpath($dir), '', realpath($path)),
                'line' => $i + 1,
                'content' => trim($line)
            ];
        }
    }
}

echo "Total Git Conflict Marker Lines Found: " . count($foundConflicts) . "\n";
echo "========================================================================================\n";
foreach ($foundConflicts as $fc) {
    echo $fc['file'] . " [Line " . $fc['line'] . "]: " . $fc['content'] . "\n";
}
