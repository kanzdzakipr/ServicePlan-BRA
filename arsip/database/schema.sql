CREATE DATABASE IF NOT EXISTS asset_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE asset_manager;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS mechanic_hours;DROP TABLE IF EXISTS mechanics;DROP TABLE IF EXISTS condition_events;DROP TABLE IF EXISTS grease_records;DROP TABLE IF EXISTS tire_inspections;DROP TABLE IF EXISTS logistics_orders;DROP TABLE IF EXISTS maintenance_orders;DROP TABLE IF EXISTS service_schedules;DROP TABLE IF EXISTS units;DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,email VARCHAR(150) UNIQUE NOT NULL,password_hash VARCHAR(255) NOT NULL,role VARCHAR(30) DEFAULT 'manager',is_active TINYINT(1) DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE units(id INT AUTO_INCREMENT PRIMARY KEY,unit_code VARCHAR(40) UNIQUE NOT NULL,unit_name VARCHAR(120) NOT NULL,category VARCHAR(80) NOT NULL,brand VARCHAR(80),model VARCHAR(80),serial_number VARCHAR(100),location VARCHAR(120),meter_type ENUM('HM','KM') DEFAULT 'HM',current_meter DECIMAL(12,2) DEFAULT 0,operational_status ENUM('Ready for Use','Breakdown','Downtime','Maintenance') DEFAULT 'Ready for Use',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);
CREATE TABLE service_schedules(id INT AUTO_INCREMENT PRIMARY KEY,unit_id INT NOT NULL UNIQUE,service_interval DECIMAL(10,2) NOT NULL,next_service_meter DECIMAL(12,2) NOT NULL,next_service_date DATE NOT NULL,status ENUM('Terjadwal','Akan Service','Jatuh Tempo','Terlambat','Selesai') DEFAULT 'Terjadwal',notes VARCHAR(255),updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE);
CREATE TABLE maintenance_orders(id INT AUTO_INCREMENT PRIMARY KEY,unit_id INT NOT NULL,work_order_no VARCHAR(50),service_date DATE,maintenance_type VARCHAR(100),description TEXT,status VARCHAR(40),actual_cost DECIMAL(15,2) DEFAULT 0,FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE);
CREATE TABLE logistics_orders(id INT AUTO_INCREMENT PRIMARY KEY,unit_id INT NULL,item_name VARCHAR(150) NOT NULL,quantity INT DEFAULT 1,vendor VARCHAR(120),order_date DATE,expected_date DATE,status ENUM('Dipesan','Diproses Vendor','Dalam Pengiriman','Tiba','Tertunda','Dibatalkan') DEFAULT 'Dipesan',total_cost DECIMAL(15,2) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL);
CREATE TABLE tire_inspections(id INT AUTO_INCREMENT PRIMARY KEY,unit_id INT NOT NULL,inspection_date DATE NOT NULL,tire_position VARCHAR(80) NOT NULL,pressure_psi DECIMAL(6,2),tread_depth_mm DECIMAL(6,2),condition_status VARCHAR(50),notes VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE);
CREATE TABLE grease_records(id INT AUTO_INCREMENT PRIMARY KEY,unit_id INT NOT NULL,grease_date DATE NOT NULL,meter_at_grease DECIMAL(12,2),interval_meter DECIMAL(10,2) DEFAULT 200,status VARCHAR(50),performed_by VARCHAR(100),notes VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE);
CREATE TABLE mechanics(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,employee_no VARCHAR(40),target_hours_month DECIMAL(8,2) DEFAULT 176,is_active TINYINT(1) DEFAULT 1);
CREATE TABLE mechanic_hours(id INT AUTO_INCREMENT PRIMARY KEY,mechanic_id INT NOT NULL,work_date DATE NOT NULL,unit_id INT NULL,work_order_no VARCHAR(50),start_time TIME,end_time TIME,effective_hours DECIMAL(6,2) DEFAULT 0,activity VARCHAR(255),FOREIGN KEY(mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE,FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL);
CREATE TABLE condition_events(id INT AUTO_INCREMENT PRIMARY KEY,unit_id INT NOT NULL,event_date DATE NOT NULL,status ENUM('Ready for Use','Breakdown','Downtime') NOT NULL,start_time TIME NULL,end_time TIME NULL,duration_hours DECIMAL(8,2) DEFAULT 0,cause VARCHAR(255),action_taken VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE);

INSERT INTO users(name,email,password_hash,role) VALUES('Asset Manager','admin@assetpro.local','$2y$12$gq6000r573QinkdByf2uiO673d7l9rCDq1or4EMkh.ViG8mHZH3wW','admin');
INSERT INTO units(unit_code,unit_name,category,brand,model,location,meter_type,current_meter,operational_status) VALUES
('DT-054','Dump Truck 00054','Dump Truck','Hino','FM 260 JD','Yard KM 12','KM',178300,'Ready for Use'),
('DT-061','Dump Truck 00061','Dump Truck','Hino','FM 260 JD','Pit','KM',186750,'Breakdown'),
('EX-021','Excavator PC200','Excavator','Komatsu','PC200','Borrow Pit','HM',8496,'Ready for Use'),
('D6G-04','Bulldozer D6G','Bulldozer','Caterpillar','D6G','Pit','HM',10420,'Downtime'),
('MG-009','Motor Grader','Motor Grader','Komatsu','GD535-5','Yard KM 12','HM',6920,'Ready for Use'),
('VR-002','Vibro Roller','Vibro Roller','Bomag','BW211D-40','Minas','HM',4840,'Ready for Use');
INSERT INTO service_schedules(unit_id,service_interval,next_service_meter,next_service_date,status,notes) VALUES
(1,5000,180000,DATE_ADD(CURDATE(),INTERVAL 8 DAY),'Terjadwal','Siapkan filter dan oli'),
(2,5000,185000,DATE_SUB(CURDATE(),INTERVAL 3 DAY),'Terlambat','Prioritas tinggi'),
(3,250,8500,CURDATE(),'Jatuh Tempo','Service 250 HM'),
(4,250,10250,DATE_SUB(CURDATE(),INTERVAL 5 DAY),'Terlambat','Periksa engine dan powertrain'),
(5,250,7000,DATE_ADD(CURDATE(),INTERVAL 4 DAY),'Akan Service','Booking mekanik'),
(6,250,5000,DATE_ADD(CURDATE(),INTERVAL 10 DAY),'Terjadwal','Normal');
INSERT INTO maintenance_orders(unit_id,work_order_no,service_date,maintenance_type,description,status,actual_cost) VALUES
(2,'WO-260701',CURDATE(),'Corrective','Penggantian spring dan pengecekan chassis','Selesai',56200000),(3,'WO-260702',CURDATE(),'Periodic','Service berkala engine','Selesai',48750000),(4,'WO-260703',CURDATE(),'Corrective','Perbaikan overheating','Dalam Proses',37800000),(5,'WO-260704',CURDATE(),'Periodic','Penggantian filter','Selesai',28450000),(6,'WO-260705',CURDATE(),'Inspection','Pengecekan rutin','Selesai',22300000);
INSERT INTO logistics_orders(unit_id,item_name,quantity,vendor,order_date,expected_date,status,total_cost) VALUES
(2,'Leaf Spring Assembly',2,'Vendor A',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 3 DAY),'Dalam Pengiriman',18000000),(4,'Water Pump',1,'Vendor B',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 5 DAY),'Diproses Vendor',12500000),(3,'Filter Kit 250 HM',1,'Vendor C',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 DAY),'Dipesan',4500000);
INSERT INTO mechanics(name,employee_no) VALUES('Apeng','M001'),('Maman','M002'),('Soleh Al Muzakar','M003'),('Darmawan','M004'),('Regar','M005'),('Landa','M006'),('Tonop Prasetyo','M007');
INSERT INTO tire_inspections(unit_id,inspection_date,tire_position,pressure_psi,tread_depth_mm,condition_status,notes) VALUES(2,CURDATE(),'Belakang Kanan',82,3.2,'Tipis','Rencanakan penggantian'),(1,CURDATE(),'Depan Kiri',88,5.0,'Aus Tidak Rata','Perlu spooring'),(5,CURDATE(),'Belakang Kiri',90,6.5,'Perlu Rotasi','Rotasi minggu ini');
INSERT INTO grease_records(unit_id,grease_date,meter_at_grease,interval_meter,status,performed_by) VALUES(4,DATE_SUB(CURDATE(),INTERVAL 10 DAY),10202,200,'Terlambat','Apeng'),(3,DATE_SUB(CURDATE(),INTERVAL 5 DAY),8304,200,'Jatuh Tempo','Maman'),(2,DATE_SUB(CURDATE(),INTERVAL 3 DAY),186602,200,'Sesuai Jadwal','Soleh Al Muzakar');
INSERT INTO mechanic_hours(mechanic_id,work_date,unit_id,work_order_no,start_time,end_time,effective_hours,activity) VALUES(1,CURDATE(),4,'WO-260703','08:00','17:00',7.5,'Perbaikan overheating'),(2,CURDATE(),3,'WO-260702','08:00','16:00',6.5,'Service berkala'),(3,CURDATE(),2,'WO-260701','08:00','18:00',8.0,'Penggantian spring');
INSERT INTO condition_events(unit_id,event_date,status,duration_hours,cause,action_taken) VALUES
(1,DATE_SUB(CURDATE(),INTERVAL 5 DAY),'Ready for Use',24,'-','Operasi normal'),(2,DATE_SUB(CURDATE(),INTERVAL 5 DAY),'Breakdown',8,'Spring patah','Perbaikan chassis'),(3,DATE_SUB(CURDATE(),INTERVAL 4 DAY),'Ready for Use',24,'-','Operasi normal'),(4,DATE_SUB(CURDATE(),INTERVAL 4 DAY),'Downtime',6,'Overheat','Inspeksi cooling system'),(5,DATE_SUB(CURDATE(),INTERVAL 3 DAY),'Ready for Use',24,'-','Operasi normal'),(6,DATE_SUB(CURDATE(),INTERVAL 2 DAY),'Ready for Use',24,'-','Operasi normal'),(2,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'Breakdown',10,'Menunggu spare part','Follow up vendor'),(4,CURDATE(),'Downtime',5,'Perbaikan berjalan','Monitoring mekanik');

