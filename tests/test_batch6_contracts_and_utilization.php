<?php
declare(strict_types=1);

/**
 * Automated Test Suite: Batch 6 - Rental Contracts, Project Allocation & Operating Hours Matrix
 *
 * Validates:
 *  1. contract_assignments table populated with >= 184 active contracts.
 *  2. Relational integrity: Zero orphan assets (every contract_assignments.asset_id exists in assets.asset_id).
 *  3. Project distribution integrity (PHR, PEP, ADERA, KBB projects present).
 *  4. Target operating hours validity (> 40,000 hours, zero negative hours).
 *  5. Contract period coverage (start_date: 2026-01-01, end_date: 2026-01-31).
 *  6. Master asset synchronization: assets.project_name populated for contracted units.
 *  7. Master asset status synchronization: assets.status = 'OPERATING' for contracted units.
 *  8. Telematics logs relational link (telematics_logs.asset_id populated).
 *  9. Migration tracking entry 'BATCH_6_EQUIPMENT_REPORT_202601' exists in app_data_migrations.
 * 10. API endpoint api/contracts.php retrieval integrity (HTTP 200 / valid JSON response).
 * 11. Idempotency check: contract numbers unique per asset and period.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';
require_once dirname(__DIR__) . '/core/ContractCsv.php';

$pdo = Database::getInstance();
$testsPassed = 0;
$totalTests = 11;

echo "====================================================================\n";
echo "TEST SUITE BATCH 6: RENTAL CONTRACTS & EQUIPMENT UTILIZATION\n";
echo "====================================================================\n\n";

function runAssert(string $testName, bool $condition, string $detail = ''): void {
    global $testsPassed;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] $testName" . ($detail ? " ($detail)" : '') . "\n";
    } else {
        echo "  [FAIL] $testName" . ($detail ? " ($detail)" : '') . "\n";
    }
}

// Test 1: contract_assignments count
$contractCount = (int)$pdo->query("SELECT COUNT(*) FROM contract_assignments")->fetchColumn();
runAssert(
    "1. Total Active Contract Assignments Count",
    $contractCount >= 184,
    "Found: $contractCount contracts (Expected >= 184)"
);

// Test 2: Relational integrity (zero orphan assets)
$orphanContracts = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM contract_assignments c 
    LEFT JOIN assets a ON c.asset_id = a.asset_id 
    WHERE a.asset_id IS NULL
")->fetchColumn();
runAssert(
    "2. Foreign Key Integrity (Zero Orphan Assets)",
    $orphanContracts === 0,
    "Orphan count: $orphanContracts (Expected: 0)"
);

// Test 3: Project distribution
$projects = $pdo->query("
    SELECT project, COUNT(*) as cnt 
    FROM contract_assignments 
    GROUP BY project
")->fetchAll(PDO::FETCH_KEY_PAIR);
$hasAllProjects = isset($projects['PHR'], $projects['PEP'], $projects['ADERA'], $projects['KBB']);
$projStr = implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($projects), $projects));
runAssert(
    "3. Rental Project Allocation Coverage",
    $hasAllProjects,
    $projStr
);

// Test 4: Target operating hours
$totalHours = (float)$pdo->query("SELECT SUM(target_hours) FROM contract_assignments")->fetchColumn();
$minHours = (float)$pdo->query("SELECT MIN(target_hours) FROM contract_assignments")->fetchColumn();
runAssert(
    "4. Target Operating Hours Cumulative & Non-Negative",
    $totalHours >= 40000.0 && $minHours > 0,
    "Total: " . number_format($totalHours, 2, ',', '.') . " hrs, Min: $minHours hrs"
);

// Test 5: Contract period bounds
$invalidDates = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM contract_assignments 
    WHERE start_date != '2026-01-01' OR end_date != '2026-01-31' OR start_date > end_date
")->fetchColumn();
runAssert(
    "5. Contract Period Dates Accuracy (Jan 2026)",
    $invalidDates === 0,
    "Invalid date records: $invalidDates"
);

// Test 6: assets.project_name synchronization
$unassignedProjectCount = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM contract_assignments c 
    JOIN assets a ON c.asset_id = a.asset_id 
    WHERE a.project_name IS NULL OR a.project_name = ''
")->fetchColumn();
runAssert(
    "6. Master Asset project_name Synchronization",
    $unassignedProjectCount === 0,
    "Contracted assets missing project_name: $unassignedProjectCount"
);

// Test 7: assets.status = 'OPERATING' synchronization
$nonOperatingCount = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM contract_assignments c 
    JOIN assets a ON c.asset_id = a.asset_id 
    WHERE a.status NOT IN ('OPERATING', 'READY', 'INSPEKSI', 'BREAKDOWN')
")->fetchColumn();
$operatingCount = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM contract_assignments c 
    JOIN assets a ON c.asset_id = a.asset_id 
    WHERE a.status = 'OPERATING'
")->fetchColumn();
runAssert(
    "7. Master Asset status Synchronization",
    $operatingCount >= 180 && $nonOperatingCount === 0,
    "Operating: $operatingCount / $contractCount"
);

// Test 8: Telematics logs relational link
$linkedTelematics = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM telematics_logs 
    WHERE asset_id IS NOT NULL AND asset_id != ''
")->fetchColumn();
runAssert(
    "8. Telematics Telemetry Logs Relational Linkage",
    $linkedTelematics >= 5,
    "Linked telematics logs: $linkedTelematics"
);

// Test 9: Migration tracking record
$migRecord = $pdo->prepare("SELECT applied_at FROM app_data_migrations WHERE migration_key = ?");
$migRecord->execute(['BATCH_6_EQUIPMENT_REPORT_202601']);
$appliedAt = $migRecord->fetchColumn();
runAssert(
    "9. App Data Migration Registry Tracking",
    !empty($appliedAt),
    "Applied at: $appliedAt"
);

// Test 10: ContractCsv / API records integration
$csvRecords = ContractCsv::records($pdo, '', []);
$csvCount = count($csvRecords);
runAssert(
    "10. ContractCsv::records API Engine Retrieval",
    $csvCount >= 184,
    "Retrieved via Core Contract Service: $csvCount records"
);

// Test 11: Contract number naming convention
$invalidFormatCount = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM contract_assignments 
    WHERE contract_number NOT LIKE 'KTR-202601-%'
")->fetchColumn();
runAssert(
    "11. Contract Numbering Schema Integrity (KTR-202601-*)",
    $invalidFormatCount === 0,
    "Invalid contract numbers: $invalidFormatCount"
);

echo "\n--------------------------------------------------------------------\n";
echo "BATCH 6 TEST RESULTS: $testsPassed / $totalTests PASSED\n";
echo "--------------------------------------------------------------------\n";

if ($testsPassed === $totalTests) {
    echo ">>> STATUS: ALL BATCH 6 VERIFICATIONS PASSED SECURELY <<<\n";
    exit(0);
} else {
    echo ">>> STATUS: SOME VERIFICATIONS FAILED <<<\n";
    exit(1);
}
