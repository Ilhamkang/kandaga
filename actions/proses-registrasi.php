<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * proses-registrasi.php
 * Updated with Captcha Validation
 */

// 1. Pastikan Session aktif untuk mengambil data captcha
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// 2. Panggil config dari root
require_once __DIR__ . '/../config.php'; 

header('Content-Type: application/json; charset=utf-8');

// Proteksi Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'message' => 'Invalid Request']));
}

// --- VALIDASI CAPTCHA ---
// Pastikan kode captcha diinput dan cocok dengan session
if (empty($_POST['captcha']) || $_POST['captcha'] != $_SESSION['captcha_code']) {
    exit(json_encode(['success' => false, 'message' => 'Kode keamanan salah!']));
}

// Hapus session captcha agar tidak bisa digunakan lagi (one-time use)
unset($_SESSION['captcha_code']);

// Sanitasi & Input
$username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
$email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$pass_conf= $_POST['password_confirm'];

// --- LOGIKA NAMA ---
$nama = (!empty($_POST['nama'])) ? trim($_POST['nama']) : $username;

// Server-side Validation
if (empty($username) || empty($email) || empty($password)) {
    exit(json_encode(['success' => false, 'message' => 'Semua field wajib diisi!']));
}

if ($password !== $pass_conf) {
    exit(json_encode(['success' => false, 'message' => 'Kata sandi tidak cocok!']));
}

if (strlen($password) < 6) {
    exit(json_encode(['success' => false, 'message' => 'Password minimal 6 karakter!']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit(json_encode(['success' => false, 'message' => 'Format email salah!']));
}

// Check Duplicate
$check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
if($check->get_result()->num_rows > 0) {
    exit(json_encode(['success' => false, 'message' => 'Username atau Email sudah terdaftar!']));
}

// Insert
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password, role, status_akun, nama, status_verifikasi) 
        VALUES (?, ?, ?, 'kontributor', 'aktif', ?, 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $username, $email, $hashed_password, $nama);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registrasi berhasil!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>