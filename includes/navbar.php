<?php
// Navbar dipakai juga oleh beranda sebelum module-head dimuat.
// Pastikan helper versi tersedia agar cache-busting tidak memicu fatal error.
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/website-visits.php';
if (isset($conn)) kandagaTrackWebsite($conn);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-navbar.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public-mobile.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">

<div class="kandaga-nav" data-kandaga-base="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/">
                <img src="<?= BASE_URL ?>/assets/images/favicon.png" height="32" alt="Logo" class="rounded shadow-sm">
                <div class="brand-text">
                    <span class="brand-main">KANDAGA</span>

                </div>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                <ul class="navbar-nav ms-auto align-items-center">

                    <li class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>/"><i class="fa-solid fa-house"></i> Beranda</a>
                    </li>

                    <li class="nav-item <?= ($current_page == 'daftar-post.php') ? 'active' : '' ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>/kabar"><i class="fa-solid fa-newspaper"></i> Kabar</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fa-solid fa-list-ul"></i> Indeks</a>
                        <div class="dropdown-menu border-0">
                            <a class="dropdown-item" href="<?= BASE_URL ?>/indeks-judul"><i class="fa-solid fa-font me-2"></i> Judul Naskah</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/indeks-lokasi"><i class="fa-solid fa-map-location-dot me-2"></i> Lokasi</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/koleksi"><i class="fa-solid fa-map-location-dot me-2"></i> Koleksi</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown <?= in_array($current_page, ['bibliografi.php','katalog.php'], true) ? 'active' : '' ?>">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fa-solid fa-book"></i> Bibliografi</a>
                        <div class="dropdown-menu border-0">
                            <a class="dropdown-item" href="<?= BASE_URL ?>/monograf"><i class="fa-solid fa-book-open me-2"></i> Monograf</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/katalog"><i class="fa-solid fa-folder-tree me-2"></i> Katalog</a>
                        </div>
                    </li>

                    <li class="nav-item <?= ($current_page == 'statistik.php') ? 'active' : '' ?>">
                        <a class="nav-link" href="<?= BASE_URL ?>/statistik"><i class="fa-solid fa-chart-pie"></i> Statistik</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fa-solid fa-circle-info"></i> Tentang</a>
                        <div class="dropdown-menu border-0">
                            <a class="dropdown-item" href="<?= BASE_URL ?>/tentang"><i class="fa-solid fa-building me-2"></i> Proyek</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/kontributor"><i class="fa-solid fa-users me-2"></i> Kontributor</a>
                        </div>
                    </li>

                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/pages/bookmark.php"><i class="fa-regular fa-bookmark"></i> Bookmark <span class="badge bg-light text-dark rounded-pill" data-bookmark-count>0</span></a></li>
                    <li class="nav-item">
                        <a class="nav-link search-pill" href="<?= BASE_URL ?>/search"><i class="fa-solid fa-magnifying-glass"></i> <span>Cari</span></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>
<!-- Status bookmark tidak ditampilkan sebagai pesan sukses di halaman. -->
<script src="<?= BASE_URL ?>/assets/js/bookmarks.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>" defer></script>
