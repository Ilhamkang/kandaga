<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../../includes/inbox-correspondence.php';
kandagaPost(true);
$started = false;
try {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['aksi'] ?? '';
    if (!$id || !in_array($action, ['read', 'unread', 'toggle', 'close', 'reopen'], true)) throw new InvalidArgumentException('Permintaan inbox tidak valid.');
    $conn->begin_transaction(); $started = true;
    $stmt = $conn->prepare('SELECT status FROM tb_inbox WHERE id_inbox = ? FOR UPDATE');
    $stmt->bind_param('i', $id); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row) throw new InvalidArgumentException('Pesan tidak ditemukan.');
    if (in_array($action, ['close', 'reopen'], true)) {
        kandagaInboxEnsureThread($conn, (int)$id, $action === 'close' ? 'closed' : 'open', (int)($_SESSION['admin_id'] ?? 0));
        $conn->commit(); $started = false;
        $total = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_inbox WHERE status = 'unread'")->fetch_assoc()['total'];
        kandagaJson(true, 'Status thread diperbarui.', ['status' => $row['status'], 'thread_status' => $action === 'close' ? 'closed' : 'open', 'total' => $total]);
    }
    $status = $action === 'toggle' ? ($row['status'] === 'read' ? 'unread' : 'read') : $action;
    $stmt = $conn->prepare('UPDATE tb_inbox SET status = ? WHERE id_inbox = ?');
    $stmt->bind_param('si', $status, $id); $stmt->execute(); $stmt->close(); $conn->commit(); $started = false;
    $total = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_inbox WHERE status = 'unread'")->fetch_assoc()['total'];
    kandagaJson(true, 'Status pesan diperbarui.', ['status' => $status, 'total' => $total]);
} catch (Throwable $error) { if ($started) $conn->rollback(); kandagaFailure($error); }
