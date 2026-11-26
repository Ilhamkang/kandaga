<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__.'/includes/request.php';kandagaAuthorize();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan sesi dimulai dan koneksi tersedia
if (session_status() === PHP_SESSION_NONE) if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) die("Koneksi database tidak ditemukan.");

// --- 1. SETUP USER ---
$nama_sapaan  = $_SESSION['nama'] ?? 'Penyunting';
$role_user    = $_SESSION['role'] ?? 'kontributor';
$current_user = $_SESSION['username'] ?? '';
$is_admin     = (strtolower(trim($role_user)) === 'admin');

// --- 2. BACKEND ENGINE ---

// A. Statistik Personal (Menggunakan kolom baru kategori_aksi)
$stats = ['total_input' => 0, 'total_edit' => 0, 'total_terlibat' => 0];

// Query Input: Menggunakan kategori_aksi
$stmt_input = $conn->prepare("SELECT COUNT(*) FROM user_logs WHERE username = ? AND kategori_aksi = 'input'");
$stmt_input->bind_param("s", $current_user);
$stmt_input->execute();
$stats['total_input'] = (int)$stmt_input->get_result()->fetch_row()[0];
$stmt_input->close();

// Query Edit: Menggunakan kategori_aksi
$stmt_edit = $conn->prepare("SELECT COUNT(*) FROM user_logs WHERE username = ? AND kategori_aksi = 'edit'");
$stmt_edit->bind_param("s", $current_user);
$stmt_edit->execute();
$stats['total_edit'] = (int)$stmt_edit->get_result()->fetch_row()[0];
$stmt_edit->close();

// Query Item Terlibat: Menghitung jumlah Naskah UNIK yang disentuh
$stmt_terlibat = $conn->prepare("SELECT COUNT(DISTINCT id_target) FROM user_logs WHERE username = ? AND id_target IS NOT NULL");
$stmt_terlibat->bind_param("s", $current_user);
$stmt_terlibat->execute();
$stats['total_terlibat'] = (int)$stmt_terlibat->get_result()->fetch_row()[0];
$stmt_terlibat->close();

// B. Data Riwayat (Log) - Tetap filter login/logout
$sql_log = $is_admin
    ? "SELECT waktu, aksi, ip_address FROM user_logs WHERE aksi NOT LIKE '%login%' AND aksi NOT LIKE '%logout%' ORDER BY id_log DESC LIMIT 10"
    : "SELECT waktu, aksi, ip_address FROM user_logs WHERE username = ? AND aksi NOT LIKE '%login%' AND aksi NOT LIKE '%logout%' ORDER BY id_log DESC LIMIT 10";

$stmt_log = $conn->prepare($sql_log);
if (!$is_admin) $stmt_log->bind_param("s", $current_user);
$stmt_log->execute();
$dataset_log = $stmt_log->get_result();

// C. Data Naskah / Watchlist
$data_naskah = null;
if ($is_admin) {
    $data_naskah = $conn->query("SELECT k.id,k.judul,k.tanggal_input,k.penginput_awal,k.gambar,k.url_gambar_eksternal,k.kode_inventaris,l.nama_tempat FROM katalog k LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi ORDER BY k.tanggal_input DESC,k.id DESC LIMIT 1");
} else {
    $stmt_watch = $conn->prepare("SELECT k.id,k.judul,k.tanggal_input,k.penginput_awal,k.gambar,k.url_gambar_eksternal,k.kode_inventaris,l.nama_tempat FROM katalog k LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi WHERE k.penginput_awal = ? ORDER BY k.tanggal_input DESC,k.id DESC LIMIT 1");
    $stmt_watch->bind_param("s", $current_user);
    $stmt_watch->execute();
    $data_naskah = $stmt_watch->get_result();
}
$featuredId=0;
if($data_naskah->num_rows){$featuredId=(int)$data_naskah->fetch_assoc()['id'];$data_naskah->data_seek(0);}
$stmt_recent=$conn->prepare("SELECT k.id,k.judul,k.gambar,k.url_gambar_eksternal,k.tanggal_input,l.nama_tempat FROM katalog k LEFT JOIN tb_lokasi l ON l.id_lokasi=k.id_lokasi WHERE k.penginput_awal=? AND k.id<>? ORDER BY k.tanggal_input DESC,k.id DESC LIMIT 5");
$stmt_recent->bind_param('si',$current_user,$featuredId);$stmt_recent->execute();$recentPersonal=$stmt_recent->get_result();
$editedWatchlist=[];
if(!$is_admin){$watch=$conn->prepare('SELECT id,judul FROM katalog WHERE penyunting_terakhir=? AND (penginput_awal<>? OR penginput_awal IS NULL) ORDER BY tanggal_update DESC,id DESC');$watch->bind_param('ss',$current_user,$current_user);$watch->execute();$editedWatchlist=$watch->get_result()->fetch_all(MYSQLI_ASSOC);}
function kandagaDashboardImage($n){return kandagaImageUrl((array)$n);}
?>

