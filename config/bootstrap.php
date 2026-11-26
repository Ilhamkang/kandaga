<?php
// Shared URL and session setup; does not open a database connection.
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/version.php';
require_once ROOT_PATH . '/includes/image-resolver.php';

/**
 * Format a human-readable list with an ampersand before the final item.
 * Examples: A; A & B; A, B, & C.
 */
if (!function_exists('kandagaFormatConjunction')) {
    function kandagaFormatConjunction(array $items, string $empty = '-'): string {
        $clean = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item === '' || in_array($item, $clean, true)) continue;
            $clean[] = $item;
        }
        $count = count($clean);
        if ($count === 0) return $empty;
        if ($count === 1) return $clean[0];
        if ($count === 2) return $clean[0] . ' & ' . $clean[1];
        $last = array_pop($clean);
        return implode(', ', $clean) . ', & ' . $last;
    }
}
if (!defined('BASE_URL')) {
    $base = getenv('KANDAGA_BASE_PATH');
    if ($base === false) {
        $doc = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $root = realpath(ROOT_PATH);
        if ($doc && ($root === $doc || strpos($root, $doc . DIRECTORY_SEPARATOR) === 0)) {
            $base = str_replace(DIRECTORY_SEPARATOR, '/', substr($root, strlen($doc)));
        } else {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $base = preg_split('~/(?:admin|actions|pages|modules|includes)/~', $script, 2);
            $base = count($base) > 1 ? $base[0] : dirname($script);
        }
    }
    $base = trim((string)$base, '/');
    $base = $base === '' || $base === '.' ? '' : '/' . $base;
    define('BASE_URL', $base);
}
error_reporting(E_ALL);
// Tampilkan error hanya pada localhost / *.test agar masalah schema mudah
// dikenali saat development; produksi tetap hanya menulis ke log.
$localDebugHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$isLocalDebug = (PHP_SAPI === 'cli')
    || in_array($localDebugHost, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
    || substr($localDebugHost, -5) === '.test';
ini_set('display_errors', $isLocalDebug ? '1' : '0');
ini_set('log_errors', '1');
// Keep session identifiers in cookies only; never let PHP place a session ID
// in a URL. The explicit cookie flags mirror the parameters below and make
// the intended policy visible to hosts that apply php.ini defaults.
ini_set('session.use_trans_sid', '0');
ini_set('session.use_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
// Baseline response hardening. HSTS is emitted only after HTTPS is confirmed
// so local HTTP development remains usable.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// v3.1.64: mulai inventarisasi Content Security Policy tanpa memblokir
// resource apa pun. Mode Report-Only dipilih agar inline script lama,
// viewer, bookmark, SMTP, dan integrasi telemetri tetap kompatibel selama
// sumber aktual diverifikasi di localhost dan produksi.
if (getenv('KANDAGA_DISABLE_CSP_REPORT_ONLY') !== '1') {
    $cspReportOnly = implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "form-action 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://cdnjs.cloudflare.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://stackpath.bootstrapcdn.com https://cdnjs.cloudflare.com",
        "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
        "img-src 'self' data: blob: https:",
        "media-src 'self' https:",
        "connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://region1.google-analytics.com https://api.zotero.org",
        "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com",
        "worker-src 'self' blob: https://cdnjs.cloudflare.com"
    ]);
    header('Content-Security-Policy-Report-Only: ' . $cspReportOnly);
}
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => (BASE_URL ?: '') . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_start();
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// v3.1.10: maintenance covers public and admin endpoints; updater/login remain accessible.
if (is_file(ROOT_PATH . '/maintenance.lock')) {
    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $relative = strpos($script, ROOT_PATH . '/') === 0 ? substr($script, strlen(ROOT_PATH)+1) : '';
    $allowed = in_array($relative, ['admin/actions/update-center.php','admin/update.php','admin/recover-update.php','login.php','actions/proses-login.php','modules/captcha.php','admin/logout.php'], true)
        || ($relative === 'admin/index.php' && ($_GET['page'] ?? '') === 'update');
    if (!$allowed) {
        http_response_code(503); header('Retry-After: 120'); header('Cache-Control: no-store');
        if (strpos($relative, 'actions/') !== false) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>false,'message'=>'Kandaga sedang diperbarui. Silakan coba kembali sebentar lagi.']); }
        else echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pembaruan Kandaga</title><body style="font-family:sans-serif;max-width:600px;margin:15vh auto;padding:24px"><h1>Kandaga sedang diperbarui</h1><p>Silakan kembali beberapa saat lagi. Terima kasih atas kesabaran Anda.</p></body></html>';
        exit;
    }
}
