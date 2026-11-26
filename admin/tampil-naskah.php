<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(false);
if (session_status() === PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) session_start(); }
require_once '../config/database.php'; 

// 1. Parameter Filter
$search = is_string($_GET['search']??null) ? trim($_GET['search']) : '';
$filter_lokasi = isset($_GET['filter_lokasi']) ? (int)$_GET['filter_lokasi'] : 0;
$limit = 20; 
$page_no = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_no < 1) $page_no = 1;
$offset = ($page_no - 1) * $limit;

// Ambil data lembaga
$lokasi_options = $conn->query("SELECT id_lokasi, nama_tempat FROM tb_lokasi ORDER BY nama_tempat ASC");

// 2. Query Count (Pagination)
$where_clauses = [];
if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where_clauses[] = "(k.judul LIKE '%$s%' OR l.nama_tempat LIKE '%$s%' OR k.kode_inventaris LIKE '%$s%')";
}
if ($filter_lokasi > 0) {
    $where_clauses[] = "k.id_lokasi = $filter_lokasi";
}
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$count_sql = "SELECT COUNT(DISTINCT k.id) as total FROM katalog k LEFT JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi $where_sql";
$total_result = $conn->query($count_sql)->fetch_assoc();
$total_rows = $total_result['total'];
$total_pages = ceil($total_rows / $limit);

// 3. Query Utama
$sql = "SELECT k.*,l.nama_tempat,l.negara,
 (SELECT GROUP_CONCAT(DISTINCT a.nama_aksara SEPARATOR '||') FROM naskah_aksara na JOIN tb_aksara a ON a.id_aksara=na.id_aksara WHERE na.id_naskah=k.id) AS daftar_aksara,
 (SELECT GROUP_CONCAT(DISTINCT b.nama_bahasa SEPARATOR '||') FROM naskah_bahasa nb JOIN tb_bahasa b ON b.id_bahasa=nb.id_bahasa WHERE nb.id_naskah=k.id) AS daftar_bahasa
 FROM katalog k LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi $where_sql ORDER BY k.id DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
?>

<div class="row mb-4 align-items-center">
    <div class="col"><h3 class="page-title mb-0">Manajemen Data Naskah</h3></div>
    <div class="col-auto"><a href="index.php?page=form-input" class="btn btn-primary px-4 shadow-sm font-weight-bold"><i class="fa-solid fa-plus-circle mr-2"></i> Tambah Naskah</a></div>
</div>

<div class="table-card">
    <div class="p-3 border-bottom bg-light">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="tampil-naskah">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Cari judul, kode, lokasi..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select name="filter_lokasi" class="form-control">
                    <option value="0">-- Semua Lembaga --</option>
                    <?php while($l = $lokasi_options->fetch_assoc()): ?>
                        <option value="<?= $l['id_lokasi'] ?>" <?= $filter_lokasi == $l['id_lokasi'] ? 'selected' : '' ?>><?= $l['nama_tempat'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter mr-2"></i> Filter</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover w-100 mb-0">
            <thead>
                <tr>
                    <th width="5%">Foto</th>
                    <th width="20%">Judul</th>
                    <th width="15%">Lembaga</th>
                    <th width="10%">Negara</th>
                    <th width="10%">Kode Inv</th>
                    <th width="10%">Aksara</th>
                    <th width="10%">Bahasa</th>
                    <th width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): 
                    while ($row = $result->fetch_assoc()):
                        $foto = kandagaImageUrl($row);
                        
                        $url_detail = BASE_URL . '/detail.php?id=' . (int)$row['id'];
                ?>
                <tr>
                    <td><a href="<?= $url_detail ?>" target="_blank"><img src="<?= htmlspecialchars($foto,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8') ?>" class="img-thumb-admin" style="width: 40px; height: 40px; object-fit: cover; border-radius:4px;"<?= kandagaImageFallbackAttribute() ?>></a></td>
                    <td class="font-weight-bold"><a href="<?= $url_detail ?>" target="_blank" class="text-decoration-none text-dark"><?= htmlspecialchars($row['judul']) ?></a></td>
                    <td><?= htmlspecialchars($row['nama_tempat'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['negara'] ?? '-') ?></td>
                    <td><code><?= htmlspecialchars($row['kode_inventaris']) ?></code></td>
                    <td><?php foreach(explode('||', $row['daftar_aksara'] ?? '') as $a) if($a) echo "<span class='badge badge-light border mr-1'>".htmlspecialchars($a,ENT_QUOTES,'UTF-8')."</span>"; ?></td>
                    <td><?php foreach(explode('||', $row['daftar_bahasa'] ?? '') as $b) if($b) echo "<span class='badge badge-light border mr-1'>".htmlspecialchars($b,ENT_QUOTES,'UTF-8')."</span>"; ?></td>
                    <td>
                        <div class='btn-group btn-action-group'>
                            <a href='<?= $url_detail ?>' target='_blank' class='btn btn-sm' title='Lihat'><i class='fa-solid fa-eye text-primary'></i></a>
                            <a href='index.php?page=form-edit&id=<?= $row['id'] ?>' class='btn btn-sm' title='Edit'><i class='fa-solid fa-pen text-warning'></i></a>
                            <button type='button' class='btn btn-sm btn-hapus' data-id='<?= $row['id'] ?>' title='Hapus'><i class='fa-solid fa-trash text-danger'></i></button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="8" class="text-center py-4">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page_no <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=tampil-naskah&p=<?= $page_no - 1 ?>&search=<?= urlencode($search) ?>&filter_lokasi=<?= $filter_lokasi ?>">&laquo;</a>
                </li>
                
                <?php 
                $start = max(1, $page_no - 2);
                $end = min($total_pages, $page_no + 2);
                if($start > 1) echo "<li class='page-item disabled'><a class='page-link'>...</a></li>";
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= ($i == $page_no) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=tampil-naskah&p=<?= $i ?>&search=<?= urlencode($search) ?>&filter_lokasi=<?= $filter_lokasi ?>"><?= $i ?></a>
                    </li>
                <?php endfor; 
                if($end < $total_pages) echo "<li class='page-item disabled'><a class='page-link'>...</a></li>";
                ?>

                <li class="page-item <?= ($page_no >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=tampil-naskah&p=<?= $page_no + 1 ?>&search=<?= urlencode($search) ?>&filter_lokasi=<?= $filter_lokasi ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    $(document).on('click', '.btn-hapus', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'actions/proses-hapus.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Berhasil!', response.message, 'success');
                            $('#kontenUtama').load('tampil-naskah.php'); 
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
