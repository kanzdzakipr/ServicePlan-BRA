<?php
declare(strict_types=1);

final class ReportIntegration
{
    public static function ensureTables(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS inventory_transactions (
            transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            report_item_position INT UNSIGNED NOT NULL,
            movement_type ENUM('IN', 'OUT') NOT NULL,
            transaction_date DATE NOT NULL,
            reference_number VARCHAR(190) NOT NULL,
            counterparty VARCHAR(190) NULL,
            part_id INT NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            unit_measure VARCHAR(20) NOT NULL,
            stock_before INT NOT NULL,
            stock_after INT NOT NULL,
            notes VARCHAR(500) NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_inventory_report_line (report_id, report_item_position, movement_type),
            KEY idx_inventory_part_date (part_id, transaction_date),
            KEY idx_inventory_active (movement_type, reversed_at, transaction_date),
            CONSTRAINT fk_inventory_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_inventory_part FOREIGN KEY (part_id) REFERENCES parts (part_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function applyFinal(
        PDO $db,
        string $templateKey,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        if ($templateKey !== 'bhw-in') {
            return ['type' => 'none', 'applied' => false, 'itemCount' => 0, 'totalQuantity' => 0];
        }

        return self::applyBhwIn($db, $reportId, $fields, $rows, $actorId);
    }

    public static function reverseFinal(PDO $db, string $reportId, int $actorId): array
    {
        $statement = $db->prepare(
            "SELECT transaction_id, part_id, movement_type, quantity
             FROM inventory_transactions
             WHERE report_id = :report_id AND reversed_at IS NULL
             ORDER BY transaction_id DESC
             FOR UPDATE"
        );
        $statement->execute([':report_id' => $reportId]);
        $transactions = $statement->fetchAll(PDO::FETCH_ASSOC);

        $reversed = 0;
        foreach ($transactions as $transaction) {
            $partStatement = $db->prepare('SELECT stock_qty FROM parts WHERE part_id = :part_id FOR UPDATE');
            $partStatement->execute([':part_id' => (int) $transaction['part_id']]);
            $currentStock = $partStatement->fetchColumn();
            if ($currentStock === false) {
                throw new DomainException('Part transaksi laporan tidak ditemukan saat proses void.');
            }

            $quantity = (int) $transaction['quantity'];
            $nextStock = $transaction['movement_type'] === 'IN'
                ? (int) $currentStock - $quantity
                : (int) $currentStock + $quantity;
            if ($nextStock < 0) {
                throw new DomainException('Laporan tidak dapat di-void karena stok sudah terpakai dan akan menjadi negatif.');
            }

            $updatePart = $db->prepare('UPDATE parts SET stock_qty = :stock_qty WHERE part_id = :part_id');
            $updatePart->execute([':stock_qty' => $nextStock, ':part_id' => (int) $transaction['part_id']]);

            $reverse = $db->prepare(
                'UPDATE inventory_transactions
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE transaction_id = :transaction_id AND reversed_at IS NULL'
            );
            $reverse->execute([
                ':actor_id' => $actorId,
                ':transaction_id' => (int) $transaction['transaction_id'],
            ]);
            $reversed += $reverse->rowCount();
        }

        return ['type' => 'inventory-reversal', 'applied' => $reversed > 0, 'itemCount' => $reversed];
    }

    private static function applyBhwIn(PDO $db, string $reportId, array $fields, array $rows, int $actorId): array
    {
        $existingStatement = $db->prepare(
            "SELECT COUNT(*) FROM inventory_transactions
             WHERE report_id = :report_id AND movement_type = 'IN'"
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingCount = (int) $existingStatement->fetchColumn();
        if ($existingCount > 0) {
            return [
                'type' => 'bhw-in',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => $existingCount,
                'totalQuantity' => 0,
            ];
        }

        $reportDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal laporan');
        $itemCount = 0;
        $totalQuantity = 0;

        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }

            $partNumber = self::requiredText($row['part_number'] ?? null, 'Part number', 100);
            $partName = self::requiredText($row['nama'] ?? null, 'Nama parts', 150);
            $unitMeasure = self::requiredText($row['satuan'] ?? null, 'Satuan', 20);
            $quantity = self::requiredNonNegativeInteger($row['jumlah'] ?? null, 'Jumlah', false);
            $reportedBefore = self::requiredNonNegativeInteger($row['saldo_lalu'] ?? null, 'Saldo lalu', true);
            $reportedAfter = self::requiredNonNegativeInteger($row['saldo_sekarang'] ?? null, 'Saldo sekarang', true);
            $expectedAfter = $reportedBefore + $quantity;
            if ($reportedAfter !== $expectedAfter) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": saldo sekarang harus sama dengan saldo lalu + jumlah ({$expectedAfter})."
                );
            }

            $transactionDate = self::optionalDate($row['tanggal'] ?? null) ?? $reportDate;
            $referenceNumber = self::requiredText(
                $row['bapb'] ?? ($fields['nomor_log'] ?? null),
                'No. BAPB/Nomor log',
                190
            );
            $counterparty = self::optionalText($row['dari'] ?? null, 190);
            $notes = self::optionalText($row['keterangan'] ?? null, 500);

            $partStatement = $db->prepare(
                'SELECT part_id, part_name, unit_measure, stock_qty
                 FROM parts WHERE part_number = :part_number LIMIT 1 FOR UPDATE'
            );
            $partStatement->execute([':part_number' => $partNumber]);
            $part = $partStatement->fetch(PDO::FETCH_ASSOC);

            if (!$part) {
                $insertPart = $db->prepare(
                    "INSERT INTO parts
                     (part_number, part_name, category, unit_measure, stock_qty, min_stock_qty, unit_cost, location_warehouse)
                     VALUES (:part_number, :part_name, 'Other', :unit_measure, :stock_qty, 2, 0, 'Gudang Yard KM 12')"
                );
                $insertPart->execute([
                    ':part_number' => $partNumber,
                    ':part_name' => $partName,
                    ':unit_measure' => $unitMeasure,
                    ':stock_qty' => $reportedBefore,
                ]);
                $part = [
                    'part_id' => (int) $db->lastInsertId(),
                    'part_name' => $partName,
                    'unit_measure' => $unitMeasure,
                    'stock_qty' => $reportedBefore,
                ];
            }

            if (strcasecmp(trim((string) $part['unit_measure']), $unitMeasure) !== 0) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": satuan {$unitMeasure} tidak cocok dengan master part {$partNumber} ({$part['unit_measure']})."
                );
            }

