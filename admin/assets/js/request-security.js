(function ($) {
    'use strict';
    if (!$ || window.kandagaRequestSecurity) return;
    window.kandagaRequestSecurity = true;
    $.ajaxPrefilter(function (options, original, xhr) {
        var target = new URL(options.url || location.href, location.href);
        if (target.origin === location.origin && /^(POST|PUT|PATCH|DELETE)$/i.test(options.type || 'GET')) {
            xhr.setRequestHeader('X-CSRF-Token', window.KANDAGA_CSRF || '');
        }
    });
    // Include the token in native and AJAX forms, including dynamically added forms.
    function attach(form) {
        if (!form.querySelector('[name="csrf_token"]')) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = 'csrf_token'; input.value = window.KANDAGA_CSRF || '';
            form.appendChild(input);
        }
    }
    document.addEventListener('submit', function (event) { attach(event.target); }, true);
    $(function () { document.querySelectorAll('form').forEach(attach); });
    // Forms that need a centred confirmation instead of native confirm().
    $(document).on('submit', '.kandaga-confirm-form', function (event) {
        var form = this;
        if (form.dataset.confirmed === '1') return;
        event.preventDefault();
        window.kandagaDialog.confirm(form.dataset.confirmMessage || 'Lanjutkan?', {
            title: 'Konfirmasi', icon: 'warning', confirmLabel: 'Ya, lanjutkan', danger: true
        }).then(function (result) {
            if (!result.isConfirmed) return;
            form.dataset.confirmed = '1';
            form.submit();
        });
    });
    $(document).on('click', 'a[data-kandaga-delete]', function (event) {
        event.preventDefault();
        var url = new URL(this.href, location.href);
        var dialog = window.kandagaDialog;
        if (!dialog) return;
        dialog.confirm('Hapus data ini?', {
            title: 'Konfirmasi hapus',
            icon: 'warning',
            confirmLabel: 'Ya, hapus',
            danger: true
        }).then(function (result) {
            if (!result.isConfirmed) return;
            var form = document.createElement('form'); form.method = 'POST';
            form.action = 'index.php?page=' + encodeURIComponent(url.searchParams.get('page'));
            url.searchParams.forEach(function (value, key) {
                if (key === 'page') return;
                var input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = value; form.appendChild(input);
            });
            attach(form); document.body.appendChild(form); form.submit();
        });
    });
})(window.jQuery);
