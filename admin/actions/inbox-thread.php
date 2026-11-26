<?php
require_once __DIR__ . '/../includes/request.php';
require_once __DIR__ . '/../../includes/inbox-correspondence.php';
kandagaAuthorize(true);
header('Cache-Control: no-store, no-cache, must-revalidate');
try {
    $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) throw new InvalidArgumentException('Thread inbox tidak valid.');
    $thread = kandagaInboxThread($conn, (int)$id);
    $mail = kandagaMailRuntimeConfig($conn);
    kandagaJson(true, 'Thread inbox dimuat.', [
        // Keep the response envelope's human-readable `message` field intact.
        'inbox_message' => $thread['message'],
        'replies' => $thread['replies'],
        'meta' => $thread['meta'],
        'mail_configured' => (bool)$mail['configured'],
        'mail_enabled' => (bool)$mail['enabled'],
    ]);
} catch (Throwable $error) { kandagaFailure($error); }
