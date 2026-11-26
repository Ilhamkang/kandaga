<?php
require_once __DIR__ . '/analytics-context.php';
function kandagaGeoResponse($raw, $ip) {
    $data=json_decode($raw,true);
    if (!is_array($data) || ($data['success']??false)!==true || !filter_var($data['ip']??'',FILTER_VALIDATE_IP) || inet_pton($data['ip'])!==inet_pton($ip)) return null;
    return ['code'=>preg_match('/^[A-Z]{2}$/D',$data['country_code']??'')?$data['country_code']:null,'country'=>mb_substr((string)($data['country']??''),0,100,'UTF-8'),'city'=>mb_substr((string)($data['city']??''),0,100,'UTF-8'),'region'=>mb_substr((string)($data['region']??''),0,100,'UTF-8')];
}
function kandagaResolveGeoBatch($conn,$limit=5) {
    $settings=kandagaAnalyticsSettings();
    if ($settings['geo_provider']!=='ipwhois') return ['processed'=>0,'resolved'=>0,'message'=>'Pemetaan lokasi dinonaktifkan.'];
    $limit=max(1,min(10,(int)$limit));$lock='kandaga_geo_'.md5($conn->query('SELECT DATABASE() AS name')->fetch_assoc()['name']);
    $stmt=$conn->prepare('SELECT GET_LOCK(?,0) AS locked');$stmt->bind_param('s',$lock);$stmt->execute();$locked=(int)$stmt->get_result()->fetch_assoc()['locked'];$stmt->close();
    if (!$locked) return ['processed'=>0,'resolved'=>0,'message'=>'Pemetaan lokasi sedang berjalan.'];
    $processed=0;$resolved=0;
    try {
        // Conservative local daily cap, plus cache and per-IP retry delay.
        $used=(int)$conn->query('SELECT COUNT(*) AS n FROM website_geo_cache WHERE checked_at>=CURDATE()')->fetch_assoc()['n'];
        if ($used>=900) return ['processed'=>0,'resolved'=>0,'message'=>'Batas pemetaan lokasi hari ini tercapai.'];
        $limit=min($limit,900-$used);
        $result=$conn->query("SELECT ip_address FROM website_geo_cache WHERE status<>'local' AND (retry_after IS NULL OR retry_after<=NOW()) ORDER BY checked_at IS NOT NULL, checked_at, ip_address LIMIT $limit");
        while ($row=$result->fetch_assoc()) {
            $ip=$row['ip_address'];if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_GLOBAL_RANGE))continue;
            $ch=curl_init('https://ipwho.is/'.rawurlencode($ip).'?fields=ip,success,country,country_code,city,region');
            curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT_MS=>700,CURLOPT_TIMEOUT_MS=>1800,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_USERAGENT=>'Kandaga-Analytics/3.1.3']);
            $raw=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);$processed++;
            $geo=$http===200&&is_string($raw)&&strlen($raw)<=65536?kandagaGeoResponse($raw,$ip):null;
            if ($geo && $geo['country']!=='') {
                $stmt=$conn->prepare("UPDATE website_geo_cache SET country_code=?,country=?,city=?,region=?,status='resolved',checked_at=NOW(),retry_after=DATE_ADD(NOW(),INTERVAL 30 DAY) WHERE ip_address=?");
                $stmt->bind_param('sssss',$geo['code'],$geo['country'],$geo['city'],$geo['region'],$ip);$stmt->execute();$stmt->close();$resolved++;
            } else {
                $stmt=$conn->prepare("UPDATE website_geo_cache SET status='failed',checked_at=NOW(),retry_after=DATE_ADD(NOW(),INTERVAL 1 DAY) WHERE ip_address=?");$stmt->bind_param('s',$ip);$stmt->execute();$stmt->close();
            }
            if ($http===429) break;
        }
    } finally {$stmt=$conn->prepare('SELECT RELEASE_LOCK(?)');$stmt->bind_param('s',$lock);$stmt->execute();$stmt->close();}
    return ['processed'=>$processed,'resolved'=>$resolved,'message'=>"$resolved dari $processed IP berhasil dipetakan. Muat ulang statistik untuk melihat hasil."];
}
