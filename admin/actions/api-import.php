<?php
require_once __DIR__ . '/../../config/bootstrap.php';
// admin/actions/api-import.php

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

// Secret wajib disediakan oleh environment/server config, tidak disimpan di
// repository atau paket rilis. Rotasi key lama setelah v3.1.34 dipasang.
$apiKey = (string)(getenv('KANDAGA_IMPORT_API_KEY') ?: '');
$providedKey = is_string($_POST['api_key'] ?? null) ? $_POST['api_key'] : '';
if (strlen($providedKey) > 256 || $apiKey === '' || $providedKey === '' || !hash_equals($apiKey, $providedKey)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Basic abuse control without a schema migration. The counter is short-lived
// and protected with flock so concurrent requests cannot bypass the limit.
$peer = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$bucket = sys_get_temp_dir() . '/kandaga-api-' . hash('sha256', $peer . '|' . gmdate('YmdHi')) . '.lock';
$lock = fopen($bucket, 'c+');
if (!$lock || !flock($lock, LOCK_EX)) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Layanan API sementara tidak tersedia']);
    exit;
}
$rawCount = stream_get_contents($lock);
$count = is_string($rawCount) && $rawCount !== '' ? (int)$rawCount : 0;
if ($count >= 60) {
    flock($lock, LOCK_UN); fclose($lock);
    http_response_code(429);
    header('Retry-After: 60');
    echo json_encode(['status' => 'error', 'message' => 'Terlalu banyak permintaan']);
    exit;
}
ftruncate($lock, 0); rewind($lock); fwrite($lock, (string)($count + 1)); fflush($lock);
flock($lock, LOCK_UN); fclose($lock);

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 1048576) {
    http_response_code(413);
    echo json_encode(['status' => 'error', 'message' => 'Payload terlalu besar']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Ambil Data (Menambahkan url_gambar_eksternal)
    $penginput_awal        = 'System_API';
    $judul                 = $_POST['judul'] ?? '';
    $id_lokasi             = (int)($_POST['id_lokasi'] ?? 0);
    $kode_inventaris       = $_POST['kode_inventaris'] ?? '';
    $deskripsi_fisik       = $_POST['deskripsi_fisik'] ?? '';
    $deskripsi_isi         = $_POST['deskripsi_isi'] ?? '';
    $kata_kunci            = $_POST['kata_kunci'] ?? '';
    $riwayat_naskah        = $_POST['riwayat_naskah'] ?? '';
    $penyedia_akses        = $_POST['penyedia_akses'] ?? '';
    $url_web               = $_POST['url_web'] ?? '';
    $url_repositori        = $_POST['url_repositori'] ?? '';
    $iiif_manifest         = $_POST['iiif_manifest'] ?? '';
    $url_pdf               = $_POST['url_pdf'] ?? '';
    $gambar                = ''; // API ini mengasumsikan input gambar lokal dikosongkan/di-handle terpisah
    $url_gambar_eksternal  = $_POST['url_gambar_eksternal'] ?? ''; 

    // 2. Query INSERT
    $sql = "INSERT INTO katalog (
                tanggal_input, penginput_awal, judul, id_lokasi, kode_inventaris, 
                deskripsi_fisik, deskripsi_isi, kata_kunci, riwayat_naskah, penyedia_akses,
                url_web, url_repositori, iiif_manifest, url_pdf, gambar, url_gambar_eksternal
            ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // 3. Bind 14 parameter (tambah satu 's' di format, dan tambah variabel baru)
    $stmt->bind_param("ssissssssssssss", 
        $penginput_awal, 
        $judul, 
        $id_lokasi, 
        $kode_inventaris, 
        $deskripsi_fisik, 
        $deskripsi_isi, 
        $kata_kunci, 
        $riwayat_naskah, 
        $penyedia_akses,
        $url_web, 
        $url_repositori, 
        $iiif_manifest, 
        $url_pdf, 
        $gambar,
        $url_gambar_eksternal
    );
    
    if (!$stmt->execute()) throw new Exception("Gagal insert katalog: " . $stmt->error);
    
    $id_naskah = $conn->insert_id;

    // 4. Fungsi Pivot (Relasi)
    function insert_pivot($conn, $table, $id_naskah, $data_array, $column_name) {
        if (empty($data_array) || !is_array($data_array)) return;
        $stmt_rel = $conn->prepare("INSERT INTO $table (id_naskah, $column_name) VALUES (?, ?)");
        foreach ($data_array as $id) {
            if (!empty($id)) {
                $id_int = (int)$id;
                $stmt_rel->bind_param("ii", $id_naskah, $id_int);
                $stmt_rel->execute();
            }
        }
    }

    // Insert data relasi
    insert_pivot($conn, 'naskah_bahasa', $id_naskah, $_POST['bahasa'] ?? [], 'id_bahasa');
    insert_pivot($conn, 'naskah_aksara', $id_naskah, $_POST['aksara'] ?? [], 'id_aksara');
    insert_pivot($conn, 'naskah_alas_tulis', $id_naskah, $_POST['alas_tulis'] ?? [], 'id_alas_tulis');

    $conn->commit();
    echo json_encode(['status' => 'success', 'id' => $id_naskah]);

} catch (Throwable $e) {
    $conn->rollback();
    error_log('Kandaga API import gagal: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Import gagal. Periksa log server.']);
}
?>
