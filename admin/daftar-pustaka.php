<?php
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(false);
if ($_SERVER['REQUEST_METHOD'] === 'POST') kandagaCsrf();

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/bibliography.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

$error_msg = '';
$success_msg = '';
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

// ==========================================
// 2. LOGIKA PEMROSESAN DATA (CRUD & PARSER ENGINE)
// ==========================================

// --- AKSI HAPUS (DELETE) ---
if (isset($_POST['del_bib'])) {
    try {
        if (!$isAdmin) { $error_msg = 'Penghapusan bibliografi hanya dapat dilakukan administrator.'; }
        else {
        $stmt = $conn->prepare("DELETE FROM tb_bibliografi WHERE id = ?");
        $stmt->bind_param("i", $_POST['del_bib']);
        $stmt->execute();
        echo "<script>window.location.href='index.php?page=daftar-pustaka';</script>";
        exit;
        }
    } catch (mysqli_sql_exception $e) {
        $error_msg = "Gagal menghapus data: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- AKSI SIMPAN & UPDATE MANUAL ---
    if (isset($_POST['simpan_bibliografi'])) {
        try {
            $id            = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $tipe_item     = $_POST['tipe_item'];
            $kategori      = $_POST['kategori'];
            $judul         = trim($_POST['judul']);
            $subjudul      = trim($_POST['subjudul']);
            $penyusun      = trim($_POST['penyusun']);
            $editor        = trim($_POST['editor']);
            $penerbit      = trim($_POST['penerbit']);
            $kota_terbit   = trim($_POST['kota_terbit']);
            $isbn_issn     = trim($_POST['isbn_issn']);
            $doi           = trim($_POST['doi']);
            $url           = trim($_POST['url']);
            $keterangan    = trim($_POST['keterangan']);
            $tahun_terbit  = trim($_POST['tahun_terbit']);
            $jilid         = trim($_POST['jilid']);
            $judul_bab     = trim($_POST['judul_bab']);
            $nama_jurnal   = trim($_POST['nama_jurnal']);
            $volume        = trim($_POST['volume']);
            $nomor         = trim($_POST['nomor']);
            $halaman       = trim($_POST['halaman']);

            if ($id) {
                // Proses UPDATE
                $sql = "UPDATE tb_bibliografi SET
                        tipe_item=?, kategori=?, judul=?, subjudul=?, penyusun=?, editor=?,
                        penerbit=?, kota_terbit=?, isbn_issn=?, doi=?, url=?, keterangan=?,
                        tahun_terbit=?, jilid=?, judul_bab=?, nama_jurnal=?, volume=?, nomor=?, halaman=?
                        WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssssssssssssi",
                    $tipe_item, $kategori, $judul, $subjudul, $penyusun, $editor,
                    $penerbit, $kota_terbit, $isbn_issn, $doi, $url, $keterangan,
                    $tahun_terbit, $jilid, $judul_bab, $nama_jurnal, $volume, $nomor, $halaman, $id
                );
            } else {
                // Proses INSERT
                $sql = "INSERT INTO tb_bibliografi
                        (tipe_item, kategori, judul, subjudul, penyusun, editor, penerbit, kota_terbit,
                         isbn_issn, doi, url, keterangan, tahun_terbit, jilid, judul_bab, nama_jurnal, volume, nomor, halaman)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssssssssssss",
                    $tipe_item, $kategori, $judul, $subjudul, $penyusun, $editor,
                    $penerbit, $kota_terbit, $isbn_issn, $doi, $url, $keterangan,
                    $tahun_terbit, $jilid, $judul_bab, $nama_jurnal, $volume, $nomor, $halaman
                );
            }

            if ($stmt->execute()) {
                echo "<script>window.location.href='index.php?page=daftar-pustaka';</script>";
                exit;
            } else {
                $error_msg = "Gagal mengeksekusi perintah database.";
            }
        } catch (mysqli_sql_exception $e) {
            $error_msg = "Galat SQL Manual: " . $e->getMessage();
        }
    }

    // --- AKSI IMPORT FILE RIS ZOTERO ---
    if (isset($_POST['import_ris'])) {
        if (!$isAdmin) { $error_msg = 'Import RIS hanya dapat dilakukan administrator.'; }
        else {
        if (isset($_FILES['file_ris']) && $_FILES['file_ris']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['file_ris']['tmp_name'];
            $kategori_import = $_POST['kategori_import'];

            $handle = fopen($file_tmp, "r");
            if ($handle) {
                $items = [];
                $current_item = [];

                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    if (preg_match('/^([A-Z0-9]{2})\s*-\s*(.*)$/', $line, $matches)) {
                        $tag = $matches[1];
                        $value = trim($matches[2]);

                        if ($tag === 'TY') {
                            if (!empty($current_item)) {
                                $items[] = $current_item;
                            }
                            $current_item = [
                                'tipe_item' => ($value === 'JOUR' || $value === 'EJOUR') ? 'jurnal' : 'buku',
                                'kategori' => $kategori_import,
                                'judul' => '', 'subjudul' => '', 'penyusun' => [], 'editor' => '',
                                'penerbit' => '', 'kota_terbit' => '', 'isbn_issn' => '', 'doi' => '',
                                'url' => '', 'keterangan' => '', 'tahun_terbit' => '', 'jilid' => '',
                                'judul_bab' => '', 'nama_jurnal' => '', 'volume' => '', 'nomor' => '', 'halaman_start' => '', 'halaman_end' => ''
                            ];
                        } else if (!empty($current_item)) {
                            switch ($tag) {
                                case 'TI': $current_item['judul'] = $value; break;
                                case 'T2': $current_item['nama_jurnal'] = $value; break;
                                case 'AU': $current_item['penyusun'][] = $value; break;
                                case 'ED': $current_item['editor'] = $value; break;
                                case 'PB': $current_item['penerbit'] = $value; break;
                                case 'CY': $current_item['kota_terbit'] = $value; break;
                                case 'VL': $current_item['volume'] = $value; break;
                                case 'IS': $current_item['nomor'] = $value; break;
                                case 'SP': $current_item['halaman_start'] = $value; break;
                                case 'EP': $current_item['halaman_end'] = $value; break;
                                case 'SN': $current_item['isbn_issn'] = $value; break;
                                case 'DO': $current_item['doi'] = $value; break;
                                case 'UR': $current_item['url'] = $value; break;
                                case 'N1': case 'AB': $current_item['keterangan'] = $value; break;
                                case 'PY': $current_item['tahun_terbit'] = substr($value, 0, 4); break;
                            }
                        }
                    }
                }
                if (!empty($current_item)) { $items[] = $current_item; }
                fclose($handle);

                try {
                    $stmt = $conn->prepare("INSERT INTO tb_bibliografi
                        (tipe_item, kategori, judul, subjudul, penyusun, editor, penerbit, kota_terbit, isbn_issn, doi, url, keterangan, tahun_terbit, jilid, judul_bab, nama_jurnal, volume, nomor, halaman)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $count_success = 0;
                    foreach ($items as $item) {
                        $authors = implode(', ', $item['penyusun']);
                        $pages = $item['halaman_start'];
                        if(!empty($item['halaman_end'])) $pages .= '-' . $item['halaman_end'];

                        $stmt->bind_param("sssssssssssssssssss",
                            $item['tipe_item'], $item['kategori'], $item['judul'], $item['subjudul'],
                            $authors, $item['editor'], $item['penerbit'], $item['kota_terbit'],
                            $item['isbn_issn'], $item['doi'], $item['url'], $item['keterangan'],
                            $item['tahun_terbit'], $item['jilid'], $item['judul_bab'],
                            $item['nama_jurnal'], $item['volume'], $item['nomor'], $pages
                        );
                        if($stmt->execute()) $count_success++;
                    }
                    echo "<script>window.location.href='index.php?page=daftar-pustaka&import_msg=" . $count_success . "';</script>";
                    exit;
                } catch (mysqli_sql_exception $e) {
                    $error_msg = "Galat SQL Import: " . $e->getMessage();
                }
            }
        } else {
            $error_msg = "File gagal diunggah atau tidak ditemukan.";
        }
        }
    }
}

