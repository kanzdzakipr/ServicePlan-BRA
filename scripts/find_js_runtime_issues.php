<?php
$htmlPath = __DIR__ . '/../dashboard.html';
$jsPath = __DIR__ . '/../scripts/dashboard.js';

$html = file_get_contents($htmlPath);
$js = file_get_contents($jsPath);

$allContent = $html . "\n" . $js;

// Collect all defined function names
preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(|window\.([a-zA-Z0-9_]+)\s*=\s*/', $allContent, $definedMatches);
$definedFns = array_filter(array_merge($definedMatches[1], $definedMatches[2]));
$definedFnsSet = array_flip($definedFns);

// Add standard browser globals
$browserGlobals = ['alert', 'confirm', 'prompt', 'fetch', 'setTimeout', 'setInterval', 'clearTimeout', 'clearInterval', 'parseInt', 'parseFloat', 'encodeURIComponent', 'decodeURIComponent', 'btoa', 'atob', 'escapeHtml'];
foreach ($browserGlobals as $bg) $definedFnsSet[$bg] = true;

echo "Total defined JS functions: " . count($definedFnsSet) . "\n";

// Audit function calls in dashboard.html
preg_match_all('/\b([a-zA-Z0-9_]+)\s*\(/', $html, $htmlCalls, PREG_OFFSET_CAPTURE);
$missingInHtml = [];
foreach ($htmlCalls[1] as $call) {
    $fn = $call[0];
    $offset = $call[1];
    $line = substr_count(substr($html, 0, $offset), "\n") + 1;
    
    // Ignore keywords
    if (in_array($fn, ['if', 'while', 'for', 'switch', 'catch', 'return', 'typeof', 'console', 'Math', 'Date', 'JSON', 'Object', 'Array', 'String', 'Number', 'Boolean', 'RegExp', 'Promise', 'document', 'window', 'element', 'e', 'evt', 'item', 'a', 'b', 'x', 'y', 't', 'r', 'val', 'attr', 'key', 'prop', 'cb', 'fn', 'res', 'err', 'opt', 'node', 'target', 'parent', 'self', 'top', 'location', 'history', 'navigator', 'screen', 'localStorage', 'sessionStorage', 'XMLHttpRequest', 'Event', 'CustomEvent', 'FileReader', 'Image', 'Audio', 'Blob', 'File', 'FormData', 'Headers', 'Request', 'Response', 'URL', 'URLSearchParams', 'WebSocket', 'Worker', 'MutationObserver', 'IntersectionObserver', 'ResizeObserver', 'Performance', 'PerformanceObserver'])) continue;
    
    if (!isset($definedFnsSet[$fn])) {
        $missingInHtml[] = "Line $line: Called '$fn()' which is NOT defined!";
    }
}

echo "\n=== UNDEFINED FUNCTION CALLS IN DASHBOARD.HTML ===\n";
$missingInHtmlUnique = array_unique($missingInHtml);
foreach ($missingInHtmlUnique as $m) echo "  $m\n";

// Audit function calls in dashboard.js
preg_match_all('/\b([a-zA-Z0-9_]+)\s*\(/', $js, $jsCalls, PREG_OFFSET_CAPTURE);
$missingInJs = [];
foreach ($jsCalls[1] as $call) {
    $fn = $call[0];
    $offset = $call[1];
    $line = substr_count(substr($js, 0, $offset), "\n") + 1;
    
    if (in_array($fn, ['if', 'while', 'for', 'switch', 'catch', 'return', 'typeof', 'console', 'Math', 'Date', 'JSON', 'Object', 'Array', 'String', 'Number', 'Boolean', 'RegExp', 'Promise', 'document', 'window', 'element', 'e', 'evt', 'item', 'a', 'b', 'x', 'y', 't', 'r', 'val', 'attr', 'key', 'prop', 'cb', 'fn', 'res', 'err', 'opt', 'node', 'target', 'parent', 'self', 'top', 'location', 'history', 'navigator', 'screen', 'localStorage', 'sessionStorage', 'XMLHttpRequest', 'Event', 'CustomEvent', 'FileReader', 'Image', 'Audio', 'Blob', 'File', 'FormData', 'Headers', 'Request', 'Response', 'URL', 'URLSearchParams', 'WebSocket', 'Worker', 'MutationObserver', 'IntersectionObserver', 'ResizeObserver', 'Performance', 'PerformanceObserver', 'Leaflet', 'L', 'Chart', 'XLSX', 'pdfjsLib', 'Tesseract', 'JSZip'])) continue;
    
    if (!isset($definedFnsSet[$fn])) {
        $missingInJs[] = "dashboard.js [Line $line]: Called '$fn()' which is NOT defined!";
    }
}

echo "\n=== UNDEFINED FUNCTION CALLS IN DASHBOARD.JS ===\n";
$missingInJsUnique = array_unique($missingInJs);
foreach ($missingInJsUnique as $m) echo "  $m\n";
