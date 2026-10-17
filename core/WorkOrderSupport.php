<?php
declare(strict_types=1);

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

    public static function overview(PDO $db, string $woId): array
    {
        $workOrder = self::workOrder($db, $woId);

        $spb = $db->prepare(
            'SELECT pr.spb_id, pr.wo_id, pr.asset_id, pr.urgency, pr.status, pr.notes,
                    pr.requested_at, COUNT(pri.id) AS item_count,
                    COALESCE(SUM(pri.qty_requested), 0) AS total_quantity
             FROM purchase_requests pr
             LEFT JOIN purchase_request_items pri ON pri.spb_id = pr.spb_id
             WHERE pr.wo_id = :wo_id
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
             GROUP BY pr.spb_id, pr.wo_id, pr.asset_id, pr.urgency, pr.status, pr.notes, pr.requested_at
             ORDER BY pr.requested_at DESC, pr.spb_id DESC'
        );
        $spb->execute([':wo_id' => $woId]);

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

        return [
            'workOrder' => [
                'woId' => (string) $workOrder['wo_id'],
                'assetId' => (string) $workOrder['asset_id'],
                'partsRequirement' => (string) $workOrder['parts_requirement'],
                'partsRequirementNote' => (string) ($workOrder['parts_requirement_note'] ?? ''),
            ],
            'purchaseRequests' => array_map(static fn(array $row): array => [
                'spbId' => (string) $row['spb_id'],
                'woId' => (string) $row['wo_id'],
                'assetId' => (string) $row['asset_id'],
                'urgency' => (string) $row['urgency'],
                'status' => (string) $row['status'],
                'notes' => (string) ($row['notes'] ?? ''),
                'requestedAt' => (string) $row['requested_at'],
                'itemCount' => (int) $row['item_count'],
                'totalQuantity' => (int) $row['total_quantity'],
            ], $spb->fetchAll(PDO::FETCH_ASSOC)),
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
            'SELECT wo_id, asset_id, parts_requirement, parts_requirement_note
             FROM work_orders WHERE wo_id = :wo_id LIMIT 1'
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

    private static function timeline(PDO $db, string $woId, string $title, string $description, ?int $actorId, string $actorName): void
    {
        $stmt = $db->prepare(
            "INSERT INTO work_order_timeline
             (wo_id, event_type, title, description, actor_user_id, actor_name)
             VALUES (:wo_id, 'DETAIL_UPDATE', :title, :description, :actor_id, :actor_name)"
        );
        $stmt->execute([
            ':wo_id' => $woId,
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
        } catch (Throwable $e) {
            error_log('WorkOrderSupport notification failed: ' . $e->getMessage());
        }
    }
}

