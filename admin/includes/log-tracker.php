<?php
function logAktivitas($conn, $kategori, $deskripsi, $id_target = NULL) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $username = $_SESSION['username'] ?? 'System';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Sesuaikan dengan kolom baru yang kita buat di database
    $stmt = $conn->prepare("INSERT INTO user_logs (username, kategori_aksi, id_target, aksi, ip_address, waktu) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssiss", $username, $kategori, $id_target, $deskripsi, $ip_address);
    $stmt->execute();
    $stmt->close();
}
?>