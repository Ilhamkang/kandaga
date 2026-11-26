<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(false);
if ($_SERVER['REQUEST_METHOD'] === 'POST') kandagaCsrf();

require_once __DIR__ . '/../config/bootstrap.php';
// Catatan: session_start() sebaiknya dipanggil di index.php (pusat) agar tidak error header
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// Pastikan koneksi database tersedia (asumsi index.php sudah memuatnya)
// Jika belum, uncomment baris bawah:
// require_once '../../config/database.php';

/**
 * Fungsi Pencatatan Log
 */
require_once __DIR__ . '/includes/log-tracker.php';

// Proses Update (jika form dikirim)
$pesan_sukses = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update'])) {
    $id = $_POST['id'];
    $judul = $_POST['judul'];
    $deskripsi_isi = $_POST['deskripsi_isi'];

    $stmt = $conn->prepare("UPDATE katalog SET judul = ?, deskripsi_isi = ? WHERE id = ?");
    $stmt->bind_param("ssi", $judul, $deskripsi_isi, $id);

    if ($stmt->execute()) {
        logAktivitas($conn, "edit", "Menyunting katalog ID: $id (Judul: $judul)", $id);
        $pesan_sukses = "Data berhasil diperbarui!";
    }
}
?>

<style>
    .card-custom { border-radius: 15px; overflow: hidden; }
    .header-custom { background: #fdfbf7; color: #5d4037; padding: 18px 20px; font-weight: bold; border-bottom: 1px solid #eee; }
</style>

<div class="container-fluid">
    <div class="card shadow-sm border-0 card-custom">
        <div class="header-custom d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-clock-rotate-left mr-2"></i>Riwayat Aktivitas</span>
            
            <ul class="nav nav-pills" id="logTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="global-tab" data-toggle="pill" href="#global-log" role="tab">Log Global</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="my-tab" data-toggle="pill" href="#my-log" role="tab">Aktivitas Saya</a>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="logTabContent">
            <div class="tab-pane fade show active" id="global-log" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover m-0">
                        <thead class="bg-light"><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>IP</th></tr></thead>
                        <tbody>
                            <?php
                            $logs_global = mysqli_query($conn, "SELECT * FROM user_logs ORDER BY id_log DESC LIMIT 50");
                            while ($l = mysqli_fetch_array($logs_global)): ?>
                                <tr>
                                    <td><?= date('H:i, d M Y', strtotime($l['waktu'])); ?></td>
                                    <td><?= htmlspecialchars($l['username']); ?></td>
                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($l['aksi']) ?></span></td>
                                    <td><code><?= $l['ip_address'] ?></code></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="my-log" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover m-0">
                        <thead class="bg-light"><tr><th>Waktu</th><th>Aktivitas</th><th>IP</th></tr></thead>
                        <tbody>
                            <?php
                            $user = $_SESSION['username'];
                            $logs_my = mysqli_query($conn, "SELECT * FROM user_logs WHERE username = '$user' ORDER BY id_log DESC LIMIT 50");
                            while ($l = mysqli_fetch_array($logs_my)): ?>
                                <tr>
                                    <td><?= date('H:i, d M Y', strtotime($l['waktu'])); ?></td>
                                    <td><span class="badge badge-warning"><?= htmlspecialchars($l['aksi']) ?></span></td>
                                    <td><code><?= $l['ip_address'] ?></code></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        // Inisialisasi tab Bootstrap
        $('#logTab a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
</script>