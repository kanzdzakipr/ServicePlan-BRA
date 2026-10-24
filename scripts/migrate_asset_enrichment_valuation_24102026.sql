-- Additive schema support for the 24 October 2026 asset enrichment batches.
-- Safe to run repeatedly; existing values and rows are never replaced here.

DROP PROCEDURE IF EXISTS add_asset_enrichment_column;
DELIMITER $$
CREATE PROCEDURE add_asset_enrichment_column(
  IN target_table VARCHAR(64),
  IN col_name VARCHAR(64),
  IN column_ddl VARCHAR(500)
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = target_table
      AND column_name = col_name
  ) THEN
    SET @statement = CONCAT('ALTER TABLE `', target_table, '` ADD COLUMN ', column_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL add_asset_enrichment_column(
  'assets',
  'standby_action_plan',
  '`standby_action_plan` VARCHAR(255) NULL AFTER `operational_position`'
);
CALL add_asset_enrichment_column(
  'assets',
  'standby_target_date',
  '`standby_target_date` DATE NULL AFTER `standby_action_plan`'
);
CALL add_asset_enrichment_column(
  'assets',
  'standby_start_date',
  '`standby_start_date` DATE NULL AFTER `standby_target_date`'
);
CALL add_asset_enrichment_column(
  'unit_valuations',
  'monthly_rental_rate',
  '`monthly_rental_rate` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `book_value`'
);
CALL add_asset_enrichment_column(
  'unit_valuations',
  'monthly_leasing_installment',
  '`monthly_leasing_installment` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `monthly_rental_rate`'
);

DROP PROCEDURE add_asset_enrichment_column;

DROP PROCEDURE IF EXISTS add_asset_enrichment_index;
DELIMITER $$
CREATE PROCEDURE add_asset_enrichment_index(
  IN target_table VARCHAR(64),
  IN target_index_name VARCHAR(64),
  IN index_ddl VARCHAR(500)
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = target_table
      AND index_name = target_index_name
  ) THEN
    SET @statement = CONCAT('ALTER TABLE `', target_table, '` ADD ', index_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL add_asset_enrichment_index(
  'unit_valuations',
  'idx_unit_valuations_asset_id',
  'INDEX `idx_unit_valuations_asset_id` (`asset_id`)'
);

DROP PROCEDURE add_asset_enrichment_index;

