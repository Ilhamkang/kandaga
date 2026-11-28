<?php
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/ownership.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/log-tracker.php';
require_once __DIR__ . '/catalog-structure.php';
require_once __DIR__ . '/../../includes/access-options.php';

function kandagaIds($field) {
    $values = $_POST[$field] ?? [];
    if (!is_array($values)) throw new InvalidArgumentException('Format pilihan ' . $field . ' tidak valid.');
    $ids = [];
    foreach ($values as $value) {
        if ($value === '' || $value === null) continue;
        if (!is_scalar($value) || !ctype_digit((string)$value) || (int)$value < 1) throw new InvalidArgumentException('Pilihan ' . $field . ' tidak valid.');
        $ids[] = (int)$value;
    }
    return array_values(array_unique($ids));
}

function kandagaRelations($conn, $id, $replace) {
    $groups = [
        ['aksara', 'naskah_aksara', 'id_aksara', 'tb_aksara', 'id_aksara'],
        ['bahasa', 'naskah_bahasa', 'id_bahasa', 'tb_bahasa', 'id_bahasa'],
        ['alas_tulis', 'naskah_alas_tulis', 'id_alas_tulis', 'tb_alas_tulis', 'id_alas_tulis']
    ];
    foreach ($groups as [$field, $table, $column, $master, $masterKey]) {
        $ids = kandagaIds($field);
        foreach ($ids as $value) {
            $check = $conn->prepare("SELECT $masterKey FROM $master WHERE $masterKey = ?");
            $check->bind_param('i', $value); $check->execute();
            if (!$check->get_result()->fetch_assoc()) throw new InvalidArgumentException('Pilihan ' . $field . ' sudah tidak tersedia.');
        }
        if ($replace) {
            $delete = $conn->prepare("DELETE FROM $table WHERE id_naskah = ?");
            $delete->bind_param('i', $id); $delete->execute();
        }
        $stmt = $conn->prepare("INSERT INTO $table (id_naskah, $column) VALUES (?, ?)");
        foreach ($ids as $value) { $stmt->bind_param('ii', $id, $value); $stmt->execute(); }
    }
    $bibs = array_values(array_unique(array_merge(kandagaIds('katalog'), kandagaIds('pub_primer'), kandagaIds('pub_sekunder'))));
    foreach ($bibs as $value) {
        $check = $conn->prepare('SELECT id FROM tb_bibliografi WHERE id = ?');
        $check->bind_param('i', $value); $check->execute();
        if (!$check->get_result()->fetch_assoc()) throw new InvalidArgumentException('Bibliografi yang dipilih sudah tidak tersedia.');
    }
    if ($replace) {
        $delete = $conn->prepare('DELETE FROM naskah_bibliografi WHERE id_naskah = ?');
        $delete->bind_param('i', $id); $delete->execute();
    }
    $stmt = $conn->prepare('INSERT INTO naskah_bibliografi (id_naskah, id_bibliografi) VALUES (?, ?)');
    foreach ($bibs as $value) { $stmt->bind_param('ii', $id, $value); $stmt->execute(); }
}

