-- Kandaga v3.1.40: stable anonymous presence for the public footer.
-- Page views remain in website_visits; this table stores only the latest
-- heartbeat per persistent visitor token and is safe to run repeatedly.
CREATE TABLE IF NOT EXISTS website_presence (
  visitor_token CHAR(64) NOT NULL,
  last_seen DATETIME NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(512) NULL,
  traffic_class VARCHAR(32) NOT NULL DEFAULT 'human',
  is_bot TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (visitor_token),
  KEY idx_presence_active (last_seen, is_bot, traffic_class)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM website_presence
WHERE last_seen < DATE_SUB(NOW(), INTERVAL 2 DAY);
