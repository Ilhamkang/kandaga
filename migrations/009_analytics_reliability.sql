-- Kandaga v3.1.37: visitor tokens and traffic classification.
-- Additive and repeatable for MySQL 8 / MariaDB 10.11.
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='visitor_token'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN visitor_token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL AFTER browser_id');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='is_bot'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN is_bot TINYINT(1) NOT NULL DEFAULT 0 AFTER search_term');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='traffic_class'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN traffic_class VARCHAR(24) NOT NULL DEFAULT ''human'' AFTER is_bot');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='referrer'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN referrer VARCHAR(512) DEFAULT NULL AFTER traffic_class');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='request_hash'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN request_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL AFTER referrer');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_visits' AND index_name='idx_website_visitor_date'), 'SELECT 1', 'ALTER TABLE website_visits ADD INDEX idx_website_visitor_date (visitor_token, visited_at)');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_visits' AND index_name='idx_website_traffic'), 'SELECT 1', 'ALTER TABLE website_visits ADD INDEX idx_website_traffic (is_bot, traffic_class, visited_at)');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
UPDATE website_visits SET traffic_class='human', is_bot=0 WHERE traffic_class IS NULL OR traffic_class='';
