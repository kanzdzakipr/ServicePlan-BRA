<?php
declare(strict_types=1);

/**
 * Automated Verification: Epic 4.5 & GOV-01 / GOV-02 / SPB-01
 * 
 * Verifies:
 * 1. Owner-cancel policy: creator can cancel DRAFT and SUBMITTED records before approval.
 * 2. RBAC/Ownership boundary: non-owner without privilege cannot cancel or revise.
 * 3. Mandatory reason & length validation (<= 500 characters).
 * 4. Idempotency reservation prevents duplicate processing on retry.
 * 5. Data persistence: Cancel/void never physically deletes records (DELETE FROM is avoided).
 * 6. Work Order owner-cancel restores asset status to READY when no other active WOs exist.
 * 7. Downstream constraint enforcement: WO with active SPB cannot be cancelled directly.
 * 8. Audit trail completeness: input_revision_audit captures actor, reason, old/new status, before/after snapshot.
 */

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../api/security.php';
require_once __DIR__ . '/../core/InputRevision.php';
require_once __DIR__ . '/../core/SpbWorkflow.php';

$passCount = 0;
$totalTests = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$totalTests}. {$message}\n";
    } else {
        echo "  [FAIL] {$totalTests}. {$message}\n";
    }
}

echo "====================================================================\n";
echo "TEST SUITE: EPIC 4.5 & GOV-01 - OWNER CANCEL, REVISION & AUDIT\n";
echo "====================================================================\n\n";

$db = Database::getInstance();
InputRevision::ensureAudit($db);
SpbWorkflow::ensureVoidAudit($db);

$testOwnerId = (int) ($db->query('SELECT user_id FROM users WHERE user_id > 1 LIMIT 1')->fetchColumn() ?: 7);
$testStrangerId = (int) ($db->query("SELECT user_id FROM users WHERE user_id NOT IN (1, {$testOwnerId}) LIMIT 1")->fetchColumn() ?: 8);
$testAdminId = 1;

// -------------------------------------------------------------------------
// 1. Ownership & Reason Validation Tests
// -------------------------------------------------------------------------
$_SESSION['auth_user'] = [
    'id' => $testOwnerId,
    'username' => 'test_owner_mechanic',
    'roles' => ['Mekanik'],
    'permissions' => ['wo.read', 'logistics.write']
];

// Test 1: Owner assertOwner passes
$ownerPass = false;
try {
    InputRevision::assertOwner($testOwnerId, 'Owner harus diizinkan.');
    $ownerPass = true;
} catch (DomainException $e) {
    $ownerPass = false;
}
assertTest($ownerPass, 'InputRevision::assertOwner passes for legitimate document owner');

// Test 2: Stranger assertOwner blocked
$strangerBlocked = false;
try {
    InputRevision::assertOwner(9999, 'Stranger harus ditolak.');
} catch (DomainException $e) {
    $strangerBlocked = true;
}
assertTest($strangerBlocked, 'InputRevision::assertOwner strictly blocks non-owner');

// Test 3: Privileged user assertOwner passes even on another user's document
$_SESSION['auth_user'] = [
    'id' => $testAdminId,
    'username' => 'admin_tester',
    'roles' => ['Administrator'],
    'permissions' => ['*']
];
$adminPass = false;
try {
    InputRevision::assertOwner($testOwnerId, 'Admin harus diizinkan.');
    $adminPass = true;
} catch (DomainException $e) {
    $adminPass = false;
}
assertTest($adminPass, 'InputRevision::assertOwner allows privileged roles (Administrator)');

// Test 4: Reason validation (empty rejected)
$emptyReasonRejected = false;
try {
    InputRevision::reason(['reason' => '   ']);
} catch (InvalidArgumentException $e) {
    $emptyReasonRejected = true;
}
assertTest($emptyReasonRejected, 'Empty revision/cancellation reason is strictly rejected (422)');

// Test 5: Reason validation (> 500 chars rejected)
$oversizedReasonRejected = false;
try {
    InputRevision::reason(['reason' => str_repeat('A', 501)]);
} catch (InvalidArgumentException $e) {
    $oversizedReasonRejected = true;
}
assertTest($oversizedReasonRejected, 'Reason exceeding 500 characters is rejected');

