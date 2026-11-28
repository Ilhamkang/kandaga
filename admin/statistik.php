<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
require_once __DIR__ . '/../includes/catalog-analytics.php';
require_once __DIR__ . '/../includes/catalog-summary.php';

$summary = kandagaCatalogSummary($conn);
$traffic = kandagaCompactCounters($conn);
$period = is_string($_GET['analytics_period'] ?? null) ? $_GET['analytics_period'] : '30';
if (!in_array($period, ['7', '30', '90'], true)) $period = '30';
$daily = $traffic !== null ? kandagaCompactDailySeries($conn, $period) : [];
$maxDaily = 0;
foreach ($daily as $point) $maxDaily = max($maxDaily, (int)($point['views'] ?? 0));
$maxDaily = max(1, $maxDaily);

$catalogCards = [
    ['key' => 'naskah', 'label' => 'Total naskah', 'icon' => 'fa-book-open', 'detail' => 'naskah'],
    ['key' => 'lembaga', 'label' => 'Koleksi', 'icon' => 'fa-building-columns', 'detail' => 'lembaga'],
    ['key' => 'bahasa', 'label' => 'Bahasa', 'icon' => 'fa-language', 'detail' => 'bahasa'],
    ['key' => 'aksara', 'label' => 'Aksara', 'icon' => 'fa-pen-fancy', 'detail' => 'aksara'],
    ['key' => 'alas_tulis', 'label' => 'Alas tulis', 'icon' => 'fa-scroll', 'detail' => 'alas_tulis'],
];
?>

<div id="statisticsDashboard" class="statistics-dashboard<?= defined('KANDAGA_ADMIN_SHELL') ? '' : ' container-fluid p-3' ?>" data-detail-endpoint="<?= htmlspecialchars(BASE_URL . '/admin/actions/statistics-detail.php', ENT_QUOTES, 'UTF-8') ?>">
    <div class="stats-header admin-page-header">
        <div><h1>Statistik Kandaga</h1><p>Ringkasan katalog dan pemanfaatan halaman yang ringan; rincian dimuat hanya saat diperlukan.</p></div>
        <form method="get" action="<?= htmlspecialchars(BASE_URL . '/admin/index.php', ENT_QUOTES, 'UTF-8') ?>" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="hidden" name="page" value="statistik">
            <label for="statisticsPeriod" class="visually-hidden">Periode grafik</label>
            <select id="statisticsPeriod" name="analytics_period" class="form-select form-select-sm stats-period" onchange="this.form.submit()">
                <?php foreach (['7' => '7 hari', '30' => '30 hari', '90' => '90 hari'] as $value => $label): ?><option value="<?= $value ?>" <?= $period === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="stats-grid" aria-label="Ringkasan akses katalog">
        <?php if ($traffic !== null): ?>
            <?php foreach ([['daily','Hari ini'],['weekly','7 hari terakhir'],['monthly','Bulan ini'],['total','Total tampilan']] as [$key,$label]): ?>
                <div class="stats-card"><span class="stats-label"><i class="fa-solid fa-chart-line stats-icon" aria-hidden="true"></i><?= $label ?></span><strong><?= number_format((int)($traffic[$key] ?? 0)) ?></strong></div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info mb-0" style="grid-column:1/-1">Agregat akses belum tersedia; statistik katalog tetap dapat digunakan.</div>
        <?php endif; ?>
    </div>

    <?php if ($traffic !== null): ?>
        <section class="stats-panel" aria-labelledby="dailyStatsTitle">
            <div class="stats-panel-heading"><div><h4 id="dailyStatsTitle">Akses halaman harian</h4><p class="stats-caption mb-0">Agregat halaman publik; tanpa log request mentah atau identitas pengunjung.</p></div><span class="badge bg-light text-dark border"><?= $period ?> hari</span></div>
            <?php if ($daily): ?><div class="stats-chart" role="img" aria-label="Grafik kunjungan harian">
                <?php foreach ($daily as $index => $point): $views = (int)($point['views'] ?? 0); $height = max(2, (int)round(($views / $maxDaily) * 100)); $label = date('d M', strtotime((string)$point['day'])); ?>
                    <div class="stats-bar-wrap" title="<?= htmlspecialchars($label . ': ' . number_format($views) . ' kunjungan', ENT_QUOTES, 'UTF-8') ?>"><div class="stats-bar" style="height:<?= $height ?>%"></div><?php if ($index === 0 || $index === count($daily)-1 || $index % 5 === 0): ?><span class="stats-bar-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></div>
                <?php endforeach; ?></div>
            <?php else: ?><p class="text-muted mb-0">Belum ada data kunjungan pada periode ini.</p><?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="stats-panel" aria-labelledby="catalogStatsTitle">
        <div class="stats-panel-heading"><div><h4 id="catalogStatsTitle">Ringkasan katalog</h4><p class="stats-caption mb-0">Klik kartu untuk melihat rincian tanpa memuat ulang halaman.</p></div></div>
        <div class="catalog-grid">
            <?php foreach ($catalogCards as $card): ?><div class="stats-card"><button type="button" data-stat-detail="<?= htmlspecialchars($card['detail'], ENT_QUOTES, 'UTF-8') ?>" data-stat-label="<?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?>"><span class="stats-label"><i class="fa-solid <?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?> stats-icon" aria-hidden="true"></i><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= number_format((int)($summary[$card['key']] ?? 0)) ?></strong><small class="text-muted">Lihat rincian <span aria-hidden="true">→</span></small></button></div><?php endforeach; ?>
        </div>
    </section>

    <section class="stats-panel" aria-labelledby="trafficDetailsTitle">
        <div class="stats-panel-heading"><div><h4 id="trafficDetailsTitle">Rincian akses halaman</h4><p class="stats-caption mb-0">Rincian agregat halaman dan pencarian; IP, geo, perangkat, dan log request tidak ditampilkan.</p></div></div>
        <div class="stats-link-grid">
            <?php foreach ([['popular_pages','Halaman populer','Peringkat halaman publik','fa-window-maximize'],['searches','Pencarian teratas','Kata kunci dan frasa','fa-magnifying-glass']] as [$type,$label,$description,$icon]): ?><button type="button" class="stats-link" data-stat-detail="<?= $type ?>" data-stat-label="<?= $label ?>"><span><strong><?= $label ?></strong><small><?= $description ?></small></span><i class="fa-solid <?= $icon ?>" aria-hidden="true"></i></button><?php endforeach; ?>
        </div>
    </section>
</div>

<div class="modal fade" id="statisticsDetailModal" tabindex="-1" aria-labelledby="statisticsDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="statisticsDetailTitle">Rincian statistik</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" id="statisticsDetailBody"><p class="text-muted mb-0">Pilih rincian statistik.</p></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button></div></div></div>
</div>
<script src="<?= htmlspecialchars(BASE_URL . '/admin/assets/js/statistics-dashboard.js', ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>" defer></script>
