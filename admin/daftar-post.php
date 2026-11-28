<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(true);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
if (session_status() == PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) session_start(); }
include '../config/database.php';

// 1. Ambil data postingan
$query = "SELECT tb_kabar.*, users.nama as penulis
          FROM tb_kabar
          LEFT JOIN users ON tb_kabar.penulis_id = users.id
          ORDER BY tb_kabar.id_kabar DESC";

$result = mysqli_query($conn, $query) or die("Query Gagal: " . mysqli_error($conn));

// 2. Query untuk data kategori
$q_kat = "SELECT DISTINCT kategori FROM tb_kabar WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC";
$res_kat = mysqli_query($conn, $q_kat) or die("Query Kategori Gagal: " . mysqli_error($conn));
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?><div class="container-fluid admin-module-surface mt-4"><?php endif; ?>
    <div class="admin-page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><h1><i class="fa-solid fa-newspaper me-2" aria-hidden="true"></i>Daftar Postingan</h1><p class="mb-0">Kelola berita, kegiatan, dan pengumuman yang tampil di katalog.</p></div>
        <div class="admin-page-header__actions"><button class="btn btn-primary shadow-sm" onclick="tambahPost()"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Tulisan</button></div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" id="inputSearch" class="form-control" placeholder="Cari judul...">
                </div>
                <div class="col-md-3">
                    <select id="selectKategori" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php while($kat = mysqli_fetch_assoc($res_kat)): ?>
                            <option value="<?= htmlspecialchars($kat['kategori']) ?>"><?= ucfirst($kat['kategori']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelPost">
                <thead class="table-light">
                    <tr>
                        <th width="80">Gambar</th>
                        <th style="cursor: pointer;" onclick="sortTable(1)">Judul</th>
                        <th>Kategori</th>
                        <th>Penulis</th>
                        <th style="cursor: pointer;" onclick="sortTable(4)">Status</th>
                        <th class="text-end" width="200">Aksi</th>
                    </tr>
                </thead>
                <tbody id="bodyTabel">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) :
                        $status_indo = ($row['status'] == 'published') ? 'Diterbitkan' : 'Draf';
                        $thumb_path = (!empty($row['thumbnail'])) ? '../assets/images/post/' . htmlspecialchars($row['thumbnail']) : '../assets/images/msc-placeholder.png';
                ?>
                        <tr data-kategori="<?= htmlspecialchars($row['kategori'] ?? '') ?>">
                            <td>
                                <img src="<?= $thumb_path ?>" style="width: 60px; height: 60px; object-fit: cover;" class="rounded shadow-sm" alt="Thumbnail">
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($row['judul']); ?></div>
                                <small class="text-muted"><?= date('d M Y', strtotime($row['waktu_buat'])); ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['kategori'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['penulis'] ?? 'Admin'); ?></td>
                            <td><?= $status_indo; ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-sm btn-light text-warning kandaga-action-btn" data-bs-toggle="tooltip" title="Edit" aria-label="Edit postingan" onclick="editPost(<?= $row['id_kabar']; ?>)"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                                    <button type="button" class="btn btn-sm btn-light text-info kandaga-action-btn" data-bs-toggle="tooltip" title="Lihat" aria-label="Lihat postingan" onclick="window.open('../kabar/<?= htmlspecialchars($row['slug']); ?>', '_blank')"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                                    <button type="button" class="btn btn-sm btn-light text-danger kandaga-action-btn btn-hapus" data-bs-toggle="tooltip" title="Hapus" aria-label="Hapus postingan" data-id="<?= $row['id_kabar']; ?>" data-judul="<?= htmlspecialchars($row['judul']); ?>"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                </div>
                            </td>
                        </tr>
                <?php
                    endwhile;
                } else {
                    echo "<tr><td colspan='6' class='empty-row'>Tidak ada data postingan ditemukan.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?></div><?php endif; ?>

<script>
// Fungsi Navigasi, Sort, Filter, dan Hapus (Sama seperti sebelumnya)
function tambahPost() { $('#adminModule').load('form-kabar.php'); }
function editPost(id) { $('#adminModule').load('form-edit-kabar.php?id=' + id); }

function sortTable(n) {
    let table = document.getElementById("tabelPost");
    let rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    switching = true; dir = "asc";
    while (switching) {
        switching = false; rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];
            if (dir == "asc") { if (x.innerText.toLowerCase() > y.innerText.toLowerCase()) { shouldSwitch = true; break; } }
            else if (dir == "desc") { if (x.innerText.toLowerCase() < y.innerText.toLowerCase()) { shouldSwitch = true; break; } }
        }
        if (shouldSwitch) { rows[i].parentNode.insertBefore(rows[i + 1], rows[i]); switching = true; switchcount++; }
        else { if (switchcount == 0 && dir == "asc") { dir = "desc"; switching = true; } }
    }
}

jQuery(document).ready(function($) {
    function filterTable() {
        let searchTerm = $('#inputSearch').val().toLowerCase();
        let catTerm = $('#selectKategori').val().toLowerCase();
        $("#bodyTabel tr").each(function() {
            let rowText = $(this).text().toLowerCase();
            let rowCat = $(this).data('kategori').toLowerCase();
            let matchSearch = rowText.indexOf(searchTerm) > -1;
            let matchCat = (catTerm === "" || rowCat === catTerm);
            $(this).toggle(matchSearch && matchCat);
        });
    }
    $('#inputSearch').on('keyup', filterTable);
    $('#selectKategori').on('change', filterTable);

    $(document).off('click', '.btn-hapus').on('click', '.btn-hapus', function(e) {
        let id = $(this).data('id');
        let judul = $(this).data('judul');
        Swal.fire({
            title: 'Hapus Postingan?',
            text: "Anda akan menghapus: " + judul,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'actions/proses-hapus-kabar.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            Swal.fire('Terhapus!', res.message, 'success').then(() => {
                                $('#adminModule').load('daftar-post.php');
                            });
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
