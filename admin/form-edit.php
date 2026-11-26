<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Direct requests must not expose manuscript metadata or edit controls.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/catalog-structure.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

/** Normalize legacy/imported entities for rich-text editors without rewriting the database. */
function kandagaEditorValue($value): string
{
    $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return htmlspecialchars($decoded, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ================= VALIDASI ID NASKAH =================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Akses ilegal: Parameter ID naskah wajib disertakan.");
}
$id_naskah = intval($_GET['id']);

// 1. Ambil Data Utama Naskah yang akan Diedit
$query_katalog = $conn->prepare("SELECT * FROM katalog WHERE id = ?");
$query_katalog->bind_param("i", $id_naskah);
$query_katalog->execute();
$naskah = $query_katalog->get_result()->fetch_assoc();

if (!$naskah) {
    die("Kesalahan: Data naskah tidak ditemukan di database.");
}

// 2. Query Data Master untuk Pilihan Dropdown
$data_bahasa = mysqli_query($conn, "SELECT id_bahasa, nama_bahasa FROM tb_bahasa ORDER BY nama_bahasa ASC");
$data_aksara = mysqli_query($conn, "SELECT id_aksara, nama_aksara FROM tb_aksara ORDER BY nama_aksara ASC");
$data_alas   = mysqli_query($conn, "SELECT id_alas_tulis, nama_alas_tulis FROM tb_alas_tulis ORDER BY nama_alas_tulis ASC");
$data_lokasi = mysqli_query($conn, "SELECT id_lokasi, nama_tempat FROM tb_lokasi ORDER BY nama_tempat ASC");

// 3. Ambil Data Relasi Eksisting (Many-to-Many) untuk Pre-populate Form
function getSelectedRelasi($conn, $tableJunction, $colId, $id_naskah) {
    $sql = "SELECT {$colId} FROM {$tableJunction} WHERE id_naskah = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_naskah);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) {
        $items[] = $r[$colId];
    }
    return $items;
}

$selected_alas   = getSelectedRelasi($conn, 'naskah_alas_tulis', 'id_alas_tulis', $id_naskah);
$selected_bahasa = getSelectedRelasi($conn, 'naskah_bahasa', 'id_bahasa', $id_naskah);
$selected_aksara = getSelectedRelasi($conn, 'naskah_aksara', 'id_aksara', $id_naskah);

// 4. Ambil Data Bibliografi Terkait (FIXED: Menggunakan b.kategori dari tb_bibliografi)
$sql_bib = "SELECT b.id, b.judul, b.penyusun, b.tahun_terbit, b.kategori
            FROM tb_bibliografi b
            JOIN naskah_bibliografi nb ON b.id = nb.id_bibliografi
            WHERE nb.id_naskah = ?";
$stmt_bib = $conn->prepare($sql_bib);
$stmt_bib->bind_param("i", $id_naskah);
$stmt_bib->execute();
$res_bib = $stmt_bib->get_result();

$bib_katalog = [];
$bib_primer = [];
$bib_sekunder = [];

while ($b_row = $res_bib->fetch_assoc()) {
    if ($b_row['kategori'] === 'katalog') $bib_katalog[] = $b_row;
    elseif ($b_row['kategori'] === 'referensi primer') $bib_primer[] = $b_row;
    elseif ($b_row['kategori'] === 'referensi sekunder') $bib_sekunder[] = $b_row;
}
$ks_data = ksLoad($conn, $id_naskah);
$parent_options = mysqli_query($conn, "SELECT id, judul, kode_inventaris FROM katalog WHERE id <> ".(int)$id_naskah." ORDER BY kode_inventaris, judul");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Form Edit Naskah</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
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
    font-size:18px;
    font-weight:700;
    color:#2563eb;
    margin-bottom:20px;
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
.btn-remove-row{
    flex:0 0 auto;
    width:2.125rem;
    height:2.125rem;
    padding:0;
    font-size:1rem;
    line-height:1;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:.5rem;
}
.sticky-submit {
    position: sticky;
    bottom: 0;
    background: #fff;
    padding: 15px 25px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    z-index: 100;
}
</style>
</head>
<body>

<div class="container-fluid py-4">

