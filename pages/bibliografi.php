<?php
require_once __DIR__ . '/../config/bootstrap.php';
// 1. Load Master Loader (config.php)
// Memastikan koneksi database dan konstanta BASE_URL tersedia
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/bibliography.php';
require_once __DIR__ . '/../includes/version.php';

// 2. Tangkap parameter pencarian, filter kategori, dan nomor halaman
$search   = isset($_GET['search_bib']) ? trim($_GET['search_bib']) : '';
$kategori = isset($_GET['kat_bib']) ? trim($_GET['kat_bib']) : '';
$tab      = isset($_GET['tab']) ? trim($_GET['tab']) : 'arsip';
if ($kategori === 'katalog' || $tab === 'katalog') {
    header('Location: ' . BASE_URL . '/katalog');
    exit;
}
$page     = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) { $page = 1; }

// 3. Atur Batasan Data per Halaman
$limit = 20;
$offset = ($page - 1) * $limit;

// 4. Bangun Klausa Kondisi (WHERE) untuk Filter
$where_clause = " WHERE b.kategori IN ('referensi primer','referensi sekunder')";

if (!empty($kategori)) {
    $where_clause .= " AND b.kategori = '" . $conn->real_escape_string($kategori) . "'";
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where_clause .= " AND (b.judul LIKE '%$search_safe%' OR b.penyusun LIKE '%$search_safe%' OR b.tahun_terbit LIKE '%$search_safe%')";
}

// 5. Hitung Total Seluruh Entri
$count_query = "SELECT COUNT(*) AS total FROM tb_bibliografi b" . $where_clause;
$count_res = $conn->query($count_query);
$total_data = 0;
if ($count_res) {
    $count_row = $count_res->fetch_assoc();
    $total_data = $count_row['total'];
}
$total_pages = ceil($total_data / $limit);

// 6. Ambil Data
$query_str = "SELECT b.*, bm.cover_filename, bm.cover_url, (SELECT COUNT(DISTINCT nb.id_naskah) FROM naskah_bibliografi nb WHERE nb.id_bibliografi=b.id) AS linked_manuscripts FROM tb_bibliografi b LEFT JOIN tb_bibliografi_media bm ON bm.id_bibliografi=b.id" . $where_clause . " ORDER BY b.penyusun ASC, b.tahun_terbit DESC LIMIT $limit OFFSET $offset";
$res = $conn->query($query_str);

