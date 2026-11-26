<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(false);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/bibliography.php';
$id = (int)($_GET['id'] ?? 0); $row = [];
if ($id) { $st=$conn->prepare('SELECT b.*,m.cover_filename,m.cover_url FROM tb_bibliografi b LEFT JOIN tb_bibliografi_media m ON m.id_bibliografi=b.id WHERE b.id=?'); $st->bind_param('i',$id); $st->execute(); $row=$st->get_result()->fetch_assoc() ?: []; $st->close(); }
$val = static fn($k,$d='') => htmlspecialchars((string)($row[$k] ?? $d), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
$cover = kandagaBibliographyCover($row);
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="fw-bold mb-1"><i class="fa-solid fa-book-open text-primary me-2"></i><?= $id ? 'Edit Bibliografi' : 'Tambah Bibliografi' ?></h3><p class="text-muted small mb-0">Form terpisah untuk monograf, artikel, dan katalog.</p></div><a class="btn btn-light" href="index.php?page=daftar-pustaka"><i class="fa-solid fa-arrow-left me-1"></i>Kembali</a></div>
  <form id="bibliographyForm" enctype="multipart/form-data" class="card border-0 shadow-sm"><input type="hidden" name="id" value="<?= $id ?>"><div class="card-body"><div class="row"><div class="col-lg-8">
    <div class="row g-2"><div class="mb-3 col-md-6"><label>Tipe item</label><select name="tipe_item" id="tipe_item" class="form-select"><option value="buku" <?= ($row['tipe_item']??'buku')==='buku'?'selected':'' ?>>Buku / katalog</option><option value="jurnal" <?= ($row['tipe_item']??'')==='jurnal'?'selected':'' ?>>Artikel jurnal</option></select></div><div class="mb-3 col-md-6"><label>Kategori</label><select name="kategori" class="form-select"><option value="referensi sekunder" <?= ($row['kategori']??'')==='referensi sekunder'?'selected':'' ?>>Referensi sekunder</option><option value="referensi primer" <?= ($row['kategori']??'')==='referensi primer'?'selected':'' ?>>Referensi primer</option><option value="katalog" <?= ($row['kategori']??'')==='katalog'?'selected':'' ?>>Katalog</option></select></div></div>
    <div class="mb-3"><label>Judul utama</label><input name="judul" class="form-control" required value="<?= $val('judul') ?>"></div><div class="mb-3"><label>Subjudul</label><input name="subjudul" class="form-control" value="<?= $val('subjudul') ?>"></div><div class="mb-3"><label>Penyusun / penulis</label><input name="penyusun" class="form-control" required value="<?= $val('penyusun') ?>"><div id="duplicateWarning" class="alert alert-warning small mt-2 py-2 px-3 d-none" role="status"></div><small class="text-muted">Kombinasi judul, penyusun, tahun, dan kategori harus unik.</small></div>
    <div class="row g-2"><div class="mb-3 col-md-4"><label>Tahun</label><input name="tahun_terbit" class="form-control" value="<?= $val('tahun_terbit') ?>"></div><div class="mb-3 col-md-4"><label>Kota terbit</label><input name="kota_terbit" class="form-control" value="<?= $val('kota_terbit') ?>"></div><div class="mb-3 col-md-4"><label>Penerbit</label><input name="penerbit" class="form-control" value="<?= $val('penerbit') ?>"></div></div>
    <div class="row g-2"><div class="mb-3 col-md-6"><label>Editor</label><input name="editor" class="form-control" value="<?= $val('editor') ?>"></div><div class="mb-3 col-md-6"><label>Judul bab / section</label><input name="judul_bab" class="form-control" value="<?= $val('judul_bab') ?>"></div></div>
    <div class="row g-2"><div class="mb-3 col-md-4"><label>ISBN / ISSN</label><input name="isbn_issn" class="form-control" value="<?= $val('isbn_issn') ?>"></div><div class="mb-3 col-md-4"><label>DOI</label><input name="doi" class="form-control" value="<?= $val('doi') ?>"></div><div class="mb-3 col-md-4"><label>URL</label><input name="url" type="url" class="form-control" value="<?= $val('url') ?>"></div></div>
    <div id="journalFields" class="row g-2"><div class="mb-3 col-md-3"><label>Jurnal</label><input name="nama_jurnal" class="form-control" value="<?= $val('nama_jurnal') ?>"></div><div class="mb-3 col-md-3"><label>Volume</label><input name="volume" class="form-control" value="<?= $val('volume') ?>"></div><div class="mb-3 col-md-3"><label>Nomor</label><input name="nomor" class="form-control" value="<?= $val('nomor') ?>"></div><div class="mb-3 col-md-3"><label>Halaman</label><input name="halaman" class="form-control" value="<?= $val('halaman') ?>"></div></div>
    <div class="mb-3"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="3"><?= $val('keterangan') ?></textarea></div>
  </div><div class="col-lg-4"><div class="card bg-light border-0"><div class="card-body"><label class="fw-bold">Cover publikasi</label><?php if($cover): ?><img src="<?= htmlspecialchars($cover) ?>" class="img-fluid rounded mb-3" style="max-height:260px;object-fit:contain" alt="Cover saat ini"><?php endif; ?><input type="file" name="cover" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"><small class="text-muted d-block mt-2">JPG, PNG, GIF, atau WEBP maksimal 5 MB.</small><label class="fw-bold mt-3">URL cover (opsional)</label><input name="cover_url" type="url" class="form-control" value="<?= $val('cover_url') ?>"><small class="text-muted">Gunakan URL jika cover disimpan di server eksternal.</small></div></div><button class="btn btn-primary w-100 mt-3" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan bibliografi</button></div></div></div></form>
</div>
<script>
(function(){
  const f=document.getElementById('bibliographyForm'),t=document.getElementById('tipe_item'),dupBox=document.getElementById('duplicateWarning');
  function toggle(){document.getElementById('journalFields').style.display=t.value==='jurnal'?'flex':'none'}
  t.addEventListener('change',toggle);toggle();
  const escapeHtml=(s)=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let dupTimer=null,dupSeq=0;
  async function checkDuplicate(){
    const seq=++dupSeq,fd=new FormData(f);
    fd.append('check_only','1');fd.append('csrf_token',window.KANDAGA_CSRF||'');
    try{
      const r=await fetch('actions/proses-bibliografi.php',{method:'POST',body:fd,headers:{'X-CSRF-Token':window.KANDAGA_CSRF||''}}),d=await r.json();
      if(seq!==dupSeq)return;
      if(d.success&&d.duplicate){
        const id=parseInt(d.duplicate.id,10);
        dupBox.classList.remove('d-none');
        dupBox.innerHTML='<i class="fa-solid fa-triangle-exclamation me-1"></i> Entri dengan judul, penyusun, tahun, dan kategori sama sudah ada (ID '+id+'): '+escapeHtml(d.duplicate.judul||'tanpa judul')+'. <a href="index.php?page=form-bibliografi&id='+id+'">Buka entri</a>.';
      }else{dupBox.classList.add('d-none');dupBox.innerHTML=''}
    }catch(x){if(seq===dupSeq)dupBox.classList.add('d-none')}
  }
  ['judul','penyusun','tahun_terbit','kategori'].forEach(name=>{
    const el=f.elements[name];if(!el)return;
    el.addEventListener('input',()=>{clearTimeout(dupTimer);dupTimer=setTimeout(checkDuplicate,350)});
    el.addEventListener('change',()=>{clearTimeout(dupTimer);dupTimer=setTimeout(checkDuplicate,0)});
  });
  f.addEventListener('submit',async e=>{
    e.preventDefault();const b=f.querySelector('button[type=submit]');b.disabled=true;
    const fd=new FormData(f);fd.append('csrf_token',window.KANDAGA_CSRF||'');
    try{
      const r=await fetch('actions/proses-bibliografi.php',{method:'POST',body:fd,headers:{'X-CSRF-Token':window.KANDAGA_CSRF||''}}),d=await r.json();
      if(!d.success)throw new Error(d.message);
      window.location.href=d.redirect||'index.php?page=daftar-pustaka';
    }catch(x){alert(x.message||'Penyimpanan gagal');b.disabled=false}
  });
})();
</script>
