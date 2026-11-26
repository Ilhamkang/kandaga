<?php
require_once __DIR__ . '/../config/bootstrap.php';
// 1. Load Master Loader
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kontributor KANDAGA - Katalog Naskah Sunda</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/contributor-activity.css?v=3.1.5">

    <style>
        body { background-color: #f8fafc; color: #334155; font-family: 'Inter', sans-serif; }
        .page-header { background: white; padding: 50px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 40px; }
        .profile-link { text-decoration: none !important; color: inherit; display: block; height: 100%; }
        .card-kontributor-premium {
            background: white; border: none; border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%; display: flex; flex-direction: column; overflow: hidden;
        }
        .card-kontributor-premium:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .avatar-wrapper { position: relative; padding-top: 25px; background: #f8fafc; }
        .profile-img-medium {
            width: 85px; height: 85px; border-radius: 50%; object-fit: cover;
            border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>

<body class="kandaga-module kandaga-special-page kandaga-contributors-page">
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header text-center">
    <div class="container">
        <h2 class="fw-bold">Kontributor Data</h2>
        <p class="text-muted">Para penggiat naskah yang berdedikasi menjaga khasanah naskah Sunda.</p>
    </div>
</div>

<div class="container pb-5">

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php
        $q_kontributor = mysqli_query($conn, "SELECT id, nama, foto, afiliasi, role FROM users WHERE role IN ('kontributor', 'admin') ORDER BY id ASC");

        if($q_kontributor && mysqli_num_rows($q_kontributor) > 0):
            while ($user = mysqli_fetch_assoc($q_kontributor)):

                // LOGIKA FIX: Pastikan file yang dipanggil adalah .jpg
                // Tambahkan ?v=time() agar browser tidak membaca cache lama
                $foto_input = !empty($user['foto']) ? $user['foto'] : 'default-user.jpg';
                $path_foto = BASE_URL . "/assets/images/user/" . $foto_input . "?v=" . time();

                $badge_class = ($user['role'] === 'admin') ? 'bg-danger' : 'bg-primary';
        ?>

        <div class="col mb-4">
            <a href="<?= BASE_URL ?>/detail-kontributor.php?id=<?= (int)$user['id']; ?>" class="profile-link">
                <div class="card-kontributor-premium text-center">
                    <div class="avatar-wrapper">
                        <img src="<?= htmlspecialchars($path_foto); ?>"
                             class="profile-img-medium"
                             alt="<?= htmlspecialchars($user['nama']); ?>"
                             loading="lazy"
                             onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/images/user/default-user.jpg';">
                    </div>

                    <div class="card-body pt-2 px-3 pb-3">
                        <h6 class="fw-bold text-truncate"><?= htmlspecialchars($user['nama']); ?></h6>
                        <span class="badge <?= $badge_class; ?> rounded-pill px-3 py-1 mb-2" style="font-size: 0.7rem;">
                            <?= htmlspecialchars(strtoupper($user['role'])); ?>
                        </span>
                        <div class="small text-muted text-truncate">
                            <i class="fas fa-university me-1"></i> <?= !empty($user['afiliasi']) ? htmlspecialchars($user['afiliasi']) : 'Independen'; ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5"><p class="text-muted">Belum ada kontributor.</p></div>
        <?php endif; ?>
    </div>
    <?php $activityPublicOnly=true;include __DIR__.'/../includes/contribution-activity.php';unset($activityPublicOnly); ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
