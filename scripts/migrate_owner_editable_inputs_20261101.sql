-- Revisi input milik pembuat sebelum approval / transaksi turunan.
DROP PROCEDURE IF EXISTS add_owner_edit_column;
DELIMITER $$
CREATE PROCEDURE add_owner_edit_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column
    ) THEN
        SET @owner_edit_sql = CONCAT('ALTER TABLE `', REPLACE(p_table, '`', '``'), '` ADD COLUMN `', REPLACE(p_column, '`', '``'), '` ', p_definition);
        PREPARE owner_edit_stmt FROM @owner_edit_sql;
        EXECUTE owner_edit_stmt;
        DEALLOCATE PREPARE owner_edit_stmt;
    END IF;
END$$
DELIMITER ;

CALL add_owner_edit_column('work_orders', 'created_by', 'INT NULL AFTER `wo_id`');
CALL add_owner_edit_column('work_orders', 'input_state', 'VARCHAR(20) NOT NULL DEFAULT ''SUBMITTED'' AFTER `created_by`');
CALL add_owner_edit_column('work_orders', 'input_revision_no', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER `input_state`');
CALL add_owner_edit_column('work_orders', 'input_last_edited_at', 'DATETIME NULL AFTER `input_revision_no`');
CALL add_owner_edit_column('work_orders', 'input_last_edited_by', 'INT NULL AFTER `input_last_edited_at`');

CALL add_owner_edit_column('assets', 'created_by', 'INT NULL AFTER `asset_id`');
CALL add_owner_edit_column('assets', 'input_state', 'VARCHAR(20) NOT NULL DEFAULT ''SUBMITTED'' AFTER `created_by`');
CALL add_owner_edit_column('assets', 'input_revision_no', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER `input_state`');
CALL add_owner_edit_column('assets', 'input_last_edited_at', 'DATETIME NULL AFTER `input_revision_no`');
CALL add_owner_edit_column('assets', 'input_last_edited_by', 'INT NULL AFTER `input_last_edited_at`');

CALL add_owner_edit_column('purchase_requests', 'input_revision_no', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER `status`');
CALL add_owner_edit_column('purchase_requests', 'input_last_edited_at', 'DATETIME NULL AFTER `input_revision_no`');
CALL add_owner_edit_column('purchase_requests', 'input_last_edited_by', 'INT NULL AFTER `input_last_edited_at`');

DROP PROCEDURE IF EXISTS add_owner_edit_column;

CREATE TABLE IF NOT EXISTS input_revision_audit (
    revision_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(30) NOT NULL,
    entity_id VARCHAR(100) NOT NULL,
    action VARCHAR(30) NOT NULL,
    reason VARCHAR(500) NOT NULL,
    actor_user_id INT NULL,
    before_json LONGTEXT NULL,
    after_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (revision_id),
    KEY idx_input_revision_entity (entity_type, entity_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
