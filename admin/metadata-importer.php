<?php
require_once __DIR__.'/includes/request.php';
kandagaAuthorize(true);
$engine=$_GET['engine']??'catalog';
if ($engine==='dreamsea') $engine='web'; // tautan lama tetap membuka Importer Web
if (!is_string($engine) || !in_array($engine,['catalog','web'],true)) $engine='catalog';
if (!defined('KANDAGA_GOAH_PANEL')) {
    header('Location: '.BASE_URL.'/admin/index.php?page=metadata-importer&engine='.$engine);
    exit;
}
?>
<section class="metadata-hub mb-4" aria-labelledby="metadata-hub-title">
 <?php kandagaPageHeader('Metadata Importer', 'fa-file-import', 'Pilih sumber data dan lanjutkan proses impor di panel Goah.', '<span class="badge bg-primary"><i class="fa-solid fa-user-shield me-1" aria-hidden="true"></i>Khusus administrator</span>', 'metadata-hub-title'); ?>
 <div class="row">
  <div class="col-md-6 mb-2"><a href="<?= BASE_URL ?>/admin/index.php?page=metadata-importer&amp;engine=catalog" class="metadata-engine <?= $engine==='catalog'?'is-active':'' ?>" <?= $engine==='catalog'?'aria-current="page"':'' ?>>
   <i class="fa-solid fa-file-csv metadata-engine-icon"></i><span><strong>Importer Katalog Cetak</strong><small>CSV · pemetaan metadata · antrean moderasi · merge</small></span><i class="fa-solid <?= $engine==='catalog'?'fa-circle-check':'fa-arrow-right' ?> ms-auto"></i>
  </a></div>
  <div class="col-md-6 mb-2"><a href="<?= BASE_URL ?>/admin/index.php?page=metadata-importer&amp;engine=web" class="metadata-engine <?= $engine==='web'?'is-active':'' ?>" <?= $engine==='web'?'aria-current="page"':'' ?>>
   <i class="fa-solid fa-cloud-arrow-down metadata-engine-icon"></i><span><strong>Importer Web</strong><small>CSV dari sumber web · dedup identitas sumber · antrean moderasi</small></span><i class="fa-solid <?= $engine==='web'?'fa-circle-check':'fa-arrow-right' ?> ms-auto"></i>
  </a></div>
 </div>
</section><?php if($engine==='catalog'): ?>
 <?php require __DIR__.'/catalog-importer.php'; ?>
<?php else: ?>
 <div class="alert alert-info"><i class="fa-solid fa-circle-info me-1"></i>Importer Web memakai antrean dan moderasi per entri untuk CSV dari sumber web mana pun. Identitas sumber dipakai untuk deduplikasi; master Kandaga tidak dibuat otomatis. Antrean Importer Web terpisah dari Importer Katalog Cetak. Lihat <code>docs/web-import-csv.md</code> untuk format CSV dan gaya terjemahan.</div>
 <div id="engine-container" class="card p-4 shadow-sm"><?php require __DIR__.'/engines/dreamsea-import.php'; ?></div>
<?php endif; ?>
