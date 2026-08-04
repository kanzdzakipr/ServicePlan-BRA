<?php
require_once 'db.php';
$db = Database::getInstance();

try {
    // Ambil data dasar dari data.json (sebagai fallback untuk struktur kompleks seperti costs dll)
    $jsonPath = __DIR__ . '/../data.json';
    $globalData = [];
    if (file_exists($jsonPath)) {
        $globalData = json_decode(file_get_contents($jsonPath), true);
    }

    // Override Assets dari Database
    $stmtAssets = $db->query("SELECT asset_id as id, type, category, status, raw_location_notes as location, last_hm_km FROM assets");
    $dbAssets = $stmtAssets->fetchAll();
    if (!empty($dbAssets)) {
        $globalData['assets'] = $dbAssets;
    }

    // Override Work Orders dari Database
    $stmtWO = $db->query("SELECT wo_id as woId, asset_id as assetId, issue_description as issue, downtime_formatted as downtime, status, priority, assigned_mechanic as assignedTo FROM work_orders");
    $dbWO = $stmtWO->fetchAll();
    if (!empty($dbWO)) {
        $globalData['work_orders'] = $dbWO;
    }

    // Override Inspections (P2H) dari Database
    try {
        $globalData['inspections'] = []; // Inisialisasi awal untuk mencegah fallback dummy data
        $stmtP2H = $db->query("SELECT raw_data FROM p2h_records ORDER BY date DESC");
        $dbP2H = $stmtP2H->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($dbP2H)) {
            $globalData['inspections'] = array_map(function($row) {
                return json_decode($row['raw_data'], true);
            }, $dbP2H);
        }
    } catch (Exception $e) {
        // Abaikan jika tabel p2h_records belum ada
    }

    echo json_encode($globalData);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
