<?php
$htmlPath = __DIR__ . '/../dashboard.view.php';
$jsPath = __DIR__ . '/../scripts/dashboard.js';

$htmlContent = file_get_contents($htmlPath);
$jsContent = file_get_contents($jsPath);

// Check 1: Inline JS blocks inside dashboard.html
preg_match_all('/<script>(.*?)<\/script>/s', $htmlContent, $matches, PREG_OFFSET_CAPTURE);

echo "Found " . count($matches[1]) . " inline <script> blocks in dashboard.html.\n";

// Write inline script blocks to temp file to check syntax
foreach ($matches[1] as $idx => $match) {
    $scriptText = $match[0];
    $offset = $match[1];
    $lineNumber = substr_count(substr($htmlContent, 0, $offset), "\n") + 1;
    
    // Check balanced braces/quotes/parens in this block
    $curly = substr_count($scriptText, '{') - substr_count($scriptText, '}');
    $paren = substr_count($scriptText, '(') - substr_count($scriptText, ')');
    $square = substr_count($scriptText, '[') - substr_count($scriptText, ']');
    
    if ($curly !== 0 || $paren !== 0 || $square !== 0) {
        echo "SYNTAX WARNING in dashboard.html inline <script> block #$idx (around line $lineNumber):\n";
        echo "  Curly balance: $curly, Paren balance: $paren, Square balance: $square\n";
    }
}

// Check 2: Brackets in dashboard.js
$jsCurly = substr_count($jsContent, '{') - substr_count($jsContent, '}');
$jsParen = substr_count($jsContent, '(') - substr_count($jsContent, ')');
$jsSquare = substr_count($jsContent, '[') - substr_count($jsContent, ']');

echo "\ndashboard.js bracket balance: Curly=$jsCurly, Paren=$jsParen, Square=$jsSquare\n";
