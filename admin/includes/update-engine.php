<?php
/** Kandaga Update Engine format 1. The runner is snapshotted outside web root per job. */
class KandagaUpdater {
    const REPOSITORY = 'https://raw.githubusercontent.com/Ilhamkang/kandaga/gh-pages/';
    const MAX_ZIP = 104857600;
    const MAX_EXPANDED = 314572800;
    private mysqli $db;
    private string $root;
    private string $store;
    public function __construct(mysqli $db, string $root, string $store) { $this->db=$db; $this->root=$root; $this->store=$store; }
    public static function json(string $file): array {
        $v=json_decode((string)@file_get_contents($file),true);
        if (!is_array($v)) throw new RuntimeException('Metadata tidak terbaca: '.basename($file));
        return $v;
    }
    public static function writeJson(string $file,array $data): void {
        $tmp=$file.'.'.bin2hex(random_bytes(4)).'.tmp';
        if (file_put_contents($tmp,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX)===false || !rename($tmp,$file)) throw new RuntimeException('Gagal menyimpan status update.');
        @chmod($file,0600);
    }
    private function defaultBackupMode(array $manifest): string {
        $declared=(string)($manifest['backup']['default']??'auto');
        if(!in_array($declared,['auto','code','full'],true))$declared='auto';
        if($declared==='code')return empty($manifest['migrations'])?'code':'full';
        if($declared==='full')return 'full';
        return empty($manifest['migrations'])?'code':'full';
    }
    private function codeOnlyAllowed(array $manifest): bool {
        return empty($manifest['migrations']) && (($manifest['backup']['allow_code_only']??true)!==false);
    }
    private function databaseBackupAllowed(array $manifest): bool {
        return (($manifest['backup']['database']??'auto') !== 'never');
    }
    public function lock() {
        $h=fopen($this->store.'/engine.lock','c');
        if (!$h || !flock($h,LOCK_EX|LOCK_NB)) throw new RuntimeException('Proses update lain sedang bekerja. Coba lagi beberapa saat.');
        return $h;
    }
    public function dir(string $id): string {
        if (!preg_match('/^[a-f0-9]{32}$/D',$id)) throw new InvalidArgumentException('ID pekerjaan tidak valid.');
        $dir=$this->store.'/'.$id;
        if (is_link($dir) || !is_dir($dir)) throw new InvalidArgumentException('Pekerjaan tidak ditemukan.');
        return $dir;
    }
    public function job(string $id): array { return self::json($this->dir($id).'/job.json'); }
    private function save(array &$j,?string $message=null): void {
        $j['updated_at']=date('c');
        $j['last_activity_at']=$j['updated_at'];
        if ($message!==null) $j['log'][]=['at'=>date('c'),'by'=>(string)($_SESSION['username']??'admin'),'message'=>$message];
        self::writeJson($this->dir($j['id']).'/job.json',$j);
        // Keep a small heartbeat in the maintenance lock as well as job.json.
        // This is diagnostic only; it never advances or deletes a job.
        $lockPath=$this->root.'/maintenance.lock';
        if(is_file($lockPath)){
            $owner=json_decode((string)@file_get_contents($lockPath),true);
            if(is_array($owner)&&($owner['job']??null)===$j['id']){
                $owner['phase']=$j['state'];$owner['last_tick_at']=$j['last_activity_at'];
                self::writeJson($lockPath,$owner);
            }
        }
    }
    private function databaseIdentity(): string { return hash('sha256',json_encode($this->db->query('SELECT DATABASE() AS name, @@hostname AS host, @@port AS port')->fetch_assoc())); }
    public function version(): string {
        $md=(string)@file_get_contents($this->root.'/RELEASE.md');
        if (preg_match('/^# Kandaga v(\d+\.\d+\.\d+)\s*$/m',$md,$m)) return $m[1];
        return self::json($this->root.'/version.json')['version']??'0.0.0';
    }
    public static function safePath(string $name): string {
        if ($name==='' || strlen($name)>240 || preg_match('~[^a-zA-Z0-9_./@+ -]|(?:^|/)\.\.?(/|$)|//~',$name) || $name[0]==='/' || str_ends_with($name,'/')) throw new RuntimeException('Path paket tidak aman: '.$name);
        return $name;
    }
    public static function protectedPath(string $p): bool {
        return in_array($p,['config/database.php','config/database.local.php','config/analytics.local.php','config/update.local.php','maintenance.lock','update_cache.json'],true)
            || preg_match('~^(?:assets/images|gambar|uploads?|storage|kandaga_backup)(?:/|$)~i',$p)
            || preg_match('~(?:^|/)(?:\.env|\.git)(?:[./]|$)~i',$p);
    }
    private function target(string $name): string {
        self::safePath($name); if (self::protectedPath($name)) throw new RuntimeException('Berkas dilindungi: '.$name);
        $path=$this->root;
        foreach(explode('/',$name) as $part) { $path.='/'.$part; if(is_link($path)) throw new RuntimeException('Symlink tujuan tidak diizinkan: '.$name); }
        return $path;
    }
    public function ready(): bool {
        $q=$this->db->query("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='kandaga_schema_migrations'");
        return (int)$q->fetch_assoc()['n']===1;
    }
    public function setup(): array {
        if (is_file($this->root.'/maintenance.lock')) throw new RuntimeException('Selesaikan pemulihan/update yang sedang aktif sebelum menyiapkan pusat pembaruan.');
        $this->runSql((string)file_get_contents($this->root.'/migrations/007_update_center.sql'));
        $m=self::json($this->root.'/update-manifest.json');
        foreach($m['migrations'] as $migration) {
            if($this->checkSchema($migration)) $this->recordMigration($migration,'verified_existing',null);
        }
        return $this->summary();
    }
    private function checkSchema(array $m): bool {
        foreach($m['checks'] as $check) {
            $table=$check['table'];
            $s=$this->db->prepare('SELECT column_name AS column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=?');$s->bind_param('s',$table);$s->execute();
            $cols=array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'column_name');$s->close();
            if(!$cols || array_diff($check['columns'],$cols)) return false;
            foreach($check['unique']??[] as $indexCols) {
                $s=$this->db->prepare("SELECT index_name AS index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') AS cols FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND non_unique=0 GROUP BY index_name");$s->bind_param('s',$table);$s->execute();$indexes=array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'cols');$s->close();
                if(!in_array(implode(',',$indexCols),$indexes,true))return false;
            }
        }
        return true;
    }
    private function recordMigration(array $m,string $method,?string $job): void {
        $id=$m['id'];$hash=$m['sha256'];
        $s=$this->db->prepare('SELECT checksum FROM kandaga_schema_migrations WHERE migration_id=?');$s->bind_param('s',$id);$s->execute();$old=$s->get_result()->fetch_assoc();$s->close();
        if($old && !hash_equals($old['checksum'],$hash)) throw new RuntimeException('Checksum migrasi yang sudah dicatat berubah: '.$id);
        $s=$this->db->prepare('INSERT IGNORE INTO kandaga_schema_migrations (migration_id,checksum,method,job_id) VALUES (?,?,?,?)');$s->bind_param('ssss',$id,$hash,$method,$job);$s->execute();$s->close();
    }
    private function runSql(string $sql): void {
        $this->db->multi_query($sql);
        do { if($r=$this->db->store_result())$r->free(); } while($this->db->more_results()&&$this->db->next_result());
    }
    private function migrations(array $m): array {
        $out=[];$applied=[];
        if($this->ready()) foreach($this->db->query('SELECT migration_id,checksum FROM kandaga_schema_migrations')->fetch_all(MYSQLI_ASSOC) as $row)$applied[$row['migration_id']]=$row['checksum'];
        foreach($m['migrations'] as $x) {
            if(isset($applied[$x['id']])&&!hash_equals($applied[$x['id']],$x['sha256']))throw new RuntimeException('Riwayat checksum migrasi berbeda: '.$x['id']);
            $ok=$this->checkSchema($x);
            $x['state']=isset($applied[$x['id']])&&$ok?'applied':($ok?'verify_existing':'pending');
            $out[]=$x;
        }
        return $out;
    }
    public function summary(): array {
        $jobs=[];
        foreach(glob($this->store.'/*/job.json')?:[] as $file) {
            $j=self::json($file);$jobs[]=$this->publicJob($j);
        }
        usort($jobs,fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));
        $m=self::json($this->root.'/update-manifest.json');
        $maintenancePath=$this->root.'/maintenance.lock';
        $maintenance=is_file($maintenancePath);
        $maintenanceMeta=$maintenance?json_decode((string)@file_get_contents($maintenancePath),true):[];
        $maintenanceStarted=is_array($maintenanceMeta)?(string)($maintenanceMeta['started_at']??''):'';
        $maintenanceJob=is_array($maintenanceMeta)?(string)($maintenanceMeta['job']??''):'';
        $maintenancePhase=is_array($maintenanceMeta)?(string)($maintenanceMeta['phase']??''):'';
        $maintenanceLastTick=is_array($maintenanceMeta)?(string)($maintenanceMeta['last_tick_at']??''):'';
        $maintenanceAge=null;
        if($maintenanceStarted!==''){
            $started=strtotime($maintenanceStarted);
            if($started!==false)$maintenanceAge=max(0,time()-$started);
        }
        $maintenanceIdle=null;
        $activityStamp=$maintenanceLastTick!==''?$maintenanceLastTick:$maintenanceStarted;
        if($activityStamp!==''){
            $last=strtotime($activityStamp);
            if($last!==false)$maintenanceIdle=max(0,time()-$last);
        }
        $incoming=glob($this->store.'/incoming-*')?:[];
        $staleIncoming=0;
        foreach($incoming as $path){
            if(is_dir($path)&&@filemtime($path)!==false&&time()-(int)filemtime($path)>3600)$staleIncoming++;
        }
        return [
            'version'=>$this->version(),
            'database'=>$this->db->query('SELECT DATABASE() AS name')->fetch_assoc()['name'],
            'ready'=>$this->ready(),
            'jobs'=>array_slice($jobs,0,50),
            'maintenance'=>$maintenance,
            'maintenance_age_seconds'=>$maintenanceAge,
            'maintenance_stale'=>$maintenanceAge!==null&&$maintenanceAge>=1800,
            'maintenance_job'=>$maintenanceJob,
            'maintenance_phase'=>$maintenancePhase,
            'maintenance_last_tick_at'=>$maintenanceLastTick,
            'maintenance_idle_seconds'=>$maintenanceIdle,
            'maintenance_idle'=>$maintenanceIdle!==null&&$maintenanceIdle>=300,
            'incoming_uploads'=>count($incoming),
            'stale_incoming_uploads'=>$staleIncoming,
            'migrations'=>$this->migrations($m),
            'storage'=>$this->store,
            'upload_max'=>ini_get('upload_max_filesize'),
            'post_max'=>ini_get('post_max_size'),
            'limit_mb'=>100
        ];
    }
    public function publicJob(array $j): array {
        $manifest = is_array($j['manifest'] ?? null) ? $j['manifest'] : [];
        $j['description'] = (string)($manifest['description'] ?? '');
        $j['release_notes'] = is_array($manifest['release_notes'] ?? null) ? $manifest['release_notes'] : [];
        $j['code_only_allowed']=$this->codeOnlyAllowed($manifest);
        $j['backup_mode']=(string)($j['backup_mode']??$this->defaultBackupMode($manifest));
        $j['database_backup_allowed']=$this->databaseBackupAllowed($manifest);
        $j['backup_options']=$j['database_backup_allowed']?['auto','code','full']:['code'];
        $activity=(string)($j['last_activity_at']??$j['updated_at']??$j['created_at']??'');
        $activityAt=$activity!==''?strtotime($activity):false;
        $j['activity_age_seconds']=$activityAt===false?null:max(0,time()-$activityAt);
        $j['activity_stale']=in_array(($j['state']??''),['backup_code','backup_db','install','migrate','verify','rollback_code','restore_db'],true)
            && $j['activity_age_seconds']!==null && $j['activity_age_seconds']>=300;
        unset($j['database_meta'], $j['recovery_hash'], $j['manifest'], $j['written']);
        return $j;
    }
    public function remote(): array {
        $raw=$this->fetch(self::REPOSITORY.'version.json');$m=json_decode($raw,true);
        if(!is_array($m)||!preg_match('/^\d+\.\d+\.\d+$/D',$m['version']??'')||!preg_match('/^[a-zA-Z0-9_.-]+\.zip$/D',$m['zip_filename']??''))throw new RuntimeException('Manifest repositori lama tidak valid.');
        if(isset($m['sha256'])&&!preg_match('/^[a-fA-F0-9]{64}$/D',$m['sha256']))throw new RuntimeException('Checksum repositori tidak valid.');
        return ['version'=>$m['version'],'description'=>(string)($m['description']??''),'zip_filename'=>$m['zip_filename'],'sha256'=>strtolower($m['sha256']??''),'newer'=>version_compare($m['version'],$this->version(),'>')];
    }
    private function fetch(string $url,?string $dest=null): string {
        $c=curl_init($url);$size=0;$data='';$fp=$dest?fopen($dest,'wb'):null;
        if($dest&&!$fp)throw new RuntimeException('Tidak dapat menyiapkan unduhan.');
        curl_setopt_array($c,[CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>120,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_WRITEFUNCTION=>function($c,$bytes)use(&$size,&$data,$fp){$size+=strlen($bytes);if($size>($fp?self::MAX_ZIP:1048576))return 0;if($fp)return fwrite($fp,$bytes);$data.=$bytes;return strlen($bytes);}]);
        $ok=curl_exec($c);$status=curl_getinfo($c,CURLINFO_RESPONSE_CODE);curl_close($c);if($fp)fclose($fp);
        if($ok===false||$status!==200)throw new RuntimeException('Unduhan repositori gagal atau melewati batas ukuran (HTTP '.$status.').');
        return $data;
    }
    public function fromRemote(string $expected): array {
        $m=$this->remote();if(!$m['newer']||$m['version']!==$expected)throw new RuntimeException('Versi repositori berubah atau tidak lebih baru. Periksa kembali.');
        $temp=$this->store.'/download-'.bin2hex(random_bytes(8)).'.zip';
        try{$this->fetch(self::REPOSITORY.$m['zip_filename'],$temp);
            if($m['sha256']!==''&&!hash_equals($m['sha256'],hash_file('sha256',$temp)))throw new RuntimeException('Checksum ZIP repositori tidak sesuai.');
            return $this->inspect($temp,'repository',$m);
        }finally{@unlink($temp);}
    }
    private function validateManifest(array $m): void {
        if(($m['format']??null)!==1||($m['product']??'')!=='kandaga'||!preg_match('/^\d+\.\d+\.\d+$/D',$m['version']??'')||!preg_match('/^\d+\.\d+\.\d+$/D',$m['min_from']??''))throw new RuntimeException('Format manifest Kandaga tidak didukung. Gunakan paket v3.1.10 atau yang lebih baru dengan update-manifest.json.');
        if(!is_array($m['files']??null)||!count($m['files'])||!is_array($m['migrations']??null)||!is_array($m['delete']??null))throw new RuntimeException('Daftar berkas/migrasi paket tidak lengkap.');
        foreach(['RELEASE.md','version.json','config/bootstrap.php','admin/update.php','admin/includes/update-engine.php'] as $required)if(!isset($m['files'][$required]))throw new RuntimeException('Paket CMS tidak lengkap: '.$required);
        foreach($m['files'] as $name=>$hash){self::safePath($name);if(self::protectedPath($name)||!preg_match('/^[a-f0-9]{64}$/D',$hash))throw new RuntimeException('Daftar berkas paket mengandung path dilindungi/hash tidak valid.');}
        foreach($m['delete'] as $name){self::safePath($name);if(self::protectedPath($name)||isset($m['files'][$name])||in_array($name,['RELEASE.md','version.json','update-manifest.json'],true))throw new RuntimeException('Daftar penghapusan paket tidak valid.');}
        $ids=[];
        foreach($m['migrations'] as $x){
            if(!preg_match('/^[0-9]{3}_[a-z0-9_]+$/D',$x['id']??'')||isset($ids[$x['id']]))throw new RuntimeException('ID migrasi tidak valid/berulang.');$ids[$x['id']]=true;
            if(!isset($m['files'][$x['path']??''])||!str_starts_with($x['path'],'migrations/')||!str_ends_with($x['path'],'.sql')||($x['sha256']??'')!==$m['files'][$x['path']]||($x['idempotent']??false)!==true||!is_bool($x['backward_compatible']??null)||empty($x['checks']))throw new RuntimeException('Definisi migrasi tidak lengkap: '.$x['id']);
            foreach($x['checks'] as $ch){if(!preg_match('/^[a-zA-Z0-9_]+$/D',$ch['table']??'')||empty($ch['columns']))throw new RuntimeException('Pemeriksaan schema tidak valid.');foreach($ch['columns'] as $col)if(!preg_match('/^[a-zA-Z0-9_]+$/D',$col))throw new RuntimeException('Kolom migrasi tidak valid.');}
        }
        $ordered=array_keys($ids);$sorted=$ordered;sort($sorted,SORT_STRING);if($ordered!==$sorted)throw new RuntimeException('Urutan migrasi tidak valid.');
    }
    private function requirements(array $m): void {
        if(version_compare(PHP_VERSION,$m['requires']['php']??'8.1','<'))throw new RuntimeException('Versi PHP belum memenuhi kebutuhan paket.');
        foreach($m['requires']['extensions']??['mysqli','zip','mbstring','curl'] as $ext)if(!extension_loaded($ext))throw new RuntimeException('Ekstensi PHP belum tersedia: '.$ext);
        $dbVersion=$this->db->query('SELECT VERSION() v')->fetch_assoc()['v'];$family=stripos($dbVersion,'MariaDB')!==false?'mariadb':'mysql';preg_match('/\d+\.\d+\.\d+/',$dbVersion,$v);
        if(version_compare($v[0]??'0',$m['requires'][$family]??($family==='mysql'?'8.0':'10.11'),'<'))throw new RuntimeException('Versi '.$family.' belum memenuhi kebutuhan paket.');
    }
    public function inspect(string $archive,string $source,array $remote=[]): array {
        if(is_file($this->root.'/maintenance.lock'))throw new RuntimeException('Ada proses aktif. Selesaikan atau pulihkan dahulu.');
        if(!is_file($archive)||filesize($archive)>self::MAX_ZIP)throw new RuntimeException('ZIP tidak tersedia atau lebih dari 100 MB.');
        $zip=new ZipArchive();if($zip->open($archive)!==true)throw new RuntimeException('Arsip ZIP tidak dapat dibaca.');
        $id=bin2hex(random_bytes(16));$dir=$this->store.'/'.$id;mkdir($dir,0700);mkdir($dir.'/stage',0700);
        try{
            $prefix=$zip->locateName('public_html/update-manifest.json')!==false?'public_html/':'';
            $info=$zip->statName($prefix.'update-manifest.json');if(!$info||$info['size']>2097152)throw new RuntimeException('Manifest hilang atau terlalu besar.');$raw=$zip->getFromName($prefix.'update-manifest.json');$m=json_decode($raw?:'',true);if(!is_array($m))throw new RuntimeException('Paket belum memiliki update-manifest.json; gunakan format paket v3.1.10+.');
            $this->validateManifest($m);$this->requirements($m);
            if(!version_compare($m['version'],$this->version(),'>'))throw new RuntimeException('Versi paket harus lebih baru daripada versi terpasang.');
            if(version_compare($this->version(),$m['min_from'],'<'))throw new RuntimeException('Paket memerlukan versi awal minimal '.$m['min_from'].'.');
            if($remote&&$m['version']!==$remote['version'])throw new RuntimeException('Versi ZIP berbeda dari repositori.');
            $seen=[];$bytes=0;
            for($i=0;$i<$zip->numFiles;$i++){
                $st=$zip->statIndex($i);$original=$st['name'];$isDir=str_ends_with($original,'/');self::safePath(rtrim($original,'/'));
                if(isset($seen[strtolower($original)]))throw new RuntimeException('Path ZIP berulang atau berbeda kapitalisasi.');$seen[strtolower($original)]=true;
                $opsys=0;$attr=0;$zip->getExternalAttributesIndex($i,$opsys,$attr);if(($attr>>16&0170000)===0120000)throw new RuntimeException('Symlink ZIP ditolak.');
                $bytes+=(int)$st['size'];if($zip->numFiles>10000||$bytes>self::MAX_EXPANDED||($st['size']>1048576&&$st['size']/max(1,$st['comp_size'])>300))throw new RuntimeException('Ukuran/jumlah/rasio kompresi ZIP melampaui batas.');
                if($isDir)continue;
                if($prefix&&!str_starts_with($original,$prefix))continue;
                $name=substr($original,strlen($prefix));
                if(self::protectedPath($name))continue;
                if($name!=='update-manifest.json'&&!isset($m['files'][$name]))throw new RuntimeException('Berkas aplikasi tidak terdaftar pada manifest: '.$name);
                $content=$zip->getFromIndex($i);if($content===false)throw new RuntimeException('Gagal membaca isi paket.');
                if($name!=='update-manifest.json'&&!hash_equals($m['files'][$name],hash('sha256',$content)))throw new RuntimeException('Checksum berkas tidak sesuai: '.$name);
                if(str_ends_with(strtolower($name),'.php')){try{token_get_all($content,TOKEN_PARSE);}catch(ParseError $e){throw new RuntimeException('Sintaks PHP paket tidak valid: '.$name);}}
                $dest=$dir.'/stage/'.$name;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);if(file_put_contents($dest,$content)===false)throw new RuntimeException('Staging paket gagal.');
            }
            foreach($m['files'] as $name=>$hash)if(!is_file($dir.'/stage/'.$name))throw new RuntimeException('Berkas paket hilang: '.$name);
            $v=self::json($dir.'/stage/version.json');$md=(string)file_get_contents($dir.'/stage/RELEASE.md');
            if(($v['version']??'')!==$m['version']||!preg_match('/^# Kandaga v'.preg_quote($m['version'],'/').'\s*$/m',$md))throw new RuntimeException('Versi RELEASE.md, version.json, dan manifest tidak sinkron.');
            $j=$this->newJob($id,$source,$m);$j['archive_sha256']=hash_file('sha256',$archive);$j['checksum_source']=$remote?(!empty($remote['sha256'])?'repository':'legacy_without_checksum'):'admin_upload';
            $j['changes']=$this->changes($m);$j['migrations']=$this->migrations($m);$j['expanded_bytes']=$bytes;
            copy(__FILE__,$dir.'/runner.php');$this->save($j,'Paket diperiksa. Menunggu tindakan admin.');return $this->publicJob($j);
        }catch(Throwable $e){$this->removeTree($dir);throw $e;}finally{$zip->close();}
    }
    private function newJob(string $id,string $source,array $m): array {
        return ['id'=>$id,'source'=>$source,'database'=>$this->db->query('SELECT DATABASE() AS name')->fetch_assoc()['name'],'database_identity'=>$this->databaseIdentity(),'from'=>$this->version(),'to'=>$m['version'],'manifest'=>$m,'state'=>'preview','created_at'=>date('c'),'updated_at'=>date('c'),'actor'=>(string)($_SESSION['username']??'admin'),'log'=>[],'written'=>[],'cursor'=>0,'db_started'=>false,'backup_mode'=>$this->defaultBackupMode($m),'code_backup_ready'=>false,'backup_ready'=>false];
    }
    private function changes(array $m): array {
        $old=is_file($this->root.'/update-manifest.json')?self::json($this->root.'/update-manifest.json'):['files'=>[]];$out=[];
        foreach(array_merge(array_keys($m['files']),['update-manifest.json'],$m['delete']) as $name){
            $dest=$this->target($name);$current=is_file($dest)?hash_file('sha256',$dest):null;$delete=in_array($name,$m['delete'],true);
            if(!$delete&&isset($m['files'][$name])&&$current===$m['files'][$name])continue;
            if($delete&&$current===null)continue;
            $parent=dirname($dest);while(!is_dir($parent))$parent=dirname($parent);
            if(!is_writable($parent)||(is_file($dest)&&!is_writable($dest)))throw new RuntimeException('Berkas/direktori belum dapat ditulis PHP: '.$name);
            $conflict=isset($old['files'][$name])&&$current!==$old['files'][$name];
            $out[]=['path'=>$name,'action'=>$delete?'delete':($current===null?'add':'replace'),'before'=>$current,'local_modified'=>$conflict];
        }
        return $out;
    }
    public function schemaJob(): array {
        if(is_file($this->root.'/maintenance.lock'))throw new RuntimeException('Ada update aktif.');
        if(!$this->ready())throw new RuntimeException('Siapkan pusat pembaruan dahulu.');
        $m=self::json($this->root.'/update-manifest.json');$this->validateManifest($m);
        $id=bin2hex(random_bytes(16));$dir=$this->store.'/'.$id;mkdir($dir,0700);mkdir($dir.'/stage',0700);
        foreach($m['migrations'] as $x){$dst=$dir.'/stage/'.$x['path'];if(!is_dir(dirname($dst)))mkdir(dirname($dst),0700,true);copy($this->root.'/'.$x['path'],$dst);if(!hash_equals($x['sha256'],hash_file('sha256',$dst)))throw new RuntimeException('Checksum SQL lokal berubah.');}
        $j=$this->newJob($id,'database',$m);$j['changes']=[];$j['migrations']=$this->migrations($m);$j['expanded_bytes']=0;$j['checksum_source']='local_installed';
        copy(__FILE__,$dir.'/runner.php');$this->save($j,'Pratinjau sinkronisasi database dibuat.');return $j;
    }
    public function begin(string $id,bool $acceptModified,?string $backupMode=null): array {
        $j=$this->job($id);if($j['state']!=='preview')throw new RuntimeException('Pekerjaan sudah dimulai.');
        if(!$this->ready())throw new RuntimeException('Siapkan pusat pembaruan dahulu.');
        if(is_file($this->root.'/maintenance.lock'))throw new RuntimeException('Ada maintenance/update yang masih aktif.');
        if(($j['database_identity']??'')!==$this->databaseIdentity())throw new RuntimeException('Database aktif berbeda dari database pada pratinjau.');
        if($j['from']!==$this->version())throw new RuntimeException('Versi terpasang berubah; periksa paket kembali.');
        $fresh=$j['source']==='database'?[]:$this->changes($j['manifest']);
        if($fresh!==$j['changes'])throw new RuntimeException('Berkas deploy berubah setelah pratinjau. Unggah/periksa ulang paket.');
        if(array_filter($fresh,fn($x)=>$x['local_modified'])&&!$acceptModified)throw new RuntimeException('Konfirmasikan penimpaan modifikasi lokal yang tercantum.');
        $this->requirements($j['manifest']);
        $requested=(string)($backupMode??($_POST['backup_mode']??'auto'));
        if(!in_array($requested,['auto','code','full'],true))throw new InvalidArgumentException('Mode backup tidak valid.');
        if($requested==='full'&&!$this->databaseBackupAllowed($j['manifest']))throw new RuntimeException('Paket ini ditetapkan sebagai pembaruan kode saja; backup database dinonaktifkan.');
        $effective=$requested==='auto'?$this->defaultBackupMode($j['manifest']):$requested;
        if($effective==='full'&&!$this->databaseBackupAllowed($j['manifest']))throw new RuntimeException('Paket ini ditetapkan sebagai pembaruan kode saja; backup database dinonaktifkan.');
        if($effective==='code'&&!$this->codeOnlyAllowed($j['manifest']))throw new RuntimeException('Backup kode saja hanya tersedia untuk paket tanpa migrasi SQL.');
        $j['backup_mode']=$effective;
        $need=($j['expanded_bytes']*2)+52428800;
        if($effective!=='code'){
            $tables=$this->db->query('SHOW FULL TABLES')->fetch_all(MYSQLI_NUM);
            foreach($tables as $table)if($table[1]!=='BASE TABLE')throw new RuntimeException('Database memiliki view. Backup otomatis versi ini hanya mendukung tabel biasa.');
            if($this->db->query('SHOW TRIGGERS')->num_rows)throw new RuntimeException('Database memiliki trigger; backup otomatis belum mendukung trigger.');
            $special=$this->db->query("SELECT (SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema=DATABASE()) + (SELECT COUNT(*) FROM information_schema.events WHERE event_schema=DATABASE()) n")->fetch_assoc();
            if((int)$special['n'])throw new RuntimeException('Database memiliki routine/event; backup otomatis belum mendukung objek ini.');
            $engines=$this->db->query("SELECT table_name AS table_name, engine AS engine FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'");
            foreach($engines->fetch_all(MYSQLI_ASSOC) as $t)if(strtolower($t['engine'])!=='innodb')throw new RuntimeException('Backup/pemulihan bertahap membutuhkan InnoDB: '.$t['table_name']);
            $estimate=(int)$this->db->query('SELECT COALESCE(SUM(data_length+index_length),0) n FROM information_schema.tables WHERE table_schema=DATABASE()')->fetch_assoc()['n'];
            $need+=($estimate*4);
        }
        if(disk_free_space($this->store)<$need||disk_free_space($this->root)<$j['expanded_bytes']+10485760)throw new RuntimeException('Ruang kosong belum cukup untuk staging dan backup.');
        $lock=fopen($this->root.'/maintenance.lock','x');if(!$lock)throw new RuntimeException('Tidak dapat mengaktifkan maintenance.');fwrite($lock,json_encode(['job'=>$id,'started_at'=>date('c'),'phase'=>'backup_code','last_tick_at'=>date('c')]));fclose($lock);
        $j['state']='backup_code';$this->save($j,'Maintenance aktif; pencadangan dimulai.');return $j;
    }
    private function q(string $identifier): string {return '`'.str_replace('`','``',$identifier).'`';}
    private function backupCode(array &$j): void {
        $dir=$this->dir($j['id']);$z=new ZipArchive();if($z->open($dir.'/code.zip',ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('ZIP backup gagal dibuat.');
        foreach($j['changes'] as $x){$dest=$this->target($x['path']);$actual=is_file($dest)?hash_file('sha256',$dest):null;if($actual!==$x['before'])throw new RuntimeException('Kode berubah sebelum backup: '.$x['path']);}
        foreach($j['changes'] as $x)if($x['before']!==null&&!$z->addFile($this->target($x['path']),$x['path']))throw new RuntimeException('Backup kode gagal.');
        $z->addFromString('BACKUP-INFO.json',json_encode(['from'=>$j['from'],'to'=>$j['to'],'changes'=>$j['changes']],JSON_PRETTY_PRINT));if(!$z->close())throw new RuntimeException('ZIP backup tidak lengkap.');
        $j['code_hash']=hash_file('sha256',$dir.'/code.zip');$j['code_backup_ready']=true;
        if(($j['backup_mode']??'full')==='code'){
            $j['backup_ready']=false;$j['state']='install';$j['cursor']=0;
            $this->save($j,'Cadangan kode selesai. Backup database dilewati sesuai mode pembaruan.');
            return;
        }
        $meta=[];
        foreach($this->db->query('SHOW FULL TABLES')->fetch_all(MYSQLI_NUM) as $t){
            $name=$t[0];$create=$this->db->query('SHOW CREATE TABLE '.$this->q($name))->fetch_row()[1];$columns=[];
            foreach($this->db->query('SHOW FULL COLUMNS FROM '.$this->q($name))->fetch_all(MYSQLI_ASSOC) as $c)if(!preg_match('/(?:VIRTUAL|STORED|PERSISTENT) GENERATED/i',$c['Extra']))$columns[]=$c['Field'];
            $meta[]=['name'=>$name,'create'=>$create,'columns'=>$columns,'rows'=>0];
        }
        self::writeJson($dir.'/db-meta.json',$meta);
        file_put_contents($dir.'/database.sql',"-- Kandaga database backup; restore only to the intended database.\nSET NAMES utf8mb4;\nSET @KANDAGA_OLD_SQL_MODE=@@SQL_MODE;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET FOREIGN_KEY_CHECKS=0;\n");
        $j['state']='backup_db';$j['table']=0;$j['offset']=0;$j['backup_sql_pos']=filesize($dir.'/database.sql');$j['backup_data_pos']=0;$this->save($j,'Cadangan kode selesai. Mulai backup database penuh.');
    }
    private function backupDb(array &$j): void {
        $dir=$this->dir($j['id']);$meta=self::json($dir.'/db-meta.json');$i=$j['table'];
        if($i>=count($meta)){
            file_put_contents($dir.'/database.sql',"SET FOREIGN_KEY_CHECKS=1;\nSET SQL_MODE=@KANDAGA_OLD_SQL_MODE;\n",FILE_APPEND);
            $j['backup_ready']=true;$j['code_backup_ready']=true;$j['code_hash']=hash_file('sha256',$dir.'/code.zip');$j['sql_hash']=hash_file('sha256',$dir.'/database.sql');$j['db_meta_hash']=hash_file('sha256',$dir.'/db-meta.json');
            $j['state']='install';$j['cursor']=0;$this->save($j,'Backup kode dan seluruh tabel database selesai.');return;
        }
        $t=&$meta[$i];$file=$dir.'/table-'.$i.'.jsonl';
        // Discard any uncommitted tail after request interruption, then replay this chunk.
        $sf=fopen($dir.'/database.sql','c+b');ftruncate($sf,$j['backup_sql_pos']);fclose($sf);
        $df=fopen($file,'c+b');ftruncate($df,$j['backup_data_pos']);fclose($df);
        if($j['offset']===0){file_put_contents($file,'');file_put_contents($dir.'/database.sql','DROP TABLE IF EXISTS '.$this->q($t['name']).";\n".$t['create'].";\n",FILE_APPEND);}
        $cols=implode(',',array_map([$this,'q'],$t['columns']));
        $rawCols=implode(',',array_map(fn($col)=>'CAST('.$this->q($col).' AS BINARY)',$t['columns']));
        $rows=$this->db->query('SELECT '.$rawCols.' FROM '.$this->q($t['name']).' LIMIT 200 OFFSET '.(int)$j['offset'])->fetch_all(MYSQLI_NUM);
        $fh=fopen($file,'ab');$sql=fopen($dir.'/database.sql','ab');if(!$fh||!$sql)throw new RuntimeException('Backup database tidak dapat ditulis.');fseek($fh,0,SEEK_END);fseek($sql,0,SEEK_END);
        foreach($rows as $row){$encoded=array_map(fn($v)=>$v===null?null:base64_encode((string)$v),$row);$line=json_encode($encoded)."\n";if(fwrite($fh,$line)!==strlen($line))throw new RuntimeException('Backup terhenti: penyimpanan penuh.');
            $line='INSERT INTO '.$this->q($t['name']).' ('.$cols.') VALUES ('.implode(',',array_map(fn($v)=>$v===null?'NULL':"X'".bin2hex((string)$v)."'",$row)).");\n";
            if(fwrite($sql,$line)!==strlen($line))throw new RuntimeException('Backup SQL tidak lengkap.');
        }$j['backup_data_pos']=ftell($fh);$j['backup_sql_pos']=ftell($sql);fclose($fh);fclose($sql);
        $j['offset']+=count($rows);$t['rows']=$j['offset'];
        if(count($rows)<200){$t['sha256']=hash_file('sha256',$file);$j['table']++;$j['offset']=0;$j['backup_data_pos']=0;}
        self::writeJson($dir.'/db-meta.json',$meta);$this->save($j);
    }
    private function install(array &$j): void {
        $dir=$this->dir($j['id']);$end=min(count($j['changes']),$j['cursor']+15);
        for(;$j['cursor']<$end;$j['cursor']++){
            $x=$j['changes'][$j['cursor']];$name=$x['path'];
            if(in_array($name,['RELEASE.md','version.json','update-manifest.json'],true))continue;
            $this->writeEntry($j,$x);
        }
        if($j['cursor']>=count($j['changes'])){$j['state']='migrate';$j['cursor']=0;$this->save($j,'Berkas aplikasi terpasang. Memeriksa migrasi database.');}else $this->save($j);
    }
    private function writeEntry(array &$j,array $x): void {
        $name=$x['path'];$dest=$this->target($name);$dir=$this->dir($j['id']);
        if(!in_array($name,$j['written'],true)){$j['written'][]=$name;$this->save($j);}
        if($x['action']==='delete'){if(is_file($dest)&&!unlink($dest))throw new RuntimeException('Gagal menghapus berkas lama: '.$name);return;}
        $src=$dir.'/stage/'.$name;$hash=$name==='update-manifest.json'?hash_file('sha256',$src):$j['manifest']['files'][$name];
        if(!is_file($src)||!hash_equals($hash,hash_file('sha256',$src)))throw new RuntimeException('Staging berubah: '.$name);
        $this->atomicFile($src,$dest);
    }
    private function atomicFile(string $src,string $dest): void {
        if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0755,true))throw new RuntimeException('Gagal membuat folder kode.');
        $tmp=$dest.'.kandaga-'.bin2hex(random_bytes(4)).'.tmp';
        if(!copy($src,$tmp))throw new RuntimeException('Gagal menyalin kode.');
        chmod($tmp,is_file($dest)?(fileperms($dest)&0777):0644);
        if(!rename($tmp,$dest)){@unlink($tmp);throw new RuntimeException('Gagal mengganti berkas kode.');}
        if(function_exists('opcache_invalidate'))@opcache_invalidate($dest,true);
    }
    private function migrate(array &$j): void {
        $i=$j['cursor'];$list=$j['manifest']['migrations'];
        if($i>=count($list)){$j['state']='verify';$this->save($j,'Seluruh migrasi selesai. Memeriksa hasil instalasi.');return;}
        $m=$list[$i];$source=$this->dir($j['id']).'/stage/'.$m['path'];
        if(!hash_equals($m['sha256'],hash_file('sha256',$source)))throw new RuntimeException('Checksum SQL berubah.');
        $existing=$this->checkSchema($m);
        if(!$existing){
            $j['db_started']=true;if(!$m['backward_compatible'])$j['requires_db_restore']=true;$this->save($j,'Menjalankan migrasi '.$m['id']);
            $this->runSql((string)file_get_contents($source));
            if(!$this->checkSchema($m))throw new RuntimeException('Pemeriksaan schema gagal setelah migrasi '.$m['id']);
        }
        $this->recordMigration($m,$existing?'verified_existing':'applied',$j['id']);$j['cursor']++;$this->save($j,'Migrasi '.$m['id'].' siap.');
    }
    private function verify(array &$j): void {
        $this->db->query('SELECT 1');
        foreach($j['manifest']['migrations'] as $m)if(!$this->checkSchema($m))throw new RuntimeException('Schema belum lengkap: '.$m['id']);
        if($j['source']!=='database'){
            foreach($j['manifest']['files'] as $name=>$hash){if(in_array($name,['RELEASE.md','version.json'],true))continue;if(!is_file($this->target($name))||!hash_equals($hash,hash_file('sha256',$this->target($name))))throw new RuntimeException('Verifikasi berkas gagal: '.$name);}
            foreach($j['changes'] as $x)if(in_array($x['path'],['RELEASE.md','version.json','update-manifest.json'],true))$this->writeEntry($j,$x);
        }
        $j['state']='completed';$this->save($j,'Update berhasil. Koneksi, schema, dan checksum berkas telah diperiksa.');$this->releaseMaintenance($j);@unlink($this->root.'/update_cache.json');
    }
    private function releaseMaintenance(array $j): void {
        $path=$this->root.'/maintenance.lock';if(!is_file($path))return;
        $owner=json_decode((string)file_get_contents($path),true);
        if(($owner['job']??null)!==$j['id'])throw new RuntimeException('Kunci maintenance dimiliki proses lain.');
        if(!unlink($path))throw new RuntimeException('Gagal menonaktifkan maintenance.');
    }
    public function tick(string $id): array {
        $j=$this->job($id);
        if(!in_array($j['state'],['backup_code','backup_db','install','migrate','verify','rollback_code','restore_db'],true))return $j;
        $owner=json_decode((string)@file_get_contents($this->root.'/maintenance.lock'),true);
        if(($owner['job']??'')!==$id)throw new RuntimeException('Maintenance tidak sesuai dengan pekerjaan ini.');
        $phase=$j['state'];
        try{
            // Record a heartbeat before doing work so a closed browser or a
            // worker failure is visible without guessing from file mtimes.
            $this->save($j);
            switch($j['state']){
                case 'backup_code':$this->backupCode($j);break;
                case 'backup_db':$this->backupDb($j);break;
                case 'install':$this->install($j);break;
                case 'migrate':$this->migrate($j);break;
                case 'verify':$this->verify($j);break;
                case 'rollback_code':$this->rollbackStep($j);break;
                case 'restore_db':$this->restoreDbStep($j);break;
            }
        }catch(Throwable $e){
            $j['error']=$e->getMessage();$j['failed_phase']=$phase;
            if(!$j['written']&&!$j['db_started']&&!in_array($j['state'],['restore_db','rollback_code'],true)){$j['state']='failed_safe';$this->save($j,'Proses dihentikan sebelum aplikasi berubah: '.$e->getMessage());$this->releaseMaintenance($j);}
            else{$j['state']='needs_recovery';$this->save($j,'Proses terhenti. Maintenance dipertahankan: '.$e->getMessage());}
        }
        return $j;
    }
    public function retry(string $id): array {
        $j=$this->job($id);if($j['state']!=='needs_recovery')throw new RuntimeException('Pekerjaan tidak memerlukan pemulihan.');
        // Backup interruption is never resumed as a trusted snapshot; failures before mutation are safe exits.
        if(!in_array($j['failed_phase'],['install','migrate','verify','rollback_code','restore_db'],true))throw new RuntimeException('Tahap ini tidak dapat dilanjutkan.');
        $j['state']=$j['failed_phase'];$this->save($j,'Admin melanjutkan tahap yang terhenti.');return $j;
    }
    public function rollback(string $id,bool $database,string $confirmation): array {
        $j=$this->job($id);
        $codeReady=!empty($j['code_backup_ready'])||!empty($j['backup_ready']);
        if(!in_array($j['state'],['completed','needs_recovery','rolled_back'],true)||!$codeReady)throw new RuntimeException('Backup kode untuk pekerjaan ini belum tersedia.');
        if($database&&empty($j['backup_ready']))throw new RuntimeException('Backup database tidak tersedia pada pekerjaan ini.');
        if($j['state']==='rolled_back'&&!$database)throw new RuntimeException('Kode sudah dipulihkan.');
        foreach(glob($this->store.'/*/job.json')?:[] as $f){$other=self::json($f);if($other['id']!==$id&&strcmp($other['created_at'],$j['created_at'])>0&&in_array($other['state'],['completed','needs_recovery','install','migrate','verify'],true))throw new RuntimeException('Pulihkan pekerjaan terbaru dahulu.');}
        if(!empty($j['requires_db_restore'])&&!$database)throw new RuntimeException('Migrasi ini memerlukan pemulihan database bersama kode.');
        if(($j['failed_phase']??'')==='restore_db'&&!$database)throw new RuntimeException('Pemulihan database sudah dimulai; lanjutkan pemulihan tersebut.');
        if($database&&$confirmation!=='PULIHKAN DATABASE')throw new RuntimeException('Ketik PULIHKAN DATABASE untuk menyetujui penggantian database dengan snapshot backup.');
        if(($j['database_identity']??'')!==$this->databaseIdentity())throw new RuntimeException('Backup ini berasal dari database/server yang berbeda.');
        $current=$this->version();if(!in_array($current,[$j['from'],$j['to']],true))throw new RuntimeException('Versi deploy tidak sesuai dengan backup ini. Pulihkan pekerjaan terbaru dahulu.');
        $dir=$this->dir($id);
        $integrity=['code.zip'=>'code_hash'];if($database)$integrity+=['database.sql'=>'sql_hash','db-meta.json'=>'db_meta_hash'];
        foreach($integrity as $f=>$key)if(!isset($j[$key])||!is_file($dir.'/'.$f)||!hash_equals($j[$key],hash_file('sha256',$dir.'/'.$f)))throw new RuntimeException('Integritas backup tidak sesuai.');
        if(is_file($this->root.'/maintenance.lock')){$owner=self::json($this->root.'/maintenance.lock');if(($owner['job']??'')!==$id)throw new RuntimeException('Update lain masih aktif.');}
        else self::writeJson($this->root.'/maintenance.lock',['job'=>$id,'started_at'=>date('c')]);
        $j['restore_database']=$database;$j['state']='rollback_code';$j['cursor']=0;$this->save($j,$database?'Pemulihan kode dan database dimulai atas konfirmasi admin.':'Pemulihan kode dimulai; migrasi tambahan yang kompatibel dipertahankan.');return $j;
    }
    private function rollbackStep(array &$j): void {
        $dir=$this->dir($j['id']);$z=new ZipArchive();if($z->open($dir.'/code.zip')!==true)throw new RuntimeException('Backup kode tidak dapat dibuka.');
        $end=min(count($j['changes']),$j['cursor']+15);
        for(;$j['cursor']<$end;$j['cursor']++){$x=$j['changes'][$j['cursor']];$dest=$this->target($x['path']);
            if($x['before']===null){if(is_file($dest)&&!unlink($dest))throw new RuntimeException('Gagal menghapus berkas baru saat pemulihan.');}
            else{$bytes=$z->getFromName($x['path']);if($bytes===false||!hash_equals($x['before'],hash('sha256',$bytes)))throw new RuntimeException('Backup berkas tidak sesuai.');$temp=$dir.'/restore-file.tmp';file_put_contents($temp,$bytes);$this->atomicFile($temp,$dest);}
        }$z->close();
        if($j['cursor']>=count($j['changes'])){
            if(!empty($j['restore_database'])){$j['state']='restore_db';$j['table']=0;$j['offset']=0;$j['restore_dropped']=false;$this->save($j,'Kode dipulihkan. Memulihkan snapshot database.');}
            else{$j['state']='rolled_back';$this->save($j,'Kode berhasil dipulihkan. Database tidak diubah kembali.');$this->releaseMaintenance($j);@unlink($this->root.'/update_cache.json');}
        }else $this->save($j);
    }
    private function restoreDbStep(array &$j): void {
        $dir=$this->dir($j['id']);$meta=self::json($dir.'/db-meta.json');$this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $checkpoint='kandaga_restore_'.substr($j['id'],0,16);
        $oldSqlMode=$this->db->query('SELECT @@session.sql_mode AS mode')->fetch_assoc()['mode'];$this->db->query("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");
        try{
            if(!$j['restore_dropped']){
                foreach($meta as $i=>$t)if(!hash_equals($t['sha256'],hash_file('sha256',$dir.'/table-'.$i.'.jsonl')))throw new RuntimeException('Snapshot tabel rusak: '.$t['name']);
                // User explicitly confirmed replacing the entire application database.
                foreach($this->db->query('SHOW FULL TABLES')->fetch_all(MYSQLI_NUM) as $t){if($t[1]!=='BASE TABLE')throw new RuntimeException('Objek database baru tidak didukung untuk restore.');$this->db->query('DROP TABLE IF EXISTS '.$this->q($t[0]));}
                $this->db->query('CREATE TABLE '.$this->q($checkpoint).' (table_no INT PRIMARY KEY, byte_offset BIGINT NOT NULL) ENGINE=InnoDB');
                $j['restore_dropped']=true;$this->save($j);return;
            }
            if($j['table']>=count($meta)){$this->db->query('DROP TABLE IF EXISTS '.$this->q($checkpoint));$j['state']='restored';$this->save($j,'Kode dan database berhasil dipulihkan ke snapshot sebelum update.');$this->releaseMaintenance($j);@unlink($this->root.'/update_cache.json');return;}
            $i=$j['table'];$t=$meta[$i];
            $progress=$this->db->query('SELECT byte_offset FROM '.$this->q($checkpoint).' WHERE table_no='.(int)$i)->fetch_assoc();
            if($progress)$j['offset']=(int)$progress['byte_offset'];
            if(!$progress){$this->db->query('DROP TABLE IF EXISTS '.$this->q($t['name']));$this->db->query($t['create']);}
            $fh=fopen($dir.'/table-'.$i.'.jsonl','rb');fseek($fh,$j['offset']);$n=0;
            $this->db->begin_transaction();
            try{while($n<200&&($line=fgets($fh))!==false){$row=json_decode($line,true,512,JSON_THROW_ON_ERROR);$vals=array_map(fn($v)=>$v===null?'NULL':"X'".bin2hex(base64_decode($v,true))."'",$row);
                $this->db->query('INSERT INTO '.$this->q($t['name']).' ('.implode(',',array_map([$this,'q'],$t['columns'])).') VALUES ('.implode(',',$vals).')');$n++;}
                $position=ftell($fh);$this->db->query('REPLACE INTO '.$this->q($checkpoint).' (table_no,byte_offset) VALUES ('.(int)$i.','.(int)$position.')');$this->db->commit();
            }catch(Throwable $e){$this->db->rollback();throw $e;}
            $j['offset']=ftell($fh);$done=feof($fh);fclose($fh);
            if($done){$j['table']++;$j['offset']=0;}$this->save($j);
        }finally{$this->db->query('SET FOREIGN_KEY_CHECKS=1');$this->db->query("SET SESSION sql_mode='".$this->db->real_escape_string($oldSqlMode)."'");}
    }
    public function backupFile(string $id,string $kind): string {
        if(!in_array($kind,['code','database'],true))throw new RuntimeException('Jenis backup tidak valid.');
        $j=$this->job($id);
        if($kind==='code'&&!empty($j['code_backup_ready'])){}elseif($kind==='database'&&!empty($j['backup_ready'])){}else throw new RuntimeException('Jenis backup belum tersedia.');
        $path=$this->dir($id).'/'.($kind==='code'?'code.zip':'database.sql');if(!hash_equals($j[$kind==='code'?'code_hash':'sql_hash'],hash_file('sha256',$path)))throw new RuntimeException('Checksum backup tidak sesuai.');return $path;
    }
    public function deleteJob(string $id): void {
        $j=$this->job($id);if(!in_array($j['state'],['preview','failed_safe','completed','rolled_back','restored'],true))throw new RuntimeException('Pekerjaan aktif tidak dapat dihapus.');
        if(is_file($this->root.'/maintenance.lock'))throw new RuntimeException('Selesaikan maintenance dahulu.');
        $this->removeTree($this->dir($id));
    }
    private function removeTree(string $dir): void {
        if(!is_dir($dir))return;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $f){if($f->isDir()&&!$f->isLink())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($dir);
    }
}
