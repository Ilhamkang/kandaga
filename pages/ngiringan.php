<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kontributor - KANDAGA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-login.css">
    <script src="<?= BASE_URL ?>/admin/assets/js/sweetalert2.min.js"></script>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module">

<div class="login-card">
    <img src="<?= BASE_URL ?>/assets/images/favicon.png" alt="Logo" class="brand-logo">
    <h5 class="text-center mb-4" style="color:#0f2042; font-weight:800;">Daftar Kontributor</h5>

    <form id="registerForm">
        <div class="mb-3">
            <label>Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-user"></i></span>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" required autocomplete="off">
            </div>
        </div>

        <div class="mb-3">
            <label>Kata Sandi</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                <input type="password" name="password" id="passwordField" class="form-control" required autocomplete="off">

                    <span class="input-group-text"><i class="fa fa-eye" id="togglePassword" style="cursor:pointer;"></i></span>

            </div>
        </div>

        <div class="mb-3">
            <label>Konfirmasi Kata Sandi</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                <input type="password" name="password_confirm" id="passwordConfirm" class="form-control" required autocomplete="off">
            </div>
        </div>

        <div class="mb-3">
            <label>Verifikasi Keamanan</label>
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fa fa-shield"></i></span>
                <input type="text" name="captcha" id="captchaInput" class="form-control" placeholder="Masukkan kode" required autocomplete="off">
            </div>
            <div class="text-center">
                <img src="<?= BASE_URL ?>/modules/captcha.php" id="captchaImage" style="cursor:pointer; border-radius:5px; border:1px solid #ccc;" title="Klik untuk refresh kode">
                <p class="text-muted" style="font-size: 10px; margin-top:5px;">Klik gambar untuk ganti kode</p>
            </div>
        </div>

        <button type="submit" class="btn btn-login" id="btnSubmit">DAFTAR SEKARANG</button>
    </form>

    <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/login.php" class="text-decoration-none" style="color:#0f2042; font-size:0.8rem;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
        </a>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. Toggle Password
    $('#togglePassword').on('click', function() {
        const password = $('#passwordField');
        const type = password.attr('type') === 'password' ? 'text' : 'password';
        password.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // 2. Refresh Captcha
    $('#captchaImage').on('click', function() {
        // Tambahkan timestamp agar gambar tidak ter-cache oleh browser
        $(this).attr('src', '<?= BASE_URL ?>/modules/captcha.php?' + new Date().getTime());
    });

    // 3. AJAX Register
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();

        if ($('#passwordField').val() !== $('#passwordConfirm').val()) {
            Swal.fire('Oops!', 'Konfirmasi kata sandi tidak cocok!', 'warning');
            return;
        }

        var btn = $('#btnSubmit');

        $.ajax({
            url: '<?= BASE_URL ?>/actions/proses-registrasi.php', // Sesuaikan path ini
            type: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> MEMPROSES...');
            },
            success: function(res) {
                // Parsing jika res berbentuk string JSON
                var data = (typeof res === 'string') ? JSON.parse(res) : res;

                if (data.success) {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => {
                        window.location.href = '<?= BASE_URL ?>/login.php';
                    });
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                    btn.prop('disabled', false).html('DAFTAR SEKARANG');
                    // Refresh captcha otomatis jika salah
                    $('#captchaImage').click();
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server.', 'error');
                btn.prop('disabled', false).html('DAFTAR SEKARANG');
            }
        });
    });
</script>
</body>
</html>