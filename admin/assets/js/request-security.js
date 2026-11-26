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
    $(document).on('click', 'a[data-kandaga-delete]', function (event) {
        event.preventDefault();
        var url = new URL(this.href, location.href);
        if (!window.confirm('Hapus data ini?')) return;
        var form = document.createElement('form'); form.method = 'POST';
        form.action = 'index.php?page=' + encodeURIComponent(url.searchParams.get('page'));
        url.searchParams.forEach(function (value, key) {
            if (key === 'page') return;
            var input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = value; form.appendChild(input);
        });
        attach(form); document.body.appendChild(form); form.submit();
    });
})(window.jQuery);
