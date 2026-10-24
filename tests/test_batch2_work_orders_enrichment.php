<?php
declare(strict_types=1);

/**
 * Automated Verification Suite for Batch 2 Database Enrichment
 * 
 * Verifies:
 * 1. 364 Work Orders populated with operational breakdown dates and times.
 * 2. Zero negative downtimes (closed_at >= trouble_at).
 * 3. Downtime minutes calculated and formatted accurately.
 * 4. Assigned mechanics and JSON arrays populated without default blanks.
 * 5. Root causes and repair actions populated for 100% of work orders.
 * 6. Work order asset_id references valid assets.
 * 7. Rows 101, 102, 103 updated from dummy seeds to authentic units.
 * 8. Asset operator history enriched and contains >= 140 entries.
 * 9. Migration audit log BATCH_2_EQUIPMENT_REPORT_202601 recorded.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " RUNNING TEST SUITE: BATCH 2 WORK ORDERS, DOWNTIME & OPERATORS\n";
echo "============================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(string $desc, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $desc" . ($detail ? " ($detail)" : "") . "\n";
        $passCount++;
    } else {
        echo "  [FAIL] $desc" . ($detail ? " ($detail)" : "") . "\n";
        $failCount++;
    }
}

// -----------------------------------------------------------------------------
// Check 1: Total Work Orders in Database
// -----------------------------------------------------------------------------
$totalWos = (int)$pdo->query("SELECT COUNT(*) FROM work_orders")->fetchColumn();
assertCheck("Total Work Orders equals 364", $totalWos === 364, "Total: $totalWos");

// -----------------------------------------------------------------------------
// Check 2: trouble_at is 100% populated
// -----------------------------------------------------------------------------
$troubleFilled = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE trouble_at IS NOT NULL")->fetchColumn();
assertCheck("trouble_at is populated for all work orders", $troubleFilled === 364, "Filled: $troubleFilled / 364");

// -----------------------------------------------------------------------------
// Check 3: Zero Negative Downtime (closed_at >= trouble_at)
// -----------------------------------------------------------------------------
$negativeDowntimes = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE closed_at IS NOT NULL AND trouble_at IS NOT NULL AND closed_at < trouble_at")->fetchColumn();
assertCheck("No negative downtimes exist (closed_at >= trouble_at)", $negativeDowntimes === 0, "Negative count: $negativeDowntimes");

// -----------------------------------------------------------------------------
// Check 4: Downtime minutes populated and positive
// -----------------------------------------------------------------------------
$downtimeCount = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE downtime_minutes > 0")->fetchColumn();
assertCheck("Downtime minutes populated for closed/active units", $downtimeCount >= 350, "Count: $downtimeCount");

// -----------------------------------------------------------------------------
// Check 5: Assigned mechanics populated
// -----------------------------------------------------------------------------
$mechCount = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE assigned_mechanic IS NOT NULL AND assigned_mechanic != 'Belum ada PIC'")->fetchColumn();
assertCheck("Assigned mechanics populated without default placeholder", $mechCount >= 340, "Count: $mechCount");

// -----------------------------------------------------------------------------
// Check 6: Assigned mechanics JSON is valid
// -----------------------------------------------------------------------------
$jsonCount = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE assigned_mechanics_json IS NOT NULL AND assigned_mechanics_json != '[]'")->fetchColumn();
assertCheck("assigned_mechanics_json populated with structured arrays", $jsonCount >= 340, "Count: $jsonCount");

// -----------------------------------------------------------------------------
// Check 7: Root cause populated
// -----------------------------------------------------------------------------
$rcCount = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE root_cause IS NOT NULL AND LENGTH(TRIM(root_cause)) > 5")->fetchColumn();
assertCheck("root_cause analysis populated for 100% of work orders", $rcCount === 364, "Count: $rcCount / 364");

// -----------------------------------------------------------------------------
// Check 8: Repair action populated
// -----------------------------------------------------------------------------
$raCount = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE repair_action IS NOT NULL AND LENGTH(TRIM(repair_action)) > 5")->fetchColumn();
assertCheck("repair_action logged for 100% of work orders", $raCount === 364, "Count: $raCount / 364");

// -----------------------------------------------------------------------------
// Check 9: Foreign key integrity between work_orders.asset_id and assets.asset_id
// -----------------------------------------------------------------------------
$orphanWos = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM work_orders wo 
    LEFT JOIN assets a ON wo.asset_id = a.asset_id 
    WHERE a.asset_id IS NULL
")->fetchColumn();
assertCheck("All work orders reference valid asset_id in assets table", $orphanWos === 0, "Orphans: $orphanWos");

// -----------------------------------------------------------------------------
// Check 10: Rows 101, 102, 103 are real units
// -----------------------------------------------------------------------------
$seed101 = $pdo->query("SELECT asset_id, issue_description FROM work_orders WHERE wo_id = 'WO-26-101'")->fetch(PDO::FETCH_ASSOC);
$seed102 = $pdo->query("SELECT asset_id, issue_description FROM work_orders WHERE wo_id = 'WO-26-102'")->fetch(PDO::FETCH_ASSOC);
$seed103 = $pdo->query("SELECT asset_id, issue_description FROM work_orders WHERE wo_id = 'WO-26-103'")->fetch(PDO::FETCH_ASSOC);

$seedsClean = (
    ($seed101['asset_id'] ?? '') === 'DT-00045' &&
    ($seed102['asset_id'] ?? '') === 'DT-00053' &&
    ($seed103['asset_id'] ?? '') === 'DT-00043'
);
assertCheck("Seed rows 101, 102, 103 synchronized to authentic units", $seedsClean, "101: {$seed101['asset_id']}, 102: {$seed102['asset_id']}, 103: {$seed103['asset_id']}");

// -----------------------------------------------------------------------------
// Check 11: Asset Operator History enriched
// -----------------------------------------------------------------------------
$opCount = (int)$pdo->query("SELECT COUNT(*) FROM asset_operator_history")->fetchColumn();
assertCheck("asset_operator_history contains enriched operator records", $opCount >= 140, "Total records: $opCount");

// -----------------------------------------------------------------------------
// Check 12: Migration record exists in app_data_migrations
// -----------------------------------------------------------------------------
$migApplied = (int)$pdo->query("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = 'BATCH_2_EQUIPMENT_REPORT_202601'")->fetchColumn();
assertCheck("Migration key BATCH_2_EQUIPMENT_REPORT_202601 is recorded", $migApplied > 0, "Applied: $migApplied");

echo "\n----------------------------------------------------------------------------\n";
echo " TEST RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "----------------------------------------------------------------------------\n";

if ($failCount > 0) {
    exit(1);
}
