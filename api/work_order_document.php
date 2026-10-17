<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json_response(405, ['status' => 'error', 'message' => 'Dokumen hanya dapat dibaca.']);
}
AuthMiddleware::checkAccess('wo', 'R');

$db = Database::getInstance();
$woId = trim((string) ($_GET['id'] ?? ''));
$alias = strtoupper(trim((string) ($_GET['doc_alias'] ?? 'WO')));
if (!in_array($alias, ['WO', 'JO'], true)) {
    api_json_response(422, ['status' => 'error', 'message' => 'Tampilan dokumen harus WO atau JO.']);
}
api_require_work_order_access($db, $woId);

$stmt = $db->prepare('SELECT w.*, a.asset_code, a.make_model AS asset_name FROM work_orders w INNER JOIN assets a ON a.asset_id = w.asset_id WHERE w.wo_id = :id LIMIT 1');
$stmt->execute([':id' => $woId]);
$wo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$wo) api_json_response(404, ['status' => 'error', 'message' => 'Work Order tidak ditemukan.']);

$esc = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$title = $alias === 'JO' ? 'Job Order' : 'Work Order';
$photo = (string) ($wo['before_photo_url'] ?? '');
$safePhoto = preg_match('~^uploads/work_orders/[A-Za-z0-9_-]+\.(?:jpg|png|webp)$~i', $photo) ? '../' . $photo : null;
$fields = [
    'ID database (tetap)' => $wo['wo_id'],
    'Unit' => $wo['asset_id'] . ' / ' . ($wo['asset_code'] ?? ''),
    'Nama alat' => $wo['asset_name'] ?? '',
    'Status' => $wo['status'],
    'Prioritas' => $wo['priority'],
    'Jenis pemeliharaan' => $wo['maintenance_type'],
    'Nomor lambung' => $wo['hull_number'],
    'Jenis alat' => $wo['equipment_type'],
    'HM/KM saat laporan' => $wo['current_hm_km'],
    'Waktu trouble' => $wo['trouble_at'],
    'Lokasi kejadian' => $wo['raw_location'],
    'PIC mekanik' => $wo['assigned_mechanic'],
    'Operasi bersyarat' => !empty($wo['is_forced_operation']) ? 'Ya' : 'Tidak',
    'Mitigasi' => $wo['mitigation_action'],
    'Estimasi selesai' => $wo['estimated_completion_at'],
    'Keluhan/kerusakan' => $wo['issue_description'],
    'Penyebab' => $wo['root_cause'],
    'Tindakan' => $wo['repair_action'],
    'Dilaporkan' => $wo['reported_at'],
    'Selesai' => $wo['closed_at'],
];
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title><?= $esc($title . ' ' . $woId) ?></title>
<style>body{font:14px Arial,sans-serif;max-width:900px;margin:32px auto;color:#172033}h1{margin-bottom:4px}small{color:#536278}table{border-collapse:collapse;width:100%;margin-top:24px}th,td{border:1px solid #cbd5e1;padding:10px;text-align:left;vertical-align:top;white-space:pre-wrap}th{width:220px;background:#f1f5f9}img{max-width:420px;max-height:300px;margin-top:12px}.toolbar{text-align:right}@media print{.toolbar{display:none}body{margin:0}}</style></head><body>
<div class="toolbar"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
<h1><?= $esc($title) ?></h1><small>Alias <?= $esc($alias) ?> dari record WO yang sama. ID database: <?= $esc($woId) ?></small>
<table><?php foreach ($fields as $label => $value): ?><tr><th><?= $esc($label) ?></th><td><?= $esc($value === null || $value === '' ? '-' : $value) ?></td></tr><?php endforeach; ?></table>
<?php if ($safePhoto !== null): ?><h2>Foto sebelum perbaikan</h2><img src="<?= $esc($safePhoto) ?>" alt="Foto dokumentasi WO"><?php endif; ?>
</body></html>
