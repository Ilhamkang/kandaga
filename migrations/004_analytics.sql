-- Kandaga v3.1.3: additive, repeatable migration for MySQL 8 / MariaDB 10.11.
-- Run in the selected application database; no catalog data is replaced.
-- Kandaga v3.1.2: run once in the existing application database.
-- Adds one independent table; does not modify existing catalog tables or data.
CREATE TABLE IF NOT EXISTS website_visits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    browser_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    page_path VARCHAR(255) NOT NULL,
    visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_website_visits_date (visited_at),
    KEY idx_website_visits_browser_date (browser_id, visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='page_key'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `page_key` VARCHAR(255) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='page_type'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `page_type` VARCHAR(32) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='page_title'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `page_title` VARCHAR(255) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='entity_id'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `entity_id` INT DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='ip_address'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='user_agent'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `user_agent` VARCHAR(512) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='device_type'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `device_type` VARCHAR(24) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='browser_name'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `browser_name` VARCHAR(32) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='os_name'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `os_name` VARCHAR(32) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='search_term'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN `search_term` VARCHAR(255) DEFAULT NULL');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_visits' AND index_name='idx_website_page_key'), 'SELECT 1', 'ALTER TABLE website_visits ADD INDEX idx_website_page_key (page_key, visited_at)');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_visits' AND index_name='idx_website_ip'), 'SELECT 1', 'ALTER TABLE website_visits ADD INDEX idx_website_ip (ip_address)');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_visits' AND index_name='idx_website_search'), 'SELECT 1', 'ALTER TABLE website_visits ADD INDEX idx_website_search (search_term)');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;

CREATE TABLE IF NOT EXISTS website_geo_cache (
 ip_address VARCHAR(45) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 country_code CHAR(2) DEFAULT NULL,
 country VARCHAR(100) DEFAULT NULL,
 city VARCHAR(100) DEFAULT NULL,
 region VARCHAR(100) DEFAULT NULL,
 status VARCHAR(16) NOT NULL DEFAULT 'pending',
 checked_at DATETIME DEFAULT NULL,
 retry_after DATETIME DEFAULT NULL,
 PRIMARY KEY (ip_address), KEY idx_geo_pending (status, retry_after)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
