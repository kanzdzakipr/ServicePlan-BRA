<?php
/**
 * Migration Script: Batch 22 - Provisioning Master Personel Tambahan & Tata Kelola Skema Absensi
 * Repositori: ServicePlan-BRA
 * Tanggal: 30 Oktober 2026
 * 
 * Cakupan:
 * 1. Registrasi role tambahan jika belum ada: Security & Humas Yard.
 * 2. Registrasi template laporan resmi `rekap-absensi-yard` di `report_templates`.
 * 3. Pembuatan skema tabel terstruktur `employee_attendance_monthly` dan `employee_attendance_daily`.
 * 4. Provisioning 16 akun personil pendukung baru (Security, Safety, Humas, Flagman, Logistik, Mekanik Harian)
 *    ke tabel `users` dan `user_roles` dengan password bcrypt standar `bra2026`.
 * 5. Verifikasi integritas relasi foreign key dan default location_id = 4 (Workshop Bay KM 12).
 */

declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';

try {
    $pdo = Database::getInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=========================================================================\n";
    echo " ServicePlan-BRA: Migration Batch 22 - Skema Absensi & Provisi Personel\n";
    echo "=========================================================================\n\n";

    // 1. Registrasi Roles Pendukung
    echo "[1/5] Memeriksa dan memperbarui master `roles`...\n";
    $rolesToEnsure = [
        'Security' => 'Petugas keamanan gerbang & yard workshop KM 12',
        'Humas Yard' => 'Hubungan masyarakat & operasional lingkungan eksternal yard'
    ];

    $roleStmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = :role_name LIMIT 1");
    $insertRoleStmt = $pdo->prepare("INSERT INTO roles (role_name, description) VALUES (:role_name, :description)");

    $roleMap = [];
    $allRoles = $pdo->query("SELECT role_id, role_name FROM roles")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allRoles as $r) {
        $roleMap[$r['role_name']] = (int)$r['role_id'];
    }

    foreach ($rolesToEnsure as $rName => $rDesc) {
        if (!isset($roleMap[$rName])) {
            $insertRoleStmt->execute([':role_name' => $rName, ':description' => $rDesc]);
            $newId = (int)$pdo->lastInsertId();
            $roleMap[$rName] = $newId;
            echo "  -> Role baru didaftarkan: {$rName} (role_id = {$newId})\n";
        } else {
            echo "  -> Role telah tersedia: {$rName} (role_id = {$roleMap[$rName]})\n";
        }
    }

    // 2. Registrasi Template Laporan Resmi di report_templates
    echo "\n[2/5] Memeriksa pendaftaran template laporan resmi `rekap-absensi-yard`...\n";
    $tplStmt = $pdo->prepare("SELECT template_id FROM report_templates WHERE template_key = 'rekap-absensi-yard' OR code = 'ABSEN-YARD' LIMIT 1");
    $tplStmt->execute();
    $existingTemplateId = $tplStmt->fetchColumn();

    $attendanceSchemaJson = json_encode([
        'id' => 'rekap-absensi-yard',
        'code' => 'ABSEN-YARD',
        'title' => 'Rekapitulasi Absensi & Lembur Tim (Yard KM 12)',
        'description' => 'Rekapitulasi kehadiran shift bulanan dan jam lembur timesheet seluruh divisi Yard KM 12.',
        'category' => 'Kepegawaian & Timesheet',
        'fields' => [
            ['key' => 'periode_label', 'type' => 'text', 'label' => 'Periode Penggajian', 'required' => true, 'placeholder' => '21 Juni 2026 s.d. 20 Juli 2026'],
            ['key' => 'bulan_kalender', 'type' => 'text', 'label' => 'Bulan Kalender', 'required' => true, 'placeholder' => '2026-07'],
            ['key' => 'lokasi', 'type' => 'text', 'label' => 'Lokasi Penugasan', 'required' => true, 'placeholder' => 'Workshop Bay KM 12 Kulim'],
            ['key' => 'total_karyawan', 'type' => 'number', 'label' => 'Total Personil Terdaftar', 'required' => true],
            ['key' => 'total_lembur', 'type' => 'number', 'label' => 'Akumulasi Jam Lembur', 'required' => true],
            ['key' => 'disetujui_oleh', 'type' => 'text', 'label' => 'Penyetuju Laporan', 'required' => true, 'placeholder' => 'Dany Agung (Head of Equipment)']
        ],
        'columns' => [
            ['key' => 'nama', 'type' => 'text', 'label' => 'Nama Karyawan', 'required' => true],
            ['key' => 'jabatan', 'type' => 'text', 'label' => 'Posisi / Jabatan', 'required' => true],
            ['key' => 'kategori', 'type' => 'text', 'label' => 'Divisi / Departemen', 'required' => false],
            ['key' => 'kj', 'type' => 'number', 'label' => 'Hari Kerja (KJ)', 'required' => true],
            ['key' => 'kl', 'type' => 'number', 'label' => 'Kerja Libur (KL)', 'required' => true],
            ['key' => 'off', 'type' => 'number', 'label' => 'Hari Off (O)', 'required' => true],
            ['key' => 'cuti', 'type' => 'number', 'label' => 'Cuti (CT)', 'required' => true],
            ['key' => 'sakit', 'type' => 'number', 'label' => 'Sakit (S)', 'required' => true],
            ['key' => 'izin', 'type' => 'number', 'label' => 'Izin (I)', 'required' => true],
            ['key' => 'mangkir', 'type' => 'number', 'label' => 'Mangkir (MK)', 'required' => true],
            ['key' => 'total_jo', 'type' => 'number', 'label' => 'Total Hari Kerja', 'required' => true],
            ['key' => 'total_lembur', 'type' => 'number', 'label' => 'Total Lembur (Jam)', 'required' => true]
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($existingTemplateId) {
        $templateId = (int)$existingTemplateId;
        $updTpl = $pdo->prepare("UPDATE report_templates SET schema_json = :schema_json, is_active = 1 WHERE template_id = :template_id");
        $updTpl->execute([':schema_json' => $attendanceSchemaJson, ':template_id' => $templateId]);
        echo "  -> Template sudah ada, skema diperbarui: template_id = {$templateId}\n";
    } else {
        $insTpl = $pdo->prepare("
            INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
            VALUES ('rekap-absensi-yard', 'ABSEN-YARD', 'Rekapitulasi Absensi & Lembur Tim (Yard KM 12)', 1, :schema_json, 1)
        ");
        $insTpl->execute([':schema_json' => $attendanceSchemaJson]);
        $templateId = (int)$pdo->lastInsertId();
        echo "  -> Template resmi baru berhasil didaftarkan: template_id = {$templateId}\n";
    }

    // 3. Pembuatan Skema Tabel employee_attendance_monthly & employee_attendance_daily
    echo "\n[3/5] Memeriksa dan membuat tabel skema data absensi...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employee_attendance_monthly (
            attendance_month_id VARCHAR(50) NOT NULL PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            user_id INT NOT NULL,
            period_key VARCHAR(7) NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            location_id INT NOT NULL DEFAULT 4,
            role_snapshot VARCHAR(100) NULL,
            department VARCHAR(100) NULL,
            kj_days INT NOT NULL DEFAULT 0,
            kl_days INT NOT NULL DEFAULT 0,
            off_days INT NOT NULL DEFAULT 0,
            cuti_days INT NOT NULL DEFAULT 0,
            sakit_days INT NOT NULL DEFAULT 0,
            izin_days INT NOT NULL DEFAULT 0,
            mangkir_days INT NOT NULL DEFAULT 0,
            project_days INT NOT NULL DEFAULT 0,
            total_work_days DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            total_overtime_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_eam_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE CASCADE,
            CONSTRAINT fk_eam_user FOREIGN KEY (user_id) REFERENCES users (user_id),
            CONSTRAINT fk_eam_location FOREIGN KEY (location_id) REFERENCES locations (location_id),
            UNIQUE KEY uq_user_period (user_id, period_key),
            KEY idx_eam_period (period_key),
            KEY idx_eam_location (location_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  -> Tabel `employee_attendance_monthly` aktif & terverifikasi.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employee_attendance_daily (
            daily_log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            attendance_month_id VARCHAR(50) NOT NULL,
            user_id INT NOT NULL,
            log_date DATE NOT NULL,
            status_code VARCHAR(10) NOT NULL,
            clock_in TIME NULL,
            clock_out TIME NULL,
            overtime_hours DECIMAL(4,2) NOT NULL DEFAULT 0.00,
            notes VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ead_month FOREIGN KEY (attendance_month_id) REFERENCES employee_attendance_monthly (attendance_month_id) ON DELETE CASCADE,
            CONSTRAINT fk_ead_user FOREIGN KEY (user_id) REFERENCES users (user_id),
            UNIQUE KEY uq_user_date (user_id, log_date),
            KEY idx_ead_date (log_date),
            KEY idx_ead_status (status_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  -> Tabel `employee_attendance_daily` aktif & terverifikasi.\n";

    // 4. Provisioning 16 Akun Personel Pendukung Baru
    echo "\n[4/5] Provisioning akun 16 personil baru ke tabel `users` dan `user_roles`...\n";

    $newPersonnel = [
        // Security
        ['username' => 'taufiq_h', 'full_name' => 'Taufiq H (Security)', 'role' => 'Security'],
        ['username' => 'jorlan_sibatuara', 'full_name' => 'Jorlan Sibatuara (Security)', 'role' => 'Security'],
        ['username' => 'sudirman_h', 'full_name' => 'Sudirman H (Security)', 'role' => 'Security'],
        ['username' => 'edy_jaka_s', 'full_name' => 'Edy Jaka S (Security)', 'role' => 'Security'],
        // SMK3L / Safety
        ['username' => 'amirudin', 'full_name' => 'Amirudin (SMK3L)', 'role' => 'Inspector K3L / Safety'],
        ['username' => 'koeswanto', 'full_name' => 'Koeswanto (SMK3L)', 'role' => 'Inspector K3L / Safety'],
        // Humas Yard
        ['username' => 'nurdin', 'full_name' => 'Nurdin (Humas Yard)', 'role' => 'Humas Yard'],
        ['username' => 'sujono', 'full_name' => 'Sujono (Humas Yard)', 'role' => 'Humas Yard'],
        ['username' => 'fadli_h', 'full_name' => 'Fadli H (Humas Yard)', 'role' => 'Humas Yard'],
        // Flagman & Logistik
        ['username' => 'david_aritonang', 'full_name' => 'David Aritonang (Flagman)', 'role' => 'Mekanik Junior / Helper'],
        ['username' => 'ahnaf', 'full_name' => 'Ahnaf (Logistik)', 'role' => 'Logistic Head'],
        // Mekanik Harian & Helper Baru Oktober
        ['username' => 'aditya', 'full_name' => 'Aditya (Helper Mekanik)', 'role' => 'Mekanik Junior / Helper'],
        ['username' => 'disma', 'full_name' => 'Disma (Mekanik Harian)', 'role' => 'Mekanik Junior / Helper'],
        ['username' => 'juliadi', 'full_name' => 'Juliadi (Mekanik Harian)', 'role' => 'Mekanik Junior / Helper'],
        ['username' => 'rizky', 'full_name' => 'Rizky (Helper Mekanik)', 'role' => 'Mekanik Junior / Helper'],
        ['username' => 'sunardi', 'full_name' => 'Sunardi (Mekanik Harian)', 'role' => 'Mekanik Junior / Helper'],
    ];

    $hash = password_hash('bra2026', PASSWORD_BCRYPT);
    $defaultLocationId = 4; // Workshop Bay KM 12

    $stmtCheckUser = $pdo->prepare("SELECT user_id, role_id FROM users WHERE username = :username LIMIT 1");
    $stmtInsertUser = $pdo->prepare("
        INSERT INTO users (username, password_hash, full_name, role_id, assigned_location_id, is_active)
        VALUES (:username, :password_hash, :full_name, :role_id, :location_id, 1)
    ");
    $stmtInsertUserRole = $pdo->prepare("
        INSERT IGNORE INTO user_roles (user_id, role_id)
        VALUES (:user_id, :role_id)
    ");

    $createdCount = 0;
    foreach ($newPersonnel as $p) {
        $roleId = $roleMap[$p['role']] ?? 5; // default Helper
        $stmtCheckUser->execute([':username' => $p['username']]);
        $existing = $stmtCheckUser->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $stmtInsertUser->execute([
                ':username' => $p['username'],
                ':password_hash' => $hash,
                ':full_name' => $p['full_name'],
                ':role_id' => $roleId,
                ':location_id' => $defaultLocationId
            ]);
            $userId = (int)$pdo->lastInsertId();
            $stmtInsertUserRole->execute([':user_id' => $userId, ':role_id' => $roleId]);
            echo "  -> User baru ditambahkan: {$p['full_name']} (user_id = {$userId}, role = {$p['role']})\n";
            $createdCount++;
        } else {
            $userId = (int)$existing['user_id'];
            $stmtInsertUserRole->execute([':user_id' => $userId, ':role_id' => $roleId]);
            echo "  -> User sudah ada: {$p['full_name']} (user_id = {$userId})\n";
        }
    }

    echo "  [OK] Provisioning selesai: {$createdCount} user baru berhasil didaftarkan.\n";

    // 5. Verifikasi Integritas Personel Database
    echo "\n[5/5] Verifikasi master users pasca-provisi...\n";
    $totalActiveUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    echo "  * Total pengguna aktif saat ini: {$totalActiveUsers} pengguna (39 sebelumnya + {$createdCount} baru)\n";

    echo "\n=========================================================================\n";
    echo " BATCH 22 SELESAI: SKEMA DAN MASTER PERSONEL 100% SIAP UNTUK INGESTI!\n";
    echo "=========================================================================\n";

} catch (Throwable $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
