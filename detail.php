<?php
// Pastikan error reporting aktif untuk pengembangan
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/bookmark-button.php';
require_once __DIR__ . '/includes/bibliography.php';
require_once __DIR__ . '/includes/website-visits.php';
require_once __DIR__ . '/includes/version.php';

// Pastikan BASE_URL terdefinisi, jika belum (karena file config belum update), kita kasih default kosong
if (!defined('BASE_URL')) define('BASE_URL', '');

// Path fisik untuk pengecekan file di server
$root_dir = ROOT_PATH;

// 1. Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . BASE_URL . "/search.php");
    exit;
}

$id = intval($_GET['id']);

// --- Sisa Kode ---
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Query Utama
$query = "SELECT k.*, l.nama_tempat, l.foto_lokasi, l.negara, l.alamat
          FROM katalog k
          LEFT JOIN tb_lokasi l ON k.id_lokasi = l.id_lokasi
          WHERE k.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    die("<div class='container mt-5 alert alert-danger shadow-sm'>Data naskah tidak ditemukan.</div>");
}

// 3. Eksekusi log popularitas naskah dengan classifier trafik yang sama
// seperti website_visits. Hal ini mencegah crawler sosial/search masuk ke
// daftar naskah terpopuler hanya karena user-agent-nya tidak memuat kata bot.
$ua_log = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512, 'UTF-8');
$traffic_log = kandagaTrafficClass($ua_log);
if (empty($traffic_log['is_bot'])) {
    try {
        $ip_log = kandagaClientIp($_SERVER) ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $stmt_log = $conn->prepare("INSERT INTO naskah_views (id_naskah, ip_address, user_agent) VALUES (?, ?, ?)");
        $stmt_log->bind_param("iss", $id, $ip_log, $ua_log);
        $stmt_log->execute();
        $stmt_log->close();
    } catch (Exception $e) {
        error_log("Gagal log kunjungan: " . $e->getMessage());
    }
}

// 4. Menghitung total kunjungan
$stmt_views = $conn->prepare("SELECT COUNT(*) as total FROM naskah_views WHERE id_naskah = ?");
$stmt_views->bind_param("i", $id);
$stmt_views->execute();
$row_views = $stmt_views->get_result()->fetch_assoc();
$total_views = $row_views['total'];
$stmt_views->close();


// 2. Mengambil Data Relasi Banyak-ke-Banyak (Aksara, Bahasa, Alas Tulis)
function getNamaRelasi($conn, $tableMaster, $tableJunction, $colId, $colNama, $id_naskah) {
    $sql = "SELECT DISTINCT m.{$colNama}
            FROM {$tableMaster} m
            JOIN {$tableJunction} j ON m.{$colId} = j.{$colId}
            WHERE j.id_naskah = ?
            ORDER BY m.{$colNama}";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_naskah);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) {
        $items[] = $r[$colNama];
    }
    return kandagaFormatConjunction($items);
}

$string_aksara     = getNamaRelasi($conn, 'tb_aksara', 'naskah_aksara', 'id_aksara', 'nama_aksara', $id);
$string_bahasa     = getNamaRelasi($conn, 'tb_bahasa', 'naskah_bahasa', 'id_bahasa', 'nama_bahasa', $id);
$string_alas_tulis = getNamaRelasi($conn, 'tb_alas_tulis', 'naskah_alas_tulis', 'id_alas_tulis', 'nama_alas_tulis', $id);

// 3. Query Seluruh Relasi Bibliografi (Katalog, Primer, Sekunder)
$sql_bib = "SELECT b.*, bm.cover_filename, bm.cover_url FROM tb_bibliografi b LEFT JOIN tb_bibliografi_media bm ON bm.id_bibliografi=b.id
            JOIN naskah_bibliografi nb ON b.id = nb.id_bibliografi
            WHERE nb.id_naskah = ?";
$stmt_bib = $conn->prepare($sql_bib);
$stmt_bib->bind_param("i", $id);
$stmt_bib->execute();
$res_bib = $stmt_bib->get_result();

$biblio_katalog = [];
$biblio_primer = [];
$biblio_sekunder = [];

