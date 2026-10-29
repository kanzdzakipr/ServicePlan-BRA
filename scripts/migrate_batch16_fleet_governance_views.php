<?php
/**
 * scripts/migrate_batch16_fleet_governance_views.php
 *
 * Batch 16 Migration:
 * - Creates and maintains database views for operational scoping and audit:
 *     1. v_active_assets: Single Source of Truth for the 299 active assets in October 2026.
 *     2. v_archived_assets: Full audit trail for the 66 sold and demobilized assets.
 *     3. v_fleet_project_summary: Real-time aggregation per project, category, and operating status.
 * - Records migration in `app_data_migrations` under `BATCH_16_FLEET_GOVERNANCE_VIEWS`.
 */

declare(strict_types=1);

define('API_SKIP_ROUTING', true);
define('API_AUTH_OPTIONAL', true);
require_once dirname(__DIR__) . '/api/db.php';

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Starting Batch 16 Migration: Database Fleet Governance Views ===\n";

$sqlFile = dirname(__DIR__) . '/scripts/migrate_batch16_fleet_governance_views.sql';
if (!file_exists($sqlFile)) {
    throw new RuntimeException("SQL file not found: {$sqlFile}");
}

$sql = file_get_contents($sqlFile);

try {
    // Execute SQL script (DDL causes implicit commit in MySQL)
    $db->exec($sql);
    echo "[OK] Applied database views: v_active_assets, v_archived_assets, v_fleet_project_summary.\n";

    // Verify view counts
    $activeCount = (int)$db->query("SELECT COUNT(*) FROM v_active_assets")->fetchColumn();
    $archivedCount = (int)$db->query("SELECT COUNT(*) FROM v_archived_assets")->fetchColumn();
    echo "[VERIFIED] v_active_assets: {$activeCount} units, v_archived_assets: {$archivedCount} units.\n";

    // Register in app_data_migrations
    $stmtReg = $db->prepare("
        INSERT INTO app_data_migrations (migration_key, applied_at)
        VALUES ('BATCH_16_FLEET_GOVERNANCE_VIEWS', NOW())
        ON DUPLICATE KEY UPDATE applied_at = NOW()
    ");
    $stmtReg->execute();

    echo "[SUCCESS] Batch 16 completed: Fleet governance views deployed and active.\n";

} catch (Exception $e) {
    echo "[ERROR] Failed executing Batch 16: " . $e->getMessage() . "\n";
    exit(1);
}
