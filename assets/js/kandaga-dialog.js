/* Kandaga dialog bridge.
 *
 * One centred Bootstrap modal replaces native alert(), SweetAlert toasts, and
 * ad-hoc confirmations. The API is Promise-based so existing `.then()` chains
 * keep working: result.isConfirmed is true only when the primary action was
 * chosen. No vendor file is modified.
 */
(function (window, document) {
    'use strict';

    if (window.kandagaDialog && window.kandagaDialog.__kandaga) return;

    var MODAL_ID = 'kandagaDialogModal';
    var ICONS = {
        success: ['fa-circle-check', 'text-success'],
        error: ['fa-circle-xmark', 'text-danger'],
        warning: ['fa-triangle-exclamation', 'text-warning'],
        info: ['fa-circle-info', 'text-primary'],
        question: ['fa-circle-question', 'text-primary']
    };

    function ensureModal() {
        var modal = document.getElementById(MODAL_ID);
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'modal fade kandaga-dialog-modal';
        modal.id = MODAL_ID;
        modal.tabIndex = -1;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = ''
            + '<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">'
            + '  <div class="modal-content">'
            + '    <div class="modal-header">'
            + '      <h5 class="modal-title"><i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i><span class="kandaga-dialog-title"></span></h5>'
            + '      <button type="button" class="btn-close" data-kandaga-dismiss aria-label="Tutup"></button>'
            + '    </div>'
            + '    <div class="modal-body">'
            + '      <p class="kandaga-dialog-message mb-0"></p>'
            + '      <input type="text" class="form-control kandaga-dialog-input mt-3" hidden>'
            + '      <p class="kandaga-dialog-error text-danger small mt-2 mb-0" hidden></p>'
            + '    </div>'
            + '    <div class="modal-footer">'
            + '      <button type="button" class="btn btn-light border kandaga-dialog-cancel">Batal</button>'
            + '      <button type="button" class="btn btn-primary kandaga-dialog-ok">Tutup</button>'
            + '    </div>'
            + '  </div>'
            + '</div>';
        (document.body || document.documentElement).appendChild(modal);
        return modal;
    }

    function bootstrapModal(modal) {
        if (window.bootstrap && window.bootstrap.Modal) {
            return window.bootstrap.Modal.getOrCreateInstance(modal, { backdrop: 'static', keyboard: false });
        }
        return null;
    }

    function hideModal(modal, bs) {
        if (bs) { bs.hide(); return; }
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        var backdrop = document.querySelector('.kandaga-dialog-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    }

    function show(options) {
        var opts = options || {};
        return new Promise(function (resolve) {
            var previousFocus = document.activeElement;
            var modal = ensureModal();
            var title = modal.querySelector('.kandaga-dialog-title');
            var message = modal.querySelector('.kandaga-dialog-message');
            var icon = modal.querySelector('.modal-title i');
            var cancel = modal.querySelector('.kandaga-dialog-cancel');
            var ok = modal.querySelector('.kandaga-dialog-ok');
            var close = modal.querySelector('[data-kandaga-dismiss]');
            var input = modal.querySelector('.kandaga-dialog-input');
            var error = modal.querySelector('.kandaga-dialog-error');
            var bs = bootstrapModal(modal);
            var timer = null;
            var settled = false;

            var iconSpec = ICONS[opts.icon] || ICONS.info;
            icon.className = 'fa-solid ' + iconSpec[0] + ' me-2 ' + iconSpec[1];
            title.textContent = opts.title || 'Kandaga';
            message.textContent = opts.message == null ? '' : String(opts.message);
            message.style.whiteSpace = 'pre-line';

            var confirmMode = !!opts.confirm;
            var danger = !!opts.danger || /#d33|#dc3545/i.test(String(opts.confirmButtonColor || ''));
            cancel.hidden = !confirmMode;
            cancel.textContent = opts.cancelLabel || 'Batal';
            ok.textContent = confirmMode ? (opts.confirmLabel || 'Ya, lanjutkan') : (opts.okLabel || 'Tutup');
            ok.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary') + ' kandaga-dialog-ok';

            error.hidden = true;
            error.textContent = '';
            var wantsInput = opts.input === 'text';
            input.hidden = !wantsInput;
            input.value = wantsInput ? String(opts.inputValue || '') : '';
            input.placeholder = opts.inputPlaceholder || '';
            input.setAttribute('aria-label', opts.inputPlaceholder || 'Konfirmasi');

            function finish(value) {
                if (settled) return;
                settled = true;
                if (timer) window.clearTimeout(timer);
                modal._kandagaResolve = null;
                hideModal(modal, bs);
                if (previousFocus && typeof previousFocus.focus === 'function') {
                    window.setTimeout(function () { previousFocus.focus(); }, 0);
                }
                resolve({
                    isConfirmed: value === true || typeof value === 'string',
                    value: value === true ? true : value,
                    dismiss: value === true || typeof value === 'string' ? undefined : 'cancel'
                });
            }

            function accept() {
                if (!wantsInput) { finish(true); return; }
                var typed = input.value;
                if (typeof opts.inputValidator === 'function') {
                    var problem = '';
                    try { problem = opts.inputValidator(typed); } catch (err) { problem = err.message; }
                    if (problem) {
                        error.textContent = problem;
                        error.hidden = false;
                        input.focus();
                        return;
                    }
                }
                finish(typed);
            }

            modal._kandagaResolve = finish;
            if (!modal._kandagaBound) {
                modal._kandagaBound = true;
                modal.addEventListener('hidden.bs.modal', function () {
                    if (modal._kandagaResolve) modal._kandagaResolve(false);
                });
                ok.addEventListener('click', function () {
                    if (modal._kandagaAccept) modal._kandagaAccept();
                });
                cancel.addEventListener('click', function () { if (modal._kandagaResolve) modal._kandagaResolve(false); });
                close.addEventListener('click', function () { if (modal._kandagaResolve) modal._kandagaResolve(false); });
                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') { event.preventDefault(); if (modal._kandagaAccept) modal._kandagaAccept(); }
                });
            }
            modal._kandagaAccept = accept;

            if (bs) {
                bs.show();
            } else {
                modal.style.display = 'block';
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show kandaga-dialog-backdrop';
                document.body.appendChild(backdrop);
            }
            window.setTimeout(function () { (wantsInput ? input : ok).focus(); }, 60);

            if (opts.timer && Number(opts.timer) > 0) {
                timer = window.setTimeout(function () { finish(false); }, Number(opts.timer));
            }
        });
    }

    function normalise(args) {
        var first = args[0];
        if (first && typeof first === 'object') return first;
        return { title: first || 'Kandaga', message: args[1] || '', icon: args[2] || 'info' };
    }

    window.kandagaDialog = {
        __kandaga: true,
        show: show,
        alert: function () { return show(normalise(arguments)); },
        confirm: function (message, options) {
            var opts = options || {};
            return show({
                title: opts.title || 'Konfirmasi',
                message: message,
                icon: opts.icon || 'question',
                confirm: true,
                confirmLabel: opts.confirmLabel || 'Ya, lanjutkan',
                cancelLabel: opts.cancelLabel || 'Batal',
                danger: !!opts.danger,
                confirmButtonColor: opts.confirmButtonColor,
                input: opts.input,
                inputPlaceholder: opts.inputPlaceholder,
                inputValue: opts.inputValue,
                inputValidator: opts.inputValidator
            });
        },
        close: function () {
            var modal = document.getElementById(MODAL_ID);
            if (modal && modal._kandagaResolve) modal._kandagaResolve(false);
        }
    };

    // SweetAlert2 call sites keep their Promise API but now render the same
    // centred Bootstrap modal as the rest of Kandaga.
    window.Swal = {
        fire: function () {
            var opts = normalise(arguments);
            return show({
                title: opts.title || 'Kandaga',
                message: opts.text || opts.html || '',
                icon: opts.icon || opts.type || 'info',
                confirm: !!opts.showCancelButton,
                confirmLabel: opts.confirmButtonText || 'Ya, lanjutkan',
                cancelLabel: opts.cancelButtonText || 'Batal',
                danger: !!opts.dangerButton,
                confirmButtonColor: opts.confirmButtonColor,
                timer: opts.timer || 0,
                input: opts.input,
                inputPlaceholder: opts.inputPlaceholder,
                inputValue: opts.inputValue,
                inputValidator: opts.inputValidator
            });
        },
        close: function () { window.kandagaDialog.close(); }
    };
    window.swal = window.Swal;
})(window, document);
