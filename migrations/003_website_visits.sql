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