<div class="page-title">
    <i class="fa-solid fa-pen-to-square"></i> Edit Naskah: <?= htmlspecialchars($naskah['judul']) ?>
</div>

<form id="formEditNaskah" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $id_naskah ?>">

    <div class="form-card">
        <div class="section-title"><i class="fa-solid fa-sitemap"></i> Struktur & Lokasi Historis <small class="text-muted ms-2">(opsional)</small></div>
        <?php $ks=$ks_data['structure']; ?>
        <div class="row">
            <div class="col-md-3 mb-3"><label>Tipe Record</label><select name="record_type" class="form-select"><option value="induk" <?= ($ks['record_type']??'induk')==='induk'?'selected':'' ?>>Naskah induk</option><option value="unit" <?= ($ks['record_type']??'')==='unit'?'selected':'' ?>>Unit naskah</option></select></div>
            <div class="col-md-5 mb-3"><label>Naskah Induk</label><select name="parent_id" class="form-select"><option value="">-- Tidak ada / record induk --</option><?php if ($parent_options): while($p=mysqli_fetch_assoc($parent_options)): ?><option value="<?= (int)$p['id'] ?>" <?= (int)($ks['parent_id']??0)===(int)$p['id']?'selected':'' ?>>#<?= (int)$p['id'] ?> · <?= htmlspecialchars($p['kode_inventaris'].' — '.$p['judul'], ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; endif; ?></select></div>
            <div class="col-md-2 mb-3"><label>Label Unit</label><input type="text" name="unit_label" class="form-control" value="<?= htmlspecialchars((string)($ks['unit_label']??''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-2 mb-3"><label>Urutan</label><input type="number" min="1" name="unit_order" class="form-control" value="<?= htmlspecialchars((string)($ks['unit_order']??''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-12 mb-3"><label>Catatan struktur</label><textarea name="structure_notes" class="form-control" rows="2"><?= htmlspecialchars((string)($ks['notes']??''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
        </div>
        <hr><label class="fw-bold">Lokasi historis</label>
        <div id="location-history-edit">
        <?php $locations=$ks_data['locations']; if (!$locations) $locations=[['location_kind'=>'asal','place_name'=>'','district'=>'','province'=>'','country'=>'','period_label'=>'','certainty'=>'belum_diketahui','source_text'=>'']]; foreach($locations as $i=>$loc): ?>
            <div class="row location-history-row mb-2"><div class="col-md-2"><select name="location_history[<?= (int)$i ?>][kind]" class="form-control"><option value="asal" <?= ($loc['location_kind']??'')==='asal'?'selected':'' ?>>Asal</option><option value="ditemukan" <?= ($loc['location_kind']??'')==='ditemukan'?'selected':'' ?>>Ditemukan</option><option value="sebelumnya" <?= ($loc['location_kind']??'')==='sebelumnya'?'selected':'' ?>>Lokasi sebelumnya</option></select></div><div class="col-md-3"><input name="location_history[<?= (int)$i ?>][place]" class="form-control" placeholder="Tempat/desa" value="<?= htmlspecialchars((string)($loc['place_name']??''),ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-2"><input name="location_history[<?= (int)$i ?>][district]" class="form-control" placeholder="Kota/kabupaten" value="<?= htmlspecialchars((string)($loc['district']??''),ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-2"><input name="location_history[<?= (int)$i ?>][province]" class="form-control" placeholder="Provinsi" value="<?= htmlspecialchars((string)($loc['province']??''),ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-2"><input name="location_history[<?= (int)$i ?>][country]" class="form-control" placeholder="Negara" value="<?= htmlspecialchars((string)($loc['country']??''),ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.location-history-row').remove()">×</button></div></div>
        <?php endforeach; ?></div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addHistoryRowEdit()">+ Lokasi historis</button>
        <small class="text-muted d-block mt-2">Lokasi sekarang tetap mengikuti Lembaga Penyimpan.</small>
        <hr><label class="fw-bold">Identitas alternatif</label>
        <div id="identifier-edit"><?php $ids=$ks_data['identifiers']; if(!$ids)$ids=[['scheme'=>'','identifier_value'=>'','source_label'=>'','is_primary'=>0]]; foreach($ids as $i=>$ident): ?><div class="row identifier-row mb-2"><div class="col-md-3"><input name="identifier[<?= (int)$i ?>][scheme]" class="form-control" placeholder="Shelfmark / Leiden" value="<?= htmlspecialchars((string)($ident['scheme']??''),ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-4"><input name="identifier[<?= (int)$i ?>][value]" class="form-control" placeholder="Nilai identitas" value="<?= htmlspecialchars((string)($ident['identifier_value']??''),ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-4"><input name="identifier[<?= (int)$i ?>][source]" class="form-control" placeholder="Sumber" value="<?= htmlspecialchars((string)($ident['source_label']??''),ENT_QUOTES,'UTF-8') ?>"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.identifier-row').remove()">×</button></div></div><?php endforeach; ?></div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addIdentifierRowEdit()">+ Identitas</button>
    </div>

    <div class="form-card">
        <div class="section-title"><i class="fa-solid fa-address-card"></i> Identitas Utama</div>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label>Judul Naskah</label>
                <input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($naskah['judul']) ?>" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Lembaga Penyimpan</label>
                <select name="id_lokasi" class="form-select select-clean select-location" data-placeholder="Ketik nama lembaga atau lokasi...">
                    <option value="">-- Pilih Lokasi --</option>
                    <?php mysqli_data_seek($data_lokasi, 0); while($l=mysqli_fetch_assoc($data_lokasi)): ?>
                        <option value="<?= $l['id_lokasi'] ?>" <?= $l['id_lokasi'] == $naskah['id_lokasi'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['nama_tempat']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label>Kode Inventaris</label>
                <input type="text" name="kode_inventaris" class="form-control" value="<?= htmlspecialchars($naskah['kode_inventaris'] ?? '') ?>">
            </div>

            <div class="col-md-12 mt-3">
                <label>Riwayat Naskah</label>
                <textarea id="riwayat_naskah" name="riwayat_naskah" class="form-control tinymce" rows="5"><?= kandagaEditorValue($naskah['riwayat_naskah'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="section-title"><i class="fa-solid fa-book-open"></i> Kodikologis & Bahasa</div>
        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Alas Tulis</label>
                <div id="container-alas_tulis">
                    <?php if (!empty($selected_alas)): foreach ($selected_alas as $index => $current_id): ?>
                    <div class="dynamic-row">
                        <select name="alas_tulis[]" class="form-select select-clean">
                            <option value="">-- Alas Tulis --</option>
                            <?php mysqli_data_seek($data_alas, 0); while($a=mysqli_fetch_assoc($data_alas)): ?>
                                <option value="<?= $a['id_alas_tulis'] ?>" <?= $a['id_alas_tulis'] == $current_id ? 'selected' : '' ?>>
                                    <?= $a['nama_alas_tulis'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-danger btn-remove-row">×</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="dynamic-row">
                        <select name="alas_tulis[]" class="form-select select-clean">
                            <option value="">-- Alas Tulis --</option>
                            <?php mysqli_data_seek($data_alas, 0); while($a=mysqli_fetch_assoc($data_alas)): ?>
                                <option value="<?= $a['id_alas_tulis'] ?>"><?= $a['nama_alas_tulis'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addStatic('alas_tulis')">+ Alas Tulis</button>
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Bahasa</label>
                <div id="container-bahasa">
                    <?php if (!empty($selected_bahasa)): foreach ($selected_bahasa as $index => $current_id): ?>
                    <div class="dynamic-row">
                        <select name="bahasa[]" class="form-select select-clean">
                            <option value="">-- Bahasa --</option>
                            <?php mysqli_data_seek($data_bahasa, 0); while($b=mysqli_fetch_assoc($data_bahasa)): ?>
                                <option value="<?= $b['id_bahasa'] ?>" <?= $b['id_bahasa'] == $current_id ? 'selected' : '' ?>>
                                    <?= $b['nama_bahasa'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-danger btn-remove-row">×</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="dynamic-row">
                        <select name="bahasa[]" class="form-select select-clean">
                            <option value="">-- Bahasa --</option>
                            <?php mysqli_data_seek($data_bahasa, 0); while($b=mysqli_fetch_assoc($data_bahasa)): ?>
                                <option value="<?= $b['id_bahasa'] ?>"><?= $b['nama_bahasa'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addStatic('bahasa')">+ Bahasa</button>
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Aksara</label>
                <div id="container-aksara">
                    <?php if (!empty($selected_aksara)): foreach ($selected_aksara as $index => $current_id): ?>
                    <div class="dynamic-row">
                        <select name="aksara[]" class="form-select select-clean">
                            <option value="">-- Aksara --</option>
                            <?php mysqli_data_seek($data_aksara, 0); while($a=mysqli_fetch_assoc($data_aksara)): ?>
                                <option value="<?= $a['id_aksara'] ?>" <?= $a['id_aksara'] == $current_id ? 'selected' : '' ?>>
                                    <?= $a['nama_aksara'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-danger btn-remove-row">×</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="dynamic-row">
                        <select name="aksara[]" class="form-select select-clean">
                            <option value="">-- Aksara --</option>
                            <?php mysqli_data_seek($data_aksara, 0); while($a=mysqli_fetch_assoc($data_aksara)): ?>
                                <option value="<?= $a['id_aksara'] ?>"><?= $a['nama_aksara'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addStatic('aksara')">+ Aksara</button>
            </div>

            <div class="col-md-12 mt-3">
                <label>Deskripsi Fisik</label>
                <textarea id="deskripsi_fisik" name="deskripsi_fisik" class="form-control tinymce" rows="5"><?= kandagaEditorValue($naskah['deskripsi_fisik'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="section-title"><i class="fa-solid fa-layer-group"></i> Bibliografi</div>

        <div id="container-katalog">
            <label class="small fw-bold">Katalog</label>
            <?php if (!empty($bib_katalog)): foreach ($bib_katalog as $index => $bib): ?>
            <div class="dynamic-row">
                <select name="katalog[]" class="form-select select-ajax-bib" data-kategori="katalog">
                    <option value="<?= $bib['id'] ?>" selected><?= "[{$bib['tahun_terbit']}] {$bib['penyusun']} - {$bib['judul']}" ?></option>
                </select>
                <button type="button" class="btn btn-danger btn-remove-row" title="Hapus bibliografi ini">×</button>
            </div>
            <?php endforeach; else: ?>
            <div class="dynamic-row">
                <select name="katalog[]" class="form-select select-ajax-bib" data-kategori="katalog"></select>
            </div>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-sm btn-info" onclick="BibliografiModule.addRow('katalog')">+ Katalog</button>

        <hr>

        <div id="container-pub_primer">
            <label class="small fw-bold">Publikasi Primer</label>
            <?php if (!empty($bib_primer)): foreach ($bib_primer as $index => $bib): ?>
            <div class="dynamic-row">
                <select name="pub_primer[]" class="form-select select-ajax-bib" data-kategori="referensi primer">
                    <option value="<?= $bib['id'] ?>" selected><?= "[{$bib['tahun_terbit']}] {$bib['penyusun']} - {$bib['judul']}" ?></option>
                </select>
                <button type="button" class="btn btn-danger btn-remove-row" title="Hapus bibliografi ini">×</button>
            </div>
            <?php endforeach; else: ?>
            <div class="dynamic-row">
                <select name="pub_primer[]" class="form-select select-ajax-bib" data-kategori="referensi primer"></select>
            </div>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-sm btn-danger" onclick="BibliografiModule.addRow('pub_primer')">+ Primer</button>

        <hr>

        <div id="container-pub_sekunder">
            <label class="small fw-bold">Publikasi Sekunder</label>
            <?php if (!empty($bib_sekunder)): foreach ($bib_sekunder as $index => $bib): ?>
            <div class="dynamic-row">
                <select name="pub_sekunder[]" class="form-select select-ajax-bib" data-kategori="referensi sekunder">
                    <option value="<?= $bib['id'] ?>" selected><?= "[{$bib['tahun_terbit']}] {$bib['penyusun']} - {$bib['judul']}" ?></option>
                </select>
                <button type="button" class="btn btn-danger btn-remove-row" title="Hapus bibliografi ini">×</button>
            </div>
            <?php endforeach; else: ?>
            <div class="dynamic-row">
                <select name="pub_sekunder[]" class="form-select select-ajax-bib" data-kategori="referensi sekunder"></select>
            </div>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-sm btn-success" onclick="BibliografiModule.addRow('pub_sekunder')">+ Sekunder</button>
    </div>

    <div class="form-card">
        <div class="section-title"><i class="fa-solid fa-align-left"></i> Deskripsi Isi</div>
        <textarea id="deskripsi_isi" name="deskripsi_isi" class="form-control mb-3 tinymce" rows="10"><?= kandagaEditorValue($naskah['deskripsi_isi'] ?? '') ?></textarea>

        <label>Kata Kunci</label>
        <input type="text" name="kata_kunci" class="form-control" value="<?= htmlspecialchars($naskah['kata_kunci'] ?? '') ?>">
    </div>

    <div class="form-card">
        <div class="section-title"><i class="fa-solid fa-link"></i> Akses Digital & Media</div>

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 col-form-label">Penyedia Akses</label>
            <div class="col-md-9">
                <input type="text" name="penyedia_akses" class="form-control" value="<?= htmlspecialchars($naskah['penyedia_akses'] ?? '') ?>">
            </div>
        </div>

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 col-form-label">URL Web</label>
            <div class="col-md-9">
                <input type="url" name="url_web" class="form-control" value="<?= htmlspecialchars($naskah['url_web'] ?? '') ?>">
            </div>
        </div>

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 col-form-label">URL Repositori</label>
            <div class="col-md-9">
                <input type="url" name="url_repositori" class="form-control" value="<?= htmlspecialchars($naskah['url_repositori'] ?? '') ?>">
            </div>
        </div>

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 col-form-label">IIIF Manifest</label>
            <div class="col-md-9">
                <input type="url" name="iiif_manifest" class="form-control" value="<?= htmlspecialchars($naskah['iiif_manifest'] ?? '') ?>">
            </div>
        </div>

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 col-form-label">URL PDF</label>
            <div class="col-md-9">
                <input type="url" name="url_pdf" class="form-control" value="<?= htmlspecialchars($naskah['url_pdf'] ?? '') ?>">
            </div>
        </div>

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 col-form-label">Contoh Gambar Sampul/Isi</label>
            <div class="col-md-9">
                <label>URL gambar eksternal (opsional)</label>
                <input type="url" name="url_gambar_eksternal" class="form-control mb-2" value="<?= htmlspecialchars($naskah['url_gambar_eksternal'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" name="gambar" class="form-control mb-2">
                <?php if (!empty($naskah['gambar'])): ?>
                    <small class="text-muted d-block">
                        <i class="fa-solid fa-image me-1"></i> File saat ini:
                        <a href="<?= BASE_URL ?>/assets/images/naskah/<?= $naskah['gambar'] ?>" target="_blank" class="text-primary fw-bold">
                            <?= htmlspecialchars($naskah['gambar']) ?>
                        </a>
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="sticky-submit">
        <button type="button" class="btn btn-light" onclick="history.back()">Batal</button>
        <button type="submit" class="btn btn-primary">Update Data</button>
    </div>
</form>

</div>







<script>
$(function(){
    initSelect2();
    BibliografiModule.init(document);
    initTinyMCE('.tinymce', 300);

    // Pasang handler hapus baris dinamis bawaan untuk data lama
    $(document).on('click', '.btn-remove-row', function() {
        $(this).parent().remove();
    });
});

function addHistoryRowEdit(){
    const box=document.getElementById('location-history-edit'); const i=box.querySelectorAll('.location-history-row').length; const row=document.createElement('div'); row.className='row location-history-row mb-2';
    row.innerHTML=`<div class="col-md-2"><select name="location_history[${i}][kind]" class="form-select"><option value="asal">Asal</option><option value="ditemukan">Ditemukan</option><option value="sebelumnya">Lokasi sebelumnya</option></select></div><div class="col-md-3"><input name="location_history[${i}][place]" class="form-control" placeholder="Tempat/desa"></div><div class="col-md-2"><input name="location_history[${i}][district]" class="form-control" placeholder="Kota/kabupaten"></div><div class="col-md-2"><input name="location_history[${i}][province]" class="form-control" placeholder="Provinsi"></div><div class="col-md-2"><input name="location_history[${i}][country]" class="form-control" placeholder="Negara"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.location-history-row').remove()">×</button></div>`;
    box.appendChild(row);
}
function addIdentifierRowEdit(){const box=document.getElementById('identifier-edit');const i=box.querySelectorAll('.identifier-row').length;const row=document.createElement('div');row.className='row identifier-row mb-2';row.innerHTML=`<div class="col-md-3"><input name="identifier[${i}][scheme]" class="form-control" placeholder="Shelfmark / Leiden"></div><div class="col-md-4"><input name="identifier[${i}][value]" class="form-control" placeholder="Nilai identitas"></div><div class="col-md-4"><input name="identifier[${i}][source]" class="form-control" placeholder="Sumber"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.identifier-row').remove()">×</button></div>`;box.appendChild(row);}

function initTinyMCE(selector, height) {
    if (typeof tinymce === 'undefined') return;
    tinymce.remove(selector);
    tinymce.init({
        selector: selector,
        height: height,
        menubar: false,
        branding: false,
        plugins: 'lists link image code fullscreen table paste wordcount',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link image | alignleft aligncenter alignright | code fullscreen',
        setup: function (editor) {
            editor.on('change keyup', function () {
                tinymce.triggerSave();
            });
        }
    });
}

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

window.addStatic = function(type) {
    const container = document.getElementById('container-' + type);
    const selects = container.querySelectorAll('select');
    const lastSelect = selects[selects.length - 1];

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
        return;
    }

    const firstRow = container.querySelector('.dynamic-row');


    const newRow = $(firstRow).clone(false);
    newRow.find('select').val('').removeClass('select2-hidden-accessible');
    newRow.find('.select2-container').remove();
    newRow.find('select').removeAttr('data-select2-id tabindex aria-hidden').find('option').removeAttr('data-select2-id');

    if (!newRow.find('.btn-remove-row').length) {
        newRow.append('<button type="button" class="btn btn-danger btn-remove-row ms-2">×</button>');
    }

    container.appendChild(newRow[0]);
    initSelect2();
};

window.BibliografiModule = (function(){
    function init(el){
        $(el).find('.select-ajax-bib').each(function(){
            const $el = $(this);
            if ($el.data('init')) return;
            $el.data('init', true);

            $el.select2({
                width: '100%',
                minimumInputLength: 2,
                language: {
                    inputTooShort: () => "Ketik minimal 2 karakter untuk mencari...",
                    noResults: () => "Data bibliografi tidak ditemukan",
                    searching: () => "Mencari..."
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
        const selects = container.querySelectorAll('select');
        const lastSelect = selects[selects.length - 1];

        if (lastSelect && lastSelect.value === "") {
            Swal.fire({
                icon: 'warning',
                title: 'Heup...',
                text: 'Silakan pilih data pada field sebelumnya terlebih dahulu!',
                toast: true,
                position: 'top-center',
                showConfirmButton: false,
                timer: 2000
            });
            return;
        }

        const row = document.createElement('div');
        row.className = 'dynamic-row';
        row.innerHTML = `
            <select name="${type}[]" class="form-select select-ajax-bib" data-kategori="${type === 'katalog' ? 'katalog' : (type === 'pub_primer' ? 'referensi primer' : 'referensi sekunder')}"></select>
            <button type="button" class="btn btn-danger btn-remove-row">×</button>
        `;

        container.appendChild(row);
        init(container);
    }

    return { init, addRow };
})();

// AJAX SUBMIT FORM EDIT
$('#formEditNaskah').on('submit', function(e){
    e.preventDefault();

    // --- TAMBAHKAN INI ---
    // Pastikan data dari TinyMCE dipindahkan ke textarea sebelum FormData dibuat
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
    // ---------------------

    let fd = new FormData(this);

    $.ajax({
        url: '<?= BASE_URL ?>/admin/actions/proses-edit.php',
        method: 'POST',
        dataType: 'json',
        data: fd,
        contentType: false,
        processData: false,
        success: function(res){
            if (!res.success) { Swal.fire('Gagal', res.message || 'Penyimpanan gagal', 'error'); return; }
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: res.message
            }).then(() => {
                window.location.href = 'index.php?page=tampil-naskah';
            });
        },
        error: function(xhr, status, error) {
            Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghubungi server.', 'error');
        }
    });
});
</script>
</body>
</html>
