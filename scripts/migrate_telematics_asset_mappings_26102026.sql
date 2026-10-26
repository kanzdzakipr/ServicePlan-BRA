-- Migration: Pemetaan 6 Unit Telematika KOMTRAX ke Master Assets & Normalisasi Serial Number
-- Tanggal: 26 Oktober 2026

-- 1. Normalisasi DZ-00001 (Dozer 08) ke Serial Number Komatsu J21020
UPDATE `assets` SET 
    `serial_number` = 'J21020',
    `make_model` = 'Bulldozer Komatsu D85ESS-2',
    `chassis_number` = COALESCE(NULLIF(`chassis_number`, ''), 'ZLHD116HR1B05388'),
    `dealer` = 'United Tractors',
    `sub_group_branch` = 'PKB PEKANBARU Branch'
WHERE `asset_id` = 'DZ-00001';
UPDATE `telematics_logs` SET `asset_id` = 'DZ-00001' WHERE `log_id` = 22;

-- 2. Normalisasi Spasi Serial Number EXC-00010 (Exca 13) dari 'DBCH 0366' ke 'DBCH0366'
UPDATE `assets` SET 
    `serial_number` = 'DBCH0366',
    `hull_number` = COALESCE(NULLIF(`hull_number`, ''), 'Exca 13'),
    `alias_name` = COALESCE(NULLIF(`alias_name`, ''), 'Exca 13')
WHERE `asset_id` = 'EXC-00010';
UPDATE `telematics_logs` SET `asset_id` = 'EXC-00010' WHERE `log_id` = 29;

-- 3. Pemetaan EXC-00008 (Exca 12 Borrow Pit Harapan Baru) ke Serial Number DBCH0380
UPDATE `assets` SET 
    `serial_number` = 'DBCH0380',
    `hull_number` = COALESCE(NULLIF(`hull_number`, ''), 'Exca 12'),
    `alias_name` = COALESCE(NULLIF(`alias_name`, ''), 'Exca 12')
WHERE `asset_id` = 'EXC-00008';
UPDATE `telematics_logs` SET `asset_id` = 'EXC-00008' WHERE `log_id` = 30;

-- 4. Pemetaan EXC-00007 (Exca 07 WUR EW) ke Serial Number DBCH0302
UPDATE `assets` SET 
    `serial_number` = 'DBCH0302',
    `hull_number` = COALESCE(NULLIF(`hull_number`, ''), 'Exca 07'),
    `alias_name` = COALESCE(NULLIF(`alias_name`, ''), 'Exca 07'),
    `make_model` = 'Excavator Komatsu PC200-10M0 / CAT 320'
WHERE `asset_id` = 'EXC-00007';
UPDATE `telematics_logs` SET `asset_id` = 'EXC-00007' WHERE `log_id` = 28;

-- 5. Penambahan Master Unit MG-00002 (MG-04 / GR4) Motor Grader Komatsu GD535-5 SN 2259 Adera
INSERT INTO `assets` (
    `asset_id`, `asset_code`, `serial_number`, `alias_name`, `hull_number`, `type`, `category`, `make_model`,
    `dealer`, `project_name`, `operational_position`, `sub_group_branch`, `status`, `ownership`, `is_active`, `created_at`
) VALUES (
    'MG-00002', 'MG-04', '2259', 'Greder 04', 'GR4', 'Heavy Equipment', 'Motor Grader', 'MOTOR GRADER KOMATSU GD535-5',
    'United Tractors', 'ADERA', 'ADERA Field', 'PLB PALEMBANG Branch', 'OPERATING', 'Milik Sendiri', 1, NOW()
) ON DUPLICATE KEY UPDATE `serial_number` = '2259', `make_model` = 'MOTOR GRADER KOMATSU GD535-5';
UPDATE `telematics_logs` SET `asset_id` = 'MG-00002' WHERE `log_id` = 24;

-- 6. Penambahan Master Unit EXC-00021 (Exca 21) Excavator Komatsu PC195LC-8 SN J10309 Medan
INSERT INTO `assets` (
    `asset_id`, `asset_code`, `serial_number`, `alias_name`, `hull_number`, `type`, `category`, `make_model`,
    `dealer`, `project_name`, `operational_position`, `sub_group_branch`, `status`, `ownership`, `is_active`, `created_at`
) VALUES (
    'EXC-00021', 'EXC-00021', 'J10309', 'Exca 21 Medan', 'EXC-21', 'Heavy Equipment', 'Excavator', 'Excavator Komatsu PC195LC-8',
    'United Tractors', 'MEDAN', 'MDN MEDAN Branch', 'MDN MEDAN Branch', 'STANDBY', 'Milik Sendiri', 1, NOW()
) ON DUPLICATE KEY UPDATE `serial_number` = 'J10309', `make_model` = 'Excavator Komatsu PC195LC-8';
UPDATE `telematics_logs` SET `asset_id` = 'EXC-00021' WHERE `log_id` = 25;
