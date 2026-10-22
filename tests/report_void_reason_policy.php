<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/ReportVoidPolicy.php';

$passed = 0;
foreach ([null, '', '   ', 1, str_repeat('x', 501)] as $invalid) {
    try {
        ReportVoidPolicy::reason($invalid);
        throw new RuntimeException('Alasan tidak valid diterima.');
    } catch (InvalidArgumentException $e) {
        $passed++;
    }
}

if (ReportVoidPolicy::reason('  Koreksi stok  ') !== 'Koreksi stok') {
    throw new RuntimeException('Alasan valid tidak dinormalisasi.');
}
$passed++;
echo "Report void reason policy: {$passed}/6 passed.\n";
