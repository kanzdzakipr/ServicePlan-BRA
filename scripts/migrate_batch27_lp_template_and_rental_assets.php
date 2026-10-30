<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 27 - Registrasi Template Laporan, Unit Sewa & Master Asset Resolver
 * 
 * Target:
 * 1. Tabel `report_templates`:
 *    - Daftarkan template 'lp-breakdown-ready' (LP-BD-READY) untuk Laporan Harian Unit Breakdown, Ready & Service Berkala.
 * 2. Tabel `assets`:
 *    - Update unit sewa PT Vadana (DT-41001 s.d. DT-41005) dengan ownership 'Sewa'.
 *    - Injeksi unit riil yang belum terdaftar: DT-00002 (B 9110 ZYT), DT-00080 (B 9632 BIS), PM-00001 (B 9012 ZEH), MG-00001 (1372 - GR3).
 * 3. File helper SSOT `scripts/lp_bdown_asset_resolver.php`:
 *    - Resolusi bertingkat (Exact ID, Serial Number, License Plate, Regex, Alias Khusus) untuk 1.066 event.
 * 4. Tabel `app_data_migrations`:
 *    - Catat migration key 'BATCH_27_LP_TEMPLATE_AND_RENTAL_ASSETS'.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$pdo = Database::getInstance();

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 27 LP BREAKDOWN TEMPLATE & ASSET ENRICHMENT\n";
echo "============================================================================\n\n";

$migrationKey = 'BATCH_27_LP_TEMPLATE_AND_RENTAL_ASSETS';

