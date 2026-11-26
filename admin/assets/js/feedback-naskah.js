(function ($) {
    'use strict';
    var root = $('#feedbackInbox'), content = $('#feedbackContent'), selected = null, pending = false;
    if (!root.length) return;
    function text(value) { return $('<span>').text(value == null ? '' : String(value)).html(); }
    function badges(total) { $('#feedbackUnread').text(total + ' belum dibaca'); $('#feedbackNavCount').text(total).toggleClass('d-none', total === 0); }
    function update(item, status, total) {
        var data = item.data('feedback'); data.status = status; item.data('feedback', data);
        item.toggleClass('unread', status === 'unread'); item.find('.email-name').toggleClass('bold', status === 'unread');
        item.find('.mail-icon').html(status === 'unread' ? '<i class="fa-solid fa-envelope"></i>' : '<i class="fa-regular fa-envelope-open"></i>'); badges(total);
    }
    function renderThread(packet) {
        var data = packet.feedback, meta = packet.meta || {}, replies = packet.replies || [], status = meta.thread_status || 'open';
        var detail = root.attr('data-detail') + encodeURIComponent(data.id_naskah || '');
        var html = '<div class="mail-subject">' + text(data.judul_naskah || 'Tanpa judul') + '</div><div class="mail-meta"><strong>' + text(data.nama) + '</strong><br>' + text(data.email) + '<br>Kode inventaris: ' + text(data.kode_inventaris || 'Belum tersedia') + '<br>' + text(data.created_at) + '<br><span class="badge bg-light border mt-2">Thread: ' + text(status) + '</span></div><div class="mail-message">' + text(data.isi_pesan) + '</div>';
        if (replies.length) {
            html += '<div class="feedback-replies"><h3 class="h6 mt-4 mb-2">Korespondensi</h3>';
            replies.forEach(function (reply) { var cls = reply.send_status === 'sent' ? 'is-sent' : 'is-failed'; html += '<article class="feedback-reply ' + cls + '"><div class="d-flex justify-content-between"><strong>' + text(reply.admin_username || 'Administrator') + '</strong><small>' + text(reply.created_at) + '</small></div><div class="small text-muted mb-2">' + text(reply.subject) + ' · ' + text(reply.send_status) + '</div><div>' + text(reply.body) + '</div>' + (reply.error_message ? '<div class="small text-danger mt-2">' + text(reply.error_message) + '</div>' : '') + '</article>'; });
            html += '</div>';
        }
        if (packet.mail_configured && packet.mail_enabled) {
            var defaultSubject = 'Re: [Kandaga] ' + (data.judul_naskah || 'Feedback naskah');
            html += '<form class="feedback-reply-form mt-4" id="feedbackReplyForm"><h3 class="h6 mb-2">Balas feedback</h3><label class="visually-hidden" for="replySubject">Subjek</label><input id="replySubject" class="form-control mb-2" maxlength="255" name="subject" value="' + text(defaultSubject) + '" required><label class="visually-hidden" for="replyBody">Isi balasan</label><textarea id="replyBody" class="form-control mb-2" name="body" rows="5" maxlength="10000" placeholder="Tulis balasan untuk ' + text(data.nama) + '..." required></textarea><button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-paper-plane me-1"></i>Kirim balasan</button></form>';
        } else {
            html += '<div class="alert alert-info mt-4">SMTP belum dikonfigurasi atau belum diuji. Buka <a href="index.php?page=pengaturan">Pengaturan → Email &amp; Notifikasi</a> untuk mengaktifkan balasan.</div>';
        }
        html += '<div class="action-buttons mt-3"><a class="btn-modern btn-read" target="_blank" rel="noopener" href="' + text(detail) + '"><i class="fa-solid fa-eye me-1"></i>Detail Naskah</a><button type="button" class="btn-modern btn-read" data-action="toggle"><i class="fa-solid fa-envelope me-1"></i>' + (data.status === 'read' ? 'Tandai belum dibaca' : 'Tandai sudah dibaca') + '</button><button type="button" class="btn-modern btn-read" data-action="' + (status === 'closed' ? 'reopen' : 'close') + '"><i class="fa-solid fa-' + (status === 'closed' ? 'folder-open' : 'check') + ' me-1"></i>' + (status === 'closed' ? 'Buka thread' : 'Tutup thread') + '</button><button type="button" class="btn-modern btn-delete" data-action="delete"><i class="fa-solid fa-trash me-1"></i>Hapus</button></div>';
        content.html(html);
    }
    function loadThread(item) {
        var data = item.data('feedback');
        $.ajax({url: root.attr('data-thread'), method: 'GET', dataType: 'json', data: {id: data.id_feedback}, cache: false}).done(function (res) { if (res.success) renderThread(res); else Swal.fire('Gagal', res.message || 'Thread gagal dimuat.', 'error'); }).fail(function () { Swal.fire('Gagal', 'Thread feedback gagal dimuat.', 'error'); });
    }
    function request(action, item) {
        if (pending) return; pending = true; var data = item.data('feedback'); root.find('button').prop('disabled', true);
        $.ajax({url: root.attr('data-action'), method: 'POST', dataType: 'json', headers: {'X-CSRF-Token': window.KANDAGA_CSRF || ''}, data: {id: data.id_feedback, action: action}}).done(function (res) { if (!res.success) { Swal.fire('Gagal', res.message || 'Permintaan gagal.', 'error'); return; } if (action === 'delete') { window.location.reload(); return; } if (res.feedback_status) update(item, res.feedback_status, res.total); loadThread(item); }).fail(function (xhr) { Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghubungi server. Coba kembali.', 'error'); }).always(function () { pending = false; root.find('button').prop('disabled', false); });
    }
    root.on('click', '.feedback-item', function () { if (pending) return; selected = $(this); root.find('.feedback-item').removeClass('active'); selected.addClass('active'); var data = selected.data('feedback'); if (data.status === 'unread') request('read', selected); else loadThread(selected); });
    content.on('submit', '#feedbackReplyForm', function (event) { event.preventDefault(); if (!selected || pending) return; var data = selected.data('feedback'), form = $(this); pending = true; form.find('button').prop('disabled', true); $.ajax({url: root.attr('data-reply'), method: 'POST', dataType: 'json', headers: {'X-CSRF-Token': window.KANDAGA_CSRF || ''}, data: {id_feedback: data.id_feedback, subject: form.find('[name=subject]').val(), body: form.find('[name=body]').val()}}).done(function (res) { if (res.success) { form[0].reset(); Swal.fire('Terkirim', res.message, 'success'); loadThread(selected); } else Swal.fire('Gagal', res.message || 'Balasan gagal dikirim.', 'error'); }).fail(function (xhr) { Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Balasan gagal dikirim.', 'error'); }).always(function () { pending = false; form.find('button').prop('disabled', false); }); });
    content.on('click', '[data-action]', function () { if (!selected || pending) return; var action = $(this).attr('data-action'), item = selected; if (action !== 'delete') { request(action, item); return; } Swal.fire({title: 'Hapus feedback?', text: 'Pesan dan riwayat balasannya akan dihapus permanen.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'}).then(function (result) { if (result.isConfirmed) request('delete', item); }); });
})(jQuery);
