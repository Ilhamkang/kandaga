<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/page-header.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if (($_SESSION['status'] ?? '') !== 'login' || empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login.php?pesan=belum_login'); exit;
}
$id = (int)$_SESSION['admin_id'];
$stmt = $conn->prepare('SELECT username, role, status_akun, status_verifikasi FROM users WHERE id = ?');
$stmt->bind_param('i', $id); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc();
if (!$user || $user['status_akun'] !== 'aktif' || $user['status_verifikasi'] !== 'verified') {
    $_SESSION = []; session_destroy();
    header('Location: ' . BASE_URL . '/login.php?pesan=akses_ditolak'); exit;
}
$_SESSION['role'] = $user['role']; $_SESSION['username'] = $user['username'];
