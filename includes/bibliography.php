<?php
/** Shared bibliography formatting and cover helpers. */
if (!function_exists('kandagaBibliographyCitation')) {
    function kandagaBibliographyCitation(array $row): string {
        $e = static fn($v) => htmlspecialchars(trim((string)$v), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $author = trim((string)($row['penyusun'] ?? '')) ?: 'Tanpa penulis';
        $year = trim((string)($row['tahun_terbit'] ?? '')) ?: 'n.d.';
        $title = trim((string)($row['judul'] ?? '')) ?: 'Tanpa judul';
        $sub = trim((string)($row['subjudul'] ?? ''));
        $title = $sub !== '' ? $title . ': ' . $sub : $title;
        $out = $e($author) . '. ' . $e($year) . '. ';
        if (trim((string)($row['judul_bab'] ?? '')) !== '') {
            $out .= '&ldquo;' . $e($row['judul_bab']) . '&rdquo;. In ';
        }
        $out .= '<em>' . $e($title) . '</em>.';
        if (($row['tipe_item'] ?? '') === 'jurnal') {
            $journal = trim((string)($row['nama_jurnal'] ?? ''));
            if ($journal !== '') $out .= ' ' . $e($journal);
            if (trim((string)($row['volume'] ?? '')) !== '') $out .= ', ' . $e($row['volume']);
            if (trim((string)($row['nomor'] ?? '')) !== '') $out .= ' (' . $e($row['nomor']) . ')';
            if (trim((string)($row['halaman'] ?? '')) !== '') $out .= ': ' . $e($row['halaman']);
            return $out . '.';
        }
        $city = trim((string)($row['kota_terbit'] ?? ''));
        $publisher = trim((string)($row['penerbit'] ?? ''));
        if ($city !== '' || $publisher !== '') $out .= ' ' . $e($city) . ($city !== '' && $publisher !== '' ? ': ' : '') . $e($publisher) . '.';
        return $out;
    }
    function kandagaBibliographyCover(array $row): ?string {
        $external = trim((string)($row['cover_url'] ?? ''));
        $normalizedExternal = function_exists('kandagaNormalizeImageUrl') ? kandagaNormalizeImageUrl($external) : '';
        if ($normalizedExternal !== '') return $normalizedExternal;

        if (!defined('ROOT_PATH')) return null;
        $root = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
        $base = trim((string)BASE_URL, '/');
        $rawValues = [trim((string)($row['cover_filename'] ?? '')), $external];
        $candidates = [];
        foreach ($rawValues as $raw) {
            if ($raw === '') continue;
            $rawPath = parse_url($raw, PHP_URL_PATH);
            $rawPath = is_string($rawPath) && $rawPath !== '' ? $rawPath : $raw;
            $rawPath = str_replace('\\', '/', $rawPath);
            $rawPath = preg_replace('~^/+~', '', $rawPath);
            if ($base !== '' && strpos($rawPath, $base . '/') === 0) $rawPath = substr($rawPath, strlen($base) + 1);
            $rawPath = ltrim($rawPath, '/');
            if ($rawPath === '' || strpos($rawPath, '..') !== false) continue;
            $file = basename($rawPath);
            if ($file === '' || $file === '.' || $file === '/') continue;
            $candidates[] = [$root . '/' . $rawPath, $rawPath];
            foreach (['assets/images/bibliografi', 'assets/images/bibliography', 'uploads/bibliografi', 'uploads/bibliography'] as $dir) {
                $candidates[] = [$root . '/' . $dir . '/' . $file, $dir . '/' . $file];
            }
        }
        foreach ($candidates as [$absolute, $relative]) {
            $resolved = realpath($absolute);
            if ($resolved === false || !is_file($resolved)) continue;
            $resolved = str_replace('\\', '/', $resolved);
            if (strpos($resolved, $root . '/') !== 0) continue;
            $relative = substr($resolved, strlen($root) + 1);
            return BASE_URL . '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
        }
        return null;
    }
}
