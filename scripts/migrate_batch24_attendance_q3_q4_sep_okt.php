<?php
/**
 * Migration Script: Batch 24 - Ingesti Absensi & Lembur Q3–Q4 (September & Oktober 2026)
 * Repositori: ServicePlan-BRA
 * Tanggal: 30 Oktober 2026
 * 
 * Cakupan:
 * 1. Pendaftaran 2 header dokumen resmi pada `report_records` (status: 'FINAL', template: 'rekap-absensi-yard').
 * 2. Injeksi 56 entri rekapitulasi presensi bulanan ke `employee_attendance_monthly`.
 * 3. Injeksi 1.705 entri log presensi harian ke `employee_attendance_daily`.
 * 4. Verifikasi presisi total jam lembur timesheet: September (171,00 Jam) & Oktober (328,50 Jam).
 */

declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';

try {
    $pdo = Database::getInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=========================================================================\n";
    echo " ServicePlan-BRA: Migration Batch 24 - Absensi Q3-Q4 (Sept & Okt 2026)\n";
    echo "=========================================================================\n\n";

    // 1. Resolve Template ID
    $tplStmt = $pdo->prepare("SELECT template_id FROM report_templates WHERE template_key = 'rekap-absensi-yard' LIMIT 1");
    $tplStmt->execute();
    $templateId = (int)$tplStmt->fetchColumn();
    if (!$templateId) {
        throw new RuntimeException("Template `rekap-absensi-yard` tidak ditemukan.");
    }
    echo "[1/4] Menggunakan Template Absensi: template_id = {$templateId}\n";

    // 2. Load User Resolver Map
    $users = $pdo->query("SELECT user_id, username, full_name FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $userMap = [];
    foreach ($users as $u) {
        $userMap[strtolower(trim($u['username']))] = (int)$u['user_id'];
        $userMap[strtolower(trim($u['full_name']))] = (int)$u['user_id'];
        $clean = strtolower(trim(preg_replace('/\s*\(.*?\)\s*/', '', $u['full_name'])));
        $userMap[$clean] = (int)$u['user_id'];
    }

    $aliases = [
        'suwardi' => 'suwardi',
        'afriyandi' => 'afriyandi',
        'darmawan' => 'darmawan',
        'martono' => 'martono',
        'samsul bahri' => 'samsul_bahri',
        'joni' => 'joni_septian',
        'joni septian' => 'joni_septian',
        'rezeki siregar' => 'rezeki_siregar',
        'rejeki siregar' => 'rezeki_siregar',
        'rahmad k' => 'rahmad_k',
        'rahmad kamandanu' => 'rahmad_k',
        'risman' => 'risman',
        'firlanda' => 'firlanda',
        'hendrik' => 'hendrik',
        'daniel sitepu' => 'daniel_sitepu',
        'faikar' => 'faikar',
        'fajar' => 'fajar_mekanik',
        'gabriel' => 'gabriel',
        'm.all ramadhan' => 'muhammad_all',
        'muhammad all' => 'muhammad_all',
        'taufiq h' => 'taufiq_h',
        'jorlan' => 'jorlan_sibatuara',
        'jorlan sibatuara' => 'jorlan_sibatuara',
        'sudirman h' => 'sudirman_h',
        'edy jaka s' => 'edy_jaka_s',
        'amirudin' => 'amirudin',
        'koeswanto' => 'koeswanto',
        'nurdin' => 'nurdin',
        'sujono' => 'sujono',
        'fadli.h' => 'fadli_h',
        'fadli h' => 'fadli_h',
        'david aritonang' => 'david_aritonang',
        'ahnaf' => 'ahnaf',
        'aditya' => 'aditya',
        'disma' => 'disma',
        'juliadi' => 'juliadi',
        'rizky' => 'rizky',
        'sunardi' => 'sunardi',
        'faren' => 'varen',
        'varen' => 'varen',
    ];

    $resolveUser = static function (string $name) use ($aliases, $userMap): ?int {
        $clean = strtolower(trim($name));
        if (isset($aliases[$clean]) && isset($userMap[$aliases[$clean]])) {
            return $userMap[$aliases[$clean]];
        }
        if (isset($userMap[$clean])) {
            return $userMap[$clean];
        }
        return null;
    };

    $formatTime = static function ($raw): ?string {
        if ($raw === null || $raw === '' || $raw === '-') return null;
        $t = trim((string)$raw);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $t, $m)) {
            return sprintf('%02d:%02d:%02d', (int)$m[1], (int)$m[2], (int)($m[3] ?? 0));
        }
        if (is_numeric($t)) {
            $num = (float)$t;
            if ($num > 0 && $num < 1.0) {
                $totalSec = (int)round($num * 86400);
                $h = (int)floor($totalSec / 3600);
                $m = (int)floor(($totalSec % 3600) / 60);
                $s = $totalSec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            }
        }
        return null;
    };

    // 3. Definition of Period Configs for September & October 2026
    $baseDir = __DIR__ . '/../material/ABSENSI_DAN_LEMBUR_MEI_OKTOBER_2026/excel';
    $periods = [
        [
            'folder' => '09_september_2026_yard_km12',
            'period_key' => '2026-09',
            'title' => 'Rekapitulasi Absensi & Lembur Yard KM 12 - September 2026',
            'start_date' => '2026-08-21',
            'end_date' => '2026-09-20',
            'start_year' => 2026, 'start_month' => 8,
            'end_year' => 2026, 'end_month' => 9,
            'created_at' => '2026-09-21 17:00:00',
            'report_number' => 'ABSEN-202609-KM12',
            'expected_ot' => 171.00
        ],
        [
            'folder' => '10_oktober_2026_yard_km12',
            'period_key' => '2026-10',
            'title' => 'Rekapitulasi Absensi & Lembur Yard KM 12 - Oktober 2026',
            'start_date' => '2026-09-21',
            'end_date' => '2026-10-20',
            'start_year' => 2026, 'start_month' => 9,
            'end_year' => 2026, 'end_month' => 10,
            'created_at' => '2026-10-21 17:00:00',
            'report_number' => 'ABSEN-202610-KM12',
            'expected_ot' => 328.50
        ]
    ];

    echo "\n[2/4] Parsing data Excel `04_absen.md` September & Oktober 2026...\n";
    $parsedPeriods = [];

    foreach ($periods as $cfg) {
        $filePath = "{$baseDir}/{$cfg['folder']}/04_absen.md";
        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        $cells = [];
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 6 && $parts[1] !== '') {
                $cells[$parts[1]] = $parts[3];
            }
        }

        $dateColMap = [];
        $summaryColMap = [];
        $passedDay1 = false;

        $colList = [];
        for ($c = 'Q'; $c !== 'AA'; $c++) $colList[] = $c;
        for ($pfx = 'A'; $pfx <= 'B'; $pfx++) {
            for ($c = 'A'; $c <= 'Z'; $c++) $colList[] = $pfx . $c;
        }

        foreach ($colList as $col) {
            $val = $cells[$col . '10'] ?? null;
            if ($val === null) continue;
            $valTrim = trim($val);
            if (is_numeric($valTrim)) {
                $dNum = (int)$valTrim;
                if ($dNum === 1) $passedDay1 = true;
                $y = $passedDay1 ? $cfg['end_year'] : $cfg['start_year'];
                $m = $passedDay1 ? $cfg['end_month'] : $cfg['start_month'];
                $dateColMap[$col] = sprintf('%04d-%02d-%02d', $y, $m, $dNum);
            } else {
                $normKey = strtoupper(trim(str_replace(['∑', 'TOTAL', ' '], '', $valTrim)));
                if (in_array($normKey, ['KJ', 'KL', 'O', 'CT', 'S', 'I', 'MK', 'PR', 'JO', 'LEMBUR'], true)) {
                    $summaryColMap[$normKey] = $col;
                }
            }
        }

        $employees = [];
        $periodOt = 0.0;

        for ($row = 12; $row <= 250; $row++) {
            $oVal = trim($cells['O' . $row] ?? '');
            $nameVal = trim($cells['D' . $row] ?? '');
            if ($oVal === 'In' && $nameVal !== '' && $nameVal !== 'NAMA KARYAWAN') {
                $userId = $resolveUser($nameVal);
                if (!$userId) {
                    throw new RuntimeException("Tidak dapat menyelesaikan user_id untuk '{$nameVal}' pada row {$row}");
                }

                $pos = trim($cells['L' . $row] ?? '-');
                $kj = (int)($cells[($summaryColMap['KJ'] ?? 'AV') . $row] ?? 0);
                $kl = (int)($cells[($summaryColMap['KL'] ?? 'AW') . $row] ?? 0);
                $off = (int)($cells[($summaryColMap['O'] ?? 'AX') . $row] ?? 0);
                $ct = (int)($cells[($summaryColMap['CT'] ?? 'AY') . $row] ?? 0);
                $s = (int)($cells[($summaryColMap['S'] ?? 'AZ') . $row] ?? 0);
                $i = (int)($cells[($summaryColMap['I'] ?? 'BA') . $row] ?? 0);
                $mk = (int)($cells[($summaryColMap['MK'] ?? 'BB') . $row] ?? 0);
                $pr = (int)($cells[($summaryColMap['PR'] ?? 'BC') . $row] ?? 0);

                $joRaw = str_replace(',', '.', (string)($cells[($summaryColMap['JO'] ?? 'BD') . $row] ?? 0));
                $jo = (float)$joRaw;

                $otRaw = str_replace(',', '.', (string)($cells[($summaryColMap['LEMBUR'] ?? 'BE') . $row] ?? 0));
                $ot = (float)$otRaw;
                $periodOt += $ot;

                $inRow = $row;
                $outRow = $row + 1;
                $absenRow = $row + 2;
                $joRow = $row + 3;
                $lemburRow = $row + 4;

                $dailyLogs = [];
                foreach ($dateColMap as $col => $dateIso) {
                    $status = trim($cells[$col . $absenRow] ?? '-');
                    if ($status === '' || $status === '-') {
                        $status = 'O';
                    }
                    $cin = $formatTime($cells[$col . $inRow] ?? null);
                    $cout = $formatTime($cells[$col . $outRow] ?? null);
                    $dOtRaw = str_replace(',', '.', (string)($cells[$col . $lemburRow] ?? 0));
                    $dOt = is_numeric($dOtRaw) ? (float)$dOtRaw : 0.0;

                    $dailyLogs[] = [
                        'date' => $dateIso,
                        'status' => $status,
                        'clock_in' => $cin,
                        'clock_out' => $cout,
                        'overtime_hours' => $dOt
                    ];
                }

                $employees[] = [
                    'name' => $nameVal,
                    'user_id' => $userId,
                    'role_snapshot' => $pos,
                    'department' => 'Yard KM 12',
                    'kj' => $kj,
                    'kl' => $kl,
                    'off' => $off,
                    'ct' => $ct,
                    's' => $s,
                    'i' => $i,
                    'mk' => $mk,
                    'pr' => $pr,
                    'total_work_days' => $jo,
                    'total_overtime_hours' => $ot,
                    'daily_logs' => $dailyLogs
                ];
            }
        }

        echo "  - Periode {$cfg['period_key']}: " . count($employees) . " Karyawan | Total Lembur: " . number_format($periodOt, 2) . " Jam\n";
        if (abs($periodOt - $cfg['expected_ot']) > 0.01) {
            throw new RuntimeException("Mismatch total lembur pada periode {$cfg['period_key']}: {$periodOt} != {$cfg['expected_ot']}");
        }

        $cfg['employees'] = $employees;
        $cfg['total_ot'] = $periodOt;
        $parsedPeriods[] = $cfg;
    }

    // 4. Database Transaction Ingestion
    echo "\n[3/4] Mengeksekusi transaksi database untuk periode September & Oktober 2026...\n";
    $pdo->beginTransaction();

    $stmtReport = $pdo->prepare("
        INSERT INTO report_records (
            report_id, template_id, client_key, report_number, status, source_method,
            field_data, standardized_payload, created_by, created_at, updated_at, finalized_at, final_number_key
        ) VALUES (
            :report_id, :template_id, :client_key, :report_number, 'FINAL', 'batch_attendance_2026',
            :field_data, :standardized_payload, 53, :created_at, :updated_at, :finalized_at, :final_number_key
        )
        ON DUPLICATE KEY UPDATE
            field_data = VALUES(field_data),
            standardized_payload = VALUES(standardized_payload),
            updated_at = NOW(),
            finalized_at = VALUES(finalized_at)
    ");

    $stmtDeleteMonthly = $pdo->prepare("DELETE FROM employee_attendance_monthly WHERE report_id = :report_id");
    
    $stmtInsertMonthly = $pdo->prepare("
        INSERT INTO employee_attendance_monthly (
            attendance_month_id, report_id, user_id, period_key, period_start, period_end,
            location_id, role_snapshot, department, kj_days, kl_days, off_days, cuti_days,
            sakit_days, izin_days, mangkir_days, project_days, total_work_days, total_overtime_hours,
            created_at
        ) VALUES (
            :att_id, :report_id, :user_id, :period_key, :period_start, :period_end,
            4, :role_snapshot, :department, :kj, :kl, :off, :ct,
            :s, :i, :mk, :pr, :jo, :ot, :created_at
        )
        ON DUPLICATE KEY UPDATE
            kj_days = VALUES(kj_days),
            kl_days = VALUES(kl_days),
            off_days = VALUES(off_days),
            cuti_days = VALUES(cuti_days),
            sakit_days = VALUES(sakit_days),
            izin_days = VALUES(izin_days),
            mangkir_days = VALUES(mangkir_days),
            project_days = VALUES(project_days),
            total_work_days = VALUES(total_work_days),
            total_overtime_hours = VALUES(total_overtime_hours),
            updated_at = NOW()
    ");

    $stmtInsertDaily = $pdo->prepare("
        INSERT INTO employee_attendance_daily (
            attendance_month_id, user_id, log_date, status_code, clock_in, clock_out, overtime_hours, created_at
        ) VALUES (
            :att_id, :user_id, :log_date, :status_code, :clock_in, :clock_out, :overtime_hours, :created_at
        )
        ON DUPLICATE KEY UPDATE
            status_code = VALUES(status_code),
            clock_in = VALUES(clock_in),
            clock_out = VALUES(clock_out),
            overtime_hours = VALUES(overtime_hours)
    ");

    $totalInsertedMonthly = 0;
    $totalInsertedDaily = 0;

    foreach ($parsedPeriods as $pData) {
        $reportId = sprintf('absen-%s-km12', $pData['period_key']);
        $finalNumberKey = "ABSEN:{$pData['report_number']}";

        $fieldData = [
            'periode_label' => "{$pData['start_date']} s.d. {$pData['end_date']}",
            'bulan_kalender' => $pData['period_key'],
            'lokasi' => 'Workshop Bay KM 12 Kulim',
            'total_karyawan' => count($pData['employees']),
            'total_lembur' => $pData['total_ot'],
            'disetujui_oleh' => 'Dany Agung (Head of Equipment)'
        ];

        $standardizedRows = [];
        foreach ($pData['employees'] as $emp) {
            $standardizedRows[] = [
                'nama' => $emp['name'],
                'jabatan' => $emp['role_snapshot'],
                'kategori' => $emp['department'],
                'kj' => $emp['kj'],
                'kl' => $emp['kl'],
                'off' => $emp['off'],
                'cuti' => $emp['ct'],
                'sakit' => $emp['s'],
                'izin' => $emp['i'],
                'mangkir' => $emp['mk'],
                'total_jo' => $emp['total_work_days'],
                'total_lembur' => $emp['total_overtime_hours']
            ];
        }

        $stmtReport->execute([
            ':report_id' => $reportId,
            ':template_id' => $templateId,
            ':client_key' => $pData['report_number'],
            ':report_number' => $pData['report_number'],
            ':field_data' => json_encode($fieldData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':standardized_payload' => json_encode($standardizedRows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':created_at' => $pData['created_at'],
            ':updated_at' => $pData['created_at'],
            ':finalized_at' => $pData['created_at'],
            ':final_number_key' => $finalNumberKey
        ]);

        $stmtDeleteMonthly->execute([':report_id' => $reportId]);

        foreach ($pData['employees'] as $emp) {
            $attMonthId = sprintf('att-%s-u%03d', $pData['period_key'], $emp['user_id']);

            $stmtInsertMonthly->execute([
                ':att_id' => $attMonthId,
                ':report_id' => $reportId,
                ':user_id' => $emp['user_id'],
                ':period_key' => $pData['period_key'],
                ':period_start' => $pData['start_date'],
                ':period_end' => $pData['end_date'],
                ':role_snapshot' => $emp['role_snapshot'],
                ':department' => $emp['department'],
                ':kj' => $emp['kj'],
                ':kl' => $emp['kl'],
                ':off' => $emp['off'],
                ':ct' => $emp['ct'],
                ':s' => $emp['s'],
                ':i' => $emp['i'],
                ':mk' => $emp['mk'],
                ':pr' => $emp['pr'],
                ':jo' => $emp['total_work_days'],
                ':ot' => $emp['total_overtime_hours'],
                ':created_at' => $pData['created_at']
            ]);
            $totalInsertedMonthly++;

            foreach ($emp['daily_logs'] as $dLog) {
                $stmtInsertDaily->execute([
                    ':att_id' => $attMonthId,
                    ':user_id' => $emp['user_id'],
                    ':log_date' => $dLog['date'],
                    ':status_code' => $dLog['status'],
                    ':clock_in' => $dLog['clock_in'],
                    ':clock_out' => $dLog['clock_out'],
                    ':overtime_hours' => $dLog['overtime_hours'],
                    ':created_at' => $pData['created_at']
                ]);
                $totalInsertedDaily++;
            }
        }
    }

    $pdo->commit();
    echo "  [OK] Seluruh data presensi & lembur Q3–Q4 berhasil disimpan ke database!\n";

    // 5. Verifikasi Integritas Database
    echo "\n[4/4] Verifikasi Integritas & Rekonsiliasi Database...\n";
    $stats = $pdo->query("
        SELECT 
            period_key,
            COUNT(DISTINCT user_id) AS total_users,
            ROUND(SUM(kj_days), 0) AS total_kj,
            ROUND(SUM(total_overtime_hours), 2) AS total_ot
        FROM employee_attendance_monthly
        WHERE period_key IN ('2026-09', '2026-10')
        GROUP BY period_key
        ORDER BY period_key ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stats as $st) {
        echo "  - Ringkasan {$st['period_key']}: {$st['total_users']} Karyawan | {$st['total_kj']} Hari Kerja | {$st['total_ot']} Jam Lembur\n";
    }

    $b24DailyCount = (int)$pdo->query("
        SELECT COUNT(*) 
        FROM employee_attendance_daily d
        JOIN employee_attendance_monthly m ON d.attendance_month_id = m.attendance_month_id
        WHERE m.period_key IN ('2026-09', '2026-10')
    ")->fetchColumn();

    echo "  * Total Log Harian Tersimpan (Batch 24): {$b24DailyCount} entri (Target: 1.705)\n";

    if ($b24DailyCount !== 1705) {
        throw new RuntimeException("Mismatch total log harian Batch 24: {$b24DailyCount} != 1705");
    }

    $cumulativeStats = $pdo->query("
        SELECT 
            COUNT(DISTINCT report_id) AS total_reports,
            COUNT(*) AS total_monthly_records,
            ROUND(SUM(total_overtime_hours), 2) AS cumulative_ot
        FROM employee_attendance_monthly
        WHERE period_key IN ('2026-07', '2026-08', '2026-09', '2026-10')
    ")->fetch(PDO::FETCH_ASSOC);

    echo "\n  - Ringkasan Kumulatif 4 Periode (Juli s/d Oktober 2026):\n";
    echo "    * Total Dokumen Resmi Laporan: {$cumulativeStats['total_reports']} Dokumen (Target: 4)\n";
    echo "    * Total Rekap Bulanan Karyawan: {$cumulativeStats['total_monthly_records']} Rekap (Target: 106)\n";
    echo "    * Total Jam Lembur Timesheet : {$cumulativeStats['cumulative_ot']} Jam (Target: 886.00 Jam)\n";

    if ((int)$cumulativeStats['total_monthly_records'] !== 106 || abs((float)$cumulativeStats['cumulative_ot'] - 886.00) > 0.05) {
        throw new RuntimeException("Mismatch pada verifikasi kumulatif 4 periode absensi!");
    }

    echo "\n=========================================================================\n";
    echo " BATCH 24 SELESAI: ABSENSI & LEMBUR Q3-Q4 RESMI TERSIMPAN SEMPURNA!\n";
    echo "=========================================================================\n";

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