-- =========================================================
-- ASSETPRO V2: SECURITY, DOCUMENTS, APPROVAL, KPI, QR, AUDIT
-- =========================================================
ALTER TABLE units ADD COLUMN location_code VARCHAR(40) NULL AFTER location;
ALTER TABLE units ADD COLUMN acquisition_value DECIMAL(15,2) DEFAULT 0 AFTER operational_status;
ALTER TABLE units ADD COLUMN qr_token VARCHAR(80) NULL UNIQUE AFTER acquisition_value;
ALTER TABLE units ADD COLUMN is_billable TINYINT(1) DEFAULT 1 AFTER qr_token;
ALTER TABLE maintenance_orders MODIFY work_order_no VARCHAR(50) NOT NULL;
ALTER TABLE maintenance_orders ADD COLUMN requested_by INT NULL AFTER actual_cost;
ALTER TABLE maintenance_orders ADD COLUMN approved_by INT NULL AFTER requested_by;
ALTER TABLE maintenance_orders ADD COLUMN completed_at DATETIME NULL AFTER approved_by;
ALTER TABLE maintenance_orders ADD COLUMN meter_at_service DECIMAL(12,2) DEFAULT 0 AFTER completed_at;
ALTER TABLE maintenance_orders ADD COLUMN downtime_hours DECIMAL(8,2) DEFAULT 0 AFTER meter_at_service;
ALTER TABLE maintenance_orders ADD UNIQUE KEY uq_work_order_no(work_order_no);
ALTER TABLE logistics_orders ADD COLUMN request_no VARCHAR(50) NULL UNIQUE AFTER id;
ALTER TABLE logistics_orders ADD COLUMN approval_status ENUM('Draft','Menunggu Approval','Disetujui','Ditolak') DEFAULT 'Draft' AFTER status;
ALTER TABLE logistics_orders ADD COLUMN requested_by INT NULL AFTER approval_status;
ALTER TABLE logistics_orders ADD COLUMN approved_by INT NULL AFTER requested_by;
ALTER TABLE logistics_orders ADD COLUMN approved_at DATETIME NULL AFTER approved_by;
ALTER TABLE logistics_orders ADD COLUMN rejection_reason VARCHAR(255) NULL AFTER approved_at;

