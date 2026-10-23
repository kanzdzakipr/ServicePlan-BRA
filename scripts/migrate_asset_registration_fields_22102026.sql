-- Additive fields for the Master Asset registration form.
DROP PROCEDURE IF EXISTS add_asset_registration_column;
DELIMITER $$
CREATE PROCEDURE add_asset_registration_column(IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
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

CALL add_asset_registration_column('dealer', 'dealer VARCHAR(150) NULL AFTER chassis_number');
CALL add_asset_registration_column('project_name', 'project_name VARCHAR(150) NULL AFTER dealer');
CALL add_asset_registration_column('operational_position', 'operational_position VARCHAR(150) NULL AFTER project_name');
CALL add_asset_registration_column('invoice_location', 'invoice_location VARCHAR(150) NULL AFTER operational_position');
CALL add_asset_registration_column('stnk_location', 'stnk_location VARCHAR(150) NULL AFTER invoice_location');
CALL add_asset_registration_column('bpkb_location', 'bpkb_location VARCHAR(150) NULL AFTER stnk_location');
CALL add_asset_registration_column('vehicle_type', 'vehicle_type VARCHAR(100) NULL AFTER bpkb_location');
CALL add_asset_registration_column('asset_age', 'asset_age SMALLINT UNSIGNED NULL AFTER vehicle_type');
CALL add_asset_registration_column('capacity', 'capacity VARCHAR(100) NULL AFTER asset_age');

DROP PROCEDURE add_asset_registration_column;
