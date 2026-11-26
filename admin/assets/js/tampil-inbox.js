(function ($) {
    'use strict';
    var root = $('#contactInbox'), content = $('#email-content'), selected = null, pending = false;
    if (!root.length) return;
    function text(value) { return $('<span>').text(value == null ? '' : String(value)).html(); }
    function updateBadge(total) { $('#badge-area').html(total > 0 ? '<span class="badge bg-primary p-2">' + total + ' belum dibaca</span>' : '<small class="text-muted">Semua dibaca</small>'); }
    function updateRow(item, status, total) {
        var data = item.data('json'); data.status = status; item.data('json', data); item.toggleClass('unread', status === 'unread'); item.find('.email-name').toggleClass('bold', status === 'unread'); item.find('.mail-icon').html(status === 'unread' ? '<i class="fa-solid fa-envelope text-primary"></i>' : '<i class="fa-regular fa-envelope-open text-secondary"></i>'); updateBadge(total);
    }
    function render(packet) {
        var data = packet.inbox_message || {}, meta = packet.meta || {}, replies = packet.replies || [], status = meta.thread_status || 'open';
        var html = '<div class="mail-subject">' + text(data.subjek || 'Tanpa subjek') + '</div><div class="mail-meta"><strong>' + text(data.nama) + '</strong><br>' + text(data.email) + '<br>' + text(data.created_at) + '<br><span class="badge bg-light text-dark border mt-2">Thread: ' + text(status) + '</span></div><div class="mail-message"><div>' + text(data.isi_pesan).replace(/\n/g, '<br>') + '</div></div>';
        if (replies.length) {
            html += '<div class="inbox-correspondence-replies"><h3 class="h6 mt-4 mb-2">Korespondensi</h3>';
            replies.forEach(function (reply) { var cls = reply.send_status === 'sent' ? 'is-sent' : 'is-failed'; html += '<article class="inbox-correspondence-reply ' + cls + '"><div class="d-flex justify-content-between"><strong>' + text(reply.admin_username || 'Administrator') + '</strong><small>' + text(reply.created_at) + '</small></div><div class="small text-muted mb-2">' + text(reply.subject) + ' · ' + text(reply.send_status) + '</div><div>' + text(reply.body).replace(/\n/g, '<br>') + '</div>' + (reply.error_message ? '<div class="small text-danger mt-2">' + text(reply.error_message) + '</div>' : '') + '</article>'; });
            html += '</div>';
        }
        if (packet.mail_configured && packet.mail_enabled) {
            var defaultSubject = 'Re: ' + (data.subjek || 'Pesan dari Kandaga');
            html += '<form class="inbox-reply-form mt-4" id="inboxReplyForm"><h3 class="h6 mb-2">Balas pesan</h3><input class="form-control mb-2" maxlength="255" name="subject" value="' + text(defaultSubject) + '" required aria-label="Subjek balasan"><textarea class="form-control mb-2" name="body" rows="5" maxlength="10000" placeholder="Tulis balasan untuk ' + text(data.nama) + '..." required aria-label="Isi balasan"></textarea><button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-paper-plane me-1"></i>Kirim balasan</button></form>';
        } else {
            html += '<div class="alert alert-info mt-4">SMTP belum aktif. Buka <a href="index.php?page=pengaturan">Pengaturan → Email &amp; Notifikasi</a> untuk mengaktifkan balasan.</div>';
        }
        html += '<div class="action-buttons mt-3"><button type="button" class="btn-modern btn-read" data-action="toggle"><i class="fa-solid fa-envelope me-1"></i>' + (data.status === 'read' ? 'Tandai belum dibaca' : 'Tandai sudah dibaca') + '</button><button type="button" class="btn-modern btn-read" data-action="' + (status === 'closed' ? 'reopen' : 'close') + '"><i class="fa-solid fa-' + (status === 'closed' ? 'folder-open' : 'check') + ' me-1"></i>' + (status === 'closed' ? 'Buka thread' : 'Tutup thread') + '</button><button type="button" class="btn-modern btn-delete btn-delete-mail" data-action="delete"><i class="fa-solid fa-trash me-1"></i>Hapus</button></div>';
        content.html(html);
    }
    function loadThread(item) {
        var data = item.data('json');
        $.ajax({url: root.attr('data-thread'), method: 'GET', dataType: 'json', data: {id: data.id}, cache: false}).done(function (res) { if (res.success) render(res); else Swal.fire('Gagal', res.message || 'Thread gagal dimuat.', 'error'); }).fail(function () { Swal.fire('Gagal', 'Thread inbox gagal dimuat.', 'error'); });
    }
    function statusRequest(action, item) {
        if (pending) return; pending = true; var data = item.data('json'); root.find('button').prop('disabled', true);
        $.ajax({url: root.attr('data-status-action'), method: 'POST', dataType: 'json', headers: {'X-CSRF-Token': window.KANDAGA_CSRF || ''}, data: {id: data.id, aksi: action}}).done(function (res) { if (!res.success) { Swal.fire('Gagal', res.message || 'Permintaan gagal.', 'error'); return; } if (res.status) updateRow(item, res.status, res.total); loadThread(item); }).fail(function (xhr) { Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghubungi server.', 'error'); }).always(function () { pending = false; root.find('button').prop('disabled', false); });
    }
    function deleteMessage(item) {
        var data = item.data('json'); pending = true; root.find('button').prop('disabled', true);
        $.ajax({url: root.attr('data-delete-action'), method: 'POST', dataType: 'json', headers: {'X-CSRF-Token': window.KANDAGA_CSRF || ''}, data: {id: data.id}}).done(function (res) { if (!res.success) { Swal.fire('Gagal', res.message || 'Pesan gagal dihapus.', 'error'); return; } item.remove(); updateBadge(res.total); content.html('<div class="empty-state"><p>Pesan dihapus.</p></div>'); selected = null; }).fail(function (xhr) { Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghubungi server.', 'error'); }).always(function () { pending = false; root.find('button').prop('disabled', false); });
    }
    root.on('click', '.email-item', function () { if (pending) return; selected = $(this); root.find('.email-item').removeClass('active'); selected.addClass('active'); var data = selected.data('json'); if (data.status === 'unread') statusRequest('read', selected); else loadThread(selected); });
    content.on('submit', '#inboxReplyForm', function (event) { event.preventDefault(); if (!selected || pending) return; var data = selected.data('json'), form = $(this); pending = true; form.find('button').prop('disabled', true); $.ajax({url: root.attr('data-reply'), method: 'POST', dataType: 'json', headers: {'X-CSRF-Token': window.KANDAGA_CSRF || ''}, data: {id_inbox: data.id, subject: form.find('[name=subject]').val(), body: form.find('[name=body]').val()}}).done(function (res) { if (res.success) { form[0].reset(); Swal.fire('Terkirim', res.message, 'success'); loadThread(selected); } else Swal.fire('Gagal', res.message || 'Balasan gagal dikirim.', 'error'); }).fail(function (xhr) { Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Balasan gagal dikirim.', 'error'); }).always(function () { pending = false; form.find('button').prop('disabled', false); }); });
    content.on('click', '[data-action]', function () { if (!selected || pending) return; var action = $(this).attr('data-action'); if (action === 'delete') { Swal.fire({title: 'Hapus pesan?', text: 'Pesan dan riwayat balasannya akan dihapus permanen.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'}).then(function (result) { if (result.isConfirmed) deleteMessage(selected); }); } else statusRequest(action, selected); });
})(jQuery);
