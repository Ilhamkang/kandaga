<?php
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/log-tracker.php';
function kandagaSanitizePostHtml($html) {
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><a><img><figure><figcaption><hr><table><thead><tbody><tr><th><td>';
    $html = strip_tags((string)$html, $allowed);
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="kandaga-post-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $dom->getElementById('kandaga-post-root');
    if (!$root) $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) return htmlspecialchars((string)$html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $walk = function($node) use (&$walk) {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($node->nodeName);
            $attrs = [];
            foreach (iterator_to_array($node->attributes ?? []) as $attr) $attrs[] = $attr->nodeName;
            foreach ($attrs as $name) {
                $lower = strtolower($name);
                if (str_starts_with($lower, 'on') || in_array($lower, ['style','srcset','formaction'], true)) $node->removeAttribute($name);
            }
            if ($tag === 'a') {
                $href = trim((string)$node->getAttribute('href'));
                if ($href !== '' && !preg_match('~^(?:https?://|mailto:)~i', $href)) $node->removeAttribute('href');
                else { $node->setAttribute('rel', 'nofollow noopener'); $node->setAttribute('target', '_blank'); }
            }
            if ($tag === 'img') {
                $src = trim((string)$node->getAttribute('src'));
                if ($src === '' || preg_match('~^(?:javascript|data|vbscript):~i', $src)) $node->parentNode->removeChild($node); else $node->setAttribute('loading','lazy');
            }
        }
        foreach (iterator_to_array($node->childNodes ?? []) as $child) if ($child->parentNode === $node) $walk($child);
    };
    $walk($root);
    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) $out .= $dom->saveHTML($child);
    return $out;
}
function kandagaSavePost($editing) {
    global $conn;
    kandagaPost(true); $new = null; $started = false; $directory = ROOT_PATH . '/assets/images/post';
    try {
        $id = (int)($_POST['id_kabar'] ?? 0);
        if ($editing && $id < 1) throw new InvalidArgumentException('ID kabar tidak valid.');
        $judul = trim($_POST['judul'] ?? ''); $konten = kandagaSanitizePostHtml($_POST['konten'] ?? '');
        $kategori = $_POST['kategori'] ?? 'berita'; $status = $_POST['status'] ?? 'draft';
        if ($judul === '' || trim($konten) === '' || mb_strlen($judul) > 255) throw new InvalidArgumentException('Judul dan konten wajib diisi; judul maksimal 255 karakter.');
        if (!in_array($kategori, ['berita','kegiatan','pengumuman','artikel'], true) || !in_array($status, ['draft','published'], true)) throw new InvalidArgumentException('Kategori atau status kabar tidak valid.');
        $slug = trim($_POST['slug'] ?? '');
        if ($slug === '') $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul), '-'));
        if ($slug === '') $slug = 'kabar-' . bin2hex(random_bytes(4));
        if (strlen($slug) > 255 || !preg_match('/^[a-zA-Z0-9-]+$/', $slug)) throw new InvalidArgumentException('Slug kabar tidak valid.');
        $conn->begin_transaction(); $started = true;
        $old = null;
        if ($editing) {
            $stmt = $conn->prepare('SELECT thumbnail FROM tb_kabar WHERE id_kabar = ? FOR UPDATE');
            $stmt->bind_param('i',$id); $stmt->execute(); $old = $stmt->get_result()->fetch_assoc();
            if (!$old) throw new InvalidArgumentException('Kabar tidak ditemukan.');
        }
        // Slug baru mengikuti perilaku CMS modern: benturan otomatis diberi akhiran.
        // Saat mengedit slug yang sama tetap dipertahankan, tetapi benturan eksplisit ditolak.
        $baseSlug = $slug; $suffix = 2;
        do {
            $check = $conn->prepare('SELECT id_kabar FROM tb_kabar WHERE slug = ? AND id_kabar <> ? LIMIT 1');
            $check->bind_param('si',$slug,$id); $check->execute();
            $collision = (bool)$check->get_result()->fetch_assoc(); $check->close();
            if (!$collision) break;
            if ($editing) throw new InvalidArgumentException('Slug sudah dipakai kabar lain. Ubah judul atau slug.');
            $slug = substr($baseSlug, 0, 255 - strlen((string)$suffix) - 1) . '-' . $suffix++;
        } while ($suffix < 10000);
        $new = kandagaUpload('thumbnail',$directory,'kabar'); $thumbnail = $new ?? ($old['thumbnail'] ?? '');
        if ($editing) {
            $stmt=$conn->prepare('UPDATE tb_kabar SET judul=?, slug=?, konten=?, thumbnail=?, kategori=?, status=? WHERE id_kabar=?');
            $stmt->bind_param('ssssssi',$judul,$slug,$konten,$thumbnail,$kategori,$status,$id);
        } else {
            $author=(int)$_SESSION['admin_id'];
            $stmt=$conn->prepare('INSERT INTO tb_kabar (judul,slug,konten,thumbnail,kategori,penulis_id,status) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('sssssis',$judul,$slug,$konten,$thumbnail,$kategori,$author,$status);
        }
        $stmt->execute(); if (!$editing) $id=$conn->insert_id;
        logAktivitas($conn,$editing?'edit':'input',($editing?'Edit kabar: ':'Input kabar: ').$judul,$id);
        $conn->commit();$started=false;
        kandagaJson(true, $editing ? 'Kabar berhasil diperbarui.' : 'Kabar berhasil disimpan.', ['id'=>$id, 'slug'=>$slug]);
    } catch(Throwable $error) { if($started)$conn->rollback();if($new)kandagaRemoveUpload($directory,$new);kandagaFailure($error); }
}
