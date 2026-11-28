<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../includes/media-library.php';
kandagaPost(true);

/**
 * Refuse to delete an image still referenced by application content. The
 * filename is safe by allowlist, so LIKE patterns cannot be injected by users.
 */
function kandagaMediaUsage(mysqli $conn, string $relative, string $name): int
{
    $checks = [
        'tb_kabar'        => ['thumbnail', 'konten'],
        'tb_kabar_gambar' => ['url_gambar'],
        'users'           => ['foto'],
        'tb_bibliografi'  => ['gambar'],
        'katalog'         => ['gambar', 'url_gambar_eksternal'],
    ];
    $suffix = '%' . strtr($name, ['=' => '==', '%' => '=%', '_' => '=_']) . '%';
    $total = 0;
    $tableCheck = $conn->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $columnCheck = $conn->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    foreach ($checks as $table => $columns) {
        $tableCheck->bind_param('s', $table);
        $tableCheck->execute();
        $tableExists = (int)$tableCheck->get_result()->fetch_row()[0] > 0;
        if (!$tableExists) continue;
        foreach ($columns as $column) {
            $columnCheck->bind_param('ss', $table, $column);
            $columnCheck->execute();
            $exists = (int)$columnCheck->get_result()->fetch_row()[0] > 0;
            if (!$exists) continue;
            $stmt = $conn->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = ? OR `$column` LIKE ? ESCAPE '='");
            if (!$stmt) continue;
            $stmt->bind_param('ss', $relative, $suffix);
            $stmt->execute();
            $total += (int)$stmt->get_result()->fetch_row()[0];
            $stmt->close();
        }
    }
    $tableCheck->close();
    $columnCheck->close();
    return $total;
}

try {
    $folderKey = is_string($_POST['folder'] ?? null) ? $_POST['folder'] : '';
    $name = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
    $relative = kandagaMediaRelativePath($folderKey, $name);
    if ($relative === null) throw new InvalidArgumentException('Berkas tidak dikenali dalam Pustaka Media.');
    // Pustaka Umum contains shipped images as well as generated location
    // photos. Only generated names may be removed from that folder.
    if ($folderKey === 'root' && !preg_match('/^(?:lokasi|kabar)_[A-Za-z0-9][A-Za-z0-9._-]*$/', $name)) {
        throw new InvalidArgumentException('Berkas bawaan Kandaga tidak dapat dihapus.');
    }
    $absolute = ROOT_PATH . '/' . $relative;
    if (!is_file($absolute)) throw new InvalidArgumentException('Berkas sudah tidak tersedia.');
    if (kandagaMediaUsage($conn, $relative, $name) > 0) {
        throw new InvalidArgumentException('Gambar masih digunakan oleh artikel atau data katalog dan belum dapat dihapus.');
    }
    if (!@unlink($absolute)) throw new RuntimeException('Gambar gagal dihapus. Periksa izin folder media.');
    kandagaJson(true, 'Gambar berhasil dihapus dari Pustaka Media.');
} catch (Throwable $error) {
    kandagaFailure($error);
}
