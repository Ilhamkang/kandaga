(function () {
    'use strict';
    var root = document.getElementById('statisticsDashboard');
    if (!root) return;

    var endpoint = root.getAttribute('data-detail-endpoint');
    var modal = document.getElementById('statisticsDetailModal');
    var title = document.getElementById('statisticsDetailTitle');
    var body = document.getElementById('statisticsDetailBody');
    var cache = Object.create(null);
    var activeRequest = null;

    function text(value) {
        return value === null || typeof value === 'undefined' || value === '' ? '—' : String(value);
    }
    function format(value) {
        if (value === null || typeof value === 'undefined' || value === '') return '—';
        var number = Number(value);
        return Number.isFinite(number) && String(value).match(/^\d+(\.\d+)?$/)
            ? new Intl.NumberFormat('id-ID').format(number)
            : text(value);
    }
    function showModal() {
        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') window.jQuery(modal).modal('show');
        else { modal.classList.add('show'); modal.style.display = 'block'; modal.removeAttribute('aria-hidden'); }
    }
    function hideModal() {
        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') window.jQuery(modal).modal('hide');
        else { modal.classList.remove('show'); modal.style.display = 'none'; modal.setAttribute('aria-hidden', 'true'); }
    }
    function loading(message) {
        body.textContent = '';
        var p = document.createElement('p');
        p.className = 'text-muted mb-0';
        p.textContent = message || 'Memuat rincian…';
        body.appendChild(p);
    }
    function render(payload) {
        body.textContent = '';
        var columns = payload.columns || {};
        var rows = payload.rows || [];
        var keys = Object.keys(columns);
        if (!rows.length) {
            var empty = document.createElement('p');
            empty.className = 'text-muted mb-0';
            empty.textContent = 'Belum ada data untuk ditampilkan.';
            body.appendChild(empty);
            return;
        }
        var wrap = document.createElement('div');
        wrap.className = 'table-responsive';
        var table = document.createElement('table');
        table.className = 'table table-sm table-striped mb-0';
        var thead = document.createElement('thead');
        var headRow = document.createElement('tr');
        keys.forEach(function (key) { var th = document.createElement('th'); th.textContent = columns[key]; headRow.appendChild(th); });
        thead.appendChild(headRow);
        table.appendChild(thead);
        var tbody = document.createElement('tbody');
        rows.forEach(function (row) {
            var tr = document.createElement('tr');
            keys.forEach(function (key) {
                var td = document.createElement('td');
                td.style.overflowWrap = 'anywhere';
                td.textContent = key === 'jumlah' || key === 'views' || key === 'searches' ? format(row[key]) : text(row[key]);
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        wrap.appendChild(table);
        body.appendChild(wrap);
    }
    function load(type, label) {
        var key = type + ':10';
        title.textContent = label || 'Rincian statistik';
        showModal();
        if (cache[key]) { render(cache[key]); return; }
        if (activeRequest) activeRequest.abort();
        loading();
        activeRequest = new AbortController();
        fetch(endpoint + '?type=' + encodeURIComponent(type) + '&limit=10', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeRequest.signal
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (payload) {
            if (!payload || payload.success !== true) throw new Error(payload && payload.message ? payload.message : 'Respons tidak valid.');
            cache[key] = payload;
            render(payload);
        }).catch(function (error) {
            if (error.name === 'AbortError') return;
            body.textContent = '';
            var alert = document.createElement('div');
            alert.className = 'alert alert-warning mb-0';
            alert.textContent = 'Rincian belum dapat dimuat. Silakan coba lagi.';
            body.appendChild(alert);
        }).finally(function () { activeRequest = null; });
    }
    root.querySelectorAll('[data-stat-detail]').forEach(function (button) {
        button.addEventListener('click', function () { load(button.dataset.statDetail, button.dataset.statLabel); });
    });
    modal.querySelectorAll('[data-dismiss="modal"]').forEach(function (button) { button.addEventListener('click', hideModal); });
})();
