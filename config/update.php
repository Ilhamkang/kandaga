<?php
// Optional private-directory override; keep this local configuration on upgrades.
// Set KANDAGA_UPDATE_DIR or create config/update.local.php returning ['storage' => '/absolute/private/path'].
function kandagaUpdateStorage(): string {
    $settings = is_file(__DIR__ . '/update.local.php') ? require __DIR__ . '/update.local.php' : [];
    if (!is_array($settings)) throw new RuntimeException('Konfigurasi update lokal tidak valid.');
    $doc = realpath($_SERVER['DOCUMENT_ROOT'] ?? ROOT_PATH) ?: ROOT_PATH;
    $path = getenv('KANDAGA_UPDATE_DIR') ?: ($settings['storage'] ?? dirname($doc) . '/kandaga-private-' . substr(hash('sha256', ROOT_PATH), 0, 12));
    if (!is_string($path) || $path === '' || $path[0] !== '/' || is_link($path)) throw new RuntimeException('Direktori update harus berupa path absolut di luar web root.');
    // Resolve nearest existing ancestor before creating anything.
    $parent = $path;
    while (!file_exists($parent) && dirname($parent) !== $parent) $parent = dirname($parent);
    $resolved = realpath($parent);
    $candidate = $resolved . substr($path, strlen($parent));
    if (preg_match('~(?:^|/)\.\.?(/|$)~', $candidate)) throw new RuntimeException('Path penyimpanan update tidak valid.');
    foreach ([$doc, realpath(ROOT_PATH)] as $web) {
        if ($candidate === $web || str_starts_with($candidate, $web . '/')) throw new RuntimeException('Backup harus disimpan di luar document root Apache dan direktori aplikasi.');
    }
    if (!is_dir($path) && !@mkdir($path, 0700, true)) throw new RuntimeException('Direktori backup belum dapat dibuat: ' . $path . '. Buat folder ini agar dapat ditulis PHP, atau atur KANDAGA_UPDATE_DIR.');
    if (!is_writable($path)) throw new RuntimeException('Direktori backup tidak dapat ditulis PHP: ' . $path);
    @chmod($path, 0700);
    return realpath($path);
}
