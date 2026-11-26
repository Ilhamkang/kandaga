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
?>

<div class="container-fluid py-4">
    <h3 class="mb-4">Manajemen Data Master</h3>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><a class="nav-link <?= $active_tab == 'aksara' ? 'active' : '' ?>" data-toggle="tab" href="#aksara">Aksara</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab == 'alas' ? 'active' : '' ?>" data-toggle="tab" href="#alas">Alas Tulis</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab == 'bahasa' ? 'active' : '' ?>" data-toggle="tab" href="#bahasa">Bahasa</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab == 'lokasi' ? 'active' : '' ?>" data-toggle="tab" href="#lokasi">Lokasi</a></li>
    </ul>

    <div class="tab-content bg-white p-3 border border-top-0">
        <div class="tab-pane fade <?= $active_tab == 'aksara' ? 'show active' : '' ?>" id="aksara">
            <button class="btn btn-primary mb-3" onclick="resetAksara()">Tambah Aksara</button>
            <table class="table table-bordered">
                <thead><tr><th>Nama</th><th>Tahun</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $res = $conn->query("SELECT * FROM tb_aksara"); while($r = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nama_aksara'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['tahun_ditemukan'] ?? '') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editAksara(<?= $r['id_aksara'] ?>, '<?= addslashes($r['nama_aksara']) ?>', '<?= addslashes($r['deskripsi']) ?>', '<?= $r['tahun_ditemukan'] ?>')">Edit</button>
                            <a href="?page=manage-referensi&del_aksara=<?= $r['id_aksara'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-danger" >Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade <?= $active_tab == 'alas' ? 'show active' : '' ?>" id="alas">
            <button class="btn btn-primary mb-3" onclick="resetAlas()">Tambah Alas</button>
            <table class="table table-bordered">
                <thead><tr><th>Nama</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $res = $conn->query("SELECT * FROM tb_alas_tulis"); while($r = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nama_alas_tulis'] ?? '') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editAlas(<?= $r['id_alas_tulis'] ?>, '<?= addslashes($r['nama_alas_tulis']) ?>')">Edit</button>
                            <a href="?page=manage-referensi&del_alas=<?= $r['id_alas_tulis'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-danger" >Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade <?= $active_tab == 'bahasa' ? 'show active' : '' ?>" id="bahasa">
            <button class="btn btn-primary mb-3" onclick="resetBahasa()">Tambah Bahasa</button>
            <table class="table table-bordered">
                <thead><tr><th>Nama</th><th>ISO</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $res = $conn->query("SELECT * FROM tb_bahasa"); while($r = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nama_bahasa'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['kode_iso'] ?? '') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editBahasa(<?= $r['id_bahasa'] ?>, '<?= addslashes($r['nama_bahasa']) ?>', '<?= addslashes($r['kode_iso']) ?>', '<?= addslashes($r['deskripsi']) ?>')">Edit</button>
                            <a href="?page=manage-referensi&del_bahasa=<?= $r['id_bahasa'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-danger" >Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade <?= $active_tab == 'lokasi' ? 'show active' : '' ?>" id="lokasi">
            <button class="btn btn-primary mb-3" onclick="resetLokasi()">Tambah Lokasi</button>
            <table class="table table-bordered">
                <thead><tr><th>Foto</th><th>Nama</th><th>Negara</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $res = $conn->query("SELECT * FROM tb_lokasi"); while($r = $res->fetch_assoc()): ?>
                    <tr>
                        <td><img src="../assets/images/<?= !empty($r['foto_lokasi']) ? $r['foto_lokasi'] : 'msc-placeholder.png' ?>" width="60"></td>
                        <td><?= htmlspecialchars($r['nama_tempat'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['negara'] ?? '') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editLokasi(<?= $r['id_lokasi'] ?>, '<?= addslashes($r['nama_tempat']) ?>', '<?= addslashes($r['negara']) ?>', '<?= addslashes($r['alamat']) ?>', '<?= addslashes($r['deskripsi']) ?>', '<?= $r['foto_lokasi'] ?>')">Edit</button>
                            <a href="?page=manage-referensi&del_lokasi=<?= $r['id_lokasi'] ?>" data-kandaga-delete="1" class="btn btn-sm btn-danger" >Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAksara"><div class="modal-dialog"><form method="POST" class="modal-content"><div class="modal-header"><h5>Aksara</h5></div><div class="modal-body"><input type="hidden" name="id_aksara" id="id_aksara"><div class="form-group"><label>Nama</label><input type="text" name="nama_aksara" id="nama_aksara" class="form-control" required></div><div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="deskripsi_aksara" class="form-control"></textarea></div><div class="form-group"><label>Tahun</label><input type="number" name="tahun_ditemukan" id="tahun_ditemukan" class="form-control"></div></div><div class="modal-footer"><button type="submit" name="simpan_aksara" class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="modalAlas"><div class="modal-dialog"><form method="POST" class="modal-content"><div class="modal-header"><h5>Alas Tulis</h5></div><div class="modal-body"><input type="hidden" name="id_alas_tulis" id="id_alas"><div class="form-group"><label>Nama</label><input type="text" name="nama_alas_tulis" id="nama_alas" class="form-control" required></div></div><div class="modal-footer"><button type="submit" name="simpan_alas" class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="modalBahasa"><div class="modal-dialog"><form method="POST" class="modal-content"><div class="modal-header"><h5>Bahasa</h5></div><div class="modal-body"><input type="hidden" name="id_bahasa" id="id_bahasa"><div class="form-group"><label>Nama</label><input type="text" name="nama_bahasa" id="nama_bahasa" class="form-control" required></div><div class="form-group"><label>Kode ISO</label><input type="text" name="kode_iso" id="kode_iso" class="form-control"></div><div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="deskripsi_bahasa" class="form-control"></textarea></div></div><div class="modal-footer"><button type="submit" name="simpan_bahasa" class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="modalLokasi"><div class="modal-dialog"><form method="POST" enctype="multipart/form-data" class="modal-content"><div class="modal-header"><h5>Lokasi</h5></div><div class="modal-body"><input type="hidden" name="id_lokasi" id="id_lokasi"><input type="hidden" name="foto_lama" id="foto_lama"><div class="text-center mb-3"><img id="preview_foto" src="" style="max-width:150px; display:none;"></div><div class="form-group"><label>Nama</label><input type="text" name="nama_tempat" id="nama_tempat" class="form-control" required></div><div class="form-group"><label>Negara</label><input type="text" name="negara" id="negara" class="form-control"></div><div class="form-group"><label>Alamat</label><textarea name="alamat" id="alamat" class="form-control"></textarea></div><div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="deskripsi_lokasi" class="form-control"></textarea></div><div class="form-group"><label>Foto</label><input type="file" name="foto_lokasi" class="form-control"></div></div><div class="modal-footer"><button type="submit" name="simpan_lokasi" class="btn btn-primary">Simpan</button></div></form></div></div>

<script>
function resetAksara() { $('#id_aksara').val(''); $('#modalAksara').modal('show'); }
function editAksara(id, n, d, t) { $('#id_aksara').val(id); $('#nama_aksara').val(n); $('#deskripsi_aksara').val(d); $('#tahun_ditemukan').val(t); $('#modalAksara').modal('show'); }

function resetAlas() { $('#id_alas').val(''); $('#modalAlas').modal('show'); }
function editAlas(id, n) { $('#id_alas').val(id); $('#nama_alas').val(n); $('#modalAlas').modal('show'); }

function resetBahasa() { $('#id_bahasa').val(''); $('#modalBahasa').modal('show'); }
function editBahasa(id, n, k, d) { $('#id_bahasa').val(id); $('#nama_bahasa').val(n); $('#kode_iso').val(k); $('#deskripsi_bahasa').val(d); $('#modalBahasa').modal('show'); }

function resetLokasi() { $('#id_lokasi').val(''); $('#preview_foto').hide(); $('#modalLokasi').modal('show'); }
function editLokasi(id, n, ng, al, des, ft) { 
    $('#id_lokasi').val(id); $('#nama_tempat').val(n); $('#negara').val(ng); $('#alamat').val(al); $('#deskripsi_lokasi').val(des); $('#foto_lama').val(ft); 
    if(ft) { $('#preview_foto').attr('src', '../assets/images/'+ft).show(); } else { $('#preview_foto').hide(); }
    $('#modalLokasi').modal('show'); 
}
</script>