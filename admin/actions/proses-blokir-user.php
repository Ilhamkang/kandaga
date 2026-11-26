<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(true);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php'; // 
require_once __DIR__ . '/../includes/log-tracker.php'; // Panggil fungsi log


if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $status = $_POST['status'];
    if (!in_array($status, ['aktif', 'blokir'], true) || (int)$id === (int)$_SESSION['admin_id']) kandagaJson(false, 'Perubahan status tidak valid.', [], 422); // 'aktif' atau 'blokir'

    // 1. Ambil username target terlebih dahulu untuk keperluan pencatatan log
    $sql_user = "SELECT username FROM users WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $id);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();
    
    if ($res_user->num_rows === 1) {
        $row_user = $res_user->fetch_assoc();
        $username_target = $row_user['username'];
        $stmt_user->close();

        // 2. Update status_akun di tabel users
        $sql_update = "UPDATE users SET status_akun = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $status, $id);
        
        if ($stmt_update->execute()) {
            $stmt_update->close();

            // 3. Catat perubahan ke user_logs sebagai aksi pemutus status 'login'
            try {
                $waktu = date("Y-m-d H:i:s");
                $ip = $_SERVER['REMOTE_ADDR'];
                
                // Jika status baru 'aktif', berarti aksinya adalah 'blokir dibuka'
                $aksi_log = ($status === 'blokir') ? 'akun diblokir oleh admin' : 'blokir dibuka oleh admin';
                
                $sql_log = "INSERT INTO user_logs (username, waktu, aksi, ip_address) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($sql_log);
                if ($stmt_log) {
                    $stmt_log->bind_param("ssss", $username_target, $waktu, $aksi_log, $ip);
                    $stmt_log->execute();
                    $stmt_log->close();
                }
            } catch (Exception $e) {
                // Abaikan jika log gagal agar proses utama tidak terhenti
            }

            // 4. Kembalikan ke halaman daftar pengguna
            header("Location: " . BASE_URL . "/admin/index.php?page=status-pengguna");
            exit();
        }
    }
    
    echo "Gagal memproses perubahan status akun.";
}
?>