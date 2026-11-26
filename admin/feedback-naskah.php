<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/request.php';
kandagaAuthorize(true);
if (!defined('KANDAGA_GOAH_PANEL')) {
    header('Location: ' . BASE_URL . '/admin/index.php?page=feedback-naskah'); exit;
}
require_once __DIR__ . '/../includes/manuscript-feedback.php';
if (!kandagaFeedbackReady($conn)) {
    echo '<div class="alert alert-info">Modul Feedback Naskah belum diaktifkan. Impor <code>migrations/006_manuscript_feedback.sql</code> pada database Kandaga melalui phpMyAdmin, lalu muat ulang halaman ini.</div>';
    return;
}
$q = mb_substr(kandagaFeedbackInput($_GET, 'q'), 0, 150, 'UTF-8');
$filter = kandagaFeedbackInput($_GET, 'status');
if (!in_array($filter, ['read','unread'], true)) $filter = '';
$pageNo = max(1, (int)($_GET['p'] ?? 1)); $limit = 25;
$where = ' WHERE 1=1'; $types = ''; $params = [];
if ($q !== '') {
    // Literal substring search, including percent/underscore characters.
    $where .= " AND (nama LIKE ? ESCAPE '=' OR email LIKE ? ESCAPE '=' OR judul_naskah LIKE ? ESCAPE '=' OR kode_inventaris LIKE ? ESCAPE '=' OR isi_pesan LIKE ? ESCAPE '=')";
    $term = '%' . strtr($q, ['=' => '==', '%' => '=%', '_' => '=_']) . '%';
    $params = array_fill(0, 5, $term); $types = 'sssss';
}
if ($filter !== '') { $where .= ' AND status = ?'; $params[] = $filter; $types .= 's'; }
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM tb_naskah_feedback' . $where);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute(); $total = (int)$stmt->get_result()->fetch_assoc()['total']; $stmt->close();
$pages = max(1, (int)ceil($total / $limit)); $pageNo = min($pageNo, $pages); $offset = ($pageNo - 1) * $limit;
$stmt = $conn->prepare("SELECT * FROM tb_naskah_feedback" . $where . " ORDER BY CASE WHEN status='unread' THEN 0 ELSE 1 END, created_at DESC, id_feedback DESC LIMIT ? OFFSET ?");
$params[] = $limit; $params[] = $offset; $types .= 'ii';
$stmt->bind_param($types, ...$params); $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
$unread = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_naskah_feedback WHERE status='unread'")->fetch_assoc()['total'];
$base = BASE_URL . '/admin/index.php?' . http_build_query(['page'=>'feedback-naskah', 'q'=>$q, 'status'=>$filter]);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/style-inbox.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/feedback-naskah.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<h1 class="h4 mb-3"><i class="fa-solid fa-comment-dots me-2"></i>Feedback Naskah</h1>
<div class="inbox-wrapper feedback-inbox" id="feedbackInbox" data-action="<?= kandagaFeedbackEscape(BASE_URL . '/admin/actions/feedback-naskah.php') ?>" data-thread="<?= kandagaFeedbackEscape(BASE_URL . '/admin/actions/feedback-thread.php') ?>" data-reply="<?= kandagaFeedbackEscape(BASE_URL . '/admin/actions/feedback-reply.php') ?>" data-detail="<?= kandagaFeedbackEscape(BASE_URL . '/detail.php?id=') ?>">
<div class="inbox-topbar"><form class="topbar-left feedback-filter" method="get" action="<?= BASE_URL ?>/admin/index.php"><input type="hidden" name="page" value="feedback-naskah"><label class="visually-hidden" for="feedbackSearch">Cari feedback</label><input id="feedbackSearch" class="search-input" name="q" value="<?= kandagaFeedbackEscape($q) ?>" placeholder="Cari nama, naskah, pesan..." maxlength="150"><label class="visually-hidden" for="feedbackStatus">Status pesan</label><select id="feedbackStatus" name="status" class="form-select form-select-sm"><option value="">Semua pesan</option><option value="unread" <?= $filter==='unread'?'selected':'' ?>>Belum dibaca</option><option value="read" <?= $filter==='read'?'selected':'' ?>>Sudah dibaca</option></select><button class="btn btn-primary btn-sm" type="submit" aria-label="Cari pesan"><i class="fa-solid fa-search"></i></button><a class="btn btn-light border btn-sm" href="<?= kandagaFeedbackEscape($base . '&p=' . $pageNo) ?>" aria-label="Muat ulang"><i class="fa-solid fa-rotate-right"></i></a></form><span id="feedbackUnread" class="badge bg-primary p-2"><?= $unread ?> belum dibaca</span></div>
<div class="inbox-body"><div class="inbox-sidebar">
<?php foreach ($rows as $row): $isUnread = $row['status'] === 'unread'; ?>
<button type="button" class="email-item feedback-item <?= $isUnread ? 'unread' : '' ?>" data-feedback='<?= kandagaFeedbackEscape(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)) ?>'>
<span class="email-row"><span class="email-left"><span class="mail-icon"><i class="<?= $isUnread ? 'fa-solid fa-envelope' : 'fa-regular fa-envelope-open' ?>"></i></span><span class="email-main"><span class="email-name <?= $isUnread ? 'bold' : '' ?>"><?= kandagaFeedbackEscape($row['nama']) ?></span><span class="email-subject"><?= kandagaFeedbackEscape($row['judul_naskah'] ?: 'Tanpa judul') ?></span><span class="email-preview"><?= kandagaFeedbackEscape($row['kode_inventaris'] ?: 'Tanpa kode inventaris') ?></span></span></span><span class="email-date"><?= kandagaFeedbackEscape(date('d M Y', strtotime($row['created_at']))) ?></span></span></button>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="p-4 text-muted">Tidak ada feedback yang sesuai.</div><?php endif; ?>
</div><section class="inbox-content" id="feedbackContent" aria-live="polite"><div class="empty-state"><i class="fa-regular fa-envelope-open"></i><h2 class="h5">Masukan Pembaca</h2><p>Pilih pesan untuk membaca isi.</p></div></section></div>
<div class="feedback-pagination p-3 d-flex align-items-center justify-content-between border-top"><small><?= $total ? $offset+1 : 0 ?>–<?= min($offset+$limit,$total) ?> dari <?= $total ?> pesan</small><nav aria-label="Halaman feedback"><a class="btn btn-light border btn-sm <?= $pageNo===1?'disabled':'' ?>" href="<?= kandagaFeedbackEscape($base . '&p=' . max(1,$pageNo-1)) ?>" <?= $pageNo===1?'aria-disabled="true" tabindex="-1"':'' ?>><i class="fa-solid fa-chevron-left me-1"></i>Sebelumnya</a><span class="mx-2 small"><?= $pageNo ?> / <?= $pages ?></span><a class="btn btn-light border btn-sm <?= $pageNo===$pages?'disabled':'' ?>" href="<?= kandagaFeedbackEscape($base . '&p=' . min($pages,$pageNo+1)) ?>" <?= $pageNo===$pages?'aria-disabled="true" tabindex="-1"':'' ?>>Berikutnya<i class="fa-solid fa-chevron-right ms-1"></i></a></nav></div>
</div><script src="<?= BASE_URL ?>/admin/assets/js/feedback-naskah.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
