-- Client feedback 2026-11-01: remove requested personal labels from active data.
-- Idempotent: rows already migrated are left unchanged.

UPDATE users
SET username = 'maintenance_planner',
    full_name = 'Maintenance Planner'
WHERE username = 'martin_planner';

UPDATE users
SET full_name = 'Maintenance Planner'
WHERE full_name = 'P. Martin (Maintenance Planner)';

UPDATE work_orders
SET assigned_mechanic = REPLACE(assigned_mechanic, 'P. Martin', 'Maintenance Planner')
WHERE assigned_mechanic LIKE '%P. Martin%';

UPDATE planner_evaluations
SET actual_evaluation = 'Perlu verifikasi kompetensi teknis'
WHERE actual_evaluation = 'D3 Akuntansi';
