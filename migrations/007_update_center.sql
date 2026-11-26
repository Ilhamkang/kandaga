-- Kandaga v3.1.10. Also installed by the authorized Update Center setup action.
CREATE TABLE IF NOT EXISTS kandaga_schema_migrations (
 migration_id VARCHAR(100) CHARACTER SET ascii NOT NULL PRIMARY KEY,
 checksum CHAR(64) CHARACTER SET ascii NOT NULL,
 applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 method VARCHAR(24) NOT NULL,
 job_id CHAR(32) CHARACTER SET ascii DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
