-- Section 1.6: extended Work Order fields and RFU Bersyarat metadata.
-- Idempotent for MySQL 8 / MariaDB; safe to run on every Laragon sync.

SET @schema_name = DATABASE();

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'hull_number'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `hull_number` VARCHAR(100) NULL AFTER `asset_id`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'equipment_type'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `equipment_type` VARCHAR(100) NULL AFTER `hull_number`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'current_hm_km'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `current_hm_km` DECIMAL(12,2) NULL AFTER `equipment_type`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'trouble_at'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `trouble_at` DATETIME NULL AFTER `current_hm_km`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'is_forced_operation'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `is_forced_operation` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_downtime`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'mitigation_action'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `mitigation_action` TEXT NULL AFTER `is_forced_operation`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'assigned_mechanics_json'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `assigned_mechanics_json` TEXT NULL AFTER `assigned_mechanic`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'estimated_completion_at'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `estimated_completion_at` DATETIME NULL AFTER `repair_started_at`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND INDEX_NAME = 'idx_wo_trouble_at'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD INDEX `idx_wo_trouble_at` (`trouble_at`)'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND INDEX_NAME = 'idx_wo_forced_operation'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD INDEX `idx_wo_forced_operation` (`is_forced_operation`, `status`)'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'maintenance_type'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `maintenance_type` ENUM(\'Corrective\', \'Preventive\', \'Inspection\', \'Emergency\') NOT NULL DEFAULT \'Corrective\' AFTER `priority`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'parts_requirement'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `parts_requirement` ENUM(\'UNASSESSED\', \'REQUIRED\', \'NOT_REQUIRED\') NOT NULL DEFAULT \'UNASSESSED\' AFTER `maintenance_type`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'parts_requirement_note'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `parts_requirement_note` TEXT NULL AFTER `parts_requirement`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'root_cause'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `root_cause` TEXT NULL AFTER `assigned_mechanics_json`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders' AND COLUMN_NAME = 'repair_action'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `repair_action` TEXT NULL AFTER `root_cause`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;


