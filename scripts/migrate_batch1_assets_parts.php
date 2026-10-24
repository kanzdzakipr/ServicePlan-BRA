<?php
declare(strict_types=1);

/**
 * Migration Script: Batch 1 - Standardisasi Identitas Aset & Master Katalog Suku Cadang
 * 
 * Target:
 * 1. Tabel `assets`:
 *    - Update operational aliases (`alias_name`) and hull codes (`hull_number`)
 *    - Populate missing serial numbers for Vibro and special machinery
 * 2. Tabel `parts`:
 *    - Ingest 45 new parts with OEM Part Number from `05_03`
 *    - Ingest 627 general hardware/consumables with standardized SKU `GEN-HW-xxxx`
 *    - Update physical ending inventory balance (`stock_qty`) for 673 matched parts
 */

define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();

echo "============================================================================\n";
echo " SERVICEPLAN-BRA: BATCH 1 DATABASE ENRICHMENT MIGRATION\n";
echo " Source: Laporan Divisi Equipment Januari 2026 (Asset & Warehouse Catalog)\n";
echo "============================================================================\n\n";

$db->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // 1. ASSET IDENTITY & ALIAS ENRICHMENT
    // -------------------------------------------------------------------------
    echo "[1/3] Memperkaya Identitas & Alias Operasional Aset...\n";

    $aliasUpdates = [
        // Vibro Compactors (Bomag Smooth Drum, Padfoot, CAT)
        'PF-00001' => ['alias' => 'PF 01', 'hull' => 'BOMAG FOOT (01)', 'sn' => '961884301016'],
        'PF-00004' => ['alias' => 'PF 02', 'hull' => 'BOMAG FOOT (02)', 'sn' => '961884301614'],
        'PF-00003' => ['alias' => 'PF 03', 'hull' => 'BOMAG FOOT (03)', 'sn' => '961582391152'],
        'PF-00011' => ['alias' => 'SD 05', 'hull' => 'BOMAG SINGLE DRUM (05)', 'sn' => '961884301945'],
        'SUP-00009' => ['alias' => 'SD 06', 'hull' => 'BOMAG SINGLE DRUM (06)', 'sn' => '961884302060'],
        'PF-00005' => ['alias' => 'SD 01', 'hull' => 'BOMAG DRUM (01)', 'sn' => '961582391008'],
        'PF-00006' => ['alias' => 'SD 02', 'hull' => 'BOMAG DRUM (02)', 'sn' => '961582391036'],
        'PF-00007' => ['alias' => 'SD 03', 'hull' => 'BOMAG DRUM (03)', 'sn' => '961582391145'],
        'PF-00008' => ['alias' => 'SD 07', 'hull' => 'BOMAG SINGLE DRUM 2024 (07)', 'sn' => '961582391103'],
        'PF-00010' => ['alias' => 'SD 08', 'hull' => 'BOMAG SINGLE DRUM 2024 (08)', 'sn' => '961582391660'],
        'PF-00013' => ['alias' => 'PF 05', 'hull' => 'BOMAG FOOT (05)', 'sn' => '961582391717'],
        'PF-00014' => ['alias' => 'SD 11', 'hull' => 'CAT CS10GC (11)', 'sn' => 'T8F03605'],
        'PF-00015' => ['alias' => 'SD 12', 'hull' => 'CAT CS10GC (12)', 'sn' => 'T8F03606'],
        'PF-00016' => ['alias' => 'PF 04', 'hull' => 'CAT Pad Foot CS10GC (04)', 'sn' => 'T8F00361'],
        
        // Excavators
        'EXC-00001' => ['alias' => 'EXCA 01', 'hull' => 'EXCA-01', 'sn' => 'C51502'],
        'EXC-00002' => ['alias' => 'EXCA 02', 'hull' => 'EXCA-02', 'sn' => 'DBCH2245'],
        'EXC-00003' => ['alias' => 'EXCA 21', 'hull' => 'EXCA-21', 'sn' => 'CE-0003'],
        'EXC-00004' => ['alias' => 'EXCA 05', 'hull' => 'EXCA-05', 'sn' => 'SYW01364'],
        'EXC-00005' => ['alias' => 'EXCA 06', 'hull' => 'EXCA-06', 'sn' => 'GX-0005'],
        
        // Bulldozers
        'DZ-00001' => ['alias' => 'DOZER 01', 'hull' => 'DZ-01', 'sn' => 'ZLHD116HR1B05388'],
        'DZ-00002' => ['alias' => 'DOZER 02', 'hull' => 'DZ-02', 'sn' => 'P6G01656'],
        'DZ-00003' => ['alias' => 'DOZER 05', 'hull' => 'BRA-05', 'sn' => 'D6G-0003'],
        'DZ-00004' => ['alias' => 'DOZER 10', 'hull' => 'DZ-10', 'sn' => 'CHSDA17AKSB054708'],
        
        // Motor Graders
        'MG-00004' => ['alias' => 'MG 01', 'hull' => 'GR135-99', 'sn' => 'GR135-99'],
        'MG-00005' => ['alias' => 'MG 02', 'hull' => 'GR135-2066', 'sn' => '2066'],
        
        // Special Machinery
        'SUP-41001' => ['alias' => 'SD 01', 'hull' => 'SPREADER (01)', 'sn' => 'XK185-001'],
        'RM-41001'  => ['alias' => 'RM 01', 'hull' => 'RECYCLING (01)', 'sn' => 'RMZ00126'],
        'SB-00001'  => ['alias' => 'BREAKER 01', 'hull' => 'BREAKER (01)', 'sn' => 'SAGAE320'],
        'TMC-00001' => ['alias' => 'CRANE 01', 'hull' => 'TMC-00001', 'sn' => 'B 9435 XFY'],
        'FBT-00001' => ['alias' => 'TR 01', 'hull' => 'SL-01', 'sn' => 'BK 8143 XE']
    ];

    $stmtAsset = $db->prepare("UPDATE assets SET 
        alias_name = COALESCE(NULLIF(:alias, ''), alias_name),
        hull_number = COALESCE(NULLIF(:hull, ''), hull_number),
        serial_number = COALESCE(NULLIF(:sn, ''), serial_number)
        WHERE asset_id = :id");

    $assetUpdated = 0;
    foreach ($aliasUpdates as $aid => $info) {
        $stmtAsset->execute([
            ':id' => $aid,
            ':alias' => $info['alias'],
            ':hull' => $info['hull'],
            ':sn' => $info['sn']
        ]);
        $assetUpdated += $stmtAsset->rowCount();
    }
    echo "  -> Berhasil memperbarui $assetUpdated unit aset dengan alias, kode lambung, dan serial number operasional.\n\n";

    // -------------------------------------------------------------------------
    // 2. PARTS CATALOG ENRICHMENT & STOCK BALANCE SYNC
    // -------------------------------------------------------------------------
    echo "[2/3] Memproses Master Suku Cadang dari Kartu Stok Warehouse (05_03)...\n";

    $existingParts = $db->query("SELECT part_id, part_number FROM parts")->fetchAll(PDO::FETCH_ASSOC);
    $existingPns = [];
    foreach ($existingParts as $p) {
        $existingPns[strtoupper(trim($p['part_number']))] = (int)$p['part_id'];
    }

    function infer_part_category(string $name): string {
        $n = strtolower($name);
        if (str_contains($n, 'filter') || str_contains($n, 'cartridge') || str_contains($n, 'separator') || str_contains($n, 'element')) return 'Filter';
        if (str_contains($n, 'hose') || str_contains($n, 'selang')) return 'Hose';
        if (str_contains($n, 'cutting bit') || str_contains($n, 'tooth') || str_contains($n, 'toot') || str_contains($n, 'blade')) return 'Cutting Bit';
        if (str_contains($n, 'ban') || str_contains($n, 'tire') || str_contains($n, 'tyre') || str_contains($n, 'velg') || str_contains($n, 'pelek')) return 'Tire';
        if (str_contains($n, 'aki') || str_contains($n, 'accu') || str_contains($n, 'battery') || str_contains($n, 'baterai')) return 'Battery';
        if (str_contains($n, 'seal') || str_contains($n, 'o-ring') || str_contains($n, 'packing') || str_contains($n, 'gasket') || str_contains($n, 'piston') || str_contains($n, 'bearing')) return 'Engine Part';
        if (str_contains($n, 'hydraulic') || str_contains($n, 'hidrolik') || str_contains($n, 'cylinder hoist') || str_contains($n, 'pump')) return 'Hydraulic';
        if (str_contains($n, 'baut') || str_contains($n, 'mur') || str_contains($n, 'nut') || str_contains($n, 'bolt') || str_contains($n, 'klem') || str_contains($n, 'clamp') || str_contains($n, 'nepel') || str_contains($n, 'lampu') || str_contains($n, 'relay') || str_contains($n, 'fuse')) return 'Fast Moving';
        return 'Other';
    }

    $f5_03_path = dirname(__DIR__) . '/material/ASSET/LAPORAN DIVISI EQUIPMENT JANUARI 2026/05_03_Laporan_Stok_Penerimaan_dan_Pemakaian_Spare_Part.md';
    if (!file_exists($f5_03_path)) {
        throw new RuntimeException("File katalog suku cadang 05_03 tidak ditemukan di: $f5_03_path");
    }

    $f5_03 = file($f5_03_path);

    $stmtInsertPart = $db->prepare("INSERT INTO parts (
        part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, unit_cost, location_warehouse
    ) VALUES (
        :pn, :pname, :cat, :uom, :qty, 0, 0.00, 'Gudang Duri'
    )");

    $stmtUpdateStock = $db->prepare("UPDATE parts SET 
        stock_qty = :qty,
        category = COALESCE(NULLIF(category, 'Other'), :cat)
        WHERE part_id = :id");

    $partsInsertedOEM = 0;
    $partsInsertedHW = 0;
    $partsUpdated = 0;
    $hwSeq = 1;

    foreach ($f5_03 as $line) {
        $trim = trim($line);
        if (!str_starts_with($trim, '|') || str_contains($trim, '---') || str_contains($trim, 'Nama Parts')) continue;
        $cols = array_map('trim', explode('|', trim($trim, '|')));
        if (count($cols) < 18) continue;
        
        $name = $cols[0];
        $pn = trim($cols[1]);
        $satuan = $cols[2];
        $saldo = max(0, (int)($cols[17] ?? 0));
        $cat = infer_part_category($name);

        if ($pn === '') {
            $sku = sprintf("GEN-HW-%04d", $hwSeq++);
            $stmtInsertPart->execute([
                ':pn' => $sku,
                ':pname' => $name,
                ':cat' => $cat,
                ':uom' => $satuan !== '' ? $satuan : 'pcs',
                ':qty' => $saldo
            ]);
            $partsInsertedHW++;
        } else {
            $pnClean = strtoupper($pn);
            if (isset($existingPns[$pnClean])) {
                $stmtUpdateStock->execute([
                    ':qty' => $saldo,
                    ':cat' => $cat,
                    ':id'  => $existingPns[$pnClean]
                ]);
                $partsUpdated += $stmtUpdateStock->rowCount();
            } else {
                $stmtInsertPart->execute([
                    ':pn' => $pn,
                    ':pname' => $name,
                    ':cat' => $cat,
                    ':uom' => $satuan !== '' ? $satuan : 'pcs',
                    ':qty' => $saldo
                ]);
                $existingPns[$pnClean] = (int)$db->lastInsertId();
                $partsInsertedOEM++;
            }
        }
    }

    echo "  -> Suku cadang baru dengan OEM Part Number: $partsInsertedOEM item.\n";
    echo "  -> General Hardware & Consumables (SKU GEN-HW-xxxx): $partsInsertedHW item.\n";
    echo "  -> Saldo stok diperbarui pada part eksisting: $partsUpdated item.\n\n";

    // -------------------------------------------------------------------------
    // 3. AUDIT & LOGGING
    // -------------------------------------------------------------------------
    echo "[3/3] Memverifikasi Hasil Migrasi...\n";

    $totalAssetsCount = (int)$db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
    $totalPartsCount = (int)$db->query("SELECT COUNT(*) FROM parts")->fetchColumn();
    $distinctPnCount = (int)$db->query("SELECT COUNT(DISTINCT part_number) FROM parts")->fetchColumn();

    if ($totalPartsCount !== $distinctPnCount) {
        throw new RuntimeException("Inkonsistensi terdeteksi: Terdapat part number ganda di tabel parts!");
    }

    // Catat log ke app_data_migrations jika tabel ada
    $checkMigTbl = $db->query("SHOW TABLES LIKE 'app_data_migrations'")->fetchColumn();
    if ($checkMigTbl) {
        $stmtMig = $db->prepare("INSERT INTO app_data_migrations (migration_key, applied_at) 
            VALUES (:key, NOW()) 
            ON DUPLICATE KEY UPDATE applied_at = NOW()");
        $stmtMig->execute([
            ':key' => 'BATCH_1_EQUIPMENT_REPORT_202601'
        ]);
    }

    $db->commit();

    echo "============================================================================\n";
    echo " BATCH 1 MIGRATION BERHASIL DISELESAIKAN SECARA PERMANEN!\n";
    echo " - Total Aset Terdaftar: $totalAssetsCount unit\n";
    echo " - Total Katalog Suku Cadang: $totalPartsCount item (Zero Duplicates)\n";
    echo "============================================================================\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR: Migrasi Batch 1 Gagal - " . $e->getMessage() . "\n";
    exit(1);
}