CREATE TABLE IF NOT EXISTS locations(
 id INT AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) UNIQUE NOT NULL,
 name VARCHAR(120) NOT NULL,
 description VARCHAR(255),
 is_active TINYINT(1) DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS unit_documents(
 id INT AUTO_INCREMENT PRIMARY KEY,
 unit_id INT NULL,
 logistics_order_id INT NULL,
 maintenance_order_id INT NULL,
 document_type ENUM('Foto Kerusakan','Quotation','Purchase Order','Invoice','BAST','Lainnya') NOT NULL,
 original_name VARCHAR(255) NOT NULL,
 stored_name VARCHAR(255) NOT NULL,
 file_path VARCHAR(255) NOT NULL,
 mime_type VARCHAR(100),
 file_size INT DEFAULT 0,
 notes VARCHAR(255),
 uploaded_by INT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE CASCADE,
 FOREIGN KEY(logistics_order_id) REFERENCES logistics_orders(id) ON DELETE CASCADE,
 FOREIGN KEY(maintenance_order_id) REFERENCES maintenance_orders(id) ON DELETE CASCADE,
 FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS status_histories(
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 entity_type VARCHAR(50) NOT NULL,
 entity_id INT NOT NULL,
 old_status VARCHAR(80),
 new_status VARCHAR(80) NOT NULL,
 notes VARCHAR(255),
 changed_by INT NOT NULL,
 changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_entity(entity_type,entity_id),
 FOREIGN KEY(changed_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS notifications(
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NULL,
 type VARCHAR(50) NOT NULL,
 title VARCHAR(150) NOT NULL,
 message VARCHAR(255) NOT NULL,
 target_url VARCHAR(255),
 is_read TINYINT(1) DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs(
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NULL,
 action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(50),
 entity_id INT NULL,
 description VARCHAR(255),
 ip_address VARCHAR(45),
 user_agent VARCHAR(255),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_audit_created(created_at),
 INDEX idx_audit_user(user_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS system_settings(
 setting_key VARCHAR(100) PRIMARY KEY,
 setting_value TEXT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO locations(code,name,description) VALUES
('YARD12','Yard BRA KM 12','Workshop dan yard utama'),
('PIT','Pit','Area produksi pit'),
('BORROW','Borrow Pit','Area borrow pit'),
('MINAS','Minas','Area operasional Minas');

UPDATE units SET location_code=CASE location
 WHEN 'Yard KM 12' THEN 'YARD12' WHEN 'Pit' THEN 'PIT' WHEN 'Borrow Pit' THEN 'BORROW' WHEN 'Minas' THEN 'MINAS' ELSE 'YARD12' END;
UPDATE units SET qr_token=CONCAT('UNIT-',id,'-',SUBSTRING(MD5(CONCAT(unit_code,NOW())),1,12)) WHERE qr_token IS NULL;

INSERT IGNORE INTO users(name,email,password_hash,role,is_active) VALUES
('Head of Equipment','head@assetpro.local','$2y$12$gq6000r573QinkdByf2uiO673d7l9rCDq1or4EMkh.ViG8mHZH3wW','head_equipment',1),
('Planner Equipment','planner@assetpro.local','$2y$12$gq6000r573QinkdByf2uiO673d7l9rCDq1or4EMkh.ViG8mHZH3wW','planner',1),
('Mekanik','mekanik@assetpro.local','$2y$12$gq6000r573QinkdByf2uiO673d7l9rCDq1or4EMkh.ViG8mHZH3wW','mechanic',1),
('Logistik','logistik@assetpro.local','$2y$12$gq6000r573QinkdByf2uiO673d7l9rCDq1or4EMkh.ViG8mHZH3wW','logistics',1),
('Viewer','viewer@assetpro.local','$2y$12$gq6000r573QinkdByf2uiO673d7l9rCDq1or4EMkh.ViG8mHZH3wW','viewer',1);
UPDATE users SET role='asset_manager' WHERE email='admin@assetpro.local';

INSERT IGNORE INTO system_settings(setting_key,setting_value) VALUES
('company_name','PT Bina Rekayasa Anugrah'),
('service_warning_days','7'),
('service_warning_meter','50'),
('backup_retention_days','30');
