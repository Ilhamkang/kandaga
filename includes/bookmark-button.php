<?php
function kandagaBookmarkButton($id, $title = '') {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return '<button type="button" class="btn btn-sm btn-outline-primary kandaga-bookmark-button" data-kandaga-bookmark="' . (int)$id . '" data-bookmark-title="' . $safeTitle . '" aria-label="Tambahkan ke bookmark' . ($title !== '' ? ': ' . $safeTitle : '') . '" title="Bookmark" aria-pressed="false"><i class="fa-regular fa-bookmark mr-1" aria-hidden="true"></i><span>Bookmark</span></button>';
}
