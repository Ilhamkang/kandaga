<?php
/** Stage 1 manuscript structure helpers. All fields are optional and additive. */
function ksReady(mysqli $conn): bool {
    $r = $conn->query("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('katalog_structure','katalog_identifiers','katalog_location_history')");
    return $r && (int)$r->fetch_assoc()['n'] === 3;
}

function ksClean($value, int $max = 255): string {
    $value = trim((string)$value);
    if (mb_strlen($value, 'UTF-8') > $max) throw new InvalidArgumentException('Data struktur naskah terlalu panjang.');
    return $value;
}

function ksLoad(mysqli $conn, int $id): array {
    $out = ['structure'=>['record_type'=>'induk','parent_id'=>'','unit_label'=>'','unit_order'=>'','notes'=>''], 'identifiers'=>[], 'locations'=>[]];
    if (!ksReady($conn)) return $out;
    $s = $conn->prepare('SELECT parent_id,record_type,unit_label,unit_order,notes FROM katalog_structure WHERE id_naskah=?');
    $s->bind_param('i', $id); $s->execute();
    if ($row = $s->get_result()->fetch_assoc()) $out['structure'] = array_merge($out['structure'], $row);
    $s = $conn->prepare('SELECT scheme,identifier_value,source_label,is_primary FROM katalog_identifiers WHERE id_naskah=? ORDER BY is_primary DESC,id');
    $s->bind_param('i', $id); $s->execute(); $out['identifiers'] = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s = $conn->prepare('SELECT location_kind,place_name,district,province,country,period_label,certainty,source_text,sort_order FROM katalog_location_history WHERE id_naskah=? ORDER BY sort_order,id');
    $s->bind_param('i', $id); $s->execute(); $out['locations'] = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    return $out;
}

function ksPostRows(string $key): array {
    $rows = $_POST[$key] ?? [];
    if (!is_array($rows)) throw new InvalidArgumentException('Format data struktur tidak valid.');
    return $rows;
}

function ksSave(mysqli $conn, int $id): void {
    if (!ksReady($conn)) return; // Backward-compatible when migration has not run yet.
    $type = ksClean($_POST['record_type'] ?? 'induk', 16);
    if (!in_array($type, ['induk','unit'], true)) throw new InvalidArgumentException('Tipe record naskah tidak valid.');
    $parent = trim((string)($_POST['parent_id'] ?? ''));
    $parentId = $parent === '' ? null : filter_var($parent, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($parent !== '' && !$parentId) throw new InvalidArgumentException('Naskah induk tidak valid.');
    if ($parentId === $id) throw new InvalidArgumentException('Naskah tidak dapat menjadi induknya sendiri.');
    if ($parentId) { $q=$conn->prepare('SELECT id FROM katalog WHERE id=?'); $q->bind_param('i',$parentId); $q->execute(); if (!$q->get_result()->fetch_assoc()) throw new InvalidArgumentException('Naskah induk tidak ditemukan.'); }
    if ($type === 'unit' && !$parentId) throw new InvalidArgumentException('Unit naskah harus memiliki naskah induk.');
    $label = ksClean($_POST['unit_label'] ?? '', 100); $notes = ksClean($_POST['structure_notes'] ?? '', 50000);
    $orderRaw = trim((string)($_POST['unit_order'] ?? '')); $order = $orderRaw === '' ? null : filter_var($orderRaw, FILTER_VALIDATE_INT);
    if ($orderRaw !== '' && $order === false) throw new InvalidArgumentException('Urutan unit tidak valid.');
    $d=$conn->prepare('DELETE FROM katalog_structure WHERE id_naskah=?'); $d->bind_param('i',$id); $d->execute();
    $s=$conn->prepare('INSERT INTO katalog_structure(id_naskah,parent_id,record_type,unit_label,unit_order,notes) VALUES(?,?,?,?,?,?)');
    $s->bind_param('iissis', $id, $parentId, $type, $label, $order, $notes); $s->execute();

    $d=$conn->prepare('DELETE FROM katalog_identifiers WHERE id_naskah=?'); $d->bind_param('i',$id); $d->execute();
    foreach (ksPostRows('identifier') as $row) {
        if (!is_array($row)) continue; $scheme=ksClean($row['scheme']??'',48); $value=ksClean($row['value']??'',255); $source=ksClean($row['source']??'',255);
        if ($scheme==='' || $value==='') continue; $primary=!empty($row['primary'])?1:0;
        $s=$conn->prepare('INSERT IGNORE INTO katalog_identifiers(id_naskah,scheme,identifier_value,source_label,is_primary) VALUES(?,?,?,?,?)'); $s->bind_param('isssi',$id,$scheme,$value,$source,$primary); $s->execute();
    }
    $d=$conn->prepare('DELETE FROM katalog_location_history WHERE id_naskah=?'); $d->bind_param('i',$id); $d->execute();
    foreach (ksPostRows('location_history') as $i=>$row) {
        if (!is_array($row)) continue; $kind=ksClean($row['kind']??'',32); $place=ksClean($row['place']??'',255); $district=ksClean($row['district']??'',255); $province=ksClean($row['province']??'',255); $country=ksClean($row['country']??'',120); $period=ksClean($row['period']??'',120); $certainty=ksClean($row['certainty']??'belum_diketahui',24); $source=ksClean($row['source']??'',50000);
        if ($place==='' && $district==='' && $province==='' && $country==='') continue; if (!in_array($certainty,['pasti','kemungkinan','dugaan','belum_diketahui'],true)) $certainty='belum_diketahui';
        $order=(int)$i; $s=$conn->prepare('INSERT INTO katalog_location_history(id_naskah,location_kind,place_name,district,province,country,period_label,certainty,source_text,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)'); $s->bind_param('issssssssi',$id,$kind,$place,$district,$province,$country,$period,$certainty,$source,$order); $s->execute();
    }
}

function ksHtml(array $data): string {
    $s=$data['structure']; $label=htmlspecialchars((string)($s['unit_label']??''),ENT_QUOTES,'UTF-8'); $parent=(int)($s['parent_id']??0); $type=htmlspecialchars((string)($s['record_type']??'induk'),ENT_QUOTES,'UTF-8');
    $out='<div class="small text-muted mb-2"><strong>Struktur:</strong> '.($type==='unit'?'Unit naskah':'Naskah induk'); if($parent) $out.=' · bagian dari ID '.$parent; if($label!=='')$out.=' · '.$label; return $out.'</div>';
}
