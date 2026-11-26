<?php
require_once __DIR__ . '/../config/analytics.php';
function kandagaAnalyticsSchemaReady($conn) {
    static $ready;
    if ($ready !== null) return $ready;
    try {
        $row = $conn->query("SELECT COUNT(*) AS n FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name IN ('page_key','page_type','page_title','entity_id','ip_address','user_agent','device_type','browser_name','os_name','search_term','visitor_token','is_bot','traffic_class','bot_name','referrer','request_hash')")->fetch_assoc();
        return $ready = (int)$row['n'] === 16;
    } catch (Throwable $error) { return $ready = false; }
}
function kandagaClientIp(array $server) {
    $peer = $server['REMOTE_ADDR'] ?? '';
    if (!filter_var($peer, FILTER_VALIDATE_IP)) return null;
    if (in_array($peer, kandagaAnalyticsSettings()['trusted_proxy_ips'], true)) {
        $forwarded = $server['HTTP_CF_CONNECTING_IP'] ?? explode(',', $server['HTTP_X_FORWARDED_FOR'] ?? '')[0];
        $forwarded = trim($forwarded);
        if (filter_var($forwarded, FILTER_VALIDATE_IP)) return inet_ntop(inet_pton($forwarded));
    }
    return inet_ntop(inet_pton($peer));
}
function kandagaDeviceInfo($ua) {
    $device = $ua === '' ? 'Tidak diketahui' : 'Desktop';
    if (preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua)) $device = 'Tablet';
    elseif (preg_match('/Mobi|iPhone|iPod|Windows Phone/i', $ua)) $device = 'Mobile';
    elseif (!preg_match('/Windows|Macintosh|Linux|CrOS/i', $ua)) $device = 'Tidak diketahui';
    $browser = 'Tidak diketahui';
    foreach (['Edge'=>'/Edg(?:e|A|iOS)?\//i','Opera'=>'/OPR\//i','Firefox'=>'/Firefox|FxiOS/i','Chrome'=>'/Chrome|CriOS/i','Safari'=>'/Safari/i'] as $name=>$pattern) if (preg_match($pattern,$ua)) {$browser=$name;break;}
    $os = 'Tidak diketahui';
    foreach (['Android'=>'/Android/i','iOS'=>'/iPhone|iPad|iPod/i','Windows'=>'/Windows/i','Chrome OS'=>'/CrOS/i','macOS'=>'/Macintosh|Mac OS X/i','Linux'=>'/Linux/i'] as $name=>$pattern) if (preg_match($pattern,$ua)) {$os=$name;break;}
    return ['device'=>$device,'browser'=>$browser,'os'=>$os];
}
function kandagaNormalizeSearch($term) {
    if (!is_string($term)) return null;
    $term = preg_replace('/[\x00-\x1f\x7f]/u', ' ', $term) ?? '';
    $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');
    if ($term === '') return null;
    return mb_substr(mb_strtolower($term, 'UTF-8'), 0, 255, 'UTF-8');
}
function kandagaSetPageContext($type, $key, $title, $entity = null, $search = null) {
    $GLOBALS['kandagaPageContext'] = ['type'=>$type,'key'=>$key,'title'=>mb_substr((string)$title,0,255,'UTF-8'),'entity'=>$entity,'search'=>$search];
}
function kandagaPageContext() {
    if (isset($GLOBALS['kandagaPageContext'])) return $GLOBALS['kandagaPageContext'];
    $page = basename(str_replace('\\','/',$_SERVER['SCRIPT_NAME'] ?? '/index.php'),'.php');
    $names=['index'=>'Beranda','search'=>'Pencarian naskah','statistik'=>'Statistik','tentang'=>'Tentang KANDAGA','kontributor'=>'Kontributor','daftar-post'=>'Kabar','kontak'=>'Kontak','bibliografi'=>'Bibliografi','indeks-judul'=>'Indeks judul','indeks-lokasi'=>'Indeks lokasi','koleksi'=>'Koleksi','bookmark'=>'Bookmark','disclaimer'=>'Disclaimer'];
    return ['type'=>$page==='search'?'search':'page','key'=>$page==='index'?'/':'/'.$page,'title'=>$names[$page]??ucwords(str_replace('-',' ',$page)),'entity'=>null,'search'=>$page==='search'?kandagaNormalizeSearch($_GET['search']??null):null];
}
function kandagaQueueGeo($conn, $ip) {
    // Geolocation is intentionally application-disabled. Server access logs
    // remain the diagnostic source for operators who need request details.
}
