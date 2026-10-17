-- Additive local migration: retain legacy alias_name without guessing or overwriting it.
SET @hull_column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'assets' AND column_name = 'hull_number');
SET @hull_ddl = IF(@hull_column_exists = 0, 'ALTER TABLE assets ADD COLUMN hull_number VARCHAR(100) NULL AFTER alias_name', 'SELECT 1');
PREPARE hull_stmt FROM @hull_ddl;
EXECUTE hull_stmt;
DEALLOCATE PREPARE hull_stmt;

CREATE TABLE IF NOT EXISTS asset_hull_history (
    history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(100) NOT NULL,
    old_hull_number VARCHAR(100) NULL,
    new_hull_number VARCHAR(100) NOT NULL,
    change_reason VARCHAR(500) NOT NULL,
    effective_date DATE NOT NULL,
    changed_by_user_id INT NULL,
    changed_by_name VARCHAR(150) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asset_hull_history_asset_time (asset_id, changed_at),
    CONSTRAINT fk_asset_hull_history_asset FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
