<?php require_once __DIR__.'/catalog-analytics.php'; $trafficCounts=kandagaCompactCounters($conn); ?>
<section class="mb-4" aria-labelledby="trafficHeading"><h3 class="fw-bold" id="trafficHeading">Statistik akses katalog</h3>
<?php if($trafficCounts===null): ?><p class="alert alert-info">Statistik kunjungan website belum diaktifkan. Popularitas naskah tetap tersedia di bawah.</p><?php else: ?>
<div class="row"><?php foreach(['daily'=>'Hari ini','weekly'=>'7 hari terakhir','monthly'=>'Bulan ini','total'=>'Total'] as $key=>$label): ?><div class="col-lg-3 col-6 mb-3"><div class="card border-0 shadow-sm p-3"><span class="text-muted"><?= $label ?></span><strong class="h3 mb-0"><?= number_format((int)$trafficCounts[$key]) ?></strong></div></div><?php endforeach; ?></div>
<p class="small text-muted">Ringkasan agregat halaman publik. Mingguan mencakup hari ini dan enam hari sebelumnya; bulanan mengikuti bulan kalender Asia/Jakarta. Tidak ada IP, user-agent, perangkat, geolokasi, atau daftar pengunjung online yang disimpan.</p>
<?php endif; ?></section>
