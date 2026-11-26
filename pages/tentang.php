<?php
require_once __DIR__ . '/../config/bootstrap.php'; 
// 1. Load Master Loader (config.php)
require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../includes/version.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tentang KANDAGA - Katalog Naskah Sunda</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-tentang.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module kandaga-special-page kandaga-about-page">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container text-center">
        <h2 class="font-weight-bold">Mengenal KANDAGA</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">Upaya kolaboratif dalam mendigitalisasi dan memetakan kekayaan intelektual naskah Sunda.</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="content-card kandaga-prose">
                <h4>Tentang KANDAGA</h4>
                <p>Katalog Naskah Sunda Kairaga, disingkat <strong>KANDAGA</strong>, adalah sebuah katalog terpadu (<i>union catalog</i>) untuk naskah-naskah Sunda yang dikembangkan secara mandiri oleh <strong>Kairaga Project</strong>. Platform ini dirancang untuk membantu para peneliti, pelajar, dan masyarakat umum memahami serta melestarikan warisan budaya Sunda secara lebih mudah dan interaktif. <b>Kandaga</b> dalam bahasa Sunda berarti 'wadah' yang secara filosofis menjadi semangat dalam pengembangan katalog ini, yaitu sebagai wadah informasi koleksi naskah-naskah Sunda.</p>

                <p>Inisiasi perancangan dimulai tahun 2023 atas dukungan program <b>Fellowship Lingling Wiyadharma</b> dari Scaliger Institut di Universitas Leiden, Belanda dan Masyarakat Pernaskahan Nusantara (Manassa). Tahap awal pengumpulan informasi melibatkan koleksi naskah Sunda dari Perpustakaan Universitas Leiden, yang kemudian dikembangkan dengan data dari berbagai katalog institusi lainnya secara bertahap.</p>

                <p>Dalam tahap awal pengembangan platform, kode disusun secara menyeluruh (<i>fullstack</i>) oleh Ilham Nurwansah. Entri katalog dan informasi metadata didukung oleh para <a href="<?= BASE_URL ?>/kontributor">kontributor</a> yang berdedikasi menyediakan informasi pernaskahan untuk kepentingan publik. </p>
                <hr/>
                Dukungan Inisiasi:
                <figure>
                    <img src="<?= BASE_URL ?>/assets/images/supporter_01.png" class="supporter-img shadow-sm" alt="Supporter 1"> 
                    <img src="<?= BASE_URL ?>/assets/images/supporter_02.png" class="supporter-img shadow-sm" alt="Supporter 2">
                    <img src="<?= BASE_URL ?>/assets/images/supporter_03.png" class="supporter-img shadow-sm" alt="Supporter 3">
                    <img src="<?= BASE_URL ?>/assets/images/supporter_07.png" class="supporter-img shadow-sm" alt="Supporter 7">
                </figure>
                
                <hr/>
                Dukungan Akses Koleksi:
                <figure>
                    <img src="<?= BASE_URL ?>/assets/images/supporter_04.png" class="supporter-img shadow-sm" alt="Supporter 4">
                </figure>

                <hr>

                <h4><i class="fa-solid fa-map-location-dot"></i> Roadmap Pengembangan</h4>
                <div class="roadmap-list mt-4">
                    <div class="roadmap-item">
                        <span class="roadmap-year">2023</span>
                        <span class="text-muted">Penjajakan informasi dan perancangan sistem arsitektur katalog naskah.</span>
                    </div>
                    <div class="roadmap-item">
                        <span class="roadmap-year">2024</span>
                        <span class="text-muted">Pengembangan database, pembuatan aplikasi web (Back-end & Front-end) dan input data dasar koleksi Leiden.</span>
                    </div>
                    <div class="roadmap-item">
                        <span class="roadmap-year">2025</span>
                        <span class="text-muted">Penambahan entri data institusi lain dan peluncuran fitur Indeks Abjad.</span>
                    </div>
                    <div class="roadmap-item">
                        <span class="roadmap-year">2026</span>
                        <span class="text-muted">Fitur statistik mendalam, integrasi blog, dan optimalisasi mesin pencarian naskah.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="content-card sticky-top" style="top: 20px;">
                <h4>Kontak & Pengembang</h4>
                <img src="<?= BASE_URL ?>/assets/images/ilham-leiden.png" class="profile-img shadow-sm" alt="Ilham Nurwansah">
                <div class="contact-info mb-4">
                    <p class="mb-2"><strong><i class="fa-solid fa-id-badge mr-2 text-primary"></i> Ilham Nurwansah</strong></p>
                    <p class="mb-0 text-muted small"><i class="fa-solid fa-envelope mr-2"></i> info@kairaga.com</p>
                </div>

                <a href="<?= BASE_URL ?>/kontak.php" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-envelope"></i> Hubungi Kami
                </a>
                <br>
                <hr>

                <span class="version-badge">Versi <?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?></span>
                <p class="small text-muted mb-4">Iterasi berkelanjutan sejak Mei 2023 untuk aksesibilitas data budaya yang lebih baik.</p>

                <hr>
                <h4><i class="fa-solid fa-file-code mr-2 text-primary"></i>Lisensi</h4>
                <p class="small text-muted mb-4">
                    Kode aplikasi KANDAGA dirilis di bawah lisensi
                    <a href="https://github.com/Ilhamkang/kandaga/blob/main/LICENSE" target="_blank" rel="noopener noreferrer">AGPL-3.0</a>.
                    Data naskah, metadata koleksi, dan pustaka pihak ketiga tidak termasuk dalam lisensi kode ini.
                </p>
                
                <div style="background: #fffbeb; border: 1px dashed #f59e0b; border-radius: 15px; padding: 20px;">
                    <p class="small mb-3" style="color: #92400e; line-height: 1.5;">
                        <i class="fa-solid fa-mug-hot mr-1"></i> 
                        Proyek ini dirawat dengan semangat pelestarian dan (tentu saja) ribuan cangkir kafein. 
                        <br><br>
                        Jika <strong>KANDAGA</strong> bermanfaat bagi Anda, dukung kami agar tetap "melek" untuk terus mengembangkan fitur-fitur selanjutnya.
                    </p>
                    
                    <div class="text-center mt-3">
                        <script type='text/javascript' src='https://edge-cdn.trakteer.id/js/embed/trbtn.min.js?v=14-05-2025'></script>
                        <script type='text/javascript'>
                            (function() {
                                var trbtnId = trbtn.init('Traktir Kopi', '#d97706', 'https://trakteer.id/kairaga/tip', 'https://trakteer.id/images/mix/coffee.png', '35');
                                trbtn.draw(trbtnId);
                            })();
                        </script>
                        <p class="text-muted mt-2" style="font-size: 0.7rem;"><i>Klik tombol di atas untuk berdonasi</i></p>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div> 

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.5.1.slim.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
