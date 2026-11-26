<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(false);
// 1. Validasi Keberadaan Koneksi (Pencegahan jika diakses langsung)
if (!isset($conn)) {
    die("<div class='alert alert-danger'>Akses ditolak: Koneksi database tidak ditemukan.</div>");
}

// 2. Mengambil ID dari session yang sudah pasti ada
$user_id = $_SESSION['admin_id'] ?? null;

if (!$user_id) {
    // Jika user tidak terdeteksi, kita arahkan balik ke login
    header("Location: ../login.php");
    exit();
}

// 3. Mengambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 4. Logika Gambar (Lebih Rapi)
$base_path = "../assets/images/user/";
$default_img = "default-user.jpg";
$nama_foto = (!empty($data['foto'])) ? $data['foto'] : $default_img;
$path_foto = $base_path . $nama_foto;

// Cek apakah file benar-benar ada di server
if (!file_exists($path_foto)) {
    $path_foto = $base_path . $default_img;
}
?>

<div class="container-fluid">
    <h3 class="mb-4">Pengaturan Profil Pengguna</h3>

    <div id="notifikasi"></div>

    <form id="formProfil" enctype="multipart/form-data">
        <input type="hidden" name="user_id" value="<?= $data['id']; ?>">
        
        <div class="row">
            <div class="col-md-4">
                <div class="card p-4 text-center">
                    <img src="<?= $path_foto; ?>?v=<?= time(); ?>" class="rounded-circle" style="width:150px; height:150px; object-fit:cover; margin:0 auto 15px;" id="avatarPreview">                    <h5 class="font-weight-bold"><?= htmlspecialchars($data['nama']); ?></h5>
                    <span class="badge badge-info mb-3"><?= htmlspecialchars($data['role']); ?></span>
                    
                    <a href="../detail-kontributor?id=<?= urlencode($data['id']); ?>" target="_blank" class="btn btn-outline-primary btn-sm mb-3">
                        <i class="fa fa-eye"></i> Lihat Profil Publik
                    </a>

                    <div class="form-group text-left">
                        <label>Ganti Foto</label>
                        <input type="file" class="form-control-file" name="foto_user" id="inputFoto" accept="image/*">
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card p-4">
                    <h5 class="font-weight-bold text-primary mb-3">Informasi Akun</h5>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Username (ID Akses)</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['username']); ?>" readonly>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Afiliasi</label>
                            <input type="text" class="form-control" name="afiliasi" value="<?= htmlspecialchars($data['afiliasi'] ?? ''); ?>" placeholder="Masukkan instansi/afiliasi Anda">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi Kepakaran</label>
                        <textarea class="form-control" name="deskripsi" rows="3"><?= htmlspecialchars($data['deskripsi'] ?? ''); ?></textarea>
                    </div>

                    <h5 class="font-weight-bold text-warning mt-3 mb-3">Keamanan</h5>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Password Baru</label>
                            <input type="password" class="form-control" name="password_baru" placeholder="Minimal 6 karakter">
                        </div>
                    </div>

                    <button type="button" id="btnSimpan" class="btn btn-primary mt-2">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // 1. Fitur Preview Foto
    $('#inputFoto').change(function(){
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { $('#avatarPreview').attr('src', e.target.result); }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // 2. Kirim Data via AJAX
    $('#btnSimpan').click(function(e) {
        e.preventDefault();
        var formData = new FormData($('#formProfil')[0]);

        $.ajax({
            url: 'actions/proses-profil.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('#btnSimpan').prop('disabled', true).text('Menyimpan...');
            },
            success: function(response) {
                var res = typeof response === 'object' ? response : JSON.parse(response);
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                }
            },
            error: function(xhr) {
                $('#btnSimpan').prop('disabled', false).text('Simpan Perubahan');
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan sistem.' });
            }
        });
    });
});
</script>