while ($bib = $res_bib->fetch_assoc()) {
    if ($bib['kategori'] === 'katalog') {
        $biblio_katalog[] = $bib;
    } elseif ($bib['kategori'] === 'referensi primer') {
        $biblio_primer[] = $bib;
    } elseif ($bib['kategori'] === 'referensi sekunder') {
        $biblio_sekunder[] = $bib;
    }
}

// Fungsi bantu untuk memformat sitasi otomatis
function formatSitasiApsa($ref) { return kandagaBibliographyCitation((array)$ref); }

// 4. Query Naskah Terkait: ranking metadata (judul, lokasi, aksara, bahasa, alas tulis).
$currentTitle = (string)($row['judul'] ?? '');
$tokenizeTitle = static function ($value) {
    $value = mb_strtolower(strip_tags((string)$value), 'UTF-8');
    $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
    $stop = ['yang','dan','dari','di','ke','dengan','untuk','pada','atau','the','of','a'];
    return array_values(array_diff(array_filter(explode(' ', trim($value)), static fn($t) => mb_strlen($t, 'UTF-8') > 2), $stop));
};
$titleTokens = array_unique($tokenizeTitle($currentTitle));
$titleScore = static function ($other) use ($titleTokens, $tokenizeTitle) {
    if (!$titleTokens) return 0.0;
    $otherTokens = array_unique($tokenizeTitle($other));
    if (!$otherTokens) return 0.0;
    $intersection = count(array_intersect($titleTokens, $otherTokens));
    $union = count(array_unique(array_merge($titleTokens, $otherTokens)));
    return $union ? ($intersection / $union) * 50 : 0.0;
};
$relationIds = static function ($table, $column) use ($conn, $id) {
    $allowed = ['naskah_aksara','naskah_bahasa','naskah_alas_tulis'];
    if (!in_array($table, $allowed, true)) return [];
    $stmt = $conn->prepare("SELECT $column FROM $table WHERE id_naskah=?");
    $stmt->bind_param('i', $id); $stmt->execute(); $result = $stmt->get_result(); $ids = [];
    while ($r = $result->fetch_assoc()) $ids[] = (int)$r[$column];
    $stmt->close(); return $ids;
};
$currentRelations = [
    'aksara' => $relationIds('naskah_aksara', 'id_aksara'),
    'bahasa' => $relationIds('naskah_bahasa', 'id_bahasa'),
    'alas' => $relationIds('naskah_alas_tulis', 'id_alas_tulis'),
];
$candidateStmt = $conn->prepare("SELECT k.id,k.judul,k.kode_inventaris,k.gambar,k.url_gambar_eksternal,k.id_lokasi,l.nama_tempat FROM katalog k LEFT JOIN tb_lokasi l ON k.id_lokasi=l.id_lokasi WHERE k.id!=? ORDER BY k.id DESC LIMIT 250");
$candidateStmt->bind_param('i', $id); $candidateStmt->execute(); $candidateResult = $candidateStmt->get_result(); $candidates = $candidateResult->fetch_all(MYSQLI_ASSOC); $candidateStmt->close();
$candidateIds = array_map(static fn($r) => (int)$r['id'], $candidates);
$relationMaps = ['aksara'=>[], 'bahasa'=>[], 'alas'=>[]];
foreach ([['aksara','naskah_aksara','id_aksara'],['bahasa','naskah_bahasa','id_bahasa'],['alas','naskah_alas_tulis','id_alas_tulis']] as [$key,$table,$column]) {
    if (!$candidateIds) continue;
    $in = implode(',', array_map('intval', $candidateIds));
    $res = $conn->query("SELECT id_naskah,$column FROM $table WHERE id_naskah IN ($in)");
    while ($r = $res->fetch_assoc()) $relationMaps[$key][(int)$r['id_naskah']][] = (int)$r[$column];
}
$ranked = [];
foreach ($candidates as $candidate) {
    $score = $titleScore($candidate['judul']); $reasons = [];
    if ((int)$candidate['id_lokasi'] === (int)$row['id_lokasi']) { $score += 20; $reasons[] = 'lokasi sama'; }
    foreach (['aksara'=>12,'bahasa'=>10,'alas'=>8] as $key=>$weight) {
        if (array_intersect($currentRelations[$key], $relationMaps[$key][(int)$candidate['id']] ?? [])) { $score += $weight; $reasons[] = $key === 'alas' ? 'alas tulis sama' : $key . ' sama'; }
    }
    if ($score > 0) { $candidate['related_score'] = $score; $candidate['related_reason'] = implode(' · ', $reasons); $ranked[] = $candidate; }
}
usort($ranked, static fn($a,$b) => ($b['related_score'] <=> $a['related_score']) ?: strcasecmp($a['judul'], $b['judul']));
$ranked = array_slice($ranked, 0, 4);

