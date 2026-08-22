<?php
declare(strict_types=1);

class AuthMiddleware {
    // RBAC Matrix based on DATA_COMPLETENESS_CHECKLIST.md Section 10.2
    private static array $rbacMatrix = [
        'Administrator' => [
            'dashboard' => 'RCUO', 'monitoring' => 'RCUO', 'asset' => 'RCUO', 'inspection' => 'RCUO', 
            'wo' => 'RCUO', 'pm' => 'RCUO', 'logistics' => 'RCUO', 'condition' => 'RCUO', 
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
        
        $role = $_SESSION['auth_user']['primary_role'] ?? 'Unknown';
        
        if (!isset(self::$rbacMatrix[$role])) {
            self::denyAccess();
        }
        
        $permissions = self::$rbacMatrix[$role][$menu] ?? '-';
        
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