// 7. Siapkan Query String URL agar pencarian tidak hilang saat pindah halaman
$url_params = "";
if (!empty($search)) { $url_params .= "&search_bib=" . urlencode($search); }
if (!empty($kategori)) { $url_params .= "&kat_bib=" . urlencode($kategori); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Daftar Pustaka & Referensi - KANDAGA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-tentang.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-bibliografi.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module kandaga-special-page bibliography-page kandaga-bibliography-page">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container pb-5">
    <div class="row">
        <div class="col-12">
            <div class="content-card bibliography-card">
                <header class="bibliography-header mb-4">
                    <h1 class="h3 fw-bold mb-2">Monograf</h1>
                    <p class="mb-0 text-muted">Sumber primer dan sekunder yang terhubung dengan entri naskah dalam KANDAGA.</p>
                </header>

                <div class="tab-content" id="bibTabContent">
                    <div class="tab-pane fade show active" id="arsip" role="tabpanel" aria-label="Daftar monograf">

                        <div class="search-box-wrapper">
                            <form method="GET" action="" class="row g-2 align-items-end">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <label class="small fw-bold text-muted mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Kata Kunci</label>
                                    <input type="text" name="search_bib" class="form-control"
                                           placeholder="Cari nama penulis, tahun, atau judul..."
                                           value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <label class="small fw-bold text-muted mb-1"><i class="fa-solid fa-filter me-1"></i> Kategori Acuan</label>
                                    <select name="kat_bib" class="form-select text-muted">
                                        <option value="">-- Primer & Sekunder --</option>
                                        <option value="referensi primer" <?= $kategori === 'referensi primer' ? 'selected' : '' ?>>📜 Publikasi Primer (Suntingan Teks)</option>
                                        <option value="referensi sekunder" <?= $kategori === 'referensi sekunder' ? 'selected' : '' ?>>📚 Publikasi Sekunder (Kajian)</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Cari</button>
                                </div>
                            </form>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h5 class="fw-bold m-0" style="color: #334155;">Daftar Rujukan</h5>
                            <span class="badge bg-secondary py-1 px-3 fw-bold text-xs">
                                Total: <?= $total_data; ?> Entri <?= $total_pages > 1 ? "(Hal $page dari $total_pages)" : ""; ?>
                            </span>
                        </div>

                        <div class="bib-list mb-4">
                            <?php
                            if ($res && $res->num_rows > 0):
                                while($row = $res->fetch_assoc()):
                                    $tahun = !empty($row['tahun_terbit']) ? htmlspecialchars($row['tahun_terbit']) : '-';
                                    $cover = kandagaBibliographyCover($row);
                                    $penyusun = htmlspecialchars($row['penyusun']);
                                    $judul = htmlspecialchars($row['judul']);
                                    $judul_bab = htmlspecialchars($row['judul_bab'] ?? '');
                                    $penerbit = htmlspecialchars($row['penerbit'] ?? '');
                                    $kota = htmlspecialchars($row['kota_terbit'] ?? '');
                                    $jurnal = htmlspecialchars($row['nama_jurnal'] ?? '');
                                    $vol = htmlspecialchars($row['volume'] ?? '');
                                    $num = htmlspecialchars($row['nomor'] ?? '');
                                    $hal = htmlspecialchars($row['halaman'] ?? '');

                                    $display_citation = kandagaBibliographyCitation($row);
                                    $cite_apsa = html_entity_decode(trim(strip_tags($display_citation)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    $cite_apa = $cite_apsa;
                                    $cite_chicago = $cite_apsa;

                                    if ($row['kategori'] === 'katalog') {
                                        $icon_html = '<i class="fa-solid fa-folder-open text-muted"></i>';
                                        $tooltip_text = 'Katalog Cetak & Inventaris';
                                    } elseif ($row['kategori'] === 'referensi primer') {
                                        $icon_html = '<i class="fa-solid fa-scroll text-danger"></i>';
                                        $tooltip_text = 'Publikasi Primer (Suntingan Teks)';
                                    } else {
                                        $icon_html = '<i class="fa-solid fa-book-bookmark text-success"></i>';
                                        $tooltip_text = 'Publikasi Sekunder (Kajian/Ulasan)';
                                    }
                                    $linkedItems = [];
                                    if (($row['kategori'] ?? '') !== 'katalog' && (int)($row['linked_manuscripts'] ?? 0) > 0) {
                                        $linkedStmt = $conn->prepare('SELECT DISTINCT k.id, k.judul, k.kode_inventaris, k.gambar, k.url_gambar_eksternal, l.nama_tempat FROM katalog k JOIN naskah_bibliografi nb ON nb.id_naskah=k.id LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi WHERE nb.id_bibliografi=? ORDER BY k.judul ASC');
                                        $linkedStmt->bind_param('i', $row['id']); $linkedStmt->execute();
                                        $linkedItems = $linkedStmt->get_result()->fetch_all(MYSQLI_ASSOC); $linkedStmt->close();
                                        foreach ($linkedItems as &$linkedItem) {
                                            $linkedItem['image'] = kandagaImageUrl($linkedItem);
                                            unset($linkedItem['gambar'], $linkedItem['url_gambar_eksternal']);
                                        }
                                        unset($linkedItem);
                                    }
                                    ?>
                                    <div class="bib-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <?php if ($cover): ?><img src="<?= htmlspecialchars($cover) ?>" alt="Cover publikasi" class="me-3 rounded shadow-sm" style="width:72px;height:96px;object-fit:cover;flex:0 0 72px"><?php endif; ?>
                                            <div class="bib-citation pe-4">
                                                <?= $display_citation ?>
                                            </div>
                                            <div class="d-flex align-items-center flex-shrink-0">
                                                <button type="button" class="btn btn-cite-trigger btn-sm me-2"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#citeModal"
                                                        data-apa="<?= htmlspecialchars($cite_apa) ?>"
                                                        data-apsa="<?= htmlspecialchars($cite_apsa) ?>"
                                                        data-chicago="<?= htmlspecialchars($cite_chicago) ?>"
                                                        title="Kutip referensi ini">
                                                    <i class="fa-solid fa-quote-right"></i>
                                                </button>

                                                <div class="category-icon-box" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $tooltip_text ?>">
                                                    <?= $icon_html ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bib-manuscript-link mt-3">
                                            <?php if (($row['kategori'] ?? '') !== 'katalog' && $linkedItems): ?>
                                                <button type="button" class="btn btn-outline-primary btn-sm btn-linked-manuscripts" data-bs-toggle="modal" data-bs-target="#linkedManuscriptsModal" data-bib-title="<?= htmlspecialchars($row['judul'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-bib-cover="<?= htmlspecialchars($cover ?: '', ENT_QUOTES, 'UTF-8') ?>" data-bib-citation="<?= htmlspecialchars($cite_apsa, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-items="<?= htmlspecialchars(json_encode($linkedItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fa-solid fa-link me-1"></i> Lihat <?= number_format(count($linkedItems)) ?> naskah terkait
                                                </button>
                                            <?php elseif (($row['kategori'] ?? '') !== 'katalog'): ?>
                                                <span class="small text-muted"><i class="fa-solid fa-link-slash me-1"></i> Belum terhubung ke naskah</span>
                                            <?php else: ?>
                                                <span class="small text-muted"><i class="fa-solid fa-folder-open me-1"></i> Relasi katalog dikelola khusus</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if(!empty($row['doi']) || !empty($row['url'])): ?>
                                            <div class="mt-2 small">
                                                <?php if(!empty($row['doi'])): ?>
                                                    <span class="me-3 text-muted">
                                                        <i class="fa-solid fa-fingerprint me-1"></i>
                                                        <a href="https://doi.org/<?= htmlspecialchars($row['doi']) ?>" target="_blank" class="text-primary fw-bold text-decoration-none">DOI: <?= htmlspecialchars($row['doi']) ?></a>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if(!empty($row['url'])): ?>
                                                    <span class="text-muted">
                                                        <i class="fa-solid fa-link me-1"></i>
                                                        <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" class="text-primary text-decoration-none text-truncate d-inline-block" style="max-width: 400px; vertical-align: bottom;"><?= htmlspecialchars($row['url']) ?></a>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-book-open mb-3 fa-2x" style="color: #cbd5e1;"></i>
                                    <p class="mb-0 fst-italic">Tidak ada acuan pustaka yang cocok dengan kriteria pencarian.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="modal fade" id="linkedManuscriptsModal" tabindex="-1" role="dialog" aria-labelledby="linkedManuscriptsTitle" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable bibliography-related-modal-dialog" role="document"><div class="modal-content border-0 shadow-lg bibliography-related-modal">
                                <div class="modal-header bibliography-related-modal-header"><img id="linkedBibliographyCover" class="linked-bibliography-cover d-none" src="" alt="Cover monograf"><div class="bibliography-related-heading"><div class="small text-white-50 mb-1">Bibliografi</div><h5 class="modal-title mb-1" id="linkedManuscriptsTitle"></h5><div id="linkedManuscriptsCitation" class="small bibliographic-citation"></div></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                                <div class="modal-body bibliography-related-modal-body"><p class="text-muted small mb-3">Manuskrip yang dirujuk atau digunakan sebagai objek penelitian dalam bibliografi ini.</p><div id="linkedManuscriptsList" class="linked-manuscripts-list"></div></div>
                            </div></div>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Navigasi Halaman Bibliografi" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?p=<?= $page - 1 . $url_params; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo; Sebelumnya</span>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?p=<?= $i . $url_params; ?>"><?= $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?p=<?= $page + 1 . $url_params; ?>" aria-label="Next">
                                            <span aria-hidden="true">Selanjutnya &raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function(){
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) { new bootstrap.Tooltip(el); });
    $('.btn-linked-manuscripts').on('click', function(){
        const title=$(this).data('bib-title')||'Sumber bibliografi';
        const citation=$(this).data('bib-citation')||'';
        const cover=$(this).attr('data-bib-cover')||'';
        let items=[]; try { items=JSON.parse($(this).attr('data-items')||'[]'); } catch(e) { items=[]; }
        $('#linkedManuscriptsTitle').text(title).toggle(!!title);
        $('#linkedManuscriptsCitation').text(citation);
        const coverEl=$('#linkedBibliographyCover');
        coverEl.off('error.kandagaCover').on('error.kandagaCover', function(){ $(this).addClass('d-none').removeAttr('src'); });
        coverEl.toggleClass('d-none', !cover).attr('src', cover || '');
        const html=items.length ? items.map(function(item){
            const safeTitle=$('<div>').text(item.judul||'Tanpa judul').html();
            const safeCode=$('<div>').text(item.kode_inventaris||'Kode inventaris tidak tersedia').html();
            const safeLocation=$('<div>').text(item.nama_tempat||'Tempat penyimpanan belum dicatat').html();
            const safeImage=$('<div>').text(item.image||'<?= BASE_URL ?>/assets/images/msc-placeholder.png').html();
            return '<div class="linked-manuscript-item"><img class="linked-manuscript-image" src="'+safeImage+'" alt="'+safeTitle+'" loading="lazy"><div class="linked-manuscript-info"><div class="manuscript-title">'+safeTitle+'</div><div class="manuscript-code">'+safeCode+'</div><div class="manuscript-location"><i class="fa-solid fa-location-dot me-1"></i>'+safeLocation+'</div></div><a class="btn btn-sm btn-primary linked-manuscript-detail" href="<?= BASE_URL ?>/detail.php?id='+encodeURIComponent(item.id)+'"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Detail naskah</a></div>';
        }).join('') : '<div class="text-center text-muted py-4">Belum ada naskah terkait.</div>';
        $('#linkedManuscriptsList').html(html);
    });
});
// ... (Bagian script lainnya tetap sama) ...
</script>

</body>
</html>
