<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/page-header.php';

// jQuery .load() requests are fragments of the canonical shell, not new pages.
if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')
    && strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
    define('KANDAGA_ADMIN_FRAGMENT', true);
}

function kandagaJson($success, $message, $extra = [], $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => $success ? 'success' : 'error', 'success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

class KandagaAccessDenied extends RuntimeException {}

function kandagaCurrentUser(): array {
    return [
        'id'       => (int)($_SESSION['admin_id'] ?? 0),
        'username' => (string)($_SESSION['username'] ?? ''),
        'role'     => (string)($_SESSION['role'] ?? ''),
    ];
}

function kandagaDeny($message = 'Akses ditolak.') {
    throw new KandagaAccessDenied($message);
}

function kandagaAuthorize($adminOnly = false) {
    global $conn;
    if (($_SESSION['status'] ?? '') !== 'login' || empty($_SESSION['admin_id'])) {
        kandagaJson(false, 'Sesi berakhir. Silakan login kembali.', [], 401);
    }
    $id = (int)$_SESSION['admin_id'];
    $stmt = $conn->prepare('SELECT username, role, status_akun, status_verifikasi FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || $user['status_akun'] !== 'aktif' || $user['status_verifikasi'] !== 'verified') {
        kandagaJson(false, 'Akun tidak memiliki akses aktif.', [], 403);
    }
    $_SESSION['role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    if ($adminOnly && $user['role'] !== 'admin') kandagaJson(false, 'Hanya administrator yang dapat melakukan aksi ini.', [], 403);
}

function kandagaSameOriginRequest() {
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    // Older form submissions and some trusted hosting proxies omit Origin;
    // CSRF remains the primary control in that case. A browser-supplied
    // cross-origin Origin, however, must match the current host.
    if ($origin === '' || strtolower($origin) === 'null') return true;
    $parts = parse_url($origin);
    if (!is_array($parts) || empty($parts['host'])) return false;
    $requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $originHost = strtolower((string)$parts['host']);
    $requestHost = preg_replace('/:\d+$/', '', $requestHost);
    if ($requestHost !== $originHost) return false;
    if (isset($parts['port'])) {
        $requestPort = (int)(parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_PORT) ?: 0);
        if ($requestPort !== (int)$parts['port']) return false;
    }
    return true;
}

function kandagaCsrf() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
        kandagaJson(false, 'Token keamanan tidak valid. Muat ulang halaman.', [], 403);
    }
}

function kandagaPost($adminOnly = false) {
    kandagaAuthorize($adminOnly);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') kandagaJson(false, 'Metode tidak diizinkan.', [], 405);
    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        kandagaJson(false, 'Data upload tidak terbaca. Periksa batas post_max_size pada server.', [], 413);
    }
    if (!kandagaSameOriginRequest()) {
        kandagaJson(false, 'Permintaan lintas situs ditolak.', [], 403);
    }
    kandagaCsrf();
}

function kandagaFailure(Throwable $error) {
    error_log('Kandaga: ' . $error->getMessage());
    if ($error instanceof KandagaAccessDenied) kandagaJson(false, $error->getMessage(), [], 403);
    $message = $error instanceof InvalidArgumentException ? $error->getMessage() : 'Penyimpanan gagal. Periksa log server dan kompatibilitas database.';
    if ($error instanceof mysqli_sql_exception && $error->getCode() === 1366) $message = 'Database belum dapat menyimpan sebagian karakter ini. Charset tabel masih perlu diperbarui pada tahap migrasi SQL.';
    kandagaJson(false, $message, [], $error instanceof InvalidArgumentException ? 422 : 500);
}
