<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Pastikan path ke database benar
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/version.php';

// Pastikan BASE_URL terdefinisi
if (!defined('BASE_URL')) define('BASE_URL', '');

// 1. Ambil abjad yang dipilih & sanitasi
$abjadRaw = strtoupper(trim((string)($_GET['letter'] ?? 'A')));
$abjad = preg_match('/^[A-Z]$/', $abjadRaw) ? mysqli_real_escape_string($conn, $abjadRaw) : 'A';
$page = max(1, min(1000000, (int)($_GET['page'] ?? 1)));
$limit = 40;

// 2. Query Total
$queryTotal = "SELECT COUNT(DISTINCT judul) AS total FROM katalog WHERE judul LIKE '$abjad%'";
$resultTotal = mysqli_query($conn, $queryTotal);
$totalData = mysqli_fetch_assoc($resultTotal)['total'] ?? 0;
$totalPages = max(1, (int)ceil((int)$totalData / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// 3. Query Utama
$sqlIndeks = "SELECT judul, COUNT(*) as jumlah, MAX(id) as id_naskah
              FROM katalog
              WHERE judul LIKE '$abjad%'
              GROUP BY judul
              ORDER BY judul ASC
              LIMIT $offset, $limit";
$resultIndeks = mysqli_query($conn, $sqlIndeks);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Indeks Judul - KANDAGA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-indeks-judul.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-footer.css">

<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module kandaga-special-page kandaga-title-index-page">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header">
    <div class="container text-center">
        <h2 class="fw-bold mb-2">Indeks Judul</h2>
        <p class="text-muted">Menampilkan koleksi naskah berdasarkan urutan alfabetis</p>
        <div class="d-flex flex-wrap justify-content-center mt-4">
            <?php foreach (range('A', 'Z') as $char): ?>
                <a href="<?= BASE_URL ?>/indeks-judul.php?letter=<?= $char ?>"
                   class="btn-letter <?= ($abjad == $char) ? 'active' : '' ?>">
                    <?= $char ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card index-card shadow-sm">
                <div class="small text-muted px-3 pt-3 pb-2" role="status">
                    Menampilkan <?= $totalData > 0 ? (($page - 1) * $limit + 1) : 0 ?>–<?= min($page * $limit, (int)$totalData) ?> dari <?= (int)$totalData ?> judul.
                </div>
                <div class="list-group list-group-flush">
                    <?php if (mysqli_num_rows($resultIndeks) > 0):
                        while ($row = mysqli_fetch_assoc($resultIndeks)):
                            $targetLink = ($row['jumlah'] == 1)
                                          ? BASE_URL . "/detail.php?id=".$row['id_naskah']
                                          : BASE_URL . "/search.php?search=".urlencode($row['judul']);
                    ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="<?= $targetLink ?>" class="judul-link">
                                <i class="fa-solid fa-book-bookmark me-3 text-primary opacity-50"></i>
                                <?= htmlspecialchars($row['judul']) ?>
                            </a>
                            <span class="naskah-count"><?= $row['jumlah'] ?> naskah</span>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="py-5 text-center text-muted">
                            <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-20"></i>
                            <p>Tidak ditemukan naskah dengan awalan huruf "<?= htmlspecialchars($abjad) ?>"</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php
                        $paginationUrl = static function (int $targetPage) use ($abjad): string {
                            return BASE_URL . '/pages/indeks-judul.php?letter=' . rawurlencode($abjad) . '&page=' . $targetPage;
                        };
                        $windowStart = max(1, $page - 2);
                        $windowEnd = min($totalPages, $page + 2);
                        ?>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <?php if ($page > 1): ?><a class="page-link" href="<?= htmlspecialchars($paginationUrl($page - 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Halaman sebelumnya"><span aria-hidden="true">&laquo;</span> <span class="pagination-label">Sebelumnya</span></a><?php else: ?><span class="page-link"><span aria-hidden="true">&laquo;</span> <span class="pagination-label">Sebelumnya</span></span><?php endif; ?>
                        </li>
                        <?php if ($windowStart > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($paginationUrl(1), ENT_QUOTES, 'UTF-8') ?>">1</a></li>
                            <?php if ($windowStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link shadow-sm border-0 mx-1 rounded" href="<?= htmlspecialchars($paginationUrl($i), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($windowEnd < $totalPages): ?>
                            <?php if ($windowEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($paginationUrl($totalPages), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <?php if ($page < $totalPages): ?><a class="page-link" href="<?= htmlspecialchars($paginationUrl($page + 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Halaman berikutnya"><span class="pagination-label">Berikutnya</span> <span aria-hidden="true">&raquo;</span></a><?php else: ?><span class="page-link"><span class="pagination-label">Berikutnya</span> <span aria-hidden="true">&raquo;</span></span><?php endif; ?>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
