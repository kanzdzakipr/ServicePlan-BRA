USE asset_manager;

SET @db = DATABASE();

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maintenance_orders' AND COLUMN_NAME='priority')=0,
'ALTER TABLE maintenance_orders ADD COLUMN priority ENUM("Rendah","Sedang","Tinggi","Darurat") NOT NULL DEFAULT "Sedang" AFTER description','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maintenance_orders' AND COLUMN_NAME='mechanic_id')=0,
'ALTER TABLE maintenance_orders ADD COLUMN mechanic_id INT NULL AFTER priority','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maintenance_orders' AND COLUMN_NAME='start_datetime')=0,
'ALTER TABLE maintenance_orders ADD COLUMN start_datetime DATETIME NULL AFTER service_date','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maintenance_orders' AND COLUMN_NAME='end_datetime')=0,
'ALTER TABLE maintenance_orders ADD COLUMN end_datetime DATETIME NULL AFTER start_datetime','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maintenance_orders' AND COLUMN_NAME='estimated_hours')=0,
'ALTER TABLE maintenance_orders ADD COLUMN estimated_hours DECIMAL(8,2) DEFAULT 0 AFTER downtime_hours','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='maintenance_orders' AND COLUMN_NAME='work_notes')=0,
'ALTER TABLE maintenance_orders ADD COLUMN work_notes TEXT NULL AFTER estimated_hours','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='maintenance_orders' AND CONSTRAINT_NAME='fk_maintenance_mechanic')=0,
'ALTER TABLE maintenance_orders ADD CONSTRAINT fk_maintenance_mechanic FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE SET NULL','SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE maintenance_orders SET priority='Sedang' WHERE priority IS NULL OR priority='';
