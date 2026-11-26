-- Kandaga v3.1.6. Additive, repeatable: MySQL 8 / MariaDB 10.11.
-- Select the application database first. Does not alter existing catalog data.
CREATE TABLE IF NOT EXISTS katalog_import_batches (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 filename VARCHAR(255) NOT NULL,
 file_hash CHAR(64) CHARACTER SET ascii NOT NULL,
 source_label VARCHAR(255) NOT NULL,
 bibliography_id INT DEFAULT NULL,
 mapping_json LONGTEXT NOT NULL,
 created_by VARCHAR(50) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_import_file (file_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS katalog_import_rows (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 batch_id BIGINT UNSIGNED NOT NULL,
 source_row INT NOT NULL,
 source_key CHAR(64) CHARACTER SET ascii NOT NULL,
 raw_json LONGTEXT NOT NULL,
 draft_json LONGTEXT NOT NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'pending',
 target_id INT DEFAULT NULL,
 reviewed_by VARCHAR(50) DEFAULT NULL,
 reviewed_at DATETIME DEFAULT NULL,
 note TEXT DEFAULT NULL,
 UNIQUE KEY uq_import_row (batch_id, source_row),
 KEY ix_import_queue (batch_id, status, id),
 KEY ix_import_source (source_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS katalog_import_sources (
 source_key CHAR(64) CHARACTER SET ascii NOT NULL PRIMARY KEY,
 katalog_id INT NOT NULL,
 import_row_id BIGINT UNSIGNED NOT NULL,
 linked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_source_catalog (katalog_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS katalog_import_audit (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 import_row_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(24) NOT NULL,
 katalog_id INT DEFAULT NULL,
 before_json LONGTEXT DEFAULT NULL,
 after_json LONGTEXT DEFAULT NULL,
 decision_json LONGTEXT NOT NULL,
 username VARCHAR(50) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_audit_row (import_row_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
