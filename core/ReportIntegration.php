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

        $db->exec("CREATE TABLE IF NOT EXISTS report_fuel_integrations (
            fuel_integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            report_item_position INT UNSIGNED NOT NULL,
            fuel_log_id INT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_report_fuel_line (report_id, report_item_position),
            UNIQUE KEY uq_report_fuel_log (fuel_log_id),
            KEY idx_report_fuel_active (reversed_at, report_id),
            CONSTRAINT fk_report_fuel_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_fuel_log FOREIGN KEY (fuel_log_id) REFERENCES fuel_logs (fuel_log_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS report_pm_integrations (
            integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            report_item_position INT UNSIGNED NOT NULL,
            pm_plan_id INT NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            owns_pm_plan TINYINT(1) NOT NULL DEFAULT 1,
            applied_payload LONGTEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_report_pm_line (report_id, report_item_position),
            KEY idx_report_pm_plan (pm_plan_id),
            KEY idx_report_pm_asset (asset_id, reversed_at),
            CONSTRAINT fk_report_pm_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_pm_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS report_work_order_integrations (
            integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            work_order_id VARCHAR(50) NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            owns_work_order TINYINT(1) NOT NULL DEFAULT 1,
            applied_payload LONGTEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_report_work_order_report (report_id),
            KEY idx_report_work_order_id (work_order_id),
            KEY idx_report_work_order_asset (asset_id, reversed_at),
            CONSTRAINT fk_report_work_order_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_work_order_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
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
        if ($templateKey === 'maintenance-board') {
            return self::applyMaintenanceBoard($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'repair-overhaul') {
            return self::applyRepairOverhaul($db, $reportId, $fields, $rows, $actorId);
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

        $operationStatement = $db->prepare(
            "SELECT operation_log_id, asset_id, previous_asset_hm, applied_asset_hm
             FROM report_operation_logs
             WHERE report_id = :report_id AND reversed_at IS NULL
             ORDER BY operation_log_id DESC
             FOR UPDATE"
        );
        $operationStatement->execute([':report_id' => $reportId]);
        $operationLogs = $operationStatement->fetchAll(PDO::FETCH_ASSOC);
        $operationReversed = 0;

        foreach ($operationLogs as $operation) {
            $assetStatement = $db->prepare('SELECT last_hm_km FROM assets WHERE asset_id = :asset_id FOR UPDATE');
            $assetStatement->execute([':asset_id' => $operation['asset_id']]);
            $currentHm = $assetStatement->fetchColumn();
            if ($currentHm === false) {
                throw new DomainException('Unit operasi LHO tidak ditemukan saat proses void.');
            }

            if (abs((float) $currentHm - (float) $operation['applied_asset_hm']) < 0.005) {
                $restoreHm = $db->prepare('UPDATE assets SET last_hm_km = :last_hm WHERE asset_id = :asset_id');
                $restoreHm->execute([
                    ':last_hm' => $operation['previous_asset_hm'],
                    ':asset_id' => $operation['asset_id'],
                ]);
            }

            $reverseOperation = $db->prepare(
                'UPDATE report_operation_logs
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE operation_log_id = :operation_log_id AND reversed_at IS NULL'
            );
            $reverseOperation->execute([
                ':actor_id' => $actorId,
                ':operation_log_id' => (int) $operation['operation_log_id'],
            ]);
            $operationReversed += $reverseOperation->rowCount();
        }

        $reverseFuel = $db->prepare(
            'UPDATE report_fuel_integrations
             SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
             WHERE report_id = :report_id AND reversed_at IS NULL'
        );
        $reverseFuel->execute([
            ':actor_id' => $actorId,
            ':report_id' => $reportId,
        ]);
        $fuelReversed = $reverseFuel->rowCount();

        $pmStatement = $db->prepare(
            "SELECT integration_id, pm_plan_id, owns_pm_plan, applied_payload
             FROM report_pm_integrations
             WHERE report_id = :report_id AND reversed_at IS NULL
             ORDER BY integration_id DESC
             FOR UPDATE"
        );
        $pmStatement->execute([':report_id' => $reportId]);
        $pmIntegrations = $pmStatement->fetchAll(PDO::FETCH_ASSOC);
        $pmReversed = 0;
        $pmDeleted = 0;
        $pmPreserved = 0;

        foreach ($pmIntegrations as $integration) {
            $planStatement = $db->prepare(
                'SELECT pm_plan_id, asset_id, interval_hm, current_smr, last_service_hm,
                        last_service_date, target_due_hm, variance_hm, status,
                        warranty_status, planner_note
                 FROM pm_plans WHERE pm_plan_id = :pm_plan_id FOR UPDATE'
            );
            $planStatement->execute([':pm_plan_id' => (int) $integration['pm_plan_id']]);
            $plan = $planStatement->fetch(PDO::FETCH_ASSOC);
            $ownsPlan = (int) $integration['owns_pm_plan'] === 1;
            $snapshot = json_decode((string) $integration['applied_payload'], true);

            if ($ownsPlan && $plan && is_array($snapshot) && self::pmPlanMatchesSnapshot($plan, $snapshot)) {
                $deletePlan = $db->prepare('DELETE FROM pm_plans WHERE pm_plan_id = :pm_plan_id');
                $deletePlan->execute([':pm_plan_id' => (int) $integration['pm_plan_id']]);
                $pmDeleted += $deletePlan->rowCount();
            } elseif ($ownsPlan && $plan) {
                // Perubahan planner setelah finalisasi tidak boleh ikut terhapus saat laporan di-void.
                $pmPreserved++;
            }

            $reversePm = $db->prepare(
                'UPDATE report_pm_integrations
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE integration_id = :integration_id AND reversed_at IS NULL'
            );
            $reversePm->execute([
                ':actor_id' => $actorId,
                ':integration_id' => (int) $integration['integration_id'],
            ]);
            $pmReversed += $reversePm->rowCount();
        }

        $workOrderStatement = $db->prepare(
            "SELECT integration_id, work_order_id, owns_work_order, applied_payload
             FROM report_work_order_integrations
             WHERE report_id = :report_id AND reversed_at IS NULL
             FOR UPDATE"
        );
        $workOrderStatement->execute([':report_id' => $reportId]);
        $workOrderIntegrations = $workOrderStatement->fetchAll(PDO::FETCH_ASSOC);
        $workOrderReversed = 0;
        $workOrderDeleted = 0;
        $workOrderPreserved = 0;

        foreach ($workOrderIntegrations as $integration) {
            $workOrderRecord = $db->prepare(
                'SELECT wo_id, asset_id, location_id, raw_location, issue_description,
                        downtime_minutes, is_downtime, status, priority, assigned_mechanic,
                        reported_at, repair_started_at, closed_at
                 FROM work_orders WHERE wo_id = :work_order_id FOR UPDATE'
            );
            $workOrderRecord->execute([':work_order_id' => $integration['work_order_id']]);
            $workOrder = $workOrderRecord->fetch(PDO::FETCH_ASSOC);
            $ownsWorkOrder = (int) $integration['owns_work_order'] === 1;
            $snapshot = json_decode((string) $integration['applied_payload'], true);
            $hasDownstreamActivity = false;

            if ($ownsWorkOrder && $workOrder) {
                $dependencyStatement = $db->prepare(
                    'SELECT
                        (SELECT COUNT(*) FROM wo_time_logs WHERE wo_id = :time_wo_id)
                      + (SELECT COUNT(*) FROM purchase_requests WHERE wo_id = :request_wo_id)
                      + (SELECT COUNT(*) FROM inspections WHERE created_wo_id = :inspection_wo_id)'
                );
                $dependencyStatement->execute([
                    ':time_wo_id' => $integration['work_order_id'],
                    ':request_wo_id' => $integration['work_order_id'],
                    ':inspection_wo_id' => $integration['work_order_id'],
                ]);
                $hasDownstreamActivity = (int) $dependencyStatement->fetchColumn() > 0;
            }

            if (
                $ownsWorkOrder
                && $workOrder
                && !$hasDownstreamActivity
                && is_array($snapshot)
                && self::workOrderMatchesSnapshot($workOrder, $snapshot)
            ) {
                $deleteWorkOrder = $db->prepare('DELETE FROM work_orders WHERE wo_id = :work_order_id');
                $deleteWorkOrder->execute([':work_order_id' => $integration['work_order_id']]);
                $workOrderDeleted += $deleteWorkOrder->rowCount();
            } elseif ($ownsWorkOrder && $workOrder) {
                // WO yang sudah dikerjakan, diubah, atau memiliki transaksi turunan wajib dipertahankan.
                $workOrderPreserved++;
            }

            $reverseWorkOrder = $db->prepare(
                'UPDATE report_work_order_integrations
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE integration_id = :integration_id AND reversed_at IS NULL'
            );
            $reverseWorkOrder->execute([
                ':actor_id' => $actorId,
                ':integration_id' => (int) $integration['integration_id'],
            ]);
            $workOrderReversed += $reverseWorkOrder->rowCount();
        }

        $totalReversed = $reversed + $inspectionReversed + $operationReversed + $fuelReversed
            + $pmReversed + $workOrderReversed;
        $reversalType = 'inventory-reversal';
        $reversalMessage = null;
        if ($inspectionReversed > 0) {
            $reversalType = 'inspection-reversal';
            $reversalMessage = 'Laporan berhasil dibatalkan dan riwayat inspeksi dinonaktifkan.';
        } elseif ($operationReversed > 0) {
            $reversalType = 'operation-reversal';
            $reversalMessage = 'Laporan berhasil dibatalkan, riwayat operasi dan transaksi BBM terkait dinonaktifkan, serta HM dipulihkan jika belum ada pembaruan lanjutan.';
        } elseif ($pmReversed > 0) {
            $reversalType = 'pm-plan-reversal';
            $reversalMessage = $pmPreserved > 0
                ? "Laporan berhasil dibatalkan. {$pmDeleted} rencana PM dihapus dan {$pmPreserved} rencana yang sudah diubah planner tetap dipertahankan."
                : "Laporan berhasil dibatalkan dan {$pmDeleted} rencana PM buatan laporan dihapus.";
        } elseif ($workOrderReversed > 0) {
            $reversalType = 'work-order-reversal';
            $reversalMessage = $workOrderPreserved > 0
                ? "Laporan berhasil dibatalkan. {$workOrderPreserved} Work Order tetap dipertahankan karena sudah diubah atau memiliki aktivitas lanjutan."
                : "Laporan berhasil dibatalkan dan {$workOrderDeleted} Work Order buatan laporan dihapus.";
        }
        return [
            'type' => $reversalType,
            'applied' => $totalReversed > 0,
            'itemCount' => $totalReversed,
            'inventoryItemCount' => $reversed,
            'inspectionItemCount' => $inspectionReversed,
            'operationItemCount' => $operationReversed,
            'fuelItemCount' => $fuelReversed,
            'pmItemCount' => $pmReversed,
            'pmDeletedCount' => $pmDeleted,
            'pmPreservedCount' => $pmPreserved,
            'workOrderItemCount' => $workOrderReversed,
            'workOrderDeletedCount' => $workOrderDeleted,
            'workOrderPreservedCount' => $workOrderPreserved,
            'message' => $reversalMessage,
        ];
    }

    private static function applyRepairOverhaul(
        PDO $db,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existingStatement = $db->prepare(
            'SELECT work_order_id FROM report_work_order_integrations WHERE report_id = :report_id LIMIT 1'
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingWorkOrderId = $existingStatement->fetchColumn();
        if ($existingWorkOrderId !== false) {
            return [
                'type' => 'repair-overhaul',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => 1,
                'workOrderId' => (string) $existingWorkOrderId,
            ];
        }

        $workOrderId = self::requiredText($fields['nomor'] ?? null, 'Nomor surat', 50);
        $reportDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal laporan');
        $assetReference = self::requiredText($fields['kode_unit'] ?? null, 'Kode unit', 100);
        $reportedAsset = self::requiredText($fields['asset'] ?? null, 'Heavy equipment asset', 190);
        $finding = self::requiredText($fields['temuan'] ?? null, 'Temuan / analisa kerusakan', 4000);
        $urgency = self::requiredText($fields['urgensi'] ?? null, 'Tingkat urgensi', 20);
        $priority = match ($urgency) {
            'Normal' => 'Normal',
            'Mendesak' => 'High',
            'Emergency' => 'Emergency',
            default => throw new DomainException('Tingkat urgensi Repair & Overhaul tidak dikenali.'),
        };

        $assetStatement = $db->prepare(
            'SELECT a.asset_id, a.asset_code, a.category, a.make_model, a.serial_number,
                    a.last_hm_km, a.current_location_id, a.raw_location_notes, l.location_name
             FROM assets a
             LEFT JOIN locations l ON l.location_id = a.current_location_id
             WHERE a.is_active = 1 AND (a.asset_id = :asset_id OR a.asset_code = :asset_code)
             ORDER BY CASE WHEN a.asset_id = :exact_asset_id THEN 0 ELSE 1 END
             LIMIT 2 FOR UPDATE'
        );
        $assetStatement->execute([
            ':asset_id' => $assetReference,
            ':asset_code' => $assetReference,
            ':exact_asset_id' => $assetReference,
        ]);
        $matchedAssets = $assetStatement->fetchAll(PDO::FETCH_ASSOC);
        if ($matchedAssets === []) {
            throw new DomainException("Unit {$assetReference} belum ada atau tidak aktif pada Master Asset.");
        }
        if (count($matchedAssets) > 1 && (string) $matchedAssets[0]['asset_id'] !== $assetReference) {
            throw new DomainException("Kode unit {$assetReference} cocok ke lebih dari satu aset. Pilih ID aset lengkap dari database.");
        }
        $asset = $matchedAssets[0];

        $solutions = [];
        $assignedMechanic = null;
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }
            $rowNumber = $position + 1;
            $solution = self::requiredText($row['solusi'] ?? null, "Solusi baris {$rowNumber}", 1000);
            $pic = self::requiredText($row['pic'] ?? null, "PIC baris {$rowNumber}", 100);
            $targetDate = self::requiredDate($row['target'] ?? null, "Target baris {$rowNumber}");
            $notes = self::optionalText($row['keterangan'] ?? null, 500);
            if ($assignedMechanic === null) {
                $assignedMechanic = $pic;
            }
            $line = "{$rowNumber}. {$solution} | PIC: {$pic} | Target: {$targetDate}";
            if ($notes) $line .= " | Catatan: {$notes}";
            $solutions[] = $line;
        }
        if ($solutions === []) {
            throw new DomainException('Repair & Overhaul harus memiliki sedikitnya satu solusi yang diusulkan.');
        }

        $hourMeter = self::nonNegativeDecimal($fields['hm'] ?? null, 'Hour meter');
        $partName = self::requiredText($fields['nama_parts'] ?? null, 'Nama parts', 150);
        $partNumber = self::optionalText($fields['part_number'] ?? null, 100);
        $history = self::optionalText($fields['riwayat'] ?? null, 2000);
        $attachment = self::optionalText($fields['lampiran'] ?? null, 1000);
        $estimateMin = self::nonNegativeDecimal($fields['estimasi_min'] ?? null, 'Estimasi biaya minimum');
        $estimateMax = self::nonNegativeDecimal($fields['estimasi_max'] ?? null, 'Estimasi biaya maksimum');
        if ($estimateMin > 0 && $estimateMax > 0 && $estimateMin > $estimateMax) {
            throw new DomainException('Estimasi biaya minimum tidak boleh melebihi estimasi biaya maksimum.');
        }

        $descriptionParts = [
            "[Laporan R&O {$workOrderId}]",
            "Asset: {$reportedAsset}",
            "Temuan: {$finding}",
            "Parts: {$partName}" . ($partNumber ? " ({$partNumber})" : ''),
            "HM/KM laporan: {$hourMeter}",
        ];
        if ($history) $descriptionParts[] = "Riwayat: {$history}";
        if ($estimateMin > 0 || $estimateMax > 0) {
            $descriptionParts[] = "Estimasi biaya: {$estimateMin} - {$estimateMax}";
        }
        $descriptionParts[] = "Solusi:\n" . implode("\n", $solutions);
        if ($attachment) $descriptionParts[] = "Lampiran: {$attachment}";
        $issueDescription = implode("\n", $descriptionParts);
        $rawLocation = trim((string) ($asset['location_name'] ?? ''));
        if ($rawLocation === '') {
            $rawLocation = self::optionalText($asset['raw_location_notes'] ?? null, 255) ?? '';
        }

        $snapshot = [
            'wo_id' => $workOrderId,
            'asset_id' => (string) $asset['asset_id'],
            'location_id' => $asset['current_location_id'] !== null ? (int) $asset['current_location_id'] : null,
            'raw_location' => $rawLocation !== '' ? $rawLocation : null,
            'issue_description' => $issueDescription,
            'downtime_minutes' => 0,
            'is_downtime' => 0,
            'status' => 'Open',
            'priority' => $priority,
            'assigned_mechanic' => $assignedMechanic ?? 'Belum ada PIC',
            'reported_at' => $reportDate . ' 00:00:00',
            'repair_started_at' => null,
            'closed_at' => null,
        ];

        $workOrderStatement = $db->prepare(
            'SELECT wo_id, asset_id, location_id, raw_location, issue_description,
                    downtime_minutes, is_downtime, status, priority, assigned_mechanic,
                    reported_at, repair_started_at, closed_at
             FROM work_orders WHERE wo_id = :work_order_id FOR UPDATE'
        );
        $workOrderStatement->execute([':work_order_id' => $workOrderId]);
        $existingWorkOrder = $workOrderStatement->fetch(PDO::FETCH_ASSOC);
        $ownsWorkOrder = !$existingWorkOrder;

        if ($existingWorkOrder && !self::workOrderMatchesSnapshot($existingWorkOrder, $snapshot)) {
            throw new DomainException("Nomor {$workOrderId} sudah digunakan oleh Work Order lain dengan data berbeda.");
        }

        if ($ownsWorkOrder) {
            $insertWorkOrder = $db->prepare(
                'INSERT INTO work_orders
                 (wo_id, asset_id, location_id, raw_location, issue_description,
                  downtime_minutes, is_downtime, status, priority, assigned_mechanic, reported_at)
                 VALUES (:wo_id, :asset_id, :location_id, :raw_location, :issue_description,
                  :downtime_minutes, :is_downtime, :status, :priority, :assigned_mechanic, :reported_at)'
            );
            $insertWorkOrder->execute([
                ':wo_id' => $snapshot['wo_id'],
                ':asset_id' => $snapshot['asset_id'],
                ':location_id' => $snapshot['location_id'],
                ':raw_location' => $snapshot['raw_location'],
                ':issue_description' => $snapshot['issue_description'],
                ':downtime_minutes' => $snapshot['downtime_minutes'],
                ':is_downtime' => $snapshot['is_downtime'],
                ':status' => $snapshot['status'],
                ':priority' => $snapshot['priority'],
                ':assigned_mechanic' => $snapshot['assigned_mechanic'],
                ':reported_at' => $snapshot['reported_at'],
            ]);
        }

        $insertIntegration = $db->prepare(
            'INSERT INTO report_work_order_integrations
             (report_id, work_order_id, asset_id, owns_work_order, applied_payload, created_by)
             VALUES (:report_id, :work_order_id, :asset_id, :owns_work_order, :applied_payload, :created_by)'
        );
        $insertIntegration->execute([
            ':report_id' => $reportId,
            ':work_order_id' => $workOrderId,
            ':asset_id' => $asset['asset_id'],
            ':owns_work_order' => $ownsWorkOrder ? 1 : 0,
            ':applied_payload' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ':created_by' => $actorId,
        ]);

        return [
            'type' => 'repair-overhaul',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => 1,
            'workOrderId' => $workOrderId,
            'createdItemCount' => $ownsWorkOrder ? 1 : 0,
            'linkedItemCount' => $ownsWorkOrder ? 0 : 1,
            'message' => $ownsWorkOrder
                ? "Laporan berhasil difinalkan dan Work Order {$workOrderId} dibuat."
                : "Laporan berhasil difinalkan dan ditautkan ke Work Order {$workOrderId} tanpa duplikasi.",
        ];
    }

    private static function applyMaintenanceBoard(
        PDO $db,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existingStatement = $db->prepare(
            'SELECT COUNT(*) FROM report_pm_integrations WHERE report_id = :report_id'
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingCount = (int) $existingStatement->fetchColumn();
        if ($existingCount > 0) {
            return [
                'type' => 'maintenance-board',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => $existingCount,
                'createdItemCount' => 0,
                'linkedItemCount' => $existingCount,
            ];
        }

        $reportDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal pembaruan');
        $location = self::requiredText($fields['lokasi'] ?? null, 'Lokasi', 190);
        $preparedBy = self::requiredText($fields['dibuat_oleh'] ?? null, 'Dibuat oleh', 150);
        $positionName = self::optionalText($fields['jabatan'] ?? null, 150);
        $department = self::optionalText($fields['departemen'] ?? null, 150);
        $itemCount = 0;
        $createdCount = 0;
        $linkedCount = 0;

        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }

            $rowNumber = $position + 1;
            $assetReference = self::requiredText(
                $row['kode'] ?? ($row['kode_unit'] ?? null),
                "Kode unit baris {$rowNumber}",
                100
            );
            $assetStatement = $db->prepare(
                'SELECT asset_id, asset_code, category, make_model, last_hm_km
                 FROM assets
                 WHERE is_active = 1 AND (asset_id = :asset_id OR asset_code = :asset_code)
                 ORDER BY CASE WHEN asset_id = :exact_asset_id THEN 0 ELSE 1 END
                 LIMIT 2 FOR UPDATE'
            );
            $assetStatement->execute([
                ':asset_id' => $assetReference,
                ':asset_code' => $assetReference,
                ':exact_asset_id' => $assetReference,
            ]);
            $matchedAssets = $assetStatement->fetchAll(PDO::FETCH_ASSOC);
            if ($matchedAssets === []) {
                throw new DomainException("Baris {$rowNumber}: unit {$assetReference} belum ada atau tidak aktif pada Master Asset.");
            }
            if (count($matchedAssets) > 1 && (string) $matchedAssets[0]['asset_id'] !== $assetReference) {
                throw new DomainException("Baris {$rowNumber}: kode unit {$assetReference} cocok ke lebih dari satu aset. Pilih ID aset lengkap dari database.");
            }
            $asset = $matchedAssets[0];

            $interval = self::requiredPmInterval($row['interval'] ?? null, $rowNumber);
            $lastServiceHm = self::requiredNonNegativeDecimal($row['hm_awal'] ?? null, "HM awal baris {$rowNumber}");
            $lastServiceDate = self::requiredDate($row['tanggal_hm'] ?? null, "Tanggal HM baris {$rowNumber}");
            $realizationDate = self::optionalDate($row['realisasi'] ?? null);
            $partsOrderedDate = self::optionalDate($row['parts_pesan'] ?? null);
            $partsArrivedDate = self::optionalDate($row['parts_tiba'] ?? null);
            $currentSmr = round((float) $asset['last_hm_km'], 2);
            $targetDueHm = round($lastServiceHm + $interval, 2);
            $varianceHm = round($currentSmr - $targetDueHm, 2);
            $status = $realizationDate !== null
                ? 'COMPLETED'
                : ($varianceHm > 0 ? 'OVERDUE' : ($varianceHm >= -50 ? 'DUE_SOON' : 'PLANNED'));

            $latestWarrantyStatement = $db->prepare(
                'SELECT warranty_status FROM pm_plans
                 WHERE asset_id = :asset_id AND warranty_status IS NOT NULL AND warranty_status <> \'\'
                 ORDER BY pm_plan_id DESC LIMIT 1'
            );
            $latestWarrantyStatement->execute([':asset_id' => $asset['asset_id']]);
            $warranty = trim((string) $latestWarrantyStatement->fetchColumn());
            if ($warranty === '') {
                $warranty = 'No Warranty';
            }

            $noteParts = [
                'Sumber: Maintenance Board',
                "Lokasi: {$location}",
                "Dibuat oleh: {$preparedBy}" . ($positionName ? " ({$positionName})" : ''),
            ];
            if ($department) $noteParts[] = "Departemen: {$department}";
            $reportedType = self::optionalText($row['jenis'] ?? null, 150);
            if ($reportedType) $noteParts[] = "Jenis: {$reportedType}";
            if ($partsOrderedDate) $noteParts[] = "Parts dipesan: {$partsOrderedDate}";
            if ($partsArrivedDate) $noteParts[] = "Parts tiba: {$partsArrivedDate}";
            if ($realizationDate) $noteParts[] = "Realisasi: {$realizationDate} (HM aktual tidak tersedia pada form)";
            $rowNote = self::optionalText($row['keterangan'] ?? null, 1000);
            if ($rowNote) $noteParts[] = $rowNote;
            $plannerNote = implode('; ', $noteParts);

            $snapshot = [
                'asset_id' => (string) $asset['asset_id'],
                'interval_hm' => $interval,
                'current_smr' => $currentSmr,
                'last_service_hm' => $lastServiceHm,
                'last_service_date' => $lastServiceDate,
                'target_due_hm' => $targetDueHm,
                'variance_hm' => $varianceHm,
                'status' => $status,
                'warranty_status' => $warranty,
                'planner_note' => $plannerNote,
            ];

            $duplicatePlan = $db->prepare(
                'SELECT pm_plan_id FROM pm_plans
                 WHERE asset_id = :asset_id AND interval_hm = :interval_hm
                   AND ABS(last_service_hm - :last_service_hm) < 0.005
                   AND last_service_date = :last_service_date
                   AND ABS(target_due_hm - :target_due_hm) < 0.005
                 ORDER BY pm_plan_id DESC LIMIT 1 FOR UPDATE'
            );
            $duplicatePlan->execute([
                ':asset_id' => $snapshot['asset_id'],
                ':interval_hm' => $snapshot['interval_hm'],
                ':last_service_hm' => $snapshot['last_service_hm'],
                ':last_service_date' => $snapshot['last_service_date'],
                ':target_due_hm' => $snapshot['target_due_hm'],
            ]);
            $pmPlanId = (int) $duplicatePlan->fetchColumn();
            $ownsPlan = $pmPlanId < 1;

            if ($ownsPlan) {
                $insertPlan = $db->prepare(
                    'INSERT INTO pm_plans
                     (asset_id, interval_hm, current_smr, last_service_hm, last_service_date,
                      target_due_hm, variance_hm, status, warranty_status, planner_note)
                     VALUES (:asset_id, :interval_hm, :current_smr, :last_service_hm, :last_service_date,
                      :target_due_hm, :variance_hm, :status, :warranty_status, :planner_note)'
                );
                $insertPlan->execute([
                    ':asset_id' => $snapshot['asset_id'],
                    ':interval_hm' => $snapshot['interval_hm'],
                    ':current_smr' => $snapshot['current_smr'],
                    ':last_service_hm' => $snapshot['last_service_hm'],
                    ':last_service_date' => $snapshot['last_service_date'],
                    ':target_due_hm' => $snapshot['target_due_hm'],
                    ':variance_hm' => $snapshot['variance_hm'],
                    ':status' => $snapshot['status'],
                    ':warranty_status' => $snapshot['warranty_status'],
                    ':planner_note' => $snapshot['planner_note'],
                ]);
                $pmPlanId = (int) $db->lastInsertId();
                $createdCount++;
            } else {
                $linkedCount++;
            }

            $insertIntegration = $db->prepare(
                'INSERT INTO report_pm_integrations
                 (report_id, report_item_position, pm_plan_id, asset_id, owns_pm_plan,
                  applied_payload, created_by)
                 VALUES (:report_id, :position, :pm_plan_id, :asset_id, :owns_pm_plan,
                  :applied_payload, :created_by)'
            );
            $insertIntegration->execute([
                ':report_id' => $reportId,
                ':position' => $position,
                ':pm_plan_id' => $pmPlanId,
                ':asset_id' => $asset['asset_id'],
                ':owns_pm_plan' => $ownsPlan ? 1 : 0,
                ':applied_payload' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                ':created_by' => $actorId,
            ]);
            $itemCount++;
        }

        if ($itemCount === 0) {
            throw new DomainException('Maintenance Board tidak memiliki baris unit yang dapat diintegrasikan.');
        }

        return [
            'type' => 'maintenance-board',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => $itemCount,
            'createdItemCount' => $createdCount,
            'linkedItemCount' => $linkedCount,
            'message' => "Laporan berhasil difinalkan: {$createdCount} rencana PM dibuat dan {$linkedCount} rencana yang sudah ada ditautkan tanpa duplikasi.",
        ];
    }

    private static function applyLho(PDO $db, string $reportId, array $fields, array $rows, int $actorId): array
    {
        $existingStatement = $db->prepare(
            'SELECT COUNT(*) FROM report_operation_logs WHERE report_id = :report_id'
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingCount = (int) $existingStatement->fetchColumn();
        if ($existingCount > 0) {
            return [
                'type' => 'lho-operation',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => $existingCount,
                'totalWorkHours' => 0,
                'totalHmOperation' => 0,
                'totalFuelLiters' => 0,
                'fuelItemCount' => 0,
            ];
        }

        $period = self::requiredMonth($fields['periode'] ?? null, 'Bulan / tahun');
        $assetLookup = self::requiredText($fields['id_alat'] ?? null, 'ID alat', 100);
        $operator = self::requiredText($fields['operator'] ?? null, 'Operator', 150);
        $defaultSite = self::requiredText($fields['lokasi'] ?? null, 'Lokasi alat', 190);

        $assetStatement = $db->prepare(
            'SELECT asset_id, asset_code, status, last_hm_km
             FROM assets WHERE asset_id = :asset_id OR asset_code = :asset_code LIMIT 1 FOR UPDATE'
        );
        $assetStatement->execute([':asset_id' => $assetLookup, ':asset_code' => $assetLookup]);
        $asset = $assetStatement->fetch(PDO::FETCH_ASSOC);
        if (!$asset) {
            throw new DomainException("Unit {$assetLookup} tidak ditemukan pada Master Asset.");
        }
        if (in_array((string) $asset['status'], ['ACCIDENT_HOLD', 'ACCIDENT HOLD', 'INACTIVE'], true)) {
            throw new DomainException("Unit {$asset['asset_id']} berstatus {$asset['status']} dan tidak dapat menerima LHO.");
        }

        $populatedRows = [];
        foreach (array_values($rows) as $position => $row) {
            if (is_array($row) && self::rowHasContent($row)) {
                $populatedRows[] = ['position' => $position, 'row' => $row];
            }
        }
        if ($populatedRows === []) {
            throw new DomainException('LHO tidak memiliki baris operasi yang dapat diintegrasikan.');
        }

        usort($populatedRows, static function (array $left, array $right): int {
            $dateCompare = strcmp((string) ($left['row']['tanggal'] ?? ''), (string) ($right['row']['tanggal'] ?? ''));
            return $dateCompare !== 0 ? $dateCompare : $left['position'] <=> $right['position'];
        });

        $currentMasterHm = (float) $asset['last_hm_km'];
        $expectedHmStart = $currentMasterHm;
        $itemCount = 0;
        $totalWorkHours = 0.0;
        $totalHmOperation = 0.0;
        $totalFuelLiters = 0.0;
        $fuelItemCount = 0;

        foreach ($populatedRows as $entry) {
            $position = (int) $entry['position'];
            $row = $entry['row'];
            $rowNumber = $position + 1;
            $operationDate = self::requiredDate($row['tanggal'] ?? null, "Tanggal baris {$rowNumber}");
            if (substr($operationDate, 0, 7) !== $period) {
                throw new DomainException("Baris {$rowNumber}: tanggal operasi harus berada pada periode {$period}.");
            }

            $startTime = self::requiredTime($row['jam_awal'] ?? null, "Jam awal baris {$rowNumber}");
            $endTime = self::requiredTime($row['jam_akhir'] ?? null, "Jam akhir baris {$rowNumber}");
            $workHours = self::timeDifferenceHours($startTime, $endTime);
            $reportedWorkHours = self::requiredNonNegativeDecimal($row['jam_kerja'] ?? null, "Jam kerja baris {$rowNumber}");
            if (abs($reportedWorkHours - $workHours) > 0.01) {
                throw new DomainException("Baris {$rowNumber}: jam kerja harus sama dengan selisih jam awal dan akhir ({$workHours}).");
            }

            $hmStart = self::requiredNonNegativeDecimal($row['hm_awal'] ?? null, "HM awal baris {$rowNumber}");
            $hmEnd = self::requiredNonNegativeDecimal($row['hm_akhir'] ?? null, "HM akhir baris {$rowNumber}");
            if ($hmEnd < $hmStart) {
                throw new DomainException("Baris {$rowNumber}: HM akhir tidak boleh lebih kecil dari HM awal.");
            }
            $hmOperation = round($hmEnd - $hmStart, 2);
            $reportedHmOperation = self::requiredNonNegativeDecimal($row['hm_operasi'] ?? null, "HM operasi baris {$rowNumber}");
            if (abs($reportedHmOperation - $hmOperation) > 0.01) {
                throw new DomainException("Baris {$rowNumber}: HM operasi harus sama dengan HM akhir - HM awal ({$hmOperation}).");
            }
            if (abs($hmStart - $expectedHmStart) > 0.01) {
                $source = $itemCount === 0 ? 'HM Master Asset' : 'HM akhir baris sebelumnya';
                throw new DomainException(
                    "Baris {$rowNumber}: HM awal {$hmStart} tidak sama dengan {$source} {$expectedHmStart}."
                );
            }

            $verificationStatus = self::requiredText($row['status'] ?? null, "Verifikasi baris {$rowNumber}", 40);
            if (strcasecmp($verificationStatus, 'Terverifikasi') !== 0) {
                throw new DomainException("Baris {$rowNumber}: status harus Terverifikasi sebelum LHO difinalkan.");
            }
            $fuelLiters = self::nonNegativeDecimal($row['bbm'] ?? 0, "BBM baris {$rowNumber}");
            $site = self::optionalText($row['site'] ?? null, 190) ?? $defaultSite;
            $weather = self::optionalText($row['cuaca'] ?? null, 40);
            $notes = self::optionalText($row['keterangan'] ?? null, 500);

            $insert = $db->prepare(
                'INSERT INTO report_operation_logs
                 (report_id, report_item_position, asset_id, operation_date, operator_name, site,
                  start_time, end_time, work_hours, hm_start, hm_end, hm_operation, fuel_liters,
                  weather, verification_status, notes, previous_asset_hm, applied_asset_hm, created_by)
                 VALUES (:report_id, :position, :asset_id, :operation_date, :operator_name, :site,
                  :start_time, :end_time, :work_hours, :hm_start, :hm_end, :hm_operation, :fuel_liters,
                  :weather, :verification_status, :notes, :previous_asset_hm, :applied_asset_hm, :created_by)'
            );
            $insert->execute([
                ':report_id' => $reportId,
                ':position' => $position,
                ':asset_id' => $asset['asset_id'],
                ':operation_date' => $operationDate,
                ':operator_name' => $operator,
                ':site' => $site,
                ':start_time' => $startTime . ':00',
                ':end_time' => $endTime . ':00',
                ':work_hours' => $workHours,
                ':hm_start' => $hmStart,
                ':hm_end' => $hmEnd,
                ':hm_operation' => $hmOperation,
                ':fuel_liters' => $fuelLiters,
                ':weather' => $weather,
                ':verification_status' => $verificationStatus,
                ':notes' => $notes,
                ':previous_asset_hm' => $expectedHmStart,
                ':applied_asset_hm' => $hmEnd,
                ':created_by' => $actorId,
            ]);

            if ($fuelLiters > 0) {
                $fuelRate = $hmOperation > 0 ? round($fuelLiters / $hmOperation, 2) : 0.0;
                $insertFuel = $db->prepare(
                    'INSERT INTO fuel_logs
                     (asset_id, refuel_date, flowmeter_start, flowmeter_end, liters_issued,
                      current_hm_km, calculated_lph, baseline_lph, is_anomaly, driver_name)
                     VALUES (:asset_id, :refuel_date, 0, 0, :liters_issued,
                      :current_hm_km, :calculated_lph, 0, 0, :driver_name)'
                );
                $insertFuel->execute([
                    ':asset_id' => $asset['asset_id'],
                    ':refuel_date' => $operationDate . ' ' . $endTime . ':00',
                    ':liters_issued' => $fuelLiters,
                    ':current_hm_km' => $hmEnd,
                    ':calculated_lph' => $fuelRate,
                    ':driver_name' => $operator,
                ]);

                $insertFuelLink = $db->prepare(
                    'INSERT INTO report_fuel_integrations
                     (report_id, report_item_position, fuel_log_id, created_by)
                     VALUES (:report_id, :position, :fuel_log_id, :created_by)'
                );
                $insertFuelLink->execute([
                    ':report_id' => $reportId,
                    ':position' => $position,
                    ':fuel_log_id' => (int) $db->lastInsertId(),
                    ':created_by' => $actorId,
                ]);
                $fuelItemCount++;
            }

            $expectedHmStart = $hmEnd;
            $itemCount++;
            $totalWorkHours += $workHours;
            $totalHmOperation += $hmOperation;
            $totalFuelLiters += $fuelLiters;
        }

        $updateAsset = $db->prepare('UPDATE assets SET last_hm_km = :last_hm WHERE asset_id = :asset_id');
        $updateAsset->execute([':last_hm' => $expectedHmStart, ':asset_id' => $asset['asset_id']]);

        return [
            'type' => 'lho-operation',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => $itemCount,
            'assetId' => (string) $asset['asset_id'],
            'totalWorkHours' => round($totalWorkHours, 2),
            'totalHmOperation' => round($totalHmOperation, 2),
            'totalFuelLiters' => round($totalFuelLiters, 2),
            'fuelItemCount' => $fuelItemCount,
            'message' => "LHO berhasil difinalkan, {$itemCount} baris operasi masuk ke Produktivitas, {$fuelItemCount} transaksi BBM masuk ke Fuel, dan HM unit diperbarui.",
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

    private static function requiredNonNegativeDecimal($value, string $label): float
    {
        if (trim((string) $value) === '') {
            throw new DomainException("{$label} wajib diisi.");
        }
        return self::nonNegativeDecimal($value, $label);
    }

    private static function requiredPmInterval($value, int $rowNumber): int
    {
        $raw = trim((string) $value);
        if (!preg_match('/^(500|1000|1500|2000)(?:\s*HM)?$/i', $raw, $matches)) {
            throw new DomainException(
                "Baris {$rowNumber}: interval PM harus dipilih dari 500 HM, 1000 HM, 1500 HM, atau 2000 HM."
            );
        }
        return (int) $matches[1];
    }

    private static function pmPlanMatchesSnapshot(array $plan, array $snapshot): bool
    {
        foreach (['asset_id', 'last_service_date', 'status', 'warranty_status', 'planner_note'] as $key) {
            if ((string) ($plan[$key] ?? '') !== (string) ($snapshot[$key] ?? '')) {
                return false;
            }
        }
        if ((int) ($plan['interval_hm'] ?? 0) !== (int) ($snapshot['interval_hm'] ?? 0)) {
            return false;
        }
        foreach (['current_smr', 'last_service_hm', 'target_due_hm', 'variance_hm'] as $key) {
            if (abs((float) ($plan[$key] ?? 0) - (float) ($snapshot[$key] ?? 0)) >= 0.005) {
                return false;
            }
        }
        return true;
    }

    private static function workOrderMatchesSnapshot(array $workOrder, array $snapshot): bool
    {
        foreach (
            [
                'wo_id', 'asset_id', 'raw_location', 'issue_description', 'status', 'priority',
                'assigned_mechanic', 'reported_at', 'repair_started_at', 'closed_at',
            ] as $key
        ) {
            $actual = $workOrder[$key] ?? null;
            $expected = $snapshot[$key] ?? null;
            if (($actual === null ? null : (string) $actual) !== ($expected === null ? null : (string) $expected)) {
                return false;
            }
        }
        foreach (['location_id', 'downtime_minutes', 'is_downtime'] as $key) {
            $actual = $workOrder[$key] ?? null;
            $expected = $snapshot[$key] ?? null;
            if (($actual === null ? null : (int) $actual) !== ($expected === null ? null : (int) $expected)) {
                return false;
            }
        }
        return true;
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

    private static function requiredMonth($value, string $label): string
    {
        $raw = trim((string) $value);
        $month = DateTimeImmutable::createFromFormat('!Y-m', $raw);
        if (!$month || $month->format('Y-m') !== $raw) {
            throw new DomainException("{$label} wajib berupa periode bulan yang valid.");
        }
        return $raw;
    }

    private static function requiredTime($value, string $label): string
    {
        $raw = trim((string) $value);
        $time = DateTimeImmutable::createFromFormat('!H:i', $raw);
        if (!$time || $time->format('H:i') !== $raw) {
            throw new DomainException("{$label} wajib berupa waktu HH:MM yang valid.");
        }
        return $raw;
    }

    private static function timeDifferenceHours(string $start, string $end): float
    {
        [$startHour, $startMinute] = array_map('intval', explode(':', $start));
        [$endHour, $endMinute] = array_map('intval', explode(':', $end));
        $minutes = ($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute);
        if ($minutes < 0) {
            $minutes += 24 * 60;
        }
        return round($minutes / 60, 2);
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
