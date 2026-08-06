-- ============================================================================
-- SYSTEM INFORMASI EQUIPMENT PT BINA REKAYASA ANUGRAH (SERVICEPLAN-BRA)
-- COMPREHENSIVE PRODUCTION DATABASE SCHEMA & EXPANDED MASTER SEEDERS (DDL + DML)
-- Compatible with MySQL 8.0+ / MariaDB 10.4+
-- Extracted & Normalized from material/ and raw-material/ Markdown Repositories
-- ============================================================================



-- Disable foreign key checks for clean structure creation
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. MASTER LOCATIONS (Yard, Pit, Workshop, Branch, Site Area)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
    `location_id` INT AUTO_INCREMENT PRIMARY KEY,
    `location_name` VARCHAR(100) NOT NULL UNIQUE,
    `location_type` ENUM('Yard', 'Pit', 'Borrow Pit', 'Workshop', 'Branch', 'Site Area') DEFAULT 'Site Area',
    `region` VARCHAR(50) DEFAULT 'Riau / Pekanbaru',
    `latitude` DECIMAL(10,7) NULL,  -- Center Latitude (e.g. 1.2854300)
    `longitude` DECIMAL(10,7) NULL, -- Center Longitude (e.g. 101.2185400)
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. ROLES & USERS (RBAC Authentication Engine)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `role_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role_id` INT NOT NULL, -- Primary default role
    `assigned_location_id` INT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`),
    FOREIGN KEY (`assigned_location_id`) REFERENCES `locations`(`location_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.1 MULTI-USER ROLE ASSIGNMENTS (Many-to-Many Role Mapping)
DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
    `user_role_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `is_primary` BOOLEAN DEFAULT FALSE,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_user_role` (`user_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 GRANULAR PERMISSIONS & ROLE PERMISSIONS MATRIX
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
    `permission_id` INT AUTO_INCREMENT PRIMARY KEY,
    `permission_key` VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'costs.view_sensitive', 'accidents.release_hold'
    `menu_slug` VARCHAR(50) NOT NULL, -- e.g., 'costs', 'hse-accident', 'work-orders'
    `action_type` ENUM('READ', 'CREATE', 'UPDATE', 'APPROVE', 'OVERRIDE') NOT NULL,
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
    `role_permission_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`permission_id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_role_perm` (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. MASTER ASSETS (Fleet & Equipment Inventory with Real-Time Spatial Tracking)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
    `asset_id` VARCHAR(100) PRIMARY KEY, -- Full ID string e.g. 'DZ-00002 SN P6G01656', 'CS-41001'
    `asset_code` VARCHAR(50) NOT NULL,    -- Cleaned Code e.g. 'DZ-00002', 'CS-41001'
    `serial_number` VARCHAR(100) NULL,
    `license_plate` VARCHAR(50) NULL,
    `previous_license_plate` VARCHAR(50) NULL,
    `alias_name` VARCHAR(100) NULL,
    `type` VARCHAR(50) DEFAULT 'Heavy Equipment',
    `category` ENUM('Excavator', 'Bulldozer', 'Dump Truck', 'Motor Grader', 'Vibro Compactor', 'Water Truck', 'Prime Mover', 'Light Vehicle', 'Reclaimer Spreader', 'Trado', 'Other') NOT NULL,
    `make_model` VARCHAR(100) NULL,
    `sub_group_branch` VARCHAR(100) NULL, -- e.g., 'PKB PEKANBARU Branch', 'PLB PALEMBANG Branch'
    `year_manufacture` INT NULL,
    `ownership` ENUM('Milik Sendiri', 'Sewa', 'Leasing') DEFAULT 'Milik Sendiri',
    `status` ENUM('READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE') DEFAULT 'READY',
    `current_location_id` INT NULL,
    `raw_location_notes` TEXT NULL,       -- Preserves raw HTML notes
    `last_hm_km` DECIMAL(10,2) DEFAULT 0.00,
    `last_latitude` DECIMAL(10,7) NULL,   -- Real-time Individual Unit Spatial Latitude
    `last_longitude` DECIMAL(10,7) NULL,  -- Real-time Individual Unit Spatial Longitude
    `gps_updated_at` TIMESTAMP NULL,      -- Timestamp of last GPS ping
    `telematics_last_comm` TIMESTAMP NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`current_location_id`) REFERENCES `locations`(`location_id`) ON DELETE SET NULL,
    INDEX `idx_asset_status` (`status`),
    INDEX `idx_asset_category` (`category`),
    INDEX `idx_asset_code` (`asset_code`),
    INDEX `idx_asset_spatial` (`last_latitude`, `last_longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. ASSET MOVEMENTS & BAST (Handover & Location Mutation Log)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `asset_movements`;
CREATE TABLE `asset_movements` (
    `movement_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `from_location_id` INT NULL,
    `to_location_id` INT NOT NULL,
    `bast_number` VARCHAR(100) NULL,
    `movement_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT NULL,
    `requested_by` INT NULL,
    `approved_by` INT NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_location_id`) REFERENCES `locations`(`location_id`),
    FOREIGN KEY (`to_location_id`) REFERENCES `locations`(`location_id`),
    FOREIGN KEY (`requested_by`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. WORK ORDERS (WO - Maintenance & Repair Tickets)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `work_orders`;
CREATE TABLE `work_orders` (
    `wo_id` VARCHAR(50) PRIMARY KEY, -- e.g. 'WO-26-347'
    `asset_id` VARCHAR(100) NOT NULL,
    `location_id` INT NULL,
    `raw_location` VARCHAR(255) NULL,
    `issue_description` TEXT NOT NULL,
    `downtime_formatted` VARCHAR(100) NULL, -- Raw downtime string (e.g. '10 jam 15 menit')
    `downtime_minutes` INT DEFAULT 0,
    `is_downtime` BOOLEAN DEFAULT TRUE,
    `status` ENUM('Open', 'In Progress', 'Waiting Part', 'Testing', 'Closed', 'Cancelled') DEFAULT 'Open',
    `priority` ENUM('Normal', 'High', 'Emergency') DEFAULT 'Normal',
    `assigned_mechanic` VARCHAR(100) DEFAULT 'Belum ada PIC',
    `reported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `repair_started_at` TIMESTAMP NULL,
    `closed_at` TIMESTAMP NULL,
    `verification_supervisor_id` INT NULL,
    `before_photo_url` VARCHAR(255) NULL,
    `after_photo_url` VARCHAR(255) NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE,
    FOREIGN KEY (`location_id`) REFERENCES `locations`(`location_id`) ON DELETE SET NULL,
    FOREIGN KEY (`verification_supervisor_id`) REFERENCES `users`(`user_id`),
    INDEX `idx_wo_status` (`status`),
    INDEX `idx_wo_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. WO TIME LOGS (Mechanic Work Hours & SPL Log)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `wo_time_logs`;
CREATE TABLE `wo_time_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `wo_id` VARCHAR(50) NOT NULL,
    `mechanic_user_id` INT NOT NULL,
    `start_time` TIMESTAMP NOT NULL,
    `end_time` TIMESTAMP NULL,
    `normal_hours` DECIMAL(5,2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(5,2) DEFAULT 0.00,
    `spl_number` VARCHAR(50) NULL,
    `activity_description` TEXT NULL,
    `is_delayed_spareparts` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`wo_id`) REFERENCES `work_orders`(`wo_id`) ON DELETE CASCADE,
    FOREIGN KEY (`mechanic_user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. INSPECTIONS & P2H (Pre-start Checklist)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `inspections`;
CREATE TABLE `inspections` (
    `inspection_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `inspector_id` INT NOT NULL,
    `inspection_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `current_hm_km` DECIMAL(10,2) NOT NULL,
    `overall_result` ENUM('PASS', 'WARNING', 'FAIL') DEFAULT 'PASS',
    `findings_summary` TEXT NULL,
    `created_wo_id` VARCHAR(50) NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE,
    FOREIGN KEY (`inspector_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`created_wo_id`) REFERENCES `work_orders`(`wo_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. PREVENTIVE MAINTENANCE PLANS (PM Interval Tracking)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `pm_plans`;
CREATE TABLE `pm_plans` (
    `pm_plan_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `interval_hm` INT NOT NULL, -- 250, 500, 1000, 2000, 7500, 10000
    `current_smr` DECIMAL(10,2) DEFAULT 0.00,
    `last_service_hm` DECIMAL(10,2) DEFAULT 0.00,
    `last_service_date` DATE NULL,
    `target_due_hm` DECIMAL(10,2) NOT NULL,
    `variance_hm` DECIMAL(10,2) DEFAULT 0.00,
    `status` ENUM('PLANNED', 'DUE_SOON', 'OVERDUE', 'IN_PROGRESS', 'COMPLETED') DEFAULT 'PLANNED',
    `warranty_status` VARCHAR(100) DEFAULT 'No Warranty',
    `planner_note` TEXT NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. PARTS INVENTORY & SPB (Procurement & Spare Parts Request)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `parts`;
CREATE TABLE `parts` (
    `part_id` INT AUTO_INCREMENT PRIMARY KEY,
    `part_number` VARCHAR(100) NOT NULL UNIQUE,
    `part_name` VARCHAR(150) NOT NULL,
    `category` ENUM('Filter', 'Hose', 'Cutting Bit', 'Tire', 'Battery', 'Fast Moving', 'Engine Part', 'Hydraulic', 'Other') DEFAULT 'Other',
    `unit_measure` VARCHAR(20) DEFAULT 'Pcs',
    `stock_qty` INT DEFAULT 0,
    `min_stock_qty` INT DEFAULT 2,
    `unit_cost` DECIMAL(15,2) DEFAULT 0.00,
    `location_warehouse` VARCHAR(100) DEFAULT 'Gudang Yard KM 12'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_requests`;
CREATE TABLE `purchase_requests` (
    `spb_id` VARCHAR(50) PRIMARY KEY,
    `wo_id` VARCHAR(50) NOT NULL,
    `asset_id` VARCHAR(100) NULL,
    `requested_by` INT NOT NULL,
    `urgency` ENUM('Normal', 'Emergency') DEFAULT 'Normal',
    `status` ENUM('Draft', 'Submitted', 'Approved', 'Ordered', 'Issued', 'Closed') DEFAULT 'Submitted',
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`wo_id`) REFERENCES `work_orders`(`wo_id`) ON DELETE CASCADE,
    FOREIGN KEY (`requested_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. CONDITION MONITORING (Tire, Battery & Component Health)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `tire_inspections`;
CREATE TABLE `tire_inspections` (
    `tire_inspection_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `tire_serial_no` VARCHAR(100) NULL,
    `tire_position` VARCHAR(20) NOT NULL, -- e.g. 'FL', 'FR', 'R1L', 'R1R'
    `tread_depth_mm` DECIMAL(4,2) NOT NULL,
    `air_pressure_psi` INT NOT NULL,
    `condition_color` ENUM('GREEN', 'YELLOW', 'RED') NOT NULL,
    `inspected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `battery_logs`;
CREATE TABLE `battery_logs` (
    `battery_log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `battery_brand` VARCHAR(100) NULL,
    `voltage_val` DECIMAL(4,2) NOT NULL,
    `water_level` ENUM('GOOD', 'LOW', 'REFILL_REQUIRED') DEFAULT 'GOOD',
    `installation_date` DATE NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cutting_bit_logs`;
CREATE TABLE `cutting_bit_logs` (
    `bit_log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `bit_installed_qty` INT DEFAULT 0,
    `bit_broken_qty` INT DEFAULT 0,
    `replaced_date` DATE NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. FUEL LOGS (Refuel Transactions & LPH Efficiency)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `fuel_logs`;
CREATE TABLE `fuel_logs` (
    `fuel_log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `refuel_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `flowmeter_start` DECIMAL(10,2) NOT NULL,
    `flowmeter_end` DECIMAL(10,2) NOT NULL,
    `liters_issued` DECIMAL(10,2) NOT NULL,
    `current_hm_km` DECIMAL(10,2) NOT NULL,
    `calculated_lph` DECIMAL(6,2) NULL,
    `baseline_lph` DECIMAL(6,2) NULL,
    `is_anomaly` BOOLEAN DEFAULT FALSE,
    `driver_name` VARCHAR(100) NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. COST TRANSACTIONS & UNIT VALUATIONS
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cost_financial_monthly`;
CREATE TABLE `cost_financial_monthly` (
    `cost_id` INT AUTO_INCREMENT PRIMARY KEY,
    `month_label` VARCHAR(20) NOT NULL, -- 'Mei', 'Jun', 'Jul', etc.
    `year_period` INT NOT NULL DEFAULT 2026,
    `budget_amount` DECIMAL(15,2) NOT NULL,
    `actual_amount` DECIMAL(15,2) NOT NULL,
    `variance_amount` DECIMAL(15,2) GENERATED ALWAYS AS (`budget_amount` - `actual_amount`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `unit_valuations`;
CREATE TABLE `unit_valuations` (
    `valuation_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NULL,
    `unit_name` VARCHAR(150) NOT NULL,
    `unit_code_alias` VARCHAR(50) NULL,
    `total_repair_cost` DECIMAL(15,2) DEFAULT 0.00,
    `purchase_price` DECIMAL(15,2) DEFAULT 0.00,
    `book_value` DECIMAL(15,2) DEFAULT 0.00,
    `market_price_min` DECIMAL(15,2) DEFAULT 0.00,
    `market_price_max` DECIMAL(15,2) DEFAULT 0.00,
    `raw_market_price_str` VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. TELEMATICS SATELLITE LOGS (KOMTRAX Ingestion Engine & GPS Breadcrumbs)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `telematics_logs`;
CREATE TABLE `telematics_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NULL,
    `serial_number` VARCHAR(100) NOT NULL,
    `model_type` VARCHAR(100) NOT NULL,
    `sub_group` VARCHAR(100) NULL,
    `smr_hours` DECIMAL(10,2) DEFAULT 0.00,
    `working_days` INT DEFAULT 0,
    `working_hours` DECIMAL(10,2) DEFAULT 0.00,
    `actual_working_hours` DECIMAL(10,2) DEFAULT 0.00,
    `actual_working_ratio` DECIMAL(5,2) DEFAULT 0.00,
    `e_mode_ratio` DECIMAL(5,2) DEFAULT 0.00,
    `travel_ratio` DECIMAL(5,2) DEFAULT 0.00,
    `digging_ratio` DECIMAL(5,2) DEFAULT 0.00,
    `hoist_ratio` DECIMAL(5,2) DEFAULT 0.00,
    `fuel_consumed_liters` DECIMAL(10,2) DEFAULT 0.00,
    `fuel_lph` DECIMAL(5,2) DEFAULT 0.00,
    `idling_ratio` DECIMAL(5,2) DEFAULT 0.00,
    `last_comm_date` TIMESTAMP NULL,
    `period_month` VARCHAR(20) DEFAULT 'Januari 2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `telematics_gps_logs`;
CREATE TABLE `telematics_gps_logs` (
    `gps_log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `latitude` DECIMAL(10,7) NOT NULL,
    `longitude` DECIMAL(10,7) NOT NULL,
    `speed_kmh` DECIMAL(5,2) DEFAULT 0.00,
    `heading_deg` INT DEFAULT 0,
    `ignition_status` ENUM('ON', 'OFF', 'IDLING') DEFAULT 'OFF',
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE,
    INDEX `idx_gps_asset_time` (`asset_id`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 14. HEAD OF EQUIPMENT KPI ASSESSMENTS (KPI Scorecard Matrix)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `head_kpi_assessments`;
CREATE TABLE `head_kpi_assessments` (
    `kpi_id` INT AUTO_INCREMENT PRIMARY KEY,
    `period_month` VARCHAR(20) DEFAULT 'Januari',
    `period_year` INT DEFAULT 2026,
    `indicator_no` INT NOT NULL,
    `aspect_name` VARCHAR(100) NOT NULL,
    `indicator_name` VARCHAR(150) NOT NULL,
    `target_standard` VARCHAR(100) NOT NULL,
    `score_val` INT NOT NULL DEFAULT 1, -- Range 1 - 5
    `weight_pct` DECIMAL(5,2) NOT NULL,
    `weighted_score` DECIMAL(5,2) GENERATED ALWAYS AS ((`score_val` * `weight_pct`) / 5) STORED,
    `audit_notes` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 15. MAINTENANCE PLANNER EVALUATIONS (Competency Matrix)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `planner_evaluations`;
CREATE TABLE `planner_evaluations` (
    `eval_id` INT AUTO_INCREMENT PRIMARY KEY,
    `planner_user_id` INT NOT NULL,
    `competency_name` VARCHAR(150) NOT NULL,
    `job_standard` TEXT NOT NULL,
    `actual_evaluation` TEXT NOT NULL,
    `target_level` INT DEFAULT 4,
    `actual_level` INT DEFAULT 2,
    `gap_level` INT GENERATED ALWAYS AS (`actual_level` - `target_level`) STORED,
    `action_plan` TEXT NULL,
    FOREIGN KEY (`planner_user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 16. ACCIDENTS & CAPA (HSE Incident Management)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `accidents`;
CREATE TABLE `accidents` (
    `accident_id` VARCHAR(50) PRIMARY KEY,
    `asset_id` VARCHAR(100) NOT NULL,
    `report_date` DATE NOT NULL,
    `incident_datetime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `location_id` INT NULL,
    `operator_name` VARCHAR(100) NULL,
    `operator_tenure` VARCHAR(50) NULL,
    `chronology` TEXT NOT NULL,
    `weather` VARCHAR(50) NULL,
    `road_condition` VARCHAR(50) NULL,
    `lighting_condition` VARCHAR(50) NULL,
    `severity` ENUM('Minor', 'Moderate', 'Critical') DEFAULT 'Minor',
    `physical_damage` TEXT NULL,
    `estimated_repair_cost` DECIMAL(15,2) DEFAULT 0.00,
    `estimated_downtime_days` INT DEFAULT 0,
    `total_financial_loss` DECIMAL(15,2) DEFAULT 0.00,
    `cause_factors_json` JSON NULL,
    `corrective_action` TEXT NULL,
    `preventive_action` TEXT NULL,
    `is_unit_locked` BOOLEAN DEFAULT TRUE,
    `status` ENUM('Reported', 'Investigating', 'CAPA_Pending', 'Closed') DEFAULT 'Reported',
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE CASCADE,
    FOREIGN KEY (`location_id`) REFERENCES `locations`(`location_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 17. APPROVAL INBOX (Workflow Approval Engine)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `approvals`;
CREATE TABLE `approvals` (
    `approval_id` INT AUTO_INCREMENT PRIMARY KEY,
    `document_type` ENUM('SPB', 'WO', 'ACCIDENT_RELEASE', 'MUTASI', 'SPL') NOT NULL,
    `document_id` VARCHAR(50) NOT NULL,
    `approver_user_id` INT NOT NULL,
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    `rejection_reason` TEXT NULL,
    `action_at` TIMESTAMP NULL,
    FOREIGN KEY (`approver_user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 18. REPORT TEMPLATES (Versioned definitions for Laporan & Form)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `report_audit_logs`;
DROP TABLE IF EXISTS `report_items`;
DROP TABLE IF EXISTS `report_records`;
DROP TABLE IF EXISTS `report_templates`;
CREATE TABLE `report_templates` (
    `template_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_key` VARCHAR(100) NOT NULL,
    `code` VARCHAR(80) NOT NULL,
    `title` VARCHAR(190) NOT NULL,
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `schema_json` JSON NOT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_report_template_version` (`template_key`, `version`),
    KEY `idx_report_template_active` (`template_key`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 19. REPORT RECORDS (Draft, final, and void document header)
-- ----------------------------------------------------------------------------
CREATE TABLE `report_records` (
    `report_id` CHAR(36) PRIMARY KEY,
    `template_id` BIGINT UNSIGNED NOT NULL,
    `client_key` VARCHAR(64) NOT NULL,
    `report_number` VARCHAR(190) NULL,
    `status` ENUM('DRAFT', 'FINAL', 'VOID') NOT NULL DEFAULT 'DRAFT',
    `source_method` VARCHAR(40) NOT NULL DEFAULT 'manual',
    `field_data` JSON NOT NULL,
    `draft_data` JSON NULL,
    `standardized_payload` JSON NULL,
    `cloned_from_report_id` CHAR(36) NULL,
    `has_pending_attachments` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `finalized_at` TIMESTAMP NULL,
    `voided_at` TIMESTAMP NULL,
    `final_number_key` VARCHAR(320) NULL,
    UNIQUE KEY `uq_report_final_number` (`final_number_key`),
    KEY `idx_report_status_updated` (`status`, `updated_at`),
    KEY `idx_report_client_draft` (`client_key`, `status`, `template_id`),
    CONSTRAINT `fk_report_template` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`template_id`),
    CONSTRAINT `fk_report_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 20. REPORT ITEMS (Repeatable table rows)
-- ----------------------------------------------------------------------------
CREATE TABLE `report_items` (
    `item_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` CHAR(36) NOT NULL,
    `position` INT UNSIGNED NOT NULL,
    `item_data` JSON NOT NULL,
    UNIQUE KEY `uq_report_item_position` (`report_id`, `position`),
    CONSTRAINT `fk_report_item_record` FOREIGN KEY (`report_id`) REFERENCES `report_records` (`report_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 21. REPORT AUDIT LOG (Important lifecycle events)
-- ----------------------------------------------------------------------------
CREATE TABLE `report_audit_logs` (
    `audit_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` CHAR(36) NOT NULL,
    `client_key` VARCHAR(64) NOT NULL,
    `actor_id` INT NULL,
    `action` VARCHAR(40) NOT NULL,
    `payload_json` JSON NULL,
    `occurred_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_report_audit_record` (`report_id`, `occurred_at`),
    CONSTRAINT `fk_report_audit_record` FOREIGN KEY (`report_id`) REFERENCES `report_records` (`report_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_report_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- FULL EXTRACTED DATA SEEDERS (DML DATA INJECTION FROM MATERIAL REPOSITORY)
-- ============================================================================

-- 1. SEED ROLES
INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'Administrator', 'Full access to system configuration, users, and DB management'),
(2, 'Equipment Manager', 'Head of Equipment - Access to executive dashboard & WO approvals'),
(3, 'Maintenance Planner', 'Responsible for PM scheduling, WO creation, & parts kitting'),
(4, 'Mekanik Senior', 'Responsible for executing WO repair & timesheet logs'),
(5, 'Mekanik Junior / Helper', 'Assisting senior mechanics on daily repair jobs'),
(6, 'Welder / Fabrikator', 'Responsible for welding, body protection, & ombeng locks'),
(7, 'Inspector K3L / Safety', 'Responsible for P2H inspections & HSE accident reports'),
(8, 'Logistic Head', 'Responsible for spare parts inventory & SPB approvals'),
(9, 'HRD Manager', 'Responsible for personnel timesheet & SPL authorization'),
(10, 'Asset Manager', 'Responsible for asset valuation, mutation BAST, & release approval');

-- 2. SEED LOCATIONS (With Real Geographic Spatial Coordinates)
INSERT INTO `locations` (`location_id`, `location_name`, `location_type`, `region`, `latitude`, `longitude`) VALUES
(1, 'Yard Duri', 'Yard', 'Duri, Riau', 1.2854300, 101.2185400),
(2, 'Yard Prabumulih', 'Yard', 'Prabumulih, Sumsel', -3.4354200, 104.2384500),
(3, 'Borrow Pit Harapan Baru', 'Borrow Pit', 'Duri, Riau', 1.3124500, 101.2451200),
(4, 'Workshop Bay KM 12', 'Workshop', 'Duri, Riau', 1.2789000, 101.2112000),
(5, 'Site Sunter Area Stadium', 'Site Area', 'Jakarta / Sunter', -6.1451200, 106.8741500),
(6, 'Site Alpha Duri', 'Site Area', 'Duri, Riau', 1.2991000, 101.2311000),
(7, 'PKB PEKANBARU Branch', 'Branch', 'Pekanbaru, Riau', 0.5070680, 101.4477790),
(8, 'PLB PALEMBANG Branch', 'Branch', 'Palembang, Sumsel', -2.9760740, 104.7754310),
(9, 'MDN MEDAN Branch', 'Branch', 'Medan, Sumut', 3.5951960, 98.6722230),
(10, 'SMG SEMARANG Rep Office', 'Branch', 'Semarang, Jateng', -6.9666670, 110.4166640),
(11, 'Minas Field Project', 'Site Area', 'Minas, Riau', 0.7321400, 101.4421100);

-- 3. SEED USERS & TEAM PERSONNEL
INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `role_id`, `assigned_location_id`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Utama', 1, 4),
(2, 'dany_agung', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dany Agung (Head of Equipment)', 2, 4),
(3, 'martin_planner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'P. Martin (Maintenance Planner)', 3, 4),
(4, 'rahmad_k', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahmad K (Mekanik Senior)', 4, 4),
(5, 'urwatul_uska', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Urwatul Uska (Helper Mekanik)', 5, 4),
(6, 'joni_septian', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Joni Septian (Mekanik)', 4, 3),
(7, 'afriyandi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Afriyandi (Mekanik Senior)', 4, 4),
(8, 'darmawan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Darmawan (Mekanik)', 4, 4),
(9, 'hendrik', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hendrik (Teknisi Listrik / Welder)', 6, 4),
(10, 'rezeki_siregar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rezeki Siregar (Mekanik)', 4, 3),
(11, 'suwardi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Suwardi (Mekanik Welding)', 6, 4),
(12, 'taufiq_h', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Taufiq H (Security)', 7, 4),
(13, 'guswan_arizal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guswan Arizal (Logistic Head)', 8, 4),
(14, 'rani_simanungkalit', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rani Simanungkalit (HRD)', 9, 4),
(15, 'widya_apriani', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Widya Apriani (Asset Manager)', 10, 4),
(16, 'm_fajar_dc', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'M. Fajar DC (Inspector K3L)', 7, 6);

-- 3.1 SEED MULTI-USER ROLE ASSIGNMENTS (Junction Many-to-Many Mapping)
INSERT INTO `user_roles` (`user_id`, `role_id`, `is_primary`) VALUES
(1, 1, TRUE),  -- Admin -> Administrator
(1, 2, FALSE), -- Admin -> Equipment Manager (Secondary)
(2, 2, TRUE),  -- Dany Agung -> Equipment Manager
(2, 3, FALSE), -- Dany Agung -> Maintenance Planner (Secondary)
(3, 3, TRUE),  -- P. Martin -> Maintenance Planner
(4, 4, TRUE),  -- Rahmad K -> Mekanik Senior
(5, 5, TRUE),  -- Urwatul Uska -> Helper Mekanik
(6, 4, TRUE),  -- Joni -> Mekanik Senior
(7, 4, TRUE),  -- Afriyandi -> Mekanik Senior
(8, 4, TRUE),  -- Darmawan -> Mekanik Senior
(9, 6, TRUE),  -- Hendrik -> Welder / Fabrikator
(9, 4, FALSE), -- Hendrik -> Mekanik Senior (Secondary)
(10, 4, TRUE), -- Rezeki -> Mekanik
(11, 6, TRUE), -- Suwardi -> Welder / Fabrikator
(12, 7, TRUE), -- Taufiq H -> Security / Safety
(13, 8, TRUE), -- Guswan -> Logistic Head
(14, 9, TRUE), -- Rani -> HRD Manager
(15, 10, TRUE),-- Widya Apriani -> Asset Manager
(15, 2, FALSE),-- Widya Apriani -> Equipment Manager (Secondary)
(16, 7, TRUE); -- M. Fajar DC -> Inspector K3L

-- 4. SEED MASTER ASSETS (With Individual Real-Time Spatial Lat/Lng Coordinates)
INSERT INTO `assets` (`asset_id`, `asset_code`, `serial_number`, `license_plate`, `category`, `make_model`, `sub_group_branch`, `year_manufacture`, `status`, `current_location_id`, `last_hm_km`, `last_latitude`, `last_longitude`, `gps_updated_at`) VALUES
('DZ-00002 SN P6G01656', 'DZ-00002', 'P6G01656', NULL, 'Bulldozer', 'Caterpillar D6G 2XL', 'PKB PEKANBARU Branch', 2024, 'READY', 3, 2177.00, 1.3124500, 101.2451200, CURRENT_TIMESTAMP),
('DT-00049 - B 9104 ZYT', 'DT-00049', NULL, 'B 9104 ZYT', 'Dump Truck', 'Hino Ranger FM 280 JD', 'PKB PEKANBARU Branch', 2024, 'BREAKDOWN', 4, 34500.00, 1.2789000, 101.2112000, CURRENT_TIMESTAMP),
('EXC-00001', 'EXC-00001', 'C51502', NULL, 'Excavator', 'Komatsu PC200-10M0 CE', 'PKB PEKANBARU Branch', 2024, 'OPERATING', 6, 2443.00, 1.2991000, 101.2311000, CURRENT_TIMESTAMP),
('CS-41001', 'CS-41001', 'XK185-001', 'BM 9012 RWI', 'Other', 'Powder Binder Spreader XCMG XKC185', 'PKB PEKANBARU Branch', 2026, 'ACCIDENT_HOLD', 6, 150.00, 1.2991500, 101.2311500, CURRENT_TIMESTAMP),
('PF-00001', 'PF-00001', '961884301016', NULL, 'Vibro Compactor', 'Vibro Bomag Pad Foot BW 211D-40SL', 'PKB PEKANBARU Branch', 2024, 'INSPEKSI', 1, 1894.20, 1.2854300, 101.2185400, CURRENT_TIMESTAMP),
('MG-00004', 'MG-00004', 'GR135-99', NULL, 'Motor Grader', 'Motor Grader XCMG GR135 MAX', 'PKB PEKANBARU Branch', 2025, 'STANDBY', 1, 2446.10, 1.2855000, 101.2186000, CURRENT_TIMESTAMP),
('VIBRO BW BRA-01', 'BRA-01', '961582391008', NULL, 'Vibro Compactor', 'Bomag Smooth Drum BW211D-40 SL', 'PKB PEKANBARU Branch', 2020, 'STANDBY', 1, 6365.00, 1.2854100, 101.2185200, CURRENT_TIMESTAMP),
('DT-00031', 'DT-00031', NULL, 'BG8976IX', 'Dump Truck', 'Hino Ranger FM 260 JD', 'PKB PEKANBARU Branch', 2019, 'STANDBY', 1, 77131.00, 1.2854500, 101.2185600, CURRENT_TIMESTAMP),
('DT-00052', 'DT-00052', NULL, 'B 9642 KYW', 'Dump Truck', 'Hino Ranger FM 260 JD', 'PKB PEKANBARU Branch', 2018, 'STANDBY', 1, 38146.00, 1.2854600, 101.2185700, CURRENT_TIMESTAMP),
('SL-01', 'SL-01', 'BK 8143 XE', NULL, 'Trado', 'Mitsubishi FV 419 P', 'PLB PALEMBANG Branch', 2006, 'STANDBY', 2, 98230.00, -3.4354200, 104.2384500, CURRENT_TIMESTAMP);

-- 5. SEED WORK ORDERS (Extracted from WMJO History & data.json)
INSERT INTO `work_orders` (`wo_id`, `asset_id`, `location_id`, `issue_description`, `downtime_formatted`, `downtime_minutes`, `status`, `priority`, `assigned_mechanic`) VALUES
('WO-26-101', 'DT-00049 - B 9104 ZYT', 4, 'DISMANTLE DISC CLUTCH LIMIT & REPAIR TRANSMISSION', '8 jam 55 menit', 535, 'In Progress', 'High', 'Rahmad K, Suwardi'),
('WO-26-102', 'CS-41001', 6, 'OVERLOAD PENGISIAN CEMENT - SYSTEM ERROR MONITOR', '#########################', 0, 'Open', 'Emergency', 'Teknisi XCMG & M. Fajar DC'),
('WO-26-103', 'PF-00001', 1, 'INSPEKSI PM 500 HM & GANTI FILTER OLI', '2 jam 30 menit', 150, 'Closed', 'Normal', 'P. Martin');

-- 6. SEED PREVENTIVE MAINTENANCE PLANS (Plan Service Juli 2026)
INSERT INTO `pm_plans` (`asset_id`, `interval_hm`, `current_smr`, `last_service_hm`, `last_service_date`, `target_due_hm`, `variance_hm`, `status`, `warranty_status`, `planner_note`) VALUES
('PF-00001', 500, 1894.20, 1509.00, '2026-03-30', 2009.00, 114.80, 'COMPLETED', 'No Warranty', 'Realisasi 2036.4 HM pada 22 Juli 2026'),
('VIBRO BW BRA-01', 500, 6365.00, 6045.00, '2026-04-24', 6545.00, 180.00, 'COMPLETED', 'No Warranty', 'Realisasi 6532.2 HM pada 20 Juli 2026'),
('MG-00004', 500, 2446.10, 2051.30, '2026-04-27', 2551.30, 105.20, 'PLANNED', 'No Warranty', 'Target SMR 2551.3 HM'),
('EXC-00001', 500, 2443.00, 2001.00, '2026-04-11', 2501.00, 58.00, 'DUE_SOON', 'Warranty - 4000', 'Perlu kitting filter Komatsu UT');

-- 7. SEED SPARE PARTS INVENTORY
INSERT INTO `parts` (`part_number`, `part_name`, `category`, `unit_measure`, `stock_qty`, `min_stock_qty`, `unit_cost`, `location_warehouse`) VALUES
('P-001-OIL', 'Filter Oli Engine Komatsu PC200-10M0', 'Filter', 'Pcs', 25, 5, 250000.00, 'Gudang Yard KM 12'),
('P-002-HYD', 'Selang Hidrolik 3/4 Inch (2 Meter)', 'Hydraulic', 'Pcs', 12, 3, 750000.00, 'Gudang Yard KM 12'),
('P-003-BIT', 'Cutting Bit Grader XCMG GR135', 'Cutting Bit', 'Pcs', 60, 15, 350000.00, 'Gudang Yard KM 12'),
('P-004-AIR', 'Air Filter Primary Hino Ranger FM280', 'Filter', 'Pcs', 18, 5, 450000.00, 'Gudang Yard KM 12'),
('P-005-TIRE', 'Ban Radial 10.00-20 Dump Truck', 'Tire', 'Pcs', 8, 4, 3800000.00, 'Gudang Yard Duri');

-- 8. SEED TELEMATICS LOGS (KOMTRAX Jan 2026 Data)
INSERT INTO `telematics_logs` (`asset_id`, `serial_number`, `model_type`, `sub_group`, `smr_hours`, `working_days`, `working_hours`, `actual_working_hours`, `actual_working_ratio`, `e_mode_ratio`, `travel_ratio`, `digging_ratio`, `hoist_ratio`, `fuel_consumed_liters`, `fuel_lph`, `idling_ratio`, `period_month`) VALUES
('EXC-00001', 'C51502', 'PC200-10M0', 'PKB PEKANBARU Branch', 6357.90, 24, 182.00, 102.40, 56.30, 41.20, 10.50, 14.60, 8.90, 1960.40, 10.80, 43.70, 'Januari 2026'),
(NULL, 'C51503', 'PC200-10M0', 'PKB PEKANBARU Branch', 5626.20, 25, 187.70, 124.40, 66.30, 36.00, 9.40, 20.20, 12.90, 2911.00, 15.50, 33.70, 'Januari 2026'),
(NULL, 'DBCH2941', 'PC200-10M0', 'PKB PEKANBARU Branch', 836.10, 22, 159.90, 62.30, 39.00, 29.00, 15.10, 6.20, 3.50, 1258.80, 7.90, 61.00, 'Januari 2026');

-- 9. SEED HEAD OF EQUIPMENT KPI ASSESSMENTS (10 Indicators Template)
INSERT INTO `head_kpi_assessments` (`period_month`, `period_year`, `indicator_no`, `aspect_name`, `indicator_name`, `target_standard`, `score_val`, `weight_pct`, `audit_notes`) VALUES
('Januari', 2026, 1, 'RTW & Downtime', '% Unit Selesai ≤ Target RTW', '≥ 90%', 5, 15.00, 'Target RTW final disepakati & lulus test function'),
('Januari', 2026, 2, 'RTW & Downtime', 'Rata-rata Downtime per Unit', '≤ Standar', 4, 20.00, 'Dump Truck ≤ 5-7 hari; Tidak ada unit >7 hari / kronis'),
('Januari', 2026, 3, 'RTW & Downtime', 'Kepatuhan PM Tepat Waktu', '≥ 95%', 4, 10.00, 'PM on-time %; Breakdown akibat PM gagal NIHIL'),
('Januari', 2026, 4, 'Percepatan', 'Waktu Respon Awal Kerusakan', '≤ 24 jam', 3, 10.00, 'Respons awal troubleshooting & JO diterbitkan'),
('Januari', 2026, 5, 'Percepatan', 'Keterlambatan karena Spare Part', '≤ 10%', 1, 10.00, 'Penurunan kerugian downtime ≥30-40%/bln'),
('Januari', 2026, 6, 'Percepatan', 'Keterlambatan karena Manpower', '≤ 5%', 1, 5.00, 'Ketersediaan mekanik, welder, dan vendor'),
('Januari', 2026, 7, 'Biaya & Kualitas', 'Deviasi Biaya Corrective', '≤ 110%', 1, 10.00, 'Realisasi biaya vs budget rencana corrective'),
('Januari', 2026, 8, 'Biaya & Kualitas', 'Repeat Breakdown ≤ 30 Hari', '≤ 5%', 1, 10.00, 'Kerusakan berulang unit/komponen yang sama'),
('Januari', 2026, 9, 'Kepemimpinan', 'Monitoring & Pelaporan Unit', 'Konsisten', 1, 5.00, 'Disiplin update JO mekanik, target RTW, & report harian'),
('Januari', 2026, 10, 'Kepemimpinan', 'Inisiatif Percepatan Perbaikan', 'Aktif', 1, 5.00, 'Solusi percepatan (shift tambahan, vendor, prioritas)');

-- 10. SEED MAINTENANCE PLANNER COMPETENCY EVALUATION (P. Martin Matrix)
INSERT INTO `planner_evaluations` (`planner_user_id`, `competency_name`, `job_standard`, `actual_evaluation`, `target_level`, `actual_level`, `action_plan`) VALUES
(3, 'Dasar Maintenance Alat Berat', 'D3/S1 Teknik Mesin', 'D3 Akuntansi', 4, 2, 'Pelatihan Dasar Teknik Mesin Alat Berat & System Diagnosis'),
(3, 'PM Scheduling & Meter Reading', 'Update Real-Time Status KM/HM', 'Jadwal terbuat, Update KM tertinggal', 4, 3, 'Standardisasi Audit Meter Reading Harian Operator'),
(3, 'Estimasi Durasi Repair', 'Akurat per Komponen', 'Belum menguasai estimasi waktu', 4, 2, 'Praktik & Benchmark Standard Job Time per Kategori Breakdown'),
(3, 'Spare Parts Control & Kitting', 'Parts Ready sebelum Job Start', 'Memahami Logistik, Parts Kitting belum terikat JO', 4, 3, 'Integrasi Form SPB dengan Nomor WO/PM secara Wajib'),
(3, 'Administrasi & Keuangan KESDMAN', 'Tertib Administrasi', 'Menguasai Administrasi & Akuntansi', 4, 5, 'Dipertahankan sebagai keunggulan kontrol dokumen');

-- 11. SEED HSE ACCIDENTS
INSERT INTO `accidents` (`accident_id`, `asset_id`, `report_date`, `incident_datetime`, `location_id`, `operator_name`, `operator_tenure`, `chronology`, `weather`, `road_condition`, `lighting_condition`, `severity`, `physical_damage`, `estimated_repair_cost`, `estimated_downtime_days`, `total_financial_loss`, `corrective_action`, `preventive_action`, `is_unit_locked`, `status`) VALUES
('01/ACC/BRA/DURI/2026', 'CS-41001', '2026-05-14', '2026-05-11 14:30:00', 6, 'M. Fajar DC', '< 1 bulan (Unit Baru)', 'Pada tanggal 11 Mei 2026 pukul 14.30 WIB, unit sedang melakukan pengisian material powder binder/cement di area pengisian. Terjadi Error System Penaburan cement yang disebabkan pada saat pengisian cement operator tidak menyalakan tombol pada monitor sehingga sistem error.', 'Cerah', 'Kering / Rata', 'Siang Hari (Terang)', 'Moderate', 'Error System Penaburan & Valve Blockage', 15500000.00, 3, 28000000.00, 'Unit dihentikan operasional (ACCIDENT_HOLD). Sebagian cement dibongkar dan diisi ulang oleh teknisi XCMG.', 'Refresher training operator penabur semen, update SOP serah terima unit baru.', TRUE, 'CAPA_Pending');

-- 12. SEED MONTHLY FINANCIAL COSTS
INSERT INTO `cost_financial_monthly` (`month_label`, `year_period`, `budget_amount`, `actual_amount`) VALUES
('Mei', 2026, 450000000.00, 420000000.00),
('Jun', 2026, 480000000.00, 510000000.00),
('Jul', 2026, 500000000.00, 490000000.00),
('Agu', 2026, 520000000.00, 470000000.00);

-- 13. SEED UNIT VALUATIONS
INSERT INTO `unit_valuations` (`asset_id`, `unit_name`, `unit_code_alias`, `total_repair_cost`, `purchase_price`, `book_value`, `market_price_min`, `market_price_max`, `raw_market_price_str`) VALUES
('DZ-00002 SN P6G01656', 'Bulldozer Caterpillar D6G 2XL', 'DZ-00002', 421415277.00, 1200000000.00, 650000000.00, 450000000.00, 520000000.00, '450.000.000 – 520.000.000'),
('DT-00049 - B 9104 ZYT', 'Dump Truck Hino Ranger FM 280 JD', 'DT-00049', 185000000.00, 850000000.00, 480000000.00, 400000000.00, 580000000.00, '400.000.000 – 580.000.000');

-- End of schema.sql Script
