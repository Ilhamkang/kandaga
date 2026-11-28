<?php
require_once __DIR__ . '/request.php';

/**
 * Daftar ID kurator yang bertugas pada sebuah lokasi/koleksi.
 * Kurator adalah baris tb_kurasi_koleksi, bukan nilai users.role.
 */
function kandagaKuratorIds(mysqli $conn, int $idLokasi): array {
    if ($idLokasi < 1) return [];
    $stmt = $conn->prepare('SELECT id_user FROM tb_kurasi_koleksi WHERE id_lokasi = ?');
    if (!$stmt) return [];
    $stmt->bind_param('i', $idLokasi);
    $stmt->execute();
    $ids = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $ids[] = (int)$row['id_user'];
    }
    return $ids;
}

/** Boleh menaruh naskah pada koleksi ini? Admin selalu boleh. */
function kandagaBolehLokasi(mysqli $conn, int $idLokasi): bool {
    $user = kandagaCurrentUser();
    if ($user['role'] === 'admin') return true;
    $kurator = kandagaKuratorIds($conn, $idLokasi);
    return !$kurator || in_array($user['id'], $kurator, true);
}

/** Boleh mengubah data koleksi? Tambah koleksi baru tetap admin-only. */
function kandagaBolehKoleksi(mysqli $conn, int $idLokasi): bool {
    $user = kandagaCurrentUser();
    if ($user['role'] === 'admin') return true;
    return in_array($user['id'], kandagaKuratorIds($conn, $idLokasi), true);
}

/**
 * Boleh mengubah/menghapus naskah?
 *
 * Jika lokasi asal atau lokasi tujuan dipegang kurator, hanya kurator pada
 * lokasi tersebut (dan administrator) yang diizinkan. Jika tidak ada kurator,
 * aturan lama dipertahankan: pembuat atau penyunting terakhir boleh mengubah.
 */
function kandagaBolehNaskah(mysqli $conn, int $idNaskah, int $targetLokasi = 0, ?array $row = null): bool {
    $user = kandagaCurrentUser();
    if ($user['role'] === 'admin') return true;
    if ($idNaskah < 1) return false;
    if ($row === null) {
        $stmt = $conn->prepare('SELECT id_lokasi, penginput_awal, penyunting_terakhir FROM katalog WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $idNaskah);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
    }
    if (!$row) return false;

    $lokasi = array_values(array_unique(array_filter([
        (int)($row['id_lokasi'] ?? 0),
        $targetLokasi,
    ])));
    $adaKurator = false;
    foreach ($lokasi as $idLokasi) {
        $kurator = kandagaKuratorIds($conn, $idLokasi);
        if (!$kurator) continue;
        $adaKurator = true;
        if (!in_array($user['id'], $kurator, true)) return false;
    }
    if ($adaKurator) return true;

    $username = $user['username'];
    if ($username === '') return false;
    return hash_equals((string)($row['penginput_awal'] ?? ''), $username)
        || hash_equals((string)($row['penyunting_terakhir'] ?? ''), $username);
}
