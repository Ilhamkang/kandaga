-- Kandaga (additive, repeatable). Select the application database first.
-- Menambahkan topik pesan masuk. Baris lama otomatis memakai "Umum".
-- Tidak mengubah struktur atau isi kolom tb_inbox yang sudah ada.

SET @ki_col = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'tb_inbox'
    AND column_name = 'topik'
);
SET @ki_sql = IF(
  @ki_col = 0,
  'ALTER TABLE tb_inbox ADD COLUMN topik VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''Umum'' AFTER subjek',
  'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_idx = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'tb_inbox'
    AND index_name = 'idx_inbox_topik'
);
SET @ki_sql = IF(
  @ki_idx = 0,
  'ALTER TABLE tb_inbox ADD INDEX idx_inbox_topik (topik)',
  'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;