// Cek apakah sudah pernah dijalankan
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
if ((int)$stmtCheck->fetchColumn() > 0) {
    echo "⚠️ PERINGATAN: Migrasi $migrationKey sudah pernah dicatat di app_data_migrations.\n";
    echo "Melanjutkan pembaruan idempotent agar seluruh data dipastikan sinkron...\n\n";
}

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // STEP 1: Registrasi Template Laporan lp-breakdown-ready
    // -------------------------------------------------------------------------
    echo "--- [1/4] Mendaftarkan Template Laporan LP-BD-READY di report_templates ---\n";
    
    $schemaJson = json_encode([
        'id' => 'lp-breakdown-ready',
        'code' => 'LP-BD-READY',
        'title' => 'Laporan Harian Unit Breakdown, Ready & Service Berkala',
        'category' => 'Pemeliharaan & Workshop',
        'tableTitle' => 'Rincian Log Harian Unit Breakdown & Ready',
        'description' => 'Monitoring harian unit breakdown, penanganan mekanik, status ready, durasi pengerjaan, dan delay pengadaan spare part.',
        'fields' => [
            [
                'key' => 'periode_label',
                'type' => 'text',
                'label' => 'Periode Laporan',
                'required' => true,
                'placeholder' => 'Desember 2025 s.d. 20 Februari 2026'
            ],
            [
                'key' => 'total_event',
                'type' => 'number',
                'label' => 'Total Kejadian Breakdown / Ready',
                'required' => true
            ],
            [
                'key' => 'total_downtime_hours',
                'type' => 'number',
                'label' => 'Total Jam Downtime',
                'required' => true
            ],
            [
                'key' => 'total_delay_procurement_hours',
                'type' => 'number',
                'label' => 'Total Jam Delay Pengadaan Part',
                'required' => true
            ],
            [
                'key' => 'total_active_repair_hours',
                'type' => 'number',
                'label' => 'Total Jam Pengerjaan Murni',
                'required' => true
            ],
            [
                'key' => 'penanggung_jawab',
                'type' => 'text',
                'label' => 'Penanggung Jawab Workshop',
                'required' => true,
                'placeholder' => 'Dany Agung (Head of Equipment)'
            ]
        ],
        'columns' => [
            [
                'key' => 'no_jo',
                'type' => 'text',
                'label' => 'No. JO / Urut',
                'required' => true
            ],
            [
                'key' => 'asset_id',
                'type' => 'text',
                'label' => 'Kode Alat / Unit',
                'required' => true
            ],
            [
                'key' => 'operator_driver',
                'type' => 'text',
                'label' => 'Operator / Driver',
                'required' => false
            ],
            [
                'key' => 'project',
                'type' => 'text',
                'label' => 'Project / Lokasi Proyek',
                'required' => false
            ],
            [
                'key' => 'kondisi',
                'type' => 'select',
                'label' => 'Update Kondisi',
                'options' => ['READY', 'BREAKDOWN', 'INSPEKSI', 'SERVICE BERKALA', 'WAITING PART'],
                'required' => true
            ],
            [
                'key' => 'waktu_breakdown',
                'type' => 'datetime',
                'label' => 'Waktu Breakdown',
                'required' => false
            ],
            [
                'key' => 'waktu_ready',
                'type' => 'datetime',
                'label' => 'Waktu Ready',
                'required' => false
            ],
            [
                'key' => 'jam_downtime',
                'type' => 'number',
                'label' => 'Jam Downtime',
                'required' => false
            ],
            [
                'key' => 'delay_pengadaan_jam',
                'type' => 'number',
                'label' => 'Delay Pengadaan (Jam)',
                'required' => false
            ],
            [
                'key' => 'durasi_pengerjaan_jam',
                'type' => 'number',
                'label' => 'Durasi Pengerjaan (Jam)',
                'required' => false
            ],
            [
                'key' => 'trouble_permasalahan',
                'type' => 'text',
                'label' => 'Permasalahan / Trouble',
                'required' => true
            ],
            [
                'key' => 'mekanik_penanganan',
                'type' => 'text',
                'label' => 'Mekanik Penanganan',
                'required' => false
            ],
            [
                'key' => 'km_hm_down',
                'type' => 'text',
                'label' => 'KM / HM Down',
                'required' => false
            ],
            [
                'key' => 'lokasi_unit_down',
                'type' => 'text',
                'label' => 'Lokasi Unit Down',
                'required' => false
            ],
            [
                'key' => 'estimasi_selesai',
                'type' => 'date',
                'label' => 'Estimasi Selesai',
                'required' => false
            ],
            [
                'key' => 'progres_saat_ini',
                'type' => 'text',
                'label' => 'Progres Saat Ini / Catatan Repair',
                'required' => false
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmtTpl = $pdo->prepare("
        INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active, created_at, updated_at)
        VALUES ('lp-breakdown-ready', 'LP-BD-READY', 'Laporan Harian Unit Breakdown, Ready & Service Berkala', 1, :schema, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            code = VALUES(code),
            title = VALUES(title),
            schema_json = VALUES(schema_json),
            is_active = 1,
            updated_at = NOW()
    ");
    $stmtTpl->execute([':schema' => $schemaJson]);
    
    $tplId = $pdo->query("SELECT template_id FROM report_templates WHERE template_key = 'lp-breakdown-ready'")->fetchColumn();
    echo "✅ Template LP-BD-READY terdaftar dengan template_id: $tplId\n\n";

    // -------------------------------------------------------------------------
    // STEP 2: Update Status Unit Sewa Vadana (DT-41001 s.d. DT-41005)
    // -------------------------------------------------------------------------
    echo "--- [2/4] Menyelaraskan Master Unit Rental Vadana di tabel assets ---\n";
    $rentalUnits = [
        ['DT-41001', 'BM 9291 JO', 'Dump Truck Hino FM260JD / Mercedes Axor (Rental Vadana)'],
        ['DT-41002', 'BM 9503 QO', 'Dump Truck Hino FM260JD / Mercedes Axor (Rental Vadana)'],
        ['DT-41003', 'BM 9956 JO', 'Dump Truck Hino FM260JD / Mercedes Axor (Rental Vadana)'],
        ['DT-41004', 'BM 9289 JO', 'Dump Truck Hino FM260JD / Mercedes Axor (Rental Vadana)'],
        ['DT-41005', 'BM 9678 JO', 'Dump Truck Hino FM260JD / Mercedes Axor (Rental Vadana)'],
    ];

    $stmtUpdRental = $pdo->prepare("
        UPDATE assets
        SET ownership = 'Sewa',
            license_plate = :plate,
            dealer = 'PT. Vadana (Rental Vendor)',
            last_update_timestamp = NOW()
        WHERE asset_id = :id
    ");

    foreach ($rentalUnits as $ru) {
        $stmtUpdRental->execute([
            ':id' => $ru[0],
            ':plate' => $ru[1]
        ]);
        echo "  -> Unit {$ru[0]} ({$ru[1]}) di-set kepemilikan: 'Sewa'\n";
    }
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 3: Injeksi Unit Riil yang Belum Ada di assets
    // -------------------------------------------------------------------------
    echo "--- [3/4] Mendaftarkan Missing Master Assets (DT-00002, DT-00080, PM-00001, MG-00001) ---\n";
    
    $missingAssets = [
        [
            'asset_id' => 'DT-00002',
            'asset_code' => 'DT-00002',
            'license_plate' => 'B 9110 ZYT',
            'hull_number' => 'DT-00002',
            'type' => 'Dump Truck',
            'category' => 'Dump Truck',
            'make_model' => 'Dump Truck Hino FM260JD / Mercedes Axor',
            'ownership' => 'Milik Sendiri',
            'status' => 'READY',
            'serial_number' => 'SN-DT00002-BRA'
        ],
        [
            'asset_id' => 'DT-00080',
            'asset_code' => 'DT-00080',
            'license_plate' => 'B 9632 BIS',
            'hull_number' => 'DT-00080',
            'type' => 'Dump Truck',
            'category' => 'Dump Truck',
            'make_model' => 'Dump Truck Hino FM260JD / Mercedes Axor',
            'ownership' => 'Milik Sendiri',
            'status' => 'READY',
            'serial_number' => 'SN-DT00080-BRA'
        ],
        [
            'asset_id' => 'PM-00001',
            'asset_code' => 'PM-00001',
            'license_plate' => 'B 9012 ZEH',
            'hull_number' => 'PM-00001',
            'type' => 'Prime Mover',
            'category' => 'Prime Mover',
            'make_model' => 'Mercedes Axor 4028 T Prime Mover',
            'ownership' => 'Milik Sendiri',
            'status' => 'READY',
            'serial_number' => 'SN-PM00001-BRA'
        ],
        [
            'asset_id' => 'MG-00001',
            'asset_code' => 'MG-00001',
            'license_plate' => '',
            'hull_number' => 'MG-00001',
            'type' => 'Motor Grader',
            'category' => 'Motor Grader',
            'make_model' => 'Motor Grader 1372 - GR3 LiuGong / Cat',
            'ownership' => 'Milik Sendiri',
            'status' => 'READY',
            'serial_number' => '1372 - GR3'
        ],
    ];

    $stmtInsertAsset = $pdo->prepare("
        INSERT INTO assets (
            asset_id, asset_code, license_plate, hull_number, type, category,
            make_model, ownership, status, serial_number, is_active, created_at, last_update_timestamp
        ) VALUES (
            :id, :code, :plate, :hull, :type, :cat,
            :model, :ownership, :status, :sn, 1, NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
            license_plate = VALUES(license_plate),
            serial_number = VALUES(serial_number),
            make_model = VALUES(make_model),
            last_update_timestamp = NOW()
    ");

    foreach ($missingAssets as $ma) {
        $stmtInsertAsset->execute([
            ':id' => $ma['asset_id'],
            ':code' => $ma['asset_code'],
            ':plate' => $ma['license_plate'],
            ':hull' => $ma['hull_number'],
            ':type' => $ma['type'],
            ':cat' => $ma['category'],
            ':model' => $ma['make_model'],
            ':ownership' => $ma['ownership'],
            ':status' => $ma['status'],
            ':sn' => $ma['serial_number']
        ]);
        echo "  -> Terdaftar aset: {$ma['asset_id']} [{$ma['category']}] (Plate: {$ma['license_plate']})\n";
    }
    echo "\n";

    // -------------------------------------------------------------------------
    // STEP 4: Catat Migrasi di app_data_migrations
    // -------------------------------------------------------------------------
    echo "--- [4/4] Mencatat Riwayat Migrasi di app_data_migrations ---\n";
    $stmtMig = $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES (:k, NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtMig->execute([':k' => $migrationKey]);
    echo "✅ Migrasi $migrationKey berhasil dicatat.\n\n";

    $pdo->commit();
    echo "============================================================================\n";
    echo " BATCH 27 SUKSES: Template laporan & Master SSOT siap untuk Batch 28 & 29!\n";
    echo "============================================================================\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
