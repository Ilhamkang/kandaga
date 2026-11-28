/* Article media picker: choose a main image or a gallery from Pustaka Media.
 * The picker itself is an iframe; only same-origin postMessage payloads are
 * accepted and the server re-validates every {folder, name} pair on save.
 */
(function (window, document) {
    'use strict';

    if (window.kandagaPostMedia) return;

    var MODAL_ID = 'kandagaPostPickerModal';
    var FRAME_ID = 'kandagaPostPickerFrame';
    var state = null;
    var modalInstance = null;

    function ensureModal() {
        var modal = document.getElementById(MODAL_ID);
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'modal fade kandaga-post-picker-modal';
        modal.id = MODAL_ID;
        modal.tabIndex = -1;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = ''
            + '<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">'
            + '  <div class="modal-content">'
            + '    <div class="modal-header">'
            + '      <h5 class="modal-title"><i class="fa-solid fa-images me-2" aria-hidden="true"></i>Pustaka Media</h5>'
            + '      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>'
            + '    </div>'
            + '    <div class="modal-body p-0"><iframe id="' + FRAME_ID + '" title="Pilih gambar" class="kandaga-post-picker-frame" loading="lazy"></iframe></div>'
            + '  </div>'
            + '</div>';
        document.body.appendChild(modal);
        return modal;
    }

    function open(options) {
        // Preserve the refs supplied by init(); callers only pass the mode.
        state = Object.assign({}, state || {}, options || {});
        var modal = ensureModal();
        var frame = document.getElementById(FRAME_ID);
        var url = state.pickerUrl + '?mode=' + encodeURIComponent(state.mode || 'single') + '&folder=editor';
        frame.setAttribute('src', url);
        if (window.bootstrap && window.bootstrap.Modal) {
            modalInstance = window.bootstrap.Modal.getOrCreateInstance(modal);
            modalInstance.show();
        } else {
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }
    }

    function close() {
        var modal = document.getElementById(MODAL_ID);
        if (!modal) return;
        if (modalInstance) modalInstance.hide();
        else {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            var backdrop = document.querySelector('.kandaga-post-picker-backdrop');
            if (backdrop) backdrop.remove();
        }
    }

    function applySingle(item) {
        if (!state || !state.hiddenInput) return;
        state.hiddenInput.value = JSON.stringify({folder: item.folder, name: item.name});
        if (state.preview) {
            state.preview.querySelector('img').src = item.url;
            state.preview.hidden = false;
        }
        if (state.fileInput) state.fileInput.value = '';
        if (state.sourceLabel) state.sourceLabel.textContent = 'Gambar dipilih dari Pustaka Media: ' + item.name;
    }

    function same(a, b) { return a.folder === b.folder && a.name === b.name; }

    function applyMultiple(items) {
        if (!state || !state.galleryInput || !state.galleryList) return;
        var gallery = state.gallery || [];
        (items || []).forEach(function (item) {
            if (!item || !item.folder || !item.name) return;
            if (!gallery.some(function (existing) { return same(existing, item); })) {
                gallery.push({folder: item.folder, name: item.name, url: item.url, alt: item.alt || ''});
            }
        });
        state.gallery = gallery.slice(0, 20);
        renderGallery();
    }

    function renderGallery() {
        var list = state.galleryList;
        list.textContent = '';
        (state.gallery || []).forEach(function (item, index) {
            var row = document.createElement('div');
            row.className = 'kandaga-post-gallery-item';

            var thumb = document.createElement('img');
            thumb.src = item.url;
            thumb.alt = '';
            thumb.loading = 'lazy';
            row.appendChild(thumb);

            var body = document.createElement('div');
            body.className = 'kandaga-post-gallery-item__body';
            var name = document.createElement('span');
            name.className = 'kandaga-post-gallery-item__name';
            name.textContent = item.name;
            name.title = item.name;
            body.appendChild(name);

            var alt = document.createElement('input');
            alt.type = 'text';
            alt.className = 'form-control form-control-sm';
            alt.placeholder = 'Teks alternatif (opsional)';
            alt.maxLength = 255;
            alt.value = item.alt || '';
            alt.setAttribute('aria-label', 'Teks alternatif ' + item.name);
            alt.addEventListener('input', function () { item.alt = alt.value; syncGallery(); });
            body.appendChild(alt);
            row.appendChild(body);

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-light text-danger kandaga-action-btn';
            remove.setAttribute('data-bs-toggle', 'tooltip');
            remove.setAttribute('title', 'Hapus gambar');
            remove.setAttribute('aria-label', 'Hapus gambar ' + item.name);
            remove.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
            remove.addEventListener('click', function () {
                state.gallery.splice(index, 1);
                renderGallery();
            });
            row.appendChild(remove);
            list.appendChild(row);
        });

        if (state.galleryCount) {
            state.galleryCount.textContent = (state.gallery || []).length + ' gambar dipilih';
        }
        syncGallery();
        if (window.kandagaInitTooltips) window.kandagaInitTooltips(list);
    }

    function syncGallery() {
        if (!state || !state.galleryInput) return;
        state.galleryInput.value = JSON.stringify((state.gallery || []).map(function (item) {
            return {folder: item.folder, name: item.name, alt: item.alt || ''};
        }));
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) return;
        var frame = document.getElementById(FRAME_ID);
        if (!frame || event.source !== frame.contentWindow) return;
        var data = event.data;
        if (!data || data.mceAction !== 'pick') return;
        if (data.mode === 'single') applySingle(data);
        else if (data.mode === 'multiple') applyMultiple(data.items);
        close();
    });

    window.kandagaPostMedia = {
        init: function (options) {
            state = options || {};
            state.gallery = state.gallery || [];
            if (!state.gallery.length && state.galleryInput && state.galleryInput.value) {
                try {
                    var saved = JSON.parse(state.galleryInput.value);
                    if (Array.isArray(saved)) {
                        state.gallery = saved.filter(function (item) {
                            return item && item.folder && item.name;
                        }).map(function (item) {
                            return {
                                folder: item.folder,
                                name: item.name,
                                alt: item.alt || '',
                                url: item.url || String(state.pickerBaseUrl || String(state.pickerUrl || '').replace(/\/admin\/file-picker\.php.*$/, '')) + '/assets/images/post/' + encodeURIComponent(item.name)
                            };
                        });
                    }
                } catch (error) { state.gallery = []; }
            }
            renderGallery();
        },
        open: open
    };
})(window, document);
