-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 07, 2026 at 02:33 PM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u646470441_ServicePlanBRA`
--

-- --------------------------------------------------------

--
-- Table structure for table `accidents`
--

CREATE TABLE `accidents` (
  `accident_id` varchar(50) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `incident_datetime` timestamp NULL DEFAULT current_timestamp(),
  `location_id` int(11) DEFAULT NULL,
  `operator_name` varchar(100) DEFAULT NULL,
  `operator_tenure` varchar(50) DEFAULT NULL,
  `chronology` text NOT NULL,
  `weather` varchar(50) DEFAULT NULL,
  `road_condition` varchar(50) DEFAULT NULL,
  `lighting_condition` varchar(50) DEFAULT NULL,
  `severity` enum('Minor','Moderate','Critical') DEFAULT 'Minor',
  `physical_damage` text DEFAULT NULL,
  `estimated_repair_cost` decimal(15,2) DEFAULT 0.00,
  `estimated_downtime_days` int(11) DEFAULT 0,
  `total_financial_loss` decimal(15,2) DEFAULT 0.00,
  `cause_factors_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cause_factors_json`)),
  `corrective_action` text DEFAULT NULL,
  `preventive_action` text DEFAULT NULL,
  `is_unit_locked` tinyint(1) DEFAULT 1,
  `status` enum('Reported','Investigating','CAPA_Pending','Closed') DEFAULT 'Reported'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accidents`
--

INSERT INTO `accidents` (`accident_id`, `asset_id`, `report_date`, `incident_datetime`, `location_id`, `operator_name`, `operator_tenure`, `chronology`, `weather`, `road_condition`, `lighting_condition`, `severity`, `physical_damage`, `estimated_repair_cost`, `estimated_downtime_days`, `total_financial_loss`, `cause_factors_json`, `corrective_action`, `preventive_action`, `is_unit_locked`, `status`) VALUES
('01/ACC/BRA/DURI/2026', 'CS-41001', '2026-05-14', '2026-05-11 14:30:00', 6, 'M. Fajar DC', '< 1 bulan (Unit Baru)', 'Pada tanggal 11 Mei 2026 pukul 14.30 WIB, unit sedang melakukan pengisian material powder binder/cement di area pengisian. Terjadi Error System Penaburan cement yang disebabkan pada saat pengisian cement operator tidak menyalakan tombol pada monitor sehingga sistem error.', 'Cerah', 'Kering / Rata', 'Siang Hari (Terang)', 'Moderate', 'Error System Penaburan & Valve Blockage', 15500000.00, 3, 28000000.00, NULL, 'Unit dihentikan operasional (ACCIDENT_HOLD). Sebagian cement dibongkar dan diisi ulang oleh teknisi XCMG.', 'Refresher training operator penabur semen, update SOP serah terima unit baru.', 1, 'CAPA_Pending');

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `approval_id` int(11) NOT NULL,
  `document_type` enum('SPB','WO','ACCIDENT_RELEASE','MUTASI','SPL') NOT NULL,
  `document_id` varchar(50) NOT NULL,
  `approver_user_id` int(11) NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `rejection_reason` text DEFAULT NULL,
  `action_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `asset_id` varchar(100) NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `license_plate` varchar(50) DEFAULT NULL,
  `previous_license_plate` varchar(50) DEFAULT NULL,
  `alias_name` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Heavy Equipment',
  `category` enum('Excavator','Bulldozer','Dump Truck','Motor Grader','Vibro Compactor','Water Truck','Prime Mover','Light Vehicle','Reclaimer Spreader','Trado','Other') NOT NULL,
  `make_model` varchar(100) DEFAULT NULL,
  `sub_group_branch` varchar(100) DEFAULT NULL,
  `year_manufacture` int(11) DEFAULT NULL,
  `ownership` enum('Milik Sendiri','Sewa','Leasing') DEFAULT 'Milik Sendiri',
  `status` enum('READY','OPERATING','STANDBY','INSPEKSI','BREAKDOWN','ACCIDENT_HOLD','INACTIVE') DEFAULT 'READY',
  `current_location_id` int(11) DEFAULT NULL,
  `raw_location_notes` text DEFAULT NULL,
  `last_hm_km` decimal(10,2) DEFAULT 0.00,
  `last_latitude` decimal(10,7) DEFAULT NULL,
  `last_longitude` decimal(10,7) DEFAULT NULL,
  `gps_updated_at` timestamp NULL DEFAULT NULL,
  `telematics_last_comm` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_update_timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`asset_id`, `asset_code`, `serial_number`, `license_plate`, `previous_license_plate`, `alias_name`, `type`, `category`, `make_model`, `sub_group_branch`, `year_manufacture`, `ownership`, `status`, `current_location_id`, `raw_location_notes`, `last_hm_km`, `last_latitude`, `last_longitude`, `gps_updated_at`, `telematics_last_comm`, `is_active`, `created_at`, `last_update_timestamp`) VALUES
('BM 8441 NU ( XCMG Double Jack )', 'BM', NULL, 'BM 8441 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('BM 8452 NU', 'BM', NULL, 'BM 8452 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bomag Pad Foot Compactor BW211D-40', 'Bomag', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bomag Smooth Drum BW211D-40', 'Bomag', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bomag Smooth Drum Compactor BW211D-40 SL', 'Bomag', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bomag Smooth Drum Compactor BW211D-40SL', 'Bomag', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('BRA-03 - B 9096 ZYT', 'BRA-03', NULL, 'B 9096 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bulldozer Caterpillar D6G', 'Bulldozer', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bulldozer Caterpillar D6G 2XL', 'Bulldozer', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bulldozer Caterpillar D6R', 'Bulldozer', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bulldozer Caterpillar D7G2', 'Bulldozer', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Bulldozer Komatsu D85ESS-2', 'Bulldozer', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('CAT RM-500B', 'CAT', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('CC-00001 - ZCC600V', 'CC-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 36, 'Well Pad 2H-013A', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('CS-01 CEMENT SPREADER', 'CS-01', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('CS-41001', 'CS-41001', 'XK185-001', 'BM 9012 RWI', NULL, NULL, 'Heavy Equipment', 'Other', 'Powder Binder Spreader XCMG XKC185', 'PKB PEKANBARU Branch', 2026, 'Milik Sendiri', 'ACCIDENT_HOLD', 6, NULL, 150.00, 1.2991500, 101.2311500, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('DOZER-BRA 05', 'DOZER-BRA', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT 0008 - B 9137 ZYT', 'DT', NULL, 'B 9137 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT 0057 - B 9914 ZYT', 'DT', NULL, 'B 9914 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00001', 'DT-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00003 - B 9109 ZYT', 'DT-00003', NULL, 'B 9109 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00004 - B 9112 ZYT', 'DT-00004', NULL, 'B 9112 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00005 - B9113ZYT', 'DT-00005', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 177, 'Site Celcin', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00006 - B9118ZYT', 'DT-00006', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 177, 'Site Celcin', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00008 - B 9115 ZYT', 'DT-00008', NULL, 'B 9115 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 177, 'Site Celcin', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00009 - B 9116 ZYT', 'DT-00009', NULL, 'B 9116 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00010 - B9117ZYT', 'DT-00010', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00011 - B 9120 ZYT', 'DT-00011', NULL, 'B 9120 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00012 - B 9121 ZYT', 'DT-00012', NULL, 'B 9121 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00013 - B 9122 ZYT', 'DT-00013', NULL, 'B 9122 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00014 - B 9123 ZYT', 'DT-00014', NULL, 'B 9123 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00015 - B 9124 ZYT', 'DT-00015', NULL, 'B 9124 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00016 - B 9125 ZYT', 'DT-00016', NULL, 'B 9125 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00017 - B 9126 ZYT', 'DT-00017', NULL, 'B 9126 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00019 - B 9128 ZYT', 'DT-00019', NULL, 'B 9128 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00021 - B 9130 ZYT', 'DT-00021', NULL, 'B 9130 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00022 - B 9131 ZYT', 'DT-00022', NULL, 'B 9131 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00023 - B9132ZYT', 'DT-00023', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00024 - B9133ZYT', 'DT-00024', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 11, 'Minas Field Project', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00025 - B 9134 ZYT', 'DT-00025', NULL, 'B 9134 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00026 - B9135ZYT', 'DT-00026', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00027 - B 9136 ZYT', 'DT-00027', NULL, 'B 9136 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00028 - B9137ZYT', 'DT-00028', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00029 - B 9138 ZYT', 'DT-00029', NULL, 'B 9138 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00030 - B 9139 ZYT', 'DT-00030', NULL, 'B 9139 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00031', 'DT-00031', NULL, 'BG8976IX', NULL, NULL, 'Heavy Equipment', 'Dump Truck', 'Hino Ranger FM 260 JD', 'PKB PEKANBARU Branch', 2019, 'Milik Sendiri', 'STANDBY', 1, NULL, 77131.00, 1.2854500, 101.2185600, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('DT-00031 - BG 8535 OW', 'DT-00031', NULL, 'BG 8535 OW', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 164, 'Site NDD', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00032', 'DT-00032', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00032 - BG8976IX', 'DT-00032', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00034', 'DT-00034', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 64, 'Head Office Pekanbaru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00035 - BM 8621 QU', 'DT-00035', NULL, 'BM 8621 QU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00037 - B 9139 ZYT', 'DT-00037', NULL, 'B 9139 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00038 - B9079ZYT', 'DT-00038', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00039-B9080ZYT', 'DT-00039-B9080ZYT', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00040 - B 9081 ZYT', 'DT-00040', NULL, 'B 9081 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00040-- B9081ZYT', 'DT-00040--', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00041-B9082ZYT', 'DT-00041-B9082ZYT', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00042 - B 9089 ZYT', 'DT-00042', NULL, 'B 9089 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00043 - B9091ZYT', 'DT-00043', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 11, 'Minas Field Project', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00044 - B9092ZYT', 'DT-00044', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00045-B9093ZYT', 'DT-00045-B9093ZYT', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00046 -B 9094 ZYT', 'DT-00046', NULL, 'B 9094 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00047 - B 9102 ZYT', 'DT-00047', NULL, 'B 9102 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00048-B 9103 ZYT', 'DT-00048-B', NULL, 'B 9103 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00049 - B 9104 ZYT', 'DT-00049', NULL, 'B 9104 ZYT', NULL, NULL, 'Heavy Equipment', 'Dump Truck', 'Hino Ranger FM 280 JD', 'PKB PEKANBARU Branch', 2024, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 34500.00, 1.2789000, 101.2112000, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:42:27'),
('DT-00050 - B 9105 ZYT', 'DT-00050', NULL, 'B 9105 ZYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'BREAKDOWN', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00052', 'DT-00052', NULL, 'B 9642 KYW', NULL, NULL, 'Heavy Equipment', 'Dump Truck', 'Hino Ranger FM 260 JD', 'PKB PEKANBARU Branch', 2018, 'Milik Sendiri', 'STANDBY', 1, NULL, 38146.00, 1.2854600, 101.2185700, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('DT-00052 - B 9642 KYW', 'DT-00052', NULL, 'B 9642 KYW', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00053 - B 9644 KYW', 'DT-00053', NULL, 'B 9644 KYW', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00054 - B 9683 KYW', 'DT-00054', NULL, 'B 9683 KYW', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00055 - B 9701 PYW', 'DT-00055', NULL, 'B 9701 PYW', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00056 - B 9892 PYW', 'DT-00056', NULL, 'B 9892 PYW', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00057 - B 9914 JYT', 'DT-00057', NULL, 'B 9914 JYT', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'BREAKDOWN', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00058 - B 9973 BIS', 'DT-00058', NULL, 'B 9973 BIS', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00059 - BG 8163 NJ', 'DT-00059', NULL, 'BG 8163 NJ', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00060 - BG 8230 MX', 'DT-00060', NULL, 'BG 8230 MX', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00061 - BG 8367 IJ', 'DT-00061', NULL, 'BG 8367 IJ', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00062 - BG8638NI', 'DT-00062', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00063 - BG 8639 NI', 'DT-00063', NULL, 'BG 8639 NI', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00064 - BG 8640 NI', 'DT-00064', NULL, 'BG 8640 NI', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00065-BG 8641 NI', 'DT-00065-BG', NULL, 'BG 8641 NI', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-00071 - DT 8126 JE', 'DT-00071', NULL, 'DT 8126 JE', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00072 - DT 8669 KE', 'DT-00072', NULL, 'DT 8669 KE', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00073 - DT8973IE', 'DT-00073', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-00074 - Z 9109 AB', 'DT-00074', NULL, 'Z 9109 AB', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04001 - BM 9174 NU', 'DT-04001', NULL, 'BM 9174 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04001-BM 9174 NU', 'DT-04001-BM', NULL, 'BM 9174 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04012 - BM 9944 JO', 'DT-04012', NULL, 'BM 9944 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04024 - BM 9285 JO', 'DT-04024', NULL, 'BM 9285 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04025 - BM 9398 JO', 'DT-04025', NULL, 'BM 9398 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04029 - BM9819QO', 'DT-04029', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04030 - BM 9510 QO', 'DT-04030', NULL, 'BM 9510 QO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04032', 'DT-04032', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04032 - BG8976IX', 'DT-04032', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'BREAKDOWN', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04036 - BM 9949 JO', 'DT-04036', NULL, 'BM 9949 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04037 - BM 9666 JO', 'DT-04037', NULL, 'BM 9666 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04038 - BM 9296 JO', 'DT-04038', NULL, 'BM 9296 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04040 - BM 9509 QO', 'DT-04040', NULL, 'BM 9509 QO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-04042 - BM 9287 JO', 'DT-04042', NULL, 'BM 9287 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04044 - BM9244NU', 'DT-04044', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-04048', 'DT-04048', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04048 - BM 9951 JO', 'DT-04048', NULL, 'BM 9951 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-04053 - BM 9824 QO', 'DT-04053', NULL, 'BM 9824 QO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DT-04054 - BM9291JO DT-41001', 'DT-04054', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04055 - BM9503QO DT-41002', 'DT-04055', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04056 DT-41003 - BM 9956 JO', 'DT-04056', NULL, 'BM 9956 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04057 - BM 9289 JO DT-41004', 'DT-04057', NULL, 'BM 9289 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-04058 - BM 9678 JO - DT-41005', 'DT-04058', NULL, 'BM 9678 JO', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-41001 - BM9291JO', 'DT-41001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-41002 - BM9503QO', 'DT-41002', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-41003 - BM9956JO', 'DT-41003', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-41004 - BM9289JO', 'DT-41004', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DT-41005 - BM9678JO', 'DT-41005', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Dump Truck FUSO FN62F HD', 'Dump', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Dump Truck HINO RANGER-FM 260 JD', 'Dump', NULL, 'FM 260 JD', NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Dump Truck HINO RANGER-FM 280 JD', 'Dump', NULL, 'FM 280 JD', NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Dump Truck QUESTER', 'Dump', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Dumptruck FUSO FM 517 HS', 'Dumptruck', NULL, 'FM 517 HS', NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Dumptruck Isuzu NMR 81', 'Dumptruck', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DZ-00001', 'DZ-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 40, 'Well Pad 3Q-19B', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('DZ-00002 SN P6G01656', 'DZ-00002', 'P6G01656', NULL, NULL, NULL, 'Heavy Equipment', 'Bulldozer', 'Caterpillar D6G 2XL', 'PKB PEKANBARU Branch', 2024, 'Milik Sendiri', 'INSPEKSI', 11, 'Minas Field Project', 2177.00, 1.3124500, 101.2451200, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:42:27'),
('DZ-00003 - BRA-05', 'DZ-00003', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'BREAKDOWN', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('DZ-00006 - SHANTUI', 'DZ-00006', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 164, 'Site NDD', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EX-00001', 'EX-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EX-00003 - C51503', 'EX-00003', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('EX-00004 - DBCH 0366', 'EX-00004', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('EX-00005-SYW01364', 'EX-00005-SYW01364', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EX-00007', 'EX-00007', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 59, 'Site Bangko', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EX-00007-C51502', 'EX-00007-C51502', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 177, 'Site Celcin', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EX-00008 - C01531', 'EX-00008', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 11, 'Minas Field Project', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('EX-00009 - DBCH2941', 'EX-00009', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 240, 'Well Pad 2H-1010A/', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EX-00011', 'EX-00011', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 164, 'Site NDD', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('EX-00012 - DBCH1801', 'EX-00012', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 365, 'Well Pad 4P-48C', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('EX-00012- PC 200', 'EX-00012-', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 43, 'Well Pad 4Q-32B', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EX-22 - EX-04001 - DBCH1801', 'EX-22', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('EXC-00001', 'EXC-00001', 'C51502', NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', 'Komatsu PC200-10M0 CE', 'PKB PEKANBARU Branch', 2024, 'Milik Sendiri', 'OPERATING', 6, NULL, 2443.00, 1.2991000, 101.2311000, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('Excavator Caterpillar 305.5E2', 'Excavator', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Excavator Caterpillar 320 GC', 'Excavator', NULL, 'ar 320 GC', NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Excavator Caterpillar 320D S/N', 'Excavator', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Excavator PC 210-10M0/P1', 'Excavator', NULL, NULL, NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('FBT 31002. - FLAT BED BM 8443 NU', 'FBT', NULL, 'BM 8443 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('FBT 31004. - BM 8451 NU', 'FBT', NULL, 'BM 8451 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('FBT-31001 - BM 8442 NU', 'FBT-31001', NULL, 'BM 8442 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('FLAT BED B 9733 XPZ', 'FLAT', NULL, 'B 9733 XPZ', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('FT-00001 - BA9016QU', 'FT-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('FUSO TMC - XCMG B 9435 XFY', 'FUSO', NULL, 'B 9435 XFY', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('HE-6.001 Exca mini', 'HE-6.001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 64, 'Head Office Pekanbaru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('HE-6.004 Exca mini', 'HE-6.004', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('HILUX - B 9123 ZBA', 'HILUX', NULL, 'B 9123 ZBA', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('LB-00001 - PM-00001', 'LB-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('LB-00003', 'LB-00003', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Lowboy PM-00003 - KT 9287 KU', 'Lowboy', NULL, 'KT 9287 KU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('MB-00006 -- BM 7680 TU', 'MB-00006', NULL, 'BM 7680 TU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'BREAKDOWN', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('MG-00004', 'MG-00004', 'GR135-99', NULL, NULL, NULL, 'Heavy Equipment', 'Motor Grader', 'Motor Grader XCMG GR135 MAX', 'PKB PEKANBARU Branch', 2025, 'Milik Sendiri', 'STANDBY', 1, NULL, 2446.10, 1.2855000, 101.2186000, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('MG-00004 SN:PRD800108', 'MG-00004', 'PRD800108', NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('MG-00005', 'MG-00005', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('ML-41001 - BRA-03', 'ML-41001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('Padfoot XCMG XS205PD', 'Padfoot', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PF-00001', 'PF-00001', '961884301016', NULL, NULL, NULL, 'Heavy Equipment', 'Vibro Compactor', 'Vibro Bomag Pad Foot BW 211D-40SL', 'PKB PEKANBARU Branch', 2024, 'Milik Sendiri', 'INSPEKSI', 1, NULL, 1894.20, 1.2854300, 101.2185400, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('PF-00001-961582391717', 'PF-00001-961582391717', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 193, 'Well Pad 4P-81B', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PF-00003 SN : 961582391152', 'PF-00003', '961582391152', NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 11, 'Minas Field Project', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PF-00004 - 961884301614', 'PF-00004', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 164, 'Site NDD', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('PF-0001 - BW - 211D - 40', 'PF-0001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 60, 'Well Pad 3Q-19C', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PF-0002', 'PF-0002', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 62, 'Well Pad 4Q-21E', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PF-41001 - Pad foot Vibro Sakai', 'PF-41001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PM-00001', 'PM-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PM-00003 - KT 9287 KU', 'PM-00003', NULL, 'KT 9287 KU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PM-0001 - B 9012 ZEH', 'PM-0001', NULL, 'B 9012 ZEH', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('PM-41001 - BM8371CU', 'PM-41001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('RM-02 -RM-500', 'RM-02', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('RM-41001 - XUG2303KJRDQ00021', 'RM-41001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('SD-00001 - BW 211D - 40', 'SD-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('SD-00002 - 961582391716', 'SD-00002', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 164, 'Site NDD', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('SD-00004 -', 'SD-00004', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 104, 'Well Pad 3Q-19A', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('SD-00004 - 961884301945', 'SD-00004', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 231, 'Well Pad 4P-81P', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('SD-00005 - 961582391661', 'SD-00005', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 11, 'Minas Field Project', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('SD-41001 -', 'SD-41001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('SL-01', 'SL-01', 'BK 8143 XE', NULL, NULL, NULL, 'Heavy Equipment', 'Trado', 'Mitsubishi FV 419 P', 'PLB PALEMBANG Branch', 2006, 'Milik Sendiri', 'STANDBY', 2, NULL, 98230.00, -3.4354200, 104.2384500, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('THRILLER - BM 8371 cu', 'THRILLER', NULL, 'BM 8371 cu', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('TMC 31001 - BM 8440 NU', 'TMC', NULL, 'BM 8440 NU', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('TMC B 9430 XFY', 'TMC', NULL, 'B 9430 XFY', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('TMC-00001 - B 9008 ZIN', 'TMC-00001', NULL, 'B 9008 ZIN', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('TRADO HINO RANGER-FM 260 JD', 'TRADO', NULL, 'FM 260 JD', NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('TRADO MITSUBISHI FV 419 P', 'TRADO', NULL, 'FV 419 P', NULL, NULL, 'Heavy Equipment', '', NULL, NULL, NULL, 'Milik Sendiri', 'STANDBY', 2, 'Yard Prabumulih', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('VIBRO BW BRA-01', 'BRA-01', '961582391008', NULL, NULL, NULL, 'Heavy Equipment', 'Vibro Compactor', 'Bomag Smooth Drum BW211D-40 SL', 'PKB PEKANBARU Branch', 2020, 'Milik Sendiri', 'STANDBY', 1, NULL, 6365.00, 1.2854100, 101.2185200, '2026-08-04 23:40:37', NULL, 1, '2026-08-04 23:40:37', '2026-08-04 23:40:37'),
('WL-31001 - AS5512759', 'WL-31001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('WT-00001', 'WT-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'READY', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24'),
('WT-00001 - B9018KFA', 'WT-00001', NULL, NULL, NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'BREAKDOWN', 3, 'Borrow Pit Harapan Baru', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:27'),
('XCMG-B 9435 XFY', 'XCMG-B', NULL, 'B 9435 XFY', NULL, NULL, 'Heavy Equipment', 'Excavator', NULL, NULL, NULL, 'Milik Sendiri', 'INSPEKSI', 12, 'Yard KM 12 Duri', 0.00, NULL, NULL, NULL, NULL, 1, '2026-08-04 23:42:24', '2026-08-04 23:42:24');

-- --------------------------------------------------------

--
-- Table structure for table `asset_movements`
--

CREATE TABLE `asset_movements` (
  `movement_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `from_location_id` int(11) DEFAULT NULL,
  `to_location_id` int(11) NOT NULL,
  `bast_number` varchar(100) DEFAULT NULL,
  `movement_date` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `battery_logs`
