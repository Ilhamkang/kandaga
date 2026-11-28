(() => {
 'use strict';
 const root=document.getElementById('catalog-importer'); if(!root || root.dataset.initialized)return; root.dataset.initialized='1';
 const $id=id=>document.getElementById(id), esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const names={judul:'Judul',kode_inventaris:'Nomor inventaris',deskripsi_fisik:'Deskripsi Fisik',deskripsi_isi:'Deskripsi Isi',riwayat_naskah:'Riwayat Naskah',kata_kunci:'Kata kunci',penyedia_akses:'Penyedia akses',url_web:'URL web',url_repositori:'URL repositori',iiif_manifest:'IIIF manifest',url_pdf:'URL PDF',url_gambar_eksternal:'URL gambar eksternal',id_lokasi:'Lokasi penyimpanan saat ini',bahasa:'Bahasa',aksara:'Aksara',alas_tulis:'Alas tulis'};
 const statuses={pending:'Menunggu',deferred:'Ditunda',rejected:'Dilewati',created:'Dibuat',merged:'Digabung'};
 const fieldDefaults={judul:'',kode_inventaris:'',deskripsi_fisik:'',deskripsi_isi:'',riwayat_naskah:'',kata_kunci:'',penyedia_akses:'',url_web:'',url_repositori:'',iiif_manifest:'',url_pdf:'',url_gambar_eksternal:''};
 const historyKinds={asal:'Asal',ditemukan:'Ditemukan',sebelumnya:'Sebelumnya'};
 const certainties={pasti:'Pasti',kemungkinan:'Kemungkinan',dugaan:'Dugaan',belum_diketahui:'Belum diketahui'};
 const identifierRow=v=>`<div class="row g-1 ki-identifier-row mb-2"><div class="col-md-3"><input class="form-control form-control-sm ki-id-scheme" placeholder="Skema" maxlength="64" value="${esc(v?.scheme||'')}"></div><div class="col-md-4"><input class="form-control form-control-sm ki-id-value" placeholder="Nilai" maxlength="255" value="${esc(v?.value||'')}"></div><div class="col-md-3"><input class="form-control form-control-sm ki-id-source" placeholder="Sumber" maxlength="255" value="${esc(v?.source||'')}"></div><div class="col-md-2"><label class="small"><input type="checkbox" class="ki-id-primary" ${v?.primary?'checked':''}> Utama</label></div></div>`;
 const historyRow=v=>`<div class="row g-1 ki-history-row mb-2"><div class="col-md-2"><select class="form-select form-select-sm ki-hist-kind">${Object.entries(historyKinds).map(([k,l])=>`<option value="${k}" ${(v?.kind||'asal')===k?'selected':''}>${l}</option>`).join('')}</select></div><div class="col-md-3"><input class="form-control form-control-sm ki-hist-place" placeholder="Tempat/koleksi" maxlength="255" value="${esc(v?.place||'')}"></div><div class="col-md-2"><input class="form-control form-control-sm ki-hist-district" placeholder="Kabupaten/kota" maxlength="150" value="${esc(v?.district||'')}"></div><div class="col-md-2"><input class="form-control form-control-sm ki-hist-province" placeholder="Provinsi" maxlength="150" value="${esc(v?.province||'')}"></div><div class="col-md-1"><input class="form-control form-control-sm ki-hist-country" placeholder="Negara" maxlength="150" value="${esc(v?.country||'')}"></div><div class="col-md-2"><input class="form-control form-control-sm ki-hist-period" placeholder="Masa" maxlength="150" value="${esc(v?.period||'')}"></div><div class="col-md-2"><select class="form-select form-select-sm ki-hist-certainty">${Object.entries(certainties).map(([k,l])=>`<option value="${k}" ${(v?.certainty||'belum_diketahui')===k?'selected':''}>${l}</option>`).join('')}</select></div><div class="col-md-2"><input class="form-control form-control-sm ki-hist-source" placeholder="Sumber" maxlength="255" value="${esc(v?.source||'')}"></div></div>`;
 let config,headers=[],page=1,current=null,loading=false;
 const required=['Sumber_ID','Bagian_Sumber','Nomor_Entri','Halaman_Cetak','Halaman_PDF','Status_Sumber'];
 function setStep(step){
  if(!config?.ready)return;
  if(step==='mapping'&&!headers.length)return;
  root.querySelectorAll('[data-step-panel]').forEach(p=>p.hidden=p.dataset.stepPanel!==step);
  root.querySelectorAll('.ki-step').forEach(b=>{const active=b.dataset.step===step;b.classList.toggle('is-active',active);if(active)b.setAttribute('aria-current','step');else b.removeAttribute('aria-current');});
 }
 function mappingStatus(){
  const controls=Array.from(root.querySelectorAll('.ki-map-select'));
  const matched=required.filter(f=>controls.some(c=>c.dataset.field===f&&c.value)).length;
  $id('ki-mapping-status').textContent=`${matched}/${required.length} kolom sumber wajib dipetakan`;
  $id('ki-mapping-status').className='badge '+(matched===required.length?'bg-success':'bg-warning text-dark');
  $id('ki-stage').disabled=matched!==required.length;
  root.querySelectorAll('.ki-step[data-step="mapping"]').forEach(b=>b.disabled=!headers.length);
 }
 function fileInfo(){
  headers=[];$id('ki-mapping').innerHTML='';$id('ki-preview').innerHTML='';$id('ki-stage').hidden=true;
  const file=$id('ki-file').files[0];$id('ki-file-info').textContent=file?`${file.name} · ${(file.size/1024).toFixed(1)} KB`:'Belum ada berkas dipilih.';
  mappingStatus();setStep('upload');
 }

 const message=(text,error=false)=>{$id('ki-message').className='alert '+(error?'alert-danger':'alert-success');$id('ki-message').textContent=text;};
 const plain=html=>new DOMParser().parseFromString(String(html??'').replace(/<br\s*\/?\s*>/gi,'\n').replace(/<\/p>/gi,'\n'),'text/html').body.textContent;
 async function api(action, params={},data=null){
  const url=new URL(root.dataset.endpoint,location.origin);url.searchParams.set('action',action);Object.entries(params).forEach(([k,v])=>url.searchParams.set(k,v));
  const opts={credentials:'same-origin',headers:{Accept:'application/json'}};if(data){opts.method='POST';opts.body=data;opts.headers['X-CSRF-Token']=root.dataset.csrf;}
  const r=await fetch(url,opts);let result;try{result=await r.json();}catch(e){throw new Error('Respons server tidak terbaca. Periksa batas upload dan log PHP.');}
  if(!r.ok||!result.success)throw new Error(result.message||'Permintaan gagal.');return result;
 }
 function masterOptions(group,selected=[],empty=true){return (empty?'<option value="">— Pilih —</option>':'')+config.masters[group].map(v=>`<option value="${Number(v.id)}" ${selected.map(Number).includes(Number(v.id))?'selected':''}>${esc(v.name)}</option>`).join('');}
 async function init(batchId){
  config=await api('init');$id('ki-setup').hidden=config.ready;$id('ki-work').hidden=!config.ready;if(!config.ready)return;
  $id('ki-bib').innerHTML=masterOptions('bibliografi');
  const options=config.batches.map(b=>`<option value="${Number(b.id)}">#${Number(b.id)} · ${esc(b.filename)} · ${esc(b.source_label)}</option>`).join('');
  $id('ki-profile').innerHTML='<option value="">Pemetaan otomatis berdasarkan nama kolom</option>'+options;
  $id('ki-batch').innerHTML=options||'<option value="">Belum ada batch</option>';
  if(batchId)$id('ki-batch').value=batchId;
  if($id('ki-batch').value){await queue();setStep('moderation');}else {setStep('upload');$id('ki-queue').textContent='Unggah CSV untuk membuat antrean pertama.';}
 }
 function mapping(){
  let previous={};const id=Number($id('ki-profile').value);const b=config.batches.find(x=>Number(x.id)===id);if(b)previous=JSON.parse(b.mapping_json);
  $id('ki-mapping').innerHTML='<p class="small text-muted">Kolom sumber wajib: Sumber_ID, Bagian_Sumber, Nomor_Entri, Halaman_Cetak, Halaman_PDF, Status_Sumber. Nama kolom boleh berbeda melalui pemetaan berikut. Kolom asli selalu disimpan.</p><div class="row">'+config.fields.map(f=>{
   const match=previous[f]&&headers.includes(previous[f])?previous[f]:headers.find(h=>h.toLowerCase()===f.toLowerCase());
   return `<div class="mb-3 col-md-4"><label for="ki-map-${esc(f)}">${esc(f)}${required.includes(f)?' <span class="text-danger">*</span>':''}</label><select id="ki-map-${esc(f)}" class="form-select form-select-sm ki-map-select" data-field="${esc(f)}"><option value="">Tidak dipetakan</option>${headers.map(h=>`<option value="${esc(h)}" ${h===match?'selected':''}>${esc(h)}</option>`).join('')}</select></div>`;
  }).join('')+'</div>';mappingStatus();
 }
 async function inspect(){
  const file=$id('ki-file').files[0];if(!file)throw new Error('Pilih CSV terlebih dahulu.');if(!$id('ki-bib').value){$id('ki-bib').focus();throw new Error('Pilih referensi bibliografi sebelum melanjutkan ke pemetaan.');}if(!$id('ki-label').value.trim())throw new Error('Isi nama katalog/sumber.');const fd=new FormData();fd.set('csv_file',file);const result=await api('inspect',{},fd);headers=result.headers;mapping();$id('ki-stage').hidden=false;
  $id('ki-preview').innerHTML='<table class="table table-sm table-bordered"><thead><tr>'+headers.map(h=>'<th>'+esc(h)+'</th>').join('')+'</tr></thead><tbody>'+result.preview.map(r=>'<tr>'+headers.map(h=>'<td><div class="ki-preview-cell">'+esc(r[h])+'</div></td>').join('')+'</tr>').join('')+'</tbody></table>';setStep('mapping');
  message(`${result.count} entri terbaca. Periksa pemetaan sebelum menyimpan ke antrean.`);
 }
 async function queue(){
  const id=$id('ki-batch').value;if(!id)return;const result=await api('queue',{batch_id:id,page,status:$id('ki-filter').value});page=result.page;
  const totals=Object.fromEntries(result.counts.map(x=>[x.status,Number(x.n)]));const all=Object.values(totals).reduce((a,b)=>a+b,0);const final=(totals.created||0)+(totals.merged||0)+(totals.rejected||0);const pct=all?Math.round(final/all*100):0;
  $id('ki-summary').innerHTML=['pending','deferred','created','merged'].map(status=>`<button type="button" data-status="${status}" class="${$id('ki-filter').value===status?'is-active':''}" aria-pressed="${$id('ki-filter').value===status}"><strong>${totals[status]||0}</strong>${esc(statuses[status])}</button>`).join('');
  $id('ki-progress-text').textContent=`${final}/${all} · ${pct}%`;$id('ki-progress-bar').style.width=pct+'%';$id('ki-progress-bar').setAttribute('aria-valuenow',pct);
  $id('ki-counts').textContent=result.counts.map(x=>`${statuses[x.status]||x.status}: ${x.n}`).join(' · ');
  $id('ki-queue').innerHTML=result.rows.length?'<table class="table table-sm table-striped"><thead><tr><th>Entri</th><th>Judul / inventaris</th><th>Sumber</th><th>Status</th><th>Aksi</th></tr></thead><tbody>'+result.rows.map(r=>`<tr><td>${Number(r.source_row)}</td><td>${esc(r.title||'[Tanpa judul]')}<br><small>${esc(r.inventory||'Tanpa nomor inventaris')}</small></td><td>${esc(r.source.Bagian_Sumber)} · ${esc(r.source.Nomor_Entri)}<br><small>Hlm. ${esc(r.source.Halaman_Cetak)}; PDF ${esc(r.source.Halaman_PDF)} · ${esc(r.source.Status_Sumber)}</small></td><td>${esc(statuses[r.status]||r.status)}${r.linked_id?'<br><small>Identitas sumber sudah ditautkan ke #'+Number(r.linked_id)+'</small>':''}</td><td><button class="btn btn-sm btn-outline-primary ki-open" data-id="${Number(r.id)}"><i class="fa-solid fa-magnifying-glass me-1"></i>Periksa</button></td></tr>`).join('')+'</tbody></table>':'Tidak ada entri untuk filter ini.';
  $id('ki-pagination').innerHTML=`<button class="btn btn-sm btn-outline-secondary" id="ki-prev" ${page<=1?'disabled':''}><i class="fa-solid fa-chevron-left me-1"></i>Sebelumnya</button><span>${page} / ${result.pages} · ${result.total} entri</span><button class="btn btn-sm btn-outline-secondary" id="ki-next" ${page>=result.pages?'disabled':''}>Berikutnya<i class="fa-solid fa-chevron-right ms-1"></i></button>`;
 }
 async function open(id,target=0,locationId=0,historical=false){
  current=await api('row',{row_id:id,target_id:target,location:locationId,historical:historical?'1':'0'});render(locationId,historical);bootstrap.Modal.getOrCreateInstance(document.getElementById('ki-modal')).show();
 }
 function render(locationId,historical){
  const {row,suggestions,candidates,duplicate,snapshot,linked,audit}=current,src=row.draft.source,fields={...fieldDefaults,...row.draft.fields},old=snapshot?.data;
  const done=!['pending','deferred'].includes(row.status),blocked=done;
  $id('ki-save').disabled=blocked;$id('ki-review-message').textContent='';
  let html=`<p><strong>${esc(row.source_label)}</strong> · ${esc(src.Bagian_Sumber)}, entri ${esc(src.Nomor_Entri)}, hlm. ${esc(src.Halaman_Cetak)} (PDF ${esc(src.Halaman_PDF)}).</p>`;
  if(linked)html+=`<div class="alert alert-info">Identitas sumber ini sudah ditautkan ke entri #${Number(linked.katalog_id)}${linked.judul?' · '+esc(linked.judul):' (entri tujuan tidak tersedia)'}. Impor ulang ditahan.</div>`;
  if(src.Status_Sumber==='berita')html+='<div class="alert alert-warning">Berita adanya naskah: belum terverifikasi. Hanya boleh ditunda atau dilewati; tidak dapat diterbitkan sebagai entri katalog.</div>';
  html+=`<div class="alert alert-light border">Lokasi/pemilik historis menurut katalog: <strong>${esc(src.Lokasi_Sumber||'Tidak disebutkan')}</strong><br>Asal naskah: ${esc(src.Asal_Naskah||'Tidak disebutkan')}<br>Lokasi sekarang harus dipilih manual di bawah; riwayat lokasi terstruktur dapat diedit pada bagian khusus.<br>Catatan koreksi: ${esc(src.Catatan_Koreksi||'—')}</div>`;
  if(suggestions.unresolved.length)html+='<div class="alert alert-warning">Istilah belum cocok dengan daftar Kandaga: '+suggestions.unresolved.map(v=>`${esc(names[v.group]||v.group)}: ${esc(v.term)}`).join('; ')+'. Pilih padanannya di form; tidak ada istilah baru yang dibuat otomatis.</div>';
  html+=`<details class="mb-3"><summary>Teks dan metadata sumber asli</summary><div class="ki-source">${esc(src.Teks_Sumber||'[Teks_Sumber kosong; lihat metadata asli di bawah]')}</div><div class="ki-source mt-2">${esc(JSON.stringify(row.raw,null,2))}</div></details>`;
  html+=`<div class="row g-2"><div class="mb-3 col-md-6"><label for="ki-match-location">Lokasi untuk mempersempit kandidat</label><select id="ki-match-location" class="form-select">${masterOptions('lokasi',locationId?[locationId]:[])}</select></div><div class="mb-3 col-md-6 align-self-end"><label><input type="checkbox" id="ki-historical" ${historical?'checked':''}> Sertakan varian kode L/Kropak dan LOr/Or</label><br><button class="btn btn-sm btn-outline-secondary" id="ki-find"><i class="fa-solid fa-magnifying-glass me-1"></i>Cari kandidat</button></div></div>`;
  html+='<p class="small text-muted">Kandidat adalah saran, bukan keputusan. Judul sama dapat merupakan salinan berbeda. Nomor tanpa prefiks perlu pemeriksaan koleksi. Memuat kandidat/tujuan mengembalikan form ke draf CSV; pilih tujuan sebelum mengedit.</p>';
  html+='<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kandidat</th><th>Kode / lokasi</th><th>Alasan</th><th>Aksi</th></tr></thead><tbody>'+candidates.map(c=>`<tr><td>#${Number(c.id)} · ${esc(c.judul)}</td><td>${esc(c.kode_inventaris)}<br>${esc(c.nama_tempat||'')}</td><td>${esc(c.reason)}</td><td><button class="btn btn-sm btn-outline-primary ki-target" data-bs-target="${Number(c.id)}"><i class="fa-solid fa-code-merge me-1"></i>Bandingkan</button> <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="${esc(root.dataset.base)}/detail.php?id=${Number(c.id)}"><i class="fa-solid fa-eye"></i><span class="visually-hidden">Detail</span></a></td></tr>`).join('')+'</tbody></table></div>';
  if(!candidates.length)html+='<p>Belum ditemukan kandidat; tetap periksa identitas dan kemungkinan kode historis.</p>';
  html+='<div class="row g-2"><div class="mb-3 col-md-6"><label for="ki-target-manual">Bandingkan ID entri lain</label><div class="input-group"><input id="ki-target-manual" type="number" min="1" class="form-control" placeholder="ID katalog"><button id="ki-load-target" class="btn btn-outline-primary"><i class="fa-solid fa-code-merge me-1"></i>Bandingkan</button></div></div><div class="mb-3 col-md-6"><label for="ki-decision">Keputusan</label><select id="ki-decision" class="form-select">';
  if(src.Status_Sumber!=='berita'&&!linked)html+='<option value="created">Buat entri baru</option>'+(old?'<option value="merged" selected>Merge ke #'+Number(old.fields.id)+'</option>':'');
  html+='<option value="deferred">Tunda</option><option value="rejected">Lewati</option></select></div></div>';
  if(old)html+=`<div class="alert alert-info">Membandingkan dengan #${Number(old.fields.id)} · ${esc(old.fields.judul)}. Semua field merge diawali dengan Pertahankan. Gambar, tautan digital, dan relasi bibliografi lama tetap dipertahankan.</div>`;
  html+='<div id="ki-edit-fields">';
  Object.entries(fields).forEach(([f,value])=>{
   const text=['deskripsi_fisik','deskripsi_isi','riwayat_naskah'].includes(f);
   const preview=f==='url_gambar_eksternal'?'<div id="ki-image-preview" class="ki-image-preview mt-2"><span class="small text-muted">Belum ada pratinjau gambar eksternal.</span></div>':'';
   html+=`<div class="row ki-field-row"><div class="col-md-5"><label>${esc(names[f])} — data sekarang</label><div class="ki-old">${esc(old?plain(old.fields[f]):'Belum ada entri tujuan')}</div></div><div class="col-md-7"><label for="ki-field-${f}">Draf sumber / hasil edit</label>${text?`<textarea id="ki-field-${f}" class="form-control" rows="4">${esc(value)}</textarea>`:`<input id="ki-field-${f}" class="form-control" maxlength="255" value="${esc(value)}">`}${old?`<select class="form-select form-select-sm mt-2 ki-choice" data-field="${f}"><option value="keep">Pertahankan data sekarang</option><option value="use">Gunakan draf yang diedit</option>${text?'<option value="append">Tambahkan draf dengan atribusi sumber</option>':''}</select>`:''}${preview}</div></div>`;
  });
  html+=`<div class="row ki-field-row"><div class="col-md-5"><label>Lokasi sekarang</label><div class="ki-old">${esc(old?(config.masters.lokasi.find(v=>Number(v.id)===Number(old.fields.id_lokasi))?.name||old.fields.id_lokasi):'Belum ada entri tujuan')}</div></div><div class="col-md-7"><label for="ki-field-id_lokasi">Lokasi penyimpanan yang telah diperiksa</label><select id="ki-field-id_lokasi" class="form-select">${masterOptions('lokasi',suggestions.lokasi?[suggestions.lokasi]:[])}</select>${old?'<select class="form-select form-select-sm mt-2 ki-choice" data-field="id_lokasi"><option value="keep">Pertahankan lokasi sekarang</option><option value="use">Gunakan lokasi pilihan</option></select>':''}<label class="mt-2 small"><input id="ki-location-confirmed" type="checkbox"> Saya sudah memeriksa lokasi penyimpanan; ini bukan pemindahan otomatis dari lokasi tahun 1988.</label></div></div>`;
  ['bahasa','aksara','alas_tulis'].forEach(k=>{
   const oldLabels=(old?.relations[k]||[]).map(id=>config.masters[k].find(v=>Number(v.id)===Number(id))?.name||id);
   html+=`<div class="row ki-field-row"><div class="col-md-5"><label>${esc(names[k])} sekarang</label><div class="ki-old">${esc(oldLabels.join(', ')||'—')}</div></div><div class="col-md-7"><label for="ki-rel-${k}">Pilihan ${esc(names[k])} (dapat lebih dari satu)</label><select id="ki-rel-${k}" class="form-select" multiple size="5">${masterOptions(k,suggestions[k],false)}</select>${old?`<select class="form-select form-select-sm mt-2 ki-choice" data-field="${k}"><option value="keep">Pertahankan relasi lama</option><option value="union">Tambahkan pilihan ke relasi lama</option><option value="replace">Gunakan pilihan sebagai pengganti</option></select>`:''}</div></div>`;
  });
  const st=row.draft.structure||{}, parentOptions='<option value="">— Tidak ada induk —</option>'+config.masters.katalog.map(v=>`<option value="${Number(v.id)}" ${Number(st.parent_id)===Number(v.id)?'selected':''}>#${Number(v.id)} · ${esc(v.kode_inventaris||v.judul)}</option>`).join('');
  html+=`<div class="row ki-field-row"><div class="col-md-5"><label>Struktur naskah</label><div class="ki-old">${esc(old?.structure?`${old.structure.record_type}${old.structure.parent_id?' · induk #'+Number(old.structure.parent_id):''}${old.structure.unit_label?' · '+old.structure.unit_label:''}`:'—')}</div></div><div class="col-md-7"><div class="row g-1"><div class="col-md-4"><label class="small">Jenis rekaman</label><select id="ki-structure-record_type" class="form-select form-select-sm"><option value="induk" ${st.record_type!=='unit'?'selected':''}>Induk</option><option value="unit" ${st.record_type==='unit'?'selected':''}>Unit</option></select></div><div class="col-md-8"><label class="small">Entri induk</label><select id="ki-structure-parent_id" class="form-select form-select-sm">${parentOptions}</select></div><div class="col-md-5"><label class="small">Label unit</label><input id="ki-structure-unit_label" class="form-control form-control-sm" maxlength="100" value="${esc(st.unit_label||'')}"></div><div class="col-md-3"><label class="small">Urutan</label><input id="ki-structure-unit_order" type="number" min="1" class="form-control form-control-sm" value="${esc(st.unit_order||'')}"></div><div class="col-md-4"><label class="small">Catatan struktur</label><input id="ki-structure-notes" class="form-control form-control-sm" maxlength="255" value="${esc(st.notes||'')}"></div></div>${old?'<select class="form-select form-select-sm mt-2 ki-choice" data-field="structure"><option value="keep">Pertahankan struktur lama</option><option value="use">Gunakan struktur ini</option></select>':''}</div></div>`;
  html+=`<div class="row ki-field-row"><div class="col-md-5"><label>Identitas alternatif</label><div class="ki-old">Identitas utama tetap berupa kode inventaris dan identitas sumber.</div></div><div class="col-md-7"><div id="ki-identifiers">${(row.draft.identifiers||[]).map(identifierRow).join('')}</div><button type="button" class="btn btn-sm btn-outline-secondary" id="ki-add-identifier"><i class="fa-solid fa-plus me-1"></i>Tambah identitas</button></div></div>`;
  html+=`<div class="row ki-field-row"><div class="col-md-5"><label>Riwayat lokasi terstruktur</label><div class="ki-old">Ditulis saat entri baru dibuat; saat merge hanya ditambahkan bila entri tujuan belum punya riwayat lokasi.</div></div><div class="col-md-7"><div id="ki-history">${(row.draft.location_history||[]).map(historyRow).join('')}</div><button type="button" class="btn btn-sm btn-outline-secondary" id="ki-add-history"><i class="fa-solid fa-plus me-1"></i>Tambah riwayat lokasi</button></div></div>`;
  const bibSelected=[...new Set([...(row.draft.bibliografi||[]),Number(row.bibliography_id||0)].filter(Boolean))];
  html+=`<div class="row ki-field-row"><div class="col-md-5"><label>Bibliografi terkait</label><div class="ki-old">Bibliografi batch tetap dipertahankan.</div></div><div class="col-md-7"><select id="ki-bibliografi" class="form-select" multiple size="4">${masterOptions('bibliografi',bibSelected,false)}</select></div></div>`;
  html+='<label class="mt-3"><input id="ki-metadata-confirmed" type="checkbox"> Saya sudah memeriksa padanan bahasa, aksara, alas tulis, lokasi saat ini, struktur, identitas, dan bibliografi; informasi yang belum tersedia tidak saya tebak.</label><br>';
  html+='<label class="mt-3"><input id="ki-duplicate-confirmed" type="checkbox"> Jika ada kandidat duplikat, saya sudah memeriksa bahwa entri baru ini merupakan naskah/salinan berbeda.</label><div id="ki-requirements" class="ki-requirements mt-3" aria-live="polite"></div></div>';
  html+='<div class="mb-3 mt-3"><label for="ki-note">Catatan keputusan</label><textarea id="ki-note" class="form-control" rows="2" maxlength="4000">'+esc(row.note||'')+'</textarea></div>';
  html+='<details><summary>Riwayat moderasi</summary>'+audit.map(a=>`<p>${esc(a.created_at)} · ${esc(a.username)} · ${esc(statuses[a.action]||a.action)} · ${esc(a.note)}</p>`).join('')+'</details>';
  $id('ki-review').innerHTML=html;$id('ki-decision').disabled=blocked;toggleFields();refreshRequirements();refreshImagePreview();
 }
 function toggleFields(){const decision=$id('ki-decision')?.value;$id('ki-edit-fields').hidden=!['created','merged'].includes(decision);root.querySelectorAll('.ki-choice').forEach(s=>s.disabled=decision!=='merged');}
 function effectiveImageUrl(){
  const input=$id('ki-field-url_gambar_eksternal');if(!input)return {url:'',old:false};
  if($id('ki-decision')?.value==='merged'&&root.querySelector('.ki-choice[data-field="url_gambar_eksternal"]')?.value==='keep')return {url:String(current?.snapshot?.data?.fields?.url_gambar_eksternal||''),old:true};
  return {url:input.value,old:false};
 }
 const checkable=id=>({id,label:id==='ki-location-confirmed'?'konfirmasi lokasi penyimpanan':id==='ki-metadata-confirmed'?'konfirmasi padanan metadata':'konfirmasi kandidat duplikat',checked:!!$id(id)?.checked});
 function requirementList(){
  const decision=$id('ki-decision')?.value;
  if(!['created','merged'].includes(decision))return [];
  const list=[];
  const locationChoice=decision==='created'?'use':(root.querySelector('.ki-choice[data-field="id_lokasi"]')?.value||'keep');
  if(locationChoice==='use')list.push(checkable('ki-location-confirmed'));
  list.push(checkable('ki-metadata-confirmed'));
  if(decision==='created'&&Boolean(current?.duplicate))list.push(checkable('ki-duplicate-confirmed'));
  return list;
 }
 function refreshRequirements(show=false){
  const box=$id('ki-requirements');if(!box)return [];
  const list=requirementList(),missing=list.filter(v=>!v.checked);
  box.innerHTML='';
  if(!list.length){const span=document.createElement('span');span.className='small text-muted';span.textContent='Tidak ada konfirmasi wajib untuk keputusan ini.';box.append(span);return [];}
  const badge=document.createElement('span');badge.className='badge '+(missing.length?'bg-warning text-dark':'bg-success');badge.textContent=`Konfirmasi wajib ${list.length-missing.length}/${list.length}`;box.append(badge);
  list.forEach(v=>$id(v.id)?.closest('label')?.classList.toggle('is-required-missing',!v.checked));
  if(missing.length){const ul=document.createElement('ul');ul.className='small text-danger mb-0 mt-1';missing.forEach(v=>{const li=document.createElement('li');li.textContent='Belum dicentang: '+v.label;ul.append(li);});box.append(ul);}
  if(show&&missing.length){$id(missing[0].id)?.focus();$id(missing[0].id)?.scrollIntoView({block:'center',behavior:'smooth'});}
  return missing;
 }
 function refreshImagePreview(){
  const input=$id('ki-field-url_gambar_eksternal'),box=$id('ki-image-preview');if(!input||!box)return;
  const {url:raw,old}=effectiveImageUrl();let url='';
  if(/^https?:\/\//i.test(raw)){try{const parsed=new URL(raw);if(['http:','https:'].includes(parsed.protocol)&&!parsed.username&&!parsed.password)url=parsed.href;}catch(e){}}
  box.innerHTML='';
  if(!url){const span=document.createElement('span');span.className='small text-muted';span.textContent='Belum ada URL gambar eksternal yang dapat ditampilkan.';box.append(span);return;}
  const link=document.createElement('a');link.href=url;link.target='_blank';link.rel='noopener noreferrer';
  const img=document.createElement('img');img.src=url;img.alt='Pratinjau gambar eksternal';img.loading='lazy';img.decoding='async';img.referrerPolicy='no-referrer';img.className='img-thumbnail';
  img.addEventListener('error',()=>{const warn=document.createElement('div');warn.className='small text-danger';warn.textContent='Thumbnail tidak dapat dimuat. Periksa URL atau buka gambar langsung.';link.textContent='Buka URL gambar';box.replaceChildren(warn,link);});
  link.append(img);box.append(link);
  const note=document.createElement('div');note.className='small text-muted mt-1';note.textContent=old?'Pratinjau memakai gambar lama yang dipertahankan. Klik untuk membuka URL penuh.':'Klik gambar untuk membuka URL penuh.';box.append(note);
 }
 async function save(){
  const missing=refreshRequirements(true);
  if(missing.length)throw new Error('Centang konfirmasi wajib: '+missing.map(v=>v.label).join(', ')+'.');
  const action=$id('ki-decision').value,fields={},choices={},relations={};
  Object.keys({...fieldDefaults,...current.row.draft.fields}).forEach(f=>{const el=$id('ki-field-'+f);if(el)fields[f]=el.value;});fields.id_lokasi=$id('ki-field-id_lokasi').value;
  root.querySelectorAll('.ki-choice').forEach(s=>choices[s.dataset.field]=s.value);
  ['bahasa','aksara','alas_tulis'].forEach(k=>relations[k]=Array.from($id('ki-rel-'+k).selectedOptions).map(o=>Number(o.value)));
  const structure={record_type:$id('ki-structure-record_type').value,parent_id:$id('ki-structure-parent_id').value,unit_label:$id('ki-structure-unit_label').value,unit_order:$id('ki-structure-unit_order').value,notes:$id('ki-structure-notes').value};
  const identifiers=Array.from(root.querySelectorAll('.ki-identifier-row')).map(row=>({scheme:row.querySelector('.ki-id-scheme').value,value:row.querySelector('.ki-id-value').value,source:row.querySelector('.ki-id-source').value,primary:row.querySelector('.ki-id-primary').checked})).filter(v=>v.scheme.trim()&&v.value.trim());
  const locationHistory=Array.from(root.querySelectorAll('.ki-history-row')).map((row,i)=>({kind:row.querySelector('.ki-hist-kind').value,place:row.querySelector('.ki-hist-place').value,district:row.querySelector('.ki-hist-district').value,province:row.querySelector('.ki-hist-province').value,country:row.querySelector('.ki-hist-country').value,period:row.querySelector('.ki-hist-period').value,certainty:row.querySelector('.ki-hist-certainty').value,source:row.querySelector('.ki-hist-source').value,sort_order:i+1})).filter(v=>v.place.trim()||v.district.trim()||v.province.trim()||v.country.trim());
  const bibliografi=Array.from($id('ki-bibliografi').selectedOptions).map(o=>Number(o.value));
  const payload={row_id:Number(current.row.id),action,fields,choices,relations,structure,identifiers,location_history:locationHistory,bibliografi,target_id:Number(current.snapshot?.data.fields.id||0),revision:current.snapshot?.revision||'',location_confirmed:$id('ki-location-confirmed').checked,metadata_confirmed:$id('ki-metadata-confirmed').checked,duplicate_confirmed:$id('ki-duplicate-confirmed').checked,note:$id('ki-note').value};
  const fd=new FormData();fd.set('payload',JSON.stringify(payload));$id('ki-save').disabled=true;
  try{const result=await api('review',{},fd);bootstrap.Modal.getOrCreateInstance(document.getElementById('ki-modal')).hide();message(result.message+(result.id?' Entri #'+result.id+'.':''));await queue();}catch(e){if(/duplikat/i.test(e.message)){const el=$id('ki-duplicate-confirmed');el?.closest('label')?.classList.add('is-required-missing');el?.focus();}$id('ki-review-message').textContent=e.message;}finally{$id('ki-save').disabled=false;}
 }
 async function deleteBatch(){
  const id=Number($id('ki-batch').value);if(!id)throw new Error('Pilih batch impor terlebih dahulu.');
  if(!window.confirm('Hapus batch impor #'+id+' beserta seluruh baris antreannya? Baris yang sudah diputuskan, identitas tertaut, dan catatan audit membuat penghapusan ditolak.'))return;
  const fd=new FormData();fd.set('batch_id',id);
  const result=await api('delete_batch',{},fd);current=null;headers=[];page=1;await init();message(result.message);
 }
 async function safely(fn,review=false){if(loading)return;loading=true;$id('ki-busy').hidden=false;root.setAttribute('aria-busy','true');try{await fn();}catch(e){if(review)$id('ki-review-message').textContent=e.message;else message(e.message,true);}finally{loading=false;$id('ki-busy').hidden=true;root.setAttribute('aria-busy','false');}}
 $id('ki-inspect').addEventListener('click',()=>safely(inspect));
 $id('ki-file').addEventListener('change',fileInfo);
 const dropzone=$id('ki-dropzone');['dragenter','dragover'].forEach(event=>dropzone.addEventListener(event,e=>{e.preventDefault();dropzone.classList.add('is-dragging');}));['dragleave','drop'].forEach(event=>dropzone.addEventListener(event,e=>{e.preventDefault();dropzone.classList.remove('is-dragging');}));dropzone.addEventListener('drop',e=>{if(loading)return;if(e.dataTransfer.files.length!==1){message('Pilih satu CSV per unggahan.',true);return;}const dt=new DataTransfer();dt.items.add(e.dataTransfer.files[0]);$id('ki-file').files=dt.files;fileInfo();});
 $id('ki-profile').addEventListener('change',()=>{if(headers.length)mapping();});
 $id('ki-upload').addEventListener('submit',e=>{e.preventDefault();safely(async()=>{
  if(!headers.length)throw new Error('Baca CSV terlebih dahulu.');const fd=new FormData();fd.set('csv_file',$id('ki-file').files[0]);fd.set('source_label',$id('ki-label').value);fd.set('bibliography_id',$id('ki-bib').value);const map={};root.querySelectorAll('.ki-map-select').forEach(s=>{if(s.value)map[s.dataset.field]=s.value;});fd.set('mapping',JSON.stringify(map));const result=await api('upload',{},fd);page=1;headers=[];await init(result.batch_id);mappingStatus();setStep('moderation');message(result.message);
 });});
 $id('ki-batch').addEventListener('change',()=>{page=1;safely(queue);});$id('ki-filter').addEventListener('change',()=>{page=1;safely(queue);});
 $id('ki-refresh').addEventListener('click',()=>safely(queue));
 $id('ki-delete-batch').addEventListener('click',()=>safely(deleteBatch));
 root.addEventListener('click',e=>{
  const step=e.target.closest('[data-step]');if(step&&!step.disabled){setStep(step.dataset.step);return;}
  const status=e.target.closest('[data-status]');if(status){$id('ki-filter').value=status.dataset.status;page=1;safely(queue);return;}
  const jump=e.target.closest('[data-review-section]');if(jump){const body=$id('ki-review'),target=$id(jump.dataset.reviewSection);body.scrollTo({top:target&&!target.hidden?target.getBoundingClientRect().top-body.getBoundingClientRect().top+body.scrollTop:0,behavior:'smooth'});return;}
  const openBtn=e.target.closest('.ki-open');if(openBtn){safely(()=>open(Number(openBtn.dataset.id)));return;}
  const target=e.target.closest('.ki-target');if(target){safely(()=>open(Number(current.row.id),Number(target.dataset.target),Number($id('ki-match-location').value),$id('ki-historical').checked),true);return;}
  if(e.target.closest('#ki-find'))safely(()=>open(Number(current.row.id),0,Number($id('ki-match-location').value),$id('ki-historical').checked),true);
  if(e.target.closest('#ki-load-target'))safely(async()=>{const id=Number($id('ki-target-manual').value);if(!Number.isInteger(id)||id<1)throw new Error('Masukkan ID entri tujuan.');await open(Number(current.row.id),id,Number($id('ki-match-location').value),$id('ki-historical').checked);},true);
  if(e.target.closest('#ki-add-identifier')){$id('ki-identifiers').insertAdjacentHTML('beforeend',identifierRow());return;}
  if(e.target.closest('#ki-add-history')){$id('ki-history').insertAdjacentHTML('beforeend',historyRow());return;}
  if(e.target.closest('#ki-prev')){page--;safely(queue);}if(e.target.closest('#ki-next')){page++;safely(queue);}
 });
 root.addEventListener('change',e=>{if(e.target.id==='ki-decision'){toggleFields();refreshRequirements();refreshImagePreview();}if(e.target.classList.contains('ki-map-select'))mappingStatus();if(e.target.classList.contains('ki-choice')){e.target.closest('.ki-field-row')?.classList.toggle('ki-selected',e.target.value!=='keep');refreshRequirements();refreshImagePreview();}if(e.target.id&&e.target.id.endsWith('-confirmed'))refreshRequirements();});
 root.addEventListener('input',e=>{if(e.target.id==='ki-field-url_gambar_eksternal')refreshImagePreview();});
 $id('ki-save').addEventListener('click',()=>safely(save,true));
 safely(()=>init());
})();
