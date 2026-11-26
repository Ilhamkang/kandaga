<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/manuscript-feedback.php';
header('Cache-Control: no-store');
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
if (!$id) { http_response_code(404); exit('Naskah tidak ditemukan.'); }
$stmt = $conn->prepare('SELECT id, judul, kode_inventaris FROM katalog WHERE id = ?');
$stmt->bind_param('i', $id); $stmt->execute();
$naskah = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$naskah) { http_response_code(404); exit('Naskah tidak ditemukan.'); }
$detailUrl = BASE_URL . '/detail.php?id=' . $id;
$formUrl = BASE_URL . '/pages/lapor-naskah.php?id=' . $id;
$values = ['nama' => '', 'email' => '', 'isi_pesan' => ''];
$error = ''; $ready = false;
try { $ready = kandagaFeedbackReady($conn); }
catch (Throwable $e) { error_log('Kandaga feedback readiness: ' . $e->getMessage()); }
if (!$ready) $error = 'Form masukan belum tersedia. Silakan coba kembali nanti.';
$showThanks = !empty($_SESSION['feedback_success'][$id]);
unset($_SESSION['feedback_success'][$id]);
// Separate per-manuscript tokens support several open manuscript tabs.
if (empty($_SESSION['feedback_forms'][$id])) $_SESSION['feedback_forms'][$id] = bin2hex(random_bytes(32));
$nonce = $_SESSION['feedback_forms'][$id];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $showThanks = false;
    foreach (array_keys($values) as $key) $values[$key] = kandagaFeedbackInput($_POST, $key);
    try {
        if (!$ready) throw new InvalidArgumentException('Form masukan belum tersedia. Silakan coba kembali nanti.');
        $csrf = kandagaFeedbackInput($_POST, 'csrf_token');
        if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
            http_response_code(403); throw new InvalidArgumentException('Sesi formulir berubah. Muat ulang halaman lalu coba kembali.');
        }
        if (!hash_equals($nonce, kandagaFeedbackInput($_POST, 'submission_token'))) {
            throw new InvalidArgumentException('Formulir sudah dikirim atau kedaluwarsa. Muat ulang halaman untuk mengirim masukan baru.');
        }
        if (time() - (int)($_SESSION['feedback_last_sent'] ?? 0) < 60) {
            throw new InvalidArgumentException('Mohon tunggu satu menit sebelum mengirim masukan berikutnya.');
        }
        $values = kandagaFeedbackValidate($_POST);
        $captcha = strtoupper(kandagaFeedbackInput($_POST, 'captcha'));
        if ($captcha === '' || empty($_SESSION['captcha_code']) || !hash_equals($_SESSION['captcha_code'], $captcha)) {
            throw new InvalidArgumentException('Kode verifikasi tidak sesuai. Silakan coba lagi.');
        }
        // Title/code are authoritative snapshots, never trusted from form fields.
        $judul = (string)($naskah['judul'] ?? '');
        $kode = (string)($naskah['kode_inventaris'] ?? '');
        $stmt = $conn->prepare('INSERT INTO tb_naskah_feedback (id_naskah, judul_naskah, kode_inventaris, nama, email, isi_pesan, submission_token) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssss', $id, $judul, $kode, $values['nama'], $values['email'], $values['isi_pesan'], $nonce);
        $stmt->execute(); $stmt->close();
        unset($_SESSION['feedback_forms'][$id], $_SESSION['captcha_code']);
        $_SESSION['feedback_last_sent'] = time();
        $_SESSION['feedback_success'][$id] = true;
        header('Location: ' . $formUrl, true, 303); exit;
    } catch (InvalidArgumentException $e) { $error = $e->getMessage(); }
      catch (Throwable $e) {
        error_log('Kandaga feedback save: ' . $e->getMessage());
        $error = 'Masukan belum dapat disimpan. Silakan coba lagi; pesan Anda tetap ada di formulir.';
      }
}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lapor / Beri Masukan Naskah - KANDAGA</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
<?php include __DIR__ . '/../includes/module-head.php'; ?>
<style>body{background:#f8fafc}.feedback-card{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px #00000008}.feedback-summary{background:#f1f5f9;border-radius:12px;padding:1rem;overflow-wrap:anywhere}.feedback-card textarea{min-height:180px}@media(max-width:575px){.feedback-card{padding:1.2rem}}</style>
</head><body class="kandaga-module kandaga-standalone-page kandaga-report-page">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">
<a class="btn btn-light border mb-3" href="<?= kandagaFeedbackEscape($detailUrl) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke detail naskah</a>
<div class="feedback-card"><h1 class="h3">Lapor / Beri Masukan Naskah</h1>
<p class="text-muted">Laporkan kekeliruan atau sampaikan tambahan informasi tentang naskah ini. Masukan dikirim kepada admin dan tidak ditampilkan kepada publik.</p>
<div class="feedback-summary mb-4"><strong><?= kandagaFeedbackEscape($naskah['judul'] ?: 'Tanpa judul') ?></strong><br>Kode inventaris: <?= kandagaFeedbackEscape($naskah['kode_inventaris'] ?: 'Belum tersedia') ?></div>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= kandagaFeedbackEscape($error) ?></div><?php endif; ?>
<?php if ($ready): ?>
<form method="post" action="<?= kandagaFeedbackEscape($formUrl) ?>">
<input type="hidden" name="csrf_token" value="<?= kandagaFeedbackEscape($_SESSION['csrf_token']) ?>">
<input type="hidden" name="submission_token" value="<?= kandagaFeedbackEscape($nonce) ?>">
<div class="row g-2"><div class="mb-3 col-md-6"><label for="feedback-name">Nama</label><input id="feedback-name" class="form-control" name="nama" autocomplete="name" maxlength="150" required value="<?= kandagaFeedbackEscape($values['nama']) ?>"></div>
<div class="mb-3 col-md-6"><label for="feedback-email">Alamat email</label><input id="feedback-email" type="email" class="form-control" name="email" autocomplete="email" maxlength="254" required value="<?= kandagaFeedbackEscape($values['email']) ?>"></div></div>
<div class="mb-3"><label for="feedback-message">Isi pesan</label><textarea id="feedback-message" class="form-control" name="isi_pesan" maxlength="10000" required rows="7"><?= kandagaFeedbackEscape($values['isi_pesan']) ?></textarea><small class="form-text text-muted">Maksimal 10.000 karakter. Nama dan email hanya dapat dilihat admin.</small></div>
<div class="mb-3"><label for="feedback-captcha">Kode verifikasi</label><div class="d-flex align-items-center mb-2"><img id="feedback-captcha-image" src="<?= BASE_URL ?>/modules/captcha.php" alt="Kode verifikasi" width="130" height="45"><button id="feedback-captcha-refresh" class="btn btn-light border ms-2" type="button" aria-label="Ganti kode verifikasi"><i class="fa-solid fa-rotate-right"></i></button></div><input id="feedback-captcha" name="captcha" class="form-control" autocomplete="off" maxlength="6" required placeholder="Masukkan 6 karakter pada gambar"></div>
<button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-paper-plane me-2"></i>Kirim Masukan</button>
</form><?php endif; ?></div></div></div></main>
<?php if ($showThanks): ?>
<div class="modal fade" id="feedbackThanks" tabindex="-1" role="dialog" aria-labelledby="feedbackThanksTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><div class="modal-body text-center p-4"><i class="fa-solid fa-circle-check text-success mb-3" style="font-size:3rem" aria-hidden="true"></i><h2 class="h4" id="feedbackThanksTitle">Terima kasih atas masukan Anda!</h2><p>Pesan Anda telah diterima dan akan ditinjau oleh admin.</p><a class="btn btn-primary" href="<?= kandagaFeedbackEscape($detailUrl) ?>">Lanjutkan penjelajahan</a></div></div></div></div>
<noscript><div class="container alert alert-success">Terima kasih! Masukan Anda sudah diterima. <a href="<?= kandagaFeedbackEscape($detailUrl) ?>">Lanjutkan penjelajahan</a>.</div></noscript>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script><script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>document.getElementById('feedback-captcha-refresh')?.addEventListener('click',function(){document.getElementById('feedback-captcha-image').src=<?= json_encode(BASE_URL . '/modules/captcha.php?', JSON_HEX_TAG | JSON_HEX_AMP) ?>+Date.now();});<?php if ($showThanks): ?>bootstrap.Modal.getOrCreateInstance(document.getElementById('feedbackThanks')).show();<?php endif; ?></script>
</body></html>
