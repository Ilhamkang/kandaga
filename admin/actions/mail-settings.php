<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../../includes/mail-settings.php';
require_once __DIR__ . '/../../includes/smtp-mailer.php';
kandagaPost(true);
$redirect = BASE_URL . '/admin/index.php?page=pengaturan';
try {
    $action = (string)($_POST['action'] ?? 'save');
    if ($action === 'save') {
        kandagaMailSaveSettings($conn, $_POST);
        header('Location: ' . $redirect . '&saved=1'); exit;
    }
    if ($action === 'test') {
        $recipient = trim((string)($_POST['test_recipient'] ?? ''));
        $config = kandagaMailRuntimeConfig($conn);
        if ($recipient === '') $recipient = (string)$config['test_recipient'];
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Alamat penerima uji belum valid.');
        kandagaSmtpSend($conn, $recipient, 'Uji koneksi email Kandaga', "Uji koneksi SMTP berhasil.\n\nPesan ini dikirim dari menu Pengaturan Kandaga.", true);
        kandagaMailEnsureSchema($conn);
        $conn->query('UPDATE kandaga_mail_settings SET enabled=1 WHERE id=1');
        header('Location: ' . $redirect . '&tested=1'); exit;
    }
    throw new InvalidArgumentException('Aksi pengaturan email tidak valid.');
} catch (Throwable $error) {
    header('Location: ' . $redirect . '&error=' . rawurlencode($error->getMessage())); exit;
}
