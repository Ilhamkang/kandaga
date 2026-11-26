<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * proses-login.php
 * Memproses otentikasi user dengan keamanan standar & pencatatan log.
 */

// 1. Panggil config dari root (ini sudah termasuk database, session_start, dan BASE_URL)
require_once __DIR__ . '/../config.php'; 

// 2. Validasi Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/login.php?pesan=Akses Tidak Valid");
    exit();
}

// 3. Validasi CSRF
$submittedCsrf = $_POST['csrf_token'] ?? '';
$sessionCsrf = $_SESSION['csrf_token'] ?? '';
if (!is_string($submittedCsrf) || !is_string($sessionCsrf) || $sessionCsrf === '' || !hash_equals($sessionCsrf, $submittedCsrf)) {
    session_unset();
    session_destroy();
    die("Akses ilegal (CSRF Detected).");
}

// $conn sudah tersedia dari config.php. Prepared statements below keep the
// username as data; no manual SQL escaping is needed.
if (!is_string($_POST['username'] ?? null) || !is_string($_POST['password'] ?? null)) {
    header("Location: " . BASE_URL . "/login.php?pesan=gagal");
    exit();
}
$username = trim($_POST['username']);
$password = $_POST['password'];
if ($username === '' || mb_strlen($username, 'UTF-8') > 150) {
    header("Location: " . BASE_URL . "/login.php?pesan=gagal");
    exit();
}

// Throttle brute-force attempts per IP + username without adding a schema
// dependency. The short-lived counter is stored outside the document root.
$loginBucket = sys_get_temp_dir() . '/kandaga-login-' . hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($username)) . '.json';
$readLoginBucket = static function($path) { $raw = @file_get_contents($path); $data = json_decode((string)$raw, true); return is_array($data) ? $data : ['started'=>time(),'count'=>0]; };
$loginState = $readLoginBucket($loginBucket);
if ((time() - (int)($loginState['started'] ?? 0)) > 900) $loginState = ['started'=>time(),'count'=>0];
if ((int)($loginState['count'] ?? 0) >= 10) {
    http_response_code(429); header('Retry-After: 900');
    header('Location: ' . BASE_URL . '/login.php?pesan=gagal'); exit();
}

// 4. Query Data
$sql = "SELECT id, username, password, role, nama, status_akun, status_verifikasi FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Verifikasi Password
    if (password_verify($password, $row['password'])) {
        
        // --- CEK STATUS AKUN ---
        if ($row['status_akun'] === 'blokir') {
            header("Location: " . BASE_URL . "/login.php?pesan=Akun Anda telah diblokir.");
            exit();
        }

        // --- CEK STATUS VERIFIKASI ---
        if ($row['status_verifikasi'] !== 'verified') {
            header("Location: " . BASE_URL . "/login.php?pesan=Akun belum diverifikasi atau ditolak. Hubungi admin.");
            exit();
        }

        // --- PENCATATAN LOG LOGIN ---
        $ip = $_SERVER['REMOTE_ADDR'];
        $aksi = "Login ke sistem";
        
        $log_sql = "INSERT INTO user_logs (username, aksi, ip_address, waktu) VALUES (?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        
        if ($log_stmt) {
            $log_stmt->bind_param("sss", $row['username'], $aksi, $ip);
            $log_stmt->execute();
            $log_stmt->close();
        }

        // Regenerate ID Sesi untuk mencegah session fixation
        session_regenerate_id(true);
        // Rotate the CSRF secret together with the authenticated session.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Set Session
        $_SESSION['status']     = "login";
        $_SESSION['admin_id']   = $row['id'];
        $_SESSION['username']   = $row['username'];
        $_SESSION['nama']       = $row['nama'];
        $_SESSION['role']       = $row['role'];
        @unlink($loginBucket);

        // Redirect menggunakan BASE_URL
        header("Location: " . BASE_URL . "/admin/index.php");
        exit();
        
    } else {
        $loginState['count'] = (int)$loginState['count'] + 1; @file_put_contents($loginBucket, json_encode($loginState), LOCK_EX);
        header("Location: " . BASE_URL . "/login.php?pesan=gagal");
        exit();
    }
} else {
    $loginState['count'] = (int)$loginState['count'] + 1; @file_put_contents($loginBucket, json_encode($loginState), LOCK_EX);
    header("Location: " . BASE_URL . "/login.php?pesan=gagal");
    exit();
}

$stmt->close();
$conn->close();
?>
