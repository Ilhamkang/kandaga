<?php
// All upload paths are absolute and generated names contain no user input.
function kandagaValidateExternalUrl($url, $label = 'URL') {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException($label . ' tidak valid.');
    $parts = parse_url($url); $scheme = strtolower((string)($parts['scheme'] ?? '')); $host = (string)($parts['host'] ?? '');
    if (!in_array($scheme, ['http','https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) throw new InvalidArgumentException($label . ' harus menggunakan http atau https tanpa kredensial URL.');
    if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) throw new InvalidArgumentException($label . ' tidak boleh mengarah ke alamat jaringan internal.');
    return $url;
}

function kandagaUpload($field, $directory, $prefix = 'gambar', $maxBytes = 5242880) {
    if (!isset($_FILES[$field])) return null;
    $file = $_FILES[$field];
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) throw new InvalidArgumentException('Format upload tidak valid.');
    $code = (int)$file['error'];
    if ($code === UPLOAD_ERR_NO_FILE) return null;
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'Ukuran gambar melebihi batas upload_max_filesize server.',
        UPLOAD_ERR_FORM_SIZE => 'Ukuran gambar melebihi batas formulir.',
        UPLOAD_ERR_PARTIAL => 'Upload gambar tidak lengkap. Silakan ulangi.',
        UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara upload tidak tersedia di server.',
        UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file upload.',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP.'
    ];
    if ($code !== UPLOAD_ERR_OK) throw new InvalidArgumentException($messages[$code] ?? 'Upload gambar gagal.');
    if ((int)$file['size'] < 1 || (int)$file['size'] > $maxBytes) throw new InvalidArgumentException('Ukuran gambar maksimal ' . ($maxBytes / 1048576) . ' MB.');
    if (!is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('File upload tidak ditemukan.');
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $image = @getimagesize($file['tmp_name']);
    // MIME dan struktur gambar menjadi sumber kebenaran; ekstensi nama file
    // dari perangkat pengguna boleh tidak konsisten (mis. .jfif/.JPG).
    // Nama tersimpan tetap memakai ekstensi aman dari MIME yang terdeteksi.
    $width = (int)($image[0] ?? 0); $height = (int)($image[1] ?? 0);
    if (!isset($allowed[$mime]) || !$image || $width < 1 || $height < 1 || $width > 8000 || $height > 8000 || ($width * $height) > 25000000) {
        throw new InvalidArgumentException('Gunakan gambar JPG, PNG, GIF, atau WEBP yang valid.');
    }
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new InvalidArgumentException('Folder gambar tidak dapat dibuat. Periksa izin server.');
    if (!is_writable($directory)) throw new InvalidArgumentException('Folder gambar tidak dapat ditulis. Periksa izin server.');
    $name = $prefix . '_' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target = $directory . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new InvalidArgumentException('Gagal memindahkan gambar ke folder penyimpanan.');
    // Uploaded assets are public media, never executable application code.
    // The assets/.htaccess rule provides the server-side boundary; this
    // permission makes the intended state explicit on hosts that honor it.
    @chmod($target, 0644);
    clearstatcache(true, $target);
    if (!is_file($target) || is_executable($target)) {
        @unlink($target);
        throw new InvalidArgumentException('File upload tidak dapat ditetapkan sebagai media non-eksekutabel.');
    }
    return $name;
}

function kandagaRemoveUpload($directory, $name) {
    if ($name && basename($name) === $name && is_file($directory . '/' . $name)) @unlink($directory . '/' . $name);
}
