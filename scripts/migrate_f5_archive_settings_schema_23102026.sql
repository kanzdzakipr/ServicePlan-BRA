-- ============================================================================
-- Migration F5: Archive Audit & Operational Settings Schema (Additive & Idempotent)
-- Date: 2026-10-23
-- Target Tables: archived_items, archive_audit_trail, operational_settings
-- ============================================================================

-- 1. Helper Stored Procedure for Additive Columns
DROP PROCEDURE IF EXISTS add_f5_column_if_not_exists;
DELIMITER $$
CREATE PROCEDURE add_f5_column_if_not_exists(IN target_table VARCHAR(64), IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
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

-- 2. Ensure archived_items Table Exists (Moved DDL out of api/archive.php)
CREATE TABLE IF NOT EXISTS `archived_items` (
    `item_type` VARCHAR(50) NOT NULL,
    `item_id` VARCHAR(100) NOT NULL,
    `archived_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `archived_by_user_id` INT NULL,
    `archived_by_name` VARCHAR(150) NULL,
    `reason` VARCHAR(255) NULL,
    PRIMARY KEY (`item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL add_f5_column_if_not_exists('archived_items', 'archived_by_user_id', '`archived_by_user_id` INT NULL AFTER `archived_at`');
CALL add_f5_column_if_not_exists('archived_items', 'archived_by_name', '`archived_by_name` VARCHAR(150) NULL AFTER `archived_by_user_id`');
CALL add_f5_column_if_not_exists('archived_items', 'reason', '`reason` VARCHAR(255) NULL AFTER `archived_by_name`');

-- 3. Archive Audit Trail Table (Tracks every archive and restore transition)
CREATE TABLE IF NOT EXISTS `archive_audit_trail` (
    `audit_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_type` VARCHAR(50) NOT NULL,
    `item_id` VARCHAR(100) NOT NULL,
    `action` VARCHAR(20) NOT NULL, -- 'ARCHIVE' or 'RESTORE'
    `actor_user_id` INT NULL,
    `actor_name` VARCHAR(150) NULL,
    `reason` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_aat_item` (`item_type`, `item_id`),
    INDEX `idx_aat_actor` (`actor_user_id`),
    INDEX `idx_aat_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Operational Settings Table (Persistent & Validated)
CREATE TABLE IF NOT EXISTS `operational_settings` (
    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) NULL,
    `updated_by_user_id` INT NULL,
    `updated_by_name` VARCHAR(150) NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Seed Default Operational Settings (Idempotent)
INSERT IGNORE INTO `operational_settings` (`setting_key`, `setting_value`, `category`, `description`) VALUES
('cfg_sla_wo', '30', 'threshold', 'SLA Tiket WO dari Unit Down (Menit)'),
('cfg_tire_red', '3.2', 'threshold', 'Batas Merah Aus Ban / Tread Limit (mm)'),
('cfg_pm_warning', '50', 'threshold', 'Batas HM Warning PM Due Soon (HM)'),
('cfg_fuel_anomali', '15.0', 'threshold', 'Toleransi % Anomali Konsumsi BBM (%)'),
('cfg_idling_limit', '50.0', 'threshold', 'Telematika Komtrax Idling Alarm Limit (%)'),
('cfg_cuttingbit_safety', '380', 'threshold', 'Safety Stock Minimum Cutting Bit (Pieces)'),
('cfg_idle_timeout', '30', 'security', 'Idle Session Timeout (Menit)'),
('cfg_backup_frequency', 'Setiap Hari (00:00 WIB)', 'security', 'Frekuensi Automatic Backup Database'),
('cfg_wo_prefix', 'WO-BRA-YYYYMM-XXXX', 'template', 'Prefix Penomoran Work Order (WO)'),
('cfg_po_prefix', 'PO-BRA-YYYY-XXXX', 'template', 'Prefix Penomoran Purchase Order (PO)'),
('cfg_company_header', 'PT BINA REKAYASA ANUGERAH - Departemen Equipment & Maintenance', 'template', 'Nama Perusahaan & Header Kop Surat');

DROP PROCEDURE IF EXISTS add_f5_column_if_not_exists;
