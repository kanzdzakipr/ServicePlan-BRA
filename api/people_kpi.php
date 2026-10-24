<?php
declare(strict_types=1);

require_once 'db.php';
require_once 'security.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

api_require_authenticated();
$currentUser = api_current_user();
$currentUserId = (int) ($currentUser['id'] ?? $currentUser['user_id'] ?? 0);
$currentRole = (string) ($currentUser['primary_role'] ?? $currentUser['role_name'] ?? '');
$currentRoles = array_map('strtolower', (array) ($currentUser['roles'] ?? []));
if ($currentRole !== '' && !in_array(strtolower($currentRole), $currentRoles, true)) {
    $currentRoles[] = strtolower($currentRole);
}

function people_kpi_validate_period(mixed $value): string
{
    $period = trim((string) $value);
    if (!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', $period)) {
        api_json_response(422, [
            'status' => 'error',
            'code' => 'INVALID_PERIOD',
            'message' => 'Format periode tidak valid (YYYY-MM).',
        ]);
    }
    return $period;
}

function people_kpi_require_location(PDO $db, mixed $value): array
{
    $locationId = (int) $value;
    if ($locationId <= 0 || !api_can_access_location($locationId)) {
        api_json_response(403, [
            'status' => 'error',
            'code' => 'LOCATION_FORBIDDEN',
            'message' => 'Lokasi KPI tidak tersedia atau berada di luar cakupan akun.',
        ]);
    }

    $stmt = $db->prepare('SELECT location_id, location_name FROM locations WHERE location_id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute([':id' => $locationId]);
    $location = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$location) {
        api_json_response(422, [
            'status' => 'error',
            'code' => 'INVALID_LOCATION',
            'message' => 'Lokasi KPI tidak aktif atau tidak ditemukan.',
        ]);
    }
    return $location;
}

function people_kpi_can_assess(array $roles): bool
{
    return (bool) array_intersect($roles, [
        'administrator',
        'equipment manager',
        'asset manager',
    ]);
}

