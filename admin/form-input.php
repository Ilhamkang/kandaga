<?php
require_once __DIR__ . '/../config/bootstrap.php';
// This form is normally loaded inside Goah, but it is also a valid direct
// request. Keep the same authenticated page guard in both cases.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/catalog-structure.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

$data_bahasa = mysqli_query($conn, "SELECT id_bahasa, nama_bahasa FROM tb_bahasa ORDER BY nama_bahasa ASC");
$data_aksara = mysqli_query($conn, "SELECT id_aksara, nama_aksara FROM tb_aksara ORDER BY nama_aksara ASC");
$data_alas   = mysqli_query($conn, "SELECT id_alas_tulis, nama_alas_tulis FROM tb_alas_tulis ORDER BY nama_alas_tulis ASC");
$data_lokasi = mysqli_query($conn, "SELECT id_lokasi, nama_tempat FROM tb_lokasi ORDER BY nama_tempat ASC");
$parent_options = mysqli_query($conn, "SELECT id, judul, kode_inventaris FROM katalog ORDER BY kode_inventaris, judul");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Form Input Naskah</title>


<style>

/* ================= PAGE TITLE ================= */
.page-title{
    font-weight:700;
    color:#334155;
    margin-bottom:20px;
}

/* ================= CARD ================= */
.form-card{
    background:#fff;
    border-radius:12px;
    padding:25px;
    margin-bottom:20px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
    border:1px solid #e2e8f0;
}

/* ================= SECTION TITLE ================= */
.section-title{
    font-size:18px; /* lebih stabil dari 20px */
    font-weight:700;
    color:#2563eb;
    margin-bottom:20px; /* FIX: sebelumnya "20 px" salah */
    padding-bottom:8px;
    border-bottom:2px solid #eff6ff;
    display:flex;
    align-items:center;
}

.section-title i{
    margin-right:8px;
}

/* ================= FORM CONTROL ================= */
.form-control,
.form-select{
    border-radius:8px;
    font-size:13px;
}

/* ================= DYNAMIC ROW ================= */
.dynamic-row{
    display:flex;
    gap:10px;
    margin-bottom:10px;
    align-items:center;
}

.dynamic-row select,
.dynamic
</style>
</head>

<body>

<div class="container-fluid">

<div class="page-title">
<i class="fa-solid fa-book"></i> Tambah Naskah Baru
</div>

<form id="formNaskah" method="POST" enctype="multipart/form-data">

<!-- ================= STRUKTUR & PROVENANCE (OPSIONAL) ================= -->
<div class="form-card">
<div class="section-title"><i class="fa-solid fa-sitemap"></i> Struktur & Lokasi Historis <small class="text-muted ms-2">(opsional)</small></div>
<div class="row">
<div class="col-md-3 mb-3"><label>Tipe Record</label><select name="record_type" class="form-select"><option value="induk">Naskah induk</option><option value="unit">Unit naskah</option></select></div>
<div class="col-md-5 mb-3"><label>Naskah Induk</label><select name="parent_id" class="form-select"><option value="">-- Tidak ada / record induk --</option><?php if ($parent_options): while($p=mysqli_fetch_assoc($parent_options)): ?><option value="<?= (int)$p['id'] ?>">#<?= (int)$p['id'] ?> · <?= htmlspecialchars($p['kode_inventaris'].' — '.$p['judul'], ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; endif; ?></select></div>
<div class="col-md-2 mb-3"><label>Label Unit</label><input type="text" name="unit_label" class="form-control" placeholder="Unit 1"></div>
<div class="col-md-2 mb-3"><label>Urutan</label><input type="number" min="1" name="unit_order" class="form-control"></div>
<div class="col-md-12 mb-3"><label>Catatan struktur</label><textarea name="structure_notes" class="form-control" rows="2"></textarea></div>
</div>
<hr><label class="fw-bold">Lokasi historis</label><div id="location-history-input"><div class="row location-history-row mb-2"><div class="col-md-2"><select name="location_history[0][kind]" class="form-select"><option value="asal">Asal</option><option value="ditemukan">Ditemukan</option><option value="sebelumnya">Lokasi sebelumnya</option></select></div><div class="col-md-3"><input name="location_history[0][place]" class="form-control" placeholder="Tempat/desa"></div><div class="col-md-2"><input name="location_history[0][district]" class="form-control" placeholder="Kota/kabupaten"></div><div class="col-md-2"><input name="location_history[0][province]" class="form-control" placeholder="Provinsi"></div><div class="col-md-2"><input name="location_history[0][country]" class="form-control" placeholder="Negara"></div><div class="col-md-1"><button type="button" class="btn btn-outline-primary" onclick="addHistoryRow()">+</button></div></div></div>
<small class="text-muted">Lokasi sekarang tetap dipilih pada field Lembaga Penyimpan.</small>
<hr><label class="fw-bold">Identitas alternatif</label>
<div id="identifier-input"><div class="row identifier-row mb-2"><div class="col-md-3"><input name="identifier[0][scheme]" class="form-control" placeholder="Shelfmark / Leiden"></div><div class="col-md-4"><input name="identifier[0][value]" class="form-control" placeholder="Nilai identitas"></div><div class="col-md-4"><input name="identifier[0][source]" class="form-control" placeholder="Sumber"></div><div class="col-md-1"><button type="button" class="btn btn-outline-primary" onclick="addIdentifierRow()">+</button></div></div></div>
</div>

