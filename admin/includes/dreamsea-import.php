<?php
// DREAMSEA ingestion: strict staging, explicit moderation, and normalized manuscript fields.
require_once __DIR__ . '/catalog-import.php';
require_once __DIR__ . '/upload.php';

function dsReady() {
    $names = ['katalog_import_batches','katalog_import_rows','katalog_import_sources','katalog_import_audit','katalog_structure','katalog_identifiers','katalog_location_history'];
    $r = kiQuery('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN (?,?,?,?,?,?,?)', $names)->get_result();
    return $r->num_rows === count($names);
}
function dsFields() {
    return [
        'msid', 'ds_id', 'record_type', 'parent_msid', 'unit_label', 'unit_order', 'structure_notes',
        'Judul', 'Kode_Inventaris', 'Lokasi_Target', 'Bahasa_List', 'Aksara_List', 'Alas_Tulis_List',
        'Deskripsi_Fisik', 'Deskripsi_Isi', 'Riwayat_Naskah', 'Kata_Kunci', 'Penyedia_Akses',
        'URL_Web', 'URL_Repositori', 'IIIF_Manifest', 'URL_PDF', 'url_gambar_eksternal',
        'Identifier_Scheme', 'Identifier_Value', 'Identifier_Source',
        'Location_Kind', 'Location_Place', 'Location_District', 'Location_Province',
        'Location_Country', 'Location_Period', 'Location_Certainty', 'Location_Source', 'Bibliografi_ID'
    ];
}
function dsTermAliases() {
    return [
        'javanese' => 'Jawa', 'sundanese' => 'Sunda', 'sanskrit' => 'Sansakerta',
        'malay' => 'Melayu', 'arabic' => 'Arab', 'dutch' => 'Belanda', 'chinese' => 'Mandarin',
        'latin' => 'Latin', 'java' => 'Jawa', 'sunda' => 'Sunda', 'kawi' => 'Kawi', 'pegon' => 'Pegon',
        'cacarakan' => 'Cacarakan', 'palm-leaf' => 'Lontar', 'palm leaf' => 'Lontar',
        'paper' => 'Kertas', 'european paper' => 'Kertas Eropa', 'daluang' => 'Daluang',
        'gebang' => 'Gebang', 'bamboo' => 'Bambu', 'wood' => 'Kayu', 'bone' => 'Tulang'
    ];
}
function dsNormalTerm($value) {
    $term = trim((string)$value);
    if ($term === '') return '';
    $alias = dsTermAliases()[kiNorm($term)] ?? null;
    return $alias ?? $term;
}
function dsSplit($value) {
    $out = [];
    foreach (preg_split('/[;|]/u', (string)$value) as $part) {
        $part = dsNormalTerm($part);
        if ($part !== '' && !in_array($part, $out, true)) $out[] = $part;
    }
    return $out;
}
function dsCode($value) {
    return strtoupper(preg_replace('/[^0-9A-Za-z]+/', '', (string)$value));
}
function dsIdentity($raw) {
    $msid = trim((string)($raw['msid'] ?? ''));
    $ds = trim((string)($raw['ds_id'] ?? ''));
    if ($msid === '' && $ds === '') throw new InvalidArgumentException('Baris harus memiliki msid atau ds_id.');
    if (preg_match('/^\d{1,6}$/', $msid)) $msid = 'MS' . str_pad($msid, 6, '0', STR_PAD_LEFT);
    if ($ds !== '' && !preg_match('/^DS\s*\d{4}\s*\d{5}$/i', $ds)) throw new InvalidArgumentException('ds_id harus berbentuk DS 0000 00000.');
    if ($ds === '' && preg_match('/\b(DS\s*\d{4}\s*\d{5})\b/i', (string)($raw['Kode_Inventaris'] ?? ''), $m)) $ds = $m[1];
    if ($ds === '' && preg_match('/\b(DS\s*\d{4}\s*\d{5})\b/i', (string)($raw['URL_Repositori'] ?? ''), $m)) $ds = $m[1];
    $primary = $msid !== '' ? ['scheme' => 'dreamsea-msid', 'value' => $msid] : ['scheme' => 'dreamsea-ds', 'value' => kiNorm($ds)];
    $key = hash('sha256', $primary['scheme'] . '|' . kiNorm($primary['value']));
    return ['msid' => $msid, 'ds_id' => $ds, 'source_key' => $key, 'primary' => $primary];
}
function dsDraft($raw) {
    foreach (dsFields() as $field) if (!array_key_exists($field, $raw)) $raw[$field] = '';
    $title = trim((string)$raw['Judul']);
    if ($title === '') throw new InvalidArgumentException('Judul wajib diisi.');
    if (mb_strlen($title) > 255) throw new InvalidArgumentException('Judul maksimal 255 karakter.');
    $identity = dsIdentity($raw);
    $inventory = trim((string)$raw['Kode_Inventaris']);
    if (mb_strlen($inventory) > 255) throw new InvalidArgumentException('Kode inventaris maksimal 255 karakter.');
    $certainty = trim((string)$raw['Location_Certainty']);
    if ($certainty !== '' && !in_array($certainty, ['pasti', 'kemungkinan', 'dugaan', 'belum_diketahui'], true)) throw new InvalidArgumentException('Location_Certainty tidak dikenal.');
    $kind = trim((string)$raw['Location_Kind']) ?: 'asal';
    if (!in_array($kind, ['asal', 'ditemukan', 'sebelumnya'], true)) throw new InvalidArgumentException('Location_Kind tidak dikenal.');
    $order = filter_var($raw['unit_order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $recordType = trim((string)$raw['record_type']) ?: 'induk';
    if (!in_array($recordType, ['induk', 'unit'], true)) throw new InvalidArgumentException('record_type harus induk atau unit.');
    $fields = [
        'judul' => $title, 'kode_inventaris' => $inventory, 'id_lokasi' => 0,
        'deskripsi_fisik' => trim((string)$raw['Deskripsi_Fisik']),
        'deskripsi_isi' => trim((string)$raw['Deskripsi_Isi']),
        'riwayat_naskah' => trim((string)$raw['Riwayat_Naskah']),
        'kata_kunci' => trim((string)$raw['Kata_Kunci']),
        'penyedia_akses' => trim((string)$raw['Penyedia_Akses']) ?: 'DREAMSEA',
        'url_web' => trim((string)$raw['URL_Web']), 'url_repositori' => trim((string)$raw['URL_Repositori']),
        'iiif_manifest' => trim((string)$raw['IIIF_Manifest']), 'url_pdf' => trim((string)$raw['URL_PDF']),
        'url_gambar_eksternal' => trim((string)$raw['url_gambar_eksternal'])
    ];
    foreach (['url_web' => 'URL_Web', 'url_repositori' => 'URL_Repositori', 'iiif_manifest' => 'IIIF_Manifest', 'url_pdf' => 'URL_PDF', 'url_gambar_eksternal' => 'url_gambar_eksternal'] as $key => $label) {
        $fields[$key] = kandagaValidateExternalUrl($fields[$key], $label);
    }
    $identifiers = [];
    $extraScheme = trim((string)$raw['Identifier_Scheme']);
    $extraValue = trim((string)$raw['Identifier_Value']);
    if ($extraScheme !== '' && $extraValue !== '') {
        $identifiers[] = ['scheme' => mb_substr($extraScheme, 0, 64), 'value' => mb_substr($extraValue, 0, 255), 'source' => mb_substr((string)$raw['Identifier_Source'], 0, 255), 'primary' => false];
    }
    foreach ([$identity['primary'], ['scheme' => 'dreamsea-ds', 'value' => kiNorm($identity['ds_id'])]] as $candidate) {
        if ($candidate['value'] === '') continue;
        $identifiers[] = ['scheme' => $candidate['scheme'], 'value' => $candidate['value'], 'source' => 'DREAMSEA', 'primary' => $candidate['scheme'] === $identity['primary']['scheme']];
    }
    $shelfmark = '';
    if (strpos($inventory, ';') !== false) $shelfmark = trim(substr($inventory, strpos($inventory, ';') + 1));
    if ($shelfmark !== '') $identifiers[] = ['scheme' => 'shelfmark', 'value' => mb_substr($shelfmark, 0, 255), 'source' => 'DREAMSEA', 'primary' => false];
    $bib = [];
    foreach (preg_split('/[,;|]/u', (string)$raw['Bibliografi_ID']) as $value) {
        $value = filter_var(trim($value), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value) $bib[] = $value;
    }
    $locationHistory = [];
    if (trim((string)$raw['Location_Place']) !== '' || trim((string)$raw['Lokasi_Target']) !== '') {
        $locationHistory[] = [
            'kind' => $kind, 'place' => mb_substr(trim((string)$raw['Location_Place']) ?: (string)$raw['Lokasi_Target'], 0, 255),
            'district' => mb_substr((string)$raw['Location_District'], 0, 150), 'province' => mb_substr((string)$raw['Location_Province'], 0, 150),
            'country' => mb_substr((string)$raw['Location_Country'], 0, 150), 'period' => mb_substr((string)$raw['Location_Period'], 0, 150),
            'certainty' => $certainty ?: 'belum_diketahui', 'source' => (string)$raw['Location_Source'], 'sort_order' => 1
        ];
    }
    return [
        'source' => array_merge($raw, $identity),
        'fields' => $fields,
        'relations' => ['bahasa' => dsSplit($raw['Bahasa_List']), 'aksara' => dsSplit($raw['Aksara_List']), 'alas_tulis' => dsSplit($raw['Alas_Tulis_List'])],
        'structure' => ['record_type' => $recordType, 'parent_msid' => trim((string)$raw['parent_msid']), 'unit_label' => mb_substr((string)$raw['unit_label'], 0, 100), 'unit_order' => $order ?: null, 'notes' => (string)$raw['structure_notes']],
        'identifiers' => $identifiers, 'location_history' => $locationHistory, 'bibliografi' => array_values(array_unique($bib))
    ];
}
function dsKey($draft) { return $draft['source']['source_key']; }
function dsSuggestions($draft, $masters) {
    $out = ['lokasi' => null, 'bahasa' => [], 'aksara' => [], 'alas_tulis' => [], 'parent' => null, 'unresolved' => []];
    $target = (string)($draft['source']['Lokasi_Target'] ?? '');
    foreach ($masters['lokasi'] as $item) if (kiNorm($item['name']) === kiNorm($target)) { $out['lokasi'] = (int)$item['id']; break; }
    if ($out['lokasi'] === null && $target !== '') $out['unresolved'][] = ['group' => 'lokasi', 'term' => $target];
    foreach (['bahasa', 'aksara', 'alas_tulis'] as $group) {
        foreach ($draft['relations'][$group] as $term) {
            $found = null;
            foreach ($masters[$group] as $item) if (kiNorm($item['name']) === kiNorm($term)) { $found = (int)$item['id']; break; }
            if ($found === null) $out['unresolved'][] = ['group' => $group, 'term' => $term]; else $out[$group][] = $found;
        }
    }
    return $out;
}
function dsCandidates($draft) {
    $out = [];
    $identity = $draft['source'];
    $schemes = ['dreamsea-msid' => [$identity['msid']], 'dreamsea-ds' => [kiNorm($identity['ds_id'])]];
    foreach ($schemes as $scheme => $values) {
        foreach ($values as $value) {
            if ($value === '') continue;
            $rows = kiQuery('SELECT i.id_naskah, k.judul, k.kode_inventaris FROM katalog_identifiers i LEFT JOIN katalog k ON k.id=i.id_naskah WHERE i.scheme=? AND i.identifier_value=?', [$scheme, $value])->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as $row) $out[] = ['id' => (int)$row['id_naskah'], 'judul' => $row['judul'], 'kode_inventaris' => $row['kode_inventaris'], 'reason' => 'Identitas ' . $scheme . ' sama', 'score' => 100];
        }
    }
    $linked = kiLinked($draft['source']['source_key']);
    if ($linked) $out[] = ['id' => (int)$linked['katalog_id'], 'judul' => $linked['judul'], 'kode_inventaris' => '', 'reason' => 'Sumber sudah ditautkan', 'score' => 100];
    $codes = kiCodes($draft['fields']['kode_inventaris']);
    $title = kiCode($draft['fields']['judul']);
    $rows = kiQuery('SELECT k.id, k.judul, k.kode_inventaris FROM katalog k')->get_result();
    while ($row = $rows->fetch_assoc()) {
        if ($codes && array_intersect($codes, kiCodes($row['kode_inventaris']))) $out[] = ['id' => (int)$row['id'], 'judul' => $row['judul'], 'kode_inventaris' => $row['kode_inventaris'], 'reason' => 'Kode inventaris sama/varian', 'score' => 80];
        $other = kiCode($row['judul']);
        if ($title !== '' && $title === $other && mb_strlen($title) >= 8) $out[] = ['id' => (int)$row['id'], 'judul' => $row['judul'], 'kode_inventaris' => $row['kode_inventaris'], 'reason' => 'Judul sama', 'score' => 60];
    }
    $seen = [];
    $out = array_values(array_filter($out, function ($item) use (&$seen) {
        $key = $item['id'] . '|' . $item['reason'];
        if (isset($seen[$key])) return false;
        $seen[$key] = true;
        return true;
    }));
    usort($out, fn($a, $b) => $b['score'] <=> $a['score'] ?: $a['id'] <=> $b['id']);
    return array_slice($out, 0, 20);
}
function dsSnapshot($id, $lock = false) {
    $snap = kiSnapshot($id, $lock);
    $snap['data']['structure'] = kiQuery('SELECT record_type,parent_id,unit_label,unit_order,notes FROM katalog_structure WHERE id_naskah=?' . ($lock ? ' FOR UPDATE' : ''), [$id])->get_result()->fetch_assoc() ?: null;
    $snap['data']['identifiers'] = kiQuery('SELECT scheme,identifier_value,source_label,is_primary FROM katalog_identifiers WHERE id_naskah=? ORDER BY id' . ($lock ? ' FOR UPDATE' : ''), [$id])->get_result()->fetch_all(MYSQLI_ASSOC);
    $snap['data']['location_history'] = kiQuery('SELECT location_kind,place_name,district,province,country,period_label,certainty,source_text,sort_order FROM katalog_location_history WHERE id_naskah=? ORDER BY sort_order,id' . ($lock ? ' FOR UPDATE' : ''), [$id])->get_result()->fetch_all(MYSQLI_ASSOC);
    $snap['revision'] = hash('sha256', kiJson($snap['data']));
    return $snap;
}
function dsValidId($value, $table, $column) {
    $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$value || !kiQuery("SELECT $column FROM $table WHERE $column=?", [$value])->get_result()->fetch_assoc()) throw new InvalidArgumentException('Pilihan ' . $table . ' tidak ditemukan di Kandaga.');
    return $value;
}
function dsApprove($input) {
    global $conn;
    $id = filter_var($input['row_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = (string)($input['action'] ?? '');
    if (!$id || !in_array($action, ['created', 'merged', 'rejected', 'deferred'], true)) throw new InvalidArgumentException('Keputusan moderasi tidak valid.');
    if (!is_string($input['note'] ?? '') || mb_strlen($input['note']) > 4000) throw new InvalidArgumentException('Catatan maksimal 4.000 karakter.');
    if (!dsReady()) throw new InvalidArgumentException('Jalankan migrations/005_catalog_import.sql dan migrations/013_catalog_structure_stage1.sql pada database aplikasi dahulu.');
    $conn->begin_transaction();
    try {
        $row = kiRow($id, true);
        if (!in_array($row['status'], ['pending', 'deferred'], true)) throw new InvalidArgumentException('Baris ini sudah diputuskan. Muat ulang antrean.');
        if (in_array($action, ['rejected', 'deferred'], true)) {
            kiAudit($row, $action, null, null, null, $input);
            $conn->commit();
            return ['id' => null, 'action' => $action];
        }
        $fields = $input['fields'] ?? null;
        $relations = $input['relations'] ?? [];
        if (!is_array($fields) || !is_array($relations)) throw new InvalidArgumentException('Format form moderasi tidak valid.');
        $editable = ['judul', 'kode_inventaris', 'deskripsi_fisik', 'deskripsi_isi', 'riwayat_naskah', 'kata_kunci', 'penyedia_akses', 'url_web', 'url_repositori', 'iiif_manifest', 'url_pdf', 'url_gambar_eksternal'];
        $resolved = [];
        foreach ($editable as $field) {
            if (!is_string($fields[$field] ?? null)) throw new InvalidArgumentException('Field ' . $field . ' tidak valid.');
            $value = trim($fields[$field]);
            $max = in_array($field, ['judul', 'kode_inventaris', 'kata_kunci', 'penyedia_akses', 'url_web', 'url_repositori', 'iiif_manifest', 'url_pdf', 'url_gambar_eksternal'], true) ? 255 : 50000;
            if (mb_strlen($value) > $max) throw new InvalidArgumentException($field . ' terlalu panjang.');
            if (in_array($field, ['url_web', 'url_repositori', 'iiif_manifest', 'url_pdf', 'url_gambar_eksternal'], true)) $value = kandagaValidateExternalUrl($value, $field);
            $resolved[$field] = in_array($field, ['deskripsi_fisik', 'deskripsi_isi', 'riwayat_naskah'], true) ? kiHtml($value) : $value;
        }
        if ($resolved['judul'] === '') throw new InvalidArgumentException('Judul wajib diisi.');
        $locationList = ['lokasi' => ['tb_lokasi', 'id_lokasi'], 'bahasa' => ['tb_bahasa', 'id_bahasa'], 'aksara' => ['tb_aksara', 'id_aksara'], 'alas_tulis' => ['tb_alas_tulis', 'id_alas_tulis']];
        $location = dsValidId($input['id_lokasi'] ?? null, 'tb_lokasi', 'id_lokasi');
        if (($input['location_confirmed'] ?? false) !== true) throw new InvalidArgumentException('Konfirmasikan lokasi penyimpanan saat ini; koleksi historis tidak otomatis menjadi lokasi sekarang.');
        if (($input['metadata_confirmed'] ?? false) !== true) throw new InvalidArgumentException('Periksa dan konfirmasikan padanan metadata sebelum menyimpan keputusan.');
        $finalRelations = [];
        foreach (['bahasa', 'aksara', 'alas_tulis'] as $group) {
            $values = $relations[$group] ?? [];
            if (!is_array($values)) throw new InvalidArgumentException('Pilihan relasi tidak valid.');
            $valid = [];
            foreach ($values as $value) $valid[] = dsValidId($value, $locationList[$group][0], $locationList[$group][1]);
            $finalRelations[$group] = array_values(array_unique($valid));
        }
        $bibliografi = [];
        foreach ((array)($input['bibliografi'] ?? []) as $value) $bibliografi[] = dsValidId($value, 'tb_bibliografi', 'id');
        if (kiLinked($row['source_key'])) throw new InvalidArgumentException('Identitas DREAMSEA ini sudah ditautkan ke entri Kandaga. Gunakan entri tersebut untuk pemeriksaan, bukan membuat entri baru.');
        $duplicate = kiQuery('SELECT i.id_naskah FROM katalog_identifiers i WHERE i.scheme=? AND i.identifier_value=? LIMIT 1', [$row['draft']['source']['primary']['scheme'], $row['draft']['source']['primary']['value']])->get_result()->fetch_assoc();
        if ($duplicate && $action === 'created' && ($input['duplicate_confirmed'] ?? false) !== true) throw new InvalidArgumentException('Identitas DREAMSEA ini sudah tersimpan pada entri #' . (int)$duplicate['id_naskah'] . '. Periksa dan konfirmasikan bila ini memang naskah berbeda.');
        $target = null;
        $before = null;
        if ($action === 'merged') {
            $target = filter_var($input['target_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$target) throw new InvalidArgumentException('Pilih entri tujuan merge.');
            $snapshot = dsSnapshot($target, true);
            $before = $snapshot['data'];
            if (!is_string($input['revision'] ?? null) || !hash_equals($snapshot['revision'], $input['revision'])) throw new InvalidArgumentException('Entri tujuan berubah sejak pratinjau. Muat ulang dan periksa kembali.');
        }
        kiEncoding($resolved);
        if ($action === 'created') {
            kiQuery("INSERT INTO katalog(tanggal_input,penginput_awal,judul,id_lokasi,kode_inventaris,deskripsi_fisik,deskripsi_isi,riwayat_naskah,kata_kunci,penyedia_akses,gambar,url_web,url_repositori,iiif_manifest,url_pdf,url_gambar_eksternal) VALUES(NOW(),?,?,?,?,?,?,?,?,?,'',?,?,?,?,?)", [$_SESSION['username'], $resolved['judul'], $location, $resolved['kode_inventaris'], $resolved['deskripsi_fisik'], $resolved['deskripsi_isi'], $resolved['riwayat_naskah'], $resolved['kata_kunci'], $resolved['penyedia_akses'], $resolved['url_web'], $resolved['url_repositori'], $resolved['iiif_manifest'], $resolved['url_pdf'], $resolved['url_gambar_eksternal']]);
            $target = $conn->insert_id;
        } else {
            kiQuery('UPDATE katalog SET judul=?,id_lokasi=?,kode_inventaris=?,deskripsi_fisik=?,deskripsi_isi=?,riwayat_naskah=?,kata_kunci=?,penyedia_akses=?,url_web=?,url_repositori=?,iiif_manifest=?,url_pdf=?,url_gambar_eksternal=?,penyunting_terakhir=?,tanggal_update=NOW() WHERE id=?', [$resolved['judul'], $location, $resolved['kode_inventaris'], $resolved['deskripsi_fisik'], $resolved['deskripsi_isi'], $resolved['riwayat_naskah'], $resolved['kata_kunci'], $resolved['penyedia_akses'], $resolved['url_web'], $resolved['url_repositori'], $resolved['iiif_manifest'], $resolved['url_pdf'], $resolved['url_gambar_eksternal'], $_SESSION['username'], $target]);
        }
        foreach ($finalRelations as $group => $ids) {
            [$table, $column] = $locationList[$group];
            kiQuery("DELETE FROM $table WHERE id_naskah=?", [$target]);
            foreach ($ids as $value) kiQuery("INSERT INTO $table(id_naskah,$column) VALUES(?,?)", [$target, $value]);
        }
        foreach ($bibliografi as $value) if (!kiQuery('SELECT id FROM naskah_bibliografi WHERE id_naskah=? AND id_bibliografi=?', [$target, $value])->get_result()->fetch_assoc()) kiQuery('INSERT INTO naskah_bibliografi(id_naskah,id_bibliografi) VALUES(?,?)', [$target, $value]);
        $structure = $row['draft']['structure'];
        $parent = null;
        $parentMsid = trim((string)$structure['parent_msid']);
        if ($parentMsid !== '') {
            if (preg_match('/^\d{1,6}$/', $parentMsid)) $parentMsid = 'MS' . str_pad($parentMsid, 6, '0', STR_PAD_LEFT);
            $parent = kiQuery('SELECT i.id_naskah FROM katalog_identifiers i WHERE i.scheme=? AND i.identifier_value=? LIMIT 1', ['dreamsea-msid', $parentMsid])->get_result()->fetch_assoc();
            $parent = $parent ? (int)$parent['id_naskah'] : null;
        }
        if ($action === 'created' || !kiQuery('SELECT id_naskah FROM katalog_structure WHERE id_naskah=?', [$target])->get_result()->fetch_assoc()) {
            kiQuery('INSERT INTO katalog_structure(id_naskah,parent_id,record_type,unit_label,unit_order,notes) VALUES(?,?,?,?,?,?)', [$target, $parent, $structure['record_type'], $structure['unit_label'], $structure['unit_order'], $structure['notes']]);
        } else {
            kiQuery('UPDATE katalog_structure SET parent_id=?,record_type=?,unit_label=?,unit_order=?,notes=? WHERE id_naskah=?', [$parent, $structure['record_type'], $structure['unit_label'], $structure['unit_order'], $structure['notes'], $target]);
        }
        foreach ($row['draft']['identifiers'] as $item) kiQuery('INSERT IGNORE INTO katalog_identifiers(id_naskah,scheme,identifier_value,source_label,is_primary) VALUES(?,?,?,?,?)', [$target, $item['scheme'], $item['value'], $item['source'], $item['primary'] ? 1 : 0]);
        if ($action === 'created') foreach ($row['draft']['location_history'] as $item) kiQuery('INSERT INTO katalog_location_history(id_naskah,location_kind,place_name,district,province,country,period_label,certainty,source_text,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)', [$target, $item['kind'], $item['place'], $item['district'], $item['province'], $item['country'], $item['period'], $item['certainty'], $item['source'], $item['sort_order']]);
        if (!kiQuery('SELECT source_key FROM katalog_import_sources WHERE source_key=?', [$row['source_key']])->get_result()->fetch_assoc()) kiQuery('INSERT INTO katalog_import_sources(source_key,katalog_id,import_row_id) VALUES(?,?,?)', [$row['source_key'], $target, $row['id']]);
        $after = dsSnapshot($target)['data'];
        kiAudit($row, $action, $target, $before, $after, $input);
        logAktivitas($conn, $action === 'created' ? 'input' : 'edit', ($action === 'created' ? 'Input naskah DREAMSEA: ' : 'Edit naskah DREAMSEA: ') . $resolved['judul'], $target);
        $conn->commit();
        return ['id' => $target, 'action' => $action];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
