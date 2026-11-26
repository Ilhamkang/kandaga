<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan BASE_URL terdefinisi
if (!defined('BASE_URL')) define('BASE_URL', '');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header("Location: " . BASE_URL . "/pages/kontributor.php");
    exit;
}

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("<div class='container mt-5 text-center'><h3>User tidak ditemukan.</h3><a href='" . BASE_URL . "/pages/kontributor.php' class='btn btn-primary'>Kembali</a></div>");
}

$username = $user['username'];
require_once __DIR__ . '/../includes/contribution-data.php';
$catalogLogCondition = kandagaCatalogLogCondition('user_logs');

// --- PERUBAHAN LOGIKA STATISTIK ---
// Menggunakan user_logs agar sinkron dengan dashboard
// Statistik Input
$stmt_input = $conn->prepare("SELECT COUNT(*) FROM user_logs WHERE username = ? AND kategori_aksi = 'input' AND $catalogLogCondition");
$stmt_input->bind_param("s", $username);
$stmt_input->execute();
$total_input = (int)$stmt_input->get_result()->fetch_row()[0];

// Statistik Edit
$stmt_edit = $conn->prepare("SELECT COUNT(*) FROM user_logs WHERE username = ? AND kategori_aksi = 'edit' AND $catalogLogCondition");
$stmt_edit->bind_param("s", $username);
$stmt_edit->execute();
$total_edit = (int)$stmt_edit->get_result()->fetch_row()[0];

// Daftar Naskah (Tetap mengambil dari katalog untuk menampilkan list naskah yang dikelola)
$sql_naskah = "SELECT id, judul, kode_inventaris FROM katalog
               WHERE penginput_awal = ? OR penyunting_terakhir = ?
               ORDER BY id DESC";
$stmt_naskah = $conn->prepare($sql_naskah);
$stmt_naskah->bind_param("ss", $username, $username);
$stmt_naskah->execute();
$q_naskah = $stmt_naskah->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil <?= htmlspecialchars($user['nama']); ?> | KANDAGA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-detail-kontributor.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #334155; }
        .profile-card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .avatar-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #f1f5f9; }
        .stat-card { background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .modal-img-banner { width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; }
        .list-group-item { padding: 1rem; border: none; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
        .list-group-item:hover { background: #f8fafc; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module kandaga-special-page kandaga-contributor-profile-page">

<?php require_once __DIR__ . '/../includes/analytics-context.php'; kandagaSetPageContext('profile','/kontributor/'.(int)$user_id,$user['nama'],(int)$user_id); ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-5">
    <div class="profile-card mb-4">
        <div class="row align-items-center">
            <div class="col-md-auto text-center">
                <?php $path_foto = BASE_URL . "/assets/images/user/" . (!empty($user['foto']) ? $user['foto'] : 'default-user.jpg'); ?>
                <img src="<?= htmlspecialchars($path_foto); ?>" class="avatar-img mb-3 mb-md-0" alt="Avatar">
            </div>
            <div class="col-md">
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($user['nama']); ?></h3>
                <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($user['role']); ?></span>
                <p class="text-muted mt-2 mb-0"><i class="fas fa-university me-2"></i><?= htmlspecialchars($user['afiliasi'] ?? 'Independen'); ?></p>
                <button type="button" class="btn btn-sm btn-outline-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalProfil">
                    <i class="fas fa-id-card me-1"></i> Lihat Profil Lengkap
                </button>
            </div>
            <div class="col-md-auto mt-3 mt-md-0">
                <div class="d-flex gap-3">
                    <div class="stat-card text-center">
                        <h4 class="mb-0 text-primary fw-bold"><?= $total_input; ?></h4>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">Input Naskah</small>
                    </div>
                    <div class="stat-card text-center">
                        <h4 class="mb-0 text-success fw-bold"><?= $total_edit; ?></h4>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem;">Suntingan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProfil" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden;">
                <div class="modal-header px-4 py-3" style="background: #1e293b; color: white; border: none;">
                    <h5 class="modal-title fw-bold">Profil Lengkap</h5>
                    <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img src="<?= htmlspecialchars($path_foto); ?>" class="modal-img-banner shadow-sm" alt="Foto">
                        </div>
                        <div class="col-md-8">
                            <div class="fw-bold text-dark" style="font-size: 1.1rem;"><?= htmlspecialchars($user['nama']); ?></div>
                            <div class="text-dark"><i class="fas fa-university text-secondary me-2"></i><?= htmlspecialchars($user['afiliasi'] ?? 'Independen'); ?></div>
                            <div class="mt-2">
                                <span class="badge rounded-pill bg-primary px-3 py-1 shadow-sm text-uppercase"><?= htmlspecialchars($user['role']); ?></span>
                            </div>
                            <div class="mt-2 pt-3 border-top">
                                <p class="text-muted small" style="line-height: 1.6; text-align: justify;">
                                    <?= nl2br(htmlspecialchars($user['deskripsi'] ?? 'Tidak ada deskripsi tersedia.')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md">
            <div class="profile-card mb-4">
                <h5 class="fw-bold mb-4"><i class="fas fa-list-ul me-2 text-primary"></i>Riwayat Kontribusi</h5>
                <div class="list-group list-group-flush">
                    <?php if($q_naskah->num_rows > 0): ?>
                        <?php while ($row = $q_naskah->fetch_assoc()): ?>
                            <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$row['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="text-truncate">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($row['judul']); ?></span>
                                    <span class="text-muted ms-2" style="font-family: 'Courier New', monospace; font-size: 0.85rem;">
                                        | <?= htmlspecialchars($row['kode_inventaris'] ?? '-'); ?>
                                    </span>
                                </div>
                                <i class="fas fa-chevron-right text-muted small"></i>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">Belum ada kontribusi tercatat.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
