<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') kandagaCsrf();

require_once __DIR__ . '/../config/bootstrap.php';
if (isset($_POST['delete'])) {
    $id_hapus = (int)$_POST['delete'];

    try {
        $stmt = $conn->prepare("DELETE FROM tb_kurasi_koleksi WHERE id = ?");
        $stmt->bind_param("i", $id_hapus);

        if ($stmt->execute()) {
            $message = "✅ Rekaman kurasi koleksi berhasil dihapus dari sistem!";
            $message_type = "success";
        } else {
            $message = "❌ Gagal menghapus rekaman data.";
            $message_type = "danger";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "Galat Sistem Basis Data: " . $e->getMessage();
        $message_type = "danger";
    }
}

// 3. PROSES SIMPAN DATA (Perbaikan Prepared Statements untuk Penambahan Data)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_simpan'])) {
    $id_lokasi = (int)$_POST['id_lokasi'];
    $id_user = (int)$_POST['id_user'];
    $posisi = trim($_POST['posisi_kurator']);
    $tanggal = trim($_POST['tanggal_mulai']);
    $keterangan = trim($_POST['keterangan']);

    if (!empty($id_lokasi) && !empty($id_user) && !empty($posisi)) {
        $stmt = $conn->prepare("INSERT INTO tb_kurasi_koleksi (id_lokasi, id_user, posisi_kurator, tanggal_mulai, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $id_lokasi, $id_user, $posisi, $tanggal, $keterangan);

        if ($stmt->execute()) {
            $message = "✅ Data manajemen kurasi koleksi baru berhasil disimpan!";
            $message_type = "success";
        } else {
            $message = "❌ Gagal menyimpan data kurasi: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        $message = "⚠️ Mohon lengkapi seluruh kolom wajib form kurasi.";
        $message_type = "warning";
    }
}

// 4. PENGAMBILAN DATA MASTER (Query Dipisahkan untuk Mencegah Kebocoran Pointer Tab)
$lokasi_query = $conn->query("SELECT id_lokasi, nama_tempat FROM tb_lokasi ORDER BY nama_tempat ASC");
$user_query   = $conn->query("SELECT id, nama FROM users ORDER BY nama ASC");
$tabel_query  = $conn->query("
    SELECT k.*, l.nama_tempat, u.nama
    FROM tb_kurasi_koleksi k
    JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi
    JOIN users u ON k.id_user = u.id
    ORDER BY k.tanggal_mulai DESC
");
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h3 class="page-title fw-bold mb-0">Manajemen Kurasi Koleksi</h3>
            <p class="text-muted small mb-0">Pengaturan data kurator, penempatan lokasi preservasi, dan dokumentasi naskah.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs border-bottom-0" id="kurasiTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active fw-bold" id="form-tab" data-bs-toggle="tab" href="#form" role="tab">
                <i class="fas fa-plus-circle text-primary me-1"></i> Tambah Kurasi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold" id="list-tab" data-bs-toggle="tab" href="#list" role="tab">
                <i class="fas fa-folder-open text-secondary me-1"></i> Daftar Transaksi Kurasi
            </a>
        </li>
    </ul>

    <div class="tab-content card border-0 shadow-sm p-4 rounded-bottom" id="kurasiTabContent" style="border-top-left-radius: 0;">

        <div class="tab-pane fade show active" id="form" role="tabpanel">
            <form method="POST" action="index.php?page=form-kurasi-koleksi">
                <input type="hidden" name="proses_simpan" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small text-secondary">Lokasi Penempatan Koleksi</label>
                        <select name="id_lokasi" class="form-select text-sm" required>
                            <option value="">-- Pilih Lembaga / Tempat Penyimpanan --</option>
                            <?php if ($lokasi_query): while ($row = $lokasi_query->fetch_assoc()): ?>
                                <option value="<?= $row['id_lokasi'] ?>"><?= htmlspecialchars($row['nama_tempat']) ?></option>
                            <?php endwhile; $lokasi_query->data_seek(0); /* Reset Pointer */ endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small text-secondary">Petugas / Kurator Penanggung Jawab</label>
                        <select name="id_user" class="form-select text-sm" required>
                            <option value="">-- Pilih Akun Anggota Kurator --</option>
                            <?php if ($user_query): while ($row = $user_query->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama']) ?></option>
                            <?php endwhile; $user_query->data_seek(0); /* Reset Pointer */ endif; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small text-secondary">Posisi / Tingkat Jabatan Kurasi</label>
                        <input type="text" name="posisi_kurator" class="form-control text-sm" placeholder="Contoh: Kurator Utama, Filolog Madya" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small text-secondary">Tanggal Mulai Tugas</label>
                        <input type="date" name="tanggal_mulai" class="form-control text-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="fw-bold small text-secondary">Keterangan / Catatan Tambahan Laporan</label>
                    <textarea name="keterangan" class="form-control text-sm" rows="3" placeholder="Tulis catatan deskriptif mengenai kondisi fisik ruang simpan atau cakupan digitalisasi..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> Simpan Data Kurasi
                </button>
            </form>
        </div>

        <div class="tab-pane fade" id="list" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Lokasi Koleksi</th>
                            <th>Nama Kurator</th>
                            <th>Jabatan Posisi</th>
                            <th>Tanggal Penugasan</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tabel_query && $tabel_query->num_rows > 0): ?>
                            <?php while ($row = $tabel_query->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_tempat']) ?></td>
                                <td><i class="fas fa-user-circle text-muted me-1"></i> <?= htmlspecialchars($row['nama']) ?></td>
                                <td><span class="badge bg-info py-1 px-2 fw-normal"><?= htmlspecialchars($row['posisi_kurator']) ?></span></td>
                                <td class="text-secondary small"><?= date('d-m-Y', strtotime($row['tanggal_mulai'])) ?></td>
                                <td class="text-center">
                                    <a href="index.php?page=form-kurasi-koleksi&delete=<?= $row['id'] ?>" data-kandaga-delete="1"
                                       class="btn btn-sm btn-light shadow-sm text-danger"

                                       title="Hapus Data">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open mb-2" style="font-size: 2.5rem; opacity: 0.3;"></i><br>Belum ada transaksi rekaman kurasi koleksi.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>