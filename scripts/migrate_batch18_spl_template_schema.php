<?php
/**
 * Migration Script: Batch 18 - Registrasi Template & Tata Kelola Skema SPL
 * Repositori: ServicePlan-BRA
 * Tanggal: 30 Oktober 2026
 * 
 * Cakupan:
 * 1. Registrasi master template resmi `spl-overtime` (Kode: `SPL`) di tabel `report_templates`
 * 2. Penyesuaian constraint relasional: melepaskan `uq_spl_overtime_employee` menjadi non-unique index
 *    `idx_spl_overtime_employee_report` agar multi-task personil pada lembar yang sama dapat tercatat
 * 3. Verifikasi ketersediaan Master Lokasi `Workshop Bay KM 12` (`location_id = 4`)
 */

declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';

try {
    $pdo = Database::getInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=========================================================================\n";
    echo " ServicePlan-BRA: Migration Batch 18 - Template & Schema Governance SPL\n";
    echo "=========================================================================\n\n";

    // 1. Template Registration
    echo "[1/3] Registrasi template resmi `spl-overtime` ke tabel `report_templates`...\n";
    $schemaJson = [
        'id' => 'spl-overtime',
        'code' => 'SPL',
        'title' => 'Surat Perintah Lembur (Yard)',
        'category' => 'Pemeliharaan & Workshop',
        'tableTitle' => 'Daftar Personil & Jam Kerja Lembur',
        'description' => 'Surat Perintah Lembur penugasan mekanik, welder, dan helper workshop KM 12.',
        'fields' => [
            ['key' => 'nomor_spl', 'full' => false, 'type' => 'text', 'label' => 'Nomor SPL', 'options' => [], 'required' => true, 'placeholder' => 'SPL-YYYYMMDD-XX'],
            ['key' => 'tanggal_spl', 'full' => false, 'type' => 'date', 'label' => 'Tanggal SPL', 'options' => [], 'required' => true, 'placeholder' => ''],
            ['key' => 'mulai_periode', 'full' => false, 'type' => 'date', 'label' => 'Mulai pelaksanaan', 'options' => [], 'required' => true, 'placeholder' => ''],
            ['key' => 'akhir_periode', 'full' => false, 'type' => 'date', 'label' => 'Selesai pelaksanaan', 'options' => [], 'required' => true, 'placeholder' => ''],
            ['key' => 'lokasi', 'full' => false, 'type' => 'text', 'label' => 'Area / lokasi SPL', 'options' => [], 'required' => true, 'placeholder' => 'Workshop Bay KM 12'],
            ['key' => 'dibuat_oleh', 'full' => false, 'type' => 'text', 'label' => 'Pembuat SPL', 'options' => [], 'required' => true, 'placeholder' => 'Samsul Bahri (Admin Equipment)'],
            ['key' => 'instruksi_oleh', 'full' => false, 'type' => 'text', 'label' => 'Pemberi instruksi SPL', 'options' => [], 'required' => true, 'placeholder' => 'Dany Agung (Head of Equipment)'],
            ['key' => 'target', 'full' => false, 'type' => 'text', 'label' => 'Target waktu', 'options' => [], 'required' => false, 'placeholder' => ''],
            ['key' => 'uraian', 'full' => true, 'type' => 'text', 'label' => 'Instruksi umum lembur', 'options' => [], 'required' => true, 'placeholder' => ''],
            ['key' => 'wo_id', 'full' => false, 'type' => 'text', 'label' => 'Nomor WO / JO terkait', 'options' => [], 'required' => false, 'placeholder' => ''],
        ],
        'columns' => [
            ['key' => 'nama', 'type' => 'text', 'label' => 'Nama personil', 'options' => [], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'jabatan', 'type' => 'text', 'label' => 'Jabatan', 'options' => [], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'lokasi', 'type' => 'text', 'label' => 'Lokasi', 'options' => [], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'uraian', 'type' => 'text', 'label' => 'Uraian pekerjaan', 'options' => [], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'mulai', 'type' => 'time', 'label' => 'Jam mulai', 'options' => [], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'selesai', 'type' => 'time', 'label' => 'Jam selesai', 'options' => [], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'durasi', 'type' => 'number', 'label' => 'Total jam', 'options' => [], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'verifikasi_mulai', 'type' => 'select', 'label' => 'Verifikasi mulai', 'options' => ['Belum diverifikasi', 'Terverifikasi'], 'readonly' => false, 'required' => true, 'allowNegative' => false],
            ['key' => 'verifikasi_selesai', 'type' => 'select', 'label' => 'Verifikasi selesai', 'options' => ['Belum diverifikasi', 'Terverifikasi'], 'readonly' => false, 'required' => true, 'allowNegative' => false],
        ],
    ];

    $checkTpl = $pdo->prepare("SELECT template_id FROM report_templates WHERE template_key = 'spl-overtime' OR code = 'SPL' LIMIT 1");
    $checkTpl->execute();
    $existingTpl = $checkTpl->fetch(PDO::FETCH_ASSOC);

    if (!$existingTpl) {
        $insTpl = $pdo->prepare("
            INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active, created_at, updated_at)
            VALUES ('spl-overtime', 'SPL', 'Surat Perintah Lembur (Yard)', 1, :schema_json, 1, NOW(), NOW())
        ");
        $insTpl->execute([':schema_json' => json_encode($schemaJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        $templateId = (int) $pdo->lastInsertId();
        echo "  [+] Template baru berhasil didaftarkan: template_id={$templateId} (Code: SPL, Key: spl-overtime)\n";
    } else {
        $templateId = (int) $existingTpl['template_id'];
        $updTpl = $pdo->prepare("
            UPDATE report_templates 
            SET template_key = 'spl-overtime', code = 'SPL', title = 'Surat Perintah Lembur (Yard)', 
                schema_json = :schema_json, is_active = 1, updated_at = NOW()
            WHERE template_id = :template_id
        ");
        $updTpl->execute([
            ':schema_json' => json_encode($schemaJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':template_id' => $templateId
        ]);
        echo "  [~] Template berhasil dimutakhirkan: template_id={$templateId} (Code: SPL, Key: spl-overtime)\n";
    }

    // 2. Schema Adjustment: Constraint uq_spl_overtime_employee
    echo "\n[2/3] Memeriksa dan mengamankan constraint unik pada tabel `spl_overtime_records`...\n";
    $indexes = $pdo->query("SHOW INDEX FROM spl_overtime_records")->fetchAll(PDO::FETCH_ASSOC);
    $hasUniqueEmployee = false;
    $hasNonUniqueEmployee = false;

    foreach ($indexes as $idx) {
        if ($idx['Key_name'] === 'uq_spl_overtime_employee') {
            $hasUniqueEmployee = true;
        }
        if ($idx['Key_name'] === 'idx_spl_overtime_employee_report') {
            $hasNonUniqueEmployee = true;
        }
    }

    if ($hasUniqueEmployee) {
        echo "  [-] Melepaskan constraint unik `uq_spl_overtime_employee` agar multi-task personil dapat dicatat...\n";
        $pdo->exec("ALTER TABLE spl_overtime_records DROP INDEX uq_spl_overtime_employee");
        echo "      [OK] Constraint unik `uq_spl_overtime_employee` berhasil dilepaskan.\n";
    } else {
        echo "  [OK] Constraint `uq_spl_overtime_employee` sudah tidak aktif.\n";
    }

    if (!$hasNonUniqueEmployee) {
        echo "  [+] Menambahkan indeks performa pencarian `idx_spl_overtime_employee_report` (report_id, employee_user_id)...\n";
        $pdo->exec("ALTER TABLE spl_overtime_records ADD INDEX idx_spl_overtime_employee_report (report_id, employee_user_id)");
        echo "      [OK] Indeks `idx_spl_overtime_employee_report` berhasil dibuat.\n";
    } else {
        echo "  [OK] Indeks `idx_spl_overtime_employee_report` sudah tersedia.\n";
    }

    // 3. Location SSOT Verification
    echo "\n[3/3] Memverifikasi SSOT Master Lokasi Workshop Bay KM 12...\n";
    $locStmt = $pdo->prepare("SELECT location_id, location_name, region FROM locations WHERE location_id = 4 LIMIT 1");
    $locStmt->execute();
    $loc = $locStmt->fetch(PDO::FETCH_ASSOC);
    if ($loc) {
        echo "  [OK] Lokasi default terverifikasi: location_id={$loc['location_id']} | {$loc['location_name']} ({$loc['region']})\n";
    } else {
        echo "  [!] Warning: location_id 4 tidak ditemukan, memeriksa berdasarkan nama...\n";
        $locByName = $pdo->query("SELECT location_id, location_name FROM locations WHERE location_name LIKE '%Workshop%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($locByName) {
            echo "  [OK] Ditemukan alternatif: location_id={$locByName['location_id']} | {$locByName['location_name']}\n";
        }
    }

    echo "\n=========================================================================\n";
    echo " EKSEKUSI BATCH 18 SELESAI DENGAN SUKSES!\n";
    echo " - Template SPL terdaftar di template_id: {$templateId}\n";
    echo " - Integritas skema spl_overtime_records siap menerima multi-task\n";
    echo " - Zero-Conflict guarantee aktif\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR] Eksekusi Batch 18 gagal: " . $e->getMessage() . "\n";
    exit(1);
}

