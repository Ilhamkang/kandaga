<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(true);

require_once __DIR__ . '/../../config/bootstrap.php';
// 1. Sertakan file autentikasi dan database
// Asumsi: file ini ada di admin/actions/, jadi naik 2 tingkat untuk config
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php'; 
require_once __DIR__ . '/../includes/log-tracker.php'; // Panggil fungsi log


// 2. Cek apakah request datang dari form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil data dari form
    $id_lokasi = (int)$_POST['id_lokasi'];
    $id_user = (int)$_POST['id_user'];
    $posisi = $_POST['posisi_kurator'];
    $tanggal = $_POST['tanggal_mulai'];
    $keterangan = $_POST['keterangan'];

    // 3. Gunakan Prepared Statements untuk keamanan (Mencegah SQL Injection)
    $stmt = $conn->prepare("INSERT INTO tb_kurasi_koleksi (id_lokasi, id_user, posisi_kurator, tanggal_mulai, keterangan) VALUES (?, ?, ?, ?, ?)");
    
    // "iisss" artinya: integer, integer, string, string, string
    $stmt->bind_param("iisss", $id_lokasi, $id_user, $posisi, $tanggal, $keterangan);

    if ($stmt->execute()) {
        // Jika berhasil, lempar kembali ke halaman form dengan pesan sukses
        header("Location: ../index.php?page=form-kurasi-koleksi&status=sukses");
    } else {
        // Jika gagal, lempar kembali dengan pesan error
        header("Location: ../index.php?page=form-kurasi-koleksi&status=gagal&error=" . urlencode($stmt->error));
    }
    
    $stmt->close();
    exit;
} else {
    // Jika user akses file ini tanpa form, kembalikan ke form
    header("Location: ../index.php?page=form-kurasi-koleksi");
    exit;
}