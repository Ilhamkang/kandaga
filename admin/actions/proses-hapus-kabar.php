<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(true);

require_once __DIR__ . '/../../config/bootstrap.php';
// 1. Keamanan & Pathing
require_once dirname(__DIR__) . '/includes/auth.php'; 
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once __DIR__ . '/../includes/log-tracker.php'; 

header('Content-Type: application/json');

// 2. Cek Method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// 3. Ambil dan validasi ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

// 4. Ambil data thumbnail DAN judul dari database
// Kita tambah 'judul' agar log bisa mencatat judul apa yang dihapus
$stmt_cek = $conn->prepare("SELECT thumbnail, judul FROM tb_kabar WHERE id_kabar = ?");
$stmt_cek->bind_param("i", $id);
$stmt_cek->execute();
$data = $stmt_cek->get_result()->fetch_assoc();
$stmt_cek->close();

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
    exit;
}

// 5. Proses Hapus File Gambar (Jika ada)
if (!empty($data['thumbnail'])) {
    $file_path = dirname(dirname(__DIR__)) . '/assets/images/post/' . $data['thumbnail'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// 6. Hapus dari Database
$stmt_del = $conn->prepare("DELETE FROM tb_kabar WHERE id_kabar = ?");
$stmt_del->bind_param("i", $id);

if ($stmt_del->execute()) {
    // CATAT AKTIVITAS
    // Menggunakan kategori 'hapus' dan menyertakan judul kabar
    logAktivitas($conn, 'hapus', "Menghapus kabar: " . $data['judul'], $id);
    
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $conn->error]);
}

$stmt_del->close();
$conn->close();
?>