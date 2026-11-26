<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/bibliography.php';

$catalogId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;
$esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$imageFor = static function(array $row) use ($esc): string {
    return kandagaImageUrl($row);
};

$catalog = null;
if ($catalogId) {
    $stmt = $conn->prepare("SELECT b.*,m.cover_filename,m.cover_url FROM tb_bibliografi b LEFT JOIN tb_bibliografi_media m ON m.id_bibliografi=b.id WHERE b.id=? AND b.kategori='katalog'");
    $stmt->bind_param('i', $catalogId); $stmt->execute(); $catalog = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$catalog) { http_response_code(404); exit('Katalog tidak ditemukan.'); }
    $needle = '%' . $search . '%';
    $countStmt = $conn->prepare("SELECT COUNT(DISTINCT k.id) AS total FROM katalog k JOIN naskah_bibliografi nb ON nb.id_naskah=k.id WHERE nb.id_bibliografi=? AND (?='' OR k.judul LIKE ? OR k.kode_inventaris LIKE ?)");
    $empty = $search === '' ? '' : $search; $countStmt->bind_param('isss', $catalogId, $empty, $needle, $needle); $countStmt->execute(); $total = (int)$countStmt->get_result()->fetch_assoc()['total']; $countStmt->close();
    $stmt = $conn->prepare("SELECT DISTINCT k.id,k.judul,k.kode_inventaris,k.gambar,k.url_gambar_eksternal,l.nama_tempat FROM katalog k JOIN naskah_bibliografi nb ON nb.id_naskah=k.id LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi WHERE nb.id_bibliografi=? AND (?='' OR k.judul LIKE ? OR k.kode_inventaris LIKE ?) ORDER BY k.judul ASC LIMIT ? OFFSET ?");
    $stmt->bind_param('isssii', $catalogId, $empty, $needle, $needle, $limit, $offset); $stmt->execute(); $manuscripts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    $totalPages = max(1, (int)ceil($total / $limit));
} else {
    $totalPublished = (int)($conn->query('SELECT COUNT(*) AS total FROM katalog')->fetch_assoc()['total'] ?? 0);
    $needle = '%' . $search . '%';
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_bibliografi WHERE kategori='katalog' AND (?='' OR judul LIKE ? OR penyusun LIKE ? OR tahun_terbit LIKE ?)");
    $empty = $search === '' ? '' : $search; $countStmt->bind_param('ssss', $empty, $needle, $needle, $needle); $countStmt->execute(); $total = (int)$countStmt->get_result()->fetch_assoc()['total']; $countStmt->close();
    $stmt = $conn->prepare("SELECT b.*,m.cover_filename,m.cover_url,(SELECT COUNT(DISTINCT nb.id_naskah) FROM naskah_bibliografi nb WHERE nb.id_bibliografi=b.id) AS linked_manuscripts FROM tb_bibliografi b LEFT JOIN tb_bibliografi_media m ON m.id_bibliografi=b.id WHERE b.kategori='katalog' AND (?='' OR b.judul LIKE ? OR b.penyusun LIKE ? OR b.tahun_terbit LIKE ?) ORDER BY b.tahun_terbit DESC,b.judul ASC LIMIT ? OFFSET ?");
    $stmt->bind_param('ssssii', $empty, $needle, $needle, $needle, $limit, $offset); $stmt->execute(); $catalogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    $totalPages = max(1, (int)ceil($total / $limit));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $catalog ? $esc($catalog['judul']) . ' — Katalog KANDAGA' : 'Katalog Sumber — KANDAGA' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-bibliografi.css">
    <style>.catalog-heading{border-left:4px solid #2563eb;padding-left:1rem}.catalog-card{height:100%;border:1px solid #e5eaf2;border-radius:16px;background:#fff;padding:1.25rem;transition:transform .2s,box-shadow .2s}.catalog-card:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(15,61,117,.12)}.catalog-card h3{font-size:1.05rem;line-height:1.45}.catalog-count{color:#2563eb;font-weight:700}.catalog-cover{width:100%;height:180px;object-fit:contain;background:#f8fafc;border-radius:10px}.catalog-detail-cover{width:150px;height:210px;object-fit:contain;background:#f8fafc;border-radius:10px;display:block}.manuscript-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}.manuscript-source-card{border:1px solid #e5eaf2;border-radius:14px;overflow:hidden;background:#fff;height:100%}.manuscript-source-card img{display:block;width:100%;height:180px;object-fit:cover;background:#f1f5f9}.manuscript-source-card .body{padding:1rem}.catalog-apsa{background:#eff6ff;border-left:4px solid #2563eb;border-radius:10px;padding:1rem;color:#1e3a68}</style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module bibliography-page">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container bibliography-main">
<?php if (!$catalog): ?>
    <section class="catalog-index-shell bibliography-card">
    <header class="bibliography-header mb-4"><div class="d-flex justify-content-between align-items-center flex-wrap"><div><h1 class="h3 fw-bold mb-2">Katalog</h1><p class="mb-0 text-muted">Daftar katalog yang menjadi sumber pemuatan data manuskrip dalam KANDAGA.</p></div><div class="text-end text-muted"><i class="fa-solid fa-folder-tree fa-2x"></i><div class="small mt-2">Naskah tayang: <strong><?= number_format($totalPublished) ?></strong></div></div></div></header>
    <form method="get" class="row g-2 align-items-end mb-4"><div class="col-md-10 mb-2 mb-md-0"><label class="small text-muted fw-bold">Cari katalog</label><input class="form-control" name="search" value="<?= $esc($search) ?>" placeholder="Judul, penyusun, atau tahun terbit"></div><div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-search me-1"></i>Cari</button></div></form>
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Daftar katalog</h2><span class="badge bg-primary px-3 py-2"><?= number_format($total) ?> katalog</span></div>
    <div class="row">
    <?php foreach ($catalogs as $item): $year=$item['tahun_terbit'] ?: 'Tahun tidak dicatat'; $linked=(int)$item['linked_manuscripts']; $cover=kandagaBibliographyCover($item); ?>
        <div class="col-lg-4 col-md-6 mb-4"><article class="catalog-card"><?php if($cover): ?><img src="<?= $esc($cover) ?>" alt="Cover katalog" class="catalog-cover mb-3"><?php endif; ?><div class="d-flex justify-content-between mb-2"><span class="badge bg-light text-dark"><i class="fa-solid fa-folder-open me-1"></i>Katalog</span><span class="small text-muted"><?= $esc($year) ?></span></div><div class="small text-muted mb-2"><?= kandagaBibliographyCitation($item) ?></div><div class="d-flex justify-content-between align-items-center"><span class="catalog-count"><i class="fa-solid fa-book-open me-1"></i><?= number_format($linked) ?> manuskrip terkait</span><a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= BASE_URL ?>/katalog?id=<?= (int)$item['id'] ?>">Lihat <i class="fa-solid fa-arrow-right ms-1"></i></a></div></article></div>
    <?php endforeach; ?>
    </div>
    </section>
<?php else: $cover=kandagaBibliographyCover($catalog); ?>
    <a href="<?= BASE_URL ?>/katalog" class="small text-muted"><i class="fa-solid fa-arrow-left me-1"></i>Semua katalog</a>
    <div class="row align-items-start mt-3">
        <section class="col-lg-8 order-2 order-lg-1">
            <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 fw-bold mb-0">Manuskrip yang tercatat dalam katalog ini</h1><span class="badge bg-primary px-3 py-2"><?= number_format($total) ?> manuskrip unik</span></div>
            <form method="get" class="row g-2 align-items-end mb-4"><input type="hidden" name="id" value="<?= (int)$catalogId ?>"><div class="col-md-10 mb-2 mb-md-0"><label class="small text-muted fw-bold">Cari manuskrip dalam katalog ini</label><input class="form-control" name="search" value="<?= $esc($search) ?>" placeholder="Judul atau kode inventaris"></div><div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-search me-1"></i>Cari</button></div></form>
            <div class="manuscript-grid mb-4"><?php foreach($manuscripts as $item): ?><article class="manuscript-source-card"><img src="<?= $esc($imageFor($item)) ?>" alt="<?= $esc($item['judul']) ?>"<?= kandagaImageFallbackAttribute() ?>><div class="body"><h3 class="h6 fw-bold mb-2"><a class="text-dark" href="<?= BASE_URL ?>/detail.php?id=<?= (int)$item['id'] ?>"><?= $esc($item['judul']) ?></a></h3><div class="small text-muted mb-1"><i class="fa-solid fa-barcode me-1"></i><?= $esc($item['kode_inventaris'] ?: 'Kode inventaris tidak tersedia') ?></div><div class="small text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= $esc($item['nama_tempat'] ?: 'Tempat penyimpanan belum dicatat') ?></div><a class="btn btn-sm btn-outline-primary rounded-pill mt-3" href="<?= BASE_URL ?>/detail.php?id=<?= (int)$item['id'] ?>">Detail naskah <i class="fa-solid fa-arrow-right ms-1"></i></a></div></article><?php endforeach; ?></div>
        </section>
        <aside class="col-lg-4 order-1 order-lg-2 mb-4">
            <section class="catalog-detail-sidebar bibliography-card sticky-sidebar">
                <div class="small text-muted text-uppercase fw-bold mb-2">Detail katalog</div>
                <?php if($cover): ?><img src="<?= $esc($cover) ?>" alt="Cover katalog" class="catalog-detail-cover mb-3"><?php else: ?><div class="catalog-detail-cover catalog-cover-placeholder mb-3"><i class="fa-solid fa-folder-tree" aria-hidden="true"></i><span>Cover tidak tersedia</span></div><?php endif; ?>
                <div class="small text-muted text-uppercase fw-bold mb-2">Bibliografi APSA</div>
                <div class="catalog-apsa mb-3"><?= kandagaBibliographyCitation($catalog) ?></div>
                <div class="catalog-detail-count"><i class="fa-solid fa-book-open me-1"></i><?= number_format($total) ?> manuskrip tercatat</div>
            </section>
        </aside>
    </div>
<?php endif; ?>
<?php if ($totalPages > 1): ?><nav aria-label="Navigasi katalog"><ul class="pagination justify-content-center flex-wrap"><?php for($i=1;$i<=$totalPages;$i++): ?><li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?<?= $catalogId?'id='.(int)$catalogId.'&':'' ?><?= $search?'search='.urlencode($search).'&':'' ?>p=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?><script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script><script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body></html>
