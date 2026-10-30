-- Lossless archive for every Markdown source under material/.
-- Structured feature migrations may reference these rows through relative_path.
CREATE TABLE IF NOT EXISTS `material_documents` (
  `document_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `relative_path` VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_markdown` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_sha256` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `content_bytes` BIGINT UNSIGNED NOT NULL,
  `source_modified_at` DATETIME NOT NULL,
  `mapping_target` VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'document_archive',
  `source_exists` TINYINT(1) NOT NULL DEFAULT 1,
  `synced_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  UNIQUE KEY `uq_material_documents_path` (`relative_path`),
  KEY `idx_material_documents_category` (`category`,`source_exists`),
  KEY `idx_material_documents_hash` (`content_sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_data_migrations` (`migration_key`, `applied_at`)
VALUES ('MATERIAL_MARKDOWN_ARCHIVE_V1', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `applied_at` = VALUES(`applied_at`);
