-- Kandaga v3.1.48: consistent visitor identity, verified presence, and
-- period-safe analytics. Additive and idempotent.
CREATE TABLE IF NOT EXISTS website_visitor_days (
  day DATE NOT NULL,
  visitor_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  first_seen DATETIME NOT NULL,
  last_seen DATETIME NOT NULL,
  PRIMARY KEY (day, visitor_hash),
  KEY idx_visitor_days_visitor (visitor_hash, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @kandaga_presence_verified = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_presence' AND column_name='presence_verified'),
  'SELECT 1',
  'ALTER TABLE website_presence ADD COLUMN presence_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER is_bot'
);
PREPARE kandaga_presence_verified_stmt FROM @kandaga_presence_verified;
EXECUTE kandaga_presence_verified_stmt;
DEALLOCATE PREPARE kandaga_presence_verified_stmt;

SET @kandaga_presence_heartbeat = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_presence' AND column_name='last_heartbeat'),
  'SELECT 1',
  'ALTER TABLE website_presence ADD COLUMN last_heartbeat DATETIME NULL AFTER last_seen'
);
PREPARE kandaga_presence_heartbeat_stmt FROM @kandaga_presence_heartbeat;
EXECUTE kandaga_presence_heartbeat_stmt;
DEALLOCATE PREPARE kandaga_presence_heartbeat_stmt;

SET @kandaga_presence_index = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_presence' AND index_name='idx_presence_verified'),
  'SELECT 1',
  'ALTER TABLE website_presence ADD INDEX idx_presence_verified (last_seen, presence_verified, is_bot, traffic_class)'
);
PREPARE kandaga_presence_index_stmt FROM @kandaga_presence_index;
EXECUTE kandaga_presence_index_stmt;
DEALLOCATE PREPARE kandaga_presence_index_stmt;

-- Existing rows have no proof of JavaScript activity and therefore remain
-- unverified until the browser sends a heartbeat.
UPDATE website_presence SET presence_verified=0 WHERE presence_verified IS NULL;

SET @kandaga_retention_backfill_cursor = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_analytics_retention' AND column_name='visitor_backfill_cursor'),
  'SELECT 1',
  'ALTER TABLE website_analytics_retention ADD COLUMN visitor_backfill_cursor BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER cursor_day'
);
PREPARE kandaga_retention_backfill_stmt FROM @kandaga_retention_backfill_cursor;
EXECUTE kandaga_retention_backfill_stmt;
DEALLOCATE PREPARE kandaga_retention_backfill_stmt;
