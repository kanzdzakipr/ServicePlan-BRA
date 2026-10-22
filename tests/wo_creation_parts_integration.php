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
        ['partNumber' => 'P-001-OIL', 'description' => 'Filter Oli Engine', 'qty' => 2, 'uom' => 'Pcs', 'isCustom' => false],
        ['partNumber' => '', 'description' => 'Baut Khusus 14mm Baja', 'qty' => 4, 'uom' => 'Pcs', 'isCustom' => true]
    ];
    $partsNote = "• 2 Pcs - Filter Oli Engine (P-001-OIL) [Gudang]\n• 4 Pcs - Baut Khusus 14mm Baja [Luar Gudang]";

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

    $asset = $db->query("SELECT * FROM assets WHERE asset_id = " . $db->quote($testAssetId))->fetch(PDO::FETCH_ASSOC);
    $ticket = WorkOrderSupport::normalizeTicketInput($woData, $asset);

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
        ':status' => 'Open',
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

    // 3. Verify fetched overview
    $overview = WorkOrderSupport::overview($db, $testWoId);
    $wo = $overview['workOrder'];
    assertTrue($wo['partsRequirement'] === 'REQUIRED', 'Parts requirement should be REQUIRED');
    assertTrue(str_contains($wo['partsRequirementNote'], 'Filter Oli Engine'), 'Parts note should contain Filter Oli');
    assertTrue(str_contains($wo['partsRequirementNote'], 'Baut Khusus 14mm'), 'Parts note should contain custom part');
    echo "PASS: Work order creation with parts builder note and requirement verified.\n";

} finally {
    $db->prepare("DELETE FROM work_orders WHERE wo_id = ?")->execute([$testWoId]);
}
