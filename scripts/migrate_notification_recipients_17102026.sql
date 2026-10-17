-- Additive, repeatable migration. No legacy audience is inferred or backfilled.
CREATE TABLE IF NOT EXISTS `notification_recipients` (
    `notification_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `location_id` INT NULL,
    `read_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`, `user_id`),
    KEY `idx_notification_recipient_user_read` (`user_id`, `read_at`, `created_at`),
    CONSTRAINT `fk_notification_recipient_notification` FOREIGN KEY (`notification_id`)
        REFERENCES `system_notifications` (`notification_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notification_recipient_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notification_recipient_location` FOREIGN KEY (`location_id`)
        REFERENCES `locations` (`location_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
