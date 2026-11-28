<?php
/**
 * Read-only media index for images already stored under assets/images.
 * Only an explicit folder allowlist and plain file names are accepted; no
 * user input is ever concatenated into a filesystem path.
 */
function kandagaMediaFolders(): array
{
    return [
        'editor'      => ['label' => 'Gambar Editor',   'path' => 'assets/images/editor',      'icon' => 'fa-wand-magic-sparkles'],
        'post'        => ['label' => 'Postingan',       'path' => 'assets/images/post',        'icon' => 'fa-newspaper'],
        'bibliografi' => ['label' => 'Bibliografi',     'path' => 'assets/images/bibliografi', 'icon' => 'fa-book-bookmark'],
        'naskah'      => ['label' => 'Naskah',          'path' => 'assets/images/naskah',      'icon' => 'fa-scroll'],
        'user'        => ['label' => 'Pengguna',        'path' => 'assets/images/user',        'icon' => 'fa-user'],
        'root'        => ['label' => 'Pustaka Umum',    'path' => 'assets/images',             'icon' => 'fa-images'],
    ];
}

function kandagaMediaFolder(string $key): ?array
{
    $folders = kandagaMediaFolders();
    return $folders[$key] ?? null;
}

function kandagaMediaExtensions(): array
{
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
}

function kandagaMediaNameValid(string $name): bool
{
    return $name !== '' && $name === basename($name)
        && !str_starts_with($name, '.')
        && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,240}$/', $name) === 1
        && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), kandagaMediaExtensions(), true);
}

function kandagaMediaRelativePath(string $folderKey, string $name): ?string
{
    $folder = kandagaMediaFolder($folderKey);
    if (!$folder || !kandagaMediaNameValid($name)) return null;
    return $folder['path'] . '/' . $name;
}

function kandagaMediaItems(string $folderKey, string $search = '', int $page = 1, int $limit = 24): array
{
    $folder = kandagaMediaFolder($folderKey);
    if (!$folder) return ['items' => [], 'total' => 0, 'pages' => 1];
    $directory = ROOT_PATH . '/' . $folder['path'];
    if (!is_dir($directory)) return ['items' => [], 'total' => 0, 'pages' => 1];
    $entries = scandir($directory);
    if (!is_array($entries)) return ['items' => [], 'total' => 0, 'pages' => 1];

    $items = [];
    foreach ($entries as $entry) {
        if (!kandagaMediaNameValid($entry)) continue;
        if ($search !== '' && stripos($entry, $search) === false) continue;
        $full = $directory . '/' . $entry;
        if (!is_file($full) || !is_readable($full)) continue;
        $size = @getimagesize($full);
        $items[] = [
            'key'      => $folderKey,
            'name'     => $entry,
            'relative' => $folder['path'] . '/' . $entry,
            'url'      => BASE_URL . '/' . $folder['path'] . '/' . rawurlencode($entry),
            'bytes'    => (int)@filesize($full),
            'modified' => (int)@filemtime($full),
            'width'    => (int)($size[0] ?? 0),
            'height'   => (int)($size[1] ?? 0),
        ];
    }
    usort($items, static function (array $a, array $b): int {
        return $b['modified'] <=> $a['modified'] ?: strcasecmp($a['name'], $b['name']);
    });
    $total = count($items);
    $pages = max(1, (int)ceil($total / max(1, $limit)));
    $page = max(1, min($page, $pages));
    return [
        'items'  => array_slice($items, ($page - 1) * $limit, $limit),
        'total'  => $total,
        'pages'  => $pages,
        'page'   => $page,
    ];
}

function kandagaMediaFolderCounts(): array
{
    $counts = [];
    foreach (kandagaMediaFolders() as $key => $folder) {
        $result = kandagaMediaItems($key, '', 1, PHP_INT_MAX);
        $counts[$key] = $result['total'];
    }
    return $counts;
}

function kandagaMediaFormatBytes(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    return max(1, (int)round($bytes / 1024)) . ' KB';
}

/**
 * Materialise a Pustaka Media image into assets/images/post so articles keep
 * the historical contract: `tb_kabar.thumbnail` is a plain basename in that
 * folder. Input is always a {folder, name} pair, never a raw URL from the
 * browser. Existing identical files are reused instead of duplicated.
 *
 * @return array{name:string,url:string,copied:bool}
 */
function kandagaMediaStoreToPost(string $folderKey, string $name): array
{
    $relative = kandagaMediaRelativePath($folderKey, $name);
    if ($relative === null) throw new InvalidArgumentException('Berkas gambar tidak dikenali.');
    $source = ROOT_PATH . '/' . $relative;
    if (!is_file($source) || !is_readable($source)) throw new InvalidArgumentException('Berkas gambar tidak tersedia.');
    if (@filesize($source) > 10 * 1048576) throw new InvalidArgumentException('Ukuran gambar melebihi batas 10 MB.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($source);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) throw new InvalidArgumentException('Jenis berkas gambar tidak didukung.');
    $size = @getimagesize($source);
    if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] > 8000 || $size[1] > 8000) {
        throw new InvalidArgumentException('Gambar tidak valid atau terlalu besar.');
    }

    $directory = ROOT_PATH . '/assets/images/post';
    if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
        throw new RuntimeException('Folder postingan tidak dapat dibuat.');
    }

    $hash = @hash_file('sha256', $source);
    $copied = false;
    if ($folderKey === 'post' && is_file($source)) {
        $storedName = $name;
    } else {
        $storedName = '';
        if ($hash) {
            foreach (scandir($directory) ?: [] as $existing) {
                if (!kandagaMediaNameValid($existing)) continue;
                if (@hash_file('sha256', $directory . '/' . $existing) === $hash) { $storedName = $existing; break; }
            }
        }
        if ($storedName === '') {
            $storedName = 'kabar_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
            if (!@copy($source, $directory . '/' . $storedName)) {
                throw new RuntimeException('Gambar gagal disalin ke folder postingan.');
            }
            @chmod($directory . '/' . $storedName, 0644);
            $copied = true;
        }
    }

    return [
        'name'  => $storedName,
        'url'   => BASE_URL . '/assets/images/post/' . rawurlencode($storedName),
        'copied' => $copied,
    ];
}

/** Validate a gallery payload coming from the media picker. */
function kandagaPostGalleryPayload($raw, int $limit = 20): array
{
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) throw new InvalidArgumentException('Format gambar tambahan tidak valid.');
    $raw = array_values($raw);
    if (count($raw) > $limit) throw new InvalidArgumentException('Maksimal ' . $limit . ' gambar tambahan per artikel.');

    $items = [];
    $seen = [];
    foreach ($raw as $entry) {
        if (!is_array($entry)) continue;
        $folder = (string)($entry['folder'] ?? '');
        $name = (string)($entry['name'] ?? '');
        if (kandagaMediaRelativePath($folder, $name) === null) continue;
        $key = $folder . '/' . $name;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $items[] = [
            'folder' => $folder,
            'name'   => $name,
            'alt'    => mb_substr(trim((string)($entry['alt'] ?? '')), 0, 255, 'UTF-8'),
        ];
    }
    return $items;
}
