<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(true);
// Pastikan koneksi tersedia
if (!isset($conn)) {
    die("Akses tidak valid: Koneksi database tidak ditemukan.");
}

$query = "SELECT u.id, u.username, u.nama, u.status_akun, u.role,
         (SELECT aksi FROM user_logs WHERE username = u.username ORDER BY id_log DESC LIMIT 1) as last_aksi,
         (SELECT waktu FROM user_logs WHERE username = u.username ORDER BY id_log DESC LIMIT 1) as last_waktu
         FROM users u ORDER BY u.id DESC";
$result = mysqli_query($conn, $query);

// Mengambil semua data ke array agar bisa di-loop untuk statistik, lalu di-loop lagi untuk tabel
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Inisialisasi statistik
$stats = ['online' => 0, 'aktif' => 0, 'blokir' => 0, 'pending' => 0];
$waktu_sekarang = time();

foreach ($users as $row) {
    // 1. Hitung Status Akun
    if (isset($stats[$row['status_akun']])) {
        $stats[$row['status_akun']]++;
    } else {
        $stats['pending']++; // Anggap selain aktif/blokir sebagai pending
    }

    // 2. Hitung Online
    if ($row['status_akun'] === 'aktif') {
        $is_online = false;
        if ($row['last_aksi'] !== null) {
            $last_waktu = strtotime($row['last_waktu']);
            if (strpos(strtolower($row['last_aksi']), 'logout') === false && ($waktu_sekarang - $last_waktu) < 300) {
                $is_online = true;
            }
        }
        if ($is_online) $stats['online']++;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft text-success"><i class="fa-solid fa-circle-nodes"></i></div>
            <div><small class="text-muted">Online</small><h5 class="mb-0"><?= $stats['online']; ?></h5></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft text-primary"><i class="fa-solid fa-user-check"></i></div>
            <div><small class="text-muted">Aktif</small><h5 class="mb-0"><?= $stats['aktif']; ?></h5></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft text-warning"><i class="fa-solid fa-user-clock"></i></div>
            <div><small class="text-muted">Pending</small><h5 class="mb-0"><?= $stats['pending']; ?></h5></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft text-danger"><i class="fa-solid fa-user-slash"></i></div>
            <div><small class="text-muted">Diblokir</small><h5 class="mb-0"><?= $stats['blokir']; ?></h5></div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 user-card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0"><i class="fa-solid fa-user-gear me-2"></i>Monitor Pengguna</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr><th class="ps-4">User</th><th>Peran</th><th class="text-center">Status Aktivitas</th><th class="text-center">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $row) :
                        $is_me = (isset($_SESSION['admin_id']) && $row['id'] == $_SESSION['admin_id']);
                        $is_online = ($row['status_akun'] === 'aktif' && $row['last_aksi'] !== null && strpos(strtolower($row['last_aksi']), 'logout') === false && ($waktu_sekarang - strtotime($row['last_waktu'])) < 300);
                        if ($is_me) $is_online = true;
                    ?>
                    <tr>
                        <td class="ps-4 align-middle">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['username']); ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['nama'] ?? ''); ?></small>
                            <?php if ($row['status_akun'] == 'blokir'): ?> <span class="badge bg-danger-subtle text-danger-emphasis ms-2">Diblokir</span> <?php endif; ?>
                        </td>
                        <td class="align-middle"><span class="badge bg-light text-dark border"><?= ucfirst($row['role']); ?></span></td>
                        <td class="text-center align-middle">
                            <span class="text-muted small">
                                <span class="status-dot <?= $is_online ? 'status-online' : 'status-offline'; ?>"></span>
                                <?= $is_online ? 'Online' : 'Offline'; ?>
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <?php if (!$is_me) : ?>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/detail-kontributor?nama=<?= urlencode($row['nama']); ?>" class="btn btn-sm btn-light text-info"><i class="fa-solid fa-eye"></i></a>
                                    <form method="POST" action="actions/proses-blokir-user.php" class="d-inline">
<input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
<input type="hidden" name="status" value="<?= $row['status_akun'] === 'aktif' ? 'blokir' : 'aktif' ?>">
<button type="submit" class="btn btn-sm btn-light"><?= $row['status_akun'] === 'aktif' ? 'Blokir' : 'Aktifkan' ?></button></form>
                                    <button class="btn btn-sm btn-light text-danger btn-hapus" data-id="<?= $row['id']; ?>" data-user="<?= $row['username']; ?>"><i class="fa-solid fa-trash-can"></i></button>
                                </div>
                            <?php else : ?>
                                <span class="badge bg-secondary">Admin Aktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
