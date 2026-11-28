<?php
/**
 * Shared public page header: one compact, token-based heading with an optional
 * short description and actions. Deliberately no breadcrumb so reading space
 * stays generous on both desktop and mobile.
 */
if (!function_exists('kandagaPublicHeader')) {
    function kandagaPublicHeader(string $title, string $icon = '', string $description = '', string $actions = '', string $eyebrow = ''): void
    {
        $iconMarkup = $icon !== ''
            ? '<span class="kandaga-page-head__icon" aria-hidden="true"><i class="fa-solid ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i></span>'
            : '';
        $eyebrowMarkup = $eyebrow !== ''
            ? '<p class="kandaga-page-head__eyebrow">' . htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';
        $descriptionMarkup = $description !== ''
            ? '<p class="kandaga-page-head__lead">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';
        $actionsMarkup = $actions !== ''
            ? '<div class="kandaga-page-head__actions">' . $actions . '</div>'
            : '';
        echo '<header class="kandaga-page-head">'
            . '<div class="container">'
            . '<div class="kandaga-page-head__row">'
            . $iconMarkup
            . '<div class="kandaga-page-head__copy">'
            . $eyebrowMarkup
            . '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
            . $descriptionMarkup
            . '</div>'
            . $actionsMarkup
            . '</div>'
            . '</div>'
            . '</header>';
    }
}

if (!function_exists('kandagaPublicSearchActions')) {
    /**
     * Compact search control for page headers. The field stays collapsed until
     * requested, keeping the reading area light on both desktop and mobile.
     *
     * @param array<string, string> $hidden Extra GET fields (already escaped by this helper).
     */
    function kandagaPublicSearchActions(
        string $action,
        string $value = '',
        array $hidden = [],
        string $placeholder = 'Cari...',
        string $id = 'kandagaPublicSearchForm',
        string $label = 'Cari'
    ): string {
        $fields = '';
        foreach ($hidden as $name => $fieldValue) {
            $fields .= '<input type="hidden" name="' . htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string)$fieldValue, ENT_QUOTES, 'UTF-8') . '">';
        }
        return '<div class="kandaga-page-head__search">'
            . '<button type="button" class="kandaga-page-head__search-toggle" data-kandaga-search-toggle aria-expanded="false" aria-controls="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</button>'
            . '<div class="kandaga-page-head__search-panel" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" hidden>'
            . '<form method="get" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" class="kandaga-page-head__search-form" role="search">'
            . $fields
            . '<input class="form-control" type="search" name="search" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '" maxlength="180" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
            . '<button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="visually-hidden">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></button>'
            . '</form>'
            . '</div>'
            . '</div>';
    }
}
