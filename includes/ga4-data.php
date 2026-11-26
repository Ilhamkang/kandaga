<?php
/**
 * Small, dependency-free Google Analytics Data API client.
 * It is external telemetry only and is not mixed with Kandaga's internal
 * catalog aggregates or server-side request log.
 */
require_once __DIR__ . '/../config/analytics.php';

function kandagaGa4Config(): array {
    $s = kandagaAnalyticsSettings();
    $json = trim((string)($s['google_service_account_json'] ?? ''));
    $file = trim((string)($s['google_service_account_file'] ?? ''));
    if ($json === '' && $file !== '' && is_file($file) && is_readable($file)) $json = (string)@file_get_contents($file);
    $account = $json !== '' ? json_decode($json, true) : null;
    return [
        'property_id' => (string)($s['google_property_id'] ?? ''),
        'account' => is_array($account) ? $account : null,
    ];
}

function kandagaGa4Available(): bool {
    $c = kandagaGa4Config();
    return (bool)($c['property_id'] && is_array($c['account']) && !empty($c['account']['client_email']) && !empty($c['account']['private_key']) && function_exists('openssl_sign') && function_exists('curl_init'));
}

function kandagaGa4B64(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }

function kandagaGa4Token(array $account): string {
    $now = time();
    $header = kandagaGa4B64(json_encode(['alg'=>'RS256','typ'=>'JWT'], JSON_UNESCAPED_SLASHES));
    $claim = kandagaGa4B64(json_encode(['iss'=>$account['client_email'],'scope'=>'https://www.googleapis.com/auth/analytics.readonly','aud'=>'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3600], JSON_UNESCAPED_SLASHES));
    $signature = '';
    if (!openssl_sign($header.'.'.$claim, $signature, $account['private_key'], OPENSSL_ALGO_SHA256)) throw new RuntimeException('JWT GA4 tidak dapat ditandatangani.');
    $jwt = $header.'.'.$claim.'.'.kandagaGa4B64($signature);
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt]), CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>12, CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]);
    $raw = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $data = json_decode((string)$raw, true);
    if ($code < 200 || $code >= 300 || empty($data['access_token'])) throw new RuntimeException('Token GA4 ditolak (HTTP '.$code.').');
    return $data['access_token'];
}

function kandagaGa4Request(string $dateRange, array $dimensions, array $metrics, int $limit = 100): array {
    $c = kandagaGa4Config();
    if (!$c['property_id'] || !$c['account']) throw new RuntimeException('Property ID atau service account GA4 belum dikonfigurasi.');
    $token = kandagaGa4Token($c['account']);
    $body = ['dateRanges'=>[['startDate'=>$dateRange,'endDate'=>'today']], 'dimensions'=>array_map(fn($n)=>['name'=>$n],$dimensions), 'metrics'=>array_map(fn($n)=>['name'=>$n],$metrics), 'limit'=>$limit];
    $url = 'https://analyticsdata.googleapis.com/v1beta/properties/'.rawurlencode($c['property_id']).':runReport';
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($body), CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20, CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json']]);
    $raw = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $data = json_decode((string)$raw, true);
    if ($code < 200 || $code >= 300 || !is_array($data)) throw new RuntimeException('GA4 Data API gagal (HTTP '.$code.').');
    $rows = [];
    foreach (($data['rows'] ?? []) as $row) {
        $item=[]; foreach (($row['dimensionValues'] ?? []) as $i=>$v) $item[$dimensions[$i] ?? 'dimension'.$i] = $v['value'] ?? '';
        foreach (($row['metricValues'] ?? []) as $i=>$v) $item[$metrics[$i] ?? 'metric'.$i] = (float)($v['value'] ?? 0);
        $rows[]=$item;
    }
    return $rows;
}

function kandagaGa4Dashboard(string $range = '30daysAgo'): array {
    $allowed=['7daysAgo','30daysAgo','90daysAgo','365daysAgo']; if (!in_array($range,$allowed,true)) $range='30daysAgo';
    $cache = sys_get_temp_dir().'/kandaga-ga4-'.sha1(($GLOBALS['kandaga_ga4_cache_key'] ?? '').$range).'.json';
    if (is_file($cache) && @filemtime($cache) > time()-300) { $v=json_decode((string)@file_get_contents($cache),true); if(is_array($v)) return $v; }
    try {
        $daily=kandagaGa4Request($range,['date'],['activeUsers','sessions','screenPageViews'],400);
        $pages=kandagaGa4Request($range,['pageTitle','pagePathPlusQueryString'],['screenPageViews'],20);
        $sources=kandagaGa4Request($range,['sessionSource','sessionMedium'],['sessions'],20);
        $total=['activeUsers'=>0,'sessions'=>0,'screenPageViews'=>0]; foreach($daily as $r) foreach(array_keys($total) as $k) $total[$k]+=(float)($r[$k]??0);
        $result=['ok'=>true,'daily'=>$daily,'pages'=>$pages,'sources'=>$sources,'total'=>$total,'updated_at'=>date('c')];
        @file_put_contents($cache,json_encode($result),LOCK_EX); return $result;
    } catch (Throwable $e) { return ['ok'=>false,'error'=>$e->getMessage(),'daily'=>[],'pages'=>[],'sources'=>[],'total'=>[]]; }
}
