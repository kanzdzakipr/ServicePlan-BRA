<?php
declare(strict_types=1);

final class AccidentWorkflow
{
    private const RESTORABLE_STATUSES = ['READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN'];

    public static function record(PDO $db, array $input, int $actorId): array
    {
        $id = trim((string) ($input['docNo'] ?? ''));
        $assetId = trim((string) ($input['unitCode'] ?? ''));
        if ($id === '' || mb_strlen($id, 'UTF-8') > 50) {
            throw new DomainException('Nomor laporan insiden wajib diisi dan maksimal 50 karakter.');
        }
        if ($assetId === '') throw new DomainException('Unit insiden wajib dipilih.');

        $severity = trim((string) ($input['severity'] ?? 'Minor'));
        if (!in_array($severity, ['Minor', 'Moderate', 'Critical'], true)) {
            throw new DomainException('Severitas insiden tidak valid.');
        }
        $chronology = trim((string) ($input['chronology'] ?? ''));
        if ($chronology === '') throw new DomainException('Kronologi insiden wajib diisi.');

        $assetStmt = $db->prepare(
            'SELECT asset_id, status, current_location_id FROM assets WHERE asset_id = :id FOR UPDATE'
        );
        $assetStmt->execute([':id' => $assetId]);
        $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$asset) throw new DomainException("Unit {$assetId} tidak ditemukan pada Master Asset.");
        if ((string) $asset['status'] === 'INACTIVE') {
            throw new DomainException('Unit INACTIVE tidak dapat diproses sebagai insiden operasional.');
        }

        $exists = $db->prepare('SELECT accident_id FROM accidents WHERE accident_id = :id LIMIT 1');
        $exists->execute([':id' => $id]);
        if ($exists->fetchColumn() !== false) {
            throw new DomainException('Nomor laporan insiden sudah digunakan.');
        }

        $isLocked = in_array($severity, ['Moderate', 'Critical'], true);
        $previousStatus = (string) $asset['status'];
        if ($isLocked && $previousStatus === 'ACCIDENT_HOLD') {
            $underlying = $db->prepare(
                "SELECT asset_status_before_hold FROM accidents
                 WHERE asset_id = :asset_id AND is_unit_locked = 1
                   AND asset_status_before_hold IS NOT NULL
                   AND asset_status_before_hold <> 'ACCIDENT_HOLD'
                 ORDER BY locked_at ASC, incident_datetime ASC LIMIT 1"
            );
            $underlying->execute([':asset_id' => $assetId]);
            $underlyingStatus = $underlying->fetchColumn();
            if ($underlyingStatus !== false) $previousStatus = (string) $underlyingStatus;
        }

        $reportDate = self::dateOnly($input['reportDate'] ?? null, 'Tanggal laporan');
        $incidentDate = self::dateTime($input['incidentDate'] ?? null, 'Tanggal kejadian');
        $status = $isLocked ? 'Investigating' : 'Reported';
        $payload = $input;
        $payload['isUnitLocked'] = $isLocked;
        $payload['status'] = $status;

        $stmt = $db->prepare(
            'INSERT INTO accidents
             (accident_id, asset_id, report_date, incident_datetime, location_id,
              operator_name, operator_tenure, chronology, weather, road_condition,
              lighting_condition, severity, physical_damage, estimated_repair_cost,
              estimated_downtime_days, total_financial_loss, cause_factors_json,
              corrective_action, preventive_action, is_unit_locked,
              asset_status_before_hold, locked_at, payload_json, status)
             VALUES
             (:id, :asset, :report_date, :incident_date, :location_id,
              :operator, :tenure, :chronology, :weather, :road,
              :lighting, :severity, :damage, :repair_cost,
              :downtime, :loss, :causes,
              :corrective, :preventive, :locked,
              :previous_status, :locked_at, :payload, :status)'
        );
        $environment = is_array($input['environment'] ?? null) ? $input['environment'] : [];
        $impact = is_array($input['impact'] ?? null) ? $input['impact'] : [];
        $causes = is_array($input['causeFactors'] ?? null) ? array_values($input['causeFactors']) : [];
        $stmt->execute([
            ':id' => $id,
            ':asset' => $assetId,
            ':report_date' => $reportDate,
            ':incident_date' => $incidentDate,
            ':location_id' => $asset['current_location_id'],
            ':operator' => self::nullableText($input['operatorName'] ?? null, 100),
            ':tenure' => self::nullableText($input['operatorTenure'] ?? null, 50),
            ':chronology' => $chronology,
            ':weather' => self::nullableText($environment['weather'] ?? null, 50),
            ':road' => self::nullableText($environment['roadCondition'] ?? null, 50),
            ':lighting' => self::nullableText($environment['lighting'] ?? null, 50),
            ':severity' => $severity,
            ':damage' => self::nullableText($impact['physicalDamage'] ?? null, 65535),
            ':repair_cost' => max(0.0, (float) ($impact['estimatedRepairCost'] ?? 0)),
            ':downtime' => max(0, (int) ($impact['estimatedDowntimeDays'] ?? 0)),
            ':loss' => max(0.0, (float) ($impact['totalFinancialImpact'] ?? 0)),
            ':causes' => json_encode($causes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ':corrective' => self::nullableText($input['correctiveAction'] ?? null, 65535),
            ':preventive' => self::nullableText($input['preventiveAction'] ?? null, 65535),
            ':locked' => $isLocked ? 1 : 0,
            ':previous_status' => $isLocked ? $previousStatus : null,
            ':locked_at' => $isLocked ? date('Y-m-d H:i:s') : null,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ':status' => $status,
        ]);

        if ($isLocked && (string) $asset['status'] !== 'ACCIDENT_HOLD') {
            $db->prepare("UPDATE assets SET status = 'ACCIDENT_HOLD' WHERE asset_id = :id")
                ->execute([':id' => $assetId]);
        }

        return ['accidentId' => $id, 'assetId' => $assetId, 'locked' => $isLocked, 'status' => $status];
    }

