<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../../includes/inbox-correspondence.php';
kandagaPost(true);
$started = false;
try {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) throw new InvalidArgumentException('ID inbox tidak valid.');
    $conn->begin_transaction(); $started = true;
    $check = $conn->prepare('SELECT id_inbox FROM tb_inbox WHERE id_inbox=? FOR UPDATE');
    $check->bind_param('i', $id); $check->execute(); $exists = $check->get_result()->fetch_assoc(); $check->close();
    if (!$exists) throw new InvalidArgumentException('Pesan tidak ditemukan.');
    if (kandagaInboxThreadSchemaReady($conn)) {
        $stmt = $conn->prepare('DELETE FROM kandaga_inbox_replies WHERE id_inbox=?');
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
        $stmt = $conn->prepare('DELETE FROM kandaga_inbox_threads WHERE id_inbox=?');
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); }
    }
    $stmt = $conn->prepare('DELETE FROM tb_inbox WHERE id_inbox=?');
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $conn->commit(); $started = false;
    $total = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_inbox WHERE status='unread'")->fetch_assoc()['total'];
    kandagaJson(true, 'Pesan berhasil dihapus.', ['total' => $total]);
} catch (Throwable $error) { if ($started) $conn->rollback(); kandagaFailure($error); }
