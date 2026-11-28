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

// Penyuntingan naskah hanya melalui proses-edit.php, yang memberlakukan
// aturan kepemilikan kurator. Blok UPDATE lama dihapus agar tidak ada jalur
// alternatif yang bisa mengabaikan aturan tersebut.
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?><div class="container-fluid admin-module-surface"><?php endif; ?>
    <?php kandagaPageHeader(
        'Aktivitas Terbaru',
        'fa-clock-rotate-left',
        'Pantau perubahan katalog dan aktivitas pengguna terbaru.',
        '<div class="kandaga-log-tabs" id="logTab" role="tablist" aria-label="Jenis aktivitas"><button type="button" class="kandaga-log-tab active" id="global-tab" data-bs-toggle="pill" data-bs-target="#global-log" role="tab" aria-controls="global-log" aria-selected="true"><i class="fa-solid fa-earth-americas" aria-hidden="true"></i>Log Global</button><button type="button" class="kandaga-log-tab" id="my-tab" data-bs-toggle="pill" data-bs-target="#my-log" role="tab" aria-controls="my-log" aria-selected="false"><i class="fa-solid fa-user" aria-hidden="true"></i>Aktivitas Saya</button></div>'
    ); ?>
    <div class="card shadow-sm border-0 card-custom">
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
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($l['aksi']) ?></span></td>
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
                            $user = (string)($_SESSION['username'] ?? '');
                            $stmtMy = $conn->prepare('SELECT * FROM user_logs WHERE username = ? ORDER BY id_log DESC LIMIT 50');
                            $stmtMy->bind_param('s', $user);
                            $stmtMy->execute();
                            $logs_my = $stmtMy->get_result();
                            while ($l = $logs_my->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('H:i, d M Y', strtotime($l['waktu'])); ?></td>
                                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($l['aksi']) ?></span></td>
                                    <td><code><?= $l['ip_address'] ?></code></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php $stmtMy->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?></div><?php endif; ?>

<script>
    $(document).ready(function(){
        // Inisialisasi tab Bootstrap
        $('#logTab .kandaga-log-tab').on('click', function (e) {
            e.preventDefault();
            bootstrap.Tab.getOrCreateInstance(this).show();
        });
    });
</script>