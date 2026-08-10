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
    $assetScope = api_location_scope_clause('a', 'init_asset_location_id');
    $assetSql = "SELECT a.asset_id as id, a.type, a.category, a.status, a.raw_location_notes as location, a.last_hm_km FROM assets a";
    if ($assetScope['sql'] !== '') $assetSql .= " WHERE " . $assetScope['sql'];
    $stmtAssets = $db->prepare($assetSql);
    $stmtAssets->execute($assetScope['params']);
    $dbAssets = $stmtAssets->fetchAll();
    // Never fall back to the unscoped JSON asset list when this user has no accessible rows.
    $globalData['assets'] = $dbAssets;

    // Override Work Orders dari Database
    $workOrderScope = api_location_scope_clause('a', 'init_wo_location_id');
    $workOrderSql = "SELECT w.wo_id as woId, w.asset_id as assetId, w.issue_description as issue,
                            w.downtime_formatted as downtime, w.status, w.priority, w.assigned_mechanic as assignedTo
                     FROM work_orders w INNER JOIN assets a ON a.asset_id = w.asset_id";
    if ($workOrderScope['sql'] !== '') $workOrderSql .= " WHERE " . $workOrderScope['sql'];
    $stmtWO = $db->prepare($workOrderSql);
    $stmtWO->execute($workOrderScope['params']);
    $dbWO = $stmtWO->fetchAll();
    $globalData['work_orders'] = $dbWO;

    // Jika ingin override costs, dll bisa dilakukan di sini

    echo json_encode($globalData);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
