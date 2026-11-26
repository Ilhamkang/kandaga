<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Pastikan path ke database benar
require_once __DIR__ . '/../config/database.php';

// Pastikan BASE_URL terdefinisi
if (!defined('BASE_URL')) define('BASE_URL', '');

$page = max(1, min(1000000, (int)($_GET['page'] ?? 1)));
$limit = 6;
$offset = ($page - 1) * $limit;

// Query Total
$queryTotal = "SELECT COUNT(DISTINCT l.id_lokasi) AS total
               FROM tb_lokasi l
               JOIN katalog k ON l.id_lokasi = k.id_lokasi";
$resultTotal = mysqli_query($conn, $queryTotal);
$totalData = $resultTotal ? mysqli_fetch_assoc($resultTotal)['total'] : 0;
$totalPages = ceil($totalData / $limit);

// Query Lokasi
$sqlLokasi = "SELECT l.*, counts.total_naskah
              FROM tb_lokasi l
              JOIN (SELECT id_lokasi, COUNT(*) AS total_naskah FROM katalog GROUP BY id_lokasi) counts ON counts.id_lokasi = l.id_lokasi
              WHERE counts.total_naskah > 0
              ORDER BY l.nama_tempat ASC
              LIMIT $offset, $limit";
$resultLokasi = mysqli_query($conn, $sqlLokasi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Lokasi - KANDAGA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">

    <style>
        body { background-color: #f8fafc; color: #334155; font-family: 'Inter', sans-serif; }
        .page-header { background: white; padding: 50px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 40px; }

        .location-grid-card { border: none; border-radius: 16px; overflow: hidden; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; display: flex; flex-direction: column; }
        .location-grid-card:hover { transform: translateY(-6px); box-shadow: 0 12px 20px -3px rgba(0,0,0,0.08); }

        .img-grid-container { position: relative; width: 100%; padding-top: 75%; overflow: hidden; background-color: #e2e8f0; }
        .img-grid-location { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .location-grid-card:hover .img-grid-location { transform: scale(1.06); }

        .badge-naskah-floating { position: absolute; top: 15px; right: 15px; background: rgba(30, 41, 59, 0.85); backdrop-filter: blur(4px); color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; z-index: 2; }
        .card-body-grid { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; }
        .institution-grid-name { font-size: 1.05rem; font-weight: 700; color: #1e293b; line-height: 1.4; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.8rem; }
        .country-meta { font-size: 0.8rem; color: #64748b; font-weight: 500; margin-bottom: 15px; }
        .country-meta i { color: #3b82f6; margin-right: 4px; }

        .btn-detail-trigger { background: #f1f5f9; color: #475569; border: none; font-weight: 600; font-size: 0.85rem; padding: 10px; border-radius: 10px; width: 100%; transition: all 0.2s ease; margin-top: auto; }
        .btn-detail-trigger:hover { background: #2563eb; color: white; }

        .modal-img-banner { width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; box-shadow: inset 0 0 20px rgba(0,0,0,0.1); }
        .modal-meta-box { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .modal-meta-item { font-size: 0.85rem; color: #475569; margin-bottom: 8px; }
        .modal-meta-item i { width: 20px; color: #2563eb; }

        .location-grid-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer; /* TAMBAHKAN INI */
        }

    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module kandaga-special-page kandaga-location-index-page">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header text-center">
    <div class="container">
        <h2 class="fw-bold">Lokasi Preservasi Koleksi</h2>
        <p class="text-muted mb-0">Daftar institusi, perpustakaan, dan tempat penyimpanan manuskrip nusantara yang terdata di platform KANDAGA.</p>
    </div>
</div>

<div class="container pb-5">
    <?php if ($resultLokasi && mysqli_num_rows($resultLokasi) > 0): ?>
        <div class="row row-cols-2">
            <?php while ($row = mysqli_fetch_assoc($resultLokasi)): ?>
                <?php
                    $img_filename = $row['foto_lokasi'] ?? '';
                    // Menggunakan __DIR__ untuk validasi file fisik, BASE_URL untuk link web
                    $server_file_path = __DIR__ . '/../assets/images/' . $img_filename;
                    $final_img = (!empty($img_filename) && file_exists($server_file_path))
                                 ? BASE_URL . '/assets/images/' . $img_filename
                                 : BASE_URL . '/assets/images/msc-placeholder.png';
                ?>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="location-grid-card shadow-sm">
                        <div class="img-grid-container">
                            <div class="badge-naskah-floating">
                                <i class="fas fa-book me-1"></i> <?= number_format($row['total_naskah']) ?> Naskah
                            </div>
                            <img src="<?= htmlspecialchars($final_img) ?>" class="img-grid-location" alt="<?= htmlspecialchars($row['nama_tempat']) ?>">
                        </div>

                        <div class="card-body-grid">
                            <h5 class="institution-grid-name" title="<?= htmlspecialchars($row['nama_tempat']) ?>">
                                <?= htmlspecialchars($row['nama_tempat']) ?>
                            </h5>
                            <div class="country-meta">
                                <i class="fas fa-globe-asia"></i> <?= htmlspecialchars($row['negara'] ?? 'Indonesia') ?>
                            </div>

                            <button type="button" class="btn-detail-trigger btn-open-location-modal"
                                    data-nama="<?= htmlspecialchars($row['nama_tempat']) ?>"
                                    data-foto="<?= htmlspecialchars($final_img) ?>"
                                    data-negara="<?= htmlspecialchars($row['negara'] ?? 'Indonesia') ?>"
                                    data-alamat="<?= htmlspecialchars($row['alamat'] ?? 'Alamat tidak tersedia') ?>"
                                    data-naskah="<?= number_format($row['total_naskah']) ?>"
                                    data-search-url="<?= BASE_URL ?>/search.php?id_lokasi=<?= (int)$row['id_lokasi'] ?>&page=1"
                                    data-deskripsi="<?= !empty($row['deskripsi']) ? htmlspecialchars($row['deskripsi']) : 'Deskripsi belum tersedia.' ?>">
                                <i class="fas fa-info-circle me-1"></i> Lihat Detail Lokasi
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
            <p class="text-muted">Belum ada data lokasi koleksi naskah yang tersedia saat ini.</p>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination pagination-sm justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link px-3 py-2 fw-bold" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalDetailLokasi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden;">
            <div class="modal-header px-4 py-3 align-items-center" style="background: #1e293b; color: white; border-bottom: none;">
                <h5 class="modal-title fw-bold" id="modalLocationName"></h5>
                <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal" aria-label="Close" style="outline: none;"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <img src="" id="modalLocationImg" class="modal-img-banner" alt="Banner Lokasi">

                <div class="row">
                    <div class="col-md-5">
                        <div class="modal-meta-box">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-fingerprint me-2 text-secondary"></i>Informasi Umum</h6>
                            <div class="modal-meta-item">
                                <i class="fas fa-globe-asia"></i> <b>Negara:</b> <span id="modalLocationCountry"></span>
                            </div>
                            <div class="modal-meta-item">
                                <i class="fas fa-map-marker-alt"></i> <b>Alamat:</b> <span id="modalLocationAddress"></span>
                            </div>
                            <div class="modal-meta-item">
                                <i class="fas fa-book"></i> <b>Total Koleksi:</b> <span id="modalLocationNaskah" class="badge bg-primary px-2 py-1"></span> Naskah Terdaftar
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-align-left me-2 text-secondary"></i>Deskripsi</h6>
                        <p id="modalLocationDesc" class="text-muted small row-deck style-text" style="line-height: 1.6; text-align: justify;"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between p-3 border-top-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px; font-size: 0.85rem; font-weight:600;">
                    <i class="fas fa-arrow-left me-2"></i> Tutup Kembali
                </button>
                <a href="" id="modalLocationSearchBtn" class="btn btn-primary px-4 shadow-sm" style="border-radius: 8px; font-size: 0.85rem; font-weight:600;">
                    Buka Lembar Koleksi <i class="fas fa-chevron-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>

<script>
    $(document).ready(function() {
        // Klik pada Card
        $('.location-grid-card').on('click', function(e) {
            // Mencegah error jika klik terjadi di dalam elemen lain yang mungkin punya event sendiri
            // Kita cari tombol .btn-open-location-modal di dalam card ini
            $(this).find('.btn-open-location-modal').trigger('click');
        });

        // Event handler untuk tombol (fungsi asli Anda)
        $('.btn-open-location-modal').on('click', function(e) {
            // Mencegah event bubbling agar tidak trigger dua kali
            e.stopPropagation();

            var nama = $(this).data('nama');
            var foto = $(this).data('foto');
            var negara = $(this).data('negara');
            var alamat = $(this).data('alamat');
            var naskah = $(this).data('naskah');
            var deskripsi = $(this).data('deskripsi');
            var searchUrl = $(this).data('search-url');

            $('#modalLocationName').text(nama);
            $('#modalLocationImg').attr('src', foto);
            $('#modalLocationCountry').text(negara);
            $('#modalLocationAddress').text(alamat);
            $('#modalLocationNaskah').text(naskah);
            $('#modalLocationSearchBtn').attr('href', searchUrl);

            var formattedDesc = deskripsi.replace(/\n/g, "<br />");
            $('#modalLocationDesc').html(formattedDesc);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailLokasi')).show();
        });
    });
</script>
</body>
</html>