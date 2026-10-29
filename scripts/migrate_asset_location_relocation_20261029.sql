-- ============================================================================
-- Migration: Asset Location Relocation & History Indexing
-- Date: 2026-10-29
-- Ensures asset_movements table and locations indexes exist for fast relocation logging
-- ============================================================================

CREATE TABLE IF NOT EXISTS `asset_movements` (
    `movement_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `from_location_id` INT NULL,
    `to_location_id` INT NOT NULL,
    `bast_number` VARCHAR(100) NULL,
    `movement_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT NULL,
    `requested_by` INT NULL,
    `approved_by` INT NULL,
    CONSTRAINT `fk_asset_movement_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_asset_movement_from_loc` FOREIGN KEY (`from_location_id`) REFERENCES `locations`(`location_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_asset_movement_to_loc` FOREIGN KEY (`to_location_id`) REFERENCES `locations`(`location_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_asset_movement_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_asset_movement_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure indexes exist even if table was pre-existing
DROP PROCEDURE IF EXISTS add_asset_movement_index;
DELIMITER $$
CREATE PROCEDURE add_asset_movement_index(IN wanted_index VARCHAR(64), IN wanted_columns VARCHAR(128))
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'asset_movements' AND index_name = wanted_index) THEN
    SET @statement = CONCAT('ALTER TABLE asset_movements ADD INDEX `', wanted_index, '` (', wanted_columns, ')');
    PREPARE statement FROM @statement;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END IF;
END$$
DELIMITER ;

CALL add_asset_movement_index('idx_asset_movement_asset_date', '`asset_id`, `movement_date`');
CALL add_asset_movement_index('idx_asset_movement_from_loc', '`from_location_id`');
CALL add_asset_movement_index('idx_asset_movement_to_loc', '`to_location_id`');
DROP PROCEDURE add_asset_movement_index;
