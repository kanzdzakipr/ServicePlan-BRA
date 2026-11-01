<?php
declare(strict_types=1);

final class InputRevision
{
    public static function ensureAudit(PDO $db): void
    {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS input_revision_audit (
                revision_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                entity_type VARCHAR(30) NOT NULL,
                entity_id VARCHAR(100) NOT NULL,
                action VARCHAR(30) NOT NULL,
                reason VARCHAR(500) NOT NULL,
                actor_user_id INT NULL,
                before_json LONGTEXT NULL,
                after_json LONGTEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (revision_id),
                KEY idx_input_revision_entity (entity_type, entity_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public static function actorId(): int
    {
        return (int) (api_current_user()['id'] ?? 0);
    }

    public static function isPrivileged(): bool
    {
        $roles = (array) (api_current_user()['roles'] ?? []);
        return (bool) array_intersect($roles, ['Administrator', 'Asset Manager', 'Equipment Manager', 'Maintenance Planner']);
    }

    public static function assertOwner(int|string|null $ownerId, string $message): void
    {
        if (!self::isPrivileged() && ((int) $ownerId <= 0 || (int) $ownerId !== self::actorId())) {
            throw new DomainException($message);
        }
    }

    public static function reason(array $input, string $fallback = 'Koreksi input sebelum persetujuan'): string
    {
        $reason = trim((string) ($input['revision_reason'] ?? $input['reason'] ?? $fallback));
        if ($reason === '' || mb_strlen($reason, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Alasan revisi wajib diisi (maksimal 500 karakter).');
        }
        return $reason;
    }

    public static function idempotencyKey(array $input): ?string
    {
        $key = trim((string) ($input['idempotency_key'] ?? ''));
        if ($key === '') return null;
        if (mb_strlen($key) > 100 || preg_match('/^[A-Za-z0-9._:-]+$/', $key) !== 1) {
            throw new InvalidArgumentException('Idempotency key tidak valid.');
        }
        return $key;
    }

    public static function reserveIdempotency(PDO $db, string $key, string $type, string $id, string $action): bool
    {
        $db->exec("CREATE TABLE IF NOT EXISTS input_revision_idempotency (
            idempotency_key VARCHAR(100) PRIMARY KEY, entity_type VARCHAR(30) NOT NULL,
            entity_id VARCHAR(100) NOT NULL, action VARCHAR(30) NOT NULL,
            response_json LONGTEXT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_revision_idempotency_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $stmt = $db->prepare('INSERT IGNORE INTO input_revision_idempotency (idempotency_key,entity_type,entity_id,action) VALUES (:key,:type,:id,:action)');
        $stmt->execute([':key'=>$key, ':type'=>$type, ':id'=>$id, ':action'=>$action]);
        return $stmt->rowCount() === 1;
    }

    public static function record(PDO $db, string $type, string $id, string $action, string $reason, array $before, array $after, ?string $idempotencyKey = null, ?string $parentId = null): void
    {
        self::ensureAudit($db);
        $columns = $db->query("SHOW COLUMNS FROM input_revision_audit")->fetchAll(PDO::FETCH_COLUMN);
        $extended = in_array('revision_no', $columns, true);
        if ($extended) {
            $stmt = $db->prepare(
                'INSERT INTO input_revision_audit
                 (entity_type, entity_id, action, revision_no, parent_entity_id, old_status, new_status, idempotency_key, reason, actor_user_id, before_json, after_json)
                 VALUES (:type,:id,:action,:revision_no,:parent_id,:old_status,:new_status,:idempotency_key,:reason,:actor,:before_json,:after_json)'
            );
            $stmt->execute([
                ':type'=>$type, ':id'=>$id, ':action'=>$action,
                ':revision_no'=>(int)($after['revision_no'] ?? $after['input_revision_no'] ?? 0),
                ':parent_id'=>$parentId, ':old_status'=>$before['status'] ?? $before['input_state'] ?? null,
                ':new_status'=>$after['status'] ?? $after['input_state'] ?? null, ':idempotency_key'=>$idempotencyKey,
                ':reason'=>$reason, ':actor'=>self::actorId() ?: null,
                ':before_json'=>json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':after_json'=>json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            return;
        }
        $stmt = $db->prepare(
            'INSERT INTO input_revision_audit
             (entity_type, entity_id, action, reason, actor_user_id, before_json, after_json)
             VALUES (:type, :id, :action, :reason, :actor, :before_json, :after_json)'
        );
        $stmt->execute([
            ':type' => $type,
            ':id' => $id,
            ':action' => $action,
            ':reason' => $reason,
            ':actor' => self::actorId() ?: null,
            ':before_json' => json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':after_json' => json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public static function history(PDO $db, string $type, string $id): array
    {
        self::ensureAudit($db);
        $stmt = $db->prepare('SELECT * FROM input_revision_audit WHERE entity_type=:type AND entity_id=:id ORDER BY created_at DESC, revision_id DESC');
        $stmt->execute([':type'=>$type, ':id'=>$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
