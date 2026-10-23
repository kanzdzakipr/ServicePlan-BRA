<?php
/**
 * Test Batch 2: P2H Critical Fail -> Automatic Work Order Generation & Void Reversal
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/ReportIntegration.php';
require_once __DIR__ . '/../core/ConditionMonitoring.php';

$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4';
$user = 'root';
$password = '';

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    echo "SKIP: Database connection failed: " . $e->getMessage() . "\n";
    exit(0);
}

ReportIntegration::ensureTables($db);
ConditionMonitoring::ensureTables($db);

echo "Running Batch 2 Tests...\n";

$testAssetId = 'TEST-ASSET-B2-' . time();
$db->prepare("
    INSERT INTO assets (asset_id, asset_code, category, make_model, serial_number, status, current_location_id, raw_location_notes, last_hm_km, is_active, created_at)
    VALUES (:aid, :acode, 'Excavator', 'Test Excavator', 'SN-TEST-B2', 'READY', 1, 'Site Pit A', 1500, 1, NOW())
")->execute([':aid' => $testAssetId, ':acode' => $testAssetId]);

$actor = $db->query('SELECT user_id, full_name FROM users ORDER BY user_id LIMIT 1')->fetch();
$actorId = (int) ($actor['user_id'] ?? 1);

// Create dummy report record in report_records
$reportId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    random_int(0, 0xffff), random_int(0, 0xffff),
    random_int(0, 0xffff),
    random_int(0x4000, 0x4fff),
    random_int(0x8000, 0xbfff),
    random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
);
$reportNumber = 'P2H-TEST-' . time();

// Ensure template exists
$templateStmt = $db->prepare("SELECT template_id FROM report_templates WHERE template_key = 'p2h-excavator' LIMIT 1");
$templateStmt->execute();
$templateId = (int) $templateStmt->fetchColumn();
if ($templateId === 0) {
    $db->prepare("
        INSERT INTO report_templates (template_key, code, title, version, schema_json)
        VALUES ('p2h-excavator', 'P2H-EXC', 'P2H Excavator', 1, '{}')
    ")->execute();
    $templateId = (int) $db->lastInsertId();
}

$db->prepare("
    INSERT INTO report_records (report_id, template_id, client_key, report_number, status, source_method, field_data, created_by)
    VALUES (:rid, :tid, 'test-client', :rnum, 'FINAL', 'manual', '{}', :actor)
")->execute([
    ':rid' => $reportId,
    ':tid' => $templateId,
    ':rnum' => $reportNumber,
    ':actor' => $actorId,
]);

$createdWoId = null;

try {
    // 1. Finalize P2H with critical failure
    $fields = [
        'tanggal_slot' => date('Y-m-d'),
        'shift' => '1',
        'operator' => 'Test Operator',
        'nrp' => '12345',
        'code_number' => $testAssetId,
        'hm_sebelum' => '1500',
        'hm_selesai' => '1508',
        'job_site' => 'Site Pit A',
    ];

    $rows = [
        [
            'item' => 'Sistem Rem & Brake',
            'kondisi' => 'Rusak',
            'critical' => '1',
            'keterangan' => 'Kebocoran minyak rem tekanan hilang',
        ],
        [
            'item' => 'Lampu Kerja',
            'kondisi' => 'Baik',
            'critical' => '0',
            'keterangan' => 'Normal',
        ]
    ];

    $db->beginTransaction();
    $result = ReportIntegration::applyFinal($db, 'p2h-excavator', $reportId, $fields, $rows, $actorId);
    $db->commit();

    assert($result['applied'] === true, "Report integration should be applied");
    assert(isset($result['workOrderId']), "Result must contain workOrderId");
    $createdWoId = $result['workOrderId'];
    echo "PASS: Auto-generated WO ID: {$createdWoId}\n";

    // Check Asset Status
    $assetStatus = $db->query("SELECT status FROM assets WHERE asset_id = '$testAssetId'")->fetchColumn();
    assert($assetStatus === 'BREAKDOWN', "Asset status should be BREAKDOWN. Got: $assetStatus");
    echo "PASS: Asset status updated to BREAKDOWN.\n";

    // Check WO in database
    $woStmt = $db->prepare("SELECT * FROM work_orders WHERE wo_id = :wo_id");
    $woStmt->execute([':wo_id' => $createdWoId]);
    $wo = $woStmt->fetch();
    assert((bool)$wo, "Work Order record must exist in DB");
    assert($wo['status'] === 'Open', "WO status should be Open. Got: {$wo['status']}");
    assert($wo['priority'] === 'Emergency', "Priority should be Emergency due to brake issue. Got: {$wo['priority']}");
    assert((int)$wo['is_downtime'] === 1, "is_downtime must be 1");
    echo "PASS: WO attributes (Open, Emergency, is_downtime=1) verified.\n";

    // Check WO timeline
    $tlCount = $db->query("SELECT COUNT(*) FROM work_order_timeline WHERE wo_id = '$createdWoId'")->fetchColumn();
    assert((int)$tlCount > 0, "Timeline must exist for generated WO");
    echo "PASS: WO Timeline entry verified.\n";

    // Check report_work_order_integrations
    $intStmt = $db->prepare("SELECT * FROM report_work_order_integrations WHERE report_id = :rid AND work_order_id = :wo_id");
    $intStmt->execute([':rid' => $reportId, ':wo_id' => $createdWoId]);
    $integration = $intStmt->fetch();
    assert((bool)$integration, "Integration record must link report and WO");
    echo "PASS: report_work_order_integrations link verified.\n";

    // 2. Test Condition Monitoring Danger Update on another READY unit
    $testCmAssetId = 'TEST-CM-ASSET-' . time();
    $db->prepare("
        INSERT INTO assets (asset_id, asset_code, category, make_model, serial_number, status, current_location_id, last_hm_km, is_active, created_at)
        VALUES (:aid, :acode, 'Dump Truck', 'Test DT', 'SN-CM-1', 'READY', 1, 1000, 1, NOW())
    ")->execute([':aid' => $testCmAssetId, ':acode' => $testCmAssetId]);

    // Save dangerous battery log (voltage 11.2V)
    $batteryData = [
        'asset_id' => $testCmAssetId,
        'voltage' => 11.2,
        'cca_percent' => 50,
        'brand' => 'GS Yuasa',
        'battery_type' => 'N120',
        'installation_date' => date('Y-m-d'),
        'terminal_condition' => 'Korosi berat',
        'electrolyte_level' => 'Rendah',
        'case_condition' => 'Baik',
        'notes' => 'Aki drop perlu inspeksi',
    ];

    $db->beginTransaction();
    $cmRes = ConditionMonitoring::save($db, 'battery', $batteryData, $actorId);
    $db->commit();

    assert($cmRes['status'] === 'danger', "Battery status should be danger");
    $cmAssetStatus = $db->query("SELECT status FROM assets WHERE asset_id = '$testCmAssetId'")->fetchColumn();
    assert($cmAssetStatus === 'INSPEKSI', "CM danger should change asset status to INSPEKSI. Got: $cmAssetStatus");
    echo "PASS: Condition Monitoring danger correctly updated asset status to INSPEKSI.\n";

    // 3. Test Void Reversal of the P2H Report
    $db->beginTransaction();
    $reversal = ReportIntegration::reverseFinal($db, $reportId, $actorId);
    $db->commit();

    // Verify WO was deleted upon report void
    $checkWoDeleted = $db->query("SELECT COUNT(*) FROM work_orders WHERE wo_id = '$createdWoId'")->fetchColumn();
    assert((int)$checkWoDeleted === 0, "Created WO should be deleted when P2H report is voided");
    echo "PASS: Auto-generated WO cleanly deleted upon report void.\n";

    // Verify Asset status restored
    $restoredStatus = $db->query("SELECT status FROM assets WHERE asset_id = '$testAssetId'")->fetchColumn();
    assert($restoredStatus === 'READY', "Asset status should be restored to READY. Got: $restoredStatus");
    echo "PASS: Asset status restored to READY after report void.\n";

} finally {
    // Cleanup
    if ($createdWoId !== null) {
        $db->exec("DELETE FROM work_order_timeline WHERE wo_id = '$createdWoId'");
        $db->exec("DELETE FROM work_orders WHERE wo_id = '$createdWoId'");
    }
    $db->exec("DELETE FROM report_work_order_integrations WHERE report_id = '$reportId'");
    $db->exec("DELETE FROM report_inspection_integrations WHERE report_id = '$reportId'");
    $db->exec("DELETE FROM inspections WHERE asset_id = '$testAssetId'");
    $db->exec("DELETE FROM report_records WHERE report_id = '$reportId'");
    $db->exec("DELETE FROM assets WHERE asset_id = '$testAssetId'");
    if (isset($testCmAssetId)) {
        $db->exec("DELETE FROM battery_logs WHERE asset_id = '$testCmAssetId'");
        $db->exec("DELETE FROM assets WHERE asset_id = '$testCmAssetId'");
    }
    echo "Cleanup completed.\n";
}

echo "All Batch 2 tests PASSED!\n";
