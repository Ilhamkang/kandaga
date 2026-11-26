<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../../includes/manuscript-feedback.php';
require_once __DIR__ . '/../../includes/smtp-mailer.php';
kandagaPost(true);
$started = false; $replyId = null;
try {
    $id = filter_var($_POST['id_feedback'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $subject = trim((string)($_POST['subject'] ?? ''));
    $body = trim((string)($_POST['body'] ?? ''));
    if (!$id || $subject === '' || mb_strlen($subject, 'UTF-8') > 255 || $body === '' || mb_strlen($body, 'UTF-8') > 10000) throw new InvalidArgumentException('Subjek dan isi balasan wajib diisi.');
    if (str_contains($subject, "\r") || str_contains($subject, "\n") || str_contains($body, "\0")) throw new InvalidArgumentException('Balasan mengandung karakter yang tidak valid.');
    $stmt = $conn->prepare('SELECT id_feedback,email,nama,judul_naskah FROM tb_naskah_feedback WHERE id_feedback=? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute(); $feedback = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$feedback || !filter_var($feedback['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Alamat email feedback tidak valid.');
    if (!kandagaFeedbackThreadSchemaReady($conn)) throw new RuntimeException('Tabel korespondensi belum tersedia.');
    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $adminName = trim((string)($_SESSION['username'] ?? $_SESSION['nama'] ?? 'Administrator'));
    $hash = hash('sha256', $id . '|' . $feedback['email'] . '|' . $subject . '|' . $body);
    $conn->begin_transaction(); $started = true;
    $stmt = $conn->prepare('SELECT id_reply,send_status FROM kandaga_naskah_feedback_replies WHERE idempotency_hash=? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('s', $hash); $stmt->execute(); $existing = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($existing && $existing['send_status'] === 'sent') {
        $conn->commit(); $started = false;
        kandagaJson(true, 'Balasan ini sudah terkirim.', ['already_sent' => true, 'id_reply' => (int)$existing['id_reply']]);
    }
    if ($existing) {
        $replyId = (int)$existing['id_reply'];
        $stmt = $conn->prepare("UPDATE kandaga_naskah_feedback_replies SET send_status='pending', error_message=NULL, subject=?, body=?, recipient_email=?, admin_id=?, admin_username=? WHERE id_reply=?");
        $stmt->bind_param('sssisi', $subject, $body, $feedback['email'], $adminId, $adminName, $replyId);
    } else {
        $stmt = $conn->prepare("INSERT INTO kandaga_naskah_feedback_replies (id_feedback,admin_id,admin_username,recipient_email,subject,body,send_status,idempotency_hash) VALUES (?,?,?,?,?,?, 'pending', ?)");
        $stmt->bind_param('iisssss', $id, $adminId, $adminName, $feedback['email'], $subject, $body, $hash);
    }
    $stmt->execute(); if (!$replyId) $replyId = (int)$conn->insert_id; $stmt->close();
    $conn->commit(); $started = false;
    try {
        $mail = kandagaSmtpSend($conn, $feedback['email'], $subject, $body);
        $stmt = $conn->prepare("UPDATE kandaga_naskah_feedback_replies SET send_status='sent',message_id=?,sent_at=NOW(),error_message=NULL WHERE id_reply=?");
        $stmt->bind_param('si', $mail['message_id'], $replyId); $stmt->execute(); $stmt->close();
        kandagaFeedbackEnsureThread($conn, (int)$id, 'replied', $adminId);
        kandagaJson(true, 'Balasan terkirim.', ['id_reply' => $replyId, 'message_id' => $mail['message_id']]);
    } catch (Throwable $mailError) {
        $safe = mb_substr($mailError->getMessage(), 0, 500, 'UTF-8');
        $stmt = $conn->prepare("UPDATE kandaga_naskah_feedback_replies SET send_status='failed',error_message=? WHERE id_reply=?");
        $stmt->bind_param('si', $safe, $replyId); $stmt->execute(); $stmt->close();
        throw new RuntimeException('Balasan gagal dikirim. Periksa pengaturan SMTP dan coba lagi.');
    }
} catch (Throwable $error) { if ($started) $conn->rollback(); kandagaFailure($error); }
