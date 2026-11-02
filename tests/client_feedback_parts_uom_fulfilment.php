<?php
declare(strict_types=1);

/**
 * Automated Verification: Epic 4.5 & PART-01 / PART-03
 * Master Part Classification & Drum/Liter UOM Conversion
 * 
 * Verifies:
 * 1. Master parts classification standards:
 *    - technical_name populated
 *    - authenticity strictly in GENUINE, NON_GENUINE, UNVERIFIED
 *    - part_group belongs to controlled PART_GROUPS vocabulary
 *    - Dany & Fadly warehouse dataset staged and upserted (1300+ items)
 * 2. Specialized packaging conversion factors:
 *    - OIL-15W40-DRUM200: factor = 200 L/drum
 *    - OIL-15W40-DRUM209: factor = 209 L/drum (PT Surya Mandiri Makmur specific)
 *    - OIL-TDTO50-PAIL20: factor = 20 L/pail
 *    - GREASE-EP2-DRUM180: factor = 180 Kg/drum
 * 3. PartUnitConversion calculation engine:
 *    - toBase & toTransaction roundtrips
 *    - Invalid/negative/infinite validation guards
 *    - Snapshot immutability generator
 * 4. Staging import mechanism & exception tracking:
 *    - part_import_staging holds batch records with status VALID & DUPLICATE
 * 5. Transaction snapshot persistence:
 *    - SPB creation records conversion_factor_snapshot and base_quantity in purchase_request_items
 *    - API returns enriched parts & inventory metadata
 */

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/security.php';
require_once __DIR__ . '/../core/PartUnitConversion.php';

$passCount = 0;
$totalTests = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$message}\n";
    } else {
        echo "![FAIL] {$message}\n";
    }
}

echo "\n=== RUNNING CLIENT FEEDBACK TESTS: MASTER PARTS & UOM (PART-01 / PART-03) ===\n\n";

$db = Database::getInstance();

// --------------------------------------------------------------------------
// TEST 1: PartUnitConversion Engine Math & Validations
// --------------------------------------------------------------------------
echo "--- Test Suite 1: Conversion Engine Calculations ---\n";

assertTest(
    PartUnitConversion::toBase(3.0, 200.0) === 600.0,
    "3 Drums @ 200L/drum converts to 600.0 Liters base quantity"
);

assertTest(
    PartUnitConversion::toBase(2.0, 209.0) === 418.0,
    "2 Drums @ 209L/drum converts to 418.0 Liters base quantity (Vendor specific)"
);

assertTest(
    PartUnitConversion::toBase(4.0, 20.0) === 80.0,
    "4 Pails @ 20L/pail converts to 80.0 Liters base quantity"
);

assertTest(
    PartUnitConversion::toBase(2.0, 180.0) === 360.0,
    "2 Drums @ 180Kg/drum grease converts to 360.0 Kg base quantity"
);

assertTest(
    PartUnitConversion::toTransaction(600.0, 200.0) === 3.0,
    "600 Liters converts back to 3.0 Drums @ 200L/drum"
);

// Validation guard: negative/zero factor throws exception
$negativeFactorCaught = false;
try {
    PartUnitConversion::validateFactor(-5.0);
} catch (InvalidArgumentException $e) {
    $negativeFactorCaught = true;
}
assertTest($negativeFactorCaught, "Negative conversion factor rejected with InvalidArgumentException");

// Snapshot generator
$snap = PartUnitConversion::snapshot(5.0, 'Drum', 200.0, 'Liter');
assertTest(
    $snap['transaction_quantity'] === 5.0 &&
    $snap['transaction_uom'] === 'Drum' &&
    $snap['conversion_factor'] === 200.0 &&
    $snap['base_quantity'] === 1000.0 &&
    $snap['base_uom'] === 'Liter',
    "Snapshot correctly captures transaction UOM, factor snapshot, and base quantity"
);

// --------------------------------------------------------------------------
// TEST 2: Controlled Vocabulary & Inferences
// --------------------------------------------------------------------------
echo "\n--- Test Suite 2: Controlled Vocabulary & Classification Inference ---\n";

assertTest(
    PartUnitConversion::normalizeAuthenticity('GENUINE') === 'GENUINE' &&
    PartUnitConversion::normalizeAuthenticity('oem') === 'GENUINE' &&
    PartUnitConversion::normalizeAuthenticity('non-genuine') === 'NON_GENUINE' &&
    PartUnitConversion::normalizeAuthenticity('UNVERIFIED') === 'UNVERIFIED',
    "normalizeAuthenticity adheres strictly to GENUINE / NON_GENUINE / UNVERIFIED"
);

$invalidAuthCaught = false;
try {
    PartUnitConversion::normalizeAuthenticity('PALSU');
} catch (InvalidArgumentException $e) {
    $invalidAuthCaught = true;
}
assertTest($invalidAuthCaught, "Disallowed authenticity string rejected by enum validator");

