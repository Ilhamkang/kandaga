/* Progressive enhancement for the shared public header's compact search. */
(function (window, document) {
    'use strict';

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-kandaga-search-toggle]');
        if (!toggle) return;
        var panel = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!panel) return;
        var willOpen = panel.hidden;
        panel.hidden = !willOpen;
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        if (willOpen) {
            var input = panel.querySelector('input[type="search"], input[type="text"]');
            if (input) input.focus();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-kandaga-search-toggle][aria-expanded="true"]').forEach(function (toggle) {
            var panel = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!panel) return;
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        });
    });
})(window, document);
