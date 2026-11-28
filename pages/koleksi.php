<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/public-page-header.php';
/**
 * Halaman Koleksi Lokasi
 * Lokasi: /pages/koleksi.php
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

// Pastikan BASE_URL terdefinisi
if (!defined('BASE_URL')) define('BASE_URL', '');

$query = "
    SELECT l.slug, l.id_lokasi, l.nama_tempat, l.foto_lokasi, u.nama as nama_kurator, u.foto
    FROM tb_lokasi l
    INNER JOIN tb_kurasi_koleksi k ON l.id_lokasi = k.id_lokasi
    INNER JOIN users u ON k.id_user = u.id
    ORDER BY l.nama_tempat ASC
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Koleksi Naskah Terkurasi - KANDAGA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">

    <style>
        body { background-color: var(--kandaga-surface); color: var(--kandaga-text); font-family: var(--kandaga-font-ui); }

        .card-koleksi-premium { border: none; border-radius: 16px; overflow: hidden; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; display: flex; flex-direction: column; }
        .card-koleksi-premium:hover { transform: translateY(-6px); box-shadow: 0 12px 20px -3px rgba(0,0,0,0.08); }

        .img-koleksi-container { position: relative; width: 100%; padding-top: 56.25%; overflow: hidden; background-color: #e2e8f0; }
        .img-koleksi-cover { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .card-koleksi-premium:hover .img-koleksi-cover { transform: scale(1.05); }

        .badge-kurasi { position: absolute; top: 15px; left: 15px; background: #2563eb; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .card-body-koleksi { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; }
        .koleksi-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; line-height: 1.4; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 3rem; }

        .kurator-wrapper { background: #f8fafc; border-radius: 12px; padding: 10px 12px; margin-top: auto; border: 1px solid #e2e8f0; }
        .kurator-avatar { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .kurator-text-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; line-height: 1.2; }
        .kurator-name { font-size: 0.85rem; font-weight: 700; color: #334155; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .btn-koleksi-action { font-size: 0.8rem; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: flex-end; margin-top: 12px; transition: gap 0.2s ease; gap: 5px; }
        .card-koleksi-premium:hover .btn-koleksi-action { gap: 10px; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module kandaga-collection-page">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php kandagaPublicHeader('Koleksi Manuskrip Terkurasi', 'fa-scroll', 'Penelusuran dokumen dan naskah kuno nusantara yang dikelola secara terdedikasi oleh tim kurator ahli.', '', 'Indeks Naskah'); ?>

<div class="container pb-5">
    <div class="row row-cols-2 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                    // Logika pengecekan file fisik foto lokasi di server
                    $path_img = __DIR__ . '/../assets/images/' . $row['foto_lokasi'];
                    $src_img = (!empty($row['foto_lokasi']) && file_exists($path_img))
                               ? BASE_URL . '/assets/images/' . $row['foto_lokasi']
                               : BASE_URL . '/assets/images/default.jpg';

                    // Logika pengecekan file fisik foto user (kurator) di server
                    $path_user = __DIR__ . '/../assets/images/user/' . $row['foto'];
                    $src_user = (!empty($row['foto']) && file_exists($path_user))
                                ? BASE_URL . '/assets/images/user/' . $row['foto']
                                : BASE_URL . '/assets/images/user/default-user.jpg';
                ?>

                <div class="col">
                    <a href="<?= BASE_URL ?>/koleksi/<?= htmlspecialchars($row['slug']) ?>" class="text-decoration-none">
                        <div class="card-koleksi-premium shadow-sm">
                            <div class="img-koleksi-container">
                                <span class="badge-kurasi"><i class="fas fa-certificate me-1"></i> Terkurasi</span>
                                <img src="<?= $src_img ?>" class="img-koleksi-cover" alt="<?= htmlspecialchars($row['nama_tempat']) ?>">
                            </div>

                            <div class="card-body-koleksi">
                                <h5 class="koleksi-title" title="<?= htmlspecialchars($row['nama_tempat']) ?>">
                                    <?= htmlspecialchars($row['nama_tempat']) ?>
                                </h5>

                                <div class="kurator-wrapper d-flex align-items-center">
                                    <img src="<?= $src_user ?>" class="kurator-avatar me-2" alt="<?= htmlspecialchars($row['nama_kurator']) ?>">
                                    <div style="overflow: hidden; width: 100%;">
                                        <span class="kurator-text-label">Kurator</span>
                                        <span class="kurator-name"><?= htmlspecialchars($row['nama_kurator']) ?></span>
                                    </div>
                                </div>

                                <div class="btn-koleksi-action">
                                    Jelajahi <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">Belum ada koleksi terkurator yang terdaftar dalam sistem saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>

</body>
</html>
