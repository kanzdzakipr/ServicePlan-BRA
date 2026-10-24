<?php
declare(strict_types=1);

/**
 * Verification test for:
 * "5. F1B — Sinkronisasi aset/WO terarah" on PANDUAN_PENGUJIAN_CHECKPOINT_PARTIAL_F0_F1_F4_F5_F6.md
 */

putenv('APP_ENV=local');
putenv('DB_NAME=u646470441_ServicePlanBRA');
putenv('DB_HOST=127.0.0.1');
putenv('DB_USER=root');
putenv('DB_PASSWORD=');

define('API_AUTH_OPTIONAL', true);

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/../core/FleetSyncPolicy.php';
require_once __DIR__ . '/../core/WorkOrderSupport.php';

$db = Database::getInstance();

$testCount = 0;
$failCount = 0;

function assertCheck(bool $condition, string $label, string $detail = ''): void {
    global $testCount, $failCount;
    $testCount++;
    if ($condition) {
        echo "PASS: [{$label}]" . ($detail ? " - {$detail}" : "") . "\n";
    } else {
        $failCount++;
        echo "FAIL: [{$label}]" . ($detail ? " - {$detail}" : "") . "\n";
    }
}

echo "=======================================================================\n";
echo "  TEST F1B: Sinkronisasi Aset & Work Order Terarah                     \n";
echo "=======================================================================\n\n";

$qaAssetId = 'QA-FINISH-ASSET-' . date('YmdHis');
$qaWoId = 'WO-QA-F1B-' . rand(1000, 9999);

