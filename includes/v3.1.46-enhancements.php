<?php
/** v3.1.46: public UI, social preview, and human-presence corrections. */
if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { return; }
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (stripos($accept, 'text/html') === false) { return; }
$path = str_replace('\\', '/', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
if (strpos($path, '/admin/actions/') !== false || strpos($path, '/actions/') !== false) { return; }

ob_start(static function (string $html): string {
    $base = defined('BASE_URL') ? BASE_URL : '';
    $assetCss = '<link rel="stylesheet" href="' . htmlspecialchars($base . '/assets/css/v3.1.46-enhancements.css?v=3.1.53', ENT_QUOTES, 'UTF-8') . '">';
    $assetJs = '<script defer src="' . htmlspecialchars($base . '/assets/js/v3.1.46-enhancements.js?v=3.1.53', ENT_QUOTES, 'UTF-8') . '"></script>';
    $assetCss .= '<style id="kandaga-3-1-53-ui">'
        . '.kandaga-bookmark-button{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;min-width:7.8rem;min-height:2.25rem;padding:.375rem .75rem;background:#fff!important;border:1px solid #2563eb!important;color:#1d4ed8!important;opacity:1!important}'
        . '.kandaga-bookmark-button:hover,.kandaga-bookmark-button:focus{background:#2563eb!important;border-color:#1d4ed8!important;color:#fff!important;opacity:1!important}'
        . '.kandaga-bookmark-button[aria-pressed="true"]{background:#eff6ff!important;color:#1d4ed8!important}'
        . '.search-list-actions,.search-card-actions{display:flex;align-items:center;gap:.5rem}'
        . '.search-card-actions .kandaga-bookmark-button{position:relative;width:2.75rem;min-width:2.75rem;height:2.75rem;min-height:2.75rem;padding:0!important;border:0!important;border-radius:0!important;background:transparent!important;color:#172033!important;box-shadow:none;gap:0;filter:drop-shadow(0 1px 1px rgba(255,255,255,.95))}'
        . '.search-card-actions .kandaga-bookmark-button:hover,.search-card-actions .kandaga-bookmark-button:focus{background:transparent!important;color:#2563eb!important;border:0!important;transform:translateY(-1px);outline:2px solid rgba(37,99,235,.2);outline-offset:2px}'
        . '.search-card-actions .kandaga-bookmark-button i{margin:0!important;font-size:1.65rem;line-height:1;position:relative;z-index:1}'
        . '.search-card-actions .kandaga-bookmark-button span{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}'
        . '.search-card-actions .kandaga-bookmark-button::after{content:"+";position:absolute;left:50%;top:50%;z-index:2;display:flex;align-items:center;justify-content:center;width:.82rem;height:.82rem;transform:translate(-50%,-50%);color:currentColor;background:transparent;font:bold 1rem/1 Arial,sans-serif;text-shadow:0 0 2px #fff}'
        . '.search-card-actions .kandaga-bookmark-button[aria-pressed="true"]{background:transparent!important;color:#2563eb!important}'
        . '.search-card-actions .kandaga-bookmark-button[aria-pressed="true"]::after{content:"+";color:#2563eb}'
        . '.search-list-actions .kandaga-bookmark-button{min-width:7.8rem}'
        . '</style>';
    if (stripos($html, '</head>') !== false && stripos($html, 'v3.1.46-enhancements.css') === false) {
        $html = preg_replace('~</head>~i', $assetCss . "\n</head>", $html, 1);
    }
    if (stripos($html, '</body>') !== false && stripos($html, 'v3.1.46-enhancements.js') === false) {
        $html = preg_replace('~</body>~i', $assetJs . "\n</body>", $html, 1);
    }

    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (preg_match('~(?:^|/)detail(?:\.php)?/?$~', $requestPath) && stripos($html, 'property="og:image"') === false) {
        $row = $GLOBALS['row'] ?? [];
        if (is_array($row) && !empty($row)) {
            $title = trim((string)($row['judul'] ?? 'Detail Naskah')) . ' — KANDAGA';
            $description = trim(preg_replace('/\s+/', ' ', strip_tags((string)($row['deskripsi_isi'] ?? ''))));
            $description = $description !== '' ? (function_exists('mb_substr') ? mb_substr($description, 0, 220) : substr($description, 0, 220)) : 'Detail katalog manuskrip dalam KANDAGA.';
            $image = (string)($GLOBALS['display_image_url'] ?? '');
            if ($image === '') $image = $base . '/assets/images/favicon.png';
            if (!preg_match('~^https?://~i', $image)) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $image = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/' . ltrim($image, '/');
            }
            $url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
            $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            $meta = "<meta property=\"og:title\" content=\"{$e($title)}\"><meta property=\"og:description\" content=\"{$e($description)}\"><meta property=\"og:url\" content=\"{$e($url)}\"><meta property=\"og:type\" content=\"article\"><meta property=\"og:image\" content=\"{$e($image)}\"><meta property=\"og:image:width\" content=\"1200\"><meta property=\"og:image:height\" content=\"630\"><meta name=\"twitter:card\" content=\"summary_large_image\"><meta name=\"twitter:image\" content=\"{$e($image)}\">";
            $html = preg_replace('~</head>~i', $meta . "\n</head>", $html, 1);
        }
    }
    return $html;
});
