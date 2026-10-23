-- ============================================================================
-- Migration: Seed Initial Notification Recipients for Testing & Production
-- Date: 2026-10-23
-- Purpose: Ensures notification bell and inbox have realistic actionable records
-- ============================================================================

-- Populate notification recipients for key active roles so UI notification
-- dropdown, unread badges, mark-read, and filter tabs are immediately testable.

INSERT INTO `notification_recipients` (`notification_id`, `user_id`, `location_id`, `read_at`) VALUES
-- Admin Utama (user_id = 1, global scope): 3 unread, 2 read
(1, 1, 4, NULL),
(2, 1, 4, NULL),
(3, 1, 4, NULL),
(4, 1, 4, '2026-08-09 11:00:00'),
(5, 1, 4, '2026-08-09 11:00:00'),

-- Head of Equipment (user_id = 2, global scope): 3 unread, 2 read
(1, 2, 4, NULL),
(2, 2, 4, NULL),
(3, 2, 4, NULL),
(4, 2, 4, '2026-08-09 11:00:00'),
(5, 2, 4, '2026-08-09 11:00:00'),

-- Maintenance Planner Duri (user_id = 3, location 4): 3 unread, 1 read
(1, 3, 4, NULL),
(2, 3, 4, NULL),
(3, 3, 4, NULL),
(5, 3, 4, '2026-08-09 11:00:00'),

-- Mekanik Senior Lead (user_id = 4, location 4): 2 unread
(1, 4, 4, NULL),
(2, 4, 4, NULL)

ON DUPLICATE KEY UPDATE
    `location_id` = VALUES(`location_id`);

