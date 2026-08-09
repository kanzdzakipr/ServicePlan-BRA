<?php
$htmlPath = __DIR__ . '/../dashboard.view.php';
$content = file_get_contents($htmlPath);
$lines = explode("\n", $content);

$stack = [];

foreach ($lines as $i => $line) {
    $lineNum = $i + 1;
    
    // Find all div tags in line
    preg_match_all('/<div\b[^>]*>|<\/div>/i', $line, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as $match) {
        $tag = strtolower($match[0]);
        if (strpos($tag, '</div') === 0) {
            if (count($stack) > 0) {
                array_pop($stack);
            } else {
                echo "Line $lineNum: Extra </div> tag found!\n";
            }
        } else {
            $stack[] = ['line' => $lineNum, 'code' => trim($line)];
        }
    }
}

echo "Total unclosed <div> tags remaining: " . count($stack) . "\n";
echo "========================================================================================\n";
foreach ($stack as $item) {
    echo "Unclosed <div> at Line {$item['line']}: {$item['code']}\n";
}
