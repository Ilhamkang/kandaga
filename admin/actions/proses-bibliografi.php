<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../../includes/bibliography.php';
kandagaPost(false);
try {
    global $conn;
    $id = (int)($_POST['id'] ?? 0);
    $fields = ['tipe_item','kategori','judul','subjudul','penyusun','editor','penerbit','kota_terbit','isbn_issn','doi','url','keterangan','tahun_terbit','jilid','judul_bab','nama_jurnal','volume','nomor','halaman'];
    $v = [];
    foreach ($fields as $field) $v[$field] = trim((string)($_POST[$field] ?? ''));
    if ($v['judul'] === '' || $v['penyusun'] === '') throw new InvalidArgumentException('Judul dan penyusun wajib diisi.');
    if (!in_array($v['tipe_item'], ['buku','jurnal'], true)) $v['tipe_item'] = 'buku';
    if (!in_array($v['kategori'], ['katalog','referensi primer','referensi sekunder'], true)) $v['kategori'] = 'referensi sekunder';

    $duplicate = kandagaBibliographyDuplicate($conn, $v, $id);
    if (!empty($_POST['check_only'])) {
        kandagaJson(true, $duplicate ? 'Entri dengan metadata yang sama sudah ada.' : 'Tidak ada entri duplikat.', ['duplicate' => $duplicate]);
    }
    if ($duplicate) {
        $label = trim((string)($duplicate['judul'] ?? ''));
        $subjudul = trim((string)($duplicate['subjudul'] ?? ''));
        if ($subjudul !== '') $label .= ': ' . $subjudul;
        throw new InvalidArgumentException('Bibliografi dengan judul, penyusun, tahun, dan kategori yang sama sudah ada (ID ' . (int)$duplicate['id'] . ($label !== '' ? ' — ' . $label : '') . '). Gunakan entri tersebut atau ubah datanya.');
    }

    $oldCover = null;
    if ($id) {
        $q = $conn->prepare('SELECT cover_filename FROM tb_bibliografi_media WHERE id_bibliografi=?'); $q->bind_param('i',$id); $q->execute(); $oldCover = $q->get_result()->fetch_assoc()['cover_filename'] ?? null; $q->close();
        $sql = 'UPDATE tb_bibliografi SET tipe_item=?,kategori=?,judul=?,subjudul=?,penyusun=?,editor=?,penerbit=?,kota_terbit=?,isbn_issn=?,doi=?,url=?,keterangan=?,tahun_terbit=?,jilid=?,judul_bab=?,nama_jurnal=?,volume=?,nomor=?,halaman=? WHERE id=?';
        $st = $conn->prepare($sql); $args = array_values($v); $st->bind_param('sssssssssssssssssssi', $args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7],$args[8],$args[9],$args[10],$args[11],$args[12],$args[13],$args[14],$args[15],$args[16],$args[17],$args[18],$id); $st->execute(); $st->close();
    } else {
        $sql = 'INSERT INTO tb_bibliografi (tipe_item,kategori,judul,subjudul,penyusun,editor,penerbit,kota_terbit,isbn_issn,doi,url,keterangan,tahun_terbit,jilid,judul_bab,nama_jurnal,volume,nomor,halaman) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $a = array_values($v); $st = $conn->prepare($sql); $st->bind_param('sssssssssssssssssss', $a[0],$a[1],$a[2],$a[3],$a[4],$a[5],$a[6],$a[7],$a[8],$a[9],$a[10],$a[11],$a[12],$a[13],$a[14],$a[15],$a[16],$a[17],$a[18]); $st->execute(); $id = (int)$conn->insert_id; $st->close();
    }
    $cover = kandagaUpload('cover', ROOT_PATH . '/assets/images/bibliografi', 'bib-cover');
    $coverUrl = trim((string)($_POST['cover_url'] ?? ''));
    $coverUrl = kandagaValidateExternalUrl($coverUrl, 'URL cover');
    if ($cover !== null || $coverUrl !== '') {
        $st = $conn->prepare('INSERT INTO tb_bibliografi_media (id_bibliografi,cover_filename,cover_url) VALUES (?,?,?) ON DUPLICATE KEY UPDATE cover_filename=VALUES(cover_filename), cover_url=VALUES(cover_url), updated_at=CURRENT_TIMESTAMP');
        $st->bind_param('iss', $id, $cover, $coverUrl); $st->execute(); $st->close();
        if ($cover !== null && $oldCover && $oldCover !== $cover) kandagaRemoveUpload(ROOT_PATH . '/assets/images/bibliografi', $oldCover);
    }
    kandagaJson(true, 'Entri bibliografi berhasil disimpan.', ['id'=>$id, 'redirect'=>'index.php?page=daftar-pustaka']);
} catch (Throwable $e) { kandagaFailure($e); }
