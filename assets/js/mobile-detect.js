(function (window, document) {
    'use strict';
    var query = window.matchMedia('(max-width: 767.98px)');
    function apply() {
        var mobile = !!query.matches;
        document.cookie = 'kandaga_viewport=' + (mobile ? 'mobile' : 'desktop') + '; path=/; SameSite=Lax';
        document.documentElement.classList.toggle('kandaga-mobile', mobile);
        document.documentElement.classList.toggle('kandaga-desktop', !mobile);
        if (document.body) {
            document.body.classList.toggle('is-mobile', mobile);
            document.body.classList.toggle('is-desktop', !mobile);
        }
        window.KandagaViewport = { mobile: mobile, desktop: !mobile };
    }
    apply();
    if (query.addEventListener) query.addEventListener('change', apply);
    else if (query.addListener) query.addListener(apply);
    document.addEventListener('DOMContentLoaded', apply, { once: true });
})(window, document);
