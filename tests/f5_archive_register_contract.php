<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$api = (string) file_get_contents($root . '/api/archive.php');
$dashboard = (string) file_get_contents($root . '/scripts/dashboard.js');
$view = (string) file_get_contents($root . '/dashboard.view.php');

$passed = 0;
function archiveContract(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $passed++;
    echo "PASS: {$message}\n";
}

archiveContract(str_contains($api, "'details'] = archiveItemSnapshot"), 'Archive API menyertakan snapshot objek');
archiveContract(str_contains($api, 'a.asset_id AS id'), 'Snapshot aset membaca master assets secara langsung');
archiveContract(str_contains($dashboard, 'window.archiveRecords'), 'UI memiliki register arsip terpisah dari daftar aktif');
archiveContract(str_contains($dashboard, 'window.loadArchiveRecords'), 'UI memuat ulang register arsip dari API');
archiveContract(str_contains($dashboard, 'record.details?.id || record.item_id'), 'Renderer aset memakai snapshot API dan fallback ID arsip');
archiveContract(str_contains($dashboard, 'window.location.reload();'), 'Restore memuat ulang register aktif dari database');
archiveContract(str_contains($view, 'window.archiveRecords = archiveDataRes.data'), 'Bootstrap dashboard menyimpan payload Archive');

echo "All {$passed} F5 archive register contract checks passed.\n";
