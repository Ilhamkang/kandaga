<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(true);
function kandagaPostIniBytes($value) { $value=trim((string)$value); if($value==='') return 0; $unit=strtolower(substr($value,-1)); $number=(float)$value; return (int)($unit==='g'?$number*1073741824:($unit==='m'?$number*1048576:($unit==='k'?$number*1024:$number))); }
$thumbnailMaxBytes = min(5242880, kandagaPostIniBytes(ini_get('upload_max_filesize')) ?: 5242880);
// Pastikan session aktif
if (session_status() == PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) session_start(); }
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?><div class="container-fluid admin-module-surface"><?php endif; ?>
    <?php kandagaPageHeader('Buat Postingan', 'fa-pen-to-square', 'Tulis kabar baru dengan editor lengkap, thumbnail, kategori, dan status publikasi.'); ?>
    <form id="formInputKabar" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="slug" id="slugInput">
        <input type="hidden" name="thumbnail_pick" id="thumbnailPick" value="">
        <input type="hidden" name="gambar_tambahan" id="gambarTambahan" value="[]">

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label">Judul Berita</label>
                    <input type="text" name="judul" id="judulInput" class="form-control form-control-lg" placeholder="Masukkan judul berita..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konten</label>
                    <textarea name="konten" id="editorKabar" class="form-control"></textarea>
                    <small class="text-muted d-block mt-1">Letakkan kursor di dalam paragraf lalu pakai tombol Gambar untuk mengunggah atau memilih berkas dari Pustaka Media.</small>
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
                        <div id="thumbnailPreview" class="mb-2" hidden><img class="img-fluid rounded shadow-sm" style="max-height:180px" alt="Pratinjau thumbnail"></div>
                        <input type="file" name="thumbnail" id="thumbnailInput" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" data-max-bytes="<?= (int)$thumbnailMaxBytes ?>">
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2 w-100" id="pickThumbnail"><i class="fa-solid fa-images me-1" aria-hidden="true"></i>Pilih dari Pustaka Media</button>
                        <small class="text-muted d-block mt-1" id="thumbnailSource">Opsional untuk draf. Unggah berkas atau pilih dari Pustaka Media. JPG, PNG, WEBP, atau GIF hingga <?= number_format($thumbnailMaxBytes / 1048576, 1) ?> MB sesuai batas server.</small>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light"><strong>Status & Kategori</strong></div>
                    <div class="card-body">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select mb-2">
                            <option value="berita">Berita</option>
                            <option value="kegiatan">Kegiatan</option>
                            <option value="pengumuman">Pengumuman</option>
                            <option value="artikel">Artikel</option>
                        </select>

                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="published">Diterbitkan</option>
                            <option value="draft" selected>Draf</option>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="submit_action" value="published" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>Terbitkan Kabar</button>
                    <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary"><i class="fa-solid fa-file-pen me-1"></i>Simpan Draf</button>
                    <button type="button" class="btn btn-secondary" onclick="$('#adminModule').load('daftar-post.php')">Batal</button>
                </div>
            </div>
        </div>
    </form>
<?php if (!defined('KANDAGA_ADMIN_SHELL') && !defined('KANDAGA_ADMIN_FRAGMENT')): ?></div><?php endif; ?>

<script src="<?= BASE_URL ?>/admin/assets/js/kandaga-editor-media.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= BASE_URL ?>/admin/assets/js/kandaga-post-media.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    $(document).ready(function() {
        // 1. Fungsi Slug Otomatis
        function createSlug(text) {
            return text.toString().toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        }

        $('#judulInput').on('input', function() {
            $('#slugInput').val(createSlug($(this).val()));
        });

        $('#thumbnailInput').on('change', function() {
            const file=this.files&&this.files[0], preview=$('#thumbnailPreview'), image=preview.find('img');
            if(!file){preview.prop('hidden',true);return;}
            const maxBytes=Number(this.dataset.maxBytes||5242880);
            if(file.size>maxBytes){this.value='';preview.prop('hidden',true);kandagaDialog.show({title:'Gambar terlalu besar',message:'Ukuran maksimal thumbnail pada server adalah '+(maxBytes/1048576).toFixed(1)+' MB.',icon:'warning'});return;}
            $('#thumbnailPick').val('');
            $('#thumbnailSource').text('Gambar utama dari berkas yang diunggah.');
            const reader=new FileReader();reader.onload=e=>{image.attr('src',e.target.result);preview.prop('hidden',false)};reader.readAsDataURL(file);
        });

        // Pemilih gambar utama dan galeri dari Pustaka Media.
        window.kandagaPostMedia.init({
            pickerUrl: '<?= BASE_URL ?>/admin/file-picker.php',
            mode: 'single',
            hiddenInput: document.getElementById('thumbnailPick'),
            preview: document.getElementById('thumbnailPreview'),
            fileInput: document.getElementById('thumbnailInput'),
            sourceLabel: document.getElementById('thumbnailSource'),
            galleryInput: document.getElementById('gambarTambahan'),
            galleryList: document.getElementById('galleryList'),
            galleryCount: document.getElementById('galleryCount')
        });
        $('#pickThumbnail').on('click', function () { window.kandagaPostMedia.open({mode: 'single'}); });
        $('#pickGallery').on('click', function () { window.kandagaPostMedia.open({mode: 'multiple'}); });

        // 2. Inisialisasi TinyMCE dengan seluruh plugin open-source yang tersedia
        //    di bundle lokal. Tidak ada modifikasi terhadap berkas vendor.
        function initTinyMCE(attempt = 0) {
            if (typeof tinymce === 'undefined' || !window.KandagaEditorMedia) {
                if (attempt < 50) setTimeout(function () { initTinyMCE(attempt + 1); }, 200);
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
                    $('#formInputKabar').trigger('submit');
                },
                autoresize_min_height: 300,
                autoresize_max_height: 1000
            });
        }
        initTinyMCE();

        // 3. Submit Form AJAX
        $('#formInputKabar').off('submit').on('submit', function(e) {
            e.preventDefault();

            // Sinkronisasi TinyMCE ke Textarea
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            var formData = new FormData(this);
            formData.set('status', e.originalEvent && e.originalEvent.submitter ? e.originalEvent.submitter.value : $('#formInputKabar select[name="status"]').val());
            var $btn = $(this).find('button[type="submit"]');

            $.ajax({
                url: 'actions/proses-input-kabar.php',
                type: 'POST',
                data: formData,
                contentType: false, // Wajib false untuk file upload
                processData: false, // Wajib false untuk file upload
                dataType: 'json',   // Menangani respons JSON
                beforeSend: function() {
                    $btn.prop('disabled', true).text('Menyimpan...');
                },
                success: function(res) {
                    // Cek berdasarkan properti JSON (status)
                    if(res.status === 'success') {
                        kandagaDialog.show({title: 'Berhasil!', message: res.message, icon: 'success', timer: 1500}).then(() => {
                            $('#adminModule').load('daftar-post.php');
                        });
                    } else {
                        kandagaDialog.show({title: 'Gagal!', message: res.message, icon: 'error'});
                    }
                },
                error: function(xhr) {
                    let message='Terjadi kesalahan sistem.';try{const data=JSON.parse(xhr.responseText);if(data.message)message=data.message;}catch(e){}
                    console.log(xhr.responseText); kandagaDialog.show({title: 'Gagal menyimpan', message: message, icon: 'error'});
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Terbitkan Kabar');
                }
            });
        });
    });
</script>
