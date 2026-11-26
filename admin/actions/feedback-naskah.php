<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../../includes/manuscript-feedback.php';
kandagaPost(true);
$started = false;
try {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['action'] ?? '';
    if (!$id || !in_array($action, ['read', 'unread', 'toggle', 'delete', 'close', 'reopen'], true)) {
        throw new InvalidArgumentException('Permintaan feedback tidak valid.');
    }
    $conn->begin_transaction(); $started = true;
    $stmt = $conn->prepare('SELECT status FROM tb_naskah_feedback WHERE id_feedback = ? FOR UPDATE');
    $stmt->bind_param('i', $id); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row) throw new InvalidArgumentException('Feedback tidak ditemukan.');
    $status = $action === 'toggle' ? ($row['status'] === 'read' ? 'unread' : 'read') : $action;
    if (in_array($action, ['close', 'reopen'], true)) {
        kandagaFeedbackEnsureThread($conn, (int)$id, $action === 'close' ? 'closed' : 'open', (int)($_SESSION['admin_id'] ?? 0));
        $conn->commit(); $started = false;
        $total = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_naskah_feedback WHERE status='unread'")->fetch_assoc()['total'];
        kandagaJson(true, 'Status thread diperbarui.', ['feedback_status' => $row['status'], 'total' => $total]);
    } elseif ($action === 'delete') {
        if (kandagaFeedbackThreadSchemaReady($conn)) {
            $cleanup = $conn->prepare('DELETE FROM kandaga_naskah_feedback_replies WHERE id_feedback=?');
            if ($cleanup) { $cleanup->bind_param('i', $id); $cleanup->execute(); $cleanup->close(); }
            $cleanup = $conn->prepare('DELETE FROM kandaga_feedback_threads WHERE id_feedback=?');
            if ($cleanup) { $cleanup->bind_param('i', $id); $cleanup->execute(); $cleanup->close(); }
        }
        $stmt = $conn->prepare('DELETE FROM tb_naskah_feedback WHERE id_feedback = ?');
        $stmt->bind_param('i', $id);
    } else {
        $stmt = $conn->prepare('UPDATE tb_naskah_feedback SET status = ? WHERE id_feedback = ?');
        $stmt->bind_param('si', $status, $id);
    }
    $stmt->execute(); $stmt->close(); $conn->commit(); $started = false;
    $total = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_naskah_feedback WHERE status='unread'")->fetch_assoc()['total'];
    kandagaJson(true, $action === 'delete' ? 'Feedback dihapus.' : 'Status feedback diperbarui.', ['feedback_status' => $status, 'total' => $total]);
} catch (Throwable $error) { if ($started) $conn->rollback(); kandagaFailure($error); }
