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
<style>
    .statistics-dashboard { --stats-blue:#315c9b; --stats-ink:#233044; --stats-muted:#718096; }
    .statistics-dashboard .stats-header { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .statistics-dashboard .stats-header h3 { color:var(--stats-ink); margin:0; font-weight:700; }
    .statistics-dashboard .stats-header p { color:var(--stats-muted); margin:.25rem 0 0; font-size:.86rem; }
    .statistics-dashboard .stats-period { min-width:130px; }
    .statistics-dashboard .stats-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
    .statistics-dashboard .stats-card { background:#fff; border:1px solid #e4eaf2; border-radius:12px; padding:.8rem .9rem; min-height:94px; box-shadow:0 2px 8px rgba(35,48,68,.04); }
    .statistics-dashboard .stats-card .stats-label { display:block; color:var(--stats-muted); font-size:.76rem; line-height:1.2; }
    .statistics-dashboard .stats-card strong { display:block; color:var(--stats-ink); font-size:1.45rem; line-height:1.35; margin-top:.25rem; }
    .statistics-dashboard .stats-card .stats-icon { color:var(--stats-blue); font-size:.9rem; margin-right:.3rem; }
    .statistics-dashboard .stats-card button { appearance:none; border:0; background:transparent; text-align:left; padding:0; width:100%; cursor:pointer; }
    .statistics-dashboard .stats-card button:focus-visible,.statistics-dashboard .stats-link:focus-visible { outline:3px solid rgba(49,92,155,.25); outline-offset:3px; border-radius:7px; }
    .statistics-dashboard .stats-card button:hover strong { color:var(--stats-blue); }
    .statistics-dashboard .stats-panel { background:#fff; border:1px solid #e4eaf2; border-radius:12px; box-shadow:0 2px 8px rgba(35,48,68,.04); padding:1rem; margin-bottom:1rem; }
    .statistics-dashboard .stats-panel h4 { color:var(--stats-ink); font-size:1rem; margin:0; }
    .statistics-dashboard .stats-panel-heading { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.8rem; }
    .statistics-dashboard .stats-chart { display:flex; align-items:flex-end; gap:4px; height:210px; padding:.5rem .1rem 1.5rem; border-bottom:1px solid #e8edf4; }
    .statistics-dashboard .stats-bar-wrap { flex:1; height:100%; display:flex; align-items:flex-end; min-width:3px; position:relative; }
    .statistics-dashboard .stats-bar { width:100%; min-height:3px; border-radius:5px 5px 0 0; background:linear-gradient(180deg,#4c82c5,#315c9b); transition:filter .15s ease; }
    .statistics-dashboard .stats-bar:hover { filter:brightness(1.15); }
    .statistics-dashboard .stats-bar-label { position:absolute; bottom:-1.35rem; left:50%; transform:translateX(-50%); color:#8290a3; font-size:.64rem; white-space:nowrap; }
    .statistics-dashboard .stats-caption { color:var(--stats-muted); font-size:.78rem; margin:.7rem 0 0; }
    .statistics-dashboard .catalog-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.75rem; }
    .statistics-dashboard .stats-link-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
    .statistics-dashboard .stats-link { display:flex; align-items:center; justify-content:space-between; gap:.75rem; border:1px solid #e4eaf2; border-radius:10px; background:#fff; padding:.8rem .9rem; color:var(--stats-ink); text-decoration:none; cursor:pointer; }
    .statistics-dashboard .stats-link:hover { border-color:#b7c9e2; color:var(--stats-blue); text-decoration:none; }
    .statistics-dashboard .stats-link small { display:block; color:var(--stats-muted); }
    .statistics-dashboard .stats-link i { color:var(--stats-blue); }
    @media (max-width:1100px) { .statistics-dashboard .stats-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .statistics-dashboard .catalog-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width:700px) { .statistics-dashboard .stats-header { align-items:flex-start; flex-direction:column; } .statistics-dashboard .stats-grid,.statistics-dashboard .catalog-grid,.statistics-dashboard .stats-link-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>

<div id="statisticsDashboard" class="statistics-dashboard container-fluid p-3" data-detail-endpoint="<?= htmlspecialchars(BASE_URL . '/admin/actions/statistics-detail.php', ENT_QUOTES, 'UTF-8') ?>">
    <div class="stats-header">
        <div><h3>Statistik Kandaga</h3><p>Ringkasan katalog dan pemanfaatan halaman yang ringan; rincian dimuat hanya saat diperlukan.</p></div>
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

<div class="modal fade" id="statisticsDetailModal" tabindex="-1" role="dialog" aria-labelledby="statisticsDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="statisticsDetailTitle">Rincian statistik</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" id="statisticsDetailBody"><p class="text-muted mb-0">Pilih rincian statistik.</p></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button></div></div></div>
</div>
<script src="<?= htmlspecialchars(BASE_URL . '/admin/assets/js/statistics-dashboard.js?v=3.1.54', ENT_QUOTES, 'UTF-8') ?>" defer></script>
