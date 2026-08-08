<?php
$filePath = __DIR__ . '/../dashboard.html';
$content = file_get_contents($filePath);

$lines = explode("\n", $content);
$mojibakeFound = [];

// Mojibake patterns: â, Â, Ã, , â€, â€”, â€¢, etc.
foreach ($lines as $i => $line) {
    if (preg_match('/[\x80-\xFF]/', $line)) {
        $mojibakeFound[] = [
            'line' => $i + 1,
            'content' => trim($line)
        ];
    }
}

echo "Lines with non-ASCII or potential Mojibake characters in dashboard.html: " . count($mojibakeFound) . "\n";
echo "========================================================================================\n";
foreach ($mojibakeFound as $item) {
    echo "Line " . $item['line'] . ": " . $item['content'] . "\n";
}
