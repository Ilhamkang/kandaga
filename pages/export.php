<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Pastikan error reporting aktif untuk pengembangan
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Menggunakan path absolut yang aman dari error 'No such file or directory'
require_once __DIR__ . '/../config/database.php';

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: search.php");
    exit;
}

$id = intval($_GET['id']);
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// 1. Query Utama - Mengambil data katalog dan lokasinya
$query = "SELECT k.*, l.nama_tempat, l.foto_lokasi, l.negara, l.alamat
          FROM katalog k 
          LEFT JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi 
          WHERE k.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("<div class='container mt-5 alert alert-danger shadow-sm'>Data naskah tidak ditemukan.</div>");
}

// 2. Mengambil Data Relasi Banyak-ke-Banyak (Aksara, Bahasa, Alas Tulis)
function getNamaRelasi($conn, $tableMaster, $tableJunction, $colId, $colNama, $id_naskah) {
    $sql = "SELECT DISTINCT m.{$colNama} 
            FROM {$tableMaster} m
            JOIN {$tableJunction} j ON m.{$colId} = j.{$colId}
            WHERE j.id_naskah = ?
            ORDER BY m.{$colNama}";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_naskah);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) {
        $items[] = $r[$colNama];
    }
    return kandagaFormatConjunction($items);
}

$string_aksara     = getNamaRelasi($conn, 'tb_aksara', 'naskah_aksara', 'id_aksara', 'nama_aksara', $id);
$string_bahasa     = getNamaRelasi($conn, 'tb_bahasa', 'naskah_bahasa', 'id_bahasa', 'nama_bahasa', $id);
$string_alas_tulis = getNamaRelasi($conn, 'tb_alas_tulis', 'naskah_alas_tulis', 'id_alas_tulis', 'nama_alas_tulis', $id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($row['judul'] ?? 'Detail Naskah') ?> - KANDAGA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <style>
        body { background-color: #f8fafc; color: #334155; }
        .detail-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 30px; }
        .table-detail th { width: 25%; color: #64748b; font-weight: 600; border-top: none; }
        .table-detail td { border-top: none; }
        .table-detail tr { border-bottom: 1px solid #f1f5f9; }
        .export-box { background-color: #f1f5f9; border-radius: 8px; padding: 15px 20px; margin-top: 30px; }
        .btn-export-link { font-weight: 500; color: #1e40af; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-export-link:hover { color: #1d4ed8; text-decoration: underline; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-5">
    
    <div class="mb-4">
        <a href="search.php" class="text-secondary text-decoration-none small font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Katalog
        </a>
    </div>

    <div class="detail-card shadow-sm">
        <h3 class="font-weight-bold mb-4 text-dark"><?= htmlspecialchars($row['judul'] ?? '-') ?></h3>
        
        <div class="table-responsive">
            <table class="table table-detail">
                <tbody>
                    <tr>
                        <th>Tempat Penyimpan</th>
                        <td><?= htmlspecialchars($row['nama_tempat'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Kode Inventaris</th>
                        <td><code class="text-dark font-weight-bold"><?= htmlspecialchars($row['kode_inventaris'] ?? '-') ?></code></td>
                    </tr>
                    <tr>
                        <th>Aksara</th>
                        <td><?= htmlspecialchars($string_aksara) ?></td>
                    </tr>
                    <tr>
                        <th>Bahasa</th>
                        <td><?= htmlspecialchars($string_bahasa) ?></td>
                    </tr>
                    <tr>
                        <th>Alas Tulis</th>
                        <td><?= htmlspecialchars($string_alas_tulis) ?></td>
                    </tr>
                    <tr>
                        <th>Riwayat Naskah</th>
                        <td><?= !empty($row['riwayat_naskah']) ? nl2br(htmlspecialchars($row['riwayat_naskah'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Deskripsi Fisik</th>
                        <td><?= !empty($row['deskripsi_fisik']) ? htmlspecialchars_decode($row['deskripsi_fisik']) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Deskripsi Isi</th>
                        <td><?= !empty($row['deskripsi_isi']) ? htmlspecialchars_decode($row['deskripsi_isi']) : '-' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="export-box d-flex flex-wrap align-items-center justify-content-start">
            <div class="mr-4 mb-2 mb-md-0">
                <span class="text-muted small font-weight-bold text-uppercase tracking-wider">
                Ekspor Metadata Berkas:
                </span>
            </div>
            <div class="d-flex flex-wrap align-items-center" style="gap: 20px;">
                <a class="btn-export-link" href="<?= BASE_URL ?>/modules/export.php?id=<?= $row['id']; ?>&format=csv">CSV</a>|
                <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/modules/export.php?id=<?= $row['id']; ?>&format=mods">MODS</a>|
                <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/modules/export.php?id=<?= $row['id']; ?>&format=pica+">PICA+</a>|
                <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/modules/export.php?id=<?= $row['id']; ?>&format=marc21">MARC21</a>|
                <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/modules/export.php?id=<?= $row['id']; ?>&format=tei">TEI</a>|
                <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/modules/export.php?id=<?= $row['id']; ?>&format=hsp-kod">HSP-KOD</a>|
                <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/modules/export.php?id=<?= $row['id']; ?>&format=hsp-desc">HSP-DESC</a>|
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.5.1.slim.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
