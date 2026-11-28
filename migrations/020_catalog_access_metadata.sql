-- Kandaga (additive, repeatable). Select the application database first.
-- Menambahkan keterangan akses naskah fisik dan status arsip digital.
-- Tidak mengubah data katalog maupun field penyedia/URL yang sudah ada.

SET @ki_col = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'katalog'
    AND column_name = 'mode_akses_fisik'
);
SET @ki_sql = IF(
  @ki_col = 0,
  'ALTER TABLE katalog ADD COLUMN mode_akses_fisik VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL AFTER penyedia_akses',
  'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_col = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'katalog'
    AND column_name = 'pihak_kontak'
);
SET @ki_sql = IF(
  @ki_col = 0,
  'ALTER TABLE katalog ADD COLUMN pihak_kontak VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL AFTER mode_akses_fisik',
  'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_col = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'katalog'
    AND column_name = 'status_arsip_digital'
);
SET @ki_sql = IF(
  @ki_col = 0,
  'ALTER TABLE katalog ADD COLUMN status_arsip_digital VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL AFTER pihak_kontak',
  'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_col = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'katalog'
    AND column_name = 'keterangan_arsip_digital'
);
SET @ki_sql = IF(
  @ki_col = 0,
  'ALTER TABLE katalog ADD COLUMN keterangan_arsip_digital VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL AFTER status_arsip_digital',
  'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;
