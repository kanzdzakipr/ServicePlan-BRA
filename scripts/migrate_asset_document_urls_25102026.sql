-- Migration: Add silo_pdf_url, stnk_pdf_url, kir_pdf_url to assets
SET @db_name = DATABASE();

DROP PROCEDURE IF EXISTS add_asset_doc_url_column;
DELIMITER $$
CREATE PROCEDURE add_asset_doc_url_column(IN col_name VARCHAR(64), IN column_ddl VARCHAR(500))
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'assets' AND column_name = col_name
  ) THEN
    SET @statement = CONCAT('ALTER TABLE assets ADD COLUMN ', column_ddl);
    PREPARE stmt FROM @statement;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL add_asset_doc_url_column('silo_pdf_url', 'silo_pdf_url VARCHAR(255) NULL');
CALL add_asset_doc_url_column('stnk_pdf_url', 'stnk_pdf_url VARCHAR(255) NULL');
CALL add_asset_doc_url_column('kir_pdf_url', 'kir_pdf_url VARCHAR(255) NULL');

DROP PROCEDURE IF EXISTS add_asset_doc_url_column;