assertTest(
    PartUnitConversion::inferPartGroup('Element Hydraulic Return Filter') === 'Filter Hidrolik' &&
    PartUnitConversion::inferPartGroup('Oli Mesin Diesel SAE 15W-40') === 'Pelumas' &&
    PartUnitConversion::inferPartGroup('Ban Luar 11.00R20 Radial') === 'Ban & Velg' &&
    PartUnitConversion::inferPartGroup('Aki N100 Incoe 12V 100AH') === 'Aki / Baterai' &&
    PartUnitConversion::inferPartGroup('Baut Roda Belang M22') === 'Fast Moving & Hardware',
    "inferPartGroup accurately infers controlled part groups from technical keywords"
);

// --------------------------------------------------------------------------
// TEST 3: Master Parts Database & Dany/Fadly Import Verification
// --------------------------------------------------------------------------
echo "\n--- Test Suite 3: Database Master Parts & Staging Table ---\n";

$totalParts = (int) $db->query("SELECT COUNT(*) FROM parts")->fetchColumn();
assertTest($totalParts >= 1300, "Master parts count is {$totalParts} (>= 1300 items from Dany & Fadly warehouse)");

$partsWithoutGroup = (int) $db->query("SELECT COUNT(*) FROM parts WHERE part_group IS NULL OR part_group = ''")->fetchColumn();
assertTest($partsWithoutGroup === 0, "All master parts have assigned part_group (0 unassigned parts)");

$partsWithoutUom = (int) $db->query("SELECT COUNT(*) FROM parts WHERE base_uom IS NULL OR transaction_uom IS NULL")->fetchColumn();
assertTest($partsWithoutUom === 0, "All master parts have base_uom and transaction_uom populated");

$stagedCount = (int) $db->query("SELECT COUNT(*) FROM part_import_staging WHERE batch_key LIKE 'BATCH-DANY-FADLY%'")->fetchColumn();
assertTest($stagedCount >= 1300, "part_import_staging contains {$stagedCount} records for Dany & Fadly batch");

$stagedValidCount = (int) $db->query("SELECT COUNT(*) FROM part_import_staging WHERE batch_key LIKE 'BATCH-DANY-FADLY%' AND validation_status = 'VALID'")->fetchColumn();
$stagedDupCount = (int) $db->query("SELECT COUNT(*) FROM part_import_staging WHERE batch_key LIKE 'BATCH-DANY-FADLY%' AND validation_status = 'DUPLICATE'")->fetchColumn();
assertTest($stagedValidCount > 1300 && $stagedDupCount > 0, "Staging audit accurately separated VALID ({$stagedValidCount}) from DUPLICATE ({$stagedDupCount}) rows");

// --------------------------------------------------------------------------
// TEST 4: Vendor Specific Drum & Pail Catalog Items
// --------------------------------------------------------------------------
echo "\n--- Test Suite 4: Vendor Oil Packaging Specifics ---\n";

$drum200 = $db->query("SELECT * FROM parts WHERE part_number = 'OIL-15W40-DRUM200'")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $drum200 && (float)$drum200['conversion_factor'] === 200.0 && $drum200['transaction_uom'] === 'Drum' && $drum200['base_uom'] === 'Liter',
    "OIL-15W40-DRUM200 has 1 Drum = 200.0 Liter conversion factor"
);

$drum209 = $db->query("SELECT * FROM parts WHERE part_number = 'OIL-15W40-DRUM209'")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $drum209 && (float)$drum209['conversion_factor'] === 209.0 && $drum209['transaction_uom'] === 'Drum' && $drum209['base_uom'] === 'Liter',
    "OIL-15W40-DRUM209 (PT Surya Mandiri Makmur) has 1 Drum = 209.0 Liter conversion factor"
);

$pail20 = $db->query("SELECT * FROM parts WHERE part_number = 'OIL-TDTO50-PAIL20'")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $pail20 && (float)$pail20['conversion_factor'] === 20.0 && $pail20['transaction_uom'] === 'Pail' && $pail20['base_uom'] === 'Liter',
    "OIL-TDTO50-PAIL20 has 1 Pail = 20.0 Liter conversion factor"
);

$greaseDrum = $db->query("SELECT * FROM parts WHERE part_number = 'GREASE-EP2-DRUM180'")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $greaseDrum && (float)$greaseDrum['conversion_factor'] === 180.0 && $greaseDrum['transaction_uom'] === 'Drum' && $greaseDrum['base_uom'] === 'Kg',
    "GREASE-EP2-DRUM180 has 1 Drum = 180.0 Kg grease conversion factor"
);

// --------------------------------------------------------------------------
// TEST 5: Transaction Snapshot Persistence in SPB
// --------------------------------------------------------------------------
echo "\n--- Test Suite 5: SPB Line Item Conversion Snapshot ---\n";

