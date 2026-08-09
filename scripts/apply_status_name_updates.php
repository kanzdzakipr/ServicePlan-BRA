<?php
$filesToUpdate = [
    __DIR__ . '/../dashboard.view.php',
    __DIR__ . '/../scripts/dashboard.js',
    __DIR__ . '/../scripts/schema.sql',
    __DIR__ . '/../scripts/u646470441_ServicePlanBRA.sql',
];

$replacements = [
    'ACCIDENT_HOLD' => 'ACCIDENT HOLD',
    'CAPA_Pending' => 'CAPA Pending',
];

foreach ($filesToUpdate as $filePath) {
    if (!file_exists($filePath)) continue;
    $content = file_get_contents($filePath);
    $origContent = $content;

    foreach ($replacements as $old => $new) {
        $content = str_replace($old, $new, $content);
    }

    if ($content !== $origContent) {
        file_put_contents($filePath, $content);
        echo "Updated " . basename($filePath) . "\n";
    } else {
        echo "No changes needed for " . basename($filePath) . "\n";
    }
}

// Now update local MySQL database directly
$mysqli = new mysqli('localhost', 'root', '', 'u646470441_ServicePlanBRA');
if ($mysqli->connect_error) {
    echo "MySQL Connect Error: " . $mysqli->connect_error . "\n";
    exit;
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

// 1. Expand Enum on assets to include both temporary ACCIDENT_HOLD and ACCIDENT HOLD
$mysqli->query("ALTER TABLE assets MODIFY COLUMN `status` ENUM('READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'ACCIDENT HOLD', 'INACTIVE') DEFAULT 'READY'");

// 2. Update rows
$mysqli->query("UPDATE assets SET status = 'ACCIDENT HOLD' WHERE status = 'ACCIDENT_HOLD'");

// 3. Finalize Enum without underscore
$mysqli->query("ALTER TABLE assets MODIFY COLUMN `status` ENUM('READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT HOLD', 'INACTIVE') DEFAULT 'READY'");

// Update accidents table status ENUM and values
$mysqli->query("ALTER TABLE accidents MODIFY COLUMN `status` ENUM('Reported', 'Investigating', 'CAPA_Pending', 'CAPA Pending', 'Closed') DEFAULT 'Reported'");
$mysqli->query("UPDATE accidents SET status = 'CAPA Pending' WHERE status = 'CAPA_Pending'");
$mysqli->query("ALTER TABLE accidents MODIFY COLUMN `status` ENUM('Reported', 'Investigating', 'CAPA Pending', 'Closed') DEFAULT 'Reported'");

$mysqli->query("UPDATE accidents SET corrective_action = REPLACE(corrective_action, 'ACCIDENT_HOLD', 'ACCIDENT HOLD')");

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "Local MySQL database successfully updated for ACCIDENT HOLD and CAPA Pending!\n";
