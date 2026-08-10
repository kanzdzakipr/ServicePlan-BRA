-- SEC-001 server-side RBAC permissions for the active API.
-- Run once with a migration account. The runtime database user does not need DDL.

START TRANSACTION;

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

-- Administrator, Equipment Manager, and Asset Manager receive all API permissions.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name IN ('Administrator', 'Equipment Manager', 'Asset Manager');

-- Maintenance Planner.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Maintenance Planner'
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'assets.write', 'work_orders.read', 'work_orders.write',
    'accidents.read', 'fuel.read', 'inspections.read', 'inspections.write', 'logistics.read',
    'logistics.write', 'pm.read', 'pm.write', 'tires.read', 'reports.read', 'reports.write',
    'archive.read', 'archive.write', 'sync.write'
  );

-- Mechanics and fabrication roles.
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

-- Safety / K3L.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Inspector K3L / Safety'
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'work_orders.read', 'accidents.read', 'accidents.write',
    'inspections.read', 'inspections.write', 'reports.read', 'reports.write',
    'archive.read', 'archive.write'
  );

-- Logistics.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Logistic Head'
  AND p.permission_key IN (
    'dashboard.read', 'assets.read', 'work_orders.read', 'logistics.read', 'logistics.write',
    'reports.read', 'reports.write', 'archive.read', 'archive.write'
  );

-- HRD.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'HRD Manager'
  AND p.permission_key IN ('dashboard.read', 'assets.read', 'reports.read', 'reports.write', 'archive.read');

COMMIT;
