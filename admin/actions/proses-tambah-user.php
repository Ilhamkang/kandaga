<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(true);

require_once __DIR__ . '/../../config/bootstrap.php';
// Pastikan tidak ada output sebelum header JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Jalur Path yang disesuaikan untuk admin/actions/
require_once __DIR__ . '/../includes/auth.php'; 
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/log-tracker.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak valid']);
    exit;
}

try {
    // Ambil data
    $nama     = $_POST['nama_lengkap'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'kontributor';
    if (!in_array($role, ['admin', 'kontributor'], true)) throw new InvalidArgumentException('Role tidak valid.');

    if (empty($nama) || empty($username) || empty($password)) {
        throw new Exception("Semua kolom wajib diisi!");
    }

    // Cek username
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception("Username sudah digunakan.");
    }
    $check->close();

    // Simpan
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, password, nama, role, status_verifikasi) VALUES (?, ?, ?, ?, 'verified')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password_hashed, $nama, $role);

    if ($stmt->execute()) {
        // Panggil fungsi log (pastikan file log-tracker.php punya fungsi logAktivitas)
        if (function_exists('logAktivitas')) {
            logAktivitas($conn, "input", "Menambah pengguna baru: " . $username, $conn->insert_id);
        }
        echo json_encode(['status' => 'success', 'message' => 'Pengguna berhasil didaftarkan!']);
    } else {
        throw new Exception("Gagal menyimpan ke database: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>