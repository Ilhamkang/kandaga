<?php
require_once __DIR__ . '/contribution-data.php';
$activityYears = kandagaContributionYears($conn);
$activityYear = filter_var($_GET['activity_year'] ?? date('Y'), FILTER_VALIDATE_INT);
if (!in_array($activityYear, $activityYears, true)) $activityYear = (int)date('Y');
$activity = kandagaContributionData($conn, $activityYear);
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
?>
<section class="card border-0 shadow-sm p-4 mb-4 <?= !empty($activityPublicOnly)?'activity-public':'' ?>" aria-labelledby="activityTitle">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h3 class="h5 fw-bold" id="activityTitle">Aktivitas kontribusi katalog</h3><p class="small text-muted mb-2"><span id="activityTotal"><?= number_format($activity['total']) ?></span> aktivitas input / pembaruan pada <?= $activityYear ?>.</p></div>
        <form method="get" action="<?= htmlspecialchars($activityFormAction??BASE_URL.'/pages/kontributor.php',ENT_QUOTES,'UTF-8') ?>" class="d-flex align-items-center gap-2 flex-wrap">
            <?php if(isset($activityFormPage)): ?><input type="hidden" name="page" value="<?= htmlspecialchars($activityFormPage,ENT_QUOTES,'UTF-8') ?>"><?php endif; ?>
            <label for="activityYear" class="me-2">Tahun</label>
            <select id="activityYear" name="activity_year" class="form-select form-select-sm me-2">
                <?php foreach ($activityYears as $year): ?><option value="<?= $year ?>" <?= $year === $activityYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?>
            </select><button type="submit" class="btn btn-sm btn-outline-primary">Tampilkan</button>
        </form>
    </div>
    <div class="activity-scroll"><div class="activity-year-grid"><div class="activity-months" aria-hidden="true"><?php foreach ($monthNames as $monthName): ?><span><?= $monthName ?></span><?php endforeach; ?></div>
        <div class="activity-calendar" aria-label="Kalender aktivitas kontribusi <?= $activityYear ?>">
        <?php
        $first = new DateTimeImmutable("$activityYear-01-01");
        for ($pad = 0; $pad < (int)$first->format('w'); $pad++) echo '<span class="activity-pad" aria-hidden="true"></span>';
        for ($day = $first; (int)$day->format('Y') === $activityYear; $day = $day->modify('+1 day')):
            $key = $day->format('Y-m-d'); $entry = $activity['days'][$key] ?? ['total' => 0, 'people' => []];
            $count = $entry['total']; $level = $count === 0 ? 0 : ($count <= 2 ? 1 : ($count <= 5 ? 2 : ($count <= 10 ? 3 : 4)));
            $tooltip = $day->format('d-m-Y') . ': ' . $count . ' aktivitas';
            foreach ($entry['people'] as $person) $tooltip .= "\n" . $person['name'] . ': ' . $person['input'] . ' input, ' . $person['edit'] . ' pembaruan';
        ?>
            <span class="activity-day activity-level-<?= $level ?>" tabindex="0" role="img" title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>" data-activity-day="<?= $key ?>" data-activity-tooltip="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"></span>
        <?php endfor; ?>
        </div>
    </div></div>
    <p class="small text-muted mt-2 mb-3">Sedikit <span class="activity-key activity-level-0"></span><span class="activity-key activity-level-1"></span><span class="activity-key activity-level-2"></span><span class="activity-key activity-level-3"></span><span class="activity-key activity-level-4"></span> Banyak — arahkan kursor atau fokus pada kotak untuk melihat kontributor.</p>
    <?php if(empty($activityPublicOnly)): ?>
    <h4 class="h6 fw-bold">Aktivitas bulanan per kontributor</h4>
    <div style="position:relative;height:300px"><canvas id="contributorMonthlyChart" aria-label="Grafik aktivitas bulanan per kontributor" role="img"></canvas></div>
    <details class="mt-3"><summary>Lihat rincian bulanan</summary><div class="table-responsive"><table class="table table-sm mt-2"><thead><tr><th>Kontributor</th><th>Bulan</th><th>Input</th><th>Pembaruan</th></tr></thead><tbody id="activityMonthlyRows">
    <?php foreach ($activity['contributors'] as $person): foreach ($monthNames as $i => $monthName): if ($person['input'][$i] + $person['edit'][$i] === 0) continue; ?>
        <tr><td><?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= $monthName ?></td><td><?= $person['input'][$i] ?></td><td><?= $person['edit'][$i] ?></td></tr>
    <?php endforeach; endforeach; ?>
    <?php if (!$activity['total']): ?><tr><td colspan="4" class="text-muted">Belum ada aktivitas tercatat pada tahun ini.</td></tr><?php endif; ?>
    </tbody></table></div></details>
    <?php endif; ?>
    <p class="small text-muted mt-3 mb-0">Aktivitas mencakup input dan pembaruan katalog yang tercatat. Satu penyimpanan dihitung satu aktivitas; riwayat lama mungkin belum lengkap. Data diperbarui otomatis setiap 60 detik saat halaman aktif.</p>
</section>
<script type="application/json" id="contributorActivityData" data-base="<?= htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8') ?>"><?= json_encode(['year' => $activityYear] + $activity, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= BASE_URL ?>/assets/js/contributor-activity.js?v=3.1.5" defer></script>
