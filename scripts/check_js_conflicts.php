<?php
$htmlPath = __DIR__ . '/../dashboard.html';
$jsPath = __DIR__ . '/../scripts/dashboard.js';

$htmlContent = file_get_contents($htmlPath);
$jsContent = file_get_contents($jsPath);

// 1. Extract functions from dashboard.js
preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $jsContent, $jsFuncs);
preg_match_all('/window\.([a-zA-Z0-9_]+)\s*=\s*function/', $jsContent, $jsWinFuncs);

// 2. Extract functions from inline scripts in dashboard.html
preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $htmlContent, $htmlFuncs);
preg_match_all('/window\.([a-zA-Z0-9_]+)\s*=\s*function/', $htmlContent, $htmlWinFuncs);

$jsFuncNames = array_merge($jsFuncs[1], $jsWinFuncs[1]);
$htmlFuncNames = array_merge($htmlFuncs[1], $htmlWinFuncs[1]);

$jsDuplicates = array_diff_assoc($jsFuncNames, array_unique($jsFuncNames));
$htmlDuplicates = array_diff_assoc($htmlFuncNames, array_unique($htmlFuncNames));
$crossDuplicates = array_intersect($jsFuncNames, $htmlFuncNames);

echo "Duplicate function names in dashboard.js:\n";
print_r(array_unique($jsDuplicates));

echo "\nDuplicate function names in inline scripts of dashboard.html:\n";
print_r(array_unique($htmlDuplicates));

echo "\nFunctions defined in BOTH dashboard.js AND inline in dashboard.html (Potential Conflicts):\n";
print_r(array_unique($crossDuplicates));
