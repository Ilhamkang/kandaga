-- Kandaga v3.1.9. Pilih database Kandaga sebelum impor di phpMyAdmin.
-- Hanya menambah tabel feedback. Tidak mengubah katalog, users, atau tb_inbox.
-- Dapat dijalankan kembali pada MySQL 8 / MariaDB 10.11.
CREATE TABLE IF NOT EXISTS tb_naskah_feedback (
 id_feedback BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 id_naskah INT NOT NULL,
 judul_naskah TEXT NOT NULL,
 kode_inventaris TEXT NOT NULL,
 nama VARCHAR(150) NOT NULL,
 email VARCHAR(254) NOT NULL,
 isi_pesan TEXT NOT NULL,
 status ENUM('unread','read') NOT NULL DEFAULT 'unread',
 submission_token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_feedback_submission (submission_token),
 KEY ix_feedback_status_date (status, created_at, id_feedback),
 KEY ix_feedback_manuscript (id_naskah)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
