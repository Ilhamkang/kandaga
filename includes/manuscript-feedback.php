<?php
// Helpers only: never creates or changes database structure on a public request.
function kandagaFeedbackReady(mysqli $conn): bool {
    try {
        $conn->query('SELECT id_feedback FROM tb_naskah_feedback LIMIT 0');
        return true;
    } catch (mysqli_sql_exception $error) {
        if ((int)$error->getCode() === 1146) return false;
        throw $error;
    }
}
function kandagaFeedbackEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function kandagaFeedbackInput(array $input, string $key): string {
    return isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : '';
}
function kandagaFeedbackValidate(array $input): array {
    $values = [];
    foreach (['nama', 'email', 'isi_pesan'] as $key) {
        $values[$key] = kandagaFeedbackInput($input, $key);
        if (!mb_check_encoding($values[$key], 'UTF-8') || strpos($values[$key], "\0") !== false) {
            throw new InvalidArgumentException('Masukan mengandung karakter yang tidak valid.');
        }
    }
    if ($values['nama'] === '' || mb_strlen($values['nama'], 'UTF-8') > 150) {
        throw new InvalidArgumentException('Nama wajib diisi, maksimal 150 karakter.');
    }
    if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Masukkan alamat email yang valid, maksimal 254 karakter.');
    }
    if ($values['isi_pesan'] === '' || mb_strlen($values['isi_pesan'], 'UTF-8') > 10000) {
        throw new InvalidArgumentException('Isi pesan wajib diisi, maksimal 10.000 karakter.');
    }
    return $values;
}

function kandagaFeedbackThreadSchemaReady(mysqli $conn): bool {
    static $ready = null;
    if ($ready !== null) return $ready;
    try {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS kandaga_feedback_threads (
  id_feedback BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  thread_status VARCHAR(24) NOT NULL DEFAULT 'open',
  assigned_admin_id BIGINT UNSIGNED NULL,
  archived_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_feedback_thread_status (thread_status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        if (!$conn->query($sql)) return $ready = false;
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS kandaga_naskah_feedback_replies (
  id_reply BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_feedback BIGINT UNSIGNED NOT NULL,
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
  UNIQUE KEY uq_feedback_reply_hash (idempotency_hash),
  KEY idx_feedback_reply_thread (id_feedback, created_at),
  KEY idx_feedback_reply_status (send_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        return $ready = (bool)$conn->query($sql);
    } catch (Throwable $error) {
        error_log('Kandaga feedback thread schema: ' . $error->getMessage());
        return $ready = false;
    }
}

function kandagaFeedbackEnsureThread(mysqli $conn, int $idFeedback, string $status = 'open', ?int $adminId = null, bool $updateExisting = true): void {
    if (!kandagaFeedbackThreadSchemaReady($conn)) throw new RuntimeException('Riwayat korespondensi belum siap.');
    $allowed = ['open', 'replied', 'closed'];
    if (!in_array($status, $allowed, true)) $status = 'open';
    $sql = $updateExisting
        ? 'INSERT INTO kandaga_feedback_threads (id_feedback, thread_status, assigned_admin_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE thread_status=VALUES(thread_status), assigned_admin_id=VALUES(assigned_admin_id)'
        : 'INSERT IGNORE INTO kandaga_feedback_threads (id_feedback, thread_status, assigned_admin_id) VALUES (?, ?, ?)';
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException('Thread feedback tidak dapat disiapkan.');
    $stmt->bind_param('isi', $idFeedback, $status, $adminId);
    $stmt->execute();
    $stmt->close();
}

function kandagaFeedbackThread(mysqli $conn, int $idFeedback): array {
    if (!kandagaFeedbackThreadSchemaReady($conn)) throw new RuntimeException('Riwayat korespondensi belum siap.');
    $stmt = $conn->prepare('SELECT * FROM tb_naskah_feedback WHERE id_feedback=? LIMIT 1');
    $stmt->bind_param('i', $idFeedback); $stmt->execute();
    $feedback = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$feedback) throw new InvalidArgumentException('Feedback tidak ditemukan.');
    kandagaFeedbackEnsureThread($conn, $idFeedback, 'open', null, false);
    $stmt = $conn->prepare('SELECT id_reply,id_feedback,admin_id,admin_username,recipient_email,subject,body,send_status,message_id,error_message,created_at,sent_at FROM kandaga_naskah_feedback_replies WHERE id_feedback=? ORDER BY created_at ASC, id_reply ASC');
    $stmt->bind_param('i', $idFeedback); $stmt->execute();
    $replies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    $stmt = $conn->prepare('SELECT thread_status,assigned_admin_id,archived_at,updated_at FROM kandaga_feedback_threads WHERE id_feedback=? LIMIT 1');
    $stmt->bind_param('i', $idFeedback); $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc() ?: ['thread_status' => 'open']; $stmt->close();
    return ['feedback' => $feedback, 'replies' => $replies, 'meta' => $meta];
}
