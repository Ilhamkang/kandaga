<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php'; kandagaAuthorize(true);
/**
 * daftar-pengguna.php
 * Halaman Manajemen Pengguna terintegrasi dengan Master Config
 */
require_once '../config.php';

// 1. SESSION GUARD
if (empty($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../login.php"); // Sesuaikan jika perlu
    exit();
}

// 2. QUERY DATA
// Menggunakan $conn dari config.php
$query = "SELECT * FROM users ORDER BY id DESC";
$result = $conn->query($query);
?>

<?php kandagaPageHeader('Daftar Pengguna', 'fa-users', 'Kelola akun, peran, dan status verifikasi pengguna Kandaga.', '<a href="index.php?page=pendaftaran-user" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Tambah Pengguna</a>'); ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <ul class="nav nav-tabs card-header-tabs" id="userTab">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#semua">Semua</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pending">Pending</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#verified">Verified</a></li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelUser">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>User</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Peran</th>
                        <th width="150" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = $result->fetch_assoc()) :
                        $status = $row['status_verifikasi'] ?? 'pending';
                    ?>
                    <tr class="user-row" data-status="<?php echo htmlspecialchars($status); ?>">
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($row['email'] ?? '-'); ?></small>
                        </td>
                        <td class="text-center">
                            <?php
                            $badge = ($status == 'verified') ? 'bg-success' : (($status == 'pending') ? 'bg-warning text-dark' : 'bg-danger');
                            echo '<span class="badge '.$badge.' text-capitalize">'.$status.'</span>';
                            ?>
                        </td>
                        <td class="text-center text-capitalize"><?php echo htmlspecialchars($row['role']); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-light text-info kandaga-action-btn btn-detail" data-bs-toggle="tooltip" title="Lihat" aria-label="Lihat pengguna"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                    data-role="<?php echo htmlspecialchars($row['role']); ?>"
                                    data-status="<?php echo htmlspecialchars($status); ?>">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light text-danger kandaga-action-btn btn-hapus" data-bs-toggle="tooltip" title="Hapus" aria-label="Hapus pengguna"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-user="<?php echo htmlspecialchars($row['username']); ?>">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formVerifikasi" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kelola Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modal_user_id">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" id="modal_username" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tentukan Peran</label>
                    <select name="role" class="form-select" id="modal_role">
                        <option value="kontributor">Kontributor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status Verifikasi</label>
                    <select name="status" class="form-select" id="modal_status">
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalHapusUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <h5>Konfirmasi Hapus</h5>
                <p>Yakin menghapus user <b id="namaUser"></b>?</p>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="confirmHapus" class="btn btn-danger">Ya, Hapus!</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. TAB FILTERING
    $('#userTab a').on('click', function (e) {
        e.preventDefault();
        bootstrap.Tab.getOrCreateInstance(this).show();
        let filter = $(this).attr('href').replace('#', '');
        $('.user-row').show();
        if (filter !== 'semua') {
            $('.user-row').not('[data-status="' + filter + '"]').hide();
        }
    });

    // 2. OPEN DETAIL MODAL
    $('.btn-detail').on('click', function() {
        $('#modal_user_id').val($(this).data('id'));
        $('#modal_username').val($(this).data('username'));
        $('#modal_role').val($(this).data('role'));
        $('#modal_status').val($(this).data('status'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailUser')).show();
    });

    // 3. SUBMIT VERIFIKASI
    $('#formVerifikasi').on('submit', function(e) {
        e.preventDefault();
        $.post('actions/proses-verifikasi-user.php', $(this).serialize(), function(res) {
            if(res.trim() == 'success') location.reload();
            else kandagaDialog.show({title: 'Gagal', message: 'Gagal memproses data: ' + res, icon: 'error'});
        });
    });

// 4. DELETE LOGIC
    let userId = null;
    $(".btn-hapus").on('click', function() {
        userId = $(this).data('id');
        $("#namaUser").text($(this).data('user'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHapusUser')).show();
    });

    $("#confirmHapus").on('click', function() {
    // Tambahkan '?v=' + Date.now() untuk memaksa browser tidak memakai cache
    $.ajax({
        url: 'actions/proses-hapus-user.php?v=' + Date.now(),
        type: 'POST',
        data: { id: userId },
        success: function(res) {
            console.log("Respon Server: " + res);
            if (res.trim() === "success") {
                location.reload();
            } else {
                kandagaDialog.show({title: 'Gagal', message: "Gagal menghapus: " + res, icon: 'error'});
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + error);
            kandagaDialog.show({title: 'Error', message: "Error: " + status + ". Cek apakah file benar-benar ada di folder admin/actions/", icon: 'error'});
        }
    });
});
});
</script>
