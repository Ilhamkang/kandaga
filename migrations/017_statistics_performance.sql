-- Kandaga v3.1.50: bounded statistics dashboard performance.
-- Additive and idempotent. No catalog, visit, or geo-cache rows are removed.
SET @kandaga_stats_page_index = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_visits' AND index_name='idx_website_traffic_page_date'),
  'SELECT 1',
  'ALTER TABLE website_visits ADD INDEX idx_website_traffic_page_date (is_bot, traffic_class, visited_at, page_key)'
);
PREPARE kandaga_stats_page_stmt FROM @kandaga_stats_page_index;
EXECUTE kandaga_stats_page_stmt;
DEALLOCATE PREPARE kandaga_stats_page_stmt;
