<?php
// Optional override; preserve this configuration when updating application code.
return [
    'geo_provider' => 'ipwhois', // 'disabled' prevents sending IPs to the geo service.
    'trusted_proxy_ips' => [], // Exact proxy IPs only. Never trust arbitrary forwarded headers.
    'detail_retention_days' => 90, // CLI cleanup redacts IP/UA/search detail, retaining page-view totals.
    'google_measurement_id' => '', // Optional GA4 ID, e.g. G-XXXXXXXXXX; leave empty for first-party only.
    // Optional Data API dashboard (requires a service account with Viewer access
    // to the GA4 property). Keep the JSON/file outside the public web root.
    'google_property_id' => '558112895', // Numeric GA4 property ID, not the Measurement ID.
    'google_service_account_file' => '', // e.g. /etc/kandaga/ga4-service-account.json
    'google_service_account_json' => '', // Alternative: JSON string; do not commit it.
];
