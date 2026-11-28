(() => {
  'use strict';
  const root=document.getElementById('dreamsea-importer'); if(!root || root.dataset.initialized)return; root.dataset.initialized='1';
  const $id=id=>document.getElementById(id), esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const labels={judul:'Judul',kode_inventaris:'Kode inventaris',deskripsi_fisik:'Deskripsi fisik',deskripsi_isi:'Deskripsi isi',riwayat_naskah:'Riwayat naskah',kata_kunci:'Kata kunci',penyedia_akses:'Penyedia akses',url_web:'URL web',url_repositori:'URL repositori',iiif_manifest:'IIIF manifest',url_pdf:'URL PDF',url_gambar_eksternal:'URL gambar eksternal'};
  const statuses={pending:'Menunggu',deferred:'Ditunda',rejected:'Dilewati',created:'Dibuat',merged:'Digabung'};
  const savedStatuses=['created','merged'];
  const savedMark=status=>savedStatuses.includes(status)?'<i class="fa-solid fa-circle-check text-success ms-1" aria-label="Sudah tersimpan"></i>':'';
  const savedBadge=(status,target)=>{
    const label=esc(statuses[status]||status);
    if(status==='rejected')return '<span class="badge bg-secondary">'+label+'</span>';
    if(!savedStatuses.includes(status))return label;
    const id=Number(target||0);
    return '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Tersimpan · '+label+'</span>'+(id?'<br><small><a href="'+esc(root.dataset.base)+'/detail.php?id='+id+'" target="_blank" rel="noopener">Lihat #'+id+'</a></small>':'');
  };
  const historyKinds={asal:'Asal',ditemukan:'Ditemukan',sebelumnya:'Sebelumnya'};
  const certainties={pasti:'Pasti',kemungkinan:'Kemungkinan',dugaan:'Dugaan',belum_diketahui:'Belum diketahui'};
  const historyRow=v=>`<div class="row g-1 ds-history-row mb-2"><div class="col-md-2"><select class="form-select form-select-sm ds-hist-kind">${Object.entries(historyKinds).map(([k,l])=>`<option value="${k}" ${(v?.kind||'asal')===k?'selected':''}>${l}</option>`).join('')}</select></div><div class="col-md-3"><input class="form-control form-control-sm ds-hist-place" placeholder="Tempat/koleksi" maxlength="255" value="${esc(v?.place||'')}"></div><div class="col-md-2"><input class="form-control form-control-sm ds-hist-district" placeholder="Kabupaten/kota" maxlength="150" value="${esc(v?.district||'')}"></div><div class="col-md-2"><input class="form-control form-control-sm ds-hist-province" placeholder="Provinsi" maxlength="150" value="${esc(v?.province||'')}"></div><div class="col-md-1"><input class="form-control form-control-sm ds-hist-country" placeholder="Negara" maxlength="150" value="${esc(v?.country||'')}"></div><div class="col-md-2"><input class="form-control form-control-sm ds-hist-period" placeholder="Masa" maxlength="150" value="${esc(v?.period||'')}"></div><div class="col-md-2"><select class="form-select form-select-sm ds-hist-certainty">${Object.entries(certainties).map(([k,l])=>`<option value="${k}" ${(v?.certainty||'belum_diketahui')===k?'selected':''}>${l}</option>`).join('')}</select></div><div class="col-md-2"><input class="form-control form-control-sm ds-hist-source" placeholder="Sumber" maxlength="255" value="${esc(v?.source||'')}"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger ds-remove-history" aria-label="Hapus riwayat lokasi"><i class="fa-solid fa-xmark"></i></button></div></div>`;
  let config,preview=null,page=1,current=null,loading=false;
  const message=(text,error=false)=>{$id('ds-message').className='alert '+(error?'alert-danger':'alert-success');$id('ds-message').textContent=text;};
  const plain=html=>new DOMParser().parseFromString(String(html??'').replace(/<br\s*\/?\s*>/gi,'\n').replace(/<\/p>/gi,'\n'),'text/html').body.textContent;
  async function api(action,params={},data=null){
    const url=new URL(root.dataset.endpoint,location.origin);url.searchParams.set('action',action);Object.entries(params).forEach(([k,v])=>url.searchParams.set(k,v));
    const opts={credentials:'same-origin',headers:{Accept:'application/json'}};if(data){opts.method='POST';opts.body=data;opts.headers['X-CSRF-Token']=root.dataset.csrf;}
    const r=await fetch(url,opts);let result;try{result=await r.json();}catch(e){throw new Error('Respons server tidak terbaca. Periksa batas upload dan log PHP.');}
    if(!r.ok||!result.success)throw new Error(result.message||'Permintaan gagal.');return result;
  }
  function setStep(step){
    if(!config?.ready)return;
    if(step==='preview'&&!preview)return;
    root.querySelectorAll('[data-step-panel]').forEach(p=>p.hidden=p.dataset.stepPanel!==step);
    root.querySelectorAll('.ds-step').forEach(b=>{const active=b.dataset.step===step;b.classList.toggle('is-active',active);if(active)b.setAttribute('aria-current','step');else b.removeAttribute('aria-current');});
    root.querySelectorAll('.ds-step[data-step="preview"]').forEach(b=>b.disabled=!preview);
  }
  function fileInfo(){
    preview=null;root.querySelectorAll('.ds-step[data-step="preview"]').forEach(b=>b.disabled=true);
    const file=$id('ds-file').files[0];$id('ds-file-info').textContent=file?`${file.name} · ${(file.size/1024).toFixed(1)} KB`:'Belum ada berkas dipilih.';setStep('upload');
  }
  function masterOptions(group,selected=[],empty=true){return (empty?'<option value="">— Pilih —</option>':'')+config.masters[group].map(v=>`<option value="${Number(v.id)}" ${selected.map(Number).includes(Number(v.id))?'selected':''}>${esc(v.name)}</option>`).join('');}
  function initBibliografiSearch(selector,modal){
    if(typeof window.jQuery==='undefined'||typeof window.jQuery.fn.select2==='undefined')return;
    const $el=window.jQuery(selector);if(!$el.length||$el.hasClass('select2-hidden-accessible'))return;
    if(!$el.find('option[value=""]').length)$el.prepend('<option value=""></option>');
    const opts={width:'100%',minimumResultsForSearch:0,placeholder:'Ketik untuk mencari bibliografi…',language:{noResults:()=>'Bibliografi tidak ditemukan',searching:()=>'Mencari…'}};
    if(modal)opts.dropdownParent=window.jQuery(modal);
    if($el.prop('multiple'))opts.closeOnSelect=false;
    $el.select2(opts);
  }
  async function init(batchId){
    config=await api('init');$id('ds-setup').hidden=config.ready;$id('ds-work').hidden=!config.ready;if(!config.ready)return;
    const options=config.batches.map(b=>`<option value="${Number(b.id)}">#${Number(b.id)} · ${esc(b.filename)} · ${esc(b.source_label)}</option>`).join('');
    $id('ds-batch').innerHTML=options||'<option value="">Belum ada batch</option>';
    if(batchId)$id('ds-batch').value=batchId;
    if($id('ds-batch').value){await queue();setStep('moderation');}else {setStep('upload');$id('ds-queue').textContent='Unggah CSV untuk membuat antrean pertama.';}
  }
  async function inspect(){
    const file=$id('ds-file').files[0];if(!file)throw new Error('Pilih CSV terlebih dahulu.');
    const fd=new FormData();fd.set('csv_file',file);preview=await api('inspect',{},fd);
    const bad=preview.preview.filter(p=>p.error).length;
    $id('ds-preview-status').textContent=`${preview.count} entri · ${bad?bad+' error':'valid'} · ${preview.headers.length} kolom`;
    $id('ds-preview-status').className='badge '+(bad?'bg-danger':'bg-success');
    $id('ds-preview').innerHTML='<table class="table table-sm table-bordered"><thead><tr><th>msid / DS</th><th>Judul</th><th>Lokasi</th><th>Bahasa</th><th>Catatan</th></tr></thead><tbody>'+preview.preview.map(p=>p.error?`<tr class="table-danger"><td colspan="5">${esc(p.error)}</td></tr>`:`<tr><td>${esc(p.msid||p.ds_id)}<br><small>${esc(p.ds_id)}</small></td><td>${esc(p.judul)}</td><td>${esc(p.lokasi)}</td><td>${esc(p.bahasa)}</td><td></td></tr>`).join('')+'</tbody></table>';
    setStep('preview');message(`${preview.count} entri terbaca. Periksa pratinjau sebelum menyimpan ke antrean.`);
  }
  async function stage(){
    if(!preview)throw new Error('Baca CSV terlebih dahulu.');
    const fd=new FormData();fd.set('csv_file',$id('ds-file').files[0]);fd.set('source_label',$id('ds-label').value.trim()||'Web');
    const result=await api('upload',{},fd);preview=null;page=1;await init(result.batch_id);message(result.message);
  }
  async function queue(){
    const id=$id('ds-batch').value;if(!id)return;
    const result=await api('queue',{batch_id:id,page,status:$id('ds-filter').value});page=result.page;
    const totals=Object.fromEntries(result.counts.map(x=>[x.status,Number(x.n)]));const all=Object.values(totals).reduce((a,b)=>a+b,0);const final=(totals.created||0)+(totals.merged||0)+(totals.rejected||0);const pct=all?Math.round(final/all*100):0;
    $id('ds-summary').innerHTML=['pending','deferred','rejected','created','merged'].map(status=>`<button type="button" data-status="${status}" class="${$id('ds-filter').value===status?'is-active':''}"><strong>${totals[status]||0}</strong>${esc(statuses[status])}${savedMark(status)}</button>`).join('');
    $id('ds-progress-text').textContent=`${final}/${all} · ${pct}%`;$id('ds-progress-bar').style.width=pct+'%';$id('ds-progress-bar').setAttribute('aria-valuenow',pct);
    $id('ds-counts').textContent=result.counts.map(x=>`${statuses[x.status]||x.status}: ${x.n}`).join(' · ');
    $id('ds-queue').innerHTML=result.rows.length?'<table class="table table-sm table-striped"><thead><tr><th>Entri</th><th>Identitas</th><th>Judul / inventaris</th><th>Status</th><th>Aksi</th></tr></thead><tbody>'+result.rows.map(r=>`<tr class="${savedStatuses.includes(r.status)?'is-saved':''}"><td>${Number(r.source_row)}</td><td>${esc(r.msid||'—')}${r.linked_id?'<br><small>Terhubung #'+Number(r.linked_id)+'</small>':''}</td><td>${esc(r.title||'[Tanpa judul]')}<br><small>${esc(r.inventory||'Tanpa inventaris')}</small></td><td>${savedBadge(r.status,r.target_id)}</td><td><button class="btn btn-sm btn-outline-primary ds-open" data-id="${Number(r.id)}"><i class="fa-solid fa-magnifying-glass me-1"></i>Periksa</button></td></tr>`).join('')+'</tbody></table>':'Tidak ada entri untuk filter ini.';
    $id('ds-pagination').innerHTML=`<button class="btn btn-sm btn-outline-secondary" id="ds-prev" ${page<=1?'disabled':''}><i class="fa-solid fa-chevron-left me-1"></i>Sebelumnya</button><span>${page} / ${result.pages} · ${result.total} entri</span><button class="btn btn-sm btn-outline-secondary" id="ds-next" ${page>=result.pages?'disabled':''}>Berikutnya<i class="fa-solid fa-chevron-right ms-1"></i></button>`;
  }
  async function open(id,target=0){
    current=await api('row',{row_id:id,target_id:target});render();bootstrap.Modal.getOrCreateInstance(document.getElementById('ds-modal')).show();
  }
  function render(){
    const {row,suggestions,candidates,duplicate,snapshot,linked,audit}=current,src=row.draft.source,fields=row.draft.fields,old=snapshot?.data;
    const done=!['pending','deferred'].includes(row.status);
    $id('ds-save').disabled=done;$id('ds-review-message').textContent='';
    let html=`<p><strong>${esc(row.source_label)}</strong> · ${esc(src.msid||'')} ${esc(src.ds_id||'')} · baris ${Number(row.source_row)}.</p>`;
    if(linked)html+=`<div class="alert alert-info">Identitas sumber sudah ditautkan ke entri #${Number(linked.katalog_id)}. Entri baru ditolak; gunakan tombol ini untuk membandingkan dan memperbarui entri tersebut. <button type="button" class="btn btn-sm btn-outline-primary ds-target" data-id="${Number(linked.katalog_id)}"><i class="fa-solid fa-code-merge me-1"></i>Bandingkan #${Number(linked.katalog_id)}</button></div>`;
    if(suggestions.unresolved.length)html+='<div class="alert alert-warning">Istilah belum cocok dengan daftar Kandaga: '+suggestions.unresolved.map(v=>`${esc(v.group)}: ${esc(v.term)}`).join('; ')+'. Pilih padanan yang tersedia; tidak ada master baru yang dibuat otomatis.</div>';
    const loc=row.draft.location||{place:src.Location_Place,district:src.Location_District,province:src.Location_Province,country:src.Location_Country};
    const locLabels=[['Desa/kecamatan',loc.place],['Kabupaten/kota',loc.district],['Provinsi',loc.province],['Negara',loc.country]];
    html+='<div class="alert alert-secondary py-2"><strong>Lokasi historis dari sumber:</strong> '+locLabels.filter(v=>v[1]).map(v=>`${esc(v[0])}: ${esc(v[1])}`).join(' · ')+'<br><small>Lokasi penyimpanan saat ini tetap dipilih manual. Riwayat lokasi historis dapat dikurasi pada field di bawah.</small></div>';
    html+=`<div class="ds-history-block mb-3"><div class="row g-2"><div class="col-md-3"><label>Riwayat lokasi historis</label><div class="small text-muted">Field ini selalu tersedia. Perubahan pada keputusan Tunda/Lewati disimpan ke draf antrean; hanya keputusan Buat entri baru/Merge yang menulis ke katalog.</div></div><div class="col-md-9"><div id="ds-history">${(row.draft.location_history||[]).map(historyRow).join('')}</div><button type="button" class="btn btn-sm btn-outline-secondary" id="ds-add-history"><i class="fa-solid fa-plus me-1"></i>Tambah riwayat lokasi</button></div></div></div>`;
    if(candidates.length)html+='<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kandidat</th><th>Kode Inventaris</th><th>Alasan</th><th>Aksi</th></tr></thead><tbody>'+candidates.map(c=>`<tr><td>#${Number(c.id)} · ${esc(c.judul)}</td><td>${esc(c.kode_inventaris||'—')}</td><td>${esc(c.reason)}</td><td><button class="btn btn-sm btn-outline-primary ds-target" data-id="${Number(c.id)}"><i class="fa-solid fa-code-merge me-1"></i>Bandingkan</button> <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="${esc(root.dataset.base)}/detail.php?id=${Number(c.id)}"><i class="fa-solid fa-eye"></i></a></td></tr>`).join('')+'</tbody></table></div>';
    else html+='<p class="text-muted">Belum ada kandidat otomatis. Tetap periksa identitas sumber sebelum membuat entri baru.</p>';
    html+='<div class="row g-2"><div class="mb-3 col-md-6"><label for="ds-target-manual">Bandingkan ID entri lain</label><div class="input-group"><input id="ds-target-manual" type="number" min="1" class="form-control" placeholder="ID katalog"><button id="ds-load-target" class="btn btn-outline-primary"><i class="fa-solid fa-code-merge me-1"></i>Bandingkan</button></div></div>';
    html+='<div class="mb-3 col-md-6"><label for="ds-decision">Keputusan</label><select id="ds-decision" class="form-select">';
    if(!linked)html+='<option value="created">Buat entri baru</option>';
    if(old)html+='<option value="merged" selected>Merge ke #'+Number(old.fields.id)+'</option>';
    html+='<option value="deferred">Tunda</option><option value="rejected">Lewati</option></select></div></div>';
    if(old)html+=`<div class="alert alert-info">Membandingkan dengan #${Number(old.fields.id)} · ${esc(old.fields.judul)}. Setiap detail dapat dipilih: pertahankan data lama, gunakan draf yang diedit, atau tambahkan draf dengan atribusi sumber. Data lama dipertahankan kecuali fieldnya masih kosong.</div>`;
    html+='<details class="mb-3"><summary>Metadata asli sumber web</summary><div class="ds-source">'+esc(JSON.stringify(row.raw,null,2))+'</div></details>';
    const locChoice=old&&old.fields.id_lokasi?'<select class="form-select form-select-sm mt-2 ds-choice" data-field="id_lokasi" aria-label="Pilihan lokasi"><option value="keep" selected>Pertahankan lokasi sekarang</option><option value="use">Gunakan lokasi pilihan</option></select>':'';
    html+='<div id="ds-edit-fields"><div class="row g-2"><div class="mb-3 col-md-6"><label for="ds-id-lokasi">Lokasi penyimpanan saat ini</label><select id="ds-id-lokasi" class="form-select">'+masterOptions('lokasi',suggestions.lokasi?[suggestions.lokasi]:[])+'</select>'+locChoice+'<label class="small mt-2"><input id="ds-location-confirmed" type="checkbox"> Saya sudah memeriksa lokasi ini; koleksi historis tidak dipindahkan otomatis.</label></div>';
    ['bahasa','aksara','alas_tulis'].forEach(k=>{const chosen=suggestions[k]||[];const relChoice=old?`<select class="form-select form-select-sm mt-2 ds-choice" data-field="${k}" aria-label="Pilihan relasi ${esc(k)}"><option value="keep" selected>Pertahankan relasi lama</option><option value="union">Tambahkan pilihan ke relasi lama</option><option value="replace">Gunakan pilihan sebagai pengganti</option></select>`:'';html+=`<div class="mb-3 col-md-6"><label for="ds-rel-${k}">${esc(k)} (pilih yang tersedia)</label><select id="ds-rel-${k}" class="form-select" multiple size="4">${masterOptions(k,chosen,false)}</select>${relChoice}</div>`;});
    html+='</div><div class="row g-2">';
    Object.entries(fields).forEach(([f,value])=>{
      if(f==='id_lokasi')return;
      const text=['deskripsi_fisik','deskripsi_isi','riwayat_naskah'].includes(f);
      const preview=f==='url_gambar_eksternal'?'<div id="ds-image-preview" class="ds-image-preview mt-2"><span class="small text-muted">Belum ada pratinjau gambar eksternal.</span></div>':'';
      const oldValue=old?String(old.fields[f]??''):'';
      const choice=old?`<select class="form-select form-select-sm mt-2 ds-choice" data-field="${esc(f)}" aria-label="Pilihan nilai ${esc(labels[f]||f)}"><option value="keep" selected>Pertahankan data lama</option><option value="use">Gunakan draf yang diedit</option>${text?'<option value="append">Tambahkan draf dengan atribusi sumber</option>':''}</select>`:'';
      html+=`<div class="mb-3 col-md-6"><label for="ds-field-${f}">${esc(labels[f]||f)}</label>${text?`<textarea id="ds-field-${f}" class="form-control" rows="4">${esc(value)}</textarea>`:`<input id="ds-field-${f}" class="form-control" maxlength="${f==='judul'||f==='kode_inventaris'||f==='kata_kunci'||f==='penyedia_akses'?255:2048}" value="${esc(value)}">`}${old?`<div class="ds-old mt-1">Sekarang: ${esc(plain(oldValue))}</div>${choice}`:''}${preview}</div>`;
    });
    html+='</div><label class="mt-3"><input id="ds-metadata-confirmed" type="checkbox"> Saya sudah memeriksa padanan bahasa, aksara, alas tulis, dan lokasi.</label><br><label class="mt-2"><input id="ds-duplicate-confirmed" type="checkbox"> Jika ada kandidat duplikat, saya sudah memeriksa bahwa entri ini berbeda.</label>';
    html+='<div id="ds-requirements" class="ds-requirements mt-3" aria-live="polite"></div>';
    const bib=row.draft.bibliografi||[];html+='<div class="mb-3 mt-3"><label for="ds-bibliografi">Bibliografi terkait (opsional, dapat lebih dari satu)</label><select id="ds-bibliografi" class="form-select" multiple size="4">'+masterOptions('bibliografi',bib,false)+'</select></div>';
    html+='</div><div class="mb-3 mt-3"><label for="ds-note">Catatan keputusan</label><textarea id="ds-note" class="form-control" rows="2" maxlength="4000">'+esc(row.note||'')+'</textarea></div>';
    html+='<details><summary>Riwayat moderasi</summary>'+audit.map(a=>`<p>${esc(a.created_at)} · ${esc(a.username)} · ${esc(statuses[a.action]||a.action)} · ${esc(a.note)}</p>`).join('')+'</details>';
    $id('ds-review').innerHTML=html;initBibliografiSearch('#ds-bibliografi','#ds-modal');$id('ds-decision').disabled=done;toggleFields();refreshRequirements();refreshImagePreview();
  }
  function syncChoiceStates(){
    const merged=$id('ds-decision')?.value==='merged';
    root.querySelectorAll('.ds-choice').forEach(s=>{
      s.disabled=!merged;
      s.closest('.mb-3')?.classList.toggle('ds-selected',merged&&s.value!=='keep');
    });
  }
  function toggleFields(){const decision=$id('ds-decision')?.value;$id('ds-edit-fields').hidden=!['created','merged'].includes(decision);syncChoiceStates();}
  function effectiveImageUrl(){
    const input=$id('ds-field-url_gambar_eksternal');if(!input)return {url:'',old:false};
    const kept=String(current?.snapshot?.data?.fields?.url_gambar_eksternal||'').trim();
    if($id('ds-decision')?.value==='merged'&&root.querySelector('.ds-choice[data-field="url_gambar_eksternal"]')?.value==='keep'&&kept!=='')return {url:kept,old:true};
    return {url:input.value.trim(),old:false};
  }
  const checkable=id=>({id,label:id==='ds-location-confirmed'?'konfirmasi lokasi penyimpanan':id==='ds-metadata-confirmed'?'konfirmasi padanan metadata':'konfirmasi kandidat duplikat',checked:!!$id(id)?.checked});
  function requirementList(){
    const decision=$id('ds-decision')?.value;
    if(!['created','merged'].includes(decision))return [];
    const list=[checkable('ds-metadata-confirmed')];
    const locationChoice=root.querySelector('.ds-choice[data-field="id_lokasi"]')?.value||'use';
    if(decision==='created'||locationChoice==='use')list.unshift(checkable('ds-location-confirmed'));
    if(decision==='created'&&Number(current?.duplicate||0)>0)list.push(checkable('ds-duplicate-confirmed'));
    return list;
  }
  function refreshRequirements(show=false){
    const box=$id('ds-requirements');if(!box)return [];
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
    const box=$id('ds-image-preview');if(!box)return;
    const effective=effectiveImageUrl(),raw=effective.url;let url='';
    if(/^https?:\/\//i.test(raw)){try{const parsed=new URL(raw);if(['http:','https:'].includes(parsed.protocol)&&!parsed.username&&!parsed.password)url=parsed.href;}catch(e){}}
    box.innerHTML='';
    if(!url){const span=document.createElement('span');span.className='small text-muted';span.textContent='Belum ada URL gambar eksternal yang dapat ditampilkan.';box.append(span);return;}
    const link=document.createElement('a');link.href=url;link.target='_blank';link.rel='noopener noreferrer';
    const img=document.createElement('img');img.src=url;img.alt='Pratinjau gambar eksternal';img.loading='lazy';img.decoding='async';img.referrerPolicy='no-referrer';img.className='img-thumbnail';
    img.addEventListener('error',()=>{const warn=document.createElement('div');warn.className='small text-danger';warn.textContent='Thumbnail tidak dapat dimuat. Periksa URL atau buka gambar langsung.';link.textContent='Buka URL gambar';box.replaceChildren(warn,link);});
    link.append(img);box.append(link);
    const note=document.createElement('div');note.className='small text-muted mt-1';note.textContent=(effective.old?'Pratinjau dari data lama yang dipertahankan. ':'Pratinjau dari draf yang diedit. ')+'Klik gambar untuk membuka URL penuh.';box.append(note);
  }
  async function save(){
    const missing=refreshRequirements(true);
    if(missing.length)throw new Error('Centang konfirmasi wajib: '+missing.map(v=>v.label).join(', ')+'.');
    const action=$id('ds-decision').value,fields={},relations={};
    Object.keys(current.row.draft.fields).forEach(f=>{const el=$id('ds-field-'+f);if(el)fields[f]=el.value;});
    ['bahasa','aksara','alas_tulis'].forEach(k=>relations[k]=Array.from($id('ds-rel-'+k).selectedOptions).map(o=>Number(o.value)));
    const locationHistory=Array.from(root.querySelectorAll('.ds-history-row')).map((row,i)=>({kind:row.querySelector('.ds-hist-kind').value,place:row.querySelector('.ds-hist-place').value,district:row.querySelector('.ds-hist-district').value,province:row.querySelector('.ds-hist-province').value,country:row.querySelector('.ds-hist-country').value,period:row.querySelector('.ds-hist-period').value,certainty:row.querySelector('.ds-hist-certainty').value,source:row.querySelector('.ds-hist-source').value,sort_order:i+1})).filter(v=>v.place.trim()||v.district.trim()||v.province.trim()||v.country.trim());
    const choices={};root.querySelectorAll('.ds-choice').forEach(s=>{if(s.dataset.field)choices[s.dataset.field]=s.value;});
    const payload={row_id:Number(current.row.id),action,fields,relations,choices,location_history:locationHistory,id_lokasi:$id('ds-id-lokasi').value,bibliografi:Array.from($id('ds-bibliografi').selectedOptions).map(o=>Number(o.value)),target_id:Number(current.snapshot?.data.fields.id||0),revision:current.snapshot?.revision||'',location_confirmed:$id('ds-location-confirmed').checked,metadata_confirmed:$id('ds-metadata-confirmed').checked,duplicate_confirmed:$id('ds-duplicate-confirmed').checked,note:$id('ds-note').value};
    const fd=new FormData();fd.set('payload',JSON.stringify(payload));$id('ds-save').disabled=true;
    try{const result=await api('review',{},fd);bootstrap.Modal.getOrCreateInstance(document.getElementById('ds-modal')).hide();message(result.message+(result.id?' Entri #'+result.id+'.':''));await queue();}catch(e){
      if(/tujuan merge|ditautkan ke entri/i.test(e.message)){const el=$id('ds-target-manual');el?.focus();el?.scrollIntoView({block:'center',behavior:'smooth'});}
      else if(/duplikat|identitas sumber/i.test(e.message)){const el=$id('ds-duplicate-confirmed');el?.closest('label')?.classList.add('is-required-missing');el?.focus();}
      $id('ds-review-message').textContent=e.message;
    }finally{$id('ds-save').disabled=false;}
  }
  async function deleteBatch(){
    const id=Number($id('ds-batch').value);if(!id)throw new Error('Pilih batch Importer Web terlebih dahulu.');
    const text=config?.batch_delete_detachable
      ? 'Hapus batch Importer Web #'+id+' beserta seluruh baris antreannya? Entri katalog yang sudah dibuat tetap tersimpan, termasuk tautan identitas dan catatan audit. Baris yang ditunda dan belum diterapkan akan ikut hilang.'
      : 'Hapus batch Importer Web #'+id+' beserta seluruh baris antreannya? Baris yang sudah diputuskan, identitas tertaut, dan catatan audit membuat penghapusan ditolak.';
    if(!window.confirm(text))return;
    const fd=new FormData();fd.set('batch_id',id);
    const result=await api('delete_batch',{},fd);current=null;preview=null;page=1;await init();message(result.message);
  }
  async function safely(fn,review=false){if(loading)return;loading=true;$id('ds-busy').hidden=false;root.setAttribute('aria-busy','true');try{await fn();}catch(e){if(review)$id('ds-review-message').textContent=e.message;else message(e.message,true);}finally{loading=false;$id('ds-busy').hidden=true;root.setAttribute('aria-busy','false');}}
  $id('ds-file').addEventListener('change',fileInfo);
  $id('ds-inspect').addEventListener('click',()=>safely(inspect));
  $id('ds-stage').addEventListener('click',()=>safely(stage));
  $id('ds-refresh').addEventListener('click',()=>safely(queue));
  $id('ds-delete-batch').addEventListener('click',()=>safely(deleteBatch));
  $id('ds-batch').addEventListener('change',()=>{page=1;safely(queue);});
  $id('ds-filter').addEventListener('change',()=>{page=1;safely(queue);});
  root.addEventListener('click',e=>{
    const step=e.target.closest('[data-step]');if(step&&!step.disabled){setStep(step.dataset.step);return;}
    const status=e.target.closest('[data-status]');if(status){$id('ds-filter').value=status.dataset.status;page=1;safely(queue);return;}
    const openBtn=e.target.closest('.ds-open');if(openBtn){safely(()=>open(Number(openBtn.dataset.id)));return;}
    const target=e.target.closest('.ds-target');if(target){safely(()=>open(Number(current.row.id),Number(target.dataset.id)),true);return;}
    if(e.target.closest('#ds-load-target'))safely(async()=>{const id=Number($id('ds-target-manual').value);if(!Number.isInteger(id)||id<1)throw new Error('Masukkan ID entri tujuan.');await open(Number(current.row.id),id);},true);
    if(e.target.closest('#ds-add-history')){$id('ds-history').insertAdjacentHTML('beforeend',historyRow());return;}
    if(e.target.closest('.ds-remove-history')){e.target.closest('.ds-history-row')?.remove();return;}
    if(e.target.closest('#ds-prev')){page--;safely(queue);}if(e.target.closest('#ds-next')){page++;safely(queue);}
  });
  root.addEventListener('change',e=>{
    if(e.target.id==='ds-decision'){toggleFields();refreshRequirements();refreshImagePreview();}
    if(e.target.classList?.contains('ds-choice')){syncChoiceStates();refreshRequirements();refreshImagePreview();}
    if(e.target.id&&e.target.id.endsWith('-confirmed'))refreshRequirements();
  });
  root.addEventListener('input',e=>{if(e.target.id==='ds-field-url_gambar_eksternal')refreshImagePreview();});
  $id('ds-save').addEventListener('click',()=>safely(save,true));
  safely(()=>init());
})();
