-- Migration: RBAC Scope All Locations for Management & Operational Lead Roles
-- Applied via sync_to_laragon.ps1

START TRANSACTION;

-- Ensure permissions table exists and has scope.all_locations defined
CREATE TABLE IF NOT EXISTS `permissions` (
  `permission_id` int NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `menu_slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` enum('READ','CREATE','UPDATE','APPROVE','OVERRIDE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_permission_id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_permission_id`),
  UNIQUE KEY `uk_role_perm` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (permission_key, menu_slug, action_type, description) VALUES
('dashboard.read', 'dashboard', 'READ', 'View the authenticated dashboard'),
('assets.read', 'assets', 'READ', 'View asset records'),
('assets.write', 'assets', 'UPDATE', 'Create, update, or remove asset records'),
('work_orders.read', 'work-orders', 'READ', 'View work orders'),
('work_orders.write', 'work-orders', 'UPDATE', 'Create or update work orders'),
('accidents.read', 'hse-accident', 'READ', 'View accident records'),
('accidents.write', 'hse-accident', 'UPDATE', 'Create or update accident records'),
('fuel.read', 'fuel', 'READ', 'View fuel records'),
('fuel.write', 'fuel', 'UPDATE', 'Create or update fuel records'),
('inspections.read', 'inspections', 'READ', 'View inspection records'),
('inspections.write', 'inspections', 'UPDATE', 'Create or update inspection records'),
('logistics.read', 'logistics', 'READ', 'View logistics records'),
('logistics.write', 'logistics', 'UPDATE', 'Create or update logistics records'),
('pm.read', 'pm', 'READ', 'View preventive maintenance plans'),
('pm.write', 'pm', 'UPDATE', 'Create or update preventive maintenance plans'),
('tires.read', 'tires', 'READ', 'View tire inspections'),
('tires.write', 'tires', 'UPDATE', 'Create or update tire inspections'),
('reports.read', 'reports', 'READ', 'View reports'),
('reports.write', 'reports', 'UPDATE', 'Create and edit reports'),
('reports.approve', 'reports', 'APPROVE', 'Void or approve final reports'),
('reports.read_all', 'reports', 'READ', 'View reports created by other users'),
('scope.all_locations', 'system', 'READ', 'Access records from every operational location'),
('archive.read', 'archive', 'READ', 'View archived records'),
('archive.write', 'archive', 'UPDATE', 'Archive or restore records'),
('sync.write', 'system', 'OVERRIDE', 'Synchronize bulk application state'),
('admin.seed', 'system', 'CREATE', 'Run development-only data seeders');

-- Grant scope.all_locations to:
-- Equipment Manager, Maintenance Planner, Asset Manager, Inspector K3L / Safety, Logistic Head, HRD Manager, and Administrator
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name IN (
    'Administrator',
    'Equipment Manager',
    'Maintenance Planner',
    'Asset Manager',
    'Inspector K3L / Safety',
    'Logistic Head',
    'HRD Manager'
) AND p.permission_key = 'scope.all_locations';

COMMIT;
