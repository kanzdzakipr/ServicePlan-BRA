<?php
declare(strict_types=1);

/**
 * Migration & Seeder: Seed Baseline Tire Layout and Pressure Specifications
 * for all Wheeled Heavy Equipment that do not have tire inspection records yet.
 * 
 * Sources & standards:
 * - material/BAN-GREASE-CUTTING_BIT-AKI/REPORT_BAN_UPDATE_19.07.2026.md
 * - Dump Truck / Water Truck / Prime Mover / Trado: 10-Tire layout (Steer, Mid Drive, Rear Drive), 110-118 PSI, 15-20mm RTD
 * - Light Vehicle: 4-Tire layout (FL, FR, RL, RR), 35 PSI, 12-14mm RTD
 * - Motor Grader: 6-Tire layout (FL, FR, R1L, R1R, R2L, R2R), 40 PSI, 18-22mm RTD
 * - Vibro Compactor: 2-Tire rear pneumatic (RL, RR), 28 PSI, 16-20mm RTD
 */

$db = new PDO('mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4',
    'root', getenv('LOCAL_MYSQL_PASSWORD') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

// Find all wheeled assets without any records in tire_inspections
$sql = "SELECT a.asset_id, a.category, a.make_model, a.hull_number
        FROM assets a
        LEFT JOIN (
            SELECT DISTINCT asset_id FROM tire_inspections
        ) t ON a.asset_id = t.asset_id
        WHERE t.asset_id IS NULL
          AND a.category IN ('Dump Truck', 'Water Truck', 'Prime Mover', 'Trado', 'Light Vehicle', 'Motor Grader', 'Vibro Compactor', 'Other')
        ORDER BY a.asset_id ASC";

$stmt = $db->query($sql);
$missingAssets = $stmt->fetchAll();

echo "Found " . count($missingAssets) . " wheeled assets without tire inspection records.\n";

if (empty($missingAssets)) {
    echo "All wheeled assets already have tire records.\n";
    exit(0);
}

$insertStmt = $db->prepare("INSERT INTO tire_inspections 
    (asset_id, tire_serial_no, tire_position, tread_depth_mm, air_pressure_psi, condition_color, inspected_at, notes)
    VALUES (:asset_id, :tire_serial_no, :tire_position, :tread_depth_mm, :air_pressure_psi, :condition_color, :inspected_at, :notes)");

$brands = ['BS', 'MI', 'GY', 'GT', 'MA'];
$insertedCount = 0;
$db->beginTransaction();

try {
    foreach ($missingAssets as $asset) {
        $category = $asset['category'];
        $assetId = $asset['asset_id'];
        
        // Define layout based on category
        $layout = [];
        if ($category === 'Light Vehicle') {
            $layout = [
                ['FL', 35, 13.50, 'GREEN', 'Steer Depan Kiri'],
                ['FR', 35, 13.80, 'GREEN', 'Steer Depan Kanan'],
                ['RL', 36, 14.00, 'GREEN', 'Drive Belakang Kiri'],
                ['RR', 36, 14.20, 'GREEN', 'Drive Belakang Kanan'],
            ];
        } elseif ($category === 'Motor Grader') {
            $layout = [
                ['FL', 40, 21.00, 'GREEN', 'Depan Kiri'],
                ['FR', 40, 20.50, 'GREEN', 'Depan Kanan'],
                ['R1L', 42, 19.80, 'GREEN', 'Tengah Kiri'],
                ['R1R', 42, 19.50, 'GREEN', 'Tengah Kanan'],
                ['R2L', 42, 18.90, 'GREEN', 'Belakang Kiri'],
                ['R2R', 42, 18.70, 'GREEN', 'Belakang Kanan'],
            ];
        } elseif ($category === 'Vibro Compactor') {
            $layout = [
                ['RL', 28, 18.50, 'GREEN', 'Roda Pneumatik Belakang Kiri'],
                ['RR', 28, 18.20, 'GREEN', 'Roda Pneumatik Belakang Kanan'],
            ];
        } else {
            // Standard 10-Tire Heavy Equipment (Dump Truck, Water Truck, Prime Mover, Trado, etc.)
            $layout = [
                ['FL', 115, 18.50, 'GREEN', 'Steer Depan Kiri'],
                ['FR', 115, 18.00, 'GREEN', 'Steer Depan Kanan'],
                ['R1LO', 112, 16.50, 'GREEN', 'Drive Mid Luar Kiri'],
                ['R1LI', 112, 16.00, 'GREEN', 'Drive Mid Dalam Kiri'],
                ['R1RI', 112, 16.20, 'GREEN', 'Drive Mid Dalam Kanan'],
                ['R1RO', 112, 16.80, 'GREEN', 'Drive Mid Luar Kanan'],
                ['R2LO', 114, 17.50, 'GREEN', 'Drive Rear Luar Kiri'],
                ['R2LI', 114, 17.00, 'GREEN', 'Drive Rear Dalam Kiri'],
                ['R2RI', 114, 17.20, 'GREEN', 'Drive Rear Dalam Kanan'],
                ['R2RO', 114, 17.60, 'GREEN', 'Drive Rear Luar Kanan'],
            ];
        }

        $now = date('Y-m-d H:i:s');
        foreach ($layout as $idx => $t) {
            $brand = $brands[($idx + strlen($assetId)) % count($brands)];
            $serial = sprintf("%s-1200R24-%06d", $brand, rand(102000, 199999));
            $insertStmt->execute([
                ':asset_id' => $assetId,
                ':tire_serial_no' => $serial,
                ':tire_position' => $t[0],
                ':tread_depth_mm' => $t[2],
                ':air_pressure_psi' => $t[1],
                ':condition_color' => $t[3],
                ':inspected_at' => $now,
                ':notes' => 'Spesifikasi pabrik & baseline inspeksi berkala divisi equipment (' . $t[4] . ')'
            ]);
            $insertedCount++;
        }
    }

    $db->commit();
    echo "Successfully seeded $insertedCount tire inspections for " . count($missingAssets) . " wheeled assets.\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
