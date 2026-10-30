<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 30 - Ingesti Journey Management Plan (JMP PHR) & Penugasan Lapangan Selsin
 * 
 * Target:
 * 1. Tabel `assets`:
 *    - Daftarkan armada sewa/penunjang JMP yang belum ada di assets (MB, LV, DT vendor PHR).
 *    - Update current_location_id = 177 dan operational_position = 'Borrow Pit Selsin' untuk 12 unit Selsin.
 * 2. Tabel `asset_operator_history`:
 *    - Injeksi 194 penugasan driver/operator JMP PHR (Desember 2025) lengkap dengan No KP, RFID, No Telp, dan Rute Kerja.
 *    - Injeksi penugasan 12 operator utama di site Borrow Pit Selsin (Januari - Februari 2026).
 * 3. Tabel `app_data_migrations`:
 *    - Catat migration key 'BATCH_30_INGEST_JMP_AND_SELSIN_ASSIGNMENTS'.
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';
require_once __DIR__ . '/analyze_lp_deep.php';
require_once __DIR__ . '/lp_bdown_asset_resolver.php';

$pdo = Database::getInstance();
$resolver = new LpAssetResolver($pdo);

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 30 JMP PHR & SELSIN FIELD ASSIGNMENTS INGESTION\n";
echo " Source: material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI/ (05b & 06)\n";
echo "============================================================================\n\n";

$migrationKey = 'BATCH_30_INGEST_JMP_AND_SELSIN_ASSIGNMENTS';

$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM app_data_migrations WHERE migration_key = :k");
$stmtCheck->execute([':k' => $migrationKey]);
if ((int)$stmtCheck->fetchColumn() > 0) {
    echo "⚠️ PERINGATAN: Migrasi $migrationKey sudah pernah dicatat di app_data_migrations.\n";
    echo "Melanjutkan pembaruan idempotent agar seluruh data dipastikan sinkron...\n\n";
}

