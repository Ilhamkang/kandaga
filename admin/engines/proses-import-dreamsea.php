<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../includes/csv.php';
kandagaAuthorize(true);
if (in_array($_GET['action'] ?? '', ['upload', 'confirm'], true) || $_SERVER['REQUEST_METHOD'] === 'POST') kandagaPost(true);

require_once __DIR__ . '/../../config/bootstrap.php';
// 1. Inisialisasi & Keamanan
require_once dirname(__DIR__, 2) . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<div class='alert alert-danger'>Akses ditolak.</div>");
}

// 2. Helper Functions
function get_or_create_id($conn, $table, $id_col, $name_col, $value) {
    $val = trim($value);
    if (empty($val)) return null;

    // Check existing
    $stmt = $conn->prepare("SELECT $id_col FROM $table WHERE $name_col = ?");
    $stmt->bind_param("s", $val);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) return $res[$id_col];

    // Insert new
    $stmt = $conn->prepare("INSERT INTO $table ($name_col) VALUES (?)");
    $stmt->bind_param("s", $val);
    $stmt->execute();
    return $conn->insert_id;
}

// --- ROUTING ACTION ---
$action = $_GET['action'] ?? '';

// --- A. PROSES UPLOAD & PREVIEW ---
if ($action == 'upload') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $data = kandagaCsvRows($file);
        
        // Simpan data di session
        $_SESSION['import_data'] = $data;
        
        echo '<h6>Pratinjau Data (5 Baris Pertama)</h6>
              <div class="table-responsive"><table class="table table-bordered table-sm small">
              <thead><tr>';
        foreach ($data[0] as $header) echo '<th>' . htmlspecialchars($header) . '</th>';
        echo '</tr></thead><tbody>';
        foreach (array_slice($data, 1, 5) as $row) {
            echo '<tr>'; foreach ($row as $cell) echo '<td>' . htmlspecialchars($cell) . '</td>'; echo '</tr>';
        }
        echo '</tbody></table></div>
              <div class="mt-3">
                <button class="btn btn-success" onclick="confirmImport()">Konfirmasi & Import Semua</button>
                <button class="btn btn-secondary" onclick="location.reload()">Batal</button>
              </div>';
    } else {
        echo '<div class="alert alert-danger">Gagal mengunggah file.</div>';
    }
}

// --- B. PROSES IMPORT KE DATABASE ---
elseif ($action == 'confirm') {
    if (!isset($_SESSION['import_data'])) die("<div class='alert alert-danger'>Sesi habis, silakan upload ulang.</div>");

    $data = $_SESSION['import_data'];
    $headers = array_shift($data); // Ambil header
    
    // Set time limit untuk proses panjang
    set_time_limit(300); 

    $conn->begin_transaction();
    try {
        $count = 0;
        foreach ($data as $row_raw) {
            // Map array ke asosiatif berdasarkan header
            if (count($headers) !== count($row_raw)) throw new InvalidArgumentException('Jumlah kolom CSV tidak sesuai header.');
            $row = array_combine($headers, $row_raw);
            if (empty($row['Judul'])) continue;

            // 1. Resolve IDs (Lookup/Create)
            $bahasa_ids = array_filter(array_map(fn($v) => get_or_create_id($conn, 'tb_bahasa', 'id_bahasa', 'nama_bahasa', $v), explode(',', $row['Bahasa_List'] ?? '')));
            $aksara_ids = array_filter(array_map(fn($v) => get_or_create_id($conn, 'tb_aksara', 'id_aksara', 'nama_aksara', $v), explode(',', $row['Aksara_List'] ?? '')));
            $alas_ids   = array_filter(array_map(fn($v) => get_or_create_id($conn, 'tb_alas_tulis', 'id_alas_tulis', 'nama_alas_tulis', $v), explode(',', $row['Alas_Tulis_List'] ?? '')));
            $id_lokasi  = get_or_create_id($conn, 'tb_lokasi', 'id_lokasi', 'nama_tempat', $row['Lokasi_Target'] ?? '');

            // 2. Insert Katalog
            $stmt = $conn->prepare("INSERT INTO katalog (tanggal_input, judul, id_lokasi, kode_inventaris, deskripsi_fisik, deskripsi_isi, riwayat_naskah, penyedia_akses, url_repositori, iiif_manifest, url_gambar_eksternal, gambar, url_web, url_pdf) VALUES (NOW(), ?, ?, ?, ?, ?, ?, 'DREAMSEA', ?, ?, ?, '', '', '')");
            $stmt->bind_param("sisssssss", 
                $row['Judul'], $id_lokasi, $row['Kode_Inventaris'], 
                $row['Deskripsi_Fisik_Clean'], $row['Deskripsi_Isi'], $row['Riwayat_Naskah'],
                $row['URL_Repositori'], $row['IIIF_Manifest'], $row['url_gambar_eksternal']
            );
            $stmt->execute();
            $id_naskah = $conn->insert_id;

            // 3. Insert Pivot Tables
            $stmt_b = $conn->prepare("INSERT INTO naskah_bahasa (id_naskah, id_bahasa) VALUES (?, ?)");
            foreach($bahasa_ids as $bid) { $stmt_b->bind_param("ii", $id_naskah, $bid); $stmt_b->execute(); }
            
            $stmt_a = $conn->prepare("INSERT INTO naskah_aksara (id_naskah, id_aksara) VALUES (?, ?)");
            foreach($aksara_ids as $aid) { $stmt_a->bind_param("ii", $id_naskah, $aid); $stmt_a->execute(); }
            
            $stmt_l = $conn->prepare("INSERT INTO naskah_alas_tulis (id_naskah, id_alas_tulis) VALUES (?, ?)");
            foreach($alas_ids as $alid) { $stmt_l->bind_param("ii", $id_naskah, $alid); $stmt_l->execute(); }

            $count++;
        }
        
        $conn->commit();
        echo "<div class='alert alert-success'>Berhasil mengimpor <strong>$count</strong> naskah.</div>";
        unset($_SESSION['import_data']);
        
    } catch (Throwable $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>