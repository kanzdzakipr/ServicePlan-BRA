<?php
declare(strict_types=1);

class AuthMiddleware {
    // RBAC Matrix based on DATA_COMPLETENESS_CHECKLIST.md Section 10.2
    private static array $rbacMatrix = [
        'Administrator' => [
            'dashboard' => 'RCUO', 'monitoring' => 'RCUO', 'asset' => 'RCUO', 'inspection' => 'RCUO', 
            'wo' => 'RCUAO', 'pm' => 'RCUO', 'logistics' => 'RCUO', 'condition' => 'RCUO',
            'fuel' => 'RCUO', 'productivity' => 'RCUO', 'biaya' => 'RCUO', 'people' => 'RCUO', 
            'hse' => 'RCUO', 'reports' => 'RCUO', 'approval' => 'RAO', 'settings' => 'RCUO'
        ],
        'Equipment Manager' => [
            'dashboard' => 'RUA', 'monitoring' => 'RUA', 'asset' => 'RUA', 'inspection' => 'RUA', 
            'wo' => 'RCUA', 'pm' => 'RCUA', 'logistics' => 'RA', 'condition' => 'RUA', 
            'fuel' => 'RUA', 'productivity' => 'RUA', 'biaya' => 'RUA', 'people' => 'RCUA', 
            'hse' => 'RCUA', 'reports' => 'RCU', 'approval' => 'RA', 'settings' => '-'
        ],
        'Maintenance Planner' => [
            'dashboard' => 'RU', 'monitoring' => 'RU', 'asset' => 'RU', 'inspection' => 'RU', 
            'wo' => 'RCUA', 'pm' => 'RCUA', 'logistics' => 'RCU', 'condition' => 'RCU', 
            'fuel' => 'RU', 'productivity' => 'RU', 'biaya' => '-', 'people' => 'RU', 
            'hse' => 'R', 'reports' => 'RCU', 'approval' => 'R', 'settings' => '-'
        ],
        'Mekanik Senior' => [
            'dashboard' => 'R', 'monitoring' => 'R', 'asset' => 'R', 'inspection' => 'RC', 
            'wo' => 'RCU', 'pm' => 'R', 'logistics' => 'RC', 'condition' => 'R', 
            'fuel' => 'R', 'productivity' => 'R', 'biaya' => '-', 'people' => 'R', 
            'hse' => 'R', 'reports' => 'R', 'approval' => '-', 'settings' => '-'
        ],
        'Mekanik Junior / Helper' => [
            'dashboard' => 'R', 'monitoring' => 'R', 'asset' => 'R', 'inspection' => 'RC', 
            'wo' => 'RU', 'pm' => 'R', 'logistics' => '-', 'condition' => '-', 
            'fuel' => '-', 'productivity' => 'R', 'biaya' => '-', 'people' => 'R', 
            'hse' => '-', 'reports' => 'R', 'approval' => '-', 'settings' => '-'
        ],
        'Welder / Fabrikator' => [
            'dashboard' => 'R', 'monitoring' => 'R', 'asset' => 'R', 'inspection' => 'RC', 
            'wo' => 'RU', 'pm' => '-', 'logistics' => '-', 'condition' => '-', 
            'fuel' => '-', 'productivity' => 'R', 'biaya' => '-', 'people' => 'R', 
            'hse' => '-', 'reports' => 'R', 'approval' => '-', 'settings' => '-'
        ],
        'Inspector K3L / Safety' => [
            'dashboard' => 'R', 'monitoring' => 'RU', 'asset' => 'R', 'inspection' => 'RCUA', 
            'wo' => 'RC', 'pm' => '-', 'logistics' => '-', 'condition' => 'RCU', 
            'fuel' => 'RCU', 'productivity' => 'R', 'biaya' => '-', 'people' => '-', 
            'hse' => 'RCUA', 'reports' => 'RCU', 'approval' => '-', 'settings' => '-'
        ],
        'Logistic Head' => [
            'dashboard' => 'R', 'monitoring' => 'R', 'asset' => 'R', 'inspection' => '-', 
            'wo' => 'R', 'pm' => 'R', 'logistics' => 'RCUA', 'condition' => 'R', 
            'fuel' => 'RCU', 'productivity' => 'R', 'biaya' => '-', 'people' => '-', 
            'hse' => '-', 'reports' => 'RCU', 'approval' => 'RA', 'settings' => '-'
        ],
        'HRD Manager' => [
            'dashboard' => 'R', 'monitoring' => '-', 'asset' => '-', 'inspection' => '-', 
            'wo' => '-', 'pm' => '-', 'logistics' => '-', 'condition' => '-', 
            'fuel' => '-', 'productivity' => '-', 'biaya' => '-', 'people' => 'RCUA', 
            'hse' => '-', 'reports' => 'RCU', 'approval' => 'RA', 'settings' => '-'
        ],
        'Asset Manager' => [
            'dashboard' => 'R', 'monitoring' => 'RUA', 'asset' => 'RCUA', 'inspection' => '-', 
            'wo' => 'R', 'pm' => '-', 'logistics' => '-', 'condition' => 'RU', 
            'fuel' => 'R', 'productivity' => 'RUA', 'biaya' => 'RCUA', 'people' => 'R', 
            'hse' => 'RA', 'reports' => 'RCU', 'approval' => 'RA', 'settings' => '-'
        ]
    ];

