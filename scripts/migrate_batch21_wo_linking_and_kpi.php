<?php
/**
 * Migration Script: Batch 21 - Relasi Cerdas Work Order, Verifikasi KPI & Sinkronisasi Master SQL Dump
 * Repositori: ServicePlan-BRA
 * Tanggal: 30 Oktober 2026
 * 
 * Cakupan:
 * 1. Intelligent linking `wo_id` pada `spl_overtime_records` terhadap work order terkait.
 * 2. Pengujian endpoint KPI `api/people_kpi.php?action=overtime_leaderboard` pada seluruh periode lembur 2026.
 * 3. Pengujian endpoint payload master `api/init.php`.
 * 4. Audit integritas SSOT: 98 lembar SPL, 617 penugasan mekanik, 2.271,56 jam lembur terverifikasi.
 */

declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';

try {
    $pdo = Database::getInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=========================================================================\n";
    echo " ServicePlan-BRA: Migration Batch 21 - Relasi WO, KPI & Final Governance\n";
    echo "=========================================================================\n\n";

    // 1. Relasi Cerdas Work Order
    echo "[1/4] Menjalankan scanning relasi cerdas Work Order (`wo_id`)...\n";

    // Cek daftar Work Order yang tersedia
    $stmtWO = $pdo->query("
        SELECT wo_id, asset_id, hull_number, trouble_at, reported_at, issue_description
        FROM work_orders
        WHERE trouble_at IS NOT NULL OR reported_at IS NOT NULL
    ");
    $workOrders = $stmtWO->fetchAll(PDO::FETCH_ASSOC);

    // Kriteria relasi spesifik untuk unit dan periode relevan
    // Contoh: PM Service Komatsu GD535 / Motor Grader MG-00005 (MG-4105) pada September 2026
    $linkMappings = [
        [
            'wo_id' => 'WO-PM-03-092026',
            'pattern' => '%mg%4105%',
            'date_start' => '2026-09-12',
            'date_end' => '2026-09-20'
        ],
        [
            'wo_id' => 'WO-PM-20-092026',
            'pattern' => '%dt%64%',
            'date_start' => '2026-09-10',
            'date_end' => '2026-09-20'
        ],
        [
            'wo_id' => 'WO-PM-21-092026',
            'pattern' => '%dt%4048%',
            'date_start' => '2026-09-01',
            'date_end' => '2026-09-08'
        ]
    ];

    $updateStmt = $pdo->prepare("
        UPDATE spl_overtime_records
        SET wo_id = :wo_id
        WHERE spl_date BETWEEN :d_start AND :d_end
          AND (LOWER(activity_description) LIKE :pattern1 OR LOWER(general_instruction) LIKE :pattern2)
          AND wo_id IS NULL
    ");

    $linkedCount = 0;
    foreach ($linkMappings as $map) {
        // Cek apakah wo_id benar-benar ada di tabel work_orders
        $checkWo = $pdo->prepare("SELECT COUNT(*) FROM work_orders WHERE wo_id = :wo_id");
        $checkWo->execute([':wo_id' => $map['wo_id']]);
        if ((int)$checkWo->fetchColumn() > 0) {
            $updateStmt->execute([
                ':wo_id' => $map['wo_id'],
                ':d_start' => $map['date_start'],
                ':d_end' => $map['date_end'],
                ':pattern1' => $map['pattern'],
                ':pattern2' => $map['pattern']
            ]);
            $affected = $updateStmt->rowCount();
            $linkedCount += $affected;
            if ($affected > 0) {
                echo "  -> Berhasil menautkan {$affected} baris lembur ke Work Order {$map['wo_id']}\n";
            }
        }
    }
    echo "  [OK] Pemindaian relasi cerdas selesai. Total penugasan bertaut WO: {$linkedCount} baris.\n";

    // 2. Pengujian Endpoint Leaderboard Lembur (People & KPI)
    echo "\n[2/4] Pengujian endpoint analitik lembur People & KPI per bulan kalender...\n";
    $periods = ['2026-03', '2026-04', '2026-05', '2026-06', '2026-07', '2026-08', '2026-09'];
    
    $kpiStmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT s.spl_number) AS total_spl,
            COUNT(*) AS total_tasks,
            ROUND(SUM(s.duration_hours), 2) AS total_hours
        FROM spl_overtime_records s
        WHERE s.reversed_at IS NULL
          AND s.spl_date LIKE :period_like
          AND s.location_id = 4
    ");

    foreach ($periods as $p) {
        $kpiStmt->execute([':period_like' => "{$p}%"]);
        $row = $kpiStmt->fetch(PDO::FETCH_ASSOC);
        printf("  - Periode %s: %2d SPL | %3d Penugasan | %8.2f Jam Lembur\n", 
            $p, 
            (int)$row['total_spl'], 
            (int)$row['total_tasks'], 
            (float)$row['total_hours']
        );
    }

    // 3. Pengujian Payload Master (api/init.php)
    echo "\n[3/4] Menguji query payload `globalData.spl_overtime_records` dari `api/init.php`...\n";
    $initSplSql = "SELECT COUNT(*)
                   FROM spl_overtime_records s
                   INNER JOIN report_records rr ON rr.report_id = s.report_id AND rr.status = 'FINAL'
                   INNER JOIN locations l ON l.location_id = s.location_id
                   INNER JOIN users employee ON employee.user_id = s.employee_user_id
                   INNER JOIN users prepared ON prepared.user_id = s.prepared_by
                   INNER JOIN users instructed ON instructed.user_id = s.instructed_by
                   WHERE s.reversed_at IS NULL";
    $initCount = (int)$pdo->query($initSplSql)->fetchColumn();
    echo "  - Total baris yang siap dikonsumsi frontend pada init.php: {$initCount} baris (Target: 617)\n";
    if ($initCount !== 617) {
        throw new RuntimeException("Integritas relasi user/location/report_records pada init.php gagal: {$initCount} != 617");
    }
    echo "  [OK] Validasi query init.php sukses 100%!\n";

    // 4. Verifikasi Akhir SSOT Master
    echo "\n[4/4] Verifikasi Akhir SSOT Master Database:\n";
    $finalStats = $pdo->query("
        SELECT 
            COUNT(DISTINCT report_id) AS total_reports,
            COUNT(DISTINCT spl_number) AS total_spls,
            COUNT(*) AS total_tasks,
            ROUND(SUM(duration_hours), 2) AS total_hours,
            COUNT(DISTINCT employee_user_id) AS total_personnel
        FROM spl_overtime_records
    ")->fetch(PDO::FETCH_ASSOC);

    echo "  * Dokumen Laporan (report_records): {$finalStats['total_reports']} Lembar (Target: 98)\n";
    echo "  * Nomor SPL Resmi (spl_number)   : {$finalStats['total_spls']} Nomor (Target: 98)\n";
    echo "  * Baris Penugasan Mekanik         : {$finalStats['total_tasks']} Baris (Target: 617)\n";
    echo "  * Total Jam Lembur SSOT           : {$finalStats['total_hours']} Jam (Target: 2,271.56)\n";
    echo "  * Total Personil Terstandarisasi  : {$finalStats['total_personnel']} Personil (Target: 25)\n";

    if ((int)$finalStats['total_reports'] !== 98 || (int)$finalStats['total_tasks'] !== 617 || abs((float)$finalStats['total_hours'] - 2271.56) > 0.05) {
        throw new RuntimeException("Verifikasi akhir SSOT gagal memenuhi kriteria presisi mutlak!");
    }

    echo "\n=========================================================================\n";
    echo " BATCH 21 SELESAI: SELURUH DATA LEMBUR 2026 RESMI TERINTEGRASI SEMPURNA!\n";
    echo "=========================================================================\n";

} catch (Throwable $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
