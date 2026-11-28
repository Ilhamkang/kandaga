<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
require_once __DIR__ . '/includes/media-library.php';

$folderKey = (string)($_GET['folder'] ?? 'editor');
if (!kandagaMediaFolder($folderKey)) $folderKey = 'editor';
$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120, 'UTF-8');
$pageNo = max(1, (int)($_GET['p'] ?? 1));
$mode = (string)($_GET['mode'] ?? 'insert');
if (!in_array($mode, ['insert', 'single', 'multiple'], true)) $mode = 'insert';
$listing = kandagaMediaItems($folderKey, $search, $pageNo, 24);
$queryBase = ['folder' => $folderKey, 'q' => $search];
if ($mode !== 'insert') $queryBase['mode'] = $mode;
$base = BASE_URL . '/admin/file-picker.php?' . http_build_query($queryBase);
$note = $mode === 'multiple'
    ? 'Pilih satu atau beberapa gambar (maksimal 20), lalu klik Selesai.'
    : ($mode === 'single' ? 'Klik gambar untuk memilihnya sebagai gambar utama.' : 'Klik gambar untuk menyisipkannya di posisi kursor. Unggah gambar baru tersedia lewat tombol Gambar di editor.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pilih gambar — Pustaka Media</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin-theme.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin-media.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="kandaga-admin-shell kandaga-media-picker" data-mode="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>">
<div class="kandaga-media-toolbar">
    <div class="kandaga-media-folders">
        <?php foreach (kandagaMediaFolders() as $key => $entry): ?>
            <?php $folderQuery = ['folder' => $key]; if ($mode !== 'insert') $folderQuery['mode'] = $mode; ?>
            <a class="kandaga-media-folder <?= $key === $folderKey ? 'active' : '' ?>" href="<?= htmlspecialchars(BASE_URL . '/admin/file-picker.php?' . http_build_query($folderQuery), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid <?= htmlspecialchars($entry['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars($entry['label'], ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </div>
    <form class="kandaga-media-search" method="get" action="<?= BASE_URL ?>/admin/file-picker.php">
        <input type="hidden" name="folder" value="<?= htmlspecialchars($folderKey, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($mode !== 'insert'): ?><input type="hidden" name="mode" value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <input class="form-control form-control-sm" type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari berkas..." maxlength="120" aria-label="Cari berkas">
        <button class="btn btn-primary btn-sm" type="submit" aria-label="Cari"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
</div>
<p class="kandaga-media-note mb-2"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></p>

<?php if (!$listing['items']): ?>
    <div class="kandaga-media-empty">Belum ada gambar di folder ini.</div>
<?php else: ?>
    <div class="kandaga-media-grid">
        <?php foreach ($listing['items'] as $item): ?>
            <button type="button" class="kandaga-media-pick" data-folder="<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" data-url="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" data-alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" title="Pilih <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="kandaga-media-card">
                    <span class="kandaga-media-card__thumb"><img src="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy"></span>
                    <span class="kandaga-media-card__meta"><span class="kandaga-media-card__name"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span><span class="kandaga-media-card__detail"><?= (int)$item['width'] ?>×<?= (int)$item['height'] ?></span></span>
                </span>
                <span class="kandaga-media-pick-mark" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="kandaga-media-pagination">
        <small><?= $listing['total'] ? ($listing['page'] - 1) * 24 + 1 : 0 ?>–<?= min($listing['page'] * 24, $listing['total']) ?> dari <?= $listing['total'] ?></small>
        <nav aria-label="Halaman pustaka media">
            <a class="btn btn-light border btn-sm <?= $listing['page'] <= 1 ? 'disabled' : '' ?>" href="<?= htmlspecialchars($base . '&p=' . max(1, $listing['page'] - 1), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-chevron-left me-1"></i>Sebelumnya</a>
            <span class="mx-2 small"><?= (int)$listing['page'] ?> / <?= (int)$listing['pages'] ?></span>
            <a class="btn btn-light border btn-sm <?= $listing['page'] >= $listing['pages'] ? 'disabled' : '' ?>" href="<?= htmlspecialchars($base . '&p=' . min($listing['pages'], $listing['page'] + 1), ENT_QUOTES, 'UTF-8') ?>">Berikutnya<i class="fa-solid fa-chevron-right ms-1"></i></a>
        </nav>
    </div>
<?php endif; ?>

<?php if ($mode === 'multiple'): ?>
<div class="kandaga-media-picker-footer">
    <span class="kandaga-media-picker-count" aria-live="polite">0 gambar dipilih</span>
    <button type="button" class="btn btn-primary btn-sm" id="kandagaMediaDone"><i class="fa-solid fa-check me-1" aria-hidden="true"></i>Selesai</button>
</div>
<?php endif; ?>
<script>
(function () {
    'use strict';
    var mode = document.body.getAttribute('data-mode') || 'insert';
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.kandaga-media-pick'));
    var selected = [];

    function send(payload) {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage(payload, window.location.origin);
        } else if (payload.url) {
            window.open(payload.url, '_blank', 'noopener');
        }
    }

    function entry(button) {
        return {
            folder: button.getAttribute('data-folder'),
            name: button.getAttribute('data-name'),
            url: button.getAttribute('data-url'),
            alt: button.getAttribute('data-alt')
        };
    }

    function refresh() {
        var count = document.querySelector('.kandaga-media-picker-count');
        if (count) count.textContent = selected.length + ' gambar dipilih';
        buttons.forEach(function (button) {
            var item = entry(button);
            var active = selected.some(function (chosen) {
                return chosen.folder === item.folder && chosen.name === item.name;
            });
            button.classList.toggle('is-selected', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var item = entry(button);
            if (mode === 'insert') {
                send({mceAction: 'insert', url: item.url, alt: item.alt});
                return;
            }
            if (mode === 'single') {
                send({mceAction: 'pick', mode: 'single', folder: item.folder, name: item.name, url: item.url, alt: item.alt});
                return;
            }
            var index = selected.findIndex(function (chosen) {
                return chosen.folder === item.folder && chosen.name === item.name;
            });
            if (index >= 0) selected.splice(index, 1);
            else if (selected.length < 20) selected.push(item);
            refresh();
        });
    });

    var done = document.getElementById('kandagaMediaDone');
    if (done) {
        done.addEventListener('click', function () {
            send({mceAction: 'pick', mode: 'multiple', items: selected});
        });
    }
    refresh();
})();
</script>
</body>
</html>
