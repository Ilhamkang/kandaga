<?php
/**
 * Server-side mail configuration for Kandaga.
 *
 * Non-secret values are kept in a small application table. The mailbox
 * password is kept in a protected local PHP file outside the package tree,
 * so an update ZIP never contains or overwrites it.
 */

require_once __DIR__ . '/../config/bootstrap.php';

function kandagaMailConfigPath(): string
{
    $configured = trim((string)(getenv('KANDAGA_MAIL_CONFIG') ?: ''));
    if ($configured !== '' && str_starts_with($configured, DIRECTORY_SEPARATOR)) return $configured;
    return dirname(ROOT_PATH, 2) . DIRECTORY_SEPARATOR . 'kandaga-mail.local.php';
}

function kandagaMailEnsureSchema(mysqli $conn): bool
{
    static $ready = null;
    if ($ready === true) return true;
    try {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS kandaga_mail_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  smtp_host VARCHAR(255) NOT NULL DEFAULT '',
  smtp_port SMALLINT UNSIGNED NOT NULL DEFAULT 465,
  encryption VARCHAR(12) NOT NULL DEFAULT 'ssl',
  username VARCHAR(254) NOT NULL DEFAULT '',
  from_email VARCHAR(254) NOT NULL DEFAULT '',
  from_name VARCHAR(150) NOT NULL DEFAULT 'Kandaga Katalog',
  test_recipient VARCHAR(254) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        if (!$conn->query($sql)) return $ready = false;
        $stmt = $conn->prepare("INSERT IGNORE INTO kandaga_mail_settings (id) VALUES (1)");
        if ($stmt) { $stmt->execute(); $stmt->close(); }
        return $ready = true;
    } catch (Throwable $error) {
        error_log('Kandaga mail schema: ' . $error->getMessage());
        return $ready = false;
    }
}

function kandagaMailReadSecret(): array
{
    $path = kandagaMailConfigPath();
    if (!is_file($path) || !is_readable($path)) return [];
    try {
        $value = require $path;
        return is_array($value) ? $value : [];
    } catch (Throwable $error) {
        error_log('Kandaga mail secret: ' . $error->getMessage());
        return [];
    }
}

function kandagaMailWriteSecret(string $password): bool
{
    $path = kandagaMailConfigPath();
    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) return false;
    $payload = "<?php\nreturn " . var_export(['password' => $password], true) . ";\n";
    $temporary = tempnam($directory, '.kandaga-mail-');
    if ($temporary === false) return false;
    @chmod($temporary, 0600);
    $written = @file_put_contents($temporary, $payload, LOCK_EX);
    if ($written === false || !@rename($temporary, $path)) {
        @unlink($temporary);
        return false;
    }
    @chmod($path, 0600);
    return true;
}

function kandagaMailSettings(mysqli $conn): array
{
    $defaults = [
        'enabled' => 0,
        'smtp_host' => '',
        'smtp_port' => 465,
        'encryption' => 'ssl',
        'username' => '',
        'from_email' => 'katalog@kairaga.com',
        'from_name' => 'Kandaga Katalog',
        'test_recipient' => 'katalog@kairaga.com',
        'password_configured' => false,
        'config_path' => kandagaMailConfigPath(),
    ];
    if (!kandagaMailEnsureSchema($conn)) return $defaults;
    try {
        $row = $conn->query('SELECT enabled,smtp_host,smtp_port,encryption,username,from_email,from_name,test_recipient FROM kandaga_mail_settings WHERE id=1')->fetch_assoc() ?: [];
        $settings = array_merge($defaults, $row);
        $secret = kandagaMailReadSecret();
        $settings['password_configured'] = isset($secret['password']) && is_string($secret['password']) && $secret['password'] !== '';
        $settings['smtp_port'] = (int)$settings['smtp_port'];
        $settings['enabled'] = (int)$settings['enabled'];
        return $settings;
    } catch (Throwable $error) {
        return $defaults;
    }
}

function kandagaMailValidateInput(array $input): array
{
    $host = trim((string)($input['smtp_host'] ?? ''));
    $port = (int)($input['smtp_port'] ?? 0);
    $encryption = strtolower(trim((string)($input['encryption'] ?? 'ssl')));
    $username = trim((string)($input['username'] ?? ''));
    $fromEmail = trim((string)($input['from_email'] ?? ''));
    $fromName = trim((string)($input['from_name'] ?? 'Kandaga Katalog'));
    $testRecipient = trim((string)($input['test_recipient'] ?? ''));
    foreach ([$host, $username, $fromEmail, $fromName, $testRecipient] as $value) {
        if (str_contains($value, "\r") || str_contains($value, "\n")) throw new InvalidArgumentException('Konfigurasi email mengandung baris yang tidak valid.');
    }
    if ($host === '' || strlen($host) > 255) throw new InvalidArgumentException('SMTP host wajib diisi.');
    if ($port < 1 || $port > 65535) throw new InvalidArgumentException('Port SMTP tidak valid.');
    if (!in_array($encryption, ['ssl', 'tls', 'none'], true)) throw new InvalidArgumentException('Jenis enkripsi SMTP tidak valid.');
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Username SMTP harus berupa alamat email yang valid.');
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Alamat pengirim tidak valid.');
    if ($fromName === '' || mb_strlen($fromName, 'UTF-8') > 150) throw new InvalidArgumentException('Nama pengirim tidak valid.');
    if ($testRecipient !== '' && !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Alamat penerima uji tidak valid.');
    return compact('host', 'port', 'encryption', 'username', 'fromEmail', 'fromName', 'testRecipient');
}

function kandagaMailSaveSettings(mysqli $conn, array $input): array
{
    $values = kandagaMailValidateInput($input);
    if (!kandagaMailEnsureSchema($conn)) throw new RuntimeException('Tabel pengaturan email belum dapat dibuat.');
    $password = trim((string)($input['password'] ?? ''));
    if ($password !== '') {
        if (strlen($password) > 512 || str_contains($password, "\0")) throw new InvalidArgumentException('Password SMTP tidak valid.');
        if (!kandagaMailWriteSecret($password)) throw new RuntimeException('File rahasia email tidak dapat ditulis. Atur konfigurasi lokal server secara manual.');
    }
    $stmt = $conn->prepare("UPDATE kandaga_mail_settings SET enabled=0,smtp_host=?,smtp_port=?,encryption=?,username=?,from_email=?,from_name=?,test_recipient=? WHERE id=1");
    if (!$stmt) throw new RuntimeException('Pengaturan email tidak dapat disiapkan.');
    $stmt->bind_param('sisssss', $values['host'], $values['port'], $values['encryption'], $values['username'], $values['fromEmail'], $values['fromName'], $values['testRecipient']);
    $stmt->execute();
    $stmt->close();
    return kandagaMailSettings($conn);
}

function kandagaMailRuntimeConfig(mysqli $conn): array
{
    $settings = kandagaMailSettings($conn);
    $secret = kandagaMailReadSecret();
    $settings['password'] = is_string($secret['password'] ?? null) ? $secret['password'] : '';
    $settings['configured'] = $settings['smtp_host'] !== '' && $settings['username'] !== '' && $settings['password'] !== '' && filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL);
    return $settings;
}
