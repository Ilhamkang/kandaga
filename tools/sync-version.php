<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$release = file_get_contents($root . '/RELEASE.md');
if (!preg_match('/^# Kandaga v(\d+\.\d+\.\d+)\s*$/m', $release, $match)) { fwrite(STDERR, "Nomor versi RELEASE.md tidak valid.\n"); exit(1); }
$metadata = json_decode(file_get_contents($root . '/version.json'), true);
if (!is_array($metadata)) { fwrite(STDERR, "version.json tidak valid.\n"); exit(1); }
$metadata['version'] = $match[1];
if (file_put_contents($root . '/version.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n") === false) exit(1);
echo 'Versi tersinkron: ' . $match[1] . "\n";
