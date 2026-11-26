<?php
require_once __DIR__ . '/../../config/analytics.php';
$ga4Settings = kandagaAnalyticsSettings();
$ga4Id = (string)($ga4Settings['google_measurement_id'] ?? '');
$ga4Enabled = $ga4Id !== '';
$ga4Masked = $ga4Enabled ? substr($ga4Id, 0, 3) . str_repeat('•', max(0, strlen($ga4Id) - 5)) . substr($ga4Id, -2) : 'Belum dikonfigurasi';
?>
<section class="card border-left-info shadow-sm mb-4" aria-labelledby="ga4StatusTitle">
    <div class="card-body py-3">
        <div class="d-flex align-items-start justify-content-between flex-wrap">
            <div>
                <h3 id="ga4StatusTitle" class="h6 mb-1"><i class="fa-solid fa-chart-line mr-2 text-info" aria-hidden="true"></i>Google Analytics 4</h3>
                <p class="small text-muted mb-0">Klaster analitik eksternal; tidak digabungkan ke tabel <code>website_visits</code>.</p>
            </div>
            <span class="badge <?= $ga4Enabled ? 'badge-success' : 'badge-secondary' ?> mt-1"><?= $ga4Enabled ? 'Aktif' : 'Opsional' ?></span>
        </div>
        <div class="small mt-2"><strong>Measurement ID:</strong> <?= htmlspecialchars($ga4Masked, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php if (!$ga4Enabled): ?><div class="small text-muted mt-1">Isi <code>config/analytics.local.php</code> atau variabel <code>KANDAGA_GA_MEASUREMENT_ID</code> untuk mengaktifkan tag GA4.</div><?php endif; ?>
    </div>
</section>
