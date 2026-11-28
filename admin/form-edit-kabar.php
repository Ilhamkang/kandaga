<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')
    && strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
    define('KANDAGA_ADMIN_FRAGMENT', true);
}
if (!defined('KANDAGA_GOAH_PANEL')) define('KANDAGA_GOAH_PANEL', true);
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

require_once __DIR__ . '/includes/media-library.php';
$existingGallery = [];
$tableCheck = @$conn->query("SHOW TABLES LIKE 'tb_kabar_gambar'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $galleryStmt = $conn->prepare('SELECT url_gambar, alt_gambar FROM tb_kabar_gambar WHERE id_kabar = ? ORDER BY urutan, id_gambar');
    $galleryStmt->bind_param('i', $id);
    $galleryStmt->execute();
    $galleryResult = $galleryStmt->get_result();
    while ($galleryRow = $galleryResult->fetch_assoc()) {
        $url = (string)$galleryRow['url_gambar'];
        $name = basename((string)parse_url($url, PHP_URL_PATH));
        if (!kandagaMediaNameValid($name)) continue;
        $existingGallery[] = [
            'folder' => 'post',
            'name'   => $name,
            'url'    => BASE_URL . '/assets/images/post/' . rawurlencode($name),
            'alt'    => (string)$galleryRow['alt_gambar'],
        ];
    }
    $galleryStmt->close();
}
$galleryJson = json_encode($existingGallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Kabar</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
<?php include __DIR__ . '/../includes/module-head.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin-theme.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="kandaga-module kandaga-admin-shell">
<?php endif; ?>

<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?><div class="container-fluid admin-module-surface"><?php endif; ?>
    <div class="admin-page-header mb-4">
        <div><h1><i class="fa-solid fa-pen-to-square me-2" aria-hidden="true"></i>Edit Postingan</h1><p class="mb-0">Perbarui isi, kategori, dan status publikasi tulisan ini.</p></div>
    </div>

    <form id="formEditKabar" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_kabar" value="<?= $data['id_kabar']; ?>">
        <input type="hidden" name="thumbnail_pick" id="thumbnailPick" value="">
        <input type="hidden" name="gambar_tambahan" id="gambarTambahan" value="<?= htmlspecialchars($galleryJson ?: '[]', ENT_QUOTES, 'UTF-8') ?>">

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Judul Berita</label>
                            <input type="text" name="judul" class="form-control form-control-lg"
                                   value="<?= htmlspecialchars($data['judul']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug (URL Friendly)</label>
                            <input type="text" name="slug" class="form-control"
                                   value="<?= htmlspecialchars($data['slug']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konten</label>
                            <textarea name="konten" id="editorKabar" class="form-control"><?= htmlspecialchars($data['konten']); ?></textarea>
                            <small class="text-muted d-block mt-1">Letakkan kursor di dalam paragraf lalu pakai tombol Gambar untuk mengunggah atau memilih berkas dari Pustaka Media.</small>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-light d-flex align-items-center justify-content-between gap-2">
                        <strong><i class="fa-solid fa-images me-2" aria-hidden="true"></i>Galeri Gambar</strong>
                        <span class="small text-muted" id="galleryCount">0 gambar dipilih</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">Gambar tambahan dari Pustaka Media akan ditampilkan sebagai galeri di bawah artikel. Maksimal 20 gambar.</p>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="pickGallery"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Pilih dari Pustaka Media</button>
                        <div class="kandaga-post-gallery mt-3" id="galleryList"></div>
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
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2 w-100" id="pickThumbnail"><i class="fa-solid fa-images me-1" aria-hidden="true"></i>Pilih dari Pustaka Media</button>
                        <small class="text-muted d-block mt-1" id="thumbnailSource">Biarkan kosong jika tidak diganti. Unggah berkas atau pilih dari Pustaka Media. Maksimal <?= number_format($thumbnailMaxBytes / 1048576, 1) ?> MB sesuai batas server.</small>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light"><strong>Status & Kategori</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select">
                                <?php foreach(['berita', 'kegiatan', 'pengumuman', 'artikel'] as $kat): ?>
                                    <option value="<?= $kat ?>" <?= $data['kategori'] == $kat ? 'selected' : '' ?>><?= ucfirst($kat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= $data['status'] == 'published' ? 'selected' : '' ?>>Diterbitkan</option>
                                <option value="draft" <?= $data['status'] == 'draft' ? 'selected' : '' ?>>Draf</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" onclick="$('#adminModule').load('daftar-post.php')">Batal</button>
                </div>
            </div>
        </div>
    </form>
<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?></div><?php endif; ?>

<script src="<?= BASE_URL ?>/admin/assets/js/kandaga-editor-media.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= BASE_URL ?>/admin/assets/js/kandaga-post-media.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    // Konfigurasi yang sama dengan "Buat Postingan", termasuk saat dimuat via AJAX.
    function initEditorKabar(attempt = 0) {
        if (typeof tinymce === 'undefined' || !window.KandagaEditorMedia) {
            if (attempt < 50) setTimeout(function () { initEditorKabar(attempt + 1); }, 200);
            return;
        }
        window.KANDAGA_EDITOR_UPLOAD_URL = '<?= BASE_URL ?>/admin/actions/editor-image-upload.php';
        window.KANDAGA_EDITOR_PICKER_URL = '<?= BASE_URL ?>/admin/file-picker.php';
        tinymce.remove('#editorKabar');
        var editorInstance = null;
        tinymce.init({
            content_css: '<?= BASE_URL ?>/assets/css/editor-fonts.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>',
            selector: '#editorKabar',
            license_key: 'gpl',
            promotion: false,
            branding: true,
            menubar: 'file edit view insert format tools table help',
            toolbar_sticky: true,
            toolbar_mode: 'sliding',
            block_formats: 'Paragraf=p; Judul 1=h1; Judul 2=h2; Judul 3=h3; Judul 4=h4; Kutipan=blockquote; Kode=pre',
            plugins: 'accordion advlist anchor autolink autoresize autosave charmap code codesample directionality emoticons fullscreen help image importcss insertdatetime link lists media nonbreaking pagebreak preview quickbars save searchreplace table visualblocks visualchars wordcount',
            toolbar: 'undo redo | save | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent accordion | link unlink anchor image kandagaMedia media emoticons | table | charmap insertdatetime nonbreaking pagebreak | searchreplace replace | ltr rtl | code codesample | visualblocks visualchars | preview fullscreen | help',
            automatic_uploads: true,
            // Keep absolute media URLs: posts are rendered outside /admin.
            convert_urls: false,
            relative_urls: false,
            remove_script_host: false,
            image_title: true,
            a11y_advanced_options: true,
            images_upload_handler: function (blobInfo, success, failure, progress) {
                window.KandagaEditorMedia.upload(blobInfo, success, failure, progress);
            },
            file_picker_callback: function (callback, value, meta) {
                window.KandagaEditorMedia.pick(editorInstance || tinymce.activeEditor, callback, meta);
            },
            setup: function (editor) {
                editorInstance = editor;
                window.KandagaEditorMedia.setup(editor);
            },
            save_onsavecallback: function () {
                tinymce.triggerSave();
                $('#formEditKabar').trigger('submit');
            },
            autoresize_min_height: 300,
            autoresize_max_height: 1000
        });
    }
    initEditorKabar();

    $('#thumbnailInputEdit').off('change').on('change', function(){
        const file=this.files&&this.files[0], box=$('#thumbnailPreviewNew'), image=box.find('img');
        if(!file){box.prop('hidden',true);return;}
        const maxBytes=Number(this.dataset.maxBytes||5242880);
        if(file.size>maxBytes){this.value='';box.prop('hidden',true);kandagaDialog.show({title:'Gambar terlalu besar',message:'Ukuran maksimal thumbnail pada server adalah '+(maxBytes/1048576).toFixed(1)+' MB.',icon:'warning'});return;}
        $('#thumbnailPick').val('');
        $('#thumbnailSource').text('Gambar utama akan diganti dari berkas yang diunggah.');
        const reader=new FileReader();reader.onload=e=>{image.attr('src',e.target.result);box.prop('hidden',false)};reader.readAsDataURL(file);
    });

    window.kandagaPostMedia.init({
        pickerUrl: '<?= BASE_URL ?>/admin/file-picker.php',
        mode: 'single',
        hiddenInput: document.getElementById('thumbnailPick'),
        preview: document.getElementById('thumbnailPreviewNew'),
        fileInput: document.getElementById('thumbnailInputEdit'),
        sourceLabel: document.getElementById('thumbnailSource'),
        galleryInput: document.getElementById('gambarTambahan'),
        galleryList: document.getElementById('galleryList'),
        galleryCount: document.getElementById('galleryCount')
    });
    $('#pickThumbnail').on('click', function () { window.kandagaPostMedia.open({mode: 'single'}); });
    $('#pickGallery').on('click', function () { window.kandagaPostMedia.open({mode: 'multiple'}); });

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
                if (!res || res.status !== 'success') { kandagaDialog.show({title: 'Gagal', message: (res && res.message) || 'Penyimpanan gagal.', icon: 'error'}); return; }
                kandagaDialog.show({title: 'Berhasil!', message: res.message || 'Data telah diperbarui.', icon: 'success'}).then(() => {
                    window.location.href = 'index.php?page=daftar-post';
                });
            },
            error: function(xhr) {
                let message='Gagal memproses. Periksa batas upload dan izin folder.';try{const data=JSON.parse(xhr.responseText);if(data.message)message=data.message;}catch(e){}
                kandagaDialog.show({title: 'Gagal menyimpan', message: message, icon: 'error'}); console.log(xhr.responseText);
            }
        });
    });
</script>
<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?>
</body>
</html>
<?php endif; ?>