    public static function release(PDO $db, string $accidentId, int $actorId, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason, 'UTF-8') > 500) {
            throw new DomainException('Alasan rilis wajib diisi dan maksimal 500 karakter.');
        }
        $stmt = $db->prepare('SELECT * FROM accidents WHERE accident_id = :id FOR UPDATE');
        $stmt->execute([':id' => $accidentId]);
        $accident = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$accident) throw new DomainException('Laporan insiden tidak ditemukan.');
        if ((int) $accident['is_unit_locked'] !== 1) {
            return ['accidentId' => $accidentId, 'assetId' => (string) $accident['asset_id'], 'alreadyReleased' => true];
        }

        $payload = json_decode((string) ($accident['payload_json'] ?? ''), true);
        if (!is_array($payload)) $payload = [];
        $payload['isUnitLocked'] = false;
        $payload['status'] = 'Closed';
        $payload['releasedAt'] = date(DATE_ATOM);
        $payload['releasedBy'] = $actorId;
        $payload['releaseReason'] = $reason;

        $update = $db->prepare(
            "UPDATE accidents SET is_unit_locked = 0, status = 'Closed', released_at = NOW(),
             released_by = :actor, release_reason = :reason, payload_json = :payload
             WHERE accident_id = :id AND is_unit_locked = 1"
        );
        $update->execute([
            ':actor' => $actorId,
            ':reason' => $reason,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ':id' => $accidentId,
        ]);

        $otherLocks = $db->prepare(
            'SELECT COUNT(*) FROM accidents WHERE asset_id = :asset AND is_unit_locked = 1'
        );
        $otherLocks->execute([':asset' => $accident['asset_id']]);
        $remainingLocks = (int) $otherLocks->fetchColumn();
        $restoredStatus = null;
        if ($remainingLocks === 0) {
            $candidate = strtoupper((string) ($accident['asset_status_before_hold'] ?? 'READY'));
            $restoredStatus = in_array($candidate, self::RESTORABLE_STATUSES, true) ? $candidate : 'READY';
            $restore = $db->prepare(
                "UPDATE assets SET status = :status
                 WHERE asset_id = :asset AND status = 'ACCIDENT_HOLD'"
            );
            $restore->execute([':status' => $restoredStatus, ':asset' => $accident['asset_id']]);
            if ($restore->rowCount() === 0) $restoredStatus = null;
        }

        return [
            'accidentId' => $accidentId,
            'assetId' => (string) $accident['asset_id'],
            'alreadyReleased' => false,
            'remainingLocks' => $remainingLocks,
            'restoredStatus' => $restoredStatus,
        ];
    }

    private static function nullableText(mixed $value, int $maxLength): ?string
    {
        $text = trim((string) $value);
        if ($text === '') return null;
        if (mb_strlen($text, 'UTF-8') > $maxLength) throw new DomainException('Teks melebihi batas panjang yang diizinkan.');
        return $text;
    }

    private static function dateOnly(mixed $value, string $label): string
    {
        $text = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $text);
        if (!$date || $date->format('Y-m-d') !== $text) throw new DomainException("{$label} tidak valid.");
        return $text;
    }

    private static function dateTime(mixed $value, string $label): string
    {
        $text = trim((string) $value);
        try { $date = new DateTimeImmutable($text); }
        catch (Throwable) { throw new DomainException("{$label} tidak valid."); }
        return $date->format('Y-m-d H:i:s');
    }
}
