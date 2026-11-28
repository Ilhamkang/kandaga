<?php
/**
 * Shared options and plain-text narratives for physical manuscript access and
 * digital archive availability. Values are validated in PHP instead of SQL
 * ENUM so future options can be added without another schema change.
 */
if (!function_exists('kandagaAccessModeOptions')) {
    function kandagaAccessModeOptions(): array
    {
        return [
            'di_tempat_penyimpanan' => 'Dapat diakses di tempat penyimpanan',
            'pemilik_naskah' => 'Disimpan oleh pemilik naskah',
            'dengan_izin' => 'Akses dengan izin pengelola',
            'terbatas' => 'Akses fisik terbatas',
            'tidak_diketahui' => 'Belum ada informasi akses fisik',
        ];
    }
}

if (!function_exists('kandagaDigitalStatusOptions')) {
    function kandagaDigitalStatusOptions(): array
    {
        return [
            'publik' => 'Arsip digital dapat diakses publik',
            'terbatas' => 'Arsip digital terbatas / berizin',
            'belum_tersedia' => 'Arsip digital belum tersedia secara publik',
            'tidak_diketahui' => 'Status arsip digital belum dikonfirmasi',
        ];
    }
}

if (!function_exists('kandagaAccessOptionsMarkup')) {
    function kandagaAccessOptionsMarkup(array $options, string $selected, string $emptyLabel = 'Belum ditentukan'): string
    {
        $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<option value="">' . $escape($emptyLabel) . '</option>';
        foreach ($options as $value => $label) {
            $chosen = ((string) $value === $selected) ? ' selected' : '';
            $html .= '<option value="' . $escape($value) . '"' . $chosen . '>' . $escape($label) . '</option>';
        }
        return $html;
    }
}

if (!function_exists('kandagaHasDigitalArchiveLink')) {
    function kandagaHasDigitalArchiveLink(array $row): bool
    {
        foreach (['url_pdf', 'iiif_manifest', 'url_repositori'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
                continue;
            }
            $scheme = strtolower((string) (parse_url($value, PHP_URL_SCHEME) ?? ''));
            if (in_array($scheme, ['http', 'https'], true)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('kandagaAccessNarrative')) {
    /**
     * @return array{fisik:string,digital:string,keterangan:string}
     */
    function kandagaAccessNarrative(array $row): array
    {
        $mode = trim((string) ($row['mode_akses_fisik'] ?? ''));
        $status = trim((string) ($row['status_arsip_digital'] ?? ''));
        $contact = trim(strip_tags((string) ($row['pihak_kontak'] ?? '')));
        $note = trim(strip_tags((string) ($row['keterangan_arsip_digital'] ?? '')));
        $contactText = $contact !== ''
            ? 'pihak ' . $contact
            : 'halaman kontak KANDAGA';

        switch ($mode) {
            case 'di_tempat_penyimpanan':
                $physical = 'Naskah fisik hanya dapat diakses langsung di tempat penyimpanan.';
                break;
            case 'pemilik_naskah':
                $physical = 'Naskah disimpan oleh pemilik naskah. Silakan hubungi ' . $contactText . ' untuk informasi lebih lanjut.';
                break;
            case 'dengan_izin':
                $physical = 'Akses naskah fisik memerlukan izin dari ' . $contactText . '.';
                break;
            case 'terbatas':
                $physical = 'Akses naskah fisik terbatas. Informasi lebih lanjut dapat disampaikan melalui ' . $contactText . '.';
                break;
            case 'tidak_diketahui':
                $physical = 'Informasi akses naskah fisik belum tersedia.';
                break;
            default:
                $physical = 'Informasi akses naskah fisik belum tercatat.';
        }

        switch ($status) {
            case 'publik':
                $digital = kandagaHasDigitalArchiveLink($row)
                    ? 'Arsip digital dapat diakses melalui tautan yang tersedia di bawah.'
                    : 'Arsip digital dinyatakan dapat diakses publik. Tautan resminya dapat ditanyakan melalui ' . $contactText . '.';
                break;
            case 'terbatas':
                $digital = 'Arsip digital tersedia dengan batasan tertentu. Silakan hubungi ' . $contactText . ' untuk informasi akses.';
                break;
            case 'belum_tersedia':
                $digital = 'Arsip digital belum tersedia secara publik.';
                break;
            case 'tidak_diketahui':
                $digital = 'Status arsip digital belum dikonfirmasi.';
                break;
            default:
                $digital = kandagaHasDigitalArchiveLink($row)
                    ? 'Arsip digital dapat diakses melalui tautan yang tersedia di bawah.'
                    : 'Arsip digital belum tersedia secara publik.';
        }

        return ['fisik' => $physical, 'digital' => $digital, 'keterangan' => $note];
    }
}
