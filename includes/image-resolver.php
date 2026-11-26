<?php
/** Centralized manuscript image resolution and safe external URL normalization. */
if (!function_exists('kandagaNormalizeImageUrl')) {
    function kandagaNormalizeImageUrl($url): string
    {
        $url = trim((string)$url);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f]/', $url) || !filter_var($url, FILTER_VALIDATE_URL)) return '';
        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user'], $parts['pass'])) return '';
        $path = preg_replace('~/+~', '/', (string)($parts['path'] ?? '/')) ?: '/';
        $out = $scheme . '://' . $host;
        $port = (int)($parts['port'] ?? 0);
        if ($port > 0 && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) $out .= ':' . $port;
        $out .= ($path[0] === '/' ? $path : '/' . $path);
        if (isset($parts['query']) && $parts['query'] !== '') $out .= '?' . $parts['query'];
        return $out;
    }
    function kandagaPlaceholderImage(): string
    {
        return (defined('BASE_URL') ? BASE_URL : '') . '/assets/images/msc-placeholder.png';
    }

    /**
     * Return true for known placeholder image values. This is intentionally
     * conservative: only explicit placeholder filenames/paths are rejected.
     */
    function kandagaIsPlaceholderImage($value): bool
    {
        $value = trim((string)$value);
        if ($value === '') return true;

        $placeholder = trim(kandagaPlaceholderImage());
        if ($placeholder !== '' && $value === $placeholder) return true;

        $path = $value;
        $parts = parse_url($value);
        if (is_array($parts) && isset($parts['path'])) {
            $path = (string)$parts['path'];
        }
        $basename = strtolower((string)basename($path));
        return in_array($basename, [
            'msc-placeholder.png',
            'placeholder.png',
            'image-placeholder.png',
            'default-placeholder.png',
        ], true);
    }

    /**
     * Resolve only a real manuscript image. Unlike kandagaImageUrl(), this
     * function never falls back to the global placeholder, so it can be used
     * by curated cards that must contain an actual image.
     */
    function kandagaRealImageUrl(array $row, string $subdirectory = 'naskah'): ?string
    {
        $local = basename(trim((string)($row['gambar'] ?? '')));
        if (
            $local !== ''
            && !kandagaIsPlaceholderImage($local)
            && defined('ROOT_PATH')
            && is_file(ROOT_PATH . '/assets/images/' . $subdirectory . '/' . $local)
        ) {
            return (defined('BASE_URL') ? BASE_URL : '')
                . '/assets/images/' . rawurlencode($subdirectory) . '/' . rawurlencode($local);
        }

        $external = kandagaNormalizeImageUrl($row['url_gambar_eksternal'] ?? '');
        if ($external !== '' && !kandagaIsPlaceholderImage($external)) {
            return $external;
        }

        return null;
    }

    function kandagaHasRealImage(array $row, string $subdirectory = 'naskah'): bool
    {
        return kandagaRealImageUrl($row, $subdirectory) !== null;
    }

    function kandagaImageUrl(array $row, string $subdirectory = 'naskah'): string
    {
        $local = basename((string)($row['gambar'] ?? ''));
        if ($local !== '' && defined('ROOT_PATH') && is_file(ROOT_PATH . '/assets/images/' . $subdirectory . '/' . $local)) {
            return (defined('BASE_URL') ? BASE_URL : '') . '/assets/images/' . rawurlencode($subdirectory) . '/' . rawurlencode($local);
        }
        $external = kandagaNormalizeImageUrl($row['url_gambar_eksternal'] ?? '');
        return $external !== '' ? $external : kandagaPlaceholderImage();
    }
    function kandagaImageFallbackAttribute(): string
    {
        $fallback = htmlspecialchars(kandagaPlaceholderImage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return " onerror=\"this.onerror=null;this.src='{$fallback}'\"";
    }
}
