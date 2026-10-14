-- Revisi 4: kebutuhan suku cadang dan layanan langsung dari Work Order.
-- Aman dijalankan berulang pada MySQL 8.x.

SET @schema_name = DATABASE();

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders'
          AND COLUMN_NAME = 'parts_requirement'
    ),
    'SELECT 1',
    "ALTER TABLE `work_orders` ADD COLUMN `parts_requirement` ENUM('UNASSESSED','REQUIRED','NOT_REQUIRED') NOT NULL DEFAULT 'UNASSESSED' AFTER `maintenance_type`"
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'work_orders'
          AND COLUMN_NAME = 'parts_requirement_note'
    ),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `parts_requirement_note` TEXT NULL AFTER `parts_requirement`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'purchase_requests'
          AND COLUMN_NAME = 'notes'
    ),
    'SELECT 1',
    'ALTER TABLE `purchase_requests` ADD COLUMN `notes` TEXT NULL AFTER `status`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE BINARY CONSTRAINT_SCHEMA = BINARY @schema_name
          AND BINARY TABLE_NAME = BINARY 'purchase_request_items'
          AND BINARY CONSTRAINT_NAME = BINARY 'fk_purchase_request_items_header'
    ),
    'SELECT 1',
    'ALTER TABLE `purchase_request_items` ADD CONSTRAINT `fk_purchase_request_items_header` FOREIGN KEY (`spb_id`) REFERENCES `purchase_requests` (`spb_id`) ON DELETE CASCADE'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

CREATE TABLE IF NOT EXISTS `work_order_service_requests` (
    `service_request_id` VARCHAR(50) NOT NULL,
    `wo_id` VARCHAR(50) NOT NULL,
    `asset_id` VARCHAR(100) NOT NULL,
    `service_type` ENUM('INTERNAL_WORKSHOP','EXTERNAL_WORKSHOP','FIELD_SERVICE','SPECIALIST_VENDOR') NOT NULL,
    `provider_name` VARCHAR(150) NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('Normal','Urgent','Emergency') NOT NULL DEFAULT 'Normal',
    `status` ENUM('Submitted','Assigned','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Submitted',
    `requested_by` INT NOT NULL,
    `assigned_to` VARCHAR(150) NULL,
    `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `notes` TEXT NULL,
    PRIMARY KEY (`service_request_id`),
    KEY `idx_wo_service_request_wo` (`wo_id`,`status`),
    KEY `idx_wo_service_request_asset` (`asset_id`,`status`),
    KEY `fk_wo_service_request_user` (`requested_by`),
    CONSTRAINT `fk_wo_service_request_wo` FOREIGN KEY (`wo_id`) REFERENCES `work_orders` (`wo_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wo_service_request_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_wo_service_request_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `work_orders` w
SET w.`parts_requirement` = 'REQUIRED',
    w.`parts_requirement_note` = COALESCE(NULLIF(w.`parts_requirement_note`, ''), 'Ditandai otomatis dari SPB yang sudah tertaut.')
WHERE EXISTS (
    SELECT 1 FROM `purchase_requests` pr WHERE pr.`wo_id` = w.`wo_id`
);

