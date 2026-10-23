<?php
declare(strict_types=1);

require_once __DIR__ . '/CostLedger.php';

final class PurchaseOrderApproval
{
    public static function ensureAuditTable(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS purchase_order_decision_audit (
            audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ppb_id VARCHAR(50) NOT NULL,
            decision VARCHAR(20) NOT NULL,
            reason VARCHAR(500) NULL,
            actor_id INT NOT NULL,
            occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_po_decision_audit (ppb_id, occurred_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function audit(PDO $db, string $ppbId, string $decision, int $actorId, string $reason = ''): void
    {
        $db->prepare('INSERT INTO purchase_order_decision_audit (ppb_id,decision,reason,actor_id) VALUES (:po,:decision,:reason,:actor)')
            ->execute([':po'=>$ppbId, ':decision'=>$decision, ':reason'=>$reason !== '' ? $reason : null, ':actor'=>$actorId]);
    }

    public static function cancel(PDO $db, string $ppbId, int $actorId, string $reason): array
    {
        if ($ppbId === '' || trim($reason) === '') throw new InvalidArgumentException('Nomor PO dan alasan pembatalan wajib diisi.');
        $query = $db->prepare('SELECT * FROM purchase_orders WHERE ppb_id = :id FOR UPDATE');
        $query->execute([':id' => $ppbId]);
        $order = $query->fetch(PDO::FETCH_ASSOC);
        if (!$order) throw new OutOfBoundsException('PO tidak ditemukan.');
        if ($order['status'] !== 'Approved') throw new DomainException('Hanya PO Approved yang belum diproses dapat dibatalkan.');
        $receipt = $db->prepare('SELECT COUNT(*) FROM report_goods_receipt_integrations WHERE ppb_id = :id AND reversed_at IS NULL');
        $receipt->execute([':id' => $ppbId]);
        if ((int)$receipt->fetchColumn() > 0) throw new DomainException('PO sudah memiliki penerimaan barang; reversal stok diperlukan dahulu.');
        $cost = $db->prepare("SELECT transaction_id FROM asset_cost_transactions WHERE source_type = 'PO_APPROVED' AND source_id = :id AND reversed_at IS NULL FOR UPDATE");
        $cost->execute([':id' => $ppbId]);
        $costId = $cost->fetchColumn();
        if ($costId === false) throw new DomainException('Biaya PO aktif tidak ditemukan; pembatalan dihentikan.');
        CostLedger::reverseTransaction($db, (int)$costId, $reason, ['id' => $actorId]);
        $update = $db->prepare("UPDATE purchase_orders SET status = 'Cancelled' WHERE ppb_id = :id AND status = 'Approved'");
        $update->execute([':id' => $ppbId]);
        if ($update->rowCount() !== 1) throw new DomainException('Status PO berubah bersamaan; pembatalan dihentikan.');
        self::audit($db, $ppbId, 'CANCELLED', $actorId, $reason);
        return ['ppbId' => $ppbId, 'status' => 'Cancelled', 'reversedCostId' => (int)$costId];
    }

    public static function decide(PDO $db, string $ppbId, int $actorId, string $decision, string $reason = ''): array
    {
        if ($ppbId === '' || !in_array($decision, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('Nomor PO dan keputusan tidak valid.');
        }
        if ($decision === 'reject' && trim($reason) === '') {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi.');
        }
        $query = $db->prepare('SELECT * FROM purchase_orders WHERE ppb_id = :id FOR UPDATE');
        $query->execute([':id' => $ppbId]);
        $order = $query->fetch(PDO::FETCH_ASSOC);
        if (!$order) throw new OutOfBoundsException('PO tidak ditemukan.');
        if ($order['status'] !== 'Submitted') throw new DomainException('PO sudah diproses; keputusan kedua ditolak.');
        $request = $db->prepare('SELECT status FROM purchase_requests WHERE spb_id = :id FOR UPDATE');
        $request->execute([':id' => $order['spb_id']]);
        if ($request->fetchColumn() !== 'Approved') throw new DomainException('SPB asal harus Approved sebelum PO diputuskan.');
        if ($decision === 'approve') {
            $amount = (float) $order['total_amount'];
            if ($amount <= 0) throw new DomainException('Nilai PO harus positif sebelum disetujui.');
            $existing = $db->prepare("SELECT transaction_id FROM asset_cost_transactions WHERE source_type = 'PO_APPROVED' AND source_id = :id LIMIT 1 FOR UPDATE");
            $existing->execute([':id' => $ppbId]);
            if ($existing->fetchColumn()) throw new DomainException('Biaya PO sudah tercatat; persetujuan ulang ditolak.');
            CostLedger::createTransaction($db, [
                'asset_id' => $order['asset_id'], 'wo_id' => $order['wo_id'],
                'transaction_date' => date('Y-m-d'), 'cost_category' => 'Sparepart',
                'description' => 'Pembelian part PO ' . $ppbId,
                'amount' => $amount, 'vendor_name' => $order['vendor'],
                'reference_no' => $ppbId, 'source_type' => 'PO_APPROVED',
                'source_id' => $ppbId, 'notes' => 'SPB ' . $order['spb_id'],
                'cost_code' => 'CST-PO-' . strtoupper(substr(hash('sha256', $ppbId), 0, 24)),
            ], ['id' => $actorId]);
            $status = 'Approved';
        } else {
            $status = 'Cancelled';
        }
        $update = $db->prepare("UPDATE purchase_orders SET status = :status WHERE ppb_id = :id AND status = 'Submitted'");
        $update->execute([':status' => $status, ':id' => $ppbId]);
        if ($update->rowCount() !== 1) throw new DomainException('Status PO berubah bersamaan; keputusan dibatalkan.');
        self::audit($db, $ppbId, strtoupper($decision === 'approve' ? 'APPROVED' : 'REJECTED'), $actorId, $reason);
        return ['ppbId' => $ppbId, 'status' => $status];
    }
}
