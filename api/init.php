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

    // Jika ingin override costs, dll bisa dilakukan di sini

    echo json_encode($globalData);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
