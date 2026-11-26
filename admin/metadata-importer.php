<?php
require_once __DIR__.'/includes/request.php';
kandagaAuthorize(true);
$engine=$_GET['engine']??'catalog';
if (!is_string($engine) || !in_array($engine,['catalog','dreamsea'],true)) $engine='catalog';
if (!defined('KANDAGA_GOAH_PANEL')) {
    header('Location: '.BASE_URL.'/admin/index.php?page=metadata-importer&engine='.$engine);
    exit;
}
?>
<section class="metadata-hub mb-4" aria-labelledby="metadata-hub-title">
 <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
  <div><h3 id="metadata-hub-title"><i class="fa-solid fa-file-import mr-2"></i>Metadata Importer</h3><p class="text-muted mb-0">Pilih sumber data dan lanjutkan proses impor di panel Goah.</p></div>
  <span class="badge badge-primary mt-2"><i class="fa-solid fa-user-shield mr-1"></i>Khusus administrator</span>
 </div>
 <div class="row">
  <div class="col-md-6 mb-2"><a href="<?= BASE_URL ?>/admin/index.php?page=metadata-importer&amp;engine=catalog" class="metadata-engine <?= $engine==='catalog'?'is-active':'' ?>" <?= $engine==='catalog'?'aria-current="page"':'' ?>>
   <i class="fa-solid fa-file-csv metadata-engine-icon"></i><span><strong>Importer Katalog Cetak</strong><small>CSV · pemetaan metadata · antrean moderasi · merge</small></span><i class="fa-solid <?= $engine==='catalog'?'fa-circle-check':'fa-arrow-right' ?> ml-auto"></i>
  </a></div>
  <div class="col-md-6 mb-2"><a href="<?= BASE_URL ?>/admin/index.php?page=metadata-importer&amp;engine=dreamsea" class="metadata-engine <?= $engine==='dreamsea'?'is-active':'' ?>" <?= $engine==='dreamsea'?'aria-current="page"':'' ?>>
   <i class="fa-solid fa-cloud-arrow-down metadata-engine-icon"></i><span><strong>Dreamsea Importer</strong><small>CSV DREAMSEA · pratinjau · impor langsung</small></span><i class="fa-solid <?= $engine==='dreamsea'?'fa-circle-check':'fa-arrow-right' ?> ml-auto"></i>
  </a></div>
 </div>
</section>
<style>
.metadata-hub .metadata-engine{display:flex;align-items:center;gap:14px;height:100%;padding:18px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;color:#334155;text-decoration:none;transition:background .15s,border-color .15s}
.metadata-hub .metadata-engine:hover,.metadata-hub .metadata-engine:focus{border-color:#2563eb;background:#eff6ff}
.metadata-hub .metadata-engine.is-active{border:2px solid #2563eb;background:#eff6ff;color:#1d4ed8}
.metadata-hub .metadata-engine small{display:block;color:#64748b;margin-top:4px}.metadata-engine-icon{font-size:1.7rem}.metadata-engine span{min-width:0}
</style>
<?php if($engine==='catalog'): ?>
 <?php require __DIR__.'/catalog-importer.php'; ?>
<?php else: ?>
 <div class="alert alert-warning"><i class="fa-solid fa-circle-info mr-1"></i>Engine DREAMSEA menggunakan impor langsung setelah konfirmasi. Untuk pemeriksaan duplikat dan merge katalog cetak, pilih Importer Katalog Cetak.</div>
 <div id="engine-container" class="card p-4 shadow-sm"><?php require __DIR__.'/engines/dreamsea-import.php'; ?></div>
<?php endif; ?>
