<?php
/**
 * Compatibility facade for the former raw visit logger.
 *
 * Since 3.1.54 Kandaga does not write or read the high-cardinality
 * website_visits / website_presence tables from public or admin requests.
 * Lightweight aggregate counters live in catalog-analytics.php. The two
 * request helpers below remain available because detail.php uses them when
 * recording the independent naskah_views catalog metric.
 */

require_once __DIR__ . '/analytics-context.php';
require_once __DIR__ . '/catalog-analytics.php';

function kandagaTrafficClass(?string $userAgent = null): array
{
    $ua = trim((string)($userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')));
    // Browser requests always carry a User-Agent. An empty one is almost
    // always a scripted client and must not inflate public counters.
    if ($ua === '') {
        return ['traffic_class' => 'unknown_bot', 'bot_name' => 'Empty User-Agent', 'is_bot' => 1];
    }
    $patterns = [
        'search_bot' => [
            'Googlebot' => '/googlebot|google-inspectiontool/i',
            'Bingbot' => '/bingbot|bingpreview/i',
            'PetalBot' => '/petalbot/i',
            'Applebot' => '/applebot/i',
        ],
        'social_bot' => [
            'Meta External Agent' => '/meta-externalagent|facebookexternalhit|facebot/i',
            'Twitterbot' => '/twitterbot/i',
            'LinkedInBot' => '/linkedinbot/i',
        ],
        'unknown_bot' => [
            'Crawler' => '/bot\b|crawler|spider|slurp|headless|archive\.org|wayback/i',
            'HTTP client' => '/python-requests|python-urllib|httpx\b|httpie|postmanruntime|go-http-client|node-fetch|axios\b|libwww-perl|selenium|playwright|puppeteer|scrapy|mechanize|curl\b|wget\b|java\/|perl\b|ruby\b/i',
            'Monitoring' => '/prometheus|datadog|newrelic|pingdom/i',
        ],
    ];
    foreach ($patterns as $class => $names) {
        foreach ($names as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                return ['traffic_class' => $class, 'bot_name' => $name, 'is_bot' => 1];
            }
        }
    }
    return ['traffic_class' => 'human', 'bot_name' => 'Human', 'is_bot' => 0];
}

function kandagaWebsiteVisitsAvailable($conn): bool
{
    return false;
}

function kandagaAnalyticsHumanPredicate(string $alias = ''): string
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    return "COALESCE({$prefix}is_bot,0)=0 AND COALESCE({$prefix}traffic_class,'')='human'";
}

function kandagaOnlineCount($conn, int $windowSeconds = 300): ?int
{
    return null;
}

function kandagaTouchPresence($conn, array $context = []): void
{
    // Presence tracking was intentionally retired. Keep the symbol for
    // stale extensions and cached templates that still call it.
}

function kandagaVerifyPresenceHeartbeat($conn): bool
{
    return false;
}

function kandagaTrackWebsite($conn): void
{
    kandagaCompactTrackPage($conn);
}

function kandagaWebsiteCounters($conn): ?array
{
    return kandagaCompactCounters($conn);
}

function kandagaAnalyticsDailySeries($conn, string $period = '30'): array
{
    return kandagaCompactDailySeries($conn, $period);
}

function kandagaArticleViews($conn, int $id): ?int
{
    return kandagaCompactArticleViews($conn, $id);
}
