<?php
// Modul beranda: kontributor data.

$q_kontributor = mysqli_query($conn, "
    SELECT id, username, nama, foto, deskripsi
    FROM users
    WHERE role IN ('kontributor', 'admin')
    ORDER BY id ASC
");

$kontributor_list = [];
while ($row = mysqli_fetch_assoc($q_kontributor)) {
    $path_foto = "assets/images/user/" . (!empty($row['foto']) ? $row['foto'] : 'default-user.jpg');
    if (!file_exists($path_foto)) $path_foto = "assets/images/user/default-user.jpg";

    $row['foto_clean'] = $path_foto;
    $kontributor_list[] = $row;
}
$total_kontributor = count($kontributor_list);
$kontributor_chunks = array_chunk($kontributor_list, 4);
$kontributor_pages = count($kontributor_chunks);
?>

<link rel="stylesheet" href="assets/css/style-kontributor.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">

<div class="embedded-module embedded-kontributor" style="max-width: 960px;">
    <div class="kontributor-section position-relative">
        <div class="kontributor-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--kandaga-dark-blue);">Kontributor Data</h4>
                <p class="text-muted small mb-0">Mereka yang merawat dan memperkaya data naskah KANDAGA.</p>
            </div>

            <?php if ($kontributor_pages > 1): ?>
                <div class="kontributor-pager" role="group" aria-label="Navigasi halaman kontributor">
                    <button type="button" class="kontributor-pager-btn" data-kontributor-prev aria-label="Kontributor sebelumnya">
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <span class="kontributor-pager-indicator" data-kontributor-indicator aria-live="off">1 / <?= $kontributor_pages ?></span>
                    <button type="button" class="kontributor-pager-btn" data-kontributor-next aria-label="Kontributor berikutnya">
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_kontributor > 0): ?>
            <div id="kontributorGrid" class="kontributor-grid">
                <?php foreach ($kontributor_chunks as $index => $slide_items): ?>
                    <div class="kontributor-page <?= ($index === 0) ? 'is-active' : '' ?>"
                         data-kontributor-page="<?= $index + 1 ?>"
                         aria-hidden="<?= ($index === 0) ? 'false' : 'true' ?>">
                        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                            <?php foreach ($slide_items as $item): ?>
                                <div class="col">
                                    <a href="detail-kontributor.php?id=<?= (int)$item['id']; ?>" class="kontributor-card-link text-decoration-none text-dark">
                                        <div class="kontributor-card p-3 text-center h-100">
                                            <div class="avatar-kontributor-wrapper">
                                                <img src="<?= $item['foto_clean']; ?>" class="avatar-kontributor-img" alt="<?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                                            </div>
                                            <div class="nama-kontributor"><?= htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <small class="kontributor-role">Kontributor</small>
                                            <p class="deskripsi-kontributor mt-2">
                                                <?= !empty($item['deskripsi']) ? htmlspecialchars(substr($item['deskripsi'], 0, 80), ENT_QUOTES, 'UTF-8') . '...' : 'Kontributor aktif KANDAGA.'; ?>
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">Belum ada kontributor.</p>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('kontributorGrid');
    if (!root) return;

    const section = root.closest('.kontributor-section');
    const pages = Array.from(root.querySelectorAll('.kontributor-page'));
    if (pages.length < 2) return;

    const prev = section.querySelector('[data-kontributor-prev]');
    const next = section.querySelector('[data-kontributor-next]');
    const indicator = section.querySelector('[data-kontributor-indicator]');

    const desktop = window.matchMedia('(min-width: 768px)');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const AUTO_DELAY = 6500;

    let active = Math.max(0, pages.findIndex(p => p.classList.contains('is-active')));
    let timer = null;
    let paused = false;

    function show(index) {
        active = (index + pages.length) % pages.length;
        pages.forEach((page, i) => {
            const visible = i === active;
            page.classList.toggle('is-active', visible);
            page.setAttribute('aria-hidden', visible ? 'false' : 'true');
        });
        if (indicator) indicator.textContent = (active + 1) + ' / ' + pages.length;
    }

    function stopAuto() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    function startAuto() {
        stopAuto();
        if (!desktop.matches || reduceMotion.matches || paused || document.hidden) return;
        timer = setInterval(() => {
            if (!document.hidden && !paused) show(active + 1);
        }, AUTO_DELAY);
    }

    function go(index) {
        show(index);
        startAuto();
    }

    if (prev) prev.addEventListener('click', () => go(active - 1));
    if (next) next.addEventListener('click', () => go(active + 1));

    section.addEventListener('mouseenter', () => { paused = true; stopAuto(); });
    section.addEventListener('mouseleave', () => { paused = false; startAuto(); });
    section.addEventListener('focusin', () => { paused = true; stopAuto(); });
    section.addEventListener('focusout', (event) => {
        if (!section.contains(event.relatedTarget)) {
            paused = false;
            startAuto();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopAuto();
        else startAuto();
    });

    if (typeof desktop.addEventListener === 'function') {
        desktop.addEventListener('change', startAuto);
        reduceMotion.addEventListener('change', startAuto);
    } else {
        desktop.addListener(startAuto);
        reduceMotion.addListener(startAuto);
    }

    show(active);
    startAuto();
})();
</script>
