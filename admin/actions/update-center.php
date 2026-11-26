<?php
require_once __DIR__ . '/../includes/update-access.php';
header('Cache-Control: no-store');
$action=is_string($_POST['action']??null)?$_POST['action']:'';
$job=is_string($_POST['job']??null)?$_POST['job']:'';
try {
    $recovery=in_array($action,['job','tick','retry'],true)&&kandagaUpdateRecoveryAccess($job);
    if($recovery){if(($_SERVER['REQUEST_METHOD']??'')!=='POST')kandagaJson(false,'Metode tidak diizinkan.',[],405);kandagaCsrf();}
    else kandagaPost(true);
    $storage=kandagaUpdateStorage();
    // Use the immutable runner of the selected job while it updates its own live source.
    $runner=__DIR__.'/../includes/update-engine.php';
    if(preg_match('/^[a-f0-9]{32}$/D',$job)&&is_file($storage.'/'.$job.'/runner.php'))$runner=$storage.'/'.$job.'/runner.php';
    require_once $runner;
    $engine=new KandagaUpdater($conn,ROOT_PATH,$storage);$guard=$engine->lock();
    switch($action){
        case 'summary':$result=$engine->summary();break;
        case 'setup':$result=$engine->setup();break;
        case 'remote':$result=$engine->remote();break;
        case 'download':$result=$engine->fromRemote((string)($_POST['expected_version']??''));break;
        case 'upload_begin':
            $size=filter_var($_POST['size']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>KandagaUpdater::MAX_ZIP]]);
            if(!$size)throw new InvalidArgumentException('Ukuran ZIP harus 1 byte sampai 100 MB.');
            $token=bin2hex(random_bytes(16));$folder=$storage.'/incoming-'.$token;mkdir($folder,0700);
            KandagaUpdater::writeJson($folder.'/meta.json',['size'=>$size,'user'=>(int)$_SESSION['admin_id'],'created_at'=>time()]);file_put_contents($folder.'/package.zip','');
            $result=['upload'=>$token,'offset'=>0];break;
        case 'upload_chunk':
        case 'upload_finish':
            $token=(string)($_POST['upload']??'');if(!preg_match('/^[a-f0-9]{32}$/D',$token))throw new InvalidArgumentException('ID upload tidak valid.');
            $folder=$storage.'/incoming-'.$token;$meta=KandagaUpdater::json($folder.'/meta.json');
            if((int)$meta['user']!==(int)$_SESSION['admin_id'])throw new RuntimeException('Upload dimiliki akun lain.');
            $archive=$folder.'/package.zip';$offset=filesize($archive);
            if($action==='upload_finish'){
                if($offset!==(int)$meta['size'])throw new RuntimeException('Upload belum lengkap.');
                try{$result=$engine->inspect($archive,'upload');}finally{unlink($archive);unlink($folder.'/meta.json');rmdir($folder);}break;
            }
            $file=$_FILES['chunk']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name'])||$file['size']>524288)throw new RuntimeException('Potongan upload tidak diterima. Periksa batas POST PHP.');
            $expected=filter_var($_POST['offset']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
            if($expected===false||$expected>$offset)throw new RuntimeException('Posisi upload tidak sesuai.');
            if($expected===$offset){$chunk=file_get_contents($file['tmp_name']);if($offset+strlen($chunk)>$meta['size'])throw new RuntimeException('Upload melebihi ukuran paket.');if(file_put_contents($archive,$chunk,FILE_APPEND|LOCK_EX)!==strlen($chunk))throw new RuntimeException('Penyimpanan upload terhenti.');$offset+=strlen($chunk);}
            $result=['upload'=>$token,'offset'=>$offset];break;
        case 'upload':
            if(empty($_FILES['package']))throw new InvalidArgumentException('Paket tidak diterima. Periksa upload_max_filesize dan post_max_size PHP.');
            $file=$_FILES['package'];
            if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Upload gagal (kode '.(int)$file['error'].'). Periksa batas upload PHP.');
            if(!is_uploaded_file($file['tmp_name'])||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='zip')throw new InvalidArgumentException('Pilih berkas ZIP CMS Kandaga.');
            $result=$engine->inspect($file['tmp_name'],'upload');break;
        case 'schema':$result=$engine->schemaJob();break;
        case 'job':$result=$engine->publicJob($engine->job($job));break;
        case 'begin':$result=$engine->begin($job,($_POST['accept_modified']??'')==='1');break;
        case 'tick':$result=$engine->publicJob($engine->tick($job));break;
        case 'retry':$result=$engine->retry($job);break;
        case 'rollback':
            $result=$engine->rollback($job,($_POST['database']??'')==='1',(string)($_POST['confirmation']??''));kandagaUpdateGrant($job);$result=$engine->publicJob($engine->job($job));break;
        case 'delete':if(($_POST['confirmation']??'')!=='HAPUS BACKUP')throw new InvalidArgumentException('Konfirmasi hapus diperlukan.');$engine->deleteJob($job);$result=['deleted'=>true];break;
        case 'backup':
            $kind=(string)($_POST['kind']??'');$file=$engine->backupFile($job,$kind);
            header('Content-Type: '.($kind==='code'?'application/zip':'application/sql'));
            header('Content-Disposition: attachment; filename="kandaga-backup-'.$job.'-'.($kind==='code'?'code.zip':'database.sql').'"');header('Content-Length: '.filesize($file));readfile($file);exit;
        default:throw new InvalidArgumentException('Aksi pembaruan tidak dikenal.');
    }
    if(isset($result['id'],$result['state']))$result=$engine->publicJob($result);
    kandagaJson(true,'OK',['data'=>$result]);
}catch(Throwable $e){error_log('Kandaga update center: '.$e->getMessage());kandagaJson(false,$e->getMessage(),[],422);}
