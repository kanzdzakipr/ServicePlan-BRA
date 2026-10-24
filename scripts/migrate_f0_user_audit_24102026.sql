-- F0: persistent audit trail for account administration.
-- Additive and idempotent; it does not modify existing user records.

CREATE TABLE IF NOT EXISTS `user_audit_trail` (
  `audit_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(32) NOT NULL,
  `actor_user_id` int DEFAULT NULL,
  `actor_username` varchar(50) NOT NULL,
  `target_user_id` int DEFAULT NULL,
  `target_username` varchar(50) NOT NULL,
  `details_json` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_user_audit_actor` (`actor_user_id`, `created_at`),
  KEY `idx_user_audit_target` (`target_user_id`, `created_at`),
  KEY `idx_user_audit_action` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
