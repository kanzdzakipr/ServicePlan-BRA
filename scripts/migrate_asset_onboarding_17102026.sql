-- Additive migration. No asset rows, existing PM plans, or legacy aliases are overwritten.
SET @db_name = DATABASE();
SET @table_name = 'assets';

DROP PROCEDURE IF EXISTS add_asset_onboarding_column;
DELIMITER $$
CREATE PROCEDURE add_asset_onboarding_column(IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'assets' AND column_name = col_name) THEN
    SET @statement = CONCAT('ALTER TABLE assets ADD COLUMN ', column_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL add_asset_onboarding_column('current_operator_name', 'current_operator_name VARCHAR(150) NULL');
CALL add_asset_onboarding_column('last_service_date', 'last_service_date DATETIME NULL');
CALL add_asset_onboarding_column('last_service_hm_km', 'last_service_hm_km DECIMAL(12,2) NULL');
CALL add_asset_onboarding_column('service_interval_val', 'service_interval_val INT NULL');
CALL add_asset_onboarding_column('service_interval_unit', "service_interval_unit ENUM('HM','KM') NULL");
CALL add_asset_onboarding_column('daily_usage_coefficient', 'daily_usage_coefficient DECIMAL(8,2) NULL');
CALL add_asset_onboarding_column('next_service_due_hm_km', 'next_service_due_hm_km DECIMAL(12,2) NULL');
CALL add_asset_onboarding_column('next_service_estimated_date', 'next_service_estimated_date DATE NULL');
CALL add_asset_onboarding_column('legal_stnk_expiry', 'legal_stnk_expiry DATE NULL');
CALL add_asset_onboarding_column('legal_kir_expiry', 'legal_kir_expiry DATE NULL');
CALL add_asset_onboarding_column('legal_silo_expiry', 'legal_silo_expiry DATE NULL');
CALL add_asset_onboarding_column('service_manual_pdf_url', 'service_manual_pdf_url VARCHAR(255) NULL');
DROP PROCEDURE add_asset_onboarding_column;

CREATE TABLE IF NOT EXISTS asset_operator_history (
  history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id VARCHAR(100) NOT NULL,
  operator_name VARCHAR(150) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  shift_type VARCHAR(50) NULL,
  handover_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_operator_asset (asset_id, start_date),
  FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_service_history (
  service_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id VARCHAR(100) NOT NULL,
  service_sequence INT NOT NULL,
  service_date DATETIME NOT NULL,
  service_hm_km DECIMAL(12,2) NOT NULL,
  service_type VARCHAR(100) NOT NULL,
  mechanic_pic VARCHAR(150) NULL,
  jo_report_number VARCHAR(100) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_service_asset (asset_id, service_date),
  FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_gallery_photos (
  photo_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id VARCHAR(100) NOT NULL,
  photo_title VARCHAR(150) NOT NULL,
  photo_angle ENUM('Front','Right','Left','Cabin','Engine','SerialPlate','Other') NOT NULL DEFAULT 'Other',
  photo_url VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_photo_asset (asset_id, uploaded_at),
  FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
