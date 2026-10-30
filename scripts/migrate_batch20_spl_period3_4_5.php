<?php
/**
 * Migration Script: Batch 20 - Penyuntikan & Rekonsiliasi SPL Periode 3, 4, & 5 (Juni – September 2026)
 * Repositori: ServicePlan-BRA
 * Tanggal: 30 Oktober 2026
 * 
 * Cakupan:
 * - Ingesti 61 lembar dokumen fisik SPL (18 Periode 3 + 24 Periode 4 + 19 Periode 5)
 * - Pembentukan 61 header resmi `report_records` (status: 'FINAL', template: 'spl-overtime')
 * - Penyuntikan 348 baris penugasan ke `spl_overtime_records` (total: 1,371.50 Jam Lembur)
 * - Penyelarasan SSOT: location_id = 4, prepared_by = 53 (Samsul Bahri), instructed_by = 2 (Dany Agung)
 * - Verifikasi Kumulatif Master: Tepat 98 lembar dan 2,271.56 Jam Lembur (Batch 19 + Batch 20)
 */

declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';

try {
    $pdo = Database::getInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=========================================================================\n";
    echo " ServicePlan-BRA: Migration Batch 20 - SPL Periode 3, 4, & 5\n";
    echo "=========================================================================\n\n";

    // 1. Resolve Template ID for SPL
    $tplStmt = $pdo->prepare("SELECT template_id FROM report_templates WHERE template_key = 'spl-overtime' OR code = 'SPL' LIMIT 1");
    $tplStmt->execute();
    $templateId = (int) $tplStmt->fetchColumn();
    if (!$templateId) {
        throw new RuntimeException("Template SPL tidak ditemukan pada tabel `report_templates`.");
    }
    echo "[1/4] Menggunakan Template SPL resmi: template_id={$templateId}\n";

    // 2. Load and verify User Mapping
    $userQuery = $pdo->query("SELECT user_id, username, full_name, role_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $userMap = [];
    foreach ($userQuery as $u) {
        $userMap[strtolower(trim($u['username']))] = $u;
        $userMap[strtolower(trim($u['full_name']))] = $u;
    }

    $aliases = [
        'afriyandi' => 'afriyandi',
        'darmawan' => 'darmawan',
        'joni septian' => 'joni_septian',
        'suwardi' => 'suwardi',
        'rejeki siregar' => 'rezeki_siregar',
        'rezeki siregar' => 'rezeki_siregar',
        'rahmad kamandanu' => 'rahmad_k',
        'rahmad k' => 'rahmad_k',
        'risman' => 'risman',
        'firlanda' => 'firlanda',
        'hendrik' => 'hendrik',
        'rahmat' => 'rahmat',
        'daniel sitepu' => 'daniel_sitepu',
        'gabriel' => 'gabriel',
        'faikar' => 'faikar',
        'martono' => 'martono',
        'agung s' => 'agung_s',
        'alficara' => 'alficara',
        'muhammad all' => 'muhammad_all',
        'm all' => 'muhammad_all',
        'muhamad all' => 'muhammad_all',
        'fajar' => 'fajar_mekanik',
        'luori' => 'luori',
        'varen' => 'varen',
        'samsul bahri' => 'samsul_bahri',
        'mekanik lapangan' => 'mekanik',
        'uska' => 'uska',
        'arif rendi' => 'arif_rendi',
        'tewdnao' => 'tewdnao',
    ];

    $resolveEmployee = static function (string $name) use ($aliases, $userMap): ?int {
        $c = strtolower(trim(str_replace(['**', '*'], '', $name)));
        if (isset($aliases[$c]) && isset($userMap[$aliases[$c]])) {
            return (int) $userMap[$aliases[$c]]['user_id'];
        }
        if (isset($userMap[$c])) {
            return (int) $userMap[$c]['user_id'];
        }
        return null;
    };

    // 3. Define Sources for Period 3, 4, 5
    $splDir = __DIR__ . '/../material/DATA-SPL';
    $files = [
        '03' => [
            'path' => $splDir . '/03_SPL_Periode_21_Juni_20_Juli_2026.md',
            'p_start' => '2026-06-21',
            'p_end' => '2026-07-20',
            'fallback' => [
                2 => '2026-07-18',
                5 => '2026-07-16',
                9 => '2026-07-05',
                13 => '2026-06-27',
                15 => '2026-06-24',
            ]
        ],
        '04' => [
            'path' => $splDir . '/04_SPL_Periode_21_Juli_20_Agustus_2026.md',
            'p_start' => '2026-07-21',
            'p_end' => '2026-08-20',
            'fallback' => [
                15 => '2026-08-01',
            ]
        ],
        '05' => [
            'path' => $splDir . '/05_SPL_Periode_21_Agustus_20_September_2026.md',
            'p_start' => '2026-08-21',
            'p_end' => '2026-09-20',
            'fallback' => []
        ],
    ];

    function normalizeMonth(string $mStr): ?string {
        $m = strtolower(trim($mStr));
        if (strpos($m, 'jan') !== false) return '01';
        if (strpos($m, 'feb') !== false) return '02';
        if (strpos($m, 'mar') !== false) return '03';
        if (strpos($m, 'apr') !== false) return '04';
        if (strpos($m, 'mei') !== false || strpos($m, 'may') !== false) return '05';
        if (strpos($m, 'jun') !== false) return '06';
        if (strpos($m, 'jul') !== false) return '07';
        if (strpos($m, 'agu') !== false || strpos($m, 'ags') !== false) return '08';
        if (strpos($m, 'sep') !== false) return '09';
        if (strpos($m, 'okt') !== false || strpos($m, 'oct') !== false) return '10';
        if (strpos($m, 'nov') !== false) return '11';
        if (strpos($m, 'des') !== false || strpos($m, 'dec') !== false) return '12';
        return null;
    }

    function parseSplDate(string $rawStr, ?string $fallback = null): ?string {
        $raw = trim($rawStr);
        // DD/MM/YYYY
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // DD Month YYYY
        if (preg_match('/(\d{1,2})\s*([A-Za-z]+)\s*(\d{4})/', $raw, $m)) {
            $mNum = normalizeMonth($m[2]);
            if ($mNum) {
                return sprintf('%04d-%02d-%02d', $m[3], $mNum, $m[1]);
            }
        }
        return $fallback;
    }

    echo "[2/4] Parsing berkas Markdown SPL Periode 03, 04, dan 05...\n";
    $sheets = [];
    foreach ($files as $pCode => $cfg) {
        $content = file_get_contents($cfg['path']);
        $lines = explode("\n", $content);
        $curSheet = null;
        $inRows = false;
        
        foreach ($lines as $line) {
            $trim = trim($line);
            if (preg_match('/^### Lembar SPL #(\d+): (.*)/i', $trim, $m)) {
                if ($curSheet) {
                    $sheets[] = $curSheet;
                }
                $sIdx = (int)$m[1];
                $curSheet = [
                    'period' => $pCode,
                    'period_start' => $cfg['p_start'],
                    'period_end' => $cfg['p_end'],
                    'sheet_idx' => $sIdx,
                    'title' => $m[2],
                    'date_str' => null,
                    'pelaksanaan_str' => null,
                    'date_iso' => null,
                    'spl_number' => null,
                    'location' => 'Workshop Bay KM 12',
                    'location_id' => 4,
                    'target' => '-',
                    'instructed_by_id' => 2, // Dany Agung
                    'prepared_by_id' => 53, // Samsul Bahri
                    'rows' => []
                ];
                $inRows = false;
                continue;
            }
            
            if ($curSheet) {
                if (preg_match('/- \*\*Tanggal SPL:\*\*\s*(.*)/i', $trim, $m)) {
                    $curSheet['date_str'] = trim($m[1]);
                } elseif (preg_match('/- \*\*Hari \/ Tanggal Pelaksanaan:\*\*\s*(.*)/i', $trim, $m)) {
                    $curSheet['pelaksanaan_str'] = trim($m[1]);
                } elseif (preg_match('/- \*\*Target Waktu:\*\*\s*(.*)/i', $trim, $m)) {
                    $curSheet['target'] = trim($m[1]);
                }
                
                if (strpos($trim, '#### Tabulasi Rincian Pekerjaan Lembur') !== false) {
                    $inRows = true;
                    continue;
                }
                if (strpos($trim, '#### Pengesahan / Tanda Tangan Dokumen') !== false || (strpos($trim, '---') === 0 && $inRows)) {
                    $inRows = false;
                }
                
                if ($inRows && strpos($trim, '|') === 0) {
                    $cols = array_map('trim', explode('|', $trim));
                    if (count($cols) >= 8 && is_numeric($cols[1])) {
                        $empName = str_replace(['**', '*'], '', $cols[2]);
                        $roleLabel = str_replace(['**', '*'], '', $cols[3]);
                        $job = $cols[4];
                        $start = $cols[5];
                        $end = $cols[6];
                        $hours = (float)str_replace(['Jam', 'jam', ' '], '', $cols[7]);
                        
                        if (preg_match('/^(\d{1,2}):(\d{2})$/', $start, $sm)) {
                            $start = sprintf('%02d:%02d:00', $sm[1], $sm[2]);
                        }
                        if (preg_match('/^(\d{1,2}):(\d{2})$/', $end, $em)) {
                            $end = sprintf('%02d:%02d:00', $em[1], $em[2]);
                        }
                        
                        $userId = $resolveEmployee($empName);
                        if (!$userId) {
                            throw new RuntimeException("Tidak dapat menemukan user_id untuk: {$empName} pada sheet P{$curSheet['period']} #{$curSheet['sheet_idx']}");
                        }
                        
                        $curSheet['rows'][] = [
                            'name' => $empName,
                            'user_id' => $userId,
                            'role_snapshot' => $roleLabel,
                            'job' => $job,
                            'start' => $start,
                            'end' => $end,
                            'duration' => $hours,
                            'status' => $cols[8] ?? 'Valid (Verified)'
                        ];
                    }
                }
            }
        }
        if ($curSheet) {
            $sheets[] = $curSheet;
        }
    }

    // Resolve date and SPL canonical number for all 61 sheets
    foreach ($sheets as &$sh) {
        $fb = $files[$sh['period']]['fallback'][$sh['sheet_idx']] ?? null;
        $dateIso = parseSplDate($sh['date_str'] ?? '', null);
        if (!$dateIso && !empty($sh['pelaksanaan_str'])) {
            $dateIso = parseSplDate($sh['pelaksanaan_str'], null);
        }
        if (!$dateIso) {
            $dateIso = $fb;
        }
        if (!$dateIso) {
            throw new RuntimeException("Gagal menentukan tanggal ISO untuk P{$sh['period']} Sheet #{$sh['sheet_idx']}");
        }
        $sh['date_iso'] = $dateIso;
        $cleanDate = str_replace('-', '', $dateIso);
        $sh['spl_number'] = sprintf('SPL-%s-%02d', $cleanDate, $sh['sheet_idx']);
    }
    unset($sh);

    echo "  [OK] Berhasil mem-parsing " . count($sheets) . " lembar SPL Periode 3, 4, & 5.\n";

    // 4. Database Transaction Ingestion
    echo "\n[3/4] Mengeksekusi transaksi database untuk 61 lembar SPL...\n";
    $pdo->beginTransaction();

    $stmtReport = $pdo->prepare("
        INSERT INTO report_records (
            report_id, template_id, client_key, report_number, status, source_method,
            field_data, standardized_payload, created_by, created_at, updated_at, finalized_at, final_number_key
        ) VALUES (
            :report_id, :template_id, :client_key, :report_number, 'FINAL', 'batch_spl_2026',
            :field_data, :standardized_payload, :created_by, :created_at, :updated_at, :finalized_at, :final_number_key
        )
        ON DUPLICATE KEY UPDATE
            field_data = VALUES(field_data),
            standardized_payload = VALUES(standardized_payload),
            updated_at = NOW(),
            finalized_at = VALUES(finalized_at)
    ");

    $stmtDeleteOvertime = $pdo->prepare("DELETE FROM spl_overtime_records WHERE report_id = :report_id");

    $stmtOvertime = $pdo->prepare("
        INSERT INTO spl_overtime_records (
            report_id, report_item_position, spl_number, wo_id, spl_date, period_start, period_end,
            location_id, employee_user_id, role_snapshot, start_time, end_time, duration_hours,
            activity_description, target_description, general_instruction, prepared_by,
            instructed_by, start_verified, end_verified, created_by, created_at
        ) VALUES (
            :report_id, :report_item_position, :spl_number, :wo_id, :spl_date, :period_start, :period_end,
            :location_id, :employee_user_id, :role_snapshot, :start_time, :end_time, :duration_hours,
            :activity_description, :target_description, :general_instruction, :prepared_by,
            :instructed_by, 1, 1, :created_by, :created_at
        )
    ");

    $totalInsertedSheets = 0;
    $totalInsertedTasks = 0;
    $totalInsertedHours = 0.0;

    foreach ($sheets as $sh) {
        $hashSuffix = substr(md5($sh['spl_number'] . '-p' . $sh['period']), 0, 16);
        $reportId = sprintf('spl-%s-%s', strtolower($sh['spl_number']), $hashSuffix);
        $reportId = substr($reportId, 0, 36);

        $fieldData = [
            'nomor_spl' => $sh['spl_number'],
            'tanggal_spl' => $sh['date_iso'],
            'mulai_periode' => $sh['period_start'],
            'akhir_periode' => $sh['period_end'],
            'lokasi' => $sh['location'],
            'dibuat_oleh' => 'Samsul Bahri (Admin Equipment)',
            'instruksi_oleh' => 'Dany Agung (Head of Equipment)',
            'target' => $sh['target'],
            'uraian' => $sh['title'],
            'wo_id' => null
        ];

        $standardizedRows = [];
        foreach ($sh['rows'] as $r) {
            $standardizedRows[] = [
                'nama' => $r['name'],
                'jabatan' => $r['role_snapshot'],
                'lokasi' => $sh['location'],
                'uraian' => $r['job'],
                'mulai' => $r['start'],
                'selesai' => $r['end'],
                'durasi' => $r['duration'],
                'verifikasi_mulai' => 'Terverifikasi',
                'verifikasi_selesai' => 'Terverifikasi',
            ];
        }

        $createdAt = "{$sh['date_iso']} 17:00:00";
        $finalizedAt = "{$sh['date_iso']} 18:00:00";
        $finalNumberKey = "SPL:{$sh['spl_number']}";

        $stmtReport->execute([
            ':report_id' => $reportId,
            ':template_id' => $templateId,
            ':client_key' => $sh['spl_number'],
            ':report_number' => $sh['spl_number'],
            ':field_data' => json_encode($fieldData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':standardized_payload' => json_encode($standardizedRows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':created_by' => $sh['prepared_by_id'],
            ':created_at' => $createdAt,
            ':updated_at' => $finalizedAt,
            ':finalized_at' => $finalizedAt,
            ':final_number_key' => $finalNumberKey
        ]);

        $stmtDeleteOvertime->execute([':report_id' => $reportId]);

        foreach ($sh['rows'] as $pos => $r) {
            $stmtOvertime->execute([
                ':report_id' => $reportId,
                ':report_item_position' => $pos,
                ':spl_number' => $sh['spl_number'],
                ':wo_id' => null,
                ':spl_date' => $sh['date_iso'],
                ':period_start' => $sh['period_start'],
                ':period_end' => $sh['period_end'],
                ':location_id' => $sh['location_id'],
                ':employee_user_id' => $r['user_id'],
                ':role_snapshot' => $r['role_snapshot'],
                ':start_time' => $r['start'],
                ':end_time' => $r['end'],
                ':duration_hours' => $r['duration'],
                ':activity_description' => $r['job'],
                ':target_description' => $sh['target'],
                ':general_instruction' => $sh['title'],
                ':prepared_by' => $sh['prepared_by_id'],
                ':instructed_by' => $sh['instructed_by_id'],
                ':created_by' => $sh['prepared_by_id'],
                ':created_at' => $createdAt
            ]);
            $totalInsertedTasks++;
            $totalInsertedHours += $r['duration'];
        }

        $totalInsertedSheets++;
    }

    $pdo->commit();

    echo "  [OK] Ingesti 61 dokumen SPL Batch 20 berhasil disimpan ke database!\n";

    // 5. Verification & Assertions
    echo "\n[4/4] Verifikasi Integritas & Rekonsiliasi Database...\n";

    $b20Stats = $pdo->query("
        SELECT 
            COUNT(DISTINCT report_id) AS total_sheets,
            COUNT(*) AS total_tasks,
            ROUND(SUM(duration_hours), 2) AS total_hours
        FROM spl_overtime_records
        WHERE period_start >= '2026-06-21' AND period_end <= '2026-09-20'
    ")->fetch(PDO::FETCH_ASSOC);

    echo "  - Ringkasan Batch 20 (Periode 3, 4, & 5):\n";
    echo "    * Lembar SPL Terdaftar : {$b20Stats['total_sheets']} (Target: 61)\n";
    echo "    * Total Penugasan Mekanik: {$b20Stats['total_tasks']} (Target: 348)\n";
    echo "    * Total Jam Lembur     : {$b20Stats['total_hours']} Jam (Target: 1,371.50 Jam)\n";

    $masterStats = $pdo->query("
        SELECT 
            COUNT(DISTINCT report_id) AS grand_sheets,
            COUNT(*) AS grand_tasks,
            ROUND(SUM(duration_hours), 2) AS grand_hours
        FROM spl_overtime_records
    ")->fetch(PDO::FETCH_ASSOC);

    echo "\n  - Ringkasan Kumulatif Grand Total Master (Batch 19 + Batch 20: Periode 1 s/d 5):\n";
    echo "    * Total Lembar SPL     : {$masterStats['grand_sheets']} (Target SSOT: 98)\n";
    echo "    * Total Penugasan Mekanik: {$masterStats['grand_tasks']} (Target SSOT: 617)\n";
    echo "    * Total Jam Lembur SSOT: {$masterStats['grand_hours']} Jam (Target SSOT: 2,271.56 Jam)\n";

    if ((int)$masterStats['grand_sheets'] !== 98) {
        throw new RuntimeException("Mismatch pada Grand Total Lembar SPL: {$masterStats['grand_sheets']} != 98");
    }
    if ((int)$masterStats['grand_tasks'] !== 617) {
        throw new RuntimeException("Mismatch pada Grand Total Penugasan: {$masterStats['grand_tasks']} != 617");
    }
    if (abs((float)$masterStats['grand_hours'] - 2271.56) > 0.05) {
        throw new RuntimeException("Mismatch pada Grand Total Jam Lembur: {$masterStats['grand_hours']} != 2271.56");
    }

    echo "\n=========================================================================\n";
    echo " Rekonsiliasi SSOT Berhasil: 100% Data SPL 2026 Tersimpan Sempurna!\n";
    echo "=========================================================================\n";

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

