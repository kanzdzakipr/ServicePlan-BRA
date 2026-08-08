<?php
$filePath = __DIR__ . '/../dashboard.html';
$content = file_get_contents($filePath);
$lines = explode("\n", $content);

foreach ($lines as $i => $line) {
    if (strpos($line, '<<<<<<<') !== false || strpos($line, '=======') !== false || strpos($line, '>>>>>>>') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
