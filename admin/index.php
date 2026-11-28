<?php
require_once __DIR__ . '/../config/bootstrap.php';
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/version.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/request.php';
if (!defined('KANDAGA_GOAH_PANEL')) define('KANDAGA_GOAH_PANEL', true);
// Included modules can drop duplicate document/container wrappers while direct requests keep them.
if (!defined('KANDAGA_ADMIN_SHELL')) define('KANDAGA_ADMIN_SHELL', true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') kandagaCsrf();
$page = $_GET['page'] ?? 'dashboard';
if ($page === 'catalog-importer') { $page = 'metadata-importer'; $_GET['engine'] = 'catalog'; }
if (in_array($page, ['tampil-lokasi', 'form-lokasi', 'entri_katalog'], true)) $page = 'manage-referensi';

// Shared styles are loaded once by the shell; page styles keep their original
// cascade position just before the token bridge in admin-theme.css.
$adminSharedStyles = [
    'admin/assets/css/admin-form.css',
    'admin/assets/css/admin-log.css',
    'admin/assets/css/admin-importer.css',
    'admin/assets/css/admin-dashboard.css',
    'admin/assets/css/admin-statistik.css',
    'admin/assets/css/admin-settings.css',
    'admin/assets/css/admin-user-status.css',
];
$adminPageStyles = [
    'inbox' => ['admin/assets/css/style-inbox.css'],
    'feedback-naskah' => ['admin/assets/css/style-inbox.css', 'admin/assets/css/feedback-naskah.css'],
    'update' => ['admin/assets/css/update-center.css'],
];

// Maintenance Mode Check
if (file_exists(ROOT_PATH . '/maintenance.lock') && ($page !== 'update')) {
    die("
    <div style='text-align:center; padding: 50px; font-family: sans-serif;'>
        <h1>Sistem Sedang Update</h1>
        <p>Mohon maaf, sistem sedang dalam pemeliharaan. Silakan kembali dalam beberapa menit.</p>
    </div>");
}

// 3. LOGIKA CEK UPDATE (CACHE 6 JAM)
$cache_file = __DIR__ . '/../update_cache.json';
$update_available = false;
$local_file = __DIR__ . '/../version.json';

if (file_exists($cache_file) && (time() - filemtime($cache_file) < 21600)) {
    $cache_data = json_decode(file_get_contents($cache_file), true);
    $update_available = $cache_data['update_available'] ?? false;
} elseif (!file_exists(ROOT_PATH . '/maintenance.lock')) {
    $local_v = kandagaVersion();
    $ch = curl_init("https://raw.githubusercontent.com/Ilhamkang/kandaga/gh-pages/version.json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $remote_data = json_decode($response, true);
    $remote_v = $remote_data['version'] ?? $local_v;
    $update_available = version_compare($remote_v, $local_v, '>');
    file_put_contents($cache_file, json_encode(['update_available' => $update_available, 'time' => time()]));
}

// 4. Data User & Halaman
$role_user = $_SESSION['role'] ?? 'kontributor';
$current_user = !empty($_SESSION['nama']) ? trim($_SESSION['nama']) : 'User';
$page = $_GET['page'] ?? 'dashboard';
if ($page === 'catalog-importer') { $page = 'metadata-importer'; $_GET['engine'] = 'catalog'; }

$allowed_pages = [
    'dashboard', 'perubahan', 'tampil-naskah', 'form-input', 'tampil-lokasi', 'cek_sesi', 'test-download',
    'form-edit', 'form-edit_lokasi', 'form-lokasi', 'entri_katalog', 'update', 'metadata-importer', 'catalog-importer',
    'profil-user', 'feedback-naskah', 'inbox', 'daftar-pustaka', 'form-bibliografi', 'form-pendaftaran-user',
    'status-pengguna', 'daftar-pengguna', 'log-user',
    'statistik', 'form-kabar', 'daftar-post', 'form-edit-kabar',
    'form-kurasi-koleksi', 'manage-referensi', 'pengaturan'
];

$admin_only_pages = [
    'daftar-pengguna', 'cek_sesi', 'update','test-download',
    'status-pengguna', 'log-user', 'statistik', 'feedback-naskah', 'inbox', 'form-pendaftaran-user', 'metadata-importer', 'catalog-importer',
    'form-kabar', 'daftar-post', 'form-edit-kabar', 'form-kurasi-koleksi', 'manage-referensi', 'pengaturan'
];

if (!in_array($page, $allowed_pages)) { $page = 'dashboard'; }

if (in_array($page, $admin_only_pages, true)) kandagaAuthorize(true);

$display_name = $current_user;
$foto_user = 'default-user.jpg';
if (!empty($_SESSION['username'])) {
    $stmt = $conn->prepare("SELECT nama, foto FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        $display_name = $res['nama'];
        $path_cek = ROOT_PATH . "/assets/images/user/" . $res['foto'];
        if (!empty($res['foto']) && file_exists($path_cek)) { $foto_user = $res['foto']; }
    }
    $stmt->close();
}
$path_foto_user = BASE_URL . "/assets/images/user/" . $foto_user;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goah KANDAGA | Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/style-index-admin.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/select2.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/goah-theme.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
    <script>window.KANDAGA_CSRF = <?= json_encode($_SESSION['csrf_token']) ?>;</script>
    <script src="<?= BASE_URL ?>/admin/assets/js/request-security.js"></script>
    <script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= BASE_URL ?>/admin/assets/js/select2.min.js"></script>
    <script src="<?= BASE_URL ?>/admin/assets/js/tinymce/tinymce.min.js"></script>
    <script src="<?= BASE_URL ?>/admin/assets/js/sweetalert2.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/mobile-detect.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php include __DIR__ . '/../includes/module-head.php'; ?>
<?php foreach ($adminSharedStyles as $adminStyle): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/<?= htmlspecialchars($adminStyle, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
<?php foreach ($adminPageStyles[$page] ?? [] as $adminPageStyle): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/<?= htmlspecialchars($adminPageStyle, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin-theme.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body class="kandaga-module kandaga-admin-shell">
    <script>
        (function() {
            if (localStorage.getItem('sidebarState') === 'mini') {
                document.body.classList.add('sidebar-mini');
            }
        })();
    </script>

    <nav class="navbar navbar-expand navbar-light fixed-top shadow-sm bg-white" style="z-index: 1050;">
        <div class="container-fluid">
            <button class="btn btn-link text-dark me-3" id="sidebarToggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="<?= BASE_URL ?>/assets/images/favicon.png" height="35" alt="Logo">
                <span class="ms-2 fw-bold">KANDAGA <span class="text-primary">GOAH</span></span>
            </a>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="me-2 text-dark small fw-bold"><?= htmlspecialchars($display_name); ?></span>
                        <img class="img-profile rounded-circle border" src="<?= $path_foto_user; ?>" style="width: 32px; height: 32px; object-fit: cover;">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userDropdown" style="border-radius: 8px;">
                        <a class="dropdown-item py-2" href="index.php?page=profil-user"><i class="fa-solid fa-user me-2 text-muted"></i> Profil Saya</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="logout()"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="d-flex" style="margin-top: 60px;">
        <nav id="sidebar" class="shadow">
            <div class="pt-4">
                <a href="<?= BASE_URL ?>/" target="_blank" class="nav-link"><i class="fa-solid fa-globe"></i> <span>Web Katalog</span></a>
                <a href="index.php?page=dashboard" class="nav-link <?= ($page == 'dashboard') ? 'active' : ''; ?>"><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="index.php?page=perubahan" class="nav-link <?= ($page == 'perubahan') ? 'active' : ''; ?>"><i class="fa-solid fa-clock-rotate-left"></i> <span>Aktivitas Terbaru</span></a>

                <?php if ($role_user === 'admin'): ?>
                    <?php
                    $unread = 0;
                    $q_inbox = $conn->query("SELECT COUNT(*) as total FROM tb_inbox WHERE status = 'unread'");
                    if ($q_inbox) { $unread = $q_inbox->fetch_assoc()['total']; }
                    ?>
                    <a href="index.php?page=inbox" class="nav-link <?= ($page == 'inbox') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-envelope"></i> <span>Inbox Feedback</span>
                        <?php if ($unread > 0): ?><span class="badge bg-danger ms-auto" style="font-size: 10px;"><?= $unread ?></span><?php endif; ?>
                    </a>
                    <?php
                    require_once __DIR__ . '/../includes/manuscript-feedback.php';
                    $feedbackUnread = 0;
                    try {
                        if (kandagaFeedbackReady($conn)) $feedbackUnread = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_naskah_feedback WHERE status='unread'")->fetch_assoc()['total'];
                    } catch (Throwable $e) { error_log('Kandaga feedback sidebar: ' . $e->getMessage()); }
                    ?>
                    <a href="index.php?page=feedback-naskah" class="nav-link <?= ($page === 'feedback-naskah') ? 'active' : ''; ?>"><i class="fa-solid fa-comment-dots"></i><span>Feedback Naskah</span><span id="feedbackNavCount" class="badge bg-danger ms-auto <?= $feedbackUnread ? '' : 'd-none' ?>"><?= $feedbackUnread ?></span></a>
                <?php endif; ?>

                <div class="sidebar-heading">Koleksi Naskah</div>
                <hr class="sidebar-divider">
                <a href="index.php?page=tampil-naskah" class="nav-link <?= ($page == 'tampil-naskah') ? 'active' : ''; ?>"><i class="fa-solid fa-database"></i> <span>Daftar Naskah</span></a>
                <a href="index.php?page=form-input" class="nav-link <?= ($page == 'form-input') ? 'active' : ''; ?>"><i class="fa-solid fa-square-plus"></i> <span>Tambah Naskah</span></a>

                <hr class="sidebar-divider">
                <?php if ($role_user === 'admin'): ?>
                <div class="sidebar-heading">Publikasi Kabar</div>
                <a href="index.php?page=daftar-post" class="nav-link <?= ($page == 'daftar-post') ? 'active' : ''; ?>"><i class="fa-solid fa-newspaper"></i> <span>Daftar Postingan</span></a>
                <a href="index.php?page=form-kabar" class="nav-link <?= ($page == 'form-kabar') ? 'active' : ''; ?>"><i class="fa-solid fa-pen-to-square"></i> <span>Buat Postingan</span></a>
                <?php endif; ?>

                <hr class="sidebar-divider">
                <div class="sidebar-heading">Manajemen Data</div>
                <a href="index.php?page=daftar-pustaka" class="nav-link <?= ($page == 'daftar-pustaka') ? 'active' : ''; ?>"><i class="fa-solid fa-book-bookmark"></i> <span>Daftar Pustaka</span></a>
                <a href="index.php?page=form-bibliografi" class="nav-link <?= ($page == 'form-bibliografi') ? 'active' : ''; ?>"><i class="fa-solid fa-book-medical"></i> <span>Tambah Bibliografi</span></a>
                <?php if ($role_user === 'admin'): ?>
                <a id="metadataImporterNav" href="index.php?page=metadata-importer" class="nav-link <?= ($page === 'metadata-importer') ? 'active' : ''; ?>"><i class="fa-solid fa-file-import"></i> <span>Metadata Importer</span></a>
                <a href="index.php?page=manage-referensi" class="nav-link <?= ($page == 'manage-referensi') ? 'active' : ''; ?>"><i class="fa-solid fa-scroll"></i> <span>Preferensi Katalog</span></a>
                <a href="index.php?page=form-kurasi-koleksi" class="nav-link <?= ($page == 'form-kurasi-koleksi') ? 'active' : ''; ?>"><i class="fa-solid fa-scroll"></i> <span>Kurator</span></a>
                <a href="index.php?page=pengaturan" class="nav-link <?= ($page == 'pengaturan') ? 'active' : ''; ?>"><i class="fa-solid fa-sliders"></i> <span>Pengaturan</span></a>
                <?php endif; ?>


                <div class="sidebar-heading">Manajemen Pengguna</div>
                <hr class="sidebar-divider">
                <a href="index.php?page=profil-user" class="nav-link <?= ($page == 'profil-user') ? 'active' : ''; ?>"><i class="fa-solid fa-user-gear"></i> <span>Pengaturan Profil</span></a>
                <?php if ($role_user === 'admin'): ?>
                <a href="index.php?page=daftar-pengguna" class="nav-link <?= ($page == 'daftar-pengguna') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> <span>Daftar Pengguna</span></a>
                <a href="index.php?page=form-pendaftaran-user" class="nav-link <?= ($page == 'pendaftaran-user') ? 'active' : ''; ?>"><i class="fa-solid fa-user-plus"></i> <span>Tambah Pengguna</span></a>
                <a href="index.php?page=status-pengguna" class="nav-link <?= ($page == 'status-pengguna') ? 'active' : ''; ?>"><i class="fa-solid fa-user-plus"></i> <span>Monitor Status</span></a>
                <a href="index.php?page=log-user" class="nav-link <?= ($page == 'log-user') ? 'active' : ''; ?>"><i class="fa-solid fa-clock-rotate-left"></i> <span>Log Aktivitas</span></a>

                <div class="sidebar-heading">Properti Sistem</div>
                <hr class="sidebar-divider">
                <a href="index.php?page=statistik" class="nav-link <?= ($page == 'statistik') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> <span>Statistik Katalog</span></a>
                <a href="index.php?page=update" class="nav-link <?= ($page == 'update') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-cloud-arrow-down"></i> <span>Pusat Pembaruan</span>
                    <?php if ($update_available): ?>
                        <span class="badge bg-warning text-dark ms-auto" style="font-size: 10px; animation: blink 1s infinite;">baru</span>
                    <?php endif; ?>
                </a>

                <?php endif; ?>
            </div>
        </nav>

        <main id="main-wrapper" class="w-100">
            <div class="container-fluid" id="kontenUtama">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Goah</a></li>
                        <li class="breadcrumb-item active text-capitalize"><?= str_replace('_', ' ', str_replace('-', ' ', $page)); ?></li>
                    </ol>
                </nav>
                <div id="adminModule" class="admin-module-surface" data-page="<?= htmlspecialchars($page, ENT_QUOTES, 'UTF-8') ?>">
                    <?php
                    $file_path = __DIR__ . '/' . $page . ".php";
                    if (file_exists($file_path)) {
                        include($file_path);
                    } else {
                        echo "<div class='card border-0 shadow-sm p-5 text-center'><h4>Modul '$page' tidak ditemukan.</h4></div>";
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>






<script>
    $(document).ready(function() {
        // Keep collapsed navigation icons uniform and expose their labels to
        // mouse, keyboard, and assistive-technology users.
        (function initSidebarTooltips() {
            var $links = $('#sidebar .nav-link');
            $links.each(function () {
                var $link = $(this);
                var label = $link.find('span').not('.badge').first().text().trim();
                if (!label) label = $link.clone().children().remove().end().text().trim();
                if (label) $link.attr({'aria-label': label, 'data-tooltip': label, 'title': label});
            });

            var tooltip;
            function hideTooltip() {
                if (tooltip) { tooltip.remove(); tooltip = null; }
            }
            function showTooltip(link) {
                hideTooltip();
                if (!document.body.classList.contains('sidebar-mini') || window.innerWidth <= 768) return;
                var label = link.getAttribute('data-tooltip');
                if (!label) return;
                tooltip = document.createElement('div');
                tooltip.className = 'kandaga-sidebar-tooltip';
                tooltip.textContent = label;
                document.body.appendChild(tooltip);
                var rect = link.getBoundingClientRect();
                var left = rect.right + 8;
                var top = rect.top + (rect.height - tooltip.offsetHeight) / 2;
                tooltip.style.left = Math.min(left, window.innerWidth - tooltip.offsetWidth - 8) + 'px';
                tooltip.style.top = Math.max(8, Math.min(top, window.innerHeight - tooltip.offsetHeight - 8)) + 'px';
            }
            $links.on('mouseenter focusin', function () { showTooltip(this); });
            $links.on('mouseleave focusout', hideTooltip);
            $('#sidebarToggle').on('click', hideTooltip);
            $(window).on('resize scroll', hideTooltip);
        }());

        // Toggle Sidebar
        $('#sidebarToggle').on('click', function () {
            if ($(window).width() <= 768) {
                $('body').toggleClass('sidebar-open');
            } else {
                $('body').toggleClass('sidebar-mini');
                // Simpan preferensi ke LocalStorage
                if ($('body').hasClass('sidebar-mini')) {
                    localStorage.setItem('sidebarState', 'mini');
                } else {
                    localStorage.setItem('sidebarState', 'full');
                }
            }
        });

        // Initialize plugins
        if (typeof applySelect2Statis !== 'function') {
            window.applySelect2Statis = function() {
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select-clean').not('.select2-hidden-accessible').each(function () {
                        var $select = $(this);
                        $select.select2({
                            placeholder: $select.data('placeholder') || "Pilih...",
                            allowClear: true,
                            width: '100%',
                            matcher: function (params, data) {
                                if ($.trim(params.term || '') === '') return data;
                                if (!data.text) return null;
                                var term = params.term.toLocaleLowerCase();
                                return data.text.toLocaleLowerCase().indexOf(term) > -1 ? data : null;
                            }
                        });
                    });
                }
            };
        }
        if (typeof applySelect2BibliografiAjax !== 'function') {
            window.applySelect2BibliografiAjax = function() {
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select-ajax-bib').not('.select2-hidden-accessible').each(function() {
                        var $this = $(this);
                        var kategoriTarget = $this.data('kategori');
                        $this.select2({
                            placeholder: "🔍 Cari rujukan...", width: '100%', minimumInputLength: 2, allowClear: true,
                            ajax: { url: '<?= BASE_URL ?>/admin/actions/ajax-search-bibliografi.php', dataType: 'json', delay: 250, data: function (params) { return { q: params.term, kategori: kategoriTarget }; }, processResults: function (data) { return { results: data }; }, cache: true }
                        });
                    });
                }
            };
        }
        if (typeof applySelect2Statis === 'function') applySelect2Statis();
        if (typeof applySelect2BibliografiAjax === 'function') applySelect2BibliografiAjax();
    });

    function logout() {
        Swal.fire({ title: 'Keluar Sistem?', text: "Yakin ingin keluar?", icon: 'warning', showCancelButton: true, confirmButtonColor: '#0f766e', confirmButtonText: 'Ya, Keluar' }).then((result) => { if (result.isConfirmed) { window.location.href = "logout.php"; } });
    }
</script>

<script src="<?= BASE_URL ?>/admin/assets/js/goah-theme.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
