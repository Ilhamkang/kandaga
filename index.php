<?php
// 1. Panggil config.php dengan benar (di baris paling atas)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/version.php';
if (isset($_GET['p']) && is_string($_GET['p']) && $_GET['p'] !== 'home') {
    $route = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['p']);
    $file = __DIR__ . '/pages/' . $route . '.php';
    if (is_file($file)) {
        $params = $_GET; unset($params['p']);
        header('Location: ' . BASE_URL . '/pages/' . $route . '.php' . ($params ? '?' . http_build_query($params) : ''), true, 302);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Naskah Sunda - Kairaga</title>
    <?php $seoType = 'website'; $seoTitle = 'KANDAGA — Katalog Naskah Sunda'; $seoDescription = 'Katalog digital manuskrip Sunda, metadata pernaskahan, koleksi, dan sumber digital.'; $seoUrl = BASE_URL . '/'; include __DIR__ . '/includes/seo-meta.php'; ?>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-index.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/module-fonts.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile-compat.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public-mobile.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-home.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-naskah-baru.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-naskah-pilihan.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-kontributor.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-kabar-responsive.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile-layout.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public-page-header.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public-theme.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= BASE_URL ?>/assets/js/public-page-header.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php include __DIR__ . '/includes/analytics-tag.php'; ?>
</head>
<body class="kandaga-public kandaga-home-page">

<?php
// Gunakan __DIR__ untuk memastikan include selalu menemukan file 'includes/navbar.php'
include __DIR__ . '/includes/navbar.php';
?>

<div class="body-wrapper">
    <div id="dynamic-content">
        <?php
        // Mengambil parameter 'p' dari URL
        $page = isset($_GET['p']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['p']) : 'home';
        $file = __DIR__ . '/pages/' . $page . '.php';

        // Jika file ada di folder /pages/, include. Jika tidak, ke home.
        if (file_exists($file)) {
            include($file);
        } else {
            include(__DIR__ . '/includes/home.php');
        }
        ?>
    </div>
</div>

<div id="kandaga-loader">
    <div style="width:40px; height:40px; border:4px solid #f3f3f3; border-top:4px solid #2563eb; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
    <span style="margin-top:10px; font-weight:600; color:#2563eb;">Memuat Konten...</span>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/fade-in.js"></script>

</body>
</html>
