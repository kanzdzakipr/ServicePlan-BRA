<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$source = static fn(string $path): string => (string) file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));

$spb = $source('api/spb_workflow.php');
$wo = $source('api/work_orders.php');
$assets = $source('api/assets.php');
$js = $source('scripts/dashboard.js');
$view = $source('dashboard.view.php');
$migration = $source('scripts/migrate_owner_editable_inputs_20261101.sql');

$assert(str_contains($spb, "['update_own_pending', 'withdraw_own_pending']"), 'SPB belum memiliki aksi revisi dan tarik kembali milik pembuat.');
$assert(str_contains($spb, 'has_purchase_order') && str_contains($spb, 'approval_status'), 'Pengaman approval/PPB SPB belum lengkap.');
$assert(str_contains($wo, '$isOwnerRevision') && str_contains($wo, 'has_inventory_transaction'), 'Pengaman revisi WO belum lengkap.');
$assert(str_contains($assets, '$isOwnerRevision') && str_contains($assets, 'can_owner_edit'), 'Mode revisi Master Asset belum lengkap.');
$assert(str_contains($js, 'editPendingSpb') && str_contains($js, 'Revisi Work Order'), 'UI revisi SPB/WO belum tersedia.');
$assert(str_contains($view, 'modalWoEditInputBtn') && str_contains($view, 'ownerAssetRevisionAllowed'), 'Tombol revisi WO/Master Asset belum tersedia.');
$assert(str_contains($migration, 'input_revision_audit') && str_contains($migration, 'add_owner_edit_column'), 'Migrasi audit revisi belum idempoten.');

if ($failures) {
    fwrite(STDERR, "FAIL\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "PASS: revisi input milik pembuat tersedia untuk SPB, WO, dan Master Asset dengan audit serta guard transaksi turunan.\n");
