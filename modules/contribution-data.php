<?php
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { http_response_code(405); header('Allow: GET'); echo '{"error":"Metode tidak diizinkan."}'; exit; }
$year = filter_var($_GET['year'] ?? date('Y'), FILTER_VALIDATE_INT);
if (!$year || $year < 2000 || $year > (int)date('Y')) { http_response_code(400); echo '{"error":"Tahun tidak valid."}'; exit; }
require_once __DIR__ . '/../config/database.php'; require_once __DIR__ . '/../includes/contribution-data.php';
try { echo json_encode(['year' => $year] + kandagaContributionData($conn, $year), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); }
catch (Throwable $error) { http_response_code(500); echo '{"error":"Aktivitas belum dapat dimuat."}'; }
