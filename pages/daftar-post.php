<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Gunakan Master Loader
require_once __DIR__ . '/../config.php';

// Tangkap parameter pencarian
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$search_condition = "";
if (!empty($search)) {
    $search_condition = " AND (judul LIKE '%$search%' OR konten LIKE '%$search%')";
}

// Query untuk semua kategori
$query_berita = "SELECT * FROM tb_kabar WHERE status = 'published' AND kategori = 'berita' $search_condition ORDER BY id_kabar DESC";
$result_berita = mysqli_query($conn, $query_berita);

$query_kegiatan = "SELECT * FROM tb_kabar WHERE status = 'published' AND kategori = 'kegiatan' $search_condition ORDER BY id_kabar DESC";
$result_kegiatan = mysqli_query($conn, $query_kegiatan);

$query_artikel = "SELECT * FROM tb_kabar WHERE status = 'published' AND kategori = 'artikel' $search_condition ORDER BY id_kabar DESC";
$result_artikel = mysqli_query($conn, $query_artikel);

$query_pengumuman = "SELECT * FROM tb_kabar WHERE status = 'published' AND kategori = 'pengumuman' $search_condition ORDER BY id_kabar DESC";
$result_pengumuman = mysqli_query($conn, $query_pengumuman);

function get_excerpt($konten, $char_limit = 120) {
    $text = strip_tags($konten);
    return (strlen($text) > $char_limit) ? substr($text, 0, $char_limit) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kabar KANDAGA - Katalog Naskah Sunda</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-daftar-post.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .page-header { background: white; padding: 40px 0 30px 0; border-bottom: 1px solid #e2e8f0; }
        .search-container { max-width: 550px; margin: 20px auto 0 auto; }
        .search-input { border-radius: 30px 0 0 30px !important; background-color: #f1f5f9; border: 1px solid #cbd5e1; padding-left: 20px; }
        .search-button { border-radius: 0 30px 30px 0 !important; padding-right: 20px; padding-left: 15px; }
        .section-kabar { margin-top: 50px; }
        .section-badge-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; padding: 6px 14px; border-radius: 30px; display: inline-block; margin-bottom: 15px; }
        .bg-berita { background-color: #e0f2fe; color: #0369a1; }
        .bg-kegiatan { background-color: #fef3c7; color: #b45309; }
        .bg-artikel { background-color: #dcfce7; color: #15803d; }
        .bg-pengumuman { background-color: #fee2e2; color: #b91c1c; }
        .section-heading { font-weight: 800; color: #0f172a; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .card-post { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; height: 100%; transition: transform 0.2s ease; background: white; }
        .card-post:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .thumb-img { height: 190px; object-fit: cover; width: 100%; }
        .card-title-link { color: #1e293b; text-decoration: none !important; font-weight: 700; font-size: 1.1rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-title-link:hover { color: #0284c7; }
        .card-text-summary { font-size: 0.875rem; color: #64748b; line-height: 1.6; text-align: justify; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style-kabar-responsive.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="kandaga-module kandaga-post-list-page">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header text-center">
    <div class="container">
        <h2 class="fw-bold" style="color: #0f2042;">Kabar KANDAGA</h2>
        <p class="text-muted mb-3">Informasi terbaru, kegiatan, dan pembaruan seputar katalog naskah Sunda.</p>
        <div class="search-container">
            <form action="<?= BASE_URL ?>/kabar" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control search-input" placeholder="Cari tulisan..." value="<?= htmlspecialchars($search); ?>">
                    <button class="btn btn-primary search-button" type="submit">
                        <i class="fas fa-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container pb-5 article-list-shell">
    <?php
    $total_rows = mysqli_num_rows($result_berita) + mysqli_num_rows($result_kegiatan) + mysqli_num_rows($result_artikel) + mysqli_num_rows($result_pengumuman);
    if($total_rows > 0):
        $categories = [
            ['res' => $result_berita, 'title' => 'Berita Terbaru', 'icon' => 'fa-newspaper', 'class' => 'bg-berita'],
            ['res' => $result_kegiatan, 'title' => 'Kegiatan & Acara', 'icon' => 'fa-calendar-alt', 'class' => 'bg-kegiatan'],
            ['res' => $result_artikel, 'title' => 'Artikel', 'icon' => 'fa-book-open', 'class' => 'bg-artikel'],
            ['res' => $result_pengumuman, 'title' => 'Pengumuman', 'icon' => 'fa-bullhorn', 'class' => 'bg-pengumuman']
        ];

        foreach($categories as $cat):
            if(mysqli_num_rows($cat['res']) > 0): ?>
            <section class="section-kabar">
                <div class="span section-badge-title <?= $cat['class'] ?>"><i class="fas <?= $cat['icon'] ?> me-1"></i> <?= explode(' ', $cat['title'])[0] ?></div>
                <h4 class="section-heading"><?= $cat['title'] ?></h4>
                <div class="row row-cols-2 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php while($row = mysqli_fetch_assoc($cat['res'])): ?>
                    <div class="col">
                        <div class="card-post">
                            <a href="<?= BASE_URL ?>/kabar/<?= $row['slug']; ?>">
                                <img src="<?= BASE_URL ?>/assets/images/post/<?= $row['thumbnail']; ?>" class="thumb-img" alt="<?= htmlspecialchars($row['judul']); ?>" loading="lazy">
                            </a>
                            <div class="card-body p-4">
                                <h5 class="mb-3">
                                    <a href="<?= BASE_URL ?>/kabar/<?= $row['slug']; ?>" class="card-title-link">
                                        <?= htmlspecialchars($row['judul']); ?>
                                    </a>
                                </h5>
                                <p class="card-text-summary"><?= get_excerpt($row['konten'], 140); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php endif;
        endforeach;
    else: ?>
        <div class="col-12 text-center py-5 my-5 bg-white rounded border shadow-sm">
            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
            <h5 class="text-secondary">Hasil Tidak Ditemukan</h5>
            <p class="text-muted small">Tidak ada yang cocok dengan kata kunci "<strong><?= htmlspecialchars($search); ?></strong>".</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
