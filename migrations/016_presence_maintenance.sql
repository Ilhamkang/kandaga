-- Kandaga v3.1.49: presence query maintenance.
-- Additive and idempotent. No visit or catalog rows are removed here;
-- stale presence tokens are cleaned in bounded application ticks.
SET @kandaga_presence_heartbeat_index = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='website_presence' AND index_name='idx_presence_heartbeat'),
  'SELECT 1',
  'ALTER TABLE website_presence ADD INDEX idx_presence_heartbeat (last_heartbeat, presence_verified, is_bot, traffic_class)'
);
PREPARE kandaga_presence_heartbeat_index_stmt FROM @kandaga_presence_heartbeat_index;
EXECUTE kandaga_presence_heartbeat_index_stmt;
DEALLOCATE PREPARE kandaga_presence_heartbeat_index_stmt;
