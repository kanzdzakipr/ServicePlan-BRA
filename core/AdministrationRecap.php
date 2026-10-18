<?php
declare(strict_types=1);

final class AdministrationRecap
{
    public static function logistics(PDO $db, string $period): array
    {
        if (!preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $period)) {
            throw new InvalidArgumentException('Periode harus dalam format YYYY-MM.');
        }
        $start = $period . '-01';
        $end = date('Y-m-d', strtotime($start . ' +1 month'));
        $statement = $db->prepare(
            "SELECT it.transaction_date, it.movement_type, it.reference_number, it.counterparty,
                    it.quantity, it.unit_measure, it.stock_before, it.stock_after, it.notes,
                    p.part_number, p.part_name, rr.report_number
             FROM inventory_transactions it
             INNER JOIN parts p ON p.part_id = it.part_id
             LEFT JOIN report_records rr ON rr.report_id = it.report_id
             WHERE it.reversed_at IS NULL AND it.transaction_date >= :start AND it.transaction_date < :end
             ORDER BY it.transaction_date, it.transaction_id"
        );
        $statement->execute([':start' => $start, ':end' => $end]);
        $incoming = [];
        $outgoing = [];
        $receivedQuantity = 0;
        $issuedQuantity = 0;
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $item = [
                'date' => (string) $row['transaction_date'],
                'reference' => (string) $row['reference_number'],
                'counterparty' => (string) ($row['counterparty'] ?? ''),
                'partNumber' => (string) $row['part_number'],
                'partName' => (string) $row['part_name'],
                'unit' => (string) $row['unit_measure'],
                'quantity' => (int) $row['quantity'],
                'stockBefore' => (int) $row['stock_before'],
                'stockAfter' => (int) $row['stock_after'],
                'notes' => (string) ($row['notes'] ?? ''),
                'reportNumber' => (string) ($row['report_number'] ?? ''),
            ];
            if ($row['movement_type'] === 'IN') {
                $incoming[] = $item;
                $receivedQuantity += $item['quantity'];
            } else {
                $outgoing[] = $item;
                $issuedQuantity += $item['quantity'];
            }
        }
        return [
            'status' => 'success',
            'period' => $period,
            'periodStart' => $start,
            'periodEndExclusive' => $end,
            'source' => 'inventory_transactions (transaksi aktif, bukan arsip PDF)',
            'summary' => [
                'incomingLines' => count($incoming), 'outgoingLines' => count($outgoing),
                'receivedQuantity' => $receivedQuantity, 'issuedQuantity' => $issuedQuantity,
            ],
            'incoming' => $incoming, 'outgoing' => $outgoing,
        ];
    }
}
