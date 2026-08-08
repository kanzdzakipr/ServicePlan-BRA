<?php
$sqlPath = __DIR__ . '/u646470441_ServicePlanBRA.sql';
$content = file_get_contents($sqlPath);

// Clean up line 183 trailing semicolon + comma
$content = preg_replace('/;\s*,\s*\n\(\'BRA-06\'/', ",\n('BRA-06'", $content);
$content = preg_replace('/\);\s*,\s*\n/', ",\n", $content);

file_put_contents($sqlPath, $content);
echo "Successfully rebuilt SQL assets INSERT statements!\n";
