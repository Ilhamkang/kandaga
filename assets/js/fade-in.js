    $(document).ready(function() {
        // 1. Jalankan efek fade-in saat halaman pertama kali dibuka
        $('#dynamic-content').addClass('fade-in');

        // 2. Efek loading saat klik menu
        $('a').on('click', function() {
            var href = $(this).attr('href');
            if (href && href.includes('index.php?p=')) {
                // Tampilkan loader
                $('#kandaga-loader').css('display', 'flex');
                
                // Hilangkan efek fade-in (transisi keluar) agar halus
                $('#dynamic-content').removeClass('fade-in');
            }
        });
    });