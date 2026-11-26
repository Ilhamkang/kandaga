<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * Halaman Statistik Katalog
 * Lokasi: /pages/statistik.php
 */
require_once __DIR__ . '/../config.php';

// Validasi BASE_URL
if (!defined('BASE_URL')) { define('BASE_URL', '/kandaga'); }

// Pastikan koneksi database tersedia
require_once __DIR__ . '/../config/database.php';

/**
 * Fungsi Pengumpul Data Statistik Dinamis Berbasis Tabel Relasi
 */
function getStatistikRelasional($conn, $tipe) {
    switch ($tipe) {
        case 'negara':
            $sql = "SELECT l.negara AS nama, COUNT(k.id) AS jumlah
                    FROM katalog k
                    JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi
                    WHERE l.negara != '' AND l.negara IS NOT NULL
                    GROUP BY l.negara
                    ORDER BY jumlah DESC";
            break;
        case 'id_lokasi':
            $sql = "SELECT l.nama_tempat AS nama, COUNT(k.id) AS jumlah
                    FROM katalog k
                    JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi
                    GROUP BY l.id_lokasi, l.nama_tempat
                    ORDER BY jumlah DESC";
            break;
        case 'aksara':
            $sql = "SELECT m.nama_aksara AS nama, COUNT(DISTINCT j.id_naskah) AS jumlah
                    FROM tb_aksara m
                    JOIN naskah_aksara j ON m.id_aksara = j.id_aksara
                    JOIN katalog kr ON kr.id = j.id_naskah GROUP BY m.id_aksara, m.nama_aksara
                    ORDER BY jumlah DESC";
            break;
        case 'bahasa':
            $sql = "SELECT m.nama_bahasa AS nama, COUNT(DISTINCT j.id_naskah) AS jumlah
                    FROM tb_bahasa m
                    JOIN naskah_bahasa j ON m.id_bahasa = j.id_bahasa
                    JOIN katalog kr ON kr.id = j.id_naskah GROUP BY m.id_bahasa, m.nama_bahasa
                    ORDER BY jumlah DESC";
            break;
        case 'alas_tulis':
            $sql = "SELECT m.nama_alas_tulis AS nama, COUNT(DISTINCT j.id_naskah) AS jumlah
                    FROM tb_alas_tulis m
                    JOIN naskah_alas_tulis j ON m.id_alas_tulis = j.id_alas_tulis
                    JOIN katalog kr ON kr.id = j.id_naskah GROUP BY m.id_alas_tulis, m.nama_alas_tulis
                    ORDER BY jumlah DESC";
            break;
        case 'view':
            $sql = "SELECT k.judul AS nama, COUNT(nv.id) AS jumlah
                    FROM naskah_views nv
                    JOIN katalog k ON nv.id_naskah = k.id
                    GROUP BY k.id, k.judul
                    ORDER BY jumlah DESC
                    LIMIT 10";
            break;
        default:
            return false;
    }
    return mysqli_query($conn, $sql);
}

$daftar_statistik = [
    ['id' => 'Negara',    'judul' => 'Sebaran Negara',    'tipe' => 'negara',     'ikon' => 'fa-globe-asia'],
    ['id' => 'Lokasi',    'judul' => 'Tempat Penyimpan',  'tipe' => 'id_lokasi',  'ikon' => 'fa-university'],
    ['id' => 'Aksara',    'judul' => 'Rincian Aksara',    'tipe' => 'aksara',     'ikon' => 'fa-pen-fancy'],
    ['id' => 'Bahasa',    'judul' => 'Rincian Bahasa',    'tipe' => 'bahasa',     'ikon' => 'fa-language'],
    ['id' => 'Bahan',     'judul' => 'Bahan / Alas Tulis', 'tipe' => 'alas_tulis', 'ikon' => 'fa-scroll'],
    ['id' => 'View',      'judul' => 'Naskah Terpopuler',  'tipe' => 'view',       'ikon' => 'fa-eye'],
];

