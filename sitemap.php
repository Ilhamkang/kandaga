<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=UTF-8');
$base = rtrim((string)BASE_URL, '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$origin = $scheme . '://' . $host;
$url = static fn(string $path): string => htmlspecialchars($origin . '/' . ltrim($base . '/' . ltrim($path, '/'), '/'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach (['', 'kabar', 'monograf', 'katalog', 'indeks-judul', 'indeks-lokasi'] as $path) {
    echo '<url><loc>' . $url($path) . '</loc><changefreq>weekly</changefreq></url>';
}
$result = $conn->query('SELECT id FROM katalog ORDER BY id ASC');
if ($result) while ($item = $result->fetch_assoc()) {
    echo '<url><loc>' . $url('detail.php?id=' . (int)$item['id']) . '</loc><changefreq>monthly</changefreq></url>';
}
echo '</urlset>';
