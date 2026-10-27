<?php
declare(strict_types=1);

/** Per-user notification delivery and read state governed by RBAC menu permissions. */
final class NotificationAudience
{
    public const GLOBAL_SCOPE_ROLES = [
        'Administrator', 'Equipment Manager', 'Maintenance Planner',
        'Asset Manager', 'Inspector K3L / Safety', 'Logistic Head', 'HRD Manager',
        'Inspector K3L', 'Safety', 'Logistic', 'HRD'
    ];

    /**
     * Map notification menu_name and action_type to one of the 16 RBAC module keys.
     */
    public static function resolveModuleKey(?string $menuName, ?string $actionType = null): string
    {
        $m = strtolower(trim((string)$menuName));
        $a = strtoupper(trim((string)$actionType));

        if (str_contains($m, 'inspeksi') || str_contains($m, 'inspection') || str_contains($m, 'p2h') || str_contains($a, 'P2H') || str_contains($a, 'INSPECT')) {
            return 'inspection';
        }
        if (str_contains($m, 'hse') || str_contains($m, 'safety') || str_contains($m, 'incident') || str_contains($m, 'accident') || str_contains($m, 'k3l') || str_contains($a, 'ACCIDENT') || str_contains($a, 'INCIDENT')) {
            return 'hse';
        }
        if ($m === 'archive' || str_contains($m, 'arsip') || str_contains($a, 'ARCHIVE') || str_contains($a, 'RESTORE')) {
            return 'asset';
        }
        if (str_contains($m, 'work order') || str_contains($m, 'wo') || str_contains($a, 'WO_')) {
            if (str_contains($m, 'logistik') || str_contains($m, 'part') || str_contains($a, 'PART_REQUEST') || str_contains($a, 'SPB')) {
                return 'logistics';
            }
            return 'wo';
        }
        if (str_contains($m, 'logistik') || str_contains($m, 'part') || str_contains($m, 'suku cadang') || str_contains($m, 'spare') || str_contains($a, 'PART') || str_contains($a, 'SPB')) {
            return 'logistics';
        }
        if (str_contains($m, 'preventive') || str_contains($m, 'pm') || str_contains($a, 'PM_')) {
            return 'pm';
        }
        if (str_contains($m, 'condition') || str_contains($m, 'tire') || str_contains($m, 'ban') || str_contains($m, 'grease') || str_contains($m, 'cutting') || str_contains($m, 'battery') || str_contains($a, 'CM_')) {
            return 'condition';
        }
        if (str_contains($m, 'standby') || str_contains($m, 'monitoring') || str_contains($a, 'STANDBY')) {
            return 'monitoring';
        }
        if (str_contains($m, 'asset') || str_contains($m, 'aset') || str_contains($m, 'unit')) {
            return 'asset';
        }
        if (str_contains($m, 'fuel') || str_contains($m, 'bbm') || str_contains($m, 'solar')) {
            return 'fuel';
        }
        if (str_contains($m, 'produktivitas') || str_contains($m, 'productivity')) {
            return 'productivity';
        }
        if (str_contains($m, 'biaya') || str_contains($m, 'cost') || str_contains($m, 'expense')) {
            return 'biaya';
        }
        if (str_contains($m, 'people') || str_contains($m, 'kpi') || str_contains($m, 'hrd')) {
            return 'people';
        }
        if (str_contains($m, 'hse') || str_contains($m, 'safety') || str_contains($m, 'incident') || str_contains($m, 'accident') || str_contains($m, 'k3l')) {
            return 'hse';
        }
        if (str_contains($m, 'laporan') || str_contains($m, 'report')) {
            return 'reports';
        }
        if (str_contains($m, 'approval') || str_contains($m, 'persetujuan')) {
            return 'approval';
        }
        if (str_contains($m, 'setting') || str_contains($m, 'pengaturan')) {
            return 'settings';
        }
        return 'wo';
    }

