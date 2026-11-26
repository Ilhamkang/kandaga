<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$kode = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6);
$_SESSION['captcha_code'] = $kode;

header('Cache-Control: no-store, no-cache, must-revalidate');
if (!function_exists('imagecreate')) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="130" height="45" viewBox="0 0 130 45"><rect width="130" height="45" fill="#f0f0f0"/><path d="M0 12 L130 30 M0 35 L130 9" stroke="#c8c8c8"/><text x="18" y="29" font-family="monospace" font-weight="bold" font-size="22" fill="#323232">' . htmlspecialchars($kode, ENT_XML1, 'UTF-8') . '</text></svg>';
    exit;
}
$image = imagecreate(130, 45);
$bg = imagecolorallocate($image, 240, 240, 240);
$txt_color = imagecolorallocate($image, 50, 50, 50);
$line_color = imagecolorallocate($image, 200, 200, 200);

for($i=0; $i<6; $i++) {
    imageline($image, 0, rand(0,45), 130, rand(0,45), $line_color);
}
imagestring($image, 5, 40, 15, $kode, $txt_color);
header("Content-type: image/png");
imagepng($image);
imagedestroy($image);
?>