<?php
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/../../config/update.php';
function kandagaUpdateRecoveryAccess(string $job): bool {
    if(!preg_match('/^[a-f0-9]{32}$/D',$job))return false;
    $token=$_SESSION['kandaga_recovery'][$job]??'';
    if(!is_string($token)||$token==='')return false;
    $path=kandagaUpdateStorage().'/'.$job.'/job.json';$j=json_decode((string)@file_get_contents($path),true);
    return is_array($j)&&in_array($j['state']??'', ['rollback_code','restore_db','needs_recovery'],true)&&hash_equals($j['recovery_hash']??'',hash('sha256',$token));
}
function kandagaUpdateGrant(string $job): void {
    $dir=kandagaUpdateStorage().'/'.$job;
    $j=KandagaUpdater::json($dir.'/job.json');
    $token=bin2hex(random_bytes(32));$_SESSION['kandaga_recovery'][$job]=$token;$j['recovery_hash']=hash('sha256',$token);
    KandagaUpdater::writeJson($dir.'/job.json',$j);
}
