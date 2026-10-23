-- ============================================================================
-- Migration: Populate Heavy Equipment PIC (Operators) from January 2026 Report
-- Generated: 2026-10-23 09:34:47
-- Total Assets: 106 units
--
-- This data migration is deliberately one-shot. The Laragon sync script runs
-- additive migrations on every preserved-database sync, so a durable marker is
-- required to prevent duplicate operator-history rows and to avoid replacing a
-- PIC that a user may have edited after the initial import.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `app_data_migrations` (
  `migration_key` VARCHAR(191) NOT NULL,
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`migration_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS `apply_asset_pics_jan2026`;
DELIMITER $$
CREATE PROCEDURE `apply_asset_pics_jan2026`()
pic_migration: BEGIN
  IF EXISTS (
    SELECT 1
    FROM `app_data_migrations`
    WHERE `migration_key` = 'populate_asset_pics_jan2026_v1'
  ) THEN
    LEAVE pic_migration;
  END IF;

  START TRANSACTION;

-- Asset: DT-00001 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SYAFRI' WHERE `asset_id` = 'DT-00001';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-06' WHERE `asset_id` = 'DT-00001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00001', 'SYAFRI', '2026-01-06', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00003 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RIO KURNIAWAN' WHERE `asset_id` = 'DT-00003';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-27' WHERE `asset_id` = 'DT-00003' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00003', 'RIO KURNIAWAN', '2025-12-27', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00004 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ERICK' WHERE `asset_id` = 'DT-00004';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00004' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00004', 'ERICK', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00005 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ALI UDIN SAGALA' WHERE `asset_id` = 'DT-00005';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-30' WHERE `asset_id` = 'DT-00005' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00005', 'ALI UDIN SAGALA', '2025-12-30', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00006 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SYAWALUDDIN' WHERE `asset_id` = 'DT-00006';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-12' WHERE `asset_id` = 'DT-00006' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00006', 'SYAWALUDDIN', '2026-01-12', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00008 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RICO SAPUTRA' WHERE `asset_id` = 'DT-00008';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-27' WHERE `asset_id` = 'DT-00008' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00008', 'RICO SAPUTRA', '2025-12-27', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00009 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'FADLIL DINIL HAQ' WHERE `asset_id` = 'DT-00009';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-06' WHERE `asset_id` = 'DT-00009' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00009', 'FADLIL DINIL HAQ', '2026-01-06', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00010 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ATIK MUZAKI' WHERE `asset_id` = 'DT-00010';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-17' WHERE `asset_id` = 'DT-00010' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00010', 'ATIK MUZAKI', '2026-01-17', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00011 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'BUYUNG KAPRI' WHERE `asset_id` = 'DT-00011';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-30' WHERE `asset_id` = 'DT-00011' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00011', 'BUYUNG KAPRI', '2025-12-30', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-00012 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RAHMAT N. NAIN' WHERE `asset_id` = 'DT-00012';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-27' WHERE `asset_id` = 'DT-00012' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00012', 'RAHMAT N. NAIN', '2025-12-27', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00013 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'WALUYO (WAJAH LUGU BUAYO)' WHERE `asset_id` = 'DT-00013';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-09' WHERE `asset_id` = 'DT-00013' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00013', 'WALUYO (WAJAH LUGU BUAYO)', '2026-01-09', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00014 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SABAM' WHERE `asset_id` = 'DT-00014';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00014' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00014', 'SABAM', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00015 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'DEDEK ILHAM ARIFIN' WHERE `asset_id` = 'DT-00015';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-14' WHERE `asset_id` = 'DT-00015' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00015', 'DEDEK ILHAM ARIFIN', '2026-01-14', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00016 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SARDI' WHERE `asset_id` = 'DT-00016';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00016' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00016', 'SARDI', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00017 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SAHRUL BAHRI' WHERE `asset_id` = 'DT-00017';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'DT-00017' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00017', 'SAHRUL BAHRI', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00019 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'KARDIMAN SARAGIH' WHERE `asset_id` = 'DT-00019';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00019' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00019', 'KARDIMAN SARAGIH', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00021 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ABDUL RAIS' WHERE `asset_id` = 'DT-00021';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00021' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00021', 'ABDUL RAIS', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00022 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'EKO FRANS SATRIA' WHERE `asset_id` = 'DT-00022';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-14' WHERE `asset_id` = 'DT-00022' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00022', 'EKO FRANS SATRIA', '2026-01-14', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00023 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'PAWITNO' WHERE `asset_id` = 'DT-00023';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00023' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00023', 'PAWITNO', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00024 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RONSON HUTABARAT' WHERE `asset_id` = 'DT-00024';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-17' WHERE `asset_id` = 'DT-00024' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00024', 'RONSON HUTABARAT', '2026-01-17', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00025 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'NOVA RIZAL' WHERE `asset_id` = 'DT-00025';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-10' WHERE `asset_id` = 'DT-00025' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00025', 'NOVA RIZAL', '2026-01-10', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00026 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'DEDI SANTIAJI' WHERE `asset_id` = 'DT-00026';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00026' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00026', 'DEDI SANTIAJI', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00027 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ANDIKA' WHERE `asset_id` = 'DT-00027';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'DT-00027' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00027', 'ANDIKA', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00028 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'AHMAD RAFIK HASIBUAN' WHERE `asset_id` = 'DT-00028';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-14' WHERE `asset_id` = 'DT-00028' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00028', 'AHMAD RAFIK HASIBUAN', '2026-01-14', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00029 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'MARSENI' WHERE `asset_id` = 'DT-00029';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-14' WHERE `asset_id` = 'DT-00029' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00029', 'MARSENI', '2026-01-14', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00030 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ZULKIFLI' WHERE `asset_id` = 'DT-00030';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-12' WHERE `asset_id` = 'DT-00030' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00030', 'ZULKIFLI', '2026-01-12', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00031 (Dump Truck - Hino Ranger FM 260 JD)
UPDATE `assets` SET `current_operator_name` = 'IGNASIUS GS LUMBAN TOBING' WHERE `asset_id` = 'DT-00031';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-30' WHERE `asset_id` = 'DT-00031' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00031', 'IGNASIUS GS LUMBAN TOBING', '2025-12-30', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 6x)', NOW());

-- Asset: DT-00032 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'DEDY HALOMOAN' WHERE `asset_id` = 'DT-00032';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-13' WHERE `asset_id` = 'DT-00032' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00032', 'DEDY HALOMOAN', '2026-01-13', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00034 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'AGUS DEDI' WHERE `asset_id` = 'DT-00034';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'DT-00034' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00034', 'AGUS DEDI', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00035 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RICHO SAMUEL dt vendor' WHERE `asset_id` = 'DT-00035';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-29' WHERE `asset_id` = 'DT-00035' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00035', 'RICHO SAMUEL dt vendor', '2025-12-29', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00037 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'HARTONO' WHERE `asset_id` = 'DT-00037';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-23' WHERE `asset_id` = 'DT-00037' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00037', 'HARTONO', '2025-12-23', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00038 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'KHAIZUL' WHERE `asset_id` = 'DT-00038';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-10' WHERE `asset_id` = 'DT-00038' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00038', 'KHAIZUL', '2026-01-10', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00039 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SUPRIYANTO' WHERE `asset_id` = 'DT-00039';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-15' WHERE `asset_id` = 'DT-00039' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00039', 'SUPRIYANTO', '2026-01-15', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00040 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ZAHAR' WHERE `asset_id` = 'DT-00040';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-31' WHERE `asset_id` = 'DT-00040' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00040', 'ZAHAR', '2025-12-31', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00041 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'JOKO SATRIO' WHERE `asset_id` = 'DT-00041';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-00041' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00041', 'JOKO SATRIO', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00042 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'DODI KANDRAYANI' WHERE `asset_id` = 'DT-00042';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-00042' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00042', 'DODI KANDRAYANI', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00043 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'JANTO MAROLOAN TUA P.' WHERE `asset_id` = 'DT-00043';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-09' WHERE `asset_id` = 'DT-00043' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00043', 'JANTO MAROLOAN TUA P.', '2026-01-09', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00044 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'JONSON' WHERE `asset_id` = 'DT-00044';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-00044' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00044', 'JONSON', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00044', 'JONSON RWI', '2026-01-01', '2026-01-20', 'Rotasi / Shift Cadangan', 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026', NOW());

-- Asset: DT-00045 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'WAHYU PURNOMO' WHERE `asset_id` = 'DT-00045';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-00045' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00045', 'WAHYU PURNOMO', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00046 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RIA IRAWAN' WHERE `asset_id` = 'DT-00046';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-18' WHERE `asset_id` = 'DT-00046' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00046', 'RIA IRAWAN', '2025-12-18', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00047 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SUPRIYANTO HASIBUAN' WHERE `asset_id` = 'DT-00047';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00047' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00047', 'SUPRIYANTO HASIBUAN', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00048 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'AFRYANDI' WHERE `asset_id` = 'DT-00048';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-07' WHERE `asset_id` = 'DT-00048' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00048', 'AFRYANDI', '2026-01-07', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-00049 (Dump Truck - Hino Ranger FM 280 JD)
UPDATE `assets` SET `current_operator_name` = 'SOFYAN' WHERE `asset_id` = 'DT-00049';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-15' WHERE `asset_id` = 'DT-00049' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00049', 'SOFYAN', '2026-01-15', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-00050 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SOBIRIN' WHERE `asset_id` = 'DT-00050';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-00050' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00050', 'SOBIRIN', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 6x)', NOW());

-- Asset: DT-00052 (Dump Truck - Hino Ranger FM 260 JD)
UPDATE `assets` SET `current_operator_name` = 'SUPRIYADI SAGALA' WHERE `asset_id` = 'DT-00052';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-22' WHERE `asset_id` = 'DT-00052' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00052', 'SUPRIYADI SAGALA', '2025-12-22', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 8x)', NOW());

-- Asset: DT-00053 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'Ahmad Rivaldi P' WHERE `asset_id` = 'DT-00053';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-23' WHERE `asset_id` = 'DT-00053' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00053', 'Ahmad Rivaldi P', '2025-12-23', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00054 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SAHRUL' WHERE `asset_id` = 'DT-00054';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-23' WHERE `asset_id` = 'DT-00054' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00054', 'SAHRUL', '2025-12-23', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 6x)', NOW());

-- Asset: DT-00055 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'KLIWON' WHERE `asset_id` = 'DT-00055';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-30' WHERE `asset_id` = 'DT-00055' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00055', 'KLIWON', '2025-12-30', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-00056 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'OJAHAN WESLEY' WHERE `asset_id` = 'DT-00056';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-00056' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00056', 'OJAHAN WESLEY', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 7x)', NOW());

-- Asset: DT-00057 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'MUSANI FADLI' WHERE `asset_id` = 'DT-00057';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-27' WHERE `asset_id` = 'DT-00057' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00057', 'MUSANI FADLI', '2025-12-27', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 8x)', NOW());

-- Asset: DT-00058 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SUWARDI' WHERE `asset_id` = 'DT-00058';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-26' WHERE `asset_id` = 'DT-00058' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00058', 'SUWARDI', '2025-12-26', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00058', 'SUWARDI MATING BOARD', '2025-12-26', '2025-12-26', 'Rotasi / Shift Cadangan', 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026', NOW());

-- Asset: DT-00059 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'AHMAD NIRWANSYAH' WHERE `asset_id` = 'DT-00059';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-16' WHERE `asset_id` = 'DT-00059' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00059', 'AHMAD NIRWANSYAH', '2025-12-16', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00061 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'CHANDRA BUDI' WHERE `asset_id` = 'DT-00061';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-25' WHERE `asset_id` = 'DT-00061' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00061', 'CHANDRA BUDI', '2025-12-25', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 5x)', NOW());

-- Asset: DT-00062 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ARFIANSYAH' WHERE `asset_id` = 'DT-00062';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-00062' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00062', 'ARFIANSYAH', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00063 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RICO SAPUTRA' WHERE `asset_id` = 'DT-00063';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-26' WHERE `asset_id` = 'DT-00063' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00063', 'RICO SAPUTRA', '2025-12-26', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-00064 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'PADLI' WHERE `asset_id` = 'DT-00064';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-18' WHERE `asset_id` = 'DT-00064' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00064', 'PADLI', '2025-12-18', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-00065 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SYAHFITRA WAHYUDI' WHERE `asset_id` = 'DT-00065';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00065' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00065', 'SYAHFITRA WAHYUDI', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00071 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'IRFAN' WHERE `asset_id` = 'DT-00071';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'DT-00071' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00071', 'IRFAN', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-00072 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'RIZKY RAHMADANDI' WHERE `asset_id` = 'DT-00072';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-22' WHERE `asset_id` = 'DT-00072' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00072', 'RIZKY RAHMADANDI', '2025-12-22', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-00073 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SUPRIANTO, J' WHERE `asset_id` = 'DT-00073';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'DT-00073' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00073', 'SUPRIANTO, J', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-00074 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ERDI MANTO PANJAITAN' WHERE `asset_id` = 'DT-00074';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'DT-00074' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-00074', 'ERDI MANTO PANJAITAN', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 6x)', NOW());

-- Asset: DT-04001 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'HERMANSYAH BATUBARA' WHERE `asset_id` = 'DT-04001';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-10' WHERE `asset_id` = 'DT-04001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04001', 'HERMANSYAH BATUBARA', '2026-01-10', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-04012 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ADE CANDRA' WHERE `asset_id` = 'DT-04012';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'DT-04012' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04012', 'ADE CANDRA', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-04024 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ROMI EFENDI' WHERE `asset_id` = 'DT-04024';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-30' WHERE `asset_id` = 'DT-04024' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04024', 'ROMI EFENDI', '2025-12-30', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 7x)', NOW());

-- Asset: DT-04025 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'SISWOYO EKO PUTRO' WHERE `asset_id` = 'DT-04025';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-30' WHERE `asset_id` = 'DT-04025' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04025', 'SISWOYO EKO PUTRO', '2025-12-30', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-04029 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'INTRA SAPUTRA' WHERE `asset_id` = 'DT-04029';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-20' WHERE `asset_id` = 'DT-04029' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04029', 'INTRA SAPUTRA', '2025-12-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DT-04030 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'M. ADE SATRIA' WHERE `asset_id` = 'DT-04030';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-04030' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04030', 'M. ADE SATRIA', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: DT-04032 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'HERMAN SYAHPUTRA' WHERE `asset_id` = 'DT-04032';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-22' WHERE `asset_id` = 'DT-04032' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04032', 'HERMAN SYAHPUTRA', '2025-12-22', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04032', 'DEDY HALOMOAN', '2025-12-22', '2025-12-22', 'Rotasi / Shift Cadangan', 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026', NOW());

-- Asset: DT-04036 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'MOSES SILABAN' WHERE `asset_id` = 'DT-04036';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-27' WHERE `asset_id` = 'DT-04036' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04036', 'MOSES SILABAN', '2025-12-27', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-04037 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'M ADE SATRIA' WHERE `asset_id` = 'DT-04037';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-23' WHERE `asset_id` = 'DT-04037' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04037', 'M ADE SATRIA', '2025-12-23', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DT-04038 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'HENDRYCO FHERMY' WHERE `asset_id` = 'DT-04038';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-23' WHERE `asset_id` = 'DT-04038' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04038', 'HENDRYCO FHERMY', '2025-12-23', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: DT-04040 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'HERMANSAH' WHERE `asset_id` = 'DT-04040';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DT-04040' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04040', 'HERMANSAH', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04040', 'HERMANSYAH', '2026-01-01', '2026-01-20', 'Rotasi / Shift Cadangan', 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026', NOW());

-- Asset: DT-04042 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'M. DHANI' WHERE `asset_id` = 'DT-04042';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-27' WHERE `asset_id` = 'DT-04042' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04042', 'M. DHANI', '2025-12-27', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 6x)', NOW());

-- Asset: DT-04044 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'ARDIKA SUGAMA BARUS' WHERE `asset_id` = 'DT-04044';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-27' WHERE `asset_id` = 'DT-04044' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04044', 'ARDIKA SUGAMA BARUS', '2025-12-27', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 5x)', NOW());

-- Asset: DT-04048 (Dump Truck - Dump Truck Hino FM260JD / Mercedes Axor)
UPDATE `assets` SET `current_operator_name` = 'DIPPOS SINAGA' WHERE `asset_id` = 'DT-04048';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-31' WHERE `asset_id` = 'DT-04048' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DT-04048', 'DIPPOS SINAGA', '2025-12-31', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 5x)', NOW());

-- Asset: DZ-00001 (Bulldozer - Bulldozer Caterpillar D6G / Shantui SD16)
UPDATE `assets` SET `current_operator_name` = 'JAILANI' WHERE `asset_id` = 'DZ-00001';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-22' WHERE `asset_id` = 'DZ-00001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DZ-00001', 'JAILANI', '2025-12-22', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DZ-00002 (Bulldozer - Caterpillar D6G 2XL)
UPDATE `assets` SET `current_operator_name` = 'VERDINAN SIMANJUNTAK' WHERE `asset_id` = 'DZ-00002';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'DZ-00002' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DZ-00002', 'VERDINAN SIMANJUNTAK', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: DZ-00003 (Bulldozer - Bulldozer Caterpillar D6G / Shantui SD16)
UPDATE `assets` SET `current_operator_name` = 'AGUSTIAWAN' WHERE `asset_id` = 'DZ-00003';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-10' WHERE `asset_id` = 'DZ-00003' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DZ-00003', 'AGUSTIAWAN', '2026-01-10', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: DZ-00006 (Bulldozer - Bulldozer Caterpillar D6G / Shantui SD16)
UPDATE `assets` SET `current_operator_name` = 'PUTRA' WHERE `asset_id` = 'DZ-00006';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-13' WHERE `asset_id` = 'DZ-00006' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('DZ-00006', 'PUTRA', '2026-01-13', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: EXC-00001 (Excavator - Komatsu PC200-10M0 CE)
UPDATE `assets` SET `current_operator_name` = 'NOVRANDI GULTOM' WHERE `asset_id` = 'EXC-00001';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-31' WHERE `asset_id` = 'EXC-00001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00001', 'NOVRANDI GULTOM', '2025-12-31', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: EXC-00003 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'ADI SANTOSO' WHERE `asset_id` = 'EXC-00003';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-22' WHERE `asset_id` = 'EXC-00003' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00003', 'ADI SANTOSO', '2025-12-22', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: EXC-00004 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'HERMAN' WHERE `asset_id` = 'EXC-00004';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-29' WHERE `asset_id` = 'EXC-00004' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00004', 'HERMAN', '2025-12-29', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: EXC-00005 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'DAUD' WHERE `asset_id` = 'EXC-00005';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-02' WHERE `asset_id` = 'EXC-00005' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00005', 'DAUD', '2026-01-02', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: EXC-00007 (Excavator - )
UPDATE `assets` SET `current_operator_name` = 'Manalu (Managua)' WHERE `asset_id` = 'EXC-00007';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-22' WHERE `asset_id` = 'EXC-00007' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00007', 'Manalu (Managua)', '2025-12-22', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00007', 'UMAR SOLEH', '2025-12-22', '2025-12-22', 'Rotasi / Shift Cadangan', 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026', NOW());

-- Asset: EXC-00008 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'M. HUTAPEA' WHERE `asset_id` = 'EXC-00008';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-09' WHERE `asset_id` = 'EXC-00008' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00008', 'M. HUTAPEA', '2026-01-09', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: EXC-00009 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'INDRA' WHERE `asset_id` = 'EXC-00009';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-10' WHERE `asset_id` = 'EXC-00009' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00009', 'INDRA', '2026-01-10', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: EXC-00011 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'SAHRUL' WHERE `asset_id` = 'EXC-00011';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-14' WHERE `asset_id` = 'EXC-00011' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00011', 'SAHRUL', '2026-01-14', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: EXC-00012 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'JUNTAK' WHERE `asset_id` = 'EXC-00012';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-23' WHERE `asset_id` = 'EXC-00012' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00012', 'JUNTAK', '2025-12-23', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00012', 'HERMAN GP', '2025-12-23', '2025-12-23', 'Rotasi / Shift Cadangan', 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026', NOW());

-- Asset: EXC-00018 (Excavator - )
UPDATE `assets` SET `current_operator_name` = 'FINKY' WHERE `asset_id` = 'EXC-00018';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-13' WHERE `asset_id` = 'EXC-00018' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00018', 'FINKY', '2026-01-13', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: EXC-00019 (Excavator - )
UPDATE `assets` SET `current_operator_name` = 'NANDA HAJI SAPUTRA' WHERE `asset_id` = 'EXC-00019';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-18' WHERE `asset_id` = 'EXC-00019' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00019', 'NANDA HAJI SAPUTRA', '2026-01-18', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: EXC-00022 (Excavator - Excavator Komatsu PC200-10M0 / CAT 320)
UPDATE `assets` SET `current_operator_name` = 'SUPRIONO ROAD &' WHERE `asset_id` = 'EXC-00022';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-30' WHERE `asset_id` = 'EXC-00022' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('EXC-00022', 'SUPRIONO ROAD &', '2025-12-30', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: FBT-00003 (Trado - Trado Heavy Hauler Mitsubishi FV 419 P / Hino)
UPDATE `assets` SET `current_operator_name` = 'SUPRAYET NO' WHERE `asset_id` = 'FBT-00003';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-29' WHERE `asset_id` = 'FBT-00003' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('FBT-00003', 'SUPRAYET NO', '2025-12-29', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: MG-00005 (Motor Grader - Motor Grader XCMG GR135 MAX)
UPDATE `assets` SET `current_operator_name` = 'HERMAN' WHERE `asset_id` = 'MG-00005';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'MG-00005' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('MG-00005', 'HERMAN', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: PF-00001 (Vibro Compactor - Vibro Bomag Pad Foot BW 211D-40SL)
UPDATE `assets` SET `current_operator_name` = 'HENDI SAPIKA' WHERE `asset_id` = 'PF-00001';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'PF-00001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-00001', 'HENDI SAPIKA', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-00001', 'HENDI SEPIKA', '2025-12-24', '2025-12-24', 'Rotasi / Shift Cadangan', 'Tercatat dalam Laporan Operasional/Breakdown Divisi Equipment Januari 2026', NOW());

-- Asset: PF-00002 (Vibro Compactor - Vibro Compactor Bomag BW211D-40SL / Sakai)
UPDATE `assets` SET `current_operator_name` = 'SUPRI' WHERE `asset_id` = 'PF-00002';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-24' WHERE `asset_id` = 'PF-00002' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-00002', 'SUPRI', '2025-12-24', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: PF-00004 (Vibro Compactor - Vibro Compactor Bomag BW211D-40SL / Sakai)
UPDATE `assets` SET `current_operator_name` = 'YOPI' WHERE `asset_id` = 'PF-00004';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-29' WHERE `asset_id` = 'PF-00004' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-00004', 'YOPI', '2025-12-29', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: PF-00010 (Vibro Compactor - Vibro Compactor Bomag BW211D-40SL / Sakai)
UPDATE `assets` SET `current_operator_name` = 'HENDI SAPIKA /M. HASBI' WHERE `asset_id` = 'PF-00010';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'PF-00010' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-00010', 'HENDI SAPIKA /M. HASBI', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: PF-00012 (Vibro Compactor - Vibro Compactor Bomag BW211D-40SL / Sakai)
UPDATE `assets` SET `current_operator_name` = 'NASIPRO NABABAN' WHERE `asset_id` = 'PF-00012';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-29' WHERE `asset_id` = 'PF-00012' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-00012', 'NASIPRO NABABAN', '2025-12-29', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

-- Asset: PF-00015 (Vibro Compactor - Vibro Compactor Bomag BW211D-40SL / Sakai)
UPDATE `assets` SET `current_operator_name` = 'ALFIAN' WHERE `asset_id` = 'PF-00015';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-29' WHERE `asset_id` = 'PF-00015' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-00015', 'ALFIAN', '2025-12-29', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 4x)', NOW());

-- Asset: PF-41001 (Vibro Compactor - Vibro Compactor Bomag BW211D-40SL / Sakai)
UPDATE `assets` SET `current_operator_name` = 'RWI' WHERE `asset_id` = 'PF-41001';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-19' WHERE `asset_id` = 'PF-41001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PF-41001', 'RWI', '2026-01-19', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: PM-00004 (Prime Mover - PRIME MOVER)
UPDATE `assets` SET `current_operator_name` = 'ABDUL ARIFIN' WHERE `asset_id` = 'PM-00004';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-29' WHERE `asset_id` = 'PM-00004' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PM-00004', 'ABDUL ARIFIN', '2025-12-29', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: PM-41002 (Prime Mover - )
UPDATE `assets` SET `current_operator_name` = 'RWI' WHERE `asset_id` = 'PM-41002';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-12' WHERE `asset_id` = 'PM-41002' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('PM-41002', 'RWI', '2026-01-12', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: RM-00002 (Reclaimer Spreader - Reclaimer / Cement Spreader XCMG XLZ2303)
UPDATE `assets` SET `current_operator_name` = 'RWI' WHERE `asset_id` = 'RM-00002';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-09' WHERE `asset_id` = 'RM-00002' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('RM-00002', 'RWI', '2026-01-09', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: RM-41001 (Reclaimer Spreader - Reclaimer / Cement Spreader XCMG XLZ2303)
UPDATE `assets` SET `current_operator_name` = 'RWI' WHERE `asset_id` = 'RM-41001';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-14' WHERE `asset_id` = 'RM-41001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('RM-41001', 'RWI', '2026-01-14', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 1x)', NOW());

-- Asset: TMC-00001 (Other - Truck Mounted Crane XCMG B 9435 XFY)
UPDATE `assets` SET `current_operator_name` = 'HADI' WHERE `asset_id` = 'TMC-00001';
UPDATE `asset_operator_history` SET `end_date` = '2025-12-31' WHERE `asset_id` = 'TMC-00001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('TMC-00001', 'HADI', '2025-12-31', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 2x)', NOW());

-- Asset: WT-00001 (Water Truck - Water Truck Hino Dutro 130 HD / Isuzu)
UPDATE `assets` SET `current_operator_name` = 'GILANG FIRMANSYAH' WHERE `asset_id` = 'WT-00001';
UPDATE `asset_operator_history` SET `end_date` = '2026-01-20' WHERE `asset_id` = 'WT-00001' AND `end_date` IS NULL;
INSERT INTO `asset_operator_history` (`asset_id`, `operator_name`, `start_date`, `end_date`, `shift_type`, `handover_notes`, `created_at`)
VALUES ('WT-00001', 'GILANG FIRMANSYAH', '2026-01-20', NULL, 'Shift 1 (Utama)', 'Sumber: Laporan Divisi Equipment Januari 2026 (tercatat 3x)', NOW());

  INSERT INTO `app_data_migrations` (`migration_key`)
  VALUES ('populate_asset_pics_jan2026_v1');

  COMMIT;
END$$
DELIMITER ;

CALL `apply_asset_pics_jan2026`();
DROP PROCEDURE `apply_asset_pics_jan2026`;
