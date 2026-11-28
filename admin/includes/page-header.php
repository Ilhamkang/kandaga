<?php
/**
 * Header submodul Goah yang seragam: judul h1 + ikon + deskripsi singkat.
 * $actions adalah HTML dari modul pemanggil (tombol/badge) dan dianggap aman.
 */
function kandagaPageHeader(string $title, string $icon = '', string $description = '', string $actions = '', string $id = ''): void
{
    $iconClass = preg_match('/^fa-[a-z0-9-]+(?: fa-[a-z0-9-]+)*$/i', trim($icon)) ? trim($icon) : '';
    $titleHtml = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $idAttr = $id !== '' ? ' id="' . preg_replace('/[^a-zA-Z0-9_-]/', '', $id) . '"' : '';
    echo '<div class="admin-page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">';
    echo '<div class="admin-page-header__copy">';
    echo '<h1' . $idAttr . '>';
    if ($iconClass !== '') echo '<i class="fa-solid ' . $iconClass . ' me-2" aria-hidden="true"></i>';
    echo $titleHtml . '</h1>';
    if ($description !== '') echo '<p class="mb-0">' . $description . '</p>';
    echo '</div>';
    if ($actions !== '') echo '<div class="admin-page-header__actions">' . $actions . '</div>';
    echo '</div>';
}
