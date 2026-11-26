<?php
/**
 * Engine Utama: Pencarian Naskah (Full Feature)
 * Lokasi: /search.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/search-options.php';
require_once __DIR__ . '/includes/bookmark-button.php';
require_once __DIR__ . '/includes/digital-access.php';
require_once __DIR__ . '/includes/version.php';

$kandagaSearchSubmitToken=is_string($_GET['search_submit']??null) && preg_match('/^[a-f0-9]{32}$/',$_GET['search_submit']) ? $_GET['search_submit'] : null;
unset($_GET['search_submit']);
// --- 1. PENGATURAN PAGINATION ---
$displayOptions = kandagaSearchOptions($_GET);
$perPage = $displayOptions['per_page'];
$viewMode = $displayOptions['view'];
$mobileViewportHint = (($_COOKIE['kandaga_viewport'] ?? '') === 'mobile');
$perPageOptions = $mobileViewportHint ? [16, 50, 100] : [15, 50, 100];
$page = max(1, min(1000000, (int)($_GET['page'] ?? 1)));
foreach (array_keys($_GET) as $key) {
    if (isset($_GET[$key]) && !is_scalar($_GET[$key])) unset($_GET[$key]);
}
foreach (['aksara', 'bahasa', 'alas_tulis', 'id_lokasi'] as $key) {
    if (isset($_GET[$key])) { $value = filter_var($_GET[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($value) $_GET[$key] = (string)$value; else unset($_GET[$key]); }
}
$_GET['view'] = $viewMode;
$_GET['per_page'] = (string)$perPage;
$start = ($page - 1) * $perPage;

// --- 2. TANGKAP PARAMETER ---
$search_keyword = $_GET['search'] ?? '';
$s_aksara       = $_GET['aksara'] ?? '';
$s_bahasa       = $_GET['bahasa'] ?? '';
$s_alas         = $_GET['alas_tulis'] ?? '';
$s_lembaga      = $_GET['id_lokasi'] ?? '';
$sortOrder      = (isset($_GET['sort']) && $_GET['sort'] == 'asc') ? "ASC" : "DESC";

$hasActiveFilters = !empty($search_keyword) || !empty($s_aksara) || !empty($s_bahasa) || !empty($s_alas) || !empty($s_lembaga);

// --- 3. BANGUN WHERE CLAUSE DINAMIS ---
$conditions = [];
$params = [];
$types = "";

if (!empty($search_keyword)) {
    $conditions[] = "(k.judul LIKE ? OR l.nama_tempat LIKE ? OR l.negara LIKE ? OR k.kode_inventaris LIKE ? OR k.riwayat_naskah LIKE ? OR k.deskripsi_fisik LIKE ? OR k.deskripsi_isi LIKE ? OR k.kata_kunci LIKE ? OR EXISTS (SELECT 1 FROM naskah_aksara na JOIN tb_aksara a ON na.id_aksara = a.id_aksara WHERE na.id_naskah = k.id AND a.nama_aksara LIKE ?) OR EXISTS (SELECT 1 FROM naskah_bahasa nb JOIN tb_bahasa b ON nb.id_bahasa = b.id_bahasa WHERE nb.id_naskah = k.id AND b.nama_bahasa LIKE ?) OR EXISTS (SELECT 1 FROM naskah_alas_tulis nat JOIN tb_alas_tulis at ON nat.id_alas_tulis = at.id_alas_tulis WHERE nat.id_naskah = k.id AND at.nama_alas_tulis LIKE ?))";
    $lk = "%$search_keyword%";
    for($i=0; $i<11; $i++) { $params[] = $lk; $types .= "s"; }
}

if (!empty($s_aksara)) { $conditions[] = "EXISTS (SELECT 1 FROM naskah_aksara WHERE id_naskah = k.id AND id_aksara = ?)"; $params[] = (int)$s_aksara; $types .= "i"; }
if (!empty($s_bahasa)) { $conditions[] = "EXISTS (SELECT 1 FROM naskah_bahasa WHERE id_naskah = k.id AND id_bahasa = ?)"; $params[] = (int)$s_bahasa; $types .= "i"; }
if (!empty($s_alas)) { $conditions[] = "EXISTS (SELECT 1 FROM naskah_alas_tulis WHERE id_naskah = k.id AND id_alas_tulis = ?)"; $params[] = (int)$s_alas; $types .= "i"; }
if (!empty($s_lembaga)) { $conditions[] = "k.id_lokasi = ?"; $params[] = (int)$s_lembaga; $types .= "i"; }

$whereClause = !empty($conditions) ? implode(" AND ", $conditions) : "1=1";

// --- 4. HITUNG TOTAL ---
$sqlCount = "SELECT COUNT(*) as total FROM katalog k LEFT JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi WHERE $whereClause";
$stmtCount = mysqli_prepare($conn, $sqlCount);
if ($types) mysqli_stmt_bind_param($stmtCount, $types, ...$params);
mysqli_stmt_execute($stmtCount);
$totalData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];
$totalPages = (int)ceil($totalData / $perPage);
$page = min($page, max(1, $totalPages));
$start = ($page - 1) * $perPage;

// --- 5. QUERY UTAMA ---
$sqlMain = "SELECT k.*, l.nama_tempat FROM katalog k LEFT JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi WHERE $whereClause ORDER BY k.tanggal_input $sortOrder, k.id $sortOrder LIMIT ?, ?";
$stmtMain = mysqli_prepare($conn, $sqlMain);
$finalParams = array_merge($params, [$start, $perPage]);
mysqli_stmt_bind_param($stmtMain, $types . "ii", ...$finalParams);
mysqli_stmt_execute($stmtMain);
$result = mysqli_stmt_get_result($stmtMain);

// --- 6. FUNGSI HELPER ---
function dapatkanKoleksiNama($conn, $sql, $id_naskah) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_naskah);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $arr = [];
    while ($row = mysqli_fetch_assoc($res)) { $arr[] = $row['nama_field']; }
    return !empty($arr) ? implode(', ', $arr) : '-';
}

function tampilkanKategoriModern($conn, $sql, $getParam, $label) {
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $collapseId = "collapse" . str_replace(' ', '', $label);
        echo "<div class='category-header d-flex justify-content-between align-items-center' data-bs-toggle='collapse' data-bs-target='#$collapseId' style='cursor:pointer;'>
                <span>$label</span><i class='fa-solid fa-chevron-down small text-muted'></i>
              </div>";
        echo "<div class='collapse show' id='$collapseId'><ul class='list-group list-group-flush mb-2'>";
        while ($row = mysqli_fetch_assoc($res)) {
            $nilaiTampil = htmlspecialchars($row['nama_tampil']);
            $nilaiKirim  = $row['id_kirim'];
            $p = $_GET; $p[$getParam] = $nilaiKirim; $p['page'] = 1;
            $link = "?" . http_build_query($p);
            $isActive = (isset($_GET[$getParam]) && $_GET[$getParam] == $nilaiKirim);
            $activeStyle = $isActive ? 'fw-bold text-primary bg-light' : '';
            echo "<li class='list-group-item $activeStyle'>
                    <a href='$link' class='w-100 d-flex justify-content-between align-items-center' style='text-decoration:none;' title='$nilaiTampil'>
                        <span class='text-truncate pe-2'>$nilaiTampil</span>
                        <span class='badge text-dark rounded-pill shadow-sm'>{$row['jumlah']}</span>
                    </a>
                  </li>";
        } echo "</ul></div>";
    }
}

function tampilkanPilihanFilterMobile($conn, $sql, $param, $label, $selected) {
    $res = mysqli_query($conn, $sql);
    echo '<div class="mb-3"><label class="small fw-bold text-muted mb-1">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><select name="' . htmlspecialchars($param, ENT_QUOTES, 'UTF-8') . '" class="form-select form-select-sm">';
    echo '<option value="">Semua</option>';
    if ($res) while ($option = mysqli_fetch_assoc($res)) {
        $value = (string)$option['id_kirim'];
        echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . ((string)$selected === $value ? ' selected' : '') . '>' . htmlspecialchars($option['nama_tampil'], ENT_QUOTES, 'UTF-8') . ' (' . (int)$option['jumlah'] . ')</option>';
    }
    echo '</select></div>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pencarian Naskah - KANDAGA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex,follow">
    <?php $seoType = 'search'; $seoTitle = 'Pencarian Naskah — KANDAGA'; $seoDescription = 'Telusuri katalog manuskrip Sunda berdasarkan judul, bahasa, aksara, lokasi, dan metadata lainnya.'; $seoUrl = BASE_URL . '/search'; include __DIR__ . '/includes/seo-meta.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style-search.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <style>.card-img-top { height: 180px; object-fit: cover; }</style>
<?php include __DIR__ . '/includes/module-head.php'; ?>
<script>
(function () {
    var mobile = window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
    var params = new URLSearchParams(window.location.search);
    if (mobile && !params.has('per_page')) {
        params.set('per_page', '16');
        params.set('page', '1');
        window.location.replace(window.location.pathname + '?' + params.toString());
    }
})();
</script>
</head>

<body class="kandaga-module kandaga-search">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="search-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h4 class="mb-4 fw-bold">Eksplorasi Katalog Naskah</h4>
                    <form action="<?= BASE_URL ?>/search.php" method="GET">
                        <?php foreach ($_GET as $key => $value): if (in_array($key, ['search', 'page'], true)) continue; ?>
                            <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="search_submit" value="<?= bin2hex(random_bytes(16)) ?>">
                        <div class="search-box-wrapper">
                            <input type="text" name="search" value="<?= htmlspecialchars($search_keyword); ?>" placeholder="Cari..." class="search-input-modern" />
                            <button type="submit" class="search-btn-modern"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5 mt-4">
        <div class="row">
            <div class="col-md-3">
                <button class="btn btn-outline-primary w-100 d-md-none mb-3" type="button" data-bs-toggle="modal" data-bs-target="#mobileSearchFilters" data-bs-backdrop="static" data-bs-keyboard="false"><i class="fa-solid fa-sliders me-2"></i>Kategori dan filter</button>
                <div id="searchCategoryPanel" class="collapse d-md-block bg-white rounded-lg shadow-sm border p-2 mb-4 sticky-top" style="top: 20px;">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold small">KATEGORI</span>
                        <?php if ($hasActiveFilters): ?><a href="<?= htmlspecialchars(kandagaSearchLink($_GET, ['page' => 1], ['search', 'aksara', 'bahasa', 'alas_tulis', 'id_lokasi']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger small fw-bold">Reset</a><?php endif; ?>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php
                        tampilkanKategoriModern($conn, "SELECT l.nama_tempat AS nama_tampil, l.id_lokasi AS id_kirim, COUNT(k.id) as jumlah FROM tb_lokasi l JOIN katalog k ON l.id_lokasi = k.id_lokasi GROUP BY l.id_lokasi, l.nama_tempat ORDER BY jumlah DESC", 'id_lokasi', 'Penyimpan');
                        tampilkanKategoriModern($conn, "SELECT am.nama_alas_tulis AS nama_tampil, am.id_alas_tulis AS id_kirim, COUNT(nat.id_naskah) AS jumlah FROM tb_alas_tulis am JOIN naskah_alas_tulis nat ON am.id_alas_tulis = nat.id_alas_tulis JOIN katalog kCount ON kCount.id = nat.id_naskah GROUP BY am.id_alas_tulis, am.nama_alas_tulis ORDER BY jumlah DESC", 'alas_tulis', 'Alas Tulis');
                        tampilkanKategoriModern($conn, "SELECT ak.nama_aksara AS nama_tampil, ak.id_aksara AS id_kirim, COUNT(na.id_naskah) AS jumlah FROM tb_aksara ak JOIN naskah_aksara na ON ak.id_aksara = na.id_aksara JOIN katalog kCount ON kCount.id = na.id_naskah GROUP BY ak.id_aksara, ak.nama_aksara ORDER BY jumlah DESC", 'aksara', 'Aksara');
                        tampilkanKategoriModern($conn, "SELECT bh.nama_bahasa AS nama_tampil, bh.id_bahasa AS id_kirim, COUNT(nb.id_naskah) AS jumlah FROM tb_bahasa bh JOIN naskah_bahasa nb ON bh.id_bahasa = nb.id_bahasa JOIN katalog kCount ON kCount.id = nb.id_naskah GROUP BY bh.id_bahasa, bh.nama_bahasa ORDER BY jumlah DESC", 'bahasa', 'Bahasa');
                        ?>
                    </ul>
                </div>
            </div>

            <div class="modal fade" id="mobileSearchFilters" tabindex="-1" role="dialog" aria-labelledby="mobileSearchFiltersTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="mobileSearchFiltersTitle"><i class="fa-solid fa-sliders me-2"></i>Kategori dan filter</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Batal"></button>
                        </div>
                        <form action="<?= BASE_URL ?>/search.php" method="GET">
                            <div class="modal-body">
                                <input type="hidden" name="search" value="<?= htmlspecialchars((string)$search_keyword, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="per_page" value="<?= (int)$perPage ?>">
                                <input type="hidden" name="sort" value="<?= htmlspecialchars(strtolower($sortOrder), ENT_QUOTES, 'UTF-8') ?>">
                                <?php
                                tampilkanPilihanFilterMobile($conn, "SELECT l.nama_tempat AS nama_tampil, l.id_lokasi AS id_kirim, COUNT(k.id) as jumlah FROM tb_lokasi l JOIN katalog k ON l.id_lokasi = k.id_lokasi GROUP BY l.id_lokasi, l.nama_tempat ORDER BY jumlah DESC", 'id_lokasi', 'Penyimpan', $s_lembaga);
                                tampilkanPilihanFilterMobile($conn, "SELECT am.nama_alas_tulis AS nama_tampil, am.id_alas_tulis AS id_kirim, COUNT(nat.id_naskah) AS jumlah FROM tb_alas_tulis am JOIN naskah_alas_tulis nat ON am.id_alas_tulis = nat.id_alas_tulis JOIN katalog kCount ON kCount.id = nat.id_naskah GROUP BY am.id_alas_tulis, am.nama_alas_tulis ORDER BY jumlah DESC", 'alas_tulis', 'Alas Tulis', $s_alas);
                                tampilkanPilihanFilterMobile($conn, "SELECT ak.nama_aksara AS nama_tampil, ak.id_aksara AS id_kirim, COUNT(na.id_naskah) AS jumlah FROM tb_aksara ak JOIN naskah_aksara na ON ak.id_aksara = na.id_aksara JOIN katalog kCount ON kCount.id = na.id_naskah GROUP BY ak.id_aksara, ak.nama_aksara ORDER BY jumlah DESC", 'aksara', 'Aksara', $s_aksara);
                                tampilkanPilihanFilterMobile($conn, "SELECT bh.nama_bahasa AS nama_tampil, bh.id_bahasa AS id_kirim, COUNT(nb.id_naskah) AS jumlah FROM tb_bahasa bh JOIN naskah_bahasa nb ON bh.id_bahasa = nb.id_bahasa JOIN katalog kCount ON kCount.id = nb.id_naskah GROUP BY bh.id_bahasa, bh.nama_bahasa ORDER BY jumlah DESC", 'bahasa', 'Bahasa', $s_bahasa);
                                ?>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <a href="<?= htmlspecialchars(kandagaSearchLink($_GET, ['page' => 1], ['search', 'aksara', 'bahasa', 'alas_tulis', 'id_lokasi']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-link text-danger">Reset</a>
                                <div><button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Terapkan</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="mb-4 d-flex flex-wrap align-items-center">
                    <div class="d-flex flex-wrap">
                        <?php
                        $activeFilters = ['search' => 'Keyword', 'aksara' => 'Aksara', 'bahasa' => 'Bahasa', 'alas_tulis' => 'Bahan', 'id_lokasi' => 'Lembaga'];
                        foreach ($activeFilters as $key => $label) {
                            if (!empty($_GET[$key])) {
                                $val = (string)$_GET[$key];
                                if($key == 'id_lokasi') { $get = mysqli_query($conn, "SELECT nama_tempat FROM tb_lokasi WHERE id_lokasi = '$val'"); $val = mysqli_fetch_assoc($get)['nama_tempat'] ?? 'Lokasi'; }
                                elseif($key == 'aksara') { $get = mysqli_query($conn, "SELECT nama_aksara FROM tb_aksara WHERE id_aksara = '$val'"); $val = mysqli_fetch_assoc($get)['nama_aksara'] ?? 'Aksara'; }
                                elseif($key == 'bahasa') { $get = mysqli_query($conn, "SELECT nama_bahasa FROM tb_bahasa WHERE id_bahasa = '$val'"); $val = mysqli_fetch_assoc($get)['nama_bahasa'] ?? 'Bahasa'; }
                                elseif($key == 'alas_tulis') { $get = mysqli_query($conn, "SELECT nama_alas_tulis FROM tb_alas_tulis WHERE id_alas_tulis = '$val'"); $val = mysqli_fetch_assoc($get)['nama_alas_tulis'] ?? 'Bahan'; }

                                $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                                $tmp = $_GET; unset($tmp[$key]); $tmp['page'] = 1;
                                $remLink = "?" . http_build_query($tmp);
                                echo "<span class='filter-badge me-2 mb-2'>$label: <strong>$val</strong> <a href='$remLink'><i class='fa-solid fa-circle-xmark'></i></a></span>";
                            }
                        }
                        ?>
                    </div>

                </div>

                <div class="search-results-toolbar mb-3">
                    <div class="search-view-toggle btn-group btn-group-sm" role="group" aria-label="Mode tampilan hasil">
                        <a href="<?= htmlspecialchars(kandagaSearchLink($_GET, ['view' => 'thumbnail', 'page' => $page]), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $viewMode === 'thumbnail' ? 'btn-primary' : 'btn-outline-primary' ?>" <?= $viewMode === 'thumbnail' ? 'aria-current="true"' : '' ?>><i class="fa-solid fa-table-cells-large me-1" aria-hidden="true"></i> Thumbnail</a>
                        <a href="<?= htmlspecialchars(kandagaSearchLink($_GET, ['view' => 'list', 'page' => $page]), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary' ?>" <?= $viewMode === 'list' ? 'aria-current="true"' : '' ?>><i class="fa-solid fa-list me-1" aria-hidden="true"></i> List</a>
                    </div>
                    <form action="<?= BASE_URL ?>/search.php" method="GET" class="search-display-form">
                        <?php foreach ($_GET as $key => $value): ?>
                            <?php if (!in_array($key, ['sort', 'per_page', 'page'], true)): ?>
                                <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="page" value="1">
                        <div class="search-select-group">
                            <label for="searchPerPage">Per halaman:</label>
                            <select id="searchPerPage" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($perPageOptions as $size): ?>
                                    <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="search-select-group">
                            <label for="searchSort">Urutkan:</label>
                            <select id="searchSort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="desc" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Terbaru</option>
                                <option value="asc" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Terlama</option>
                            </select>
                        </div>
                        <noscript><button type="submit" class="btn btn-sm btn-primary">Terapkan</button></noscript>
                    </form>
                </div>
                <p class="small text-muted mb-3" role="status">Menampilkan <?= $totalData > 0 ? $start + 1 : 0 ?>–<?= min($start + $perPage, (int)$totalData) ?> dari <?= (int)$totalData ?> naskah.</p>

                <div class="row">
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)):
                            $img = htmlspecialchars(kandagaImageUrl($row), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $alas_tulis_tampil = dapatkanKoleksiNama($conn, "SELECT m.nama_alas_tulis AS nama_field FROM tb_alas_tulis m JOIN naskah_alas_tulis j ON m.id_alas_tulis = j.id_alas_tulis WHERE j.id_naskah = ?", $row['id']);
                            $digital_indicator = kandagaDigitalAccessIndicatorMarkup($row, (int) $row['id']);
                        ?>
                            <?php if ($viewMode === 'list'): ?>
                                <div class="col-12 mb-3">
                                    <article class="card search-list-card shadow-sm">
                                        <div class="search-list-layout">
                                            <a class="search-list-image-link" href="<?= BASE_URL ?>/detail.php?id=<?= (int)$row['id'] ?>" tabindex="-1" aria-hidden="true"><img src="<?= $img ?>" alt="" class="search-list-image" loading="lazy"<?= kandagaImageFallbackAttribute() ?>></a>
                                            <div class="search-list-body">
                                                <h6 class="card-title search-list-title"><a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') ?></a></h6>
                                                <p class="small text-muted mb-2"><i class="fa-solid fa-university me-1" aria-hidden="true"></i><?= htmlspecialchars($row['nama_tempat'] ?? 'Lokasi Tidak Diketahui', ENT_QUOTES, 'UTF-8') ?></p>
                                                <div class="search-list-metadata small">
                                                    <span class="text-primary fw-bold"><?= htmlspecialchars($row['kode_inventaris'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="text-muted search-material-meta"><i class="fa-solid fa-scroll me-1" aria-hidden="true"></i><?= htmlspecialchars($alas_tulis_tampil, ENT_QUOTES, 'UTF-8') ?><?= $digital_indicator ?></span>
                                                </div>
                                            </div>
                                            <div class="search-list-actions"><?= kandagaBookmarkButton($row['id'], $row['judul']) ?></div>
                                        </div>
                                    </article>
                                </div>
                            <?php else: ?>
                            <div class="col-6 col-md-4 mb-4">
                                <div class="card card-naskah shadow-sm h-100">
                                    <div class="search-card-image-wrap"><a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$row['id'] ?>" aria-label="Buka detail <?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') ?>"><img src="<?= $img; ?>" class="card-img-top" alt="<?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy"<?= kandagaImageFallbackAttribute() ?>></a><div class="search-card-actions"><?= kandagaBookmarkButton($row['id'], $row['judul']) ?></div></div>
                                    <div class="card-body p-3">
                                        <h6 class="card-title text-truncate" title="<?= htmlspecialchars($row['judul']); ?>"><a class="text-dark" href="<?= BASE_URL ?>/detail.php?id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['judul']); ?></a></h6>
                                        <p class="small text-muted mb-2"><i class="fa-solid fa-university me-1"></i><?= htmlspecialchars($row['nama_tempat'] ?? 'Lokasi Tidak Diketahui'); ?></p>
                                        <div class="search-card-metadata">
                                            <span class="small text-primary fw-bold search-card-code"><?= htmlspecialchars($row['kode_inventaris']); ?></span>
                                            <span class="small text-muted search-material-meta"><i class="fa-solid fa-scroll me-1" aria-hidden="true"></i><?= htmlspecialchars($alas_tulis_tampil); ?><?= $digital_indicator ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5"><p class="text-muted">Tidak ditemukan naskah.</p></div>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php
                        $range = 2;
                        $prevParams = $_GET; $prevParams['page'] = $page - 1;
                        ?>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?= http_build_query($prevParams) ?>">«</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++):
                            if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)):
                                $pg = $_GET; $pg['page'] = $i;
                        ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?= http_build_query($pg); ?>"><?= $i; ?></a>
                            </li>
                        <?php elseif ($i == $page - $range - 1 || $i == $page + $range + 1): ?>
                            <li class="page-item disabled"><a class="page-link">...</a></li>
                        <?php endif; endfor; ?>
                        <?php
                        $nextParams = $_GET; $nextParams['page'] = $page + 1;
                        ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?= http_build_query($nextParams) ?>">»</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= BASE_URL; ?>/assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL; ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
