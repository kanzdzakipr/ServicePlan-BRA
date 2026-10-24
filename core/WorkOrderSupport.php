<?php
declare(strict_types=1);

require_once __DIR__ . '/CostLedger.php';

final class WorkOrderSupport
{
    public const PARTS_UNASSESSED = 'UNASSESSED';
    public const PARTS_REQUIRED = 'REQUIRED';
    public const PARTS_NOT_REQUIRED = 'NOT_REQUIRED';

    private const SERVICE_TYPES = [
        'INTERNAL_WORKSHOP',
        'EXTERNAL_WORKSHOP',
        'FIELD_SERVICE',
        'SPECIALIST_VENDOR',
    ];

    private const SERVICE_PRIORITIES = ['Normal', 'Urgent', 'Emergency'];
    private const SERVICE_STATUSES = ['Submitted', 'Assigned', 'In Progress', 'Completed', 'Cancelled'];

    /**
     * Normalize the extended WO form payload into database-ready values.
     * Supports both API snake_case and dashboard camelCase keys so the
     * transaction controller and offline sync route share one contract.
     */
    public static function normalizeTicketInput(array $input, array $asset): array
    {
        $meter = self::inputValue($input, 'current_hm_km', 'currentHmKm');
        if ($meter === null || $meter === '' || !is_numeric($meter) || !is_finite((float) $meter) || (float) $meter < 0) {
            throw new InvalidArgumentException('HM/KM aktual wajib berupa angka nol atau positif.');
        }

        $troubleAt = self::normalizeDateTime(self::inputValue($input, 'trouble_at', 'troubleAt'), true, 'Tanggal trouble');
        $repairStartedAt = self::normalizeDateTime(self::inputValue($input, 'repair_started_at', 'repairStartedAt'), false, 'Waktu mulai pekerjaan');
        $estimatedCompletionAt = self::normalizeDateTime(
            self::inputValue($input, 'estimated_completion_at', 'estimatedCompletionAt'),
            false,
            'Estimasi waktu selesai'
        );
        if ($repairStartedAt !== null && $estimatedCompletionAt !== null && $estimatedCompletionAt < $repairStartedAt) {
            throw new InvalidArgumentException('Estimasi waktu selesai tidak boleh lebih awal dari waktu mulai pekerjaan.');
        }

        $location = self::boundedText(self::inputValue($input, 'raw_location', 'rawLocation'), 255, true, 'Lokasi trouble');
        $forcedOperation = self::booleanValue(self::inputValue($input, 'is_forced_operation', 'isForcedOperation'));
        $isDowntime = $forcedOperation
            ? false
            : self::booleanValue(self::inputValue($input, 'is_downtime', 'isDowntime', true));
        $mitigation = self::boundedText(
            self::inputValue($input, 'mitigation_action', 'mitigationAction'),
            10000,
            $forcedOperation,
            'Tindakan mitigasi'
        );

        $mechanics = self::normalizeMechanics(
            self::inputValue($input, 'assigned_mechanics', 'assignedMechanics', $input['assigned_mechanic'] ?? null)
        );
        $mechanicLabel = $mechanics ? implode(', ', $mechanics) : 'Belum ada PIC';
        if (mb_strlen($mechanicLabel, 'UTF-8') > 100) {
            throw new InvalidArgumentException('Gabungan nama PIC mekanik maksimal 100 karakter.');
        }

        $equipmentType = self::boundedText(
            self::inputValue($input, 'equipment_type', 'equipmentType', $asset['category'] ?? $asset['type'] ?? null),
            100,
            false,
            'Jenis alat'
        );
        $hullNumber = self::boundedText(
            self::inputValue($input, 'hull_number', 'hullNumber', ($asset['hull_number'] ?? null) ?: (($asset['alias_name'] ?? null) ?: ($asset['asset_code'] ?? null))),
            100,
            false,
            'Nomor lambung'
        );

        $calculatedAssetStatus = self::assetStatusFor($forcedOperation, $isDowntime);
        $currentAssetStatus = strtoupper(trim((string) ($asset['status'] ?? '')));
        $assetStatus = in_array($currentAssetStatus, ['ACCIDENT_HOLD', 'INACTIVE'], true)
            ? $currentAssetStatus
            : $calculatedAssetStatus;

        return [
            'hull_number' => $hullNumber,
            'equipment_type' => $equipmentType,
            'current_hm_km' => round((float) $meter, 2),
            'trouble_at' => $troubleAt,
            'raw_location' => $location,
            'repair_started_at' => $repairStartedAt,
            'estimated_completion_at' => $estimatedCompletionAt,
            'is_forced_operation' => $forcedOperation ? 1 : 0,
            'is_downtime' => $isDowntime ? 1 : 0,
            'mitigation_action' => $mitigation,
            'assigned_mechanics' => $mechanics,
            'assigned_mechanics_json' => json_encode($mechanics, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'assigned_mechanic' => $mechanicLabel,
            'asset_status' => $assetStatus,
        ];
    }

    /** @return list<string> */
    public static function normalizeMechanics(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        if ($value === null || $value === '') return [];
        if (!is_array($value)) throw new InvalidArgumentException('PIC mekanik harus berupa daftar nama.');

        $result = [];
        $seen = [];
        foreach ($value as $name) {
            if (!is_scalar($name)) throw new InvalidArgumentException('Nama PIC mekanik tidak valid.');
            $name = trim((string) $name);
            if ($name === '' || in_array(mb_strtolower($name, 'UTF-8'), ['belum ada pic', 'pending'], true)) continue;
            if (mb_strlen($name, 'UTF-8') > 100) throw new InvalidArgumentException('Nama PIC mekanik maksimal 100 karakter.');
            $key = mb_strtolower($name, 'UTF-8');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $name;
            }
        }
        if (count($result) > 20) throw new InvalidArgumentException('Maksimal 20 PIC mekanik dalam satu Work Order.');
        return $result;
    }

    public static function assetStatusFor(bool $forcedOperation, bool $isDowntime): string
    {
        if ($forcedOperation) return 'OPERATING';
        return $isDowntime ? 'BREAKDOWN' : 'STANDBY';
    }

