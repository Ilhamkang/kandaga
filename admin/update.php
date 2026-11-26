<?php
require_once __DIR__.'/includes/request.php';kandagaAuthorize(true);
if(!defined('KANDAGA_GOAH_PANEL')){header('Location: '.BASE_URL.'/admin/index.php?page=update');exit;}
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/update-center.css?v=3.1.65">
<div id="updateCenter" data-api="<?= BASE_URL ?>/admin/actions/update-center.php" data-recovery="<?= BASE_URL ?>/admin/recover-update.php">
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4"><div><h1 class="h3"><i class="fa-solid fa-arrows-rotate mr-2"></i>Pusat Pembaruan</h1><p class="text-muted mb-0">Versi terpasang <strong id="updateVersion">—</strong> · Kandaga Goah</p></div><button class="btn btn-light border" id="refreshUpdates"><i class="fa-solid fa-rotate-right mr-2"></i>Muat ulang</button></div>
<div id="updateNotice" role="status" aria-live="polite"></div>
<div id="updateDiagnostics" role="status" aria-live="polite"></div>
<div id="updateSetup" class="card p-3 mb-3" hidden><h2 class="h5">Siapkan pusat pembaruan</h2><p>Membuat catatan migrasi dan memeriksa struktur database yang sudah tersedia. Data katalog tetap dipertahankan.</p><button class="btn btn-primary align-self-start" id="setupUpdates"><i class="fa-solid fa-wrench mr-2"></i>Siapkan Sekarang</button></div>
<div class="row"><div class="col-lg-6 mb-3"><div class="card h-100 p-4"><h2 class="h5"><i class="fa-solid fa-cloud-arrow-down mr-2"></i>Repositori Kandaga</h2><p class="text-muted">Periksa pembaruan dari kanal rilis GitHub.</p><button id="checkRemote" class="btn btn-outline-primary align-self-start"><i class="fa-solid fa-magnifying-glass mr-2"></i>Periksa Versi</button><div id="remoteResult" class="mt-3"></div></div></div>
<div class="col-lg-6 mb-3"><div class="card h-100 p-4"><h2 class="h5"><i class="fa-solid fa-file-zipper mr-2"></i>Upload Paket CMS</h2><p class="text-muted">Pilih ZIP lengkap Kandaga. Versi dan perubahan diperiksa sebelum pemasangan.</p><form id="packageUpload"><label class="sr-only" for="packageFile">Paket ZIP Kandaga</label><input id="packageFile" name="package" class="form-control-file mb-3" type="file" accept=".zip" required><button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload mr-2"></i>Upload & Periksa</button></form><small id="uploadLimit" class="text-muted mt-2"></small></div></div></div>
<div class="card p-4 mb-3"><div class="d-flex justify-content-between flex-wrap"><h2 class="h5"><i class="fa-solid fa-database mr-2"></i>Kesiapan Database <small id="updateDatabase" class="text-muted"></small></h2><button id="schemaUpdate" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-list-check mr-2"></i>Periksa & Lengkapi</button></div><div id="migrationList" class="mt-2"></div></div>
<div id="updatePreview" class="card p-4 mb-3" hidden></div>
<div id="updateProgress" class="card p-4 mb-3" hidden></div>
<div class="card p-4"><h2 class="h5"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Riwayat & Backup</h2><p class="small text-muted">Backup dan status pekerjaan tersimpan di server. Browser hanya memantau proses; jika koneksi terputus, buka kembali halaman ini untuk melanjutkan otomatis.</p><div id="updateHistory"></div></div>
</div>
<script src="<?= BASE_URL ?>/admin/assets/js/update-center.js?v=3.1.65"></script>
