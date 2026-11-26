<?php
function kandagaAnalyticsSettings() {
    static $settings;
    if ($settings !== null) return $settings;
    $settings = [
        'geo_provider' => 'ipwhois',
        'trusted_proxy_ips' => [],
        'detail_retention_days' => 90,
        'google_measurement_id' => '',
        // Optional Google Analytics Data API settings for the admin dashboard.
        'google_property_id' => '558112895',
        'google_service_account_file' => '',
        'google_service_account_json' => '',
    ];
    if (is_file(__DIR__ . '/analytics.local.php')) {
        $local = require __DIR__ . '/analytics.local.php';
        if (is_array($local)) $settings = array_replace($settings, $local);
    }
    if (getenv('KANDAGA_GEO_PROVIDER') !== false) $settings['geo_provider'] = getenv('KANDAGA_GEO_PROVIDER');
    if (getenv('KANDAGA_GA_MEASUREMENT_ID') !== false) $settings['google_measurement_id'] = getenv('KANDAGA_GA_MEASUREMENT_ID');
    if (getenv('KANDAGA_GA_PROPERTY_ID') !== false) $settings['google_property_id'] = getenv('KANDAGA_GA_PROPERTY_ID');
    if (getenv('KANDAGA_GA_SERVICE_ACCOUNT_FILE') !== false) $settings['google_service_account_file'] = getenv('KANDAGA_GA_SERVICE_ACCOUNT_FILE');
    if (getenv('KANDAGA_GA_SERVICE_ACCOUNT_JSON') !== false) $settings['google_service_account_json'] = getenv('KANDAGA_GA_SERVICE_ACCOUNT_JSON');
    $settings['geo_provider'] = $settings['geo_provider'] === 'ipwhois' ? 'ipwhois' : 'disabled';
    $settings['trusted_proxy_ips'] = is_array($settings['trusted_proxy_ips']) ? $settings['trusted_proxy_ips'] : [];
    $settings['detail_retention_days'] = max(1, min(3650, (int)$settings['detail_retention_days']));
    $settings['google_measurement_id'] = preg_match('/^G-[A-Z0-9]+$/i', (string)$settings['google_measurement_id']) ? strtoupper($settings['google_measurement_id']) : '';
    $settings['google_property_id'] = preg_replace('/[^0-9]/', '', (string)$settings['google_property_id']);
    return $settings;
}
