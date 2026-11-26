<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * proxy.php - Proxy untuk mengambil file PDF eksternal
 * Terintegrasi dengan Master Config
 */

// 1. Panggil config dari root
// Pastikan path '../config.php' sudah sesuai dengan lokasi file proxy.php Anda
require_once __DIR__ . '/../config.php';

$url = isset($_GET['url']) ? filter_var($_GET['url'], FILTER_VALIDATE_URL) : '';

if (empty($url)) {
    die("URL tidak ditemukan.");
}

// 2. Validasi Domain (Whitelist)
// Sangat penting untuk keamanan agar server tidak digunakan sebagai proxy umum
if (strtolower(parse_url($url, PHP_URL_SCHEME) ?? '') !== 'https' || strtolower(parse_url($url, PHP_URL_HOST) ?? '') !== 'repositori.kemdikbud.go.id') {
    die("Akses ditolak: Domain tidak diizinkan.");
}

// 3. Menambahkan User-Agent agar tidak diblokir oleh server tujuan
// Ambil hanya PDF dengan batas waktu dan ukuran agar endpoint tidak menjadi
// proxy bandwidth/DoS. Redirect dan protokol non-HTTPS tidak diizinkan.
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>30, CURLOPT_MAXFILESIZE=>52428800, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_HTTPHEADER=>['Accept: application/pdf','User-Agent: Kandaga-PDF-Proxy/3.1.34']]);
$file_content = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE); curl_close($ch);
if ($file_content === false || $code < 200 || $code >= 300 || strlen($file_content) > 52428800 || ($type !== '' && stripos($type, 'application/pdf') === false) || strncmp($file_content, '%PDF-', 5) !== 0) die("Gagal mengambil PDF dari server eksternal.");
header('Content-Type: application/pdf');
header('Content-Length: ' . strlen($file_content));
header('X-Content-Type-Options: nosniff');
echo $file_content;
?>
