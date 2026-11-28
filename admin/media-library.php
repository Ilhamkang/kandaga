<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
require_once __DIR__ . '/includes/media-library.php';

$folderKey = (string)($_GET['folder'] ?? 'editor');
if (!kandagaMediaFolder($folderKey)) $folderKey = 'editor';
$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120, 'UTF-8');
$pageNo = max(1, (int)($_GET['p'] ?? 1));
$listing = kandagaMediaItems($folderKey, $search, $pageNo, 24);
$folderCounts = kandagaMediaFolderCounts();
$folder = kandagaMediaFolder($folderKey);
$base = BASE_URL . '/admin/index.php?' . http_build_query(['page' => 'media-library', 'folder' => $folderKey, 'q' => $search]);
$uploadUrl = BASE_URL . '/admin/actions/editor-image-upload.php';
$deleteUrl = BASE_URL . '/admin/actions/media-delete.php';
?>
<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pustaka Media — KANDAGA</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
<?php include __DIR__ . '/../includes/module-head.php'; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin-theme.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin-media.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
</head><body class="kandaga-module kandaga-admin-shell">
<?php endif; ?>

<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?><div class="container-fluid admin-module-surface"><?php endif; ?>
    <?php kandagaPageHeader(
        'Pustaka Media',
        'fa-folder-open',
        'Jelajahi dan salin URL gambar yang telah diunggah ke pustaka media Kandaga.',
        '<label class="btn btn-outline-primary mb-0 kandaga-media-upload-btn"><i class="fa-solid fa-upload me-2" aria-hidden="true"></i>Unggah Gambar<input type="file" id="kandagaMediaUpload" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none"></label>'
    ); ?>

    <div class="kandaga-media-toolbar">
        <div class="kandaga-media-folders" role="tablist" aria-label="Folder pustaka">
            <?php foreach (kandagaMediaFolders() as $key => $entry): ?>
                <a class="kandaga-media-folder <?= $key === $folderKey ? 'active' : '' ?>" href="<?= htmlspecialchars(BASE_URL . '/admin/index.php?page=media-library&folder=' . rawurlencode($key), ENT_QUOTES, 'UTF-8') ?>" <?= $key === $folderKey ? 'aria-current="page"' : '' ?>>
                    <i class="fa-solid <?= htmlspecialchars($entry['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                    <?= htmlspecialchars($entry['label'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge bg-light text-secondary border"><?= (int)$folderCounts[$key] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <form class="kandaga-media-search" method="get" action="<?= BASE_URL ?>/admin/index.php">
            <input type="hidden" name="page" value="media-library">
            <input type="hidden" name="folder" value="<?= htmlspecialchars($folderKey, ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control form-control-sm" type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama berkas..." maxlength="120" aria-label="Cari nama berkas">
            <button class="btn btn-primary btn-sm" type="submit" aria-label="Cari berkas"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    <p class="kandaga-media-note mb-3"><i class="fa-solid fa-folder-open me-1" aria-hidden="true"></i><?= htmlspecialchars($folder['path'], ENT_QUOTES, 'UTF-8') ?> · <?= (int)$listing['total'] ?> berkas pada folder ini. Hanya gambar yang ditampilkan.</p>

    <?php if (!$listing['items']): ?>
        <div class="kandaga-media-empty"><i class="fa-regular fa-folder-open fa-2x mb-3 d-block"></i>Belum ada gambar di folder ini.</div>
    <?php else: ?>
        <div class="kandaga-media-grid">
            <?php foreach ($listing['items'] as $item): ?>
                <?php $mediaJson = json_encode([
                    'folder' => $item['key'], 'name' => $item['name'], 'url' => $item['url'],
                    'width' => $item['width'], 'height' => $item['height'],
                    'bytes' => kandagaMediaFormatBytes($item['bytes']),
                    'modified' => date('d M Y H:i', $item['modified']),
                    'path' => $item['relative'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
                <article class="kandaga-media-card" role="button" tabindex="0" data-media="<?= htmlspecialchars($mediaJson, ENT_QUOTES, 'UTF-8') ?>" aria-label="Lihat detail <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="kandaga-media-card__thumb"><img src="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy"></div>
                    <div class="kandaga-media-card__meta">
                        <span class="kandaga-media-card__name" title="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="kandaga-media-card__detail"><?= (int)$item['width'] ?>×<?= (int)$item['height'] ?> · <?= htmlspecialchars(kandagaMediaFormatBytes($item['bytes']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="kandaga-media-pagination">
            <small><?= $listing['total'] ? ($listing['page'] - 1) * 24 + 1 : 0 ?>–<?= min($listing['page'] * 24, $listing['total']) ?> dari <?= $listing['total'] ?> berkas</small>
            <nav aria-label="Halaman Pustaka Media">
                <a class="btn btn-light border btn-sm <?= $listing['page'] <= 1 ? 'disabled' : '' ?>" href="<?= htmlspecialchars($base . '&p=' . max(1, $listing['page'] - 1), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-chevron-left me-1"></i>Sebelumnya</a>
                <span class="mx-2 small"><?= (int)$listing['page'] ?> / <?= (int)$listing['pages'] ?></span>
                <a class="btn btn-light border btn-sm <?= $listing['page'] >= $listing['pages'] ? 'disabled' : '' ?>" href="<?= htmlspecialchars($base . '&p=' . min($listing['pages'], $listing['page'] + 1), ENT_QUOTES, 'UTF-8') ?>">Berikutnya<i class="fa-solid fa-chevron-right ms-1"></i></a>
            </nav>
        </div>
    <?php endif; ?>
<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?></div><?php endif; ?>

<div class="modal fade kandaga-media-detail-modal" id="kandagaMediaDetail" tabindex="-1" aria-labelledby="kandagaMediaDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kandagaMediaDetailTitle"><i class="fa-regular fa-image me-2" aria-hidden="true"></i>Detail Gambar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Keluar"></button>
            </div>
            <div class="modal-body">
                <div class="kandaga-media-detail-preview"><img id="kandagaMediaDetailImage" src="" alt="" loading="lazy"></div>
                <dl class="kandaga-media-detail-meta mb-0">
                    <div><dt>Nama berkas</dt><dd id="kandagaMediaDetailName"></dd></div>
                    <div><dt>Folder</dt><dd id="kandagaMediaDetailFolder"></dd></div>
                    <div><dt>Lokasi</dt><dd id="kandagaMediaDetailPath"></dd></div>
                    <div><dt>Dimensi</dt><dd id="kandagaMediaDetailSize"></dd></div>
                    <div><dt>Ukuran</dt><dd id="kandagaMediaDetailBytes"></dd></div>
                    <div><dt>Diubah</dt><dd id="kandagaMediaDetailModified"></dd></div>
                    <div><dt>URL</dt><dd id="kandagaMediaDetailUrl"></dd></div>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>Keluar</button>
                <button type="button" class="btn btn-outline-danger" id="kandagaMediaDelete"><i class="fa-solid fa-trash me-1" aria-hidden="true"></i>Hapus</button>
                <button type="button" class="btn btn-primary" id="kandagaMediaCopy"><i class="fa-solid fa-link me-1" aria-hidden="true"></i>Salin link</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    window.KANDAGA_CSRF = window.KANDAGA_CSRF || <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
    var uploadUrl = <?= json_encode($uploadUrl, JSON_UNESCAPED_SLASHES) ?>;
    var deleteUrl = <?= json_encode($deleteUrl, JSON_UNESCAPED_SLASHES) ?>;
    var current = null;
    var detail = document.getElementById('kandagaMediaDetail');
    var bsDetail = window.bootstrap && window.bootstrap.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(detail) : null;

    function notify(message, icon) {
        return window.kandagaDialog.show({
            title: icon === 'success' ? 'Berhasil' : 'Informasi',
            message: message,
            icon: icon,
            timer: 2200
        });
    }

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) node.textContent = value || '—';
    }

    function openDetail(item) {
        current = item;
        var image = document.getElementById('kandagaMediaDetailImage');
        image.src = item.url;
        image.alt = item.name;
        setText('kandagaMediaDetailName', item.name);
        setText('kandagaMediaDetailFolder', item.folder);
        setText('kandagaMediaDetailPath', item.path);
        setText('kandagaMediaDetailSize', item.width + ' × ' + item.height + ' px');
        setText('kandagaMediaDetailBytes', item.bytes);
        setText('kandagaMediaDetailModified', item.modified);
        setText('kandagaMediaDetailUrl', item.url);
        if (bsDetail) bsDetail.show();
        else {
            detail.style.display = 'block';
            detail.classList.add('show');
            detail.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }
    }

    function closeDetail() {
        current = null;
        if (bsDetail) bsDetail.hide();
        else {
            detail.style.display = 'none';
            detail.classList.remove('show');
            detail.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        }
    }

    function askConfirm(message) {
        return window.kandagaDialog.confirm(message, {
            title: 'Hapus gambar?',
            icon: 'warning',
            confirmLabel: 'Ya, hapus',
            cancelLabel: 'Batal',
            danger: true
        });
    }

    function postJson(url, fields) {
        var form = new FormData();
        Object.keys(fields).forEach(function (key) { form.append(key, fields[key]); });
        return fetch(url, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
            headers: {'X-CSRF-Token': window.KANDAGA_CSRF}
        }).then(function (response) {
            return response.text().then(function (text) {
                var data = {};
                try { data = JSON.parse(text); } catch (error) { data = {}; }
                return {ok: response.ok, data: data};
            });
        });
    }

    function copyUrl(url) {
        function done() { notify('URL gambar disalin.', 'success'); }
        function fallback() {
            var helper = document.createElement('textarea');
            helper.value = url;
            helper.setAttribute('readonly', '');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();
            try { document.execCommand('copy'); done(); }
            catch (error) { notify('URL tidak dapat disalin otomatis.', 'error'); }
            helper.remove();
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(fallback);
        } else {
            fallback();
        }
    }

    document.getElementById('kandagaMediaCopy').addEventListener('click', function () {
        if (current) copyUrl(current.url);
    });

    document.getElementById('kandagaMediaDelete').addEventListener('click', function () {
        if (!current) return;
        var item = current;
        askConfirm('Gambar "' + item.name + '" akan dihapus dari Pustaka Media. Tindakan ini tidak dapat dibatalkan.').then(function (answer) {
            if (!answer || !answer.isConfirmed) return;
            postJson(deleteUrl, {folder: item.folder, name: item.name})
                .then(function (result) {
                    if (result.ok && result.data.success) { closeDetail(); window.location.reload(); return; }
                    notify(result.data.message || 'Gambar gagal dihapus.', 'error');
                })
                .catch(function () { notify('Gagal menghubungi server penghapusan gambar.', 'error'); });
        });
    });

    document.querySelectorAll('.kandaga-media-card').forEach(function (card) {
        card.addEventListener('click', function () {
            try { openDetail(JSON.parse(card.getAttribute('data-media'))); } catch (error) { /* invalid payload */ }
        });
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                card.click();
            }
        });
    });

    var fileInput = document.getElementById('kandagaMediaUpload');
    var uploadButton = document.querySelector('.kandaga-media-upload-btn');
    fileInput.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        uploadButton.setAttribute('aria-busy', 'true');
        postJson(uploadUrl, {file: file})
            .then(function (result) {
                if (result.ok && result.data.success) { window.location.reload(); return; }
                notify(result.data.message || 'Gambar gagal diunggah.', 'error');
            })
            .catch(function () { notify('Gagal menghubungi server unggah gambar.', 'error'); })
            .finally(function () {
                uploadButton.removeAttribute('aria-busy');
                fileInput.value = '';
            });
    });
})();
</script>
<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?></body></html><?php endif; ?>
