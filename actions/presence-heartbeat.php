<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
http_response_code(410);
echo json_encode([
    'success' => false,
    'disabled' => true,
    'message' => 'Pelacakan pengunjung online dinonaktifkan; statistik agregat tetap tersedia.',
], JSON_UNESCAPED_UNICODE);
