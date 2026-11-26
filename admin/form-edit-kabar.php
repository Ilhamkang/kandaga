<?php
require_once __DIR__ . '/../config/bootstrap.php';
function kandagaEditPostIniBytes($value) { $value=trim((string)$value); if($value==='') return 0; $unit=strtolower(substr($value,-1)); $number=(float)$value; return (int)($unit==='g'?$number*1073741824:($unit==='m'?$number*1048576:($unit==='k'?$number*1024:$number))); }
$thumbnailMaxBytes = min(5242880, kandagaEditPostIniBytes(ini_get('upload_max_filesize')) ?: 5242880);
// The parent panel already authorizes this page; this guard also covers a
// direct request and enforces the admin-only boundary.
require_once __DIR__ . '/includes/auth.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}
require_once '../config/database.php';


// --- DEBUG MODE: AKTIFKAN AGAR ERROR MUNCUL ---
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Pastikan sesi aktif
if (session_status() == PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) session_start(); }

// --- CEK PATH FILE ---
$path_config = '../config/database.php'; // Sesuaikan jika salah
if (!file_exists($path_config)) {
    die("ERROR: File koneksi database tidak ditemukan di path: " . realpath($path_config));
}

require_once $path_config;

// --- CEK KONEKSI DATABASE ---
if (!isset($conn)) {
    die("ERROR: Variabel \$conn tidak tersedia. Periksa isi file database.php Anda.");
}

// Validasi ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ERROR: ID tidak valid atau tidak ditemukan.");
}

$query = mysqli_query($conn, "SELECT * FROM tb_kabar WHERE id_kabar = '$id'");
if (!$query) {
    die("ERROR QUERY: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("ERROR: Data dengan ID $id tidak ditemukan di database.");
}
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Kabar</title>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module">
<?php endif; ?>

<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?><div class="container-fluid"><?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Edit Berita / Kabar</h3>
    </div>

    <form id="formEditKabar" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_kabar" value="<?= $data['id_kabar']; ?>">
        <input type="hidden" name="thumbnail_lama" value="<?= htmlspecialchars($data['thumbnail']); ?>">

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label>Judul Berita</label>
                            <input type="text" name="judul" class="form-control form-control-lg"
                                   value="<?= htmlspecialchars($data['judul']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Slug (URL Friendly)</label>
                            <input type="text" name="slug" class="form-control"
                                   value="<?= htmlspecialchars($data['slug']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Konten</label>
                            <textarea name="konten" id="editorKabar" class="form-control"><?= htmlspecialchars($data['konten']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light"><strong>Gambar Utama</strong></div>
                    <div class="card-body text-center">
                        <?php if (!empty($data['thumbnail'])): ?>
                            <div class="mb-2" id="thumbnailPreview">
                                <img src="../assets/images/post/<?= htmlspecialchars($data['thumbnail']) ?>" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                        <?php endif; ?>
                        <div id="thumbnailPreviewNew" class="mb-2" hidden><img class="img-fluid rounded shadow-sm" style="max-height:180px" alt="Pratinjau thumbnail baru"></div>
                        <input type="file" name="thumbnail" id="thumbnailInputEdit" class="form-control mt-2" accept="image/jpeg,image/png,image/webp,image/gif" data-max-bytes="<?= (int)$thumbnailMaxBytes ?>">
                        <small class="text-muted d-block mt-1">Biarkan kosong jika tidak diganti. Maksimal <?= number_format($thumbnailMaxBytes / 1048576, 1) ?> MB sesuai batas server.</small>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light"><strong>Status & Kategori</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label>Kategori</label>
                            <select name="kategori" class="form-select">
                                <?php foreach(['berita', 'kegiatan', 'pengumuman', 'artikel'] as $kat): ?>
                                    <option value="<?= $kat ?>" <?= $data['kategori'] == $kat ? 'selected' : '' ?>><?= ucfirst($kat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= $data['status'] == 'published' ? 'selected' : '' ?>>Diterbitkan</option>
                                <option value="draft" <?= $data['status'] == 'draft' ? 'selected' : '' ?>>Draf</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" onclick="$('#kontenUtama').load('daftar-post.php')">Batal</button>
                </div>
            </div>
        </div>
    </form>
<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?></div><?php endif; ?>

<script>
    // Memastikan TinyMCE di-load kembali saat file ini di-load via AJAX
    if (typeof tinymce !== 'undefined') {
        tinymce.remove('#editorKabar');
        tinymce.init({
        content_css: '<?= BASE_URL ?>/assets/css/editor-fonts.css?v=3.1.2',
            selector: '#editorKabar',
            license_key: 'gpl',
            height: 300
        });
    }

    $('#thumbnailInputEdit').off('change').on('change', function(){
        const file=this.files&&this.files[0], box=$('#thumbnailPreviewNew'), image=box.find('img');
        if(!file){box.prop('hidden',true);return;}
        const maxBytes=Number(this.dataset.maxBytes||5242880);
        if(file.size>maxBytes){this.value='';box.prop('hidden',true);Swal.fire('Gambar terlalu besar','Ukuran maksimal thumbnail pada server adalah '+(maxBytes/1048576).toFixed(1)+' MB.','warning');return;}
        const reader=new FileReader();reader.onload=e=>{image.attr('src',e.target.result);box.prop('hidden',false)};reader.readAsDataURL(file);
    });

    $('#formEditKabar').off('submit').on('submit', function(e) {
        e.preventDefault();
        if (typeof tinymce !== 'undefined') tinymce.triggerSave();

        var formData = new FormData(this);

        $.ajax({
            url: 'actions/proses-edit-kabar.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if (!res || res.status !== 'success') { Swal.fire('Gagal', (res && res.message) || 'Penyimpanan gagal.', 'error'); return; }
                Swal.fire('Berhasil!', res.message || 'Data telah diperbarui.', 'success').then(() => {
                    window.location.href = 'index.php?page=daftar-post';
                });
            },
            error: function(xhr) {
                let message='Gagal memproses. Periksa batas upload dan izin folder.';try{const data=JSON.parse(xhr.responseText);if(data.message)message=data.message;}catch(e){}
                Swal.fire('Gagal menyimpan', message, 'error'); console.log(xhr.responseText);
            }
        });
    });
</script>
<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?>
</body>
</html>
<?php endif; ?>
