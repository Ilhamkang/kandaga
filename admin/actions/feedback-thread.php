<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../../includes/manuscript-feedback.php';
require_once __DIR__ . '/../../includes/mail-settings.php';
kandagaAuthorize(true);
header('Cache-Control: no-store, no-cache, must-revalidate');
try {
    $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) throw new InvalidArgumentException('Thread feedback tidak valid.');
    $thread = kandagaFeedbackThread($conn, (int)$id);
    $mail = kandagaMailRuntimeConfig($conn);
    kandagaJson(true, 'Thread dimuat.', [
        'feedback' => $thread['feedback'],
        'replies' => $thread['replies'],
        'meta' => $thread['meta'],
        'mail_configured' => (bool)$mail['configured'],
        'mail_enabled' => (bool)$mail['enabled'],
    ]);
} catch (Throwable $error) { kandagaFailure($error); }
