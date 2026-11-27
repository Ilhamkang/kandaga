<?php
require_once __DIR__ . '/../includes/dreamsea-import.php';
kandagaAuthorize(true);
$action = $_GET['action'] ?? 'init';
if (!is_string($action)) kandagaJson(false, 'Aksi tidak valid.', [], 400);
if (in_array($action, ['inspect', 'upload', 'review', 'delete_batch'], true)) kandagaPost(true);
try {
    if ($action === 'init') {
        $batches = dsReady() ? dsBatches() : [];
        kandagaJson(true, 'Konfigurasi Importer Web.', ['ready' => dsReady(), 'fields' => dsFields(), 'masters' => kiMasters(), 'batches' => $batches]);
    }
    if (!dsReady()) throw new InvalidArgumentException('Jalankan migrations/005_catalog_import.sql, migrations/013_catalog_structure_stage1.sql, dan migrations/018_web_import_batch_separation.sql pada database Kandaga dahulu.');
    if (in_array($action, ['inspect', 'upload'], true)) {
        [$headers, $rows] = kiCsv($_FILES['csv_file'] ?? []);
        foreach (['Judul', 'Lokasi_Target'] as $required) if (!in_array($required, $headers, true)) throw new InvalidArgumentException('Header wajib tidak ditemukan: ' . $required . '.');
        $hasIdentity = in_array('msid', $headers, true) || in_array('ds_id', $headers, true)
            || (in_array('Identifier_Scheme', $headers, true) && in_array('Identifier_Value', $headers, true));
        if (!$hasIdentity) throw new InvalidArgumentException('CSV harus memiliki msid, ds_id, atau pasangan Identifier_Scheme/Identifier_Value.');
        if ($action === 'inspect') {
            $preview = [];
            foreach (array_slice($rows, 0, 3) as $row) {
                try {
                    $draft = dsDraft($row);
                    $preview[] = ['msid' => $draft['source']['primary']['value'], 'ds_id' => $draft['source']['ds_id'], 'judul' => $draft['fields']['judul'], 'lokasi' => $draft['source']['Lokasi_Target'], 'bahasa' => implode(', ', $draft['relations']['bahasa'])];
                } catch (Throwable $e) {
                    $preview[] = ['error' => $e->getMessage()];
                }
            }
            kandagaJson(true, 'CSV terbaca.', ['headers' => $headers, 'count' => count($rows), 'preview' => $preview]);
        }
        $label = trim((string)($_POST['source_label'] ?? 'Web'));
        if ($label === '' || mb_strlen($label) > 255) throw new InvalidArgumentException('Nama sumber wajib, maksimal 255 karakter.');
        $drafts = [];
        $seen = [];
        foreach ($rows as $index => $row) {
            $draft = dsDraft($row);
            $key = dsKey($draft);
            if (isset($seen[$key])) throw new InvalidArgumentException('Identitas DREAMSEA berulang pada baris ' . ($index + 2) . '. Gunakan msid/ds_id yang berbeda.');
            $seen[$key] = true;
            $drafts[] = [$row, $draft, $key];
        }
        $hash = hash_file('sha256', $_FILES['csv_file']['tmp_name']);
        $existing = dsFindBatch($hash);
        if ($existing) kandagaJson(true, 'Berkas yang sama sudah ada; membuka batch sebelumnya.', ['batch_id' => (int)$existing['id'], 'reused' => true]);
        $conn->begin_transaction();
        try {
            $filename = mb_substr(basename($_FILES['csv_file']['name']), 0, 255);
            $mapping = kiJson($headers);
            $batch = dsInsertBatch($filename, $hash, $label, $mapping);
            foreach ($drafts as $index => [$raw, $draft, $key]) kiQuery('INSERT INTO katalog_import_rows(batch_id,source_row,source_key,raw_json,draft_json) VALUES(?,?,?,?,?)', [$batch, $index + 1, $key, kiJson($raw), kiJson($draft)]);
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
        kandagaJson(true, 'CSV masuk antrean; katalog utama belum diubah.', ['batch_id' => $batch, 'count' => count($drafts)]);
    }
    if ($action === 'queue') {
        $batch = filter_var($_GET['batch_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = (string)($_GET['status'] ?? 'all');
        if (!$batch || !$page || !in_array($status, ['all', 'pending', 'deferred', 'created', 'merged', 'rejected'], true)) throw new InvalidArgumentException('Filter antrean tidak valid.');
        $queue = kiQueueData($batch, KI_ENGINE_WEB, $page, $status);
        $list = $queue['rows'];
        foreach ($list as &$row) {
            $draft = json_decode($row['draft_json'], true, 512, JSON_THROW_ON_ERROR);
            $row['title'] = $draft['fields']['judul'];
            $row['inventory'] = $draft['fields']['kode_inventaris'];
            $row['msid'] = $draft['source']['primary']['value'];
            unset($row['draft_json']);
        }
        unset($row);
        kandagaJson(true, 'Antrean.', ['rows' => $list, 'total' => $queue['total'], 'page' => $queue['page'], 'pages' => $queue['pages'], 'counts' => $queue['counts']]);
    }
    if ($action === 'row') {
        $id = filter_var($_GET['row_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) throw new InvalidArgumentException('ID baris tidak valid.');
        $row = kiRow($id, false, KI_ENGINE_WEB);
        $masters = kiMasters();
        $suggestions = dsSuggestions($row['draft'], $masters);
        $target = filter_var($_GET['target_id'] ?? 0, FILTER_VALIDATE_INT);
        if ($target === false || $target < 0) throw new InvalidArgumentException('ID tujuan tidak valid.');
        $snapshot = $target ? dsSnapshot($target) : null;
        $audit = kiQuery('SELECT action,katalog_id,username,created_at,decision_json FROM katalog_import_audit WHERE import_row_id=? ORDER BY id DESC', [$id])->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($audit as &$item) {
            $decision = json_decode($item['decision_json'], true, 512, JSON_THROW_ON_ERROR);
            $item['note'] = $decision['note'] ?? '';
            unset($item['decision_json']);
        }
        unset($item);
        kandagaJson(true, 'Pratinjau.', ['row' => $row, 'suggestions' => $suggestions, 'candidates' => dsCandidates($row['draft']), 'linked' => kiLinked($row['source_key']), 'snapshot' => $snapshot, 'audit' => $audit]);
    }
    if ($action === 'review') {
        $input = json_decode($_POST['payload'] ?? '', true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($input)) throw new InvalidArgumentException('Payload tidak valid.');
        $result = dsApprove($input);
        kandagaJson(true, 'Keputusan tersimpan.', $result);
    }
    if ($action === 'delete_batch') {
        $batch = filter_var($_POST['batch_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$batch) throw new InvalidArgumentException('ID batch tidak valid.');
        $result = kiDeleteBatch($batch, KI_ENGINE_WEB);
        kandagaJson(true, 'Batch Importer Web dihapus; ' . $result['rows'] . ' baris antrean dibersihkan.', $result);
    }
    kandagaJson(false, 'Aksi tidak dikenal.', [], 404);
} catch (JsonException $e) {
    kandagaJson(false, 'Format JSON tidak valid.', [], 422);
} catch (Throwable $e) {
    kandagaFailure($e);
}
