-- F3c: persistent and auditable HSE accident hold/release state.
-- Additive and idempotent; existing accident data is preserved.

DROP PROCEDURE IF EXISTS add_f3c_accident_column;
DELIMITER $$
CREATE PROCEDURE add_f3c_accident_column(IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'accidents'
      AND column_name = col_name
  ) THEN
    SET @statement = CONCAT('ALTER TABLE accidents ADD COLUMN ', column_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL add_f3c_accident_column('asset_status_before_hold', 'asset_status_before_hold VARCHAR(30) NULL AFTER is_unit_locked');
CALL add_f3c_accident_column('locked_at', 'locked_at DATETIME NULL AFTER asset_status_before_hold');
CALL add_f3c_accident_column('released_at', 'released_at DATETIME NULL AFTER locked_at');
CALL add_f3c_accident_column('released_by', 'released_by INT NULL AFTER released_at');
CALL add_f3c_accident_column('release_reason', 'release_reason VARCHAR(500) NULL AFTER released_by');

DROP PROCEDURE add_f3c_accident_column;

SET @f3c_index_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'accidents'
      AND index_name = 'idx_accident_asset_lock'
  ),
  'SELECT 1',
  'CREATE INDEX idx_accident_asset_lock ON accidents (asset_id, is_unit_locked, released_at)'
);
PREPARE f3c_index_stmt FROM @f3c_index_sql;
EXECUTE f3c_index_stmt;
DEALLOCATE PREPARE f3c_index_stmt;
