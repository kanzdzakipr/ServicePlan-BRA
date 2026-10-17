-- Additive indexes for prefix lookup. The bounded contains fallback remains available.
DROP PROCEDURE IF EXISTS add_asset_search_index;
DELIMITER $$
CREATE PROCEDURE add_asset_search_index(IN wanted_index VARCHAR(64), IN wanted_column VARCHAR(64))
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'assets' AND index_name = wanted_index) THEN
    SET @statement = CONCAT('ALTER TABLE assets ADD INDEX `', wanted_index, '` (`', wanted_column, '`, `asset_id`)');
    PREPARE statement FROM @statement;
    EXECUTE statement;
    DEALLOCATE PREPARE statement;
  END IF;
END$$
DELIMITER ;

CALL add_asset_search_index('idx_asset_search_hull', 'hull_number');
CALL add_asset_search_index('idx_asset_search_alias', 'alias_name');
CALL add_asset_search_index('idx_asset_search_serial', 'serial_number');
CALL add_asset_search_index('idx_asset_search_plate', 'license_plate');
DROP PROCEDURE add_asset_search_index;