--

CREATE TABLE `battery_logs` (
  `battery_log_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `battery_brand` varchar(100) DEFAULT NULL,
  `voltage_val` decimal(4,2) NOT NULL,
  `water_level` enum('GOOD','LOW','REFILL_REQUIRED') DEFAULT 'GOOD',
  `installation_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cost_financial_monthly`
--

CREATE TABLE `cost_financial_monthly` (
  `cost_id` int(11) NOT NULL,
  `month_label` varchar(20) NOT NULL,
  `year_period` int(11) NOT NULL DEFAULT 2026,
  `budget_amount` decimal(15,2) NOT NULL,
  `actual_amount` decimal(15,2) NOT NULL,
  `variance_amount` decimal(15,2) GENERATED ALWAYS AS (`budget_amount` - `actual_amount`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cost_financial_monthly`
--

INSERT INTO `cost_financial_monthly` (`cost_id`, `month_label`, `year_period`, `budget_amount`, `actual_amount`) VALUES
(1, 'Mei', 2026, 450000000.00, 420000000.00),
(2, 'Jun', 2026, 480000000.00, 510000000.00),
(3, 'Jul', 2026, 500000000.00, 490000000.00),
(4, 'Agu', 2026, 520000000.00, 470000000.00),
(5, 'Mei', 2026, 330000000.00, 393758107.00),
(6, 'Jun', 2026, 330000000.00, 553477901.00),
(7, 'Jul', 2026, 430000000.00, 5425098935.00),
(8, 'Agu', 2026, 330000000.00, 439682327.00),
(9, 'Sep', 2026, 330000000.00, 264006655.00),
(10, 'Okt', 2026, 330000000.00, 321656974.00),
(11, 'Nov', 2026, 330000000.00, 4297177288.00),
(12, 'Des', 2026, 550000000.00, 449174956.00),
(13, 'Mei', 2026, 330000000.00, 393758107.00),
(14, 'Jun', 2026, 330000000.00, 553477901.00),
(15, 'Jul', 2026, 430000000.00, 5425098935.00),
(16, 'Agu', 2026, 330000000.00, 439682327.00),
(17, 'Sep', 2026, 330000000.00, 264006655.00),
(18, 'Okt', 2026, 330000000.00, 321656974.00),
(19, 'Nov', 2026, 330000000.00, 4297177288.00),
(20, 'Des', 2026, 550000000.00, 449174956.00);

-- --------------------------------------------------------

--
-- Table structure for table `cutting_bit_logs`
--

CREATE TABLE `cutting_bit_logs` (
  `bit_log_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `bit_installed_qty` int(11) DEFAULT 0,
  `bit_broken_qty` int(11) DEFAULT 0,
  `replaced_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_logs`
--

CREATE TABLE `fuel_logs` (
  `fuel_log_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `refuel_date` timestamp NULL DEFAULT current_timestamp(),
  `flowmeter_start` decimal(10,2) NOT NULL,
  `flowmeter_end` decimal(10,2) NOT NULL,
  `liters_issued` decimal(10,2) NOT NULL,
  `current_hm_km` decimal(10,2) NOT NULL,
  `calculated_lph` decimal(6,2) DEFAULT NULL,
  `baseline_lph` decimal(6,2) DEFAULT NULL,
  `is_anomaly` tinyint(1) DEFAULT 0,
  `driver_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuel_logs`
--

INSERT INTO `fuel_logs` (`fuel_log_id`, `asset_id`, `refuel_date`, `flowmeter_start`, `flowmeter_end`, `liters_issued`, `current_hm_km`, `calculated_lph`, `baseline_lph`, `is_anomaly`, `driver_name`) VALUES
(1, 'EXC-00001', '2026-08-05 00:40:27', 8431.00, 8450.00, 361.00, 8450.00, 19.00, 15.00, 1, 'Budi Santoso'),
(2, 'DT-00049 - B 9104 ZYT', '2026-08-05 00:40:27', 45200.00, 45760.00, 200.00, 45760.00, 2.80, 3.50, 1, 'Rahmat Hidayat'),
(3, 'MG-00004', '2026-08-05 00:40:27', 3120.00, 3130.00, 120.00, 3130.00, 12.00, 13.00, 0, 'Supriadi');

-- --------------------------------------------------------

--
-- Table structure for table `head_kpi_assessments`
--

CREATE TABLE `head_kpi_assessments` (
  `kpi_id` int(11) NOT NULL,
  `period_month` varchar(20) DEFAULT 'Januari',
  `period_year` int(11) DEFAULT 2026,
  `indicator_no` int(11) NOT NULL,
  `aspect_name` varchar(100) NOT NULL,
  `indicator_name` varchar(150) NOT NULL,
  `target_standard` varchar(100) NOT NULL,
  `score_val` int(11) NOT NULL DEFAULT 1,
  `weight_pct` decimal(5,2) NOT NULL,
  `weighted_score` decimal(5,2) GENERATED ALWAYS AS (`score_val` * `weight_pct` / 5) STORED,
  `audit_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `head_kpi_assessments`
--

INSERT INTO `head_kpi_assessments` (`kpi_id`, `period_month`, `period_year`, `indicator_no`, `aspect_name`, `indicator_name`, `target_standard`, `score_val`, `weight_pct`, `audit_notes`) VALUES
(1, 'Januari', 2026, 1, 'RTW & Downtime', '% Unit Selesai ≤ Target RTW', '≥ 90%', 5, 15.00, 'Target RTW final disepakati & lulus test function'),
(2, 'Januari', 2026, 2, 'RTW & Downtime', 'Rata-rata Downtime per Unit', '≤ Standar', 4, 20.00, 'Dump Truck ≤ 5-7 hari; Tidak ada unit >7 hari / kronis'),
(3, 'Januari', 2026, 3, 'RTW & Downtime', 'Kepatuhan PM Tepat Waktu', '≥ 95%', 4, 10.00, 'PM on-time %; Breakdown akibat PM gagal NIHIL'),
(4, 'Januari', 2026, 4, 'Percepatan', 'Waktu Respon Awal Kerusakan', '≤ 24 jam', 3, 10.00, 'Respons awal troubleshooting & JO diterbitkan'),
(5, 'Januari', 2026, 5, 'Percepatan', 'Keterlambatan karena Spare Part', '≤ 10%', 1, 10.00, 'Penurunan kerugian downtime ≥30-40%/bln'),
(6, 'Januari', 2026, 6, 'Percepatan', 'Keterlambatan karena Manpower', '≤ 5%', 1, 5.00, 'Ketersediaan mekanik, welder, dan vendor'),
(7, 'Januari', 2026, 7, 'Biaya & Kualitas', 'Deviasi Biaya Corrective', '≤ 110%', 1, 10.00, 'Realisasi biaya vs budget rencana corrective'),
(8, 'Januari', 2026, 8, 'Biaya & Kualitas', 'Repeat Breakdown ≤ 30 Hari', '≤ 5%', 1, 10.00, 'Kerusakan berulang unit/komponen yang sama'),
(9, 'Januari', 2026, 9, 'Kepemimpinan', 'Monitoring & Pelaporan Unit', 'Konsisten', 1, 5.00, 'Disiplin update JO mekanik, target RTW, & report harian'),
(10, 'Januari', 2026, 10, 'Kepemimpinan', 'Inisiatif Percepatan Perbaikan', 'Aktif', 1, 5.00, 'Solusi percepatan (shift tambahan, vendor, prioritas)');

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

CREATE TABLE `inspections` (
  `inspection_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `inspector_id` int(11) NOT NULL,
  `inspection_date` timestamp NULL DEFAULT current_timestamp(),
  `current_hm_km` decimal(10,2) NOT NULL,
  `overall_result` enum('PASS','WARNING','FAIL') DEFAULT 'PASS',
  `findings_summary` text DEFAULT NULL,
  `created_wo_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `location_type` enum('Yard','Pit','Borrow Pit','Workshop','Branch','Site Area') DEFAULT 'Site Area',
  `region` varchar(50) DEFAULT 'Riau / Pekanbaru',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `location_name`, `location_type`, `region`, `latitude`, `longitude`, `is_active`, `created_at`) VALUES
(1, 'Yard Duri', 'Yard', 'Duri, Riau', 1.2854300, 101.2185400, 1, '2026-08-04 23:40:37'),
(2, 'Yard Prabumulih', 'Yard', 'Prabumulih, Sumsel', -3.4354200, 104.2384500, 1, '2026-08-04 23:40:37'),
(3, 'Borrow Pit Harapan Baru', 'Borrow Pit', 'Duri, Riau', 1.3124500, 101.2451200, 1, '2026-08-04 23:40:37'),
(4, 'Workshop Bay KM 12', 'Workshop', 'Duri, Riau', 1.2789000, 101.2112000, 1, '2026-08-04 23:40:37'),
(5, 'Site Sunter Area Stadium', 'Site Area', 'Jakarta / Sunter', -6.1451200, 106.8741500, 1, '2026-08-04 23:40:37'),
(6, 'Site Alpha Duri', 'Site Area', 'Duri, Riau', 1.2991000, 101.2311000, 1, '2026-08-04 23:40:37'),
(7, 'PKB PEKANBARU Branch', 'Branch', 'Pekanbaru, Riau', 0.5070680, 101.4477790, 1, '2026-08-04 23:40:37'),
(8, 'PLB PALEMBANG Branch', 'Branch', 'Palembang, Sumsel', -2.9760740, 104.7754310, 1, '2026-08-04 23:40:37'),
(9, 'MDN MEDAN Branch', 'Branch', 'Medan, Sumut', 3.5951960, 98.6722230, 1, '2026-08-04 23:40:37'),
(10, 'SMG SEMARANG Rep Office', 'Branch', 'Semarang, Jateng', -6.9666670, 110.4166640, 1, '2026-08-04 23:40:37'),
(11, 'Minas Field Project', 'Site Area', 'Minas, Riau', 0.7321400, 101.4421100, 1, '2026-08-04 23:40:37'),
(12, 'Yard KM 12 Duri', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(36, 'Well Pad 2H-013A', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(40, 'Well Pad 3Q-19B', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(43, 'Well Pad 4Q-32B', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(59, 'Site Bangko', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(60, 'Well Pad 3Q-19C', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(62, 'Well Pad 4Q-21E', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(64, 'Head Office Pekanbaru', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(83, 'Well Pad 2H-1010B', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(84, 'Well Pad 2J-0110A', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(104, 'Well Pad 3Q-19A', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(111, 'Well Pad 2H-1010A', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(129, 'Well Pad 2H-1110A', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(132, 'Well Pad 2H-0306', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(164, 'Site NDD', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(177, 'Site Celcin', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(193, 'Well Pad 4P-81B', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(199, 'Well Pad 3S-46C', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(213, 'Well Pad 4R41D.', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(231, 'Well Pad 4P-81P', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(240, 'Well Pad 2H-1010A/', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(344, 'Well Pad 4P48C', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24'),
(365, 'Well Pad 4P-48C', 'Site Area', 'Riau / Pekanbaru', NULL, NULL, 1, '2026-08-04 23:42:24');

-- --------------------------------------------------------

--
-- Table structure for table `parts`
--

CREATE TABLE `parts` (
  `part_id` int(11) NOT NULL,
  `part_number` varchar(100) NOT NULL,
  `part_name` varchar(150) NOT NULL,
  `category` enum('Filter','Hose','Cutting Bit','Tire','Battery','Fast Moving','Engine Part','Hydraulic','Other') DEFAULT 'Other',
  `unit_measure` varchar(20) DEFAULT 'Pcs',
  `stock_qty` int(11) DEFAULT 0,
  `min_stock_qty` int(11) DEFAULT 2,
  `unit_cost` decimal(15,2) DEFAULT 0.00,
  `location_warehouse` varchar(100) DEFAULT 'Gudang Yard KM 12'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parts`
--

INSERT INTO `parts` (`part_id`, `part_number`, `part_name`, `category`, `unit_measure`, `stock_qty`, `min_stock_qty`, `unit_cost`, `location_warehouse`) VALUES
(1, 'P-001-OIL', 'Filter Oli Engine Komatsu PC200-10M0', 'Filter', 'Pcs', 25, 5, 250000.00, 'Gudang Yard KM 12'),
(2, 'P-002-HYD', 'Selang Hidrolik 3/4 Inch (2 Meter)', 'Hydraulic', 'Pcs', 12, 3, 750000.00, 'Gudang Yard KM 12'),
(3, 'P-003-BIT', 'Cutting Bit Grader XCMG GR135', 'Cutting Bit', 'Pcs', 60, 15, 350000.00, 'Gudang Yard KM 12'),
(4, 'P-004-AIR', 'Air Filter Primary Hino Ranger FM280', 'Filter', 'Pcs', 18, 5, 450000.00, 'Gudang Yard KM 12'),
(5, 'P-005-TIRE', 'Ban Radial 10.00-20 Dump Truck', 'Tire', 'Pcs', 8, 4, 3800000.00, 'Gudang Yard Duri');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `menu_slug` varchar(50) NOT NULL,
  `action_type` enum('READ','CREATE','UPDATE','APPROVE','OVERRIDE') NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `planner_evaluations`
--

CREATE TABLE `planner_evaluations` (
  `eval_id` int(11) NOT NULL,
  `planner_user_id` int(11) NOT NULL,
  `competency_name` varchar(150) NOT NULL,
  `job_standard` text NOT NULL,
  `actual_evaluation` text NOT NULL,
  `target_level` int(11) DEFAULT 4,
  `actual_level` int(11) DEFAULT 2,
  `gap_level` int(11) GENERATED ALWAYS AS (`actual_level` - `target_level`) STORED,
  `action_plan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `planner_evaluations`
--

INSERT INTO `planner_evaluations` (`eval_id`, `planner_user_id`, `competency_name`, `job_standard`, `actual_evaluation`, `target_level`, `actual_level`, `action_plan`) VALUES
(1, 3, 'Dasar Maintenance Alat Berat', 'D3/S1 Teknik Mesin', 'D3 Akuntansi', 4, 2, 'Pelatihan Dasar Teknik Mesin Alat Berat & System Diagnosis'),
(2, 3, 'PM Scheduling & Meter Reading', 'Update Real-Time Status KM/HM', 'Jadwal terbuat, Update KM tertinggal', 4, 3, 'Standardisasi Audit Meter Reading Harian Operator'),
(3, 3, 'Estimasi Durasi Repair', 'Akurat per Komponen', 'Belum menguasai estimasi waktu', 4, 2, 'Praktik & Benchmark Standard Job Time per Kategori Breakdown'),
(4, 3, 'Spare Parts Control & Kitting', 'Parts Ready sebelum Job Start', 'Memahami Logistik, Parts Kitting belum terikat JO', 4, 3, 'Integrasi Form SPB dengan Nomor WO/PM secara Wajib'),
(5, 3, 'Administrasi & Keuangan KESDMAN', 'Tertib Administrasi', 'Menguasai Administrasi & Akuntansi', 4, 5, 'Dipertahankan sebagai keunggulan kontrol dokumen');

-- --------------------------------------------------------

--
-- Table structure for table `pm_plans`
--

CREATE TABLE `pm_plans` (
  `pm_plan_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `interval_hm` int(11) NOT NULL,
  `current_smr` decimal(10,2) DEFAULT 0.00,
  `last_service_hm` decimal(10,2) DEFAULT 0.00,
  `last_service_date` date DEFAULT NULL,
  `target_due_hm` decimal(10,2) NOT NULL,
  `variance_hm` decimal(10,2) DEFAULT 0.00,
  `status` enum('PLANNED','DUE_SOON','OVERDUE','IN_PROGRESS','COMPLETED') DEFAULT 'PLANNED',
  `warranty_status` varchar(100) DEFAULT 'No Warranty',
  `planner_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pm_plans`
--

INSERT INTO `pm_plans` (`pm_plan_id`, `asset_id`, `interval_hm`, `current_smr`, `last_service_hm`, `last_service_date`, `target_due_hm`, `variance_hm`, `status`, `warranty_status`, `planner_note`) VALUES
(1, 'PF-00001', 500, 1894.20, 1509.00, '2026-03-30', 2009.00, 114.80, 'COMPLETED', 'No Warranty', 'Realisasi 2036.4 HM pada 22 Juli 2026'),
(2, 'VIBRO BW BRA-01', 500, 6365.00, 6045.00, '2026-04-24', 6545.00, 180.00, 'COMPLETED', 'No Warranty', 'Realisasi 6532.2 HM pada 20 Juli 2026'),
(3, 'MG-00004', 500, 2446.10, 2051.30, '2026-04-27', 2551.30, 105.20, 'PLANNED', 'No Warranty', 'Target SMR 2551.3 HM'),
(4, 'EXC-00001', 500, 2443.00, 2001.00, '2026-04-11', 2501.00, 58.00, 'DUE_SOON', 'Warranty - 4000', 'Perlu kitting filter Komatsu UT');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `spb_id` varchar(50) NOT NULL,
  `wo_id` varchar(50) NOT NULL,
  `asset_id` varchar(100) DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `urgency` enum('Normal','Emergency') DEFAULT 'Normal',
  `status` enum('Draft','Submitted','Approved','Ordered','Issued','Closed') DEFAULT 'Submitted',
  `requested_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'Administrator', 'Full access to system configuration, users, and DB management'),
(2, 'Equipment Manager', 'Head of Equipment - Access to executive dashboard & WO approvals'),
(3, 'Maintenance Planner', 'Responsible for PM scheduling, WO creation, & parts kitting'),
(4, 'Mekanik Senior', 'Responsible for executing WO repair & timesheet logs'),
(5, 'Mekanik Junior / Helper', 'Assisting senior mechanics on daily repair jobs'),
(6, 'Welder / Fabrikator', 'Responsible for welding, body protection, & ombeng locks'),
(7, 'Inspector K3L / Safety', 'Responsible for P2H inspections & HSE accident reports'),
(8, 'Logistic Head', 'Responsible for spare parts inventory & SPB approvals'),
(9, 'HRD Manager', 'Responsible for personnel timesheet & SPL authorization'),
(10, 'Asset Manager', 'Responsible for asset valuation, mutation BAST, & release approval');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_permission_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telematics_gps_logs`
--

CREATE TABLE `telematics_gps_logs` (
  `gps_log_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `speed_kmh` decimal(5,2) DEFAULT 0.00,
  `heading_deg` int(11) DEFAULT 0,
  `ignition_status` enum('ON','OFF','IDLING') DEFAULT 'OFF',
  `recorded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telematics_logs`
--

CREATE TABLE `telematics_logs` (
  `log_id` int(11) NOT NULL,
  `asset_id` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) NOT NULL,
  `model_type` varchar(100) NOT NULL,
  `sub_group` varchar(100) DEFAULT NULL,
  `smr_hours` decimal(10,2) DEFAULT 0.00,
  `working_days` int(11) DEFAULT 0,
  `working_hours` decimal(10,2) DEFAULT 0.00,
  `actual_working_hours` decimal(10,2) DEFAULT 0.00,
  `actual_working_ratio` decimal(5,2) DEFAULT 0.00,
  `e_mode_ratio` decimal(5,2) DEFAULT 0.00,
  `travel_ratio` decimal(5,2) DEFAULT 0.00,
  `digging_ratio` decimal(5,2) DEFAULT 0.00,
  `hoist_ratio` decimal(5,2) DEFAULT 0.00,
  `fuel_consumed_liters` decimal(10,2) DEFAULT 0.00,
  `fuel_lph` decimal(5,2) DEFAULT 0.00,
  `idling_ratio` decimal(5,2) DEFAULT 0.00,
  `last_comm_date` timestamp NULL DEFAULT NULL,
  `period_month` varchar(20) DEFAULT 'Januari 2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `telematics_logs`
--

INSERT INTO `telematics_logs` (`log_id`, `asset_id`, `serial_number`, `model_type`, `sub_group`, `smr_hours`, `working_days`, `working_hours`, `actual_working_hours`, `actual_working_ratio`, `e_mode_ratio`, `travel_ratio`, `digging_ratio`, `hoist_ratio`, `fuel_consumed_liters`, `fuel_lph`, `idling_ratio`, `last_comm_date`, `period_month`) VALUES
(1, 'EXC-00001', 'C51502', 'PC200-10M0', 'PKB PEKANBARU Branch', 6357.90, 24, 182.00, 102.40, 56.30, 41.20, 10.50, 14.60, 8.90, 1960.40, 10.80, 43.70, NULL, 'Januari 2026'),
(2, NULL, 'C51503', 'PC200-10M0', 'PKB PEKANBARU Branch', 5626.20, 25, 187.70, 124.40, 66.30, 36.00, 9.40, 20.20, 12.90, 2911.00, 15.50, 33.70, NULL, 'Januari 2026'),
(3, NULL, 'DBCH2941', 'PC200-10M0', 'PKB PEKANBARU Branch', 836.10, 22, 159.90, 62.30, 39.00, 29.00, 15.10, 6.20, 3.50, 1258.80, 7.90, 61.00, NULL, 'Januari 2026');

-- --------------------------------------------------------

--
-- Table structure for table `tire_inspections`
--

CREATE TABLE `tire_inspections` (
  `tire_inspection_id` int(11) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `tire_serial_no` varchar(100) DEFAULT NULL,
  `tire_position` varchar(20) NOT NULL,
  `tread_depth_mm` decimal(4,2) NOT NULL,
  `air_pressure_psi` int(11) NOT NULL,
  `condition_color` enum('GREEN','YELLOW','RED') NOT NULL,
  `inspected_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tire_inspections`
--

INSERT INTO `tire_inspections` (`tire_inspection_id`, `asset_id`, `tire_serial_no`, `tire_position`, `tread_depth_mm`, `air_pressure_psi`, `condition_color`, `inspected_at`) VALUES
(1, 'DT-00049 - B 9104 ZYT', NULL, 'FL', 11.20, 110, 'GREEN', '2026-08-05 00:40:27'),
(2, 'DT-00049 - B 9104 ZYT', NULL, 'FR', 10.80, 108, 'GREEN', '2026-08-05 00:40:27'),
(3, 'DT-00049 - B 9104 ZYT', NULL, 'R1L', 9.50, 112, 'GREEN', '2026-08-05 00:40:27'),
(4, 'DT-00049 - B 9104 ZYT', NULL, 'R1LI', 8.20, 105, 'YELLOW', '2026-08-05 00:40:27'),
(5, 'DT-00049 - B 9104 ZYT', NULL, 'R1R', 9.00, 110, 'GREEN', '2026-08-05 00:40:27');

-- --------------------------------------------------------

--
-- Table structure for table `unit_valuations`
--

CREATE TABLE `unit_valuations` (
  `valuation_id` int(11) NOT NULL,
  `asset_id` varchar(100) DEFAULT NULL,
  `unit_name` varchar(150) NOT NULL,
  `unit_code_alias` varchar(50) DEFAULT NULL,
  `total_repair_cost` decimal(15,2) DEFAULT 0.00,
  `purchase_price` decimal(15,2) DEFAULT 0.00,
  `book_value` decimal(15,2) DEFAULT 0.00,
  `market_price_min` decimal(15,2) DEFAULT 0.00,
  `market_price_max` decimal(15,2) DEFAULT 0.00,
  `raw_market_price_str` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_valuations`
--

INSERT INTO `unit_valuations` (`valuation_id`, `asset_id`, `unit_name`, `unit_code_alias`, `total_repair_cost`, `purchase_price`, `book_value`, `market_price_min`, `market_price_max`, `raw_market_price_str`) VALUES
(1, 'DZ-00002 SN P6G01656', 'Bulldozer Caterpillar D6G 2XL', 'DZ-00002', 421415277.00, 1200000000.00, 650000000.00, 450000000.00, 520000000.00, '450.000.000 – 520.000.000'),
(2, 'DT-00049 - B 9104 ZYT', 'Dump Truck Hino Ranger FM 280 JD', 'DT-00049', 185000000.00, 850000000.00, 480000000.00, 400000000.00, 580000000.00, '400.000.000 – 580.000.000'),
(3, 'Dozer 06', 'Bulldozer Caterpillar D6R', 'Dozer 06', 421415277.00, 600000000.00, 360000000.00, 9999999999999.99, 9999999999999.99, '450.000.000 â€“ 520.000.000'),
(4, 'Dozer 04', 'Bulldozer Komatsu D85ESS-2', 'Dozer 04', 403684851.00, 1220000000.00, 854000000.00, 9999999999999.99, 9999999999999.99, '750.000.000 â€“ 900.000.000'),
(5, 'Exca 16', 'Excavator Caterpillar 305.5E2', 'Exca 16', 35606864.00, 841380000.00, 588966000.00, 9999999999999.99, 9999999999999.99, '420.000.000 â€“480.000.000'),
(6, 'Exca 17', 'Excavator Caterpillar 305.5E2', 'Exca 17', 66808545.00, 841380000.00, 588966000.00, 9999999999999.99, 9999999999999.99, 'Rp420.000.000 â€“ Rp480.000.000'),
(7, 'Dozer 07', 'Bulldozer Caterpillar D7G2', 'Dozer 07', 286553522.00, 800000000.00, 480000000.00, 9999999999999.99, 9999999999999.99, '420.000.000 â€“ 500.000.000'),
(8, 'Dozer 01', 'Bulldozer Caterpillar D6G', 'Dozer 01', 579012317.00, 415000000.00, 290500000.00, 9999999999999.99, 9999999999999.99, '400.000.000 â€“ 550.000.000'),
(9, 'Exca 04', 'Excavator Caterpillar 320D', 'Exca 04', 456208914.00, 370000000.00, 185000000.00, 9999999999999.99, 9999999999999.99, '400.000.00 â€“ 580.000.000'),
(10, 'Dozer 06', 'Bulldozer Caterpillar D6R', 'Dozer 06', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(11, 'Dozer 04', 'Bulldozer Komatsu D85ESS-2', 'Dozer 04', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(12, 'Exca 16', 'Excavator Caterpillar 305.5E2', 'Exca 16', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(13, 'Exca 17', 'Excavator Caterpillar 305.5E2', 'Exca 17', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(14, 'Dozer 07', 'Bulldozer Caterpillar D7G2', 'Dozer 07', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(15, 'Dozer 01', 'Bulldozer Caterpillar D6G', 'Dozer 01', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(16, 'Exca 04', 'Excavator Caterpillar 320D', 'Exca 04', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(17, 'Dozer 06', 'Bulldozer Caterpillar D6R', 'Dozer 06', 421415277.00, 600000000.00, 360000000.00, 9999999999999.99, 9999999999999.99, '450.000.000 â€“ 520.000.000'),
(18, 'Dozer 04', 'Bulldozer Komatsu D85ESS-2', 'Dozer 04', 403684851.00, 1220000000.00, 854000000.00, 9999999999999.99, 9999999999999.99, '750.000.000 â€“ 900.000.000'),
(19, 'Exca 16', 'Excavator Caterpillar 305.5E2', 'Exca 16', 35606864.00, 841380000.00, 588966000.00, 9999999999999.99, 9999999999999.99, '420.000.000 â€“480.000.000'),
(20, 'Exca 17', 'Excavator Caterpillar 305.5E2', 'Exca 17', 66808545.00, 841380000.00, 588966000.00, 9999999999999.99, 9999999999999.99, 'Rp420.000.000 â€“ Rp480.000.000'),
(21, 'Dozer 07', 'Bulldozer Caterpillar D7G2', 'Dozer 07', 286553522.00, 800000000.00, 480000000.00, 9999999999999.99, 9999999999999.99, '420.000.000 â€“ 500.000.000'),
(22, 'Dozer 01', 'Bulldozer Caterpillar D6G', 'Dozer 01', 579012317.00, 415000000.00, 290500000.00, 9999999999999.99, 9999999999999.99, '400.000.000 â€“ 550.000.000'),
(23, 'Exca 04', 'Excavator Caterpillar 320D', 'Exca 04', 456208914.00, 370000000.00, 185000000.00, 9999999999999.99, 9999999999999.99, '400.000.00 â€“ 580.000.000'),
(24, 'Dozer 06', 'Bulldozer Caterpillar D6R', 'Dozer 06', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(25, 'Dozer 04', 'Bulldozer Komatsu D85ESS-2', 'Dozer 04', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(26, 'Exca 16', 'Excavator Caterpillar 305.5E2', 'Exca 16', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(27, 'Exca 17', 'Excavator Caterpillar 305.5E2', 'Exca 17', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(28, 'Dozer 07', 'Bulldozer Caterpillar D7G2', 'Dozer 07', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(29, 'Dozer 01', 'Bulldozer Caterpillar D6G', 'Dozer 01', 0.00, 0.00, 0.00, 0.00, 0.00, ''),
(30, 'Exca 04', 'Excavator Caterpillar 320D', 'Exca 04', 0.00, 0.00, 0.00, 0.00, 0.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_location_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `role_id`, `assigned_location_id`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Utama', 1, 4, 1, '2026-08-04 23:40:37'),
(2, 'dany_agung', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dany Agung (Head of Equipment)', 2, 4, 1, '2026-08-04 23:40:37'),
(3, 'martin_planner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'P. Martin (Maintenance Planner)', 3, 4, 1, '2026-08-04 23:40:37'),
(4, 'rahmad_k', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahmad K (Mekanik Senior)', 4, 4, 1, '2026-08-04 23:40:37'),
(5, 'urwatul_uska', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Urwatul Uska (Helper Mekanik)', 5, 4, 1, '2026-08-04 23:40:37'),
(6, 'joni_septian', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Joni Septian (Mekanik)', 4, 3, 1, '2026-08-04 23:40:37'),
(7, 'afriyandi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Afriyandi (Mekanik Senior)', 4, 4, 1, '2026-08-04 23:40:37'),
(8, 'darmawan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Darmawan (Mekanik)', 4, 4, 1, '2026-08-04 23:40:37'),
(9, 'hendrik', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hendrik (Teknisi Listrik / Welder)', 6, 4, 1, '2026-08-04 23:40:37'),
(10, 'rezeki_siregar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rezeki Siregar (Mekanik)', 4, 3, 1, '2026-08-04 23:40:37'),
(11, 'suwardi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Suwardi (Mekanik Welding)', 6, 4, 1, '2026-08-04 23:40:37'),
(12, 'taufiq_h', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Taufiq H (Security)', 7, 4, 1, '2026-08-04 23:40:37'),
(13, 'guswan_arizal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Guswan Arizal (Logistic Head)', 8, 4, 1, '2026-08-04 23:40:37'),
(14, 'rani_simanungkalit', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rani Simanungkalit (HRD)', 9, 4, 1, '2026-08-04 23:40:37'),
(15, 'widya_apriani', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Widya Apriani (Asset Manager)', 10, 4, 1, '2026-08-04 23:40:37'),
(16, 'm_fajar_dc', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'M. Fajar DC (Inspector K3L)', 7, 6, 1, '2026-08-04 23:40:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_role_id`, `user_id`, `role_id`, `is_primary`, `assigned_at`) VALUES
(1, 1, 1, 1, '2026-08-04 23:40:37'),
(2, 1, 2, 0, '2026-08-04 23:40:37'),
(3, 2, 2, 1, '2026-08-04 23:40:37'),
(4, 2, 3, 0, '2026-08-04 23:40:37'),
(5, 3, 3, 1, '2026-08-04 23:40:37'),
(6, 4, 4, 1, '2026-08-04 23:40:37'),
(7, 5, 5, 1, '2026-08-04 23:40:37'),
(8, 6, 4, 1, '2026-08-04 23:40:37'),
(9, 7, 4, 1, '2026-08-04 23:40:37'),
(10, 8, 4, 1, '2026-08-04 23:40:37'),
(11, 9, 6, 1, '2026-08-04 23:40:37'),
(12, 9, 4, 0, '2026-08-04 23:40:37'),
(13, 10, 4, 1, '2026-08-04 23:40:37'),
(14, 11, 6, 1, '2026-08-04 23:40:37'),
(15, 12, 7, 1, '2026-08-04 23:40:37'),
(16, 13, 8, 1, '2026-08-04 23:40:37'),
(17, 14, 9, 1, '2026-08-04 23:40:37'),
(18, 15, 10, 1, '2026-08-04 23:40:37'),
(19, 15, 2, 0, '2026-08-04 23:40:37'),
(20, 16, 7, 1, '2026-08-04 23:40:37');

-- --------------------------------------------------------

--
-- Table structure for table `work_orders`
--

CREATE TABLE `work_orders` (
  `wo_id` varchar(50) NOT NULL,
  `asset_id` varchar(100) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `raw_location` varchar(255) DEFAULT NULL,
  `issue_description` text NOT NULL,
  `downtime_formatted` varchar(100) DEFAULT NULL,
  `downtime_minutes` int(11) DEFAULT 0,
  `is_downtime` tinyint(1) DEFAULT 1,
  `status` enum('Open','In Progress','Waiting Part','Testing','Closed','Cancelled') DEFAULT 'Open',
  `priority` enum('Normal','High','Emergency') DEFAULT 'Normal',
  `assigned_mechanic` varchar(100) DEFAULT 'Belum ada PIC',
  `reported_at` timestamp NULL DEFAULT current_timestamp(),
  `repair_started_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `verification_supervisor_id` int(11) DEFAULT NULL,
  `before_photo_url` varchar(255) DEFAULT NULL,
  `after_photo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_orders`
--

INSERT INTO `work_orders` (`wo_id`, `asset_id`, `location_id`, `raw_location`, `issue_description`, `downtime_formatted`, `downtime_minutes`, `is_downtime`, `status`, `priority`, `assigned_mechanic`, `reported_at`, `repair_started_at`, `closed_at`, `verification_supervisor_id`, `before_photo_url`, `after_photo_url`) VALUES
('WO-26-001', 'DZ-00002 SN P6G01656', NULL, 'Yard KM 12 Duri', 'STUCK ENGINE. Mati total, Over heat, minyak di Nozzle sudah bercampur air, packing - head bocor', '579 jam 16 menit', 34756, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-002', 'DT-00049 - B 9104 ZYT', NULL, 'Yard KM 12 Duri', 'MEKANIKAL & K3 - 1. Perbaikan lampu hazard. - 2. Karet ting tong bawah kanan 1 Pcs, - 3. Karet ting tong atas 2 Pcs, - 4. Karet susu/bantalan 4 Pcs - 5. Seal cylinder hoist bocor - 6. Housekeeping - 7. Perbaikan kepala Baterai - 8. Mounting gantungan knalpot - WELDING - 1. Lantai dump - 2. Sub Chasis retak kiri & kanan luar dalam - 3. Tutup aki - 4. Dinding dump kanan bocor. - 5. Underrun retak dan bengkok - 6. Gantungan Ban Serep', '371 jam 51 menit', 22311, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-003', 'DT-04024 - BM 9285 JO', NULL, 'Borrow Pit Harapan Baru', 'Radiator Bocor, Mounting Engine', '49 jam 40 menit', 2980, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-004', 'DT-00055 - B 9701 PYW', NULL, 'Yard KM 12 Duri', '1. Karet ting tong bawah kanan 2 Pcs, - 2. Karet ting tong atas 4 Pcs, - 3. Karet susu/bantalan 4 Pcs - 4. Lantai dump - 5. Sub Chasis retak kiri & kanan luar dalam - 6. Seal cylinder hoist bocor - 7. Tutup aki - 8. Dinding dump kanan bocor. - 9. Underrun retak dan bengkok - 10. Gantungan Ban Serep - 11. Housekeeping - 12. Perbaikan kepala Baterai - 13. Pemasangan per depan - REGREASING', '368 jam 00 menit', 22080, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-005', 'DT-00059 - BG 8163 NJ', NULL, 'Yard KM 12 Duri', 'WELDING - 1. Cover body kiri penyok - 2. Kisi2 chasis dump - 3. Bracket knalpot - 4. Baut clamp sub chasis. Cover body - 5. Kunci Om Beng - MEKANIKAL & K3 - 1. Pls check body set / trunion. - 2. Tutup Baterai - 3. Ting tong atas bawah - 4. Karet susu OK - 5. Apar low pressure - 6. Lock safety belt kiri - 7. Clemp per depan kanan - 8. Grease all fitting - 9. Housekeeping - 10. Simpang 4 PTO - 11. Safety lumpur kanan depan - 12. URL Belakang - Temuan baru : pelek roda retak 2 Pcs . - Regreasing', '202 jam 21 menit', 12141, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-006', 'DT-00046 -B 9094 ZYT', NULL, 'Borrow Pit Harapan Baru', '1. Ting tong atas bawah - Inspek tgl 18-12-2025 - 2. Alarm mundur - 3. Grease all fitting', '262 jam 40 menit', 15760, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-007', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', '1. Om Beng bengkok / retak kiri, Tidak bisa dikunci - 2. Bracket tutup Baterai. - 3. Stabil front - 4. Grease all fitting', '89 jam 57 menit', 5397, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-008', 'DT-00060 - BG 8230 MX', NULL, 'Yard KM 12 Duri', 'WELDING - 1.Lantai dump. - 2. Underrun kanan patah - 3. Chasis dump & kisi2 - 4. Bracket clamp Baterai dan cover Baterai - Mekanikal & K3 - 1. Karet susu - 2. Ban Serep - 3. Pin bushing cylinder hoise atas & bawah - 4. Ting tong atas - 5. Karet mounting shower per depan - 6. Grease all fitting - 7. Service berkala', '269 jam 56 menit', 16196, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-009', 'DT-00064 - BG 8640 NI', NULL, 'Yard KM 12 Duri', 'WELDING - 1 Underrun belakang - 2. Lantai dump - 3. Cross member sub chassis - Mekanikal & K3 - 1. Clamp tutup Baterai - 2. Marking nut - 3. Alarm mundur - 4. Ting tong atas bawah - 5. Baut pin bushing cyllinder hoise longgar - 6. Simpang 4 PTO - 7. Karet susu - 8. Grease all fitting (Regreasing) CLose tgl 25/12/2025', '145 jam 23 menit', 8723, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-010', 'DT-00050 - B 9105 ZYT', NULL, 'Borrow Pit Harapan Baru', 'WELDING - 1. SUB Chasis keropos - 2. Chasis dump retak dan kisi - MEKANIKAL - 1. Packing cylinder head (merembes) - 2. Karet susu kiri belakang - 3. Greasie all fitting', '102 jam 00 menit', 6120, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-011', 'DT-00065-BG 8641 NI', NULL, 'Borrow Pit Harapan Baru', 'WELDING - 1. Kisi ksi chasis dump - 2. Lantai dump - 3. Clamp Baterai dan clamp tutup Baterai - 4. Chasis dump ke stand hydraulic - 5. Gantungan knalpot dan ujung knalpot - Info Mekanik tgl 30/12/2025 : Gantungan Ban Serep putus/hilang - Baterai low Batt. Jumper Accu. Pembuatan bracket penahan Accu - MEKANIKAL - 1. Marking nut - 2. Lampu rem - 3. Baut pin bushing cylinder hoise bawah - 4. Kaca rayban kena lentingan batu - 5. Grease all fitting', '431 jam 47 menit', 25907, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-012', 'DT-04029 - BM9819QO', NULL, 'Borrow Pit Harapan Baru', '1.hanger pen spring depan - 2.penyetelan kanvas rem - 3.service berkala - 4.pengecekan disclutch - 5. Om Beng baling', '94 jam 00 menit', 5640, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-013', 'DT-04048', NULL, 'Borrow Pit Harapan Baru', 'Pergantian Shock Absorber 2 Pcs', '3 jam 22 menit', 202, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-014', 'DT-04058 - BM 9678 JO - DT-41005', NULL, 'Yard KM 12 Duri', '1. Penggantian Baut Roda 2 set - 2. Ganti kampas rem 1 set - 3. Ganti brake lining 1 tromol RH', '343 jam 00 menit', 20580, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-015', 'DT-04053 - BM 9824 QO', NULL, 'Yard KM 12 Duri', 'Perbaikan / Ganti baut roda - Penggantian Accu', '338 jam 00 menit', 20280, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-016', 'DT-04057 - BM 9289 JO DT-41004', NULL, 'Yard KM 12 Duri', 'Perbaikan / Ganti baut roda - Welding Bolt Roda ( penahan Bolt pada saat di Kunci)', '907 jam 50 menit', 54470, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-017', 'DT-04056 DT-41003 - BM 9956 JO', NULL, 'Yard KM 12 Duri', 'Perbaikan / Ganti baut roda - Welding Bolt Roda 10 Pcs ( Bolt baru welding penahan agar Tidak berputar saat di Kunci)', '907 jam 50 menit', 54470, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-018', 'DT-04054 - BM9291JO DT-41001', NULL, 'Yard KM 12 Duri', 'Perbaikan / Ganti baut roda - ðŸ”´ A. STRUKTURAL & SUSPENSI (BERAT / KRITIS) - Per depan kanan patah â†’ âœ… SUDAH diganti - Shock breaker depan â†’ âœ… SUDAH terpasang - Mounting shock absorber kiri â†’ â— PERLU verifikasi retak / kekuatan - Sub chassis retak â†’ â— BELUM ada konfirmasi selesai - Mounting / stopper per depan kiriâ€“kanan â†’ â— BELUM dikonfirmasi - Hanger spring / gantungan per kiri â†’ â— BELUM dikonfirmasi - Clamp / klem per kiri â†’ â— BELUM dikonfirmasi - ðŸŸ¡ B. SISTEM STEERING & REM (PENENTU RTW) - Steering keras Indikasi: masalah sistem power steering (pompa / tekanan / hose) - Problem rem Bisa terkait: adjuster, selang, atau sistem rem utama - ðŸŸ¡ C. SISTEM DUMP & MEKANIKAL - Ting tong atas â†’ âœ… SUDAH terpasang - Trunnion / bushing per kanan â†’ â— BELUM dikonfirmasi selesai - Stud bolt / cucumber per â†’ â— BELUM dikonfirmasi - ðŸŸ¢ D. FUEL SYSTEM & ENGINE SUPPORT - Filter solar dari tangki Tidak terpasang / terbuka - â†’ â— KRITIS, BELUM dikonfirmasi - (Ini Tidak boleh diabaikan karena risiko kotoran masuk sistem) - ðŸŸ¢ E. K3 & FINISHING - Lock seat belt kiri â†’ â— BELUM dikonfirmasi - Fender kiri depan lepas â†’ â— BELUM dikonfirmasi - Marking nut & torque check â†’ â— BELUM dilakukan / belum dilaporkan - Tutup Baterai & clamp Baterai â†’ â— BELUM dikonfirmasi', '907 jam 50 menit', 54470, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-019', 'DT-04055 - BM9503QO DT-41002', NULL, 'Yard KM 12 Duri', 'Perbaikan / Ganti baut roda 40 Pcs', '907 jam 50 menit', 54470, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-020', 'DT-00052 - B 9642 KYW', NULL, 'Borrow Pit Harapan Baru', '1. Perbaikan lampu kabut dan lampu besar . - 2. BROKEN, MASTER KLOSE ATAS, MASTER KLOSE BAWAH', '833 jam 50 menit', 50030, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-021', 'DT-04024 - BM 9285 JO', NULL, 'Borrow Pit Harapan Baru', '1. Putus monting enggine (depan 2 Pcs)(belakang 2 Pcs) - 2. Safety radiator pecah - 3. Safety lumpur spackboard', '833 jam 50 menit', 50030, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-022', 'DOZER-BRA 05', NULL, 'Borrow Pit Harapan Baru', 'Tambah oli hidrolik dobel', '840 jam 35 menit', 50435, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-023', 'DT-00072 - DT 8669 KE', NULL, 'Yard KM 12 Duri', 'Regreasing', '840 jam 05 menit', 50405, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-024', 'Lowboy PM-00003 - KT 9287 KU', NULL, 'Yard KM 12 Duri', 'Regreasing', '839 jam 20 menit', 50360, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-025', 'CC-00001 - ZCC600V', NULL, 'Well Pad 2H-013A', 'Ada kebocoran oli hidrolik di Hose. Penambahan olihyidrolik', '839 jam 00 menit', 50340, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-026', 'DT-00054 - B 9683 KYW', NULL, 'Borrow Pit Harapan Baru', 'Penggantian kepala Baterai', '839 jam 50 menit', 50390, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-027', 'DT-04038 - BM 9296 JO', NULL, 'Borrow Pit Harapan Baru', 'Kepala Baterai longgar. Perbaikan', '839 jam 05 menit', 50345, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-028', 'DT-04032', NULL, 'Borrow Pit Harapan Baru', 'Tambah oli hidrolik. - Perbaikan Kunci Om Beng kiri dan kanan', '831 jam 41 menit', 49901, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-029', 'DZ-00001', NULL, 'Well Pad 3Q-19B', 'Filter Solar sdh kotor . Pergantian Filter Solar', '830 jam 50 menit', 49850, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-030', 'EX-00003 - C51503', NULL, 'Well Pad 3Q-19B', 'Regreasing', '832 jam 20 menit', 49940, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-031', 'DT-00032', NULL, 'Yard KM 12 Duri', 'Ganti KEPALA Baterai. KEPALA Baterai LONGGAR. Minta DICOR - pengelasan pagar sebelah kiri dan Om Beng', '817 jam 20 menit', 49040, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-032', 'EX-00012- PC 200', NULL, 'Well Pad 4Q-32B', 'Tambah Oli Engine 2,5 Lt. Klakson Tidak Bunyi', '816 jam 20 menit', 48980, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-033', 'DT-00071 - DT 8126 JE', NULL, 'Borrow Pit Harapan Baru', 'Radiator berlumpur, perlu Perbaikan - Oli kipas berkurang. - Accu Soak pinjam dari DT-04030 - Perbaikan elektrik lampu kota dan penggantian lampu', '785 jam 20 menit', 47120, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-034', 'DT-04037 - BM 9666 JO', NULL, 'Borrow Pit Harapan Baru', 'SELANG RADIATOR. - Karet ting tong teouble. - Turbo charge leaking perlu diganti.(Turbo broken) - Temuan tngl. 19/01/2026 : poly engine dan V-Belt engine Trouble', '815 jam 50 menit', 48950, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-035', 'DT-00050 - B 9105 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Accu / Baterai Minta Ganti 1 Pcs', '96 jam 08 menit', 5768, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-036', 'WT-00001', NULL, 'Borrow Pit Harapan Baru', 'Accu / Baterai Minta Ganti 1 Pcs', '100 jam 03 menit', 6003, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-037', 'DT-00037 - B 9139 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Kunci OMEBNG', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-038', 'DT-00040 - B 9081 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan angin', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-039', 'DT-00054 - B 9683 KYW', NULL, 'Yard KM 12 Duri', 'MEKANIKAL - 1. Lock safety belt kiri, 2. Steering problem (steering system), 3. Brake adjuster - 4. Marking Nut 5. Per kanan depan patah 6. Moiunting stoper per depan kiri kanan - 7. Sobreker / absorber mounting kiri 8. Ting tong atas. 9. HUnger spring- kiri - 10. klem per kiri, 11. Pls check stud bolt / cucuk per dan trunion bushing kanan. - 11. Tutup Baterai dan clam Pls check fuel system (selang filter dari tangki Tidak - dipasang . 12. REgreasing . - Welding - 1. Lantai dump. 2. Fender kiri depan lepas. 3. SUb chassis retak . \\', '101 jam 39 menit', 6099, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-040', 'DT-00053 - B 9644 KYW', NULL, 'Borrow Pit Harapan Baru', '1. Perbaikan Kunci Om Beng kiri, - 2. Pengelasan breket underround belakang', '0 jam 49 menit', 49, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-041', 'DT-00003 - B 9109 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan safety lumpur', '0 jam 33 menit', 33, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-042', 'DT-04038 - BM 9296 JO', NULL, 'Borrow Pit Harapan Baru', 'Patah baut keliling transmisi', '23 jam 09 menit', 1389, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-043', 'DT-04012 - BM 9944 JO', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan elektrik, lampu2 mati', '1 jam 10 menit', 70, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-044', 'DT-00035 - BM 8621 QU', NULL, 'Borrow Pit Harapan Baru', 'Ganti selang minyak rem belakang', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-045', 'DT-00027 - B 9136 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Gantungan Ban Serep, Baterai low. - Bumper patah', '1 jam 37 menit', 97, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-046', 'DT-00073 - DT8973IE', NULL, 'Yard KM 12 Duri', 'Perbaikan/penggantian Trouble engine mounting', '3 jam 00 menit', 180, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-047', 'DT-04048 - BM 9951 JO', NULL, 'Borrow Pit Harapan Baru', 'Selang tangki BBM putus (welding)', '0 jam 51 menit', 51, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-048', 'EX-00007', NULL, 'Site Bangko', 'Ganti oli hidrolik/tambah', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-049', 'PF-0001 - BW - 211D - 40', NULL, 'Well Pad 3Q-19C', 'Alarm mundur Tidak hidup. Penggantian alaarm mundur - Service Berkala', '165 jam 16 menit', 9916, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-050', 'DT-00058 - B 9973 BIS', NULL, 'Borrow Pit Harapan Baru', 'Pengencangan baut as tarik. Mufler longgar dan bocor', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-051', 'PF-0002', NULL, 'Well Pad 4Q-21E', 'Trouble ; Alat Low Power - Solution ; Pencucian Filter Solar', '1 jam 19 menit', 79, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-052', 'DT-04042 - BM 9287 JO', NULL, 'Borrow Pit Harapan Baru', 'Penggantian mounting engine', '24 jam 54 menit', 1494, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-053', 'DT-00034', NULL, 'Head Office Pekanbaru', 'Baut roda patah 8 Pcs', '2 jam 50 menit', 170, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-054', 'DT-00017 - B 9126 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Underrun Protection Patah . Welding', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-055', 'DT-00061 - BG 8367 IJ', NULL, 'Borrow Pit Harapan Baru', 'Radiator Leaking - TALI KIPAS RADIATOR PUTUS - Selang radiator rusak', '98 jam 53 menit', 5933, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-056', 'DT-00063 - BG 8639 NI', NULL, 'Borrow Pit Harapan Baru', 'Inspeksi CEM tgl. 24/12/2025 - 1. Karet Wiiper 2. Klem per kiri kanan, 3. Per depan kanan patah, 4. Tutup Baterai - 5. Pin bushing gantiungan per kiri depan. 6. Ting tomg kanan bawah 1 Pcs, - 7. Simpang 4 PTO. 8. Housekeepig 9. Grease all fitting 10. Safety lumpur kanan depan - 11. Hose cylinder noise. 12. LOck pintu kanan 13. Cover lampu sein kiri belakang lepas - Welding : - 1 Sub Chasis retak, 2. Kisi2 chasis dump. 3. Underrun kiri broken. 4. Knalpot patah', '79 jam 23 menit', 4763, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-057', 'DT-00058 - B 9973 BIS', NULL, 'Yard KM 12 Duri', 'Inspeksi CEM tgl. 24/12/2025 - 1. Packing cyl head. 2. Baut Axle shaft kiri longgar. 3. Ting tong kiri belakang. - 4. Karet susu. 5. Engine brake. 6. Lampu kabut kanan. 7. Kunci pintu kanan - 8. Marking nut. 9. Ban Serep. 10. Alarm low sound 11. Lampu rem Tidak berfungsi - Welding - 1. Bracket lamu belakang kiri kanan 2. Underrun belakang kri retak. - 3. Brakcet tutup Baterai 4. Brakcet klam knalpot. 5. Bak samping kanan keropos - 6. Engsel Om Beng 7. Dump kanan atas bengkok dan retak - Info Mekanik tgl. 30/12/2025 (Joni Septian : ) - Engine Break Rusak perlu penggantian part. 1 set.: engine break', '106 jam 53 menit', 6413, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-058', 'DT-04040 - BM 9509 QO', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Kunci Om Beng', '1 jam 25 menit', 85, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-059', 'DT-04048 - BM 9951 JO', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan gantungan Ban Serep', '0 jam 45 menit', 45, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-060', 'DT-00031 - BG 8535 OW', NULL, 'Borrow Pit Harapan Baru', 'Radiator Leaking - Ganti radiator baru - pengisian air coolen 25 liter - Ganti baterai baru', '24 jam 51 menit', 1491, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-061', 'MB-00006 -- BM 7680 TU', NULL, 'Yard KM 12 Duri', 'TEMPAT DUDUK TENGAH BROKEN - Welding tapak bangku penumpang & lock - Ganti kepala Accu & Ganti skun kabel power', '24 jam 53 menit', 1493, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-062', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', '1. Om Beng bengkok / retak kiri, Tidak bisa dikunci - 2. Bracket tutup Baterai. - 3. Stabil front - 4. Grease all fitting - 5. LAMPU BELAKANG PUTUS - 6. KACA SPION KIRI RUSAK', '114 jam 42 menit', 6882, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-063', 'PM-00001', NULL, 'Yard KM 12 Duri', 'Penggantian 1 bh.songket jek lamdor loboy', '1 jam 23 menit', 83, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-064', 'DT-00057 - B 9914 JYT', NULL, 'Borrow Pit Harapan Baru', 'Lampu Stop Belakang Minta Diganti 1 Set', '2 jam 46 menit', 166, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-065', 'DT-04042 - BM 9287 JO', NULL, 'Borrow Pit Harapan Baru', 'Peraikan Kunci Om Beng. Overheat, mounting engine rusak lagi', '77 jam 21 menit', 4641, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-066', 'DT-00003 - B 9109 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Penggantian lampu kota', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-067', 'DT-00012 - B 9121 ZYT', NULL, 'Yard KM 12 Duri', 'Stel/tonjok Klos Bawah', '2 jam 00 menit', 120, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-068', 'DT-04036 - BM 9949 JO', NULL, 'Borrow Pit Harapan Baru', 'Om Beng koyak', '2 jam 25 menit', 145, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-069', 'DT-00008 - B 9115 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Gantungan Ban Serep putus & lasing', '20 jam 46 menit', 1246, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-070', 'DT-04044 - BM9244NU', NULL, 'Borrow Pit Harapan Baru', 'Penyambungan selang angin bocor', '0 jam 46 menit', 46, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-071', 'PM-00003 - KT 9287 KU', NULL, 'Yard KM 12 Duri', 'Safety Underround Samping sebelah kanan broken', '0 jam 44 menit', 44, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-072', 'MG-00004 SN:PRD800108', NULL, 'Well Pad 2H-1010B', 'Lampu Sein Kanan Short', '1 jam 15 menit', 75, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-073', 'SD-00001 - BW 211D - 40', NULL, 'Well Pad 2J-0110A', 'Lampu Rotari Putus . Pengantian lampu rotary', '1 jam 30 menit', 90, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-074', 'PF-00004 - 961884301614', NULL, 'Well Pad 2J-0110A', 'Tombol getar broken', '0 jam 37 menit', 37, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-075', 'DT-00035 - BM 8621 QU', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lamu belakang dan pemeriksaan jalur elektrik', '0 jam 53 menit', 53, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-076', 'THRILLER - BM 8371 cu', NULL, 'Yard KM 12 Duri', 'Penggantian Accu / Baterai 1pcs', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-077', 'HE-6.001 Exca mini', NULL, 'Yard KM 12 Duri', 'Ada Rembesan Oli di Track Sisi Kanan Unit', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-078', 'PM-0001 - B 9012 ZEH', NULL, 'Yard KM 12 Duri', 'Perbaikan Baut bohel sebelah kiri lepas.dan per miring', '5 jam 49 menit', 349, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-079', 'DT-04024 - BM 9285 JO', NULL, 'Borrow Pit Harapan Baru', 'Ganti piston bawah', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-080', 'SD-00005 - 961582391661', NULL, 'Minas Field Project', 'Pergantian input Sealing washer fuel 1pcs', '1 jam 21 menit', 81, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-081', 'EX-00004 - DBCH 0366', NULL, 'Minas Field Project', 'Kerusakan jalur hyduluic. Kode eror L03 pada control panel', '7 jam 31 menit', 451, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-082', 'DT-04024 - BM 9285 JO', NULL, 'Borrow Pit Harapan Baru', 'Penggantian safety lumpur - Pabrikasi dudukan spackboard', '27 jam 44 menit', 1664, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-083', 'DT-00055 - B 9701 PYW', NULL, 'Borrow Pit Harapan Baru', 'Dinamo stater rusak', '171 jam 09 menit', 10269, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-084', 'PF-00003 SN : 961582391152', NULL, 'Minas Field Project', 'Solenoid gas rusak. Harus diganti', '249 jam 11 menit', 14951, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-085', 'DT-00005 - B9113ZYT', NULL, 'Borrow Pit Harapan Baru', 'Lampu Rem Rusak', '0 jam 48 menit', 48, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-086', 'EX-22 - EX-04001 - DBCH1801', NULL, 'Yard KM 12 Duri', 'Perbaikan / Ganti Pin As dan Bushing Bucket, karena kondisi aus', '10 jam 51 menit', 651, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-087', 'DT-04025 - BM 9398 JO', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu besar dan kota', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-088', 'DT-00031 - BG 8535 OW', NULL, 'Borrow Pit Harapan Baru', 'Alarm Mundur - Driver Tidak Info', '18 jam 27 menit', 1107, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-089', 'DT-00011 - B 9120 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Seal Crankshaft Bocor', '164 jam 00 menit', 9840, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-090', 'MG-00004 SN:PRD800108', NULL, 'Borrow Pit Harapan Baru', 'Penguncian baut penahan kabin', '0 jam 38 menit', 38, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-091', 'TMC-00001 - B 9008 ZIN', NULL, 'Yard KM 12 Duri', 'Fabrikasi Safety Trailer & TMC Samping Sebelah kanan dan kiri', '3 jam 55 menit', 235, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-092', 'DT-00040-- B9081ZYT', NULL, 'Borrow Pit Harapan Baru', '1.Ganti belting AC. - 2.Ganti volli AC', '0 jam 39 menit', 39, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-093', 'SD-00004 -', NULL, 'Well Pad 3Q-19A', 'Service Berkala', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-094', 'DT-04048 - BM 9951 JO', NULL, 'Borrow Pit Harapan Baru', 'Pengelsan Kunci Om Beng - 1 : Ganti gantungan ban depan. - 2: Perbaikan lampu rem. - 3 : wiper kaca mati. - 4 : lampu saen', '51 jam 10 menit', 3070, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-095', 'EX-00001', NULL, 'Borrow Pit Harapan Baru', 'Pemberian Grease ke Operator', '0 jam 25 menit', 25, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-096', 'DT-00060 - BG 8230 MX', NULL, 'Yard KM 12 Duri', 'Perbaikan temuan inspek pekerjaan Perbaikan Frame Dump dan tambahkan plate - dump pada sisi kiri robek', '1 jam 05 menit', 65, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-097', 'DZ-00003 - BRA-05', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Unit low power', '1 jam 54 menit', 114, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-098', 'DT-00056 - B 9892 PYW', NULL, 'Borrow Pit Harapan Baru', 'Kabel Baterai', '1 jam 51 menit', 111, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-099', 'EX-00005-SYW01364', NULL, 'Yard KM 12 Duri', '1. Penggantian kuku bucket. - 2. Mufler keropos - 3. Bushing bucket aus - 4. Pin Bucket aus - 5. Side cutter kiri kanan - 6. Oli Hidrolik, Filter Minta Ganti - 7. Tag Apar', '223 jam 00 menit', 13380, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-100', 'SD-00001 - BW 211D - 40', NULL, 'Well Pad 2H-1010A', 'Penambahan Oli Engine & Hidrolik 1,5 Liter', '0 jam 43 menit', 43, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-101', 'DT-00049 - B 9104 ZYT', 4, NULL, 'DISMANTLE DISC CLUTCH LIMIT & REPAIR TRANSMISSION', '8 jam 55 menit', 60, 1, 'Closed', 'High', 'Rahmad K, Suwardi', '2026-08-04 23:40:37', NULL, NULL, NULL, NULL, NULL),
('WO-26-102', 'CS-41001', 6, NULL, 'OVERLOAD PENGISIAN CEMENT - SYSTEM ERROR MONITOR', '#########################', 120, 1, 'Closed', 'Emergency', 'Teknisi XCMG & M. Fajar DC', '2026-08-04 23:40:37', NULL, NULL, NULL, NULL, NULL),
('WO-26-103', 'PF-00001', 1, NULL, 'INSPEKSI PM 500 HM & GANTI FILTER OLI', '2 jam 30 menit', 103, 1, 'Closed', 'Normal', 'P. Martin', '2026-08-04 23:40:37', NULL, NULL, NULL, NULL, NULL),
('WO-26-104', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', 'Penggantian 1 bh kepala batrai', '0 jam 29 menit', 29, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-105', 'EX-00008 - C01531', NULL, 'Minas Field Project', 'Service Berkala -fuel sparator 2pcs -Fuel filter 1pcs -Oil SAE 40 : 25 ltrs', '1 jam 12 menit', 72, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-106', 'EX-00004 - DBCH 0366', NULL, 'Minas Field Project', 'Ganti filter oli 1pcs, Ganti filter sparator 1pcs', '0 jam 22 menit', 22, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-107', 'EX-00008 - C01531', NULL, 'Minas Field Project', 'Pada sealr arm bucket ada rembesan', '552 jam 04 menit', 33124, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-108', 'EX-00004 - DBCH 0366', NULL, 'Minas Field Project', 'Pada layar muncul : \"Blow pressure high error\". Error L03 kembali muncul tgl. - 05/01/2026 - Pengecekan soket injektor exc 0004 lok minas - Injector rusak - Ada rembesan oli dari soket injektor - Service Berkala - Baut as pen', '149 jam 14 menit', 8954, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-109', 'EX-00011', NULL, 'Head Office Pekanbaru', 'Pabrikasi bracket lampu rotary', '1 jam 41 menit', 101, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-110', 'DT-00057 - B 9914 JYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti : Belting AC & Pully AC', '0 jam 47 menit', 47, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-111', 'BRA-03 - B 9096 ZYT', NULL, 'Yard KM 12 Duri', 'Universal join sudah pecah', '147 jam 00 menit', 8820, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-112', 'WT-00001', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan lampu rem belakang mati', '1 jam 02 menit', 62, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-113', 'FUSO TMC - XCMG B 9435 XFY', NULL, 'Borrow Pit Harapan Baru', 'Instal lampu sorot + pergantian bohlam dari 12 volt ke 24 volt', '1 jam 30 menit', 90, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-114', 'DT-00038 - B9079ZYT', NULL, 'Borrow Pit Harapan Baru', 'Bell Ting AC lepas', '1 jam 05 menit', 65, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-115', 'MG-00004 SN:PRD800108', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan lampu Hazard', '0 jam 20 menit', 20, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-116', 'DT-00071 - DT 8126 JE', NULL, 'Yard KM 12 Duri', '1. Overheat, air menembak dari radiator. - 2. Ganti packing cilinder head - 3. Cleaning component,skir klep,Ganti packing cylinder head. - Temuan kerusakan tanggal 18/01/2026 : STARTING MOTOR Trouble - TINDAKAN MITIGASI YANG DILAKUKAN: - 1.Membuka silinder head - 2.skir klep - 3.cleaning - 4.Ganti starting motor - 5.mencabut bolt turbo 5pcs', '332 jam 51 menit', 19971, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-117', 'DT-00021 - B 9130 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Tuas pompa pto lengket', '2 jam 25 menit', 145, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-118', 'EX-00011', NULL, 'Well Pad 2H-1110A', 'Lampu Rotary Putus. Penggantian Lampu Rotary', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-119', 'DT-00053 - B 9644 KYW', NULL, 'Borrow Pit Harapan Baru', 'Knalpot Rusak. Perbaikan Welding', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-120', 'DT-00062 - BG8638NI', NULL, 'Borrow Pit Harapan Baru', 'Penggantian Drak Link', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-121', 'EX-00012 - DBCH1801', NULL, 'Well Pad 2H-0306', 'Apar Low Pressure. Klakson Tidak Mau Bunyi', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-122', 'DT-00057 - B 9914 JYT', NULL, 'Well Pad 2H-0306', 'PUTUS CENTRAL Bolt PER DEPAN / - patah baut cucuk per', '51 jam 00 menit', 3060, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-123', 'DT-00061 - BG 8367 IJ', NULL, 'Borrow Pit Harapan Baru', '1 : lampu besar depan sebelah kiri pecah. - 2 : lampu kabut kiri pecah. - 3. Bumper depan rusak', '22 jam 35 menit', 1355, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-124', 'DT-04048 - BM 9951 JO', NULL, 'Borrow Pit Harapan Baru', 'Ganti pully AC', '0 jam 49 menit', 49, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-125', 'DT-04024 - BM 9285 JO', NULL, 'Borrow Pit Harapan Baru', 'Bangku mesin belakang kiri depan kiri rusak. (Penggantian). - Perbaikan dan pengelasan Kunci Om Beng baru', '7 jam 56 menit', 476, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-126', 'DT-04042 - BM 9287 JO', NULL, 'Borrow Pit Harapan Baru', 'Sleang radiator lepas', '1 jam 21 menit', 81, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-127', 'DT-00021 - B 9130 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu kiri depan', '0 jam 38 menit', 38, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-128', 'DT-04036 - BM 9949 JO', NULL, 'Borrow Pit Harapan Baru', 'Ganti sambungan selang', '0 jam 40 menit', 40, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-129', 'DT-04038 - BM 9296 JO', NULL, 'Borrow Pit Harapan Baru', 'Selang radiator bocor', '1 jam 15 menit', 75, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-130', 'DT-00001', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan underround belakang', '1 jam 03 menit', 63, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-131', 'DT-04025 - BM 9398 JO', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan Om Beng yang retak', '1 jam 45 menit', 105, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-132', 'DT-00062 - BG8638NI', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan gantungan Ban Serep', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-133', 'DT-00059 - BG 8163 NJ', NULL, 'Borrow Pit Harapan Baru', 'Repair attention Muffler selesai. - pengelasan muffler yang patah Selesai', '1 jam 15 menit', 75, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-134', 'DT-00009 - B 9116 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan underround belakang yang patah', '0 jam 51 menit', 51, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-135', 'DT-00045-B9093ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan pagar kiri dan kanan', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-136', 'DZ-00003 - BRA-05', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan tapak shoe Dozer. Trouble nozle no.5 & 6', '43 jam 53 menit', 2633, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-137', 'EX-00008 - C01531', NULL, 'Minas Field Project', 'Penggantian filter oli', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-138', 'DT-00052 - B 9642 KYW', NULL, 'Yard KM 12 Duri', 'Perbaikan Om Beng dan Kunci Om Beng', '1 jam 27 menit', 87, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-139', 'MG-00005', NULL, 'Borrow Pit Harapan Baru', 'Penambahan oli hidrolik 5 ltr. Control Valve Bocor', '143 jam 20 menit', 8600, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-140', 'DT-00032 - BG8976IX', NULL, 'Borrow Pit Harapan Baru', 'Trouble ; Radiator Bocor', '26 jam 14 menit', 1574, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-141', 'DT-04048 - BM 9951 JO', NULL, 'Borrow Pit Harapan Baru', 'Borrow Pit Harapan baru', '0 jam 46 menit', 46, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-142', 'DT-00072 - DT 8669 KE', NULL, 'Borrow Pit Harapan Baru', 'Ganti kepala bateray', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-143', 'DT-04032', NULL, 'Borrow Pit Harapan Baru', 'Ganti sambungan slang.1 bh - Ganti Nepal slang velve angin . - Ganti slang .40 cm', '1 jam 18 menit', 78, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-144', 'DT-00054 - B 9683 KYW', NULL, 'Borrow Pit Harapan Baru', 'Unit Susah hidup. Pengantian kepala batere ,kabel seri dan menjamper batere', '1 jam 34 menit', 94, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-145', 'DT-00048-B 9103 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Alternator / dinamo cas rusak. - Sama Vanbelt dinamo cas Minta Ganti 2 bh. - Gantungan Ban Serep rusak - 08/01/2026 : Dinamo charer sudah dipasang, Van Belt Tidak cocok - Ganti pully ac', '51 jam 26 menit', 3086, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-146', 'DT-00052 - B 9642 KYW', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan kepala Baterai. Low power. - Perbaikan pesawat2 kaca samping kanan', '3 jam 13 menit', 193, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-147', 'DT-00044 - B9092ZYT', NULL, 'Borrow Pit Harapan Baru', 'Penggantian ban dalam 1 Pcs', '1 jam 47 menit', 107, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-148', 'DT-00056 - B 9892 PYW', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan gantungan Ban Serep.dan pasang lasing baru. - Ganti kepala batrai 1 Pcs', '1 jam 20 menit', 80, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-149', 'DT-00031 - BG 8535 OW', NULL, 'Borrow Pit Harapan Baru', 'Ganti master kelos bawah', '3 jam 58 menit', 238, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-150', 'DT-00026 - B9135ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan pen Om Beng dan pagar yang patah', '0 jam 49 menit', 49, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-151', 'DT-04001-BM 9174 NU', NULL, 'Borrow Pit Harapan Baru', 'Penggantian lampu rem / lampu kota', '1 jam 06 menit', 66, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-152', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan dudukan apar', '1 jam 14 menit', 74, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-153', 'SD-00002 - 961582391716', NULL, 'Site NDD', 'Alat low power, Proses Perbaikan Oleh Mekanik dan Pencucian Filter Solar', '1 jam 15 menit', 75, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-154', 'SD-00005 - 961582391661', NULL, 'Site NDD', 'Minyak kososng di filter, buang solar', '1 jam 15 menit', 75, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-155', 'DT-00052 - B 9642 KYW', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan klakson', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-156', 'CAT RM-500B', NULL, 'Yard KM 12 Duri', 'Instal & pabrikasi Stensen flank penghubung ke watertank', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-157', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', 'Selesai pekerjaan Ganti filter solar bawah 1 Pcs', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-158', 'DT-00061 - BG 8367 IJ', NULL, 'Borrow Pit Harapan Baru', 'Bracket knalpot patah', '1 jam 06 menit', 66, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-159', 'PF-00004 - 961884301614', NULL, 'Site NDD', 'Finding Tpe. Pemasangan Baut Pen Bomag - 1. Pen di Bubut lagi u penambahan daging besi penahan - 2. Pergantian Pen baru', '0 jam 43 menit', 43, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-160', 'DT-04042 - BM 9287 JO', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan pengunci Om Beng', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-161', 'DT-04001-BM 9174 NU', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Kunci Om Beng yang bengkok', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-162', 'DZ-00006 - SHANTUI', NULL, 'Site NDD', 'Trouble ; Alat Low Power - Solution ; Pencucian Filter Solar Alat', '102 jam 39 menit', 6159, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-163', 'MG-00004 SN:PRD800108', NULL, 'Site NDD', 'Baut Kuku Grader Patah , Proses Pergantian Baut Oleh Mekanik', '1 jam 32 menit', 92, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-164', 'EX-00012 - DBCH1801', NULL, 'Well Pad 2H-1010A', 'Penambahan Oli Engine 1,5 Liter', '0 jam 22 menit', 22, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-165', 'WT-00001 - B9018KFA', NULL, 'Borrow Pit Harapan Baru', 'Nozel rusak. Minta Ganti nozle - pengelasan pagar atas yang patah', '3 jam 15 menit', 195, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-166', 'DT-00029 - B 9138 ZYT', NULL, 'Site Celcin', 'Disc Clutch dan Belting AC Trouble. Stel klose dan belting AC', '2 jam 19 menit', 139, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-167', 'DT-00050 - B 9105 ZYT', NULL, 'Site Celcin', 'Perbaikan slang minyak lepas', '2 jam 50 menit', 170, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-168', 'DT-00026 - B9135ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan engsel Om Beng dan Kunci Om Beng', '0 jam 52 menit', 52, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-169', 'DT-04040 - BM 9509 QO', NULL, 'Site Celcin', 'Pekerjaan penyetelan rem', '0 jam 54 menit', 54, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-170', 'DT-00003 - B 9109 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu rem sebelah kiri', '0 jam 25 menit', 25, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-171', 'EX-00007-C51502', NULL, 'Site Celcin', 'Regreasing', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-172', 'DT-00005 - B9113ZYT', NULL, 'Site Celcin', 'Regreasing, Pembersihan Saringan Udara', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-173', 'DT-00039-B9080ZYT', NULL, 'Site Celcin', 'Regreasing, Pembersihan Saringan Udara', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-174', 'DT-00014 - B 9123 ZYT', NULL, 'Site Celcin', 'Regreasing, Pembersihan Saringan Udara', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-175', 'DT-00041-B9082ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan/penggantian Pully Ac', '1 jam 18 menit', 78, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-176', 'DT-00046 -B 9094 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Service Berkala', '1 jam 08 menit', 68, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-177', 'DT 0008 - B 9137 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan bola lampu', '23 jam 27 menit', 1407, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-178', 'DT 0057 - B 9914 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan bola lampu', '23 jam 12 menit', 1392, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-179', 'XCMG-B 9435 XFY', NULL, 'Yard KM 12 Duri', 'Melengkapi Unit', '103 jam 10 menit', 6190, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-180', 'FLAT BED B 9733 XPZ', NULL, 'Yard KM 12 Duri', 'Melengkapi Unit', '103 jam 10 menit', 6190, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-181', 'DT-00054 - B 9683 KYW', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan bola lampu', '0 jam 10 menit', 10, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-182', 'SD-00004 - 961884301945', NULL, 'Well Pad 4P-81B', 'Pergantian Filter Solar - Pergantian Filter Oli - Pergantian Saringan Hawa - Trouble ; Elektrik System Short', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-183', 'PF-00001-961582391717', NULL, 'Well Pad 4P-81B', 'Penambahan Oli Engine 1,5 Liter', '0 jam 53 menit', 53, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-184', 'HE-6.001 Exca mini', NULL, 'Head Office Pekanbaru', 'Dinamo charge Trouble. (Perbaikan)', '2 jam 59 menit', 179, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-185', 'DT-04024 - BM 9285 JO', NULL, 'Borrow Pit Harapan Baru', 'Pakam klos bermasalah', '101 jam 51 menit', 6111, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-186', 'DT-00072 - DT 8669 KE', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan kepala Baterai', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-187', 'EX-00012 - DBCH1801', NULL, 'Well Pad 2H-1010A', 'Trouble ; Cucuk Pin Bucket Patah - Solution ; Proses Pergantian Cucuk Pin Bucket', '1 jam 57 menit', 117, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-188', 'DT-00056 - B 9892 PYW', NULL, 'Well Pad 3S-46C', 'Broken kit jamber dan Valve angin bocor', '76 jam 55 menit', 4615, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-189', 'MG-00004 SN:PRD800108', NULL, 'Borrow Pit Harapan Baru', 'Slang oli Tranmisi & torq conm.lepas. - Oli Tranmisi habis. Alat perlu oli SAE 40. sebanyak 20 liter', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `work_orders` (`wo_id`, `asset_id`, `location_id`, `raw_location`, `issue_description`, `downtime_formatted`, `downtime_minutes`, `is_downtime`, `status`, `priority`, `assigned_mechanic`, `reported_at`, `repair_started_at`, `closed_at`, `verification_supervisor_id`, `before_photo_url`, `after_photo_url`) VALUES
('WO-26-190', 'DT-00012 - B 9121 ZYT', NULL, 'Yard KM 12 Duri', 'Regreasing, Pembersihan Saringan Udara', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-191', 'DT-00004 - B 9112 ZYT', NULL, 'Yard KM 12 Duri', 'Regreasing, Pembersihan Saringan Udara, Stel Belting Ac', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-192', 'DT-00016 - B 9125 ZYT', NULL, 'Yard KM 12 Duri', 'Pekerjaan setel jemberit', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-193', 'DT-00013 - B 9122 ZYT', NULL, 'Yard KM 12 Duri', 'Regreasing, Pembersihan Saringan Udara', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-194', 'DT-04040 - BM 9509 QO', NULL, 'Site Celcin', 'Ganti baut roda belakang 1 Pcs', '20 jam 10 menit', 1210, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-195', 'DT-00043 - B9091ZYT', NULL, 'Minas Field Project', 'Menyetel V-Belt AC', '0 jam 42 menit', 42, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-196', 'DT-00031 - BG 8535 OW', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Kunci Om Beng', '1 jam 05 menit', 65, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-197', 'DT-00057 - B 9914 JYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Kunci Om Beng', '0 jam 48 menit', 48, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-198', 'RM-02 -RM-500', NULL, 'Yard KM 12 Duri', 'Pengecekan blade di body rottor', '0 jam 49 menit', 49, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-199', 'EX-00008 - C01531', NULL, 'Minas Field Project', 'Pergantian Track Roler', '1 jam 28 menit', 88, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-200', 'DT-04001 - BM 9174 NU', NULL, 'Borrow Pit Harapan Baru', 'Proses penggantian karet susu 4 Pcs', '1 jam 34 menit', 94, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-201', 'DT-00028 - B9137ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu kota dan lampu rem', '0 jam 21 menit', 21, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-202', 'DT-00050 - B 9105 ZYT', NULL, 'Well Pad 4R41D.', 'Tidak Mau masuk gigi. Ganti guli2 handel 1 Pcs', '1 jam 15 menit', 75, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-203', 'DT-00059 - BG 8163 NJ', NULL, 'Borrow Pit Harapan Baru', 'Gantungan knalpot rusak/Pengelasan . Mufler patah', '117 jam 20 menit', 7040, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-204', 'DT-00022 - B 9131 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan pengelasan underround belakang patah', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-205', 'DT-00017 - B 9126 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan pengelasan underround belakang patah', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-206', 'FBT 31002. - FLAT BED BM 8443 NU', NULL, 'Yard KM 12 Duri', '1Pasang Bareket Apar. - 2Merubah dimensi tinggi/Rendah pada Underround Protection. - 3Merubah dimensi tinggi/Rendah pada Safety Underround Samping sebelah - kanan/Kiri . - 4Fabrikasi Stopper ( Pagar Pipa )sisi kanan 4 buah dan kiri 4 buah= 8 buah ( proses - pengerjaan ) - 5Pasang Safety Bareket Apar.( Selesai )', '267 jam 09 menit', 16029, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-207', 'FBT 31004. - BM 8451 NU', NULL, 'Yard KM 12 Duri', '1Merubah dimensi Tinggi/rendah Safety Underround Protection belakang Sesuai - Standard JPK.( Selesai ) - 2Merubah Tinggi /Rendah Safety Underround Samping Kanan dan kiri sesuai standard - JPK ( selesai ) - 3Fabrikasi Penempatan Whellchock 2 Pcs ( proses ) - 4Fabrikasi Stopper ( Pagar Pipa )sisi kanan 4 buah dan kiri 4 buah= 8 buah ( proses ) - 5Pasang Safety Bareket Apar.( Proses )', '220 jam 00 menit', 13200, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-208', 'BM 8441 NU ( XCMG Double Jack )', NULL, 'Yard KM 12 Duri', '1Merubah dimensi tinggi/Rendah Underround Protection ( belakang ) - 2Merubah dimensi tinggi/Rendah Safety Underround Samping kanan dan kiri. - 3Pasang Bareket Apar - 4Penempatan Whellchock/ melengkapi - 5Pasang Safety Pagar Lantai kanan 4 buah dan kiri 4 buah total = 8 buah - 6Pasang Tempat untuk tapak JackOutrigger kanan/kiri . - 7Fabrikasi Tangga Untuk pengoperasian JackOutrigger kanan dan kiri', '51 jam 57 menit', 3117, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-209', 'HILUX - B 9123 ZBA', NULL, 'Yard KM 12 Duri', 'Disc Cluth Trouble. Penggantian disc cluth', '57 jam 00 menit', 3420, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-210', 'DT-00052 - B 9642 KYW', NULL, 'Borrow Pit Harapan Baru', 'Hight - low Tidak berfungsi. Penggantian bola lampu depan kiri', '1 jam 39 menit', 99, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-211', 'DZ-00003 - BRA-05', NULL, 'Yard KM 12 Duri', 'Unit Tidak Mau Starter. Hand Pump Trouble - INDIKASI KLEP BOCOR/ RENCANA LAKUKAN Overhaul - 1 penggantian paking dexsel - 2 penyecrupan dexsel - 3 penyekiran clep - 4 penggantian sil clep - 5 seting clep ulang - 6 kalibrasi nozel - 7 penggantian sil nozel - 8 penggantian paking set pada dexsel', '0 jam 00 menit', 0, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-212', 'MB-00006 -- BM 7680 TU', NULL, 'Yard KM 12 Duri', 'SERVICE BERKALA - Penggantian filter solar atas, Filter udara, filter oli, filter solar bawah. - temuan buat Perbaikan, tambahan air radiator dan perbaiki Kunci pintu belakang - Bateray lemah / TEKOR', '173 jam 30 menit', 10410, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-213', 'DT-00038 - B9079ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan Van Belt Ac', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-214', 'DT-04001 - BM 9174 NU', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Kunci Om Beng dan pengelasan Om Beng yang retak. - Filter solar atas retak .dan kebocoran solar. Filter solar pinjam punya DT-00055', '5 jam 38 menit', 338, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-215', 'DT-00065-BG 8641 NI', NULL, 'Borrow Pit Harapan Baru', 'PENGGANTIAN PULLY AC 1 Pcs. Repair attention MuffleR', '0 jam 42 menit', 42, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-216', 'DT-04030 - BM 9510 QO', NULL, 'Borrow Pit Harapan Baru', 'Penambahan oli power steering', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-217', 'DT-04042 - BM 9287 JO', NULL, 'Borrow Pit Harapan Baru', 'High/low gear planetary Tidak Mau masuk. canibal switch/sensor H/L dari DT 04037 ke - DT 04042', '1 jam 11 menit', 71, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-218', 'DT-00073 - DT8973IE', NULL, 'Borrow Pit Harapan Baru', 'Kontrol velve bocor.angin sering berkurang', '49 jam 48 menit', 2988, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-219', 'PM-00003 - KT 9287 KU', NULL, 'Yard KM 12 Duri', '1Braket Lampu Send sebelah kanan broken - 2Safety Ban Serep patah. - 3Safety Underround Samping sebelah kanan broken', '2 jam 31 menit', 151, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-220', 'SD-00004 - 961884301945', NULL, 'Well Pad 4P-81P', 'Perbaikan Elektrik System Short', '2 jam 29 menit', 149, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-221', 'DT-00055 - B 9701 PYW', NULL, 'Borrow Pit Harapan Baru', 'Propeller shaff inter axle Trouble . Filter solar dipinjam DT-04001 - = > pemasang shaf propeler baru,pemasang batere, - Ganti filter solar kecil,Ganti kaber sery,Ganti fuse kecil 4pcs', '50 jam 15 menit', 3015, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-222', 'DT-04044 - BM9244NU', NULL, 'Yard KM 12 Duri', 'Pengelasan Sefti lampu belakang benkel luar', '2 jam 14 menit', 134, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-223', 'DT-00049 - B 9104 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu besar 1 Pcs. - Ganti bohlam lampu kabut kiri 1 Pcs', '1 jam 50 menit', 110, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-224', 'DT-00064 - BG 8640 NI', NULL, 'Site Celcin', 'Ganti bola lampu kabut 1 Pcs', '0 jam 52 menit', 52, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-225', 'DT-00025 - B 9134 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pipa slang solar di dalam tengki patah.perlu pengelasan Kuningan', '40 jam 20 menit', 2420, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-226', 'FBT-31001 - BM 8442 NU', NULL, 'Yard KM 12 Duri', '1Merubah dimensi tinggi/renda pada Underround Safety Samping kanan dan kiri. - 2Merubah dimensi tinggi/Renda pada Underround Protection Belakang. - 3Pasang Safety ( Bareket) Apar. - 4Pasang Safety Pagar Lantai 8 buah ( kanan 4 Pcs & kiri 4 Pcs ). - 12/01/2026 - 5Penempatan Whellchock/ melengkapi ( selesai pengerjaan) - 6Pasang Safety Pagar Lantai kanan 4 buah dan kiri 4 buah total = 8 buah( dlm proses - pengerjaan ). - 12/01/206 : lembur assembly Railling', '59 jam 25 menit', 3565, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-227', 'BM 8452 NU', NULL, 'Yard KM 12 Duri', '1Merubah dimensi Tinggi/rendah Safety Underround Protection belakang Sesuai - Standard JPK.(Dalam Proses Repair). - 2Merubah Tinggi /Rendah Safety Underround Samping Kanan dan kiri sesuai standard - JPK ( Sudah Selesai Repair). - 3Pabrikasi Tangga pengoperasian Undle JackOutrigger system hidrolik ( Sudah selesai - pengerjaan) - 4Fabrikasi Penempatan Tapak JackOutrigger Kanan dan kiri ( Masih dalam proses - pengerjaan) - 5Fabrikasi Penempatan Whellchock 2 Pcs ( sudah selesai pengerjaan). - 6Fabrikasi Stopper ( Pagar Pipa )sisi kanan 4 buah dan kiri 4 buah= 8 buah - 7Pasang Bareket Apar ( selesai pengerjaan) - 8Pasang Safety Pagar Lantai kanan 4 buah dan kiri 4 buah total = 8 buah( dlm proses', '375 jam 10 menit', 22510, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-228', 'TMC 31001 - BM 8440 NU', NULL, 'Yard KM 12 Duri', '1.Pasang / Revisi underan - 2.Tangga oplator', '375 jam 10 menit', 22510, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-229', 'EX-00009 - DBCH2941', NULL, 'Well Pad 2H-1010A/', 'BautÂ² Lampu Rotari Lepas', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-230', 'DT-00017 - B 9126 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu rem.kiri dan kanan', '0 jam 42 menit', 42, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-231', 'MG-00004 SN:PRD800108', NULL, 'Yard KM 12 Duri', 'Ban Sebelah Kiri Tengah Bocor. Bongkar pasang bawa ke Bengkel ban - BAN MOTOR GRADER SOBEK Minta Ganti - Baut Roda Mor 36 sdh dol dan Minta di Ganti. Unit dibawa ke Yard untuk Perbaikan - baut roda/', '75 jam 00 menit', 4500, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-232', 'ML-41001 - BRA-03', NULL, 'Yard KM 12 Duri', 'Memasang simpang4 PTO - Shockbreaker depan belakang, - Lampu rem Tidak berfungsi', '7 jam 39 menit', 459, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-233', 'PM-41001 - BM8371CU', NULL, 'Yard KM 12 Duri', '1Dumping Plate Chasis kanan dan kiri. - 2Welding Casing luar Chasis kanan dan kiri. - 3 Perbaikan dan penggantian spakbor 2 Pcs - 4 Pengantian gagangan pintu - 5 Lanjutan pemasangan kaca', '128 jam 00 menit', 7680, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-234', 'DT-00061 - BG 8367 IJ', NULL, 'Borrow Pit Harapan Baru', 'Master klos angin bocor', '1 jam 25 menit', 85, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-235', 'DT-00046 -B 9094 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti volli AC 1 Pcs', '0 jam 26 menit', 26, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-236', 'DT-00031 - BG 8535 OW', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan mufler keropos / Pabrikasi welding', '5 jam 47 menit', 347, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-237', 'DT-04036 - BM 9949 JO', NULL, 'Borrow Pit Harapan Baru', 'Ganti alarm mundur 1 Pcs', '0 jam 50 menit', 50, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-238', 'DT-00004 - B 9112 ZYT', NULL, 'Site Celcin', 'Ganti filter solar 2 Pcs', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-239', 'DT-00064 - BG 8640 NI', NULL, 'Site Celcin', 'Ganti oring filter oli bawah 2 Pcs', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-240', 'DT-00057 - B 9914 JYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu rem.sebelah kanan', '0 jam 49 menit', 49, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-241', 'DT-00050 - B 9105 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu rem Sebelah kiri', '0 jam 55 menit', 55, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-242', 'DT-00005 - B9113ZYT', NULL, 'Site Celcin', 'Ganti filter solar bawah 1 Pcs', '0 jam 55 menit', 55, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-243', 'DT-00023 - B9132ZYT', NULL, 'Site Celcin', 'Ganti filter solar bawah 1 Pcs', '0 jam 42 menit', 42, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-244', 'DT-00006 - B9118ZYT', NULL, 'Site Celcin', 'Gerising, membersihkan saringan udara, Ganti filter solar bawah 1 Pcs', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-245', 'DT-00047 - B 9102 ZYT', NULL, 'Site Celcin', 'Regreasing', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-246', 'EX-00003 - C51503', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan busing dan - pergantian pen excavator 00003 - Perbaikan lampu rotary', '25 jam 30 menit', 1530, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-247', 'DT-00063 - BG 8639 NI', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu besar, H4 24v100w sebanyak 1pcs', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-248', 'DT-00030 - B 9139 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti belting AC 1 Pcs (LEMBUR)', '1 jam 30 menit', 90, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-249', 'DT-04012 - BM 9944 JO', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu 2 pc, - Penggantian tingtong 4pcs dan - Ganti karet susu 4pcs', '26 jam 37 menit', 1597, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-250', 'HE-6.004 Exca mini', NULL, 'Yard KM 12 Duri', 'Sambungan bucket mini exca Trouble', '2 jam 30 menit', 150, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-251', 'FT-00001 - BA9016QU', NULL, 'Yard KM 12 Duri', 'Lampu sein kiri mati', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-252', 'DT-00049 - B 9104 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan selang angin bocor. - Ganti bola lampu kota belakang dan lampu besar h4', '0 jam 56 menit', 56, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-253', 'DT-00011 - B 9120 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pasang mur guli handle gigi ,menyeter tali ac', '0 jam 34 menit', 34, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-254', 'DT-00032 - BG8976IX', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu', '0 jam 23 menit', 23, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-255', 'DT-00056 - B 9892 PYW', NULL, 'Borrow Pit Harapan Baru', '1. Ganti bola lampu. - 2. Pengelasan gantungan muffler', '2 jam 14 menit', 134, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-256', 'DT-41001 - BM9291JO', NULL, 'Yard KM 12 Duri', 'Mengunci ulang baut roda (baut masih kendor) - Pemasangan alarm mundur', '28 jam 01 menit', 1681, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-257', 'DT-41002 - BM9503QO', NULL, 'Yard KM 12 Duri', 'Mengunci ulang baut roda (baut masih kendor)', '0 jam 32 menit', 32, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-258', 'DT-41003 - BM9956JO', NULL, 'Yard KM 12 Duri', 'Mengunci ulang baut roda (baut masih kendor)', '0 jam 32 menit', 32, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-259', 'DT-41004 - BM9289JO', NULL, 'Yard KM 12 Duri', 'Mengunci ulang baut roda (baut masih kendor)', '0 jam 32 menit', 32, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-260', 'DT-41005 - BM9678JO', NULL, 'Yard KM 12 Duri', 'Mengunci ulang baut roda (baut masih kendor)', '0 jam 32 menit', 32, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-261', 'DZ-00006 - SHANTUI', NULL, 'Site NDD', 'Perbaikan kabel Lampu Rotari ada yg short', '0 jam 36 menit', 36, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-262', 'SD-00001 - BW 211D - 40', NULL, 'Well Pad 2H-1010A', 'Unit low power. Pencucian Filter Solar', '1 jam 01 menit', 61, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-263', 'FT-00001 - BA9016QU', NULL, 'Yard KM 12 Duri', 'Service berkala,', '0 jam 59 menit', 59, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-264', 'TMC B 9430 XFY', NULL, 'Yard KM 12 Duri', 'Merubah 8 line jalur seling menjadi 4 line jalur seling crane', '1 jam 50 menit', 110, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-265', 'DT-00058 - B 9973 BIS', NULL, 'Yard KM 12 Duri', 'Perbaikan selang minyak solar', '7 jam 24 menit', 444, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-266', 'DT-00063 - BG 8639 NI', NULL, 'Borrow Pit Harapan Baru', 'Radiator Bocor', '96 jam 00 menit', 5760, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-267', 'DT-00016 - B 9125 ZYT', NULL, 'Yard KM 12 Duri', 'Ganti poly AC 1 Pcs', '0 jam 45 menit', 45, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-268', 'DT-00029 - B 9138 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Stel bell Ting AC', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-269', 'TMC-00001 - B 9008 ZIN', NULL, 'Yard KM 12 Duri', '1# pengatian Bering AC - 2# pengatian v belt - 3# Penambahan freon', '0 jam 30 menit', 30, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-270', 'DT-04030 - BM 9510 QO', NULL, 'Borrow Pit Harapan Baru', 'Kerusakan hose hydrolik Dump - Aki tekor : repair kepala aki - Hose hydrolik bocor : install hose hydrolik - V-Belt engine putus : canibal V-Belt engine dari DT 04037 - Ganti pully ac dan belting kipas', '26 jam 12 menit', 1572, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-271', 'DT-04029 - BM9819QO', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan lampu kota dan lampu rem', '1 jam 05 menit', 65, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-272', 'SD-41001 -', NULL, 'Yard KM 12 Duri', 'Instal emergency stop - River breaket apar sesuai standard jpk - Melengkapi/permintaan JPK membuat atap safety compag', '2 jam 27 menit', 147, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-273', 'DT-00008 - B 9115 ZYT', NULL, 'Site Celcin', 'Ganti gantungan Ban Serep - Regreaising - Membersihkan saringan udara', '0 jam 43 menit', 43, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-274', 'DT-00056 - B 9892 PYW', NULL, 'Borrow Pit Harapan Baru', 'Penggantian piston master clutch', '1 jam 30 menit', 90, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-275', 'SD-00001 - BW 211D - 40', NULL, 'Borrow Pit Harapan Baru', 'Service Berkala', '0 jam 53 menit', 53, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-276', 'DT-04040 - BM 9509 QO', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan baut roda belakang sebelah kanan patah - Ganti baut roda belakang sebelah kanan : 3 Pcs', '0 jam 47 menit', 47, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-277', 'DT-00058 - B 9973 BIS', NULL, 'Borrow Pit Harapan Baru', 'Pekerjaan setel rem semua roda', '1 jam 01 menit', 61, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-278', 'DT-00022 - B 9131 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Regreasing dan membersihkan saringan udara - PENGELASAN DUDUKAN APAR YANG PATAH', '2 jam 39 menit', 159, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-279', 'DT-00061 - BG 8367 IJ', NULL, 'Borrow Pit Harapan Baru', 'PENGELASAN GANTUNGAN MUFFLERYANG PATAH. - Spring depan no 1 patah - Temuan kerusakan Mekanik tgl. 19/01/2026 : Baut turbo patah 6 Pcs (baut 12 pjg 2,5\")', '145 jam 06 menit', 8706, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-280', 'DT-04025 - BM 9398 JO', NULL, 'Borrow Pit Harapan Baru', 'Regreasing, Pembersihan Saringan Udara', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-281', 'LB-00001 - PM-00001', NULL, 'Yard KM 12 Duri', '1Ganti filter ( solar ) atas 1 Pcs dan bawah 1 Pcs .( Andi Low & landak ) - 2Low Power. ( Andi Low & Landak ) Penggantian filter solar atas dan bawah. - 3Chasis Trailer PATAH di bagian Sisi kanan dan kiri ( sama dengan kejadian yang - sebelumnya) . - 4Casing Penutup body Chasis bagian luar kanan dan kiri patah', '77 jam 57 menit', 4677, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-282', 'DT-00028 - B9137ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu rem 1 Pcs - Ganti bola lampu kabut 1pcs', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-283', 'DT-00052 - B 9642 KYW', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan dudukan APAR, pemasangan lasing Ban Serep 1 Pcs', '1 jam 02 menit', 62, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-284', 'RM-41001 - XUG2303KJRDQ00021', NULL, 'Yard KM 12 Duri', 'Regreasing 48 titik nipple Unit Cold Recycling', '1 jam 23 menit', 83, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-285', 'DT-00044 - B9092ZYT', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-286', 'DT-00073 - DT8973IE', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu KABUT', '0 jam 16 menit', 16, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-287', 'DT-00021 - B 9130 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pengantian lampu kota dan lampu rem', '0 jam 40 menit', 40, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-288', 'DT-00054 - B 9683 KYW', NULL, 'Borrow Pit Harapan Baru', 'PENGELASAN PAGAR YANG PATAH - Perbaikan Lok pintu sebelah kiri. - Stel tali jek Dum. - Pasang spakbor sebelah kiri', '4 jam 21 menit', 261, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-289', 'TMC 31001 - BM 8440 NU', NULL, 'Yard KM 12 Duri', '1Merubah dimensi Tinggi/rendah Safety Underround Protection belakang Sesuai - Standard JPK.( Proses ) - 2Merubah Tinggi /Rendah Safety Underround Samping Kanan dan kiri sesuai standard - JPK ( proses ) - 3Pabrikasi Tangga pengoperasian Undle JackOutrigger system hidrolik ( proses ) - 4Fabrikasi Penempatan Tapak JackOutrigger Kanan dan kiri ( proses ) - 5Fabrikasi Penempatan Whellchock 2 Pcs ( Proses ) - 6Fabrikasi Stopper ( Pagar Pipa )sisi kanan 4 buah dan kiri 4 buah= 8 buah ( proses ) - 7Pasang Safety Bareket Apar.( Proses) - 8. Pabrikasi , Pemasangan railing', '101 jam 05 menit', 6065, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-290', 'DT-00015 - B 9124 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti filter solar bawah 2 Pcs. - Unit dt low power. - Ganti bohlam lampu rem 1 Pcs', '1 jam 52 menit', 112, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-291', 'EX-00011', NULL, 'Site NDD', 'Trouble ; Kunci Exca Hilang - Solution ; Proses Pelepasan Kunci Betina', '24 jam 24 menit', 1464, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-292', 'WT-00001 - B9018KFA', NULL, 'Borrow Pit Harapan Baru', 'Pencopotan mesin pompa water tank menuju myanmar service', '4 jam 00 menit', 240, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-293', 'DT-00062 - BG8638NI', NULL, 'Borrow Pit Harapan Baru', 'Kipas Radiator Trouble', '51 jam 19 menit', 3079, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-294', 'DT-00056 - B 9892 PYW', NULL, 'Borrow Pit Harapan Baru', 'Penggantian Bola Lampu Kota', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-295', 'DT-00055 - B 9701 PYW', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan kit Valve angin . - Rem chamber - Ganti kit Valve angin - Perbaikan jamber belakang sebelah kanan. - Cek kompresor angin', '103 jam 27 menit', 6207, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-296', 'DT-00057 - B 9914 JYT', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan pengunci Om Beng', '1 jam 05 menit', 65, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-297', 'DT-00039-B9080ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu rem', '0 jam 36 menit', 36, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-298', 'DT-04024 - BM 9285 JO', NULL, 'Borrow Pit Harapan Baru', 'SERVICE BERKALA - *Ganti filter solar 2 Pcs - *Ganti filter oli 1 Pcs - *Ganti oli mesin - gerising - membersihkan saringan udara', '1 jam 39 menit', 99, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-299', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', 'Ban depan kanan bocor. Bongkar pasang Ganti Ban Serep', '1 jam 49 menit', 109, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-300', 'DT-00011 - B 9120 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu', '0 jam 28 menit', 28, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-301', 'DT-00047 - B 9102 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan penutup Unnderound belakang dan samping', '1 jam 17 menit', 77, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-302', 'DT-00023 - B9132ZYT', NULL, 'Borrow Pit Harapan Baru', 'Selesai pekerjaan gerising dan memberikan saringan udara', '1 jam 28 menit', 88, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-303', 'LB-00003', NULL, 'Yard KM 12 Duri', 'Penggantian 2 bh ban meledak di KM 9', '2 jam 24 menit', 144, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-304', 'DT-00064 - BG 8640 NI', NULL, 'Borrow Pit Harapan Baru', 'Regreasing dan pembersihan saringan udara', '0 jam 36 menit', 36, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-305', 'DT-00072 - DT 8669 KE', NULL, 'Yard KM 12 Duri', '1Pintu Om Beng Terlepas, Engsel patah kanan/kiri.( Proses Perbaikan). - 2Kunci Pintu Cabin sebelah kanan Broken.( Proses Perbaikan )', '113 jam 44 menit', 6824, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-306', 'DT-00049 - B 9104 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Seal Crankshaft Bocor; Penggantian seal crankshaft', '121 jam 51 menit', 7311, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-307', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu kota depan', '0 jam 22 menit', 22, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-308', 'DT-00010 - B9117ZYT', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu kabut', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-309', 'WL-31001 - AS5512759', NULL, 'Yard KM 12 Duri', 'Regreasing', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-310', 'DT-04044 - BM9244NU', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan / Perbaikan gantungan Ban Serep', '1 jam 02 menit', 62, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-311', 'DT-04032 - BG8976IX', NULL, 'Borrow Pit Harapan Baru', 'Service berkala - Ganti filter solar 3 Pcs - Ganti filter oli 1 Pcs - Ganti oli - Temuan kerusakan: patah per depan nomor 6 dan 7 sebelah kanan', '106 jam 35 menit', 6395, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-312', 'SD-00005 - 961582391661', NULL, 'Minas Field Project', 'Problem Unit Tidak bisa start. Pencucian filter solar. tersumbat', '9 jam 08 menit', 548, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-313', 'DT-00024 - B9133ZYT', NULL, 'Minas Field Project', 'Lampu rem Tidak nyala', '9 jam 10 menit', 550, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-314', 'DT-00053 - B 9644 KYW', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan muffler yang patah', '1 jam 07 menit', 67, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-315', 'DT-04042 - BM 9287 JO', NULL, 'Borrow Pit Harapan Baru', 'Ganti filter solar bawah 1 Pcs', '0 jam 43 menit', 43, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-316', 'EX-00004 - DBCH 0366', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan lampu depan', '0 jam 57 menit', 57, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-317', 'DT-00057 - B 9914 JYT', NULL, 'Borrow Pit Harapan Baru', 'Pompa hidrolik Trouble', '0 jam 55 menit', 55, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-318', 'MB-00006 -- BM 7680 TU', NULL, 'Yard KM 12 Duri', 'Baterai tekor - Accu Soak', '0 jam 00 menit', 0, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-319', 'DT-00063 - BG 8639 NI', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu belakang 2 Pcs', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-320', 'DT-00021 - B 9130 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan lampu rem belakang mati', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-321', 'DT-00065-BG 8641 NI', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu depan', '0 jam 26 menit', 26, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-322', 'DT-04053 - BM 9824 QO', NULL, 'Borrow Pit Harapan Baru', 'Servis berkala - *Ganti filter solar 1pcs (2 baru diganti) - *Ganti oli mesin - *Ganti filter oli 1 Pcs', '1 jam 03 menit', 63, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-323', 'DT-04025 - BM 9398 JO', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan kaca spion', '0 jam 25 menit', 25, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-324', 'DT-00031 - BG 8535 OW', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan selang angin bocor', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-325', 'DT-00026 - B9135ZYT', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu depan', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-326', 'DT-00012 - B 9121 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Penggantian bola lampu depan', '0 jam 13 menit', 13, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-327', 'DT-00058 - B 9973 BIS', NULL, 'Borrow Pit Harapan Baru', 'Pemasangan baut gantungan knalpot', '0 jam 27 menit', 27, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-328', 'DT-00014 - B 9123 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan Underround Belakang', '1 jam 08 menit', 68, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-329', 'DT-00023 - B9132ZYT', NULL, 'Borrow Pit Harapan Baru', 'PENGELASAN UNDERROUND BELAKANG - Perbaikan selang angin bocor', '0 jam 31 menit', 31, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-330', 'DT-00047 - B 9102 ZYT', NULL, 'Borrow Pit Harapan Baru', 'PENGELASAN UNDERROUND SAMPING SEBELAH KIRI - Perbaikan Om Beng YANG PATAH', '3 jam 26 menit', 206, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-331', 'DT-00004 - B 9112 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan Underround Samping Sebelah Kiri', '0 jam 10 menit', 10, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-332', 'DT-00074 - Z 9109 AB', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan lampu rem belakang mati', '0 jam 41 menit', 41, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-333', 'EX-00012 - DBCH1801', NULL, 'Well Pad 4P48C', 'Low Power, Ganti Filter Solar', '1 jam 57 menit', 117, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-334', 'DT-00054 - B 9683 KYW', NULL, 'Borrow Pit Harapan Baru', 'PASANG KEMBALI Baterai 2 Pcs', '0 jam 32 menit', 32, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-335', 'DT-00050 - B 9105 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Meperbaiki selang minyak balik bocor dan lampu rem , kota', '1 jam 47 menit', 107, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-336', 'DT-00011 - B 9120 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bohlam lampu rem sebelah kanan', '0 jam 25 menit', 25, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-337', 'CS-01 CEMENT SPREADER', NULL, 'Yard KM 12 Duri', 'REGREASING, Lubricatin', '1 jam 22 menit', 82, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-338', 'PF-41001 - Pad foot Vibro Sakai', NULL, 'Yard KM 12 Duri', 'REGREASING, Lubricatin', '1 jam 00 menit', 60, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-339', 'DT-00031 - BG 8535 OW', NULL, 'Site NDD', 'Angin tekor / bocor -> Mengikat selang angin yg bocor lokasil ndd', '0 jam 00 menit', 0, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-340', 'DT-00052 - B 9642 KYW', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan Bagian Pintu Obeng Yang Patah', '1 jam 02 menit', 62, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-341', 'SD-00002 - 961582391716', NULL, 'Site NDD', 'Low power, cuci filter solar', '1 jam 32 menit', 92, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-342', 'DT-00019 - B 9128 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti filter solar bawah 1 Pcs', '0 jam 23 menit', 23, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-343', 'DT-00016 - B 9125 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Ganti bola lampu kabut 1 Pcs', '0 jam 20 menit', 20, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-344', 'DT-04030 - BM 9510 QO', NULL, 'Borrow Pit Harapan Baru', 'Regreasing, pembersihan saringan udara', '0 jam 40 menit', 40, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-345', 'EX-00003 - C51503', NULL, 'Borrow Pit Harapan Baru', 'Regreasing, pembersihan saringan udara. Penggantian filter solar 1 Pcs', '0 jam 30 menit', 30, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-346', 'DT-00057 - B 9914 JYT', NULL, 'Borrow Pit Harapan Baru', 'Safety Valve cylinder dump rusak', '0 jam 00 menit', 0, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-347', 'DT-04044 - BM9244NU', NULL, 'Borrow Pit Harapan Baru', 'Konector selang angin Trouble. Penggantian lampu rem', '0 jam 00 menit', 0, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-348', 'DT-00044 - B9092ZYT', NULL, 'Borrow Pit Harapan Baru', 'Stel belting AC', '0 jam 49 menit', 49, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-349', 'WT-00001 - B9018KFA', NULL, 'Borrow Pit Harapan Baru', 'Accu / Baterai Soak', '0 jam 00 menit', 0, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-350', 'DT-00050 - B 9105 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Kebocoran fuel di selang minyak balik', '0 jam 00 menit', 0, 1, 'Open', 'High', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-351', 'DT-00041-B9082ZYT', NULL, 'Borrow Pit Harapan Baru', 'Regreasing. Daniel Sitepu, USka - PENGELASAN DUDUKAN BAK DUMP (Soleh)', '1 jam 22 menit', 82, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-352', 'DT-00062 - BG8638NI', NULL, 'Borrow Pit Harapan Baru', 'PENGELASAN MUFFLER YANG PATAH DAN UNDERROUND SAMPING - Regreasing', '1 jam 44 menit', 104, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-353', 'DT-00052 - B 9642 KYW', NULL, 'Yard KM 12 Duri', '1 Pintu Om Beng broken: - Engsel Patah - Kunci Pintu bengkok - Body Pintu las retak di beberapa titik. - 2 Lantai Plate Dump robek di beberapa titik', '0 jam 22 menit', 22, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-354', 'EX-00012 - DBCH1801', NULL, 'Well Pad 4P-48C', 'Alat low power. - Kode eror pada panel muncul .L01 - Selang solar tersumbat', '10 jam 15 menit', 615, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-355', 'DT-00056 - B 9892 PYW', NULL, 'Borrow Pit Harapan Baru', 'Oli hydrolik low ( tambah oli hydrolik 5 liter )', '0 jam 06 menit', 6, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-356', 'DT-04044 - BM9244NU', NULL, 'Borrow Pit Harapan Baru', 'Trobel slang angin pecah butuh sambungan slang 14 1pcs - Penggantian konektor selang 15 mm', '5 jam 02 menit', 302, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-357', 'DT-00042 - B 9089 ZYT', NULL, 'Borrow Pit Harapan Baru', 'Regreasing, Pembersihan saringan udara', '0 jam 56 menit', 56, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-358', 'DT-00045-B9093ZYT', NULL, 'Borrow Pit Harapan Baru', 'Regreasing, Pembersihan saringan udara', '0 jam 48 menit', 48, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-359', 'DT-04040 - BM 9509 QO', NULL, 'Borrow Pit Harapan Baru', 'Pengelasan Chacis Dan Sub Chasis Yang Retak', '3 jam 46 menit', 226, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-360', 'MG-00005', NULL, 'Borrow Pit Harapan Baru', 'Regreasing', '0 jam 31 menit', 31, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-361', 'DZ-00002 SN P6G01656', NULL, 'Minas Field Project', 'Lampu rotary Minta diganti. Sudah buram', '0 jam 00 menit', 0, 1, 'In Progress', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-362', 'DT-04030 - BM 9510 QO', NULL, 'Borrow Pit Harapan Baru', 'Perbaikan Kopling slip (stel kopling)', '0 jam 18 menit', 18, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-363', 'Padfoot XCMG XS205PD', NULL, 'Yard KM 12 Duri', 'Instal GPS di Unit', '2 jam 59 menit', 179, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL),
('WO-26-364', 'CS-01 CEMENT SPREADER', NULL, 'Yard KM 12 Duri', 'Instal GPS di Unit', '1 jam 33 menit', 93, 1, 'Closed', 'Normal', 'Belum ada PIC', '2026-08-04 23:42:24', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wo_time_logs`
--

CREATE TABLE `wo_time_logs` (
  `log_id` int(11) NOT NULL,
  `wo_id` varchar(50) NOT NULL,
  `mechanic_user_id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `normal_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `spl_number` varchar(50) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `is_delayed_spareparts` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accidents`
--
ALTER TABLE `accidents`
  ADD PRIMARY KEY (`accident_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `approver_user_id` (`approver_user_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`asset_id`),
  ADD KEY `current_location_id` (`current_location_id`),
  ADD KEY `idx_asset_status` (`status`),
  ADD KEY `idx_asset_category` (`category`),
  ADD KEY `idx_asset_code` (`asset_code`),
  ADD KEY `idx_asset_spatial` (`last_latitude`,`last_longitude`);

--
-- Indexes for table `asset_movements`
--
ALTER TABLE `asset_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `from_location_id` (`from_location_id`),
  ADD KEY `to_location_id` (`to_location_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `battery_logs`
--
ALTER TABLE `battery_logs`
  ADD PRIMARY KEY (`battery_log_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `cost_financial_monthly`
--
ALTER TABLE `cost_financial_monthly`
  ADD PRIMARY KEY (`cost_id`);

--
-- Indexes for table `cutting_bit_logs`
--
ALTER TABLE `cutting_bit_logs`
  ADD PRIMARY KEY (`bit_log_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD PRIMARY KEY (`fuel_log_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `head_kpi_assessments`
--
ALTER TABLE `head_kpi_assessments`
  ADD PRIMARY KEY (`kpi_id`);

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`inspection_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `inspector_id` (`inspector_id`),
  ADD KEY `created_wo_id` (`created_wo_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`),
  ADD UNIQUE KEY `location_name` (`location_name`);

--
-- Indexes for table `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`part_id`),
  ADD UNIQUE KEY `part_number` (`part_number`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `planner_evaluations`
--
ALTER TABLE `planner_evaluations`
  ADD PRIMARY KEY (`eval_id`),
  ADD KEY `planner_user_id` (`planner_user_id`);

--
-- Indexes for table `pm_plans`
--
ALTER TABLE `pm_plans`
  ADD PRIMARY KEY (`pm_plan_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`spb_id`),
  ADD KEY `wo_id` (`wo_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_permission_id`),
  ADD UNIQUE KEY `uk_role_perm` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `telematics_gps_logs`
--
ALTER TABLE `telematics_gps_logs`
  ADD PRIMARY KEY (`gps_log_id`),
  ADD KEY `idx_gps_asset_time` (`asset_id`,`recorded_at`);

--
-- Indexes for table `telematics_logs`
--
ALTER TABLE `telematics_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `tire_inspections`
--
ALTER TABLE `tire_inspections`
  ADD PRIMARY KEY (`tire_inspection_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `unit_valuations`
--
ALTER TABLE `unit_valuations`
  ADD PRIMARY KEY (`valuation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `assigned_location_id` (`assigned_location_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_role_id`),
  ADD UNIQUE KEY `uk_user_role` (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD PRIMARY KEY (`wo_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `verification_supervisor_id` (`verification_supervisor_id`),
  ADD KEY `idx_wo_status` (`status`),
  ADD KEY `idx_wo_priority` (`priority`);

--
-- Indexes for table `wo_time_logs`
--
ALTER TABLE `wo_time_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `wo_id` (`wo_id`),
  ADD KEY `mechanic_user_id` (`mechanic_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_movements`
--
ALTER TABLE `asset_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `battery_logs`
--
ALTER TABLE `battery_logs`
  MODIFY `battery_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cost_financial_monthly`
--
ALTER TABLE `cost_financial_monthly`
  MODIFY `cost_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `cutting_bit_logs`
--
ALTER TABLE `cutting_bit_logs`
  MODIFY `bit_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  MODIFY `fuel_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `head_kpi_assessments`
--
ALTER TABLE `head_kpi_assessments`
  MODIFY `kpi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inspections`
--
ALTER TABLE `inspections`
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=844;

--
-- AUTO_INCREMENT for table `parts`
--
ALTER TABLE `parts`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `planner_evaluations`
--
ALTER TABLE `planner_evaluations`
  MODIFY `eval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pm_plans`
--
ALTER TABLE `pm_plans`
  MODIFY `pm_plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `role_permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `telematics_gps_logs`
--
ALTER TABLE `telematics_gps_logs`
  MODIFY `gps_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `telematics_logs`
--
ALTER TABLE `telematics_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tire_inspections`
--
ALTER TABLE `tire_inspections`
  MODIFY `tire_inspection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `unit_valuations`
--
ALTER TABLE `unit_valuations`
  MODIFY `valuation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `user_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `wo_time_logs`
--
ALTER TABLE `wo_time_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accidents`
--
ALTER TABLE `accidents`
  ADD CONSTRAINT `accidents_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accidents_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL;

--
-- Constraints for table `approvals`
--
ALTER TABLE `approvals`
  ADD CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`current_location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_movements`
--
ALTER TABLE `asset_movements`
  ADD CONSTRAINT `asset_movements_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_movements_ibfk_2` FOREIGN KEY (`from_location_id`) REFERENCES `locations` (`location_id`),
  ADD CONSTRAINT `asset_movements_ibfk_3` FOREIGN KEY (`to_location_id`) REFERENCES `locations` (`location_id`),
  ADD CONSTRAINT `asset_movements_ibfk_4` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `asset_movements_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `battery_logs`
--
ALTER TABLE `battery_logs`
  ADD CONSTRAINT `battery_logs_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `cutting_bit_logs`
--
ALTER TABLE `cutting_bit_logs`
  ADD CONSTRAINT `cutting_bit_logs_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD CONSTRAINT `fuel_logs_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `inspections`
--
ALTER TABLE `inspections`
  ADD CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspections_ibfk_2` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `inspections_ibfk_3` FOREIGN KEY (`created_wo_id`) REFERENCES `work_orders` (`wo_id`) ON DELETE SET NULL;

--
-- Constraints for table `planner_evaluations`
--
ALTER TABLE `planner_evaluations`
  ADD CONSTRAINT `planner_evaluations_ibfk_1` FOREIGN KEY (`planner_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `pm_plans`
--
ALTER TABLE `pm_plans`
  ADD CONSTRAINT `pm_plans_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_ibfk_1` FOREIGN KEY (`wo_id`) REFERENCES `work_orders` (`wo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `telematics_gps_logs`
--
ALTER TABLE `telematics_gps_logs`
  ADD CONSTRAINT `telematics_gps_logs_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `tire_inspections`
--
ALTER TABLE `tire_inspections`
  ADD CONSTRAINT `tire_inspections_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`assigned_location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;

--
-- Constraints for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD CONSTRAINT `work_orders_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_orders_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_orders_ibfk_3` FOREIGN KEY (`verification_supervisor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `wo_time_logs`
--
ALTER TABLE `wo_time_logs`
  ADD CONSTRAINT `wo_time_logs_ibfk_1` FOREIGN KEY (`wo_id`) REFERENCES `work_orders` (`wo_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wo_time_logs_ibfk_2` FOREIGN KEY (`mechanic_user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