    private static function inputValue(array $input, string $snakeKey, string $camelKey, mixed $default = null): mixed
    {
        if (array_key_exists($snakeKey, $input)) return $input[$snakeKey];
        if (array_key_exists($camelKey, $input)) return $input[$camelKey];
        return $default;
    }

    private static function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value) || is_float($value)) return (int) $value === 1;
        return in_array(mb_strtolower(trim((string) ($value ?? '')), 'UTF-8'), ['1', 'true', 'yes', 'ya', 'on'], true);
    }

    private static function normalizeDateTime(mixed $value, bool $required, string $label): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            if ($required) throw new InvalidArgumentException("{$label} wajib diisi.");
            return null;
        }

        foreach (['!Y-m-d\\TH:i', '!Y-m-d\\TH:i:s', '!Y-m-d H:i', '!Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        throw new InvalidArgumentException("{$label} tidak valid.");
    }

    private static function boundedText(mixed $value, int $maxLength, bool $required, string $label): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            if ($required) throw new InvalidArgumentException("{$label} wajib diisi.");
            return null;
        }
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException("{$label} maksimal {$maxLength} karakter.");
        }
        return $value;
    }

    public static function overview(PDO $db, string $woId): array
    {
        $workOrder = self::workOrder($db, $woId);

        $spb = $db->prepare(
            'SELECT pr.spb_id, pr.wo_id, pr.asset_id, pr.requested_by, pr.urgency, pr.status, pr.notes,
                    pr.requested_at, COUNT(pri.id) AS item_count,
                    COALESCE(SUM(pri.qty_requested), 0) AS total_quantity,
                    u.full_name AS requested_by_name
             FROM purchase_requests pr
             LEFT JOIN purchase_request_items pri ON pri.spb_id = pr.spb_id
             LEFT JOIN users u ON u.user_id = pr.requested_by
             WHERE pr.wo_id = :wo_id
               AND (
                   NOT EXISTS (
                       SELECT 1 FROM report_purchase_request_integrations report_link
                       WHERE report_link.spb_id = pr.spb_id
                   )
                   OR EXISTS (
                       SELECT 1 FROM report_purchase_request_integrations active_link
                       WHERE active_link.spb_id = pr.spb_id AND active_link.reversed_at IS NULL
                   )
               )
             GROUP BY pr.spb_id, pr.wo_id, pr.asset_id, pr.requested_by, pr.urgency, pr.status, pr.notes, pr.requested_at, u.full_name
             ORDER BY pr.requested_at DESC, pr.spb_id DESC'
        );
        $spb->execute([':wo_id' => $woId]);
        $spbRows = $spb->fetchAll(PDO::FETCH_ASSOC);

        $spbIds = array_column($spbRows, 'spb_id');
        $spbItemsMap = [];
        if (!empty($spbIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($spbIds), '?'));
            $itemStmt = $db->prepare(
                "SELECT pri.id, pri.spb_id, pri.part_number, pri.description, pri.qty_requested, pri.status,
                        p.part_name, COALESCE(p.stock_qty, 0) AS current_stock, p.unit_measure, p.unit_cost
                 FROM purchase_request_items pri
                 LEFT JOIN parts p ON (p.part_number = pri.part_number OR p.part_name = pri.description)
                 WHERE pri.spb_id IN ($inPlaceholders)
                 ORDER BY pri.spb_id, pri.id"
            );
            $itemStmt->execute($spbIds);
            while ($item = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
                $spbItemsMap[$item['spb_id']][] = [
                    'id' => (string) $item['id'],
                    'spbId' => (string) $item['spb_id'],
                    'partNumber' => (string) $item['part_number'],
                    'partName' => (string) ($item['part_name'] ?: $item['description']),
                    'description' => (string) ($item['description'] ?? ''),
                    'qtyRequested' => (int) $item['qty_requested'],
                    'status' => (string) $item['status'],
                    'currentStock' => (int) $item['current_stock'],
                    'unit' => (string) ($item['unit_measure'] ?? 'PCS'),
                    'unitCost' => (float) ($item['unit_cost'] ?? 0),
                ];
            }
        }

        // PPB (Purchase Orders) linked to this WO or its SPBs
        $ppbStmt = $db->prepare(
            'SELECT po.ppb_id, po.spb_id, po.wo_id, po.asset_id, po.vendor, po.project,
                    po.quote_number, po.quote_date, po.delivery_due, po.delivery_location,
                    po.subtotal, po.tax_amount, po.total_amount, po.status, po.created_at,
                    u.full_name AS created_by_name
             FROM purchase_orders po
             LEFT JOIN users u ON u.user_id = po.created_by
             WHERE po.wo_id = :wo_id OR po.spb_id IN (
                 SELECT pr.spb_id FROM purchase_requests pr WHERE pr.wo_id = :wo_id_sub
             )
             ORDER BY po.created_at DESC, po.ppb_id DESC'
        );
        $ppbStmt->execute([':wo_id' => $woId, ':wo_id_sub' => $woId]);
        $ppbRows = $ppbStmt->fetchAll(PDO::FETCH_ASSOC);

        $ppbIds = array_column($ppbRows, 'ppb_id');
        $ppbItemsMap = [];
        if (!empty($ppbIds)) {
            $inPpb = implode(',', array_fill(0, count($ppbIds), '?'));
            $poiStmt = $db->prepare(
                "SELECT poi.id, poi.ppb_id, poi.part_number, poi.description, poi.unit_measure,
                        poi.quantity, poi.unit_price, poi.total_price, poi.notes
                 FROM purchase_order_items poi
                 WHERE poi.ppb_id IN ($inPpb)
                 ORDER BY poi.ppb_id, poi.id"
            );
            $poiStmt->execute($ppbIds);
            while ($poi = $poiStmt->fetch(PDO::FETCH_ASSOC)) {
                $ppbItemsMap[$poi['ppb_id']][] = [
                    'id' => (string) $poi['id'],
                    'ppbId' => (string) $poi['ppb_id'],
                    'partNumber' => (string) $poi['part_number'],
                    'description' => (string) $poi['description'],
                    'unit' => (string) $poi['unit_measure'],
                    'quantity' => (int) $poi['quantity'],
                    'unitPrice' => (float) $poi['unit_price'],
                    'totalPrice' => (float) $poi['total_price'],
                    'notes' => (string) ($poi['notes'] ?? ''),
                ];
            }
        }

        // Summary Procurement Pipeline
        $totalSpb = count($spbRows);
        $totalItems = 0;
        $approvedItems = 0;
        $orderedItems = 0;
        $receivedItems = 0;
        $pendingItems = 0;

        foreach ($spbRows as $s) {
            $items = $spbItemsMap[$s['spb_id']] ?? [];
            foreach ($items as $it) {
                $totalItems++;
                $st = strtolower($it['status']);
                if (str_contains($st, 'tiba') || str_contains($st, 'received') || str_contains($st, 'issued')) {
                    $receivedItems++;
                } elseif (str_contains($st, 'pesan') || str_contains($st, 'order') || str_contains($st, 'ppb')) {
                    $orderedItems++;
                } elseif (str_contains($st, 'setuju') || str_contains($st, 'approv')) {
                    $approvedItems++;
                } else {
                    $pendingItems++;
                }
            }
        }

        $overallProcurementStatus = 'NO_PARTS';
        if (($workOrder['parts_requirement'] ?? '') === 'REQUIRED') {
            if ($totalSpb === 0) {
                $overallProcurementStatus = 'NEED_SPB';
            } elseif ($totalItems > 0 && $receivedItems >= $totalItems) {
                $overallProcurementStatus = 'ALL_RECEIVED';
            } elseif ($orderedItems > 0 || count($ppbRows) > 0) {
                $overallProcurementStatus = 'ORDERED';
            } elseif ($approvedItems > 0) {
                $overallProcurementStatus = 'APPROVED';
            } else {
                $overallProcurementStatus = 'WAITING_APPROVAL';
            }
        } elseif (($workOrder['parts_requirement'] ?? '') === 'NOT_REQUIRED') {
            $overallProcurementStatus = 'NOT_REQUIRED';
        }

        // Costs summary via CostLedger
        $costs = CostLedger::getSummaryByWo($db, $woId);

        $services = $db->prepare(
            'SELECT service_request_id, wo_id, asset_id, service_type, provider_name,
                    description, priority, status, requested_by, assigned_to,
                    requested_at, started_at, completed_at, notes
             FROM work_order_service_requests
             WHERE wo_id = :wo_id
             ORDER BY requested_at DESC, service_request_id DESC'
        );
        $services->execute([':wo_id' => $woId]);

        $usage = $db->prepare(
            "SELECT it.transaction_id, it.transaction_date, it.reference_number,
                    it.quantity, it.unit_measure, it.wo_id, it.asset_id,
                    p.part_number, p.part_name, rr.report_number
             FROM inventory_transactions it
             INNER JOIN parts p ON p.part_id = it.part_id
             INNER JOIN report_records rr ON rr.report_id = it.report_id
             WHERE it.wo_id = :wo_id AND it.asset_id = :asset_id
               AND it.movement_type = 'OUT' AND it.reversed_at IS NULL
               AND rr.status = 'FINAL'
             ORDER BY it.transaction_date DESC, it.transaction_id DESC
             LIMIT 100"
        );
        $usage->execute([':wo_id' => $woId, ':asset_id' => $workOrder['asset_id']]);

        // Overtime Records (Surat Perintah Lembur) linked to this WO
        $splStmt = $db->prepare(
            'SELECT s.spl_number, s.report_id, s.wo_id, s.spl_date, s.period_start, s.period_end,
                    s.start_time, s.end_time, s.duration_hours, s.activity_description, s.general_instruction,
                    s.role_snapshot AS employee_role, s.start_verified, s.end_verified,
                    COALESCE(u.full_name, "Personel") AS employee_name,
                    prep.full_name AS prepared_by_name,
                    inst.full_name AS instructed_by_name,
                    loc.location_name
             FROM spl_overtime_records s
             LEFT JOIN users u ON u.user_id = s.employee_user_id
             LEFT JOIN users prep ON prep.user_id = s.prepared_by
             LEFT JOIN users inst ON inst.user_id = s.instructed_by
             LEFT JOIN locations loc ON loc.location_id = s.location_id
             WHERE s.wo_id = :wo_id AND s.reversed_at IS NULL
             ORDER BY s.spl_date DESC, s.report_item_position ASC'
        );
        $splStmt->execute([':wo_id' => $woId]);
        $splRows = $splStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'workOrder' => [
                'woId' => (string) $workOrder['wo_id'],
                'assetId' => (string) $workOrder['asset_id'],
                'status' => (string) ($workOrder['status'] ?? 'Open'),
                'priority' => (string) ($workOrder['priority'] ?? 'Normal'),
                'maintenanceType' => (string) ($workOrder['maintenance_type'] ?? 'Corrective'),
                'partsRequirement' => (string) ($workOrder['parts_requirement'] ?? 'UNASSESSED'),
                'partsRequirementNote' => (string) ($workOrder['parts_requirement_note'] ?? ''),
                'currentHmKm' => (float) ($workOrder['current_hm_km'] ?? 0),
                'troubleAt' => (string) ($workOrder['trouble_at'] ?? ''),
                'reportedAt' => (string) ($workOrder['reported_at'] ?? ''),
                'repairStartedAt' => (string) ($workOrder['repair_started_at'] ?? ''),
                'estimatedCompletionAt' => (string) ($workOrder['estimated_completion_at'] ?? ''),
                'closedAt' => (string) ($workOrder['closed_at'] ?? ''),
                'rawLocation' => (string) ($workOrder['raw_location'] ?? ''),
                'issueDescription' => (string) ($workOrder['issue_description'] ?? ''),
                'rootCause' => (string) ($workOrder['root_cause'] ?? ''),
                'repairAction' => (string) ($workOrder['repair_action'] ?? ''),
                'assignedMechanic' => (string) ($workOrder['assigned_mechanic'] ?? ''),
                'assignedMechanics' => json_decode((string) ($workOrder['assigned_mechanics_json'] ?? '[]'), true) ?: [],
                'beforePhotoUrl' => (string) ($workOrder['before_photo_url'] ?? ''),
                'isForcedOperation' => (bool) ($workOrder['is_forced_operation'] ?? false),
                'isDowntime' => (bool) ($workOrder['is_downtime'] ?? true),
                'mitigationAction' => (string) ($workOrder['mitigation_action'] ?? ''),
            ],
            'purchaseRequests' => array_map(static fn(array $row): array => [
                'spbId' => (string) $row['spb_id'],
                'woId' => (string) $row['wo_id'],
                'assetId' => (string) $row['asset_id'],
                'requestedBy' => (string) ($row['requested_by_name'] ?? $row['requested_by']),
                'urgency' => (string) $row['urgency'],
                'status' => (string) $row['status'],
                'notes' => (string) ($row['notes'] ?? ''),
                'requestedAt' => (string) $row['requested_at'],
                'itemCount' => (int) $row['item_count'],
                'totalQuantity' => (int) $row['total_quantity'],
                'items' => $spbItemsMap[$row['spb_id']] ?? [],
            ], $spbRows),
            'purchaseOrders' => array_map(static fn(array $row): array => [
                'ppbId' => (string) $row['ppb_id'],
                'spbId' => (string) $row['spb_id'],
                'woId' => (string) $row['wo_id'],
                'assetId' => (string) $row['asset_id'],
                'vendor' => (string) $row['vendor'],
                'project' => (string) $row['project'],
                'quoteNumber' => (string) ($row['quote_number'] ?? ''),
                'quoteDate' => (string) ($row['quote_date'] ?? ''),
                'deliveryDue' => (string) $row['delivery_due'],
                'deliveryLocation' => (string) $row['delivery_location'],
                'subtotal' => (float) $row['subtotal'],
                'taxAmount' => (float) $row['tax_amount'],
                'totalAmount' => (float) $row['total_amount'],
                'status' => (string) $row['status'],
                'createdAt' => (string) $row['created_at'],
                'createdBy' => (string) ($row['created_by_name'] ?? ''),
                'items' => $ppbItemsMap[$row['ppb_id']] ?? [],
            ], $ppbRows),
            'procurementPipeline' => [
                'totalSpb' => $totalSpb,
                'totalItems' => $totalItems,
                'pendingItems' => $pendingItems,
                'approvedItems' => $approvedItems,
                'orderedItems' => $orderedItems,
                'receivedItems' => $receivedItems,
                'overallStatus' => $overallProcurementStatus,
                'ppbCount' => count($ppbRows),
            ],
            'partUsage' => array_map(static fn(array $row): array => [
                'transactionId' => (int) $row['transaction_id'],
                'date' => (string) $row['transaction_date'],
                'referenceNumber' => (string) $row['reference_number'],
                'reportNumber' => (string) ($row['report_number'] ?? ''),
                'partNumber' => (string) $row['part_number'],
                'partName' => (string) $row['part_name'],
                'quantity' => (int) $row['quantity'],
                'unit' => (string) $row['unit_measure'],
                'woId' => (string) $row['wo_id'],
                'assetId' => (string) $row['asset_id'],
            ], $usage->fetchAll(PDO::FETCH_ASSOC)),
            'serviceRequests' => array_map([self::class, 'mapServiceRequest'], $services->fetchAll(PDO::FETCH_ASSOC)),
            'overtimeRecords' => array_map(static fn(array $row): array => [
                'splNumber' => (string) $row['spl_number'],
                'reportId' => (int) $row['report_id'],
                'woId' => (string) $row['wo_id'],
                'splDate' => (string) $row['spl_date'],
                'periodStart' => (string) $row['period_start'],
                'periodEnd' => (string) $row['period_end'],
                'startTime' => (string) ($row['start_time'] ?? ''),
                'endTime' => (string) ($row['end_time'] ?? ''),
                'durationHours' => (float) $row['duration_hours'],
                'activityDescription' => (string) ($row['activity_description'] ?? ''),
                'generalInstruction' => (string) ($row['general_instruction'] ?? ''),
                'employeeName' => (string) ($row['employee_name'] ?? ''),
                'employeeRole' => (string) ($row['employee_role'] ?? ''),
                'preparedByName' => (string) ($row['prepared_by_name'] ?? ''),
                'instructedByName' => (string) ($row['instructed_by_name'] ?? ''),
                'locationName' => (string) ($row['location_name'] ?? ''),
                'startVerified' => (bool) $row['start_verified'],
                'endVerified' => (bool) $row['end_verified'],
            ], $splRows),
            'costs' => $costs,
        ];
    }

    public static function markNoParts(
        PDO $db,
        string $woId,
        string $reason,
        ?int $actorId,
        string $actorName
    ): void {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Alasan tidak memerlukan suku cadang wajib diisi.');
        }
        $workOrder = self::workOrder($db, $woId);

        $active = $db->prepare(
            "SELECT pr.spb_id
             FROM purchase_requests pr
             WHERE pr.wo_id = :wo_id AND pr.status <> 'Closed'
               AND (
                   NOT EXISTS (
                       SELECT 1 FROM report_purchase_request_integrations owned
                       WHERE owned.spb_id = pr.spb_id AND owned.owns_purchase_request = 1
                   )
                   OR EXISTS (
                       SELECT 1 FROM report_purchase_request_integrations active_link
                       WHERE active_link.spb_id = pr.spb_id AND active_link.reversed_at IS NULL
                   )
               )
             LIMIT 1"
        );
        $active->execute([':wo_id' => $woId]);
        $activeSpb = $active->fetchColumn();
        if ($activeSpb !== false) {
            throw new DomainException("WO masih memiliki SPB aktif {$activeSpb}. Tutup SPB sebelum memilih tidak memerlukan part.");
        }

        $stmt = $db->prepare(
            "UPDATE work_orders
             SET parts_requirement = 'NOT_REQUIRED', parts_requirement_note = :note
             WHERE wo_id = :wo_id"
        );
        $stmt->execute([':note' => $reason, ':wo_id' => $woId]);
        self::timeline($db, $woId, 'Kebutuhan part: tidak diperlukan', $reason, $actorId, $actorName);
        self::notify(
            $db,
            'PART_NOT_REQUIRED',
            "Konfirmasi Tanpa Part WO #{$woId}",
            "Work Order #{$woId} (Unit: {$workOrder['asset_id']}) dikonfirmasi tidak memerlukan suku cadang. Catatan: {$reason}",
            $woId,
            (string) $workOrder['asset_id']
        );
    }

    public static function markPartsRequired(
        PDO $db,
        string $woId,
        string $note,
        ?int $actorId,
        string $actorName,
        ?string $spbId = null
    ): void {
        $workOrder = self::workOrder($db, $woId);
        $note = trim($note);
        if ($note === '') {
            $note = $spbId ? "Permintaan part dicatat melalui {$spbId}." : 'Permintaan part dicatat dari Work Order.';
        }

        $stmt = $db->prepare(
            "UPDATE work_orders
             SET parts_requirement = 'REQUIRED', parts_requirement_note = :note
             WHERE wo_id = :wo_id"
        );
        $stmt->execute([':note' => $note, ':wo_id' => $woId]);
        self::timeline($db, $woId, 'Kebutuhan part dicatat', $note, $actorId, $actorName);

        // Auto transition WO to 'Waiting Part' if status is Open or In Progress
        $currentStatus = (string) ($workOrder['status'] ?? 'Open');
        if (in_array($currentStatus, ['Open', 'In Progress'], true)) {
            $updWo = $db->prepare("UPDATE work_orders SET status = 'Waiting Part' WHERE wo_id = :wo_id");
            $updWo->execute([':wo_id' => $woId]);
            self::timeline(
                $db,
                $woId,
                'Status otomatis: Waiting Part',
                "Status WO dialihkan ke Waiting Part karena terdapat kebutuhan suku cadang aktif" . ($spbId ? " ({$spbId})" : "") . ".",
                $actorId,
                $actorName,
                'Waiting Part',
                $currentStatus,
                'STATUS_CHANGE'
            );
        }

        self::notify(
            $db,
            'PART_REQUEST',
            "Permintaan Suku Cadang WO #{$woId}" . ($spbId ? " ({$spbId})" : ''),
            "Permintaan suku cadang dicatat untuk Work Order #{$woId} (Unit: {$workOrder['asset_id']}). {$note}",
            $woId,
            (string) $workOrder['asset_id'],
            $spbId
        );
    }

    public static function checkAndResumeWorkOrder(PDO $db, string $woId, ?int $actorId, string $actorName): bool
    {
        $workOrder = self::workOrder($db, $woId);
        if (($workOrder['status'] ?? '') !== 'Waiting Part') {
            return false;
        }

        $pending = $db->prepare(
            "SELECT COUNT(*) FROM purchase_requests
             WHERE wo_id = :wo_id AND status NOT IN ('Issued', 'Closed', 'Cancelled')"
        );
        $pending->execute([':wo_id' => $woId]);
        $pendingCount = (int) $pending->fetchColumn();

        if ($pendingCount === 0) {
            $upd = $db->prepare("UPDATE work_orders SET status = 'In Progress' WHERE wo_id = :wo_id");
            $upd->execute([':wo_id' => $woId]);
            self::timeline(
                $db,
                $woId,
                'Status otomatis: In Progress',
                'Status WO dikembalikan ke In Progress karena seluruh suku cadang telah siap/diterbitkan.',
                $actorId,
                $actorName,
                'In Progress',
                'Waiting Part',
                'STATUS_CHANGE'
            );
            self::notify(
                $db,
                'WO_RESUMED',
                "Work Order #{$woId} Siap Dikerjakan",
                "Seluruh kebutuhan suku cadang untuk Work Order #{$woId} (Unit: {$workOrder['asset_id']}) telah siap. Pekerjaan dapat dilanjutkan.",
                $woId,
                (string) $workOrder['asset_id']
            );
            return true;
        }

        return false;
    }

    public static function createServiceRequest(
        PDO $db,
        string $woId,
        string $assetId,
        array $input,
        int $actorId,
        string $actorName
    ): array {
        $workOrder = self::workOrder($db, $woId);
        if (!hash_equals((string) $workOrder['asset_id'], trim($assetId))) {
            throw new DomainException('Asset permintaan layanan tidak sesuai dengan asset pada Work Order.');
        }

        $serviceType = self::enumValue('jenis layanan', (string) ($input['serviceType'] ?? ''), self::SERVICE_TYPES);
        $priority = self::enumValue('prioritas', (string) ($input['priority'] ?? 'Normal'), self::SERVICE_PRIORITIES);
        $description = trim((string) ($input['description'] ?? ''));
        if ($description === '') {
            throw new InvalidArgumentException('Deskripsi layanan wajib diisi.');
        }

        $requestId = self::newServiceRequestId($db);
        $provider = self::nullableText($input['providerName'] ?? null);
        $assignedTo = self::nullableText($input['assignedTo'] ?? null);
        $notes = self::nullableText($input['notes'] ?? null);
        $status = $assignedTo !== null ? 'Assigned' : 'Submitted';

        $stmt = $db->prepare(
            'INSERT INTO work_order_service_requests
             (service_request_id, wo_id, asset_id, service_type, provider_name, description,
              priority, status, requested_by, assigned_to, notes)
             VALUES
             (:id, :wo_id, :asset_id, :service_type, :provider_name, :description,
              :priority, :status, :requested_by, :assigned_to, :notes)'
        );
        $stmt->execute([
            ':id' => $requestId,
            ':wo_id' => $woId,
            ':asset_id' => $assetId,
            ':service_type' => $serviceType,
            ':provider_name' => $provider,
            ':description' => $description,
            ':priority' => $priority,
            ':status' => $status,
            ':requested_by' => $actorId,
            ':assigned_to' => $assignedTo,
            ':notes' => $notes,
        ]);

        $label = self::serviceTypeLabel($serviceType);
        self::timeline(
            $db,
            $woId,
            "Permintaan layanan {$requestId}",
            "{$label} - {$description}" . ($provider ? " (Penyedia: {$provider})" : ''),
            $actorId,
            $actorName
        );
        self::notify(
            $db,
            'SERVICE_REQUEST',
            "Permintaan Layanan WO #{$woId} (#{$requestId})",
            "Permintaan {$label} diajukan untuk Work Order #{$woId} (Unit: {$assetId}). Prioritas: {$priority}. {$description}",
            $woId,
            $assetId,
            null,
            $requestId
        );

        return self::serviceRequest($db, $requestId);
    }

    public static function updateServiceStatus(
        PDO $db,
        string $requestId,
        string $status,
        ?string $assignedTo,
        ?string $notes,
        ?int $actorId,
        string $actorName
    ): array {
        $current = self::serviceRequest($db, $requestId);
        $status = self::enumValue('status layanan', $status, self::SERVICE_STATUSES);
        self::assertTransition((string) $current['status'], $status);

        $assignedTo = self::nullableText($assignedTo);
        $notes = self::nullableText($notes);
        $stmt = $db->prepare(
            'UPDATE work_order_service_requests
             SET status = :status,
                 assigned_to = COALESCE(:assigned_to, assigned_to),
                 notes = COALESCE(:notes, notes),
                 started_at = CASE
                     WHEN :status_started IN (\'In Progress\', \'Completed\') THEN COALESCE(started_at, NOW())
                     ELSE started_at
                 END,
                 completed_at = CASE
                     WHEN :status_completed = \'Completed\' THEN COALESCE(completed_at, NOW())
                     WHEN :status_reset <> \'Completed\' THEN NULL
                     ELSE completed_at
                 END
             WHERE service_request_id = :id'
        );
        $stmt->execute([
            ':status' => $status,
            ':assigned_to' => $assignedTo,
            ':notes' => $notes,
            ':status_started' => $status,
            ':status_completed' => $status,
            ':status_reset' => $status,
            ':id' => $requestId,
        ]);

        $description = "Status layanan berubah dari {$current['status']} menjadi {$status}.";
        if ($assignedTo !== null) $description .= " PIC: {$assignedTo}.";
        if ($notes !== null) $description .= " Catatan: {$notes}";
        self::timeline($db, (string) $current['wo_id'], "Status layanan {$requestId}: {$status}", $description, $actorId, $actorName);
        self::notify(
            $db,
            'SERVICE_STATUS_CHANGE',
            "Status Layanan WO #{$current['wo_id']} (#{$requestId})",
            "Permintaan layanan #{$requestId} untuk Work Order #{$current['wo_id']} dialihkan ke status {$status}." . ($assignedTo !== null ? " PIC: {$assignedTo}." : ''),
            (string) $current['wo_id'],
            (string) $current['asset_id'],
            null,
            $requestId
        );

        return self::serviceRequest($db, $requestId);
    }

    private static function workOrder(PDO $db, string $woId): array
    {
        $woId = trim($woId);
        if ($woId === '') throw new InvalidArgumentException('Work Order wajib dipilih.');
        $stmt = $db->prepare(
            'SELECT * FROM work_orders WHERE wo_id = :wo_id LIMIT 1'
        );
        $stmt->execute([':wo_id' => $woId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('Work Order tidak ditemukan.');
        return $row;
    }

    private static function serviceRequest(PDO $db, string $requestId): array
    {
        $stmt = $db->prepare('SELECT * FROM work_order_service_requests WHERE service_request_id = :id LIMIT 1');
        $stmt->execute([':id' => trim($requestId)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('Permintaan layanan tidak ditemukan.');
        return $row;
    }

    private static function newServiceRequestId(PDO $db): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $id = 'SRV-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $check = $db->prepare('SELECT 1 FROM work_order_service_requests WHERE service_request_id = :id');
            $check->execute([':id' => $id]);
            if (!$check->fetchColumn()) return $id;
        }
        throw new RuntimeException('Gagal membuat nomor permintaan layanan yang unik.');
    }

    public static function timeline(
        PDO $db,
        string $woId,
        string $title,
        string $description,
        ?int $actorId,
        string $actorName,
        ?string $toStatus = null,
        ?string $fromStatus = null,
        string $eventType = 'DETAIL_UPDATE'
    ): void {
        $stmt = $db->prepare(
            "INSERT INTO work_order_timeline
             (wo_id, event_type, from_status, to_status, title, description, actor_user_id, actor_name)
             VALUES (:wo_id, :event_type, :from_status, :to_status, :title, :description, :actor_id, :actor_name)"
        );
        $stmt->execute([
            ':wo_id' => $woId,
            ':event_type' => $eventType,
            ':from_status' => $fromStatus,
            ':to_status' => $toStatus,
            ':title' => $title,
            ':description' => $description,
            ':actor_id' => $actorId,
            ':actor_name' => trim($actorName) !== '' ? trim($actorName) : 'Pengguna',
        ]);
    }

    private static function enumValue(string $field, string $value, array $allowed): string
    {
        $value = trim($value);
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Nilai {$field} tidak valid.");
        }
        return $value;
    }

    private static function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private static function assertTransition(string $from, string $to): void
    {
        if ($from === $to) return;
        $allowed = [
            'Submitted' => ['Assigned', 'In Progress', 'Cancelled'],
            'Assigned' => ['In Progress', 'Completed', 'Cancelled'],
            'In Progress' => ['Completed', 'Cancelled'],
            'Completed' => [],
            'Cancelled' => [],
        ];
        if (!in_array($to, $allowed[$from] ?? [], true)) {
            throw new DomainException("Perubahan status layanan dari {$from} ke {$to} tidak diizinkan.");
        }
    }

    private static function serviceTypeLabel(string $type): string
    {
        return [
            'INTERNAL_WORKSHOP' => 'Bengkel internal',
            'EXTERNAL_WORKSHOP' => 'Bengkel eksternal',
            'FIELD_SERVICE' => 'Layanan lapangan',
            'SPECIALIST_VENDOR' => 'Vendor spesialis',
        ][$type] ?? $type;
    }

    private static function mapServiceRequest(array $row): array
    {
        return [
            'serviceRequestId' => (string) $row['service_request_id'],
            'woId' => (string) $row['wo_id'],
            'assetId' => (string) $row['asset_id'],
            'serviceType' => (string) $row['service_type'],
            'providerName' => (string) ($row['provider_name'] ?? ''),
            'description' => (string) $row['description'],
            'priority' => (string) $row['priority'],
            'status' => (string) $row['status'],
            'requestedBy' => (int) $row['requested_by'],
            'assignedTo' => (string) ($row['assigned_to'] ?? ''),
            'requestedAt' => (string) $row['requested_at'],
            'startedAt' => $row['started_at'],
            'completedAt' => $row['completed_at'],
            'notes' => (string) ($row['notes'] ?? ''),
        ];
    }

    public static function notify(
        PDO $db,
        string $actionType,
        string $title,
        string $message,
        string $woId,
        ?string $assetId = null,
        ?string $spbId = null,
        ?string $serviceRequestId = null,
        ?int $locationId = null
    ): void {
        try {
            require_once __DIR__ . '/NotificationAudience.php';
            $user = function_exists('api_current_user') ? api_current_user() : [];
            $actorName = trim((string) ($user['full_name'] ?? $user['username'] ?? 'Planner'));
            $actorRole = trim((string) ($user['role_name'] ?? $user['role'] ?? 'Planner'));

            // Idempotency: avoid duplicate within 60s
            $dupStmt = $db->prepare(
                "SELECT notification_id FROM system_notifications
                 WHERE action_type = :action_type AND title = :title AND created_at >= NOW() - INTERVAL 60 SECOND
                 LIMIT 1"
            );
            $dupStmt->execute([':action_type' => $actionType, ':title' => $title]);
            if ($dupStmt->fetchColumn()) {
                return;
            }

            if ($locationId === null && $assetId !== null && $assetId !== '') {
                $locStmt = $db->prepare('SELECT current_location_id FROM assets WHERE asset_id = :asset_id LIMIT 1');
                $locStmt->execute([':asset_id' => $assetId]);
                $locVal = $locStmt->fetchColumn();
                if ($locVal !== false && $locVal !== null) {
                    $locationId = (int) $locVal;
                }
            }

            $parties = array_values(array_unique(array_filter([
                'Tim Logistik & Suku Cadang',
                'Maintenance Planner',
                $actorName,
            ])));

            $tables = ['work_orders'];
            $records = [['table' => 'work_orders', 'id' => $woId]];
            if ($assetId) {
                $tables[] = 'assets';
                $records[] = ['table' => 'assets', 'id' => $assetId];
            }
            if ($spbId) {
                $tables[] = 'purchase_requests';
                $records[] = ['table' => 'purchase_requests', 'id' => $spbId];
            }
            if ($serviceRequestId) {
                $tables[] = 'work_order_service_requests';
                $records[] = ['table' => 'work_order_service_requests', 'id' => $serviceRequestId];
            }

            $tablesData = [
                'tables' => array_unique($tables),
                'records' => $records,
                'wo_id' => $woId,
                'asset_id' => $assetId,
                'spb_id' => $spbId,
                'service_request_id' => $serviceRequestId,
                'location_id' => $locationId,
            ];

            $stmt = $db->prepare(
                "INSERT INTO system_notifications
                 (menu_name, user_name, user_role, action_type, title, message,
                  involved_parties_json, related_tables_json, is_read, created_at)
                 VALUES ('Logistik & Suku Cadang', :user_name, :user_role, :action_type, :title, :message,
                         :parties, :tables, 0, NOW())"
            );
            $stmt->execute([
                ':user_name' => $actorName,
                ':user_role' => $actorRole ?: 'Planner',
                ':action_type' => $actionType,
                ':title' => $title,
                ':message' => $message,
                ':parties' => json_encode($parties, JSON_UNESCAPED_UNICODE),
                ':tables' => json_encode($tablesData, JSON_UNESCAPED_UNICODE),
            ]);
            NotificationAudience::deliverWo($db, (int) $db->lastInsertId(), $locationId, isset($user['id']) ? (int) $user['id'] : null);
        } catch (Throwable $e) {
            error_log('WorkOrderSupport notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Inspect warehouse physical stock for requested spare parts.
     * Evaluates whether all parts are available in warehouse, partial, or out of stock.
     */
    public static function checkPartsAvailability(PDO $db, array $items): array
    {
        if (empty($items)) {
            return [
                'all_available' => true,
                'any_available' => false,
                'summary_status' => 'EMPTY',
                'items' => [],
                'available_items' => [],
                'deficit_items' => [],
            ];
        }

        $allAvailable = true;
        $anyAvailable = false;
        $evaluatedItems = [];
        $availableItems = [];
        $deficitItems = [];

        foreach ($items as $item) {
            $partNum = trim((string) ($item['part_number'] ?? $item['partNumber'] ?? ''));
            $desc = trim((string) ($item['description'] ?? ''));
            $qtyReq = max(1, (int) ($item['qty'] ?? 1));
            $isManual = !empty($item['is_manual']) || !empty($item['isManual']);
            $uom = trim((string) ($item['uom'] ?? 'pcs')) ?: 'pcs';

            $partRow = null;
            if (!$isManual && $partNum !== '') {
                $stmt = $db->prepare('SELECT part_id, part_number, part_name, unit_measure, stock_qty, min_stock_qty, location_warehouse FROM parts WHERE part_number = :pnum LIMIT 1');
                $stmt->execute([':pnum' => $partNum]);
                $partRow = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$partRow && is_numeric($partNum)) {
                    $stmt = $db->prepare('SELECT part_id, part_number, part_name, unit_measure, stock_qty, min_stock_qty, location_warehouse FROM parts WHERE part_id = :pid LIMIT 1');
                    $stmt->execute([':pid' => (int) $partNum]);
                    $partRow = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            $currentStock = $partRow ? (int) $partRow['stock_qty'] : 0;
            $isAvailable = (!$isManual && $partRow && $currentStock >= $qtyReq);
            $deficit = $isAvailable ? 0 : ($partRow ? max(0, $qtyReq - $currentStock) : $qtyReq);

            $eval = [
                'part_id' => $partRow ? (int) $partRow['part_id'] : null,
                'part_number' => $partRow ? (string) $partRow['part_number'] : $partNum,
                'part_name' => $partRow ? (string) $partRow['part_name'] : ($desc ?: 'Custom Part'),
                'description' => $desc ?: ($partRow ? (string) $partRow['part_name'] : $partNum),
                'unit_measure' => $partRow ? ((string) $partRow['unit_measure'] ?: $uom) : $uom,
                'location_warehouse' => $partRow ? (string) $partRow['location_warehouse'] : 'Gudang Utama',
                'requested_qty' => $qtyReq,
                'stock_qty' => $currentStock,
                'is_available' => $isAvailable,
                'is_manual' => $isManual,
                'deficit_qty' => $deficit,
            ];

            $evaluatedItems[] = $eval;
            if ($isAvailable) {
                $anyAvailable = true;
                $availableItems[] = $eval;
            } else {
                $allAvailable = false;
                $deficitItems[] = $eval;
            }
        }

        $summaryStatus = $allAvailable
            ? 'ALL_AVAILABLE'
            : ($anyAvailable ? 'PARTIAL' : 'OUT_OF_STOCK');

        return [
            'all_available' => $allAvailable,
            'any_available' => $anyAvailable,
            'summary_status' => $summaryStatus,
            'items' => $evaluatedItems,
            'available_items' => $availableItems,
            'deficit_items' => $deficitItems,
        ];
    }

    /**
     * Directly issue available spare parts from warehouse for a Work Order (BHW-OUT flow).
     * Atomically deducts stock, creates inventory transactions, and links to the Work Order.
     */
    public static function issueDirectParts(
        PDO $db,
        string $woId,
        string $assetId,
        array $itemsToIssue,
        ?int $actorId,
        string $actorName
    ): array {
        if ($woId === '' || empty($itemsToIssue)) {
            throw new InvalidArgumentException('ID Work Order dan daftar suku cadang yang dikeluarkan wajib diisi.');
        }

        // Generate UUID for report_id compliant with inventory_transactions foreign key
        $reportId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0x4000, 0x4fff),
            random_int(0x8000, 0xbfff),
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );

        $tplStmt = $db->prepare("SELECT template_id FROM report_templates WHERE template_key = 'bhw-out' LIMIT 1");
        $tplStmt->execute();
        $templateId = (int) $tplStmt->fetchColumn();
        if ($templateId === 0) {
            $insertTpl = $db->prepare("
                INSERT INTO report_templates (template_key, code, title, version, schema_json, is_active)
                VALUES ('bhw-out', 'BHW-OUT', 'Buku Harian Warehouse (Barang Keluar)', 1, '{}', 1)
            ");
            $insertTpl->execute();
            $templateId = (int) $db->lastInsertId();
        }

        $reportNumber = 'BHW-OUT/WO/' . $woId . '/' . date('ymdHis');
        $db->prepare("
            INSERT INTO report_records
            (report_id, template_id, client_key, report_number, status, source_method, field_data, created_by, created_at, finalized_at)
            VALUES (:rid, :tid, :ckey, :rnum, 'FINAL', 'wo_direct_issue', '{}', :actor, NOW(), NOW())
        ")->execute([
            ':rid' => $reportId,
            ':tid' => $templateId,
            ':ckey' => 'client-bhwout-' . substr(md5($reportId), 0, 12),
            ':rnum' => $reportNumber,
            ':actor' => $actorId ?: 1,
        ]);

        $issuedResults = [];
        $position = 1;

        foreach ($itemsToIssue as $item) {
            $partNumber = (string) ($item['part_number'] ?? '');
            $qty = max(1, (int) ($item['requested_qty'] ?? $item['qty'] ?? 1));

            // Lock and verify stock
            $pStmt = $db->prepare('SELECT * FROM parts WHERE part_number = :pnum FOR UPDATE');
            $pStmt->execute([':pnum' => $partNumber]);
            $part = $pStmt->fetch(PDO::FETCH_ASSOC);

            if (!$part && is_numeric($partNumber)) {
                $pStmt = $db->prepare('SELECT * FROM parts WHERE part_id = :pid FOR UPDATE');
                $pStmt->execute([':pid' => (int) $partNumber]);
                $part = $pStmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$part) {
                throw new OutOfBoundsException("Suku cadang {$partNumber} tidak ditemukan di master database parts.");
            }

            $currentStock = (int) ($part['stock_qty'] ?? 0);
            if ($currentStock < $qty) {
                throw new DomainException("Stok fisik {$part['part_name']} ({$part['part_number']}) tidak mencukupi saat proses pengeluaran! Tersedia: {$currentStock}, Diminta: {$qty}");
            }

            $stockAfter = $currentStock - $qty;

            // Deduct stock
            $db->prepare('UPDATE parts SET stock_qty = :stk WHERE part_id = :pid')
               ->execute([':stk' => $stockAfter, ':pid' => (int) $part['part_id']]);

            // Insert inventory transaction
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
                ':ref_num' => $reportNumber,
                ':counterparty' => 'Gudang Yard KM 12',
                ':asset_id' => $assetId,
                ':wo_id' => $woId,
                ':part_id' => (int) $part['part_id'],
                ':quantity' => $qty,
                ':unit_measure' => $part['unit_measure'] ?: 'Pcs',
                ':stock_before' => $currentStock,
                ':stock_after' => $stockAfter,
                ':notes' => "Pengeluaran langsung suku cadang fisik untuk Work Order #{$woId} (Unit {$assetId})",
                ':created_by' => $actorId,
            ]);

            $issuedResults[] = [
                'partId' => (int) $part['part_id'],
                'partNumber' => $part['part_number'],
                'partName' => $part['part_name'],
                'qtyIssued' => $qty,
                'stockBefore' => $currentStock,
                'stockAfter' => $stockAfter,
            ];
        }

        return [
            'reportId' => $reportId,
            'reportNumber' => $reportNumber,
            'woId' => $woId,
            'assetId' => $assetId,
            'itemsIssued' => $issuedResults,
            'totalItems' => count($issuedResults),
        ];
    }
}


