<?php
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Public Zotero reference view.
 *
 * The API key is deliberately read from the server environment. It must never
 * be committed to the CMS tree or exposed in a page, URL, or client script.
 */
function kandagaZoteroEnv(string $name, string $default = ''): string
{
    $value = getenv($name);
    if ($value === false && defined($name)) $value = constant($name);
    return trim((string)($value === false ? $default : $value));
}

function kandagaZoteroEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kandagaZoteroAuthor(array $creator): string
{
    $name = trim((string)($creator['name'] ?? ''));
    if ($name !== '') return $name;
    return trim(implode(' ', array_filter([
        (string)($creator['firstName'] ?? ''),
        (string)($creator['lastName'] ?? '')
    ])));
}

function kandagaZoteroItems(string $userId, string $apiKey, string &$error): array
{
    $error = '';
    if ($userId === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $userId)) {
        $error = 'Identitas pengguna Zotero belum dikonfigurasi.';
        return [];
    }
    if ($apiKey === '') {
        $error = 'Referensi Zotero belum dikonfigurasi pada server.';
        return [];
    }
    if (!function_exists('curl_init')) {
        $error = 'Layanan referensi belum tersedia pada server.';
        return [];
    }

    // Cache singkat di direktori temporary server, bukan di document root.
    $cacheFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . 'kandaga-zotero-' . hash('sha256', $userId) . '.json';
    if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) < 60) {
        $cached = json_decode((string)@file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $url = 'https://api.zotero.org/users/' . rawurlencode($userId) . '/items?format=json';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Kandaga-Zotero/3.1.63',
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Zotero-API-Key: ' . $apiKey],
    ]);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($response) || $response === '' || $status !== 200) {
        $error = 'Referensi Zotero sedang tidak dapat diambil. Coba lagi nanti.';
        return [];
    }
    $items = json_decode($response, true);
    if (!is_array($items)) {
        $error = 'Respons referensi Zotero tidak valid.';
        return [];
    }
    @file_put_contents($cacheFile, json_encode($items, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $items;
}

$userId = kandagaZoteroEnv('KANDAGA_ZOTERO_USER_ID', 'ilham.nurwansah');
$apiKey = kandagaZoteroEnv('KANDAGA_ZOTERO_API_KEY');
$error = '';
$items = kandagaZoteroItems($userId, $apiKey, $error);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Referensi Zotero</title>
  <style>
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
  </style>
  <?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>
<body class="kandaga-module">
  <?php if ($error !== ''): ?>
    <div class="alert alert-light border" role="status"><?= kandagaZoteroEscape($error) ?></div>
  <?php elseif ($items === []): ?>
    <div class="alert alert-light border" role="status">Tidak ada item referensi pada koleksi Zotero.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Judul</th><th>Penulis</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item):
          $data = is_array($item['data'] ?? null) ? $item['data'] : [];
          $title = trim((string)($data['title'] ?? ''));
          $creators = is_array($data['creators'] ?? null) ? $data['creators'] : [];
          $authors = [];
          foreach ($creators as $creator) if (is_array($creator)) {
              $author = kandagaZoteroAuthor($creator);
              if ($author !== '') $authors[] = $author;
          }
          $authorText = kandagaFormatConjunction($authors, '—');
      ?>
        <tr>
          <td><?= kandagaZoteroEscape($title !== '' ? $title : 'Tanpa judul') ?></td>
          <td><?= kandagaZoteroEscape($authorText) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
