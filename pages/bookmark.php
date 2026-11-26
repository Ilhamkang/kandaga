<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookmark Naskah - KANDAGA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-search.css?v=3.1.2">
    <?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container my-5">
    <h1 class="h3 fw-bold">Bookmark naskah</h1>
    <p class="text-muted">Naskah yang Anda tandai, tanpa perlu login. Bookmark tersimpan pada browser ini; tidak tersinkron ke perangkat atau domain lain dan dapat hilang jika data browser dihapus.</p>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3"><p id="bookmarkSummary" class="mb-2" role="status"></p><a href="<?= BASE_URL ?>/search.php" class="btn btn-outline-primary mb-2">Cari naskah</a></div>
    <div id="bookmarkResults" data-bookmark-page aria-live="polite"></div>
    <nav id="bookmarkPagination" class="d-flex justify-content-center align-items-center mt-3" aria-label="Halaman bookmark"></nav>
    <noscript><p class="alert alert-info">Aktifkan JavaScript untuk menggunakan bookmark yang disimpan di browser.</p></noscript>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body></html>
