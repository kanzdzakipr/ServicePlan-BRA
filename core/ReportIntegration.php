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

        $db->exec("CREATE TABLE IF NOT EXISTS report_asset_movement_integrations (
            integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            movement_id INT NULL,
            asset_id VARCHAR(100) NOT NULL,
            previous_location_id INT NULL,
            applied_location_id INT NOT NULL,
            previous_hm DECIMAL(10,2) NOT NULL DEFAULT 0,
            applied_hm DECIMAL(10,2) NOT NULL DEFAULT 0,
            applied_payload LONGTEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_report_asset_movement_report (report_id),
            UNIQUE KEY uq_report_asset_movement_record (movement_id),
            KEY idx_report_asset_movement_asset (asset_id, reversed_at),
            CONSTRAINT fk_report_asset_movement_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_asset_movement_record FOREIGN KEY (movement_id) REFERENCES asset_movements (movement_id) ON DELETE SET NULL,
            CONSTRAINT fk_report_asset_movement_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
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

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_request_items (
            id VARCHAR(100) PRIMARY KEY,
            spb_id VARCHAR(50) NOT NULL,
            part_number VARCHAR(100) NOT NULL,
            description VARCHAR(255) NULL,
            qty_requested INT NOT NULL DEFAULT 1,
            status VARCHAR(50) NOT NULL DEFAULT 'Menunggu Approval',
            KEY idx_purchase_request_items_spb (spb_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS report_purchase_request_integrations (
            integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            spb_id VARCHAR(50) NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            owns_purchase_request TINYINT(1) NOT NULL DEFAULT 1,
            applied_payload LONGTEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_report_purchase_request_report (report_id),
            KEY idx_report_purchase_request_spb (spb_id),
            KEY idx_report_purchase_request_asset (asset_id, reversed_at),
            CONSTRAINT fk_report_purchase_request_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_purchase_request_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
            ppb_id VARCHAR(50) PRIMARY KEY,
            spb_id VARCHAR(50) NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            wo_id VARCHAR(50) NOT NULL,
            vendor VARCHAR(190) NOT NULL,
            project VARCHAR(190) NOT NULL,
            quote_number VARCHAR(100) NULL,
            quote_date DATE NULL,
            delivery_due DATE NOT NULL,
            delivery_location VARCHAR(255) NOT NULL,
            subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
            tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            status ENUM('Submitted', 'Approved', 'Ordered', 'Received', 'Cancelled') NOT NULL DEFAULT 'Submitted',
            created_by INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_purchase_orders_spb (spb_id),
            KEY idx_purchase_orders_asset (asset_id, status),
            CONSTRAINT fk_purchase_orders_spb FOREIGN KEY (spb_id) REFERENCES purchase_requests (spb_id) ON DELETE RESTRICT,
            CONSTRAINT fk_purchase_orders_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
            id VARCHAR(100) PRIMARY KEY,
            ppb_id VARCHAR(50) NOT NULL,
            part_number VARCHAR(100) NOT NULL,
            description VARCHAR(255) NOT NULL,
            unit_measure VARCHAR(20) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(15,2) NOT NULL,
            total_price DECIMAL(15,2) NOT NULL,
            notes VARCHAR(255) NULL,
            KEY idx_purchase_order_items_ppb (ppb_id),
            CONSTRAINT fk_purchase_order_items_header FOREIGN KEY (ppb_id) REFERENCES purchase_orders (ppb_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS report_purchase_order_integrations (
            integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            ppb_id VARCHAR(50) NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            owns_purchase_order TINYINT(1) NOT NULL DEFAULT 1,
            applied_payload LONGTEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_report_purchase_order_report (report_id),
            KEY idx_report_purchase_order_ppb (ppb_id),
            KEY idx_report_purchase_order_asset (asset_id, reversed_at),
            CONSTRAINT fk_report_purchase_order_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_report_purchase_order_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS report_goods_receipt_integrations (
            receipt_integration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            report_item_position INT UNSIGNED NOT NULL,
            ppb_id VARCHAR(50) NOT NULL,
            spb_id VARCHAR(50) NOT NULL,
            purchase_order_item_id VARCHAR(100) NOT NULL,
            purchase_request_item_id VARCHAR(100) NULL,
            part_id INT NOT NULL,
            accepted_quantity INT UNSIGNED NOT NULL DEFAULT 0,
            damaged_quantity INT UNSIGNED NOT NULL DEFAULT 0,
            missing_quantity INT UNSIGNED NOT NULL DEFAULT 0,
            previous_order_status VARCHAR(40) NOT NULL,
            applied_order_status VARCHAR(40) NOT NULL,
            previous_request_status VARCHAR(40) NOT NULL,
            applied_request_status VARCHAR(40) NOT NULL,
            previous_item_status VARCHAR(50) NULL,
            applied_item_status VARCHAR(50) NULL,
            receipt_payload LONGTEXT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_goods_receipt_line (report_id, report_item_position),
            KEY idx_goods_receipt_order_item (ppb_id, purchase_order_item_id, reversed_at),
            KEY idx_goods_receipt_part (part_id, reversed_at),
            CONSTRAINT fk_goods_receipt_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_goods_receipt_part FOREIGN KEY (part_id) REFERENCES parts (part_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS procurement_monitoring_logs (
            monitoring_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL, report_item_position INT UNSIGNED NOT NULL,
            spb_id VARCHAR(50) NOT NULL, purchase_request_item_id VARCHAR(100) NOT NULL,
            ppb_id VARCHAR(50) NULL,
            previous_request_status VARCHAR(40) NOT NULL, applied_request_status VARCHAR(40) NOT NULL,
            previous_item_status VARCHAR(50) NOT NULL, applied_item_status VARCHAR(50) NOT NULL,
            previous_order_status VARCHAR(40) NULL, applied_order_status VARCHAR(40) NULL,
            monitoring_payload LONGTEXT NOT NULL, created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL, reversed_by INT NULL,
            UNIQUE KEY uq_procurement_monitoring_line (report_id, report_item_position),
            KEY idx_procurement_monitoring_spb (spb_id, reversed_at),
            CONSTRAINT fk_procurement_monitoring_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS parts_weekly_snapshots (
            snapshot_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL, report_item_position INT UNSIGNED NOT NULL,
            part_id INT NOT NULL, report_date DATE NOT NULL, yard VARCHAR(190) NOT NULL,
            week_number INT NOT NULL, report_year INT NOT NULL,
            incoming_total INT NOT NULL, outgoing_total INT NOT NULL,
            reported_balance INT NOT NULL, actual_balance INT NOT NULL, balance_variance INT NOT NULL,
            unit_price DECIMAL(15,2) NOT NULL, reported_value DECIMAL(15,2) NOT NULL,
            notes VARCHAR(500) NULL, created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL, reversed_by INT NULL,
            UNIQUE KEY uq_parts_weekly_line (report_id, report_item_position),
            KEY idx_parts_weekly_part (part_id, report_date, reversed_at),
            CONSTRAINT fk_parts_weekly_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_parts_weekly_part FOREIGN KEY (part_id) REFERENCES parts (part_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS calibration_records (
            calibration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            report_id CHAR(36) NOT NULL,
            report_item_position INT UNSIGNED NOT NULL,
            period_month CHAR(7) NOT NULL,
            location_id INT NOT NULL,
            report_date DATE NOT NULL,
            instrument_name VARCHAR(190) NOT NULL,
            identification_no VARCHAR(100) NOT NULL,
            brand_type VARCHAR(190) NOT NULL,
            planned_date DATE NOT NULL,
            performed_date DATE NOT NULL,
            execution_type VARCHAR(20) NOT NULL,
            calibration_result VARCHAR(40) NOT NULL,
            follow_up_status VARCHAR(40) NOT NULL,
            notes VARCHAR(500) NULL,
            prepared_by INT NOT NULL,
            checked_by INT NOT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            UNIQUE KEY uq_calibration_report_line (report_id, report_item_position),
            KEY idx_calibration_instrument (identification_no, performed_date, reversed_at),
            KEY idx_calibration_schedule (planned_date, follow_up_status, reversed_at),
            KEY idx_calibration_location (location_id, reversed_at),
            CONSTRAINT fk_calibration_report FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE RESTRICT,
            CONSTRAINT fk_calibration_location FOREIGN KEY (location_id) REFERENCES locations (location_id) ON DELETE RESTRICT,
            CONSTRAINT fk_calibration_prepared_by FOREIGN KEY (prepared_by) REFERENCES users (user_id) ON DELETE RESTRICT,
            CONSTRAINT fk_calibration_checked_by FOREIGN KEY (checked_by) REFERENCES users (user_id) ON DELETE RESTRICT
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
        if ($templateKey === 'mde-02') {
            return self::applyMde02($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'kalibrasi') {
            return self::applyCalibration($db, $reportId, $fields, $rows, $actorId);
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
        if ($templateKey === 'spb') {
            return self::applySpb($db, $reportId, $fields, $rows, $actorId);
        }
        if (in_array($templateKey, ['sppu', 'sppu-006-pf04-cs10'], true)) {
            return self::applyUrgentPartsRequest($db, $templateKey, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'ppb') {
            return self::applyPpb($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'procurement-monitoring') {
            return self::applyProcurementMonitoring($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'parts-weekly') {
            return self::applyPartsWeekly($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'bapp') {
            return self::applyGoodsReceipt($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'bukti-kirim') {
            return self::applyInternalDelivery($db, $reportId, $fields, $rows, $actorId);
        }
        if ($templateKey === 'bast-mde1') {
            return self::applyBastMde1($db, $reportId, $fields, $rows, $actorId);
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

        $purchaseRequestStatement = $db->prepare(
            "SELECT integration_id, spb_id, owns_purchase_request, applied_payload
             FROM report_purchase_request_integrations
             WHERE report_id = :report_id AND reversed_at IS NULL
             FOR UPDATE"
        );
        $purchaseRequestStatement->execute([':report_id' => $reportId]);
        $purchaseRequestIntegrations = $purchaseRequestStatement->fetchAll(PDO::FETCH_ASSOC);
        $purchaseRequestReversed = 0;
        $purchaseRequestDeleted = 0;
        $purchaseRequestPreserved = 0;

        foreach ($purchaseRequestIntegrations as $integration) {
            $requestStatement = $db->prepare(
                'SELECT spb_id, wo_id, asset_id, requested_by, urgency, status, requested_at
                 FROM purchase_requests WHERE spb_id = :spb_id FOR UPDATE'
            );
            $requestStatement->execute([':spb_id' => $integration['spb_id']]);
            $request = $requestStatement->fetch(PDO::FETCH_ASSOC);
            $ownsRequest = (int) $integration['owns_purchase_request'] === 1;
            $snapshot = json_decode((string) $integration['applied_payload'], true);

            $itemStatement = $db->prepare(
                'SELECT id, spb_id, part_number, description, qty_requested, status
                 FROM purchase_request_items WHERE spb_id = :spb_id ORDER BY id FOR UPDATE'
            );
            $itemStatement->execute([':spb_id' => $integration['spb_id']]);
            $items = $itemStatement->fetchAll(PDO::FETCH_ASSOC);

            $approvalStatement = $db->prepare(
                "SELECT COUNT(*) FROM approvals WHERE document_type = 'SPB' AND document_id = :spb_id"
            );
            $approvalStatement->execute([':spb_id' => $integration['spb_id']]);
            $hasApprovalActivity = (int) $approvalStatement->fetchColumn() > 0;

            if (
                $ownsRequest
                && $request
                && !$hasApprovalActivity
                && is_array($snapshot)
                && self::purchaseRequestMatchesSnapshot($request, $items, $snapshot)
            ) {
                $deleteItems = $db->prepare('DELETE FROM purchase_request_items WHERE spb_id = :spb_id');
                $deleteItems->execute([':spb_id' => $integration['spb_id']]);
                $deleteRequest = $db->prepare('DELETE FROM purchase_requests WHERE spb_id = :spb_id');
                $deleteRequest->execute([':spb_id' => $integration['spb_id']]);
                $purchaseRequestDeleted += $deleteRequest->rowCount();
            } elseif ($ownsRequest && $request) {
                // SPB yang sudah diproses logistik atau approval wajib dipertahankan.
                $purchaseRequestPreserved++;
            }

            $reversePurchaseRequest = $db->prepare(
                'UPDATE report_purchase_request_integrations
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE integration_id = :integration_id AND reversed_at IS NULL'
            );
            $reversePurchaseRequest->execute([
                ':actor_id' => $actorId,
                ':integration_id' => (int) $integration['integration_id'],
            ]);
            $purchaseRequestReversed += $reversePurchaseRequest->rowCount();
        }

        $purchaseOrderStatement = $db->prepare('SELECT integration_id, ppb_id, owns_purchase_order, applied_payload FROM report_purchase_order_integrations WHERE report_id = :report_id AND reversed_at IS NULL FOR UPDATE');
        $purchaseOrderStatement->execute([':report_id' => $reportId]);
        $purchaseOrderReversed = 0; $purchaseOrderDeleted = 0; $purchaseOrderPreserved = 0;
        foreach ($purchaseOrderStatement->fetchAll(PDO::FETCH_ASSOC) as $integration) {
            $orderStatement = $db->prepare('SELECT ppb_id, spb_id, asset_id, wo_id, vendor, project, quote_number, quote_date, delivery_due, delivery_location, subtotal, tax_amount, total_amount, status, created_by FROM purchase_orders WHERE ppb_id = :ppb_id FOR UPDATE');
            $orderStatement->execute([':ppb_id' => $integration['ppb_id']]);
            $order = $orderStatement->fetch(PDO::FETCH_ASSOC);
            $items = $db->prepare('SELECT id, ppb_id, part_number, description, unit_measure, quantity, unit_price, total_price, notes FROM purchase_order_items WHERE ppb_id = :ppb_id ORDER BY id FOR UPDATE');
            $items->execute([':ppb_id' => $integration['ppb_id']]);
            $snapshot = json_decode((string) $integration['applied_payload'], true);
            $owns = (int) $integration['owns_purchase_order'] === 1;
            if ($owns && $order && is_array($snapshot) && self::purchaseOrderMatchesSnapshot($order, $items->fetchAll(PDO::FETCH_ASSOC), $snapshot)) {
                $delete = $db->prepare('DELETE FROM purchase_orders WHERE ppb_id = :ppb_id');
                $delete->execute([':ppb_id' => $integration['ppb_id']]);
                $purchaseOrderDeleted += $delete->rowCount();
            } elseif ($owns && $order) {
                $purchaseOrderPreserved++;
            }
            $reverse = $db->prepare('UPDATE report_purchase_order_integrations SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id WHERE integration_id = :id AND reversed_at IS NULL');
            $reverse->execute([':actor_id'=>$actorId, ':id'=>(int)$integration['integration_id']]);
            $purchaseOrderReversed += $reverse->rowCount();
        }

        $goodsReceiptStatement = $db->prepare(
            'SELECT * FROM report_goods_receipt_integrations
             WHERE report_id = :report_id AND reversed_at IS NULL
             ORDER BY receipt_integration_id DESC FOR UPDATE'
        );
        $goodsReceiptStatement->execute([':report_id' => $reportId]);
        $goodsReceiptReversed = 0;
        foreach ($goodsReceiptStatement->fetchAll(PDO::FETCH_ASSOC) as $receipt) {
            if ($receipt['purchase_request_item_id'] !== null && $receipt['applied_item_status'] !== null) {
                $itemStatus = $db->prepare('SELECT status FROM purchase_request_items WHERE id = :id FOR UPDATE');
                $itemStatus->execute([':id' => $receipt['purchase_request_item_id']]);
                if ($itemStatus->fetchColumn() === $receipt['applied_item_status']) {
                    $db->prepare('UPDATE purchase_request_items SET status = :status WHERE id = :id')->execute([
                        ':status' => $receipt['previous_item_status'],
                        ':id' => $receipt['purchase_request_item_id'],
                    ]);
                }
            }

            $orderStatus = $db->prepare('SELECT status FROM purchase_orders WHERE ppb_id = :id FOR UPDATE');
            $orderStatus->execute([':id' => $receipt['ppb_id']]);
            if ($orderStatus->fetchColumn() === $receipt['applied_order_status']) {
                $db->prepare('UPDATE purchase_orders SET status = :status WHERE ppb_id = :id')->execute([
                    ':status' => $receipt['previous_order_status'],
                    ':id' => $receipt['ppb_id'],
                ]);
            }

            $requestStatus = $db->prepare('SELECT status FROM purchase_requests WHERE spb_id = :id FOR UPDATE');
            $requestStatus->execute([':id' => $receipt['spb_id']]);
            if ($requestStatus->fetchColumn() === $receipt['applied_request_status']) {
                $db->prepare('UPDATE purchase_requests SET status = :status WHERE spb_id = :id')->execute([
                    ':status' => $receipt['previous_request_status'],
                    ':id' => $receipt['spb_id'],
                ]);
            }

            $reverseReceipt = $db->prepare(
                'UPDATE report_goods_receipt_integrations
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE receipt_integration_id = :id AND reversed_at IS NULL'
            );
            $reverseReceipt->execute([
                ':actor_id' => $actorId,
                ':id' => (int) $receipt['receipt_integration_id'],
            ]);
            $goodsReceiptReversed += $reverseReceipt->rowCount();
        }

        $monitoringStatement=$db->prepare('SELECT * FROM procurement_monitoring_logs WHERE report_id=:report_id AND reversed_at IS NULL ORDER BY monitoring_log_id DESC FOR UPDATE');$monitoringStatement->execute([':report_id'=>$reportId]);$monitoringReversed=0;
        foreach($monitoringStatement->fetchAll(PDO::FETCH_ASSOC) as $log){
            $request=$db->prepare('SELECT status FROM purchase_requests WHERE spb_id=:id FOR UPDATE');$request->execute([':id'=>$log['spb_id']]);if($request->fetchColumn()===$log['applied_request_status'])$db->prepare('UPDATE purchase_requests SET status=:status WHERE spb_id=:id')->execute([':status'=>$log['previous_request_status'],':id'=>$log['spb_id']]);
            $item=$db->prepare('SELECT status FROM purchase_request_items WHERE id=:id FOR UPDATE');$item->execute([':id'=>$log['purchase_request_item_id']]);if($item->fetchColumn()===$log['applied_item_status'])$db->prepare('UPDATE purchase_request_items SET status=:status WHERE id=:id')->execute([':status'=>$log['previous_item_status'],':id'=>$log['purchase_request_item_id']]);
            if($log['ppb_id']!==null){$order=$db->prepare('SELECT status FROM purchase_orders WHERE ppb_id=:id FOR UPDATE');$order->execute([':id'=>$log['ppb_id']]);if($order->fetchColumn()===$log['applied_order_status'])$db->prepare('UPDATE purchase_orders SET status=:status WHERE ppb_id=:id')->execute([':status'=>$log['previous_order_status'],':id'=>$log['ppb_id']]);}
            $reverse=$db->prepare('UPDATE procurement_monitoring_logs SET reversed_at=CURRENT_TIMESTAMP,reversed_by=:actor WHERE monitoring_log_id=:id AND reversed_at IS NULL');$reverse->execute([':actor'=>$actorId,':id'=>$log['monitoring_log_id']]);$monitoringReversed+=$reverse->rowCount();
        }
        $weekly=$db->prepare('UPDATE parts_weekly_snapshots SET reversed_at=CURRENT_TIMESTAMP,reversed_by=:actor WHERE report_id=:report_id AND reversed_at IS NULL');$weekly->execute([':actor'=>$actorId,':report_id'=>$reportId]);$weeklyReversed=$weekly->rowCount();

        $calibration = $db->prepare(
            'UPDATE calibration_records
             SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor
             WHERE report_id = :report_id AND reversed_at IS NULL'
        );
        $calibration->execute([':actor' => $actorId, ':report_id' => $reportId]);
        $calibrationReversed = $calibration->rowCount();

        $assetMovementStatement = $db->prepare(
            'SELECT integration_id, movement_id, asset_id, previous_location_id, applied_location_id,
                    previous_hm, applied_hm
             FROM report_asset_movement_integrations
             WHERE report_id = :report_id AND reversed_at IS NULL
             FOR UPDATE'
        );
        $assetMovementStatement->execute([':report_id' => $reportId]);
        $assetMovementReversed = 0;
        $assetMovementDeleted = 0;
        $assetMovementPreserved = 0;
        foreach ($assetMovementStatement->fetchAll(PDO::FETCH_ASSOC) as $integration) {
            $assetStatement = $db->prepare(
                'SELECT current_location_id, last_hm_km FROM assets WHERE asset_id = :asset_id FOR UPDATE'
            );
            $assetStatement->execute([':asset_id' => $integration['asset_id']]);
            $asset = $assetStatement->fetch(PDO::FETCH_ASSOC);
            if (!$asset) {
                throw new DomainException('Unit BAST tidak ditemukan saat proses void.');
            }

            $movementId = $integration['movement_id'] !== null ? (int) $integration['movement_id'] : null;
            $hasLaterMovement = false;
            if ($movementId !== null) {
                $laterMovement = $db->prepare(
                    'SELECT COUNT(*) FROM asset_movements WHERE asset_id = :asset_id AND movement_id > :movement_id'
                );
                $laterMovement->execute([
                    ':asset_id' => $integration['asset_id'],
                    ':movement_id' => $movementId,
                ]);
                $hasLaterMovement = (int) $laterMovement->fetchColumn() > 0;
            }

            $currentLocation = $asset['current_location_id'] !== null ? (int) $asset['current_location_id'] : null;
            $locationUnchanged = $currentLocation === (int) $integration['applied_location_id'];
            $hmUnchanged = abs((float) $asset['last_hm_km'] - (float) $integration['applied_hm']) < 0.005;
            if (!$hasLaterMovement && $locationUnchanged && $hmUnchanged) {
                $restoreAsset = $db->prepare(
                    'UPDATE assets SET current_location_id = :location_id, last_hm_km = :last_hm WHERE asset_id = :asset_id'
                );
                $restoreAsset->bindValue(
                    ':location_id',
                    $integration['previous_location_id'] !== null ? (int) $integration['previous_location_id'] : null,
                    $integration['previous_location_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
                );
                $restoreAsset->bindValue(':last_hm', (string) $integration['previous_hm']);
                $restoreAsset->bindValue(':asset_id', $integration['asset_id']);
                $restoreAsset->execute();

                if ($movementId !== null) {
                    $deleteMovement = $db->prepare('DELETE FROM asset_movements WHERE movement_id = :movement_id');
                    $deleteMovement->execute([':movement_id' => $movementId]);
                    $assetMovementDeleted += $deleteMovement->rowCount();
                }
            } else {
                // Perpindahan lanjutan atau perubahan HM setelah BAST harus tetap dipertahankan.
                $assetMovementPreserved++;
            }

            $reverseMovement = $db->prepare(
                'UPDATE report_asset_movement_integrations
                 SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :actor_id
                 WHERE integration_id = :integration_id AND reversed_at IS NULL'
            );
            $reverseMovement->execute([
                ':actor_id' => $actorId,
                ':integration_id' => (int) $integration['integration_id'],
            ]);
            $assetMovementReversed += $reverseMovement->rowCount();
        }

        $totalReversed = $reversed + $inspectionReversed + $operationReversed + $fuelReversed
            + $pmReversed + $workOrderReversed + $purchaseRequestReversed + $purchaseOrderReversed
            + $goodsReceiptReversed + $monitoringReversed + $weeklyReversed + $assetMovementReversed
            + $calibrationReversed;
        $reversalType = 'inventory-reversal';
        $reversalMessage = null;
        if ($assetMovementReversed > 0) {
            $reversalType = 'asset-movement-reversal';
            $reversalMessage = $assetMovementPreserved > 0
                ? "Laporan BAST dibatalkan. {$assetMovementPreserved} perpindahan dipertahankan karena unit sudah mengalami perubahan lanjutan."
                : 'Laporan BAST dibatalkan, riwayat perpindahan dihapus, serta lokasi dan HM unit dipulihkan.';
        } elseif ($calibrationReversed > 0) {
            $reversalType = 'calibration-reversal';
            $reversalMessage = "Laporan kalibrasi dibatalkan dan {$calibrationReversed} record dinonaktifkan dari register Preventive Maintenance.";
        } elseif ($goodsReceiptReversed > 0) {
            $reversalType = 'goods-receipt-reversal';
            $reversalMessage = "Laporan BAPP berhasil dibatalkan. {$goodsReceiptReversed} baris penerimaan dan stok terkait dibalik, lalu status PPB/SPB dipulihkan jika belum diubah lagi.";
        } elseif ($inspectionReversed > 0) {
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
        } elseif ($purchaseRequestReversed > 0) {
            $reversalType = 'purchase-request-reversal';
            $reversalMessage = $purchaseRequestPreserved > 0
                ? "Laporan berhasil dibatalkan. {$purchaseRequestPreserved} SPB tetap dipertahankan karena sudah diproses logistik atau approval."
                : "Laporan berhasil dibatalkan dan {$purchaseRequestDeleted} SPB buatan laporan dihapus.";
        } elseif ($purchaseOrderReversed > 0) {
            $reversalType = 'purchase-order-reversal';
            $reversalMessage = $purchaseOrderPreserved > 0
                ? "Laporan berhasil dibatalkan. {$purchaseOrderPreserved} PPB tetap dipertahankan karena sudah diproses pengadaan."
                : "Laporan berhasil dibatalkan dan {$purchaseOrderDeleted} PPB buatan laporan dihapus.";
        } elseif ($monitoringReversed > 0) {
            $reversalType='procurement-monitoring-reversal';
            $reversalMessage="Laporan berhasil dibatalkan dan {$monitoringReversed} pembaruan status pengadaan dipulihkan jika belum diubah lagi.";
        } elseif ($weeklyReversed > 0) {
            $reversalType='parts-weekly-reversal';
            $reversalMessage="Laporan berhasil dibatalkan dan {$weeklyReversed} snapshot stok mingguan dinonaktifkan tanpa mengubah stok aktual.";
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
            'purchaseRequestItemCount' => $purchaseRequestReversed,
            'purchaseRequestDeletedCount' => $purchaseRequestDeleted,
            'purchaseRequestPreservedCount' => $purchaseRequestPreserved,
            'purchaseOrderItemCount' => $purchaseOrderReversed,
            'purchaseOrderDeletedCount' => $purchaseOrderDeleted,
            'purchaseOrderPreservedCount' => $purchaseOrderPreserved,
            'goodsReceiptItemCount' => $goodsReceiptReversed,
            'procurementMonitoringItemCount' => $monitoringReversed,
            'partsWeeklyItemCount' => $weeklyReversed,
            'calibrationItemCount' => $calibrationReversed,
            'assetMovementItemCount' => $assetMovementReversed,
            'assetMovementDeletedCount' => $assetMovementDeleted,
            'assetMovementPreservedCount' => $assetMovementPreserved,
            'message' => $reversalMessage,
        ];
    }

    private static function applyCalibration(
        PDO $db,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existing = $db->prepare(
            'SELECT calibration_id FROM calibration_records WHERE report_id = :report_id ORDER BY calibration_id LIMIT 1'
        );
        $existing->execute([':report_id' => $reportId]);
        if ($existing->fetchColumn() !== false) {
            return [
                'type' => 'calibration-register',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => 0,
            ];
        }

        $reportNumber = self::requiredText($fields['nomor_laporan'] ?? null, 'Nomor laporan kalibrasi', 190);
        $period = trim((string) ($fields['periode'] ?? ''));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            throw new DomainException('Periode laporan kalibrasi harus berformat bulan dan tahun yang valid.');
        }
        $reportDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal pengesahan');
        $locationName = self::requiredText($fields['lokasi'] ?? null, 'Lokasi pengesahan', 190);
        $preparedName = self::requiredText($fields['dibuat_oleh'] ?? null, 'Dibuat oleh', 150);
        $checkedName = self::requiredText($fields['diperiksa_oleh'] ?? null, 'Diperiksa oleh', 150);

        $locationStatement = $db->prepare(
            'SELECT location_id FROM locations WHERE is_active = 1 AND location_name = :name ORDER BY location_id LIMIT 2'
        );
        $locationStatement->execute([':name' => $locationName]);
        $locationIds = $locationStatement->fetchAll(PDO::FETCH_COLUMN);
        if ($locationIds === []) {
            throw new DomainException("Lokasi {$locationName} tidak ditemukan pada Master Lokasi.");
        }
        if (count($locationIds) > 1) {
            throw new DomainException("Nama lokasi {$locationName} tidak unik pada Master Lokasi.");
        }
        $locationId = (int) $locationIds[0];

        $personStatement = $db->prepare(
            'SELECT user_id FROM users WHERE is_active = 1 AND full_name = :name ORDER BY user_id LIMIT 2'
        );
        $personStatement->execute([':name' => $preparedName]);
        $preparedIds = $personStatement->fetchAll(PDO::FETCH_COLUMN);
        if ($preparedIds === []) {
            throw new DomainException("Pembuat laporan {$preparedName} tidak ditemukan pada Master Personel.");
        }
        $personStatement->execute([':name' => $checkedName]);
        $checkedIds = $personStatement->fetchAll(PDO::FETCH_COLUMN);
        if ($checkedIds === []) {
            throw new DomainException("Pemeriksa {$checkedName} tidak ditemukan pada Master Personel.");
        }

        $allowedExecutionTypes = ['Intern', 'Ekstern'];
        $allowedResults = ['Memenuhi', 'Tidak memenuhi'];
        $allowedFollowUps = ['Selesai', 'Perlu perbaikan', 'Kalibrasi ulang', 'Menunggu sertifikat'];
        $preparedRows = [];
        $identifications = [];
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) continue;
            $line = $position + 1;
            $instrumentName = self::requiredText($row['nama'] ?? null, "Nama alat baris {$line}", 190);
            $identification = self::requiredText($row['identifikasi'] ?? null, "Nomor identifikasi baris {$line}", 100);
            $brandType = self::requiredText($row['merk'] ?? null, "Merk/type baris {$line}", 190);
            $plannedDate = self::requiredDate($row['rencana'] ?? null, "Rencana kalibrasi baris {$line}");
            $performedDate = self::requiredDate($row['pelaksanaan'] ?? null, "Tanggal pelaksanaan baris {$line}");
            $executionType = self::requiredText($row['jenis'] ?? null, "Pelaksanaan kalibrasi baris {$line}", 20);
            $result = self::requiredText($row['hasil'] ?? null, "Hasil kalibrasi baris {$line}", 40);
            $followUp = self::requiredText($row['tindak_lanjut'] ?? null, "Tindak lanjut baris {$line}", 40);
            if (!in_array($executionType, $allowedExecutionTypes, true)) {
                throw new DomainException("Baris {$line}: jenis pelaksanaan kalibrasi tidak dikenali.");
            }
            if (!in_array($result, $allowedResults, true)) {
                throw new DomainException("Baris {$line}: hasil kalibrasi tidak dikenali.");
            }
            if (!in_array($followUp, $allowedFollowUps, true)) {
                throw new DomainException("Baris {$line}: tindak lanjut kalibrasi tidak dikenali.");
            }
            $identityKey = mb_strtolower($identification);
            if (isset($identifications[$identityKey])) {
                throw new DomainException("Nomor identifikasi {$identification} dicatat lebih dari sekali dalam laporan yang sama.");
            }
            $identifications[$identityKey] = true;
            $preparedRows[] = [
                'position' => $position,
                'instrumentName' => $instrumentName,
                'identification' => $identification,
                'brandType' => $brandType,
                'plannedDate' => $plannedDate,
                'performedDate' => $performedDate,
                'executionType' => $executionType,
                'result' => $result,
                'followUp' => $followUp,
                'notes' => self::optionalText($row['keterangan'] ?? null, 500),
            ];
        }
        if ($preparedRows === []) {
            throw new DomainException('Laporan kalibrasi harus memiliki sedikitnya satu alat ukur.');
        }

        $insert = $db->prepare(
            'INSERT INTO calibration_records
             (report_id, report_item_position, period_month, location_id, report_date,
              instrument_name, identification_no, brand_type, planned_date, performed_date,
              execution_type, calibration_result, follow_up_status, notes,
              prepared_by, checked_by, created_by)
             VALUES
             (:report_id, :position, :period, :location_id, :report_date,
              :instrument_name, :identification_no, :brand_type, :planned_date, :performed_date,
              :execution_type, :result, :follow_up, :notes,
              :prepared_by, :checked_by, :created_by)'
        );
        foreach ($preparedRows as $row) {
            $insert->execute([
                ':report_id' => $reportId,
                ':position' => $row['position'],
                ':period' => $period,
                ':location_id' => $locationId,
                ':report_date' => $reportDate,
                ':instrument_name' => $row['instrumentName'],
                ':identification_no' => $row['identification'],
                ':brand_type' => $row['brandType'],
                ':planned_date' => $row['plannedDate'],
                ':performed_date' => $row['performedDate'],
                ':execution_type' => $row['executionType'],
                ':result' => $row['result'],
                ':follow_up' => $row['followUp'],
                ':notes' => $row['notes'],
                ':prepared_by' => (int) $preparedIds[0],
                ':checked_by' => (int) $checkedIds[0],
                ':created_by' => $actorId,
            ]);
        }

        return [
            'type' => 'calibration-register',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => count($preparedRows),
            'reportNumber' => $reportNumber,
            'message' => "Laporan {$reportNumber} berhasil difinalkan dan " . count($preparedRows)
                . ' alat masuk ke Register Kalibrasi Preventive Maintenance.',
        ];
    }

    private static function applyBastMde1(
        PDO $db,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existingStatement = $db->prepare(
            'SELECT movement_id FROM report_asset_movement_integrations WHERE report_id = :report_id LIMIT 1'
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingMovementId = $existingStatement->fetchColumn();
        if ($existingMovementId !== false) {
            return [
                'type' => 'asset-movement',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => 1,
                'movementId' => $existingMovementId !== null ? (int) $existingMovementId : null,
            ];
        }

        $bastNumber = self::requiredText($fields['nomor_urut'] ?? null, 'Nomor BAST', 100);
        $movementDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal serah terima');
        $assetReference = self::requiredText($fields['kode_alat'] ?? null, 'Nomor kode alat', 100);
        $originName = self::requiredText($fields['project_asal'] ?? null, 'Lokasi/project asal', 190);
        $destinationName = self::requiredText($fields['project_tujuan'] ?? null, 'Lokasi/project tujuan', 190);
        $reportedCategory = self::requiredText($fields['jenis_alat'] ?? null, 'Jenis alat', 100);
        $movementType = self::requiredText($fields['jenis_serah_terima'] ?? null, 'Jenis serah terima', 50);
        $allowedMovementTypes = ['Pembelian baru', 'Mobilisasi', 'Sewa-menyewa', 'Pinjaman', 'Pemakaian karya terakhir'];
        if (!in_array($movementType, $allowedMovementTypes, true)) {
            throw new DomainException('Jenis serah terima BAST tidak dikenali.');
        }
        $reportedHm = self::requiredNonNegativeDecimal($fields['hm_om'] ?? null, 'HM/OM saat serah terima');

        $assetStatement = $db->prepare(
            'SELECT asset_id, asset_code, category, make_model, current_location_id, last_hm_km
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
            throw new DomainException("Unit {$assetReference} belum ada atau tidak aktif pada Master Asset.");
        }
        if (count($matchedAssets) > 1 && (string) $matchedAssets[0]['asset_id'] !== $assetReference) {
            throw new DomainException("Kode unit {$assetReference} cocok ke lebih dari satu aset. Pilih ID aset lengkap dari database.");
        }
        $asset = $matchedAssets[0];
        if (strcasecmp((string) $asset['category'], $reportedCategory) !== 0) {
            throw new DomainException('Jenis alat BAST tidak sesuai dengan Master Asset.');
        }
        if ($asset['current_location_id'] === null) {
            throw new DomainException('Lokasi unit pada Master Asset belum ditentukan. Perbarui Master Asset sebelum membuat BAST.');
        }
        if ($reportedHm + 0.005 < (float) $asset['last_hm_km']) {
            throw new DomainException('HM/OM BAST tidak boleh lebih kecil dari HM/KM Master Asset.');
        }

        $locationStatement = $db->prepare(
            'SELECT location_id, location_name FROM locations WHERE is_active = 1 AND location_name = :name LIMIT 1'
        );
        $locationStatement->execute([':name' => $originName]);
        $origin = $locationStatement->fetch(PDO::FETCH_ASSOC);
        if (!$origin) {
            throw new DomainException("Lokasi asal {$originName} tidak ditemukan pada Master Lokasi.");
        }
        $locationStatement->execute([':name' => $destinationName]);
        $destination = $locationStatement->fetch(PDO::FETCH_ASSOC);
        if (!$destination) {
            throw new DomainException("Lokasi tujuan {$destinationName} tidak ditemukan pada Master Lokasi.");
        }
        if ((int) $origin['location_id'] !== (int) $asset['current_location_id']) {
            throw new DomainException('Lokasi asal BAST tidak sama dengan lokasi unit saat ini pada Master Asset.');
        }
        if ((int) $origin['location_id'] === (int) $destination['location_id']) {
            throw new DomainException('Lokasi tujuan BAST harus berbeda dari lokasi asal.');
        }

        $checklist = [];
        $allowedConditions = ['Baik', 'Rusak', 'Kurang', 'Tidak ada'];
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) continue;
            $line = $position + 1;
            $condition = self::requiredText($row['kondisi'] ?? null, "Kondisi baris {$line}", 40);
            if (!in_array($condition, $allowedConditions, true)) {
                throw new DomainException("Kondisi kelengkapan baris {$line} tidak dikenali.");
            }
            $checklist[] = [
                'item' => self::requiredText($row['item'] ?? null, "Item kelengkapan baris {$line}", 190),
                'quantity' => self::requiredNonNegativeInteger($row['jumlah'] ?? null, "Jumlah baris {$line}", false),
                'condition' => $condition,
                'notes' => self::optionalText($row['keterangan'] ?? null, 500),
            ];
        }
        if ($checklist === []) {
            throw new DomainException('BAST harus memiliki sedikitnya satu baris kelengkapan atau catatan serah terima.');
        }

        $snapshot = [
            'bast_number' => $bastNumber,
            'asset_id' => (string) $asset['asset_id'],
            'from_location_id' => (int) $origin['location_id'],
            'to_location_id' => (int) $destination['location_id'],
            'movement_date' => $movementDate . ' 00:00:00',
            'previous_hm' => round((float) $asset['last_hm_km'], 2),
            'applied_hm' => $reportedHm,
            'movement_type' => $movementType,
            'project' => self::optionalText($fields['project'] ?? null, 190),
            'sender' => self::requiredText($fields['dari'] ?? null, 'Pihak penyerah', 190),
            'recipient' => self::requiredText($fields['kepada'] ?? null, 'Pihak penerima', 190),
            'contract_number' => self::optionalText($fields['nomor_kontrak'] ?? null, 100),
            'attachment_number' => self::optionalText($fields['lampiran'] ?? null, 100),
            'checklist' => $checklist,
        ];
        $notes = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $insertMovement = $db->prepare(
            'INSERT INTO asset_movements
             (asset_id, from_location_id, to_location_id, bast_number, movement_date, notes, requested_by, approved_by)
             VALUES (:asset_id, :from_location_id, :to_location_id, :bast_number, :movement_date, :notes, :requested_by, NULL)'
        );
        $insertMovement->execute([
            ':asset_id' => $snapshot['asset_id'],
            ':from_location_id' => $snapshot['from_location_id'],
            ':to_location_id' => $snapshot['to_location_id'],
            ':bast_number' => $snapshot['bast_number'],
            ':movement_date' => $snapshot['movement_date'],
            ':notes' => $notes,
            ':requested_by' => $actorId,
        ]);
        $movementId = (int) $db->lastInsertId();

        $updateAsset = $db->prepare(
            'UPDATE assets SET current_location_id = :location_id, last_hm_km = :last_hm WHERE asset_id = :asset_id'
        );
        $updateAsset->execute([
            ':location_id' => $snapshot['to_location_id'],
            ':last_hm' => $snapshot['applied_hm'],
            ':asset_id' => $snapshot['asset_id'],
        ]);

        $insertIntegration = $db->prepare(
            'INSERT INTO report_asset_movement_integrations
             (report_id, movement_id, asset_id, previous_location_id, applied_location_id,
              previous_hm, applied_hm, applied_payload, created_by)
             VALUES (:report_id, :movement_id, :asset_id, :previous_location_id, :applied_location_id,
              :previous_hm, :applied_hm, :applied_payload, :created_by)'
        );
        $insertIntegration->execute([
            ':report_id' => $reportId,
            ':movement_id' => $movementId,
            ':asset_id' => $snapshot['asset_id'],
            ':previous_location_id' => $snapshot['from_location_id'],
            ':applied_location_id' => $snapshot['to_location_id'],
            ':previous_hm' => $snapshot['previous_hm'],
            ':applied_hm' => $snapshot['applied_hm'],
            ':applied_payload' => $notes,
            ':created_by' => $actorId,
        ]);

        return [
            'type' => 'asset-movement',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => 1,
            'movementId' => $movementId,
            'assetId' => $snapshot['asset_id'],
            'fromLocation' => $origin['location_name'],
            'toLocation' => $destination['location_name'],
            'message' => "BAST {$bastNumber} berhasil difinalkan. Lokasi {$snapshot['asset_id']} diperbarui ke {$destination['location_name']}.",
        ];
    }

    private static function applyGoodsReceipt(
        PDO $db,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existing = $db->prepare(
            'SELECT COUNT(*) FROM report_goods_receipt_integrations WHERE report_id = :report_id'
        );
        $existing->execute([':report_id' => $reportId]);
        $existingCount = (int) $existing->fetchColumn();
        if ($existingCount > 0) {
            return [
                'type' => 'goods-receipt',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => $existingCount,
            ];
        }

        $receiptNumber = self::requiredText($fields['nomor'] ?? null, 'Nomor BAPP', 190);
        $receiptDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal penerimaan');
        $sender = self::requiredText($fields['pengirim'] ?? null, 'Pengirim', 190);
        $ppbId = self::requiredText($fields['nomor_po'] ?? null, 'Nomor PPB/PO', 50);

        $orderStatement = $db->prepare(
            'SELECT po.ppb_id, po.spb_id, po.status, pr.status AS request_status
             FROM purchase_orders po
             INNER JOIN purchase_requests pr ON pr.spb_id = po.spb_id
             WHERE po.ppb_id = :ppb_id FOR UPDATE'
        );
        $orderStatement->execute([':ppb_id' => $ppbId]);
        $order = $orderStatement->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new DomainException("PPB/PO {$ppbId} tidak ditemukan.");
        }
        if (in_array($order['status'], ['Received', 'Cancelled'], true)) {
            throw new DomainException("PPB/PO {$ppbId} berstatus {$order['status']} dan tidak dapat menerima BAPP baru.");
        }

        $orderItemsStatement = $db->prepare(
            'SELECT id, part_number, description, unit_measure, quantity
             FROM purchase_order_items WHERE ppb_id = :ppb_id ORDER BY id FOR UPDATE'
        );
        $orderItemsStatement->execute([':ppb_id' => $ppbId]);
        $orderItems = [];
        foreach ($orderItemsStatement->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $orderItems[strtolower((string) $item['part_number'])] = $item;
        }
        if (!$orderItems) {
            throw new DomainException("PPB/PO {$ppbId} tidak memiliki item barang.");
        }

        $previousOrderStatus = (string) $order['status'];
        $previousRequestStatus = (string) $order['request_status'];
        $count = 0;
        $acceptedTotal = 0;

        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }
            $line = $position + 1;
            $partName = self::requiredText($row['nama'] ?? null, "Nama barang baris {$line}", 150);
            $unit = self::requiredText($row['satuan'] ?? null, "Satuan baris {$line}", 20);
            $reportedQuantity = self::requiredNonNegativeInteger($row['jumlah'] ?? null, "Jumlah baris {$line}", false);
            $accepted = self::requiredNonNegativeInteger($row['baik'] ?? null, "Jumlah baik baris {$line}", true);
            $damaged = self::requiredNonNegativeInteger($row['rusak'] ?? null, "Jumlah rusak baris {$line}", true);
            $missing = self::requiredNonNegativeInteger($row['kurang'] ?? null, "Jumlah kurang baris {$line}", true);
            if ($reportedQuantity !== $accepted + $damaged + $missing) {
                throw new DomainException("Jumlah baris {$line} harus sama dengan baik + rusak + kurang.");
            }

            $partStatement = $db->prepare(
                'SELECT part_id, part_number, part_name, unit_measure, stock_qty
                 FROM parts WHERE part_name = :part_name LIMIT 1 FOR UPDATE'
            );
            $partStatement->execute([':part_name' => $partName]);
            $part = $partStatement->fetch(PDO::FETCH_ASSOC);
            if (!$part) {
                throw new DomainException("Barang {$partName} pada baris {$line} belum ada pada Master Part.");
            }
            if (strcasecmp((string) $part['unit_measure'], $unit) !== 0) {
                throw new DomainException("Satuan barang baris {$line} tidak sesuai Master Part.");
            }

            $orderItem = $orderItems[strtolower((string) $part['part_number'])] ?? null;
            if (!$orderItem) {
                throw new DomainException("Part {$part['part_number']} pada baris {$line} tidak tercatat di PPB/PO {$ppbId}.");
            }
            if (strcasecmp((string) $orderItem['unit_measure'], $unit) !== 0) {
                throw new DomainException("Satuan barang baris {$line} tidak sesuai item PPB/PO {$ppbId}.");
            }

            $receivedStatement = $db->prepare(
                'SELECT COALESCE(SUM(accepted_quantity + damaged_quantity), 0)
                 FROM report_goods_receipt_integrations
                 WHERE ppb_id = :ppb_id AND purchase_order_item_id = :item_id AND reversed_at IS NULL'
            );
            $receivedStatement->execute([
                ':ppb_id' => $ppbId,
                ':item_id' => $orderItem['id'],
            ]);
            $previousPhysicalReceipt = (int) $receivedStatement->fetchColumn();
            if ($previousPhysicalReceipt + $accepted + $damaged > (int) $orderItem['quantity']) {
                throw new DomainException("Penerimaan part {$part['part_number']} melebihi jumlah pesanan PPB/PO {$ppbId}.");
            }

            $requestItemStatement = $db->prepare(
                'SELECT id, status FROM purchase_request_items
                 WHERE spb_id = :spb_id AND part_number = :part_number LIMIT 1 FOR UPDATE'
            );
            $requestItemStatement->execute([
                ':spb_id' => $order['spb_id'],
                ':part_number' => $part['part_number'],
            ]);
            $requestItem = $requestItemStatement->fetch(PDO::FETCH_ASSOC) ?: null;

            $insertReceipt = $db->prepare(
                'INSERT INTO report_goods_receipt_integrations
                 (report_id, report_item_position, ppb_id, spb_id, purchase_order_item_id,
                  purchase_request_item_id, part_id, accepted_quantity, damaged_quantity,
                  missing_quantity, previous_order_status, applied_order_status,
                  previous_request_status, applied_request_status, previous_item_status,
                  applied_item_status, receipt_payload, created_by)
                 VALUES
                 (:report_id, :position, :ppb_id, :spb_id, :order_item_id,
                  :request_item_id, :part_id, :accepted, :damaged, :missing,
                  :previous_order, :applied_order, :previous_request, :applied_request,
                  :previous_item, :applied_item, :payload, :created_by)'
            );
            $insertReceipt->execute([
                ':report_id' => $reportId,
                ':position' => $position,
                ':ppb_id' => $ppbId,
                ':spb_id' => $order['spb_id'],
                ':order_item_id' => $orderItem['id'],
                ':request_item_id' => $requestItem['id'] ?? null,
                ':part_id' => (int) $part['part_id'],
                ':accepted' => $accepted,
                ':damaged' => $damaged,
                ':missing' => $missing,
                ':previous_order' => $previousOrderStatus,
                ':applied_order' => $previousOrderStatus,
                ':previous_request' => $previousRequestStatus,
                ':applied_request' => $previousRequestStatus,
                ':previous_item' => $requestItem['status'] ?? null,
                ':applied_item' => $requestItem['status'] ?? null,
                ':payload' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                ':created_by' => $actorId,
            ]);

            if ($accepted > 0) {
                $stockBefore = (int) $part['stock_qty'];
                $stockAfter = $stockBefore + $accepted;
                $db->prepare('UPDATE parts SET stock_qty = :stock WHERE part_id = :part_id')->execute([
                    ':stock' => $stockAfter,
                    ':part_id' => (int) $part['part_id'],
                ]);
                $db->prepare(
                    'INSERT INTO inventory_transactions
                     (report_id, report_item_position, movement_type, transaction_date,
                      reference_number, counterparty, part_id, quantity, unit_measure,
                      stock_before, stock_after, notes, created_by)
                     VALUES
                     (:report_id, :position, \'IN\', :transaction_date, :reference_number,
                      :counterparty, :part_id, :quantity, :unit_measure, :stock_before,
                      :stock_after, :notes, :created_by)'
                )->execute([
                    ':report_id' => $reportId,
                    ':position' => $position,
                    ':transaction_date' => $receiptDate,
                    ':reference_number' => $receiptNumber,
                    ':counterparty' => $sender,
                    ':part_id' => (int) $part['part_id'],
                    ':quantity' => $accepted,
                    ':unit_measure' => $unit,
                    ':stock_before' => $stockBefore,
                    ':stock_after' => $stockAfter,
                    ':notes' => "BAPP {$receiptNumber}; PPB {$ppbId}; rusak {$damaged}; kurang {$missing}",
                    ':created_by' => $actorId,
                ]);
            }

            $acceptedTotal += $accepted;
            $count++;
        }

        if ($count === 0) {
            throw new DomainException('BAPP tidak memiliki baris barang yang dapat diterima.');
        }

        $fulfilmentStatement = $db->prepare(
            'SELECT poi.id, poi.quantity,
                    COALESCE(SUM(CASE WHEN gri.reversed_at IS NULL THEN gri.accepted_quantity ELSE 0 END), 0) AS accepted_total
             FROM purchase_order_items poi
             LEFT JOIN report_goods_receipt_integrations gri
               ON gri.ppb_id = poi.ppb_id AND gri.purchase_order_item_id = poi.id
             WHERE poi.ppb_id = :ppb_id
             GROUP BY poi.id, poi.quantity'
        );
        $fulfilmentStatement->execute([':ppb_id' => $ppbId]);
        $allFulfilled = true;
        foreach ($fulfilmentStatement->fetchAll(PDO::FETCH_ASSOC) as $fulfilment) {
            if ((int) $fulfilment['accepted_total'] < (int) $fulfilment['quantity']) {
                $allFulfilled = false;
                break;
            }
        }
        $appliedOrderStatus = $allFulfilled ? 'Received' : 'Ordered';
        $appliedRequestStatus = $allFulfilled ? 'Issued' : 'Ordered';

        $db->prepare('UPDATE purchase_orders SET status = :status WHERE ppb_id = :ppb_id')->execute([
            ':status' => $appliedOrderStatus,
            ':ppb_id' => $ppbId,
        ]);
        $db->prepare('UPDATE purchase_requests SET status = :status WHERE spb_id = :spb_id')->execute([
            ':status' => $appliedRequestStatus,
            ':spb_id' => $order['spb_id'],
        ]);

        $currentReceipts = $db->prepare(
            'SELECT receipt_integration_id, purchase_order_item_id, purchase_request_item_id
             FROM report_goods_receipt_integrations WHERE report_id = :report_id'
        );
        $currentReceipts->execute([':report_id' => $reportId]);
        foreach ($currentReceipts->fetchAll(PDO::FETCH_ASSOC) as $receipt) {
            $itemFulfilment = $db->prepare(
                'SELECT poi.quantity,
                        COALESCE(SUM(CASE WHEN gri.reversed_at IS NULL THEN gri.accepted_quantity ELSE 0 END), 0) AS accepted_total,
                        COALESCE(SUM(CASE WHEN gri.reversed_at IS NULL THEN gri.accepted_quantity + gri.damaged_quantity ELSE 0 END), 0) AS physical_total
                 FROM purchase_order_items poi
                 LEFT JOIN report_goods_receipt_integrations gri
                   ON gri.ppb_id = poi.ppb_id AND gri.purchase_order_item_id = poi.id
                 WHERE poi.id = :item_id GROUP BY poi.id, poi.quantity'
            );
            $itemFulfilment->execute([':item_id' => $receipt['purchase_order_item_id']]);
            $itemState = $itemFulfilment->fetch(PDO::FETCH_ASSOC);
            $appliedItemStatus = null;
            if ($receipt['purchase_request_item_id'] !== null && $itemState) {
                $appliedItemStatus = (int) $itemState['accepted_total'] >= (int) $itemState['quantity']
                    ? 'Tiba'
                    : ((int) $itemState['physical_total'] > 0 ? 'Parsial' : 'Tertunda');
                $db->prepare('UPDATE purchase_request_items SET status = :status WHERE id = :id')->execute([
                    ':status' => $appliedItemStatus,
                    ':id' => $receipt['purchase_request_item_id'],
                ]);
            }
            $db->prepare(
                'UPDATE report_goods_receipt_integrations
                 SET applied_order_status = :order_status,
                     applied_request_status = :request_status,
                     applied_item_status = :item_status
                 WHERE receipt_integration_id = :id'
            )->execute([
                ':order_status' => $appliedOrderStatus,
                ':request_status' => $appliedRequestStatus,
                ':item_status' => $appliedItemStatus,
                ':id' => (int) $receipt['receipt_integration_id'],
            ]);
        }

        return [
            'type' => 'goods-receipt',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => $count,
            'totalQuantity' => $acceptedTotal,
            'message' => "BAPP berhasil difinalkan. {$acceptedTotal} barang baik masuk ke stok dan status PPB/SPB diperbarui.",
        ];
    }

    private static function applyProcurementMonitoring(PDO $db, string $reportId, array $fields, array $rows, int $actorId): array
    {
        $existing = $db->prepare('SELECT COUNT(*) FROM procurement_monitoring_logs WHERE report_id = :report_id');
        $existing->execute([':report_id' => $reportId]);
        $existingCount = (int)$existing->fetchColumn();
        if ($existingCount > 0) return ['type'=>'procurement-monitoring','applied'=>false,'alreadyApplied'=>true,'itemCount'=>$existingCount];
        $requestStatusMap=['Menunggu Approval'=>'Submitted','Disetujui'=>'Approved','Dipesan'=>'Ordered','Dalam Pengiriman'=>'Ordered','Tiba'=>'Issued','Diserahkan'=>'Issued','Tertunda'=>'Submitted','Dibatalkan'=>'Draft'];
        $orderStatusMap=['Menunggu Approval'=>'Submitted','Disetujui'=>'Approved','Dipesan'=>'Ordered','Dalam Pengiriman'=>'Ordered','Tiba'=>'Received','Diserahkan'=>'Received','Tertunda'=>'Submitted','Dibatalkan'=>'Cancelled'];
        $count=0;
        foreach(array_values($rows) as $position=>$row){
            if(!is_array($row)||!self::rowHasContent($row))continue;$line=$position+1;
            $spbId=self::requiredText($row['nomor_spb']??null,"Nomor SPB baris {$line}",50);
            $woId=self::requiredText($row['nomor_jo']??null,"Nomor JO baris {$line}",50);
            $assetId=self::requiredText($row['id_unit']??null,"ID unit baris {$line}",100);
            $partNumber=self::requiredText($row['part_number']??null,"Part number baris {$line}",100);
            $quantity=self::requiredNonNegativeInteger($row['qty']??null,"Qty baris {$line}",false);
            $status=self::requiredText($row['status_pengadaan']??null,"Status pengadaan baris {$line}",40);
            if(!isset($requestStatusMap[$status]))throw new DomainException("Status pengadaan baris {$line} tidak dikenali.");
            $request=$db->prepare('SELECT spb_id,wo_id,asset_id,status FROM purchase_requests WHERE spb_id=:spb_id FOR UPDATE');$request->execute([':spb_id'=>$spbId]);$requestRow=$request->fetch(PDO::FETCH_ASSOC);
            if(!$requestRow)throw new DomainException("SPB {$spbId} pada baris {$line} tidak ditemukan.");
            if($requestRow['wo_id']!==$woId||$requestRow['asset_id']!==$assetId)throw new DomainException("Referensi SPB, JO, dan unit pada baris {$line} tidak konsisten.");
            $item=$db->prepare('SELECT id,status,qty_requested FROM purchase_request_items WHERE spb_id=:spb_id AND part_number=:part_number LIMIT 1 FOR UPDATE');$item->execute([':spb_id'=>$spbId,':part_number'=>$partNumber]);$itemRow=$item->fetch(PDO::FETCH_ASSOC);
            if(!$itemRow)throw new DomainException("Part {$partNumber} belum tercatat pada SPB {$spbId}.");
            if((int)$itemRow['qty_requested']!==$quantity)throw new DomainException("Qty part {$partNumber} pada baris {$line} tidak sesuai dengan SPB {$spbId}.");
            $order=$db->prepare('SELECT po.ppb_id,po.status FROM purchase_orders po INNER JOIN purchase_order_items poi ON poi.ppb_id=po.ppb_id WHERE po.spb_id=:spb_id AND poi.part_number=:part_number ORDER BY po.created_at DESC LIMIT 1 FOR UPDATE');$order->execute([':spb_id'=>$spbId,':part_number'=>$partNumber]);$orderRow=$order->fetch(PDO::FETCH_ASSOC)?:null;
            $appliedRequest=$requestStatusMap[$status];$appliedOrder=$orderRow?$orderStatusMap[$status]:null;
            $db->prepare('UPDATE purchase_requests SET status=:status WHERE spb_id=:spb_id')->execute([':status'=>$appliedRequest,':spb_id'=>$spbId]);
            $db->prepare('UPDATE purchase_request_items SET status=:status WHERE id=:id')->execute([':status'=>$status,':id'=>$itemRow['id']]);
            if($orderRow)$db->prepare('UPDATE purchase_orders SET status=:status WHERE ppb_id=:ppb_id')->execute([':status'=>$appliedOrder,':ppb_id'=>$orderRow['ppb_id']]);
            $insert=$db->prepare('INSERT INTO procurement_monitoring_logs(report_id,report_item_position,spb_id,purchase_request_item_id,ppb_id,previous_request_status,applied_request_status,previous_item_status,applied_item_status,previous_order_status,applied_order_status,monitoring_payload,created_by) VALUES(:report_id,:position,:spb_id,:item_id,:ppb_id,:previous_request,:applied_request,:previous_item,:applied_item,:previous_order,:applied_order,:payload,:created_by)');
            $insert->execute([':report_id'=>$reportId,':position'=>$position,':spb_id'=>$spbId,':item_id'=>$itemRow['id'],':ppb_id'=>$orderRow['ppb_id']??null,':previous_request'=>$requestRow['status'],':applied_request'=>$appliedRequest,':previous_item'=>$itemRow['status'],':applied_item'=>$status,':previous_order'=>$orderRow['status']??null,':applied_order'=>$appliedOrder,':payload'=>json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),':created_by'=>$actorId]);$count++;
        }
        if($count===0)throw new DomainException('Monitoring procurement tidak memiliki baris yang dapat diintegrasikan.');
        return ['type'=>'procurement-monitoring','applied'=>true,'alreadyApplied'=>false,'itemCount'=>$count,'message'=>"Laporan berhasil difinalkan dan {$count} status pengadaan diperbarui."];
    }

    private static function applyPartsWeekly(PDO $db,string $reportId,array $fields,array $rows,int $actorId):array
    {
        $existing=$db->prepare('SELECT COUNT(*) FROM parts_weekly_snapshots WHERE report_id=:report_id');$existing->execute([':report_id'=>$reportId]);$existingCount=(int)$existing->fetchColumn();
        if($existingCount>0)return ['type'=>'parts-weekly','applied'=>false,'alreadyApplied'=>true,'itemCount'=>$existingCount];
        $yard=self::requiredText($fields['yard']??null,'Yard',190);$week=self::requiredNonNegativeInteger($fields['pekan']??null,'Pekan',false);$year=self::requiredNonNegativeInteger($fields['tahun']??null,'Tahun',false);$date=self::requiredDate($fields['tanggal']??null,'Tanggal laporan');
        if($week>53||$year<2000||$year>2100)throw new DomainException('Pekan atau tahun laporan mingguan tidak valid.');$count=0;
        foreach(array_values($rows) as $position=>$row){if(!is_array($row)||!self::rowHasContent($row))continue;$line=$position+1;
            $name=self::requiredText($row['nama']??null,"Nama part baris {$line}",150);$unit=self::requiredText($row['satuan']??null,"Satuan baris {$line}",20);$price=self::requiredNonNegativeDecimal($row['harga']??null,"Harga baris {$line}");
            $inPast=self::requiredNonNegativeInteger($row['in_lalu']??null,"In lalu baris {$line}",true);$inNow=self::requiredNonNegativeInteger($row['in_ini']??null,"In pekan ini baris {$line}",true);$outPast=self::requiredNonNegativeInteger($row['out_lalu']??null,"Out lalu baris {$line}",true);$outNow=self::requiredNonNegativeInteger($row['out_ini']??null,"Out pekan ini baris {$line}",true);
            $inTotal=self::requiredNonNegativeInteger($row['in_total']??null,"In total baris {$line}",true);$outTotal=self::requiredNonNegativeInteger($row['out_total']??null,"Out total baris {$line}",true);$balance=self::requiredNonNegativeInteger($row['saldo']??null,"Saldo baris {$line}",true);$value=self::requiredNonNegativeDecimal($row['nilai_saldo']??null,"Nilai saldo baris {$line}");
            if($inTotal!==$inPast+$inNow||$outTotal!==$outPast+$outNow||$balance!==$inTotal-$outTotal||abs($value-$balance*$price)>=0.01)throw new DomainException("Perhitungan rekap parts baris {$line} tidak konsisten.");
            $part=$db->prepare('SELECT part_id,unit_measure,stock_qty FROM parts WHERE part_name=:name LIMIT 1 FOR UPDATE');$part->execute([':name'=>$name]);$master=$part->fetch(PDO::FETCH_ASSOC);if(!$master)throw new DomainException("Part {$name} belum ada pada Master Part.");if(strcasecmp($master['unit_measure'],$unit)!==0)throw new DomainException("Satuan part baris {$line} tidak cocok dengan Master Part.");
            $insert=$db->prepare('INSERT INTO parts_weekly_snapshots(report_id,report_item_position,part_id,report_date,yard,week_number,report_year,incoming_total,outgoing_total,reported_balance,actual_balance,balance_variance,unit_price,reported_value,notes,created_by) VALUES(:report_id,:position,:part_id,:date,:yard,:week,:year,:incoming,:outgoing,:reported,:actual,:variance,:price,:value,:notes,:created_by)');
            $actual=(int)$master['stock_qty'];$insert->execute([':report_id'=>$reportId,':position'=>$position,':part_id'=>$master['part_id'],':date'=>$date,':yard'=>$yard,':week'=>$week,':year'=>$year,':incoming'=>$inTotal,':outgoing'=>$outTotal,':reported'=>$balance,':actual'=>$actual,':variance'=>$balance-$actual,':price'=>$price,':value'=>$value,':notes'=>self::optionalText($row['keterangan']??null,500),':created_by'=>$actorId]);$count++;}
        if($count===0)throw new DomainException('Report Parts Weekly tidak memiliki baris yang dapat dicatat.');
        return ['type'=>'parts-weekly','applied'=>true,'alreadyApplied'=>false,'itemCount'=>$count,'message'=>"Laporan berhasil difinalkan dan {$count} snapshot stok mingguan dicatat tanpa mengubah stok aktual."];
    }

    private static function applyPpb(PDO $db, string $reportId, array $fields, array $rows, int $actorId): array
    {
        $existing = $db->prepare('SELECT ppb_id FROM report_purchase_order_integrations WHERE report_id = :report_id LIMIT 1');
        $existing->execute([':report_id' => $reportId]);
        $existingId = $existing->fetchColumn();
        if ($existingId !== false) {
            return ['type' => 'ppb', 'applied' => false, 'alreadyApplied' => true, 'itemCount' => 1, 'ppbId' => (string) $existingId];
        }

        $ppbId = self::requiredText($fields['nomor_ppb'] ?? null, 'Nomor PPB', 50);
        $spbId = self::requiredText($fields['nomor_spb'] ?? null, 'Nomor SPB', 50);
        $vendor = self::requiredText($fields['kepada'] ?? null, 'Vendor / Kepada Yth.', 190);
        $project = self::requiredText($fields['project'] ?? null, 'Project', 190);
        $deliveryDue = self::requiredDate($fields['batas_penyerahan'] ?? null, 'Batas penyerahan');
        $deliveryLocation = self::requiredText($fields['tempat_penyerahan'] ?? null, 'Tempat penyerahan', 255);
        $quoteNumber = self::optionalText($fields['nomor_penawaran'] ?? null, 100);
        $quoteDate = self::optionalDate($fields['tanggal_penawaran'] ?? null);

        $requestStatement = $db->prepare(
            "SELECT pr.spb_id, pr.wo_id, pr.asset_id, pr.status, w.status AS wo_status
             FROM purchase_requests pr
             INNER JOIN work_orders w ON w.wo_id = pr.wo_id
             WHERE pr.spb_id = :spb_id LIMIT 1 FOR UPDATE"
        );
        $requestStatement->execute([':spb_id' => $spbId]);
        $request = $requestStatement->fetch(PDO::FETCH_ASSOC);
        if (!$request) throw new DomainException("SPB {$spbId} belum tersedia pada menu Logistik.");
        if ($request['status'] === 'Closed' || in_array($request['wo_status'], ['Closed', 'Cancelled'], true)) {
            throw new DomainException("SPB {$spbId} atau Work Order terkait sudah ditutup.");
        }

        $items = [];
        $subtotal = 0.0;
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) continue;
            $line = $position + 1;
            $name = self::requiredText($row['nama'] ?? null, "Nama barang baris {$line}", 200);
            $partNumber = self::requiredText($row['sc'] ?? ($row['part_number'] ?? null), "No. SC / part number baris {$line}", 100);
            $unit = self::requiredText($row['satuan'] ?? null, "Satuan baris {$line}", 20);
            $quantity = self::requiredNonNegativeInteger($row['jumlah'] ?? null, "Jumlah baris {$line}", false);
            $unitPrice = self::requiredNonNegativeDecimal($row['harga'] ?? null, "Harga satuan baris {$line}");
            $reportedTotal = self::requiredNonNegativeDecimal($row['total'] ?? null, "Jumlah harga baris {$line}");
            $expectedTotal = round($quantity * $unitPrice, 2);
            if (abs($reportedTotal - $expectedTotal) >= 0.01) {
                throw new DomainException("Baris {$line}: jumlah harga harus sama dengan jumlah × harga satuan ({$expectedTotal}).");
            }
            $part = $db->prepare('SELECT part_number, part_name, unit_measure FROM parts WHERE part_number = :number OR part_name = :name ORDER BY CASE WHEN part_number = :exact THEN 0 ELSE 1 END LIMIT 1');
            $part->execute([':number' => $partNumber, ':name' => $name, ':exact' => $partNumber]);
            $master = $part->fetch(PDO::FETCH_ASSOC);
            if ($master) {
                if (strcasecmp((string) $master['unit_measure'], $unit) !== 0) {
                    throw new DomainException("Baris {$line}: satuan tidak cocok dengan Master Part {$master['part_number']}.");
                }
                $partNumber = (string) $master['part_number'];
                $name = (string) $master['part_name'];
            }
            $items[] = [
                'id' => 'RPPB-' . strtoupper(substr(hash('sha256', $reportId . '|' . $position), 0, 24)),
                'ppb_id' => $ppbId,
                'part_number' => $partNumber,
                'description' => $name,
                'unit_measure' => $unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $expectedTotal,
                'notes' => self::optionalText($row['keterangan'] ?? null, 255),
            ];
            $subtotal += $expectedTotal;
        }
        if ($items === []) throw new DomainException('PPB harus memiliki sedikitnya satu barang yang dipesan.');
        usort($items, static fn(array $a, array $b): int => strcmp($a['id'], $b['id']));
        $subtotal = round($subtotal, 2);
        $tax = round($subtotal * 0.11, 2);
        $snapshot = ['header' => [
            'ppb_id' => $ppbId, 'spb_id' => $spbId, 'asset_id' => (string) $request['asset_id'],
            'wo_id' => (string) $request['wo_id'], 'vendor' => $vendor, 'project' => $project,
            'quote_number' => $quoteNumber, 'quote_date' => $quoteDate, 'delivery_due' => $deliveryDue,
            'delivery_location' => $deliveryLocation, 'subtotal' => $subtotal, 'tax_amount' => $tax,
            'total_amount' => round($subtotal + $tax, 2), 'status' => 'Submitted', 'created_by' => $actorId,
        ], 'items' => $items];

        $orderStatement = $db->prepare('SELECT ppb_id, spb_id, asset_id, wo_id, vendor, project, quote_number, quote_date, delivery_due, delivery_location, subtotal, tax_amount, total_amount, status, created_by FROM purchase_orders WHERE ppb_id = :ppb_id FOR UPDATE');
        $orderStatement->execute([':ppb_id' => $ppbId]);
        $order = $orderStatement->fetch(PDO::FETCH_ASSOC);
        $ownsOrder = !$order;
        if ($order) {
            $orderItems = $db->prepare('SELECT id, ppb_id, part_number, description, unit_measure, quantity, unit_price, total_price, notes FROM purchase_order_items WHERE ppb_id = :ppb_id ORDER BY id FOR UPDATE');
            $orderItems->execute([':ppb_id' => $ppbId]);
            if (!self::purchaseOrderMatchesSnapshot($order, $orderItems->fetchAll(PDO::FETCH_ASSOC), $snapshot)) {
                throw new DomainException("Nomor {$ppbId} sudah digunakan oleh PPB lain dengan data berbeda.");
            }
        }
        if ($ownsOrder) {
            $header = $snapshot['header'];
            $insert = $db->prepare('INSERT INTO purchase_orders (ppb_id, spb_id, asset_id, wo_id, vendor, project, quote_number, quote_date, delivery_due, delivery_location, subtotal, tax_amount, total_amount, status, created_by) VALUES (:ppb_id,:spb_id,:asset_id,:wo_id,:vendor,:project,:quote_number,:quote_date,:delivery_due,:delivery_location,:subtotal,:tax_amount,:total_amount,:status,:created_by)');
            $params = []; foreach ($header as $key => $value) $params[':' . $key] = $value; $insert->execute($params);
            $insertItem = $db->prepare('INSERT INTO purchase_order_items (id, ppb_id, part_number, description, unit_measure, quantity, unit_price, total_price, notes) VALUES (:id,:ppb_id,:part_number,:description,:unit_measure,:quantity,:unit_price,:total_price,:notes)');
            foreach ($items as $item) { $params = []; foreach ($item as $key => $value) $params[':' . $key] = $value; $insertItem->execute($params); }
        }
        $ledger = $db->prepare('INSERT INTO report_purchase_order_integrations (report_id, ppb_id, asset_id, owns_purchase_order, applied_payload, created_by) VALUES (:report_id,:ppb_id,:asset_id,:owns,:payload,:created_by)');
        $ledger->execute([':report_id'=>$reportId, ':ppb_id'=>$ppbId, ':asset_id'=>$request['asset_id'], ':owns'=>$ownsOrder ? 1 : 0, ':payload'=>json_encode($snapshot, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR), ':created_by'=>$actorId]);
        return ['type'=>'ppb','applied'=>true,'alreadyApplied'=>false,'itemCount'=>count($items),'ppbId'=>$ppbId,'spbId'=>$spbId,'createdItemCount'=>$ownsOrder?count($items):0,'linkedItemCount'=>$ownsOrder?0:count($items),'message'=>$ownsOrder ? "Laporan berhasil difinalkan dan PPB {$ppbId} dengan ".count($items).' item dibuat.' : "Laporan berhasil ditautkan ke PPB {$ppbId} tanpa duplikasi."];
    }

    private static function applyUrgentPartsRequest(
        PDO $db,
        string $templateKey,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $mappedRows = [];
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }
            $line = $position + 1;
            $partName = self::requiredText($row['nama'] ?? null, "Nama part baris {$line}", 180);
            $partNumber = self::requiredText($row['pn'] ?? null, "Part number baris {$line}", 100);
            $unitMeasure = self::requiredText($row['satuan'] ?? null, "Satuan baris {$line}", 20);
            $quantity = self::requiredNonNegativeInteger($row['jumlah'] ?? null, "Jumlah baris {$line}", false);

            $partStatement = $db->prepare(
                'SELECT part_number, part_name, unit_measure
                 FROM parts
                 WHERE part_number = :part_number OR part_name = :part_name
                 ORDER BY CASE WHEN part_number = :exact_part_number THEN 0 ELSE 1 END
                 LIMIT 1'
            );
            $partStatement->execute([
                ':part_number' => $partNumber,
                ':part_name' => $partName,
                ':exact_part_number' => $partNumber,
            ]);
            $masterPart = $partStatement->fetch(PDO::FETCH_ASSOC);
            if (!$masterPart) {
                throw new DomainException("Part {$partNumber} pada baris {$line} belum ada pada Master Part.");
            }
            if (strcasecmp(trim((string) $masterPart['unit_measure']), $unitMeasure) !== 0) {
                throw new DomainException(
                    "Satuan {$unitMeasure} pada baris {$line} tidak cocok dengan Master Part {$masterPart['part_number']} ({$masterPart['unit_measure']})."
                );
            }

            $detailParts = [];
            foreach (['analisa', 'solusi', 'kelompok'] as $detailKey) {
                $detail = self::optionalText($row[$detailKey] ?? null, 120);
                if ($detail !== null) $detailParts[] = $detail;
            }
            if ($templateKey === 'sppu-006-pf04-cs10') {
                foreach (['analisa', 'dampak', 'tindak_lanjut'] as $fieldKey) {
                    $detail = self::optionalText($fields[$fieldKey] ?? null, 120);
                    if ($detail !== null) $detailParts[] = $detail;
                }
            }

            $mappedRows[] = [
                'nama' => (string) $masterPart['part_name'],
                'spesifikasi' => (string) $masterPart['part_number'],
                'satuan' => (string) $masterPart['unit_measure'],
                'jumlah' => $quantity,
                'keterangan' => mb_substr(implode(' | ', $detailParts), 0, 70),
                'status' => 'Diajukan',
            ];
        }
        if ($mappedRows === []) {
            throw new DomainException('SPPU harus memiliki sedikitnya satu part urgent.');
        }

        $priority = (string) ($fields['prioritas'] ?? 'Urgent');
        $urgency = $priority === 'Prioritas normal' ? 'Normal' : 'Emergency';
        $spbFields = [
            'nomor_spb' => self::requiredText($fields['nomor'] ?? null, 'Nomor SPPU', 50),
            'nomor_wo' => $fields['nomor_wo'] ?? null,
            'kode_unit' => $fields['kode_unit'] ?? null,
            'tanggal' => $fields['tanggal'] ?? null,
            'urgensi' => $urgency,
        ];
        $result = self::applySpb($db, $reportId, $spbFields, $mappedRows, $actorId);
        $result['type'] = 'urgent-parts-request';
        if (!($result['alreadyApplied'] ?? false)) {
            $result['message'] = "SPPU {$result['spbId']} berhasil dibuat sebagai permintaan part "
                . ($urgency === 'Emergency' ? 'darurat' : 'prioritas normal')
                . ' dengan ' . count($mappedRows) . ' item.';
        }
        return $result;
    }

    private static function applySpb(
        PDO $db,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existingStatement = $db->prepare(
            'SELECT spb_id FROM report_purchase_request_integrations WHERE report_id = :report_id LIMIT 1'
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingSpbId = $existingStatement->fetchColumn();
        if ($existingSpbId !== false) {
            return [
                'type' => 'spb',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => 1,
                'spbId' => (string) $existingSpbId,
            ];
        }

        $spbId = self::requiredText($fields['nomor_spb'] ?? null, 'Nomor SPB', 50);
        $workOrderId = self::requiredText(
            $fields['nomor_wo'] ?? ($fields['wo_id'] ?? null),
            'Work Order / JO',
            50
        );
        $reportedAsset = self::requiredText($fields['kode_unit'] ?? null, 'Kode unit', 100);
        $requestDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal permintaan');
        $urgencyInput = self::requiredText($fields['urgensi'] ?? 'Normal', 'Urgensi', 20);
        $urgency = match ($urgencyInput) {
            'Normal' => 'Normal',
            'Emergency', 'Mendesak' => 'Emergency',
            default => throw new DomainException('Urgensi SPB tidak dikenali.'),
        };

        $workOrderStatement = $db->prepare(
            'SELECT w.wo_id, w.asset_id, w.status, a.asset_code, a.is_active
             FROM work_orders w
             INNER JOIN assets a ON a.asset_id = w.asset_id
             WHERE w.wo_id = :wo_id
             LIMIT 1 FOR UPDATE'
        );
        $workOrderStatement->execute([':wo_id' => $workOrderId]);
        $workOrder = $workOrderStatement->fetch(PDO::FETCH_ASSOC);
        if (!$workOrder) {
            throw new DomainException("Work Order {$workOrderId} tidak ditemukan.");
        }
        if ((int) $workOrder['is_active'] !== 1) {
            throw new DomainException("Unit pada Work Order {$workOrderId} sudah tidak aktif.");
        }
        if (in_array((string) $workOrder['status'], ['Closed', 'Cancelled'], true)) {
            throw new DomainException("Work Order {$workOrderId} sudah ditutup atau dibatalkan.");
        }
        if (
            !hash_equals((string) $workOrder['asset_id'], $reportedAsset)
            && !hash_equals((string) ($workOrder['asset_code'] ?? ''), $reportedAsset)
        ) {
            throw new DomainException(
                "Kode unit {$reportedAsset} tidak sesuai dengan unit Work Order {$workOrderId} ({$workOrder['asset_id']})."
            );
        }

        $itemStatusMap = [
            'Diajukan' => 'Menunggu Approval',
            'Diproses' => 'Disetujui',
            'Tersedia' => 'Tiba',
            'Parsial' => 'Dalam Pengiriman',
            'Tidak tersedia' => 'Tertunda',
        ];
        $items = [];
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }
            $rowNumber = $position + 1;
            $partName = self::requiredText($row['nama'] ?? null, "Nama barang baris {$rowNumber}", 180);
            $partNumber = self::requiredText(
                $row['spesifikasi'] ?? ($row['part_number'] ?? null),
                "Spesifikasi / part number baris {$rowNumber}",
                100
            );
            $unitMeasure = self::requiredText($row['satuan'] ?? null, "Satuan baris {$rowNumber}", 20);
            $quantity = self::requiredNonNegativeInteger($row['jumlah'] ?? null, "Jumlah baris {$rowNumber}", false);
            $notes = self::optionalText($row['keterangan'] ?? null, 70);
            $requestedStatus = self::requiredText($row['status'] ?? 'Diajukan', "Status baris {$rowNumber}", 40);
            if (!isset($itemStatusMap[$requestedStatus])) {
                throw new DomainException("Status pemenuhan baris {$rowNumber} tidak dikenali.");
            }

            $partStatement = $db->prepare(
                'SELECT part_number, part_name, unit_measure
                 FROM parts
                 WHERE part_number = :part_number OR part_name = :part_name
                 ORDER BY CASE WHEN part_number = :exact_part_number THEN 0 ELSE 1 END
                 LIMIT 1'
            );
            $partStatement->execute([
                ':part_number' => $partNumber,
                ':part_name' => $partName,
                ':exact_part_number' => $partNumber,
            ]);
            $masterPart = $partStatement->fetch(PDO::FETCH_ASSOC);
            if ($masterPart) {
                if (strcasecmp(trim((string) $masterPart['unit_measure']), $unitMeasure) !== 0) {
                    throw new DomainException(
                        "Baris {$rowNumber}: satuan {$unitMeasure} tidak cocok dengan Master Part {$masterPart['part_number']} ({$masterPart['unit_measure']})."
                    );
                }
                $partNumber = (string) $masterPart['part_number'];
                $partName = (string) $masterPart['part_name'];
            }

            $description = $partName . ($notes ? " | {$notes}" : '');
            $items[] = [
                'id' => 'RSPB-' . strtoupper(substr(hash('sha256', $reportId . '|' . $position), 0, 24)),
                'spb_id' => $spbId,
                'part_number' => $partNumber,
                'description' => mb_substr($description, 0, 255),
                'qty_requested' => $quantity,
                'status' => $itemStatusMap[$requestedStatus],
            ];
        }
        if ($items === []) {
            throw new DomainException('SPB harus memiliki sedikitnya satu barang yang diminta.');
        }
        usort($items, static fn(array $left, array $right): int => strcmp($left['id'], $right['id']));

        $snapshot = [
            'header' => [
                'spb_id' => $spbId,
                'wo_id' => $workOrderId,
                'asset_id' => (string) $workOrder['asset_id'],
                'requested_by' => $actorId,
                'urgency' => $urgency,
                'status' => 'Submitted',
                'requested_at' => $requestDate . ' 00:00:00',
            ],
            'items' => $items,
        ];

        $requestStatement = $db->prepare(
            'SELECT spb_id, wo_id, asset_id, requested_by, urgency, status, requested_at
             FROM purchase_requests WHERE spb_id = :spb_id FOR UPDATE'
        );
        $requestStatement->execute([':spb_id' => $spbId]);
        $existingRequest = $requestStatement->fetch(PDO::FETCH_ASSOC);
        $ownsRequest = !$existingRequest;
        if ($existingRequest) {
            $existingItemsStatement = $db->prepare(
                'SELECT id, spb_id, part_number, description, qty_requested, status
                 FROM purchase_request_items WHERE spb_id = :spb_id ORDER BY id FOR UPDATE'
            );
            $existingItemsStatement->execute([':spb_id' => $spbId]);
            $existingItems = $existingItemsStatement->fetchAll(PDO::FETCH_ASSOC);
            if (!self::purchaseRequestMatchesSnapshot($existingRequest, $existingItems, $snapshot)) {
                throw new DomainException("Nomor {$spbId} sudah digunakan oleh SPB lain dengan data berbeda.");
            }
        }

        if ($ownsRequest) {
            $insertRequest = $db->prepare(
                'INSERT INTO purchase_requests
                 (spb_id, wo_id, asset_id, requested_by, urgency, status, requested_at)
                 VALUES (:spb_id, :wo_id, :asset_id, :requested_by, :urgency, :status, :requested_at)'
            );
            $insertRequest->execute([
                ':spb_id' => $snapshot['header']['spb_id'],
                ':wo_id' => $snapshot['header']['wo_id'],
                ':asset_id' => $snapshot['header']['asset_id'],
                ':requested_by' => $snapshot['header']['requested_by'],
                ':urgency' => $snapshot['header']['urgency'],
                ':status' => $snapshot['header']['status'],
                ':requested_at' => $snapshot['header']['requested_at'],
            ]);
            $insertItem = $db->prepare(
                'INSERT INTO purchase_request_items
                 (id, spb_id, part_number, description, qty_requested, status)
                 VALUES (:id, :spb_id, :part_number, :description, :qty_requested, :status)'
            );
            foreach ($items as $item) {
                $insertItem->execute([
                    ':id' => $item['id'],
                    ':spb_id' => $item['spb_id'],
                    ':part_number' => $item['part_number'],
                    ':description' => $item['description'],
                    ':qty_requested' => $item['qty_requested'],
                    ':status' => $item['status'],
                ]);
            }
        }

        $insertIntegration = $db->prepare(
            'INSERT INTO report_purchase_request_integrations
             (report_id, spb_id, asset_id, owns_purchase_request, applied_payload, created_by)
             VALUES (:report_id, :spb_id, :asset_id, :owns_purchase_request, :applied_payload, :created_by)'
        );
        $insertIntegration->execute([
            ':report_id' => $reportId,
            ':spb_id' => $spbId,
            ':asset_id' => $workOrder['asset_id'],
            ':owns_purchase_request' => $ownsRequest ? 1 : 0,
            ':applied_payload' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ':created_by' => $actorId,
        ]);

        return [
            'type' => 'spb',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => count($items),
            'spbId' => $spbId,
            'workOrderId' => $workOrderId,
            'createdItemCount' => $ownsRequest ? count($items) : 0,
            'linkedItemCount' => $ownsRequest ? 0 : count($items),
            'message' => $ownsRequest
                ? "Laporan berhasil difinalkan dan SPB {$spbId} dengan " . count($items) . ' item dibuat.'
                : "Laporan berhasil difinalkan dan ditautkan ke SPB {$spbId} tanpa duplikasi.",
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

    private static function applyMde02(
        PDO $db,
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
                'type' => 'mde-02-inspection',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => 1,
                'inspectionId' => (int) $existingInspectionId,
            ];
        }

        $inspectionNumber = self::requiredText($fields['nomor_urut'] ?? null, 'Nomor pemeriksaan', 100);
        $inspectionDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal pemeriksaan');
        $assetReference = self::requiredText($fields['kode_alat'] ?? null, 'Nomor kode alat', 100);
        $project = self::requiredText($fields['project'] ?? null, 'Project', 190);
        $reportedCategory = self::requiredText($fields['jenis_alat'] ?? null, 'Jenis alat', 100);
        $reportedHm = self::requiredNonNegativeDecimal($fields['hm_om'] ?? null, 'HM/OM saat pemeriksaan');
        $inspectorName = self::requiredText($fields['diperiksa_oleh'] ?? null, 'Pemeriksa', 150);
        $approverName = self::requiredText($fields['disetujui_oleh'] ?? null, 'Pihak yang menyetujui', 150);

        $assetStatement = $db->prepare(
            'SELECT asset_id, asset_code, category, make_model, serial_number, year_manufacture,
                    status, current_location_id, raw_location_notes, last_hm_km
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
            throw new DomainException("Unit {$assetReference} belum ada atau tidak aktif pada Master Asset.");
        }
        if (count($matchedAssets) > 1 && (string) $matchedAssets[0]['asset_id'] !== $assetReference) {
            throw new DomainException("Kode unit {$assetReference} cocok ke lebih dari satu aset. Pilih ID aset lengkap dari database.");
        }
        $asset = $matchedAssets[0];
        if (strcasecmp((string) $asset['category'], $reportedCategory) !== 0) {
            throw new DomainException('Jenis alat MDE-02 tidak sesuai dengan Master Asset.');
        }
        if (in_array((string) $asset['status'], ['ACCIDENT_HOLD', 'ACCIDENT HOLD', 'INACTIVE'], true)) {
            throw new DomainException("Unit {$asset['asset_id']} berstatus {$asset['status']} dan tidak dapat diproses melalui MDE-02.");
        }
        $currentHm = (float) $asset['last_hm_km'];
        if ($reportedHm + 0.005 < $currentHm) {
            throw new DomainException('HM/OM MDE-02 tidak boleh lebih kecil dari HM/KM Master Asset.');
        }

        $reportedModel = self::optionalText($fields['tipe_alat'] ?? null, 100);
        if ($reportedModel !== null && trim((string) $asset['make_model']) !== ''
            && strcasecmp($reportedModel, trim((string) $asset['make_model'])) !== 0) {
            throw new DomainException('Tipe alat MDE-02 tidak sesuai dengan Master Asset.');
        }
        $reportedSerial = self::optionalText($fields['nomor_seri'] ?? null, 100);
        if ($reportedSerial !== null && trim((string) $asset['serial_number']) !== ''
            && strcasecmp($reportedSerial, trim((string) $asset['serial_number'])) !== 0) {
            throw new DomainException('Nomor seri MDE-02 tidak sesuai dengan Master Asset.');
        }
        $reportedYear = trim((string) ($fields['tahun'] ?? ''));
        if ($reportedYear !== '' && $asset['year_manufacture'] !== null
            && (int) $reportedYear !== (int) $asset['year_manufacture']) {
            throw new DomainException('Tahun pembuatan MDE-02 tidak sesuai dengan Master Asset.');
        }

        $inspectorStatement = $db->prepare(
            'SELECT user_id, full_name FROM users WHERE is_active = 1 AND full_name = :full_name ORDER BY user_id LIMIT 1'
        );
        $inspectorStatement->execute([':full_name' => $inspectorName]);
        $inspector = $inspectorStatement->fetch(PDO::FETCH_ASSOC);
        if (!$inspector) {
            throw new DomainException("Pemeriksa {$inspectorName} tidak ditemukan pada Master Personel.");
        }
        $inspectorStatement->execute([':full_name' => $approverName]);
        if (!$inspectorStatement->fetch(PDO::FETCH_ASSOC)) {
            throw new DomainException("Pihak yang menyetujui {$approverName} tidak ditemukan pada Master Personel.");
        }

        $failures = [];
        $warnings = [];
        $details = [];
        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) continue;
            $line = $position + 1;
            $group = self::requiredText($row['kelompok'] ?? null, "Kelompok baris {$line}", 150);
            $item = self::requiredText($row['item'] ?? null, "Uraian pemeriksaan baris {$line}", 255);
            $condition = self::requiredText($row['kondisi'] ?? null, "Kondisi baris {$line}", 100);
            $classification = self::classifyP2hCondition($condition, $line);
            $notes = self::optionalText($row['keterangan'] ?? null, 500) ?? '';
            $details[] = [
                'group' => $group,
                'item' => $item,
                'condition' => $condition,
                'action' => $notes,
                'classification' => $classification,
            ];
            $finding = "{$group} - {$item}" . ($notes !== '' ? ": {$notes}" : '');
            if ($classification === 'FAIL') $failures[] = $finding;
            elseif ($classification === 'WARNING') $warnings[] = $finding;
        }
        if ($details === []) {
            throw new DomainException('MDE-02 harus memiliki sedikitnya satu item pemeriksaan.');
        }

        $overallResult = $failures !== [] ? 'FAIL' : ($warnings !== [] ? 'WARNING' : 'PASS');
        $statusLabel = $overallResult === 'FAIL'
            ? 'GAGAL (PERLU PEMERIKSAAN)'
            : ($overallResult === 'WARNING' ? 'LULUS DENGAN CATATAN' : 'LULUS (PASS)');
        $findings = array_merge($failures, $warnings);
        $summary = $findings !== []
            ? implode('; ', $findings)
            : 'Seluruh item MDE-02 dalam kondisi baik/normal.';

        $previousStatus = (string) $asset['status'];
        $appliedStatus = $previousStatus;
        if ($overallResult !== 'PASS' && $previousStatus !== 'BREAKDOWN') {
            $appliedStatus = 'INSPEKSI';
        }
        $appliedHm = max($reportedHm, $currentHm);

        $payload = [
            'id' => $inspectionNumber,
            'date' => $inspectionDate . ' 00:00',
            'unitId' => (string) $asset['asset_id'],
            'category' => (string) $asset['category'],
            'operator' => (string) $inspector['full_name'],
            'nrp' => '',
            'site' => $project,
            'hmStart' => $reportedHm,
            'hmEnd' => $reportedHm,
            'status' => $statusLabel,
            'criticalFails' => count($failures),
            'warnings' => count($warnings),
            'notes' => $summary,
            'details' => $details,
            'approvedBy' => $approverName,
            'source' => 'Laporan & Form',
            'sourceReportId' => $reportId,
            'schemaId' => 'mde-02',
        ];

        $insertInspection = $db->prepare(
            'INSERT INTO inspections
             (asset_id, inspector_id, inspection_date, current_hm_km, overall_result, findings_summary, payload_json)
             VALUES (:asset_id, :inspector_id, :inspection_date, :current_hm, :result, :summary, :payload)'
        );
        $insertInspection->execute([
            ':asset_id' => $asset['asset_id'],
            ':inspector_id' => (int) $inspector['user_id'],
            ':inspection_date' => $inspectionDate . ' 00:00:00',
            ':current_hm' => $reportedHm,
            ':result' => $overallResult,
            ':summary' => $summary,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
        $inspectionId = (int) $db->lastInsertId();

        $db->prepare(
            'UPDATE assets SET status = :status, last_hm_km = :last_hm WHERE asset_id = :asset_id'
        )->execute([
            ':status' => $appliedStatus,
            ':last_hm' => $appliedHm,
            ':asset_id' => $asset['asset_id'],
        ]);

        $db->prepare(
            'INSERT INTO report_inspection_integrations
             (report_id, inspection_id, asset_id, previous_asset_status, applied_asset_status,
              previous_hm, applied_hm, created_by)
             VALUES (:report_id, :inspection_id, :asset_id, :previous_status, :applied_status,
              :previous_hm, :applied_hm, :created_by)'
        )->execute([
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
            'type' => 'mde-02-inspection',
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => 1,
            'inspectionId' => $inspectionId,
            'result' => $overallResult,
            'assetId' => (string) $asset['asset_id'],
            'message' => "MDE-02 {$inspectionNumber} berhasil difinalkan dan masuk ke Riwayat Inspeksi & P2H ({$statusLabel}).",
        ];
    }

    private static function applyInternalDelivery(
        PDO $db,
        string $reportId,
        array $fields,
        array $rows,
        int $actorId
    ): array {
        $existingStatement = $db->prepare(
            'SELECT COUNT(*) FROM inventory_transactions WHERE report_id = :report_id'
        );
        $existingStatement->execute([':report_id' => $reportId]);
        $existingCount = (int) $existingStatement->fetchColumn();
        if ($existingCount > 0) {
            return [
                'type' => 'internal-delivery',
                'applied' => false,
                'alreadyApplied' => true,
                'itemCount' => $existingCount,
                'totalQuantity' => 0,
            ];
        }

        $transactionType = self::requiredText($fields['transaksi'] ?? null, 'Jenis transaksi', 20);
        if (!in_array($transactionType, ['Kirim', 'Terima'], true)) {
            throw new DomainException('Jenis transaksi harus Kirim atau Terima.');
        }
        $movementType = $transactionType === 'Kirim' ? 'OUT' : 'IN';
        $referenceNumber = self::requiredText($fields['nomor'] ?? null, 'Nomor bukti', 190);
        $transactionDate = self::requiredDate($fields['tanggal'] ?? null, 'Tanggal transaksi');
        $sender = self::requiredText($fields['dari'] ?? null, 'Asal barang', 190);
        $recipient = self::requiredText($fields['ke'] ?? null, 'Tujuan barang', 190);
        $counterparty = $movementType === 'OUT' ? $recipient : $sender;
        $itemCount = 0;
        $totalQuantity = 0;

        foreach (array_values($rows) as $position => $row) {
            if (!is_array($row) || !self::rowHasContent($row)) {
                continue;
            }
            $line = $position + 1;
            $partName = self::requiredText($row['nama'] ?? null, "Nama barang baris {$line}", 150);
            $unitMeasure = self::requiredText($row['satuan'] ?? null, "Satuan baris {$line}", 20);
            $quantity = self::requiredNonNegativeInteger($row['jumlah'] ?? null, "Jumlah baris {$line}", false);

            $partStatement = $db->prepare(
                'SELECT part_id, part_number, part_name, unit_measure, stock_qty
                 FROM parts WHERE part_name = :part_name LIMIT 1 FOR UPDATE'
            );
            $partStatement->execute([':part_name' => $partName]);
            $part = $partStatement->fetch(PDO::FETCH_ASSOC);
            if (!$part) {
                throw new DomainException("Barang {$partName} pada baris {$line} belum ada pada Master Part.");
            }
            if (strcasecmp(trim((string) $part['unit_measure']), $unitMeasure) !== 0) {
                throw new DomainException(
                    "Satuan {$unitMeasure} pada baris {$line} tidak cocok dengan Master Part {$part['part_number']} ({$part['unit_measure']})."
                );
            }

            $stockBefore = (int) $part['stock_qty'];
            if ($movementType === 'OUT' && $quantity > $stockBefore) {
                throw new DomainException(
                    "Stok {$part['part_number']} tidak cukup untuk pengiriman {$quantity} {$unitMeasure} pada baris {$line}."
                );
            }
            $stockAfter = $movementType === 'IN'
                ? $stockBefore + $quantity
                : $stockBefore - $quantity;
            $db->prepare('UPDATE parts SET stock_qty = :stock WHERE part_id = :part_id')->execute([
                ':stock' => $stockAfter,
                ':part_id' => (int) $part['part_id'],
            ]);

            $rowNotes = self::optionalText($row['keterangan'] ?? null, 350);
            $notes = "Bukti intern {$transactionType}; dari {$sender}; ke {$recipient}";
            if ($rowNotes !== null) $notes .= "; {$rowNotes}";
            $db->prepare(
                'INSERT INTO inventory_transactions
                 (report_id, report_item_position, movement_type, transaction_date,
                  reference_number, counterparty, part_id, quantity, unit_measure,
                  stock_before, stock_after, notes, created_by)
                 VALUES
                 (:report_id, :position, :movement_type, :transaction_date,
                  :reference_number, :counterparty, :part_id, :quantity, :unit_measure,
                  :stock_before, :stock_after, :notes, :created_by)'
            )->execute([
                ':report_id' => $reportId,
                ':position' => $position,
                ':movement_type' => $movementType,
                ':transaction_date' => $transactionDate,
                ':reference_number' => $referenceNumber,
                ':counterparty' => $counterparty,
                ':part_id' => (int) $part['part_id'],
                ':quantity' => $quantity,
                ':unit_measure' => $unitMeasure,
                ':stock_before' => $stockBefore,
                ':stock_after' => $stockAfter,
                ':notes' => $notes,
                ':created_by' => $actorId,
            ]);

            $itemCount++;
            $totalQuantity += $quantity;
        }

        if ($itemCount === 0) {
            throw new DomainException('Bukti Kirim/Terima tidak memiliki baris barang yang dapat diintegrasikan.');
        }

        return [
            'type' => 'internal-delivery',
            'movementType' => $movementType,
            'applied' => true,
            'alreadyApplied' => false,
            'itemCount' => $itemCount,
            'totalQuantity' => $totalQuantity,
            'message' => "Bukti {$transactionType} berhasil difinalkan. {$totalQuantity} unit dicatat sebagai transaksi stok {$movementType}.",
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

    private static function purchaseRequestMatchesSnapshot(array $request, array $items, array $snapshot): bool
    {
        $expectedHeader = is_array($snapshot['header'] ?? null) ? $snapshot['header'] : [];
        foreach (['spb_id', 'wo_id', 'asset_id', 'urgency', 'status', 'requested_at'] as $key) {
            $actual = $request[$key] ?? null;
            $expected = $expectedHeader[$key] ?? null;
            if (($actual === null ? null : (string) $actual) !== ($expected === null ? null : (string) $expected)) {
                return false;
            }
        }
        if ((int) ($request['requested_by'] ?? 0) !== (int) ($expectedHeader['requested_by'] ?? 0)) {
            return false;
        }

        $expectedItems = is_array($snapshot['items'] ?? null) ? array_values($snapshot['items']) : [];
        usort($items, static fn(array $left, array $right): int => strcmp((string) $left['id'], (string) $right['id']));
        usort($expectedItems, static fn(array $left, array $right): int => strcmp((string) $left['id'], (string) $right['id']));
        if (count($items) !== count($expectedItems)) {
            return false;
        }
        foreach ($items as $index => $item) {
            $expected = $expectedItems[$index];
            foreach (['id', 'spb_id', 'part_number', 'description', 'status'] as $key) {
                if ((string) ($item[$key] ?? '') !== (string) ($expected[$key] ?? '')) {
                    return false;
                }
            }
            if ((int) ($item['qty_requested'] ?? 0) !== (int) ($expected['qty_requested'] ?? 0)) {
                return false;
            }
        }
        return true;
    }

    private static function purchaseOrderMatchesSnapshot(array $order, array $items, array $snapshot): bool
    {
        $header = is_array($snapshot['header'] ?? null) ? $snapshot['header'] : [];
        foreach (['ppb_id','spb_id','asset_id','wo_id','vendor','project','quote_number','quote_date','delivery_due','delivery_location','status'] as $key) {
            $actual = $order[$key] ?? null; $expected = $header[$key] ?? null;
            if (($actual === null ? null : (string)$actual) !== ($expected === null ? null : (string)$expected)) return false;
        }
        if ((int)($order['created_by'] ?? 0) !== (int)($header['created_by'] ?? 0)) return false;
        foreach (['subtotal','tax_amount','total_amount'] as $key) {
            if (abs((float)($order[$key] ?? 0) - (float)($header[$key] ?? 0)) >= 0.01) return false;
        }
        $expectedItems = is_array($snapshot['items'] ?? null) ? array_values($snapshot['items']) : [];
        usort($items, static fn(array $a,array $b):int => strcmp((string)$a['id'],(string)$b['id']));
        usort($expectedItems, static fn(array $a,array $b):int => strcmp((string)$a['id'],(string)$b['id']));
        if (count($items) !== count($expectedItems)) return false;
        foreach ($items as $index => $item) {
            $expected = $expectedItems[$index];
            foreach (['id','ppb_id','part_number','description','unit_measure','notes'] as $key) {
                $actual = $item[$key] ?? null; $wanted = $expected[$key] ?? null;
                if (($actual === null ? null : (string)$actual) !== ($wanted === null ? null : (string)$wanted)) return false;
            }
            if ((int)$item['quantity'] !== (int)$expected['quantity']) return false;
            foreach (['unit_price','total_price'] as $key) if (abs((float)$item[$key] - (float)$expected[$key]) >= 0.01) return false;
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
