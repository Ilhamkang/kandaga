<?php
/**
 * Build a Kandaga Update Center package from tracked source files.
 * Run in CI after bumping RELEASE.md to the version being tagged.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root = dirname(__DIR__);
chdir($root);

function kdFail(string $message): void {
    fwrite(STDERR, "Gagal: {$message}\n");
    exit(1);
}

function kdHash(string $file): string {
    $hash = @hash_file('sha256', $file);
    if ($hash === false) kdFail('Tidak dapat membaca ' . $file);
    return $hash;
}

function kdRemoveTree(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    ) as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function kdProtected(string $path): bool {
    return in_array($path, [
        'config/database.php',
        'config/database.local.php',
        'config/analytics.local.php',
        'config/update.local.php',
        'maintenance.lock',
        'update_cache.json',
    ], true)
    || preg_match('~^(?:assets/images|gambar|uploads?|storage|kandaga_backup)(?:/|$)~i', $path)
    || preg_match('~(?:^|/)(?:\.env|\.git)(?:[./]|$)~i', $path);
}

function kdExcluded(string $path): bool {
    if (kdProtected($path)) return true;
    if (in_array($path, ['dist', '.github', '.gitignore', 'cek-localhost.php'], true)) return true;
    if (str_starts_with($path, 'dist/') || str_starts_with($path, '.github/')) return true;
    if (str_starts_with($path, 'config/') && str_ends_with($path, '.local.php')) return true;
    return false;
}

function kdTrackedFiles(): array {
    $raw = @shell_exec('git ls-files -z');
    if (!is_string($raw) || $raw === '') kdFail('Tidak ada berkas ter-track. Jalankan git add/commit terlebih dahulu.');
    $files = array_filter(explode("\0", $raw), 'strlen');
    sort($files, SORT_STRING);
    return $files;
}

$release = (string)@file_get_contents($root . '/RELEASE.md');
if (!preg_match('/^# Kandaga v(\d+\.\d+\.\d+)\s*$/m', $release, $match)) {
    kdFail('Judul RELEASE.md tidak valid. Contoh: # Kandaga v3.1.66');
}
$version = $match[1];

$meta = json_decode((string)@file_get_contents($root . '/version.json'), true);
if (!is_array($meta)) kdFail('version.json tidak valid.');
$description = (string)($meta['description'] ?? '');
if ($description === '') kdFail('version.json.description tidak boleh kosong.');

$tag = (string)(getenv('GITHUB_REF_NAME') ?: getenv('TAG') ?: '');
if ($tag !== '' && $tag !== 'v' . $version) {
    kdFail("Tag {$tag} tidak cocok dengan RELEASE.md v{$version}.");
}

$old = json_decode((string)@file_get_contents($root . '/update-manifest.json'), true);
if (!is_array($old)) kdFail('update-manifest.json baseline tidak valid.');
$minFrom = (string)($old['version'] ?? '');
if ($minFrom === '' || !preg_match('/^\d+\.\d+\.\d+$/D', $minFrom)) kdFail('Versi baseline manifest tidak valid.');
if ($minFrom === $version && !in_array('--allow-same', $argv, true)) {
    kdFail('Versi baru harus lebih tinggi dari manifest baseline. Naikkan RELEASE.md terlebih dahulu.');
}

$dist = $root . '/dist';
$stage = $dist . '/stage';
@mkdir($dist, 0777, true);
kdRemoveTree($stage);
mkdir($stage, 0777, true);

$hashes = [];
foreach (kdTrackedFiles() as $path) {
    if (kdExcluded($path)) continue;
    $source = $root . '/' . $path;
    if (!is_file($source) || is_link($source)) kdFail('Berkas sumber tidak valid: ' . $path);
    $dest = $stage . '/' . $path;
    if (!is_dir(dirname($dest)) && !mkdir(dirname($dest), 0777, true)) kdFail('Gagal membuat folder staging: ' . $path);
    if (!copy($source, $dest)) kdFail('Gagal menyalin: ' . $path);
    $hashes[$path] = kdHash($dest);
}
ksort($hashes, SORT_STRING);

foreach (['RELEASE.md', 'version.json', 'admin/update.php', 'admin/includes/update-engine.php', 'config/bootstrap.php'] as $required) {
    if (!isset($hashes[$required])) kdFail('Berkas wajib tidak masuk paket: ' . $required);
}

$migrations = is_array($old['migrations'] ?? null) ? $old['migrations'] : [];
foreach ($migrations as &$migration) {
    $path = (string)($migration['path'] ?? '');
    if (!isset($hashes[$path])) kdFail('Migrasi tidak ditemukan di paket: ' . $path);
    $migration['sha256'] = $hashes[$path];
}
unset($migration);

$delete = array_values(array_filter(
    is_array($old['delete'] ?? null) ? $old['delete'] : [],
    static fn($path) => is_string($path) && $path !== '' && !kdProtected($path) && !isset($hashes[$path])
));

$backup = is_array($old['backup'] ?? null) ? $old['backup'] : [];
if ($migrations) {
    $backup['default'] = 'full';
    $backup['allow_code_only'] = false;
    $backup['database'] = 'auto';
    $backup['note'] = 'Paket menyertakan migrasi SQL; backup database wajib.';
} else {
    $backup['default'] = 'code';
    $backup['allow_code_only'] = true;
    $backup['database'] = 'never';
    $backup['note'] = 'Rilis code-only tanpa migrasi SQL; backup database dinonaktifkan.';
}

$requires = is_array($old['requires'] ?? null) ? $old['requires'] : [
    'php' => '>=8.1',
    'mysql' => '>=8.0',
    'mariadb' => '>=10.11',
    'extensions' => ['mysqli', 'zip', 'mbstring', 'curl', 'tokenizer'],
];

$manifest = [
    'format' => 1,
    'product' => 'kandaga',
    'version' => $version,
    'min_from' => $minFrom,
    'requires' => $requires,
    'backup' => $backup,
    'files' => $hashes,
    'migrations' => $migrations,
    'delete' => $delete,
    'release_notes' => [$description],
];
if (file_put_contents($stage . '/update-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n") === false) {
    kdFail('Gagal menulis manifest paket.');
}

$zipFilename = 'kandaga-' . $version . '.zip';
$zipPath = $dist . '/' . $zipFilename;
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) kdFail('Gagal membuat ZIP.');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iterator as $item) {
    $name = substr($item->getPathname(), strlen($stage) + 1);
    $name = str_replace(DIRECTORY_SEPARATOR, '/', $name);
    if ($item->isDir()) $zip->addEmptyDir($name);
    else $zip->addFile($item->getPathname(), $name);
}
if (!$zip->close()) kdFail('ZIP tidak berhasil ditutup.');

$channel = [
    'version' => $version,
    'description' => $description,
    'zip_filename' => $zipFilename,
    'sha256' => kdHash($zipPath),
];
if (file_put_contents($dist . '/version.json', json_encode($channel, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n") === false) {
    kdFail('Gagal menulis channel version.json.');
}

// Self-check before the workflow publishes anything.
$verify = new ZipArchive();
if ($verify->open($zipPath) !== true) kdFail('ZIP hasil build tidak dapat dibaca.');
$built = json_decode((string)$verify->getFromName('update-manifest.json'), true);
if (!is_array($built) || ($built['version'] ?? '') !== $version) kdFail('Manifest dalam ZIP tidak valid.');
foreach ($built['files'] as $name => $hash) {
    if ($verify->locateName($name) === false) kdFail('Berkas manifest hilang dari ZIP: ' . $name);
}
$verify->close();

kdRemoveTree($stage);

echo "Paket siap: dist/{$zipFilename}\n";
echo "Kanal:     dist/version.json\n";
echo "Versi:     {$version} (min_from {$minFrom})\n";
echo "Berkas:    " . count($hashes) . "\n";
echo "Migrasi:   " . count($migrations) . "\n";
echo "SHA-256:   {$channel['sha256']}\n";
