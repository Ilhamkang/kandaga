<?php
require_once __DIR__.'/includes/request.php';
kandagaAuthorize(true);
if (!defined('KANDAGA_GOAH_PANEL')) {
    header('Location: '.BASE_URL.'/admin/index.php?page=metadata-importer&engine=catalog');
    exit;
}
?>
<section id="catalog-importer" data-page-title="Importer Katalog Cetak" data-endpoint="<?= htmlspecialchars(BASE_URL,ENT_QUOTES) ?>/admin/actions/catalog-import.php" data-base="<?= htmlspecialchars(BASE_URL,ENT_QUOTES) ?>" data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'],ENT_QUOTES) ?>">
 <div class="card"><div class="card-header admin-page-header"><h1><i class="fa-solid fa-file-csv me-2"></i>Importer Katalog Cetak</h1></div><div class="card-body">
  <p>Unggah CSV UTF-8, petakan kolom, lalu moderasi setiap entri. Unggah hanya menambah antrean. Data katalog berubah setelah keputusan Buat baru atau Merge.</p>
  <nav class="ki-steps mb-3" aria-label="Tahapan impor">
   <button class="ki-step is-active" type="button" data-step="upload" aria-current="step"><span>1</span><strong>Unggah CSV</strong><small>Pilih berkas &amp; sumber</small></button>
   <button class="ki-step" type="button" data-step="mapping" disabled><span>2</span><strong>Pemetaan &amp; pratinjau</strong><small>Periksa kolom metadata</small></button>
   <button class="ki-step" type="button" data-step="moderation"><span>3</span><strong>Moderasi</strong><small>Duplikat &amp; merge</small></button>
  </nav>
  <div id="ki-busy" hidden role="status" class="mb-2 text-primary"><i class="fa-solid fa-spinner fa-spin me-1"></i>Memuat data…</div>
  <div id="ki-message" role="status" aria-live="polite"></div>
  <div id="ki-setup" class="alert alert-warning" hidden>Jalankan <code>migrations/005_catalog_import.sql</code>, <code>migrations/013_catalog_structure_stage1.sql</code>, dan <code>migrations/018_web_import_batch_separation.sql</code> pada database aplikasi melalui phpMyAdmin, lalu muat ulang. Tabel katalog lama tidak diubah oleh migrasi ini.</div>
  <div id="ki-work" hidden>
   <div id="ki-upload-section">
   <form id="ki-upload">
   <div data-step-panel="upload">
    <h5 class="mb-3">1. Pilih CSV dan referensi sumber</h5>
    <div class="row g-2">
     <div class="mb-3 col-md-6"><label class="form-label" for="ki-file">CSV (koma, UTF-8; maksimal 10 MB / 5.000 entri)</label><div id="ki-dropzone" class="ki-dropzone"><i class="fa-solid fa-cloud-arrow-up mb-2"></i><p class="mb-2">Tarik CSV ke sini atau pilih berkas</p><input id="ki-file" class="form-control" type="file" accept=".csv,text/csv" required><small id="ki-file-info" class="d-block mt-2 text-muted">Belum ada berkas dipilih.</small></div></div>
     <div class="mb-3 col-md-6"><label class="form-label" for="ki-label">Nama katalog/sumber</label><input id="ki-label" class="form-control" maxlength="255" required value="Ekadjati (ed.), Naskah Sunda: Inventarisasi dan Pencatatan, 1988"></div>
     <div class="mb-3 col-md-6"><label class="form-label" for="ki-bib">Referensi bibliografi katalog</label><select id="ki-bib" class="form-select" required></select><small class="form-text text-muted">Tambahkan katalog melalui Daftar Pustaka terlebih dahulu jika belum tersedia.</small></div>
     <div class="mb-3 col-md-6"><label class="form-label" for="ki-profile">Gunakan pemetaan batch sebelumnya</label><select id="ki-profile" class="form-select"></select></div>
    </div>
    <button id="ki-inspect" type="button" class="btn btn-outline-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>Baca CSV</button>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/import-examples/template-katalog-cetak.csv" download><i class="fa-solid fa-download me-1"></i>Template</a>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/import-examples/ekadjati-1988-sampel.csv" download><i class="fa-solid fa-download me-1"></i>CSV contoh</a>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/docs/katalog-cetak-import.md" target="_blank" rel="noopener"><i class="fa-solid fa-book me-1"></i>Panduan kolom</a>
   </div>
   <div data-step-panel="mapping" hidden>
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><h5>2. Pemetaan kolom &amp; pratinjau</h5><span id="ki-mapping-status" class="badge bg-secondary"></span></div>
    <p class="text-muted small">Pilih padanan kolom CSV untuk setiap field. Informasi asli tetap disimpan dalam antrean.</p>
    <details class="mb-3" open><summary class="fw-bold">Pratinjau tiga entri pertama</summary><div id="ki-preview" class="table-responsive mt-2"></div></details>
    <div id="ki-mapping" class="mt-3"></div>
    <button id="ki-stage" type="submit" class="btn btn-primary mt-2" hidden><i class="fa-solid fa-upload me-1"></i>Simpan ke antrean</button>
    <button type="button" class="btn btn-outline-secondary mt-2" data-step="upload"><i class="fa-solid fa-arrow-left me-1"></i>Kembali ke berkas</button>
   </div>
   </form></div>
   <div data-step-panel="moderation" hidden>
   <h5 class="mb-3">3. Moderasi antrean impor</h5>
   <div id="ki-summary" class="ki-summary mb-3"></div>
   <div class="ki-progress mb-3"><div class="d-flex justify-content-between small mb-1"><span>Keputusan final dalam batch</span><span id="ki-progress-text">0%</span></div><div class="progress"><div id="ki-progress-bar" class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%"></div></div></div>
   <div class="row g-2 align-items-end"><div class="mb-3 col-md-8"><label class="form-label" for="ki-batch">Batch impor</label><select id="ki-batch" class="form-select"></select></div><div class="mb-3 col-md-4"><label class="form-label" for="ki-filter">Status moderasi</label><select id="ki-filter" class="form-select"><option value="all">Semua</option><option value="pending">Menunggu</option><option value="deferred">Ditunda</option><option value="created">Dibuat</option><option value="merged">Digabung</option><option value="rejected">Dilewati</option></select></div></div>
   <button id="ki-refresh" type="button" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa-solid fa-rotate me-1"></i>Muat ulang antrean</button>
    <button id="ki-delete-batch" type="button" class="btn btn-sm btn-outline-danger mb-3"><i class="fa-solid fa-trash me-1"></i>Hapus batch</button>
   <div id="ki-counts" class="mb-2 small"></div><div id="ki-queue" class="table-responsive"></div><div id="ki-pagination" class="d-flex justify-content-between align-items-center"></div>
   </div>
  </div>
 </div></div>
 <div class="modal fade" id="ki-modal" tabindex="-1" aria-labelledby="ki-modal-title" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="ki-modal-title">Moderasi entri katalog</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="ki-review-toolbar px-3 py-2 border-bottom"><button type="button" class="btn btn-sm btn-outline-secondary" data-review-section="top"><i class="fa-solid fa-file-lines me-1"></i>Sumber &amp; kandidat</button> <button type="button" class="btn btn-sm btn-outline-secondary" data-review-section="ki-edit-fields"><i class="fa-solid fa-table-columns me-1"></i>Perbandingan metadata</button> <button type="button" class="btn btn-sm btn-outline-secondary" data-review-section="ki-note"><i class="fa-solid fa-check me-1"></i>Keputusan</button></div><div class="modal-body" id="ki-review"></div><div class="modal-footer"><span id="ki-review-message" class="me-auto" role="status" aria-live="polite"></span><button type="button" id="ki-save" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Simpan keputusan</button></div></div></div></div>
</section><script src="<?= BASE_URL ?>/admin/assets/js/catalog-importer.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
