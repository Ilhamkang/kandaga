<?php
require_once __DIR__ . "/catalog-summary.php";
// Pastikan config/database.php sudah terhubung
if (!isset($conn)) { include 'config/database.php'; }
$catalogSummary=kandagaCatalogSummary($conn);

// Ambil statistik live
$total_naskah = $conn->query("SELECT COUNT(*) as total FROM katalog")->fetch_assoc()['total'];
$total_lembaga = $conn->query("SELECT COUNT(*) as total FROM tb_lokasi")->fetch_assoc()['total'];
$total_negara = $conn->query("SELECT COUNT(DISTINCT negara) as total FROM tb_lokasi WHERE negara != '' AND negara IS NOT NULL")->fetch_assoc()['total'];
?>

<link rel="stylesheet" href="assets/css/style-home.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="assets/css/style-naskah-baru.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">


<div class="home-container">
    <div class="hero-section py-5">
        <div class="container text-center">
            <h2 class="display-4 fw-bold mb-3" style="color: #0f2042;">Katalog Naskah Sunda</h2>
            <p class="lead text-muted mb-5" style="max-width: 600px; margin: auto;">Himpunan informasi naskah Sunda dari berbagai koleksi lembaga dan individu di seluruh dunia.</p>

            <div class="row justify-content-center mb-5">
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stat-card p-3 shadow-sm bg-white rounded">
                        <i class="fa-solid fa-book-open fa-2x mb-2 text-primary"></i>
                        <h3 class="stat-number fw-bold"><?= number_format($total_naskah); ?></h3>
                        <span class="stat-label text-muted">Total Naskah</span>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stat-card p-3 shadow-sm bg-white rounded">
                        <i class="fa-solid fa-building-columns fa-2x mb-2 text-primary"></i>
                        <h3 class="stat-number fw-bold"><?= number_format($total_lembaga); ?></h3>
                        <span class="stat-label text-muted">Koleksi</span>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stat-card p-3 shadow-sm bg-white rounded">
                        <i class="fa-solid fa-earth-americas fa-2x mb-2 text-primary"></i>
                        <h3 class="stat-number fw-bold"><?= number_format($total_negara); ?></h3>
                        <span class="stat-label text-muted">Negara</span>
                    </div>
                </div>
<div class="col-lg-2 col-md-4 col-6 mb-3"><div class="stat-card p-3 shadow-sm bg-white rounded"><i class="fa-solid fa-language fa-2x mb-2 text-primary"></i><h3 class="stat-number fw-bold"><?= number_format($catalogSummary['bahasa']) ?></h3><span class="stat-label text-muted">Bahasa</span></div></div><div class="col-lg-2 col-md-4 col-6 mb-3"><div class="stat-card p-3 shadow-sm bg-white rounded"><i class="fa-solid fa-pen-fancy fa-2x mb-2 text-primary"></i><h3 class="stat-number fw-bold"><?= number_format($catalogSummary['aksara']) ?></h3><span class="stat-label text-muted">Aksara</span></div></div><div class="col-lg-2 col-md-4 col-6 mb-3"><div class="stat-card p-3 shadow-sm bg-white rounded"><i class="fa-solid fa-scroll fa-2x mb-2 text-primary"></i><h3 class="stat-number fw-bold"><?= number_format($catalogSummary['alas_tulis']) ?></h3><span class="stat-label text-muted">Alas tulis</span></div></div>
            </div>

            <a href="search.php" class="btn btn-primary btn-lg px-4">
                <i class="fa-solid fa-magnifying-glass me-2"></i> Jelajahi Koleksi
            </a>
        </div>
    </div>

    <div class="bg-light py-5 home-section home-section-featured">
        <div class="container">
            <?php include 'includes/naskah-pilihan.php'; ?>
        </div>
    </div>

    <div class="py-5 home-section home-section-new">
        <div class="container">
            <h4 class="fw-bold mb-4" style="color: #0f2042;">
                <i class="fa-regular fa-bookmark text-danger me-2"></i> Entri Naskah Terbaru
            </h4>
            <div class="row">
                <div class="col-md-12">
                    <?php include 'includes/naskah-baru.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-light py-5 home-section home-section-contributors">
        <div class="container">
            <?php include 'includes/kontributor.php'; ?>
        </div>
    </div>

    <div class="py-5 home-section home-section-news">
        <div class="container">
            <?php include 'includes/post.php'; ?>
        </div>
    </div>
</div>
