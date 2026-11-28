<?php
require_once __DIR__.'/includes/request.php';
kandagaAuthorize(true);
if (!defined('KANDAGA_GOAH_PANEL')) {
    header('Location: '.BASE_URL.'/admin/index.php?page=metadata-importer&engine=catalog');
    exit;
}
?>
<section id="catalog-importer" data-endpoint="<?= htmlspecialchars(BASE_URL,ENT_QUOTES) ?>/admin/actions/catalog-import.php" data-base="<?= htmlspecialchars(BASE_URL,ENT_QUOTES) ?>" data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'],ENT_QUOTES) ?>">
 <div class="card"><div class="card-header"><h4 class="mb-0"><i class="fa-solid fa-file-csv me-2"></i>Importer Katalog Cetak</h4></div><div class="card-body">
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
     <div class="mb-3 col-md-6"><label for="ki-file">CSV (koma, UTF-8; maksimal 10 MB / 5.000 entri)</label><div id="ki-dropzone" class="ki-dropzone"><i class="fa-solid fa-cloud-arrow-up mb-2"></i><p class="mb-2">Tarik CSV ke sini atau pilih berkas</p><input id="ki-file" class="form-control" type="file" accept=".csv,text/csv" required><small id="ki-file-info" class="d-block mt-2 text-muted">Belum ada berkas dipilih.</small></div></div>
     <div class="mb-3 col-md-6"><label for="ki-label">Nama katalog/sumber</label><input id="ki-label" class="form-control" maxlength="255" required value="Ekadjati (ed.), Naskah Sunda: Inventarisasi dan Pencatatan, 1988"></div>
     <div class="mb-3 col-md-6"><label for="ki-bib">Referensi bibliografi katalog</label><select id="ki-bib" class="form-select" required></select><small class="form-text text-muted">Tambahkan katalog melalui Daftar Pustaka terlebih dahulu jika belum tersedia.</small></div>
     <div class="mb-3 col-md-6"><label for="ki-profile">Gunakan pemetaan batch sebelumnya</label><select id="ki-profile" class="form-select"></select></div>
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
   <div class="row g-2 align-items-end"><div class="mb-3 col-md-8"><label for="ki-batch">Batch impor</label><select id="ki-batch" class="form-select"></select></div><div class="mb-3 col-md-4"><label for="ki-filter">Status moderasi</label><select id="ki-filter" class="form-select"><option value="all">Semua</option><option value="pending">Menunggu</option><option value="deferred">Ditunda</option><option value="created">Dibuat</option><option value="merged">Digabung</option><option value="rejected">Dilewati</option></select></div></div>
   <button id="ki-refresh" type="button" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa-solid fa-rotate me-1"></i>Muat ulang antrean</button>
    <button id="ki-delete-batch" type="button" class="btn btn-sm btn-outline-danger mb-3"><i class="fa-solid fa-trash me-1"></i>Hapus batch belum diproses</button>
   <div id="ki-counts" class="mb-2 small"></div><div id="ki-queue" class="table-responsive"></div><div id="ki-pagination" class="d-flex justify-content-between align-items-center"></div>
   </div>
  </div>
 </div></div>
 <div class="modal fade" id="ki-modal" tabindex="-1" role="dialog" aria-labelledby="ki-modal-title" aria-hidden="true"><div class="modal-dialog modal-xl" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="ki-modal-title">Moderasi entri katalog</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="ki-review-toolbar px-3 py-2 border-bottom"><button type="button" class="btn btn-sm btn-outline-secondary" data-review-section="top"><i class="fa-solid fa-file-lines me-1"></i>Sumber &amp; kandidat</button> <button type="button" class="btn btn-sm btn-outline-secondary" data-review-section="ki-edit-fields"><i class="fa-solid fa-table-columns me-1"></i>Perbandingan metadata</button> <button type="button" class="btn btn-sm btn-outline-secondary" data-review-section="ki-note"><i class="fa-solid fa-check me-1"></i>Keputusan</button></div><div class="modal-body" id="ki-review"></div><div class="modal-footer"><span id="ki-review-message" class="me-auto" role="status" aria-live="polite"></span><button type="button" id="ki-save" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Simpan keputusan</button></div></div></div></div>
