-- F4 finishing: location-aware KPI lifecycle and append-only audit trail.
-- Additive and idempotent; does not alter or delete historical assessment rows.

CREATE TABLE IF NOT EXISTS `head_kpi_audit_trail` (
    `audit_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `period_key` VARCHAR(10) NOT NULL,
    `location_id` INT NOT NULL,
    `version` INT NOT NULL DEFAULT 1,
    `action` VARCHAR(32) NOT NULL,
    `from_status` VARCHAR(30) NOT NULL,
    `to_status` VARCHAR(30) NOT NULL,
    `actor_user_id` INT NOT NULL,
    `snapshot_json` JSON NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`audit_id`),
    KEY `idx_head_kpi_audit_scope` (`period_key`, `location_id`, `version`, `created_at`),
    KEY `idx_head_kpi_audit_actor` (`actor_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_f4_finishing_index;
DELIMITER $$
CREATE PROCEDURE add_f4_finishing_index(
    IN p_target_table VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN index_columns VARCHAR(500)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = p_target_table
          AND index_name = p_index_name
    ) THEN
        SET @statement = CONCAT(
            'ALTER TABLE `', p_target_table, '` ADD INDEX `', p_index_name, '` ', index_columns
        );
        PREPARE stmt FROM @statement;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL add_f4_finishing_index(
    'head_kpi_assessments',
    'idx_head_kpi_period_location',
    '(`period_key`, `location_id`, `indicator_no`)'
);

DROP PROCEDURE IF EXISTS add_f4_finishing_index;
