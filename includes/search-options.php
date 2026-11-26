<?php
// Presentation options only; database filters remain in search.php.
function kandagaSearchOptions(array $query) {
    $default = (($_COOKIE['kandaga_viewport'] ?? '') === 'mobile') ? 16 : 15;
    $perPage = filter_var($query['per_page'] ?? $default, FILTER_VALIDATE_INT);
    $view = $query['view'] ?? 'thumbnail'; // Viewport responsiveness belongs to CSS, not a cached user-agent default.
    return [
        'per_page' => in_array($perPage, [15, 16, 50, 100], true) ? $perPage : $default,
        'view' => $view === 'list' ? 'list' : 'thumbnail',
    ];
}

function kandagaSearchLink(array $query, array $changes = [], array $remove = []) {
    foreach ($remove as $key) unset($query[$key]);
    $query = array_replace($query, $changes);
    return '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}
