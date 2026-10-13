-- Revisi 3: detail dan timeline Work Order.
-- Aman dijalankan berulang kali pada MySQL 8 / MariaDB modern.

SET @schema_name = DATABASE();

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'work_orders' AND column_name = 'maintenance_type'),
    'SELECT 1',
    "ALTER TABLE `work_orders` ADD COLUMN `maintenance_type` ENUM('Corrective','Preventive','Inspection','Emergency') NOT NULL DEFAULT 'Corrective' AFTER `priority`"
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'work_orders' AND column_name = 'root_cause'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `root_cause` TEXT NULL AFTER `assigned_mechanic`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = @schema_name AND table_name = 'work_orders' AND column_name = 'repair_action'),
    'SELECT 1',
    'ALTER TABLE `work_orders` ADD COLUMN `repair_action` TEXT NULL AFTER `root_cause`'
);
PREPARE statement FROM @ddl; EXECUTE statement; DEALLOCATE PREPARE statement;

CREATE TABLE IF NOT EXISTS `work_order_timeline` (
    `timeline_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `wo_id` VARCHAR(50) NOT NULL,
    `event_type` ENUM('CREATED', 'STATUS_CHANGE', 'DETAIL_UPDATE') NOT NULL DEFAULT 'DETAIL_UPDATE',
    `from_status` VARCHAR(30) NULL,
    `to_status` VARCHAR(30) NULL,
    `title` VARCHAR(180) NOT NULL,
    `description` TEXT NULL,
    `actor_user_id` INT NULL,
    `actor_name` VARCHAR(100) NULL,
    `occurred_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_work_order_timeline_wo` FOREIGN KEY (`wo_id`) REFERENCES `work_orders`(`wo_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_work_order_timeline_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    INDEX `idx_wo_timeline_order` (`wo_id`, `occurred_at`, `timeline_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `work_order_timeline`
    (`wo_id`, `event_type`, `to_status`, `title`, `description`, `occurred_at`)
SELECT w.`wo_id`, 'CREATED', w.`status`, 'Work Order tercatat',
       'Riwayat awal dibuat dari data Work Order yang sudah ada.', w.`reported_at`
FROM `work_orders` w
WHERE NOT EXISTS (
    SELECT 1 FROM `work_order_timeline` t WHERE t.`wo_id` = w.`wo_id`
);
