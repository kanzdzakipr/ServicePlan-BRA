-- ============================================================================
-- Migration F4: People & KPI Official Lifecycle Schema (Additive & Idempotent)
-- Date: 2026-10-23
-- Target Tables: head_kpi_assessments, planner_evaluations, spl_planner_reviews
-- ============================================================================

DROP PROCEDURE IF EXISTS add_f4_people_kpi_column;
DELIMITER $$
CREATE PROCEDURE add_f4_people_kpi_column(IN target_table VARCHAR(64), IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
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

-- 1. Columns for head_kpi_assessments
CALL add_f4_people_kpi_column('head_kpi_assessments', 'period_key', '`period_key` VARCHAR(10) DEFAULT "2026-01" AFTER `period_year`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'location_id', '`location_id` INT NULL AFTER `period_key`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'assessor_user_id', '`assessor_user_id` INT NULL AFTER `location_id`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'evaluated_user_id', '`evaluated_user_id` INT NULL AFTER `assessor_user_id`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'status', '`status` VARCHAR(30) NOT NULL DEFAULT "DRAFT" AFTER `audit_notes`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'version', '`version` INT NOT NULL DEFAULT 1 AFTER `status`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'submitted_at', '`submitted_at` DATETIME NULL AFTER `version`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'approved_by', '`approved_by` INT NULL AFTER `submitted_at`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'approved_at', '`approved_at` DATETIME NULL AFTER `approved_by`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'approval_notes', '`approval_notes` TEXT NULL AFTER `approved_at`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'created_at', '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `approval_notes`');
CALL add_f4_people_kpi_column('head_kpi_assessments', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

-- 2. Columns for planner_evaluations
CALL add_f4_people_kpi_column('planner_evaluations', 'period_key', '`period_key` VARCHAR(10) DEFAULT "2026-01" AFTER `planner_user_id`');
CALL add_f4_people_kpi_column('planner_evaluations', 'location_id', '`location_id` INT NULL AFTER `period_key`');
CALL add_f4_people_kpi_column('planner_evaluations', 'evaluator_user_id', '`evaluator_user_id` INT NULL AFTER `location_id`');
CALL add_f4_people_kpi_column('planner_evaluations', 'status', '`status` VARCHAR(30) NOT NULL DEFAULT "DRAFT" AFTER `action_plan`');
CALL add_f4_people_kpi_column('planner_evaluations', 'version', '`version` INT NOT NULL DEFAULT 1 AFTER `status`');
CALL add_f4_people_kpi_column('planner_evaluations', 'approved_by', '`approved_by` INT NULL AFTER `version`');
CALL add_f4_people_kpi_column('planner_evaluations', 'approved_at', '`approved_at` DATETIME NULL AFTER `approved_by`');
CALL add_f4_people_kpi_column('planner_evaluations', 'created_at', '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `approved_at`');
CALL add_f4_people_kpi_column('planner_evaluations', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

DROP PROCEDURE IF EXISTS add_f4_people_kpi_column;

-- 3. Table for SPL Planner Reviews (Replacing localStorage)
CREATE TABLE IF NOT EXISTS `spl_planner_reviews` (
    `review_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` CHAR(36) NOT NULL,
    `spl_number` VARCHAR(190) NOT NULL,
    `planner_user_id` INT NOT NULL,
    `review_status` VARCHAR(50) NOT NULL DEFAULT 'Reviewed',
    `review_notes` TEXT NULL,
    `reviewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_spl_review_report` (`report_id`),
    INDEX `idx_spl_review_number` (`spl_number`),
    INDEX `idx_spl_review_planner` (`planner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Initial update for baseline rows (evaluated users)
UPDATE `head_kpi_assessments`
SET `period_key` = '2026-01',
    `evaluated_user_id` = 2,
    `status` = 'DRAFT'
WHERE `evaluated_user_id` IS NULL;

UPDATE `planner_evaluations`
SET `period_key` = '2026-01',
    `evaluator_user_id` = 2,
    `status` = 'DRAFT'
WHERE `evaluator_user_id` IS NULL;