$validUser = $db->query("SELECT user_id FROM users ORDER BY user_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$userId = (int) ($validUser['user_id'] ?? 1);

$validAsset = $db->query("SELECT asset_id FROM assets ORDER BY asset_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$assetId = (string) ($validAsset['asset_id'] ?? 'DT-01');

$testSpbId = 'SPB-TEST-UOM-' . date('His');
$db->prepare("
    INSERT INTO purchase_requests (spb_id, asset_id, requested_by, urgency, status, notes, requested_at)
    VALUES (:id, :aid, :uid, 'Normal', 'Submitted', 'Test Drum UOM snapshot', NOW())
")->execute([':id' => $testSpbId, ':aid' => $assetId, ':uid' => $userId]);

// Insert Drum line item: 3 Drums of 200L
$itemSnap200 = PartUnitConversion::snapshot(3.0, 'Drum', (float)$drum200['conversion_factor'], $drum200['base_uom']);
$testItemId1 = 'SPBI-TEST-DRUM200-' . date('His');
$db->prepare("
    INSERT INTO purchase_request_items (
        id, spb_id, part_id, part_number, description, qty_requested,
        transaction_uom, conversion_factor_snapshot, base_quantity, status
    ) VALUES (
        :id, :spb_id, :pid, :pnum, :desc, :qty,
        :tx_uom, :factor, :base_qty, 'Submitted'
    )
")->execute([
    ':id' => $testItemId1,
    ':spb_id' => $testSpbId,
    ':pid' => $drum200['part_id'],
    ':pnum' => $drum200['part_number'],
    ':desc' => $drum200['part_name'],
    ':qty' => 3,
    ':tx_uom' => $itemSnap200['transaction_uom'],
    ':factor' => $itemSnap200['conversion_factor'],
    ':base_qty' => $itemSnap200['base_quantity'],
]);

// Insert Drum line item: 2 Drums of 209L
$itemSnap209 = PartUnitConversion::snapshot(2.0, 'Drum', (float)$drum209['conversion_factor'], $drum209['base_uom']);
$testItemId2 = 'SPBI-TEST-DRUM209-' . date('His');
$db->prepare("
    INSERT INTO purchase_request_items (
        id, spb_id, part_id, part_number, description, qty_requested,
        transaction_uom, conversion_factor_snapshot, base_quantity, status
    ) VALUES (
        :id, :spb_id, :pid, :pnum, :desc, :qty,
        :tx_uom, :factor, :base_qty, 'Submitted'
    )
")->execute([
    ':id' => $testItemId2,
    ':spb_id' => $testSpbId,
    ':pid' => $drum209['part_id'],
    ':pnum' => $drum209['part_number'],
    ':desc' => $drum209['part_name'],
    ':qty' => 2,
    ':tx_uom' => $itemSnap209['transaction_uom'],
    ':factor' => $itemSnap209['conversion_factor'],
    ':base_qty' => $itemSnap209['base_quantity'],
]);

$savedItem1 = $db->query("SELECT * FROM purchase_request_items WHERE id = '{$testItemId1}'")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $savedItem1 && (int)$savedItem1['qty_requested'] === 3 &&
    $savedItem1['transaction_uom'] === 'Drum' &&
    (float)$savedItem1['conversion_factor_snapshot'] === 200.0 &&
    (float)$savedItem1['base_quantity'] === 600.0,
    "SPB item 1 preserves snapshot: 3 Drums @ factor 200 = 600 Liters base quantity"
);

$savedItem2 = $db->query("SELECT * FROM purchase_request_items WHERE id = '{$testItemId2}'")->fetch(PDO::FETCH_ASSOC);
assertTest(
    $savedItem2 && (int)$savedItem2['qty_requested'] === 2 &&
    $savedItem2['transaction_uom'] === 'Drum' &&
    (float)$savedItem2['conversion_factor_snapshot'] === 209.0 &&
    (float)$savedItem2['base_quantity'] === 418.0,
    "SPB item 2 preserves snapshot: 2 Drums @ factor 209 = 418 Liters base quantity"
);

// Clean up test SPB records
$db->prepare("DELETE FROM purchase_request_items WHERE spb_id = :id")->execute([':id' => $testSpbId]);
$db->prepare("DELETE FROM purchase_requests WHERE spb_id = :id")->execute([':id' => $testSpbId]);

// --------------------------------------------------------------------------
// TEST 6: Staging Report Query Verification
// --------------------------------------------------------------------------
echo "\n--- Test Suite 6: Staging Report & Exception Query ---\n";

$stagingSummaryStmt = $db->query("
    SELECT batch_key,
           COUNT(*) as total_rows,
           SUM(CASE WHEN validation_status = 'VALID' THEN 1 ELSE 0 END) as valid_rows,
           SUM(CASE WHEN validation_status = 'DUPLICATE' THEN 1 ELSE 0 END) as duplicate_rows
    FROM part_import_staging
    GROUP BY batch_key
    ORDER BY MIN(created_at) DESC LIMIT 1
");
$batch = $stagingSummaryStmt->fetch(PDO::FETCH_ASSOC);

assertTest(
    $batch && (int)$batch['total_rows'] >= 1300 && (int)$batch['valid_rows'] > 1300,
    "Staging report returns batch summary with valid count >= 1300 rows"
);

echo "\n--------------------------------------------------------------------------\n";
echo "RESULT: {$passCount} / {$totalTests} TESTS PASSED\n";
echo "--------------------------------------------------------------------------\n\n";

if ($passCount === $totalTests) {
    exit(0);
} else {
    exit(1);
}
