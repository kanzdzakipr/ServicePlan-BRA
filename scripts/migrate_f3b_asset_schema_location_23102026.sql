-- F3b: keep the Master Asset API schema compatible and repair the known
-- WUR EW location reference without reseeding or overwriting asset data.
-- This migration is intentionally idempotent.

DROP PROCEDURE IF EXISTS add_f3b_asset_column;
DELIMITER $$
CREATE PROCEDURE add_f3b_asset_column(IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'assets'
      AND column_name = col_name
  ) THEN
    SET @statement = CONCAT('ALTER TABLE assets ADD COLUMN ', column_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL add_f3b_asset_column('warranty', 'warranty VARCHAR(100) DEFAULT NULL AFTER make_model');
CALL add_f3b_asset_column('chassis_number', 'chassis_number VARCHAR(100) DEFAULT NULL AFTER warranty');
CALL add_f3b_asset_column('dealer', 'dealer VARCHAR(150) DEFAULT NULL AFTER chassis_number');
CALL add_f3b_asset_column('project_name', 'project_name VARCHAR(150) DEFAULT NULL AFTER dealer');
CALL add_f3b_asset_column('operational_position', 'operational_position VARCHAR(150) DEFAULT NULL AFTER project_name');
CALL add_f3b_asset_column('invoice_location', 'invoice_location VARCHAR(150) DEFAULT NULL AFTER operational_position');
CALL add_f3b_asset_column('stnk_location', 'stnk_location VARCHAR(150) DEFAULT NULL AFTER invoice_location');
CALL add_f3b_asset_column('bpkb_location', 'bpkb_location VARCHAR(150) DEFAULT NULL AFTER stnk_location');
CALL add_f3b_asset_column('vehicle_type', 'vehicle_type VARCHAR(100) DEFAULT NULL AFTER bpkb_location');
CALL add_f3b_asset_column('asset_age', 'asset_age SMALLINT UNSIGNED DEFAULT NULL AFTER vehicle_type');
CALL add_f3b_asset_column('capacity', 'capacity VARCHAR(100) DEFAULT NULL AFTER asset_age');

DROP PROCEDURE add_f3b_asset_column;

-- Location 15 is referenced by the imported WUR EW asset set. Only repair it
-- when the ID is absent and those references can be proven from raw notes.
INSERT INTO locations (
  location_id, location_name, location_type, region, is_active
)
SELECT
  15, 'WUR EW Project', 'Site Area', 'Duri, Riau', 1
WHERE NOT EXISTS (
  SELECT 1 FROM locations WHERE location_id = 15
)
AND NOT EXISTS (
  SELECT 1 FROM locations WHERE location_name = 'WUR EW Project'
)
AND EXISTS (
  SELECT 1
  FROM assets
  WHERE current_location_id = 15
    AND UPPER(COALESCE(raw_location_notes, '')) LIKE 'WUR EW%'
);

-- If the canonical WUR EW location already exists under a different ID,
-- reconnect only the same proven orphan references instead of duplicating it.
UPDATE assets a
JOIN locations canonical
  ON canonical.location_name = 'WUR EW Project'
LEFT JOIN locations current_location
  ON current_location.location_id = a.current_location_id
SET a.current_location_id = canonical.location_id
WHERE a.current_location_id = 15
  AND current_location.location_id IS NULL
  AND UPPER(COALESCE(a.raw_location_notes, '')) LIKE 'WUR EW%';
