<?php
/**
 * Small dependency-free SMTP sender used only by the administrator reply flow.
 * It deliberately keeps credentials out of the browser and out of SQL.
 */
require_once __DIR__ . '/mail-settings.php';

function kandagaSmtpResponse($socket): string
{
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 2048);
        if ($line === false) break;
        $response .= $line;
        if (preg_match('/^([0-9]{3})([ -])/', $line, $match) && $match[2] === ' ') break;
    }
    return trim($response);
}

function kandagaSmtpCode(string $response): int
{
    return preg_match('/^([0-9]{3})/', $response, $match) ? (int)$match[1] : 0;
}

function kandagaSmtpCommand($socket, string $command, array $accepted): string
{
    if (@fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException('Koneksi SMTP gagal mengirim perintah.');
    }
    $response = kandagaSmtpResponse($socket);
    if (!in_array(kandagaSmtpCode($response), $accepted, true)) {
        throw new RuntimeException('Server SMTP menolak salah satu langkah pengiriman.');
    }
    return $response;
}

function kandagaSmtpHeader(string $name, string $value): string
{
    $value = str_replace(["\r", "\n"], '', $value);
    return $name . ': ' . (function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n") : $value);
}

function kandagaSmtpSend(mysqli $conn, string $to, string $subject, string $body, bool $test = false): array
{
    $config = kandagaMailRuntimeConfig($conn);
    if (!$config['configured'] || (!$test && !(int)$config['enabled'])) {
        throw new RuntimeException('SMTP belum dikonfigurasi atau belum diaktifkan melalui email uji.');
    }
    foreach ([$to, $subject] as $value) {
        if (str_contains($value, "\r") || str_contains($value, "\n")) throw new InvalidArgumentException('Pesan email mengandung karakter header yang tidak valid.');
    }
    if (str_contains($body, "\0")) throw new InvalidArgumentException('Isi email mengandung karakter yang tidak valid.');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || mb_strlen($subject, 'UTF-8') > 255 || $body === '') {
        throw new InvalidArgumentException('Penerima atau isi email tidak valid.');
    }
    $host = (string)$config['smtp_host'];
    $port = (int)$config['smtp_port'];
    $encryption = (string)$config['encryption'];
    $transport = $encryption === 'ssl' ? 'ssl://' . $host : 'tcp://' . $host;
    $context = stream_context_create(['ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'peer_name' => $host,
        'allow_self_signed' => false,
    ]]);
    $errno = 0; $errstr = '';
    $socket = @stream_socket_client($transport . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) throw new RuntimeException('Server SMTP tidak dapat dihubungi.');
    stream_set_timeout($socket, 15);
    $messageId = '<' . bin2hex(random_bytes(12)) . '@' . (parse_url(defined('BASE_URL') ? BASE_URL : '', PHP_URL_HOST) ?: 'kairaga.com') . '>';
    try {
        $greeting = kandagaSmtpResponse($socket);
        if (!in_array(kandagaSmtpCode($greeting), [220], true)) throw new RuntimeException('Sapaan server SMTP tidak valid.');
        kandagaSmtpCommand($socket, 'EHLO katalog.kairaga.com', [250]);
        if ($encryption === 'tls') {
            kandagaSmtpCommand($socket, 'STARTTLS', [220]);
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS SMTP gagal diaktifkan.');
            kandagaSmtpCommand($socket, 'EHLO katalog.kairaga.com', [250]);
        }
        kandagaSmtpCommand($socket, 'AUTH LOGIN', [334]);
        kandagaSmtpCommand($socket, base64_encode((string)$config['username']), [334]);
        kandagaSmtpCommand($socket, base64_encode((string)$config['password']), [235]);
        kandagaSmtpCommand($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
        kandagaSmtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        kandagaSmtpCommand($socket, 'DATA', [354]);
        $headers = [
            kandagaSmtpHeader('From', $config['from_name'] . ' <' . $config['from_email'] . '>'),
            kandagaSmtpHeader('To', $to),
            kandagaSmtpHeader('Reply-To', $config['from_email']),
            kandagaSmtpHeader('Subject', $subject),
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'Message-ID: ' . $messageId,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $safeBody = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body));
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", (string)$safeBody) . "\r\n.";
        if (@fwrite($socket, $payload . "\r\n") === false) throw new RuntimeException('Isi email gagal dikirim.');
        $response = kandagaSmtpResponse($socket);
        if (!in_array(kandagaSmtpCode($response), [250], true)) throw new RuntimeException('Server SMTP tidak menerima isi email.');
        kandagaSmtpCommand($socket, 'QUIT', [221, 250]);
        fclose($socket);
        return ['message_id' => $messageId];
    } catch (Throwable $error) {
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        throw $error;
    }
}
