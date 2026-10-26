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

-- Grant baseline API permissions and scope.all_locations to:
-- Administrator
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Administrator';

-- Equipment Manager & Asset Manager (all operational except admin.seed and users.manage)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name IN ('Equipment Manager', 'Asset Manager')
  AND p.permission_key NOT IN ('admin.seed', 'users.manage');

-- Maintenance Planner
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Maintenance Planner'
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'assets.write', 'work_orders.read', 'work_orders.write',
    'accidents.read', 'fuel.read', 'inspections.read', 'inspections.write', 'logistics.read',
    'logistics.write', 'pm.read', 'pm.write', 'tires.read', 'reports.read', 'reports.write',
    'archive.read', 'archive.write', 'sync.write', 'scope.all_locations'
  );

-- Mechanics & Welders
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name IN ('Mekanik Senior', 'Mekanik Junior / Helper', 'Welder / Fabrikator')
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'work_orders.read', 'work_orders.write',
    'inspections.read', 'inspections.write', 'pm.read', 'tires.read', 'tires.write',
    'reports.read', 'archive.read'
  );

-- Inspector K3L / Safety
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name IN ('Inspector K3L / Safety', 'Inspector K3L', 'Safety')
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'work_orders.read', 'accidents.read', 'accidents.write',
    'inspections.read', 'inspections.write', 'reports.read', 'reports.write',
    'archive.read', 'archive.write', 'scope.all_locations'
  );

-- Logistic Head
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name IN ('Logistic Head', 'Logistic')
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'work_orders.read', 'logistics.read', 'logistics.write',
    'reports.read', 'reports.write', 'archive.read', 'archive.write', 'scope.all_locations'
  );

-- HRD Manager
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name IN ('HRD Manager', 'HRD')
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'reports.read', 'reports.write', 'archive.read', 'scope.all_locations'
  );

COMMIT;
