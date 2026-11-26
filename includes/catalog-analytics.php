<?php
/**
 * Statistik katalog ringkas.
 *
 * Modul ini menggantikan log request mentah untuk statistik halaman dan
 * pencarian. Tidak menyimpan IP, user-agent, perangkat, geolokasi, token
 * pengunjung, atau status online. Tabel dibuat secara idempoten saat pertama
 * kali dipakai sehingga rilis ini tetap dapat dipasang sebagai code-only.
 */

require_once __DIR__ . '/analytics-context.php';

function kandagaAnalyticsCacheFile(string $key): string {
    $dir = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'kandaga-analytics';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $namespace = defined('ROOT_PATH') ? (string)ROOT_PATH : 'kandaga';
    return $dir . DIRECTORY_SEPARATOR . sha1($namespace . '|' . $key) . '.json';
}

function kandagaAnalyticsCacheRead(string $key, int $ttl): ?array {
    $file = kandagaAnalyticsCacheFile($key);
    if (!is_file($file) || @filemtime($file) < time() - max(1, $ttl)) return null;
    $value = json_decode((string)@file_get_contents($file), true);
    return is_array($value) ? $value : null;
}

function kandagaAnalyticsCacheWrite(string $key, array $value): void {
    $file = kandagaAnalyticsCacheFile($key);
    @file_put_contents($file, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), LOCK_EX);
}

function kandagaCompactAnalyticsReady($conn): bool {
    static $ready = null;
    // Cache only a successful probe. A failed probe may be followed by the
    // lazy CREATE TABLE sequence in the same request.
    if ($ready === true) return true;
    if (!($conn instanceof mysqli)) return $ready = false;
    try {
        $result = $conn->query("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('kandaga_page_daily','kandaga_search_daily')");
        return $ready = $result && (int)($result->fetch_assoc()['n'] ?? 0) === 2;
    } catch (Throwable $error) {
        return $ready = false;
    }
}

