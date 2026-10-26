-- Migration: Tambah Pengaturan Dinamis Harga Acuan Solar Industri
-- Tanggal: 26 Oktober 2026

INSERT INTO `operational_settings` (`setting_key`, `setting_value`, `category`, `description`, `updated_at`)
VALUES ('cfg_fuel_price_per_liter', '14500', 'threshold', 'Harga Acuan Solar Industri per Liter (Rp)', NOW())
ON DUPLICATE KEY UPDATE 
    `description` = 'Harga Acuan Solar Industri per Liter (Rp)';
