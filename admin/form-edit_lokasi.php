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

<style>
    .page-header { margin-bottom: 1.5rem; }
    .page-title { font-weight: 700; color: #334155; }
    .form-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 25px; border: none; margin-bottom: 2rem; }
    .section-title { font-size: 1.1rem; font-weight: 600; color: #2563eb; margin-bottom: 1.5rem; display: flex; align-items: center; border-bottom: 2px solid #eff6ff; padding-bottom: 10px; }
    .section-title i { margin-right: 10px; }
    .form-group label { font-weight: 600; color: #475569; font-size: 0.9rem; margin-bottom: 8px; }
    .form-control { border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 15px; }
    .sticky-submit { background: #f8fafc; padding: 15px 25px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
    .preview-img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0; margin-bottom: 10px; }
</style>

<div class="container-fluid py-4">
    <div class="page-header">
        <h2 class="page-title">Edit Lokasi Koleksi</h2>
        <p class="text-muted small">Perbarui data institusi atau perpustakaan penyimpan naskah.</p>
    </div>

    <form id="formEditLokasi" enctype="multipart/form-data">
        <input type="hidden" name="id_lokasi" value="<?= $data['id_lokasi'] ?>">

        <div class="form-card">
            <div class="section-title"><i class="fa-solid fa-map-location-dot"></i> Detail Lokasi</div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-group">
                        <label>Nama Tempat / Institusi</label>
                        <input type="text" class="form-control" name="nama_tempat" 
                               value="<?= htmlspecialchars($data['nama_tempat']) ?>" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label>Negara</label>
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
                    <div class="form-group">
                        <label>Foto Lokasi</label><br>
                        <?php 
                        $path_foto = !empty($data['foto_lokasi']) ? "../assets/images/" . $data['foto_lokasi'] : "../assets/images/lokasi-placeholder.png";
                        ?>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $path_foto ?>" class="preview-img mr-3" alt="Foto Lokasi">
                            <div class="flex-grow-1">
                                <input type="file" class="form-control" name="gambar" id="gambar" accept="image/*">
                                <small class="text-muted">Kosongkan jika tidak ingin mengganti foto.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan alamat lengkap lokasi..."><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="form-group">
                        <label>Deskripsi Singkat</label>
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
</div>

<script>
    $(document).off('submit', '#formEditLokasi').on('submit', '#formEditLokasi', function(e) {
        e.preventDefault();
        
        let btn = $('#btnUpdateLokasi');
        let formData = new FormData(this);

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

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
