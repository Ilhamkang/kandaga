<?php
require_once __DIR__ . '/../includes/request.php';
kandagaAuthorize(true);
if (!defined('KANDAGA_GOAH_PANEL')) {
    header('Location: ' . BASE_URL . '/admin/index.php?page=metadata-importer&engine=web');
    exit;
}
?>
<section id="dreamsea-importer" data-endpoint="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>/admin/actions/dreamsea-import.php" data-base="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>" data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
  <div class="card"><div class="card-header"><h4 class="mb-0"><i class="fa-solid fa-cloud-arrow-down me-2"></i>Importer Web</h4></div><div class="card-body">
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
          <div class="mb-3 col-md-7"><label for="ds-file">CSV (koma, UTF-8; maksimal 10 MB / 5.000 entri)</label><div id="ds-dropzone" class="ds-dropzone"><i class="fa-solid fa-cloud-arrow-up mb-2"></i><p class="mb-2">Tarik CSV ke sini atau pilih berkas</p><input id="ds-file" class="form-control" type="file" accept=".csv,text/csv" required><small id="ds-file-info" class="d-block mt-2 text-muted">Belum ada berkas dipilih.</small></div></div>
          <div class="mb-3 col-md-5"><label for="ds-label">Nama sumber/batch</label><input id="ds-label" class="form-control" maxlength="255" value="Web — Indramayu (Jawa Barat)"><small class="form-text text-muted">Gunakan nama sumber/kota/proyek agar batch mudah dikenali.</small></div>
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
        <div class="row g-2 align-items-end"><div class="mb-3 col-md-8"><label for="ds-batch">Batch impor</label><select id="ds-batch" class="form-select"></select></div><div class="mb-3 col-md-4"><label for="ds-filter">Status</label><select id="ds-filter" class="form-select"><option value="all">Semua</option><option value="pending">Menunggu</option><option value="deferred">Ditunda</option><option value="created">Dibuat</option><option value="merged">Digabung</option><option value="rejected">Dilewati</option></select></div></div>
        <button id="ds-refresh" type="button" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa-solid fa-rotate me-1"></i>Muat ulang antrean</button>
        <button id="ds-delete-batch" type="button" class="btn btn-sm btn-outline-danger mb-3"><i class="fa-solid fa-trash me-1"></i>Hapus batch belum diproses</button>
        <div id="ds-counts" class="mb-2 small"></div>
        <div id="ds-queue" class="table-responsive"></div>
        <div id="ds-pagination" class="d-flex justify-content-between align-items-center"></div>
      </div>
    </div>
  </div></div>
  <div class="modal fade" id="ds-modal" tabindex="-1" role="dialog" aria-labelledby="ds-modal-title" aria-hidden="true"><div class="modal-dialog modal-xl" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="ds-modal-title">Moderasi entri web</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body" id="ds-review"></div>
    <div class="modal-footer"><span id="ds-review-message" class="me-auto" role="status" aria-live="polite"></span><button type="button" id="ds-save" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Simpan keputusan</button></div>
  </div></div></div>
</section>
<style>
#dreamsea-importer .ds-source{white-space:pre-wrap;overflow-wrap:anywhere;max-height:220px;overflow:auto;background:#f8fafc;padding:12px;border:1px solid #e2e8f0}
#dreamsea-importer .ds-old{white-space:pre-wrap;overflow-wrap:anywhere;max-height:150px;overflow:auto;background:#f1f5f9;padding:8px;font-size:.9rem}
#dreamsea-importer #ds-review-message{max-width:75%;overflow-wrap:anywhere}
#dreamsea-importer .ds-history-block{border:1px solid #e2e8f0;border-radius:8px;padding:10px;background:#f8fafc}
#dreamsea-importer .ds-image-preview{overflow-wrap:anywhere}
#dreamsea-importer .ds-image-preview img{width:150px;max-height:170px;object-fit:contain;background:#fff}
#dreamsea-importer label.is-required-missing{background:#fff3cd;border-left:3px solid #dc3545;padding:2px 6px;border-radius:4px}
#ds-modal{z-index:1070}body.modal-open:has(#ds-modal.show) .modal-backdrop{z-index:1060}
#ds-modal .modal-content{background:#fff;color:#334155}
#ds-modal .modal-body{max-height:calc(100vh - 200px);max-height:calc(100dvh - 200px);overflow-y:auto}
#dreamsea-importer #ds-queue table{min-width:760px}
#dreamsea-importer #ds-pagination{gap:8px;flex-wrap:wrap}
#dreamsea-importer .ds-dropzone{padding:18px;border:2px dashed #cbd5e1;background:#f8fafc;border-radius:10px;text-align:center}#dreamsea-importer .ds-dropzone>i{font-size:1.8rem;color:#2563eb}#dreamsea-importer .ds-dropzone.is-dragging{border-color:#2563eb;background:#eff6ff}
#dreamsea-importer .ds-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
#dreamsea-importer .ds-step{position:relative;text-align:left;padding:12px 12px 12px 48px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b}
#dreamsea-importer .ds-step span{position:absolute;left:12px;top:15px;display:grid;place-items:center;width:26px;height:26px;border-radius:50%;background:#e2e8f0;color:#475569}
#dreamsea-importer .ds-step strong,#dreamsea-importer .ds-step small{display:block}
#dreamsea-importer .ds-step.is-active{border-color:#2563eb;background:#eff6ff;color:#1d4ed8}#dreamsea-importer .ds-step.is-active span{background:#2563eb;color:#fff}
#dreamsea-importer .ds-step:disabled{opacity:.55;cursor:not-allowed}
#dreamsea-importer .ds-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
#dreamsea-importer .ds-summary button{padding:14px;text-align:left;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;color:#334155}#dreamsea-importer .ds-summary strong{display:block;font-size:1.5rem;color:#1d4ed8}
#dreamsea-importer .ds-summary button.is-active{background:#eff6ff;border-color:#2563eb}
@media(max-width:767.98px){#dreamsea-importer .ds-step{padding:8px 4px;text-align:center}#dreamsea-importer .ds-step span{position:static;margin:0 auto 5px}#dreamsea-importer .ds-step small{display:none}#dreamsea-importer .ds-step strong{font-size:.75rem}#dreamsea-importer .ds-summary{grid-template-columns:repeat(2,minmax(0,1fr))}#ds-modal .modal-dialog{margin:8px}}
</style>
<script src="<?= BASE_URL ?>/admin/assets/js/dreamsea-importer.js?v=3.1.71-web-history"></script>
