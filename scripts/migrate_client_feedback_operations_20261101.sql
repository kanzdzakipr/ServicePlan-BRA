-- Backlog internal: governance, PM forecast, master part/UOM, fulfilment, dan ekspor.
DROP PROCEDURE IF EXISTS add_feedback_column;
DELIMITER $$
CREATE PROCEDURE add_feedback_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition VARCHAR(1000))
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=p_table AND column_name=p_column) THEN
    SET @ddl = CONCAT('ALTER TABLE `', REPLACE(p_table,'`','``'), '` ADD COLUMN `', REPLACE(p_column,'`','``'), '` ', p_definition);
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL add_feedback_column('parts','technical_name','VARCHAR(180) NULL AFTER `part_name`');
CALL add_feedback_column('parts','authenticity','ENUM(''GENUINE'',''NON_GENUINE'',''UNVERIFIED'') NOT NULL DEFAULT ''UNVERIFIED'' AFTER `technical_name`');
CALL add_feedback_column('parts','part_group','VARCHAR(100) NULL AFTER `authenticity`');
CALL add_feedback_column('parts','base_uom','VARCHAR(30) NULL AFTER `unit_measure`');
CALL add_feedback_column('parts','transaction_uom','VARCHAR(30) NULL AFTER `base_uom`');
CALL add_feedback_column('parts','conversion_factor','DECIMAL(18,6) NOT NULL DEFAULT 1 AFTER `transaction_uom`');
CALL add_feedback_column('purchase_request_items','part_id','INT NULL AFTER `part_number`');
CALL add_feedback_column('purchase_request_items','pm_plan_id','INT NULL AFTER `part_id`');
CALL add_feedback_column('purchase_request_items','transaction_uom','VARCHAR(30) NULL AFTER `qty_requested`');
CALL add_feedback_column('purchase_request_items','conversion_factor_snapshot','DECIMAL(18,6) NOT NULL DEFAULT 1 AFTER `transaction_uom`');
CALL add_feedback_column('purchase_request_items','base_quantity','DECIMAL(18,6) NULL AFTER `conversion_factor_snapshot`');
CALL add_feedback_column('purchase_request_items','fulfilled_quantity','DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `base_quantity`');
CALL add_feedback_column('purchase_request_items','fulfilment_pic_user_id','INT NULL AFTER `fulfilled_quantity`');
CALL add_feedback_column('purchase_request_items','fulfilled_at','DATETIME NULL AFTER `fulfilment_pic_user_id`');
CALL add_feedback_column('purchase_requests','parent_spb_id','VARCHAR(50) NULL AFTER `spb_id`');
CALL add_feedback_column('purchase_requests','revision_no','INT UNSIGNED NOT NULL DEFAULT 0 AFTER `parent_spb_id`');
CALL add_feedback_column('pm_plans','original_estimated_date','DATE NULL AFTER `target_due_hm`');
CALL add_feedback_column('pm_plans','revised_estimated_date','DATE NULL AFTER `original_estimated_date`');
CALL add_feedback_column('pm_plans','forecast_revision_no','INT UNSIGNED NOT NULL DEFAULT 0 AFTER `revised_estimated_date`');
CALL add_feedback_column('pm_plans','generation_key','VARCHAR(100) NULL AFTER `forecast_revision_no`');
CALL add_feedback_column('input_revision_audit','revision_no','INT UNSIGNED NOT NULL DEFAULT 0 AFTER `action`');
CALL add_feedback_column('input_revision_audit','parent_entity_id','VARCHAR(100) NULL AFTER `revision_no`');
CALL add_feedback_column('input_revision_audit','old_status','VARCHAR(30) NULL AFTER `parent_entity_id`');
CALL add_feedback_column('input_revision_audit','new_status','VARCHAR(30) NULL AFTER `old_status`');
CALL add_feedback_column('input_revision_audit','idempotency_key','VARCHAR(100) NULL AFTER `new_status`');

ALTER TABLE purchase_requests MODIFY status ENUM('Draft','Submitted','Revision Required','Rejected','Approved','Ordered','Issued','Closed','Cancelled') NOT NULL DEFAULT 'Submitted';

CREATE TABLE IF NOT EXISTS pm_forecast_adjustments (
  adjustment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pm_plan_id INT NOT NULL, asset_id VARCHAR(100) NOT NULL,
  adjustment_type ENUM('METER_CORRECTION','LONG_STANDBY','BREAKDOWN','OPERATION_CHANGE','OTHER') NOT NULL,
  original_estimated_date DATE NULL, revised_estimated_date DATE NOT NULL,
  target_meter DECIMAL(12,2) NOT NULL, current_meter DECIMAL(12,2) NOT NULL,
  usage_rate DECIMAL(12,4) NULL, effective_from DATE NULL, effective_to DATE NULL,
  status_source VARCHAR(100) NULL, reason VARCHAR(500) NOT NULL,
  created_by INT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pm_adjustment_plan (pm_plan_id, created_at), KEY idx_pm_adjustment_asset (asset_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS part_import_staging (
  staging_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_key VARCHAR(80) NOT NULL,
  row_number INT NOT NULL, sku VARCHAR(100) NULL, technical_name VARCHAR(180) NULL,
  authenticity VARCHAR(30) NULL, part_group VARCHAR(100) NULL, base_uom VARCHAR(30) NULL,
  transaction_uom VARCHAR(30) NULL, conversion_factor DECIMAL(18,6) NULL,
  warehouse_location VARCHAR(100) NULL, opening_balance DECIMAL(18,6) NULL,
  validation_status ENUM('VALID','INVALID','DUPLICATE') NOT NULL, validation_errors TEXT NULL,
  imported_by INT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_part_stage_batch_row (batch_key,row_number), KEY idx_part_stage_status (batch_key,validation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS input_revision_idempotency (
  idempotency_key VARCHAR(100) PRIMARY KEY, entity_type VARCHAR(30) NOT NULL,
  entity_id VARCHAR(100) NOT NULL, action VARCHAR(30) NOT NULL,
  response_json LONGTEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_revision_idempotency_entity (entity_type,entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_feedback_column;
