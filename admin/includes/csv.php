<?php
function kandagaCsvRows($path) {
    if (!is_uploaded_file($path)) throw new InvalidArgumentException('Upload CSV tidak valid.');
    if (filesize($path) > 10485760) throw new InvalidArgumentException('CSV maksimal 10 MB.');
    $handle = fopen($path, 'r'); $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if ($row === [null]) continue;
        if (!$rows) $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
        $rows[] = $row;
    }
    fclose($handle);
    if (count($rows) < 2 || !in_array('Judul', $rows[0], true)) throw new InvalidArgumentException('CSV harus memuat header Judul dan setidaknya satu baris data.');
    if (count(array_unique($rows[0])) !== count($rows[0])) throw new InvalidArgumentException('Header CSV tidak boleh berulang.');
    return $rows;
}
