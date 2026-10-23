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

    /**
     * Issue parts from warehouse for an approved SPB, deduct stock,
     * record inventory transactions, update SPB items, and auto-resume WO.
     */
    public static function issueParts(PDO $db, string $spbId, array $itemsToIssue, int $actorId, string $actorName, callable $authorizeAsset): array
    {
        if ($spbId === '' || $actorId < 1) {
            throw new InvalidArgumentException('Nomor SPB dan identitas aktor wajib diisi.');
        }

        // 1. Fetch SPB header with row lock
        $stmt = $db->prepare('SELECT * FROM purchase_requests WHERE spb_id = :id FOR UPDATE');
        $stmt->execute([':id' => $spbId]);
        $spb = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$spb) {
            throw new OutOfBoundsException('SPB tidak ditemukan.');
        }

        $authorizeAsset((string) $spb['asset_id']);

        if (!in_array($spb['status'], ['Approved', 'Submitted', 'Ordered', 'Issued'], true)) {
            throw new DomainException("SPB berstatus {$spb['status']} tidak dapat melakukan pengeluaran suku cadang.");
        }

        // 2. Fetch existing items
        $itemsStmt = $db->prepare('SELECT * FROM purchase_request_items WHERE spb_id = :id FOR UPDATE');
        $itemsStmt->execute([':id' => $spbId]);
        $existingItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($existingItems)) {
            throw new DomainException('SPB tidak memiliki daftar item suku cadang.');
        }

        $itemsById = [];
        $itemsByPart = [];
        foreach ($existingItems as $it) {
            $itemsById[(string) $it['id']] = $it;
            $itemsByPart[(string) $it['part_number']] = $it;
        }

        // Prepare issue targets
        $issueList = [];
        if (empty($itemsToIssue)) {
            foreach ($existingItems as $it) {
                if ($it['status'] !== 'Issued') {
                    $issueList[] = [
                        'item_id' => (string) $it['id'],
                        'part_number' => (string) $it['part_number'],
                        'qty' => (int) $it['qty_requested'],
                    ];
                }
            }
        } else {
            foreach ($itemsToIssue as $inputItem) {
                $itemId = (string) ($inputItem['id'] ?? $inputItem['item_id'] ?? '');
                $partNum = (string) ($inputItem['part_number'] ?? $inputItem['part_id'] ?? '');
                $matched = null;
                if ($itemId !== '' && isset($itemsById[$itemId])) {
                    $matched = $itemsById[$itemId];
                } elseif ($partNum !== '' && isset($itemsByPart[$partNum])) {
                    $matched = $itemsByPart[$partNum];
                }
                if ($matched) {
                    $qty = isset($inputItem['qty_issued']) ? (int) $inputItem['qty_issued'] : (int) ($inputItem['qty'] ?? $matched['qty_requested']);
                    if ($qty > 0) {
                        $issueList[] = [
                            'item_id' => (string) $matched['id'],
                            'part_number' => (string) $matched['part_number'],
                            'qty' => $qty,
                        ];
                    }
                }
            }
        }

        if (empty($issueList)) {
            throw new DomainException('Seluruh item pada SPB ini telah dikeluarkan sebelumnya.');
        }

        // Resolve valid report_id for fk_inventory_report constraint
        $repStmt = $db->prepare("
            SELECT report_id FROM report_purchase_request_integrations 
            WHERE spb_id = :id AND reversed_at IS NULL 
            ORDER BY integration_id DESC LIMIT 1
        ");
        $repStmt->execute([':id' => $spbId]);
        $reportId = $repStmt->fetchColumn();

        if (!$reportId) {
            $checkRep = $db->prepare("SELECT report_id FROM report_records WHERE report_number = :spb LIMIT 1");
            $checkRep->execute([':spb' => $spbId]);
            $reportId = $checkRep->fetchColumn();
        }

        if (!$reportId) {
            $reportId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff), random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0x4000, 0x4fff),
                random_int(0x8000, 0xbfff),
                random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
            );

            // Fetch template_id for 'spb'
            $tplStmt = $db->prepare("SELECT template_id FROM report_templates WHERE template_key = 'spb' LIMIT 1");
            $tplStmt->execute();
            $templateId = (int) $tplStmt->fetchColumn();
            if ($templateId === 0) $templateId = 24;

            $db->prepare("
                INSERT INTO report_records (report_id, template_id, client_key, report_number, status, source_method, field_data, created_by, created_at)
                VALUES (:rid, :tid, 'system-spb', :rnum, 'FINAL', 'spb_issue', '{}', :actor, NOW())
            ")->execute([
                ':rid' => $reportId,
                ':tid' => $templateId,
                ':rnum' => $spbId,
                ':actor' => $actorId,
            ]);

            $db->prepare("
                INSERT INTO report_purchase_request_integrations (report_id, spb_id, asset_id, owns_purchase_request, applied_payload, created_by, created_at)
                VALUES (:rid, :spb, :aid, 1, '{}', :actor, NOW())
            ")->execute([
                ':rid' => $reportId,
                ':spb' => $spbId,
                ':aid' => (string) $spb['asset_id'],
                ':actor' => $actorId,
            ]);
        }

        $issuedResults = [];
        $position = 1;

        foreach ($issueList as $target) {
            $partNumber = $target['part_number'];
            $qty = $target['qty'];
            $itemId = $target['item_id'];

            // Find and lock part record
            $partStmt = $db->prepare('SELECT * FROM parts WHERE part_number = :pnum FOR UPDATE');
            $partStmt->execute([':pnum' => $partNumber]);
            $part = $partStmt->fetch(PDO::FETCH_ASSOC);

            if (!$part && is_numeric($partNumber)) {
                $partStmt = $db->prepare('SELECT * FROM parts WHERE part_id = :pid FOR UPDATE');
                $partStmt->execute([':pid' => (int) $partNumber]);
                $part = $partStmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$part) {
                throw new OutOfBoundsException("Suku cadang {$partNumber} tidak terdaftar di master database parts.");
            }

            $currentStock = (int) ($part['stock_qty'] ?? 0);
            if ($currentStock < $qty) {
                throw new DomainException("Stok fisik {$part['part_name']} ({$part['part_number']}) tidak mencukupi! Tersedia di gudang: {$currentStock}, Diminta: {$qty}");
            }

            $stockAfter = $currentStock - $qty;

            // Deduct stock from parts table
            $db->prepare('UPDATE parts SET stock_qty = :stk WHERE part_id = :pid')
               ->execute([':stk' => $stockAfter, ':pid' => (int) $part['part_id']]);

            // Record inventory mutation transaction
            $db->prepare("
                INSERT INTO inventory_transactions
                (report_id, report_item_position, movement_type, transaction_date,
                 reference_number, counterparty, asset_id, wo_id, part_id, quantity, unit_measure,
                 stock_before, stock_after, notes, created_by)
                VALUES
                (:report_id, :position, 'OUT', CURDATE(),
                 :ref_num, :counterparty, :asset_id, :wo_id, :part_id, :quantity, :unit_measure,
                 :stock_before, :stock_after, :notes, :created_by)
            ")->execute([
                ':report_id' => $reportId,
                ':position' => $position++,
                ':ref_num' => $spbId,
                ':counterparty' => 'Gudang Yard KM 12',
                ':asset_id' => $spb['asset_id'],
                ':wo_id' => $spb['wo_id'],
                ':part_id' => (int) $part['part_id'],
                ':quantity' => $qty,
                ':unit_measure' => $part['unit_measure'] ?: 'Pcs',
                ':stock_before' => $currentStock,
                ':stock_after' => $stockAfter,
                ':notes' => "Pengeluaran fisik suku cadang via SPB {$spbId} untuk WO {$spb['wo_id']}",
                ':created_by' => $actorId,
            ]);

            // Mark item as Issued
            $db->prepare("UPDATE purchase_request_items SET status = 'Issued' WHERE id = :id")
               ->execute([':id' => $itemId]);

            $issuedResults[] = [
                'itemId' => $itemId,
                'partNumber' => $part['part_number'],
                'partName' => $part['part_name'],
                'qtyIssued' => $qty,
                'stockBefore' => $currentStock,
                'stockAfter' => $stockAfter,
            ];
        }

        // Check if all items in this SPB are now Issued
        $remainingStmt = $db->prepare("SELECT COUNT(*) FROM purchase_request_items WHERE spb_id = :id AND status != 'Issued'");
        $remainingStmt->execute([':id' => $spbId]);
        $remainingCount = (int) $remainingStmt->fetchColumn();

        $headerStatus = $remainingCount === 0 ? 'Issued' : $spb['status'];
        if ($remainingCount === 0 && $spb['status'] !== 'Issued') {
            $db->prepare("UPDATE purchase_requests SET status = 'Issued' WHERE spb_id = :id")
               ->execute([':id' => $spbId]);
        }

        // Auto-resume WO if applicable
        $resumeResult = null;
        if (!empty($spb['wo_id'])) {
            require_once __DIR__ . '/WorkOrderSupport.php';
            $resumeResult = WorkOrderSupport::checkAndResumeWorkOrder($db, (string) $spb['wo_id'], $actorId, $actorName);
        }

        return [
            'spbId' => $spbId,
            'spbStatus' => $headerStatus,
            'itemsIssued' => $issuedResults,
            'allIssued' => $remainingCount === 0,
            'woResume' => $resumeResult,
        ];
    }
}
