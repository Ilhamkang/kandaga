<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(false);
// Ambil ID dari parameter URL
$id_lokasi = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Query ambil data lama
$query = mysqli_query($conn, "SELECT * FROM tb_lokasi WHERE id_lokasi = '$id_lokasi'");
$data  = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<div class='alert alert-danger'>Data tidak ditemukan!</div>";
    exit;
}
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?><div class="container-fluid admin-module-surface py-4"><?php endif; ?>
    <div class="admin-page-header">
        <h1>Edit Lokasi Koleksi</h1>
        <p>Perbarui data institusi atau perpustakaan penyimpan naskah.</p>
    </div>

    <form id="formEditLokasi" enctype="multipart/form-data">
        <input type="hidden" name="id_lokasi" value="<?= $data['id_lokasi'] ?>">

        <div class="form-card">
            <div class="section-title"><i class="fa-solid fa-map-location-dot"></i> Detail Lokasi</div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Nama Tempat / Institusi</label>
                        <input type="text" class="form-control" name="nama_tempat"
                               value="<?= htmlspecialchars($data['nama_tempat']) ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Negara</label>
                        <input type="text" class="form-control" name="negara" list="listNegara"
                               value="<?= htmlspecialchars($data['negara'] ?? '') ?>" required>
                        <datalist id="listNegara">
                            <?php
                            $queryNegara = mysqli_query($conn, "SELECT DISTINCT negara FROM tb_lokasi ORDER BY negara ASC");
                            while($row = mysqli_fetch_assoc($queryNegara)) {
                                echo "<option value='".htmlspecialchars($row['negara'])."'>";
                            }
                            ?>
                        </datalist>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Foto Lokasi</label><br>
                        <?php
                        $path_foto = !empty($data['foto_lokasi']) ? "../assets/images/" . $data['foto_lokasi'] : "../assets/images/lokasi-placeholder.png";
                        ?>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $path_foto ?>" class="preview-img me-3" alt="Foto Lokasi">
                            <div class="flex-grow-1">
                                <input type="file" class="form-control" name="gambar" id="gambar" accept="image/*">
                                <small class="text-muted">Kosongkan jika tidak ingin mengganti foto.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan alamat lengkap lokasi..."><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Deskripsi Singkat</label>
                        <textarea class="form-control" name="deskripsi" rows="4"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky-submit mb-5">
            <button type="button" class="btn btn-light px-4" onclick="loadPage('tampil-lokasi.php')">Batal</button>
            <button type="submit" id="btnUpdateLokasi" class="btn btn-primary px-5 shadow">Update Lokasi</button>
        </div>
    </form>
<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?></div><?php endif; ?>

<script>
    $(document).off('submit', '#formEditLokasi').on('submit', '#formEditLokasi', function(e) {
        e.preventDefault();

        let btn = $('#btnUpdateLokasi');
        let formData = new FormData(this);

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Memproses...');

        $.ajax({
            url: 'actions/proses-edit_lokasi.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (typeof response !== 'string' || response.trim() !== 'success') { Swal.fire('Gagal', (response && response.message) || 'Penyimpanan gagal.', 'error'); return; }
                let res = response.trim();
                if (res === "success") {
                    alert("✅ Data lokasi berhasil diperbarui!");

                    // OPSI 1: Jika menggunakan sistem loading AJAX (SPA)
                    // loadPage('tampil-lokasi.php');

                    // OPSI 2 (Lebih Aman): Redirect penuh via URL index
                    window.location.href = 'index.php?page=tampil-lokasi';
                } else {
                    alert("❌ " + res);
                    btn.prop('disabled', false).text('Update Lokasi');
                }
            },
            error: function() {
                alert("❌ Terjadi kesalahan koneksi ke server.");
                btn.prop('disabled', false).text('Update Lokasi');
            }
        });
    });
</script>