<!-- ================= IDENTITAS UTAMA ================= -->
<div class="form-card">
<div class="section-title"><i class="fa-solid fa-address-card"></i> Identitas Utama</div>

<div class="row">
<div class="col-md-12 mb-3">
<label>Judul Naskah</label>
<input type="text" name="judul" class="form-control" required>
</div>

<div class="col-md-6 mb-3">
<label>Lembaga Penyimpan</label>
<select name="id_lokasi" class="form-select select-clean select-location" data-placeholder="Ketik nama lembaga atau lokasi...">
<option value="">-- Pilih Lokasi --</option>
<?php while($l=mysqli_fetch_assoc($data_lokasi)): ?>
<option value="<?= $l['id_lokasi'] ?>"><?= htmlspecialchars($l['nama_tempat']) ?></option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-6 mb-3">
<label>Kode Inventaris</label>
<input type="text" name="kode_inventaris" class="form-control">
</div>

<div class="col-md-12 mt-3">
    <label>Riwayat Naskah</label>
    <textarea
        id="riwayat_naskah"
        name="riwayat_naskah"
        class="form-control tinymce"
        rows="5"></textarea>
</div>

</div>
</div>

<!-- ================= KODIKOLOGIS ================= -->
<div class="form-card">
<div class="section-title">
    <i class="fa-solid fa-book-open"></i> Kodikologis & Bahasa
</div>

<div class="row">

<!-- ================= ALAS TULIS ================= -->
<div class="col-md-4 mb-3">
<label class="small fw-bold">Alas Tulis</label>

