<?php
/**
 * login.php - Katalog Naskah Sunda
 */

// Panggil config dari root
require_once 'config.php';

// Proteksi: Jika user sudah login, arahkan ke dashboard
if (isset($_SESSION['status']) && $_SESSION['status'] === "login") {
    header("Location: " . BASE_URL . "/admin/index.php");
    exit();
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logika Pesan Error dari URL
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';
$alert_text = '';

if ($pesan === 'gagal') {
    $alert_text = 'Username atau kata sandi salah.';
} elseif ($pesan === 'Akun diblokir.') {
    $alert_text = 'Akun Anda diblokir. Silakan hubungi admin.';
} elseif (!empty($pesan)) {
    $alert_text = htmlspecialchars($pesan);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Goah KANDAGA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-login.css">
<?php include __DIR__ . '/includes/module-head.php'; ?>
</head>
<body class="kandaga-module">

<div class="login-card">
    <img src="<?= BASE_URL ?>/assets/images/favicon.png" alt="Logo" class="brand-logo">
    <h5 class="text-center mb-4" style="color:#0f2042; font-weight:800;">Katalog Naskah Sunda</h5>

    <?php if (!empty($alert_text)): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            <small><i class="fa fa-exclamation-circle me-1"></i> <?php echo $alert_text; ?></small>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form id="loginForm" action="<?= BASE_URL ?>/actions/proses-login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="mb-3">
            <label>Username</label>
            <div class="input-group">

                    <span class="input-group-text"><i class="fa fa-user"></i></span>

                <input type="text" name="username" class="form-control" required autofocus autocomplete="off">
            </div>
        </div>

        <div class="mb-3">
            <label>Kata Sandi</label>
            <div class="input-group">

                    <span class="input-group-text"><i class="fa fa-lock"></i></span>

                <input type="password" name="password" id="passwordField" class="form-control" required autocomplete="off">

                    <span class="input-group-text">
                        <i class="fa fa-eye" id="togglePassword" style="cursor:pointer;"></i>
                    </span>

            </div>
        </div>

        <button type="submit" class="btn btn-login" id="btnSubmit">MASUK</button>
    </form>

    <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/" class="text-decoration-none" style="color:#0f2042; font-size:0.8rem;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog
        </a>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>

<script>
    // Toggle Password Visibility
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#passwordField');

    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });

    // Form Submit Guard
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        var btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> MEMPROSES...';
    });
</script>
</body>
</html>