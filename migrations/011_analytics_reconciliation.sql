-- Kandaga v3.1.39: reconcile historical bot labels and defaults.
-- Additive and repeatable for MySQL 8 / MariaDB 10.11.
SET @kandaga_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='website_visits' AND column_name='bot_name'), 'ALTER TABLE website_visits MODIFY COLUMN bot_name VARCHAR(80) NOT NULL DEFAULT ''Unknown crawler''', 'SELECT 1');
PREPARE kandaga_stmt FROM @kandaga_sql; EXECUTE kandaga_stmt; DEALLOCATE PREPARE kandaga_stmt;
UPDATE website_visits
SET is_bot=CASE
  WHEN LOWER(COALESCE(user_agent,'')) REGEXP 'meta-externalagent|meta-externalfetcher|facebookexternalhit|facebot|twitterbot|linkedinbot|slackbot|whatsapp|pinterest|telegrambot|discordbot|googlebot|google-inspectiontool|bingbot|yandex|baiduspider|duckduckbot|applebot|petalbot|bot|crawler|spider|slurp|mediapartners|headless|bytespider|semrush|ahrefs' THEN 1 ELSE 0 END,
    traffic_class=CASE
  WHEN LOWER(COALESCE(user_agent,'')) REGEXP 'meta-externalagent|meta-externalfetcher|facebookexternalhit|facebot|twitterbot|linkedinbot|slackbot|whatsapp|pinterest|telegrambot|discordbot' THEN 'social_bot'
  WHEN LOWER(COALESCE(user_agent,'')) REGEXP 'googlebot|google-inspectiontool|bingbot|yandex|baiduspider|duckduckbot|applebot|petalbot' THEN 'search_bot'
  WHEN LOWER(COALESCE(user_agent,'')) REGEXP 'bot|crawler|spider|slurp|mediapartners|headless|bytespider|semrush|ahrefs' THEN 'unknown_bot'
  ELSE 'human' END,
    bot_name=CASE
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%meta-externalagent%' THEN 'Meta External Agent'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%meta-externalfetcher%' THEN 'Meta External Fetcher'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%google-inspectiontool%' THEN 'Google Inspection Tool'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%googlebot%' THEN 'Googlebot'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%bingbot%' THEN 'Bingbot'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%petalbot%' THEN 'PetalBot'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%yandex%' THEN 'YandexBot'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%baiduspider%' THEN 'BaiduSpider'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%duckduckbot%' THEN 'DuckDuckBot'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%applebot%' THEN 'Applebot'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%facebookexternalhit%' THEN 'Facebook External Hit'
  WHEN LOWER(COALESCE(user_agent,'')) LIKE '%twitterbot%' THEN 'Twitterbot'
  WHEN LOWER(COALESCE(user_agent,'')) REGEXP 'bot|crawler|spider|slurp|mediapartners|headless|bytespider|semrush|ahrefs' THEN 'Unknown crawler'
  ELSE 'Human' END;
