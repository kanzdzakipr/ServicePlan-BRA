<?php
$htmlPath = __DIR__ . '/../dashboard.view.php';
$jsPath = __DIR__ . '/../scripts/dashboard.js';

$htmlContent = file_get_contents($htmlPath);
$jsContent = file_get_contents($jsPath);

echo "=== AUDITING DASHBOARD.HTML ===\n";

// 1. Check duplicate element IDs in dashboard.html
preg_match_all('/id=["\']([^"\']+)["\']/', $htmlContent, $idMatches, PREG_OFFSET_CAPTURE);
$ids = [];
$dupIds = [];
foreach ($idMatches[1] as $item) {
    $id = $item[0];
    $offset = $item[1];
    $line = substr_count(substr($htmlContent, 0, $offset), "\n") + 1;
    if (isset($ids[$id])) {
        $dupIds[] = "Duplicate ID '$id' at line $line (first seen at line " . $ids[$id] . ")";
    } else {
        $ids[$id] = $line;
    }
}
echo "Duplicate IDs found in dashboard.html: " . count($dupIds) . "\n";
foreach ($dupIds as $d) echo "  - $d\n";

// 2. Check for missing closing tags in HTML
$tags = ['div', 'select', 'option', 'table', 'tr', 'td', 'th', 'button', 'script', 'form', 'label'];
echo "\nChecking HTML tag counts:\n";
foreach ($tags as $t) {
    $open = preg_match_all('/<' . $t . '[\s>]/i', $htmlContent);
    $close = preg_match_all('/<\/' . $t . '>/i', $htmlContent);
    if ($open !== $close) {
        echo "  - TAG <$t>: Open count = $open, Close count = $close (Mismatch: " . ($open - $close) . ")\n";
    }
}

// 3. Check inline JS syntax error in dashboard.html
echo "\n=== AUDITING SCRIPT BLOCKS IN DASHBOARD.HTML ===\n";
preg_match_all('/<script>(.*?)<\/script>/s', $htmlContent, $scriptMatches, PREG_OFFSET_CAPTURE);
foreach ($scriptMatches[1] as $idx => $match) {
    $script = $match[0];
    $offset = $match[1];
    $startLine = substr_count(substr($htmlContent, 0, $offset), "\n") + 1;
    
    // Check for syntax issues like double comma, trailing comma in parameters, unclosed strings
    $lines = explode("\n", $script);
    foreach ($lines as $lIdx => $line) {
        $curLine = $startLine + $lIdx;
        if (preg_match('/,\s*,/', $line)) {
            echo "Line $curLine: Double comma in inline script -> " . trim($line) . "\n";
        }
        if (preg_match('/function\s*\(.*,\s*\)/', $line)) {
            echo "Line $curLine: Trailing comma in function params -> " . trim($line) . "\n";
        }
    }
}

echo "\n=== AUDITING DASHBOARD.JS ===\n";
$jsLines = explode("\n", $jsContent);
foreach ($jsLines as $idx => $line) {
    $lineNum = $idx + 1;
    if (preg_match('/,\s*,/', $line)) {
        echo "Line $lineNum: Double comma in JS -> " . trim($line) . "\n";
    }
    if (preg_match('/function\s*\(.*,\s*\)/', $line)) {
        echo "Line $lineNum: Trailing comma in function params -> " . trim($line) . "\n";
    }
    if (strpos($line, 'undefined.') !== false) {
        echo "Line $lineNum: Potential undefined access -> " . trim($line) . "\n";
    }
}
