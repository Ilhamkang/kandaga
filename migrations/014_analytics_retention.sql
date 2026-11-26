-- Kandaga v3.1.44: compact daily analytics and bounded raw-log retention.
-- Additive and idempotent. Existing catalog and historical visit rows are not
-- removed by this migration; cleanup starts only after the application sees
-- a row older than the configured retention period.
CREATE TABLE IF NOT EXISTS website_visit_daily (
  day DATE NOT NULL,
  human_views BIGINT UNSIGNED NOT NULL DEFAULT 0,
  unique_visitors BIGINT UNSIGNED NOT NULL DEFAULT 0,
  search_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS website_analytics_retention (
  id TINYINT UNSIGNED NOT NULL,
  retention_days SMALLINT UNSIGNED NOT NULL DEFAULT 90,
  bot_retention_days SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  last_run_at DATETIME NULL,
  cursor_day DATE NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO website_analytics_retention (id,retention_days)
VALUES (1,90);

SET @kandaga_retention_column = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_analytics_retention' AND column_name='bot_retention_days'),
  'SELECT 1',
  'ALTER TABLE website_analytics_retention ADD COLUMN bot_retention_days SMALLINT UNSIGNED NOT NULL DEFAULT 7 AFTER retention_days'
);
PREPARE kandaga_retention_stmt FROM @kandaga_retention_column;
EXECUTE kandaga_retention_stmt;
DEALLOCATE PREPARE kandaga_retention_stmt;
