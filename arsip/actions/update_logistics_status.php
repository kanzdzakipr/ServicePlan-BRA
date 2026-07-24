<?php
require __DIR__ . '/../app/bootstrap.php';

require_login();
require_permission('logistics.edit');
verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$newStatus = trim($_POST['new_status'] ?? '');
$expectedDate = trim($_POST['expected_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (!$id) {
    flash('error', 'Data pemesanan tidak valid.');
    redirect('../' . page_url('logistics'));
}

$allowedTransitions = [
    'Dipesan' => ['Diproses Vendor', 'Tertunda', 'Dibatalkan'],
    'Diproses Vendor' => ['Dalam Pengiriman', 'Tertunda', 'Dibatalkan'],
    'Dalam Pengiriman' => ['Tiba', 'Tertunda', 'Dibatalkan'],
    'Tertunda' => ['Dipesan', 'Diproses Vendor', 'Dalam Pengiriman', 'Dibatalkan'],
    'Tiba' => [],
    'Dibatalkan' => [],
];

$stmt = $pdo->prepare('SELECT * FROM logistics_orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Data pemesanan tidak ditemukan.');
    redirect('../' . page_url('logistics'));
}

$oldStatus = $order['status'];
$allowedNext = $allowedTransitions[$oldStatus] ?? [];

if (!in_array($newStatus, $allowedNext, true)) {
    flash('error', 'Perubahan status dari ' . $oldStatus . ' ke ' . ($newStatus ?: '(kosong)') . ' tidak diizinkan.');
    redirect('../' . page_url('logistics', ['edit_status' => $id]));
}

if ($expectedDate !== '') {
    $date = DateTime::createFromFormat('Y-m-d', $expectedDate);
    if (!$date || $date->format('Y-m-d') !== $expectedDate) {
        flash('error', 'Format estimasi tanggal tiba tidak valid.');
        redirect('../' . page_url('logistics', ['edit_status' => $id]));
    }
} else {
    $expectedDate = null;
}

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare(
        'UPDATE logistics_orders
         SET status = ?, expected_date = ?
         WHERE id = ?'
    );
    $update->execute([$newStatus, $expectedDate, $id]);

    $historyNotes = $notes !== '' ? $notes : 'Status barang diperbarui';
    add_history($pdo, 'logistics_order', $id, $oldStatus, $newStatus, $historyNotes);

    audit(
        $pdo,
        'UPDATE_STATUS',
        'logistics_order',
        $id,
        'Status ' . ($order['request_no'] ?: '#' . $id) . ' diubah dari ' . $oldStatus . ' menjadi ' . $newStatus
    );

    if (!empty($order['requested_by'])) {
        notify(
            $pdo,
            (int)$order['requested_by'],
            'logistics_status',
            'Status barang diperbarui',
            ($order['request_no'] ?: 'Permintaan #' . $id) . ' menjadi ' . $newStatus,
            page_url('logistics')
        );
    }

    $pdo->commit();
    flash('success', 'Status barang berhasil diubah dari ' . $oldStatus . ' menjadi ' . $newStatus . '.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Status barang gagal diperbarui. Silakan coba kembali.');
}

redirect('../' . page_url('logistics'));
