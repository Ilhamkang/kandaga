<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * proses-kontak.php
 * Memproses pengiriman pesan kontak ke database
 */

// 1. Panggil config dari root (sudah menangani koneksi DB, session, dan BASE_URL)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/captcha.php';
require_once __DIR__ . '/../includes/inbox-correspondence.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Verifikasi CSRF dan CAPTCHA
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) die('Sesi formulir berubah. Silakan muat ulang halaman.');
    if (!kandagaCaptchaVerify($_POST['captcha'] ?? null)) {
        // Kode sekali pakai; pengirim harus memuat ulang halaman kontak.
        die("❌ Gagal: Jawaban verifikasi salah. Silakan <a href='" . BASE_URL . "/kontak'>coba lagi</a>.");
    }

    // 3. Validasi dan simpan ke database
    $values = kandagaContactValidate($_POST);
    $stmt = $conn->prepare("INSERT INTO tb_inbox (nama, email, subjek, topik, isi_pesan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $values['nama'], $values['email'], $values['subjek'], $values['topik'], $values['isi_pesan']);

    if ($stmt->execute()) {
        // Redirect kembali ke halaman kontak menggunakan BASE_URL
        header("Location: " . BASE_URL . "/kontak?status=success");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>