<div id="container-alas_tulis">
    <div class="dynamic-row">
        <select name="alas_tulis[]" class="form-select select-clean">
            <option value="">-- Alas Tulis --</option>
            <?php mysqli_data_seek($data_alas,0); while($a=mysqli_fetch_assoc($data_alas)): ?>
                <option value="<?= $a['id_alas_tulis'] ?>">
                    <?= $a['nama_alas_tulis'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<button type="button"
    class="btn btn-sm btn-outline-primary mt-2"
    onclick="addStatic('alas_tulis')">
    + Alas Tulis
</button>
</div>

<!-- ================= BAHASA ================= -->
<div class="col-md-4 mb-3">
<label class="small fw-bold">Bahasa</label>

<div id="container-bahasa">
    <div class="dynamic-row">
        <select name="bahasa[]" class="form-select select-clean">
            <option value="">-- Bahasa --</option>
            <?php mysqli_data_seek($data_bahasa,0); while($b=mysqli_fetch_assoc($data_bahasa)): ?>
                <option value="<?= $b['id_bahasa'] ?>">
                    <?= $b['nama_bahasa'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<button type="button"
    class="btn btn-sm btn-outline-primary mt-2"
    onclick="addStatic('bahasa')">
    + Bahasa
</button>
</div>

<!-- ================= AKSARA ================= -->
<div class="col-md-4 mb-3">
<label class="small fw-bold">Aksara</label>

<div id="container-aksara">
    <div class="dynamic-row">
        <select name="aksara[]" class="form-select select-clean">
            <option value="">-- Aksara --</option>
            <?php mysqli_data_seek($data_aksara,0); while($a=mysqli_fetch_assoc($data_aksara)): ?>
                <option value="<?= $a['id_aksara'] ?>">
                    <?= $a['nama_aksara'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<button type="button"
    class="btn btn-sm btn-outline-primary mt-2"
    onclick="addStatic('aksara')">
    + Aksara
</button>
</div>

<!-- ================= DESKRIPSI FISIK ================= -->
<div class="col-md-12 mt-3">
    <label>Deskripsi Fisik</label>
    <textarea
        id="deskripsi_fisik"
        name="deskripsi_fisik"
        class="form-control tinymce"
        rows="5"></textarea>
</div>
</div>
</div>

<!-- ================= BIBLIOGRAFI ================= -->
<div class="form-card">
<div class="section-title"><i class="fa-solid fa-layer-group"></i> Bibliografi</div>

<div id="container-katalog">
<label class="small fw-bold">Katalog</label>
<div class="dynamic-row">
<select name="katalog[]" class="form-select select-ajax-bib" data-kategori="katalog"></select>
</div>
</div>
<button type="button" class="btn btn-sm btn-info" onclick="BibliografiModule.addRow('katalog')">+ Katalog</button>

<hr>
<div id="container-pub_primer">
<label class="small fw-bold">Publikasi Primer</label>
<div class="dynamic-row">
<select name="pub_primer[]" class="form-select select-ajax-bib" data-kategori="referensi primer"></select>
</div>
</div>
<button type="button" class="btn btn-sm btn-danger" onclick="BibliografiModule.addRow('pub_primer')">+ Primer</button>

<hr>

<div id="container-pub_sekunder">
<label class="small fw-bold">Publikasi Sekunder</label>
<div class="dynamic-row">
<select name="pub_sekunder[]" class="form-select select-ajax-bib" data-kategori="referensi sekunder"></select>
</div>
</div>
<button type="button" class="btn btn-sm btn-success" onclick="BibliografiModule.addRow('pub_sekunder')">+ Sekunder</button>
</div>

<!-- ================= DESKRIPSI ISI (UPDATED TINYMCE) ================= -->
<div class="form-card">
<div class="section-title"><i class="fa-solid fa-align-left"></i> Deskripsi Isi</div>

<textarea
    id="deskripsi_isi"
    name="deskripsi_isi"
    class="form-control mb-3 tinymce"
    rows="10"></textarea>

<label>Kata Kunci</label>
<input type="text" name="kata_kunci" class="form-control">
</div>

<!-- ================= DIGITAL ACCESS ================= -->
<div class="form-card">
    <div class="section-title">
        <i class="fa-solid fa-link"></i> Akses Digital & Media
    </div>

    <!-- Penyedia Akses -->
    <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label">Penyedia Akses</label>
        <div class="col-md-9">
            <input type="text" name="penyedia_akses" class="form-control">
        </div>
    </div>

    <!-- URL Web -->
    <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label">URL Web</label>
        <div class="col-md-9">
            <input type="url" name="url_web" class="form-control">
        </div>
    </div>

    <!-- URL Repositori -->
    <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label">URL Repositori</label>
        <div class="col-md-9">
            <input type="url" name="url_repositori" class="form-control">
        </div>
    </div>

    <!-- IIIF Manifest -->
    <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label">IIIF Manifest</label>
        <div class="col-md-9">
            <input type="url" name="iiif_manifest" class="form-control">
        </div>
    </div>

    <!-- URL PDF -->
    <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label">URL PDF</label>
        <div class="col-md-9">
            <input type="url" name="url_pdf" class="form-control">
        </div>
    </div>

    <!-- Gambar -->
    <div class="row mb-3 align-items-center">
        <label class="col-md-3 col-form-label">Contoh Gambar Sampul/Isi</label>
        <div class="col-md-9">
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" name="gambar" class="form-control">
        </div>
    </div>
</div>

<!-- ================= SUBMIT ================= -->
<div class="sticky-submit d-flex justify-content-end">
    <button type="button" class="btn btn-light me-2" onclick="history.back()">Batal</button>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>

</form>

</div>

<!-- ================= JS ================= -->







<script>
$(function(){
    initSelect2();
    BibliografiModule.init(document);

    // INI KUNCI: Inisialisasi semua textarea dengan class .tinymce secara sekaligus
    // Kita tidak perlu memanggil satu-satu berdasarkan ID
    initTinyMCE('.tinymce', 300);
});

/* =========================================================
   TINYMCE INIT (DIPERBAIKI)
========================================================= */
function initTinyMCE(selector, height) {
    // Pastikan tinymce dimuat
    if (typeof tinymce === 'undefined') return;

    tinymce.remove(selector); // Bersihkan instansi lama

    tinymce.init({
        selector: selector, // Sekarang menangkap semua yang punya class .tinymce
        height: height,
        menubar: false,
        branding: false,
        plugins: 'lists link image code fullscreen table paste wordcount',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link image | alignleft aligncenter alignright | code fullscreen',
        setup: function (editor) {
            // Sinkronisasi otomatis ke textarea saat user mengetik
            editor.on('change keyup', function () {
                tinymce.triggerSave();
            });
        }
    });
}

/* =========================================================
   SELECT2 GLOBAL INIT (ANTI NESTING FIX)
========================================================= */
function initSelect2(){
    $('.select-clean').not('.select2-hidden-accessible').each(function () {
        const $select = $(this);
        $select.select2({
            width: '100%',
            dropdownParent: $(document.body),
            placeholder: $select.data('placeholder') || 'Pilih...',
            allowClear: true
        });
    });
}


/* =========================================================
   ADD STATIC (DENGAN VALIDASI STRICT)
========================================================= */
window.addStatic = function(type) {
    const container = document.getElementById('container-' + type);

    // --- LOGIKA VALIDASI STRICT ---
    // Ambil semua select di dalam container
    const selects = container.querySelectorAll('select');
    const lastSelect = selects[selects.length - 1];

    // Cek apakah select terakhir kosong
    if (lastSelect.value === "") {
        Swal.fire({
            icon: 'warning',
            title: 'Heup...',
            text: 'Harap isi pilihan pada baris sebelumnya terlebih dahulu!',
            toast: true,
            position: 'top-center',
            showConfirmButton: false,
            timer: 2000
        });
        return; // Batalkan penambahan jika kosong
    }
    // ------------------------------

    // (Lanjutkan dengan kode cloning Anda yang sebelumnya)
    const firstRow = container.querySelector('.dynamic-row');


    const newRow = $(firstRow).clone(false);

    newRow.find('select').val('').removeClass('select2-hidden-accessible');
    newRow.find('.select2-container').remove();
    newRow.find('select').removeAttr('data-select2-id tabindex aria-hidden').find('option').removeAttr('data-select2-id');

    if (!newRow.find('.btn-remove-row').length) {
        newRow.append('<button type="button" class="btn btn-danger btn-remove-row ms-2">×</button>');
    }

    newRow.find('.btn-remove-row').on('click', function() {
        $(this).parent().remove();
    });

    container.appendChild(newRow[0]);

    $('.select-clean').not('.select2-hidden-accessible').select2({
        width: '100%',
        dropdownParent: $(document.body)
    });
};



/* =========================================================
   BIBLIOGRAFI MODULE (AJAX SELECT2)
========================================================= */
window.BibliografiModule = (function(){

    function init(el){
        $(el).find('.select-ajax-bib').each(function(){
            const $el = $(this);

            if ($el.data('init')) return;
            $el.data('init', true);

            $el.select2({
                width: '100%',
                minimumInputLength: 2,
                // TAMBAHAN: Pengaturan bahasa agar pesan lebih ramah
                language: {
                    inputTooShort: function () {
                        return "Ketik minimal 2 karakter untuk mencari...";
                    },
                    noResults: function () {
                        return "Data bibliografi tidak ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                },
                ajax: {
                    url: 'actions/ajax-search-bibliografi.php',
                    data: params => ({
                        q: params.term,
                        kategori: $el.data('kategori')
                    }),
                    processResults: data => ({ results: data })
                }
            });
        });
    }

function addRow(type) {
        const container = document.getElementById('container-' + type);

        // --- LOGIKA VALIDASI BARU ---
        // Cari semua select di dalam container ini
        const selects = container.querySelectorAll('select');
        const lastSelect = selects[selects.length - 1];

        // Jika select terakhir kosong, beri peringatan atau stop
        if (lastSelect.value === "") {
            Swal.fire({
                icon: 'warning',
                title: 'Heup...',
                text: 'Silakan pilih data pada field sebelumnya terlebih dahulu!',
                toast: true,
                position: 'top-center',
                showConfirmButton: false,
                timer: 2000
            });
            return; // Berhenti di sini, tidak menambah baris
        }
        // ----------------------------

        const row = document.createElement('div');
        row.className = 'dynamic-row';

        row.innerHTML = `
            <select name="${type}[]" class="form-select select-ajax-bib" data-kategori="${type === 'katalog' ? 'katalog' : (type === 'pub_primer' ? 'referensi primer' : 'referensi sekunder')}"></select>
            <button type="button" class="btn btn-danger btn-remove-row">×</button>
        `;

        row.querySelector('button').onclick = () => row.remove();
        container.appendChild(row);

        init(container);
    }

    return { init, addRow };
})();


/* =========================================================
   AJAX SUBMIT FORM (DENGAN PERBAIKAN TINYMCE)
========================================================= */
$('#formNaskah').on('submit', function(e){
    e.preventDefault();

    // --- FIX: Sinkronisasi TinyMCE ke Textarea ---
    // Fungsi ini akan mengambil konten dari semua editor TinyMCE
    // dan memasukkannya kembali ke dalam masing-masing textarea aslinya.
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
    // ---------------------------------------------

    let fd = new FormData(this);

    $.ajax({
        url: '<?= BASE_URL ?>/admin/actions/proses-input.php',
        method: 'POST',
        dataType: 'json',
        data: fd,
        contentType: false,
        processData: false,
        success: function(res){
            if (!res.success) { Swal.fire('Gagal', res.message || 'Penyimpanan gagal', 'error'); return; }
            Swal.fire({
                title: 'Sukses!',
                text: res.message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?page=tampil-naskah';
                }
            });
        },
        error: function(xhr, status, error) {
            Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghubungi server.', 'error');
        }
    });
});

function addHistoryRow(){
    const box=document.getElementById('location-history-input');
    const i=box.querySelectorAll('.location-history-row').length;
    const row=document.createElement('div'); row.className='row location-history-row mb-2';
    row.innerHTML=`<div class="col-md-2"><select name="location_history[${i}][kind]" class="form-select"><option value="asal">Asal</option><option value="ditemukan">Ditemukan</option><option value="sebelumnya">Lokasi sebelumnya</option></select></div><div class="col-md-3"><input name="location_history[${i}][place]" class="form-control" placeholder="Tempat/desa"></div><div class="col-md-2"><input name="location_history[${i}][district]" class="form-control" placeholder="Kota/kabupaten"></div><div class="col-md-2"><input name="location_history[${i}][province]" class="form-control" placeholder="Provinsi"></div><div class="col-md-2"><input name="location_history[${i}][country]" class="form-control" placeholder="Negara"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.location-history-row').remove()">×</button></div>`;
    box.appendChild(row);
}
function addIdentifierRow(){const box=document.getElementById('identifier-input');const i=box.querySelectorAll('.identifier-row').length;const row=document.createElement('div');row.className='row identifier-row mb-2';row.innerHTML=`<div class="col-md-3"><input name="identifier[${i}][scheme]" class="form-control" placeholder="Shelfmark / Leiden"></div><div class="col-md-4"><input name="identifier[${i}][value]" class="form-control" placeholder="Nilai identitas"></div><div class="col-md-4"><input name="identifier[${i}][source]" class="form-control" placeholder="Sumber"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.identifier-row').remove()">×</button></div>`;box.appendChild(row);}


</script>

</body>
</html>
