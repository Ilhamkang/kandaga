<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(true);
try {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id || $id === (int)$_SESSION['admin_id']) throw new InvalidArgumentException('Anda tidak dapat menghapus akun sendiri.');
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
    if (!$stmt->affected_rows) throw new InvalidArgumentException('Pengguna tidak ditemukan.');
    echo 'success';
} catch (Throwable $error) { kandagaFailure($error); }
