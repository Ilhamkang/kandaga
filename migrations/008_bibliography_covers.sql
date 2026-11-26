CREATE TABLE IF NOT EXISTS tb_bibliografi_media (
  id_bibliografi INT NOT NULL,
  cover_filename VARCHAR(255) NULL,
  cover_url TEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_bibliografi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
