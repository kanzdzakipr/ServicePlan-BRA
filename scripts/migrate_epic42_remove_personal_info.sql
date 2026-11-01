-- ============================================================================
-- Migration: Epic 4.2 - People & KPI Objektif (Pembersihan Data Personal & Netralitas Jabatan)
-- Target: planner_evaluations, users, work_orders
-- ============================================================================

UPDATE `planner_evaluations`
SET `actual_evaluation` = 'Perlu Pelatihan Teknis Mesin & Diagnostik'
WHERE `actual_evaluation` LIKE '%D3 Akuntansi%' OR `actual_evaluation` = 'D3 Akuntansi';

UPDATE `planner_evaluations`
SET `actual_evaluation` = 'Menguasai Administrasi & Pembukuan'
WHERE `actual_evaluation` LIKE '%Administrasi & Akuntansi%';

UPDATE `users`
SET `full_name` = 'Maintenance Planner'
WHERE `full_name` LIKE '%P. Martin%' OR `username` = 'martin_planner';

UPDATE `users`
SET `username` = 'maintenance_planner'
WHERE `username` = 'martin_planner';

UPDATE `work_orders`
SET `assigned_mechanic` = REPLACE(`assigned_mechanic`, 'P. Martin', 'Maintenance Planner')
WHERE `assigned_mechanic` LIKE '%P. Martin%';

UPDATE `work_orders`
SET `assigned_mechanics_json` = REPLACE(`assigned_mechanics_json`, 'P. Martin', 'Maintenance Planner')
WHERE `assigned_mechanics_json` LIKE '%P. Martin%';
