<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * proses-kontak.php
 * Memproses pengiriman pesan kontak ke database
 */

// 1. Panggil config dari root (sudah menangani koneksi DB, session, dan BASE_URL)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/inbox-correspondence.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Verifikasi CSRF dan CAPTCHA
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) die('Sesi formulir berubah. Silakan muat ulang halaman.');
    $captcha_user = strtoupper($_POST['captcha'] ?? '');
    $captcha_true = $_SESSION['captcha_code'] ?? '';

    if (empty($captcha_user) || $captcha_user !== $captcha_true) {
        // Menggunakan BASE_URL untuk link kembali
        die("❌ Gagal: Jawaban verifikasi salah. Silakan <a href='" . BASE_URL . "/kontak.php'>coba lagi</a>.");
    }

    // 3. Validasi dan simpan ke database
    $values = kandagaContactValidate($_POST);
    $stmt = $conn->prepare("INSERT INTO tb_inbox (nama, email, subjek, isi_pesan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $values['nama'], $values['email'], $values['subjek'], $values['isi_pesan']);

    if ($stmt->execute()) {
        // Redirect kembali ke halaman kontak menggunakan BASE_URL
        header("Location: " . BASE_URL . "/kontak.php?status=success");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>
