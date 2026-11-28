-- Kandaga (additive, repeatable). Select the application database first.
-- Galeri gambar artikel: satu baris per gambar tambahan yang dipilih dari
-- Pustaka Media. Gambar utama tetap berada di tb_kabar.thumbnail.
CREATE TABLE IF NOT EXISTS tb_kabar_gambar (
  id_gambar BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_kabar INT NOT NULL,
  url_gambar VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  alt_gambar VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  urutan INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_kabar_gambar_post (id_kabar, urutan),
  KEY idx_kabar_gambar_url (url_gambar(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