<div class="kandaga-dashboard-context">
    <style>
        .metric-container { display: flex; gap: 20px; margin-bottom: 20px; }
        .metric-box { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; flex: 1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .workspace-split-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .workspace-block { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .block-title-bar { background: #f8fafc; padding: 15px; border-bottom: 1px solid #e2e8f0; font-weight: bold; }
        .naskah-node-item { transition: 0.2s; padding: 12px 15px; border-bottom: 1px solid #f1f5f9; }
        .naskah-node-item:hover { background: #f1f5f9; }
        .dashboard-welcome { position: relative; overflow: hidden; padding: 26px 30px; border: 0; border-radius: 18px; color: #fff; background: linear-gradient(115deg,#123b62 0%,#236d78 58%,#5a8f80 100%); box-shadow: 0 10px 24px rgba(18,59,98,.18); }
        .dashboard-welcome:after { content: ''; position: absolute; width: 190px; height: 190px; right: -45px; top: -75px; border-radius: 50%; background: rgba(255,255,255,.11); }
        .dashboard-welcome .welcome-kicker { text-transform: uppercase; letter-spacing: .12em; font-size: .7rem; font-weight: 700; opacity: .78; }
        .dashboard-welcome h2 { color: #fff!important; font-size: clamp(1.35rem,2.2vw,1.9rem); letter-spacing: -.02em; }
        .dashboard-welcome .role-badge { background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28); color: #fff; font-weight: 600; }
        .dashboard-welcome .welcome-clock { position: relative; z-index: 1; min-width: 145px; padding: 11px 14px; border-radius: 12px; text-align: right; background: rgba(0,0,0,.14); }
        .dashboard-welcome #realtime-clock { color: #fff!important; font-family: var(--kandaga-font-ui); font-size: 1.35rem; letter-spacing: .02em; }
        .dashboard-welcome #realtime-date { color: rgba(255,255,255,.8)!important; }
        @media (max-width: 768px) { .workspace-split-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="dashboard-welcome mb-4 d-flex justify-content-between align-items-center">
        <div class="position-relative" style="z-index:1">
            <div class="welcome-kicker mb-2"><i class="fa-solid fa-leaf me-1" aria-hidden="true"></i> Ruang kerja Kandaga</div>
            <h2 class="fw-bold mb-2">Sampurasun, <?= htmlspecialchars($nama_sapaan); ?>!</h2>
            <p class="mb-0 small">Wilujeng sumping di Goah. <span class="badge role-badge ms-1"><?= htmlspecialchars($role_user); ?></span></p>
        </div>
        <div class="welcome-clock d-none d-sm-block">
            <div id="realtime-clock">00:00:00</div>
            <div id="realtime-date" class="small text-muted fw-bold">Memuat...</div>
        </div>
    </div>

    <div class="metric-container">
        <div class="metric-box"><span>Item Terlibat</span><h4 class="m-0"><?= $stats['total_terlibat']; ?></h4></div>
        <div class="metric-box"><span>Input</span><h4 class="m-0" style="color: #0d47a1;"><?= $stats['total_input']; ?></h4></div>
        <div class="metric-box"><span>Edit</span><h4 class="m-0" style="color: #e65100;"><?= $stats['total_edit']; ?></h4></div>
    </div>

    <div class="workspace-split-grid">
        <div class="workspace-block">
            <div class="block-title-bar"><h5><i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Aktivitas</h5></div>
            <ul class="list-unstyled m-0">
                <?php while ($log = $dataset_log->fetch_assoc()):
                    $aksi = strtolower($log['aksi']);
                    $icon = strpos($aksi, 'tambah') !== false ? 'fa-plus-circle text-success' : (strpos($aksi, 'edit') !== false ? 'fa-pen-to-square text-primary' : 'fa-history text-secondary');
                ?>
                <li class="naskah-node-item d-flex align-items-center">
                    <i class="fa-solid <?= $icon; ?> me-3" style="width: 20px;"></i>
                    <div>
                        <div class="small fw-bold text-dark"><?= htmlspecialchars($log['aksi']); ?></div>
                        <small class="text-muted"><?= date('d M H:i', strtotime($log['waktu'])); ?></small>
                    </div>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="workspace-block">
            <div class="block-title-bar"><h5><i class="fa-solid <?= $is_admin ? 'fa-folder-plus' : 'fa-eye'; ?> me-2"></i>Naskah Terbaru</h5></div>
            <ul class="list-unstyled m-0">
                <?php while ($n = $data_naskah->fetch_assoc()):
                    $img=BASE_URL.'/assets/images/msc-placeholder.png';
                    if(!empty($n['url_gambar_eksternal']) && filter_var($n['url_gambar_eksternal'],FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($n['url_gambar_eksternal'],PHP_URL_SCHEME)),['http','https'],true))$img=$n['url_gambar_eksternal'];
                    elseif(!empty($n['gambar']) && is_file(__DIR__.'/../assets/images/naskah/'.basename($n['gambar'])))$img=BASE_URL.'/assets/images/naskah/'.rawurlencode(basename($n['gambar']));

                ?>
                <li class="naskah-node-item">
                    <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$n['id'] ?>" class="latest-manuscript text-decoration-none text-dark">
                        <img src="<?= htmlspecialchars($img,ENT_QUOTES,'UTF-8') ?>" alt="Gambar naskah" style="border-radius:8px" class="me-3 shadow-sm border">
                        <div>
                            <div class="fw-bold">
                                <?= htmlspecialchars($n['judul'] ?? ''); ?>
                                <p class="small text-muted mt-2 mb-1"><i class="fa-solid fa-building-columns me-1"></i><?= htmlspecialchars($n['nama_tempat']??'') ?></p><p class="small text-muted"><i class="fa-solid fa-barcode me-1"></i><?= htmlspecialchars($n['kode_inventaris']?:'Tanpa nomor inventaris') ?></p>
                            </div>
                            <small class="text-muted">
                                <?= htmlspecialchars($n['penginput_awal'] ?? ''); ?> &bull; <?= date('d M Y', strtotime($n['tanggal_input'] ?? 'now')); ?>
                            </small>
                        </div>
                    </a>
                </li>
                <?php endwhile;if(!$featuredId): ?><li class="p-3 text-muted small">Belum ada entri naskah.</li><?php endif; ?>
            </ul>
            <div class="block-title-bar small">5 entri terbaru lainnya yang saya input</div>
            <ul class="list-unstyled m-0 dashboard-entry-list">
            <?php while($recent=$recentPersonal->fetch_assoc()): ?><li class="naskah-node-item"><a class="d-flex align-items-center text-decoration-none text-dark" href="<?= BASE_URL ?>/detail.php?id=<?= (int)$recent['id'] ?>"><img src="<?= htmlspecialchars(kandagaDashboardImage($recent),ENT_QUOTES,'UTF-8') ?>" alt="" class="rounded border me-3" loading="lazy"><div class="small"><strong><?= htmlspecialchars($recent['judul'],ENT_QUOTES,'UTF-8') ?></strong><div class="text-muted"><?= htmlspecialchars($recent['nama_tempat']??'',ENT_QUOTES,'UTF-8') ?> · <?= date('d M Y',strtotime($recent['tanggal_input']??'now')) ?></div></div></a></li><?php endwhile;if(!$recentPersonal->num_rows): ?><li class="p-3 text-muted small">Belum ada entri lainnya.</li><?php endif; ?></ul>
            <?php if($editedWatchlist): ?><details class="p-3"><summary class="small fw-bold">Watchlist naskah yang saya sunting</summary><ul class="small mt-2"><?php foreach($editedWatchlist as $watched): ?><li><a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$watched['id'] ?>"><?= htmlspecialchars($watched['judul'],ENT_QUOTES,'UTF-8') ?></a></li><?php endforeach; ?></ul></details><?php endif; ?>
        </div>
    </div>
</div>

<script>(function(){const clock=document.getElementById('realtime-clock'),date=document.getElementById('realtime-date');function tick(){if(!clock.isConnected){clearInterval(timer);return;}const now=new Date();clock.textContent=now.toLocaleTimeString('id-ID');date.textContent=now.toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});}const timer=setInterval(tick,1000);tick();})();</script>