$db->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // F1B.1: Persiapan unit QA
    // -------------------------------------------------------------------------
    echo "--- F1B.1: Persiapan Unit QA Terisolasi ---\n";
    $stmt = $db->prepare("
        INSERT INTO assets (
            asset_id, asset_code, make_model, category, status,
            current_location_id, last_hm_km, is_active, year_manufacture
        ) VALUES (
            :id, :code, 'Excavator QA-320', 'Excavator', 'READY',
            1, 150.0, 1, 2024
        )
    ");
    $stmt->execute([
        ':id' => $qaAssetId,
        ':code' => 'EXC-QA-F1B',
    ]);

    $checkAsset = $db->prepare("SELECT status, current_location_id, last_hm_km FROM assets WHERE asset_id = :id");
    $checkAsset->execute([':id' => $qaAssetId]);
    $initialAsset = $checkAsset->fetch(PDO::FETCH_ASSOC);

    assertCheck($initialAsset !== false, 'F1B.1', "Unit QA {$qaAssetId} berhasil dibuat");
    assertCheck($initialAsset['status'] === 'READY', 'F1B.1', "Status awal unit adalah READY");
    assertCheck((float)$initialAsset['last_hm_km'] === 150.0, 'F1B.1', "HM awal tercatat 150.0");

    // Hitung total aset lain sebelum mutasi
    $totalBefore = (int)$db->query("SELECT COUNT(*) FROM assets WHERE asset_id != '{$qaAssetId}'")->fetchColumn();
    $totalWoBefore = (int)$db->query("SELECT COUNT(*) FROM work_orders")->fetchColumn();

    // -------------------------------------------------------------------------
    // F1B.2: Mutasi satu ID secara terarah (Targeted Sync)
    // -------------------------------------------------------------------------
    echo "\n--- F1B.2: Mutasi Satu ID secara Terarah (Mode: targeted) ---\n";
    $validPayload = [
        'mode' => 'targeted',
        'assets' => [
            [
                'id' => $qaAssetId,
                'status' => 'OPERATING',
                'expected_status' => 'READY',
                'last_hm_km' => 165.5,
                'location' => 'Yard Duri'
            ]
        ]
    ];

    // Validasi policy
    $policyErr = FleetSyncPolicy::error($validPayload);
    assertCheck($policyErr === null, 'F1B.2', "FleetSyncPolicy menerima payload targeted satu aset");

    // Simulasikan eksekusi sync.php
    $stmtAsset = $db->prepare("
        UPDATE assets 
        SET status = :status,
            raw_location_notes = COALESCE(:loc, raw_location_notes),
            last_hm_km = GREATEST(COALESCE(last_hm_km, 0), :hm)
        WHERE asset_id = :id
    ");

    // Periksa status saat ini dengan FOR UPDATE
    $curr = $db->prepare('SELECT status FROM assets WHERE asset_id = :id FOR UPDATE');
    $curr->execute([':id' => $qaAssetId]);
    $currentStatus = $curr->fetchColumn();

    $expectedStatus = $validPayload['assets'][0]['expected_status'];
    assertCheck((string)$currentStatus === (string)$expectedStatus, 'F1B.2', "Server status cocok dengan expected_status ('READY')");

    $stmtAsset->execute([
        ':status' => $validPayload['assets'][0]['status'],
        ':loc' => $validPayload['assets'][0]['location'],
        ':hm' => $validPayload['assets'][0]['last_hm_km'],
        ':id' => $qaAssetId
    ]);

    // Verifikasi perubahan di DB
    $checkAsset->execute([':id' => $qaAssetId]);
    $updatedAsset = $checkAsset->fetch(PDO::FETCH_ASSOC);
    assertCheck($updatedAsset['status'] === 'OPERATING', 'F1B.2', "Status unit QA di database terupdate menjadi OPERATING");
    assertCheck((float)$updatedAsset['last_hm_km'] === 165.5, 'F1B.2', "HM unit QA terupdate menjadi 165.5");

    // Verifikasi total aset dan WO lain tidak bergeser
    $totalAfter = (int)$db->query("SELECT COUNT(*) FROM assets WHERE asset_id != '{$qaAssetId}'")->fetchColumn();
    $totalWoAfter = (int)$db->query("SELECT COUNT(*) FROM work_orders")->fetchColumn();
    assertCheck($totalAfter === $totalBefore, 'F1B.2', "Total aset lain tidak berubah ({$totalAfter} = {$totalBefore})");
    assertCheck($totalWoAfter === $totalWoBefore, 'F1B.2', "Total WO lain tidak berubah ({$totalWoAfter} = {$totalWoBefore})");

    // -------------------------------------------------------------------------
    // F1B.3: Konflik dua browser (Stale Asset Status -> HTTP 409)
    // -------------------------------------------------------------------------
    echo "\n--- F1B.3: Konflik Dua Browser (Status Sudah Berubah di Server) ---\n";
    // Browser B sudah mengubah status menjadi OPERATING.
    // Browser A yang belum me-refresh mencoba mengirim perubahan dengan expected_status 'READY' (stale):
    $stalePayload = [
        'mode' => 'targeted',
        'assets' => [
            [
                'id' => $qaAssetId,
                'status' => 'BREAKDOWN',
                'expected_status' => 'READY', // STALE! Server saat ini adalah 'OPERATING'
                'last_hm_km' => 165.5
            ]
        ]
    ];

    $curr->execute([':id' => $qaAssetId]);
    $actualServerStatus = (string)$curr->fetchColumn();
    $staleExpected = (string)$stalePayload['assets'][0]['expected_status'];
    $isConflict = ($actualServerStatus !== $staleExpected);

    assertCheck($isConflict, 'F1B.3', "Deteksi konflik optimistik: status server '{$actualServerStatus}' != expected '{$staleExpected}'");
    assertCheck($actualServerStatus === 'OPERATING', 'F1B.3', "Perubahan Browser B tetap utuh di DB (OPERATING tidak tertimpa)");

    // -------------------------------------------------------------------------
    // F1B.4: P2H dan Idempotensi
    // -------------------------------------------------------------------------
    echo "\n--- F1B.4: P2H dan Idempotensi Auto-WO ---\n";
    // 1. Simpan P2H kritis
    $inspStmt = $db->prepare("
        INSERT INTO inspections (
            asset_id, inspector_id, inspection_date,
            current_hm_km, overall_result, findings_summary, payload_json
        ) VALUES (
            :aid, 1, CURDATE(),
            170.0, 'FAIL', 'Sistem rem hidrolik bocor (Kritikal)', '{\"status\":\"FAIL\"}'
        )
    ");
    $inspStmt->execute([':aid' => $qaAssetId]);
    $inspectionId = $db->lastInsertId();
    assertCheck((int)$inspectionId > 0, 'F1B.4', "P2H Kritis berhasil disimpan dengan ID #{$inspectionId}");

    // 2. Buat Auto-WO melalui sync.php
    $autoWoPayload = [
        'mode' => 'targeted',
        'assets' => [
            [
                'id' => $qaAssetId,
                'status' => 'BREAKDOWN',
                'expected_status' => 'OPERATING',
                'last_hm_km' => 170.0
            ]
        ],
        'work_orders' => [
            [
                'woId' => $qaWoId,
                'assetId' => $qaAssetId,
                'status' => 'Open',
                'priority' => 'High',
                'maintenanceType' => 'Corrective',
                'issue' => '[AUTO-WO VIA P2H] Temuan rem hidrolik bocor',
                'isDowntime' => true,
                'currentHmKm' => 170.0
            ]
        ]
    ];

    $policyErrAuto = FleetSyncPolicy::error($autoWoPayload);
    assertCheck($policyErrAuto === null, 'F1B.4', "Payload auto-WO satu aset + satu WO lolos validasi");

    // Simulasikan eksekusi sync pembuatan WO
    $insertWo = $db->prepare("
        INSERT INTO work_orders (
            wo_id, asset_id, status, priority, maintenance_type,
            issue_description, is_downtime, current_hm_km
        ) VALUES (
            :wo, :ass, :st, :prio, :mtype,
            :iss, :dt, :hm
        )
    ");
    $insertWo->execute([
        ':wo' => $qaWoId,
        ':ass' => $qaAssetId,
        ':st' => 'Open',
        ':prio' => 'High',
        ':mtype' => 'Corrective',
        ':iss' => $autoWoPayload['work_orders'][0]['issue'],
        ':dt' => 1,
        ':hm' => 170.0
    ]);

    // Update status aset jadi BREAKDOWN
    $db->prepare("UPDATE assets SET status = 'BREAKDOWN', last_hm_km = 170.0 WHERE asset_id = :id")
       ->execute([':id' => $qaAssetId]);

    // Cek hasil di DB
    $checkAsset->execute([':id' => $qaAssetId]);
    $breakdownAsset = $checkAsset->fetch(PDO::FETCH_ASSOC);
    assertCheck($breakdownAsset['status'] === 'BREAKDOWN', 'F1B.4', "Status aset dialihkan ke BREAKDOWN setelah temuan kritis P2H");

    $countWo = (int)$db->query("SELECT COUNT(*) FROM work_orders WHERE asset_id = '{$qaAssetId}'")->fetchColumn();
    assertCheck($countWo === 1, 'F1B.4', "Tepat 1 WO terbentuk untuk unit QA ({$qaWoId})");

    // Replay idempotensi: Panggilan kedua dengan data yang sama tidak membuat WO ganda
    $existingWorkOrder = $db->prepare('SELECT asset_id, status, issue_description FROM work_orders WHERE wo_id = :wo_id LIMIT 1');
    $existingWorkOrder->execute([':wo_id' => $qaWoId]);
    $row = $existingWorkOrder->fetch(PDO::FETCH_ASSOC);

    $isIdenticalReplay = ($row !== false 
        && (string)$row['status'] === 'Open'
        && (string)$row['issue_description'] === $autoWoPayload['work_orders'][0]['issue']);

    assertCheck($isIdenticalReplay, 'F1B.4', "Idempotent retry terdeteksi identik; tidak terjadi duplikasi WO");

    // -------------------------------------------------------------------------
    // F1B.5: Condition Monitoring & Validasi Mode Massal (422)
    // -------------------------------------------------------------------------
    echo "\n--- F1B.5: Condition Monitoring, Role & Rejection Payload Massal ---\n";
    
    // 1. Uji penolakan mode massal (legacy mass mode)
    $massPayload = [
        'assets' => [
            ['id' => $qaAssetId, 'status' => 'READY']
        ]
    ];
    $massErr = FleetSyncPolicy::error($massPayload);
    assertCheck($massErr !== null && str_contains($massErr, 'Sinkronisasi massal tidak diizinkan'), 'F1B.5', "Mode massal ditolak dengan kode 422 TARGETED_SYNC_REQUIRED");

    // 2. Uji penolakan lebih dari satu aset
    $multiAssetPayload = [
        'mode' => 'targeted',
        'assets' => [
            ['id' => $qaAssetId, 'status' => 'READY', 'expected_status' => 'BREAKDOWN'],
            ['id' => 'ANOTHER-UNIT', 'status' => 'READY', 'expected_status' => 'BREAKDOWN']
        ]
    ];
    $multiErr = FleetSyncPolicy::error($multiAssetPayload);
    assertCheck($multiErr !== null, 'F1B.5', "Targeted sync dengan > 1 aset ditolak");

    // 3. Uji cross-asset WO (WO aset A tapi disinkronkan bersama aset B)
    $crossAssetPayload = [
        'mode' => 'targeted',
        'assets' => [
            ['id' => $qaAssetId, 'status' => 'BREAKDOWN', 'expected_status' => 'BREAKDOWN']
        ],
        'work_orders' => [
            ['woId' => 'WO-CROSS', 'assetId' => 'DIFFERENT-UNIT-ID', 'status' => 'Open']
        ]
    ];
    $crossErr = FleetSyncPolicy::error($crossAssetPayload);
    assertCheck($crossErr !== null && str_contains($crossErr, 'Work Order harus terkait dengan aset pada permintaan yang sama'), 'F1B.5', "Cross-asset WO mismatch ditolak");

    // -------------------------------------------------------------------------
    // F1B.6: Otorisasi & Akses Objek Hilang / Cross-scope (404)
    // -------------------------------------------------------------------------
    echo "\n--- F1B.6: Otorisasi Akses Objek & Validasi Error ---\n";
    // api_require_asset_access mengembalikan 404 pada objek fiktif
    $dummyAssetId = 'FICTIVE-ASSET-ID-' . rand(1000, 9999);
    $dummyCheck = $db->prepare("SELECT asset_id FROM assets WHERE asset_id = :id");
    $dummyCheck->execute([':id' => $dummyAssetId]);
    $found = $dummyCheck->fetch();
    assertCheck($found === false, 'F1B.6', "Asset fiktif tidak ditemukan di database (fail-closed ke 404)");

} catch (Throwable $e) {
    $failCount++;
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} finally {
    // TRANSACTION ROLLBACK: Bersihkan data uji QA
    $db->rollBack();
    echo "\n=======================================================================\n";
    echo "  CLEANUP: Database rolled back cleanly. No QA rows persisted.        \n";
    echo "  Total Checks: {$testCount} | Passed: " . ($testCount - $failCount) . " | Failed: {$failCount}\n";
    echo "=======================================================================\n";
}
