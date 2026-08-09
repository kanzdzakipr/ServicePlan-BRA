<?php
$htmlPath = __DIR__ . '/../dashboard.view.php';
$jsPath = __DIR__ . '/../scripts/dashboard.js';

$htmlContent = file_get_contents($htmlPath);
$jsContent = file_get_contents($jsPath);

// 1. Check all duplicate IDs in dashboard.html (excluding template strings)
preg_match_all('/id=["\']([^"\']+)["\']/', $htmlContent, $matches, PREG_OFFSET_CAPTURE);

$seenIds = [];
$duplicates = [];

foreach ($matches[1] as $match) {
    $id = $match[0];
    $offset = $match[1];
    $line = substr_count(substr($htmlContent, 0, $offset), "\n") + 1;
    
    // Ignore template literal placeholders like ${...}
    if (strpos($id, '${') !== false) continue;

    if (isset($seenIds[$id])) {
        $duplicates[] = "Line $line: Duplicate ID '$id' (first seen on line {$seenIds[$id]})";
    } else {
        $seenIds[$id] = $line;
    }
}

echo "=== REAL DUPLICATE HTML IDs ===\n";
foreach ($duplicates as $dup) {
    echo "  $dup\n";
}

// 2. Check onclick handlers referencing non-existent functions
preg_match_all('/onclick=["\']([^"\']+)["\']/', $htmlContent, $onclickMatches, PREG_OFFSET_CAPTURE);
echo "\n=== CHECKING ONCLICK HANDLERS IN DASHBOARD.HTML ===\n";
foreach ($onclickMatches[1] as $match) {
    $handler = $match[0];
    $offset = $match[1];
    $line = substr_count(substr($htmlContent, 0, $offset), "\n") + 1;
    
    // Extract function name
    if (preg_match('/^([a-zA-Z0-9_]+)\s*\(/', trim($handler), $fnMatch)) {
        $fnName = $fnMatch[1];
        if (strpos($htmlContent, "function $fnName") === false && 
            strpos($jsContent, "function $fnName") === false && 
            strpos($htmlContent, "window.$fnName") === false && 
            strpos($jsContent, "window.$fnName") === false) {
            echo "Line $line: onclick calls undefined function '$fnName()' -> $handler\n";
        }
    }
}

// 3. Check onclick handlers in dashboard.js string templates
preg_match_all('/onclick=\\\\?["\']([a-zA-Z0-9_]+)\s*\(/', $jsContent, $jsOnClickMatches, PREG_OFFSET_CAPTURE);
echo "\n=== CHECKING ONCLICK HANDLERS IN DASHBOARD.JS ===\n";
$checkedJsFns = [];
foreach ($jsOnClickMatches[1] as $match) {
    $fnName = $match[0];
    if (isset($checkedJsFns[$fnName])) continue;
    $checkedJsFns[$fnName] = true;
    
    if (strpos($htmlContent, "function $fnName") === false && 
        strpos($jsContent, "function $fnName") === false && 
        strpos($htmlContent, "window.$fnName") === false && 
        strpos($jsContent, "window.$fnName") === false) {
        echo "dashboard.js string template calls undefined function '$fnName()'\n";
    }
}