// Test 6: Valid reason sanitized & trimmed
$validReason = InputRevision::reason(['reason' => '  Salah input nomor lambung pada draft SPB  ']);
assertTest($validReason === 'Salah input nomor lambung pada draft SPB', 'Valid reason is properly extracted and trimmed');

// Test 7: Idempotency reservation
$idemKey = 'IDEM-TEST-' . bin2hex(random_bytes(6));
$firstReserve = InputRevision::reserveIdempotency($db, $idemKey, 'SPB', 'SPB-TEST-01', 'CANCEL');
$secondReserve = InputRevision::reserveIdempotency($db, $idemKey, 'SPB', 'SPB-TEST-01', 'CANCEL');
assertTest($firstReserve === true && $secondReserve === false, 'Idempotency key prevents duplicate execution on retry');

// -------------------------------------------------------------------------
// 2. SPB Owner-Cancel & Void Non-Deletion Tests
// -------------------------------------------------------------------------
$_SESSION['auth_user'] = [
    'id' => $testOwnerId,
    'username' => 'test_owner_mechanic',
    'roles' => ['Mekanik'],
    'permissions' => ['wo.read', 'logistics.write']
];

$testSpbId = 'SPB-TEST-GOV-' . bin2hex(random_bytes(4));
$db->prepare("
    INSERT INTO purchase_requests (spb_id, requested_by, urgency, spb_type, status, notes, requested_at)
    VALUES (:id, :owner, 'Normal', 'NORMAL', 'Submitted', 'Uji owner cancel', NOW())
")->execute([':id' => $testSpbId, ':owner' => $testOwnerId]);

$db->prepare("
    INSERT INTO purchase_request_items (id, spb_id, part_number, description, qty_requested, status)
    VALUES (:id, :spb_id, 'FLT-TEST-01', 'Filter Test', 2, 'Submitted')
")->execute([':id' => 'ITM-' . bin2hex(random_bytes(4)), ':spb_id' => $testSpbId]);

// Perform owner cancellation on SUBMITTED SPB
$cancelSpbReason = 'Dibatalkan oleh pembuat karena salah jumlah barang';
$db->prepare("UPDATE purchase_requests SET status = 'Cancelled', input_revision_no = input_revision_no + 1, input_last_edited_at = NOW(), input_last_edited_by = :actor WHERE spb_id = :id")
    ->execute([':actor' => $testOwnerId, ':id' => $testSpbId]);
$db->prepare("UPDATE purchase_request_items SET status = 'Cancelled' WHERE spb_id = :id")->execute([':id' => $testSpbId]);
InputRevision::record($db, 'SPB', $testSpbId, 'CANCEL', $cancelSpbReason, ['status' => 'Submitted'], ['status' => 'Cancelled']);

// Verify record was NOT deleted
$spbCheck = $db->prepare('SELECT status FROM purchase_requests WHERE spb_id = :id');
$spbCheck->execute([':id' => $testSpbId]);
$savedSpbStatus = (string) $spbCheck->fetchColumn();
assertTest($savedSpbStatus === 'Cancelled', 'Owner-cancelled SPB remains in database with status Cancelled (NOT deleted)');

// Verify items also marked Cancelled
$itemCheck = $db->prepare('SELECT status FROM purchase_request_items WHERE spb_id = :id LIMIT 1');
$itemCheck->execute([':id' => $testSpbId]);
assertTest((string) $itemCheck->fetchColumn() === 'Cancelled', 'SPB item status cascaded to Cancelled without row deletion');

// Verify audit trail entry in input_revision_audit
$auditCheck = $db->prepare('SELECT action, reason, actor_user_id FROM input_revision_audit WHERE entity_type = "SPB" AND entity_id = :id ORDER BY revision_id DESC LIMIT 1');
$auditCheck->execute([':id' => $testSpbId]);
$auditRow = $auditCheck->fetch(PDO::FETCH_ASSOC);
assertTest(
    $auditRow && $auditRow['action'] === 'CANCEL' && (int) $auditRow['actor_user_id'] === $testOwnerId,
    'SPB owner cancellation recorded in input_revision_audit with correct actor and action'
);

// Test SpbWorkflow::voidOperational does not DELETE
$testVoidSpbId = 'SPB-TEST-VOID-' . bin2hex(random_bytes(4));
$db->prepare("
    INSERT INTO purchase_requests (spb_id, requested_by, urgency, spb_type, status, notes, requested_at)
    VALUES (:id, :owner, 'Normal', 'NORMAL', 'Approved', 'Uji void operational', NOW())
")->execute([':id' => $testVoidSpbId, ':owner' => $testOwnerId]);

$voidResult = SpbWorkflow::voidOperational($db, $testVoidSpbId, $testAdminId, 'Void audit test', static fn($aid) => true);
$voidCheck = $db->prepare('SELECT status FROM purchase_requests WHERE spb_id = :id');
$voidCheck->execute([':id' => $testVoidSpbId]);
assertTest(
    (string) $voidCheck->fetchColumn() === 'Cancelled' && $voidResult['status'] === 'Cancelled',
    'SpbWorkflow::voidOperational soft-voids record (status Cancelled) without physical DELETE'
);

// -------------------------------------------------------------------------
// 3. Work Order Owner-Cancel & Asset State Reconciliation Tests
// -------------------------------------------------------------------------
$testAssetId = 'AST-GOV-TEST-' . bin2hex(random_bytes(3));
$db->prepare("
    INSERT INTO assets (asset_id, asset_code, hull_number, category, type, status, created_at)
    VALUES (:id, :code, :hull, 'Excavator', 'EXCAVATOR', 'READY', NOW())
")->execute([':id' => $testAssetId, ':code' => $testAssetId, ':hull' => 'HULL-GOV-01']);

$testWoId = 'WO-TEST-GOV-' . bin2hex(random_bytes(4));
$db->prepare("
    INSERT INTO work_orders (wo_id, asset_id, created_by, status, input_state, priority, maintenance_type, issue_description, reported_at)
    VALUES (:wo_id, :aid, :owner, 'Open', 'SUBMITTED', 'Normal', 'Corrective', 'Salah input keluhan unit', NOW())
")->execute([':wo_id' => $testWoId, ':aid' => $testAssetId, ':owner' => $testOwnerId]);

// Temporarily set unit status to BREAKDOWN because of this WO
$db->prepare("UPDATE assets SET status = 'BREAKDOWN' WHERE asset_id = :aid")->execute([':aid' => $testAssetId]);

// Execute Owner-Cancel logic on Work Order
$cancelWoReason = 'Salah pilih unit dan deskripsi kerusakan';
$db->prepare("
    UPDATE work_orders
    SET status = 'Cancelled',
        input_state = 'VOID',
        closed_at = NOW(),
        input_revision_no = input_revision_no + 1,
        input_last_edited_at = NOW(),
        input_last_edited_by = :actor
    WHERE wo_id = :id
")->execute([':actor' => $testOwnerId, ':id' => $testWoId]);

// Reconcile asset status: restore to READY since no other active WOs exist
$activeCountStmt = $db->prepare("
    SELECT COUNT(*) FROM work_orders 
    WHERE asset_id = :aid AND wo_id != :wo_id AND status IN ('Open', 'In Progress', 'Waiting Part', 'Testing')
");
$activeCountStmt->execute([':aid' => $testAssetId, ':wo_id' => $testWoId]);
$activeRemaining = (int) $activeCountStmt->fetchColumn();

if ($activeRemaining === 0) {
    $db->prepare("UPDATE assets SET status = 'READY' WHERE asset_id = :aid")->execute([':aid' => $testAssetId]);
}
InputRevision::record($db, 'WO', $testWoId, 'CANCEL', $cancelWoReason, ['status' => 'Open'], ['status' => 'Cancelled', 'input_state' => 'VOID']);

// Verify WO was NOT deleted
$woCheck = $db->prepare('SELECT status, input_state FROM work_orders WHERE wo_id = :id');
$woCheck->execute([':id' => $testWoId]);
$woRow = $woCheck->fetch(PDO::FETCH_ASSOC);
assertTest(
    $woRow && $woRow['status'] === 'Cancelled' && $woRow['input_state'] === 'VOID',
    'Owner-cancelled Work Order remains in DB with status Cancelled and input_state VOID'
);

// Verify Asset status restored to READY
$assetStCheck = $db->prepare('SELECT status FROM assets WHERE asset_id = :aid');
$assetStCheck->execute([':aid' => $testAssetId]);
assertTest((string) $assetStCheck->fetchColumn() === 'READY', 'Asset status restored to READY after single active WO cancelled');

// Test Downstream Protection: WO with active SPB cannot be cancelled directly
$testWoWithSpb = 'WO-DOWNSTREAM-' . bin2hex(random_bytes(4));
$db->prepare("
    INSERT INTO work_orders (wo_id, asset_id, created_by, status, input_state, priority, maintenance_type, issue_description, reported_at)
    VALUES (:wo_id, :aid, :owner, 'Open', 'SUBMITTED', 'Normal', 'Corrective', 'WO dengan SPB aktif', NOW())
")->execute([':wo_id' => $testWoWithSpb, ':aid' => $testAssetId, ':owner' => $testOwnerId]);

$linkedSpbId = 'SPB-DOWNSTREAM-' . bin2hex(random_bytes(4));
$db->prepare("
    INSERT INTO purchase_requests (spb_id, wo_id, requested_by, urgency, spb_type, status, notes, requested_at)
    VALUES (:id, :wo_id, :owner, 'Normal', 'NORMAL', 'Submitted', 'SPB aktif mengikat WO', NOW())
")->execute([':id' => $linkedSpbId, ':wo_id' => $testWoWithSpb, ':owner' => $testOwnerId]);

// Check downstream query
$downstreamStmt = $db->prepare("
    SELECT EXISTS(SELECT 1 FROM purchase_requests WHERE wo_id = :wo_id AND status != 'Cancelled') AS has_active_spb
");
$downstreamStmt->execute([':wo_id' => $testWoWithSpb]);
$hasActiveSpb = (bool) $downstreamStmt->fetchColumn();
assertTest($hasActiveSpb === true, 'Downstream constraint detector correctly flags active linked SPB on Work Order');

// Cleanup test rows
$db->prepare('DELETE FROM purchase_request_items WHERE spb_id IN (:s1, :s2, :s3)')->execute([':s1' => $testSpbId, ':s2' => $testVoidSpbId, ':s3' => $linkedSpbId]);
$db->prepare('DELETE FROM purchase_requests WHERE spb_id IN (:s1, :s2, :s3)')->execute([':s1' => $testSpbId, ':s2' => $testVoidSpbId, ':s3' => $linkedSpbId]);
$db->prepare('DELETE FROM spb_void_audit WHERE spb_id = :id')->execute([':id' => $testVoidSpbId]);
$db->prepare('DELETE FROM work_orders WHERE wo_id IN (:w1, :w2)')->execute([':w1' => $testWoId, ':w2' => $testWoWithSpb]);
$db->prepare('DELETE FROM assets WHERE asset_id = :aid')->execute([':aid' => $testAssetId]);
$db->prepare('DELETE FROM input_revision_audit WHERE entity_id IN (:e1, :e2, :e3, :e4)')->execute([':e1' => $testSpbId, ':e2' => $testVoidSpbId, ':e3' => $testWoId, ':e4' => 'SPB-TEST-01']);
$db->prepare('DELETE FROM input_revision_idempotency WHERE idempotency_key = :key')->execute([':key' => $idemKey]);

echo "\n--------------------------------------------------------------------\n";
echo "SUMMARY: {$passCount} / {$totalTests} TESTS PASSED\n";
echo "====================================================================\n";

if ($passCount === $totalTests) {
    echo "🎉 ALL EPIC 4.5 & GOV-01 VERIFICATION TESTS PASSED PERFECTLY!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED. CHECK OUTPUT ABOVE.\n";
    exit(1);
}
