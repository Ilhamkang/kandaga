<?php
/** Dynamic social preview metadata for public pages. */
if (!defined('BASE_URL')) { return; }

$socialTitle = $socialTitle ?? 'KANDAGA — Katalog Naskah Sunda';
$socialDescription = $socialDescription ?? 'Katalog digital manuskrip dan pengetahuan pernaskahan Sunda.';
$socialImage = $socialImage ?? (BASE_URL . '/assets/images/favicon.png');
$socialUrl = $socialUrl ?? (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'));

$esc = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<meta property="og:title" content="<?= $esc($socialTitle) ?>">
<meta property="og:description" content="<?= $esc($socialDescription) ?>">
<meta property="og:url" content="<?= $esc($socialUrl) ?>">
<meta property="og:type" content="article">
<meta property="og:image" content="<?= $esc($socialImage) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= $esc($socialTitle) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $esc($socialTitle) ?>">
<meta name="twitter:description" content="<?= $esc($socialDescription) ?>">
<meta name="twitter:image" content="<?= $esc($socialImage) ?>">