function kandagaSaveManuscript($editing = false) {
    global $conn;
    kandagaPost();
    $directory = ROOT_PATH . '/assets/images/naskah';
    $newFile = null;
    $transaction = false;
    try {
        $id = $editing ? filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
        if ($editing && !$id) throw new InvalidArgumentException('ID naskah tidak valid.');
        $fields = ['judul', 'id_lokasi', 'kode_inventaris', 'deskripsi_fisik', 'deskripsi_isi', 'kata_kunci', 'riwayat_naskah', 'penyedia_akses', 'url_web', 'url_repositori', 'iiif_manifest', 'url_pdf', 'url_gambar_eksternal', 'mode_akses_fisik', 'pihak_kontak', 'status_arsip_digital', 'keterangan_arsip_digital'];
        $values = [];
        foreach ($fields as $field) {
            if (isset($_POST[$field]) && !is_string($_POST[$field])) throw new InvalidArgumentException('Format ' . $field . ' tidak valid.');
            $values[] = trim($_POST[$field] ?? '');
        }
        // Kode inventaris is optional: private collections and historical
        // records may legitimately have no assigned shelfmark.  Keep the
        // empty value compatible with existing installations and show a
        // neutral placeholder in public views.
        if ($values[0] === '') throw new InvalidArgumentException('Judul wajib diisi.');
        foreach ([0, 2, 5, 8, 9, 10, 11, 12, 13, 14, 15] as $index) {
            if (mb_strlen($values[$index], 'UTF-8') > 255) throw new InvalidArgumentException('Isi ' . $fields[$index] . ' maksimal 255 karakter.');
        }
        $values[1] = filter_var($values[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$values[1]) throw new InvalidArgumentException('Pilih lokasi penyimpanan yang valid.');
        foreach ([8, 9, 10, 11, 12] as $index) {
            if ($values[$index] !== '' && (!filter_var($values[$index], FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($values[$index], PHP_URL_SCHEME) ?? ''), ['http', 'https'], true))) throw new InvalidArgumentException('URL ' . $fields[$index] . ' harus menggunakan http atau https.');
        }
        if ($values[13] !== '' && !array_key_exists($values[13], kandagaAccessModeOptions())) throw new InvalidArgumentException('Mode akses fisik tidak valid.');
        if ($values[15] !== '' && !array_key_exists($values[15], kandagaDigitalStatusOptions())) throw new InvalidArgumentException('Status arsip digital tidak valid.');
        $values[14] = trim(strip_tags($values[14]));
        $values[16] = trim(strip_tags($values[16]));
        if (mb_strlen($values[16], 'UTF-8') > 500) throw new InvalidArgumentException('Isi keterangan arsip digital maksimal 500 karakter.');
        $conn->begin_transaction(); $transaction = true;
        $old = null;
        if ($editing) {
            $stmt = $conn->prepare('SELECT id_lokasi, gambar, url_gambar_eksternal FROM katalog WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $id); $stmt->execute(); $old = $stmt->get_result()->fetch_assoc();
            if (!$old) throw new InvalidArgumentException('Naskah tidak ditemukan.');
        }
        $location = $conn->prepare('SELECT id_lokasi FROM tb_lokasi WHERE id_lokasi = ?');
        $location->bind_param('i', $values[1]); $location->execute();
        if (!$location->get_result()->fetch_assoc()) throw new InvalidArgumentException('Lokasi penyimpanan tidak ditemukan.');
        if ($editing) {
            if (!kandagaBolehNaskah($conn, $id, $values[1], $old)) {
                kandagaDeny('Naskah ini dipegang kurator. Hanya kurator penanggung jawab atau administrator yang dapat mengubahnya.');
            }
        } elseif (!kandagaBolehLokasi($conn, $values[1])) {
            kandagaDeny('Koleksi ini dipegang kurator. Pilih koleksi lain atau hubungi administrator.');
        }
        $newFile = kandagaUpload('gambar', $directory, 'naskah');
        if ($editing && !array_key_exists('url_gambar_eksternal', $_POST)) $values[12] = $newFile ? '' : ($old['url_gambar_eksternal'] ?? '');
        if ($newFile) $values[12] = ''; // A selected local upload becomes the displayed image.
        $fields[] = 'gambar';
        $image = $newFile ?? ($old['gambar'] ?? '');
        if (!$newFile && $values[12] !== '' && $values[12] !== ($old['url_gambar_eksternal'] ?? '')) $image = '';
        $values[] = $image;
        $types = 'si' . str_repeat('s', count($fields) - 2);
        $username = $_SESSION['username'];
        if ($editing) {
            $assignments = implode(', ', array_map(fn($name) => "$name = ?", $fields));
            $stmt = $conn->prepare("UPDATE katalog SET $assignments, penyunting_terakhir = ?, tanggal_update = NOW() WHERE id = ?");
            $values[] = $username; $values[] = $id;
            $stmt->bind_param($types . 'si', ...$values);
        } else {
            $stmt = $conn->prepare('INSERT INTO katalog (tanggal_input, penginput_awal, ' . implode(', ', $fields) . ') VALUES (NOW(), ' . implode(', ', array_fill(0, count($fields) + 1, '?')) . ')');
            array_unshift($values, $username);
            $stmt->bind_param('s' . $types, ...$values);
        }
        $stmt->execute();
        if (!$editing) $id = $conn->insert_id;
        kandagaRelations($conn, $id, $editing);
        // Stage 1 fields are optional and stored in additive tables only.
        ksSave($conn, $id);
        logAktivitas($conn, $editing ? 'edit' : 'input', ($editing ? 'Edit naskah: ' : 'Input naskah: ') . $values[$editing ? 0 : 1], $id);
        $conn->commit(); $transaction = false;
        kandagaJson(true, $editing ? 'Data naskah berhasil diperbarui!' : 'Data naskah berhasil disimpan!', ['id' => $id]);
    } catch (Throwable $error) {
        if ($transaction) $conn->rollback();
        if ($newFile) kandagaRemoveUpload($directory, $newFile);
        kandagaFailure($error);
    }
}
