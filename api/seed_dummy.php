<?php
require_once 'db.php';

$db = Database::getInstance();

echo "Starting dummy data seeding...\n";

// 1. Seed Fuel Logs
$fuelLogs = [
    [
        'trxId' => 'BBM-20260727-0001',
        'unitId' => 'EXC-00001', // Changed to match schema
        'fuelman' => 'Fuelman A',
        'operator' => 'Budi Santoso',
        'meterPrev' => 8431,
        'meterCurr' => 8450,
        'liter' => 361,
        'rateActual' => 19.0,
        'targetRate' => 15.0,
        'anomalyStatus' => 1,
        'notes' => 'Beban kerja heavy excavation di pit utama'
    ],
    [
        'trxId' => 'BBM-20260726-0042',
        'unitId' => 'DT-00049 - B 9104 ZYT',
        'fuelman' => 'Fuelman B',
        'operator' => 'Rahmat Hidayat',
        'meterPrev' => 45200,
        'meterCurr' => 45760,
        'liter' => 200,
        'rateActual' => 2.8,
        'targetRate' => 3.5,
        'anomalyStatus' => 1,
        'notes' => 'Jalur hauling licin & tanjakan curam'
    ],
    [
        'trxId' => 'BBM-20260726-0038',
        'unitId' => 'MG-00004',
        'fuelman' => 'Fuelman A',
        'operator' => 'Supriadi',
        'meterPrev' => 3120,
        'meterCurr' => 3130,
        'liter' => 120,
        'rateActual' => 12.0,
        'targetRate' => 13.0,
        'anomalyStatus' => 0,
        'notes' => 'Normal'
    ]
];

$stmtFuel = $db->prepare("
    INSERT INTO fuel_logs (
        asset_id, refuel_date, flowmeter_start, flowmeter_end, 
        liters_issued, current_hm_km, calculated_lph, baseline_lph, 
        is_anomaly, driver_name
    ) VALUES (
        :asset_id, NOW(), :f_start, :f_end, 
        :liters, :hm_km, :lph, :baseline, 
        :anomaly, :driver
    )
");

foreach ($fuelLogs as $log) {
    try {
        $stmtFuel->execute([
            ':asset_id' => $log['unitId'],
            ':f_start' => $log['meterPrev'],
            ':f_end' => $log['meterCurr'],
            ':liters' => $log['liter'],
            ':hm_km' => $log['meterCurr'],
            ':lph' => $log['rateActual'],
            ':baseline' => $log['targetRate'],
            ':anomaly' => $log['anomalyStatus'],
            ':driver' => $log['operator']
        ]);
        echo "Seeded Fuel Log: " . $log['trxId'] . "\n";
    } catch (PDOException $e) {
        echo "Error Fuel Log (" . $log['trxId'] . "): " . $e->getMessage() . "\n";
    }
}

// 2. Seed Tire Inspections
$tires = [
    'FL' => ['tread' => 11.2, 'pressure' => 110],
    'FR' => ['tread' => 10.8, 'pressure' => 108],
    'R1L' => ['tread' => 9.5, 'pressure' => 112],
    'R1LI' => ['tread' => 8.2, 'pressure' => 105],
    'R1R' => ['tread' => 9.0, 'pressure' => 110]
];

$stmtTire = $db->prepare("
    INSERT INTO tire_inspections (
        asset_id, tire_position, tread_depth_mm, air_pressure_psi, condition_color
    ) VALUES (
        :asset_id, :position, :tread, :pressure, :color
    )
");

foreach ($tires as $pos => $data) {
    $color = 'GREEN';
    if ($data['tread'] < 3.2) $color = 'RED';
    else if ($data['tread'] <= 8.5) $color = 'YELLOW';
    
    try {
        $stmtTire->execute([
            ':asset_id' => 'DT-00049 - B 9104 ZYT',
            ':position' => $pos,
            ':tread' => $data['tread'],
            ':pressure' => $data['pressure'],
            ':color' => $color
        ]);
        echo "Seeded Tire Inspection: " . $pos . "\n";
    } catch (PDOException $e) {
        echo "Error Tire (" . $pos . "): " . $e->getMessage() . "\n";
    }
}

// 3. Seed PM Plans (We already have some PM plans in schema.sql, but we can add more if needed)
// Skipping PM Plans since schema.sql already seeded 4 rows.

echo "Seeding Complete!\n";
?>