// A bibliographic relation is the explicit, auditable proxy for research status.
$researchStats = ['total' => 0, 'researched' => 0, 'unresearched' => 0, 'updated_at' => null];
try {
    // EXISTS avoids duplicate bibliography rows and normalises legacy values.
    $r = $conn->query("SELECT COUNT(*) AS total,
        SUM(CASE WHEN EXISTS(SELECT 1 FROM naskah_bibliografi nb
                   JOIN tb_bibliografi b ON b.id=nb.id_bibliografi
                   WHERE nb.id_naskah=k.id
                     AND LOWER(TRIM(b.kategori)) IN ('referensi primer','referensi sekunder'))
                 THEN 1 ELSE 0 END) AS researched,
        MAX(GREATEST(COALESCE(k.tanggal_input,'1000-01-01'),COALESCE(k.tanggal_update,'1000-01-01'))) AS updated_at
        FROM katalog k")->fetch_assoc();
    $researchStats['total'] = (int)($r['total'] ?? 0);
    $researchStats['researched'] = (int)($r['researched'] ?? 0);
    $researchStats['unresearched'] = max(0, $researchStats['total'] - $researchStats['researched']);
    $researchStats['updated_at'] = $r['updated_at'] ?? null;
} catch (Throwable $e) { /* Older installations can still render catalog statistics. */ }
$researchTotal = $researchStats['total'];
$researchPercent = $researchTotal ? round($researchStats['researched'] / $researchTotal * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Statistik - KANDAGA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/fontawesome/css/all.min.css">
    <script src="<?= BASE_URL; ?>/assets/js/chart.js"></script>

    <style>
        body { background-color: #f8fafc; color: #334155; font-family: 'Inter', sans-serif; }
        .page-header { background: white; padding: 60px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 40px; }
        .stat-card { border: none; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: white; overflow: hidden; height: 100%; position: relative; }
        .stat-card-header { background: #1e293b; color: white; padding: 1.2rem; display: flex; align-items: center; justify-content: space-between; }
        .stat-card-header-left { display: flex; align-items: center; gap: 10px; }
        .stat-card-header i.main-icon { color: #3b82f6; }
        .btn-zoom-stat { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: #fff; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; transition: all 0.2s ease; cursor: pointer; }
        .btn-zoom-stat:hover { background: #2563eb; color: white; border-color: #2563eb; transform: scale(1.05); }
        .stat-card .nav-tabs { padding: 8px 15px 0 15px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
        .stat-card .nav-link { border: none; color: #64748b; font-size: 0.85rem; font-weight: 600; padding: 8px 16px; border-radius: 8px 8px 0 0; }
        .stat-card .nav-link.active { background: white; color: #2563eb; border: none; }
        .chart-container { padding: 20px; position: relative; height: 260px; }
        .table-container { max-height: 312px; overflow-y: auto; }
        .table thead th { position: sticky; top: 0; background: #f8fafc; font-size: 0.7rem; text-transform: uppercase; color: #64748b; padding: 12px 15px; z-index: 10; }
        .badge-count { background: #eff6ff; color: #2563eb; font-weight: 700; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; }
        .modal-chart-container { position: relative; height: 420px; width: 100%; padding: 10px; }
        .modal-table-container { max-height: 420px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px; }
        .traffic-stat-row{align-items:stretch}.traffic-stat-row>[class*="col-"]{display:flex}.traffic-stat-row .public-top-searches,.traffic-stat-row .popular-manuscripts{min-height:360px;height:100%;width:100%;display:flex;flex-direction:column}.traffic-stat-row .public-top-searches .table-responsive,.traffic-stat-row .popular-manuscripts .table-responsive{flex:1;overflow:auto}
        .research-status-card{border:1px solid #e2e8f0;border-radius:20px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.06);padding:1.25rem 1.35rem}
        .research-status-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}
        .research-status-title{display:flex;align-items:center;gap:.7rem;min-width:0}.research-status-title i{display:grid;place-items:center;width:2.25rem;height:2.25rem;border-radius:12px;background:#eff6ff;color:#2563eb;flex:0 0 2.25rem}.research-status-title h3{font-size:1rem;line-height:1.35}
        .research-status-total{font-size:1.75rem;font-weight:800;line-height:1;color:#0f172a}.research-status-total small{display:block;margin-top:.35rem;font-size:.72rem;font-weight:600;color:#64748b}
        .research-progress{display:flex;overflow:hidden;height:12px;margin:1.1rem 0 .75rem;background:#e2e8f0;border-radius:999px}.research-progress-related{background:#2563eb}.research-progress-unrelated{background:#cbd5e1}
        .research-status-legend{display:flex;flex-wrap:wrap;gap:.8rem 1.2rem;color:#475569;font-size:.82rem}.research-status-legend span{display:inline-flex;align-items:center;gap:.38rem}.research-status-legend i{width:.62rem;height:.62rem;border-radius:50%;display:inline-block}.research-status-legend .related-dot{background:#2563eb}.research-status-legend .unrelated-dot{background:#cbd5e1}
        .research-status-note{color:#64748b;font-size:.78rem;line-height:1.5}.research-status-detail{border:1px solid #cbd5e1;color:#2563eb;background:#fff;border-radius:9px;font-size:.78rem;font-weight:700;white-space:nowrap}.research-status-detail:hover,.research-status-detail:focus{background:#eff6ff;color:#1d4ed8;border-color:#93c5fd}
        .research-detail-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid #e2e8f0}.research-detail-row:last-child{border-bottom:0}.research-detail-value{font-size:1.25rem;font-weight:800;color:#0f172a}.research-detail-label{color:#475569;font-size:.88rem}.research-detail-bar{height:8px;margin-top:.45rem;background:#e2e8f0;border-radius:99px;overflow:hidden}.research-detail-bar span{display:block;height:100%;border-radius:inherit;background:#2563eb}.research-detail-bar .muted{background:#94a3b8}
        @media (max-width:575.98px){.research-status-card{padding:1rem}.research-status-head{gap:.65rem}.research-status-title{align-items:flex-start}.research-status-title h3{font-size:.95rem}.research-status-detail{padding:.35rem .55rem}.research-status-total{font-size:1.55rem}.research-status-legend{gap:.55rem .9rem}}
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>

<body class="kandaga-module kandaga-special-page kandaga-statistics-page">
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header text-center">
    <div class="container">
        <h2 class="fw-bold">Statistik KANDAGA</h2>
        <p class="text-muted">Data internal katalog dan pemanfaatan katalog dalam dua klaster.</p>
    </div>
</div>

<div class="container pb-5">
    <p class="small text-muted mb-2"><i class="fas fa-clock me-1"></i> Pembaruan katalog terakhir: <strong><?= $researchStats['updated_at'] ? htmlspecialchars(date('d-m-Y H:i', strtotime($researchStats['updated_at'])), ENT_QUOTES, 'UTF-8') : 'Belum tersedia' ?></strong></p>
    <div class="research-status-card mb-4">
        <div class="research-status-head">
            <div class="research-status-title">
                <i class="fas fa-book-reader" aria-hidden="true"></i>
                <div><h3 class="mb-1 fw-bold">Status relasi naskah dengan penelitian</h3><p class="research-status-note mb-0">Relasi referensi primer dan sekunder yang tercatat dalam katalog.</p></div>
            </div>
            <button type="button" class="btn research-status-detail" data-bs-toggle="modal" data-bs-target="#researchStatusDetailModal"><i class="fas fa-list-ul me-1" aria-hidden="true"></i>Rincian</button>
        </div>
        <div class="d-flex align-items-end justify-content-between mt-3"><div class="research-status-total"><?= number_format($researchTotal) ?><small>Total naskah terukur</small></div><span class="badge-count"><?= number_format($researchPercent, 1) ?>% terkait</span></div>
        <div class="research-progress" role="img" aria-label="<?= number_format($researchPercent, 1) ?> persen naskah memiliki referensi bibliografi"><span class="research-progress-related" style="width: <?= htmlspecialchars((string)$researchPercent, ENT_QUOTES, 'UTF-8') ?>%"></span><span class="research-progress-unrelated" style="width: <?= htmlspecialchars((string)(100 - $researchPercent), ENT_QUOTES, 'UTF-8') ?>%"></span></div>
        <div class="research-status-legend"><span><i class="related-dot" aria-hidden="true"></i><?= number_format($researchStats['researched']) ?> memiliki referensi</span><span><i class="unrelated-dot" aria-hidden="true"></i><?= number_format($researchStats['unresearched']) ?> belum memiliki referensi</span></div>
        <p class="research-status-note mt-3 mb-0">Indikator ini menunjukkan keberadaan relasi bibliografi, bukan penilaian akademik final.</p>
    </div>
    <p class="small text-muted mb-4">Hanya relasi sumber primer dan sekunder yang dihitung; relasi katalog tidak termasuk.</p>
    <?php foreach (['catalog' => array_filter($daftar_statistik, fn($item) => $item['tipe'] !== 'view'), 'traffic' => array_filter($daftar_statistik, fn($item) => $item['tipe'] === 'view')] as $cluster => $clusterItems): ?>
    <?php if ($cluster === 'catalog'): ?><h3 class="fw-bold">Data internal katalog</h3><p class="text-muted">Sebaran metadata naskah. Naskah dengan beberapa bahasa atau aksara dapat dihitung pada beberapa kategori.</p><?php else: ?><h3 class="fw-bold">Pemanfaatan katalog</h3><p class="text-muted">Naskah dan kata kunci yang paling sering digunakan pembaca.</p><?php endif; ?>
    <div class="row <?= $cluster === 'traffic' ? 'traffic-stat-row' : '' ?>">
        <?php if($cluster==='traffic'){echo '<div class="col-lg-6 mb-4">';include __DIR__.'/../includes/popular-manuscripts.php';echo '</div><div class="col-lg-6 mb-4">';include __DIR__.'/../includes/top-searches.php';echo '</div>';$clusterItems=[];} ?>
        <?php foreach ($clusterItems as $item): ?>
            <?php
                $labels = [];
                $values = [];
                $table_rows_html = '';

                $res = getStatistikRelasional($conn, $item['tipe']);
                if ($res && mysqli_num_rows($res) > 0) {
                    while ($data = mysqli_fetch_assoc($res)) {
                        $nama_item = !empty($data['nama']) ? $data['nama'] : 'Tidak Terdefinisi';
                        $labels[] = $nama_item;
                        $values[] = (int)$data['jumlah'];

                        $table_rows_html .= '<tr>
                            <td class="ps-4 py-2 small fw-bold">'.htmlspecialchars($nama_item).'</td>
                            <td class="text-end pe-4 py-2"><span class="badge-count">'.number_format($data['jumlah']).'</span></td>
                        </tr>';
                    }
                } else {
                    $table_rows_html = '<tr><td colspan="2" class="text-center text-muted small py-4">Data tidak ditemukan</td></tr>';
                }
            ?>

            <div class="col-lg-6 col-md-12 mb-4">
                <div class="stat-card shadow-sm">
                    <div class="stat-card-header">
                        <div class="stat-card-header-left">
                            <i id="html-icon-<?= $item['id'] ?>" class="fas <?= $item['ikon'] ?> main-icon"></i>
                            <h6 class="mb-0 fw-bold d-inline-block"><?= $item['judul'] ?></h6>
                        </div>
                        <button type="button" class="btn-zoom-stat visual-zoom-trigger"
                                data-judul="<?= htmlspecialchars($item['judul']) ?>"
                                data-ikon="html-icon-<?= $item['id'] ?>"
                                data-labels='<?= htmlspecialchars(json_encode($labels), ENT_QUOTES, 'UTF-8') ?>'
                                data-values='<?= json_encode($values) ?>'>
                            <i class="fas fa-expand-alt me-1"></i> Perbesar
                        </button>
                    </div>

                    <ul class="nav nav-tabs" id="tab<?= $item['id'] ?>" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pie-tab-<?= $item['id'] ?>" data-bs-toggle="tab" href="#pie-panel-<?= $item['id'] ?>" role="tab"><i class="fas fa-chart-pie me-1"></i> Pie</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="bar-tab-<?= $item['id'] ?>" data-bs-toggle="tab" href="#bar-panel-<?= $item['id'] ?>" role="tab"><i class="fas fa-chart-bar me-1"></i> Batang</a>
                        </li>
                    </ul>

                    <div class="row no-gutters">
                        <div class="col-md-6 border-right">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="pie-panel-<?= $item['id'] ?>" role="tabpanel">
                                    <div class="chart-container">
                                        <canvas id="pie<?= $item['id'] ?>"></canvas>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="bar-panel-<?= $item['id'] ?>" role="tabpanel">
                                    <div class="chart-container">
                                        <canvas id="bar<?= $item['id'] ?>"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="table-container">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Item</th>
                                            <th class="text-end pe-4"><?= $item['tipe'] === 'view' ? 'Kunjungan' : 'Naskah' ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body-src-<?= $item['id'] ?>">
                                        <?= $table_rows_html ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var chartLabels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
                    var chartDataValues = <?= json_encode($values) ?>;
                    var colorPalette = ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#1e293b', '#334155', '#475569', '#64748b', '#94a3b8', '#059669', '#10b981', '#34d399', '#6ee7b7', '#a7f3d0'];

                    var ctxPie = document.getElementById('pie<?= $item['id'] ?>');
                    if (ctxPie) {
                        new Chart(ctxPie, {
                            type: 'pie',
                            data: {
                                labels: chartLabels,
                                datasets: [{ data: chartDataValues, backgroundColor: colorPalette, borderWidth: 2, borderColor: '#ffffff' }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                        });
                    }

                    var ctxBar = document.getElementById('bar<?= $item['id'] ?>');
                    if (ctxBar) {
                        new Chart(ctxBar, {
                            type: 'bar',
                            data: {
                                labels: chartLabels,
                                datasets: [{ data: chartDataValues, backgroundColor: colorPalette, borderWidth: 0, borderRadius: 4 }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0, font: { size: 10 } } },
                                    y: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, callback: function(value) { var label = this.getLabelForValue(value); return label.length > 15 ? label.substring(0, 15) + '...' : label; } } }
                                },
                                plugins: { legend: { display: false } }
                            }
                        });
                    }
                });
            </script>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="researchStatusDetailModal" tabindex="-1" role="dialog" aria-labelledby="researchStatusDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header bg-primary">
                <h5 class="modal-title fw-bold" id="researchStatusDetailTitle"><i class="fas fa-book-reader me-2" aria-hidden="true"></i>Rincian relasi penelitian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Ringkasan ini menghitung relasi sumber primer dan sekunder yang tercatat pada naskah. Relasi katalog tidak termasuk.</p>
                <div class="research-detail-row"><div class="flex-grow-1"><div class="research-detail-label">Memiliki referensi bibliografi</div><div class="research-detail-bar"><span style="width: <?= htmlspecialchars((string)$researchPercent, ENT_QUOTES, 'UTF-8') ?>%"></span></div></div><div class="text-end"><div class="research-detail-value text-primary"><?= number_format($researchStats['researched']) ?></div><small class="text-muted"><?= number_format($researchPercent, 1) ?>%</small></div></div>
                <div class="research-detail-row"><div class="flex-grow-1"><div class="research-detail-label">Belum memiliki referensi bibliografi</div><div class="research-detail-bar"><span class="muted" style="width: <?= htmlspecialchars((string)(100 - $researchPercent), ENT_QUOTES, 'UTF-8') ?>%"></span></div></div><div class="text-end"><div class="research-detail-value"><?= number_format($researchStats['unresearched']) ?></div><small class="text-muted"><?= number_format(100 - $researchPercent, 1) ?>%</small></div></div>
                <p class="small text-muted mt-3 mb-0"><i class="fas fa-info-circle me-1" aria-hidden="true"></i>Terakhir diperbarui: <?= $researchStats['updated_at'] ? htmlspecialchars(date('d-m-Y H:i', strtotime($researchStats['updated_at'])), ENT_QUOTES, 'UTF-8') : 'Belum tersedia' ?></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPerbesarStatistik" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 24px; border: none; overflow: hidden;">
            <div class="modal-header px-4 py-3 align-items-center" style="background: #1e293b; color: white; border-bottom: none;">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="modalTitleText"></h5>
                <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal" aria-label="Close" style="outline: none;"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <ul class="nav nav-pills mb-3 justify-content-center" id="modalTabChart" role="tablist">
                    <li class="nav-item mx-1">
                        <a class="nav-link active px-4 py-2" id="modal-pie-tab" data-bs-toggle="pill" href="#modal-pie-panel" role="tab"><i class="fas fa-chart-pie me-2"></i>Diagram Lingkaran</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link px-4 py-2" id="modal-bar-tab" data-bs-toggle="pill" href="#modal-bar-panel" role="tab"><i class="fas fa-chart-bar me-2"></i>Diagram Batang</a>
                    </li>
                </ul>
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-4 mb-lg-0">
                        <div class="card p-3 border-0 shadow-sm" style="border-radius: 16px; background: white;">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="modal-pie-panel" role="tabpanel">
                                    <div class="modal-chart-container">
                                        <canvas id="modalPieCanvas"></canvas>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="modal-bar-panel" role="tabpanel">
                                    <div class="modal-chart-container">
                                        <canvas id="modalBarCanvas"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="modal-table-container bg-white shadow-sm">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3">Kategori Metadata</th>
                                        <th class="text-end pe-4 py-3">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody id="modalTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL; ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL; ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        var modalPieChartInstance = null;
        var modalBarChartInstance = null;
        var colorPalette = ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#1e293b', '#334155', '#475569', '#64748b', '#94a3b8', '#059669', '#10b981', '#34d399', '#6ee7b7', '#a7f3d0'];

        $('.visual-zoom-trigger').on('click', function() {
            var judul = $(this).data('judul');
            var iconId = $(this).data('ikon');
            var iconClass = $('#' + iconId).attr('class');
            var labels = $(this).data('labels');
            var values = $(this).data('values');
            var originalCardId = iconId.replace('html-icon-', '');
            var tableContent = $('#table-body-src-' + originalCardId).html();

            $('#modalTitleText').html('<i class="' + iconClass + ' me-3 text-primary"></i> Analisis Data Rinci: ' + judul);
            $('#modalTableBody').html(tableContent);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPerbesarStatistik')).show();

            setTimeout(function() {
                if (modalPieChartInstance) modalPieChartInstance.destroy();
                if (modalBarChartInstance) modalBarChartInstance.destroy();
                var ctxModalPie = document.getElementById('modalPieCanvas').getContext('2d');
                modalPieChartInstance = new Chart(ctxModalPie, {
                    type: 'pie',
                    data: { labels: labels, datasets: [{ data: values, backgroundColor: colorPalette, borderWidth: 2, borderColor: '#ffffff' }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'right' } } }
                });
                var ctxModalBar = document.getElementById('modalBarCanvas').getContext('2d');
                modalBarChartInstance = new Chart(ctxModalBar, {
                    type: 'bar',
                    data: { labels: labels, datasets: [{ data: values, backgroundColor: colorPalette, borderRadius: 6 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true }, y: { grid: { display: false } } }, plugins: { legend: { display: false } } }
                });
            }, 250);
        });
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
