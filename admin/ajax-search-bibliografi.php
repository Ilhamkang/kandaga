<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
header('Content-Type: application/json; charset=utf-8');

$search   = isset($_GET['q']) ? trim($_GET['q']) : '';
$kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

try {
    // Definisi kolom yang ingin dijadikan target pencarian
    $kolom_cari = [
        "judul", "subjudul", "judul_bab", "nama_jurnal", 
        "penyusun", "editor", "penerbit", "tahun_terbit", "isbn_issn"
    ];

    // Membangun string query OR LIKE secara dinamis
    $where_clauses = [];
    foreach ($kolom_cari as $kolom) {
        $where_clauses[] = "$kolom LIKE ?";
    }
    $where_string = implode(" OR ", $where_clauses);

    // Query akhir: kategori tetap filter utama, sisanya adalah pencarian di multi-field
    $query = "SELECT id AS id, 
              CONCAT(judul, ' (', penyusun, ' - ', tahun_terbit, ')') AS text 
              FROM tb_bibliografi 
              WHERE kategori = ? AND ($where_string) 
              LIMIT 15";
              
    $stmt = $conn->prepare($query);
    
    // Menyiapkan parameter bind (kategori + 9 field pencarian)
    $params = array_fill(0, count($kolom_cari) + 1, "");
    $params[0] = $kategori;
    $like_search = "%" . $search . "%";
    
    // Teknik bind dinamis agar performa tetap ringan
    $types = str_repeat("s", count($kolom_cari) + 1);
    $values = [$kategori];
    for ($i = 0; $i < count($kolom_cari); $i++) {
        $values[] = $like_search;
    }
    
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = ['id' => $row['id'], 'text' => $row['text']];
    }
    
    echo json_encode($data);
    $stmt->close();

} catch (Exception $e) {
    echo json_encode([]);
}
exit;
