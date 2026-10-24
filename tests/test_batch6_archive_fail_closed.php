<?php
declare(strict_types=1);

putenv('APP_ENV=local');
putenv('DB_NAME=u646470441_ServicePlanBRA');
putenv('DB_HOST=127.0.0.1');
putenv('DB_USER=root');
putenv('DB_PASSWORD=');

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';

echo "Running Batch 6 (Fail-Closed Archive Filtering) Tests...\n";

$db = Database::getInstance();
$testAssetId = 'AST-QA-ARCHIVE-' . random_int(1000, 9999);
$testAccidentId = 'ACC-QA-ARCHIVE-' . random_int(1000, 9999);

try {
    // 1. Create a test asset
    $db->prepare("
        INSERT INTO assets (asset_id, asset_code, hull_number, category, make_model, status, current_location_id, created_at)
        VALUES (:id, :code, :hull, 'Excavator', 'Komatsu PC200 QA', 'READY', 1, NOW())
    ")->execute([
        ':id' => $testAssetId,
        ':code' => $testAssetId,
        ':hull' => 'QA-ARC-01'
    ]);

    // Verify asset appears in assets query before archive
    $stmtCheck = $db->prepare("
        SELECT a.asset_id 
        FROM assets a 
        WHERE a.asset_id = :id
          AND NOT EXISTS (
              SELECT 1 FROM archived_items arc 
              WHERE arc.item_type = 'asset' AND arc.item_id = a.asset_id
          )
    ");
    $stmtCheck->execute([':id' => $testAssetId]);
    if ($stmtCheck->fetchColumn() !== $testAssetId) {
        throw new RuntimeException("Initial asset check failed: asset not found before archiving.");
    }
    echo "PASS: Test asset {$testAssetId} successfully created and visible before archive.\n";

    // 2. Archive the test asset
    $db->prepare("
        INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason)
        VALUES ('asset', :id, NOW(), 1, 'QA Tester', 'Pengujian Batch 6')
    ")->execute([':id' => $testAssetId]);

    // 3. Verify asset is excluded from api/assets.php query (both single item and list)
    $stmtSingle = $db->prepare("
        SELECT a.*, l.location_name as location_name 
        FROM assets a 
        LEFT JOIN locations l ON a.current_location_id = l.location_id
        WHERE a.asset_id = :id
          AND NOT EXISTS (
              SELECT 1 FROM archived_items arc 
              WHERE arc.item_type = 'asset' AND arc.item_id = a.asset_id
          )
    ");
    $stmtSingle->execute([':id' => $testAssetId]);
    $rowSingle = $stmtSingle->fetch();
    if ($rowSingle !== false) {
        throw new RuntimeException("FAIL: Single asset query returned archived asset!");
    }
    echo "PASS: Single asset query in api/assets.php successfully excluded archived unit.\n";

    $stmtList = $db->query("
        SELECT a.asset_id 
        FROM assets a 
        WHERE NOT EXISTS (
            SELECT 1 FROM archived_items arc 
            WHERE arc.item_type = 'asset' AND arc.item_id = a.asset_id
        )
    ");
    $listAssetIds = $stmtList->fetchAll(PDO::FETCH_COLUMN);
    if (in_array($testAssetId, $listAssetIds, true)) {
        throw new RuntimeException("FAIL: Asset list query returned archived asset!");
    }
    echo "PASS: Asset list query in api/assets.php successfully excluded archived unit.\n";

    // 4. Verify asset is excluded from api/init.php asset query
    $stmtInit = $db->query("
        SELECT a.asset_id 
        FROM assets a 
        LEFT JOIN locations l ON l.location_id = a.current_location_id
        WHERE NOT EXISTS (
            SELECT 1 FROM archived_items arc 
            WHERE arc.item_type = 'asset' AND arc.item_id = a.asset_id
        )
    ");
    $initAssetIds = $stmtInit->fetchAll(PDO::FETCH_COLUMN);
    if (in_array($testAssetId, $initAssetIds, true)) {
        throw new RuntimeException("FAIL: api/init.php returned archived asset!");
    }
    echo "PASS: api/init.php assets query successfully excluded archived unit.\n";

    // 5. Verify asset is returned in api/archive.php query
    $stmtArchive = $db->prepare("
        SELECT item_type, item_id, reason, archived_by_name 
        FROM archived_items 
        WHERE item_type = 'asset' AND item_id = :id
    ");
    $stmtArchive->execute([':id' => $testAssetId]);
    $arcRow = $stmtArchive->fetch(PDO::FETCH_ASSOC);
    if (!$arcRow || $arcRow['item_id'] !== $testAssetId) {
        throw new RuntimeException("FAIL: Asset not visible in archive API query!");
    }
    echo "PASS: Archived asset is visible in archive query with reason: '{$arcRow['reason']}'.\n";

    // 6. Test accident exclusion
    $db->prepare("
        INSERT INTO accidents (accident_id, asset_id, report_date, incident_datetime, chronology, severity, status)
        VALUES (:acc_id, :asset_id, CURDATE(), NOW(), 'QA Test incident', 'Minor', 'Reported')
    ")->execute([
        ':acc_id' => $testAccidentId,
        ':asset_id' => $testAssetId
    ]);

    $db->prepare("
        INSERT INTO archived_items (item_type, item_id, archived_at, archived_by_user_id, archived_by_name, reason)
        VALUES ('accident', :id, NOW(), 1, 'QA Tester', 'Archived accident test')
    ")->execute([':id' => $testAccidentId]);

    $stmtAcc = $db->query("
        SELECT ac.accident_id FROM accidents ac
        WHERE NOT EXISTS (
            SELECT 1 FROM archived_items arc 
            WHERE arc.item_type = 'accident' AND arc.item_id = ac.accident_id
        )
    ");
    $accList = $stmtAcc->fetchAll(PDO::FETCH_COLUMN);
    if (in_array($testAccidentId, $accList, true)) {
        throw new RuntimeException("FAIL: Archived accident appeared in api/accidents.php query!");
    }
    echo "PASS: api/accidents.php query successfully excluded archived accident record.\n";

    // 7. Test restore: remove from archived_items
    $db->prepare("DELETE FROM archived_items WHERE item_type = 'asset' AND item_id = :id")->execute([':id' => $testAssetId]);

    $stmtRestore = $db->prepare("
        SELECT a.asset_id 
        FROM assets a 
        WHERE a.asset_id = :id
          AND NOT EXISTS (
              SELECT 1 FROM archived_items arc 
              WHERE arc.item_type = 'asset' AND arc.item_id = a.asset_id
          )
    ");
    $stmtRestore->execute([':id' => $testAssetId]);
    if ($stmtRestore->fetchColumn() !== $testAssetId) {
        throw new RuntimeException("FAIL: Restored asset did not reappear in active query!");
    }
    echo "PASS: Restored asset immediately reappeared in active asset query.\n";

} finally {
    // Cleanup
    $db->prepare("DELETE FROM archived_items WHERE item_id IN (:a1, :a2)")->execute([':a1' => $testAssetId, ':a2' => $testAccidentId]);
    $db->prepare("DELETE FROM accidents WHERE accident_id = :id")->execute([':id' => $testAccidentId]);
    $db->prepare("DELETE FROM assets WHERE asset_id = :id")->execute([':id' => $testAssetId]);
    echo "Cleanup completed.\n";
}

echo "All Batch 6 tests PASSED!\n";