</section>
<style>
#catalog-importer .ki-source{white-space:pre-wrap;overflow-wrap:anywhere;max-height:250px;overflow:auto;background:#f8fafc;padding:12px;border:1px solid #e2e8f0}
#catalog-importer .ki-old{white-space:pre-wrap;overflow-wrap:anywhere;max-height:160px;overflow:auto;background:#f1f5f9;padding:8px;font-size:.9rem}
#catalog-importer .ki-field-row{padding:12px 0;border-bottom:1px solid #e2e8f0}
#catalog-importer #ki-review-message{max-width:75%;overflow-wrap:anywhere}
#catalog-importer .ki-image-preview{overflow-wrap:anywhere}
#catalog-importer .ki-image-preview img{width:150px;max-height:170px;object-fit:contain;background:#fff}
#catalog-importer label.is-required-missing{background:#fff3cd;border-left:3px solid #dc3545;padding:2px 6px;border-radius:4px}
#ki-modal{z-index:1070}body.modal-open:has(#ki-modal.show) .modal-backdrop{z-index:1060}
#ki-modal .modal-content{background:#fff;color:#334155}
#ki-modal .modal-body{max-height:calc(100vh - 260px);max-height:calc(100dvh - 260px);overflow-y:auto}
#catalog-importer #ki-queue table{min-width:760px}
#catalog-importer #ki-pagination{gap:8px;flex-wrap:wrap}
@media(max-width:575.98px){#catalog-importer .card-body{padding:12px}#catalog-importer #ki-pagination>span{width:100%;order:3;text-align:center}#ki-modal .modal-dialog{margin:8px}#ki-modal .modal-header,#ki-modal .modal-body,#ki-modal .modal-footer{padding:12px}}
#ki-modal label{font-weight:600}
#catalog-importer .ki-map-select{width:100%}

#catalog-importer .ki-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
#catalog-importer .ki-step{position:relative;text-align:left;padding:12px 12px 12px 48px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b}
#catalog-importer .ki-step span{position:absolute;left:12px;top:15px;display:grid;place-items:center;width:26px;height:26px;border-radius:50%;background:#e2e8f0;color:#475569}
#catalog-importer .ki-step strong,#catalog-importer .ki-step small{display:block}
#catalog-importer .ki-step.is-active{border-color:#2563eb;background:#eff6ff;color:#1d4ed8}#catalog-importer .ki-step.is-active span{background:#2563eb;color:#fff}
#catalog-importer .ki-step:disabled{opacity:.55;cursor:not-allowed}
#catalog-importer .ki-dropzone{padding:18px;border:2px dashed #cbd5e1;background:#f8fafc;border-radius:10px;text-align:center}#catalog-importer .ki-dropzone>i{font-size:1.8rem;color:#2563eb}#catalog-importer .ki-dropzone.is-dragging{border-color:#2563eb;background:#eff6ff}
#catalog-importer .ki-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
#catalog-importer .ki-summary button{padding:14px;text-align:left;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;color:#334155}#catalog-importer .ki-summary strong{display:block;font-size:1.5rem;color:#1d4ed8}
#catalog-importer .ki-summary button.is-active{background:#eff6ff;border-color:#2563eb}#catalog-importer .ki-preview-cell{max-width:260px;max-height:100px;overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere;font-size:.85rem}
#catalog-importer #ki-preview th,#catalog-importer #ki-preview td{min-width:170px}
#catalog-importer .ki-field-row.ki-selected{background:#eff6ff;border-left:3px solid #2563eb}
#ki-modal .ki-review-toolbar{display:flex;gap:8px;flex-wrap:wrap;background:#f8fafc}
@media(max-width:767.98px){#catalog-importer .ki-step{padding:8px 4px;text-align:center}#catalog-importer .ki-step span{position:static;margin:0 auto 5px}#catalog-importer .ki-step small{display:none}#catalog-importer .ki-step strong{font-size:.75rem;overflow-wrap:normal;word-break:normal}#catalog-importer .ki-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<script src="<?= BASE_URL ?>/admin/assets/js/catalog-importer.js?v=3.1.71-catalog-ui"></script>
