<?php
require_once __DIR__ . '/../includes/request.php';
kandagaAuthorize(true);
if (!defined('KANDAGA_GOAH_PANEL')) {
    header('Location: ' . BASE_URL . '/admin/index.php?page=metadata-importer&engine=web');
    exit;
}
?>
<section id="dreamsea-importer" data-page-title="Importer Web" data-endpoint="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>/admin/actions/dreamsea-import.php" data-base="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>" data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
  <div class="card"><div class="card-header admin-page-header"><h1><i class="fa-solid fa-cloud-arrow-down me-2"></i>Importer Web</h1></div><div class="card-body">
    <p>Unggah CSV UTF-8 hasil scraping web, periksa pratinjau, lalu moderasi setiap entri. Antrean Importer Web terpisah dari Importer Katalog Cetak. Unggah hanya menambah antrean; data katalog berubah setelah keputusan Buat baru atau Merge.</p>
    <nav class="ds-steps mb-3" aria-label="Tahapan impor web">
      <button class="ds-step is-active" type="button" data-step="upload" aria-current="step"><span>1</span><strong>Unggah CSV</strong><small>Berkas &amp; identitas</small></button>
      <button class="ds-step" type="button" data-step="preview" disabled><span>2</span><strong>Pratinjau</strong><small>Validasi awal</small></button>
      <button class="ds-step" type="button" data-step="moderation"><span>3</span><strong>Moderasi</strong><small>Duplikat &amp; master</small></button>
    </nav>
    <div id="ds-busy" hidden role="status" class="mb-2 text-primary"><i class="fa-solid fa-spinner fa-spin me-1"></i>Memuat data…</div>
    <div id="ds-message" role="status" aria-live="polite"></div>
    <div id="ds-setup" class="alert alert-warning" hidden>Jalankan <code>migrations/005_catalog_import.sql</code>, <code>migrations/013_catalog_structure_stage1.sql</code>, dan <code>migrations/018_web_import_batch_separation.sql</code> pada database aplikasi, lalu muat ulang.</div>
    <div id="ds-work" hidden>
      <div data-step-panel="upload">
        <h5 class="mb-3">1. Pilih CSV dari sumber web</h5>
        <div class="row g-2">
          <div class="mb-3 col-md-7"><label class="form-label" for="ds-file">CSV (koma, UTF-8; maksimal 10 MB / 5.000 entri)</label><div id="ds-dropzone" class="ds-dropzone"><i class="fa-solid fa-cloud-arrow-up mb-2"></i><p class="mb-2">Tarik CSV ke sini atau pilih berkas</p><input id="ds-file" class="form-control" type="file" accept=".csv,text/csv" required><small id="ds-file-info" class="d-block mt-2 text-muted">Belum ada berkas dipilih.</small></div></div>
          <div class="mb-3 col-md-5"><label class="form-label" for="ds-label">Nama sumber/batch</label><input id="ds-label" class="form-control" maxlength="255" value="Web — Indramayu (Jawa Barat)"><small class="form-text text-muted">Gunakan nama sumber/kota/proyek agar batch mudah dikenali.</small></div>
        </div>
        <button id="ds-inspect" type="button" class="btn btn-outline-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>Baca CSV</button>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/import-examples/dreamsea-normalized-template.csv" download><i class="fa-solid fa-download me-1"></i>Template CSV</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/docs/web-import-csv.md" target="_blank" rel="noopener"><i class="fa-solid fa-book me-1"></i>Panduan format CSV</a>
      </div>
      <div data-step-panel="preview" hidden>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><h5>2. Pratinjau validasi</h5><span id="ds-preview-status" class="badge bg-secondary"></span></div>
        <p class="text-muted small">Tiga entri pertama dari CSV. Error di sini harus diperbaiki sebelum masuk antrean.</p>
        <div id="ds-preview" class="table-responsive mb-3"></div>
        <button id="ds-stage" type="button" class="btn btn-primary"><i class="fa-solid fa-upload me-1"></i>Simpan ke antrean</button>
        <button type="button" class="btn btn-outline-secondary" data-step="upload"><i class="fa-solid fa-arrow-left me-1"></i>Kembali ke berkas</button>
      </div>
      <div data-step-panel="moderation" hidden>
        <h5 class="mb-3">3. Moderasi antrean</h5>
        <div id="ds-summary" class="ds-summary mb-3"></div>
        <div class="ds-progress mb-3"><div class="d-flex justify-content-between small mb-1"><span>Keputusan final dalam batch</span><span id="ds-progress-text">0%</span></div><div class="progress"><div id="ds-progress-bar" class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%"></div></div></div>
        <div class="row g-2 align-items-end"><div class="mb-3 col-md-8"><label class="form-label" for="ds-batch">Batch impor</label><select id="ds-batch" class="form-select"></select></div><div class="mb-3 col-md-4"><label class="form-label" for="ds-filter">Status</label><select id="ds-filter" class="form-select"><option value="all">Semua</option><option value="pending">Menunggu</option><option value="deferred">Ditunda</option><option value="created">Dibuat</option><option value="merged">Digabung</option><option value="rejected">Dilewati</option></select></div></div>
        <button id="ds-refresh" type="button" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa-solid fa-rotate me-1"></i>Muat ulang antrean</button>
        <button id="ds-delete-batch" type="button" class="btn btn-sm btn-outline-danger mb-3"><i class="fa-solid fa-trash me-1"></i>Hapus batch</button>
        <div id="ds-counts" class="mb-2 small"></div>
        <div id="ds-queue" class="table-responsive"></div>
        <div id="ds-pagination" class="d-flex justify-content-between align-items-center"></div>
      </div>
    </div>
  </div></div>
  <div class="modal fade" id="ds-modal" tabindex="-1" aria-labelledby="ds-modal-title" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="ds-modal-title">Moderasi entri web</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body" id="ds-review"></div>
    <div class="modal-footer"><span id="ds-review-message" class="me-auto" role="status" aria-live="polite"></span><button type="button" id="ds-save" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Simpan keputusan</button></div>
  </div></div></div>
</section><script src="<?= BASE_URL ?>/admin/assets/js/dreamsea-importer.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
