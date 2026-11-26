<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/version.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="icon" type="image/png" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/images/favicon.png">
<link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/images/favicon.png">
<link rel="apple-touch-icon" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/images/favicon.png">
<link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/module-fonts.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/mobile-compat.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php if (!defined('KANDAGA_GOAH_PANEL')): ?>
<link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/public-mobile.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/mobile-layout.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php if (defined('KANDAGA_GOAH_PANEL')): ?>
<link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/admin/assets/css/style-admin-consistency.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/js/mobile-detect.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php
// GA4 is public-site telemetry. The Goah admin panel must not emit pageviews
// or load the external tag, so opening an admin module stays self-contained.
if (!defined('KANDAGA_GOAH_PANEL')) include __DIR__ . '/analytics-tag.php';
?>
