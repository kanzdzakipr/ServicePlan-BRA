<?php
declare(strict_types=1);

/**
 * Core Service: Cost Ledger & Expense Tracking (BR-09A / M10)
 * 
 * Provides verifiable financial ledger for equipment maintenance expenses,
 * drill-down traceability by Unit and Work Order, strict double-counting prevention,
 * and immutable reversal audit trails.
 */
final class CostLedger
{
    public const ALLOWED_CATEGORIES = [
        'Sparepart',
        'Maintenance',
        'External',
        'Capex',
        'Fuel',
        'Other'
    ];

    public const ALLOWED_SOURCE_TYPES = [
        'MANUAL',
        'PO_APPROVED',
        'SPPU_FINAL',
        'INVENTORY_OUT'
    ];

    /**
     * Ensure database table exists and seed baseline realistic records if empty.
     */
    public static function ensureTables(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS asset_cost_transactions (
            transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cost_code VARCHAR(50) NOT NULL UNIQUE,
            asset_id VARCHAR(100) NOT NULL,
            wo_id VARCHAR(50) NULL,
            transaction_date DATE NOT NULL,
            cost_category ENUM('Sparepart', 'Maintenance', 'External', 'Capex', 'Fuel', 'Other') NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
            vendor_name VARCHAR(150) NULL,
            reference_no VARCHAR(100) NULL,
            source_type ENUM('MANUAL', 'PO_APPROVED', 'SPPU_FINAL', 'INVENTORY_OUT') NOT NULL DEFAULT 'MANUAL',
            source_id VARCHAR(100) NULL,
            location_id INT NULL,
            notes TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reversed_at TIMESTAMP NULL,
            reversed_by INT NULL,
            reversal_reason VARCHAR(255) NULL,
            KEY idx_cost_asset (asset_id, transaction_date),
            KEY idx_cost_wo (wo_id, transaction_date),
            KEY idx_cost_active (reversed_at, transaction_date),
            KEY idx_cost_location (location_id, reversed_at),
            KEY idx_cost_source (source_type, source_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Seed realistic baseline transactions if empty
        $count = (int) $db->query("SELECT COUNT(*) FROM asset_cost_transactions")->fetchColumn();
        if ($count === 0) {
            self::seedBaselineTransactions($db);
        }
    }

    private static function seedBaselineTransactions(PDO $db): void
    {
        $baseline = [
            [
                'cost_code' => 'CST-2026-0001',
                'asset_id' => 'DT-00049',
                'wo_id' => 'WO-26-101',
                'transaction_date' => '2026-08-09',
                'cost_category' => 'Sparepart',
                'description' => 'Penggantian Clutch Disc & Pressure Plate Kopling DT-00049',
                'amount' => 14850000.00,
                'vendor_name' => 'PT United Tractors Semesta',
                'reference_no' => 'PO-2026-0812',
                'source_type' => 'MANUAL',
                'source_id' => 'WO-26-101',
                'location_id' => 1, // Yard Duri
            ],
            [
                'cost_code' => 'CST-2026-0002',
                'asset_id' => 'DT-00049',
                'wo_id' => 'WO-26-101',
                'transaction_date' => '2026-08-09',
                'cost_category' => 'External',
                'description' => 'Jasa Bubut Flywheel & Kalibrasi Transmisi Heavy Duty',
                'amount' => 3500000.00,
                'vendor_name' => 'Bengkel Bubut Presisi Riau',
                'reference_no' => 'INV-2026-094',
                'source_type' => 'MANUAL',
                'source_id' => 'WO-26-101',
                'location_id' => 1,
            ],
            [
                'cost_code' => 'CST-2026-0003',
                'asset_id' => 'CS-41001',
                'wo_id' => 'WO-26-102',
                'transaction_date' => '2026-08-09',
                'cost_category' => 'Maintenance',
                'description' => 'Perbaikan Valve Blockage & Cleaning System Penabur CS-41001',
                'amount' => 8750000.00,
                'vendor_name' => 'XCMG Authorized Service Partner',
                'reference_no' => 'SPK-2026-XCMG-04',
                'source_type' => 'MANUAL',
                'source_id' => 'WO-26-102',
                'location_id' => 1,
            ],
            [
                'cost_code' => 'CST-2026-0004',
                'asset_id' => 'EXC-00007',
                'wo_id' => null,
                'transaction_date' => '2026-08-08',
                'cost_category' => 'Maintenance',
                'description' => 'Paket Filter & Oli Servis Berkala PM 500 HM EXC-00007',
                'amount' => 12500000.00,
                'vendor_name' => 'PT Traktor Nusantara',
                'reference_no' => 'PO-2026-0805',
                'source_type' => 'MANUAL',
                'source_id' => null,
                'location_id' => 1,
            ],
            [
                'cost_code' => 'CST-2026-0005',
                'asset_id' => 'EXC-00001',
                'wo_id' => null,
                'transaction_date' => '2026-07-25',
                'cost_category' => 'Sparepart',
                'description' => 'Penggantian Seal Kit Cylinder Boom & Arm Excavator',
                'amount' => 9200000.00,
                'vendor_name' => 'PT United Tractors Tbk',
                'reference_no' => 'PO-2026-0789',
                'source_type' => 'MANUAL',
                'source_id' => null,
                'location_id' => 1,
            ],
        ];

        $stmt = $db->prepare("INSERT IGNORE INTO asset_cost_transactions 
            (cost_code, asset_id, wo_id, transaction_date, cost_category, description, amount, 
             vendor_name, reference_no, source_type, source_id, location_id, created_by)
            VALUES 
            (:cost_code, :asset_id, :wo_id, :transaction_date, :cost_category, :description, :amount,
             :vendor_name, :reference_no, :source_type, :source_id, :location_id, 1)");

        foreach ($baseline as $row) {
            $stmt->execute($row);
        }
    }

    /**
     * Query cost transactions with multi-criteria filtering and location scoping.
     */
    public static function getTransactions(PDO $db, array $filters = [], ?int $locationId = null): array
    {
        $sql = "SELECT 
                    c.*,
                    a.asset_code,
                    a.make_model,
                    a.category AS asset_category,
                    a.serial_number,
                    l.location_name,
                    u_creator.full_name AS creator_name,
                    u_rev.full_name AS reverser_name
                FROM asset_cost_transactions c
                LEFT JOIN assets a ON c.asset_id = a.asset_id
                LEFT JOIN locations l ON COALESCE(c.location_id, a.current_location_id) = l.location_id
                LEFT JOIN users u_creator ON c.created_by = u_creator.user_id
                LEFT JOIN users u_rev ON c.reversed_by = u_rev.user_id
                WHERE 1=1";
        $params = [];

        if ($locationId !== null) {
            $sql .= " AND COALESCE(c.location_id, a.current_location_id) = :scope_location_id";
            $params[':scope_location_id'] = $locationId;
        }

        if (!empty($filters['asset_id'])) {
            $sql .= " AND c.asset_id = :asset_id";
            $params[':asset_id'] = trim((string)$filters['asset_id']);
        }

        if (!empty($filters['wo_id'])) {
            $sql .= " AND c.wo_id = :wo_id";
            $params[':wo_id'] = trim((string)$filters['wo_id']);
        }

        if (!empty($filters['cost_category']) && $filters['cost_category'] !== 'ALL') {
            $sql .= " AND c.cost_category = :cost_category";
            $params[':cost_category'] = trim((string)$filters['cost_category']);
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND c.transaction_date >= :start_date";
            $params[':start_date'] = trim((string)$filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND c.transaction_date <= :end_date";
            $params[':end_date'] = trim((string)$filters['end_date']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $sql .= " AND c.reversed_at IS NULL";
            } elseif ($filters['status'] === 'reversed') {
                $sql .= " AND c.reversed_at IS NOT NULL";
            }
        }

        $sql .= " ORDER BY c.transaction_date DESC, c.transaction_id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agregasi di server (menghindari double counting dan menghormati reversal)
        $totalActiveAmount = 0.0;
        $totalReversedAmount = 0.0;
        $activeCount = 0;
        $reversedCount = 0;
        $byCategory = [];

        foreach ($rows as &$r) {
            $amt = (float) $r['amount'];
            $cat = (string) $r['cost_category'];
            $isReversed = $r['reversed_at'] !== null;

            if ($isReversed) {
                $totalReversedAmount += $amt;
                $reversedCount++;
            } else {
                $totalActiveAmount += $amt;
                $activeCount++;
                $byCategory[$cat] = ($byCategory[$cat] ?? 0.0) + $amt;
            }

            // Normalisasi output
            $r['transactionId'] = (int) $r['transaction_id'];
            $r['costCode'] = (string) $r['cost_code'];
            $r['assetId'] = (string) $r['asset_id'];
            $r['woId'] = $r['wo_id'] ? (string) $r['wo_id'] : null;
            $r['date'] = (string) $r['transaction_date'];
            $r['category'] = $cat;
            $r['description'] = (string) $r['description'];
            $r['amount'] = $amt;
            $r['formattedAmount'] = 'Rp ' . number_format($amt, 0, ',', '.');
            $r['vendor'] = (string) ($r['vendor_name'] ?? '');
            $r['referenceNo'] = (string) ($r['reference_no'] ?? '');
            $r['sourceType'] = (string) $r['source_type'];
            $r['sourceId'] = (string) ($r['source_id'] ?? '');
            $r['isReversed'] = $isReversed;
            $r['reversedAt'] = $r['reversed_at'];
            $r['reversalReason'] = $r['reversal_reason'];
        }
        unset($r);

        return [
            'transactions' => $rows,
            'summary' => [
                'totalActiveAmount' => $totalActiveAmount,
                'formattedTotalActive' => 'Rp ' . number_format($totalActiveAmount, 0, ',', '.'),
                'totalReversedAmount' => $totalReversedAmount,
                'activeCount' => $activeCount,
                'reversedCount' => $reversedCount,
                'totalCount' => count($rows),
                'byCategory' => $byCategory,
            ]
        ];
    }

    /**
     * Create a cost transaction with strict data validation.
     */
    public static function createTransaction(PDO $db, array $data, array $user): array
    {
        $assetId = trim((string)($data['asset_id'] ?? ''));
        if ($assetId === '') {
            throw new InvalidArgumentException('Unit aset (asset_id) wajib dipilih.');
        }

        // Validate asset existence and location
        $assetStmt = $db->prepare("SELECT asset_id, asset_code, current_location_id FROM assets WHERE asset_id = :id");
        $assetStmt->execute([':id' => $assetId]);
        $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$asset) {
            throw new InvalidArgumentException("Aset '{$assetId}' tidak ditemukan.");
        }

        $woId = isset($data['wo_id']) && trim((string)$data['wo_id']) !== ''
            ? trim((string)$data['wo_id'])
            : null;

        if ($woId !== null) {
            $woStmt = $db->prepare("SELECT wo_id, asset_id FROM work_orders WHERE wo_id = :wo_id");
            $woStmt->execute([':wo_id' => $woId]);
            $wo = $woStmt->fetch(PDO::FETCH_ASSOC);
            if (!$wo) {
                throw new InvalidArgumentException("Work Order '{$woId}' tidak ditemukan.");
            }
        }

        $amount = (float)($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nilai biaya (amount) harus berupa angka positif lebih dari nol.');
        }

        $category = trim((string)($data['cost_category'] ?? ''));
        if (!in_array($category, self::ALLOWED_CATEGORIES, true)) {
            throw new InvalidArgumentException('Kategori biaya tidak valid. Pilihan: ' . implode(', ', self::ALLOWED_CATEGORIES));
        }

        $description = trim((string)($data['description'] ?? ''));
        if ($description === '') {
            throw new InvalidArgumentException('Uraian / deskripsi transaksi biaya wajib diisi.');
        }

        $date = trim((string)($data['transaction_date'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Format tanggal transaksi tidak valid (gunakan format YYYY-MM-DD).');
        }

        $sourceType = trim((string)($data['source_type'] ?? 'MANUAL'));
        if (!in_array($sourceType, self::ALLOWED_SOURCE_TYPES, true)) {
            $sourceType = 'MANUAL';
        }

        $vendor = trim((string)($data['vendor_name'] ?? ''));
        $refNo = trim((string)($data['reference_no'] ?? ''));
        $sourceId = trim((string)($data['source_id'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $locationId = !empty($data['location_id']) ? (int)$data['location_id'] : ($asset['current_location_id'] ? (int)$asset['current_location_id'] : null);

        // Generate custom or automatic cost code
        $costCode = trim((string)($data['cost_code'] ?? ''));
        if ($costCode === '') {
            $seq = (int) $db->query("SELECT COUNT(*) + 1 FROM asset_cost_transactions")->fetchColumn();
            $costCode = sprintf('CST-%s-%04d', date('Y'), $seq);
        }

        // Check duplicate cost_code
        $checkStmt = $db->prepare("SELECT transaction_id FROM asset_cost_transactions WHERE cost_code = :code");
        $checkStmt->execute([':code' => $costCode]);
        if ($checkStmt->fetch()) {
            $costCode .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $insertStmt = $db->prepare("INSERT INTO asset_cost_transactions 
            (cost_code, asset_id, wo_id, transaction_date, cost_category, description, amount, 
             currency, vendor_name, reference_no, source_type, source_id, location_id, notes, created_by)
            VALUES 
            (:cost_code, :asset_id, :wo_id, :transaction_date, :cost_category, :description, :amount,
             'IDR', :vendor_name, :reference_no, :source_type, :source_id, :location_id, :notes, :created_by)");

        $insertStmt->execute([
            ':cost_code' => $costCode,
            ':asset_id' => $assetId,
            ':wo_id' => $woId,
            ':transaction_date' => $date,
            ':cost_category' => $category,
            ':description' => $description,
            ':amount' => $amount,
            ':vendor_name' => $vendor !== '' ? $vendor : null,
            ':reference_no' => $refNo !== '' ? $refNo : null,
            ':source_type' => $sourceType,
            ':source_id' => $sourceId !== '' ? $sourceId : null,
            ':location_id' => $locationId,
            ':notes' => $notes !== '' ? $notes : null,
            ':created_by' => $user['id'] ?? null,
        ]);

        $newId = (int) $db->lastInsertId();

        return [
            'transactionId' => $newId,
            'costCode' => $costCode,
            'assetId' => $assetId,
            'woId' => $woId,
            'amount' => $amount,
            'formattedAmount' => 'Rp ' . number_format($amount, 0, ',', '.'),
            'category' => $category,
            'description' => $description,
            'date' => $date,
        ];
    }

    /**
     * Reverse / void a cost transaction without deleting history (audit-compliant).
     */
    public static function reverseTransaction(PDO $db, int $transactionId, string $reason, array $user): array
    {
        $stmt = $db->prepare("SELECT * FROM asset_cost_transactions WHERE transaction_id = :id");
        $stmt->execute([':id' => $transactionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new InvalidArgumentException("Transaksi biaya ID {$transactionId} tidak ditemukan.");
        }

        if ($row['reversed_at'] !== null) {
            throw new InvalidArgumentException("Transaksi biaya ini telah dibatalkan sebelumnya pada {$row['reversed_at']}.");
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException("Alasan pembatalan (reversal reason) wajib diisi untuk kepatuhan audit.");
        }

        $upStmt = $db->prepare("UPDATE asset_cost_transactions 
            SET reversed_at = CURRENT_TIMESTAMP, reversed_by = :user_id, reversal_reason = :reason
            WHERE transaction_id = :id");
        $upStmt->execute([
            ':user_id' => $user['id'] ?? null,
            ':reason' => $reason,
            ':id' => $transactionId,
        ]);

        return [
            'transactionId' => $transactionId,
            'costCode' => (string) $row['cost_code'],
            'amount' => (float) $row['amount'],
            'status' => 'REVERSED',
            'reversalReason' => $reason,
        ];
    }

    /**
     * Retrieve cost summary per asset.
     */
    public static function getSummaryByAsset(PDO $db, ?int $locationId = null): array
    {
        $sql = "SELECT 
                    a.asset_id,
                    a.asset_code,
                    a.make_model,
                    a.category,
                    a.serial_number,
                    l.location_name,
                    COALESCE(SUM(CASE WHEN c.reversed_at IS NULL THEN c.amount ELSE 0 END), 0) AS total_active_cost,
                    COALESCE(COUNT(CASE WHEN c.reversed_at IS NULL THEN 1 END), 0) AS transaction_count,
                    MAX(c.transaction_date) AS last_cost_date
                FROM assets a
                LEFT JOIN asset_cost_transactions c ON a.asset_id = c.asset_id
                LEFT JOIN locations l ON a.current_location_id = l.location_id
                WHERE 1=1";
        $params = [];

        if ($locationId !== null) {
            $sql .= " AND a.current_location_id = :location_id";
            $params[':location_id'] = $locationId;
        }

        $sql .= " GROUP BY a.asset_id ORDER BY total_active_cost DESC, a.asset_code ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['totalActiveCost'] = (float) $r['total_active_cost'];
            $r['formattedTotal'] = 'Rp ' . number_format($r['totalActiveCost'], 0, ',', '.');
            $r['transactionCount'] = (int) $r['transaction_count'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Retrieve cost summary specifically tied to a given Work Order.
     */
    public static function getSummaryByWo(PDO $db, string $woId): array
    {
        $stmt = $db->prepare("SELECT 
                    c.*,
                    u_creator.full_name AS creator_name
                FROM asset_cost_transactions c
                LEFT JOIN users u_creator ON c.created_by = u_creator.user_id
                WHERE c.wo_id = :wo_id
                ORDER BY c.transaction_date DESC, c.transaction_id DESC");
        $stmt->execute([':wo_id' => $woId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalActive = 0.0;
        $items = [];

        foreach ($rows as $r) {
            $amt = (float) $r['amount'];
            $isReversed = $r['reversed_at'] !== null;
            if (!$isReversed) {
                $totalActive += $amt;
            }

            $items[] = [
                'transactionId' => (int) $r['transaction_id'],
                'costCode' => (string) $r['cost_code'],
                'date' => (string) $r['transaction_date'],
                'category' => (string) $r['cost_category'],
                'description' => (string) $r['description'],
                'amount' => $amt,
                'formattedAmount' => 'Rp ' . number_format($amt, 0, ',', '.'),
                'vendor' => (string) ($r['vendor_name'] ?? '-'),
                'referenceNo' => (string) ($r['reference_no'] ?? '-'),
                'isReversed' => $isReversed,
                'reversalReason' => $r['reversal_reason'],
            ];
        }

        return [
            'woId' => $woId,
            'totalCost' => $totalActive,
            'formattedTotalCost' => 'Rp ' . number_format($totalActive, 0, ',', '.'),
            'itemCount' => count($items),
            'items' => $items,
        ];
    }
}