$baseDir = dirname(__DIR__) . '/material/LP_UNIT_BDOWN_READY_SD_20_PEBRUARI';
$c5b = parseCellMarkdown($baseDir . '/05b_sheet1_equipment_celcin.md');
$c6 = parseCellMarkdown($baseDir . '/06_jmp_dec25.md');

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // STEP 1: Daftarkan Unit Vendor / Penunjang JMP yang Belum Terdaftar di assets
    // -------------------------------------------------------------------------
    echo "--- [1/3] Mendaftarkan Master Unit Penunjang JMP PHR di tabel assets ---\n";

    $stmtInsertAsset = $pdo->prepare("
        INSERT INTO assets (
            asset_id, asset_code, license_plate, hull_number, type, category,
            make_model, ownership, status, is_active, dealer, created_at, last_update_timestamp
        ) VALUES (
            :id, :code, :plate, :hull, :type, :cat,
            :model, 'Sewa', 'READY', 1, 'Sub-Contractor PHR SPHR00751A', NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
            license_plate = VALUES(license_plate),
            last_update_timestamp = NOW()
    ");

    $newAssetsRegistered = 0;
    foreach ($c6 as $r => $cols) {
        if ($r < 7) continue;
        $driver = trim($cols['B']['val'] ?? '');
        if ($driver === '' || $driver === 'Nama Pengemudi') continue;

        $plate = trim($cols['F']['val'] ?? '');
        $unit = trim($cols['G']['val'] ?? '');
        $type = trim($cols['H']['val'] ?? '');

        $resolved = $resolver->resolve($plate);
        if (!$resolved && $unit !== '') {
            $resolved = $resolver->resolve($unit);
        }

        if (!$resolved) {
            // Tentukan ID baru berdasarkan unit code mentah
            $targetId = $unit !== '' ? strtoupper($unit) : 'VND-' . preg_replace('/[^A-Z0-9]/', '', strtoupper($plate));
            
            // Tentukan category
            $category = 'Other';
            if (stripos($type, 'BUS') !== false || stripos($targetId, 'MB') !== false) {
                $category = 'Other';
                $model = "Medium / Micro Transport Bus ($type)";
            } elseif (stripos($type, 'LIGHT') !== false || stripos($targetId, 'LV') !== false) {
                $category = 'Light Vehicle';
                $model = "Support Light Vehicle / D-Cabin ($type)";
            } elseif (stripos($type, 'DUMP') !== false || stripos($targetId, 'DT') !== false) {
                $category = 'Dump Truck';
                $model = "Dump Truck Vendor Regional ($type)";
            } elseif (stripos($type, 'PRIME') !== false || stripos($targetId, 'PM') !== false) {
                $category = 'Prime Mover';
                $model = "Prime Mover Heavy Haulage ($type)";
            } elseif (stripos($type, 'FUEL') !== false || stripos($targetId, 'FT') !== false) {
                $category = 'Other';
                $model = "Fuel Tank Service Truck ($type)";
            } elseif (stripos($type, 'CRANE') !== false || stripos($targetId, 'CC') !== false || stripos($targetId, 'TMC') !== false) {
                $category = 'Other';
                $model = "Lifting & Crane Equipment ($type)";
            } else {
                $model = "Kendaraan Penunjang Lapangan ($type)";
            }

            $stmtInsertAsset->execute([
                ':id' => $targetId,
                ':code' => $targetId,
                ':plate' => $plate,
                ':hull' => $targetId,
                ':type' => $type,
                ':cat' => $category,
                ':model' => $model
            ]);
            $newAssetsRegistered++;
        }
    }
    echo "✅ Berhasil mendaftarkan $newAssetsRegistered unit armada penunjang JMP baru ke tabel assets.\n\n";

    // Re-initialize resolver agar asset-asset baru langsung terbaca
    $resolver = new LpAssetResolver($pdo);

    // -------------------------------------------------------------------------
    // STEP 2: Ingesti 194 Penugasan Driver JMP PHR ke asset_operator_history
    // -------------------------------------------------------------------------
    echo "--- [2/3] Menginjeksi Penugasan Driver JMP PHR ke asset_operator_history ---\n";

    $stmtHistory = $pdo->prepare("
        INSERT INTO asset_operator_history (
            asset_id, operator_name, start_date, end_date, shift_type, handover_notes, created_at
        ) VALUES (
            :aid, :op_name, :s_date, :e_date, 'Shift 1 (Utama)', :notes, NOW()
        )
    ");

    $stmtCheckHistory = $pdo->prepare("
        SELECT COUNT(*) FROM asset_operator_history
        WHERE asset_id = :aid AND operator_name = :op AND start_date = :s
    ");

    $jmpIngested = 0;
    foreach ($c6 as $r => $cols) {
        if ($r < 7) continue;
        $driver = trim($cols['B']['val'] ?? '');
        if ($driver === '' || $driver === 'Nama Pengemudi') continue;

        $plate = trim($cols['F']['val'] ?? '');
        $unit = trim($cols['G']['val'] ?? '');
        $phone = trim($cols['C']['val'] ?? '');
        $kpNo = trim($cols['D']['val'] ?? '');
        $rfid = trim($cols['E']['val'] ?? '');
        $route = trim($cols['O']['val'] ?? (trim($cols['M']['val'] ?? 'All Area PHR')));

        $startDate = '2025-12-01';
        if (isset($cols['I']['val']) && is_numeric($cols['I']['val']) && $cols['I']['val'] > 40000) {
            $startDate = date('Y-m-d', (int)(($cols['I']['val'] - 25569) * 86400));
        }

        $endDate = '2025-12-31';
        if (isset($cols['J']['val']) && is_numeric($cols['J']['val']) && $cols['J']['val'] > 40000) {
            $endDate = date('Y-m-d', (int)(($cols['J']['val'] - 25569) * 86400));
        }

        $assetId = $resolver->resolve($plate);
        if (!$assetId && $unit !== '') {
            $assetId = $resolver->resolve($unit);
        }
        if (!$assetId) {
            $assetId = $unit !== '' ? strtoupper($unit) : 'VND-' . preg_replace('/[^A-Z0-9]/', '', strtoupper($plate));
        }

        // Cek duplikasi idempotent
        $stmtCheckHistory->execute([':aid' => $assetId, ':op' => $driver, ':s' => $startDate]);
        if ((int)$stmtCheckHistory->fetchColumn() > 0) {
            continue;
        }

        $notes = "JMP PHR Des 2025 (Kontrak SPHR00751A) | No. KP: {$kpNo} | Kartu RFID: {$rfid} | Telp: {$phone} | Rute: {$route}";

        $stmtHistory->execute([
            ':aid' => $assetId,
            ':op_name' => $driver,
            ':s_date' => $startDate,
            ':e_date' => $endDate,
            ':notes' => $notes
        ]);
        $jmpIngested++;
    }
    echo "✅ Berhasil menyuntikkan $jmpIngested penugasan resmi pengemudi & sertifikasi RFID JMP PHR.\n\n";

    // -------------------------------------------------------------------------
    // STEP 3: Penugasan 12 Unit Alat Berat di Borrow Pit Selsin (05b)
    // -------------------------------------------------------------------------
    echo "--- [3/3] Menyelaraskan Penugasan 12 Alat Berat Borrow Pit Selsin ---\n";

    $selsinUnits = [
        ['raw' => 'Exca 00007', 'driver' => 'Bonar Marbun'],
        ['raw' => 'DT 00040', 'driver' => 'Zahar'],
        ['raw' => 'DT 00047', 'driver' => 'Suprianto'],
        ['raw' => 'DT 00019', 'driver' => 'Kardiman'],
        ['raw' => 'DT 00013', 'driver' => 'Waluyo'],
        ['raw' => 'DT 00023', 'driver' => 'Pawitno'],
        ['raw' => 'DT 00064', 'driver' => 'Padli'],
        ['raw' => 'DT 00006', 'driver' => 'Sawaluddin'],
        ['raw' => 'DT 00004', 'driver' => 'Erick'],
        ['raw' => 'DT 00039', 'driver' => 'Supriyanto'],
        ['raw' => 'DT 04040', 'driver' => 'Hermansah'],
        ['raw' => 'DT 04044', 'driver' => 'Ardika Barus'],
    ];

    $stmtUpdLocation = $pdo->prepare("
        UPDATE assets
        SET current_location_id = 177,
            operational_position = 'Borrow Pit Selsin',
            current_operator_name = :driver,
            last_update_timestamp = NOW()
        WHERE asset_id = :aid
    ");

    $selsinUpdated = 0;
    foreach ($selsinUnits as $su) {
        $aid = $resolver->resolve($su['raw']);
        if ($aid) {
            $stmtUpdLocation->execute([
                ':driver' => $su['driver'],
                ':aid' => $aid
            ]);

            // Tambahkan history penugasan di site Selsin
            $stmtCheckHistory->execute([':aid' => $aid, ':op' => $su['driver'], ':s' => '2026-01-01']);
            if ((int)$stmtCheckHistory->fetchColumn() === 0) {
                $stmtHistory->execute([
                    ':aid' => $aid,
                    ':op_name' => $su['driver'],
                    ':s_date' => '2026-01-01',
                    ':e_date' => '2026-02-28',
                    ':notes' => "Penempatan resmi armada tambang di Borrowpit Selsin (Material 05b Equipment Celcin)."
                ]);
            }
            $selsinUpdated++;
            echo "  -> Unit $aid di-assign ke Site Celcin (Operator: {$su['driver']})\n";
        }
    }
    echo "✅ Berhasil memetakan $selsinUpdated unit alat berat ke Site Celcin (Location ID 177).\n\n";

    // Catat migrasi di app_data_migrations
    $stmtMig = $pdo->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES (:k, NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtMig->execute([':k' => $migrationKey]);
    echo "✅ Migrasi $migrationKey berhasil dicatat.\n\n";

    $pdo->commit();
    echo "============================================================================\n";
    echo " BATCH 30 SUKSES: JMP PHR & Penugasan Selsin berhasil diintegrasikan!\n";
    echo "============================================================================\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

