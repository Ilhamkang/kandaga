<?php
/**
 * Contact inbox validation and lazy correspondence storage.
 * The legacy tb_inbox table remains unchanged; replies live in separate tables.
 */
require_once __DIR__ . '/mail-settings.php';

function kandagaContactValidate(array $input): array
{
    $values = [];
    foreach (['nama', 'email', 'subjek', 'isi_pesan'] as $key) {
        $value = isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : '';
        if (!mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) throw new InvalidArgumentException('Masukan kontak mengandung karakter yang tidak valid.');
        $values[$key] = $value;
    }
    if ($values['nama'] === '' || mb_strlen($values['nama'], 'UTF-8') > 150) throw new InvalidArgumentException('Nama wajib diisi, maksimal 150 karakter.');
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 254) throw new InvalidArgumentException('Masukkan alamat email yang valid.');
    if ($values['subjek'] === '' || mb_strlen($values['subjek'], 'UTF-8') > 255 || str_contains($values['subjek'], "\r") || str_contains($values['subjek'], "\n")) throw new InvalidArgumentException('Subjek wajib diisi, maksimal 255 karakter.');
    if ($values['isi_pesan'] === '' || mb_strlen($values['isi_pesan'], 'UTF-8') > 10000) throw new InvalidArgumentException('Isi pesan wajib diisi, maksimal 10.000 karakter.');
    return $values;
}

function kandagaInboxThreadSchemaReady(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;
    try {
        $threads = <<<'SQL'
CREATE TABLE IF NOT EXISTS kandaga_inbox_threads (
  id_inbox BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  thread_status VARCHAR(24) NOT NULL DEFAULT 'open',
  assigned_admin_id BIGINT UNSIGNED NULL,
  archived_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_inbox_thread_status (thread_status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        if (!$conn->query($threads)) return $ready = false;
        $replies = <<<'SQL'
CREATE TABLE IF NOT EXISTS kandaga_inbox_replies (
  id_reply BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_inbox BIGINT UNSIGNED NOT NULL,
  admin_id BIGINT UNSIGNED NULL,
  admin_username VARCHAR(150) NULL,
  recipient_email VARCHAR(254) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  send_status VARCHAR(16) NOT NULL DEFAULT 'pending',
  idempotency_hash CHAR(64) NOT NULL,
  message_id VARCHAR(255) NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  UNIQUE KEY uq_inbox_reply_hash (idempotency_hash),
  KEY idx_inbox_reply_thread (id_inbox, created_at),
  KEY idx_inbox_reply_status (send_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        return $ready = (bool)$conn->query($replies);
    } catch (Throwable $error) {
        error_log('Kandaga inbox correspondence schema: ' . $error->getMessage());
        return $ready = false;
    }
}

function kandagaInboxEnsureThread(mysqli $conn, int $idInbox, string $status = 'open', ?int $adminId = null, bool $updateExisting = true): void
{
    if (!kandagaInboxThreadSchemaReady($conn)) throw new RuntimeException('Riwayat inbox belum siap.');
    if (!in_array($status, ['open', 'replied', 'closed'], true)) $status = 'open';
    $sql = $updateExisting
        ? 'INSERT INTO kandaga_inbox_threads (id_inbox,thread_status,assigned_admin_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE thread_status=VALUES(thread_status), assigned_admin_id=VALUES(assigned_admin_id)'
        : 'INSERT IGNORE INTO kandaga_inbox_threads (id_inbox,thread_status,assigned_admin_id) VALUES (?,?,?)';
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException('Thread inbox tidak dapat disiapkan.');
    $stmt->bind_param('isi', $idInbox, $status, $adminId);
    $stmt->execute(); $stmt->close();
}

function kandagaInboxThread(mysqli $conn, int $idInbox): array
{
    if (!kandagaInboxThreadSchemaReady($conn)) throw new RuntimeException('Riwayat inbox belum siap.');
    $stmt = $conn->prepare('SELECT * FROM tb_inbox WHERE id_inbox=? LIMIT 1');
    $stmt->bind_param('i', $idInbox); $stmt->execute(); $message = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$message) throw new InvalidArgumentException('Pesan tidak ditemukan.');
    kandagaInboxEnsureThread($conn, $idInbox, 'open', null, false);
    $stmt = $conn->prepare('SELECT id_reply,id_inbox,admin_id,admin_username,recipient_email,subject,body,send_status,message_id,error_message,created_at,sent_at FROM kandaga_inbox_replies WHERE id_inbox=? ORDER BY created_at ASC,id_reply ASC');
    $stmt->bind_param('i', $idInbox); $stmt->execute(); $replies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    $stmt = $conn->prepare('SELECT thread_status,assigned_admin_id,archived_at,updated_at FROM kandaga_inbox_threads WHERE id_inbox=? LIMIT 1');
    $stmt->bind_param('i', $idInbox); $stmt->execute(); $meta = $stmt->get_result()->fetch_assoc() ?: ['thread_status' => 'open']; $stmt->close();
    return ['message' => $message, 'replies' => $replies, 'meta' => $meta];
}
