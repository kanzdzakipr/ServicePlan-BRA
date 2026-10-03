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

        $db->exec("CREATE TABLE IF NOT EXISTS report_inspection_integrations (
            integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            inspection_id INT NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            previous_asset_status VARCHAR(40) NOT NULL,
            applied_asset_status VARCHAR(40) NOT NULL,
            previous_hm DECIMAL(10,2) NOT NULL DEFAULT 0,
            applied_hm DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_report_inspection_report (report_id),
            UNIQUE KEY uq_report_inspection_record (inspection_id),
            KEY idx_report_inspection_asset (asset_id, reversed_at),
            CONSTRAINT fk_report_inspection_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_inspection_record FOREIGN KEY (inspection_id) REFERENCES inspections (inspection_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_inspection_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS report_operation_logs (
            operation_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            report_item_position INT UNSIGNED NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            operation_date DATE NOT NULL,
            operator_name VARCHAR(150) NOT NULL,
            site VARCHAR(190) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            work_hours DECIMAL(8,2) NOT NULL,
            hm_start DECIMAL(10,2) NOT NULL,
            hm_end DECIMAL(10,2) NOT NULL,
            hm_operation DECIMAL(8,2) NOT NULL,
            fuel_liters DECIMAL(10,2) NOT NULL DEFAULT 0,
            weather VARCHAR(40) NULL,
            verification_status VARCHAR(40) NOT NULL,
            notes VARCHAR(500) NULL,
            previous_asset_hm DECIMAL(10,2) NOT NULL,
            applied_asset_hm DECIMAL(10,2) NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_operation_report_line (report_id, report_item_position),
            KEY idx_operation_asset_date (asset_id, operation_date),
            KEY idx_operation_active (reversed_at, operation_date),
            CONSTRAINT fk_operation_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_operation_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
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
        if ($templateKey === 'bhw-in') {
            return self::applyBhwIn($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'bhw-out') {
            return self::applyBhwOut($db, $reportId, $fields, $rows, $actorId);
        }
        if (in_array($templateKey, ['p2h-excavator', 'p2h-roller'], true)) {
            return self::applyP2h($db, $templateKey, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'lho') {
            return self::applyLho($db, $reportId, $fields, $rows, $actorId);
        }
        return ['type' => 'none', 'applied' => false, 'itemCount' => 0, 'totalQuantity' => 0];
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

        $inspectionStatement = $db->prepare(
            "SELECT integration_id, asset_id, previous_asset_status, applied_asset_status,
                    previous_hm, applied_hm
             FROM report_inspection_integrations
             WHERE report_id = :report_id AND reversed_at IS NULL
             FOR UPDATE"
        );
        $inspectionStatement->execute([':report_id' => $reportId]);
        $inspectionIntegrations = $inspectionStatement->fetchAll(PDO::FETCH_ASSOC);
        $inspectionReversed = 0;

        foreach ($inspectionIntegrations as $integration) {
            $assetStatement = $db->prepare(
                'SELECT status, last_hm_km FROM assets WHERE asset_id = :asset_id FOR UPDATE'
            );
            $assetStatement->execute([':asset_id' => $integration['asset_id']]);
            $asset = $assetStatement->fetch(PDO::FETCH_ASSOC);
            if (!$asset) {
                throw new DomainException('Unit inspeksi tidak ditemukan saat proses void.');
            }

            $statusUnchanged = (string) $asset['status'] === (string) $integration['applied_asset_status'];
            $hmUnchanged = abs((float) $asset['last_hm_km'] - (float) $integration['applied_hm']) < 0.005;
            if ($statusUnchanged && $hmUnchanged) {
                $restoreAsset = $db->prepare(
                    'UPDATE assets SET status = :status, last_hm_km = :last_hm WHERE asset_id = :asset_id'
                );
                $restoreAsset->execute([
                    ':status' => $integration['previous_asset_status'],
                    ':last_hm' => $integration['previous_hm'],
                    ':asset_id' => $integration['asset_id'],
                ]);
            }

            $reverseInspection = $db->prepare(
                'UPDATE report_inspection_integrations
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE integration_id = :integration_id AND reversed_at IS NULL'
            );
            $reverseInspection->execute([
                ':actor_id' => $actorId,
                ':integration_id' => (int) $integration['integration_id'],
            ]);
            $inspectionReversed += $reverseInspection->rowCount();
        }

        $totalReversed = $reversed + $inspectionReversed;
        return [
            'type' => $inspectionReversed > 0 ? 'inspection-reversal' : 'inventory-reversal',
            'applied' => $totalReversed > 0,
            'itemCount' => $totalReversed,
            'inventoryItemCount' => $reversed,
            'inspectionItemCount' => $inspectionReversed,
            'message' => $inspectionReversed > 0
                ? 'Laporan berhasil dibatalkan dan riwayat inspeksi dinonaktifkan.'
                : null,
        ];
    }

    private static function applyP2h(
        PDO $db,
        string $templateKey,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existingStatement = $db->prepare(
            'SELECT inspection_id FROM report_inspection_integrations WHERE report_id = :report_id LIMIT 1'
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingInspectionId = $existingStatement->fetchColumn();
        if ($existingInspectionId !== false) {
            return [
                'type' => 'p2h-inspection',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => 1,
                'inspectionId' => (int) $existingInspectionId,
            ];
        }

        $assetId = self::requiredText($fields['code_number'] ?? null, 'Code number', 100);
        $inspectionDate = self::requiredDate($fields['tanggal_slot'] ?? null, 'Tanggal pelaksanaan');
        $operator = self::requiredText($fields['operator'] ?? null, 'Nama operator', 150);
        $nrp = self::optionalText($fields['nrp'] ?? null, 100) ?? '';
        $site = self::requiredText($fields['job_site'] ?? null, 'Job site', 190);
        $hmStart = self::nonNegativeDecimal($fields['hm_sebelum'] ?? 0, 'HM sebelum operasi');
        $hmEnd = self::nonNegativeDecimal($fields['hm_selesai'] ?? $hmStart, 'HM selesai operasi');
        if ($hmEnd < $hmStart) {
            throw new DomainException('HM selesai operasi tidak boleh lebih kecil dari HM sebelum operasi.');
        }

        $assetStatement = $db->prepare(
            'SELECT asset_id, category, status, last_hm_km, raw_location_notes
             FROM assets WHERE asset_id = :asset_id OR asset_code = :asset_code LIMIT 1 FOR UPDATE'
        );
        $assetStatement->execute([':asset_id' => $assetId, ':asset_code' => $assetId]);
        $asset = $assetStatement->fetch(PDO::FETCH_ASSOC);
        if (!$asset) {
            throw new DomainException("Unit {$assetId} tidak ditemukan pada Master Asset.");
        }

        $expectedCategory = $templateKey === 'p2h-excavator' ? 'Excavator' : 'Vibro Compactor';
        if (strcasecmp((string) $asset['category'], $expectedCategory) !== 0) {
            throw new DomainException(
                "Unit {$asset['asset_id']} berkategori {$asset['category']}, tidak cocok dengan template {$expectedCategory}."
            );
        }
        if (in_array((string) $asset['status'], ['ACCIDENT_HOLD', 'ACCIDENT HOLD', 'INACTIVE'], true)) {
            throw new DomainException("Unit {$asset['asset_id']} berstatus {$asset['status']} dan tidak dapat diproses melalui P2H.");
        }

        $currentHm = (float) $asset['last_hm_km'];
        if ($hmEnd > 0 && $hmEnd < $currentHm) {
            throw new DomainException(
                "HM selesai {$hmEnd} lebih kecil dari HM master unit {$currentHm}. Periksa kembali pembacaan hour meter."
            );
        }

        $failures = [];
        $warnings = [];
        $details = [];
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }
            $item = self::requiredText($row['item'] ?? null, 'Item pemeriksaan baris ' . ($position + 1), 255);
            $condition = self::requiredText($row['kondisi'] ?? null, 'Kondisi baris ' . ($position + 1), 100);
            $classification = self::classifyP2hCondition($condition, $position + 1);
            $action = self::optionalText($row['tindakan'] ?? null, 500) ?? '';
            $detail = [
                'group' => self::optionalText($row['kelompok'] ?? null, 150) ?? '',
                'item' => $item,
                'condition' => $condition,
                'addition' => self::nonNegativeDecimal($row['tambahan'] ?? 0, 'Penambahan cairan baris ' . ($position + 1)),
                'action' => $action,
                'classification' => $classification,
            ];
            $details[] = $detail;
            $finding = $item . ($action !== '' ? ': ' . $action : '');
            if ($classification === 'FAIL') {
                $failures[] = $finding;
            } elseif ($classification === 'WARNING') {
                $warnings[] = $finding;
            }
        }
        if ($details === []) {
            throw new DomainException('P2H tidak memiliki item pemeriksaan yang dapat diintegrasikan.');
        }

        $overallResult = $failures !== [] ? 'FAIL' : ($warnings !== [] ? 'WARNING' : 'PASS');
        $statusLabel = $overallResult === 'FAIL'
            ? 'GAGAL (CRITICAL FAIL)'
            : ($overallResult === 'WARNING' ? 'LULUS DENGAN CATATAN' : 'LULUS (PASS)');
        $summaryParts = array_merge($failures, $warnings);
        $summary = $summaryParts !== []
            ? implode('; ', $summaryParts)
            : 'Seluruh item pemeriksaan dalam kondisi normal.';

        $previousStatus = (string) $asset['status'];
        $appliedStatus = $previousStatus;
        if ($overallResult === 'FAIL') {
            $appliedStatus = 'BREAKDOWN';
        } elseif ($overallResult === 'WARNING' && $previousStatus !== 'BREAKDOWN') {
            $appliedStatus = 'INSPEKSI';
        }
        $appliedHm = $hmEnd > $currentHm ? $hmEnd : $currentHm;

        $reportStatement = $db->prepare('SELECT report_number FROM report_records WHERE report_id = :report_id LIMIT 1');
        $reportStatement->execute([':report_id' => $reportId]);
        $reportNumber = trim((string) $reportStatement->fetchColumn());
        if ($reportNumber === '') {
            $reportNumber = 'P2H-' . strtoupper(substr(str_replace('-', '', $reportId), 0, 12));
        }

        $payload = [
            'id' => $reportNumber,
            'date' => $inspectionDate . ' 00:00',
            'unitId' => (string) $asset['asset_id'],
            'category' => $expectedCategory === 'Vibro Compactor' ? 'Compactor' : 'Excavator',
            'operator' => $operator,
            'nrp' => $nrp,
            'site' => $site !== '' ? $site : (string) ($asset['raw_location_notes'] ?? ''),
            'hmStart' => $hmStart,
            'hmEnd' => $hmEnd,
            'status' => $statusLabel,
            'criticalFails' => count($failures),
            'warnings' => count($warnings),
            'notes' => $summary,
            'details' => $details,
            'source' => 'Laporan & Form',
            'sourceReportId' => $reportId,
            'schemaId' => $templateKey,
        ];

        $insertInspection = $db->prepare(
            'INSERT INTO inspections
             (asset_id, inspector_id, inspection_date, current_hm_km, overall_result, findings_summary, payload_json)
             VALUES (:asset_id, :inspector_id, :inspection_date, :current_hm, :result, :summary, :payload)'
        );
        $insertInspection->execute([
            ':asset_id' => $asset['asset_id'],
            ':inspector_id' => $actorId,
            ':inspection_date' => $inspectionDate . ' 00:00:00',
            ':current_hm' => $hmEnd,
            ':result' => $overallResult,
            ':summary' => $summary,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
        $inspectionId = (int) $db->lastInsertId();

        $updateAsset = $db->prepare(
            'UPDATE assets SET status = :status, last_hm_km = :last_hm WHERE asset_id = :asset_id'
        );
        $updateAsset->execute([
            ':status' => $appliedStatus,
            ':last_hm' => $appliedHm,
            ':asset_id' => $asset['asset_id'],
        ]);

        $insertIntegration = $db->prepare(
            'INSERT INTO report_inspection_integrations
             (report_id, inspection_id, asset_id, previous_asset_status, applied_asset_status,
              previous_hm, applied_hm, created_by)
             VALUES (:report_id, :inspection_id, :asset_id, :previous_status, :applied_status,
              :previous_hm, :applied_hm, :created_by)'
        );
        $insertIntegration->execute([
            ':report_id' => $reportId,
            ':inspection_id' => $inspectionId,
            ':asset_id' => $asset['asset_id'],
            ':previous_status' => $previousStatus,
            ':applied_status' => $appliedStatus,
            ':previous_hm' => $currentHm,
            ':applied_hm' => $appliedHm,
            ':created_by' => $actorId,
        ]);

        return [
            'type' => 'p2h-inspection',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => 1,
            'inspectionId' => $inspectionId,
            'result' => $overallResult,
            'assetId' => (string) $asset['asset_id'],
            'message' => "Laporan berhasil difinalkan dan masuk ke Riwayat Inspeksi & P2H ({$statusLabel}).",
        ];
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

    private static function applyBhwOut(PDO $db, string $reportId, array $fields, array $rows, int $actorId): array
    {
        $existingStatement = $db->prepare(
            "SELECT COUNT(*) FROM inventory_transactions
             WHERE report_id = :report_id AND movement_type = 'OUT'"
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingCount = (int) $existingStatement->fetchColumn();
        if ($existingCount > 0) {
            return [
                'type' => 'bhw-out',
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
            $quantity = self::requiredNonNegativeInteger($row['diberikan'] ?? null, 'Jumlah diberikan', false);
            $reportedBefore = self::requiredNonNegativeInteger($row['persediaan'] ?? null, 'Persediaan', true);
            $reportedAfter = self::requiredNonNegativeInteger($row['sisa'] ?? null, 'Sisa', true);
            if ($quantity > $reportedBefore) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ': jumlah diberikan tidak boleh melebihi persediaan.'
                );
            }
            $expectedAfter = $reportedBefore - $quantity;
            if ($reportedAfter !== $expectedAfter) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": sisa harus sama dengan persediaan - jumlah diberikan ({$expectedAfter})."
                );
            }

            $transactionDate = self::optionalDate($row['tanggal'] ?? null) ?? $reportDate;
            $referenceNumber = self::requiredText(
                $row['nomor_bukti'] ?? ($fields['nomor_log'] ?? null),
                'No. bukti/Nomor log',
                190
            );
            $counterparty = self::optionalText($row['tujuan'] ?? null, 190);
            $notes = self::optionalText($row['keterangan'] ?? null, 500);

            $partStatement = $db->prepare(
                'SELECT part_id, part_name, unit_measure, stock_qty
                 FROM parts WHERE part_number = :part_number LIMIT 1 FOR UPDATE'
            );
            $partStatement->execute([':part_number' => $partNumber]);
            $part = $partStatement->fetch(PDO::FETCH_ASSOC);
            if (!$part) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": part number {$partNumber} belum ada pada master stok."
                );
            }

            if (strcasecmp(trim((string) $part['unit_measure']), $unitMeasure) !== 0) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": satuan {$unitMeasure} tidak cocok dengan master part {$partNumber} ({$part['unit_measure']})."
                );
            }

            $currentStock = (int) $part['stock_qty'];
            if ($currentStock !== $reportedBefore) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": persediaan {$reportedBefore} tidak sama dengan stok database {$currentStock} untuk {$partNumber}. Muat ulang data stok sebelum finalisasi."
                );
            }
            if ($quantity > $currentStock) {
                throw new DomainException(
                    "Baris " . ($position + 1) . ": stok {$partNumber} tidak cukup untuk pengeluaran {$quantity} {$unitMeasure}."
                );
            }

            $stockAfter = $currentStock - $quantity;
            $updatePart = $db->prepare('UPDATE parts SET stock_qty = :stock_qty WHERE part_id = :part_id');
            $updatePart->execute([':stock_qty' => $stockAfter, ':part_id' => (int) $part['part_id']]);

            $insertTransaction = $db->prepare(
                "INSERT INTO inventory_transactions
                 (report_id, report_item_position, movement_type, transaction_date, reference_number,
                  counterparty, part_id, quantity, unit_measure, stock_before, stock_after, notes, created_by)
                 VALUES (:report_id, :position, 'OUT', :transaction_date, :reference_number,
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
            throw new DomainException('BHW-OUT tidak memiliki baris barang yang dapat diintegrasikan.');
        }

        return [
            'type' => 'bhw-out',
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
            throw new DomainException("{$label} wajib diisi untuk integrasi laporan.");
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

    private static function nonNegativeDecimal($value, string $label): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }
        $normalized = str_replace(',', '.', $raw);
        if (!is_numeric($normalized)) {
            throw new DomainException("{$label} harus berupa angka non-negatif.");
        }
        $number = (float) $normalized;
        if (!is_finite($number) || $number < 0) {
            throw new DomainException("{$label} harus berupa angka non-negatif.");
        }
        return round($number, 2);
    }

    private static function classifyP2hCondition(string $condition, int $rowNumber): string
    {
        $normalized = mb_strtolower(trim($condition));
        if (
            preg_match('/^x(?:\s|\x{2014}|\x{2013}|-|$)/u', $normalized)
            || str_contains($normalized, 'tidak normal')
            || str_contains($normalized, 'perlu perbaikan')
            || str_contains($normalized, 'tidak ada')
            || str_contains($normalized, 'rusak')
        ) {
            return 'FAIL';
        }
        if (
            preg_match('/^ok(?:\s|\x{2014}|\x{2013}|-|$)/u', $normalized)
            || str_contains($normalized, 'sudah diperbaiki')
            || str_contains($normalized, 'warning')
            || str_contains($normalized, 'catatan')
        ) {
            return 'WARNING';
        }
        if (
            preg_match('/^v(?:\s|\x{2014}|\x{2013}|-|$)/u', $normalized)
            || $normalized === 'normal'
            || $normalized === 'baik'
            || str_contains($normalized, '— normal')
            || str_contains($normalized, '- normal')
        ) {
            return 'PASS';
        }
        throw new DomainException("Baris {$rowNumber}: kondisi pemeriksaan tidak dikenali ({$condition}).");
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
