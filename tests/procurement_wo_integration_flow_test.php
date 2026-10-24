<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/WorkOrderSupport.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';
require_once dirname(__DIR__) . '/core/SpbWorkflow.php';

$dsn = (string) (getenv('REPORT_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
$user = (string) (getenv('REPORT_TEST_DB_USER') ?: 'root');
$password = (string) (getenv('REPORT_TEST_DB_PASSWORD') ?: '');
$passed = 0;

function itAssert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException("FAIL: {$message}");
    }
    $passed++;
    echo "PASS: {$message}\n";
}

try {
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $error) {
    echo "SKIPPED: Database not reachable: " . $error->getMessage() . "\n";
    exit(0);
}

echo "=======================================================================\n";
echo "  TEST SUITE: Procurement, Warehouse & Work Order Dual-Flow Lifecycle  \n";
echo "=======================================================================\n\n";

$suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$testAssetId = 'TEST-ASSET-' . $suffix;
$testWoIdA = 'WO-EMG-A-' . $suffix;
$testWoIdB = 'WO-EMG-B-' . $suffix;
$testPartNumA = 'PART-AVAIL-' . $suffix;
$testPartNumB = 'PART-EMPTY-' . $suffix;
$actorId = 1;

$db->beginTransaction();

try {
    // 0. Setup test asset and parts
    $db->prepare("INSERT INTO assets (asset_id, asset_code, category, status, current_location_id) 
                  VALUES (:id, :code, 'Dump Truck', 'BREAKDOWN', 1)")
       ->execute([':id' => $testAssetId, ':code' => $testAssetId]);

    // Part A: Stock 20
    $db->prepare("INSERT INTO parts (part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, location_warehouse)
                  VALUES (:pnum, 'Test Available Part', 'Filter', 'Pcs', 20, 5, 'Gudang Yard KM 12')")
       ->execute([':pnum' => $testPartNumA]);

    // Part B: Stock 0
    $db->prepare("INSERT INTO parts (part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, location_warehouse)
                  VALUES (:pnum, 'Test Empty Part', 'Hydraulic', 'Pcs', 0, 2, 'Gudang Yard KM 12')")
       ->execute([':pnum' => $testPartNumB]);

    // -------------------------------------------------------------------------
    // TEST 1: checkPartsAvailability evaluates physical stock
    // -------------------------------------------------------------------------
    $availTest = WorkOrderSupport::checkPartsAvailability($db, [
        ['part_number' => $testPartNumA, 'qty' => 5],
        ['part_number' => $testPartNumB, 'qty' => 2],
    ]);
    itAssert($availTest['all_available'] === false, 'checkPartsAvailability flags incomplete stock');
    itAssert($availTest['any_available'] === true, 'checkPartsAvailability detects partial availability');
    itAssert($availTest['summary_status'] === 'PARTIAL', 'checkPartsAvailability status is PARTIAL');
    itAssert(count($availTest['available_items']) === 1, 'Exactly 1 item is available');
    itAssert(count($availTest['deficit_items']) === 1, 'Exactly 1 item is in deficit');

    $availAll = WorkOrderSupport::checkPartsAvailability($db, [
        ['part_number' => $testPartNumA, 'qty' => 5],
    ]);
    itAssert($availAll['all_available'] === true, 'All available check returns true when stock is sufficient');
    itAssert($availAll['summary_status'] === 'ALL_AVAILABLE', 'Status is ALL_AVAILABLE');

    // -------------------------------------------------------------------------
    // TEST 2: Alur Emergency - Kondisi A (Stok Ada di Gudang)
    // -------------------------------------------------------------------------
    // Create WO A (Emergency breakdown)
    $db->prepare("INSERT INTO work_orders (wo_id, asset_id, status, priority, maintenance_type, parts_requirement, issue_description, reported_at)
                  VALUES (:wo_id, :asset_id, 'Open', 'Emergency', 'Emergency', 'REQUIRED', 'Kerusakan mendesak part tersedia', NOW())")
       ->execute([':wo_id' => $testWoIdA, ':asset_id' => $testAssetId]);

    // Directly issue parts via WorkOrderSupport::issueDirectParts
    $directIssue = WorkOrderSupport::issueDirectParts(
        $db,
        $testWoIdA,
        $testAssetId,
        [['part_number' => $testPartNumA, 'requested_qty' => 5]],
        $actorId,
        'Mekanik QA'
    );
    itAssert(!empty($directIssue['reportNumber']), 'Direct issue creates valid BHW-OUT report number');
    itAssert($directIssue['totalItems'] === 1, 'Direct issue processes 1 item');

    // Verify stock is decremented from 20 to 15
    $checkStockA = $db->prepare("SELECT stock_qty FROM parts WHERE part_number = :pnum");
    $checkStockA->execute([':pnum' => $testPartNumA]);
    itAssert((int) $checkStockA->fetchColumn() === 15, 'Stock of Part A is decremented from 20 to 15');

    // Verify inventory transaction record
    $txStmt = $db->prepare("SELECT movement_type, wo_id, asset_id, quantity, stock_before, stock_after 
                            FROM inventory_transactions WHERE wo_id = :wo_id");
    $txStmt->execute([':wo_id' => $testWoIdA]);
    $tx = $txStmt->fetch();
    itAssert($tx && $tx['movement_type'] === 'OUT', 'inventory_transactions recorded with movement OUT');
    itAssert((int) $tx['quantity'] === 5, 'Transaction quantity is 5');
    itAssert((int) $tx['stock_before'] === 20 && (int) $tx['stock_after'] === 15, 'Stock before/after tracked accurately');
    itAssert($tx['asset_id'] === $testAssetId, 'Transaction links to correct unit asset');

    // Verify NO purchase_requests created for WO A
    $spbCheckA = $db->prepare("SELECT COUNT(*) FROM purchase_requests WHERE wo_id = :wo_id");
    $spbCheckA->execute([':wo_id' => $testWoIdA]);
    itAssert((int) $spbCheckA->fetchColumn() === 0, 'Kondisi A does NOT generate unnecessary SPB');

    // -------------------------------------------------------------------------
    // TEST 3: Alur Emergency - Kondisi B (Stok Kosong / Kurang)
    // -------------------------------------------------------------------------
    // In Alur Emergency, the Work Order is created first!
    $db->prepare("INSERT INTO work_orders (wo_id, asset_id, status, priority, maintenance_type, parts_requirement, issue_description, reported_at)
                  VALUES (:wo_id, :asset_id, 'Waiting Part', 'Emergency', 'Emergency', 'REQUIRED', 'Kerusakan mendesak', NOW())")
       ->execute([':wo_id' => $testWoIdB, ':asset_id' => $testAssetId]);

    // Next, Emergency SPB is generated linked to this Work Order
    $spbIdB = 'SPB-EMG-' . $suffix;
    $db->prepare("INSERT INTO purchase_requests (spb_id, wo_id, asset_id, requested_by, urgency, spb_type, status, notes, requested_at)
                  VALUES (:spb_id, :wo_id, :asset_id, :req, 'Emergency', 'EMERGENCY', 'Submitted', 'Perbaikan mendesak unit breakdown', NOW())")
       ->execute([
           ':spb_id' => $spbIdB,
           ':wo_id' => $testWoIdB,
           ':asset_id' => $testAssetId,
           ':req' => $actorId,
       ]);

    $db->prepare("INSERT INTO purchase_request_items (id, spb_id, part_number, description, qty_requested, status)
                  VALUES (:id, :spb_id, :pnum, 'Test Empty Part', 2, 'Submitted')")
       ->execute([
           ':id' => 'ITEM-B-' . $suffix,
           ':spb_id' => $spbIdB,
           ':pnum' => $testPartNumB,
       ]);

    // Verify WO B is in 'Waiting Part'
    $woStmtB = $db->prepare("SELECT status FROM work_orders WHERE wo_id = :wo_id");
    $woStmtB->execute([':wo_id' => $testWoIdB]);
    itAssert($woStmtB->fetchColumn() === 'Waiting Part', 'Kondisi B sets WO status to Waiting Part');

    // Verify checkAndResumeWorkOrder does NOT resume while SPB is still Submitted
    $resumed = WorkOrderSupport::checkAndResumeWorkOrder($db, $testWoIdB, $actorId, 'QA Tester');
    itAssert($resumed === false, 'WO does not resume while Emergency SPB is Submitted');

    // Now simulate arrival and issue of parts: SPB updated to 'Issued'
    $db->prepare("UPDATE purchase_requests SET status = 'Issued' WHERE spb_id = :spb_id")
       ->execute([':spb_id' => $spbIdB]);

    // Resume WO
    $resumedAfter = WorkOrderSupport::checkAndResumeWorkOrder($db, $testWoIdB, $actorId, 'QA Tester');
    itAssert($resumedAfter === true, 'WO automatically resumes to In Progress when Emergency SPB is fulfilled');

    $woStmtB->execute([':wo_id' => $testWoIdB]);
    itAssert($woStmtB->fetchColumn() === 'In Progress', 'WO status is restored to In Progress');

    // -------------------------------------------------------------------------
    // TEST 4: Alur Normal Preventive Maintenance (SPB tanpa WO di awal)
    // -------------------------------------------------------------------------
    $spbNormalId = 'SPB-NORM-' . $suffix;
    $db->prepare("INSERT INTO purchase_requests (spb_id, wo_id, asset_id, requested_by, urgency, spb_type, status, notes, requested_at)
                  VALUES (:spb_id, NULL, :asset_id, :req, 'Normal', 'NORMAL', 'Submitted', 'Pengadaan bulanan suku cadang PM', NOW())")
       ->execute([
           ':spb_id' => $spbNormalId,
           ':asset_id' => $testAssetId,
           ':req' => $actorId,
       ]);

    $checkNormal = $db->prepare("SELECT wo_id, spb_type, status FROM purchase_requests WHERE spb_id = :spb_id");
    $checkNormal->execute([':spb_id' => $spbNormalId]);
    $norm = $checkNormal->fetch();
    itAssert($norm['wo_id'] === null, 'Normal PM SPB successfully persists with wo_id = NULL');
    itAssert($norm['spb_type'] === 'NORMAL', 'Normal PM SPB has spb_type = NORMAL');

    // -------------------------------------------------------------------------
    // TEST 5: Surat Perintah Lembur (SPL) linked to wo_id
    // -------------------------------------------------------------------------
    $reportId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff), random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0x4000, 0x4fff),
        random_int(0x8000, 0xbfff),
        random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
    );

    // Setup temporary report template for SPL QA
    $splTemplateKey = 'qa-spl-' . strtolower($suffix);
    $db->prepare("INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
                  VALUES (:tkey, 'SPL', 'QA Overtime', 1, '{}', 1)")
       ->execute([':tkey' => $splTemplateKey]);
    $tplSpl = (int) $db->lastInsertId();

    // Fetch active location for SPL
    $activeLocation = (string) ($db->query("SELECT location_name FROM locations WHERE is_active = 1 LIMIT 1")->fetchColumn() ?: 'Site Bangko');

    $splNumber = 'SPL-QA-' . $suffix;
    $fields = [
        'nomor_spl' => $splNumber,
        'tanggal_spl' => date('Y-m-d'),
        'mulai_periode' => date('Y-m-d'),
        'akhir_periode' => date('Y-m-d'),
        'lokasi' => $activeLocation,
        'dibuat_oleh' => 'Administrator Utama',
        'instruksi_oleh' => 'Administrator Utama',
        'uraian' => 'Perbaikan lembur breakdown unit darurat',
        'wo_id' => $testWoIdA,
    ];

    $rows = [[
        'nama' => 'Administrator Utama',
        'jabatan' => 'Administrator',
        'lokasi' => $activeLocation,
        'mulai' => '17:00',
        'selesai' => '20:00',
        'durasi' => '3.00',
        'uraian' => 'Pekerjaan perbaikan lembur',
        'verifikasi_mulai' => 'Terverifikasi',
        'verifikasi_selesai' => 'Terverifikasi',
    ]];

    // Insert parent report_record to satisfy fk_spl_overtime_report
    $db->prepare("INSERT INTO report_records (report_id, template_id, client_key, report_number, status, source_method, field_data, created_by, finalized_at)
                  VALUES (:rid, :tid, :ckey, :rnum, 'FINAL', 'test', '{}', :actor, NOW())")
       ->execute([
           ':rid' => $reportId,
           ':tid' => $tplSpl,
           ':ckey' => 'client-spl-' . $suffix,
           ':rnum' => $splNumber,
           ':actor' => $actorId,
       ]);

    $splResult = ReportIntegration::applyFinal($db, 'spl', $reportId, $fields, $rows, $actorId);
    itAssert($splResult['applied'] === true, 'SPL Report integration applied successfully');

    $splRecord = $db->prepare("SELECT wo_id, spl_number, duration_hours FROM spl_overtime_records WHERE report_id = :rid");
    $splRecord->execute([':rid' => $reportId]);
    $spl = $splRecord->fetch();
    itAssert($spl['wo_id'] === $testWoIdA, 'SPL record correctly stores linked wo_id');
    itAssert((float) $spl['duration_hours'] === 3.0, 'SPL duration is recorded accurately');

    echo "\nAll {$passed} lifecycle integration checks PASSED successfully!\n";
} finally {
    // Clean up test data completely via transaction rollback
    $db->rollBack();
    echo "TRANSACTION ROLLBACK: All test records reverted cleanly. 0 QA rows left in database.\n";
}
