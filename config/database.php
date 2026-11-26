<?php
require_once __DIR__ . '/bootstrap.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('BASE_URL')) {
    define('BASE_URL', ''); 
}

// Credentials come from config/database.local.php or KANDAGA_DB_* environment variables.
// Never commit real credentials to the source repository.
$servername = "localhost";
$database = "katalogus";
$username = "";
$password = "";

// Optional local settings; production credentials above remain unchanged.
$localHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$isLocalHost = in_array($localHost, ['localhost', '127.0.0.1', '[::1]', '::1'], true) || substr($localHost, -5) === '.test';
$port = 3306;
$socket = null;
if ($isLocalHost && is_file(__DIR__ . '/database.local.php')) {
    $settings = require __DIR__ . '/database.local.php';
    if (!is_array($settings)) throw new RuntimeException('config/database.local.php harus mengembalikan array konfigurasi.');
    $servername = $settings['host'] ?? $servername;
    $database = $settings['database'] ?? $database;
    $username = $settings['username'] ?? $username;
    $password = $settings['password'] ?? $password;
    $port = (int)($settings['port'] ?? 3306);
    $socket = $settings['socket'] ?? null;
}
foreach (['KANDAGA_DB_HOST' => 'servername', 'KANDAGA_DB_NAME' => 'database', 'KANDAGA_DB_USER' => 'username', 'KANDAGA_DB_PASSWORD' => 'password'] as $name => $variable) {
    $value = getenv($name);
    if ($value !== false) $$variable = $value;
}
if (getenv('KANDAGA_DB_PORT') !== false) $port = (int)getenv('KANDAGA_DB_PORT');
if (getenv('KANDAGA_DB_SOCKET') !== false) $socket = getenv('KANDAGA_DB_SOCKET');
try {
    $conn = mysqli_connect($servername, $username, $password, $database, $port, $socket);
} catch (mysqli_sql_exception $error) {
    error_log('Kandaga database connection failed (code ' . $error->getCode() . ')');
    if (defined('KANDAGA_DIAGNOSTIC')) throw $error;
    http_response_code(500);
    if ($isLocalHost) {
        echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Koneksi Kandaga</title><body><h2>Koneksi database belum tersedia</h2><p>Periksa nama database dan akun MySQL pada config/database.local.php atau config/database.php.</p><p>Login phpMyAdmin sebagai root belum memastikan PHP aplikasi dapat memakai akun yang sama.</p><p><a href="' . htmlspecialchars(BASE_URL . '/cek-localhost.php', ENT_QUOTES, 'UTF-8') . '">Buka pemeriksaan localhost</a></p></body></html>';
    } else echo 'Koneksi database belum tersedia.';
    exit;
}

// ... (fungsi tulis_log tetap sama) ...
if (!function_exists('tulis_log')) {
    function tulis_log($conn, $username, $pesan_aksi) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql = "INSERT INTO user_logs (username, waktu, aksi, ip_address) VALUES (?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $username, $pesan_aksi, $ip);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Gagal melakukan prepare log Kandaga: " . $conn->error);
        }
    }
}

// Set character set to UTF-8
mysqli_set_charset($conn, 'utf8mb4');
?>
