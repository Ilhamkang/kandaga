-- Kandaga v3.1.71 (additive). Select the application database first.
-- Pisahkan antrean Importer Katalog Cetak dan Importer Web tanpa mengubah data katalog.
-- Relasi, identitas, dan audit tetap bersama; hanya batch yang diberi penanda submodul.

SET @ki_col = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_batches' AND column_name = 'engine'
);
SET @ki_sql = IF(
 @ki_col = 0,
 'ALTER TABLE katalog_import_batches ADD COLUMN engine VARCHAR(24) NOT NULL DEFAULT ''catalog'' AFTER source_label',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

-- Batch DREAMSEA lama dibaca sebagai batch Importer Web.
UPDATE katalog_import_batches
SET engine = 'web'
WHERE source_label LIKE 'DREAMSEA%' AND engine = 'catalog';

SET @ki_idx = (
 SELECT COUNT(*) FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_batches' AND index_name = 'uq_import_file'
);
SET @ki_sql = IF(@ki_idx > 0, 'ALTER TABLE katalog_import_batches DROP INDEX uq_import_file', 'SELECT 1');
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;

SET @ki_idx = (
 SELECT COUNT(*) FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'katalog_import_batches' AND index_name = 'uq_import_batch_file'
);
SET @ki_sql = IF(
 @ki_idx = 0,
 'ALTER TABLE katalog_import_batches ADD UNIQUE KEY uq_import_batch_file (engine, file_hash)',
 'SELECT 1'
);
PREPARE ki_stmt FROM @ki_sql;
EXECUTE ki_stmt;
DEALLOCATE PREPARE ki_stmt;
