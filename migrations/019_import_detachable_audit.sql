-- Kandaga v3.1.72 (additive, repeatable). Select the application database first.
-- Batch CSV hanya artefak staging: setelah datanya masuk katalog, batch boleh dihapus.
-- Catatan audit dan tautan identitas dilepas dari baris batch agar tetap tersimpan.
-- Tidak mengubah data katalog maupun keputusan moderasi yang sudah ada.

SET @ki_nullable = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_sources'
   AND column_name = 'import_row_id' AND is_nullable = 'YES'
);
SET @ki_sql = IF(
 @ki_nullable = 0,
 'ALTER TABLE katalog_import_sources MODIFY COLUMN import_row_id BIGINT UNSIGNED DEFAULT NULL',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_nullable = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_audit'
   AND column_name = 'import_row_id' AND is_nullable = 'YES'
);
SET @ki_sql = IF(
 @ki_nullable = 0,
 'ALTER TABLE katalog_import_audit MODIFY COLUMN import_row_id BIGINT UNSIGNED DEFAULT NULL',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_col = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_audit' AND column_name = 'source_key'
);
SET @ki_sql = IF(
 @ki_col = 0,
 'ALTER TABLE katalog_import_audit ADD COLUMN source_key CHAR(64) CHARACTER SET ascii DEFAULT NULL AFTER import_row_id',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_col = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_audit' AND column_name = 'batch_id'
);
SET @ki_sql = IF(
 @ki_col = 0,
 'ALTER TABLE katalog_import_audit ADD COLUMN batch_id BIGINT UNSIGNED DEFAULT NULL AFTER source_key',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_col = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_audit' AND column_name = 'filename'
);
SET @ki_sql = IF(
 @ki_col = 0,
 'ALTER TABLE katalog_import_audit ADD COLUMN filename VARCHAR(255) DEFAULT NULL AFTER batch_id',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_col = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_audit' AND column_name = 'engine'
);
SET @ki_sql = IF(
 @ki_col = 0,
 'ALTER TABLE katalog_import_audit ADD COLUMN engine VARCHAR(24) DEFAULT NULL AFTER filename',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_idx = (
 SELECT COUNT(*) FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_audit' AND index_name = 'ix_audit_source'
);
SET @ki_sql = IF(
 @ki_idx = 0,
 'ALTER TABLE katalog_import_audit ADD KEY ix_audit_source (source_key, id)',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_idx = (
 SELECT COUNT(*) FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_audit' AND index_name = 'ix_audit_batch'
);
SET @ki_sql = IF(
 @ki_idx = 0,
 'ALTER TABLE katalog_import_audit ADD KEY ix_audit_batch (batch_id)',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

-- Isi kolom baru pada audit lama agar tetap terbaca setelah batch dihapus.
UPDATE katalog_import_audit a
JOIN katalog_import_rows r ON r.id = a.import_row_id
JOIN katalog_import_batches b ON b.id = r.batch_id
SET a.source_key = r.source_key,
    a.batch_id = b.id,
    a.filename = b.filename,
    a.engine = b.engine
WHERE a.source_key IS NULL AND a.batch_id IS NULL;
