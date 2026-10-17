<?php
declare(strict_types=1);

/** Per-user notification delivery and read state. Legacy notifications are not
 * backfilled: their intended audience cannot be inferred safely. */
final class NotificationAudience
{
    private const GLOBAL_ROLES = ['Administrator', 'Equipment Manager', 'Asset Manager'];
    private const WO_ROLES = [
        'Maintenance Planner', 'Logistic Head', 'Mekanik Senior',
        'Mekanik Junior / Helper', 'Welder / Fabrikator',
    ];

    public static function recipientIds(array $users, ?int $locationId, ?int $actorId): array
    {
        $ids = [];
        foreach ($users as $user) {
            if (!(bool) ($user['is_active'] ?? false)) continue;
            $roles = $user['roles'] ?? [];
            if (!is_array($roles)) $roles = [];
            $id = (int) ($user['user_id'] ?? 0);
            if ($id <= 0) continue;
            $isGlobal = (bool) array_intersect($roles, self::GLOBAL_ROLES);
            $sameLocation = $locationId !== null
                && isset($user['assigned_location_id'])
                && (int) $user['assigned_location_id'] === $locationId;
            $operationalRole = (bool) array_intersect($roles, self::WO_ROLES);
            if ($isGlobal || ($sameLocation && $operationalRole) || ($actorId === $id && ($sameLocation || $locationId === null))) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    public static function activeUsers(PDO $db): array
    {
        $rows = $db->query(
            'SELECT u.user_id, u.assigned_location_id, u.is_active, r.role_name
             FROM users u INNER JOIN roles r ON r.role_id = u.role_id
             WHERE u.is_active = 1'
        )->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $id = (int) $row['user_id'];
            $users[$id] = [
                'user_id' => $id,
                'assigned_location_id' => $row['assigned_location_id'] !== null ? (int) $row['assigned_location_id'] : null,
                'is_active' => true,
                'roles' => [(string) $row['role_name']],
            ];
        }
        $extra = $db->query(
            'SELECT ur.user_id, r.role_name FROM user_roles ur
             INNER JOIN roles r ON r.role_id = ur.role_id
             INNER JOIN users u ON u.user_id = ur.user_id WHERE u.is_active = 1'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($extra as $row) {
            $id = (int) $row['user_id'];
            if (isset($users[$id])) $users[$id]['roles'][] = (string) $row['role_name'];
        }
        return array_values($users);
    }

    public static function deliverWo(PDO $db, int $notificationId, ?int $locationId, ?int $actorId): int
    {
        $recipients = self::recipientIds(self::activeUsers($db), $locationId, $actorId);
        $stmt = $db->prepare(
            'INSERT IGNORE INTO notification_recipients
             (notification_id, user_id, location_id) VALUES (:notification_id, :user_id, :location_id)'
        );
        foreach ($recipients as $userId) {
            $stmt->execute([
                ':notification_id' => $notificationId,
                ':user_id' => $userId,
                ':location_id' => $locationId,
            ]);
        }
        return count($recipients);
    }

    public static function deliverSelf(PDO $db, int $notificationId, int $userId, ?int $locationId): void
    {
        $stmt = $db->prepare(
            'INSERT IGNORE INTO notification_recipients
             (notification_id, user_id, location_id) VALUES (:notification_id, :user_id, :location_id)'
        );
        $stmt->execute([':notification_id' => $notificationId, ':user_id' => $userId, ':location_id' => $locationId]);
    }

    public static function listForUser(PDO $db, int $userId, ?int $locationId, bool $globalScope, int $limit): array
    {
        $sql = 'SELECT n.*, nr.read_at
                FROM notification_recipients nr
                INNER JOIN system_notifications n ON n.notification_id = nr.notification_id
                WHERE nr.user_id = :user_id';
        if (!$globalScope) {
            $sql .= ' AND (nr.location_id IS NULL OR nr.location_id = :location_id)';
        }
        $sql .= ' ORDER BY n.created_at DESC, n.notification_id DESC LIMIT :limit';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if (!$globalScope) $stmt->bindValue(':location_id', $locationId, $locationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function markRead(PDO $db, int $userId, ?int $locationId, bool $globalScope, int $notificationId): bool
    {
        $sql = 'UPDATE notification_recipients SET read_at = COALESCE(read_at, NOW())
                WHERE notification_id = :notification_id AND user_id = :user_id';
        if (!$globalScope) $sql .= ' AND (location_id IS NULL OR location_id = :location_id)';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':notification_id', $notificationId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if (!$globalScope) $stmt->bindValue(':location_id', $locationId, $locationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) return true;

        // MySQL rowCount is zero when an already-read row remains unchanged.
        $check = $db->prepare('SELECT 1 FROM notification_recipients WHERE notification_id = :notification_id AND user_id = :user_id'
            . (!$globalScope ? ' AND (location_id IS NULL OR location_id = :location_id)' : ''));
        $check->bindValue(':notification_id', $notificationId, PDO::PARAM_INT);
        $check->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if (!$globalScope) $check->bindValue(':location_id', $locationId, $locationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $check->execute();
        return (bool) $check->fetchColumn();
    }

    public static function markAllRead(PDO $db, int $userId, ?int $locationId, bool $globalScope): int
    {
        $sql = 'UPDATE notification_recipients SET read_at = NOW()
                WHERE user_id = :user_id AND read_at IS NULL';
        if (!$globalScope) $sql .= ' AND (location_id IS NULL OR location_id = :location_id)';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if (!$globalScope) $stmt->bindValue(':location_id', $locationId, $locationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
