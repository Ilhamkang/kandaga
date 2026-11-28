<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Pastikan sesi aktif
require_once __DIR__ . '/includes/auth.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom p-3 admin-page-header">
        <h1><i class="fa-solid fa-user-plus me-2"></i>Tambah Pengguna Baru</h1>
    </div>
    <div class="card-body p-4">
        <form id="formTambahUser">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="small fw-bold text-uppercase">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="small fw-bold text-uppercase">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Username unik" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="small fw-bold text-uppercase">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="passInput" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>

                                <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
                                    <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                </button>

                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="small fw-bold text-uppercase">Tingkatan (Role)</label>
                        <select name="role" class="form-select" required>
                            <option value="kontributor">Kontributor Naskah</option>
                            <option value="admin">Administrator Utama</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3 mt-3">
                <button type="submit" id="btnUser" class="btn btn-primary px-4">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Fungsi Tampilkan/Sembunyikan Password
function togglePass() {
    var x = document.getElementById("passInput");
    var icon = document.getElementById("toggleIcon");
    if (x.type === "password") {
        x.type = "text";
        icon.className = "fa-solid fa-eye-slash";
    } else {
        x.type = "password";
        icon.className = "fa-solid fa-eye";
    }
}

$(document).ready(function() {
    $("#formTambahUser").on("submit", function(e) {
        e.preventDefault();
        const btn = $("#btnUser");

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Memproses...');

        $.ajax({
            url: 'actions/proses-tambah-user.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json', // Memastikan respon diterima sebagai JSON
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => {
                        window.location.href = 'index.php?page=daftar-pengguna';
                    });
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
                btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Pengguna');
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Pengguna');
            }
        });
    });
});
</script>
