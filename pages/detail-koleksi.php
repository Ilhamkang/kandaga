<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * detail-koleksi.php
 * Versi Lengkap: Perbaikan error, pencarian, pagination, dan profil kurator
 */
ini_set('display_errors', 0); 
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
if (!defined('BASE_URL')) define('BASE_URL', '');

// 1. Ambil parameter dari URL
$slug   = $_GET['slug'] ?? null;
$id     = $_GET['id'] ?? null;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? ''; 
$limit  = 12; 
$offset = ($page - 1) * $limit;

// 2. Query Data Lokasi & Kurator
if ($slug) {
    $stmt = $conn->prepare("SELECT l.*, u.id as id_kurator, u.nama as nama_kurator, u.foto as foto_kurator 
                            FROM tb_lokasi l
                            LEFT JOIN tb_kurasi_koleksi k ON l.id_lokasi = k.id_lokasi
                            LEFT JOIN users u ON k.id_user = u.id
                            WHERE l.slug = ?");
    $stmt->bind_param("s", $slug);
} else if ($id) {
    $stmt = $conn->prepare("SELECT l.*, u.id as id_kurator, u.nama as nama_kurator, u.foto as foto_kurator 
                            FROM tb_lokasi l
                            LEFT JOIN tb_kurasi_koleksi k ON l.id_lokasi = k.id_lokasi
                            LEFT JOIN users u ON k.id_user = u.id
                            WHERE l.id_lokasi = ?");
    $stmt->bind_param("i", $id);
} else {
    header("Location: " . BASE_URL . "/pages/koleksi.php");
    exit;
}
$stmt->execute();
$data_lokasi = $stmt->get_result()->fetch_assoc();
if (!$data_lokasi) die("Koleksi tidak ditemukan.");

// 3. Menentukan URL Dasar untuk Reset agar bersih
$base_url_reset = $id ? "?id=" . urlencode((string)$id) : "?slug=" . urlencode((string)$slug);

// 4. Query Hitung Total Naskah (dengan Search Filter)
$search_query = "%$search%";
$sql_count = "SELECT COUNT(*) as total FROM katalog WHERE id_lokasi = ? AND (judul LIKE ? OR kode_inventaris LIKE ?)";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("iss", $data_lokasi['id_lokasi'], $search_query, $search_query);
$stmt_count->execute();
$total_naskah = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_naskah / $limit);

// 5. Query Naskah dengan LIMIT & SEARCH
$sql_naskah = "SELECT * FROM katalog WHERE id_lokasi = ? AND (judul LIKE ? OR kode_inventaris LIKE ?) ORDER BY id ASC LIMIT ? OFFSET ?";
$stmt_naskah = $conn->prepare($sql_naskah);
$stmt_naskah->bind_param("issii", $data_lokasi['id_lokasi'], $search_query, $search_query, $limit, $offset);
$stmt_naskah->execute();
$naskah_result = $stmt_naskah->get_result();

// Path Gambar Lokasi (Support External URL)
if (!empty($data_lokasi['url_gambar_eksternal']) && (strpos($data_lokasi['url_gambar_eksternal'], 'http') === 0)) {
    $src_lokasi = kandagaNormalizeImageUrl($data_lokasi['url_gambar_eksternal']) ?: kandagaPlaceholderImage();
} elseif (!empty($data_lokasi['foto_lokasi']) && file_exists(__DIR__ . '/../assets/images/' . $data_lokasi['foto_lokasi'])) {
    $src_lokasi = BASE_URL . '/assets/images/' . $data_lokasi['foto_lokasi'];
} else {
    $src_lokasi = BASE_URL . '/assets/images/msc-placeholder.png';
}

$src_user = (!empty($data_lokasi['foto_kurator']) && file_exists(__DIR__ . '/../assets/images/user/' . $data_lokasi['foto_kurator'])) ? BASE_URL . '/assets/images/user/' . $data_lokasi['foto_kurator'] : BASE_URL . '/assets/images/user/default-user.jpg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string)($data_lokasi['nama_tempat'] ?? 'Detail Koleksi')) ?> - Kandaga</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f8fafc; color: #334155; font-family: 'Inter', sans-serif; }
        .sidebar-sticky { position: sticky; top: 100px; }
        .card-meta-lokasi { border: none; border-radius: 16px; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .img-sidebar-cover { height: 220px; object-fit: cover; width: 100%; }
        .card-manuskrip { border: none; border-radius: 14px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; height: 100%; display: flex; flex-direction: column; }
        .card-manuskrip:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .img-naskah-wrapper { position: relative; width: 100%; padding-top: 110%; overflow: hidden; background-color: #fefcbf; border-bottom: 1px solid #f1f5f9; }
        .img-naskah-thumb { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .badge-code { font-family: 'SFMono-Regular', Consolas, "Liberation Mono", Menlo, monospace; background-color: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .box-kurator { background-color: #f8fafc; border-radius: 14px; border: 1px dashed #cbd5e1; padding: 16px; }
        .img-kurator-besar { width: 68px; height: 68px; object-fit: cover; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07); }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module">
    <?php require_once __DIR__ . '/../includes/analytics-context.php'; kandagaSetPageContext('collection','/koleksi/'.(int)$data_lokasi['id_lokasi'],$data_lokasi['nama_tempat'],(int)$data_lokasi['id_lokasi']); ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 order-2 order-lg-1">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <div>
                        <h3 class="font-weight-bold text-dark mb-1">Inventaris Manuskrip</h3>
                        <p class="text-muted small mb-0">Total <?= $total_naskah ?> naskah ditemukan.</p>
                    </div>
                </div>

                <form method="GET" action="" class="mb-4">
                    <?php if ($id): ?><input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>"><?php endif; ?>
                    <?php if ($slug): ?><input type="hidden" name="slug" value="<?= htmlspecialchars((string)$slug) ?>"><?php endif; ?>

                    <div class="input-group shadow-sm">
                        <input type="text" name="search" class="form-control border-primary" placeholder="Cari naskah di lokasi ini..." value="<?= htmlspecialchars((string)$search) ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        <?php if ($search !== ''): ?>
                            <a href="<?= $base_url_reset ?>" class="btn btn-outline-danger"><i class="fas fa-times"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <div class="row g-4">
                    <?php if ($naskah_result->num_rows > 0): ?>
                        <?php while ($naskah = $naskah_result->fetch_assoc()): ?>
                            <?php 
                                $src_naskah = kandagaImageUrl($naskah);
                            ?>
                            <div class="col-xl-4 col-md-6 col-sm-12">
                                <a href="<?= BASE_URL ?>/detail.php?id=<?= htmlspecialchars((string)$naskah['id']) ?>" class="text-decoration-none text-dark">
                                    <div class="card-manuskrip shadow-sm">
                                        <div class="img-naskah-wrapper">
                                            <img src="<?= htmlspecialchars((string)$src_naskah, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="img-naskah-thumb" alt="<?= htmlspecialchars((string)$naskah['judul']) ?>"<?= kandagaImageFallbackAttribute() ?>>
                                        </div>
                                        <div class="card-body p-3">
                                            <h6 class="font-weight-bold text-truncate"><?= htmlspecialchars((string)$naskah['judul']) ?></h6>
                                            <div class="badge-code mt-2">
                                                <i class="fas fa-archive mr-1"></i> <?= htmlspecialchars((string)$naskah['kode_inventaris']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <p class="text-muted">Tidak ada naskah ditemukan dengan kata kunci "<?= htmlspecialchars((string)$search) ?>".</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center flex-wrap">
                        <?php 
                        $range = 5;
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $start + $range - 1);
                        
                        $base_url_pagination = $base_url_reset . ($search ? "&search=" . urlencode((string)$search) : "");

                        if ($page > 1) echo '<li class="page-item"><a class="page-link" href="'.$base_url_pagination.'&page='.($page-1).'">«</a></li>';
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $base_url_pagination ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; 
                        
                        if ($page < $total_pages) echo '<li class="page-item"><a class="page-link" href="'.$base_url_pagination.'&page='.($page+1).'">»</a></li>';
                        ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4 order-1 order-lg-2 mb-4">
                <div class="card-meta-lokasi sidebar-sticky shadow-sm">
                    <img src="<?= htmlspecialchars((string)$src_lokasi) ?>" class="img-sidebar-cover" alt="Foto Tempat">
                    <div class="card-body p-4">
                        <h4 class="font-weight-bold"><?= htmlspecialchars((string)$data_lokasi['nama_tempat']) ?></h4>
                        <p class="text-muted small"><?= htmlspecialchars((string)($data_lokasi['alamat'] ?? 'Alamat tidak tersedia')) ?></p>
                        <hr>
                        <h6 class="text-muted font-weight-bold text-uppercase" style="font-size: 0.75rem;">Kurator</h6>
                        
                        <?php if (!empty($data_lokasi['id_kurator'])): ?>
                            <a href="<?= BASE_URL ?>/pages/detail-kontributor.php?id=<?= urlencode((string)$data_lokasi['id_kurator']) ?>" class="text-decoration-none">
                                <div class="box-kurator d-flex align-items-center">
                                    <img src="<?= htmlspecialchars((string)$src_user) ?>" class="rounded-circle img-kurator-besar me-3">
                                    <p class="fw-bold mb-0 text-dark"><?= htmlspecialchars((string)($data_lokasi['nama_kurator'] ?? 'Belum ada')) ?></p>
                                </div>
                            </a>
                        <?php else: ?>
                            <div class="box-kurator d-flex align-items-center">
                                <img src="<?= htmlspecialchars((string)$src_user) ?>" class="rounded-circle img-kurator-besar me-3">
                                <p class="fw-bold mb-0 text-muted">Belum ada</p>
                            </div>
                        <?php endif; ?>
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