// --- LOGIKA FILTER TABEL (GET) ---
$f_kategori = $_GET['f_kategori'] ?? '';
$f_tipe     = $_GET['f_tipe'] ?? '';
$f_q        = trim((string)($_GET['q'] ?? ''));
$f_q        = function_exists('mb_substr') ? mb_substr($f_q, 0, 120, 'UTF-8') : substr($f_q, 0, 120);

$conditions = ['1=1']; $params = [];
if ($f_kategori !== '') { $conditions[] = 'b.kategori = ?'; $params[] = $f_kategori; }
if ($f_tipe !== '') { $conditions[] = 'b.tipe_item = ?'; $params[] = $f_tipe; }
if ($f_q !== '') {
    $conditions[] = '(b.judul LIKE ? OR b.subjudul LIKE ? OR b.penyusun LIKE ? OR b.editor LIKE ? OR b.penerbit LIKE ? OR b.kota_terbit LIKE ? OR b.tahun_terbit LIKE ? OR b.isbn_issn LIKE ? OR b.doi LIKE ?)';
    for ($i = 0; $i < 9; $i++) $params[] = '%' . $f_q . '%';
}
$query_str = "SELECT b.*,m.cover_filename,m.cover_url FROM tb_bibliografi b LEFT JOIN tb_bibliografi_media m ON m.id_bibliografi=b.id WHERE " . implode(' AND ', $conditions) . " ORDER BY b.penyusun ASC, b.tahun_terbit DESC, b.judul ASC";
$res = false;
if ($stmt = $conn->prepare($query_str)) {
    if ($params) { $bind = [str_repeat('s', count($params))]; foreach ($params as $key => $value) { $params[$key] = $value; $bind[] = &$params[$key]; } call_user_func_array([$stmt, 'bind_param'], $bind); }
    $stmt->execute(); $res = $stmt->get_result();
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-bookmark text-primary me-2"></i>Daftar Pustaka & Bibliografi</h3>
            <p class="text-muted small mb-0">Kelola rujukan referensi primer, sekunder, dan katalogisasi manuskrip ala Zotero.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <?php if ($isAdmin): ?><button class="btn btn-outline-success shadow-sm fw-bold px-3 me-2" data-bs-toggle="modal" data-bs-target="#modalImportRis">
                <i class="fa-solid fa-file-import me-1"></i> Import dari Zotero (.ris)
            </button><?php endif; ?>
            <button class="btn btn-primary shadow-sm fw-bold px-3" onclick="window.location.href='index.php?page=form-bibliografi'">
                <i class="fa-solid fa-circle-plus me-1"></i> Tambah Bibliografi
            </button>
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-xmark me-1"></i> <?= htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['import_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-1"></i> Berhasil mengimpor <strong><?= (int)$_GET['import_msg'] ?></strong> entri bibliografi dari Zotero secara massal!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="daftar-pustaka">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="visually-hidden" for="bibliografi-search">Cari bibliografi</label>
                    <div class="input-group input-group-sm">
                        <input id="bibliografi-search" type="search" name="q" value="<?= htmlspecialchars($f_q, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Cari judul, penulis, penerbit, tahun, DOI…" autocomplete="off">
                        <?php if ($f_q !== ''): ?><a class="btn btn-outline-secondary" href="index.php?page=daftar-pustaka" title="Hapus pencarian"><i class="fa-solid fa-xmark"></i></a><?php endif; ?>
                    </div>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <select name="f_kategori" class="form-select form-select-sm text-sm">
                        <option value="">-- Semua Kategori --</option>
                        <option value="katalog" <?= $f_kategori == 'katalog' ? 'selected' : '' ?>>Katalog</option>
                        <option value="referensi primer" <?= $f_kategori == 'referensi primer' ? 'selected' : '' ?>>Referensi Primer</option>
                        <option value="referensi sekunder" <?= $f_kategori == 'referensi sekunder' ? 'selected' : '' ?>>Referensi Sekunder</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <select name="f_tipe" class="form-select form-select-sm text-sm">
                        <option value="">-- Semua Tipe Item --</option>
                        <option value="buku" <?= $f_tipe == 'buku' ? 'selected' : '' ?>>Buku (Monograf/Section)</option>
                        <option value="jurnal" <?= $f_tipe == 'jurnal' ? 'selected' : '' ?>>Jurnal Ilmiah</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                </div>
                <?php if($f_q !== '' || !empty($f_kategori) || !empty($f_tipe)): ?>
                    <div class="col-md-2">
                        <a href="index.php?page=daftar-pustaka" class="btn btn-sm btn-link text-muted p-0 mt-1 d-block text-center">Reset</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-lg p-3">
        <?php if ($f_q !== ''): ?><div class="small text-muted mb-2"><i class="fa-solid fa-magnifying-glass me-1"></i> Hasil pencarian untuk <strong><?= htmlspecialchars($f_q, ENT_QUOTES, 'UTF-8') ?></strong><?= $res ? ' — ' . (int)$res->num_rows . ' entri' : '' ?></div><?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="12%">Kategori</th>
                        <th width="43%">Detail Sitasi / Bibliografi</th>
                        <th width="25%">Metadata / Identifikasi</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($res && $res->num_rows > 0):
                        while($row = $res->fetch_assoc()):
                            $display_citation = kandagaBibliographyCitation($row);
                            $kat_badge = 'bg-light text-dark';
                            if($row['kategori'] == 'katalog') $kat_badge = 'bg-info text-dark';
                            if($row['kategori'] == 'referensi primer') $kat_badge = 'bg-danger';
                            if($row['kategori'] == 'referensi sekunder') $kat_badge = 'bg-success';
                            ?>
                            <tr>
                                <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                                <td>
                                    <span class="badge <?= $kat_badge ?> text-uppercase px-2 py-1 small fw-bold d-block text-center"><?= $row['kategori'] ?></span>
                                    <small class="text-muted d-block text-center mt-1"><i class="<?= $row['tipe_item'] == 'buku' ? 'fa-solid fa-book text-muted' : 'fa-solid fa-file-lines text-warning' ?>"></i> <?= ucfirst($row['tipe_item']) ?></small>
                                </td>
                                <td class="text-sm text-dark align-top">
                                    <div class="mb-1 d-flex align-items-start"><?php if($cover=kandagaBibliographyCover($row)): ?><img src="<?= htmlspecialchars($cover) ?>" alt="Cover" style="width:42px;height:58px;object-fit:cover" class="rounded me-2"><?php endif; ?><div><?= $display_citation ?></div></div>
                                    <?php if(!empty($row['subjudul'])): ?>
                                        <small class="text-muted d-block">Subjudul: <?= htmlspecialchars($row['subjudul']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary">
                                    <?php if(!empty($row['isbn_issn'])): ?> <div><i class="fa-solid fa-barcode me-1"></i> <?= htmlspecialchars($row['isbn_issn']) ?></div> <?php endif; ?>
                                    <?php if(!empty($row['doi'])): ?> <div><i class="fa-solid fa-fingerprint me-1"></i> <a href="https://doi.org/<?= htmlspecialchars($row['doi']) ?>" target="_blank" class="text-primary"><?= htmlspecialchars($row['doi']) ?></a></div> <?php endif; ?>
                                    <?php if(!empty($row['url'])): ?> <div><i class="fa-solid fa-link me-1"></i> <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 180px;"><?= htmlspecialchars($row['url']) ?></a></div> <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group shadow-sm rounded">
                                        <button type="button" class="btn btn-sm btn-light" title="Edit Item"
                                                onclick="window.location.href='index.php?page=form-bibliografi&id=<?= (int)$row['id'] ?>'">
                                            <i class="fa-solid fa-pen-to-square text-warning"></i>
                                        </button>
                                        <a href="index.php?page=daftar-pustaka&del_bib=<?= $row['id'] ?>" data-kandaga-delete="1"
                                           class="btn btn-sm btn-light"
                                            title="Hapus">
                                            <i class="fa-solid fa-trash text-danger"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada entri data bibliografi pustaka.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBib" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <form method="POST" action="" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="modalTitleBib"><i class="fa-solid fa-book-open text-primary me-2"></i>Tambah Entri Pustaka</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <input type="hidden" name="id" id="id_bib">

                <div class="row g-2">
                    <div class="mb-3 col-md-6">
                        <label class="fw-bold text-secondary small mb-1">Tipe Item (Zotero-style)</label>
                        <select name="tipe_item" id="tipe_item" class="form-select form-select-sm text-sm fw-bold bg-white" onchange="toggleTipeInput()" required>
                            <option value="buku">📖 Buku / Bagian Buku</option>
                            <option value="jurnal">📰 Artikel Jurnal Ilmiah</option>
                        </select>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="fw-bold text-secondary small mb-1">Kategori Dokumen</label>
                        <select name="kategori" id="kategori" class="form-select form-select-sm text-sm" required>
                            <option value="referensi sekunder">Referensi Sekunder</option>
                            <option value="referensi primer">Referensi Primer</option>
                            <option value="katalog">Katalog</option>
                        </select>
                    </div>
                </div>

                <hr class="my-2">

                <div class="row g-2">
                    <div class="col-md-12 mb-2">
                        <label class="fw-bold text-secondary small mb-1">Judul Utama</label>
                        <input type="text" name="judul" id="judul" class="form-control form-control-sm text-sm" placeholder="Judul buku atau judul artikel" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="fw-bold text-secondary small mb-1">Subjudul (Optional)</label>
                        <input type="text" name="subjudul" id="subjudul" class="form-control form-control-sm text-sm">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="fw-bold text-secondary small mb-1">Penyusun / Penulis (Author)</label>
                        <input type="text" name="penyusun" id="penyusun" class="form-control form-control-sm text-sm" required>
                    </div>
                </div>

                <div id="grup_buku">
                    <div class="row g-2 p-2 bg-light rounded mb-2 border border-dashed">
                        <div class="col-md-4 mb-2">
                            <label class="fw-bold text-muted small mb-1">Penerbit</label>
                            <input type="text" name="penerbit" id="penerbit" class="form-control form-control-sm text-sm">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="fw-bold text-muted small mb-1">Kota Tempat Terbit</label>
                            <input type="text" name="kota_terbit" id="kota_terbit" class="form-control form-control-sm text-sm">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="fw-bold text-muted small mb-1">Editor / Penyunting</label>
                            <input type="text" name="editor" id="editor" class="form-control form-control-sm text-sm">
                        </div>
                        <div class="col-md-6 mb-0">
                            <label class="fw-bold text-muted small mb-1">Judul Bab / Section</label>
                            <input type="text" name="judul_bab" id="judul_bab" class="form-control form-control-sm text-sm">
                        </div>
                        <div class="col-md-6 mb-0">
                            <label class="fw-bold text-muted small mb-1">Jilid / Edisi</label>
                            <input type="text" name="jilid" id="jilid" class="form-control form-control-sm text-sm">
                        </div>
                    </div>
                </div>

                <div id="grup_jurnal" style="display:none;">
                    <div class="row g-2 p-2 bg-light rounded mb-2 border border-dashed">
                        <div class="col-md-6 mb-2">
                            <label class="fw-bold text-warning small mb-1">Nama Jurnal Berkala</label>
                            <input type="text" name="nama_jurnal" id="nama_jurnal" class="form-control form-control-sm text-sm">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="fw-bold text-warning small mb-1">Volume</label>
                            <input type="text" name="volume" id="volume" class="form-control form-control-sm text-sm">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="fw-bold text-warning small mb-1">Nomor / Issue</label>
                            <input type="text" name="nomor" id="nomor" class="form-control form-control-sm text-sm">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="fw-bold text-warning small mb-1">Halaman</label>
                            <input type="text" name="halaman" id="halaman" class="form-control form-control-sm text-sm" placeholder="12-34">
                        </div>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-3 mb-2">
                        <label class="fw-bold text-secondary small mb-1">Tahun Terbit</label>
                        <input type="text" name="tahun_terbit" id="tahun_terbit" class="form-control form-control-sm text-sm">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="fw-bold text-secondary small mb-1">ISBN / ISSN</label>
                        <input type="text" name="isbn_issn" id="isbn_issn" class="form-control form-control-sm text-sm">
                    </div>
                    <div class="col-md-5 mb-2">
                        <label class="fw-bold text-secondary small mb-1">DOI (Digital Object Identifier)</label>
                        <input type="text" name="doi" id="doi" class="form-control form-control-sm text-sm" placeholder="10.xxxx/xxx">
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="fw-bold text-secondary small mb-1">URL / Tautan Akses</label>
                        <input type="url" name="url" id="url" class="form-control form-control-sm text-sm">
                    </div>
                    <div class="col-md-12 mb-0">
                        <label class="fw-bold text-secondary small mb-1">Keterangan / Memo Riset</label>
                        <textarea name="keterangan" id="keterangan" class="form-control text-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-sm btn-secondary fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="simpan_bibliografi" class="btn btn-sm btn-primary fw-bold px-4 shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Pustaka
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalImportRis" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-file-import text-success me-2"></i>Import Bibliografi Zotero</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <div class="alert alert-info small mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i> <strong>Petunjuk:</strong> Ekspor berkas dari Zotero Anda menggunakan format <strong>RIS</strong> (*Research Information Systems*), lalu unggah berkas berekstensi <code>.ris</code> Anda di bawah ini.
                </div>

                <div class="mb-3">
                    <label class="fw-bold text-secondary small mb-1">Tentukan Kategori Hasil Import</label>
                    <select name="kategori_import" class="form-select form-select-sm text-sm" required>
                        <option value="referensi sekunder" selected>Referensi Sekunder (Default)</option>
                        <option value="referensi primer">Referensi Primer</option>
                        <option value="katalog">Katalog</option>
                    </select>
                </div>

                <div class="mb-0">
                    <label class="fw-bold text-secondary small mb-1">Pilih File RIS (<code>.ris</code>)</label>
                    <div class="mb-3">
                        <input type="file" name="file_ris" class="form-control" id="customFileRis" accept=".ris" required>
                        <label class="form-label text-sm" for="customFileRis">Pilih berkas...</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-sm btn-secondary fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="import_ris" class="btn btn-sm btn-success fw-bold px-4 shadow-sm">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Mulai Import Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleTipeInput() {
        var tipe = document.getElementById('tipe_item').value;
        if(tipe === 'buku') {
            document.getElementById('grup_buku').style.display = 'block';
            document.getElementById('grup_jurnal').style.display = 'none';
        } else {
            document.getElementById('grup_buku').style.display = 'none';
            document.getElementById('grup_jurnal').style.display = 'block';
        }
    }

    function resetFormBib() {
        document.getElementById('modalTitleBib').innerHTML = '<i class="fa-solid fa-book-open text-primary me-2"></i> Tambah Entri Pustaka';
        document.getElementById('id_bib').value = '';
        document.getElementById('tipe_item').value = 'buku';
        document.getElementById('kategori').value = 'referensi sekunder';
        document.getElementById('judul').value = '';
        document.getElementById('subjudul').value = '';
        document.getElementById('penyusun').value = '';
        document.getElementById('editor').value = '';
        document.getElementById('penerbit').value = '';
        document.getElementById('kota_terbit').value = '';
        document.getElementById('isbn_issn').value = '';
        document.getElementById('doi').value = '';
        document.getElementById('url').value = '';
        document.getElementById('keterangan').value = '';
        document.getElementById('tahun_terbit').value = '';
        document.getElementById('jilid').value = '';
        document.getElementById('judul_bab').value = '';
        document.getElementById('nama_jurnal').value = '';
        document.getElementById('volume').value = '';
        document.getElementById('nomor').value = '';
        document.getElementById('halaman').value = '';
        toggleTipeInput();
    }

    function editBibData(data) {
        document.getElementById('modalTitleBib').innerHTML = '<i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Entri Pustaka';
        document.getElementById('id_bib').value = data.id;
        document.getElementById('tipe_item').value = data.tipe_item;
        document.getElementById('kategori').value = data.kategori;
        document.getElementById('judul').value = data.judul || '';
        document.getElementById('subjudul').value = data.subjudul || '';
        document.getElementById('penyusun').value = data.penyusun || '';
        document.getElementById('editor').value = data.editor || '';
        document.getElementById('penerbit').value = data.penerbit || '';
        document.getElementById('kota_terbit').value = data.kota_terbit || '';
        document.getElementById('isbn_issn').value = data.isbn_issn || '';
        document.getElementById('doi').value = data.doi || '';
        document.getElementById('url').value = data.url || '';
        document.getElementById('keterangan').value = data.keterangan || '';
        document.getElementById('tahun_terbit').value = data.tahun_terbit || '';
        document.getElementById('jilid').value = data.jilid || '';
        document.getElementById('judul_bab').value = data.judul_bab || '';
        document.getElementById('nama_jurnal').value = data.nama_jurnal || '';
        document.getElementById('volume').value = data.volume || '';
        document.getElementById('nomor').value = data.nomor || '';
        document.getElementById('halaman').value = data.halaman || '';

        toggleTipeInput();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBib')).show();
    }

    document.getElementById('customFileRis').addEventListener('change', function(e){
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });
</script>
