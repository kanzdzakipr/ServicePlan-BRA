<?php
$rows = $pdo->query(
    'SELECT l.*, u.unit_code
     FROM logistics_orders l
     LEFT JOIN units u ON u.id = l.unit_id
     ORDER BY l.id DESC'
)->fetchAll();

$units = $pdo->query('SELECT id, unit_code FROM units ORDER BY unit_code')->fetchAll();

$editRow = null;
$editId = filter_input(INPUT_GET, 'edit_status', FILTER_VALIDATE_INT);
if ($editId && can('logistics.edit')) {
    $stmt = $pdo->prepare(
        'SELECT l.*, u.unit_code
         FROM logistics_orders l
         LEFT JOIN units u ON u.id = l.unit_id
         WHERE l.id = ?'
    );
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$nextStatusMap = [
    'Dipesan' => ['Diproses Vendor', 'Tertunda', 'Dibatalkan'],
    'Diproses Vendor' => ['Dalam Pengiriman', 'Tertunda', 'Dibatalkan'],
    'Dalam Pengiriman' => ['Tiba', 'Tertunda', 'Dibatalkan'],
    'Tertunda' => ['Dipesan', 'Diproses Vendor', 'Dalam Pengiriman', 'Dibatalkan'],
    'Tiba' => [],
    'Dibatalkan' => [],
];
?>

<?php if (can('logistics.request') || can('logistics.edit')): ?>
<div class="card panel">
    <h3>Permintaan Spare Part / Barang Logistik</h3>
    <form class="form-grid compact" method="post" action="actions/save_logistics.php">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

        <label>Unit
            <select name="unit_id">
                <option value="">Umum</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"><?= e($u['unit_code']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Nama Barang
            <input name="item_name" required>
        </label>

        <label>Qty
            <input type="number" name="quantity" min="1" value="1" required>
        </label>

        <label>Vendor
            <input name="vendor">
        </label>

        <label>Tanggal Permintaan
            <input type="date" name="order_date" value="<?= date('Y-m-d') ?>">
        </label>

        <label>Estimasi Tiba
            <input type="date" name="expected_date">
        </label>

        <label>Nilai
            <input type="number" name="total_cost" min="0" step="1">
        </label>

        <label>Ajukan Approval
            <select name="submit_approval">
                <option value="1">Ya</option>
                <option value="0">Simpan Draft</option>
            </select>
        </label>

        <button class="btn primary">Simpan Permintaan</button>
    </form>
</div>
<?php endif; ?>

<?php if ($editRow && can('logistics.edit')): ?>
<div class="card panel" id="form-edit-status" style="border:2px solid #2563eb;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
        <div>
            <h3 style="margin-bottom:6px;">Edit Status Barang</h3>
            <p style="margin:0;color:#64748b;font-size:13px;">
                <?= e($editRow['request_no'] ?: 'Tanpa nomor') ?> ·
                <?= e($editRow['unit_code'] ?: 'Umum') ?> ·
                <?= e($editRow['item_name']) ?>
            </p>
        </div>
        <a class="btn" href="<?= e(page_url('logistics')) ?>">Tutup</a>
    </div>

    <?php $availableStatuses = $nextStatusMap[$editRow['status']] ?? []; ?>

    <?php if (!$availableStatuses): ?>
        <div class="alert alert-warning" style="margin-top:16px;">
            Status <strong><?= e($editRow['status']) ?></strong> sudah merupakan status akhir dan tidak dapat dilanjutkan.
        </div>
    <?php else: ?>
        <form class="form-grid compact" method="post" action="actions/update_logistics_status.php" style="margin-top:16px;">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">

            <label>Status Saat Ini
                <input value="<?= e($editRow['status']) ?>" readonly>
            </label>

            <label>Status Baru
                <select name="new_status" required>
                    <option value="">-- Pilih status berikutnya --</option>
                    <?php foreach ($availableStatuses as $status): ?>
                        <option value="<?= e($status) ?>"><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Estimasi Tiba
                <input type="date" name="expected_date" value="<?= e($editRow['expected_date']) ?>">
            </label>

            <label>Catatan Perubahan
                <input name="notes" maxlength="255" placeholder="Contoh: barang sudah dikirim vendor">
            </label>

            <button class="btn primary">Simpan Perubahan Status</button>
        </form>

        <p style="margin:12px 0 0;color:#64748b;font-size:12px;">
            Alur normal: Dipesan → Diproses Vendor → Dalam Pengiriman → Tiba.
            Status Tertunda dan Dibatalkan dipakai bila diperlukan.
        </p>
    <?php endif; ?>
</div>

<script>
window.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-edit-status');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
<?php endif; ?>

<div class="card panel">
    <h3>Status Pemesanan dan Approval</h3>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>No Permintaan</th>
                    <th>Tanggal</th>
                    <th>Unit</th>
                    <th>Barang</th>
                    <th>Qty</th>
                    <th>Vendor</th>
                    <th>Status Barang</th>
                    <th>Approval</th>
                    <th>Nilai</th>
                    <?php if (can('logistics.edit')): ?><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['request_no']) ?></td>
                        <td><?= e($r['order_date']) ?></td>
                        <td><?= e($r['unit_code'] ?? 'Umum') ?></td>
                        <td><?= e($r['item_name']) ?></td>
                        <td><?= (int)$r['quantity'] ?></td>
                        <td><?= e($r['vendor']) ?></td>
                        <td><span class="status <?= status_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                        <td><span class="status <?= status_class($r['approval_status']) ?>"><?= e($r['approval_status']) ?></span></td>
                        <td><?= rupiah($r['total_cost']) ?></td>
                        <?php if (can('logistics.edit')): ?>
                            <td>
                                <?php if (in_array($r['status'], ['Tiba', 'Dibatalkan'], true)): ?>
                                    <span style="color:#64748b;font-size:12px;">Selesai</span>
                                <?php else: ?>
                                    <a class="btn" style="padding:7px 10px;white-space:nowrap;" href="<?= e(page_url('logistics', ['edit_status' => (int)$r['id']])) ?>#form-edit-status">
                                        Edit Status
                                    </a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