            $currentStock = (int) $part['stock_qty'];
            if ($currentStock !== $reportedBefore) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": saldo lalu {$reportedBefore} tidak sama dengan stok database {$currentStock} untuk {$partNumber}. Muat ulang data stok sebelum finalisasi."
                );
            }

            $stockAfter = $currentStock + $quantity;
            $updatePart = $db->prepare('UPDATE parts SET stock_qty = :stock_qty WHERE part_id = :part_id');
            $updatePart->execute([':stock_qty' => $stockAfter, ':part_id' => (int) $part['part_id']]);

            $insertTransaction = $db->prepare(
                "INSERT INTO inventory_transactions
                 (report_id, report_item_position, movement_type, transaction_date, reference_number,
                  counterparty, part_id, quantity, unit_measure, stock_before, stock_after, notes, created_by)
                 VALUES (:report_id, :position, 'IN', :transaction_date, :reference_number,
                  :counterparty, :part_id, :quantity, :unit_measure, :stock_before, :stock_after, :notes, :created_by)"
            );
            $insertTransaction->execute([
                ':report_id' => $reportId,
                ':position' => $position,
                ':transaction_date' => $transactionDate,
                ':reference_number' => $referenceNumber,
                ':counterparty' => $counterparty,
                ':part_id' => (int) $part['part_id'],
                ':quantity' => $quantity,
                ':unit_measure' => $unitMeasure,
                ':stock_before' => $currentStock,
                ':stock_after' => $stockAfter,
                ':notes' => $notes,
                ':created_by' => $actorId,
            ]);

            $itemCount++;
            $totalQuantity += $quantity;
        }

        if ($itemCount === 0) {
            throw new DomainException('BHW-IN tidak memiliki baris barang yang dapat diintegrasikan.');
        }

        return [
            'type' => 'bhw-in',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => $itemCount,
            'totalQuantity' => $totalQuantity,
        ];
    }

    private static function rowHasContent(array $row): bool
    {
        foreach ($row as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }
            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }

    private static function requiredText($value, string $label, int $maxLength): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            throw new DomainException("{$label} wajib diisi untuk integrasi stok.");
        }
        if (mb_strlen($text) > $maxLength) {
            throw new DomainException("{$label} maksimal {$maxLength} karakter.");
        }
        return $text;
    }

    private static function optionalText($value, int $maxLength): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        return mb_substr($text, 0, $maxLength);
    }

    private static function requiredNonNegativeInteger($value, string $label, bool $allowZero): int
    {
        $raw = trim((string) $value);
        if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
            throw new DomainException("{$label} harus berupa bilangan bulat non-negatif.");
        }
        $number = (int) $raw;
        if (!$allowZero && $number < 1) {
            throw new DomainException("{$label} harus lebih besar dari nol.");
        }
        return $number;
    }

    private static function requiredDate($value, string $label): string
    {
        $date = self::optionalDate($value);
        if ($date === null) {
            throw new DomainException("{$label} wajib berupa tanggal yang valid.");
        }
        return $date;
    }

    private static function optionalDate($value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        return $date && $date->format('Y-m-d') === $raw ? $raw : null;
    }
}
