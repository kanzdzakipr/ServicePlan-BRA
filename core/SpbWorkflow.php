<?php
declare(strict_types=1);

final class SpbWorkflow
{
    public static function ensureVoidAudit(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS spb_void_audit (
            spb_id VARCHAR(50) PRIMARY KEY,
            reason VARCHAR(500) NOT NULL,
            snapshot_json LONGTEXT NOT NULL,
            voided_by INT NOT NULL,
            voided_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function voidOperational(PDO $db, string $spbId, int $actorId, string $reason, callable $authorizeAsset): array
    {
        if ($spbId === '' || $actorId < 1 || $reason === '' || mb_strlen($reason, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Nomor SPB dan alasan void (maksimal 500 karakter) wajib diisi.');
        }
        $stmt = $db->prepare('SELECT * FROM purchase_requests WHERE spb_id = :id FOR UPDATE');
        $stmt->execute([':id' => $spbId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) throw new OutOfBoundsException('SPB tidak ditemukan atau sudah di-void.');
        $authorizeAsset((string) $request['asset_id']);
        $linked = $db->prepare("SELECT 1 FROM report_purchase_request_integrations rpri
            INNER JOIN report_records rr ON rr.report_id = rpri.report_id
            WHERE rpri.spb_id = :id AND rr.status <> 'VOID' LIMIT 1");
        $linked->execute([':id' => $spbId]);
        if ($linked->fetchColumn()) throw new DomainException('SPB ini berasal dari laporan. Void laporan asal melalui Riwayat Laporan.');
        if (!in_array($request['status'], ['Draft', 'Submitted', 'Approved'], true)) {
            throw new DomainException('SPB sudah masuk tahap pengadaan/pengeluaran. Void transaksi turunannya terlebih dahulu.');
        }
        $orders = $db->prepare('SELECT COUNT(*) FROM purchase_orders WHERE spb_id = :id');
        $orders->execute([':id' => $spbId]);
        if ((int) $orders->fetchColumn() > 0) throw new DomainException('SPB masih memiliki Purchase Order. Void/selesaikan PO terlebih dahulu.');
        $stock = $db->prepare("SELECT COUNT(*) FROM inventory_transactions WHERE wo_id = :wo_id AND movement_type = 'OUT' AND reversed_at IS NULL");
        $stock->execute([':wo_id' => $request['wo_id']]);
        if ((int) $stock->fetchColumn() > 0) throw new DomainException('WO sudah memiliki transaksi stok keluar. Rekonsiliasi stok diperlukan sebelum SPB dapat di-void.');
        $items = $db->prepare('SELECT * FROM purchase_request_items WHERE spb_id = :id ORDER BY id FOR UPDATE');
        $items->execute([':id' => $spbId]);
        $approvals = $db->prepare("SELECT * FROM approvals WHERE document_type = 'SPB' AND document_id = :id ORDER BY approval_id");
        $approvals->execute([':id' => $spbId]);
        $snapshot = json_encode(['request' => $request, 'items' => $items->fetchAll(PDO::FETCH_ASSOC), 'approvals' => $approvals->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $db->prepare('INSERT INTO spb_void_audit (spb_id, reason, snapshot_json, voided_by) VALUES (:id, :reason, :snapshot, :actor)')
            ->execute([':id' => $spbId, ':reason' => $reason, ':snapshot' => $snapshot, ':actor' => $actorId]);
        $db->prepare('DELETE FROM purchase_request_items WHERE spb_id = :id')->execute([':id' => $spbId]);
        $db->prepare('DELETE FROM purchase_requests WHERE spb_id = :id')->execute([':id' => $spbId]);
        return ['spbId' => $spbId, 'status' => 'VOID'];
    }
    public static function canApproveRoles(array $roles): bool
    {
        return (bool) array_intersect($roles, ['Administrator', 'Equipment Manager', 'Logistic Head']);
    }

    public static function decide(PDO $db, string $spbId, int $actorId, string $decision, string $reason, callable $authorizeAsset): array
    {
        if ($spbId === '' || $actorId < 1 || !in_array($decision, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('SPB dan keputusan wajib diisi.');
        }
        if ($decision === 'reject' && ($reason === '' || mb_strlen($reason, 'UTF-8') > 500)) {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi (maksimal 500 karakter).');
        }

        $stmt = $db->prepare('SELECT spb_id, asset_id, status FROM purchase_requests WHERE spb_id = :id FOR UPDATE');
        $stmt->execute([':id' => $spbId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) throw new OutOfBoundsException('SPB tidak ditemukan.');
        $authorizeAsset((string) $request['asset_id']);
        if ($request['status'] !== 'Submitted') {
            throw new DomainException('SPB sudah diproses atau belum diajukan. Muat ulang daftar.');
        }

        $newStatus = $decision === 'approve' ? 'Approved' : 'Draft';
        $update = $db->prepare("UPDATE purchase_requests SET status = :status WHERE spb_id = :id AND status = 'Submitted'");
        $update->execute([':status' => $newStatus, ':id' => $spbId]);
        if ($update->rowCount() !== 1) throw new DomainException('SPB berubah selama diproses. Muat ulang daftar.');

        $db->prepare("INSERT INTO approvals (document_type, document_id, approver_user_id, status, rejection_reason, action_at)
                      VALUES ('SPB', :id, :actor, :status, :reason, NOW())")
            ->execute([
                ':id' => $spbId,
                ':actor' => $actorId,
                ':status' => $decision === 'approve' ? 'APPROVED' : 'REJECTED',
                ':reason' => $decision === 'reject' ? $reason : null,
            ]);
        return ['spbId' => $spbId, 'status' => $newStatus];
    }
}
