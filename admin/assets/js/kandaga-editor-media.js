/* TinyMCE media helpers shared by "Buat Postingan" and "Edit Postingan".
 * Uploads go through the authenticated local endpoint; the picker opens the
 * same read-only media index used by the Goah "Pustaka Berkas" submodule. */
(function (window) {
    'use strict';

    function escapeAttribute(value) {
        return String(value || '')
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function upload(blobInfo, success, failure, progress) {
        var endpoint = window.KANDAGA_EDITOR_UPLOAD_URL;
        if (!endpoint) { failure('Unggah gambar belum dikonfigurasi.'); return; }
        var form = new FormData();
        form.append('file', blobInfo.blob(), blobInfo.filename());
        var request = new XMLHttpRequest();
        request.open('POST', endpoint, true);
        request.setRequestHeader('X-CSRF-Token', window.KANDAGA_CSRF || '');
        if (typeof progress === 'function') {
            request.upload.onprogress = function (event) {
                if (event.lengthComputable) progress(Math.round(event.loaded * 100 / event.total));
            };
        }
        request.onload = function () {
            var data = {};
            try { data = JSON.parse(request.responseText); } catch (error) { data = {}; }
            var location = data.data && data.data.location;
            if (request.status >= 200 && request.status < 300 && data.success && location) {
                success(location);
                return;
            }
            failure(data.message || 'Gambar gagal diunggah. Periksa ukuran dan jenis berkas.');
        };
        request.onerror = function () { failure('Gagal menghubungi server unggah gambar.'); };
        request.send(form);
    }

    function pick(editor, callback, meta) {
        var picker = window.KANDAGA_EDITOR_PICKER_URL;
        if (!picker) { return; }
        editor.windowManager.openUrl({
            title: 'Pustaka Berkas — pilih gambar',
            url: picker,
            width: Math.min(1040, window.innerWidth - 40),
            height: Math.min(720, window.innerHeight - 40),
            onMessage: function (dialog, message) {
                if (!message || message.mceAction !== 'insert') return;
                var url = String(message.url || '');
                var alt = String(message.alt || '');
                if (!url) return;
                if (typeof callback === 'function') {
                    // Called by the image plugin: fill its Source field.
                    callback(url, { alt: alt, title: alt });
                } else {
                    // Called by the toolbar button: insert inline at the cursor.
                    editor.insertContent('<img src="' + escapeAttribute(url) + '" alt="' + escapeAttribute(alt) + '">');
                }
                editor.windowManager.close();
            }
        });
    }

    function setup(editor) {
        editor.ui.registry.addButton('kandagaMedia', {
            icon: 'image',
            tooltip: 'Sisipkan gambar dari Pustaka Berkas',
            onAction: function () { pick(editor, null, null); }
        });
    }

    window.KandagaEditorMedia = { upload: upload, pick: pick, setup: setup };
})(window);
