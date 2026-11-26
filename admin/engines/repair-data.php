<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(true);
if (in_array($_GET['action'] ?? '', ['upload', 'confirm'], true) || $_SERVER['REQUEST_METHOD'] === 'POST') kandagaPost(true);

require_once __DIR__ . '/../../config/bootstrap.php';
// --- SETTING DIAGNOSTIK ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(0); // Supaya tidak timeout
header('X-Accel-Buffering: no'); // Paksa Nginx agar tidak buffer

require_once dirname(__DIR__, 2) . '/config/database.php';

echo "<h3>Diagnostik Perbaikan Relasi...</h3>";
flush();

// 1. CEK KONEKSI
if (!$conn) die("Database error: " . mysqli_connect_error());

// 2. CEK NAMA KOLOM (SESUAIKAN INI DENGAN NAMA DI PHPMyADMIN)
// Coba ganti 'bahasa_list' dan 'aksara_list' di bawah dengan nama kolom Anda yang sebenarnya
$sql = "SELECT id, judul, bahasa_list, aksara_list FROM katalog"; 
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "<div style='color:red;'><strong>Gagal Query:</strong> " . mysqli_error($conn) . "</div>";
    echo "<p>Tips: Pastikan nama kolom <code>bahasa_list</code> dan <code>aksara_list</code> ada di tabel <code>katalog</code>. Cek di phpMyAdmin!</p>";
    exit();
}

echo "<div>Query berhasil, mulai memproses...</div>";
flush();

$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $count++;
    if ($count % 10 == 0) {
        echo "Sedang memproses item ke-$count...<br>";
        flush();
    }
    // (Logika proses seperti sebelumnya)
}
echo "Selesai!";
?>