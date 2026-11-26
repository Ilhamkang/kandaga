<?php
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { http_response_code(405); header('Allow: GET'); echo '{"error":"Metode tidak diizinkan."}'; exit; }
$raw = $_GET['ids'] ?? '';
if (!is_string($raw) || strlen($raw) > 1200 || !preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/D', $raw)) { http_response_code(400); echo '{"error":"Daftar bookmark tidak valid."}'; exit; }
$ids = array_values(array_unique(explode(',', $raw)));
if (count($ids) > 100 || array_filter($ids, fn($id) => strlen($id) > 10 || (int)$id > 2147483647)) { http_response_code(400); echo '{"error":"Maksimal 100 ID per permintaan."}'; exit; }
$ids = array_map('intval', $ids);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image-resolver.php';
try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT k.id, k.judul, k.kode_inventaris, k.gambar, k.url_gambar_eksternal, l.nama_tempat FROM katalog k LEFT JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi WHERE k.id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids); $stmt->execute(); $result = $stmt->get_result(); $items = [];
    while ($row = $result->fetch_assoc()) {
        $image = kandagaImageUrl($row);
        $items[] = ['id' => (int)$row['id'], 'title' => $row['judul'], 'inventory' => $row['kode_inventaris'], 'location' => $row['nama_tempat'] ?? 'Lokasi tidak diketahui', 'image' => $image];
    }
    $stmt->close(); echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) { http_response_code(500); echo '{"error":"Data bookmark belum dapat dimuat. Coba lagi."}'; }
