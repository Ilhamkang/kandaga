<?php
function kandagaVersion() {
    static $version;
    if ($version !== null) return $version;
    $release = @file_get_contents(dirname(__DIR__) . '/RELEASE.md');
    if (is_string($release) && preg_match('/^# Kandaga v(\d+\.\d+\.\d+)\s*$/m', $release, $match)) return $version = $match[1];
    $metadata = json_decode((string)@file_get_contents(dirname(__DIR__) . '/version.json'), true);
    return $version = (string)($metadata['version'] ?? 'Tidak tersedia');
}
