(function($) {
    "use strict";

    $(document).ready(function(){
        // PERBAIKAN: Path relatif dari /admin/inbox.php ke /admin/actions/
        const ACTION_DIR = 'actions/';

        $('#searchInbox').on('keyup', function(){
            let val = $(this).val().toLowerCase();
            $('.email-item').each(function(){
                $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
            });
        });

        $(document).on('click', '.email-item', function(){
            $('.email-item').removeClass('active');
            $(this).addClass('active');

            let data = $(this).data('json');
            $('#email-content').html(`
                <div class="mail-subject">${data.subjek}</div>
                <div class="mail-meta"><strong>${data.nama}</strong><br>${data.email}</div>
                <div class="mail-message"><div>${data.pesan}</div></div>
                <div class="action-buttons">
                    <button type="button" class="btn-modern btn-read btn-toggle" data-id="${data.id}"><i class="fa-solid fa-envelope-open"></i> Toggle Read</button>
                    <button type="button" class="btn-modern btn-delete btn-delete-mail" data-id="${data.id}"><i class="fa-solid fa-trash"></i> Hapus</button>
                </div>
            `);

            if(data.status === 'unread'){
                $.post(ACTION_DIR + 'tandai_baca.php', { id: data.id, aksi: 'read' }, function(res){
                    if(res.success) updateUI($('#email-' + data.id), 'read', res.total);
                }, 'json');
            }
        });

        $(document).on('click', '.btn-toggle', function(e){
            e.stopPropagation();
            let id = $(this).data('id');
            $.post(ACTION_DIR + 'tandai_baca.php', { id: id, aksi: 'toggle' }, function(res){
                if(res.success) updateUI($('#email-' + id), res.status, res.total);
            }, 'json');
        });

        $(document).on('click', '.btn-delete-mail', function(e){
            e.stopPropagation();
            let id = $(this).data('id');
            if(!confirm('Hapus pesan ini?')) return;
            $.post(ACTION_DIR + 'hapus-pesan.php', { id: id }, function(res){
                if(res.success){
                    $('#email-' + id).fadeOut(200, function(){ $(this).remove(); });
                    updateBadge(res.total);
                    $('#email-content').html('<div class="empty-state"><p>Pesan dihapus.</p></div>');
                }
            }, 'json');
        });
    });

    window.updateUI = function(item, status, total){
        item.attr('data-status', status);
        let name = item.find('.email-name');
        if(status === 'read'){
            item.removeClass('unread');
            name.removeClass('bold');
            item.find('.mail-icon').html('<i class="fa-regular fa-envelope-open text-secondary"></i>');
        } else {
            item.addClass('unread');
            name.addClass('bold');
            item.find('.mail-icon').html('<i class="fa-solid fa-envelope text-primary"></i>');
        }
        window.updateBadge(total);
    }

    window.updateBadge = function(total){
        if(total > 0) $('#badge-area').html('<span class="badge bg-primary p-2">' + total + ' unread</span>');
        else $('#badge-area').html('<small class="text-muted">Semua dibaca</small>');
    }
})(jQuery);