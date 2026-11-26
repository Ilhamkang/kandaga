<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../admin/includes/request.php';
kandagaPost(true);
/**
 * proses-update-slug.php
 * Script untuk memperbarui slug lokasi secara massal
 */

// 1. Panggil config dari root (sudah mencakup koneksi database)
// Pastikan jumlah '../' disesuaikan dengan posisi folder file ini. 
// Jika file ini di root, gunakan 'config.php'. Jika di dalam folder, gunakan '../config.php'.
require_once __DIR__ . '/../config.php'; 

// 2. Memulai proses update
// $conn sudah tersedia dari config.php
$query = mysqli_query($conn, "SELECT id_lokasi, nama_tempat FROM tb_lokasi");

$berhasil = 0;
while($row = mysqli_fetch_assoc($query)) {
    // Membuat slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row['nama_tempat'])));
    
    // Sanitasi ID agar aman
    $id = intval($row['id_lokasi']);
    
    // Update ke database
    // Menggunakan variabel $id yang sudah disanitasi
    $update = mysqli_query($conn, "UPDATE tb_lokasi SET slug = '$slug' WHERE id_lokasi = $id");
    
    if($update) {
        $berhasil++;
    }
}

echo "Selesai! Sebanyak " . $berhasil . " lokasi berhasil di-update slug-nya.";
?>