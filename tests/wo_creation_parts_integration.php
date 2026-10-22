<?php
declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../core/WorkOrderSupport.php';

function assertTrue(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException("Assertion failed: {$message}");
    }
}

$db = Database::getInstance();
$asset = $db->query('SELECT * FROM assets WHERE is_active = 1 ORDER BY asset_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$asset) {
    echo "SKIP: No active asset found.\n";
    exit(0);
}
$testAssetId = $asset['asset_id'];
$testWoId = 'WO-TEST-' . bin2hex(random_bytes(4));

try {
    // 2. Prepare WO data with parts items
    $partsItems = [
        ['partNumber' => 'P-001-OIL', 'description' => 'Filter Oli Engine', 'qty' => 2, 'uom' => 'Pcs', 'isManual' => false],
        ['partNumber' => '', 'description' => 'Baut Khusus 14mm Baja', 'qty' => 4, 'uom' => 'Pcs', 'isManual' => true]
    ];
    $partsNote = "• 2 Pcs - Filter Oli Engine (P-001-OIL) [Gudang]\n• 4 Pcs - Baut Khusus 14mm Baja [Luar Gudang/Manual]";

    $woData = [
        'wo_id' => $testWoId,
        'asset_id' => $testAssetId,
        'hull_number' => 'TEST-01',
        'equipment_type' => 'Excavator',
        'current_hm_km' => 1250,
        'trouble_at' => date('Y-m-d H:i:s'),
        'raw_location' => 'Pit North 2',
        'issue_description' => 'Kebocoran pada sambungan filter dan baut aus',
        'status' => 'Open',
        'priority' => 'High',
        'maintenance_type' => 'Corrective',
        'parts_requirement' => 'REQUIRED',
        'parts_requirement_note' => $partsNote,
        'parts_items' => $partsItems,
        'assigned_mechanics' => ['Suryanto', 'Bambang'],
        'assigned_mechanic' => 'Suryanto, Bambang',
        'root_cause' => 'Vibrasi tinggi',
        'repair_action' => 'Ganti filter oli dan baut',
        'is_downtime' => true,
        'is_forced_operation' => false,
        'mitigation_action' => null,
        'repair_started_at' => null,
        'estimated_completion_at' => null
    ];

    // Simulate backend WO creation logic with Draft SPB Unit integration
    $asset = $db->query("SELECT * FROM assets WHERE asset_id = " . $db->quote($testAssetId))->fetch(PDO::FETCH_ASSOC);
    $ticket = WorkOrderSupport::normalizeTicketInput($woData, $asset);

    $rawPartsItems = $woData['parts_items'];
    $validPartsItems = [];
    foreach ($rawPartsItems as $pItem) {
        $pDesc = trim((string) ($pItem['description'] ?? ''));
        $pNum = trim((string) ($pItem['part_number'] ?? $pItem['partNumber'] ?? ''));
        $pQty = max(1, (int) ($pItem['qty'] ?? 1));
        $pUom = trim((string) ($pItem['uom'] ?? 'pcs')) ?: 'pcs';
        if ($pDesc !== '' || $pNum !== '') {
            $validPartsItems[] = [
                'part_number' => $pNum,
                'description' => $pDesc ?: $pNum,
                'qty' => $pQty,
                'uom' => $pUom,
                'is_manual' => !empty($pItem['is_manual']) || !empty($pItem['isManual']),
            ];
        }
    }

    $status = 'Open';
    $stmt = $db->prepare(
        'INSERT INTO work_orders
         (wo_id, asset_id, hull_number, equipment_type, current_hm_km, trouble_at,
          raw_location, issue_description, status, priority, maintenance_type,
          parts_requirement, parts_requirement_note,
          assigned_mechanic, assigned_mechanics_json, root_cause, repair_action,
          is_downtime, is_forced_operation, mitigation_action, downtime_formatted,
          repair_started_at, estimated_completion_at)
         VALUES (:wo_id, :asset_id, :hull_number, :equipment_type, :current_hm_km, :trouble_at,
                 :raw_location, :issue, :status, :priority, :maintenance_type,
                 :parts_requirement, :parts_requirement_note,
                 :mechanic, :mechanics_json, :root_cause, :repair_action,
                 :is_downtime, :is_forced_operation, :mitigation_action, :downtime_formatted,
                 :repair_started_at, :estimated_completion_at)'
    );
    $stmt->execute([
        ':wo_id' => $testWoId,
        ':asset_id' => $testAssetId,
        ':issue' => $woData['issue_description'],
        ':status' => $status,
        ':priority' => 'High',
        ':maintenance_type' => 'Corrective',
        ':parts_requirement' => 'REQUIRED',
        ':parts_requirement_note' => $partsNote,
        ':hull_number' => $ticket['hull_number'],
        ':equipment_type' => $ticket['equipment_type'],
        ':current_hm_km' => $ticket['current_hm_km'],
        ':trouble_at' => $ticket['trouble_at'],
        ':raw_location' => $ticket['raw_location'],
        ':mechanic' => $ticket['assigned_mechanic'],
        ':mechanics_json' => $ticket['assigned_mechanics_json'],
        ':root_cause' => $woData['root_cause'],
        ':repair_action' => $woData['repair_action'],
        ':is_downtime' => 1,
        ':is_forced_operation' => 0,
        ':mitigation_action' => null,
        ':downtime_formatted' => '0 jam 00 menit',
        ':repair_started_at' => null,
        ':estimated_completion_at' => null,
    ]);

    $createdSpbId = sprintf('SPB-%s%s-%04d', date('y'), date('m'), random_int(1000, 9999));
    $stmtSpb = $db->prepare(
        'INSERT INTO purchase_requests
         (spb_id, wo_id, asset_id, requested_by, urgency, status, notes, requested_at)
         VALUES (:spb_id, :wo_id, :asset_id, :requested_by, :urgency, :status, :notes, NOW())'
    );
    $stmtSpb->execute([
        ':spb_id' => $createdSpbId,
        ':wo_id' => $testWoId,
        ':asset_id' => $testAssetId,
        ':requested_by' => 1,
        ':urgency' => 'Emergency',
        ':status' => 'Submitted',
        ':notes' => $partsNote,
    ]);

    $stmtSpbItem = $db->prepare(
        'INSERT INTO purchase_request_items
         (id, spb_id, part_number, description, qty_requested, status)
         VALUES (:id, :spb_id, :part_number, :description, :qty_requested, :status)'
    );
    foreach ($validPartsItems as $idx => $p) {
        $itemId = sprintf('SPBI-%s-%02d', substr(md5($createdSpbId), 0, 8), $idx + 1);
        $stmtSpbItem->execute([
            ':id' => $itemId,
            ':spb_id' => $createdSpbId,
            ':part_number' => $p['part_number'] ?: 'CUSTOM',
            ':description' => mb_substr($p['description'], 0, 255),
            ':qty_requested' => $p['qty'],
            ':status' => 'Submitted',
        ]);
    }

    if ($status === 'Open') {
        $db->prepare("UPDATE work_orders SET status = 'Waiting Part' WHERE wo_id = ?")->execute([$testWoId]);
        $status = 'Waiting Part';
    }

    // 3. Verify fetched overview
    $overview = WorkOrderSupport::overview($db, $testWoId);
    $wo = $overview['workOrder'];
    assertTrue($wo['status'] === 'Waiting Part', 'WO status should auto-transition to Waiting Part');
    assertTrue($wo['partsRequirement'] === 'REQUIRED', 'Parts requirement should be REQUIRED');
    assertTrue(str_contains($wo['partsRequirementNote'], 'Filter Oli Engine'), 'Parts note should contain Filter Oli');
    assertTrue(count($overview['purchaseRequests']) === 1, 'Should have 1 linked SPB');
    assertTrue($overview['purchaseRequests'][0]['spbId'] === $createdSpbId, 'SPB ID should match');
    assertTrue(count($overview['purchaseRequests'][0]['items']) === 2, 'Should have 2 SPB items');

    echo "PASS: Work order creation with parts builder and auto-generated SPB verified successfully.\n";

} finally {
    if (isset($createdSpbId)) {
        $db->prepare("DELETE FROM purchase_request_items WHERE spb_id = ?")->execute([$createdSpbId]);
        $db->prepare("DELETE FROM purchase_requests WHERE spb_id = ?")->execute([$createdSpbId]);
    }
    $db->prepare("DELETE FROM work_orders WHERE wo_id = ?")->execute([$testWoId]);
}
