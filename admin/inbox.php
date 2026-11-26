<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/includes/request.php';
require_once __DIR__ . '/../includes/inbox-correspondence.php';
kandagaAuthorize(true);
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 150, 'UTF-8');
$filter = (string)($_GET['status'] ?? '');
if (!in_array($filter, ['read', 'unread'], true)) $filter = '';
$pageNo = max(1, (int)($_GET['p'] ?? 1)); $limit = 25;
$where = ' WHERE 1=1'; $types = ''; $params = [];
if ($q !== '') {
    $where .= " AND (nama LIKE ? ESCAPE '=' OR email LIKE ? ESCAPE '=' OR subjek LIKE ? ESCAPE '=' OR isi_pesan LIKE ? ESCAPE '=')";
    $term = '%' . strtr($q, ['=' => '==', '%' => '=%', '_' => '=_']) . '%';
    $params = array_fill(0, 4, $term); $types = 'ssss';
}
if ($filter !== '') { $where .= ' AND status=?'; $params[] = $filter; $types .= 's'; }
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM tb_inbox' . $where);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute(); $total = (int)$stmt->get_result()->fetch_assoc()['total']; $stmt->close();
$pages = max(1, (int)ceil($total / $limit)); $pageNo = min($pageNo, $pages); $offset = ($pageNo - 1) * $limit;
$params[] = $limit; $params[] = $offset; $types .= 'ii';
$stmt = $conn->prepare("SELECT * FROM tb_inbox" . $where . " ORDER BY CASE WHEN status='unread' THEN 0 ELSE 1 END, created_at DESC, id_inbox DESC LIMIT ? OFFSET ?");
$stmt->bind_param($types, ...$params); $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
$unread = (int)$conn->query("SELECT COUNT(*) AS total FROM tb_inbox WHERE status='unread'")->fetch_assoc()['total'];
$base = BASE_URL . '/admin/index.php?' . http_build_query(['page'=>'inbox', 'q'=>$q, 'status'=>$filter]);
?>
<link rel="stylesheet" href="assets/css/style-inbox.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
<div class="inbox-wrapper" id="contactInbox" data-thread="<?= htmlspecialchars(BASE_URL . '/admin/actions/inbox-thread.php', ENT_QUOTES, 'UTF-8') ?>" data-reply="<?= htmlspecialchars(BASE_URL . '/admin/actions/inbox-reply.php', ENT_QUOTES, 'UTF-8') ?>" data-status-action="<?= htmlspecialchars(BASE_URL . '/admin/actions/tandai_baca.php', ENT_QUOTES, 'UTF-8') ?>" data-delete-action="<?= htmlspecialchars(BASE_URL . '/admin/actions/proses-hapus-pesan.php', ENT_QUOTES, 'UTF-8') ?>">
    <div class="inbox-topbar"><form class="topbar-left inbox-filter" method="get" action="<?= BASE_URL ?>/admin/index.php"><input type="hidden" name="page" value="inbox"><input id="searchInbox" class="search-input" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari pesan..." maxlength="150"><select name="status" class="form-control form-control-sm"><option value="">Semua pesan</option><option value="unread" <?= $filter==='unread'?'selected':'' ?>>Belum dibaca</option><option value="read" <?= $filter==='read'?'selected':'' ?>>Sudah dibaca</option></select><button type="submit" class="btn btn-primary btn-sm" aria-label="Cari pesan"><i class="fa-solid fa-search"></i></button><a class="btn btn-light border btn-sm" href="<?= htmlspecialchars($base . '&p=' . $pageNo, ENT_QUOTES, 'UTF-8') ?>" aria-label="Muat ulang"><i class="fa-solid fa-rotate-right"></i></a></form><span id="badge-area"><?= $unread > 0 ? '<span class="badge badge-primary p-2">' . $unread . ' belum dibaca</span>' : '<small class="text-muted">Semua dibaca</small>' ?></span></div>
    <div class="inbox-body"><div class="inbox-sidebar">
        <?php foreach ($rows as $row): $isUnread = $row['status'] === 'unread'; $data = json_encode(['id'=>(int)$row['id_inbox'],'nama'=>$row['nama'],'email'=>$row['email'],'subjek'=>$row['subjek'],'pesan'=>$row['isi_pesan'],'status'=>$row['status'],'created_at'=>$row['created_at']], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>
        <button type="button" class="email-item <?= $isUnread ? 'unread' : '' ?>" id="email-<?= (int)$row['id_inbox'] ?>" data-json='<?= htmlspecialchars($data, ENT_QUOTES, 'UTF-8') ?>'><span class="email-row"><span class="email-left"><span class="mail-icon"><?= $isUnread ? '<i class="fa-solid fa-envelope text-primary"></i>' : '<i class="fa-regular fa-envelope-open text-secondary"></i>' ?></span><span class="email-main"><span class="email-name <?= $isUnread ? 'bold' : '' ?>"><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></span><span class="email-subject"><?= htmlspecialchars($row['subjek'], ENT_QUOTES, 'UTF-8') ?></span></span></span><span class="email-date"><?= htmlspecialchars(date('d M H:i', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></span></button>
        <?php endforeach; ?>
        <?php if (!$rows): ?><div class="p-5 text-center text-muted">Tidak ada pesan yang sesuai.</div><?php endif; ?>
    </div><section class="inbox-content" id="email-content" aria-live="polite"><div class="empty-state"><i class="fa-regular fa-envelope-open"></i><h5>Inbox Kandaga</h5><p>Pilih pesan untuk membaca isi dan membalas.</p></div></section></div>
    <div class="feedback-pagination p-3 d-flex align-items-center justify-content-between border-top"><small><?= $total ? $offset+1 : 0 ?>–<?= min($offset+$limit,$total) ?> dari <?= $total ?> pesan</small><nav aria-label="Halaman inbox"><a class="btn btn-light border btn-sm <?= $pageNo===1?'disabled':'' ?>" href="<?= htmlspecialchars($base . '&p=' . max(1,$pageNo-1), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-chevron-left mr-1"></i>Sebelumnya</a><span class="mx-2 small"><?= $pageNo ?> / <?= $pages ?></span><a class="btn btn-light border btn-sm <?= $pageNo===$pages?'disabled':'' ?>" href="<?= htmlspecialchars($base . '&p=' . min($pages,$pageNo+1), ENT_QUOTES, 'UTF-8') ?>">Berikutnya<i class="fa-solid fa-chevron-right ml-1"></i></a></nav></div>
</div>
<script src="<?= BASE_URL ?>/admin/assets/js/tampil-inbox.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
