<?php
// Printed catalog ingestion: upload to staging, then explicit per-entry moderation.
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/log-tracker.php';
function kiJson($value) { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); }
function kiQuery($sql, $values = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($values) $stmt->bind_param(str_repeat('s', count($values)), ...$values);
    $stmt->execute(); return $stmt;
}
function kiReady() {
    $names = ['katalog_import_batches','katalog_import_rows','katalog_import_sources','katalog_import_audit'];
    $r = kiQuery('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN (?,?,?,?)', $names)->get_result();
    return $r->num_rows === 4;
}
function kiFields() {
    return ['Judul','Kode_Inventaris','Lokasi_Sumber','Bahasa_List','Aksara_List','Alas_Tulis_List','Ukuran','Tebal_Naskah','Jumlah_Baris','Deskripsi_Fisik','Deskripsi_Isi','Riwayat_Naskah','Bentuk_Karangan','Asal_Naskah','Sumber_ID','Bagian_Sumber','Nomor_Entri','Halaman_Cetak','Halaman_PDF','Status_Sumber','Catatan_Koreksi','Teks_Sumber'];
}
function kiNorm($value) {
    return mb_strtolower(preg_replace('/\s+/u',' ',trim((string)$value)), 'UTF-8');
}
function kiCode($value) { return preg_replace('/[^\p{L}\p{N}]/u','',kiNorm($value)); }
function kiCodes($value, $historical = false) {
    $out = [];
    foreach (preg_split('/[;|]/u', $value) as $code) {
        $code = kiCode($code); if ($code === '') continue;
        $out[] = $code;
        if ($historical) {
            // Candidate suggestions only: a bare number is not a universal identifier.
            if (preg_match('/^(?:l|kropak)?(\d+[ab]?)$/', $code, $m)) $out[] = 'lontar:'.$m[1];
            if (preg_match('/^(?:lor|or)(\d+[a-z]?)$/', $code, $m)) $out[] = 'leiden:'.$m[1];
        }
    }
    return array_unique($out);
}
function kiCsv($file) {
    if (!isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('Pilih CSV yang berhasil diunggah; periksa batas upload PHP.');
    if (filesize($file['tmp_name']) > 10485760) throw new InvalidArgumentException('CSV maksimal 10 MB.');
    $h = fopen($file['tmp_name'], 'r'); $rows=[];
    try {
        while (($r=fgetcsv($h, 0, ',', '"', '')) !== false) {
            if ($r === [null]) continue;
            if (!$rows) $r[0]=preg_replace('/^\xEF\xBB\xBF/','',$r[0]);
            foreach ($r as $cell) {
                if (!mb_check_encoding($cell ?? '', 'UTF-8')) throw new InvalidArgumentException('CSV harus UTF-8.');
                if (strlen($cell ?? '') > 262144 || strpos($cell ?? '', "\0") !== false) throw new InvalidArgumentException('Isi sel terlalu besar atau mengandung karakter tidak valid.');
            }
            $rows[]=$r;
            if (count($rows)>5001) throw new InvalidArgumentException('Maksimal 5.000 entri per berkas.');
        }
    } finally { fclose($h); }
    if (count($rows)<2 || count($rows[0])>80 || in_array('', $rows[0], true) || count(array_unique($rows[0]))!==count($rows[0])) throw new InvalidArgumentException('CSV perlu header unik dan minimal satu entri. Gunakan pemisah koma.');
    $headers=array_shift($rows); $out=[];
    foreach($rows as $i=>$r) {
        if(count($r)!==count($headers)) throw new InvalidArgumentException('Jumlah kolom tidak sesuai pada baris data '.($i+1).'.');
        $out[]=array_combine($headers, array_map(fn($v)=>trim($v ?? ''),$r));
    }
    return [$headers,$out];
}
function kiDraft($raw, $mapping) {
    $v=[];
    foreach(kiFields() as $f) $v[$f]=isset($mapping[$f]) ? ($raw[$mapping[$f]] ?? '') : '';
    foreach(['Sumber_ID','Bagian_Sumber','Nomor_Entri','Halaman_Cetak','Halaman_PDF','Status_Sumber'] as $f) if ($v[$f]==='') throw new InvalidArgumentException('Identitas sumber wajib: '.$f.'.');
    foreach(['Judul','Kode_Inventaris','Sumber_ID','Bagian_Sumber','Nomor_Entri','Halaman_Cetak','Halaman_PDF'] as $f) if(mb_strlen($v[$f])>255) throw new InvalidArgumentException($f.' maksimal 255 karakter.');
    $status=kiNorm($v['Status_Sumber'] ?: 'deskriptif');
    if(!in_array($status,['deskriptif','tabel','berita'],true)) throw new InvalidArgumentException('Status_Sumber harus deskriptif, tabel, atau berita.');
    $v['Status_Sumber']=$status;
    $physical=[];
    foreach(['Ukuran'=>'Ukuran naskah','Tebal_Naskah'=>'Tebal naskah','Jumlah_Baris'=>'Jumlah baris'] as $f=>$label) if($v[$f]!=='') $physical[]=$label.': '.$v[$f].'.';
    if($v['Deskripsi_Fisik']!=='') $physical[]=$v['Deskripsi_Fisik'];
    $history=[];
    if($v['Asal_Naskah']!=='') $history[]='Asal naskah menurut katalog: '.$v['Asal_Naskah'].'.';
    if($v['Lokasi_Sumber']!=='') $history[]='Lokasi/pemilik menurut katalog: '.$v['Lokasi_Sumber'].'.';
    if($v['Riwayat_Naskah']!=='') $history[]=$v['Riwayat_Naskah'];
    $content=$v['Deskripsi_Isi'];
    if($v['Bentuk_Karangan']!=='') $content=trim('Bentuk karangan: '.$v['Bentuk_Karangan'].".\n\n".$content);
    return ['source'=>$v,'fields'=>['judul'=>$v['Judul'],'kode_inventaris'=>$v['Kode_Inventaris'],'deskripsi_fisik'=>implode("\n",$physical),'deskripsi_isi'=>$content,'riwayat_naskah'=>implode("\n",$history),'kata_kunci'=>'']];
}
function kiKey($v) { return hash('sha256',kiJson(array_map(fn($f)=>kiNorm($v[$f]),['Sumber_ID','Bagian_Sumber','Nomor_Entri']))); }
function kiMasters() {
    $out=[];
    foreach(['lokasi'=>['tb_lokasi','id_lokasi','nama_tempat'],'bahasa'=>['tb_bahasa','id_bahasa','nama_bahasa'],'aksara'=>['tb_aksara','id_aksara','nama_aksara'],'alas_tulis'=>['tb_alas_tulis','id_alas_tulis','nama_alas_tulis']] as $key=>[$table,$id,$name]) $out[$key]=kiQuery("SELECT $id AS id,$name AS name FROM $table ORDER BY $name")->get_result()->fetch_all(MYSQLI_ASSOC);
    $out['bibliografi']=kiQuery("SELECT id, judul AS name FROM tb_bibliografi ORDER BY judul")->get_result()->fetch_all(MYSQLI_ASSOC);
    return $out;
}
function kiSuggestions($source,$masters) {
    $out=['lokasi'=>null,'bahasa'=>[],'aksara'=>[],'alas_tulis'=>[],'unresolved'=>[]];
    foreach(['lokasi'=>'Lokasi_Sumber','bahasa'=>'Bahasa_List','aksara'=>'Aksara_List','alas_tulis'=>'Alas_Tulis_List'] as $key=>$f) {
        $terms=$key==='lokasi'?[$source[$f]]:preg_split('/[,;|]/u',$source[$f]);
        foreach($terms as $term) {
            if(trim($term)==='') continue;
            $found=null;
            foreach($masters[$key] as $item) if(kiNorm($item['name'])===kiNorm($term)) {$found=(int)$item['id'];break;}
            if($found===null) $out['unresolved'][]=['group'=>$key,'term'=>$term];
            elseif($key==='lokasi') $out[$key]=$found;
            else $out[$key][]=$found;
        }
    }
    return $out;
}
function kiSnapshot($id,$lock=false) {
    $suffix=$lock?' FOR UPDATE':'';
    $row=kiQuery('SELECT * FROM katalog WHERE id=?'.$suffix,[$id])->get_result()->fetch_assoc();
    if(!$row) throw new InvalidArgumentException('Entri tujuan sudah tidak tersedia.');
    $relations=[];
    foreach(['bahasa'=>['naskah_bahasa','id_bahasa'],'aksara'=>['naskah_aksara','id_aksara'],'alas_tulis'=>['naskah_alas_tulis','id_alas_tulis'],'bibliografi'=>['naskah_bibliografi','id_bibliografi']] as $k=>[$t,$c]) {
        $relations[$k]=array_map('intval',array_column(kiQuery("SELECT $c FROM $t WHERE id_naskah=? ORDER BY $c".$suffix,[$id])->get_result()->fetch_all(MYSQLI_ASSOC),$c));
    }
    $data=['fields'=>$row,'relations'=>$relations];
    return ['data'=>$data,'revision'=>hash('sha256',kiJson($data))];
}
function kiCandidates($draft,$location=0,$historical=false) {
    $codes=kiCodes($draft['source']['Kode_Inventaris'],$historical); $title=kiCode($draft['fields']['judul']); $out=[];
    // Scan identifiers once: no arbitrary title-only automatic merges.
    $rows=kiQuery('SELECT k.id,k.judul,k.kode_inventaris,k.id_lokasi,l.nama_tempat FROM katalog k LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi')->get_result();
    while($r=$rows->fetch_assoc()) {
        $reason=[];$score=0;
        if($codes && array_intersect($codes,kiCodes($r['kode_inventaris'],$historical))) {$score=80;$reason[]='Kode sama/varian yang diizinkan';}
        $other=kiCode($r['judul']);
        if($title!=='' && $title===$other) {$score=max($score,60);$reason[]='Judul sama';}
        elseif(mb_strlen($title)>=6 && mb_strlen($other)>=6) {
            similar_text($title,$other,$sim);
            if($sim>=80) {$score=max($score,40);$reason[]='Judul mirip';}
        }
        if(!$score)continue;
        if($location && (int)$r['id_lokasi']===$location) {$score+=15;$reason[]='Lokasi pilihan sama';}
        elseif($location) $reason[]='Lokasi berbeda; bisa salinan lain';
        $r['score']=$score;$r['reason']=implode('; ',$reason);$out[]=$r;
    }
    usort($out,fn($a,$b)=>$b['score']<=>$a['score'] ?: (int)$a['id']<=>(int)$b['id']);
    return array_slice($out,0,20);
}
function kiRow($id,$lock=false) {
    $row=kiQuery('SELECT r.*,b.source_label,b.bibliography_id FROM katalog_import_rows r JOIN katalog_import_batches b ON b.id=r.batch_id WHERE r.id=?'.($lock?' FOR UPDATE':''),[$id])->get_result()->fetch_assoc();
    if(!$row)throw new InvalidArgumentException('Baris impor tidak ditemukan.');
    $row['raw']=json_decode($row['raw_json'],true,512,JSON_THROW_ON_ERROR);$row['draft']=json_decode($row['draft_json'],true,512,JSON_THROW_ON_ERROR);
    unset($row['raw_json'],$row['draft_json']);return $row;
}
function kiLinked($key) { return kiQuery('SELECT s.katalog_id,k.judul FROM katalog_import_sources s LEFT JOIN katalog k ON k.id=s.katalog_id WHERE s.source_key=?',[$key])->get_result()->fetch_assoc(); }
function kiHtml($text) {
    // Numeric HTML entities keep descriptive Unicode readable even on legacy latin1 TEXT.
    $html=nl2br(htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),false);
    return mb_encode_numericentity($html,[0x80,0x10ffff,0,0x1fffff],'UTF-8');
}
function kiAudit($row,$action,$target,$before,$after,$decision) {
    kiQuery('INSERT INTO katalog_import_audit(import_row_id,action,katalog_id,before_json,after_json,decision_json,username) VALUES(?,?,?,?,?,?,?)',[$row['id'],$action,$target,$before===null?null:kiJson($before),$after===null?null:kiJson($after),kiJson($decision),$_SESSION['username']]);
    kiQuery('UPDATE katalog_import_rows SET status=?,target_id=?,reviewed_by=?,reviewed_at=NOW(),note=? WHERE id=?',[$action,$target,$_SESSION['username'],(string)($decision['note']??''),$row['id']]);
}
function kiEncoding($fields) {
    // Fail explicitly instead of silently replacing unsupported title/code characters,
    // even when a deployment does not enable strict SQL mode.
    $columns=kiQuery("SELECT COLUMN_NAME AS field_name, CHARACTER_SET_NAME AS field_charset FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='katalog' AND column_name IN ('judul','kode_inventaris','kata_kunci')")->get_result()->fetch_all(MYSQLI_ASSOC);
    // Explicit aliases keep result keys stable across MySQL and MariaDB.
    $expected = ['judul','kode_inventaris','kata_kunci'];
    if(count($columns)!==count($expected))throw new InvalidArgumentException('Charset katalog tidak dapat diverifikasi.');
    $seen = [];
    foreach($columns as $column) {
        $charset=$column['field_charset']??null;$name=$column['field_name']??null;
        if(!in_array($name,$expected,true) || isset($seen[$name]) || !is_string($charset) || !preg_match('/^[a-z0-9_]+$/i',$charset))throw new InvalidArgumentException('Charset katalog tidak dapat diverifikasi.');
        $seen[$name]=true;
        $text=(string)($fields[$name]??'');
        $roundtrip=kiQuery("SELECT CONVERT(CONVERT(? USING $charset) USING utf8mb4) AS value",[$text])->get_result()->fetch_assoc()['value'];
        if($text!==$roundtrip)throw new InvalidArgumentException('Karakter pada '.$name.' belum didukung charset '.$charset.'. Migrasikan kolom katalog ke utf8mb4 sebelum menyetujui entri ini. Teks sumber tetap tersimpan di antrean.');
    }
}
function kiApprove($input) {
    global $conn;
    $id=filter_var($input['row_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $action=$input['action']??'';
    if(!$id || !in_array($action,['created','merged','rejected','deferred'],true)) throw new InvalidArgumentException('Keputusan moderasi tidak valid.');
    if(!is_string($input['note']??'') || mb_strlen($input['note']??'')>4000) throw new InvalidArgumentException('Catatan maksimal 4.000 karakter.');
    $conn->begin_transaction();
    try {
        $row=kiRow($id,true);
        if(!in_array($row['status'],['pending','deferred'],true)) throw new InvalidArgumentException('Baris ini sudah diputuskan. Muat ulang antrean.');
        if(in_array($action,['rejected','deferred'],true)) {
            kiAudit($row,$action,null,null,null,$input);$conn->commit();return ['id'=>null,'action'=>$action];
        }
        if($row['draft']['source']['Status_Sumber']==='berita') throw new InvalidArgumentException('Berita adanya naskah belum dapat diterbitkan. Tunda sampai identitas bendanya terverifikasi; siapkan CSV baru berstatus deskriptif.');
        if(kiLinked($row['source_key'])) throw new InvalidArgumentException('Identitas sumber ini sudah diimpor. Tidak disimpan ulang.');
        $fields=$input['fields']??null; $choices=$input['choices']??[]; $relations=$input['relations']??[];
        if(!is_array($fields)||!is_array($choices)||!is_array($relations)) throw new InvalidArgumentException('Format form moderasi tidak valid.');
        $editable=['judul','kode_inventaris','deskripsi_fisik','deskripsi_isi','riwayat_naskah','kata_kunci'];
        foreach($editable as $f) {
            if(!is_string($fields[$f]??null))throw new InvalidArgumentException('Field '.$f.' tidak valid.');
            $fields[$f]=trim($fields[$f]);
            $max=in_array($f,['judul','kode_inventaris','kata_kunci'],true)?255:50000;
            if(mb_strlen($fields[$f])>$max) throw new InvalidArgumentException($f.' terlalu panjang.');
        }
        $target=null;$before=null;$old=null;
        if($action==='merged') {
            $target=filter_var($input['target_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if(!$target)throw new InvalidArgumentException('Pilih entri tujuan merge.');
            $snap=kiSnapshot($target,true);$old=$snap['data'];$before=$old;
            if(!is_string($input['revision']??null)||!hash_equals($snap['revision'],$input['revision'])) throw new InvalidArgumentException('Entri tujuan berubah sejak pratinjau. Muat ulang dan periksa perbedaannya.');
        }
        $locationChoice=$action==='created'?'use':($choices['id_lokasi']??'keep');
        if(!in_array($locationChoice,['keep','use'],true))throw new InvalidArgumentException('Pilihan lokasi tidak valid.');
        $location=$locationChoice==='keep'?($old['fields']['id_lokasi']??0):($fields['id_lokasi']??0);
        $location=filter_var($location,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$location || !kiQuery('SELECT id_lokasi FROM tb_lokasi WHERE id_lokasi=?',[$location])->get_result()->fetch_assoc()) throw new InvalidArgumentException('Pilih lokasi dari daftar Kandaga.');
        if($locationChoice==='use' && ($input['location_confirmed']??false)!==true) throw new InvalidArgumentException('Konfirmasikan lokasi penyimpanan saat ini; lokasi historis tidak otomatis menjadi lokasi sekarang.');
        $resolved=[];
        foreach($editable as $f) {
            $mode=$action==='created'?'use':($choices[$f]??'keep');
            if(!in_array($mode,['keep','use','append'],true)||($mode==='append'&&!in_array($f,['deskripsi_fisik','deskripsi_isi','riwayat_naskah'],true))) throw new InvalidArgumentException('Pilihan merge tidak valid: '.$f.'.');
            $isText=in_array($f,['deskripsi_fisik','deskripsi_isi','riwayat_naskah'],true);
            $new=$isText?kiHtml($fields[$f]):$fields[$f];
            if($mode==='keep')$resolved[$f]=$old['fields'][$f]??'';
            elseif($mode==='append') {
                if($fields[$f]==='')throw new InvalidArgumentException('Tidak ada teks untuk digabungkan pada '.$f.'.');
                $v=$row['draft']['source'];
                $credit=kiHtml($row['source_label'].'; bagian '.$v['Bagian_Sumber'].', entri '.$v['Nomor_Entri'].', hlm. '.$v['Halaman_Cetak'].'.');
                $resolved[$f]=($old['fields'][$f]??'').'<p><strong>'.$credit.'</strong><br>'.$new.'</p>';
            }else {
                if($action==='merged'&&$fields[$f]===''&&trim(strip_tags($old['fields'][$f]??''))!=='')throw new InvalidArgumentException('Nilai kosong tidak boleh menghapus data lama: '.$f.'. Pilih pertahankan.');
                $resolved[$f]=$new;
            }
            if($isText&&strlen($resolved[$f])>60000)throw new InvalidArgumentException('Gabungan deskripsi terlalu panjang untuk kolom TEXT.');
        }
        if(trim($resolved['judul'])==='')throw new InvalidArgumentException('Judul wajib diisi sebelum membuat entri baru.');
        $candidateDraft=$row['draft'];$candidateDraft['fields']['judul']=$resolved['judul'];$candidateDraft['source']['Kode_Inventaris']=$resolved['kode_inventaris'];
        if(kiSuggestions($row['draft']['source'],kiMasters())['unresolved'] && ($input['metadata_confirmed']??false)!==true) throw new InvalidArgumentException('Periksa istilah yang belum cocok dan konfirmasikan pemetaan metadata.');
        if($action==='created' && kiCandidates($candidateDraft,(int)$location,true) && ($input['duplicate_confirmed']??false)!==true) throw new InvalidArgumentException('Ada kandidat duplikat. Periksa dan konfirmasikan bahwa ini salinan berbeda sebelum membuat entri baru.');
        $groupDefs=['bahasa'=>['naskah_bahasa','id_bahasa','tb_bahasa'],'aksara'=>['naskah_aksara','id_aksara','tb_aksara'],'alas_tulis'=>['naskah_alas_tulis','id_alas_tulis','tb_alas_tulis']];
        $finalRelations=[];
        foreach($groupDefs as $k=>[$table,$col,$master]) {
            $mode=$action==='created'?'replace':($choices[$k]??'keep');
            if(!in_array($mode,['keep','union','replace'],true))throw new InvalidArgumentException('Pilihan relasi tidak valid.');
            $ids=$relations[$k]??[];
            if(!is_array($ids))throw new InvalidArgumentException('Pilihan relasi harus daftar ID.');
            $valid=[];
            foreach($ids as $value) {
                $value=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if(!$value||!kiQuery("SELECT $col FROM $master WHERE $col=?",[$value])->get_result()->fetch_assoc())throw new InvalidArgumentException('Pilihan '.$k.' tidak ditemukan.');
                $valid[]=$value;
            }
            if($mode==='replace'&&$action==='merged'&&!$valid&&!empty($old['relations'][$k]))throw new InvalidArgumentException('Pilihan kosong tidak boleh menghapus relasi lama.');
            $finalRelations[$k]=array_values(array_unique($mode==='keep'?($old['relations'][$k]??[]):($mode==='union'?array_merge($old['relations'][$k]??[],$valid):$valid)));
        }
        $bibliography=(int)$row['bibliography_id'];
        if(!$bibliography || !kiQuery('SELECT id FROM tb_bibliografi WHERE id=?',[$bibliography])->get_result()->fetch_assoc())throw new InvalidArgumentException('Referensi bibliografi batch tidak tersedia.');
        kiEncoding($resolved);
        if($action==='created') {
            kiQuery("INSERT INTO katalog(tanggal_input,penginput_awal,judul,id_lokasi,kode_inventaris,deskripsi_fisik,deskripsi_isi,riwayat_naskah,kata_kunci,gambar,url_web,url_repositori,iiif_manifest,url_pdf) VALUES(NOW(),?,?,?,?,?,?,?,?,'','','','','')",[$_SESSION['username'],$resolved['judul'],$location,$resolved['kode_inventaris'],$resolved['deskripsi_fisik'],$resolved['deskripsi_isi'],$resolved['riwayat_naskah'],$resolved['kata_kunci']]);
            $target=$conn->insert_id;
        }else {
            kiQuery('UPDATE katalog SET judul=?,id_lokasi=?,kode_inventaris=?,deskripsi_fisik=?,deskripsi_isi=?,riwayat_naskah=?,kata_kunci=?,penyunting_terakhir=?,tanggal_update=NOW() WHERE id=?',[$resolved['judul'],$location,$resolved['kode_inventaris'],$resolved['deskripsi_fisik'],$resolved['deskripsi_isi'],$resolved['riwayat_naskah'],$resolved['kata_kunci'],$_SESSION['username'],$target]);
        }
        foreach($groupDefs as $k=>[$t,$c,$m]) {
            if($action==='merged'&&($choices[$k]??'keep')==='keep')continue;
            kiQuery("DELETE FROM $t WHERE id_naskah=?",[$target]);
            foreach($finalRelations[$k] as $value)kiQuery("INSERT INTO $t(id_naskah,$c) VALUES(?,?)",[$target,$value]);
        }
        if(!kiQuery('SELECT id_bibliografi FROM naskah_bibliografi WHERE id_naskah=? AND id_bibliografi=?',[$target,$bibliography])->get_result()->fetch_assoc())kiQuery('INSERT INTO naskah_bibliografi(id_naskah,id_bibliografi) VALUES(?,?)',[$target,$bibliography]);
        kiQuery('INSERT INTO katalog_import_sources(source_key,katalog_id,import_row_id) VALUES(?,?,?)',[$row['source_key'],$target,$row['id']]);
        $after=kiSnapshot($target)['data'];
        kiAudit($row,$action,$target,$before,$after,$input);
        logAktivitas($conn,$action==='created'?'input':'edit',($action==='created'?'Input naskah: ':'Edit naskah: ').$resolved['judul'].' (impor katalog cetak)', $target);
        $conn->commit();return ['id'=>$target,'action'=>$action];
    }catch(Throwable $e){$conn->rollback();throw $e;}
}
