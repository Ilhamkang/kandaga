<?php
// Only catalogue writes: auth, posts, profile changes and deletions are excluded.
function kandagaCatalogLogCondition($alias = 'ul') {
    return "$alias.kategori_aksi IN ('input', 'edit') AND ($alias.aksi LIKE 'Input naskah:%' OR $alias.aksi LIKE 'Edit naskah:%' OR $alias.aksi LIKE 'Menambah naskah baru:%' OR $alias.aksi LIKE 'Menyunting detail informasi naskah:%' OR $alias.aksi LIKE 'Menyunting katalog ID:%')";
}
function kandagaContributionYears($conn) {
    $years = [(int)date('Y')];
    $result = $conn->query('SELECT DISTINCT YEAR(ul.waktu) AS year FROM user_logs ul WHERE ' . kandagaCatalogLogCondition() . ' ORDER BY year DESC');
    while ($row = $result->fetch_assoc()) if ((int)$row['year'] >= 2000 && (int)$row['year'] <= (int)date('Y')) $years[] = (int)$row['year'];
    $years = array_values(array_unique($years)); rsort($years); return $years;
}
function kandagaContributionData($conn, $year) {
    $from = "$year-01-01 00:00:00"; $until = ($year + 1) . '-01-01 00:00:00';
    $sql = "SELECT DATE(ul.waktu) AS day, ul.username, COALESCE(NULLIF(u.nama, ''), ul.username) AS name, ul.kategori_aksi AS kind, COUNT(*) AS total FROM user_logs ul LEFT JOIN users u ON u.username = ul.username WHERE " . kandagaCatalogLogCondition() . " AND ul.waktu >= ? AND ul.waktu < ? GROUP BY DATE(ul.waktu), ul.username, u.nama, ul.kategori_aksi ORDER BY day, ul.username";
    $stmt = $conn->prepare($sql); $stmt->bind_param('ss', $from, $until); $stmt->execute();
    $result = $stmt->get_result(); $days = []; $contributors = []; $total = 0;
    while ($row = $result->fetch_assoc()) {
        $day = $row['day']; $key = $row['username']; $kind = $row['kind']; $count = (int)$row['total']; $month = (int)substr($day, 5, 2) - 1;
        if (!isset($contributors[$key])) $contributors[$key] = ['name' => $row['name'], 'input' => array_fill(0, 12, 0), 'edit' => array_fill(0, 12, 0)];
        $contributors[$key][$kind][$month] += $count;
        if (!isset($days[$day])) $days[$day] = ['total' => 0, 'people' => []];
        if (!isset($days[$day]['people'][$key])) $days[$day]['people'][$key] = ['name' => $row['name'], 'input' => 0, 'edit' => 0];
        $days[$day]['people'][$key][$kind] += $count; $days[$day]['total'] += $count; $total += $count;
    }
    $stmt->close(); return ['days' => $days, 'contributors' => array_values($contributors), 'total' => $total];
}