function people_kpi_record_audit(
    PDO $db,
    string $period,
    int $locationId,
    int $version,
    string $action,
    string $fromStatus,
    string $toStatus,
    int $actorId,
    array $snapshot
): void {
    $stmt = $db->prepare('
        INSERT INTO head_kpi_audit_trail
            (period_key, location_id, version, action, from_status, to_status,
             actor_user_id, snapshot_json, created_at)
        VALUES
            (:period, :location, :version, :action, :from_status, :to_status,
             :actor, :snapshot, NOW())
    ');
    $stmt->execute([
        ':period' => $period,
        ':location' => $locationId,
        ':version' => $version,
        ':action' => strtoupper($action),
        ':from_status' => $fromStatus,
        ':to_status' => $toStatus,
        ':actor' => $actorId,
        ':snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    ]);
}

function get_default_head_kpi_indicators(): array {
    return [
        ['indicator_no' => 1, 'aspect_name' => 'RTW & Downtime', 'indicator_name' => '% Unit Selesai ≤ Target RTW', 'target_standard' => '≥ 90%', 'score_val' => 5, 'weight_pct' => 15.00, 'audit_notes' => 'Target RTW final disepakati & lulus test function'],
        ['indicator_no' => 2, 'aspect_name' => 'RTW & Downtime', 'indicator_name' => 'Rata-rata Downtime per Unit', 'target_standard' => '≤ Standar', 'score_val' => 4, 'weight_pct' => 20.00, 'audit_notes' => 'Dump Truck ≤ 5-7 hari; Tidak ada unit >7 hari / kronis'],
        ['indicator_no' => 3, 'aspect_name' => 'RTW & Downtime', 'indicator_name' => 'Kepatuhan PM Tepat Waktu', 'target_standard' => '≥ 95%', 'score_val' => 4, 'weight_pct' => 10.00, 'audit_notes' => 'PM on-time %; Breakdown akibat PM gagal NIHIL'],
        ['indicator_no' => 4, 'aspect_name' => 'Percepatan', 'indicator_name' => 'Waktu Respon Awal Kerusakan', 'target_standard' => '≤ 24 jam', 'score_val' => 3, 'weight_pct' => 10.00, 'audit_notes' => 'Respons awal troubleshooting & JO diterbitkan'],
        ['indicator_no' => 5, 'aspect_name' => 'Percepatan', 'indicator_name' => 'Keterlambatan karena Spare Part', 'target_standard' => '≤ 10%', 'score_val' => 1, 'weight_pct' => 10.00, 'audit_notes' => 'Penurunan kerugian downtime ≤30-40%/bln'],
        ['indicator_no' => 6, 'aspect_name' => 'Percepatan', 'indicator_name' => 'Keterlambatan karena Manpower', 'target_standard' => '≤ 5%', 'score_val' => 1, 'weight_pct' => 5.00, 'audit_notes' => 'Ketersediaan mekanik, welder, dan vendor'],
        ['indicator_no' => 7, 'aspect_name' => 'Biaya & Kualitas', 'indicator_name' => 'Deviasi Biaya Corrective', 'target_standard' => '≤ 110%', 'score_val' => 1, 'weight_pct' => 10.00, 'audit_notes' => 'Realisasi biaya vs budget rencana corrective'],
        ['indicator_no' => 8, 'aspect_name' => 'Biaya & Kualitas', 'indicator_name' => 'Repeat Breakdown ≤ 30 Hari', 'target_standard' => '≤ 5%', 'score_val' => 1, 'weight_pct' => 10.00, 'audit_notes' => 'Kerusakan berulang unit/komponen yang sama'],
        ['indicator_no' => 9, 'aspect_name' => 'Kepemimpinan', 'indicator_name' => 'Monitoring & Pelaporan Unit', 'target_standard' => 'Konsisten', 'score_val' => 1, 'weight_pct' => 5.00, 'audit_notes' => 'Disiplin update JO mekanik, target RTW, & report harian'],
        ['indicator_no' => 10, 'aspect_name' => 'Kepemimpinan', 'indicator_name' => 'Inisiatif Percepatan Perbaikan', 'target_standard' => 'Aktif', 'score_val' => 1, 'weight_pct' => 5.00, 'audit_notes' => 'Solusi percepatan (shift tambahan, vendor, prioritas)'],
    ];
}

function get_default_planner_evaluations(): array {
    return [
        ['competency_name' => 'Dasar Maintenance Alat Berat', 'job_standard' => 'D3/S1 Teknik Mesin', 'actual_evaluation' => 'D3 Akuntansi', 'target_level' => 4, 'actual_level' => 2, 'action_plan' => 'Pelatihan Dasar Teknik Mesin Alat Berat & System Diagnosis'],
        ['competency_name' => 'PM Scheduling & Meter Reading', 'job_standard' => 'Update Real-Time Status KM/HM', 'actual_evaluation' => 'Jadwal terbuat, Update KM tertinggal', 'target_level' => 4, 'actual_level' => 3, 'action_plan' => 'Standardisasi Audit Meter Reading Harian Operator'],
        ['competency_name' => 'Estimasi Durasi Repair', 'job_standard' => 'Akurat per Komponen', 'actual_evaluation' => 'Belum menguasai estimasi waktu', 'target_level' => 4, 'actual_level' => 2, 'action_plan' => 'Praktik & Benchmark Standard Job Time per Kategori Breakdown'],
        ['competency_name' => 'Spare Parts Control & Kitting', 'job_standard' => 'Parts Ready sebelum Job Start', 'actual_evaluation' => 'Memahami Logistik, Parts Kitting belum terikat JO', 'target_level' => 4, 'actual_level' => 3, 'action_plan' => 'Integrasi Form SPB dengan Nomor WO/PM secara Wajib'],
        ['competency_name' => 'Administrasi & Keuangan KESDMAN', 'job_standard' => 'Tertib Administrasi', 'actual_evaluation' => 'Menguasai Administrasi & Akuntansi', 'target_level' => 4, 'actual_level' => 5, 'action_plan' => 'Dipertahankan sebagai keunggulan kontrol dokumen'],
    ];
}

$action = trim((string) ($_GET['action'] ?? 'head_kpi'));

// -----------------------------------------------------------------------------
// GET ACTIONS
// -----------------------------------------------------------------------------
if ($method === 'GET') {
    $period = people_kpi_validate_period($_GET['period'] ?? '2026-01');

    if ($action === 'locations') {
        $sql = 'SELECT location_id, location_name FROM locations WHERE is_active = 1';
        $params = [];
        if (!api_has_global_location_scope()) {
            $sql .= ' AND location_id = :location_id';
            $params[':location_id'] = api_current_location_id() ?? -1;
        }
        $sql .= ' ORDER BY location_name';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        api_json_response(200, [
            'status' => 'success',
            'locations' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'assigned_location_id' => api_current_location_id(),
        ]);
    }

    if ($action === 'head_kpi') {
        $location = people_kpi_require_location($db, $_GET['location_id'] ?? api_current_location_id());
        $locationId = (int) $location['location_id'];

        // 1. Fetch indicators for this period and location.
        $stmt = $db->prepare("
            SELECT h.*, 
                   u_eval.full_name AS evaluated_user_name,
                   u_app.full_name AS approved_by_name,
                   u_ass.full_name AS assessor_name
            FROM head_kpi_assessments h
            LEFT JOIN users u_eval ON u_eval.user_id = h.evaluated_user_id
            LEFT JOIN users u_app ON u_app.user_id = h.approved_by
            LEFT JOIN users u_ass ON u_ass.user_id = h.assessor_user_id
            WHERE h.period_key = :period
              AND h.location_id = :location_id
            ORDER BY h.indicator_no ASC
        ");
        $stmt->execute([':period' => $period, ':location_id' => $locationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If no records for this period, load default baseline template
        $isNewDraft = false;
        if (empty($rows)) {
            $isNewDraft = true;
            $stmtTpl = $db->query("
                SELECT indicator_no, aspect_name, indicator_name, target_standard, 
                       score_val, weight_pct, audit_notes
                FROM head_kpi_assessments
                ORDER BY indicator_no ASC
                LIMIT 10
            ");
            $rows = $stmtTpl->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                $rows = get_default_head_kpi_indicators();
            }
            foreach ($rows as &$r) {
                $r['kpi_id'] = null;
                $r['period_key'] = $period;
                $r['location_id'] = $locationId;
                $r['status'] = 'DRAFT';
                $r['version'] = 1;
                $r['evaluated_user_id'] = 2; // Dany Agung
                $r['evaluated_user_name'] = 'Dany Agung (Head of Equipment)';
                $r['approved_by_name'] = null;
                $r['approved_at'] = null;
                $r['approval_notes'] = null;
                $r['score_val'] = (int) ($r['score_val'] ?? 1);
                $r['weight_pct'] = (float) ($r['weight_pct'] ?? 10);
                $r['weighted_score'] = round(($r['score_val'] * $r['weight_pct']) / 5, 2);
            }
            unset($r);
        }

        // Calculate actual operational figures from work_orders for this period
        $woStatsStmt = $db->prepare("
            SELECT 
                COUNT(*) AS total_wo,
                COALESCE(SUM(CASE WHEN w.status = 'Closed' THEN 1 ELSE 0 END), 0) AS closed_wo,
                COALESCE(AVG(CASE WHEN w.status = 'Closed' AND w.downtime_minutes > 0 THEN w.downtime_minutes ELSE NULL END), 0) AS avg_downtime_minutes,
                COALESCE(SUM(CASE WHEN w.maintenance_type = 'Preventive' AND w.status = 'Closed' THEN 1 ELSE 0 END), 0) AS pm_completed,
                COALESCE(SUM(CASE WHEN w.maintenance_type = 'Preventive' THEN 1 ELSE 0 END), 0) AS pm_total
            FROM work_orders w
            INNER JOIN assets a ON a.asset_id = w.asset_id
            WHERE w.reported_at LIKE :period_like
              AND a.current_location_id = :location_id
        ");
        $woStatsStmt->execute([':period_like' => "{$period}%", ':location_id' => $locationId]);
        $woStats = $woStatsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $closedWo = (int) ($woStats['closed_wo'] ?? 0);
        $totalWo = (int) ($woStats['total_wo'] ?? 0);
        $rtwPct = $totalWo > 0 ? round(($closedWo / $totalWo) * 100, 1) : 100.0;
        $avgDowntimeHours = round(((float) ($woStats['avg_downtime_minutes'] ?? 0)) / 60, 1);
        $pmTotal = (int) ($woStats['pm_total'] ?? 0);
        $pmCompleted = (int) ($woStats['pm_completed'] ?? 0);
        $pmCompliancePct = $pmTotal > 0 ? round(($pmCompleted / $pmTotal) * 100, 1) : 100.0;

        if ($isNewDraft) {
            foreach ($rows as &$r) {
                $indNo = (int) ($r['indicator_no'] ?? 0);
                if ($indNo === 1) { // % Unit Selesai ≤ Target RTW
                    $r['score_val'] = $rtwPct >= 90 ? 5 : ($rtwPct >= 80 ? 4 : ($rtwPct >= 70 ? 3 : 2));
                    $r['audit_notes'] = "Realisasi RTW: {$rtwPct}% ({$closedWo} selesai dari {$totalWo} WO periode {$period})";
                } elseif ($indNo === 2) { // Rata-rata Downtime per Unit
                    $r['score_val'] = $avgDowntimeHours <= 24 ? 5 : ($avgDowntimeHours <= 48 ? 4 : ($avgDowntimeHours <= 72 ? 3 : 2));
                    $r['audit_notes'] = "Rata-rata downtime periode {$period}: {$avgDowntimeHours} jam/unit ({$closedWo} WO selesai)";
                } elseif ($indNo === 3) { // Kepatuhan PM Tepat Waktu
                    $r['score_val'] = $pmCompliancePct >= 95 ? 5 : ($pmCompliancePct >= 90 ? 4 : ($pmCompliancePct >= 80 ? 3 : 2));
                    $r['audit_notes'] = "Kepatuhan PM: {$pmCompliancePct}% ({$pmCompleted} selesai dari {$pmTotal} PM periode {$period})";
                }
                $r['weighted_score'] = round(($r['score_val'] * (float) $r['weight_pct']) / 5, 2);
            }
            unset($r);
        }

        // Calculate weighted scores & summary
        $totalScore = 0;
        $totalWeight = 0.0;
        $totalWeightedScore = 0.0;
        $overallStatus = 'DRAFT';
        $approvedByName = null;
        $approvedAt = null;
        $approvalNotes = null;
        $submittedAt = null;
        $version = 1;
        $assessorUserId = null;
        $assessorName = null;
        $evaluatedUserId = 2;
        $evaluatedName = 'Dany Agung (Head of Equipment)';

        foreach ($rows as $r) {
            $totalScore += (int) $r['score_val'];
            $totalWeight += (float) $r['weight_pct'];
            $totalWeightedScore += (float) ($r['weighted_score'] ?? (($r['score_val'] * $r['weight_pct']) / 5));
            if (!empty($r['status'])) $overallStatus = $r['status'];
            if (!empty($r['approved_by_name'])) $approvedByName = $r['approved_by_name'];
            if (!empty($r['approved_at'])) $approvedAt = $r['approved_at'];
            if (!empty($r['approval_notes'])) $approvalNotes = $r['approval_notes'];
            if (!empty($r['submitted_at'])) $submittedAt = $r['submitted_at'];
            if (!empty($r['version'])) $version = (int) $r['version'];
            if (!empty($r['assessor_user_id'])) $assessorUserId = (int) $r['assessor_user_id'];
            if (!empty($r['assessor_name'])) $assessorName = $r['assessor_name'];
            if (!empty($r['evaluated_user_id'])) $evaluatedUserId = (int) $r['evaluated_user_id'];
            if (!empty($r['evaluated_user_name'])) $evaluatedName = $r['evaluated_user_name'];
        }

        // Percentage achievement: weighted score / 20 * 100% (since max weighted score is 20 if scale is 5)
        // or sum of weighted score directly as max is 100 if formula is (score/5 * weight)
        $achievementPct = round($totalWeightedScore, 1);

        api_json_response(200, [
            'status' => 'success',
            'period' => $period,
            'location' => $location,
            'is_new_draft' => $isNewDraft,
            'assessment' => [
                'status' => $overallStatus,
                'period' => $period,
                'location_id' => $locationId,
                'location_name' => $location['location_name'],
                'version' => $version,
                'assessor_user_id' => $assessorUserId,
                'assessor_name' => $assessorName,
                'evaluated_user_id' => $evaluatedUserId,
                'evaluated_name' => $evaluatedName,
                'total_score' => $totalScore,
                'total_weight' => $totalWeight,
                'total_weighted_score' => round($totalWeightedScore, 2),
                'achievement_pct' => $achievementPct,
                'submitted_at' => $submittedAt,
                'approved_by_name' => $approvedByName,
                'approved_at' => $approvedAt,
                'approval_notes' => $approvalNotes,
            ],
            'real_metrics' => [
                'period' => $period,
                'total_wo' => $totalWo,
                'closed_wo' => $closedWo,
                'rtw_pct' => $rtwPct,
                'avg_downtime_hours' => $avgDowntimeHours,
                'pm_total' => $pmTotal,
                'pm_completed' => $pmCompleted,
                'pm_compliance_pct' => $pmCompliancePct,
            ],
            'indicators' => $rows
        ]);
    }

    if ($action === 'planner_eval') {
        $plannerUserId = (int) ($_GET['planner_id'] ?? 3);
        $stmt = $db->prepare("
            SELECT pe.*, 
                   u_plan.full_name AS planner_name,
                   u_eval.full_name AS evaluator_name
            FROM planner_evaluations pe
            LEFT JOIN users u_plan ON u_plan.user_id = pe.planner_user_id
            LEFT JOIN users u_eval ON u_eval.user_id = pe.evaluator_user_id
            WHERE pe.period_key = :period AND pe.planner_user_id = :planner_id
            ORDER BY pe.eval_id ASC
        ");
        $stmt->execute([':period' => $period, ':planner_id' => $plannerUserId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            // Load standard template
            $stmtTpl = $db->query("
                SELECT competency_name, job_standard, actual_evaluation, 
                       target_level, actual_level, action_plan
                FROM planner_evaluations
                ORDER BY eval_id ASC
                LIMIT 5
            ");
            $rows = $stmtTpl->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                $rows = get_default_planner_evaluations();
            }
            foreach ($rows as &$r) {
                $r['eval_id'] = null;
                $r['period_key'] = $period;
                $r['planner_user_id'] = $plannerUserId;
                $r['status'] = 'DRAFT';
                $r['gap_level'] = (int) ($r['actual_level'] ?? 2) - (int) ($r['target_level'] ?? 4);
            }
            unset($r);
        }

        api_json_response(200, [
            'status' => 'success',
            'period' => $period,
            'planner_id' => $plannerUserId,
            'evaluations' => $rows
        ]);
    }

    if ($action === 'overtime_leaderboard') {
        $location = people_kpi_require_location($db, $_GET['location_id'] ?? api_current_location_id());
        $locationId = (int) $location['location_id'];
        // Aggregate real overtime from spl_overtime_records
        // Period filter: YYYY-MM
        $stmt = $db->prepare("
            SELECT 
                s.employee_user_id,
                COALESCE(u.full_name, s.role_snapshot) AS employee_name,
                s.role_snapshot AS role_name,
                l.location_name,
                COUNT(DISTINCT s.spl_number) AS total_spl,
                SUM(s.duration_hours) AS total_hours,
                SUM(CASE WHEN s.end_verified = 1 THEN 1 ELSE 0 END) AS verified_sessions
            FROM spl_overtime_records s
            LEFT JOIN users u ON u.user_id = s.employee_user_id
            LEFT JOIN locations l ON l.location_id = s.location_id
            WHERE s.reversed_at IS NULL
              AND s.spl_date LIKE :period_like
              AND s.location_id = :location_id
            GROUP BY s.employee_user_id, employee_name, s.role_snapshot, l.location_name
            ORDER BY total_hours DESC
        ");
        $stmt->execute([':period_like' => "{$period}%", ':location_id' => $locationId]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasData = !empty($records);
        $ranked = [];
        $rank = 1;
        foreach ($records as $rec) {
            $rec['rank'] = $rank++;
            $rec['total_hours'] = round((float) $rec['total_hours'], 1);
            $ranked[] = $rec;
        }

        api_json_response(200, [
            'status' => 'success',
            'period' => $period,
            'location' => $location,
            'has_data' => $hasData,
            'message' => $hasData ? 'Data lembur terverifikasi ditemukan.' : 'Belum ada data lembur / SPL terverifikasi pada periode ' . $period . '.',
            'leaderboard' => $ranked
        ]);
    }

    if ($action === 'spl_reviews') {
        $location = people_kpi_require_location($db, $_GET['location_id'] ?? api_current_location_id());
        $locationId = (int) $location['location_id'];
        // Return only reviews whose official SPL belongs to the selected location.
        $stmt = $db->prepare("
            SELECT r.*, u.full_name AS planner_name
            FROM spl_planner_reviews r
            LEFT JOIN users u ON u.user_id = r.planner_user_id
            WHERE EXISTS (
                SELECT 1
                FROM spl_overtime_records s
                INNER JOIN report_records rr ON rr.report_id = s.report_id
                WHERE rr.status = 'FINAL'
                  AND s.reversed_at IS NULL
                  AND s.location_id = :location_id
                  AND (s.report_id = r.report_id OR s.spl_number = r.spl_number)
            )
            ORDER BY r.reviewed_at DESC
        ");
        $stmt->execute([':location_id' => $locationId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $indexed = [];
        foreach ($reviews as $rev) {
            $indexed[$rev['spl_number']] = $rev;
            $indexed[$rev['report_id']] = $rev;
        }

        api_json_response(200, [
            'status' => 'success',
            'location' => $location,
            'reviews' => $indexed
        ]);
    }

    api_json_response(400, [
        'status' => 'error',
        'code' => 'INVALID_ACTION',
        'message' => 'Action tidak dikenali.'
    ]);
}

// -----------------------------------------------------------------------------
// POST ACTIONS (Mutations)
// -----------------------------------------------------------------------------
if ($method === 'POST') {
    api_verify_csrf();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    if ($action === 'save_head_kpi') {
        if (!people_kpi_can_assess($currentRoles)) {
            api_json_response(403, [
                'status' => 'error',
                'code' => 'FORBIDDEN',
                'message' => 'Role ini tidak berwenang membuat penilaian KPI Head of Equipment.',
            ]);
        }

        $period = people_kpi_validate_period($input['period'] ?? '');
        $location = people_kpi_require_location($db, $input['location_id'] ?? null);
        $locationId = (int) $location['location_id'];

        $targetStatus = strtoupper(trim((string) ($input['status'] ?? 'DRAFT')));
        if (!in_array($targetStatus, ['DRAFT', 'SUBMITTED'], true)) {
            $targetStatus = 'DRAFT';
        }

        $indicators = $input['indicators'] ?? [];
        if (!is_array($indicators) || count($indicators) !== 10) {
            api_json_response(422, ['status' => 'error', 'message' => 'Penilaian resmi wajib berisi tepat 10 indikator.']);
        }

        $normalizedIndicators = [];
        $indicatorNumbers = [];
        $totalWeight = 0.0;
        foreach ($indicators as $idx => $ind) {
            $score = (int) ($ind['score_val'] ?? 0);
            $weight = (float) ($ind['weight_pct'] ?? 0);
            $indicatorNo = (int) ($ind['indicator_no'] ?? ($idx + 1));
            if ($score < 1 || $score > 5 || $weight <= 0 || $indicatorNo <= 0 || isset($indicatorNumbers[$indicatorNo])) {
                api_json_response(422, ['status' => 'error', 'message' => 'Nomor indikator, skor, atau bobot KPI tidak valid.']);
            }
            $indicatorNumbers[$indicatorNo] = true;
            $totalWeight += $weight;
            $normalizedIndicators[] = [
                'indicator_no' => $indicatorNo,
                'aspect_name' => mb_substr(trim((string) ($ind['aspect_name'] ?? 'Kinerja')), 0, 100),
                'indicator_name' => mb_substr(trim((string) ($ind['indicator_name'] ?? ('Indikator ' . $indicatorNo))), 0, 150),
                'target_standard' => mb_substr(trim((string) ($ind['target_standard'] ?? 'Standar')), 0, 100),
                'score_val' => $score,
                'weight_pct' => $weight,
                'audit_notes' => trim((string) ($ind['audit_notes'] ?? '')),
            ];
        }
        if (abs($totalWeight - 100.0) > 0.01) {
            api_json_response(422, ['status' => 'error', 'message' => 'Total bobot 10 indikator wajib tepat 100%.']);
        }
        $actualIndicatorNumbers = array_keys($indicatorNumbers);
        sort($actualIndicatorNumbers);
        if ($actualIndicatorNumbers !== range(1, 10)) {
            api_json_response(422, ['status' => 'error', 'message' => 'Nomor indikator resmi wajib lengkap dari 1 sampai 10.']);
        }

        $evaluatedUserId = (int) ($input['evaluated_user_id'] ?? 2);
        $evaluatedStmt = $db->prepare('SELECT user_id, full_name FROM users WHERE user_id = :id AND is_active = 1 LIMIT 1');
        $evaluatedStmt->execute([':id' => $evaluatedUserId]);
        $evaluatedUser = $evaluatedStmt->fetch(PDO::FETCH_ASSOC);
        if (!$evaluatedUser) {
            api_json_response(422, ['status' => 'error', 'message' => 'Personel yang dinilai tidak ditemukan atau nonaktif.']);
        }

        $db->beginTransaction();
        try {
            $checkStmt = $db->prepare("
                SELECT status, version, assessor_user_id, submitted_at
                FROM head_kpi_assessments
                WHERE period_key = :period AND location_id = :location_id
                ORDER BY indicator_no
                LIMIT 1 FOR UPDATE
            ");
            $checkStmt->execute([':period' => $period, ':location_id' => $locationId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $existingStatus = strtoupper((string) ($existing['status'] ?? 'NONE'));
            $existingVersion = max(1, (int) ($existing['version'] ?? 1));

            if ($existingStatus === 'APPROVED') {
                $db->rollBack();
                api_json_response(409, [
                    'status' => 'error',
                    'code' => 'ALREADY_APPROVED',
                    'message' => 'Penilaian yang sudah APPROVED terkunci dan hanya dapat diubah melalui proses revisi resmi.',
                ]);
            }
            if ($existingStatus === 'SUBMITTED') {
                $db->rollBack();
                if ($targetStatus === 'SUBMITTED') {
                    api_json_response(200, [
                        'status' => 'success',
                        'already_submitted' => true,
                        'message' => 'Penilaian ini sudah SUBMITTED; request ulang tidak membuat duplikasi.',
                    ]);
                }
                api_json_response(409, [
                    'status' => 'error',
                    'code' => 'ALREADY_SUBMITTED',
                    'message' => 'Penilaian SUBMITTED menunggu keputusan approver dan tidak dapat dikembalikan langsung ke DRAFT.',
                ]);
            }
            if ($existing && (int) ($existing['assessor_user_id'] ?? 0) > 0
                && (int) $existing['assessor_user_id'] !== $currentUserId) {
                $db->rollBack();
                api_json_response(409, [
                    'status' => 'error',
                    'code' => 'ASSESSOR_MISMATCH',
                    'message' => 'Draf periode dan lokasi ini dimiliki penilai lain.',
                ]);
            }

            $version = $existingStatus === 'REJECTED' ? $existingVersion + 1 : $existingVersion;
            $submittedAt = ($targetStatus === 'SUBMITTED') ? date('Y-m-d H:i:s') : null;

            $del = $db->prepare("DELETE FROM head_kpi_assessments WHERE period_key = :period AND location_id = :location_id");
            $del->execute([':period' => $period, ':location_id' => $locationId]);

            $stmtInsert = $db->prepare("
                INSERT INTO head_kpi_assessments (
                    period_month, period_year, period_key, location_id, indicator_no, aspect_name,
                    indicator_name, target_standard, score_val, weight_pct,
                    audit_notes, status, version, evaluated_user_id, assessor_user_id,
                    submitted_at, created_at, updated_at
                ) VALUES (
                    :p_month, :p_year, :p_key, :location_id, :ind_no, :aspect,
                    :ind_name, :target_std, :score, :weight,
                    :notes, :status, :version, :eval_uid, :assessor_uid,
                    :sub_at, NOW(), NOW()
                )
            ");

            [$year, $monthNum] = explode('-', $period);
            $monthNames = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            ];
            $monthName = $monthNames[$monthNum] ?? 'Januari';
            foreach ($normalizedIndicators as $ind) {
                $stmtInsert->execute([
                    ':p_month' => $monthName,
                    ':p_year' => (int) $year,
                    ':p_key' => $period,
                    ':location_id' => $locationId,
                    ':ind_no' => $ind['indicator_no'],
                    ':aspect' => $ind['aspect_name'],
                    ':ind_name' => $ind['indicator_name'],
                    ':target_std' => $ind['target_standard'],
                    ':score' => $ind['score_val'],
                    ':weight' => $ind['weight_pct'],
                    ':notes' => $ind['audit_notes'],
                    ':status' => $targetStatus,
                    ':version' => $version,
                    ':eval_uid' => $evaluatedUserId,
                    ':assessor_uid' => $currentUserId,
                    ':sub_at' => $submittedAt
                ]);
            }

            people_kpi_record_audit(
                $db,
                $period,
                $locationId,
                $version,
                $targetStatus === 'SUBMITTED' ? 'SUBMIT' : 'SAVE_DRAFT',
                $existingStatus,
                $targetStatus,
                $currentUserId,
                [
                    'evaluated_user_id' => $evaluatedUserId,
                    'evaluated_user_name' => $evaluatedUser['full_name'],
                    'location_name' => $location['location_name'],
                    'indicator_count' => count($normalizedIndicators),
                    'total_weight' => $totalWeight,
                    'indicators' => $normalizedIndicators,
                ]
            );

            $db->commit();
            api_json_response(200, [
                'status' => 'success',
                'period' => $period,
                'location_id' => $locationId,
                'version' => $version,
                'message' => ($targetStatus === 'SUBMITTED')
                    ? 'Penilaian KPI Head of Equipment berhasil dikirim (SUBMITTED) untuk persetujuan GM.'
                    : 'Draf penilaian KPI Head of Equipment berhasil disimpan.'
            ]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response(500, ['status' => 'error', 'message' => 'Gagal menyimpan KPI: ' . $e->getMessage()]);
        }
    }

    if ($action === 'approve_head_kpi') {
        // Enforce role: Only General Manager or Administrator
        $canApprove = in_array('administrator', $currentRoles, true)
            || in_array('general manager', $currentRoles, true)
            || strtolower((string)($currentUser['username'] ?? '')) === 'admin';
        if (!$canApprove) {
            api_json_response(403, [
                'status' => 'error',
                'code' => 'FORBIDDEN',
                'message' => 'Hanya General Manager atau Administrator yang berwenang memberikan persetujuan (Approval) KPI Head of Equipment.'
            ]);
        }

        $period = people_kpi_validate_period($input['period'] ?? '');
        $location = people_kpi_require_location($db, $input['location_id'] ?? null);
        $locationId = (int) $location['location_id'];
        $decision = strtoupper(trim((string) ($input['decision'] ?? 'APPROVED')));
        if (!in_array($decision, ['APPROVED', 'REJECTED'], true)) {
            $decision = 'APPROVED';
        }
        $notes = trim((string) ($input['approval_notes'] ?? ''));
        if ($decision === 'REJECTED' && $notes === '') {
            api_json_response(422, ['status' => 'error', 'message' => 'Alasan penolakan wajib diisi.']);
        }

        $db->beginTransaction();
        try {
            $currentStmt = $db->prepare("
                SELECT status, version, assessor_user_id
                FROM head_kpi_assessments
                WHERE period_key = :period AND location_id = :location_id
                ORDER BY indicator_no LIMIT 1 FOR UPDATE
            ");
            $currentStmt->execute([':period' => $period, ':location_id' => $locationId]);
            $assessment = $currentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$assessment) {
                $db->rollBack();
                api_json_response(404, ['status' => 'error', 'message' => "Tidak ada data penilaian KPI untuk periode dan lokasi tersebut."]);
            }
            if (strtoupper((string) $assessment['status']) !== 'SUBMITTED') {
                $db->rollBack();
                api_json_response(409, [
                    'status' => 'error',
                    'code' => 'INVALID_TRANSITION',
                    'message' => 'Approval hanya dapat dilakukan pada penilaian berstatus SUBMITTED.',
                ]);
            }
            if ((int) ($assessment['assessor_user_id'] ?? 0) === $currentUserId) {
                $db->rollBack();
                api_json_response(403, [
                    'status' => 'error',
                    'code' => 'SELF_APPROVAL_FORBIDDEN',
                    'message' => 'Penilai tidak boleh menyetujui penilaiannya sendiri.',
                ]);
            }

            $countStmt = $db->prepare('
                SELECT COUNT(*)
                FROM head_kpi_assessments
                WHERE period_key = :period AND location_id = :location_id
            ');
            $countStmt->execute([':period' => $period, ':location_id' => $locationId]);
            $indicatorCount = (int) $countStmt->fetchColumn();
            if ($indicatorCount !== 10) {
                $db->rollBack();
                api_json_response(409, [
                    'status' => 'error',
                    'code' => 'INCOMPLETE_ASSESSMENT',
                    'message' => 'Approval ditolak karena penilaian tidak berisi tepat 10 indikator.',
                ]);
            }

            $stmt = $db->prepare("
                UPDATE head_kpi_assessments
                SET status = :decision,
                    approved_by = :approver_id,
                    approved_at = NOW(),
                    approval_notes = :notes
                WHERE period_key = :period
                  AND location_id = :location_id
                  AND status = 'SUBMITTED'
            ");
            $stmt->execute([
                ':decision' => $decision,
                ':approver_id' => $currentUserId,
                ':notes' => $notes,
                ':period' => $period,
                ':location_id' => $locationId,
            ]);

            people_kpi_record_audit(
                $db,
                $period,
                $locationId,
                (int) $assessment['version'],
                $decision,
                'SUBMITTED',
                $decision,
                $currentUserId,
                [
                    'assessor_user_id' => (int) $assessment['assessor_user_id'],
                    'approver_user_id' => $currentUserId,
                    'approval_notes' => $notes,
                    'indicator_count' => $indicatorCount,
                    'location_name' => $location['location_name'],
                ]
            );
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response(500, [
                'status' => 'error',
                'code' => 'KPI_APPROVAL_FAILED',
                'message' => 'Gagal memproses approval KPI.',
            ]);
        }

        api_json_response(200, [
            'status' => 'success',
            'period' => $period,
            'location_id' => $locationId,
            'decision' => $decision,
            'message' => ($decision === 'APPROVED')
                ? 'KPI Head of Equipment periode ' . $period . ' telah disetujui (APPROVED) secara resmi.'
                : 'KPI Head of Equipment periode ' . $period . ' ditolak (REJECTED).'
        ]);
    }

    if ($action === 'save_planner_eval') {
        $period = trim((string) ($input['period'] ?? '2026-01'));
        $plannerUserId = (int) ($input['planner_user_id'] ?? 3);
        $evaluations = $input['evaluations'] ?? [];

        if (!is_array($evaluations) || empty($evaluations)) {
            api_json_response(422, ['status' => 'error', 'message' => 'Daftar evaluasi kompetensi planner wajib diisi.']);
        }

        $db->beginTransaction();
        try {
            // Delete existing records for this period & planner
            $del = $db->prepare("DELETE FROM planner_evaluations WHERE period_key = :period AND planner_user_id = :planner_id");
            $del->execute([':period' => $period, ':planner_id' => $plannerUserId]);

            $stmtIns = $db->prepare("
                INSERT INTO planner_evaluations (
                    planner_user_id, period_key, evaluator_user_id, competency_name,
                    job_standard, actual_evaluation, target_level, actual_level,
                    action_plan, status, created_at, updated_at
                ) VALUES (
                    :planner_id, :period, :evaluator_id, :comp_name,
                    :job_std, :act_eval, :tgt_lvl, :act_lvl,
                    :act_plan, 'FINAL', NOW(), NOW()
                )
            ");

            foreach ($evaluations as $e) {
                $compName = trim((string) ($e['competency_name'] ?? 'Kompetensi'));
                $jobStd = trim((string) ($e['job_standard'] ?? '-'));
                $actEval = trim((string) ($e['actual_evaluation'] ?? '-'));
                $tgtLvl = (int) ($e['target_level'] ?? 4);
                $actLvl = (int) ($e['actual_level'] ?? 2);
                $actPlan = trim((string) ($e['action_plan'] ?? ''));

                $stmtIns->execute([
                    ':planner_id' => $plannerUserId,
                    ':period' => $period,
                    ':evaluator_id' => $currentUserId,
                    ':comp_name' => $compName,
                    ':job_std' => $jobStd,
                    ':act_eval' => $actEval,
                    ':tgt_lvl' => $tgtLvl,
                    ':act_lvl' => $actLvl,
                    ':act_plan' => $actPlan
                ]);
            }

            $db->commit();
            api_json_response(200, ['status' => 'success', 'message' => 'Evaluasi kompetensi Planner berhasil disimpan.']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            api_json_response(500, ['status' => 'error', 'message' => 'Gagal menyimpan evaluasi planner: ' . $e->getMessage()]);
        }
    }

    if ($action === 'save_spl_review') {
        if (!in_array('maintenance planner', $currentRoles, true)
            && !in_array('administrator', $currentRoles, true)) {
            api_json_response(403, [
                'status' => 'error',
                'code' => 'FORBIDDEN',
                'message' => 'Hanya Maintenance Planner atau Administrator yang berwenang meninjau SPL.',
            ]);
        }

        $splNumber = trim((string) ($input['spl_number'] ?? ''));
        $reportId = trim((string) ($input['report_id'] ?? ''));
        $location = people_kpi_require_location($db, $input['location_id'] ?? api_current_location_id());
        $locationId = (int) $location['location_id'];
        $reviewStatus = trim((string) ($input['review_status'] ?? 'Reviewed'));
        $notes = trim((string) ($input['review_notes'] ?? ''));

        if ($splNumber === '' && $reportId === '') {
            api_json_response(422, ['status' => 'error', 'message' => 'Nomor SPL atau Report ID wajib disertakan.']);
        }

        $officialStmt = $db->prepare("
            SELECT s.report_id, s.spl_number
            FROM spl_overtime_records s
            INNER JOIN report_records rr ON rr.report_id = s.report_id
            WHERE rr.status = 'FINAL'
              AND s.reversed_at IS NULL
              AND s.location_id = :location_id
              AND (s.report_id = :report_id OR s.spl_number = :spl_number)
            ORDER BY s.overtime_id
            LIMIT 1
        ");
        $officialStmt->execute([
            ':location_id' => $locationId,
            ':report_id' => $reportId,
            ':spl_number' => $splNumber,
        ]);
        $official = $officialStmt->fetch(PDO::FETCH_ASSOC);
        if (!$official) {
            api_json_response(404, [
                'status' => 'error',
                'code' => 'SPL_NOT_FOUND',
                'message' => 'SPL final aktif tidak ditemukan pada lokasi akun.',
            ]);
        }
        $reportId = (string) $official['report_id'];
        $splNumber = (string) $official['spl_number'];

        // Upsert review record
        $stmtCheck = $db->prepare("SELECT review_id FROM spl_planner_reviews WHERE spl_number = :spl OR report_id = :rep LIMIT 1");
        $stmtCheck->execute([':spl' => $splNumber, ':rep' => $reportId]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $stmtUp = $db->prepare("
                UPDATE spl_planner_reviews
                SET review_status = :status,
                    review_notes = :notes,
                    planner_user_id = :uid,
                    reviewed_at = NOW()
                WHERE review_id = :id
            ");
            $stmtUp->execute([
                ':status' => $reviewStatus,
                ':notes' => $notes,
                ':uid' => $currentUserId,
                ':id' => $existingId
            ]);
        } else {
            $stmtIn = $db->prepare("
                INSERT INTO spl_planner_reviews (
                    report_id, spl_number, planner_user_id, review_status, review_notes, reviewed_at
                ) VALUES (
                    :rep, :spl, :uid, :status, :notes, NOW()
                )
            ");
            $stmtIn->execute([
                ':rep' => $reportId,
                ':spl' => $splNumber,
                ':uid' => $currentUserId,
                ':status' => $reviewStatus,
                ':notes' => $notes
            ]);
        }

        api_json_response(200, [
            'status' => 'success',
            'message' => "Catatan review planner untuk SPL {$splNumber} berhasil disimpan ke database."
        ]);
    }

    api_json_response(400, ['status' => 'error', 'message' => 'Action POST tidak dikenali.']);
}

api_json_response(405, ['status' => 'error', 'message' => 'Metode HTTP tidak didukung.']);

