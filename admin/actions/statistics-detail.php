<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/request.php';
kandagaAuthorize(true);
require_once __DIR__ . '/../../includes/catalog-analytics.php';

header('Cache-Control: private, max-age=60');

$type = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : '';
$allowed = ['naskah', 'lembaga', 'bahasa', 'aksara', 'alas_tulis', 'popular_pages', 'searches'];
if (!in_array($type, $allowed, true)) {
    kandagaJson(false, 'Jenis rincian tidak tersedia.', [], 422);
}
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = max(5, min(50, $limit));
$cacheKey = 'admin-statistics-detail-v2-' . $type . '-' . $limit;
$cached = kandagaAnalyticsCacheRead($cacheKey, 60);
if ($cached !== null) {
    kandagaJson(true, 'Rincian statistik tersedia.', $cached + ['cached' => true]);
}

$columns = [];
$rows = [];
$sql = null;
$limitSql = (string)$limit;

try {
    switch ($type) {
        case 'naskah':
            $columns = ['judul' => 'Judul naskah', 'koleksi' => 'Koleksi'];
            $sql = "SELECT COALESCE(k.judul,'Tanpa judul') AS judul,
                           COALESCE(l.nama_tempat,'Tidak diketahui') AS koleksi
                    FROM katalog k
                    LEFT JOIN tb_lokasi l ON l.id_lokasi = k.id_lokasi
                    ORDER BY k.id DESC LIMIT {$limitSql}";
            break;
        case 'lembaga':
            $columns = ['nama' => 'Koleksi/lembaga', 'jumlah' => 'Jumlah naskah'];
            $sql = "SELECT COALESCE(l.nama_tempat,'Tidak diketahui') AS nama, COUNT(k.id) AS jumlah
                    FROM tb_lokasi l LEFT JOIN katalog k ON k.id_lokasi = l.id_lokasi
                    GROUP BY l.id_lokasi,l.nama_tempat ORDER BY jumlah DESC,nama LIMIT {$limitSql}";
            break;
        case 'bahasa':
            $columns = ['nama' => 'Bahasa', 'jumlah' => 'Jumlah naskah'];
            $sql = "SELECT COALESCE(m.nama_bahasa,'Tidak diketahui') AS nama, COUNT(DISTINCT j.id_naskah) AS jumlah
                    FROM tb_bahasa m JOIN naskah_bahasa j ON m.id_bahasa=j.id_bahasa
                    JOIN katalog k ON k.id=j.id_naskah
                    GROUP BY m.id_bahasa,m.nama_bahasa ORDER BY jumlah DESC,nama LIMIT {$limitSql}";
            break;
        case 'aksara':
            $columns = ['nama' => 'Aksara', 'jumlah' => 'Jumlah naskah'];
            $sql = "SELECT COALESCE(m.nama_aksara,'Tidak diketahui') AS nama, COUNT(DISTINCT j.id_naskah) AS jumlah
                    FROM tb_aksara m JOIN naskah_aksara j ON m.id_aksara=j.id_aksara
                    JOIN katalog k ON k.id=j.id_naskah
                    GROUP BY m.id_aksara,m.nama_aksara ORDER BY jumlah DESC,nama LIMIT {$limitSql}";
            break;
        case 'alas_tulis':
            $columns = ['nama' => 'Alas tulis', 'jumlah' => 'Jumlah naskah'];
            $sql = "SELECT COALESCE(m.nama_alas_tulis,'Tidak diketahui') AS nama, COUNT(DISTINCT j.id_naskah) AS jumlah
                    FROM tb_alas_tulis m JOIN naskah_alas_tulis j ON m.id_alas_tulis=j.id_alas_tulis
                    JOIN katalog k ON k.id=j.id_naskah
                    GROUP BY m.id_alas_tulis,m.nama_alas_tulis ORDER BY jumlah DESC,nama LIMIT {$limitSql}";
            break;
        case 'popular_pages':
            $columns = ['title' => 'Halaman', 'path' => 'URL', 'views' => 'Tampilan'];
            $rows = kandagaCompactPopularPages($conn, $limit, 90);
            break;
        case 'searches':
            $columns = ['term' => 'Kata kunci/frasa', 'searches' => 'Pencarian'];
            $rows = kandagaCompactTopSearches($conn, $limit, 90);
            break;
    }
    if ($type === 'searches') {
        foreach ($rows as &$row) {
            $row = ['term' => $row['search_term'] ?? '', 'searches' => (int)($row['total'] ?? 0)];
        }
        unset($row);
    } elseif ($type === 'popular_pages') {
        foreach ($rows as &$row) {
            $row = ['title' => $row['title'] ?? '', 'path' => $row['path'] ?? '', 'views' => (int)($row['views'] ?? 0)];
        }
        unset($row);
    } elseif ($sql !== null) {
        $result = $conn->query($sql);
        if ($result) $rows = $result->fetch_all(MYSQLI_ASSOC);
    }
    $payload = ['type' => $type, 'columns' => $columns, 'rows' => $rows, 'cached' => false];
    kandagaAnalyticsCacheWrite($cacheKey, $payload);
    kandagaJson(true, 'Rincian statistik tersedia.', $payload);
} catch (Throwable $error) {
    error_log('Kandaga statistics detail: ' . $error->getMessage());
    kandagaJson(false, 'Rincian statistik belum dapat dimuat.', [], 503);
}
