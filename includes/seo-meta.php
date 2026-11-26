<?php
/** Centralized SEO metadata for public Kandaga pages. */
if (!defined('BASE_URL')) { return; }

if (!function_exists('kandagaSeoEscape')) {
    function kandagaSeoEscape($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    function kandagaSeoAbsoluteUrl(string $url): string {
        if (preg_match('~^https?://~i', $url)) return $url;
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/' . ltrim($url, '/');
    }
    function kandagaSeoDescription(string $value, string $fallback): string {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
        if ($value === '') return $fallback;
        return function_exists('mb_substr') ? mb_substr($value, 0, 240, 'UTF-8') : substr($value, 0, 240);
    }
}

$seoType = $seoType ?? 'website';
$seoTitle = trim((string)($seoTitle ?? 'KANDAGA — Katalog Naskah Sunda'));
$seoDescription = kandagaSeoDescription((string)($seoDescription ?? ''), 'Katalog digital manuskrip dan pengetahuan pernaskahan Sunda.');
$seoUrl = kandagaSeoAbsoluteUrl((string)($seoUrl ?? (BASE_URL . '/')));
$seoImage = trim((string)($seoImage ?? ''));
if ($seoImage !== '') $seoImage = kandagaSeoAbsoluteUrl($seoImage);
?>
<meta name="description" content="<?= kandagaSeoEscape($seoDescription) ?>">
<link rel="canonical" href="<?= kandagaSeoEscape($seoUrl) ?>">
<meta property="og:title" content="<?= kandagaSeoEscape($seoTitle) ?>">
<meta property="og:description" content="<?= kandagaSeoEscape($seoDescription) ?>">
<meta property="og:url" content="<?= kandagaSeoEscape($seoUrl) ?>">
<meta property="og:type" content="<?= kandagaSeoEscape($seoType === 'manuscript' ? 'article' : 'website') ?>">
<?php if ($seoImage !== ''): ?><meta property="og:image" content="<?= kandagaSeoEscape($seoImage) ?>">
<meta property="og:image:alt" content="<?= kandagaSeoEscape($seoTitle) ?>">
<?php endif; ?><meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= kandagaSeoEscape($seoTitle) ?>">
<meta name="twitter:description" content="<?= kandagaSeoEscape($seoDescription) ?>">
<?php if ($seoImage !== ''): ?><meta name="twitter:image" content="<?= kandagaSeoEscape($seoImage) ?>"><?php endif; ?>
<?php if ($seoType === 'manuscript' && !empty($row) && is_array($row)):
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => (string)($row['judul'] ?? $seoTitle),
        'url' => $seoUrl,
        'description' => $seoDescription,
    ];
    if (!empty($row['kode_inventaris'])) $schema['identifier'] = (string)$row['kode_inventaris'];
    if (!empty($row['nama_tempat'])) $schema['holdingArchive'] = ['@type' => 'ArchiveOrganization', 'name' => (string)$row['nama_tempat']];
    if (!empty($string_bahasa) && $string_bahasa !== '-') $schema['inLanguage'] = (string)$string_bahasa;
    if ($seoImage !== '') $schema['image'] = $seoImage;
    $schema['isPartOf'] = ['@type' => 'WebSite', 'name' => 'KANDAGA — Katalog Naskah Sunda', 'url' => kandagaSeoAbsoluteUrl(BASE_URL . '/')];
    $breadcrumb = ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
        ['@type'=>'ListItem','position'=>1,'name'=>'Beranda','item'=>kandagaSeoAbsoluteUrl(BASE_URL . '/')],
        ['@type'=>'ListItem','position'=>2,'name'=>'Pencarian Naskah','item'=>kandagaSeoAbsoluteUrl(BASE_URL . '/search')],
        ['@type'=>'ListItem','position'=>3,'name'=>(string)($row['judul'] ?? $seoTitle),'item'=>$seoUrl]
    ]];
?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php elseif ($seoType === 'website'): ?>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'KANDAGA — Katalog Naskah Sunda','url'=>$seoUrl,'potentialAction'=>['@type'=>'SearchAction','target'=>kandagaSeoAbsoluteUrl(BASE_URL . '/search?search={search_term_string}'),'query-input'=>'required name=search_term_string']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
