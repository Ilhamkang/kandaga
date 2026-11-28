<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../includes/ownership.php';
require_once __DIR__ . '/../includes/log-tracker.php';
kandagaPost();
$started = false;
try {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) throw new InvalidArgumentException('ID naskah tidak valid.');
    $conn->begin_transaction(); $started = true;
    $stmt = $conn->prepare('SELECT judul, gambar, id_lokasi, penginput_awal, penyunting_terakhir FROM katalog WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $id); $stmt->execute(); $data = $stmt->get_result()->fetch_assoc();
    if (!$data) throw new InvalidArgumentException('Naskah tidak ditemukan.');
    if (!kandagaBolehNaskah($conn, $id, 0, $data)) {
        kandagaDeny('Naskah ini dipegang kurator. Hanya kurator penanggung jawab atau administrator yang dapat menghapusnya.');
    }
    // Existing database has no cascades on these active junction tables.
    foreach (['naskah_aksara', 'naskah_bahasa', 'naskah_alas_tulis', 'naskah_bibliografi', 'naskah_views'] as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE id_naskah = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
    }
    $stmt = $conn->prepare('DELETE FROM katalog WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
    logAktivitas($conn, 'hapus', 'Menghapus naskah: ' . $data['judul'], $id);
    $conn->commit(); $started = false;
    // Keep existing server images: they may also be referenced by other records.
    kandagaJson(true, 'Data naskah berhasil dihapus.');
} catch (Throwable $error) {
    if ($started) $conn->rollback(); kandagaFailure($error);
}
