-- Kandaga v3.1.41: additive catalog structure for future importer support.
-- No existing catalog rows are changed or deleted.

CREATE TABLE IF NOT EXISTS katalog_structure (
  id_naskah INT NOT NULL,
  parent_id INT NULL,
  record_type VARCHAR(24) NOT NULL DEFAULT 'induk',
  unit_label VARCHAR(100) NULL,
  unit_order INT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_naskah),
  KEY idx_katalog_structure_parent (parent_id),
  KEY idx_katalog_structure_type (record_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS katalog_identifiers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_naskah INT NOT NULL,
  scheme VARCHAR(64) NOT NULL,
  identifier_value VARCHAR(255) NOT NULL,
  source_label VARCHAR(255) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_katalog_identifier (id_naskah, scheme, identifier_value),
  KEY idx_katalog_identifier_value (scheme, identifier_value),
  KEY idx_katalog_identifier_naskah (id_naskah)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS katalog_location_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_naskah INT NOT NULL,
  location_kind VARCHAR(32) NOT NULL DEFAULT 'asal',
  place_name VARCHAR(255) NULL,
  district VARCHAR(150) NULL,
  province VARCHAR(150) NULL,
  country VARCHAR(150) NULL,
  period_label VARCHAR(150) NULL,
  certainty VARCHAR(32) NULL,
  source_text TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_katalog_location_naskah (id_naskah, sort_order),
  KEY idx_katalog_location_place (province, district, place_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
