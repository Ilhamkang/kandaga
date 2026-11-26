<?php
/**
 * Shared digital-access indicators for manuscript cards and detail pages.
 * Only absolute HTTP(S) URLs are exposed as links; empty or malformed values
 * remain invisible rather than producing misleading availability icons.
 */
if (!function_exists('kandagaNormalizeDigitalUrl')) {
    function kandagaNormalizeDigitalUrl($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 4096 || !filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }
        $parts = parse_url($value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user'], $parts['pass'])) {
            return '';
        }
        return $value;
    }

    /** @return array<int,array{type:string,href:string,label:string,icon:string}> */
    function kandagaDigitalAccessItems(array $row, int $id): array
    {
        $items = [];
        $pdf = kandagaNormalizeDigitalUrl($row['url_pdf'] ?? '');
        if ($pdf !== '') {
            $items[] = [
                'type' => 'pdf',
                'href' => $pdf,
                'label' => 'Buka PDF',
                'icon' => 'fa-file-pdf',
            ];
        }

        $manifest = kandagaNormalizeDigitalUrl($row['iiif_manifest'] ?? '');
        if ($manifest !== '') {
            $base = defined('BASE_URL') ? BASE_URL : '';
            $viewer = $base . '/iiif-viewer.php?id=' . rawurlencode((string) $id)
                . '&manifest=' . rawurlencode($manifest);
            $items[] = [
                'type' => 'iiif',
                'href' => $viewer,
                'label' => 'Buka IIIF',
                'icon' => 'fa-images',
            ];
        }

        return $items;
    }

    function kandagaDigitalAccessMarkup(array $row, int $id, string $context = 'card'): string
    {
        $items = kandagaDigitalAccessItems($row, $id);
        if (!$items) {
            return '';
        }

        $classes = 'kandaga-digital-access kandaga-digital-access-' . preg_replace('/[^a-z0-9_-]/i', '', $context);
        $html = '<div class="' . htmlspecialchars($classes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            . ' aria-label="Akses digital tersedia">';
        foreach ($items as $item) {
            $type = htmlspecialchars($item['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $href = htmlspecialchars($item['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $label = htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $icon = htmlspecialchars($item['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $target = $item['type'] === 'pdf' ? ' target="_blank" rel="noopener noreferrer"' : '';
            $html .= '<a class="kandaga-digital-access-link kandaga-digital-access-' . $type . '"'
                . ' href="' . $href . '"' . $target
                . ' title="' . $label . '" aria-label="' . $label . '">'
                . '<i class="fa-solid ' . $icon . '" aria-hidden="true"></i>'
                . '<span class="visually-hidden">' . $label . '</span></a>';
        }
        return $html . '</div>';
    }

    /**
     * Render a compact, non-clickable availability marker for search cards.
     * Detail pages retain the full links; search results only need to signal
     * that a digital surrogate exists without consuming card width.
     */
    function kandagaDigitalAccessIndicatorMarkup(array $row, int $id = 0): string
    {
        $items = kandagaDigitalAccessItems($row, $id);
        if (!$items) {
            return '';
        }

        $labels = [];
        $html = '<span class="kandaga-digital-indicators" role="img" aria-label="Akses digital tersedia">';
        foreach ($items as $item) {
            $label = htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $type = htmlspecialchars($item['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $icon = htmlspecialchars($item['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $labels[] = $label;
            $html .= '<i class="fa-solid ' . $icon . ' kandaga-digital-indicator kandaga-digital-indicator-' . $type . '"'
                . ' title="' . $label . '" aria-label="' . $label . '" aria-hidden="true"></i>';
        }
        return $html . '<span class="visually-hidden">' . htmlspecialchars(implode(' dan ', $labels), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span></span>';
    }
}
