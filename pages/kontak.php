<?php
require_once __DIR__ . '/../config/bootstrap.php';
// 1. Memanggil config.php dari root
// Pastikan file kontak.php berada di dalam folder /pages/
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/inbox-correspondence.php';

$msg = '';
$showModal = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_kontak'])) {
    $captcha_user = strtoupper($_POST['captcha'] ?? '');
    $captcha_true = $_SESSION['captcha_code'] ?? '';

    // Validasi CSRF dan CAPTCHA
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $msg = '<div class="alert alert-danger"><i class="fa-solid fa-shield-halved me-2"></i>Sesi formulir berubah. Silakan muat ulang halaman.</div>';
    }
    // Validasi CAPTCHA
    elseif (empty($captcha_user) || $captcha_user !== $captcha_true) {
        $msg = '<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark me-2"></i> Kode verifikasi salah!</div>';
    }
    else {
        try {
            $values = kandagaContactValidate($_POST);
            $stmt = $conn->prepare("INSERT INTO tb_inbox (nama, email, subjek, isi_pesan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $values['nama'], $values['email'], $values['subjek'], $values['isi_pesan']);
            if (!$stmt->execute()) throw new RuntimeException('Pesan belum dapat disimpan.');
            $stmt->close(); unset($_SESSION['captcha_code']); $showModal = true;
        } catch (InvalidArgumentException $error) {
            $msg = '<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark me-2"></i>' . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        } catch (Throwable $error) {
            error_log('Kandaga contact save: ' . $error->getMessage());
            $msg = '<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Terjadi kesalahan sistem.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kontak - KANDAGA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f8fafc; color: #334155; line-height: 1.7; }
        .page-header { background: white; padding: 60px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 50px; }
        .content-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-control { border-radius: 10px; border: 1px solid #e2e8f0; padding: 12px; }
        .btn-primary { border-radius: 10px; padding: 10px 20px; font-weight: 600; background-color: #2563eb; border: none; }
        .captcha-img { border: 1px solid #e2e8f0; border-radius: 10px; display: block; margin-bottom: 10px; cursor: pointer; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>

<body class="kandaga-module kandaga-standalone-page kandaga-contact-page">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div class="text-success mb-3" style="font-size: 3rem;"><i class="fa-solid fa-circle-check"></i></div>
        <h5>Pesan Terkirim!</h5>
        <p>Terima kasih telah menghubungi kami. Pesan Anda telah kami terima.</p>
        <button type="button" class="btn btn-primary px-4" onclick="window.location.href='<?= BASE_URL ?>/index.php'">OK</button>
      </div>
    </div>
  </div>
</div>

<div class="page-header">
    <div class="container text-center">
        <h2 class="fw-bold">Kontak Kami</h2>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="content-card kandaga-prose">
                <?php echo $msg; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Subjek <span class="text-danger">*</span></label>
                        <input type="text" name="subjek" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Isi Pesan</label>
                        <textarea name="isi_pesan" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label>Verifikasi Keamanan</label>
                        <img src="<?= BASE_URL ?>/modules/captcha.php" alt="CAPTCHA" class="captcha-img" onclick="this.src='<?= BASE_URL ?>/modules/captcha.php?'+Math.random();">
                        <input type="text" name="captcha" class="form-control w-50" placeholder="6 karakter" required>
                    </div>

                    <button type="submit" name="submit_kontak" class="btn btn-primary w-100 mt-4">Kirim Pesan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php if ($showModal): ?>
<script>
    $(document).ready(function(){
        bootstrap.Modal.getOrCreateInstance(document.getElementById('successModal')).show();
    });
</script>
<?php endif; ?>

</body>
</html>
