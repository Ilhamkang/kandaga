-- Kandaga v3.1.38: named crawler taxonomy.
-- Additive and repeatable for MySQL 8 / MariaDB 10.11.
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='bot_name'), 'SELECT 1', 'ALTER TABLE website_visits ADD COLUMN bot_name VARCHAR(80) NOT NULL DEFAULT ''Human'' AFTER traffic_class');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_visits' AND index_name='idx_website_bot_name'), 'SELECT 1', 'ALTER TABLE website_visits ADD INDEX idx_website_bot_name (is_bot, bot_name, visited_at)');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
UPDATE website_visits SET bot_name=CASE WHEN COALESCE(is_bot,0)=0 THEN 'Human' ELSE 'Unknown crawler' END WHERE bot_name IS NULL OR bot_name='';
