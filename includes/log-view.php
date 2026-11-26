<?php
// /includes/log_view.php

function catatKunjungan($conn, $id_naskah) {
    // Validasi ID agar tidak memproses data sampah
    if (empty($id_naskah) || !is_numeric($id_naskah)) return;

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Gunakan Prepared Statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO naskah_views (id_naskah, ip_address, user_agent, visited_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $id_naskah, $ip_address, $user_agent);
    
    // Kita jalankan secara silent (tidak perlu error handling yang mengganggu user)
    @$stmt->execute();
    $stmt->close();
}
?>

