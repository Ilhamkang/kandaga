<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(true);
function kandagaPostIniBytes($value) { $value=trim((string)$value); if($value==='') return 0; $unit=strtolower(substr($value,-1)); $number=(float)$value; return (int)($unit==='g'?$number*1073741824:($unit==='m'?$number*1048576:($unit==='k'?$number*1024:$number))); }
$thumbnailMaxBytes = min(5242880, kandagaPostIniBytes(ini_get('upload_max_filesize')) ?: 5242880);
// Pastikan session aktif
if (session_status() == PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) session_start(); }
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?><div class="container-fluid"><?php endif; ?>
    <form id="formInputKabar" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="slug" id="slugInput">

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label>Judul Berita</label>
                    <input type="text" name="judul" id="judulInput" class="form-control form-control-lg" placeholder="Masukkan judul berita..." required>
                </div>
                <div class="mb-3">
                    <label>Konten</label>
                    <textarea name="konten" id="editorKabar" class="form-control"></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light"><strong>Gambar Utama</strong></div>
                    <div class="card-body text-center">
                        <div id="thumbnailPreview" class="mb-2" hidden><img class="img-fluid rounded shadow-sm" style="max-height:180px" alt="Pratinjau thumbnail"></div>
                        <input type="file" name="thumbnail" id="thumbnailInput" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" data-max-bytes="<?= (int)$thumbnailMaxBytes ?>">
                        <small class="text-muted d-block mt-1">Opsional untuk draf. JPG, PNG, WEBP, atau GIF hingga <?= number_format($thumbnailMaxBytes / 1048576, 1) ?> MB sesuai batas server.</small>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light"><strong>Status & Kategori</strong></div>
                    <div class="card-body">
                        <label>Kategori</label>
                        <select name="kategori" class="form-select mb-2">
                            <option value="berita">Berita</option>
                            <option value="kegiatan">Kegiatan</option>
                            <option value="pengumuman">Pengumuman</option>
                            <option value="artikel">Artikel</option>
                        </select>

                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="published">Diterbitkan</option>
                            <option value="draft" selected>Draf</option>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="submit_action" value="published" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>Terbitkan Kabar</button>
                    <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary"><i class="fa-solid fa-file-pen me-1"></i>Simpan Draf</button>
                    <button type="button" class="btn btn-secondary" onclick="$('#kontenUtama').load('daftar-post.php')">Batal</button>
                </div>
            </div>
        </div>
    </form>
<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?></div><?php endif; ?>

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
            if(file.size>maxBytes){this.value='';preview.prop('hidden',true);Swal.fire('Gambar terlalu besar','Ukuran maksimal thumbnail pada server adalah '+(maxBytes/1048576).toFixed(1)+' MB.','warning');return;}
            const reader=new FileReader();reader.onload=e=>{image.attr('src',e.target.result);preview.prop('hidden',false)};reader.readAsDataURL(file);
        });

        // 2. Inisialisasi TinyMCE
        function initTinyMCE() {
            if (typeof tinymce !== 'undefined') {
                tinymce.remove('#editorKabar');
                tinymce.init({
        content_css: '<?= BASE_URL ?>/assets/css/editor-fonts.css?v=3.1.2',
                    selector: '#editorKabar',
                    license_key: 'gpl',
                    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount autoresize',
                    toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | removeformat',
                    autoresize_min_height: 300,
                    autoresize_max_height: 1000
                });
            } else {
                setTimeout(initTinyMCE, 200);
            }
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
                        Swal.fire({
                            title: 'Berhasil!',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#kontenUtama').load('daftar-post.php');
                        });
                    } else {
                        Swal.fire('Gagal!', res.message, 'error');
                    }
                },
                error: function(xhr) {
                    let message='Terjadi kesalahan sistem.';try{const data=JSON.parse(xhr.responseText);if(data.message)message=data.message;}catch(e){}
                    console.log(xhr.responseText); Swal.fire('Gagal menyimpan', message, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Terbitkan Kabar');
                }
            });
        });
    });
</script>