$display_image_url = kandagaRealImageUrl($row);
// Gambar utama detail hanya boleh berasal dari file lokal atau URL eksternal
// yang valid. Placeholder tetap dipertahankan untuk kartu naskah terkait.
$display_image_path = strtolower((string)parse_url((string)$display_image_url, PHP_URL_PATH));
if ($display_image_url !== '' && preg_match('/(?:^|[\/_-])(msc[-_ ]?placeholder|placeholder|no[-_ ]?(?:image|photo)|image[-_ ]?not[-_ ]?available)(?:[.\/_-]|$)/i', $display_image_path)) {
    $display_image_url = '';
}
$seoTitle = (string)($row['judul'] ?? 'Detail Naskah') . ' — KANDAGA';
$seoDescription = (string)($row['deskripsi_isi'] ?? '');
$seoUrl = BASE_URL . '/detail.php?id=' . (int)$id;
$seoImage = $display_image_url;


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($row['judul'] ?? 'Detail Naskah') ?> - Detail Naskah</title>
    <?php $seoType = 'manuscript'; include __DIR__ . '/includes/seo-meta.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/viewer.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-detail-naskah.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php include __DIR__ . '/includes/module-head.php'; ?>
</head>

<body class="kandaga-module manuscript-detail-page">
<?php require_once __DIR__ . '/includes/analytics-context.php'; kandagaSetPageContext('manuscript','/naskah/'.$id,$row['judul'],$id); ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="mb-4">
        <a href="search.php" class="btn btn-light rounded-pill px-4 text-muted border shadow-sm">
            <i class="fas fa-arrow-left me-2 text-primary"></i>Kembali ke Katalog
        </a>
    </div>

    <div class="row">
        <?php if (!empty($display_image_url)): ?>
        <div class="col-lg-12 mb-4">
            <div class="image-viewer-container">
                <div class="image-viewer-box" id="imageGallery">
                    <img src="<?= htmlspecialchars($display_image_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Naskah" class="naskah-img-fit" onerror="this.onerror=null;var box=this.closest('.image-viewer-container');if(box){box.remove();}">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-lg-12">
            <div class="bg-white p-4 rounded-lg shadow-sm border-0">
                <div class="mb-3 d-flex flex-wrap align-items-center" style="gap: .5rem">
                    <?= kandagaBookmarkButton($id, $row['judul'] ?? '') ?>
                    <a class="btn btn-sm btn-outline-warning bookmark-style-action" href="<?= BASE_URL ?>/pages/lapor-naskah.php?id=<?= (int)$id ?>"><i class="fa-solid fa-comment-dots me-1"></i>Lapor / Beri Masukan</a>
                </div>
                <h2 class="detail-title mb-4"><?= htmlspecialchars($row['judul'] ?? '-') ?></h2>
                <div class="table-responsive">
                    <table class="table table-detail detail-metadata">
                        <tr><td class="label-col">Tempat Penyimpan</td><td class="value-col"><?= htmlspecialchars($row['nama_tempat'] ?? '-') ?></td></tr>
                        <tr><td class="label-col">Kode Inventaris</td><td class="value-col"><?= htmlspecialchars($row['kode_inventaris'] ?? '-') ?></td></tr>
                        <tr><td class="label-col">Riwayat Naskah</td><td class="value-col prose-value"><?= !empty($row['riwayat_naskah']) ? htmlspecialchars_decode($row['riwayat_naskah']) : "<span class='text-muted fst-italic'>Informasi belum tersedia</span>" ?></td></tr>
                        <tr><td class="label-col">Aksara</td><td class="value-col"><?= htmlspecialchars($string_aksara) ?></td></tr>
                        <tr><td class="label-col">Bahasa</td><td class="value-col"><?= htmlspecialchars($string_bahasa) ?></td></tr>
                        <tr><td class="label-col">Alas Tulis</td><td class="value-col"><?= htmlspecialchars($string_alas_tulis) ?></td></tr>
                        <tr><td class="label-col">Deskripsi Fisik</td><td class="value-col prose-value"><?= !empty($row['deskripsi_fisik']) ? htmlspecialchars_decode($row['deskripsi_fisik']) : '-' ?></td></tr>
                        <tr><td class="label-col">Deskripsi Isi</td><td class="value-col prose-value"><?= !empty($row['deskripsi_isi']) ? htmlspecialchars_decode($row['deskripsi_isi']) : "<span class='text-muted fst-italic'>Informasi belum tersedia</span>" ?></td></tr>

                        <tr>
                            <td class="label-col">Referensi Katalog</td>
                            <td class="value-col">
                                <?php if (!empty($biblio_katalog)): ?>
                                    <?php foreach ($biblio_katalog as $index => $ref): ?>
                                        <div class="biblio-box shadow-sm mb-2">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <button type="button" class="btn btn-xs btn-light border py-0 px-2" onclick="copySitasiText('kat-<?= $index ?>', this)">
                                                    <i class="far fa-copy me-1"></i> Salin
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-start"><?php if ($cover = kandagaBibliographyCover($ref)): ?><img src="<?= htmlspecialchars($cover) ?>" alt="Cover bibliografi" style="width:48px;height:64px;object-fit:cover" class="rounded me-2"><?php endif; ?><div id="kat-<?= $index ?>"><?= formatSitasiApsa($ref) ?></div></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Data katalog belum tersedia.</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="label-col">Referensi Primer</td>
                            <td class="value-col">
                                <?php if (!empty($biblio_primer)): ?>
                                    <?php foreach ($biblio_primer as $index => $ref): ?>
                                        <div class="biblio-box shadow-sm mb-2">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <button type="button" class="btn btn-xs btn-light border py-0 px-2" onclick="copySitasiText('prim-<?= $index ?>', this)">
                                                    <i class="far fa-copy me-1"></i> Salin
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-start"><?php if ($cover = kandagaBibliographyCover($ref)): ?><img src="<?= htmlspecialchars($cover) ?>" alt="Cover bibliografi" style="width:48px;height:64px;object-fit:cover" class="rounded me-2"><?php endif; ?><div id="prim-<?= $index ?>"><?= formatSitasiApsa($ref) ?></div></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Data referensi primer belum tersedia.</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="label-col">Referensi Sekunder</td>
                            <td class="value-col">
                                <?php if (!empty($biblio_sekunder)): ?>
                                    <?php foreach ($biblio_sekunder as $index => $ref): ?>
                                        <div class="biblio-box shadow-sm mb-2">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <button type="button" class="btn btn-xs btn-light border py-0 px-2" onclick="copySitasiText('sek-<?= $index ?>', this)">
                                                    <i class="far fa-copy me-1"></i> Salin
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-start"><?php if ($cover = kandagaBibliographyCover($ref)): ?><img src="<?= htmlspecialchars($cover) ?>" alt="Cover bibliografi" style="width:48px;height:64px;object-fit:cover" class="rounded me-2"><?php endif; ?><div id="sek-<?= $index ?>"><?= formatSitasiApsa($ref) ?></div></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Data referensi sekunder belum tersedia.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                         <tr>
                            <td class="label-col">
                                <span class="text-muted fw-bold">
                                    Ekspor Obyek <i class="fa-solid fa-file-export me-1"></i>
                                </span>
                            </td>
                            <td class="value-col">
                                <div class="d-flex flex-wrap align-items-center" style="gap: 20px;">
                                    <a class="btn-export-link" href="<?= BASE_URL ?>/export.php?id=<?= $row['id']; ?>&format=csv">
                                        <i class="fa-solid fa-file-csv text-success me-1"></i> CSV
                                    </a>
                                    <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/export.php?id=<?= $row['id']; ?>&format=mods">
                                        <i class="fa-solid fa-file-lines text-warning me-1"></i> MODS
                                    </a>
                                    <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/export.php?id=<?= $row['id']; ?>&format=pica+">
                                        <i class="fa-solid fa-quote-right text-muted me-1"></i> PICA+
                                    </a>
                                    <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/export.php?id=<?= $row['id']; ?>&format=marc21">
                                        <i class="fa-solid fa-code text-primary me-1"></i> MARC21
                                    </a>
                                    <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/export.php?id=<?= $row['id']; ?>&format=tei">
                                        <i class="fa-solid fa-file-code text-info me-1"></i> TEI
                                    </a>
                                    <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/export.php?id=<?= $row['id']; ?>&format=hsp-kod">
                                        <i class="fa-solid fa-book-open text-danger me-1"></i> HSP-KOD
                                    </a>
                                    <a class="btn-export-link" target="_blank" href="<?= BASE_URL ?>/export.php?id=<?= $row['id']; ?>&format=hsp-desc">
                                        <i class="fa-solid fa-align-left text-secondary me-1"></i> HSP-DESC
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

 <br>