function kandagaCompactAnalyticsEnsureSchema($conn): bool {
    static $attempted = false;
    static $ready = false;
    if ($attempted) return $ready;
    $attempted = true;
    if (!($conn instanceof mysqli)) return false;
    if (kandagaCompactAnalyticsReady($conn)) return $ready = true;
    try {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS kandaga_page_daily (
  day DATE NOT NULL,
  page_key VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  page_type VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'page',
  page_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  entity_id INT NULL,
  views BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (day, page_key),
  KEY idx_kandaga_page_daily_type_day (page_type, day),
  KEY idx_kandaga_page_daily_views (day, views)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kandaga_search_daily (
  day DATE NOT NULL,
  search_term VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  searches BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (day, search_term),
  KEY idx_kandaga_search_daily_searches (day, searches)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        if (!$conn->multi_query($sql)) return false;
        do {
            if ($result = $conn->store_result()) $result->free();
        } while ($conn->more_results() && $conn->next_result());
        $ready = kandagaCompactAnalyticsReady($conn);
    } catch (Throwable $error) {
        $ready = false;
    }
    return $ready;
}

function kandagaCompactAnalyticsNow(): DateTimeImmutable {
    return new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
}

function kandagaCompactAnalyticsDateBounds(string $period): array {
    $now = kandagaCompactAnalyticsNow();
    $today = $now->setTime(0, 0, 0);
    if ($period === 'daily') $start = $today;
    elseif ($period === 'weekly') $start = $today->modify('-6 days');
    elseif ($period === 'monthly') $start = $today->modify('first day of this month');
    else $start = null;
    return [$start ? $start->format('Y-m-d') : null, $now->format('Y-m-d')];
}

function kandagaCompactAnalyticsIsBot(string $ua): bool {
    if (function_exists('kandagaTrafficClass')) {
        return !empty(kandagaTrafficClass($ua)['is_bot']);
    }
    return (bool)preg_match('/bot|crawler|spider|slurp|headless|meta-external|googlebot|bingbot|petalbot|applebot|facebookexternalhit/i', $ua);
}

function kandagaCompactPageContext(): array {
    if (function_exists('kandagaPageContext')) return kandagaPageContext();
    $path = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php'), '.php');
    return [
        'type' => $path === 'search' ? 'search' : 'page',
        'key' => $path === 'index' ? '/' : '/' . $path,
        'title' => ucwords(str_replace('-', ' ', $path)),
        'entity' => null,
        'search' => $path === 'search' ? kandagaNormalizeSearch($_GET['search'] ?? null) : null,
    ];
}

function kandagaCompactTrackPage($conn): void {
    static $recorded = false;
    if ($recorded || PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return;
    $recorded = true;
    if (!kandagaCompactAnalyticsEnsureSchema($conn)) return;

    try {
        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512, 'UTF-8');
        if (kandagaCompactAnalyticsIsBot($ua)) return;
        $requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        if ($requestPath !== '' && (str_starts_with($requestPath, '/admin') || str_contains($requestPath, '/admin/'))) return;
        $context = kandagaCompactPageContext();
        $key = trim((string)($context['key'] ?? ''));
        if ($key === '' || str_starts_with($key, '/admin')) return;
        $now = kandagaCompactAnalyticsNow();
        $day = $now->format('Y-m-d');
        $pageType = mb_substr((string)($context['type'] ?? 'page'), 0, 32, 'UTF-8');
        $pageTitle = mb_substr((string)($context['title'] ?? ''), 0, 255, 'UTF-8');
        $entityId = isset($context['entity']) && is_numeric($context['entity']) ? (int)$context['entity'] : null;

        $stmt = $conn->prepare('INSERT INTO kandaga_page_daily (day,page_key,page_type,page_title,entity_id,views) VALUES (?,?,?,?,?,1) ON DUPLICATE KEY UPDATE page_title=VALUES(page_title),page_type=VALUES(page_type),entity_id=VALUES(entity_id),views=views+1');
        if ($stmt) {
            $stmt->bind_param('ssssi', $day, $key, $pageType, $pageTitle, $entityId);
            $stmt->execute();
            $stmt->close();
        }

        if (($context['type'] ?? '') === 'search') {
            $term = kandagaNormalizeSearch($context['search'] ?? ($_GET['search'] ?? null));
            if ($term !== null) {
                $signature = hash('sha256', json_encode([
                    'search' => $term,
                    'aksara' => $_GET['aksara'] ?? null,
                    'bahasa' => $_GET['bahasa'] ?? null,
                    'alas_tulis' => $_GET['alas_tulis'] ?? null,
                    'id_lokasi' => $_GET['id_lokasi'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                if (($_SESSION['kandaga_compact_search_signature'] ?? null) !== $signature) {
                    $_SESSION['kandaga_compact_search_signature'] = $signature;
                    $searchStmt = $conn->prepare('INSERT INTO kandaga_search_daily (day,search_term,searches) VALUES (?,?,1) ON DUPLICATE KEY UPDATE searches=searches+1');
                    if ($searchStmt) {
                        $searchStmt->bind_param('ss', $day, $term);
                        $searchStmt->execute();
                        $searchStmt->close();
                    }
                }
            }
        } else {
            unset($_SESSION['kandaga_compact_search_signature']);
        }
    } catch (Throwable $error) {
        error_log('Kandaga compact analytics: ' . $error->getMessage());
    }
}

function kandagaCompactCounters($conn): ?array {
    static $counters = null;
    if ($counters !== null) return $counters;
    if (!kandagaCompactAnalyticsEnsureSchema($conn)) return null;
    try {
        $today = kandagaCompactAnalyticsNow()->format('Y-m-d');
        [$weekStart] = kandagaCompactAnalyticsDateBounds('weekly');
        [$monthStart] = kandagaCompactAnalyticsDateBounds('monthly');
        $stmt = $conn->prepare('SELECT COALESCE(SUM(CASE WHEN day=? THEN views ELSE 0 END),0) AS daily, COALESCE(SUM(CASE WHEN day>=? THEN views ELSE 0 END),0) AS weekly, COALESCE(SUM(CASE WHEN day>=? THEN views ELSE 0 END),0) AS monthly, COALESCE(SUM(views),0) AS total FROM kandaga_page_daily');
        $stmt->bind_param('sss', $today, $weekStart, $monthStart);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $counters = [
            'daily' => (int)($row['daily'] ?? 0),
            'weekly' => (int)($row['weekly'] ?? 0),
            'monthly' => (int)($row['monthly'] ?? 0),
            'total' => (int)($row['total'] ?? 0),
        ];
    } catch (Throwable $error) {
        return null;
    }
}

function kandagaCompactDailySeries($conn, string $period = '30'): array {
    if (!kandagaCompactAnalyticsEnsureSchema($conn)) return [];
    $days = $period === 'all' ? 36500 : max(1, min(3650, (int)$period));
    $start = kandagaCompactAnalyticsNow()->setTime(0, 0, 0)->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
    try {
        $stmt = $conn->prepare('SELECT day, SUM(views) AS views FROM kandaga_page_daily WHERE day>=? GROUP BY day ORDER BY day');
        $stmt->bind_param('s', $start);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    } catch (Throwable $error) {
        return [];
    }
}

function kandagaCompactPopularPages($conn, int $limit = 10, int $days = 90): array {
    if (!kandagaCompactAnalyticsEnsureSchema($conn)) return [];
    $limit = max(1, min(50, $limit));
    $start = kandagaCompactAnalyticsNow()->modify('-' . max(1, $days) . ' days')->format('Y-m-d');
    try {
        $sql = 'SELECT COALESCE(MAX(page_title), page_key) AS title, page_key AS path, SUM(views) AS views FROM kandaga_page_daily WHERE day>=? GROUP BY page_key ORDER BY views DESC,path LIMIT ' . $limit;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $start);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    } catch (Throwable $error) {
        return [];
    }
}

function kandagaCompactTopSearches($conn, int $limit = 10, int $days = 90): array {
    if (!kandagaCompactAnalyticsEnsureSchema($conn)) return [];
    $limit = max(1, min(50, $limit));
    $start = kandagaCompactAnalyticsNow()->modify('-' . max(1, $days) . ' days')->format('Y-m-d');
    try {
        $sql = 'SELECT search_term, SUM(searches) AS total FROM kandaga_search_daily WHERE day>=? GROUP BY search_term ORDER BY total DESC,search_term LIMIT ' . $limit;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $start);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    } catch (Throwable $error) {
        return [];
    }
}

function kandagaCompactArticleViews($conn, int $id): ?int {
    if (!kandagaCompactAnalyticsEnsureSchema($conn)) return null;
    try {
        $key = '/kabar/' . $id;
        $stmt = $conn->prepare('SELECT COALESCE(SUM(views),0) AS total FROM kandaga_page_daily WHERE page_key=?');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    } catch (Throwable $error) {
        return null;
    }
}
