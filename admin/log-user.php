<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
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

<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?><div class="container-fluid"><?php endif; ?>
    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $pesan_sukses ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 card-custom">
        <div class="header-custom">
            <i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Aktivitas Pengguna
        </div>
        <div class="table-responsive">
            <table class="table table-hover m-0">
                <thead class="bg-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Detail Aktivitas</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logs = mysqli_query($conn, "SELECT * FROM user_logs ORDER BY id_log DESC LIMIT 50");
                    if ($logs && mysqli_num_rows($logs) > 0):
                        while ($l = mysqli_fetch_array($logs)):
                            $badge = (stripos($l['aksi'], 'Menyunting') !== false) ? 'bg-warning text-dark' : 'bg-secondary';
                    ?>
                    <tr>
                        <td><?= date('H:i, d M Y', strtotime($l['waktu'])); ?></td>
                        <td><?= htmlspecialchars($l['username']); ?></td>
                        <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($l['aksi']) ?></span></td>
                        <td><code><?= $l['ip_address'] ?></code></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center">Belum ada riwayat aktivitas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?></div><?php endif; ?>