    private static ?array $customRbacMatrix = null;

    public static function clearMatrixCache(): void {
        self::$customRbacMatrix = null;
    }

    public static function getEffectiveMatrix(): array {
        if (self::$customRbacMatrix !== null) {
            return self::$customRbacMatrix;
        }

        $matrix = self::$rbacMatrix;
        try {
            $dbPath = dirname(__DIR__) . '/api/db.php';
            if (file_exists($dbPath)) {
                require_once $dbPath;
                if (class_exists('Database')) {
                    $db = Database::getInstance();
                    $stmt = $db->prepare("SELECT setting_value FROM operational_settings WHERE setting_key = 'cfg_rbac_matrix' LIMIT 1");
                    $stmt->execute();
                    $val = $stmt->fetchColumn();
                    if ($val && is_string($val)) {
                        $customRoles = json_decode($val, true);
                        if (is_array($customRoles)) {
                            $moduleKeysOrder = [
                                'dashboard', 'monitoring', 'asset', 'inspection',
                                'wo', 'pm', 'logistics', 'condition',
                                'fuel', 'productivity', 'biaya', 'people',
                                'hse', 'reports', 'approval', 'settings'
                            ];
                            foreach ($customRoles as $cr) {
                                if (!isset($cr['name']) || !isset($cr['permissions']) || !is_array($cr['permissions'])) {
                                    continue;
                                }
                                $cleanName = preg_replace('/^\d+\.\s*/', '', trim((string)$cr['name']));
                                $matchedKey = null;
                                foreach (array_keys($matrix) as $roleKey) {
                                    if (strcasecmp($roleKey, $cleanName) === 0) {
                                        $matchedKey = $roleKey;
                                        break;
                                    }
                                }
                                if ($matchedKey) {
                                    foreach ($cr['permissions'] as $idx => $perm) {
                                        if (isset($moduleKeysOrder[$idx])) {
                                            $modKey = $moduleKeysOrder[$idx];
                                            $matrix[$matchedKey][$modKey] = str_replace(' ', '', (string)$perm);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // fallback safely to default matrix
        }

        self::$customRbacMatrix = $matrix;
        return self::$customRbacMatrix;
    }

    /**
     * Memastikan user yang sedang login memiliki hak akses tertentu pada sebuah menu/modul.
     * Jika tidak, fungsi ini akan langsung menghentikan eksekusi dan mengembalikan 403 Forbidden.
     * 
     * @param string $menu Kode menu (misal: 'wo', 'assets')
     * @param string $action Kode aksi yang dibutuhkan ('R', 'C', 'U', 'A', 'O')
     */
    public static function checkAccess(string $menu, string $action): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $role = trim((string)($_SESSION['auth_user']['primary_role'] ?? 'Unknown'));

        // Administrator always has full master access
        if (strcasecmp($role, 'Administrator') === 0 || strcasecmp($role, 'admin') === 0) {
            return;
        }

        $effectiveMatrix = self::getEffectiveMatrix();
        $matchedRoleKey = null;
        foreach (array_keys($effectiveMatrix) as $k) {
            if (strcasecmp($k, $role) === 0) {
                $matchedRoleKey = $k;
                break;
            }
        }
        
        if (!$matchedRoleKey) {
            self::denyAccess();
        }
        
        $permissions = $effectiveMatrix[$matchedRoleKey][$menu] ?? '-';
        
        if ($permissions === '-' || strpos($permissions, $action) === false) {
            self::denyAccess();
        }
    }
    
    private static function denyAccess(): void {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki hak akses (RBAC) untuk melakukan operasi ini.'
        ]);
        exit;
    }
}
