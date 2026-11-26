<?php
// GA4 is optional external telemetry only. It is not mixed with Kandaga's
// internal catalog statistics or any server-side request log.
require_once __DIR__ . '/../config/analytics.php';
$kandagaGaId = kandagaAnalyticsSettings()['google_measurement_id'] ?? '';
if ($kandagaGaId !== ''):
?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($kandagaGaId, ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= htmlspecialchars($kandagaGaId, ENT_QUOTES, 'UTF-8') ?>', {anonymize_ip: true});
</script>
<?php endif; ?>
