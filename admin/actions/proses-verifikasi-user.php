<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(true);

require_once __DIR__ . '/../../config/bootstrap.php';
/**
 * proses-verifikasi-user.php
 * Menangani update peran dan status verifikasi user dari modal
 */

// 1. Session & Auth Check
// Pastikan hanya admin yang bisa mengakses file ini
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    exit('error'); // Hentikan proses jika belum login
}

// 2. Load Database Connection
require_once __DIR__ . '/../../config/database.php';

// 3. Proses Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pastikan data yang diperlukan ada
    if (isset($_POST['user_id'], $_POST['role'], $_POST['status'])) {
        
        $id     = intval($_POST['user_id']);
        $role   = $_POST['role'];
        $status = $_POST['status'];
        if ($id === (int)$_SESSION['admin_id'] && ($role !== 'admin' || $status !== 'verified')) kandagaJson(false, 'Anda tidak dapat menonaktifkan akses sendiri.', [], 422);

        // Whitelist validation: Pastikan data sesuai dengan enum di database
        $allowed_roles   = ['admin', 'kontributor'];
        $allowed_status  = ['pending', 'verified', 'rejected'];

        if (!in_array($role, $allowed_roles) || !in_array($status, $allowed_status)) {
            exit('error'); // Hentikan jika data tidak valid (mencegah manipulasi input)
        }

        // Menggunakan Prepared Statement (Proteksi SQL Injection)
        $stmt = $conn->prepare("UPDATE users SET role = ?, status_verifikasi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $role, $status, $id);

        if ($stmt->execute()) {
            echo "success"; // Diterima oleh AJAX di daftar-pengguna.php
        } else {
            echo "error";
        }
        
        $stmt->close();
    } else {
        echo "error";
    }
} else {
    // Jika akses via URL langsung (bukan POST), tolak akses
    exit('error');
}
?>