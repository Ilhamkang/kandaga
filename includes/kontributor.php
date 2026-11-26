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

<div class="embedded-module embedded-kontributor">
    <div class="kontributor-section position-relative">
        <div class="kontributor-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--kandaga-dark-blue);">Kontributor Data</h4>
                <p class="text-muted small mb-0">Mereka yang merawat dan memperkaya data naskah KANDAGA.</p>
            </div>

            <?php if ($kontributor_pages > 1): ?>
                <div class="kontributor-dots" role="group" aria-label="Halaman kontributor">
                    <?php for ($page = 1; $page <= $kontributor_pages; $page++): ?>
                        <button type="button"
                                class="kontributor-dot <?= ($page === 1) ? 'is-active' : '' ?>"
                                data-kontributor-dot="<?= $page ?>"
                                aria-label="Tampilkan kontributor halaman <?= $page ?>"
                                aria-current="<?= ($page === 1) ? 'true' : 'false' ?>"></button>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($kontributor_pages > 1): ?>
            <p class="kontributor-swipe-hint small text-muted mb-3">
                <i class="fa-solid fa-arrow-left-right me-1" aria-hidden="true"></i> Geser untuk melihat kontributor lainnya
            </p>
        <?php endif; ?>

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

    const dotsWrap = section.querySelector('.kontributor-dots');
    const dots = Array.from(section.querySelectorAll('[data-kontributor-dot]'));

    const desktop = window.matchMedia('(min-width: 768px)');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const AUTO_DELAY = 6500;
    const SLIDE_MS = 460;

    let active = Math.max(0, pages.findIndex(p => p.classList.contains('is-active')));
    let timer = null;
    let paused = false;
    let cleanupTimer = null;

    function clearSlideClasses() {
        pages.forEach(p => p.classList.remove('is-in-left', 'is-in-right', 'is-out-left', 'is-out-right'));
        if (cleanupTimer !== null) {
            clearTimeout(cleanupTimer);
            cleanupTimer = null;
        }
    }

    function syncIndicators() {
        pages.forEach((page, i) => page.setAttribute('aria-hidden', i === active ? 'false' : 'true'));
        dots.forEach((dot, i) => {
            const current = i === active;
            dot.classList.toggle('is-active', current);
            dot.setAttribute('aria-current', current ? 'true' : 'false');
        });
    }

    function show(index, direction) {
        const next = (index + pages.length) % pages.length;
        if (next === active) return;

        clearSlideClasses();
        const current = pages[active];
        const upcoming = pages[next];

        if (reduceMotion.matches) {
            pages.forEach((page, i) => page.classList.toggle('is-active', i === next));
            active = next;
            syncIndicators();
            return;
        }

        current.classList.remove('is-active');
        current.classList.add(direction > 0 ? 'is-out-left' : 'is-out-right');

        upcoming.classList.add(direction > 0 ? 'is-in-right' : 'is-in-left');
        void upcoming.offsetWidth;
        upcoming.classList.add('is-active');
        upcoming.classList.remove('is-in-right', 'is-in-left');

        active = next;
        syncIndicators();

        cleanupTimer = setTimeout(() => {
            clearSlideClasses();
        }, SLIDE_MS + 60);
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
            if (!document.hidden && !paused) show(active + 1, 1);
        }, AUTO_DELAY);
    }

    function directionTo(index) {
        const forward = (index - active + pages.length) % pages.length;
        const backward = (active - index + pages.length) % pages.length;
        return forward <= backward ? 1 : -1;
    }

    function go(index) {
        show(index, directionTo(index));
        startAuto();
    }

    if (dotsWrap) {
        dots.forEach((dot, i) => dot.addEventListener('click', () => go(i)));
        dotsWrap.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                event.preventDefault();
                go(active + 1);
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                event.preventDefault();
                go(active - 1);
            }
        });
    }

    let startX = null;
    let startY = null;
    let tracking = false;

    root.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        startX = event.clientX;
        startY = event.clientY;
        tracking = true;
    });

    root.addEventListener('pointerup', (event) => {
        if (!tracking || startX === null || startY === null) return;
        const dx = event.clientX - startX;
        const dy = event.clientY - startY;
        tracking = false;
        startX = null;
        startY = null;

        if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.2) {
            const direction = dx < 0 ? 1 : -1;
            show(active + direction, direction);
            startAuto();
        }
    });

    root.addEventListener('pointercancel', () => {
        tracking = false;
        startX = null;
        startY = null;
    });

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

    syncIndicators();
    startAuto();
})();
</script>
