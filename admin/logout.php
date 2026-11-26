<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * logout.php - Goah KANDAGA
 * Lokasi berkas: admin/logout.php
 */

// 1. Mulai session
if (session_status() === PHP_SESSION_NONE) session_start();

// 2. Sertakan koneksi database
// Memastikan file database.php ditemukan relative dari folder admin/
if (file_exists('../config/database.php')) {
    require_once __DIR__ . '/../config.php'; 
}

// 3. Catat aktivitas logout ke database sebelum session dihapus
if (isset($_SESSION['username']) && isset($conn) && $conn instanceof mysqli) {
    $username = $_SESSION['username'];
    
    // Optimasi penanganan IP Address jika berada di balik proxy/lokal
    $ip_address = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    $aksi = 'Logout dari sistem';

    // Menggunakan NOW() dari MySQL untuk sinkronisasi waktu yang akurat
    $sql_log = "INSERT INTO user_logs (username, waktu, aksi, ip_address) VALUES (?, NOW(), ?, ?)";
    if ($stmt_log = $conn->prepare($sql_log)) {
        $stmt_log->bind_param("sss", $username, $aksi, $ip_address);
        $stmt_log->execute();
        $stmt_log->close();
    }
}

// 4. Kosongkan semua variabel session
$_SESSION = array();

// 5. Hapus cookie session (Standard Security)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

// 6. Hancurkan session secara total di server
session_destroy();

// 7. Tutup koneksi database jika masih terbuka
if (isset($conn) && $conn instanceof mysqli) { 
    $conn->close(); 
}

// 8. Alihkan pengguna kembali ke halaman login
// Pastikan jalur ../login.php sesuai dengan struktur folder Goah KANDAGA Anda
header("Location: ../login.php?pesan=logout_berhasil");
exit();
?>
