<?php
require_once __DIR__.'/../includes/catalog-import.php';
kandagaAuthorize(true);
$action=$_GET['action']??'init';
if(!is_string($action))kandagaJson(false,'Aksi tidak valid.',[],400);
$mutating=in_array($action,['inspect','upload','review','delete_batch'],true);
if($mutating)kandagaPost(true);
try {
    if($action==='init') {
        kandagaJson(true,'Konfigurasi importer.',['ready'=>kiReady(),'fields'=>kiFields(),'masters'=>kiMasters(),'batches'=>kiReady()?kiBatches(KI_ENGINE_CATALOG):[],'batch_delete_detachable'=>kiImportDetachable()]);
    }
    if(!kiReady())throw new InvalidArgumentException('Jalankan migrations/005_catalog_import.sql, migrations/013_catalog_structure_stage1.sql, dan migrations/018_web_import_batch_separation.sql pada database Kandaga dahulu.');
    if(in_array($action,['inspect','upload'],true)) {
        [$headers,$rows]=kiCsv($_FILES['csv_file']??[]);
        if($action==='inspect')kandagaJson(true,'CSV terbaca.',['headers'=>$headers,'count'=>count($rows),'preview'=>array_slice($rows,0,3)]);
        $mapping=json_decode($_POST['mapping']??'',true,512,JSON_THROW_ON_ERROR);
        if(!is_array($mapping))throw new InvalidArgumentException('Pemetaan kolom tidak valid.');
        foreach($mapping as $key=>$value)if(!in_array($key,kiFields(),true)||!is_string($value)||!in_array($value,$headers,true))throw new InvalidArgumentException('Pemetaan kolom tidak dikenal.');
        $label=$_POST['source_label']??'';
        if(!is_string($label)||trim($label)===''||mb_strlen($label)>255)throw new InvalidArgumentException('Nama sumber wajib, maksimal 255 karakter.');
        $bib=filter_var($_POST['bibliography_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$bib||!kiQuery('SELECT id FROM tb_bibliografi WHERE id=?',[$bib])->get_result()->fetch_assoc())throw new InvalidArgumentException('Pilih bibliografi katalog. Tambahkan dahulu melalui Daftar Pustaka jika belum tersedia.');
        $hash=hash_file('sha256',$_FILES['csv_file']['tmp_name']);
        $existing=kiFindBatch($hash,KI_ENGINE_CATALOG);
        if($existing)kandagaJson(true,'Berkas yang sama sudah ada; membuka batch sebelumnya.',['batch_id'=>(int)$existing['id'],'reused'=>true]);
        $drafts=[];$seen=[];
        foreach($rows as $n=>$raw){$draft=kiDraft($raw,$mapping);$key=kiKey($draft['source']);if(isset($seen[$key]))throw new InvalidArgumentException('Identitas sumber berulang pada baris '.($n+1).'. Tambahkan pembeda pada Nomor_Entri.');$seen[$key]=true;$drafts[]=[$raw,$draft,$key];}
        $conn->begin_transaction();
        try{
            $batch=kiInsertBatch(KI_ENGINE_CATALOG,mb_substr(basename($_FILES['csv_file']['name']),0,255),$hash,trim($label),$bib,kiJson($mapping));
            foreach($drafts as $n=>[$raw,$draft,$key])kiQuery('INSERT INTO katalog_import_rows(batch_id,source_row,source_key,raw_json,draft_json) VALUES(?,?,?,?,?)',[$batch,$n+1,$key,kiJson($raw),kiJson($draft)]);
            $conn->commit();
        }catch(Throwable $e){$conn->rollback();throw $e;}
        kandagaJson(true,'CSV masuk antrean; katalog utama belum diubah.',['batch_id'=>$batch,'count'=>count($drafts)]);
    }
    if($action==='queue') {
        $batch=filter_var($_GET['batch_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $page=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $status=$_GET['status']??'all';
        if(!$batch||!$page||!is_string($status)||!in_array($status,['all','pending','deferred','created','merged','rejected'],true))throw new InvalidArgumentException('Filter antrean tidak valid.');
        $queue=kiQueueData($batch,KI_ENGINE_CATALOG,$page,$status);
        $list=$queue['rows'];
        foreach($list as &$r){$d=json_decode($r['draft_json'],true);$r['title']=$d['fields']['judul'];$r['inventory']=$d['fields']['kode_inventaris'];$r['source']=$d['source'];unset($r['draft_json']);}unset($r);
        kandagaJson(true,'Antrean.',['rows'=>$list,'total'=>$queue['total'],'page'=>$queue['page'],'pages'=>$queue['pages'],'counts'=>$queue['counts']]);
    }
    if($action==='row') {
        $id=filter_var($_GET['row_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$id)throw new InvalidArgumentException('ID baris tidak valid.');
        $row=kiRow($id,false,KI_ENGINE_CATALOG);$masters=kiMasters();$suggestions=kiSuggestions($row['draft']['source'],$masters);
        $location=filter_var($_GET['location']??0,FILTER_VALIDATE_INT);
        if($location===false)throw new InvalidArgumentException('Lokasi tidak valid.');
        $candidates=kiCandidates($row['draft'],(int)$location,($_GET['historical']??'0')==='1');
        $target=filter_var($_GET['target_id']??0,FILTER_VALIDATE_INT);
        if($target===false||$target<0)throw new InvalidArgumentException('ID tujuan tidak valid.');
        $snapshot=$target?kiSnapshot($target):null;
        $auditSql='SELECT action,katalog_id,username,created_at,decision_json FROM katalog_import_audit WHERE import_row_id=?';$auditArgs=[$id];
        if(kiImportDetachable()){$auditSql.=' OR source_key=?';$auditArgs[]=$row['source_key'];}
        $audit=kiQuery($auditSql.' ORDER BY id DESC',$auditArgs)->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach($audit as &$item){$d=json_decode($item['decision_json'],true);$item['note']=$d['note']??'';unset($item['decision_json']);}unset($item);
        $duplicate=kiCandidates($row['draft'],$location,true);
        kandagaJson(true,'Pratinjau.',['row'=>$row,'suggestions'=>$suggestions,'candidates'=>$candidates,'duplicate'=>count($duplicate)>0,'linked'=>kiLinked($row['source_key']),'snapshot'=>$snapshot,'audit'=>$audit]);
    }
    if($action==='review') {
        $input=json_decode($_POST['payload']??'',true,512,JSON_THROW_ON_ERROR);
        if(!is_array($input))throw new InvalidArgumentException('Payload tidak valid.');
        $result=kiApprove($input);kandagaJson(true,'Keputusan tersimpan.',$result);
    }
    if($action==='delete_batch') {
        $batch=filter_var($_POST['batch_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$batch)throw new InvalidArgumentException('ID batch tidak valid.');
        $result=kiDeleteBatch($batch,KI_ENGINE_CATALOG);
        $message='Batch katalog cetak dihapus; '.$result['rows'].' baris antrean dibersihkan.';
        if($result['detachable'])$message.=' '.$result['detached_audit'].' catatan audit dan '.$result['detached_sources'].' tautan identitas tetap tersimpan.';
        kandagaJson(true,$message,$result);
    }
    kandagaJson(false,'Aksi tidak dikenal.',[],404);
}catch(JsonException $e){kandagaJson(false,'Format JSON tidak valid.',[],422);}catch(Throwable $e){kandagaFailure($e);}
