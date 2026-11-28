<?php
require_once __DIR__ . '/../config/bootstrap.php';
// 1. Memanggil config.php dari root
// Pastikan file kontak.php berada di dalam folder /pages/
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/captcha.php';
require_once __DIR__ . '/../includes/inbox-correspondence.php';
require_once __DIR__ . '/../includes/public-page-header.php';

$msg = '';
$showModal = false;
$old = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
$value = static function (string $key) use ($old): string {
    return htmlspecialchars(is_string($old[$key] ?? null) ? $old[$key] : '', ENT_QUOTES, 'UTF-8');
};

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_kontak'])) {
    // Validasi CSRF dan CAPTCHA
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $msg = '<div class="alert alert-danger"><i class="fa-solid fa-shield-halved me-2"></i>Sesi formulir berubah. Silakan muat ulang halaman.</div>';
    }
    // Validasi CAPTCHA: kode sekali pakai, termasuk setelah jawaban salah.
    elseif (!kandagaCaptchaVerify($_POST['captcha'] ?? null)) {
        $msg = '<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark me-2"></i> Kode verifikasi salah! Klik gambar untuk mendapat kode baru.</div>';
    }
    else {
        try {
            $values = kandagaContactValidate($_POST);
            $stmt = $conn->prepare("INSERT INTO tb_inbox (nama, email, subjek, topik, isi_pesan) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $values['nama'], $values['email'], $values['subjek'], $values['topik'], $values['isi_pesan']);
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
        body { background-color: var(--kandaga-surface); color: var(--kandaga-text); line-height: 1.7; }
        .content-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #e2e8f0; padding: 12px; }
        .btn-primary { border-radius: 10px; padding: 10px 20px; font-weight: 600; background-color: #0f766e; border: none; }
        .btn-primary:hover { background-color: #115e59; }
        .captcha-img { border: 1px solid #e2e8f0; border-radius: 10px; display: block; margin-bottom: 10px; cursor: pointer; max-width: 100%; }
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

<?php kandagaPublicHeader('Kontak Kami', 'fa-envelope', 'Sampaikan pertanyaan, usulan kerja sama, testimoni, atau saran dan kritik Anda kepada tim KANDAGA.'); ?>

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
                                <label for="contactNama">Nama Lengkap</label>
                                <input type="text" id="contactNama" name="nama" class="form-control" value="<?= $value('nama') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contactEmail">Email <span class="text-danger">*</span></label>
                                <input type="email" id="contactEmail" name="email" class="form-control" value="<?= $value('email') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contactTopik">Topik <span class="text-danger">*</span></label>
                                <select id="contactTopik" name="topik" class="form-select" required>
                                    <?php foreach (kandagaContactTopics() as $topic): ?>
                                        <option value="<?= htmlspecialchars($topic, ENT_QUOTES, 'UTF-8') ?>" <?= ($old['topik'] ?? 'Umum') === $topic ? 'selected' : '' ?>><?= htmlspecialchars($topic, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contactSubjek">Subjek <span class="text-danger">*</span></label>
                                <input type="text" id="contactSubjek" name="subjek" class="form-control" value="<?= $value('subjek') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="contactPesan">Isi Pesan</label>
                        <textarea id="contactPesan" name="isi_pesan" class="form-control" rows="4" required><?= $value('isi_pesan') ?></textarea>
                    </div>

                    <div class="visually-hidden" aria-hidden="true">
                        <label for="kandagaWebsite">Biarkan kolom ini kosong</label>
                        <input type="text" id="kandagaWebsite" name="kandaga_website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label for="contactCaptcha">Verifikasi Keamanan</label>
                        <img src="<?= BASE_URL ?>/modules/captcha.php" alt="Kode verifikasi berformat miring dan tidak beraturan" class="captcha-img" title="Klik gambar untuk kode baru" onclick="this.src='<?= BASE_URL ?>/modules/captcha.php?'+Math.random();">
                        <input type="text" id="contactCaptcha" name="captcha" class="form-control w-50" placeholder="6 karakter" autocomplete="off" required>
                        <small class="text-muted d-block mt-1">Ketik enam huruf/angka yang miring dengan posisi tidak teratur. Klik gambar jika sulit dibaca.</small>
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