<div class="row">
    <div class="col-lg-12">
        <div class="bg-white p-4 rounded-lg shadow-sm border-0">
            <h4 class="detail-title mb-1">Akses Digital</h4>
            <div class="table-responsive">
                <table class="table table-detail">
                    <tbody>
                        <tr>
                            <td class="label-col">Penyedia</td>
                            <td class="value-col">
                                <?= !empty($row['penyedia_akses']) ? htmlspecialchars($row['penyedia_akses']) : "<span class='text-muted fst-italic'>Tidak tersedia</span>" ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="label-col">URL repositori</td>
                            <td class="value-col">
                                <?= !empty($row['url_repositori']) ? "<a href='".htmlspecialchars($row['url_repositori'])."' target='_blank'>".htmlspecialchars($row['url_repositori'])."</a>" : "<span class='text-muted fst-italic'>Tidak tersedia</span>" ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="label-col">Advance viewer</td>
                            <td class="value-col">
                                <?php if (!empty($row['url_pdf']) || !empty($row['iiif_manifest'])): ?>
                                    <div class="d-flex align-items-center" style="gap: 20px;">
                                        <?php if (!empty($row['url_pdf'])): ?>
                                            <a href="<?= htmlspecialchars($row['url_pdf']) ?>" target="_blank" class="text-danger text-decoration-none text-center">
                                                <i class="fas fa-file-pdf fa-3x"></i>
                                                <div class="small mt-1 fw-bold">PDF</div>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($row['iiif_manifest'])): ?>
                                        <a href="iiif-viewer.php?id=<?= $row['id'] ?>&manifest=<?= urlencode($row['iiif_manifest']) ?>" ...>
                                                    <img src="assets/images/iiif-icon.png" style="width: 45px;">
                                                <div class="small mt-1 fw-bold text-dark">IIIF</div>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Tidak tersedia</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<br>
    <div class="row">
        <div class="col-lg-12">
            <div class="bg-white p-4 rounded-lg shadow-sm border-0">
                <h4 class="detail-title mb-1">Lokasi Koleksi</h4>
                <div class="table-responsive">
                    <table class="table table-detail collection-location-table">
                        <tr>
                            <td class="label-col" style="width: 200px; vertical-align: top;">
                                <?php $foto_lokasi = !empty($row['foto_lokasi']) ? "assets/images/" . $row['foto_lokasi'] : "assets/images/msc-placeholder.png"; ?>
                                <span class="visually-hidden">Foto lokasi koleksi</span>
                                <img src="<?= htmlspecialchars($foto_lokasi) ?>" alt="Foto <?= htmlspecialchars($row['nama_tempat'] ?? 'Lokasi') ?>" class="img-fluid rounded shadow-sm" style="width: 200px; height: 100px; object-fit: cover;">
                            </td>
                            <td class="value-col">
                                <h5 class="detail-title mb-1" style="font-weight: 700; color: #1e40af;"><?= htmlspecialchars($row['nama_tempat'] ?? '-') ?></h5>
                                <p><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($row['alamat'] ?? '-') ?> <?= htmlspecialchars($row['negara'] ?? '-') ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="bg-white p-4 rounded-lg shadow-sm border-0">
                <h6 class="fw-bold mb-3"><i class="fas fa-share-alt me-2"></i>Bagikan Naskah Ini</h6>
                <div class="d-flex flex-wrap" style="gap: 10px;">
                    <a href="https://wa.me/?text=Lihat%20naskah%20ini di Katalog Naskah Sunda:%20<?= urlencode($current_url) ?>" target="_blank" class="btn btn-success"><i class="fab fa-whatsapp me-1"></i> WhatsApp</a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($current_url) ?>" target="_blank" class="btn btn-primary"><i class="fab fa-facebook me-1"></i> Facebook</a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($current_url) ?>&text=Cek%20naskah%20ini:%20<?= urlencode($row['judul'] ?? '') ?>" target="_blank" class="btn btn-dark"><i class="fab fa-x-twitter me-1"></i> X</a>
                    <button type="button" onclick="copyLink()" id="btnCopy" class="btn btn-outline-secondary"><i class="fas fa-link me-1"></i> Salin Link</button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 text-muted small">
    <i class="fa-solid fa-eye"></i> Halaman dilihat: <strong><?= $total_views; ?></strong> kali.
    </div>


    <div class="mt-5 pt-4">
        <h5 class="fw-bold mb-4" style="color: #1e293b;">Naskah Terkait Lainnya</h5>
        <div class="row">
            <?php foreach ($ranked as $rel):
                $rel_img = kandagaImageUrl($rel);
            ?>
            <div class="col-6 col-md-3 mb-4">
                <a href="detail.php?id=<?= $rel['id'] ?>" class="text-decoration-none text-dark">
                    <div class="related-card h-100 shadow-sm border" style="border-radius: 10px; overflow: hidden; transition: 0.3s;">
                        <div class="related-img-box" style="height: 150px; overflow: hidden;">
                            <img src="<?= htmlspecialchars($rel_img, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: cover;"<?= kandagaImageFallbackAttribute() ?>>
                        </div>
                        <div class="p-3 bg-white">
                            <p class="small fw-bold mb-1" style="height: 38px; overflow: hidden; line-height: 1.2; color: #334155;"><?= htmlspecialchars($rel['judul']) ?></p>
                            <small class="text-muted d-block text-truncate"><i class="fas fa-barcode me-1" style="font-size: 10px;"></i> <?= htmlspecialchars($rel['kode_inventaris'] ?? '-') ?></small>
                            <small class="text-muted d-block text-truncate"><i class="fas fa-university me-1" style="font-size: 10px;"></i> <?= htmlspecialchars($rel['nama_tempat'] ?? '-') ?></small>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; if (!$ranked): ?><div class="col-12"><p class="text-muted small">Belum ada naskah terkait dengan metadata yang serupa.</p></div><?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/viewer.min.js"></script>
<script>
    $(document).ready(function() {
        if ($('#imageGallery').length) {
            new Viewer(document.getElementById('imageGallery'), { navbar: false, title: false });
        }
    });

    function copyLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            const btn = document.getElementById('btnCopy');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Tersalin!';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.replace('btn-success', 'btn-outline-secondary');
            }, 2000);
        });
    }

    async function copySitasiText(elementId, btnElement) {
        const el = document.getElementById(elementId);
        const plainText = el.innerText;
        try {
            await navigator.clipboard.writeText(plainText);
            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => { btnElement.innerHTML = originalText; }, 2000);
        } catch (err) {
            console.error('Gagal menyalin text', err);
        }
    }
</script>
</body>
</html>
