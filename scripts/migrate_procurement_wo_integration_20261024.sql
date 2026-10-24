-- ============================================================================
-- Migration: Procurement, Inventory & Work Order Integration Schema
-- Date: 2026-10-24
-- Purpose:
--   1. Allow SPB (purchase_requests) and PPB (purchase_orders) to have nullable wo_id
--      to support Normal Preventive Maintenance (SPB created before WO exists).
--   2. Add spb_type ENUM('NORMAL', 'EMERGENCY') to purchase_requests.
--   3. Add nullable asset_id to purchase_orders for bulk/unassigned stock orders.
--   4. Add nullable wo_id with index to spl_overtime_records for emergency WO overtime tracking.
-- ============================================================================

DROP PROCEDURE IF EXISTS add_procurement_column;
DELIMITER $$
CREATE PROCEDURE add_procurement_column(IN target_table VARCHAR(64), IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
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

DROP PROCEDURE IF EXISTS make_column_nullable;
DELIMITER $$
CREATE PROCEDURE make_column_nullable(IN target_table VARCHAR(64), IN col_name VARCHAR(64), IN modify_ddl VARCHAR(500))
BEGIN
  IF EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = target_table
      AND column_name = col_name
      AND is_nullable = 'NO'
  ) THEN
    SET @statement = CONCAT('ALTER TABLE `', target_table, '` MODIFY COLUMN ', modify_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS add_procurement_index;
DELIMITER $$
CREATE PROCEDURE add_procurement_index(IN target_table VARCHAR(64), IN idx_name VARCHAR(64), IN index_ddl VARCHAR(500))
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = target_table
      AND index_name = idx_name
  ) THEN
    SET @statement = CONCAT('ALTER TABLE `', target_table, '` ADD INDEX `', idx_name, '` ', index_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

-- 1. purchase_requests adjustments
CALL make_column_nullable('purchase_requests', 'wo_id', '`wo_id` VARCHAR(50) NULL');
CALL add_procurement_column('purchase_requests', 'spb_type', '`spb_type` ENUM("NORMAL", "EMERGENCY") NOT NULL DEFAULT "NORMAL" AFTER `urgency`');
UPDATE `purchase_requests` SET `spb_type` = 'EMERGENCY' WHERE `urgency` = 'Emergency' AND `spb_type` = 'NORMAL';

-- 2. purchase_orders adjustments
CALL make_column_nullable('purchase_orders', 'wo_id', '`wo_id` VARCHAR(50) NULL');
CALL make_column_nullable('purchase_orders', 'asset_id', '`asset_id` VARCHAR(100) NULL');

-- 3. spl_overtime_records adjustments
CALL add_procurement_column('spl_overtime_records', 'wo_id', '`wo_id` VARCHAR(50) NULL AFTER `spl_number`');
CALL add_procurement_index('spl_overtime_records', 'idx_spl_overtime_wo_id', '(`wo_id`)');

-- Cleanup temporary helper procedures
DROP PROCEDURE IF EXISTS add_procurement_column;
DROP PROCEDURE IF EXISTS make_column_nullable;
DROP PROCEDURE IF EXISTS add_procurement_index;
