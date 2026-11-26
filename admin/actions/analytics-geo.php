<?php
require_once __DIR__ . '/../../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode(['success' => false, 'disabled' => true, 'message' => 'Statistik geolokasi aplikasi telah dinonaktifkan.'], JSON_UNESCAPED_UNICODE);
