<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * Halaman Detail Kabar
 * Lokasi: /pages/post.php
 */
require_once __DIR__ . '/../config.php';

// Validasi BASE_URL agar tidak error jika konstanta belum didefinisikan
if (!defined('BASE_URL')) { define('BASE_URL', '/kandaga'); }

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';

// Query artikel
$stmt = $conn->prepare("SELECT k.*, u.nama FROM tb_kabar k LEFT JOIN users u ON k.penulis_id = u.id WHERE k.slug = ? AND k.status = 'published' LIMIT 1");
$stmt->bind_param('s', $slug); $stmt->execute(); $data = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$data) {
    http_response_code(404);
    die("<div class='container py-5 text-center'>
            <h3>Artikel tidak ditemukan.</h3>
            <a href='" . BASE_URL . "/kabar' class='btn btn-primary'>Kembali ke Daftar Kabar</a>
         </div>");
}

// Query sidebar
$stmt_sidebar = $conn->prepare("SELECT judul, slug, thumbnail, konten FROM tb_kabar WHERE slug != ? AND status = 'published' ORDER BY id_kabar DESC LIMIT 3");
$stmt_sidebar->bind_param('s', $slug); $stmt_sidebar->execute(); $result_sidebar = $stmt_sidebar->get_result();

function get_summary($konten, $limit = 20) {
    $text = strip_tags($konten);
    $words = explode(' ', $text);
    $summary = count($words) > $limit ? implode(' ', array_slice($words, 0, $limit)) . '...' : $text;
    return htmlspecialchars($summary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($data['judul']); ?> - KANDAGA</title>

    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style-post-kabar.css">
        <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style-footer.css">


    <style>
        .article-meta { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; margin-bottom: 25px; color: #64748b; }
        .badge-category { font-weight: 700; text-transform: uppercase; font-size: 0.75rem; padding: 5px 10px; border-radius: 6px; }
        .badge-berita { background-color: #e0f2fe; color: #0369a1; }
        .badge-kegiatan { background-color: #fef3c7; color: #b45309; }
        .badge-artikel { background-color: #dcfce7; color: #15803d; }
        .badge-default { background-color: #f1f5f9; color: #475569; }
        .sidebar-search-container { background: white; border-radius: 8px; padding: 15px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style-kabar-responsive.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="kandaga-module kandaga-post-page">

    <?php require_once __DIR__ . '/../includes/analytics-context.php'; kandagaSetPageContext('article','/kabar/'.(int)$data['id_kabar'],$data['judul'],(int)$data['id_kabar']); ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5 article-page-shell">
        <div class="row">
            <main class="col-lg-9 article-main-column">
                <div class="main-content kandaga-prose article-surface">
                    <h1 class="article-title" style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($data['judul']); ?></h1>

                    <div class="article-meta">
                        <?php
                        $kategori = isset($data['kategori']) ? strtolower($data['kategori']) : 'lainnya';
                        $cls_badge = 'badge-default';
                        if($kategori == 'berita') $cls_badge = 'badge-berita';
                        elseif($kategori == 'kegiatan') $cls_badge = 'badge-kegiatan';
                        elseif($kategori == 'artikel') $cls_badge = 'badge-artikel';
                        ?>
                        <span class="badge <?= $cls_badge; ?> badge-category shadow-sm">
                            <i class="fas fa-tag me-1"></i> <?= htmlspecialchars($data['kategori'] ?? 'Kabar'); ?>
                        </span>

                        <span><i class="fa-solid fa-calendar-days"></i> <?= date('d M Y • H:i', strtotime($data['waktu_buat'])); ?></span>
                        <span><i class="fa-solid fa-user-pen"></i> <?= htmlspecialchars($data['nama'] ?? 'Admin'); ?></span>
                        <?php $articleViews=kandagaArticleViews($conn,(int)$data['id_kabar']); if($articleViews!==null): ?><span class="article-views"><i class="fa-solid fa-eye" aria-hidden="true"></i> <?= number_format($articleViews) ?> kunjungan</span><?php endif; ?>

                    </div>

                    <img src="<?= BASE_URL; ?>/assets/images/post/<?= $data['thumbnail']; ?>" class="img-fluid w-100 mb-4 article-featured-image" style="border-radius: 8px; max-height: 480px; object-fit: cover;" alt="<?= htmlspecialchars($data['judul']); ?>">

                    <div class="article-body">
                        <?= $data['konten']; ?>
                    </div>

                </div>
            </main>

            <aside class="col-lg-3 article-sidebar-column">
                <div class="sidebar-search-container shadow-sm article-sidebar-card">
                    <form action="<?= BASE_URL; ?>/kabar/" method="GET">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control border-end-0 bg-light" placeholder="Cari tulisan..." required>
                            <button class="btn btn-outline-secondary border-start-0 bg-light text-muted" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="sidebar-container article-sidebar-card">
                    <h5 class="sidebar-title">Informasi Lainnya</h5>
                    <?php while ($side = mysqli_fetch_assoc($result_sidebar)): ?>
                        <div class="sidebar-item">
                            <a href="<?= BASE_URL; ?>/kabar/<?= rawurlencode((string)$side['slug']); ?>">
                                <img src="<?= BASE_URL; ?>/assets/images/post/<?= rawurlencode((string)$side['thumbnail']); ?>" class="sidebar-thumb" style="border-radius: 4px; object-fit: cover; width: 100%; height: auto; aspect-ratio: 16 / 10;" alt="<?= htmlspecialchars($side['judul'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            </a>
                            <a href="<?= BASE_URL; ?>/kabar/<?= rawurlencode((string)$side['slug']); ?>" class="sidebar-link"><?= htmlspecialchars($side['judul'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></a>
                            <p class="sidebar-summary"><?= get_summary($side['konten'], 20); ?></p>
                        </div>
                        <hr>
                    <?php endwhile; ?>
                </div>
            </aside>
        </div>
    </div>


<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