    /**
     * Check if a specific role has read permission ('R') on a given module in the RBAC matrix.
     */
    public static function roleHasReadPermission(string $roleName, string $moduleKey, ?array $matrix = null): bool
    {
        if (strcasecmp($roleName, 'Administrator') === 0 || strcasecmp($roleName, 'admin') === 0) {
            return true;
        }
        if ($matrix === null) {
            require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
            $matrix = AuthMiddleware::getEffectiveMatrix();
        }
        foreach ($matrix as $rKey => $modPerms) {
            if (strcasecmp($rKey, $roleName) === 0) {
                $perm = (string) ($modPerms[$moduleKey] ?? '-');
                return $perm !== '-' && stripos($perm, 'R') !== false;
            }
        }
        return false;
    }

    public static function recipientIds(array $users, ?int $locationId, ?int $actorId, ?string $menuName = null, ?string $actionType = null): array
    {
        require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
        $matrix = AuthMiddleware::getEffectiveMatrix();
        $moduleKey = self::resolveModuleKey($menuName, $actionType);

        $ids = [];
        foreach ($users as $user) {
            if (!(bool) ($user['is_active'] ?? false)) continue;
            $roles = $user['roles'] ?? [];
            if (!is_array($roles)) $roles = [];
            $id = (int) ($user['user_id'] ?? 0);
            if ($id <= 0) continue;

            $hasReadPerm = false;
            foreach ($roles as $r) {
                if (self::roleHasReadPermission($r, $moduleKey, $matrix)) {
                    $hasReadPerm = true;
                    break;
                }
            }

            $isActor = ($actorId !== null && $actorId === $id);
            if (!$hasReadPerm && !$isActor) {
                continue;
            }

            $isGlobalRole = (bool) array_intersect($roles, self::GLOBAL_SCOPE_ROLES);
            $hasNoLocationConstraint = !isset($user['assigned_location_id']) || $user['assigned_location_id'] === null;
            $sameLocation = $locationId !== null
                && isset($user['assigned_location_id'])
                && (int) $user['assigned_location_id'] === $locationId;

            if ($isGlobalRole || $hasNoLocationConstraint || $locationId === null || $sameLocation || $isActor) {
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

    public static function deliverWo(PDO $db, int $notificationId, ?int $locationId, ?int $actorId, ?string $menuName = null, ?string $actionType = null): int
    {
        return self::deliverNotification($db, $notificationId, $locationId, $actorId, $menuName, $actionType);
    }

    public static function deliverNotification(PDO $db, int $notificationId, ?int $locationId = null, ?int $actorId = null, ?string $menuName = null, ?string $actionType = null): int
    {
        if ($menuName === null || $locationId === null) {
            $stmt = $db->prepare('SELECT menu_name, action_type, related_tables_json FROM system_notifications WHERE notification_id = :id');
            $stmt->execute([':id' => $notificationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if ($menuName === null) {
                    $menuName = (string) ($row['menu_name'] ?? '');
                }
                if ($actionType === null) {
                    $actionType = (string) ($row['action_type'] ?? '');
                }
                if ($locationId === null && !empty($row['related_tables_json'])) {
                    $related = json_decode((string)$row['related_tables_json'], true) ?: [];
                    if (isset($related['location_id']) && $related['location_id'] !== null && $related['location_id'] !== '') {
                        $locationId = (int) $related['location_id'];
                    }
                }
            }
        }

        $recipients = self::recipientIds(self::activeUsers($db), $locationId, $actorId, $menuName, $actionType);
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

    public static function syncMissingRecipientsForUser(PDO $db, int $userId, bool $globalScope, ?int $locationId): int
    {
        $userRow = $db->prepare('SELECT u.user_id, u.assigned_location_id, u.is_active, r.role_name
            FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = :id AND u.is_active = 1');
        $userRow->execute([':id' => $userId]);
        $u = $userRow->fetch(PDO::FETCH_ASSOC);
        if (!$u) return 0;

        $roles = [(string)$u['role_name']];
        $extra = $db->prepare('SELECT r.role_name FROM user_roles ur INNER JOIN roles r ON r.role_id = ur.role_id WHERE ur.user_id = :id');
        $extra->execute([':id' => $userId]);
        foreach ($extra->fetchAll(PDO::FETCH_COLUMN) as $er) {
            $roles[] = (string)$er;
        }

        require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
        $matrix = AuthMiddleware::getEffectiveMatrix();

        $unlinkedStmt = $db->prepare(
            'SELECT n.notification_id, n.menu_name, n.action_type, n.related_tables_json
             FROM system_notifications n
             LEFT JOIN notification_recipients nr ON nr.notification_id = n.notification_id AND nr.user_id = :user_id
             WHERE nr.notification_id IS NULL
             ORDER BY n.notification_id DESC LIMIT 100'
        );
        $unlinkedStmt->execute([':user_id' => $userId]);
        $unlinked = $unlinkedStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($unlinked)) return 0;

        $insertStmt = $db->prepare(
            'INSERT IGNORE INTO notification_recipients
             (notification_id, user_id, location_id) VALUES (:notification_id, :user_id, :location_id)'
        );
        $synced = 0;
        $isGlobal = $globalScope || (bool) array_intersect($roles, self::GLOBAL_SCOPE_ROLES);
        $userLoc = $u['assigned_location_id'] !== null ? (int)$u['assigned_location_id'] : null;

        foreach ($unlinked as $n) {
            $modKey = self::resolveModuleKey($n['menu_name'], $n['action_type']);
            $hasReadPerm = false;
            foreach ($roles as $r) {
                if (self::roleHasReadPermission($r, $modKey, $matrix)) {
                    $hasReadPerm = true;
                    break;
                }
            }
            if (!$hasReadPerm) continue;

            $locId = null;
            if (!empty($n['related_tables_json'])) {
                $related = json_decode((string)$n['related_tables_json'], true) ?: [];
                if (isset($related['location_id']) && $related['location_id'] !== null && $related['location_id'] !== '') {
                    $locId = (int)$related['location_id'];
                }
            }

            if ($isGlobal || $userLoc === null || $locId === null || $userLoc === $locId) {
                $insertStmt->execute([
                    ':notification_id' => (int)$n['notification_id'],
                    ':user_id' => $userId,
                    ':location_id' => $locId,
                ]);
                $synced += $insertStmt->rowCount();
            }
        }
        return $synced;
    }

    public static function backfillRecipients(PDO $db): int
    {
        $users = self::activeUsers($db);
        $stmt = $db->query('SELECT notification_id, menu_name, action_type, related_tables_json FROM system_notifications ORDER BY notification_id ASC');
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalInserted = 0;

        $insertStmt = $db->prepare(
            'INSERT IGNORE INTO notification_recipients
             (notification_id, user_id, location_id) VALUES (:notification_id, :user_id, :location_id)'
        );

        foreach ($notifs as $n) {
            $notifId = (int) $n['notification_id'];
            $menuName = (string) ($n['menu_name'] ?? '');
            $actionType = (string) ($n['action_type'] ?? '');
            $locationId = null;
            if (!empty($n['related_tables_json'])) {
                $related = json_decode((string)$n['related_tables_json'], true) ?: [];
                if (isset($related['location_id']) && $related['location_id'] !== null && $related['location_id'] !== '') {
                    $locationId = (int) $related['location_id'];
                }
            }

            $recipients = self::recipientIds($users, $locationId, null, $menuName, $actionType);
            foreach ($recipients as $userId) {
                $insertStmt->execute([
                    ':notification_id' => $notifId,
                    ':user_id' => $userId,
                    ':location_id' => $locationId,
                ]);
                $totalInserted += $insertStmt->rowCount();
            }
        }
        return $totalInserted;
    }

    public static function listForUser(PDO $db, int $userId, ?int $locationId, bool $globalScope, int $limit): array
    {
        self::syncMissingRecipientsForUser($db, $userId, $globalScope, $locationId);

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
