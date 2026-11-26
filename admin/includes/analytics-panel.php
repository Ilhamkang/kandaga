<?php
/**
 * Deprecated compatibility include.
 * The active dashboard is admin/statistik.php; this fragment deliberately
 * avoids the former raw visit, IP, geo, device, and presence queries.
 */
require_once __DIR__ . '/../../includes/catalog-analytics.php';
$trafficBadge = isset($conn) ? kandagaCompactCounters($conn) : null;
?>
<section id="analyticsPanel" class="mt-4" aria-labelledby="analyticsPanelTitle">
    <h3 id="analyticsPanelTitle" class="h5 fw-bold">Statistik akses katalog</h3>
    <p class="small text-muted mb-0">Panel rinci trafik mentah telah dinonaktifkan. Gunakan ringkasan agregat dan statistik katalog pada halaman Statistik Kandaga. Google Analytics tetap berjalan sebagai telemetri eksternal opsional.</p>
</section>
