<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../includes/upload.php';
kandagaPost(true);

try {
    if (!isset($_FILES['file'])) throw new InvalidArgumentException('Berkas gambar tidak ditemukan.');
    $raw = trim((string)ini_get('upload_max_filesize'));
    $number = (float)$raw;
    $unit = strtolower(substr($raw, -1));
    $serverLimit = (int)($unit === 'g' ? $number * 1073741824 : ($unit === 'm' ? $number * 1048576 : ($unit === 'k' ? $number * 1024 : $number)));
    $limit = min(5242880, $serverLimit ?: 5242880);
    // TinyMCE upload endpoint accepts only real images; names are generated
    // by kandagaUpload(), never taken from the browser.
    $name = kandagaUpload('file', ROOT_PATH . '/assets/images/editor', 'editor', $limit);
    if (!$name) throw new InvalidArgumentException('Pilih berkas gambar terlebih dahulu.');
    kandagaJson(true, 'Gambar berhasil diunggah ke Pustaka Media.', [
        'location' => BASE_URL . '/assets/images/editor/' . rawurlencode($name),
        'filename' => $name,
    ]);
} catch (Throwable $error) {
    kandagaFailure($error);
}
