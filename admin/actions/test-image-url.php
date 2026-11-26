<?php
require_once __DIR__ . '/../includes/request.php';
kandagaPost(false);

try {
    $raw = $_POST['url'] ?? '';
    $url = kandagaNormalizeImageUrl($raw);
    if ($url === '') throw new InvalidArgumentException('URL gambar tidak valid atau tidak memakai HTTP/HTTPS.');
    $parts = parse_url($url);
    $host = strtolower((string)($parts['host'] ?? ''));
    if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        throw new InvalidArgumentException('URL mengarah ke alamat jaringan internal.');
    }
    $size = 0;
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_RANGE => '0-65535',
        CURLOPT_USERAGENT => 'Kandaga image verifier/3.1.36',
        CURLOPT_WRITEFUNCTION => static function ($handle, $chunk) use (&$size) {
            $size += strlen($chunk);
            return $size <= 65536 ? strlen($chunk) : 0;
        },
    ]);
    $ok = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $type = strtolower((string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
    curl_close($curl);
    if ($ok === false && $size === 0) throw new RuntimeException('URL tidak dapat diakses' . ($error ? ': ' . $error : '.'));
    if ($status < 200 || $status >= 300) throw new RuntimeException('Server gambar merespons HTTP ' . $status . ($type ? ' (' . $type . ')' : '.') . ' Periksa hotlink protection atau URL IIIF.');
    if ($type !== '' && !str_starts_with($type, 'image/')) throw new RuntimeException('Respons bukan berkas gambar (' . $type . ').');
    kandagaJson(true, 'URL gambar dapat diakses.', ['url' => $url, 'http_code' => $status, 'content_type' => $type ?: 'tidak dilaporkan']);
} catch (Throwable $error) {
    error_log('Kandaga image URL test: ' . $error->getMessage());
    kandagaJson(false, $error instanceof InvalidArgumentException ? $error->getMessage() : 'URL gambar tidak dapat diakses atau bukan respons gambar yang valid.', [], $error instanceof InvalidArgumentException ? 422 : 502);
}
