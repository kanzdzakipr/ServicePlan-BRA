<?php
$sqlPath = __DIR__ . '/u646470441_ServicePlanBRA.sql';
$sql = file_get_contents($sqlPath);

$sql = str_replace(");,\n", ";\nINSERT INTO `assets` VALUES ", $sql);
$sql = str_replace(");,", ";\nINSERT INTO `assets` VALUES ", $sql);

file_put_contents($sqlPath, $sql);
echo "SQL syntax cleaned successfully!\n";
