<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') kandagaCsrf();

require_once __DIR__ . '/../config/bootstrap.php';
// FUNGSI HELPER REDIRECT JAVASCRIPT
function redirect($url) {
    echo "<script>window.location.href='$url';</script>";
    exit;
}

// ==========================================
// 1. LOGIKA PEMROSESAN DATA (CRUD)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Proses Aksara
    if (isset($_POST['simpan_aksara'])) {
        $id = !empty($_POST['id_aksara']) ? (int)$_POST['id_aksara'] : null;
        if ($id) {
            $stmt = $conn->prepare("UPDATE tb_aksara SET nama_aksara=?, deskripsi=?, tahun_ditemukan=? WHERE id_aksara=?");
            $stmt->bind_param("ssii", $_POST['nama_aksara'], $_POST['deskripsi'], $_POST['tahun_ditemukan'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO tb_aksara (nama_aksara, deskripsi, tahun_ditemukan) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $_POST['nama_aksara'], $_POST['deskripsi'], $_POST['tahun_ditemukan']);
        }
        $stmt->execute();
        redirect("index.php?page=manage-referensi&tab=aksara");
    }

    // B. Proses Alas Tulis
    if (isset($_POST['simpan_alas'])) {
        $id = !empty($_POST['id_alas_tulis']) ? (int)$_POST['id_alas_tulis'] : null;
        if ($id) {
            $stmt = $conn->prepare("UPDATE tb_alas_tulis SET nama_alas_tulis = ? WHERE id_alas_tulis = ?");
            $stmt->bind_param("si", $_POST['nama_alas_tulis'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO tb_alas_tulis (nama_alas_tulis) VALUES (?)");
            $stmt->bind_param("s", $_POST['nama_alas_tulis']);
        }
        $stmt->execute();
        redirect("index.php?page=manage-referensi&tab=alas");
    }

    // C. Proses Bahasa
    if (isset($_POST['simpan_bahasa'])) {
        $id = !empty($_POST['id_bahasa']) ? (int)$_POST['id_bahasa'] : null;
        if ($id) {
            $stmt = $conn->prepare("UPDATE tb_bahasa SET nama_bahasa=?, kode_iso=?, deskripsi=? WHERE id_bahasa=?");
            $stmt->bind_param("sssi", $_POST['nama_bahasa'], $_POST['kode_iso'], $_POST['deskripsi'], $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO tb_bahasa (nama_bahasa, kode_iso, deskripsi) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $_POST['nama_bahasa'], $_POST['kode_iso'], $_POST['deskripsi']);
        }
        $stmt->execute();
        redirect("index.php?page=manage-referensi&tab=bahasa");
    }

    // D. Proses Lokasi
    if (isset($_POST['simpan_lokasi'])) {
        $id = !empty($_POST['id_lokasi']) ? (int)$_POST['id_lokasi'] : null;
        require_once __DIR__ . '/includes/upload.php';
        $foto_baru = '';
        if ($id) {
            $old = $conn->prepare('SELECT foto_lokasi FROM tb_lokasi WHERE id_lokasi = ?');
            $old->bind_param('i', $id); $old->execute();
            $foto_baru = $old->get_result()->fetch_assoc()['foto_lokasi'] ?? '';
        }
        $new = kandagaUpload('foto_lokasi', ROOT_PATH . '/assets/images', 'lokasi');
        if ($new) $foto_baru = $new;

        if ($id) {
            $stmt = $conn->prepare("UPDATE tb_lokasi SET nama_tempat=?, negara=?, alamat=?, deskripsi=?, foto_lokasi=? WHERE id_lokasi=?");
            $stmt->bind_param("sssssi", $_POST['nama_tempat'], $_POST['negara'], $_POST['alamat'], $_POST['deskripsi'], $foto_baru, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO tb_lokasi (nama_tempat, negara, alamat, deskripsi, foto_lokasi) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $_POST['nama_tempat'], $_POST['negara'], $_POST['alamat'], $_POST['deskripsi'], $foto_baru);
        }
        $stmt->execute();
        redirect("index.php?page=manage-referensi&tab=lokasi");
    }
}

// Proses Delete via GET
if (isset($_POST['del_aksara'])) { $conn->query("DELETE FROM tb_aksara WHERE id_aksara = ".(int)$_POST['del_aksara']); redirect("index.php?page=manage-referensi&tab=aksara"); }
if (isset($_POST['del_alas'])) { $conn->query("DELETE FROM tb_alas_tulis WHERE id_alas_tulis = ".(int)$_POST['del_alas']); redirect("index.php?page=manage-referensi&tab=alas"); }
if (isset($_POST['del_bahasa'])) { $conn->query("DELETE FROM tb_bahasa WHERE id_bahasa = ".(int)$_POST['del_bahasa']); redirect("index.php?page=manage-referensi&tab=bahasa"); }
if (isset($_POST['del_lokasi'])) { $conn->query("DELETE FROM tb_lokasi WHERE id_lokasi = ".(int)$_POST['del_lokasi']); redirect("index.php?page=manage-referensi&tab=lokasi"); }

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'aksara';
$prefCounts = [
    'aksara' => (int)$conn->query('SELECT COUNT(*) AS total FROM tb_aksara')->fetch_assoc()['total'],
    'alas'   => (int)$conn->query('SELECT COUNT(*) AS total FROM tb_alas_tulis')->fetch_assoc()['total'],
    'bahasa' => (int)$conn->query('SELECT COUNT(*) AS total FROM tb_bahasa')->fetch_assoc()['total'],
    'lokasi' => (int)$conn->query('SELECT COUNT(*) AS total FROM tb_lokasi')->fetch_assoc()['total'],
];
?>

<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?><div class="container-fluid admin-module-surface py-4"><?php endif; ?>
<?php kandagaPageHeader('Preferensi Katalog', 'fa-scroll', 'Kelola data master aksara, alas tulis, bahasa, dan lokasi yang dipakai saat menginput naskah.'); ?>

<section class="kandaga-pref-grid row g-3" aria-label="Grup preferensi katalog">
    <div class="col-lg-6" id="pref-aksara">
        <article class="kandaga-pref-card h-100 <?= $active_tab === 'aksara' ? 'is-active' : '' ?>">
            <header class="kandaga-pref-card__header">
                <span class="kandaga-pref-icon"><i class="fa-solid fa-pen-fancy" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><h2>Aksara</h2><p>Aksara yang tersedia saat menginput naskah.</p></div>
                <span class="badge rounded-pill kandaga-pref-count"><?= $prefCounts['aksara'] ?> entri</span>
            </header>
            <div class="kandaga-pref-tools">
                <button class="btn btn-sm btn-primary" type="button" onclick="resetAksara()"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Aksara</button>
                <div class="form-check form-switch mb-0"><input class="form-check-input kandaga-pref-switch" type="checkbox" role="switch" id="prefCompactRows"><label class="form-check-label" for="prefCompactRows">Baris padat</label></div>
            </div>
            <div class="admin-scroll-table">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Nama</th><th>Tahun</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php $res = $conn->query('SELECT * FROM tb_aksara'); while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama_aksara'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['tahun_ditemukan'] ?? '') ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light text-warning kandaga-action-btn" data-bs-toggle="tooltip" title="Edit" aria-label="Edit aksara" onclick="editAksara(<?= $r['id_aksara'] ?>, '<?= addslashes($r['nama_aksara']) ?>', '<?= addslashes($r['deskripsi']) ?>', '<?= $r['tahun_ditemukan'] ?>')"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                                <a href="?page=manage-referensi&del_aksara=<?= $r['id_aksara'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-light text-danger kandaga-action-btn" data-bs-toggle="tooltip" title="Hapus" aria-label="Hapus aksara"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <div class="col-lg-6" id="pref-alas">
        <article class="kandaga-pref-card h-100 <?= $active_tab === 'alas' ? 'is-active' : '' ?>">
            <header class="kandaga-pref-card__header">
                <span class="kandaga-pref-icon"><i class="fa-solid fa-scroll" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><h2>Alas Tulis</h2><p>Media fisik tempat naskah ditulis.</p></div>
                <span class="badge rounded-pill kandaga-pref-count"><?= $prefCounts['alas'] ?> entri</span>
            </header>
            <div class="kandaga-pref-tools">
                <button class="btn btn-sm btn-primary" type="button" onclick="resetAlas()"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Alas Tulis</button>
            </div>
            <div class="admin-scroll-table">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Nama</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php $res = $conn->query('SELECT * FROM tb_alas_tulis'); while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama_alas_tulis'] ?? '') ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light text-warning kandaga-action-btn" data-bs-toggle="tooltip" title="Edit" aria-label="Edit alas tulis" onclick="editAlas(<?= $r['id_alas_tulis'] ?>, '<?= addslashes($r['nama_alas_tulis']) ?>')"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                                <a href="?page=manage-referensi&del_alas=<?= $r['id_alas_tulis'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-light text-danger kandaga-action-btn" data-bs-toggle="tooltip" title="Hapus" aria-label="Hapus alas tulis"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <div class="col-lg-6" id="pref-bahasa">
        <article class="kandaga-pref-card h-100 <?= $active_tab === 'bahasa' ? 'is-active' : '' ?>">
            <header class="kandaga-pref-card__header">
                <span class="kandaga-pref-icon"><i class="fa-solid fa-language" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><h2>Bahasa</h2><p>Bahasa teks dan kode identifikasi ISO-nya.</p></div>
                <span class="badge rounded-pill kandaga-pref-count"><?= $prefCounts['bahasa'] ?> entri</span>
            </header>
            <div class="kandaga-pref-tools">
                <button class="btn btn-sm btn-primary" type="button" onclick="resetBahasa()"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Bahasa</button>
                <div class="form-check form-switch mb-0"><input class="form-check-input kandaga-pref-switch" type="checkbox" role="switch" id="prefShowIso"><label class="form-check-label" for="prefShowIso">Tampilkan kode ISO</label></div>
            </div>
            <div class="admin-scroll-table">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Nama</th><th class="pref-iso-col">ISO</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php $res = $conn->query('SELECT * FROM tb_bahasa'); while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama_bahasa'] ?? '') ?></td>
                            <td class="pref-iso-col"><?= htmlspecialchars($r['kode_iso'] ?? '') ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light text-warning kandaga-action-btn" data-bs-toggle="tooltip" title="Edit" aria-label="Edit bahasa" onclick="editBahasa(<?= $r['id_bahasa'] ?>, '<?= addslashes($r['nama_bahasa']) ?>', '<?= addslashes($r['kode_iso']) ?>', '<?= addslashes($r['deskripsi']) ?>')"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                                <a href="?page=manage-referensi&del_bahasa=<?= $r['id_bahasa'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-light text-danger kandaga-action-btn" data-bs-toggle="tooltip" title="Hapus" aria-label="Hapus bahasa"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <div class="col-lg-6" id="pref-lokasi">
        <article class="kandaga-pref-card h-100 <?= $active_tab === 'lokasi' ? 'is-active' : '' ?>">
            <header class="kandaga-pref-card__header">
                <span class="kandaga-pref-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
                <div class="flex-grow-1"><h2>Lokasi</h2><p>Lembaga atau tempat penyimpanan naskah.</p></div>
                <span class="badge rounded-pill kandaga-pref-count"><?= $prefCounts['lokasi'] ?> entri</span>
            </header>
            <div class="kandaga-pref-tools">
                <button class="btn btn-sm btn-primary" type="button" onclick="resetLokasi()"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Lokasi</button>
                <div class="form-check form-switch mb-0"><input class="form-check-input kandaga-pref-switch" type="checkbox" role="switch" id="prefShowPhoto"><label class="form-check-label" for="prefShowPhoto">Tampilkan foto</label></div>
            </div>
            <div class="admin-scroll-table">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th class="pref-photo-col">Foto</th><th>Nama</th><th>Negara</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php
                        $res = $conn->query('SELECT * FROM tb_lokasi');
                        while ($r = $res->fetch_assoc()):
                            $foto = !empty($r['foto_lokasi']) ? $r['foto_lokasi'] : 'msc-placeholder.png';
                            $fotoUrl = BASE_URL . '/assets/images/' . rawurlencode(basename($foto));
                        ?>
                        <tr>
                            <td class="pref-photo-col"><img src="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="img-thumb-admin" style="width:38px;height:38px;object-fit:cover;"></td>
                            <td><?= htmlspecialchars($r['nama_tempat'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['negara'] ?? '') ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light text-warning kandaga-action-btn" data-bs-toggle="tooltip" title="Edit" aria-label="Edit lokasi" onclick="editLokasi(<?= $r['id_lokasi'] ?>, '<?= addslashes($r['nama_tempat']) ?>', '<?= addslashes($r['negara']) ?>', '<?= addslashes($r['alamat']) ?>', '<?= addslashes($r['deskripsi']) ?>', '<?= $r['foto_lokasi'] ?>')"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                                <a href="?page=manage-referensi&del_lokasi=<?= $r['id_lokasi'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-light text-danger kandaga-action-btn" data-bs-toggle="tooltip" title="Hapus" aria-label="Hapus lokasi"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>
</section>
<?php if (!defined('KANDAGA_ADMIN_SHELL')): ?></div><?php endif; ?>

<div class="modal fade" id="modalAksara"><div class="modal-dialog modal-dialog-centered"><form method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-pen-fancy me-2" aria-hidden="true"></i>Aksara</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><input type="hidden" name="id_aksara" id="id_aksara"><div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama_aksara" id="nama_aksara" class="form-control" required></div><div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="deskripsi_aksara" class="form-control"></textarea></div><div class="mb-3"><label class="form-label">Tahun</label><input type="number" name="tahun_ditemukan" id="tahun_ditemukan" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="simpan_aksara" class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="modalAlas"><div class="modal-dialog modal-dialog-centered"><form method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-scroll me-2" aria-hidden="true"></i>Alas Tulis</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><input type="hidden" name="id_alas_tulis" id="id_alas"><div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama_alas_tulis" id="nama_alas" class="form-control" required></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="simpan_alas" class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="modalBahasa"><div class="modal-dialog modal-dialog-centered"><form method="POST" class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-language me-2" aria-hidden="true"></i>Bahasa</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><input type="hidden" name="id_bahasa" id="id_bahasa"><div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama_bahasa" id="nama_bahasa" class="form-control" required></div><div class="mb-3"><label class="form-label">Kode ISO</label><input type="text" name="kode_iso" id="kode_iso" class="form-control"></div><div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="deskripsi_bahasa" class="form-control"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="simpan_bahasa" class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="modalLokasi"><div class="modal-dialog modal-dialog-centered"><form method="POST" enctype="multipart/form-data" class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>Lokasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><input type="hidden" name="id_lokasi" id="id_lokasi"><input type="hidden" name="foto_lama" id="foto_lama"><div class="text-center mb-3"><img id="preview_foto" src="" style="max-width:150px; display:none;"></div><div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama_tempat" id="nama_tempat" class="form-control" required></div><div class="mb-3"><label class="form-label">Negara</label><input type="text" name="negara" id="negara" class="form-control"></div><div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" id="alamat" class="form-control"></textarea></div><div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="deskripsi_lokasi" class="form-control"></textarea></div><div class="mb-3"><label class="form-label">Foto</label><input type="file" name="foto_lokasi" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" name="simpan_lokasi" class="btn btn-primary">Simpan</button></div></form></div></div>

<script>
function resetAksara() { $('#id_aksara').val(''); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAksara')).show(); }
function editAksara(id, n, d, t) { $('#id_aksara').val(id); $('#nama_aksara').val(n); $('#deskripsi_aksara').val(d); $('#tahun_ditemukan').val(t); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAksara')).show(); }

function resetAlas() { $('#id_alas').val(''); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAlas')).show(); }
function editAlas(id, n) { $('#id_alas').val(id); $('#nama_alas').val(n); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAlas')).show(); }

function resetBahasa() { $('#id_bahasa').val(''); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBahasa')).show(); }
function editBahasa(id, n, k, d) { $('#id_bahasa').val(id); $('#nama_bahasa').val(n); $('#kode_iso').val(k); $('#deskripsi_bahasa').val(d); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBahasa')).show(); }

function resetLokasi() { $('#id_lokasi').val(''); $('#preview_foto').hide(); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLokasi')).show(); }
function editLokasi(id, n, ng, al, des, ft) {
    $('#id_lokasi').val(id); $('#nama_tempat').val(n); $('#negara').val(ng); $('#alamat').val(al); $('#deskripsi_lokasi').val(des); $('#foto_lama').val(ft);
    if(ft) { $('#preview_foto').attr('src', '../assets/images/'+ft).show(); } else { $('#preview_foto').hide(); }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLokasi')).show();
}

// Tampilan preferensi bersifat lokal di peramban dan tidak mengubah data master.
(function () {
    const key = 'kandagaCatalogPrefs';
    const defaults = { prefCompactRows: false, prefShowIso: true, prefShowPhoto: true };
    let prefs = {};
    try { prefs = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch (error) { prefs = {}; }
    function apply() {
        document.body.classList.toggle('pref-compact-rows', !!prefs.prefCompactRows);
        document.querySelectorAll('.pref-iso-col').forEach(function (el) { el.hidden = prefs.prefShowIso === false; });
        document.querySelectorAll('.pref-photo-col').forEach(function (el) { el.hidden = prefs.prefShowPhoto === false; });
    }
    document.querySelectorAll('.kandaga-pref-switch').forEach(function (input) {
        const name = input.id;
        input.checked = Object.prototype.hasOwnProperty.call(prefs, name) ? !!prefs[name] : !!defaults[name];
        input.addEventListener('change', function () {
            prefs[this.id] = this.checked;
            localStorage.setItem(key, JSON.stringify(prefs));
            apply();
        });
    });
    apply();
}());
</script>