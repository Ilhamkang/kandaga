<?php
// Printed catalog ingestion: upload to staging, then explicit per-entry moderation.
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/log-tracker.php';
require_once __DIR__ . '/upload.php';
function kiJson($value) { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); }
function kiQuery($sql, $values = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($values) $stmt->bind_param(str_repeat('s', count($values)), ...$values);
    $stmt->execute(); return $stmt;
}
if (!defined('KI_ENGINE_CATALOG')) define('KI_ENGINE_CATALOG', 'catalog');
if (!defined('KI_ENGINE_WEB')) define('KI_ENGINE_WEB', 'web');
function kiHasColumn($table, $column) {
    $r = kiQuery('SELECT COUNT(*) AS n FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?', [$table, $column])->get_result();
    return (int)$r->fetch_assoc()['n'] > 0;
}
function kiColumnNullable($table, $column) {
    $row = kiQuery('SELECT IS_NULLABLE AS is_nullable FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?', [$table, $column])->get_result()->fetch_assoc();
    return $row && strtoupper((string)$row['is_nullable']) === 'YES';
}
function kiImportDetachable() {
    static $ready = null;
    if ($ready === null) {
        $ready = kiColumnNullable('katalog_import_audit', 'import_row_id')
            && kiColumnNullable('katalog_import_sources', 'import_row_id')
            && kiHasColumn('katalog_import_audit', 'source_key')
            && kiHasColumn('katalog_import_audit', 'batch_id')
            && kiHasColumn('katalog_import_audit', 'filename')
            && kiHasColumn('katalog_import_audit', 'engine');
    }
    return $ready;
}
function kiReady() {
    $names = [
        'katalog_import_batches','katalog_import_rows','katalog_import_sources','katalog_import_audit',
        'katalog_structure','katalog_identifiers','katalog_location_history'
    ];
    $r = kiQuery('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN (?,?,?,?,?,?,?)', $names)->get_result();
    return $r->num_rows === count($names) && kiHasColumn('katalog_import_batches', 'engine');
}
function kiBatches($engine = KI_ENGINE_CATALOG) {
    return kiQuery('SELECT id,filename,source_label,engine,mapping_json,created_at FROM katalog_import_batches WHERE engine=? ORDER BY id DESC LIMIT 50', [$engine])->get_result()->fetch_all(MYSQLI_ASSOC);
}
function kiFindBatch($hash, $engine) {
    return kiQuery('SELECT id FROM katalog_import_batches WHERE file_hash=? AND engine=?', [$hash, $engine])->get_result()->fetch_assoc();
}
function kiInsertBatch($engine, $filename, $hash, $label, $bibliographyId, $mappingJson) {
    kiQuery('INSERT INTO katalog_import_batches(filename,file_hash,source_label,bibliography_id,mapping_json,created_by,engine) VALUES(?,?,?,?,?,?,?)', [$filename, $hash, $label, $bibliographyId, $mappingJson, $_SESSION['username'], $engine]);
    global $conn;
    return (int)$conn->insert_id;
}
function kiAssertBatch($id, $engine) {
    $row = kiQuery('SELECT id,filename,source_label,engine FROM katalog_import_batches WHERE id=?', [$id])->get_result()->fetch_assoc();
    if (!$row || $row['engine'] !== $engine) throw new InvalidArgumentException('Batch impor tidak ditemukan pada submodul ini.');
    return $row;
}
function kiQueueData($batch, $engine, $page, $status, $perPage = 15) {
    kiAssertBatch($batch, $engine);
    $where = 'r.batch_id=? AND b.engine=?'; $args = [$batch, $engine];
    if ($status !== 'all') { $where .= ' AND r.status=?'; $args[] = $status; }
    $total = (int)kiQuery('SELECT COUNT(*) AS n FROM katalog_import_rows r JOIN katalog_import_batches b ON b.id=r.batch_id WHERE ' . $where, $args)->get_result()->fetch_assoc()['n'];
    $page = min($page, max(1, (int)ceil($total / $perPage)));
    $offset = ($page - 1) * $perPage;
    $list = kiQuery('SELECT r.id,r.source_row,r.status,r.target_id,r.note,r.draft_json,s.katalog_id AS linked_id FROM katalog_import_rows r JOIN katalog_import_batches b ON b.id=r.batch_id LEFT JOIN katalog_import_sources s ON s.source_key=r.source_key WHERE ' . $where . ' ORDER BY r.id LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset, $args)->get_result()->fetch_all(MYSQLI_ASSOC);
    $counts = kiQuery('SELECT status,COUNT(*) AS n FROM katalog_import_rows WHERE batch_id=? GROUP BY status', [$batch])->get_result()->fetch_all(MYSQLI_ASSOC);
    return ['rows' => $list, 'total' => $total, 'page' => $page, 'pages' => max(1, (int)ceil($total / $perPage)), 'counts' => $counts];
}
function kiFields() {
    return [
        'Judul','Kode_Inventaris','Lokasi_Sumber','Bahasa_List','Aksara_List','Alas_Tulis_List',
        'Ukuran','Tebal_Naskah','Jumlah_Baris','Deskripsi_Fisik','Deskripsi_Isi','Riwayat_Naskah',
        'Bentuk_Karangan','Asal_Naskah','Sumber_ID','Bagian_Sumber','Nomor_Entri','Halaman_Cetak',
        'Halaman_PDF','Status_Sumber','Catatan_Koreksi','Teks_Sumber',
        'Kata_Kunci','Penyedia_Akses','Lokasi_Target','URL_Web','URL_Repositori','IIIF_Manifest',
        'URL_PDF','URL_Gambar_Eksternal','Identifier_Scheme','Identifier_Value','Identifier_Source',
        'Location_Kind','Location_Place','Location_District','Location_Province','Location_Country',
        'Location_Period','Location_Certainty','Location_Source','Record_Type','Parent_ID',
        'Unit_Label','Unit_Order','Structure_Notes','Bibliografi_ID'
    ];
}
function kiNorm($value) {
    return mb_strtolower(preg_replace('/\s+/u',' ',trim((string)$value)), 'UTF-8');
}
function kiCode($value) { return preg_replace('/[^\p{L}\p{N}]/u','',kiNorm($value)); }
function kiTermAliases() {
    return [
        'javanese' => 'Jawa', 'sundanese' => 'Sunda', 'sanskrit' => 'Sansakerta',
        'malay' => 'Melayu', 'arabic' => 'Arab', 'dutch' => 'Belanda', 'chinese' => 'Mandarin',
        'latin' => 'Latin', 'roman' => 'Latin', 'java' => 'Jawa', 'sunda' => 'Sunda', 'kawi' => 'Kawi', 'pegon' => 'Pegon',
        'jawi' => 'Pegon', 'indonesian' => 'Melayu', 'batak' => 'Batak', 'hanzi' => 'Hanzi', 'buda' => 'Buda',
        'cacarakan' => 'Cacarakan', 'palm-leaf' => 'Lontar', 'palm leaf' => 'Lontar',
        'paper' => 'Kertas', 'european paper' => 'Kertas Eropa', 'daluang' => 'Daluang',
        'dluwang' => 'Daluang', 'dluwang paper' => 'Daluang', 'tree bark paper' => 'Daluang',
        'exercise book' => 'Kertas', 'lined paper' => 'Kertas', 'lined folio paper' => 'Kertas',
        'lined european paper' => 'Kertas Eropa', 'lined exercise paper' => 'Kertas',
        'line factory paper' => 'Kertas', 'greaseproof paper' => 'Kertas', 'hvs paper' => 'Kertas',
        'kaboa wood' => 'Kayu', 'gebang' => 'Gebang', 'bamboo' => 'Bambu', 'wood' => 'Kayu', 'bone' => 'Tulang'
    ];
}
function kiNormalTerm($value) {
    $term = trim((string)$value);
    if ($term === '') return '';
    return kiTermAliases()[kiNorm($term)] ?? $term;
}
function kiSplitTerms($value) {
    $out = [];
    foreach (preg_split('/[;,|]/u', (string)$value) as $part) {
        $part = kiNormalTerm($part);
        if ($part !== '' && !in_array($part, $out, true)) $out[] = $part;
    }
    return $out;
}
function kiCodes($value, $historical = false) {
    $out = [];
    foreach (preg_split('/[;|]/u', (string)$value) as $code) {
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
    foreach(['Judul','Kode_Inventaris','Sumber_ID','Bagian_Sumber','Nomor_Entri','Halaman_Cetak','Halaman_PDF','Lokasi_Target','Penyedia_Akses'] as $f) if(mb_strlen($v[$f])>255) throw new InvalidArgumentException($f.' maksimal 255 karakter.');
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
    $sourceLabel=trim($v['Penyedia_Akses']) ?: 'Katalog cetak';
    $fields=[
        'judul'=>$v['Judul'], 'kode_inventaris'=>$v['Kode_Inventaris'],
        'deskripsi_fisik'=>implode("\n",$physical), 'deskripsi_isi'=>$content,
        'riwayat_naskah'=>implode("\n",$history), 'kata_kunci'=>trim($v['Kata_Kunci']),
        'penyedia_akses'=>$sourceLabel, 'url_web'=>trim($v['URL_Web']),
        'url_repositori'=>trim($v['URL_Repositori']), 'iiif_manifest'=>trim($v['IIIF_Manifest']),
        'url_pdf'=>trim($v['URL_PDF']), 'url_gambar_eksternal'=>trim($v['URL_Gambar_Eksternal'])
    ];
    foreach(['url_web'=>'URL_Web','url_repositori'=>'URL_Repositori','iiif_manifest'=>'IIIF_Manifest','url_pdf'=>'URL_PDF','url_gambar_eksternal'=>'URL_Gambar_Eksternal'] as $key=>$label) $fields[$key]=kandagaValidateExternalUrl($fields[$key],$label);
    $certainty=trim($v['Location_Certainty']);
    if($certainty!=='' && !in_array($certainty,['pasti','kemungkinan','dugaan','belum_diketahui'],true)) throw new InvalidArgumentException('Location_Certainty tidak dikenal.');
    $kind=trim($v['Location_Kind']) ?: 'asal';
    if(!in_array($kind,['asal','ditemukan','sebelumnya'],true)) throw new InvalidArgumentException('Location_Kind tidak dikenal.');
    $recordType=trim($v['Record_Type']) ?: 'induk';
    if(!in_array($recordType,['induk','unit'],true)) throw new InvalidArgumentException('Record_Type harus induk atau unit.');
    $order=filter_var($v['Unit_Order'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $identifiers=[];
    $scheme=trim($v['Identifier_Scheme']); $identifierValue=trim($v['Identifier_Value']);
    if($scheme!=='' && $identifierValue!=='') {
        $identifiers[]=['scheme'=>mb_substr($scheme,0,64),'value'=>mb_substr($identifierValue,0,255),'source'=>mb_substr(trim($v['Identifier_Source']) ?: $sourceLabel,0,255),'primary'=>false];
    }
    if($v['Kode_Inventaris']!=='') $identifiers[]=['scheme'=>'shelfmark','value'=>mb_substr($v['Kode_Inventaris'],0,255),'source'=>$sourceLabel,'primary'=>true];
    $provenance=mb_substr($v['Sumber_ID'].'|'.$v['Bagian_Sumber'].'|'.$v['Nomor_Entri'],0,255);
    $identifiers[]=['scheme'=>'katalog-cetak','value'=>$provenance,'source'=>$sourceLabel,'primary'=>false];
    $bib=[];
    foreach(preg_split('/[,;|]/u',$v['Bibliografi_ID']) as $value) {
        $value=filter_var(trim($value),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($value)$bib[]=$value;
    }
    $place=trim($v['Location_Place']); $district=trim($v['Location_District']); $province=trim($v['Location_Province']); $country=trim($v['Location_Country']);
    $locationHistory=[];
    if($place!==''||$district!==''||$province!==''||$country!==''||trim($v['Lokasi_Sumber'])!==''||trim($v['Lokasi_Target'])!=='') {
        $locationHistory[]=[
            'kind'=>$kind, 'place'=>mb_substr($place ?: $v['Lokasi_Sumber'] ?: $v['Lokasi_Target'],0,255),
            'district'=>mb_substr($district,0,150), 'province'=>mb_substr($province,0,150),
            'country'=>mb_substr($country,0,150), 'period'=>mb_substr(trim($v['Location_Period']),0,150),
            'certainty'=>$certainty ?: 'belum_diketahui',
            'source'=>mb_substr(trim($v['Location_Source']) ?: $sourceLabel,0,255), 'sort_order'=>1
        ];
    }
    $v['source_label']=$sourceLabel;
    return [
        'source'=>$v, 'fields'=>$fields,
        'relations'=>['bahasa'=>kiSplitTerms($v['Bahasa_List']),'aksara'=>kiSplitTerms($v['Aksara_List']),'alas_tulis'=>kiSplitTerms($v['Alas_Tulis_List'])],
        'structure'=>['record_type'=>$recordType,'parent_id'=>trim($v['Parent_ID']),'unit_label'=>mb_substr(trim($v['Unit_Label']),0,100),'unit_order'=>$order ?: null,'notes'=>trim($v['Structure_Notes'])],
        'identifiers'=>$identifiers, 'location_history'=>$locationHistory, 'bibliografi'=>array_values(array_unique($bib))
    ];
}
function kiKey($v) { return hash('sha256',kiJson(array_map(fn($f)=>kiNorm($v[$f]),['Sumber_ID','Bagian_Sumber','Nomor_Entri']))); }
function kiMasters() {
    $out=[];
    foreach(['lokasi'=>['tb_lokasi','id_lokasi','nama_tempat'],'bahasa'=>['tb_bahasa','id_bahasa','nama_bahasa'],'aksara'=>['tb_aksara','id_aksara','nama_aksara'],'alas_tulis'=>['tb_alas_tulis','id_alas_tulis','nama_alas_tulis']] as $key=>[$table,$id,$name]) $out[$key]=kiQuery("SELECT $id AS id,$name AS name FROM $table ORDER BY $name")->get_result()->fetch_all(MYSQLI_ASSOC);
    $out['bibliografi']=kiQuery("SELECT id, judul AS name FROM tb_bibliografi ORDER BY judul")->get_result()->fetch_all(MYSQLI_ASSOC);
    $out['katalog']=kiQuery('SELECT id, kode_inventaris, judul FROM katalog ORDER BY id DESC LIMIT 5000')->get_result()->fetch_all(MYSQLI_ASSOC);
    return $out;
}
function kiSuggestions($source,$masters) {
    $out=['lokasi'=>null,'bahasa'=>[],'aksara'=>[],'alas_tulis'=>[],'parent'=>null,'unresolved'=>[]];
    $target=trim((string)($source['Lokasi_Target']??''));
    foreach($masters['lokasi'] as $item) if(kiNormalTerm($item['name'])===kiNormalTerm($target)){$out['lokasi']=(int)$item['id'];break;}
    if($out['lokasi']===null && $target!=='') $out['unresolved'][]=['group'=>'lokasi','term'=>$target];
    foreach(['bahasa','aksara','alas_tulis'] as $key) {
        foreach(kiSplitTerms($source[['bahasa'=>'Bahasa_List','aksara'=>'Aksara_List','alas_tulis'=>'Alas_Tulis_List'][$key]]??'') as $term) {
            $found=null;
            foreach($masters[$key] as $item) if(kiNormalTerm($item['name'])===kiNormalTerm($term)){$found=(int)$item['id'];break;}
            if($found===null)$out['unresolved'][]=['group'=>$key,'term'=>$term];
            else $out[$key][]=$found;
        }
        $out[$key]=array_values(array_unique($out[$key]));
    }
    $parent=trim((string)($source['Parent_ID']??''));
    if($parent!=='') {
        foreach($masters['katalog'] as $item) {
            if((string)$item['id']===$parent || kiNorm($item['kode_inventaris'])===kiNorm($parent)){$out['parent']=(int)$item['id'];break;}
        }
        if($out['parent']===null)$out['unresolved'][]=['group'=>'parent','term'=>$parent];
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
    $data=[
        'fields'=>$row, 'relations'=>$relations,
        'structure'=>kiQuery('SELECT record_type,parent_id,unit_label,unit_order,notes FROM katalog_structure WHERE id_naskah=?'.$suffix,[$id])->get_result()->fetch_assoc() ?: null,
        'identifiers'=>kiQuery('SELECT scheme,identifier_value,source_label,is_primary FROM katalog_identifiers WHERE id_naskah=? ORDER BY id'.$suffix,[$id])->get_result()->fetch_all(MYSQLI_ASSOC),
        'location_history'=>kiQuery('SELECT location_kind,place_name,district,province,country,period_label,certainty,source_text,sort_order FROM katalog_location_history WHERE id_naskah=? ORDER BY sort_order,id'.$suffix,[$id])->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
    return ['data'=>$data,'revision'=>hash('sha256',kiJson($data))];
}
function kiCandidates($draft,$location=0,$historical=false) {
    $out=[];$seen=[];
    foreach((array)($draft['identifiers']??[]) as $identifier) {
        $scheme=trim((string)($identifier['scheme']??'')); $value=trim((string)($identifier['value']??''));
        if($scheme===''||$value==='')continue;
        $rows=kiQuery('SELECT k.id AS id,k.judul,k.kode_inventaris,k.id_lokasi,l.nama_tempat FROM katalog_identifiers i LEFT JOIN katalog k ON k.id=i.id_naskah LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi WHERE i.scheme=? AND i.identifier_value=?',[$scheme,$value])->get_result();
        while($r=$rows->fetch_assoc()) {
            $key=$r['id'].'|Identitas '.$scheme.' sama';
            if(isset($seen[$key]))continue;
            $seen[$key]=true;
            $r['score']=100;$r['reason']='Identitas '.$scheme.' sama';$out[]=$r;
        }
    }
    $codes=kiCodes($draft['source']['Kode_Inventaris'],$historical); $title=kiCode($draft['fields']['judul']);
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
        $label=implode('; ',$reason);$key=$r['id'].'|'.$label;
        if(isset($seen[$key]))continue;
        $seen[$key]=true;
        if($location && (int)$r['id_lokasi']===$location) {$score+=15;$reason[]='Lokasi pilihan sama';}
        elseif($location) $reason[]='Lokasi berbeda; bisa salinan lain';
        $r['score']=$score;$r['reason']=implode('; ',$reason);$out[]=$r;
    }
    usort($out,fn($a,$b)=>$b['score']<=>$a['score'] ?: (int)$a['id']<=>(int)$b['id']);
    return array_slice($out,0,20);
}
function kiBatchDeleteInfo($id,$engine) {
    kiAssertBatch($id,$engine);
    $counts=kiQuery('SELECT status,COUNT(*) AS n FROM katalog_import_rows WHERE batch_id=? GROUP BY status',[$id])->get_result()->fetch_all(MYSQLI_ASSOC);
    $decided=0;$total=0;
    foreach($counts as $item){$total+=(int)$item['n'];if(!in_array($item['status'],['pending','deferred'],true))$decided+=(int)$item['n'];}
    $linked=(int)kiQuery('SELECT COUNT(*) AS n FROM katalog_import_sources s JOIN katalog_import_rows r ON r.source_key=s.source_key WHERE r.batch_id=?',[$id])->get_result()->fetch_assoc()['n'];
    $audited=(int)kiQuery('SELECT COUNT(*) AS n FROM katalog_import_audit a JOIN katalog_import_rows r ON r.id=a.import_row_id WHERE r.batch_id=?',[$id])->get_result()->fetch_assoc()['n'];
    return ['total'=>$total,'decided'=>$decided,'linked'=>$linked,'audited'=>$audited,'detachable'=>kiImportDetachable()];
}
function kiDeleteBatch($id,$engine) {
    global $conn;
    $batch=kiAssertBatch($id,$engine);
    $info=kiBatchDeleteInfo($id,$engine);
    // Tanpa migrasi 019, jejak audit masih terikat pada baris sehingga batch
    // yang sudah diproses tetap ditolak. Dengan migrasi, jejak dilepas dulu.
    if(!$info['detachable']&&($info['decided']>0||$info['linked']>0||$info['audited']>0)) throw new InvalidArgumentException('Batch ini memiliki '.$info['decided'].' baris diputuskan, '.$info['linked'].' identitas tertaut, dan '.$info['audited'].' catatan audit. Jalankan migrations/019_import_detachable_audit.sql agar jejak dapat dipertahankan saat batch dihapus.');
    $detachedAudit=0;$detachedSources=0;$orphans=0;
    $conn->begin_transaction();
    try {
        if($info['detachable']) {
            // Jejak yang menuju entri katalog yang sudah dihapus tidak berguna lagi.
            $orphans+=(int)kiQuery('DELETE a FROM katalog_import_audit a JOIN katalog_import_rows r ON r.id=a.import_row_id LEFT JOIN katalog k ON k.id=a.katalog_id WHERE r.batch_id=? AND a.katalog_id IS NOT NULL AND k.id IS NULL',[$id])->affected_rows;
            $orphans+=(int)kiQuery('DELETE s FROM katalog_import_sources s JOIN katalog_import_rows r ON r.source_key=s.source_key LEFT JOIN katalog k ON k.id=s.katalog_id WHERE r.batch_id=? AND k.id IS NULL',[$id])->affected_rows;
            // Lepaskan sisanya dari baris batch; identitas & audit tetap terbaca.
            $detachedAudit=(int)kiQuery('UPDATE katalog_import_audit a JOIN katalog_import_rows r ON r.id=a.import_row_id SET a.import_row_id=NULL WHERE r.batch_id=?',[$id])->affected_rows;
            $detachedSources=(int)kiQuery('UPDATE katalog_import_sources s JOIN katalog_import_rows r ON r.source_key=s.source_key SET s.import_row_id=NULL WHERE r.batch_id=?',[$id])->affected_rows;
        }
        kiQuery('DELETE FROM katalog_import_rows WHERE batch_id=?',[$id]);
        kiQuery('DELETE FROM katalog_import_batches WHERE id=? AND engine=?',[$id,$engine]);
        $detail=$info['detachable']?'; '.$detachedAudit.' catatan audit dan '.$detachedSources.' tautan identitas dipertahankan':'';
        if($orphans)$detail.='; '.$orphans.' jejak yatim dibersihkan';
        logAktivitas($conn,'hapus','Hapus batch impor #'.(int)$id.' ('.$batch['filename'].') pada submodul '.$engine.'; '.(int)$info['total'].' baris'.$detail,0);
        $conn->commit();
    }catch(Throwable $e){$conn->rollback();throw $e;}
    return ['id'=>(int)$id,'rows'=>(int)$info['total'],'decided'=>(int)$info['decided'],'linked'=>(int)$info['linked'],'audited'=>(int)$info['audited'],'detached_audit'=>$detachedAudit,'detached_sources'=>$detachedSources,'orphans_removed'=>$orphans,'detachable'=>$info['detachable']];
}
function kiRow($id,$lock=false,$engine=null) {
    $sql='SELECT r.*,b.filename AS batch_filename,b.source_label,b.bibliography_id,b.engine AS batch_engine FROM katalog_import_rows r JOIN katalog_import_batches b ON b.id=r.batch_id WHERE r.id=?';
    $args=[$id];
    if($engine!==null){$sql.=' AND b.engine=?';$args[]=$engine;}
    $row=kiQuery($sql.($lock?' FOR UPDATE':''),$args)->get_result()->fetch_assoc();
    if(!$row)throw new InvalidArgumentException('Baris impor tidak ditemukan pada submodul ini.');
    $row['raw']=json_decode($row['raw_json'],true,512,JSON_THROW_ON_ERROR);$row['draft']=json_decode($row['draft_json'],true,512,JSON_THROW_ON_ERROR);
    unset($row['raw_json'],$row['draft_json']);return $row;
}
function kiLinked($key) { return kiQuery('SELECT s.katalog_id,k.judul FROM katalog_import_sources s LEFT JOIN katalog k ON k.id=s.katalog_id WHERE s.source_key=?',[$key])->get_result()->fetch_assoc(); }
function kiHtml($text) {
    // Numeric HTML entities keep descriptive Unicode readable even on legacy latin1 TEXT.
    $html=nl2br(htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),false);
    return mb_encode_numericentity($html,[0x80,0x10ffff,0,0x1fffff],'UTF-8');
}
function kiLocationHistory($input,$fallback=[],$sourceLabel='') {
    $items=$input['location_history']??$fallback;
    if(!is_array($items))throw new InvalidArgumentException('Riwayat lokasi tidak valid.');
    $out=[];
    foreach($items as $item) {
        if(!is_array($item))continue;
        $kind=trim((string)($item['kind']??'asal')) ?: 'asal';
        if(!in_array($kind,['asal','ditemukan','sebelumnya'],true))throw new InvalidArgumentException('Location_Kind tidak dikenal.');
        $certainty=trim((string)($item['certainty']??'belum_diketahui')) ?: 'belum_diketahui';
        if(!in_array($certainty,['pasti','kemungkinan','dugaan','belum_diketahui'],true))throw new InvalidArgumentException('Location_Certainty tidak dikenal.');
        $place=mb_substr(trim((string)($item['place']??'')),0,255);
        $district=mb_substr(trim((string)($item['district']??'')),0,150);
        $province=mb_substr(trim((string)($item['province']??'')),0,150);
        $country=mb_substr(trim((string)($item['country']??'')),0,150);
        if($place===''&&$district===''&&$province===''&&$country==='')continue;
        $sort=filter_var($item['sort_order']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 1;
        $source=mb_substr(trim((string)($item['source']??'')) ?: trim((string)$sourceLabel),0,255);
        $out[]=['kind'=>$kind,'place'=>$place,'district'=>$district,'province'=>$province,'country'=>$country,'period'=>mb_substr(trim((string)($item['period']??'')),0,150),'certainty'=>$certainty,'source'=>$source,'sort_order'=>$sort];
    }
    return $out;
}
function kiAudit($row,$action,$target,$before,$after,$decision) {
    $payload=[$before===null?null:kiJson($before),$after===null?null:kiJson($after),kiJson($decision),$_SESSION['username']];
    if(kiImportDetachable()) {
        kiQuery('INSERT INTO katalog_import_audit(import_row_id,source_key,batch_id,filename,engine,action,katalog_id,before_json,after_json,decision_json,username) VALUES(?,?,?,?,?,?,?,?,?,?,?)',[
            $row['id'],$row['source_key'],$row['batch_id'],$row['batch_filename']??null,$row['batch_engine'],$action,$target,$payload[0],$payload[1],$payload[2],$payload[3]
        ]);
    } else {
        kiQuery('INSERT INTO katalog_import_audit(import_row_id,action,katalog_id,before_json,after_json,decision_json,username) VALUES(?,?,?,?,?,?,?)',[$row['id'],$action,$target,$payload[0],$payload[1],$payload[2],$payload[3]]);
    }
    kiQuery('UPDATE katalog_import_rows SET status=?,target_id=?,reviewed_by=?,reviewed_at=NOW(),note=? WHERE id=?',[$action,$target,$_SESSION['username'],(string)($decision['note']??''),$row['id']]);
}
function kiEncoding($fields) {
    // Fail explicitly instead of silently replacing unsupported title/code characters,
    // even when a deployment does not enable strict SQL mode. Check only columns
    // that this engine actually writes, including digital-access metadata.
    $columns=kiQuery("SELECT COLUMN_NAME AS field_name, CHARACTER_SET_NAME AS field_charset FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='katalog'")->get_result()->fetch_all(MYSQLI_ASSOC);
    $map=[];
    foreach($columns as $column) {
        $name=$column['field_name']??null;$charset=$column['field_charset']??null;
        if(is_string($name) && is_string($charset) && preg_match('/^[a-z0-9_]+$/i',$charset)) $map[$name]=$charset;
    }
    foreach(['judul','kode_inventaris','kata_kunci'] as $required) if(!isset($map[$required])) throw new InvalidArgumentException('Charset katalog tidak dapat diverifikasi.');
    foreach($fields as $name=>$text) {
        if(!isset($map[$name]))continue;
        $charset=$map[$name];
        $text=(string)$text;
        $roundtrip=kiQuery("SELECT CONVERT(CONVERT(? USING $charset) USING utf8mb4) AS value",[$text])->get_result()->fetch_assoc()['value'];
        if($text!==$roundtrip)throw new InvalidArgumentException('Karakter pada '.$name.' belum didukung charset '.$charset.'. Migrasikan kolom katalog ke utf8mb4 sebelum menyetujui entri ini. Teks sumber tetap tersimpan di antrean.');
    }
}
function kiValidId($value,$table,$column) {
    $value=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if(!$value||!kiQuery("SELECT $column FROM $table WHERE $column=?",[$value])->get_result()->fetch_assoc())throw new InvalidArgumentException('Pilihan '.$table.' tidak ditemukan di Kandaga.');
    return $value;
}
function kiApprove($input) {
    global $conn;
    $id=filter_var($input['row_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $action=$input['action']??'';
    if(!$id || !in_array($action,['created','merged','rejected','deferred'],true)) throw new InvalidArgumentException('Keputusan moderasi tidak valid.');
    if(!is_string($input['note']??'') || mb_strlen($input['note']??'')>4000) throw new InvalidArgumentException('Catatan maksimal 4.000 karakter.');
    if(!kiReady())throw new InvalidArgumentException('Jalankan migrations/005_catalog_import.sql, migrations/013_catalog_structure_stage1.sql, dan migrations/018_web_import_batch_separation.sql pada database Kandaga dahulu.');
    $conn->begin_transaction();
    try {
        $row=kiRow($id,true,KI_ENGINE_CATALOG);
        if(!in_array($row['status'],['pending','deferred'],true)) throw new InvalidArgumentException('Baris ini sudah diputuskan. Muat ulang antrean.');
        $oldHistory=$row['draft']['location_history']??[];
        $history=kiLocationHistory($input,$oldHistory,$row['source_label']);
        if(in_array($action,['rejected','deferred'],true)) {
            if($history!==$oldHistory) {
                $draft=$row['draft'];$draft['location_history']=$history;
                kiQuery('UPDATE katalog_import_rows SET draft_json=? WHERE id=?',[kiJson($draft),$row['id']]);
                $row['draft']=$draft;
            }
            kiAudit($row,$action,null,['location_history'=>$oldHistory],['location_history'=>$history],$input);
            $conn->commit();return ['id'=>null,'action'=>$action];
        }
        if($row['draft']['source']['Status_Sumber']==='berita') throw new InvalidArgumentException('Berita adanya naskah belum dapat diterbitkan. Tunda sampai identitas bendanya terverifikasi; siapkan CSV baru berstatus deskriptif.');
        if(kiLinked($row['source_key'])) throw new InvalidArgumentException('Identitas sumber ini sudah diimpor. Tidak disimpan ulang.');
        $fields=$input['fields']??null; $choices=$input['choices']??[]; $relations=$input['relations']??[];
        if(!is_array($fields)||!is_array($choices)||!is_array($relations)) throw new InvalidArgumentException('Format form moderasi tidak valid.');
        $editable=['judul','kode_inventaris','deskripsi_fisik','deskripsi_isi','riwayat_naskah','kata_kunci','penyedia_akses','url_web','url_repositori','iiif_manifest','url_pdf','url_gambar_eksternal'];
        foreach($editable as $f) {
            if(!array_key_exists($f,$fields)) {
                if(in_array($f,['judul','kode_inventaris'],true))throw new InvalidArgumentException('Field '.$f.' tidak valid.');
                $fields[$f]='';
            }
            if(!is_string($fields[$f]))throw new InvalidArgumentException('Field '.$f.' tidak valid.');
            $fields[$f]=trim($fields[$f]);
            $short=in_array($f,['judul','kode_inventaris','kata_kunci','penyedia_akses','url_web','url_repositori','iiif_manifest','url_pdf','url_gambar_eksternal'],true);
            if(mb_strlen($fields[$f])>($short?255:50000)) throw new InvalidArgumentException($f.' terlalu panjang.');
            if(in_array($f,['url_web','url_repositori','iiif_manifest','url_pdf','url_gambar_eksternal'],true)) $fields[$f]=kandagaValidateExternalUrl($fields[$f],$f);
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
        $location=$locationChoice==='keep'?($old['fields']['id_lokasi']??0):($input['id_lokasi']??0);
        $location=kiValidId($location,'tb_lokasi','id_lokasi');
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
        if(($input['metadata_confirmed']??false)!==true) throw new InvalidArgumentException('Periksa dan konfirmasikan padanan metadata sebelum menyimpan keputusan.');
        $candidateDraft=$row['draft'];$candidateDraft['fields']['judul']=$resolved['judul'];$candidateDraft['source']['Kode_Inventaris']=$resolved['kode_inventaris'];
        if($action==='created' && kiCandidates($candidateDraft,(int)$location,true) && ($input['duplicate_confirmed']??false)!==true) throw new InvalidArgumentException('Ada kandidat duplikat. Periksa dan konfirmasikan bahwa ini salinan berbeda sebelum membuat entri baru.');
        $groupDefs=['bahasa'=>['naskah_bahasa','id_bahasa','tb_bahasa'],'aksara'=>['naskah_aksara','id_aksara','tb_aksara'],'alas_tulis'=>['naskah_alas_tulis','id_alas_tulis','tb_alas_tulis']];
        $finalRelations=[];
        foreach($groupDefs as $k=>[$table,$col,$master]) {
            $mode=$action==='created'?'replace':($choices[$k]??'keep');
            if(!in_array($mode,['keep','union','replace'],true))throw new InvalidArgumentException('Pilihan relasi tidak valid.');
            $ids=$relations[$k]??[];
            if(!is_array($ids))throw new InvalidArgumentException('Pilihan relasi harus daftar ID.');
            $valid=[];
            foreach($ids as $value) $valid[]=kiValidId($value,$master,$col);
            if($mode==='replace'&&$action==='merged'&&!$valid&&!empty($old['relations'][$k]))throw new InvalidArgumentException('Pilihan kosong tidak boleh menghapus relasi lama.');
            $finalRelations[$k]=array_values(array_unique($mode==='keep'?($old['relations'][$k]??[]):($mode==='union'?array_merge($old['relations'][$k]??[],$valid):$valid)));
        }
        $bibliography=(int)$row['bibliography_id'];
        $bibIds=[];
        foreach((array)($input['bibliografi']??($row['draft']['bibliografi']??[])) as $value) $bibIds[]=kiValidId($value,'tb_bibliografi','id');
        if($bibliography)$bibIds[]=kiValidId($bibliography,'tb_bibliografi','id');
        $bibIds=array_values(array_unique($bibIds));
        $structureInput=$input['structure']??null;
        $structure=is_array($structureInput)?$structureInput:($row['draft']['structure']??[]);
        if($action==='merged' && ($choices['structure']??'use')==='keep' && !empty($old['structure'])) $structure=$old['structure'];
        $recordType=trim((string)($structure['record_type']??'induk')) ?: 'induk';
        if(!in_array($recordType,['induk','unit'],true))throw new InvalidArgumentException('Record_Type tidak dikenal.');
        $parent=null;
        $parentValue=trim((string)($structure['parent_id']??''));
        if($parentValue!=='') {
            $parent=filter_var($parentValue,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if(!$parent) {
                $found=kiQuery('SELECT id FROM katalog WHERE kode_inventaris=? ORDER BY id LIMIT 1',[$parentValue])->get_result()->fetch_assoc();
                $parent=$found?(int)$found['id']:null;
            }
            if(!$parent)throw new InvalidArgumentException('Entri induk tidak ditemukan pada Kandaga.');
        }
        if($recordType==='unit'&&!$parent)throw new InvalidArgumentException('Unit naskah memerlukan entri induk.');
        $unitLabel=mb_substr(trim((string)($structure['unit_label']??'')),0,100);
        $unitOrder=filter_var($structure['unit_order']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: null;
        $notes=mb_substr(trim((string)($structure['notes']??'')),0,50000);
        $identifiers=[];
        foreach((array)($input['identifiers']??($row['draft']['identifiers']??[])) as $item) {
            if(!is_array($item))continue;
            $scheme=trim((string)($item['scheme']??'')); $value=trim((string)($item['value']??''));
            if($scheme===''||$value==='')continue;
            $identifiers[]=['scheme'=>mb_substr($scheme,0,64),'value'=>mb_substr($value,0,255),'source'=>mb_substr(trim((string)($item['source']??'')) ?: $row['source_label'],0,255),'primary'=>!empty($item['primary'])];
        }
        kiEncoding($resolved);
        if($action==='created') {
            kiQuery("INSERT INTO katalog(tanggal_input,penginput_awal,judul,id_lokasi,kode_inventaris,deskripsi_fisik,deskripsi_isi,riwayat_naskah,kata_kunci,penyedia_akses,gambar,url_web,url_repositori,iiif_manifest,url_pdf,url_gambar_eksternal) VALUES(NOW(),?,?,?,?,?,?,?,?,?,'',?,?,?,?,?)",[$_SESSION['username'],$resolved['judul'],$location,$resolved['kode_inventaris'],$resolved['deskripsi_fisik'],$resolved['deskripsi_isi'],$resolved['riwayat_naskah'],$resolved['kata_kunci'],$resolved['penyedia_akses'],$resolved['url_web'],$resolved['url_repositori'],$resolved['iiif_manifest'],$resolved['url_pdf'],$resolved['url_gambar_eksternal']]);
            $target=$conn->insert_id;
        }else {
            kiQuery('UPDATE katalog SET judul=?,id_lokasi=?,kode_inventaris=?,deskripsi_fisik=?,deskripsi_isi=?,riwayat_naskah=?,kata_kunci=?,penyedia_akses=?,url_web=?,url_repositori=?,iiif_manifest=?,url_pdf=?,url_gambar_eksternal=?,penyunting_terakhir=?,tanggal_update=NOW() WHERE id=?',[$resolved['judul'],$location,$resolved['kode_inventaris'],$resolved['deskripsi_fisik'],$resolved['deskripsi_isi'],$resolved['riwayat_naskah'],$resolved['kata_kunci'],$resolved['penyedia_akses'],$resolved['url_web'],$resolved['url_repositori'],$resolved['iiif_manifest'],$resolved['url_pdf'],$resolved['url_gambar_eksternal'],$_SESSION['username'],$target]);
        }
        foreach($groupDefs as $k=>[$t,$c,$m]) {
            if($action==='merged'&&($choices[$k]??'keep')==='keep')continue;
            kiQuery("DELETE FROM $t WHERE id_naskah=?",[$target]);
            foreach($finalRelations[$k] as $value)kiQuery("INSERT INTO $t(id_naskah,$c) VALUES(?,?)",[$target,$value]);
        }
        foreach($bibIds as $value) if(!kiQuery('SELECT id_bibliografi FROM naskah_bibliografi WHERE id_naskah=? AND id_bibliografi=?',[$target,$value])->get_result()->fetch_assoc())kiQuery('INSERT INTO naskah_bibliografi(id_naskah,id_bibliografi) VALUES(?,?)',[$target,$value]);
        if($action==='created'||!kiQuery('SELECT id_naskah FROM katalog_structure WHERE id_naskah=?',[$target])->get_result()->fetch_assoc()) {
            kiQuery('INSERT INTO katalog_structure(id_naskah,parent_id,record_type,unit_label,unit_order,notes) VALUES(?,?,?,?,?,?)',[$target,$parent,$recordType,$unitLabel,$unitOrder,$notes]);
        }else {
            kiQuery('UPDATE katalog_structure SET parent_id=?,record_type=?,unit_label=?,unit_order=?,notes=? WHERE id_naskah=?',[$parent,$recordType,$unitLabel,$unitOrder,$notes,$target]);
        }
        foreach($identifiers as $item)kiQuery('INSERT IGNORE INTO katalog_identifiers(id_naskah,scheme,identifier_value,source_label,is_primary) VALUES(?,?,?,?,?)',[$target,$item['scheme'],$item['value'],$item['source'],$item['primary']?1:0]);
        if($action==='created'||!kiQuery('SELECT id_naskah FROM katalog_location_history WHERE id_naskah=?',[$target])->get_result()->fetch_assoc()) {
            foreach($history as $item)kiQuery('INSERT INTO katalog_location_history(id_naskah,location_kind,place_name,district,province,country,period_label,certainty,source_text,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)',[$target,$item['kind'],$item['place'],$item['district'],$item['province'],$item['country'],$item['period'],$item['certainty'],$item['source'],$item['sort_order']]);
        }
        kiQuery('INSERT INTO katalog_import_sources(source_key,katalog_id,import_row_id) VALUES(?,?,?)',[$row['source_key'],$target,$row['id']]);
        $after=kiSnapshot($target)['data'];
        kiAudit($row,$action,$target,$before,$after,$input);
        logAktivitas($conn,$action==='created'?'input':'edit',($action==='created'?'Input naskah: ':'Edit naskah: ').$resolved['judul'].' (impor katalog cetak)', $target);
        $conn->commit();return ['id'=>$target,'action'=>$action];
    }catch(Throwable $e){$conn->rollback();throw $e;}
}
