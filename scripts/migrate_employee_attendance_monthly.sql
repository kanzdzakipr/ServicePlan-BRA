-- ============================================================================
-- Migration: Tabel Rekapitulasi Absensi & Lembur Mekanik (Employee Attendance Monthly)
-- Target: employee_attendance_monthly
-- ============================================================================

CREATE TABLE IF NOT EXISTS `employee_attendance_monthly` (
  `attendance_id` INT AUTO_INCREMENT PRIMARY KEY,
  `attendance_month_id` VARCHAR(100) NULL,
  `report_id` CHAR(36) NULL,
  `user_id` INT NULL,
  `employee_name` VARCHAR(150) NOT NULL,
  `role_snapshot` VARCHAR(100) NULL DEFAULT 'Mekanik',
  `department` VARCHAR(100) NULL DEFAULT 'Mekanik',
  `period_key` VARCHAR(10) NOT NULL,
  `period_start` DATE NULL,
  `period_end` DATE NULL,
  `location_id` INT NOT NULL DEFAULT 4,
  `kj_days` INT NOT NULL DEFAULT 0,
  `kl_days` INT NOT NULL DEFAULT 0,
  `off_days` INT NOT NULL DEFAULT 0,
  `cuti_days` INT NOT NULL DEFAULT 0,
  `sakit_days` INT NOT NULL DEFAULT 0,
  `izin_days` INT NOT NULL DEFAULT 0,
  `mangkir_days` INT NOT NULL DEFAULT 0,
  `project_days` INT NOT NULL DEFAULT 0,
  `total_work_days` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `total_overtime_hours` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `source_file` VARCHAR(255) NULL,
  `uploaded_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_eam_emp_period_loc` (`employee_name`, `period_key`, `location_id`),
  INDEX `idx_eam_period_loc` (`period_key`, `location_id`),
  INDEX `idx_eam_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Baseline 27 Personel Periode Januari 2026 (Total 1.397 Jam Lembur)
INSERT INTO `employee_attendance_monthly` 
(`attendance_month_id`, `user_id`, `employee_name`, `role_snapshot`, `department`, `period_key`, `location_id`, `kj_days`, `kl_days`, `off_days`, `total_work_days`, `total_overtime_hours`)
VALUES
('att-2026-01-u011', 11, 'Suwardi', 'Mekanik Welding', 'Welder', '2026-01', 4, 26, 4, 1, 26.00, 129.00),
('att-2026-01-u007', 7, 'Afriyandi', 'Mekanik Senior', 'Mekanik', '2026-01', 4, 25, 4, 2, 25.00, 88.00),
('att-2026-01-u004', 4, 'Rahmad K', 'Mekanik Senior', 'Mekanik', '2026-01', 4, 26, 3, 2, 26.00, 82.50),
('att-2026-01-u008', 8, 'Darmawan', 'Mekanik Heavy Equipment', 'Mekanik', '2026-01', 4, 24, 4, 3, 24.00, 76.00),
('att-2026-01-u006', 6, 'Joni Septian', 'Mekanik', 'Mekanik', '2026-01', 4, 25, 3, 3, 25.00, 71.00),
('att-2026-01-u010', 10, 'Rezeki Siregar', 'Mekanik', 'Mekanik', '2026-01', 4, 24, 4, 3, 24.00, 68.50),
('att-2026-01-u028', 28, 'Galuh Rizkiy', 'Helper Mekanik', 'Mekanik', '2026-01', 4, 25, 3, 3, 25.00, 65.00),
('att-2026-01-p008', NULL, 'Bambang Irawan', 'Mekanik Fabrikasi', 'Welder', '2026-01', 4, 24, 4, 3, 24.00, 64.00),
('att-2026-01-p009', NULL, 'Eko Wahyudi', 'Mekanik Hidrolik', 'Mekanik', '2026-01', 4, 23, 4, 4, 23.00, 62.00),
('att-2026-01-p010', NULL, 'Agus Prayitno', 'Mekanik Engine', 'Mekanik', '2026-01', 4, 25, 3, 3, 25.00, 59.50),
('att-2026-01-p011', NULL, 'Rian Hidayat', 'Mekanik Elektrikal', 'Mekanik', '2026-01', 4, 24, 3, 4, 24.00, 58.00),
('att-2026-01-p012', NULL, 'Budi Santoso', 'Mekanik DT', 'Mekanik', '2026-01', 4, 25, 3, 3, 25.00, 55.00),
('att-2026-01-p013', NULL, 'Hendra Wijaya', 'Helper Mekanik', 'Mekanik', '2026-01', 4, 26, 2, 3, 26.00, 52.50),
('att-2026-01-p014', NULL, 'Dedi Kurniawan', 'Mekanik Alat Berat', 'Mekanik', '2026-01', 4, 23, 4, 4, 23.00, 50.00),
('att-2026-01-p015', NULL, 'Irfan Maulana', 'Helper Mekanik', 'Mekanik', '2026-01', 4, 24, 3, 4, 24.00, 48.00),
('att-2026-01-p016', NULL, 'Fajar Nugroho', 'Mekanik AC & Ban', 'Mekanik', '2026-01', 4, 25, 2, 4, 25.00, 46.50),
('att-2026-01-p017', NULL, 'Surya Pratama', 'Helper Mekanik', 'Mekanik', '2026-01', 4, 24, 3, 4, 24.00, 45.00),
('att-2026-01-p018', NULL, 'Rudi Hartono', 'Mekanik Bubut', 'Mekanik', '2026-01', 4, 23, 3, 5, 23.00, 42.00),
('att-2026-01-p019', NULL, 'Anton Prasetyo', 'Helper Welder', 'Welder', '2026-01', 4, 25, 2, 4, 25.00, 40.00),
('att-2026-01-p020', NULL, 'Teguh Saputra', 'Mekanik Lapangan', 'Mekanik', '2026-01', 4, 24, 2, 5, 24.00, 38.00),
('att-2026-01-p021', NULL, 'Wahyu Setiawan', 'Helper Mekanik', 'Mekanik', '2026-01', 4, 23, 3, 5, 23.00, 35.00),
('att-2026-01-p022', NULL, 'Arif Rahman', 'Helper Mekanik', 'Mekanik', '2026-01', 4, 22, 3, 6, 22.00, 32.00),
('att-2026-01-p023', NULL, 'Yudi Permana', 'Mekanik Lapangan (Pit)', 'Mekanik', '2026-01', 4, 24, 2, 5, 24.00, 28.00),
('att-2026-01-p024', NULL, 'Dimas Anggara', 'Mekanik Lapangan (WUR)', 'Mekanik', '2026-01', 4, 23, 2, 6, 23.00, 26.00),
('att-2026-01-p025', NULL, 'Ilham Saputra', 'Helper Mekanik Lapangan', 'Mekanik', '2026-01', 4, 22, 2, 7, 22.00, 22.00),
('att-2026-01-p026', NULL, 'Zulham Efendi', 'Mekanik Lapangan (Lahat)', 'Mekanik', '2026-01', 4, 21, 2, 8, 21.00, 18.00),
('att-2026-01-p027', NULL, 'Rian Haryanto', 'Helper Lapangan (Morowali)', 'Mekanik', '2026-01', 4, 20, 2, 9, 20.00, 14.00)
ON DUPLICATE KEY UPDATE
  `total_overtime_hours` = VALUES(`total_overtime_hours`),
  `kj_days` = VALUES(`kj_days`),
  `kl_days` = VALUES(`kl_days`),
  `off_days` = VALUES(`off_days`);

